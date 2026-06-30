<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\BudgetPeriod;
use App\Models\BudgetVersion;
use App\Models\BudgetLineItem;
use App\Models\Department;
use App\Models\AccountCode;
use App\Models\AccountCategory;
use App\Models\Virement;
use App\Exports\BudgetExport;
use App\Exports\VarianceExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\CodeExplorerExport; 
use App\Services\AuditLogger;

class ReportController extends Controller
{
    // ── Landing ──────────────────────────────────────────────
    public function index()
    {
        $periods     = BudgetPeriod::orderByDesc('year')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $categories  = AccountCategory::where('is_active', true)->orderBy('name')->get();

        // Quick KPIs for landing cards
        $currentPeriod = BudgetPeriod::current()
            ?? BudgetPeriod::orderByDesc('year')->first();

        $kpis = $this->landingKpis($currentPeriod);

        return view('reports.index', compact(
            'periods', 'departments', 'categories', 'currentPeriod', 'kpis'
        ));
    }

  // ── Executive Summary ─────────────────────────────────────
public function executive(Request $request)
{
    $period      = $this->resolvePeriod($request);
    $periods     = BudgetPeriod::orderByDesc('year')->get();
    $departments = Department::where('is_active', true)->orderBy('name')->get();

    if (!$period) {
        return redirect()->route('reports.index')
            ->with('error', 'No budget period found.');
    }

    // All approved versions for this period
    $versions = BudgetVersion::with('department', 'lineItems.accountCode.category')
        ->where('budget_period_id', $period->id)
        ->where('status', 'approved')
        ->get();

    // Dept totals for ranking chart
    $deptTotals = $versions->map(function($v) {
        $original = $v->lineItems->sum('total_amount');
        $supplementary = $v->lineItems->sum(fn($i) => $i->approvedSupplementaryTotal());
        $effective = $original + $supplementary;

        return [
            'name'         => $v->department->name,
            'code'         => $v->department->code,
            'q1'           => $v->lineItems->sum('q1_amount'),
            'q2'           => $v->lineItems->sum('q2_amount'),
            'q3'           => $v->lineItems->sum('q3_amount'),
            'q4'           => $v->lineItems->sum('q4_amount'),
            'original'     => $original,
            'supplementary'=> $supplementary,
            'total'        => $effective, // ✅ Effective total
        ];
    })->sortByDesc('total')->values();

    // ✅ Calculate total supplementary for grand total
    $totalSupplementary = $deptTotals->sum('supplementary');

    // Category breakdown
    $categoryTotals = [];
    foreach ($versions as $v) {
        foreach ($v->lineItems as $item) {
            $cat = $item->accountCode->category->name ?? 'Uncategorised';
            // ✅ Use effectiveBudget() for each line item
            $categoryTotals[$cat] = ($categoryTotals[$cat] ?? 0) + $item->effectiveBudget();
        }
    }
    arsort($categoryTotals);

    // Quarterly trend across all depts
    $quarterlyTotals = [
        'q1' => $versions->sum(fn($v) => $v->lineItems->sum('q1_amount')),
        'q2' => $versions->sum(fn($v) => $v->lineItems->sum('q2_amount')),
        'q3' => $versions->sum(fn($v) => $v->lineItems->sum('q3_amount')),
        'q4' => $versions->sum(fn($v) => $v->lineItems->sum('q4_amount')),
    ];

    // Submission stats
    $allVersions    = BudgetVersion::where('budget_period_id', $period->id)->get();
    $totalDepts     = $departments->count();
    $submissionStats = [
        'approved'     => $allVersions->where('status','approved')->unique('department_id')->count(),
        'under_review' => $allVersions->whereIn('status',['submitted','under_review'])->unique('department_id')->count(),
        'rejected'     => $allVersions->where('status','rejected')->unique('department_id')->count(),
        'draft'        => $allVersions->where('status','draft')->unique('department_id')->count(),
        'not_started'  => max(0, $totalDepts - $allVersions->unique('department_id')->count()),
        'total'        => $totalDepts,
    ];

    // ✅ Use effectiveTotal() for grand total
    $grandTotal = $versions->sum(fn($v) => $v->effectiveTotal());

    return view('reports.executive', compact(
        'period', 'periods', 'departments',
        'deptTotals', 'categoryTotals', 'quarterlyTotals',
        'submissionStats', 'grandTotal', 'versions',
        'totalSupplementary' // ✅ Pass to view
    ));
}

