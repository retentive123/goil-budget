<?php

namespace App\Services;

use App\Models\BudgetVersion;
use App\Models\ApprovalStage;
use App\Models\ApprovalDecision;
use App\Models\LineItemApproval;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class ApprovalService
{
    public function __construct(
        protected NotificationService $notifier
    ) {}

    public function currentStage(BudgetVersion $version): ?ApprovalStage
    {
        $decidedStageIds = $version->approvalDecisions()
                                   ->where('decision', 'approved')
                                   ->pluck('approval_stage_id');

        return ApprovalStage::where('is_active', true)
                            ->whereNotIn('id', $decidedStageIds)
                            ->orderBy('order')
                            ->first();
    }

    public function canCurrentUserDecide(BudgetVersion $version): bool
    {
        $user  = auth()->user();
        $stage = $this->currentStage($version);

        if (!$stage) return false;

        if (!in_array($version->status, [
            BudgetVersion::STATUS_SUBMITTED,
            BudgetVersion::STATUS_UNDER_REVIEW,
        ])) return false;

        // Must have the stage's role
        if (!$user->hasRole($stage->role_name)) return false;

        // Enforce scope — 'own' means only their department
        $role = Role::where('name', $stage->role_name)->first();
        if ($role && $role->scope === 'own') {
            if ($user->department_id !== $version->department_id) {
                return false;
            }
        }

         // Segregation of duties
        if (\App\Services\SegregationService::enabled()) {
            if ($version->submitted_by && $version->submitted_by === $user->id) {
                return false;
            }
        }

        return true;
    }

    // Get role config for the current user's approver role on this version
    public function currentRoleConfig(BudgetVersion $version): ?Role
    {
        $stage = $this->currentStage($version);
        if (!$stage) return null;

        return Role::where('name', $stage->role_name)->first();
    }

    public function decide(
        BudgetVersion $version,
        string        $decision,
        string        $comments,
        array         $lineItemDecisions = []
    ): void {

        // ── Segregation of duties ──
        \App\Services\SegregationService::check(
            $version->submitted_by,
            'approve this budget'
        );

        DB::transaction(function () use ($version, $decision, $comments, $lineItemDecisions) {

            $stage = $this->currentStage($version);

            if (!$stage) {
                throw new \Exception('No pending approval stage found.');
            }

            $approvalDecision = ApprovalDecision::create([
                'budget_version_id' => $version->id,
                'approval_stage_id' => $stage->id,
                'decided_by'        => auth()->id(),
                'decision'          => $decision,
                'comments'          => $comments,
                'decided_at'        => now(),
            ]);

            foreach ($lineItemDecisions as $itemId => $itemData) {
                if (empty($itemData['status'])) continue;

                LineItemApproval::create([
                    'approval_decision_id' => $approvalDecision->id,
                    'budget_line_item_id'  => $itemId,
                    'status'               => $itemData['status'],
                    'approved_amount'      => $itemData['approved_amount'] ?? null,
                    'comments'             => $itemData['comments']        ?? null,
                ]);
            }

            if ($decision === 'rejected') {
                $this->handleRejection($version, $comments);
                return;
            }

            $nextStage = $stage->nextStage();

            if ($nextStage) {
                $version->update(['status' => BudgetVersion::STATUS_UNDER_REVIEW]);
                $this->notifier->notifyApprovers($version, $nextStage);
            } else {
                $version->update(['status' => BudgetVersion::STATUS_APPROVED]);
                $this->notifier->notifyDepartment($version, 'approved');
            }
        });
    }

    private function handleRejection(BudgetVersion $version, string $comments): void
    {
        $version->update(['status' => BudgetVersion::STATUS_REJECTED]);
        $this->notifier->notifyDepartment($version, 'rejected', $comments);
    }

    public function approvalProgress(BudgetVersion $version): array
    {
        $stages    = ApprovalStage::orderBy('order')->get();
        $decisions = $version->approvalDecisions()
                             ->with('stage','decidedBy')
                             ->get()
                             ->keyBy('approval_stage_id');

        $currentStage = $this->currentStage($version);
        $progress     = [];

        foreach ($stages as $stage) {
            $decision   = $decisions->get($stage->id);
            $roleConfig = Role::where('name', $stage->role_name)->first();

            $progress[] = [
                'stage'       => $stage,
                'decision'    => $decision,
                'is_active'   => $stage->is_active,
                'role_config' => $roleConfig,
                'status'      => $decision
                    ? $decision->decision
                    : ($currentStage?->id === $stage->id ? 'pending' : 'waiting'),
            ];
        }

        return $progress;
    }
}
