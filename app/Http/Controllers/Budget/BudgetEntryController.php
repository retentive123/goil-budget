<?php

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use App\Models\BudgetVersion;
use App\Models\BudgetPeriod;
use App\Models\BudgetLineItem;
use App\Services\BudgetCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetEntryController extends Controller
{
    public function __construct(
        protected BudgetCalculationService $calculator
    ) {}

    // Show the department's budget for the current period
    public function index()
    {
        $user          = auth()->user();
        $currentPeriod = BudgetPeriod::current();

        if (!$currentPeriod) {
            return view('budget.no-period');
        }

        // Get the latest version for this department
        $version = BudgetVersion::where('budget_period_id', $currentPeriod->id)
                                ->where('department_id', $user->department_id)
                                ->orderByDesc('version_number')
                                ->first();

        return view('budget.index', compact('currentPeriod', 'version', 'user'));
    }

    // Start a new budget version for the current period
    public function start(Request $request)
    {
        $user = auth()->user();

        if (!$user->department_id) {
            return back()->with('error', 'Your account is not assigned to a department. Please contact the administrator.');
        }

        $currentPeriod = BudgetPeriod::current();

        if (!$currentPeriod) {
            return back()->with('error', 'No active budget period.');
        }

        if (!BudgetVersion::canCreateNew($currentPeriod->id, $user->department_id)) {
            return back()->with('error', 'Maximum of 4 budget versions reached for this period.');
        }

        DB::transaction(function () use ($user, $currentPeriod) {
            $version = BudgetVersion::create([
                'budget_period_id' => $currentPeriod->id,
                'department_id'    => $user->department_id,
                'version_number'   => BudgetVersion::nextVersionNumber($currentPeriod->id, $user->department_id),
                'status'           => BudgetVersion::STATUS_DRAFT,
                'submitted_by'     => null,
            ]);

            $this->calculator->populateLineItems($version);
        });

        $version = BudgetVersion::where('budget_period_id', $currentPeriod->id)
                                ->where('department_id', $user->department_id)
                                ->orderByDesc('version_number')
                                ->first();

        return redirect()->route('budget.show', $version)
            ->with('success', "Budget v{$version->version_number} created. Start entering your figures.");
    }
    // Show the budget entry form
    public function show(BudgetVersion $budgetVersion)
    {
        $this->authorizeBudgetAccess($budgetVersion);

        // Sync any account codes assigned to the dept after this version was created
        if ($budgetVersion->isEditable()) {
            $this->calculator->populateLineItems($budgetVersion);
        }

        $budgetVersion->load('lineItems.accountCode.category', 'period', 'department');

        $summary     = $this->calculator->summaryByCategory($budgetVersion);
        $grandTotals = $this->calculator->grandTotals($budgetVersion);

        // Revenue categories first, then expense
        $revenueTypes = ['revenue', 'both'];
        uasort($summary, function ($a, $b) use ($revenueTypes) {
            $typeA = $a['items']->first()?->accountCode?->category?->budget_type ?? 'expense';
            $typeB = $b['items']->first()?->accountCode?->category?->budget_type ?? 'expense';
            return (in_array($typeA, $revenueTypes) ? 0 : 1) <=> (in_array($typeB, $revenueTypes) ? 0 : 1);
        });

        return view('budget.show', compact('budgetVersion', 'summary', 'grandTotals'));
    }

    // Show the P&L-style budget entry form
    public function showPnl(BudgetVersion $budgetVersion)
    {
        $this->authorizeBudgetAccess($budgetVersion);

        if ($budgetVersion->isEditable()) {
            $this->calculator->populateLineItems($budgetVersion);
        }

        $budgetVersion->load('lineItems.accountCode.category', 'period', 'department');

        $grandTotals = $this->calculator->grandTotals($budgetVersion);

        $period = $budgetVersion->period;
        $prevPeriod = \App\Models\BudgetPeriod::where('year', $period->year - 1)
            ->orderByDesc('id')->first()
            ?? \App\Models\BudgetPeriod::where('id', '<', $period->id)
                ->orderByDesc('year')->orderByDesc('id')->first();

        $pnlData = $this->calculator->buildPnlData($budgetVersion, $prevPeriod);

        return view('budget.show-pnl', compact('budgetVersion', 'grandTotals', 'prevPeriod', 'pnlData'));
    }

    // Save line item amounts (auto-save)
    public function save(Request $request, BudgetVersion $budgetVersion)
    {
        $this->authorizeBudgetAccess($budgetVersion);

        if (!$budgetVersion->isEditable()) {
            return response()->json(['error' => 'This budget is no longer editable.'], 403);
        }

        $justificationRule = \App\Models\SystemSetting::get('require_justification', false)
            ? 'required|string|max:500'
            : 'nullable|string|max:500';

        $justificationRules = explode('|', $justificationRule);

        $request->validate([
            'items'          => ['required', 'array'],
            'items.*.id'     => ['required', 'exists:budget_line_items,id'],
            'items.*.q1'     => ['required', 'numeric', 'min:0'],
            'items.*.q2'     => ['required', 'numeric', 'min:0'],
            'items.*.q3'     => ['required', 'numeric', 'min:0'],
            'items.*.q4'     => ['required', 'numeric', 'min:0'],
            'items.*.notes'  => $justificationRules,
        ]);

        DB::transaction(function () use ($request, $budgetVersion) {
            foreach ($request->items as $itemData) {
                BudgetLineItem::where('id', $itemData['id'])
                    ->where('budget_version_id', $budgetVersion->id)
                    ->update([
                        'q1_amount'       => $itemData['q1'],
                        'q2_amount'       => $itemData['q2'],
                        'q3_amount'       => $itemData['q3'],
                        'q4_amount'       => $itemData['q4'],
                        'justification'   => $itemData['notes'] ?? null,
                        'last_updated_by' => auth()->id(),
                    ]);
            }
        });

        // Return updated totals for live UI refresh
        $grandTotals = $this->calculator->grandTotals($budgetVersion->fresh());

        return response()->json([
            'success'     => true,
            'saved_at'    => now()->format('H:i:s'),
            'grand_total' => number_format($grandTotals['total'], 2),
            'totals'      => $grandTotals,
        ]);
    }

    // Ensure only the owning department can access this version
    private function authorizeBudgetAccess(BudgetVersion $version): void
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['finance_reviewer', 'gceo', 'board', 'bdu_admin', 'super_admin'])) {
            return; // These roles can view all
        }

        if ($version->department_id !== $user->department_id) {
            abort(403, 'You do not have access to this budget.');
        }
    }
}