    // ── Department Drill-down ─────────────────────────────────
public function department(Request $request)
{
    $period      = $this->resolvePeriod($request);
    $periods     = BudgetPeriod::orderByDesc('year')->get();
    $departments = Department::where('is_active', true)->orderBy('name')->get();
    $categories  = AccountCategory::where('is_active', true)->orderBy('name')->get();

    $department  = $request->department_id
        ? Department::find($request->department_id)
        : $departments->first();

    if (!$department || !$period) {
        return view('reports.department', compact(
            'periods','departments','categories'
        ) + ['period'=>$period,'department'=>$department,'version'=>null]);
    }

    // Latest approved version for this dept/period
    $version = BudgetVersion::with('lineItems.accountCode.category')
        ->where('budget_period_id', $period->id)
        ->where('department_id',    $department->id)
        ->where('status', 'approved')
        ->orderByDesc('version_number')
        ->first();

    // All versions for history
    $versionHistory = BudgetVersion::where('budget_period_id', $period->id)
        ->where('department_id', $department->id)
        ->orderBy('version_number')
        ->get();

    // Line items grouped by category
$byCategory = [];
$quarterSums = ['q1'=>0,'q2'=>0,'q3'=>0,'q4'=>0,'total'=>0, 'supplementary'=>0, 'original'=>0];

if ($version) {
    foreach ($version->lineItems as $item) {
        $cat  = $item->accountCode->category->name ?? 'Uncategorised';
        $code = $item->accountCode->code;

        if (!isset($byCategory[$cat])) {
            $byCategory[$cat] = [
                'items' => [],
                'q1' => 0,
                'q2' => 0,
                'q3' => 0,
                'q4' => 0,
                'total' => 0,
                'supplementary' => 0,
                'original' => 0,
            ];
        }

        $supplementary = $item->approvedSupplementaryTotal();
        $effectiveBudget = $item->effectiveBudget();

        $byCategory[$cat]['items'][] = $item;
        $byCategory[$cat]['q1'] += $item->q1_amount;
        $byCategory[$cat]['q2'] += $item->q2_amount;
        $byCategory[$cat]['q3'] += $item->q3_amount;
        $byCategory[$cat]['q4'] += $item->q4_amount; // ✅ No longer adding supp to Q4
        $byCategory[$cat]['total'] += $effectiveBudget;
        $byCategory[$cat]['supplementary'] += $supplementary;
        $byCategory[$cat]['original'] += $item->total_amount;

        $quarterSums['q1'] += $item->q1_amount;
        $quarterSums['q2'] += $item->q2_amount;
        $quarterSums['q3'] += $item->q3_amount;
        $quarterSums['q4'] += $item->q4_amount; // ✅ No longer adding supp to Q4
        $quarterSums['total'] += $effectiveBudget;
        $quarterSums['supplementary'] += $supplementary;
        $quarterSums['original'] += $item->total_amount;
    }
    arsort($byCategory);
}

    // Year-over-year for this department across all periods
    $yoyData = $this->deptYoY($department->id);

    // Category filter
    $categoryFilter = $request->category_id;
    if ($categoryFilter && $version) {
        foreach ($byCategory as $cat => $data) {
            $filtered = array_filter(
                $data['items'],
                fn($item) => $item->accountCode->account_category_id == $categoryFilter
            );
            if (empty($filtered)) {
                unset($byCategory[$cat]);
            }
        }
    }

    return view('reports.department', compact(
        'period','periods','departments','categories',
        'department','version','versionHistory',
        'byCategory','quarterSums','yoyData','categoryFilter'
    ));
}


        // ── Code Explorer ─────────────────────────────────


   public function codeExplorer(Request $request)
{
    $user       = auth()->user();
    $periods    = BudgetPeriod::orderByDesc('year')->get();
    $categories = AccountCategory::with('accountCodes')->orderBy('name')->get();
    $allCodes   = AccountCode::with('category')->orderBy('code')->get();

    $selectedCategory = $request->category_id
        ? AccountCategory::with('accountCodes')->find($request->category_id)
        : null;

    $selectedCode = $request->account_code_id
        ? AccountCode::with('category')->find($request->account_code_id)
        : null;

    $period = $this->resolvePeriod($request);

    $canViewAll = $user->hasAnyRole([
        'finance_reviewer','gceo','board','bdu_admin','super_admin'
    ]);

    $scopedDepartmentId = $canViewAll
        ? ($request->department_id ?: null)
        : $user->department_id;

    $departments = $canViewAll
        ? Department::where('is_active', true)->orderBy('name')->get()
        : collect([$user->department]);

    // ✅ Use the shared method
    $reportData = $this->buildCodeExplorerData($request);

    $reportTitle    = '';
    $reportSubtitle = '';

    if ($selectedCategory) {
        $reportTitle    = $selectedCategory->name;
        $reportSubtitle = $selectedCategory->code . ' — ' . count($reportData) . ' codes';
    } elseif ($selectedCode) {
        $reportTitle    = $selectedCode->code . ' — ' . $selectedCode->name;
        $reportSubtitle = $selectedCode->category->name;
    }

    return view('reports.code-explorer', compact(
        'periods', 'categories', 'allCodes', 'departments',
        'selectedCategory', 'selectedCode', 'period',
        'reportData', 'reportTitle', 'reportSubtitle',
        'canViewAll', 'scopedDepartmentId'
    ));
}


    // ── Year-over-Year ────────────────────────────────────────
    public function yoy(Request $request)
    {
        $periods     = BudgetPeriod::orderByDesc('year')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        $periodAId = $request->period_a ?? $periods->first()?->id;
        $periodBId = $request->period_b ?? $periods->skip(1)->first()?->id;

        $periodA = $periodAId ? BudgetPeriod::find($periodAId) : null;
        $periodB = $periodBId ? BudgetPeriod::find($periodBId) : null;

        $department = $request->department_id
            ? Department::find($request->department_id)
            : null;

        $comparison = $this->buildYoYComparison($periodA, $periodB, $department);

        $comparisonData = $this->buildYoYComparison($periodA, $periodB, $department);
        if (!empty($comparisonData)) {
            $comparisonData['budget_change'] = $comparisonData['budget_total_b'] - $comparisonData['budget_total_a'];
        }

        return view('reports.yoy', compact(
            'periods','departments',
            'periodA','periodB','department','comparison'
        ));
    }

