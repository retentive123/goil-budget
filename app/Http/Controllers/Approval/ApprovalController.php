<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Controller;
use App\Models\BudgetVersion;
use App\Models\BudgetPeriod;
use App\Services\ApprovalService;
use App\Services\BudgetCalculationService;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function __construct(
        protected ApprovalService          $approvalService,
        protected BudgetCalculationService $calculator
    ) {}

    // List all budgets pending this user's approval
    public function index()
    {
        $user          = auth()->user();
        $currentPeriod = BudgetPeriod::current();

        // Get all submitted/under_review versions
        $pending = BudgetVersion::with('department', 'period')
            ->whereIn('status', [
                BudgetVersion::STATUS_SUBMITTED,
                BudgetVersion::STATUS_UNDER_REVIEW,
            ])
            ->when($currentPeriod, fn($q) => $q->where('budget_period_id', $currentPeriod->id))
            ->get()
            ->filter(fn($v) => $this->approvalService->canCurrentUserDecide($v));

        // Already decided by this user
        $decided = BudgetVersion::with('department', 'period')
            ->whereHas('approvalDecisions', fn($q) => $q->where('decided_by', $user->id))
            ->when($currentPeriod, fn($q) => $q->where('budget_period_id', $currentPeriod->id))
            ->get();

        return view('approval.index', compact('pending', 'decided', 'currentPeriod'));
    }

    // Show a budget version for review
    public function show(BudgetVersion $budgetVersion)
    {
        $budgetVersion->load(
            'department', 'period',
            'lineItems.accountCode.category',
            'approvalDecisions.stage',
            'approvalDecisions.decidedBy',
            'submittedBy'
        );

        $canDecide    = $this->approvalService->canCurrentUserDecide($budgetVersion);
        $currentStage = $this->approvalService->currentStage($budgetVersion);
        $progress     = $this->approvalService->approvalProgress($budgetVersion);
        $summary      = $this->calculator->summaryByCategory($budgetVersion);
        $grandTotals  = $this->calculator->grandTotals($budgetVersion);
        $approvalService = $this->approvalService;

        // Revenue categories first, then expense
        $revenueTypes = ['revenue', 'both'];
        uasort($summary, function ($a, $b) use ($revenueTypes) {
            $typeA = $a['items']->first()?->accountCode?->category?->budget_type ?? 'expense';
            $typeB = $b['items']->first()?->accountCode?->category?->budget_type ?? 'expense';
            return (in_array($typeA, $revenueTypes) ? 0 : 1) <=> (in_array($typeB, $revenueTypes) ? 0 : 1);
        });

        return view('approval.show', compact(
            'budgetVersion', 'canDecide', 'currentStage',
            'progress', 'summary', 'grandTotals', 'approvalService'
        ));
    }

    // Show a budget version for review in P&L view
    public function showPnl(BudgetVersion $budgetVersion)
    {
        $budgetVersion->load(
            'department', 'period',
            'lineItems.accountCode.category',
            'approvalDecisions.stage',
            'approvalDecisions.decidedBy',
            'submittedBy'
        );

        $canDecide    = $this->approvalService->canCurrentUserDecide($budgetVersion);
        $currentStage = $this->approvalService->currentStage($budgetVersion);
        $progress     = $this->approvalService->approvalProgress($budgetVersion);
        $summary      = $this->calculator->summaryByCategory($budgetVersion);
        $grandTotals  = $this->calculator->grandTotals($budgetVersion);
        $approvalService = $this->approvalService;

        $period = $budgetVersion->period;
        $prevPeriod = BudgetPeriod::where('year', $period->year - 1)
            ->orderByDesc('id')->first()
            ?? BudgetPeriod::where('id', '<', $period->id)
                ->orderByDesc('year')->orderByDesc('id')->first();

        $pnlData = $this->calculator->buildPnlData($budgetVersion, $prevPeriod);

        return view('approval.show-pnl', compact(
            'budgetVersion', 'canDecide', 'currentStage',
            'progress', 'summary', 'grandTotals', 'approvalService',
            'pnlData', 'prevPeriod'
        ));
    }

    // Process approve or reject decision
    public function decide(Request $request, BudgetVersion $budgetVersion)
    {
        if (!$this->approvalService->canCurrentUserDecide($budgetVersion)) {
            abort(403, 'You are not authorised to decide on this budget at this stage.');
        }

        $request->validate([
            'decision'   => ['required', 'in:approved,rejected'],
            'comments'   => ['required_if:decision,rejected', 'nullable', 'string', 'max:1000'],
            'line_items' => ['nullable', 'array'],
        ]);

        // Parse line item decisions if provided
        $lineItemDecisions = [];
        if ($request->line_items) {
            foreach ($request->line_items as $itemId => $data) {
                if (!empty($data['status'])) {
                    $lineItemDecisions[$itemId] = [
                        'status'          => $data['status'],
                        'approved_amount' => $data['approved_amount'] ?? null,
                        'comments'        => $data['comments'] ?? null,
                    ];
                }
            }
        }

        try {
            $this->approvalService->decide(
                $budgetVersion,
                $request->decision,
                $request->comments ?? '',
                $lineItemDecisions
            );

            $message = $request->decision === 'approved'
                ? 'Budget approved and forwarded to the next stage.'
                : 'Budget rejected. The department has been notified to revise.';

            return redirect()->route('approvals.index')->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // Full approval history for a version
    public function history(BudgetVersion $budgetVersion)
    {
        $budgetVersion->load(
            'department',
            'period',
            'submittedBy',
            'lineItems.accountCode.category',
            'approvalDecisions.stage',
            'approvalDecisions.decidedBy',
            'approvalDecisions.lineItemApprovals.lineItem.accountCode'
        );

        $progress = $this->approvalService->approvalProgress($budgetVersion);

        return view('approval.history', compact('budgetVersion', 'progress'));
    }
}
