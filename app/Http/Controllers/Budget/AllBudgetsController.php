<?php

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use App\Models\BudgetVersion;
use App\Models\BudgetPeriod;
use App\Models\Department;
use App\Models\Subsidiary;
use App\Models\SubsidiaryCategory;
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
        $periods            = BudgetPeriod::orderByDesc('year')->get();
        $departments        = Department::where('is_active', true)->with('zone')->orderBy('name')->get();
        $subsidiaries       = Subsidiary::where('is_active', true)->with('category')->orderBy('name')->get();
        $subsidiaryCategories = SubsidiaryCategory::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $categories         = AccountCategory::orderBy('name')->get();

        // Resolve entity-type filter (departments | subsidiaries | all)
        $entityType = $request->entity_type ?? 'departments'; // 'departments' | 'subsidiaries' | 'all'

        // null = "All Periods" — never fall back to the active period
        $period = $request->period_id
            ? BudgetPeriod::find($request->period_id)
            : null;

        // Build query — scope to departments or subsidiaries depending on filter
        $query = BudgetVersion::with('department', 'subsidiary.category', 'period', 'submittedBy', 'lineItems')
            ->when($period,                   fn($q) => $q->where('budget_period_id', $period->id))
            ->when($request->status,         fn($q) => $q->where('status', $request->status))
            ->when($request->version_number, fn($q) => $q->where('version_number', $request->version_number));

        if ($entityType === 'departments') {
            $query->whereNotNull('department_id');
            if ($request->department_id) $query->where('department_id', $request->department_id);
        } elseif ($entityType === 'subsidiaries') {
            $query->whereNotNull('subsidiary_id');
            if ($request->subsidiary_id)         $query->where('subsidiary_id', $request->subsidiary_id);
            if ($request->subsidiary_category_id) $query->whereHas('subsidiary', fn($q) => $q->where('subsidiary_category_id', $request->subsidiary_category_id));
        }

        // Search by name
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('department', fn($q2) =>
                    $q2->where('name', 'like', "%{$request->search}%")
                       ->orWhere('code', 'like', "%{$request->search}%")
                )->orWhereHas('subsidiary', fn($q2) =>
                    $q2->where('name', 'like', "%{$request->search}%")
                       ->orWhere('code', 'like', "%{$request->search}%")
                );
            });
        }

        $budgets = $query->orderByDesc('version_number')->paginate(30)->withQueryString();

        // Summary stats — scoped to selected period or all periods
        $allVersions = BudgetVersion::when($period, fn($q) => $q->where('budget_period_id', $period->id))->get();
        $totalDepts  = $departments->count();
        $totalSubs   = $subsidiaries->count();

        $stats = [
            'total_depts'       => $totalDepts,
            'total_subsidiaries'=> $totalSubs,
            'approved'          => $allVersions->where('status','approved')->count(),
            'in_review'         => $allVersions->whereIn('status',['submitted','under_review'])->count(),
            'rejected'          => $allVersions->where('status','rejected')->count(),
            'draft'             => $allVersions->where('status','draft')->count(),
            'not_started_depts' => max(0, $totalDepts  - $allVersions->whereNotNull('department_id')->unique('department_id')->count()),
            'not_started_subs'  => max(0, $totalSubs   - $allVersions->whereNotNull('subsidiary_id')->unique('subsidiary_id')->count()),
            'total_value'       => $allVersions->where('status','approved')->sum(fn($v) => $v->effectiveTotal()),
        ];

        // Pre-compute actuals per department (no N+1)
        $actualsByDept = $period
            ? \App\Models\BudgetActual::where('budget_period_id', $period->id)
                ->where('status', 'confirmed')->whereNotNull('department_id')
                ->selectRaw('department_id, SUM(amount) as total')
                ->groupBy('department_id')->pluck('total', 'department_id')
            : collect();

        // Pre-compute actuals per subsidiary
        $actualsBySub = $period
            ? \App\Models\BudgetActual::where('budget_period_id', $period->id)
                ->where('status', 'confirmed')->whereNotNull('subsidiary_id')
                ->selectRaw('subsidiary_id, SUM(amount) as total')
                ->groupBy('subsidiary_id')->pluck('total', 'subsidiary_id')
            : collect();

        // Department matrix rows
        $deptMatrix = $departments->map(function ($dept) use ($allVersions, $actualsByDept) {
            $versions = $allVersions->where('department_id', $dept->id)->sortByDesc('version_number');
            $latest   = $versions->first();
            return [
                'dept'     => $dept,
                'type'     => 'department',
                'versions' => $versions,
                'latest'   => $latest,
                'total'    => $latest ? $latest->effectiveTotal() : 0,
                'actual'   => (float) ($actualsByDept->get($dept->id) ?? 0),
            ];
        });

        // Subsidiary matrix rows
        $subMatrix = $subsidiaries->map(function ($sub) use ($allVersions, $actualsBySub) {
            $versions = $allVersions->where('subsidiary_id', $sub->id)->sortByDesc('version_number');
            $latest   = $versions->first();
            return [
                'dept'     => $sub,
                'type'     => 'subsidiary',
                'versions' => $versions,
                'latest'   => $latest,
                'total'    => $latest ? $latest->effectiveTotal() : 0,
                'actual'   => (float) ($actualsBySub->get($sub->id) ?? 0),
            ];
        });

        return view('budgets.all.index', compact(
            'budgets', 'periods', 'departments', 'subsidiaries', 'subsidiaryCategories',
            'period', 'stats', 'deptMatrix', 'subMatrix', 'categories', 'entityType'
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
        $grandTotals  = $this->calculator->grandTotals($budgetVersion);
        $progress     = $this->approvalService->approvalProgress($budgetVersion);
        $canDecide    = $this->approvalService->canCurrentUserDecide($budgetVersion);
        $currentStage = $this->approvalService->currentStage($budgetVersion);

        // Revenue categories first, then expense
        $revenueTypes = ['revenue', 'both'];
        uasort($summary, function ($a, $b) use ($revenueTypes) {
            $typeA = $a['items']->first()?->accountCode?->category?->budget_type ?? 'expense';
            $typeB = $b['items']->first()?->accountCode?->category?->budget_type ?? 'expense';
            return (in_array($typeA, $revenueTypes) ? 0 : 1) <=> (in_array($typeB, $revenueTypes) ? 0 : 1);
        });

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

    // ── P&L view of a single budget version ──────────────────
    public function showPnl(Request $request, BudgetVersion $budgetVersion)
    {
        $budgetVersion->load(
            'department', 'period', 'submittedBy',
            'lineItems.accountCode.category',
            'approvalDecisions.stage',
            'approvalDecisions.decidedBy',
            'approvalDecisions.lineItemApprovals.lineItem.accountCode'
        );

        $summary      = $this->calculator->summaryByCategory($budgetVersion);
        $grandTotals  = $this->calculator->grandTotals($budgetVersion);
        $progress     = $this->approvalService->approvalProgress($budgetVersion);
        $canDecide    = $this->approvalService->canCurrentUserDecide($budgetVersion);
        $currentStage = $this->approvalService->currentStage($budgetVersion);

        $actualsPerItem = \App\Models\BudgetActual::where('department_id', $budgetVersion->department_id)
            ->where('budget_period_id', $budgetVersion->budget_period_id)
            ->where('status', 'confirmed')
            ->get()
            ->groupBy('budget_line_item_id')
            ->map(fn($g) => $g->sum('amount'));

        $allVersions = BudgetVersion::where('budget_period_id', $budgetVersion->budget_period_id)
            ->where('department_id', $budgetVersion->department_id)
            ->orderByDesc('version_number')
            ->get();

        $supplementaries = \App\Models\SupplementaryBudget::where('budget_period_id', $budgetVersion->budget_period_id)
            ->where('department_id', $budgetVersion->department_id)
            ->with('accountCode', 'requestedBy', 'approvedBy')
            ->get();

        $period = $budgetVersion->period;
        $prevPeriod = BudgetPeriod::where('year', $period->year - 1)
            ->orderByDesc('id')->first()
            ?? BudgetPeriod::where('id', '<', $period->id)
                ->orderByDesc('year')->orderByDesc('id')->first();

        $pnlData = $this->calculator->buildPnlData($budgetVersion, $prevPeriod);

        return view('budgets.all.show-pnl', compact(
            'budgetVersion', 'summary', 'grandTotals',
            'progress', 'canDecide', 'currentStage',
            'actualsPerItem', 'allVersions', 'supplementaries',
            'pnlData', 'prevPeriod'
        ));
    }

    // ── Department summary — all versions for one dept ────────
    public function department(Request $request, Department $department)
    {
        $periods = BudgetPeriod::orderByDesc('year')->get();

        // null = "All Periods" — never fall back to the active period
        $period = $request->period_id
            ? BudgetPeriod::find($request->period_id)
            : null;

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