   // ── Department Comparison ─────────────────────────────────
public function deptComparison(Request $request)
{
    $period      = $this->resolvePeriod($request);
    $periods     = BudgetPeriod::orderByDesc('year')->get();
    $departments = Department::where('is_active', true)->orderBy('name')->get();

    $selectedDeptIds = $request->dept_ids
        ? (is_array($request->dept_ids) ? $request->dept_ids : explode(',', $request->dept_ids))
        : $departments->take(5)->pluck('id')->toArray();

    $compData = [];

    if ($period) {
        foreach ($selectedDeptIds as $deptId) {
            $dept    = Department::find($deptId);
            $version = BudgetVersion::with('lineItems.accountCode.category')
                ->where('budget_period_id', $period->id)
                ->where('department_id',    $deptId)
                ->where('status', 'approved')
                ->orderByDesc('version_number')
                ->first();

            $original = $version?->lineItems->sum('total_amount') ?? 0;
            $supplementary = $version?->lineItems->sum(fn($i) => $i->approvedSupplementaryTotal()) ?? 0;
            $effective = $original + $supplementary;

            $compData[] = [
                'dept'  => $dept?->name,
                'code'  => $dept?->code,
                'q1'    => $version?->lineItems->sum('q1_amount') ?? 0,
                'q2'    => $version?->lineItems->sum('q2_amount') ?? 0,
                'q3'    => $version?->lineItems->sum('q3_amount') ?? 0,
                'q4'    => $version?->lineItems->sum('q4_amount') ?? 0,
                'original' => $original,
                'supplementary' => $supplementary,
                'total' => $effective, // ✅ Effective total
                'categories' => $version
                    ? $version->lineItems->groupBy('accountCode.category.name')
                        ->map(fn($items) => $items->sum(fn($item) => $item->effectiveBudget()))
                        ->sortDesc()->toArray()
                    : [],
            ];
        }
    }

    return view('reports.dept-comparison', compact(
        'period','periods','departments',
        'selectedDeptIds','compData'
    ));
}

    public function variance(Request $request)
{
    $period      = $this->resolvePeriod($request);
    $periods     = BudgetPeriod::orderByDesc('year')->get();
    $departments = Department::where('is_active', true)->orderBy('name')->get();

    $department = $request->department_id
        ? Department::find($request->department_id)
        : null;

    $minVariancePct = $request->min_variance ?? 0;
    $varianceFilter = $request->variance_filter ?? 'all'; // all, over, under

    $versions = BudgetVersion::with('department','lineItems.accountCode.category')
        ->where('budget_period_id', $period?->id)
        ->where('status', 'approved')
        ->when($department, fn($q) => $q->where('department_id', $department->id))
        ->get();

    // ✅ Build variance data once
    $varianceData = $this->buildVarianceData(
        $versions, $minVariancePct, $varianceFilter
    );

    // ✅ Calculate summary from $varianceData
    $totalBudget = 0;
    $totalActual = 0;
    $onBudget = 0;

    foreach ($varianceData as $category => $codes) {
        foreach ($codes as $code => $row) {
            $totalBudget += $row['budget'];
            $totalActual += $row['actual'];
            if ($row['variance'] == 0) {
                $onBudget++;
            }
        }
    }

    $summary = [
        'total_budget' => $totalBudget,
        'total_actual' => $totalActual,
        'on_budget'    => $onBudget,
    ];

    return view('reports.variance', compact(
        'period','periods','departments',
        'department',
        'varianceData',  // ✅ Pass as varianceData
        'summary',
        'minVariancePct','varianceFilter'
    ));
}


    // ── Utilisation ───────────────────────────────────────────
    public function utilisation(Request $request)
    {
        $period      = $this->resolvePeriod($request);
        $periods     = BudgetPeriod::orderByDesc('year')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        $versions = BudgetVersion::with('department','lineItems')
            ->where('budget_period_id', $period?->id)
            ->where('status', 'approved')
            ->get();

        $utilisation = $versions->map(function ($v) {
            $approved = $v->lineItems->sum('total_amount');

            $actual = \App\Models\BudgetActual::where('department_id', $v->department_id)
                ->where('budget_period_id', $v->period->id)
                ->where('status', 'confirmed')
                ->sum('amount');

            $pct      = $approved > 0 ? round(($actual/$approved)*100,1) : 0;

            return [
                'department'      => $v->department->name,
                'code'            => $v->department->code,
                'approved'        => $approved,
                'actual'          => $actual,
                'utilisation_pct' => $pct,
                'remaining'       => $approved - $actual,
                'status'          => $pct > 90 ? 'critical'
                    : ($pct > 70 ? 'warning' : 'healthy'),
            ];
        })->sortByDesc('utilisation_pct')->values();

        return view('reports.utilisation', compact(
            'period','periods','departments','utilisation'
        ));
    }

