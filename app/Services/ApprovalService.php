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

    public function approvalRoute(BudgetVersion $version): string
    {
        $version->loadMissing('department', 'subsidiary');
        return $version->department?->approval_route
            ?? $version->subsidiary?->approval_route
            ?? 'gceo';
    }

    public function currentStage(BudgetVersion $version): ?ApprovalStage
    {
        $decidedStageIds = $version->approvalDecisions()
                                   ->where('decision', 'approved')
                                   ->pluck('approval_stage_id');

        $route = $this->approvalRoute($version);

        return ApprovalStage::where('is_active', true)
                            ->whereNotIn('id', $decidedStageIds)
                            ->where(function ($q) use ($route) {
                                $q->whereNull('route_key')
                                  ->orWhere('route_key', $route);
                            })
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

        // Enforce scope — 'own' means only their assigned entity
        $role = Role::where('name', $stage->role_name)->first();
        if ($role && $role->scope === 'own') {
            $user->loadMissing('approvalDepartments');
            if ($version->subsidiary_id !== null) {
                // Subsidiary budget — user must be assigned to that subsidiary
                if ($user->subsidiary_id !== $version->subsidiary_id) {
                    return false;
                }
            } else {
                // Dept budget — must be in the user's approval dept list
                if (!in_array($version->department_id, $user->approvalDepartmentIds())) {
                    return false;
                }
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

    public function pendingForUser(): int
    {
        $user = auth()->user();
        if (!$user) return 0;

        $roleNames = $user->roles->pluck('name')->toArray();
        if (empty($roleNames)) return 0;

        $placeholders = implode(',', array_fill(0, count($roleNames), '?'));

        $query = BudgetVersion::whereIn('status', [
                BudgetVersion::STATUS_SUBMITTED,
                BudgetVersion::STATUS_UNDER_REVIEW,
            ])
            ->whereRaw("(
                SELECT role_name FROM approval_stages
                WHERE is_active = 1
                AND (
                    route_key IS NULL
                    OR route_key = COALESCE(
                        (SELECT approval_route FROM departments WHERE id = budget_versions.department_id),
                        'gceo'
                    )
                )
                AND id NOT IN (
                    SELECT approval_stage_id FROM approval_decisions
                    WHERE budget_version_id = budget_versions.id
                    AND decision = 'approved'
                )
                ORDER BY `order` ASC LIMIT 1
            ) IN ({$placeholders})", $roleNames);

        // For users with 'own' scope: restrict to their entity
        $rolesWithOwnScope = \Spatie\Permission\Models\Role::whereIn('name', $roleNames)
            ->where('scope', 'own')->pluck('name');

        if ($rolesWithOwnScope->isNotEmpty()) {
            if ($user->subsidiary_id) {
                $query->where(function ($q) use ($user) {
                    $q->where('subsidiary_id', $user->subsidiary_id)
                      ->orWhereIn('department_id', $user->approvalDepartmentIds());
                });
            } else {
                $deptIds = $user->loadMissing('approvalDepartments')->approvalDepartmentIds();
                if (!empty($deptIds)) {
                    $query->whereIn('department_id', $deptIds);
                }
            }
        }

        return $query->count();
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

        $version->loadMissing('period');
        $entryMode = $version->period->entry_mode ?? 'quarterly';

        DB::transaction(function () use ($version, $decision, $comments, $lineItemDecisions, $entryMode) {

            $stage = $this->currentStage($version);

            if (!$stage) {
                throw new \Exception('No pending approval stage found.');
            }

            // Remove any stale decision for this version+stage (e.g. the version
            // was reopened after rejection and is now going through approval again).
            // The unique constraint on (budget_version_id, approval_stage_id) means
            // we must clear the old row before inserting the new one.
            ApprovalDecision::where('budget_version_id', $version->id)
                ->where('approval_stage_id', $stage->id)
                ->delete(); // cascade removes child line_item_approvals automatically

            $approvalDecision = ApprovalDecision::create([
                'budget_version_id' => $version->id,
                'approval_stage_id' => $stage->id,
                'decided_by'        => auth()->id(),
                'decision'          => $decision,
                'comments'          => $comments,
                'decided_at'        => now(),
            ]);

            $spreadQuarter = function (float $total): array {
                $third = round($total / 3, 2);
                return [$third, $third, round($total - $third * 2, 2)];
            };

            foreach ($lineItemDecisions as $itemId => $itemData) {
                if (empty($itemData['status'])) continue;

                // Convert period_amounts to m1-m12 for storage
                $mAmounts = array_fill_keys([
                    'm1_amount', 'm2_amount', 'm3_amount', 'm4_amount',
                    'm5_amount', 'm6_amount', 'm7_amount', 'm8_amount',
                    'm9_amount', 'm10_amount', 'm11_amount', 'm12_amount',
                ], null);

                $pa = $itemData['period_amounts'] ?? [];
                if (in_array($itemData['status'], ['reduced', 'increased']) && !empty($pa)) {
                    if ($entryMode === 'monthly') {
                        foreach (range(1, 12) as $mn) {
                            $mAmounts["m{$mn}_amount"] = (float) ($pa["m{$mn}"] ?? 0);
                        }
                    } else {
                        [$m1, $m2, $m3]    = $spreadQuarter((float) ($pa['q1'] ?? 0));
                        [$m4, $m5, $m6]    = $spreadQuarter((float) ($pa['q2'] ?? 0));
                        [$m7, $m8, $m9]    = $spreadQuarter((float) ($pa['q3'] ?? 0));
                        [$m10, $m11, $m12] = $spreadQuarter((float) ($pa['q4'] ?? 0));
                        $mAmounts = [
                            'm1_amount'  => $m1,  'm2_amount'  => $m2,  'm3_amount'  => $m3,
                            'm4_amount'  => $m4,  'm5_amount'  => $m5,  'm6_amount'  => $m6,
                            'm7_amount'  => $m7,  'm8_amount'  => $m8,  'm9_amount'  => $m9,
                            'm10_amount' => $m10, 'm11_amount' => $m11, 'm12_amount' => $m12,
                        ];
                    }
                }

                LineItemApproval::create(array_merge([
                    'approval_decision_id' => $approvalDecision->id,
                    'budget_line_item_id'  => $itemId,
                    'status'               => $itemData['status'],
                    'approved_amount'      => $itemData['approved_amount'] ?? null,
                    'comments'             => $itemData['comments']        ?? null,
                ], $mAmounts));
            }

            if ($decision === 'rejected') {
                $this->handleRejection($version, $comments);
                return;
            }

            $route     = $this->approvalRoute($version);
            $nextStage = $stage->nextStage($route);

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
        $route = $this->approvalRoute($version);

        $stages = ApprovalStage::orderBy('order')
            ->where(function ($q) use ($route) {
                $q->whereNull('route_key')
                  ->orWhere('route_key', $route);
            })
            ->get();

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
