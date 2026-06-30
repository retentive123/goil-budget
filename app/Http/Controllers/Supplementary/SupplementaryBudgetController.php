<?php

namespace App\Http\Controllers\Supplementary;

use App\Http\Controllers\Controller;
use App\Models\SupplementaryBudget;
use App\Models\BudgetVersion;
use App\Models\BudgetLineItem;
use App\Models\BudgetPeriod;
use App\Models\Department;
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
            ->orderByDesc('created_at');

        $supplementaries = $query->paginate(20);
        $periods         = BudgetPeriod::orderByDesc('year')->get();

        return view('supplementary.index', compact('supplementaries','periods'));
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
            'budget_period_id'    => ['required','exists:budget_periods,id'],
            'department_id'       => ['required','exists:departments,id'],
            'budget_line_item_id' => ['required','exists:budget_line_items,id'],
            'requested_amount'    => ['required','numeric','min:1'],
            'justification'       => ['required','string','min:20','max:2000'],
            'supporting_evidence' => ['nullable','string','max:2000'],
        ]);

        $lineItem = BudgetLineItem::with('accountCode')->findOrFail($request->budget_line_item_id);

        // Check if there's already a pending request for this line item
        $existing = SupplementaryBudget::where('budget_line_item_id', $lineItem->id)
            ->whereIn('status', ['draft','submitted','under_review'])
            ->first();

        if ($existing) {
            return back()->withInput()->with('error',
                "There is already a pending supplementary request for {$lineItem->accountCode->code}. " .
                "Wait for it to be processed before submitting another."
            );
        }

        DB::transaction(function () use ($request, $lineItem) {
            $supplementary = SupplementaryBudget::create([
                'budget_period_id'    => $request->budget_period_id,
                'department_id'       => $request->department_id,
                'budget_line_item_id' => $request->budget_line_item_id,
                'account_code_id'     => $lineItem->account_code_id,
                'line_type'           => $lineItem->line_type,
                'original_amount'     => $lineItem->total_amount,
                'requested_amount'    => $request->requested_amount,
                'justification'       => $request->justification,
                'supporting_evidence' => $request->supporting_evidence,
                'status'              => 'submitted',
                'requested_by'        => auth()->id(),
                'submitted_at'        => now(),
            ]);

            // Notify finance reviewers
            $this->notifyFinanceOfSupplementary($supplementary->fresh()->load(
                'department','accountCode','period','requestedBy'
            ));

            \App\Services\AuditLogger::record(
                'supplementary_submitted', 'budget', 'created',
                [
                    'subject_label' => "Supplementary: {$lineItem->accountCode->code} — " .
                                       "GHS " . number_format($request->requested_amount, 2),
                    'severity'      => 'info',
                ]
            );
        });

        return redirect()->route('supplementary.index')
            ->with('success',
                'Supplementary budget request submitted. Finance has been notified.'
            );
    }

    public function show(SupplementaryBudget $supplementary)
    {
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

    public function pending()
    {
        $pending = SupplementaryBudget::with(
                'department','accountCode.category','requestedBy','period'
            )
            ->whereIn('status', ['submitted','under_review'])
            ->orderByDesc('submitted_at')
            ->paginate(20);

        return view('supplementary.pending', compact('pending'));
    }

    private function notifyFinanceOfSupplementary(SupplementaryBudget $s): void
    {
        $financeUsers = \App\Models\User::role('finance_reviewer')
                                        ->where('is_active', true)->get();

        foreach ($financeUsers as $user) {
            \App\Models\BudgetNotification::create([
                'user_id'         => $user->id,
                'type'            => 'supplementary_pending',
                'subject'         => "Supplementary budget request — {$s->department->name}",
                'message'         => "{$s->department->name} has requested a supplementary budget of " .
                                     "GHS " . number_format($s->requested_amount, 2) .
                                     " for {$s->accountCode->code} ({$s->accountCode->name}) " .
                                     "in {$s->period->name}.",
                'notifiable_id'   => $s->id,
                'notifiable_type' => SupplementaryBudget::class,
            ]);
        }
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
