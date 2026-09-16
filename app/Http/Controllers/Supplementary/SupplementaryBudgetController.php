<?php

namespace App\Http\Controllers\Supplementary;

use App\Http\Controllers\Controller;
use App\Models\SupplementaryBudget;
use App\Models\BudgetVersion;
use App\Models\BudgetLineItem;
use App\Models\BudgetPeriod;
use App\Models\Department;
use App\Models\ApprovalStage;
use App\Models\SystemSetting;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplementaryBudgetController extends Controller
{
    public function __construct(protected NotificationService $notifier) {}

    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = SupplementaryBudget::with(
                'department','accountCode.category',
                'requestedBy','reviewedBy','approvedBy','period'
            )
            ->when(
                !$user->hasAnyRole(['finance_reviewer','bdu_admin','super_admin']),
                fn($q) => $q->where('department_id', $user->department_id)
            )
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->period_id, fn($q) => $q->where('budget_period_id', $request->period_id))
            ->orderByDesc('submitted_at')
            ->orderByDesc('id');

        $supplementaries = $query->paginate(50);
        $periods         = BudgetPeriod::orderByDesc('year')->get();

        // Group current page into batches (null batch_id → each record is its own batch)
        $batches = $supplementaries->getCollection()
            ->groupBy(fn($s) => $s->batch_id ?? ('solo_' . $s->id))
            ->values();

        return view('supplementary.index', compact('supplementaries','batches','periods'));
    }

    public function create(Request $request)
    {
        $user          = auth()->user();
        $currentPeriod = $request->period_id
            ? BudgetPeriod::find($request->period_id)
            : BudgetPeriod::current();

        $department = $request->department_id
            ? Department::find($request->department_id)
            : $user->department;

        if (!$currentPeriod || !$department) {
            return redirect()->route('supplementary.index')
                ->with('error', 'No active period or department found.');
        }

        // Get approved budget version
        $version = BudgetVersion::with('lineItems.accountCode.category')
            ->where('budget_period_id', $currentPeriod->id)
            ->where('department_id', $department->id)
            ->where('status', 'approved')
            ->orderByDesc('version_number')
            ->first();

        if (!$version) {
            return redirect()->route('supplementary.index')
                ->with('error',
                    'No approved budget found for this department. ' .
                    'Supplementary requests can only be made against an approved budget.'
                );
        }

        // Pre-select line item if coming from over-budget block
        $preselectedItemId = $request->line_item_id;

        // Get existing actuals per line item for context
        $actualsPerItem = \App\Models\BudgetActual::where('budget_period_id', $currentPeriod->id)
            ->where('department_id', $department->id)
            ->where('status', 'confirmed')
            ->get()
            ->groupBy('budget_line_item_id')
            ->map(fn($g) => $g->sum('amount'));

        return view('supplementary.create', compact(
            'currentPeriod','department','version',
            'actualsPerItem','preselectedItemId'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'budget_period_id'              => ['required','exists:budget_periods,id'],
            'department_id'                 => ['required','exists:departments,id'],
            'justification'                 => ['required','string','min:20','max:2000'],
            'supporting_evidence'           => ['nullable','string','max:2000'],
            'items'                         => ['required','array','min:1'],
            'items.*.budget_line_item_id'   => ['required','exists:budget_line_items,id'],
            'items.*.requested_amount'      => ['required','numeric','min:1'],
        ]);

        // Ensure the requester can only file against their own department
        $user = auth()->user();
        if (!$user->hasAnyRole(['finance_reviewer','bdu_admin','super_admin'])
            && (int) $request->department_id !== (int) $user->department_id) {
            abort(403, 'You can only submit supplementary budgets for your own department.');
        }

        // Resolve the dept's approved version and collect valid line item IDs
        $approvedVersion = BudgetVersion::where('department_id', $request->department_id)
            ->where('budget_period_id', $request->budget_period_id)
            ->where('status', 'approved')
            ->first();

        if (!$approvedVersion) {
            return back()->withInput()->with('error', 'No approved budget version found for this department and period.');
        }

        $validItemIds = $approvedVersion->lineItems()->pluck('id')->toArray();

        // Check for existing pending requests before creating anything
        $blockErrors = [];
        foreach ($request->items as $data) {
            if (!in_array((int) $data['budget_line_item_id'], $validItemIds)) {
                return back()->withInput()->with('error', 'One or more selected items do not belong to your approved budget.');
            }

            $lineItem = BudgetLineItem::with('accountCode')->find($data['budget_line_item_id']);
            $existing = SupplementaryBudget::where('budget_line_item_id', $lineItem->id)
                ->whereIn('status', ['draft','submitted','under_review'])
                ->first();
            if ($existing) {
                $blockErrors[] = "A pending request already exists for {$lineItem->accountCode->code} — wait for it to be processed.";
            }
        }

        if (!empty($blockErrors)) {
            return back()->withInput()->with('error', implode(' ', $blockErrors));
        }

        $batchId = (string) \Illuminate\Support\Str::uuid();
        $created = [];
        DB::transaction(function () use ($request, $batchId, &$created) {
            foreach ($request->items as $data) {
                $lineItem = BudgetLineItem::with('accountCode')->find($data['budget_line_item_id']);
                $supp = SupplementaryBudget::create([
                    'batch_id'            => $batchId,
                    'budget_period_id'    => $request->budget_period_id,
                    'department_id'       => $request->department_id,
                    'budget_line_item_id' => $lineItem->id,
                    'account_code_id'     => $lineItem->account_code_id,
                    'line_type'           => $lineItem->line_type,
                    'original_amount'     => $lineItem->total_amount,
                    'requested_amount'    => $data['requested_amount'],
                    'justification'       => $request->justification,
                    'supporting_evidence' => $request->supporting_evidence,
                    'status'              => 'submitted',
                    'requested_by'        => auth()->id(),
                    'submitted_at'        => now(),
                ]);
                $created[] = $supp->fresh()->load('department','accountCode','period','requestedBy');
            }
        });

        $this->notifyBasedOnMode($created);

        \App\Services\AuditLogger::record(
            'supplementary_submitted', 'budget', 'created',
            [
                'subject_label' => count($created) . ' supplementary request(s) submitted — GHS ' .
                                   number_format(array_sum(array_column($request->items, 'requested_amount')), 2),
                'severity'      => 'info',
            ]
        );

        $count = count($created);
        return redirect()->route('supplementary.index')
            ->with('success',
                "{$count} supplementary budget request(s) submitted. Finance has been notified."
            );
    }

    public function show(SupplementaryBudget $supplementary)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['finance_reviewer', 'bdu_admin', 'super_admin'])) {
            abort_unless($supplementary->department_id === $user->department_id, 403);
        }

        $supplementary->load(
            'department','accountCode.category','period',
            'lineItem','requestedBy','reviewedBy','approvedBy'
        );

        // Get actuals for context
        $ytdActual = \App\Models\BudgetActual::where('budget_line_item_id', $supplementary->budget_line_item_id)
            ->where('status','confirmed')->sum('amount');

        return view('supplementary.show', compact('supplementary','ytdActual'));
    }

    // Finance review — approve
    public function approve(Request $request, SupplementaryBudget $supplementary)
    {
        $request->validate([
            'approved_amount' => ['required','numeric','min:1'],
            'review_notes'    => ['nullable','string','max:1000'],
        ]);

        if (!in_array($supplementary->status, ['submitted','under_review'])) {
            return back()->with('error', 'This request cannot be approved in its current state.');
        }

        \App\Services\SegregationService::check(
            $supplementary->requested_by,
            'approve this supplementary budget request'
        );

        DB::transaction(function () use ($request, $supplementary) {
            $supplementary->update([
                'status'          => 'approved',
                'approved_amount' => $request->approved_amount,
                'reviewed_by'     => auth()->id(),
                'approved_by'     => auth()->id(),
                'reviewed_at'     => now(),
                'approved_at'     => now(),
            ]);

            // ── DO NOT touch q1-q4_amount or total_amount on the line item ──
            // The original approved budget must remain untouched.
            // Supplementary is tracked ONLY in the supplementary_budgets table
            // and summed on-demand wherever an "effective budget" is needed.

            $this->notifyDepartmentOfDecision($supplementary, 'approved', $request->review_notes);

            \App\Services\AuditLogger::record(
                'supplementary_approved', 'budget', 'approved',
                [
                    'subject_label' => "Supplementary: {$supplementary->accountCode->code}",
                    'new_values'    => ['approved_amount' => $request->approved_amount],
                    'severity'      => 'info',
                ]
            );
        });

        return redirect()->route('supplementary.pending')
            ->with('success',
                "Supplementary budget approved. GHS " . number_format($request->approved_amount, 2) .
                " is now available as additional budget for this line."
            );
    }

    // Finance review — reject
    public function reject(Request $request, SupplementaryBudget $supplementary)
    {
        $request->validate([
            'rejection_reason' => ['required','string','min:10','max:1000'],
        ]);

        if (!in_array($supplementary->status, ['submitted','under_review'])) {
            return back()->with('error', 'This request cannot be rejected in its current state.');
        }

        // ── Segregation ──
        \App\Services\SegregationService::check(
            $supplementary->requested_by,
            'reject this supplementary budget request'
        );

        $supplementary->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'reviewed_by'      => auth()->id(),
            'reviewed_at'      => now(),
        ]);

        $this->notifyDepartmentOfDecision($supplementary, 'rejected', $request->rejection_reason);

        \App\Services\AuditLogger::record(
            'supplementary_rejected', 'budget', 'rejected',
            [
                'subject_label' => "Supplementary: {$supplementary->accountCode->code}",
                'meta'          => ['reason' => $request->rejection_reason],
                'severity'      => 'warning',
            ]
        );

        return redirect()->route('supplementary.pending')
            ->with('success', 'Supplementary budget request rejected. Department notified.');
    }

    public function destroyBatch(string $batchId)
    {
        $user  = auth()->user();
        $items = SupplementaryBudget::where('batch_id', $batchId)
            ->when(
                !$user->hasAnyRole(['finance_reviewer', 'bdu_admin', 'super_admin']),
                fn($q) => $q->where('department_id', $user->department_id)
            )
            ->whereIn('status', ['submitted','under_review','draft'])
            ->get();

        if ($items->isEmpty()) {
            return back()->with('error', 'No deletable items found for this batch.');
        }

        $count = $items->count();
        $dept  = $items->first()->load('department')->department->name;

        SupplementaryBudget::where('batch_id', $batchId)
            ->whereIn('status', ['submitted','under_review','draft'])
            ->delete();

        \App\Services\AuditLogger::record(
            'supplementary_batch_deleted', 'budget', 'deleted',
            [
                'subject_label' => "Supplementary batch deleted: {$count} item(s) — {$dept}",
                'severity'      => 'warning',
            ]
        );

        return back()->with('success', "{$count} supplementary request(s) deleted.");
    }

    public function destroy(SupplementaryBudget $supplementary)
    {
        if (!in_array($supplementary->status, ['submitted','under_review','draft'])) {
            return back()->with('error', 'Only pending requests can be deleted.');
        }

        $code = $supplementary->accountCode->code ?? '—';

        $supplementary->delete();

        \App\Services\AuditLogger::record(
            'supplementary_deleted', 'budget', 'deleted',
            [
                'subject_label' => "Supplementary request deleted: {$code}",
                'severity'      => 'warning',
            ]
        );

        return back()->with('success', "Supplementary request for {$code} has been deleted.");
    }

    public function approveBatch(Request $request, string $batchId)
    {
        $request->validate([
            'review_notes' => ['nullable','string','max:1000'],
            'amounts'      => ['nullable','array'],
            'amounts.*'    => ['nullable','numeric','min:1'],
        ]);

        $user  = auth()->user();
        $items = SupplementaryBudget::with('accountCode','department','period','requestedBy')
            ->where('batch_id', $batchId)
            ->when(
                !$user->hasAnyRole(['finance_reviewer', 'bdu_admin', 'super_admin']),
                fn($q) => $q->where('department_id', $user->department_id)
            )
            ->whereIn('status', ['submitted','under_review'])
            ->get();

        if ($items->isEmpty()) {
            return back()->with('error', 'No pending items found for this batch.');
        }

        foreach ($items as $item) {
            \App\Services\SegregationService::check($item->requested_by, 'approve this supplementary budget request');
        }

        $amounts = $request->amounts ?? [];

        DB::transaction(function () use ($request, $items, $amounts) {
            foreach ($items as $item) {
                // Use the value entered in the table; fall back to requested_amount
                $approvedAmt = (isset($amounts[$item->id]) && (float)$amounts[$item->id] > 0)
                    ? (float)$amounts[$item->id]
                    : $item->requested_amount;

                $item->update([
                    'status'          => 'approved',
                    'approved_amount' => $approvedAmt,
                    'reviewed_by'     => auth()->id(),
                    'approved_by'     => auth()->id(),
                    'reviewed_at'     => now(),
                    'approved_at'     => now(),
                ]);
                $this->notifyDepartmentOfDecision($item, 'approved', $request->review_notes);
            }
        });

        $count = $items->count();
        \App\Services\AuditLogger::record('supplementary_batch_approved', 'budget', 'approved', [
            'subject_label' => "Batch approved: {$count} item(s) — " . $items->first()->department->name,
            'severity'      => 'info',
        ]);

        return back()->with('success', "{$count} supplementary request(s) approved.");
    }

    public function rejectBatch(Request $request, string $batchId)
    {
        $request->validate(['rejection_reason' => ['required','string','min:10','max:1000']]);

        $items = SupplementaryBudget::with('accountCode','department','period','requestedBy')
            ->where('batch_id', $batchId)
            ->whereIn('status', ['submitted','under_review'])
            ->get();

        if ($items->isEmpty()) {
            return back()->with('error', 'No pending items found for this batch.');
        }

        foreach ($items as $item) {
            \App\Services\SegregationService::check($item->requested_by, 'reject this supplementary budget request');
        }

        DB::transaction(function () use ($request, $items) {
            foreach ($items as $item) {
                $item->update([
                    'status'           => 'rejected',
                    'rejection_reason' => $request->rejection_reason,
                    'reviewed_by'      => auth()->id(),
                    'reviewed_at'      => now(),
                ]);
                $this->notifyDepartmentOfDecision($item, 'rejected', $request->rejection_reason);
            }
        });

        $count = $items->count();
        \App\Services\AuditLogger::record('supplementary_batch_rejected', 'budget', 'rejected', [
            'subject_label' => "Batch rejected: {$count} item(s) — " . $items->first()->department->name,
            'severity'      => 'warning',
        ]);

        return back()->with('success', "{$count} supplementary request(s) rejected.");
    }

    public function pending()
    {
        $user = auth()->user();
        $mode = SystemSetting::get('supplementary_approval_mode', 'finance_final');

        $query = SupplementaryBudget::with(
                'department','accountCode.category','requestedBy','period'
            )
            ->orderByDesc('submitted_at')
            ->orderByDesc('id');

        if ($mode === 'dept_head_final') {
            // Dept head sees items from their own department awaiting dept-head approval
            if (!$user->hasAnyRole(['finance_reviewer','bdu_admin','super_admin'])) {
                $query->where('department_id', $user->department_id)
                      ->where(function ($q) {
                          $q->whereNull('dept_head_status')
                            ->orWhere('dept_head_status', 'pending');
                      });
            } else {
                // Finance/admin: see everything still pending
                $query->whereIn('status', ['submitted','under_review']);
            }
        } elseif ($mode === 'full_stages') {
            // Determine which role the current user holds among approval stages
            $stages     = ApprovalStage::ordered();
            $userRoles  = $user->roles->pluck('name')->toArray();
            $myStages   = $stages->filter(fn($s) => in_array($s->role_name, $userRoles));

            if ($myStages->isNotEmpty()) {
                $myOrders = $myStages->pluck('order')->toArray();
                $query->whereIn('status', ['submitted','under_review'])
                      ->whereIn('current_stage_order', $myOrders);
            } elseif ($user->hasAnyRole(['finance_reviewer','bdu_admin','super_admin'])) {
                $query->whereIn('status', ['submitted','under_review']);
            } else {
                $query->whereRaw('1=0'); // no pending items for this user
            }
        } else {
            // finance_final: default
            $query->whereIn('status', ['submitted','under_review']);
        }

        $pending = $query->paginate(60);
        $batches = $pending->getCollection()
            ->groupBy(fn($s) => $s->batch_id ?? ('solo_' . $s->id))
            ->values();

        return view('supplementary.pending', compact('pending', 'batches', 'mode'));
    }

    // ── Dept-head approval (dept_head_final mode) ─────────────────────────
    public function deptHeadApprove(Request $request, SupplementaryBudget $supplementary)
    {
        $request->validate([
            'approved_amount' => ['required','numeric','min:1'],
            'dept_head_notes' => ['nullable','string','max:1000'],
        ]);

        abort_unless(SystemSetting::get('supplementary_approval_mode') === 'dept_head_final', 403);

        $user = auth()->user();
        abort_unless($supplementary->department_id === $user->department_id
            || $user->hasAnyRole(['bdu_admin','super_admin']), 403);

        \App\Services\SegregationService::check(
            $supplementary->requested_by,
            'approve this supplementary budget request'
        );

        DB::transaction(function () use ($request, $supplementary) {
            $supplementary->update([
                'status'            => 'approved',
                'approved_amount'   => $request->approved_amount,
                'dept_head_status'  => 'approved',
                'dept_head_by'      => auth()->id(),
                'dept_head_at'      => now(),
                'dept_head_notes'   => $request->dept_head_notes,
                'approved_by'       => auth()->id(),
                'approved_at'       => now(),
            ]);

            $this->notifyDepartmentOfDecision($supplementary, 'approved', $request->dept_head_notes);

            \App\Services\AuditLogger::record(
                'supplementary_approved', 'budget', 'approved',
                [
                    'subject_label' => "Supplementary (dept-head): {$supplementary->accountCode->code}",
                    'new_values'    => ['approved_amount' => $request->approved_amount],
                    'severity'      => 'info',
                ]
            );
        });

        return redirect()->route('supplementary.pending')
            ->with('success',
                "Supplementary budget approved. GHS " . number_format($request->approved_amount, 2) .
                " added to the department's budget."
            );
    }

    public function deptHeadReject(Request $request, SupplementaryBudget $supplementary)
    {
        $request->validate(['rejection_reason' => ['required','string','min:10','max:1000']]);

        abort_unless(SystemSetting::get('supplementary_approval_mode') === 'dept_head_final', 403);

        $user = auth()->user();
        abort_unless($supplementary->department_id === $user->department_id
            || $user->hasAnyRole(['bdu_admin','super_admin']), 403);

        $supplementary->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'dept_head_status' => 'rejected',
            'dept_head_by'     => auth()->id(),
            'dept_head_at'     => now(),
            'dept_head_notes'  => $request->rejection_reason,
        ]);

        $this->notifyDepartmentOfDecision($supplementary, 'rejected', $request->rejection_reason);

        \App\Services\AuditLogger::record(
            'supplementary_rejected', 'budget', 'rejected',
            [
                'subject_label' => "Supplementary (dept-head): {$supplementary->accountCode->code}",
                'meta'          => ['reason' => $request->rejection_reason],
                'severity'      => 'warning',
            ]
        );

        return redirect()->route('supplementary.pending')
            ->with('success', 'Supplementary budget request rejected. Department notified.');
    }

    // ── Stage advance (full_stages mode) ──────────────────────────────────
    public function stageApprove(Request $request, SupplementaryBudget $supplementary)
    {
        $request->validate([
            'approved_amount' => ['required','numeric','min:1'],
            'review_notes'    => ['nullable','string','max:1000'],
        ]);

        abort_unless(SystemSetting::get('supplementary_approval_mode') === 'full_stages', 403);

        $stages    = ApprovalStage::ordered();
        $userRoles = auth()->user()->roles->pluck('name')->toArray();

        // Confirm this user is at the current stage
        $currentStage = $stages->firstWhere('order', $supplementary->current_stage_order);
        abort_unless($currentStage && in_array($currentStage->role_name, $userRoles), 403);

        \App\Services\SegregationService::check(
            $supplementary->requested_by,
            'approve this supplementary budget request'
        );

        $nextStage = $stages->first(fn($s) => $s->order > $currentStage->order);

        DB::transaction(function () use ($request, $supplementary, $currentStage, $nextStage) {
            if ($nextStage) {
                // Advance to next stage
                $supplementary->update([
                    'current_stage_order' => $nextStage->order,
                    'status'              => 'under_review',
                ]);
                $this->notifyStage($supplementary, $nextStage);
            } else {
                // Final stage — approve
                $supplementary->update([
                    'status'          => 'approved',
                    'approved_amount' => $request->approved_amount,
                    'approved_by'     => auth()->id(),
                    'approved_at'     => now(),
                    'reviewed_by'     => auth()->id(),
                    'reviewed_at'     => now(),
                ]);
                $this->notifyDepartmentOfDecision($supplementary, 'approved', $request->review_notes);
            }

            \App\Services\AuditLogger::record(
                'supplementary_stage_approved', 'budget', 'approved',
                [
                    'subject_label' => "Supplementary stage {$currentStage->order}: {$supplementary->accountCode->code}",
                    'severity'      => 'info',
                ]
            );
        });

        $msg = $nextStage
            ? "Supplementary budget approved at stage {$currentStage->name}. Forwarded to {$nextStage->name}."
            : "Supplementary budget fully approved.";

        return redirect()->route('supplementary.pending')->with('success', $msg);
    }

    private function notifyBasedOnMode(array $items): void
    {
        $mode = SystemSetting::get('supplementary_approval_mode', 'finance_final');

        if ($mode === 'dept_head_final') {
            // Mark dept_head_status = pending and notify dept-level approvers
            foreach ($items as $supp) {
                $supp->update(['dept_head_status' => 'pending']);
            }
            // Notify managers/first-stage approvers in same department
            $this->notifyDeptApproversOfBatch($items);
        } elseif ($mode === 'full_stages') {
            $firstStage = ApprovalStage::ordered()->first();
            if ($firstStage) {
                foreach ($items as $supp) {
                    $supp->update(['current_stage_order' => $firstStage->order]);
                    $this->notifyStage($supp, $firstStage);
                }
            } else {
                // No stages configured — fall back to finance
                $this->notifyFinanceOfBatch($items);
            }
        } else {
            // finance_final (default)
            $this->notifyFinanceOfBatch($items);
        }
    }

    private function notifyDeptApproversOfBatch(array $items): void
    {
        if (empty($items)) return;

        $first  = $items[0];
        $stages = ApprovalStage::ordered();

        // Determine who should approve: users in same dept with the first-stage role
        $firstStageRole = $stages->first()?->role_name ?? 'finance_reviewer';
        $approvers = \App\Models\User::role($firstStageRole)
            ->where('is_active', true)
            ->get();

        // If no dept-level approvers found, fall back to finance
        if ($approvers->isEmpty()) {
            $this->notifyFinanceOfBatch($items);
            return;
        }

        $dept   = $first->department->name;
        $period = $first->period->name;
        $count  = count($items);
        $total  = array_sum(array_map(fn($s) => $s->requested_amount, $items));

        foreach ($approvers as $user) {
            \App\Models\BudgetNotification::create([
                'user_id'         => $user->id,
                'type'            => 'supplementary_pending',
                'subject'         => "Supplementary budget awaiting your approval — {$dept} ({$count} item(s))",
                'message'         => "{$dept} has submitted {$count} supplementary budget request(s) "
                                   . "totalling GHS " . number_format($total, 2)
                                   . " for {$period}. As department approver, your approval is needed.",
                'notifiable_id'   => $first->id,
                'notifiable_type' => SupplementaryBudget::class,
            ]);
        }
    }

    private function notifyStage(SupplementaryBudget $supp, ApprovalStage $stage): void
    {
        $approvers = \App\Models\User::role($stage->role_name)->where('is_active', true)->get();

        foreach ($approvers as $user) {
            \App\Models\BudgetNotification::create([
                'user_id'         => $user->id,
                'type'            => 'supplementary_pending',
                'subject'         => "Supplementary budget — stage {$stage->order}: {$supp->accountCode->code}",
                'message'         => "A supplementary budget request for {$supp->accountCode->name} "
                                   . "(GHS " . number_format($supp->requested_amount, 2) . ") "
                                   . "is now at {$stage->name} and requires your review.",
                'notifiable_id'   => $supp->id,
                'notifiable_type' => SupplementaryBudget::class,
            ]);
        }
    }

    private function notifyFinanceOfBatch(array $items): void
    {
        if (empty($items)) return;

        $financeUsers = \App\Models\User::role('finance_reviewer')
                                        ->where('is_active', true)->get();

        $first  = $items[0];
        $dept   = $first->department->name;
        $period = $first->period->name;
        $count  = count($items);
        $total  = array_sum(array_map(fn($s) => $s->requested_amount, $items));
        $codes  = implode(', ', array_map(fn($s) => $s->accountCode->code, $items));

        foreach ($financeUsers as $user) {
            \App\Models\BudgetNotification::create([
                'user_id'         => $user->id,
                'type'            => 'supplementary_pending',
                'subject'         => "Supplementary budget request — {$dept} ({$count} item(s))",
                'message'         => "{$dept} has submitted {$count} supplementary budget request(s) " .
                                     "totalling GHS " . number_format($total, 2) .
                                     " for {$period}. Items: {$codes}.",
                'notifiable_id'   => $first->id,
                'notifiable_type' => SupplementaryBudget::class,
            ]);
        }
    }

    private function notifyFinanceOfSupplementary(SupplementaryBudget $s): void
    {
        $this->notifyFinanceOfBatch([$s]);
    }

    private function notifyDepartmentOfDecision(
        SupplementaryBudget $s,
        string $decision,
        ?string $notes
    ): void {
        $members = \App\Models\User::where('department_id', $s->department_id)
                                   ->where('is_active', true)->get();

        $subject = $decision === 'approved'
            ? "Supplementary budget approved — {$s->accountCode->code}"
            : "Supplementary budget rejected — {$s->accountCode->code}";

        $message = $decision === 'approved'
            ? "Your supplementary budget request for {$s->accountCode->name} has been approved. " .
              "GHS " . number_format($s->approved_amount, 2) . " has been added to your budget."
            : "Your supplementary budget request for {$s->accountCode->name} has been rejected. " .
              "Reason: {$notes}";

        foreach ($members as $member) {
            \App\Models\BudgetNotification::create([
                'user_id'         => $member->id,
                'type'            => "supplementary_{$decision}",
                'subject'         => $subject,
                'message'         => $message,
                'notifiable_id'   => $s->id,
                'notifiable_type' => SupplementaryBudget::class,
            ]);
        }
    }
}
