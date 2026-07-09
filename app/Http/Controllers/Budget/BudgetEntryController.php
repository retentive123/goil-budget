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
        $entryMode   = $budgetVersion->period->entry_mode ?? 'quarterly';

        // Revenue categories first, then expense
        $revenueTypes = ['revenue', 'both'];
        uasort($summary, function ($a, $b) use ($revenueTypes) {
            $typeA = $a['items']->first()?->accountCode?->category?->budget_type ?? 'expense';
            $typeB = $b['items']->first()?->accountCode?->category?->budget_type ?? 'expense';
            return (in_array($typeA, $revenueTypes) ? 0 : 1) <=> (in_array($typeB, $revenueTypes) ? 0 : 1);
        });

        return view('budget.show', compact('budgetVersion', 'summary', 'grandTotals', 'entryMode'));
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

        $justificationRules = explode('|', \App\Models\SystemSetting::get('require_justification', false)
            ? 'required|string|max:500'
            : 'nullable|string|max:500');

        $mode = $budgetVersion->period->entry_mode ?? 'quarterly';

        if ($mode === 'monthly') {
            $request->validate([
                'items'       => ['required', 'array'],
                'items.*.id'  => ['required', 'exists:budget_line_items,id'],
                'items.*.m1'  => ['required', 'numeric', 'min:0'],
                'items.*.m2'  => ['required', 'numeric', 'min:0'],
                'items.*.m3'  => ['required', 'numeric', 'min:0'],
                'items.*.m4'  => ['required', 'numeric', 'min:0'],
                'items.*.m5'  => ['required', 'numeric', 'min:0'],
                'items.*.m6'  => ['required', 'numeric', 'min:0'],
                'items.*.m7'  => ['required', 'numeric', 'min:0'],
                'items.*.m8'  => ['required', 'numeric', 'min:0'],
                'items.*.m9'  => ['required', 'numeric', 'min:0'],
                'items.*.m10' => ['required', 'numeric', 'min:0'],
                'items.*.m11' => ['required', 'numeric', 'min:0'],
                'items.*.m12' => ['required', 'numeric', 'min:0'],
                'items.*.notes' => $justificationRules,
            ]);

            DB::transaction(function () use ($request, $budgetVersion) {
                foreach ($request->items as $d) {
                    BudgetLineItem::where('id', $d['id'])
                        ->where('budget_version_id', $budgetVersion->id)
                        ->update([
                            'm1_amount'  => $d['m1'],  'm2_amount'  => $d['m2'],  'm3_amount'  => $d['m3'],
                            'm4_amount'  => $d['m4'],  'm5_amount'  => $d['m5'],  'm6_amount'  => $d['m6'],
                            'm7_amount'  => $d['m7'],  'm8_amount'  => $d['m8'],  'm9_amount'  => $d['m9'],
                            'm10_amount' => $d['m10'], 'm11_amount' => $d['m11'], 'm12_amount' => $d['m12'],
                            'justification'   => $d['notes'] ?? null,
                            'last_updated_by' => auth()->id(),
                        ]);
                }
            });
        } else {
            // Quarterly mode: validate Q1–Q4, spread each quarter equally across 3 months
            $request->validate([
                'items'         => ['required', 'array'],
                'items.*.id'    => ['required', 'exists:budget_line_items,id'],
                'items.*.q1'    => ['required', 'numeric', 'min:0'],
                'items.*.q2'    => ['required', 'numeric', 'min:0'],
                'items.*.q3'    => ['required', 'numeric', 'min:0'],
                'items.*.q4'    => ['required', 'numeric', 'min:0'],
                'items.*.notes' => $justificationRules,
            ]);

            DB::transaction(function () use ($request, $budgetVersion) {
                foreach ($request->items as $d) {
                    [$m1, $m2, $m3]   = $this->spreadQuarter((float) $d['q1']);
                    [$m4, $m5, $m6]   = $this->spreadQuarter((float) $d['q2']);
                    [$m7, $m8, $m9]   = $this->spreadQuarter((float) $d['q3']);
                    [$m10, $m11, $m12] = $this->spreadQuarter((float) $d['q4']);

                    BudgetLineItem::where('id', $d['id'])
                        ->where('budget_version_id', $budgetVersion->id)
                        ->update([
                            'm1_amount'  => $m1,  'm2_amount'  => $m2,  'm3_amount'  => $m3,
                            'm4_amount'  => $m4,  'm5_amount'  => $m5,  'm6_amount'  => $m6,
                            'm7_amount'  => $m7,  'm8_amount'  => $m8,  'm9_amount'  => $m9,
                            'm10_amount' => $m10, 'm11_amount' => $m11, 'm12_amount' => $m12,
                            'justification'   => $d['notes'] ?? null,
                            'last_updated_by' => auth()->id(),
                        ]);
                }
            });
        }

        $grandTotals = $this->calculator->grandTotals($budgetVersion->fresh());

        return response()->json([
            'success'     => true,
            'saved_at'    => now()->format('H:i:s'),
            'grand_total' => number_format($grandTotals['total'], 2),
            'totals'      => $grandTotals,
        ]);
    }

    // Split a quarter total equally into 3 months, putting any remainder in the last month
    private function spreadQuarter(float $total): array
    {
        $third = round($total / 3, 2);
        return [$third, $third, round($total - $third * 2, 2)];
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
