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
        $pending = BudgetVersion::with('department', 'period', 'lineItems', 'approvalDecisions.lineItemApprovals')
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
    public function show(BudgetVersion $budgetVersion, Request $request)
    {
        $budgetVersion->load(
            'department', 'period',
            'lineItems.accountCode.category',
            'lineItems.subEntries',
            'approvalDecisions.stage',
            'approvalDecisions.decidedBy',
            'approvalDecisions.lineItemApprovals',
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

        // Calc mode for this period (needed to show Qty/Rate/Freq columns to approvers)
        $calcMode      = $budgetVersion->period->calcMode();
        $adminSetsRate = $budgetVersion->period->adminSetsRate();
        $adminSetsFreq = $budgetVersion->period->adminSetsFreq();
        $entryMode     = $budgetVersion->period->entry_mode ?? 'quarterly';

        // Prior-stage reductions visible to the current approver
        $priorReductions    = collect(); // item_id => approved_amount
        $priorReductionLias = collect(); // item_id => LineItemApproval
        foreach ($budgetVersion->approvalDecisions as $dec) {
            foreach ($dec->lineItemApprovals as $lia) {
                if (in_array($lia->status, ['reduced', 'increased']) && $lia->approved_amount !== null) {
                    $priorReductions[$lia->budget_line_item_id]    = (float) $lia->approved_amount;
                    $priorReductionLias[$lia->budget_line_item_id] = $lia;
                }
            }
        }

        $allowRestore = setting('approval_allow_restore', false);

        return view('approval.show', compact(
            'budgetVersion', 'canDecide', 'currentStage',
            'progress', 'summary', 'grandTotals', 'approvalService',
            'calcMode', 'adminSetsRate', 'adminSetsFreq', 'entryMode',
            'priorReductions', 'priorReductionLias', 'allowRestore'
        ));
    }

    // Show a budget version for review in P&L view
    public function showPnl(BudgetVersion $budgetVersion)
    {
        $budgetVersion->load(
            'department', 'period',
            'lineItems.accountCode.category',
            'lineItems.subEntries',
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

        $pnlData   = $this->calculator->buildPnlData($budgetVersion, $prevPeriod);
        $entryMode = $budgetVersion->period->entry_mode ?? 'quarterly';

        return view('approval.show-pnl', compact(
            'budgetVersion', 'canDecide', 'currentStage',
            'progress', 'summary', 'grandTotals', 'approvalService',
            'pnlData', 'prevPeriod', 'entryMode'
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

        // Parse and validate line item decisions
        $lineItemDecisions = [];
        $amountErrors = [];
        if ($request->line_items) {
            $budgetVersion->loadMissing('lineItems', 'period', 'approvalDecisions.lineItemApprovals');
            $itemsById = $budgetVersion->lineItems->keyBy('id');

            // Build prior reductions to use as the cap for further reductions
            $priorReductions = collect();
            foreach ($budgetVersion->approvalDecisions as $dec) {
                foreach ($dec->lineItemApprovals as $lia) {
                    if (in_array($lia->status, ['reduced', 'increased']) && $lia->approved_amount !== null) {
                        $priorReductions[$lia->budget_line_item_id] = (float) $lia->approved_amount;
                    }
                }
            }
            $allowRestore = setting('approval_allow_restore', false);

            foreach ($request->line_items as $itemId => $data) {
                if (empty($data['status'])) continue;

                $approvedAmount = isset($data['approved_amount']) && $data['approved_amount'] !== ''
                    ? (float) $data['approved_amount']
                    : null;

                if ($data['status'] === 'reduced') {
                    $item = $itemsById->get($itemId);
                    if ($item) {
                        $code = $item->accountCode->code;
                        if ($item->subEntries()->exists()) {
                            $amountErrors[] = '"' . $code . '" has sub-items and cannot be reduced at the parent level.';
                        } elseif ($approvedAmount !== null) {
                            // Cap: if a prior stage already reduced this item and restore is not allowed,
                            // the new amount cannot exceed that prior reduction.
                            $priorReduced = $priorReductions->get($itemId);
                            $cap = ($priorReduced !== null && !$allowRestore)
                                ? $priorReduced
                                : (float) $item->effectiveBudget();

                            if ($approvedAmount <= 0) {
                                $amountErrors[] = '"' . $code . '" reduced amount must be greater than zero.';
                            } elseif ($approvedAmount >= $cap) {
                                $capLabel = $priorReduced !== null && !$allowRestore
                                    ? 'the previous stage\'s approved amount'
                                    : 'the original budget';
                                $amountErrors[] = '"' . $code . '" reduced amount (' . number_format($approvedAmount, 2) . ') must be less than ' . $capLabel . ' (' . number_format($cap, 2) . ').';
                            } else {
                                // Validate period breakdown must sum to approved_amount
                                $periodAmounts = $data['period_amounts'] ?? [];
                                $periodSum = round(array_sum(array_map('floatval', array_values($periodAmounts))), 2);
                                if (empty($periodAmounts) || $periodSum == 0) {
                                    $amountErrors[] = '"' . $code . '" requires a period breakdown (months/quarters) when reducing.';
                                } elseif (abs($periodSum - $approvedAmount) > 0.01) {
                                    $amountErrors[] = '"' . $code . '" period breakdown total (' . number_format($periodSum, 2) . ') must equal the reduced amount (' . number_format($approvedAmount, 2) . ').';
                                }
                            }
                        }
                    }
                }

                if ($data['status'] === 'increased') {
                    $item = $itemsById->get($itemId);
                    if ($item) {
                        $code = $item->accountCode->code;
                        if (!$allowRestore) {
                            $amountErrors[] = '"' . $code . '" cannot be increased — the adjust setting is not enabled.';
                        } elseif ($item->subEntries()->exists()) {
                            $amountErrors[] = '"' . $code . '" has sub-items and cannot be increased at the parent level.';
                        } elseif ($approvedAmount !== null) {
                            $originalBudget = (float) $item->effectiveBudget();
                            if ($approvedAmount <= 0) {
                                $amountErrors[] = '"' . $code . '" increased amount must be greater than zero.';
                            } elseif ($approvedAmount <= $originalBudget) {
                                $amountErrors[] = '"' . $code . '" increased amount (' . number_format($approvedAmount, 2) . ') must be greater than the original budget (' . number_format($originalBudget, 2) . ').';
                            } else {
                                $periodAmounts = $data['period_amounts'] ?? [];
                                $periodSum = round(array_sum(array_map('floatval', array_values($periodAmounts))), 2);
                                if (empty($periodAmounts) || $periodSum == 0) {
                                    $amountErrors[] = '"' . $code . '" requires a period breakdown when increasing.';
                                } elseif (abs($periodSum - $approvedAmount) > 0.01) {
                                    $amountErrors[] = '"' . $code . '" period breakdown total (' . number_format($periodSum, 2) . ') must equal the increased amount (' . number_format($approvedAmount, 2) . ').';
                                }
                            }
                        }
                    }
                }

                $lineItemDecisions[$itemId] = [
                    'status'          => $data['status'],
                    'approved_amount' => $approvedAmount,
                    'comments'        => $data['comments'] ?? null,
                    'period_amounts'  => $data['period_amounts'] ?? [],
                ];
            }
        }

        if (!empty($amountErrors)) {
            return back()->withInput()->with('error', implode(' | ', $amountErrors));
        }

        try {
            $this->approvalService->decide(
                $budgetVersion,
                $request->decision,
                $request->comments ?? '',
                $lineItemDecisions
            );

            if ($request->decision === 'approved') {
                $freshStatus = $budgetVersion->fresh()->status;
                $message = $freshStatus === \App\Models\BudgetVersion::STATUS_APPROVED
                    ? 'Budget fully approved. The department has been notified.'
                    : 'Budget approved and forwarded to the next stage.';
            } else {
                $message = 'Budget rejected. The department has been notified to revise.';
            }

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

        $progress  = $this->approvalService->approvalProgress($budgetVersion);
        $entryMode = $budgetVersion->period->entry_mode ?? 'quarterly';

        return view('approval.history', compact('budgetVersion', 'progress', 'entryMode'));
    }
}