    // ── Virement Report ───────────────────────────────────────
    public function virement(Request $request)
    {
        $period      = $this->resolvePeriod($request);
        $periods     = BudgetPeriod::orderByDesc('year')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        $virements = Virement::with(
                'department',
                'fromLineItem.accountCode.category',
                'toLineItem.accountCode.category',
                'requestedBy','approvedBy'
            )
            ->when($period,                  fn($q) => $q->where('budget_period_id', $period->id))
            ->when($request->department_id,  fn($q) => $q->where('department_id', $request->department_id))
            ->when($request->status,         fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->get();

        // Stats
        $stats = [
            'total'    => $virements->count(),
            'approved' => $virements->where('status','approved')->count(),
            'pending'  => $virements->where('status','pending')->count(),
            'rejected' => $virements->where('status','rejected')->count(),
            'value'    => $virements->where('status','approved')->sum('amount'),
        ];

        return view('reports.virement', compact(
            'period','periods','departments','virements','stats'
        ));
    }

    // ── Flexed Budget ─────────────────────────────────────────
    public function flexed(Request $request)
    {
        $period      = $this->resolvePeriod($request);
        $periods     = BudgetPeriod::orderByDesc('year')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        $activityLevel = (float) $request->get('activity_level', 100);
        $department    = $request->department_id
            ? Department::find($request->department_id)
            : null;

        $versions = BudgetVersion::with('department','lineItems.accountCode.category')
            ->where('budget_period_id', $period?->id)
            ->where('status', 'approved')
            ->when($department, fn($q) => $q->where('department_id', $department->id))
            ->get();

        $flexed = $this->buildFlexedData($versions, $activityLevel);

        return view('reports.flexed', compact(
            'period','periods','departments',
            'department','flexed','activityLevel'
        ));
    }

    // ── Approved Budget ───────────────────────────────────────
    public function approved(Request $request)
    {
        $period      = $this->resolvePeriod($request);
        $periods     = BudgetPeriod::orderByDesc('year')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        $department = $request->department_id
            ? Department::find($request->department_id)
            : null;

        $versions = BudgetVersion::with('department','lineItems.accountCode.category')
            ->where('budget_period_id', $period?->id)
            ->where('status', 'approved')
            ->when($department, fn($q) => $q->where('department_id', $department->id))
            ->get();

        $data = $this->buildBudgetData($versions);

        return view('reports.approved', compact(
            'period','periods','departments','department','versions','data'
        ));
    }

    // ── Exports ───────────────────────────────────────────────
    public function exportApproved(Request $request)
    {
        $period   = $this->resolvePeriod($request);
        $filename = 'approved-budget-'.($period?->year ?? 'all').'.xlsx';
        return Excel::download(
            new BudgetExport($period?->id, $request->department_id, 'approved'),
            $filename
        );
    }

public function codeExplorerExport(Request $request)
{
    // ✅ Use the shared method instead of duplicating logic
    $reportData = $this->buildCodeExplorerData($request);

    if (empty($reportData)) {
        return back()->with('error', 'No data to export. Select a category or code first.');
    }

    AuditLogger::reportExported('code_explorer', 'xlsx', auth()->user());

    $filename = 'code-explorer-' . now()->format('Y-m-d') . '.xlsx';

    return Excel::download(
        new CodeExplorerExport($reportData),
        $filename
    );
}

    public function exportVariance(Request $request)
    {
        $period   = $this->resolvePeriod($request);
        $filename = 'variance-'.($period?->year ?? 'all').'.xlsx';
        return Excel::download(
            new VarianceExport($period?->id, $request->department_id),
            $filename
        );
    }

    public function exportUtilisation(Request $request)
    {
        $period   = $this->resolvePeriod($request);
        $filename = 'utilisation-'.($period?->year ?? 'all').'.xlsx';
        return Excel::download(
            new BudgetExport($period?->id, $request->department_id, 'utilisation'),
            $filename
        );
    }

    public function exportVirement(Request $request)
    {
        $period   = $this->resolvePeriod($request);
        $filename = 'virement-'.($period?->year ?? 'all').'.xlsx';
        return Excel::download(
            new BudgetExport($period?->id, $request->department_id, 'virement'),
            $filename
        );
    }

    public function exportPdf(Request $request, string $type)
    {
        $period   = $this->resolvePeriod($request);
        $versions = BudgetVersion::with('department','lineItems.accountCode.category')
            ->where('budget_period_id', $period?->id)
            ->where('status','approved')
            ->get();

        $data = $this->buildBudgetData($versions);

        $pdf = Pdf::loadView(
            "reports.pdf.{$type}",
            compact('period','data','versions')
        )->setPaper('a4','landscape');

        return $pdf->download("goil-{$type}-{$period?->year}.pdf");
    }

    // ── Private helpers ───────────────────────────────────────

    private function resolvePeriod(Request $request): ?BudgetPeriod
    {
        if ($request->period_id) {
            return BudgetPeriod::find($request->period_id);
        }
        return BudgetPeriod::current()
            ?? BudgetPeriod::orderByDesc('year')->first();
    }

    private function landingKpis(?BudgetPeriod $period): array
    {
        if (!$period) return [];

        $versions = BudgetVersion::where('budget_period_id', $period->id)->get();

        return [
            'approved_count'  => $versions->where('status','approved')->unique('department_id')->count(),
            'pending_count'   => $versions->whereIn('status',['submitted','under_review'])->unique('department_id')->count(),
            'total_value'     => BudgetLineItem::whereHas('budgetVersion', fn($q) =>
                $q->where('budget_period_id', $period->id)->where('status','approved')
            )->sum('total_amount'),
            'virement_count'  => Virement::where('budget_period_id', $period->id)->count(),
        ];
    }

   private function deptYoY(int $deptId): array
{
    $result = [];
    $periods = BudgetPeriod::orderBy('year')->get();

    foreach ($periods as $p) {
        $version = BudgetVersion::with('lineItems')
            ->where('budget_period_id', $p->id)
            ->where('department_id',    $deptId)
            ->where('status','approved')
            ->first();

        $result[] = [
            'year'  => $p->year,
            'name'  => $p->name,
            // ✅ Use effectiveTotal() to include supplementary
            'total' => $version?->effectiveTotal() ?? 0,
        ];
    }

    return $result;
}

private function buildYoYComparison(
    ?BudgetPeriod $periodA,
    ?BudgetPeriod $periodB,
    ?Department   $department
): array {
    if (!$periodA || !$periodB) return [];

    $getVersionItems = function (?BudgetPeriod $p) use ($department) {
        if (!$p) return collect();
        return BudgetLineItem::with('accountCode.category', 'budgetVersion.department')
            ->whereHas('budgetVersion', function ($q) use ($p, $department) {
                $q->where('budget_period_id', $p->id)->where('status', 'approved');
                if ($department) $q->where('department_id', $department->id);
            })
            ->get();
    };

    $itemsA = $getVersionItems($periodA);
    $itemsB = $getVersionItems($periodB);

    // Original budget per code
    $origA = $itemsA->groupBy('account_code_id')->map(fn($i) => $i->sum('total_amount'));
    $origB = $itemsB->groupBy('account_code_id')->map(fn($i) => $i->sum('total_amount'));

    // Supplementary per code
    $suppA = $itemsA->groupBy('account_code_id')->map(fn($i) => $i->sum(fn($x) => $x->approvedSupplementaryTotal()));
    $suppB = $itemsB->groupBy('account_code_id')->map(fn($i) => $i->sum(fn($x) => $x->approvedSupplementaryTotal()));

    // Effective budget per code (original + supplementary)
    $budgetA = $origA->map(fn($v, $code) => $v + ($suppA[$code] ?? 0));
    $budgetB = $origB->map(fn($v, $code) => $v + ($suppB[$code] ?? 0));

    $actualA = $itemsA->groupBy('account_code_id')->map(function ($codeItems) {
        $ids = $codeItems->pluck('id');
        return \App\Models\BudgetActual::whereIn('budget_line_item_id', $ids)
            ->where('status', 'confirmed')->sum('amount');
    });
    $actualB = $itemsB->groupBy('account_code_id')->map(function ($codeItems) {
        $ids = $codeItems->pluck('id');
        return \App\Models\BudgetActual::whereIn('budget_line_item_id', $ids)
            ->where('status', 'confirmed')->sum('amount');
    });

    $budgetAQ = $itemsA->groupBy('account_code_id')->map(fn($i) => [
        'q1'=>$i->sum('q1_amount'),'q2'=>$i->sum('q2_amount'),
        'q3'=>$i->sum('q3_amount'),'q4'=>$i->sum('q4_amount'),
    ]);
    $budgetBQ = $itemsB->groupBy('account_code_id')->map(fn($i) => [
        'q1'=>$i->sum('q1_amount'),'q2'=>$i->sum('q2_amount'),
        'q3'=>$i->sum('q3_amount'),'q4'=>$i->sum('q4_amount'),
    ]);

    $actualAQ = $itemsA->groupBy('account_code_id')->map(function ($codeItems) {
        $ids = $codeItems->pluck('id');
        $monthly = \App\Models\BudgetActual::whereIn('budget_line_item_id', $ids)
            ->where('status', 'confirmed')
            ->selectRaw('month, SUM(amount) as total')->groupBy('month')
            ->pluck('total', 'month')->toArray();
        return [
            'q1'=>($monthly[1]??0)+($monthly[2]??0)+($monthly[3]??0),
            'q2'=>($monthly[4]??0)+($monthly[5]??0)+($monthly[6]??0),
            'q3'=>($monthly[7]??0)+($monthly[8]??0)+($monthly[9]??0),
            'q4'=>($monthly[10]??0)+($monthly[11]??0)+($monthly[12]??0),
        ];
    });
    $actualBQ = $itemsB->groupBy('account_code_id')->map(function ($codeItems) {
        $ids = $codeItems->pluck('id');
        $monthly = \App\Models\BudgetActual::whereIn('budget_line_item_id', $ids)
            ->where('status', 'confirmed')
            ->selectRaw('month, SUM(amount) as total')->groupBy('month')
            ->pluck('total', 'month')->toArray();
        return [
            'q1'=>($monthly[1]??0)+($monthly[2]??0)+($monthly[3]??0),
            'q2'=>($monthly[4]??0)+($monthly[5]??0)+($monthly[6]??0),
            'q3'=>($monthly[7]??0)+($monthly[8]??0)+($monthly[9]??0),
            'q4'=>($monthly[10]??0)+($monthly[11]??0)+($monthly[12]??0),
        ];
    });

    $allCodes = $budgetA->keys()->merge($budgetB->keys())->unique();
    $rows     = [];

    foreach ($allCodes as $codeId) {
        $code = AccountCode::with('category')->find($codeId);

        $oA = (float)($origA->get($codeId, 0));
        $oB = (float)($origB->get($codeId, 0));
        $sA = (float)($suppA->get($codeId, 0));
        $sB = (float)($suppB->get($codeId, 0));
        $bA = (float)($budgetA->get($codeId, 0)); // effective
        $bB = (float)($budgetB->get($codeId, 0)); // effective
        $aA = (float)($actualA->get($codeId, 0));
        $aB = (float)($actualB->get($codeId, 0));

        $bAQ = $budgetAQ->get($codeId, ['q1'=>0,'q2'=>0,'q3'=>0,'q4'=>0]);
        $bBQ = $budgetBQ->get($codeId, ['q1'=>0,'q2'=>0,'q3'=>0,'q4'=>0]);
        $aAQ = $actualAQ->get($codeId, ['q1'=>0,'q2'=>0,'q3'=>0,'q4'=>0]);
        $aBQ = $actualBQ->get($codeId, ['q1'=>0,'q2'=>0,'q3'=>0,'q4'=>0]);

        $budgetChange    = $bB - $bA; // change in effective budget
        $budgetChangePct = $bA > 0 ? round(($budgetChange / $bA) * 100, 1) : null;

        $actualChange    = $aB - $aA;
        $actualChangePct = $aA > 0 ? round(($actualChange / $aA) * 100, 1) : null;

        $utilA = $bA > 0 ? round(($aA / $bA) * 100, 1) : 0;
        $utilB = $bB > 0 ? round(($aB / $bB) * 100, 1) : 0;

        $rows[] = [
            'code'              => $code?->code,
            'name'              => $code?->name,
            'category'          => $code?->category?->name,

            'original_a'        => $oA,                 // ← NEW
            'supplementary_a'   => $sA,                  // ← NEW
            'budget_a'          => $bA,                  // effective
            'actual_a'          => $aA,
            'variance_a'        => $aA - $bA,
            'utilisation_a'     => $utilA,
            'budget_a_q'        => $bAQ,
            'actual_a_q'        => $aAQ,

            'original_b'        => $oB,                  // ← NEW
            'supplementary_b'   => $sB,                  // ← NEW
            'budget_b'          => $bB,                  // effective
            'actual_b'          => $aB,
            'variance_b'        => $aB - $bB,
            'utilisation_b'     => $utilB,
            'budget_b_q'        => $bBQ,
            'actual_b_q'        => $aBQ,

            'budget_change'     => $budgetChange,
            'budget_change_pct' => $budgetChangePct,
            'actual_change'     => $actualChange,
            'actual_change_pct' => $actualChangePct,

            'budget_trend'      => $budgetChange > 0 ? 'up' : ($budgetChange < 0 ? 'down' : 'flat'),
            'actual_trend'      => $actualChange  > 0 ? 'up' : ($actualChange  < 0 ? 'down' : 'flat'),
        ];
    }

    usort($rows, fn($x, $y) => abs($y['budget_change']) <=> abs($x['budget_change']));

    return [
        'rows' => $rows,

        // KPI cards — now correctly include supplementary
        'original_total_a'      => $itemsA->sum('total_amount'),
        'supplementary_total_a' => $itemsA->sum(fn($i) => $i->approvedSupplementaryTotal()),
        'budget_total_a'        => $itemsA->sum('total_amount') + $itemsA->sum(fn($i) => $i->approvedSupplementaryTotal()),
        'actual_total_a'        => $actualA->sum(),

        'original_total_b'      => $itemsB->sum('total_amount'),
        'supplementary_total_b' => $itemsB->sum(fn($i) => $i->approvedSupplementaryTotal()),
        'budget_total_b'        => $itemsB->sum('total_amount') + $itemsB->sum(fn($i) => $i->approvedSupplementaryTotal()),
        'actual_total_b'        => $actualB->sum(),

        'budget_change'  => 0, // computed below after totals are known
        'actual_change'  => $actualB->sum() - $actualA->sum(),
    ];
}

private function buildBudgetData($versions): array
{
    $raw = [];

    foreach ($versions as $version) {
        foreach ($version->lineItems()->with('accountCode.category')->get() as $item) {
            $cat  = $item->accountCode->category->name;
            $code = $item->accountCode->code;

            if (!isset($raw[$cat][$code])) {
                $raw[$cat][$code] = [
                    'name'          => $item->accountCode->name,
                    'q1'            => 0,
                    'q2'            => 0,
                    'q3'            => 0,
                    'q4'            => 0,
                    'original'      => 0,
                    'supplementary' => 0,
                    'total'         => 0, // effective = original + supplementary
                ];
            }

            $supp = $item->approvedSupplementaryTotal();

            $raw[$cat][$code]['q1']            += $item->q1_amount;
            $raw[$cat][$code]['q2']            += $item->q2_amount;
            $raw[$cat][$code]['q3']            += $item->q3_amount;
            $raw[$cat][$code]['q4']            += $item->q4_amount + $supp; // supplementary shown in Q4 bucket
            $raw[$cat][$code]['original']      += $item->total_amount;
            $raw[$cat][$code]['supplementary'] += $supp;
            $raw[$cat][$code]['total']          = $raw[$cat][$code]['original'] + $raw[$cat][$code]['supplementary'];
        }
    }

    return $raw;
}

private function buildVarianceData($versions, float $minPct = 0, string $filter = 'all'): array
{
    $raw = $this->buildBudgetData($versions);

    $versionIds = $versions->pluck('id');

    $actualsByCode = \App\Models\BudgetActual::whereHas('lineItem', function ($q) use ($versionIds) {
            $q->whereIn('budget_version_id', $versionIds);
        })
        ->where('status', 'confirmed')
        ->join('budget_line_items', 'budget_actuals.budget_line_item_id', '=', 'budget_line_items.id')
        ->join('account_codes', 'budget_line_items.account_code_id', '=', 'account_codes.id')
        ->selectRaw('account_codes.code as account_code, SUM(budget_actuals.amount) as total')
        ->groupBy('account_codes.code')
        ->pluck('total', 'account_code');

    $result = [];

    foreach ($raw as $cat => $codes) {
        foreach ($codes as $code => $vals) {

            $actual   = (float) ($actualsByCode[$code] ?? 0);
            $variance = $actual - $vals['total']; // total = effective budget
            $pct      = $vals['total'] > 0
                ? round(($variance / $vals['total']) * 100, 1)
                : 0;

            if (abs($pct) < $minPct) continue;
            if ($filter === 'over'  && $variance <= 0) continue;
            if ($filter === 'under' && $variance >= 0) continue;

            $result[$cat][$code] = [
                'name'          => $vals['name'],
                'original'      => $vals['original'],
                'supplementary' => $vals['supplementary'],
                'budget'        => $vals['total'], // effective
                'actual'        => $actual,
                'variance'      => $variance,
                'pct'           => $pct,
                'status'        => $variance > 0 ? 'overspend'
                    : ($variance < 0 ? 'underspend' : 'on_budget'),
            ];
        }
    }

    return $result;
}

    private function buildFlexedData($versions, float $activityLevel): array
    {
        $data   = $this->buildBudgetData($versions);
        $factor = $activityLevel / 100;
        $result = [];

        foreach ($data as $cat => $codes) {
            foreach ($codes as $code => $vals) {
                $result[$cat][$code] = [
                    'name'      => $vals['name'],
                    'original'  => $vals['total'],
                    'q1'        => $vals['q1'],
                    'q2'        => $vals['q2'],
                    'q3'        => $vals['q3'],
                    'q4'        => $vals['q4'],
                    'flexed'      => round($vals['total'] * $factor, 2),
                    'q1_flexed'   => round($vals['q1'] * $factor, 2),
                    'q2_flexed'   => round($vals['q2'] * $factor, 2),
                    'q3_flexed'   => round($vals['q3'] * $factor, 2),
                    'q4_flexed'   => round($vals['q4'] * $factor, 2),
                    'difference'  => round($vals['total'] * $factor - $vals['total'], 2),
                ];
            }
        }

        return $result;
    }


    /**
 * Build Code Explorer data - shared between codeExplorer() and codeExplorerExport()
 */
private function buildCodeExplorerData(Request $request): array
{
    $period = $this->resolvePeriod($request);

    $user       = auth()->user();
    $canViewAll = $user->hasAnyRole(['finance_reviewer','gceo','board','bdu_admin','super_admin']);
    $scopedDepartmentId = $canViewAll ? ($request->department_id ?: null) : $user->department_id;

    $selectedCategory = $request->category_id
        ? AccountCategory::with('accountCodes')->find($request->category_id)
        : null;

    $selectedCode = $request->account_code_id
        ? AccountCode::with('category')->find($request->account_code_id)
        : null;

    $buildCodeData = function (array $codeIds) use ($period, $scopedDepartmentId): array {
        $result = [];

        foreach ($codeIds as $codeId) {
            $code = AccountCode::with('category')->find($codeId);
            if (!$code) continue;

            $items = BudgetLineItem::with('budgetVersion.department')
                ->where('account_code_id', $codeId)
                ->whereHas('budgetVersion', function ($q) use ($period, $scopedDepartmentId) {
                    $q->where('status', 'approved');
                    if ($period)             $q->where('budget_period_id', $period->id);
                    if ($scopedDepartmentId) $q->where('department_id', $scopedDepartmentId);
                })
                ->get();

            if ($items->isEmpty()) continue;

            $lineItemIds = $items->pluck('id');

            $monthlyActuals = \App\Models\BudgetActual::whereIn('budget_line_item_id', $lineItemIds)
                ->where('status', 'confirmed')
                ->selectRaw('month, SUM(amount) as total')
                ->groupBy('month')
                ->pluck('total', 'month')
                ->toArray();

            $qActuals = [
                'q1' => ($monthlyActuals[1]??0)+($monthlyActuals[2]??0)+($monthlyActuals[3]??0),
                'q2' => ($monthlyActuals[4]??0)+($monthlyActuals[5]??0)+($monthlyActuals[6]??0),
                'q3' => ($monthlyActuals[7]??0)+($monthlyActuals[8]??0)+($monthlyActuals[9]??0),
                'q4' => ($monthlyActuals[10]??0)+($monthlyActuals[11]??0)+($monthlyActuals[12]??0),
            ];
            $totalActual = array_sum($qActuals);

            $budgetQ1 = $items->sum('q1_amount');
            $budgetQ2 = $items->sum('q2_amount');
            $budgetQ3 = $items->sum('q3_amount');
            $budgetQ4 = $items->sum('q4_amount');

            $budgetOriginal      = $items->sum('total_amount');
            $budgetSupplementary = $items->sum(fn($i) => $i->approvedSupplementaryTotal());
            $budgetTotal         = $budgetOriginal + $budgetSupplementary; // effective

            // Per-department breakdown — NOW INCLUDES SUPPLEMENTARY
            $deptBreakdown = [];
            foreach ($items as $item) {
                $dept = $item->budgetVersion->department;

                $itemMonthly = \App\Models\BudgetActual::where('budget_line_item_id', $item->id)
                    ->where('status', 'confirmed')
                    ->selectRaw('month, SUM(amount) as total')
                    ->groupBy('month')
                    ->pluck('total', 'month')
                    ->toArray();

                $itemQActuals = [
                    'q1' => ($itemMonthly[1]??0)+($itemMonthly[2]??0)+($itemMonthly[3]??0),
                    'q2' => ($itemMonthly[4]??0)+($itemMonthly[5]??0)+($itemMonthly[6]??0),
                    'q3' => ($itemMonthly[7]??0)+($itemMonthly[8]??0)+($itemMonthly[9]??0),
                    'q4' => ($itemMonthly[10]??0)+($itemMonthly[11]??0)+($itemMonthly[12]??0),
                ];

                $itemSupp           = $item->approvedSupplementaryTotal();
                $itemOriginalTotal  = $item->total_amount;
                $itemEffectiveTotal = $itemOriginalTotal + $itemSupp;

                $deptBreakdown[] = [
                    'dept'              => $dept->name,
                    'dept_code'         => $dept->code,
                    'budget_q1'         => $item->q1_amount,
                    'budget_q2'         => $item->q2_amount,
                    'budget_q3'         => $item->q3_amount,
                    'budget_q4'         => $item->q4_amount,
                    'budget_original'   => $itemOriginalTotal,
                    'supplementary'     => $itemSupp,
                    'budget_total'      => $itemEffectiveTotal,
                    'actual_q1'         => $itemQActuals['q1'],
                    'actual_q2'         => $itemQActuals['q2'],
                    'actual_q3'         => $itemQActuals['q3'],
                    'actual_q4'         => $itemQActuals['q4'],
                    'actual_total'      => array_sum($itemQActuals),
                    'monthly'           => $itemMonthly,
                    'variance'          => array_sum($itemQActuals) - $itemEffectiveTotal,
                ];
            }

            $yearTrend = BudgetLineItem::with('budgetVersion.period')
                ->where('account_code_id', $codeId)
                ->whereHas('budgetVersion', function ($q) use ($scopedDepartmentId) {
                    $q->where('status', 'approved');
                    if ($scopedDepartmentId) $q->where('department_id', $scopedDepartmentId);
                })
                ->get()
                ->groupBy(fn($i) => $i->budgetVersion->period->year)
                ->map(function ($yearItems) {
                    $ids = $yearItems->pluck('id');
                    $actualTotal = \App\Models\BudgetActual::whereIn('budget_line_item_id', $ids)
                        ->where('status', 'confirmed')->sum('amount');

                    $origTotal = $yearItems->sum('total_amount');
                    $suppTotal = $yearItems->sum(fn($i) => $i->approvedSupplementaryTotal());

                    return [
                        'budget' => $origTotal + $suppTotal,
                        'actual' => $actualTotal,
                    ];
                })
                ->sortKeys()
                ->toArray();

            $result[] = [
                'code_id'         => $codeId,
                'code'            => $code->code,
                'name'            => $code->name,
                'category'        => $code->category->name,
                'budget_q1'       => $budgetQ1,
                'budget_q2'       => $budgetQ2,
                'budget_q3'       => $budgetQ3,
                'budget_q4'       => $budgetQ4 + $budgetSupplementary,
                'budget_original' => $budgetOriginal,
                'budget_supplementary' => $budgetSupplementary,
                'budget_total'    => $budgetTotal,
                'actual_q1'       => $qActuals['q1'],
                'actual_q2'       => $qActuals['q2'],
                'actual_q3'       => $qActuals['q3'],
                'actual_q4'       => $qActuals['q4'],
                'actual_total'    => $totalActual,
                'monthly_actuals' => $monthlyActuals,
                'variance'        => $totalActual - $budgetTotal,
                'utilisation'     => $budgetTotal > 0
                    ? round(($totalActual / $budgetTotal) * 100, 1) : 0,
                'dept_breakdown'  => $deptBreakdown,
                'year_trend'      => $yearTrend,
            ];
        }

        return $result;
    };

    if ($selectedCategory) {
        return $buildCodeData($selectedCategory->accountCodes->pluck('id')->toArray());
    } elseif ($selectedCode) {
        return $buildCodeData([$selectedCode->id]);
    }

    return [];
}

}
