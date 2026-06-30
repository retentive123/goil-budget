<?php

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use App\Models\BudgetVersion;
use App\Models\BudgetPeriod;
use App\Models\Department;
use App\Models\AccountCategory;
use App\Services\BudgetCalculationService;
use App\Services\ApprovalService;
use Illuminate\Http\Request;

class AllBudgetsController extends Controller
{
    public function __construct(
        protected BudgetCalculationService $calculator,
        protected ApprovalService          $approvalService
    ) {}

    // ── Main listing ─────────────────────────────────────────
    public function index(Request $request)
    {
        $periods     = BudgetPeriod::orderByDesc('year')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $categories  = AccountCategory::orderBy('name')->get();

        $period = $request->period_id
            ? BudgetPeriod::find($request->period_id)
            : (BudgetPeriod::current() ?? $periods->first());

        $query = BudgetVersion::with('department', 'period', 'submittedBy', 'lineItems')
            ->when($period,                    fn($q) => $q->where('budget_period_id', $period->id))
            ->when($request->department_id,   fn($q) => $q->where('department_id', $request->department_id))
            ->when($request->status,          fn($q) => $q->where('status', $request->status))
            ->when($request->version_number,  fn($q) => $q->where('version_number', $request->version_number));

        // Search by department name
        if ($request->search) {
            $query->whereHas('department', fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%")
            );
        }

        $budgets = $query->orderBy('department_id')->orderByDesc('version_number')->paginate(30)->withQueryString();

        // Summary stats for the selected period
        $allVersions = BudgetVersion::where('budget_period_id', $period?->id)->get();
        $totalDepts  = $departments->count();

        $stats = [
            'total_depts'   => $totalDepts,
            'approved'      => $allVersions->where('status','approved')->unique('department_id')->count(),
            'in_review'     => $allVersions->whereIn('status',['submitted','under_review'])->unique('department_id')->count(),
            'rejected'      => $allVersions->where('status','rejected')->unique('department_id')->count(),
            'draft'         => $allVersions->where('status','draft')->unique('department_id')->count(),
            'not_started'   => max(0, $totalDepts - $allVersions->unique('department_id')->count()),
            // ✅ Use effectiveTotal() here
            'total_value'   => $allVersions->where('status','approved')
                ->sum(fn($v) => $v->effectiveTotal()),
        ];

        // Group latest version per department for the matrix view
        $deptMatrix = $departments->map(function ($dept) use ($period, $allVersions) {
            $versions = $allVersions->where('department_id', $dept->id)
                                    ->sortByDesc('version_number');
            $latest   = $versions->first();

            return [
                'dept'     => $dept,
                'versions' => $versions,
                'latest'   => $latest,
                // ✅ Use effectiveTotal() here
                'total'    => $latest ? $latest->effectiveTotal() : 0,
            ];
        });

        return view('budgets.all.index', compact(
            'budgets', 'periods', 'departments', 'period',
            'stats', 'deptMatrix', 'categories'
        ));
    }

    // ── View a single budget version in detail ────────────────
    public function show(Request $request, BudgetVersion $budgetVersion)
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

        $summary      = $this->calculator->summaryByCategory($budgetVersion);
        // ✅ Grand totals already use effective budget if you update BudgetCalculationService
        $grandTotals  = $this->calculator->grandTotals($budgetVersion);
        $progress     = $this->approvalService->approvalProgress($budgetVersion);
        $canDecide    = $this->approvalService->canCurrentUserDecide($budgetVersion);
        $currentStage = $this->approvalService->currentStage($budgetVersion);

        // Actuals per line item
        $actualsPerItem = \App\Models\BudgetActual::where('department_id', $budgetVersion->department_id)
            ->where('budget_period_id', $budgetVersion->budget_period_id)
            ->where('status', 'confirmed')
            ->get()
            ->groupBy('budget_line_item_id')
            ->map(fn($g) => $g->sum('amount'));

        // All versions for this dept/period (for switcher)
        $allVersions = BudgetVersion::where('budget_period_id', $budgetVersion->budget_period_id)
            ->where('department_id', $budgetVersion->department_id)
            ->orderByDesc('version_number')
            ->get();

        // Supplementary requests
        $supplementaries = \App\Models\SupplementaryBudget::where('budget_period_id', $budgetVersion->budget_period_id)
            ->where('department_id', $budgetVersion->department_id)
            ->with('accountCode', 'requestedBy', 'approvedBy')
            ->get();

        return view('budgets.all.show', compact(
            'budgetVersion', 'summary', 'grandTotals',
            'progress', 'canDecide', 'currentStage',
            'actualsPerItem', 'allVersions', 'supplementaries'
        ));
    }

    // ── Department summary — all versions for one dept ────────
    public function department(Request $request, Department $department)
    {
        $periods = BudgetPeriod::orderByDesc('year')->get();

        $period = $request->period_id
            ? BudgetPeriod::find($request->period_id)
            : (BudgetPeriod::current() ?? $periods->first());

        $versions = BudgetVersion::with('lineItems.accountCode.category', 'submittedBy')
            ->where('department_id', $department->id)
            ->when($period, fn($q) => $q->where('budget_period_id', $period->id))
            ->orderByDesc('version_number')
            ->get();

        // All periods this dept has a budget in
        $allPeriodSummary = BudgetVersion::where('department_id', $department->id)
            ->with('period', 'lineItems')
            ->get()
            ->groupBy('budget_period_id')
            ->map(fn($vv) => [
                'period'  => $vv->first()->period,
                'latest'  => $vv->sortByDesc('version_number')->first(),
                // ✅ Use effectiveTotal() here
                'budget'  => $vv->where('status','approved')
                    ->sum(fn($v) => $v->effectiveTotal()),
                'actual'  => \App\Models\BudgetActual::where('department_id', $department->id)
                    ->where('budget_period_id', $vv->first()->budget_period_id)
                    ->where('status','confirmed')->sum('amount'),
            ])
            ->sortByDesc(fn($r) => $r['period']->year)
            ->values();

        return view('budgets.all.department', compact(
            'department', 'period', 'periods', 'versions', 'allPeriodSummary'
        ));
    }

    // ── Export all budgets ─────────────────────────────────────
    public function export(Request $request)
    {
        $period = $request->period_id
            ? BudgetPeriod::find($request->period_id)
            : BudgetPeriod::current();

        $filename = 'all-budgets-' . ($period?->year ?? 'all') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\BudgetExport($period?->id, null, 'approved'),
            $filename
        );
    }
}
