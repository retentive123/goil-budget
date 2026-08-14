<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\BudgetPeriod;
use App\Models\BudgetVersion;
use App\Models\BudgetActual;
use App\Models\Department;
use App\Models\Virement;
use App\Models\BudgetLineItem;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __construct(
        protected ApprovalService $approvalService
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();

        $periods          = BudgetPeriod::orderByDesc('year')->orderByDesc('id')->get();
        $selectedPeriodId = $request->period_id;
        $selectedYear     = $request->year;
        $isAllPeriods     = !$selectedPeriodId && !$selectedYear;

        // Resolve the period used for filtering
        if ($selectedPeriodId) {
            $currentPeriod = BudgetPeriod::find($selectedPeriodId);
        } elseif ($selectedYear) {
            $currentPeriod = BudgetPeriod::where('year', $selectedYear)
                ->orderByDesc('id')->first();
        } else {
            // No filter — pick the open period, or fall back to the latest
            $currentPeriod = BudgetPeriod::current()
                ?? BudgetPeriod::orderByDesc('year')->orderByDesc('id')->first();
        }

        // Years list: all distinct years that have budget periods, plus the current calendar year
        $periodYears = BudgetPeriod::select('year')->distinct()->orderByDesc('year')->pluck('year');
        $years = $periodYears->push(now()->year)->unique()->sortDesc()->values();

        $data = [
            'currentPeriod'    => $currentPeriod,
            'isAllPeriods'     => $isAllPeriods,
            'periods'          => $periods,
            'years'            => $years,
            'selectedPeriodId' => $selectedPeriodId,
            'selectedYear'     => $selectedYear,
            'user'             => $user,
            'unreadCount'      => $user->unreadNotifications()->count(),
            'recentNotifs'     => $user->budgetNotifications()
                                       ->orderByDesc('created_at')
                                       ->limit(5)->get(),
        ];

        if ($user->hasAnyRole(['department_user', 'department_head'])) {
            $data = array_merge($data, $this->getDepartmentData($currentPeriod, $user));
        }

        if ($user->hasAnyRole(['finance_reviewer', 'gceo', 'board', 'bdu_admin', 'super_admin'])) {
            $data = array_merge($data, $this->getFinanceData($currentPeriod, $user, $isAllPeriods));
        }

        return view('dashboard', $data);
    }

    // ── Department user ────────────────────────────────────────────────────────

    private function getDepartmentData($currentPeriod, $user): array
    {
        $data = [];

        $myBudget = $currentPeriod
            ? BudgetVersion::where('budget_period_id', $currentPeriod->id)
                ->where('department_id', $user->department_id)
                ->orderByDesc('version_number')->first()
            : null;

        $data['myBudget']      = $myBudget;
        $data['myVirements']   = Virement::where('department_id', $user->department_id)
                                         ->where('status', 'pending')->count();
        $data['versionHistory'] = $currentPeriod
            ? BudgetVersion::where('budget_period_id', $currentPeriod->id)
                ->where('department_id', $user->department_id)
                ->orderByDesc('version_number')->get()
            : collect();

        if ($myBudget) {
            $data['quarterTotals'] = BudgetLineItem::where('budget_version_id', $myBudget->id)
                ->selectRaw('
                    SUM(m1_amount+m2_amount+m3_amount)    as q1,
                    SUM(m4_amount+m5_amount+m6_amount)    as q2,
                    SUM(m7_amount+m8_amount+m9_amount)    as q3,
                    SUM(m10_amount+m11_amount+m12_amount) as q4
                ')->first();

            $data['topItems'] = BudgetLineItem::where('budget_version_id', $myBudget->id)
                ->with('accountCode.category')
                ->orderByDesc('total_amount')
                ->limit(5)->get();

            $allItems = BudgetLineItem::where('budget_version_id', $myBudget->id)
                ->with('accountCode.category')->get();
            $rev = $allItems->filter(fn($i) => in_array($i->accountCode->category->budget_type, ['revenue','both']))
                            ->sum(fn($i) => $i->effectiveBudget());
            $exp = $allItems->filter(fn($i) => $i->accountCode->category->budget_type === 'expense')
                            ->sum(fn($i) => $i->effectiveBudget());
            $cx  = $allItems->filter(fn($i) => $i->accountCode->category->budget_type === 'capital_expenditure')
                            ->sum(fn($i) => $i->effectiveBudget());
            $bl  = $allItems->filter(fn($i) => in_array($i->accountCode->category->budget_type, ['assets','liabilities']))
                            ->sum(fn($i) => $i->effectiveBudget());

            $data['mySectionTotals'] = [
                'revenue' => $rev, 'expense' => $exp,
                'net'     => $rev - $exp, 'capex' => $cx, 'balance' => $bl,
            ];

            $data['monthlyActuals'] = BudgetActual::where('department_id', $user->department_id)
                ->where('budget_period_id', $currentPeriod->id)
                ->where('status', 'confirmed')
                ->selectRaw('month, SUM(amount) as total')
                ->groupBy('month')->orderBy('month')
                ->pluck('total', 'month')->toArray();
        }

        $data['deptPeriodSummary'] = BudgetVersion::where('department_id', $user->department_id)
            ->where('status', 'approved')
            ->with('period', 'lineItems')
            ->get()
            ->map(fn($v) => [
                'period' => $v->period->name,
                'year'   => $v->period->year,
                'budget' => $v->effectiveTotal(),
                'actual' => BudgetActual::where('department_id', $user->department_id)
                    ->where('budget_period_id', $v->budget_period_id)
                    ->where('status', 'confirmed')->sum('amount'),
            ])
            ->sortBy('year')->values();

        return $data;
    }

    // ── Finance / Admin ────────────────────────────────────────────────────────

    private function getFinanceData($currentPeriod, $user, bool $isAllPeriods): array
    {
        $data = [];

        // Cache key distinguishes "all periods" from a specific period
        $cacheKey = 'dashboard.finance.' . ($isAllPeriods ? 'all' : ($currentPeriod?->id ?? 'none')) . '.' . $user->id;

        $expensiveData = Cache::remember($cacheKey, 300, function () use ($currentPeriod, $isAllPeriods) {

            // ── Department budget totals (for bar chart) ──
            // Use DB aggregates instead of loading line items into PHP memory.
            $deptBudgets = [];

            // Batch-load actuals keyed by department_id (one query)
            $actualsQuery = BudgetActual::where('status', 'confirmed')
                ->selectRaw('department_id, SUM(amount) as total')
                ->groupBy('department_id');
            if (!$isAllPeriods && $currentPeriod) {
                $actualsQuery->where('budget_period_id', $currentPeriod->id);
            }
            $actualsByDept = $actualsQuery->pluck('total', 'department_id');

            // Batch-load budget totals via DB SUM (no lineItems eager load)
            $budgetQuery = BudgetVersion::where('status', 'approved')
                ->with('department')
                ->join('budget_line_items', 'budget_versions.id', '=', 'budget_line_items.budget_version_id')
                ->selectRaw('budget_versions.department_id,
                             MAX(budget_versions.id) as version_id,
                             SUM(budget_line_items.total_amount) as grand_total')
                ->groupBy('budget_versions.department_id');
            if (!$isAllPeriods && $currentPeriod) {
                $budgetQuery->where('budget_versions.budget_period_id', $currentPeriod->id);
            }
            $budgetQuery->get()->each(function ($row) use ($actualsByDept, &$deptBudgets) {
                $dept = Department::find($row->department_id);
                if (!$dept) return;
                $deptBudgets[] = [
                    'name'   => $dept->name,
                    'code'   => $dept->code,
                    'total'  => (float) $row->grand_total,
                    'actual' => (float) ($actualsByDept->get($row->department_id) ?? 0),
                ];
            });
            usort($deptBudgets, fn($a, $b) => $b['total'] <=> $a['total']);

            // ── Section / category totals ──
            $sectionTotals = ['revenue' => 0, 'expense' => 0, 'capex' => 0, 'balance' => 0];

            $itemsQuery = BudgetLineItem::whereHas('budgetVersion', function ($q) use ($currentPeriod, $isAllPeriods) {
                $q->where('status', 'approved');
                if (!$isAllPeriods && $currentPeriod) {
                    $q->where('budget_period_id', $currentPeriod->id);
                }
            })->with('accountCode.category');

            $items = $itemsQuery->get();

            $categoryBreakdown = [];
            foreach ($items->groupBy('accountCode.category.name') as $cat => $catItems) {
                $budgetType = $catItems->first()->accountCode->category->budget_type ?? 'expense';
                $total      = $catItems->sum(fn($item) => $item->effectiveBudget());
                $categoryBreakdown[] = ['name' => $cat, 'total' => $total, 'budget_type' => $budgetType];
                $section = match(true) {
                    in_array($budgetType, ['revenue', 'both'])       => 'revenue',
                    $budgetType === 'capital_expenditure'            => 'capex',
                    in_array($budgetType, ['assets', 'liabilities']) => 'balance',
                    default                                          => 'expense',
                };
                $sectionTotals[$section] += $total;
            }
            usort($categoryBreakdown, fn($a, $b) => $b['total'] <=> $a['total']);

            // ── Year-over-year ──
            $yoySummary = BudgetPeriod::orderBy('year')->get()->map(function ($p) {
                $budget = BudgetVersion::where('budget_period_id', $p->id)
                    ->where('status', 'approved')->get()
                    ->sum(fn($v) => $v->effectiveTotal());
                $actual = BudgetActual::where('budget_period_id', $p->id)
                    ->where('status', 'confirmed')->sum('amount');
                return ['year' => $p->year, 'name' => $p->name, 'budget' => $budget, 'actual' => $actual];
            })->filter(fn($r) => $r['budget'] > 0)->values();

            return compact('deptBudgets', 'categoryBreakdown', 'sectionTotals', 'yoySummary');
        });

        $data = array_merge($data, $expensiveData);

        // ── Non-cached: period submission stats (current period → health bar) ──
        $allVersions = $currentPeriod
            ? BudgetVersion::where('budget_period_id', $currentPeriod->id)
                ->with('department')->get()
            : collect();

        $data['pendingApprovals'] = ($isAllPeriods
            ? BudgetVersion::whereIn('status', ['submitted', 'under_review'])->with('department')->get()
            : $allVersions->filter(fn($v) => in_array($v->status, ['submitted', 'under_review']))
        )->filter(fn($v) => $this->approvalService->canCurrentUserDecide($v));

        $totalDepts     = Department::where('is_active', true)->count();
        $submittedDepts = $allVersions->whereIn('status', ['submitted', 'under_review', 'approved'])
                                      ->unique('department_id')->count();
        $rejectedDepts  = $allVersions->where('status', 'rejected')->unique('department_id')->count();

        // Approved counts — scoped correctly to the active filter
        // ($allVersions is always current-period only, so for "all periods" we query separately)
        $approvedDeptIdsQuery = BudgetVersion::where('status', 'approved');
        if (!$isAllPeriods && $currentPeriod) {
            $approvedDeptIdsQuery->where('budget_period_id', $currentPeriod->id);
        }
        $approvedDeptIds = $approvedDeptIdsQuery->pluck('department_id')->unique();

        $approvedByType = Department::whereIn('id', $approvedDeptIds)
            ->where('is_active', true)
            ->selectRaw('entity_type, COUNT(*) as cnt')
            ->groupBy('entity_type')
            ->pluck('cnt', 'entity_type');

        $approvedDeptCount    = (int) ($approvedByType['department']      ?? 0);
        $approvedStationCount = (int) ($approvedByType['service_station'] ?? 0);
        $approvedTotal        = $approvedDeptCount + $approvedStationCount;

        $data['periodStats'] = [
            'total'             => $totalDepts,
            'submitted'         => $submittedDepts,
            'approved'          => $approvedTotal,
            'approved_depts'    => $approvedDeptCount,
            'approved_stations' => $approvedStationCount,
            'rejected'          => $rejectedDepts,
            'draft'             => $allVersions->where('status', 'draft')->unique('department_id')->count(),
            'not_started'       => max(0, $totalDepts - $allVersions->unique('department_id')->count()),
        ];

        // ── Totals ──
        if ($isAllPeriods) {
            $data['totalApprovedValue'] = BudgetVersion::where('status', 'approved')
                ->get()->sum(fn($v) => $v->effectiveTotal());
            $data['totalActualValue'] = BudgetActual::where('status', 'confirmed')->sum('amount');
        } else {
            $data['totalApprovedValue'] = $currentPeriod
                ? BudgetVersion::where('budget_period_id', $currentPeriod->id)
                    ->where('status', 'approved')->get()->sum(fn($v) => $v->effectiveTotal())
                : 0;
            $data['totalActualValue'] = $currentPeriod
                ? BudgetActual::where('budget_period_id', $currentPeriod->id)
                    ->where('status', 'confirmed')->sum('amount')
                : 0;
        }

        // ── Department statuses — batch load to avoid N+1 ──
        // Get the latest version per department in one query using a subquery
        $depts = Department::where('is_active', true)->orderBy('name')->get();

        if ($isAllPeriods) {
            // Latest version overall per dept (max id = most recent)
            $latestIds = BudgetVersion::selectRaw('MAX(id) as id')
                ->groupBy('department_id')->pluck('id');
            $latestVersions = BudgetVersion::whereIn('id', $latestIds)
                ->get()->keyBy('department_id');
        } else {
            $latestVersions = $currentPeriod
                ? BudgetVersion::where('budget_period_id', $currentPeriod->id)
                    ->orderByDesc('version_number')
                    ->get()
                    ->groupBy('department_id')
                    ->map(fn($g) => $g->first())  // highest version_number per dept
                : collect();
        }

        // effectiveTotal() needs lineItems — eager load them in one go
        $versionIds = $latestVersions->pluck('id')->filter();
        if ($versionIds->count()) {
            $lineItemTotals = \App\Models\BudgetLineItem::whereIn('budget_version_id', $versionIds)
                ->selectRaw('budget_version_id, SUM(total_amount) as grand_total')
                ->groupBy('budget_version_id')
                ->pluck('grand_total', 'budget_version_id');
        } else {
            $lineItemTotals = collect();
        }

        $data['deptStatuses'] = $depts->map(function ($dept) use ($latestVersions, $lineItemTotals) {
            $version = $latestVersions->get($dept->id);
            return [
                'name'       => $dept->name,
                'code'       => $dept->code,
                'id'         => $dept->id,
                'status'     => $version?->status ?? 'not_started',
                'version'    => $version?->version_number ?? 0,
                'total'      => $version ? (float) ($lineItemTotals->get($version->id) ?? 0) : 0,
                'version_id' => $version?->id,
            ];
        });

        $data['pendingVirements'] = Virement::where('status', 'pending')->count();

        // ── Monthly actuals trend ──
        if ($isAllPeriods) {
            $data['monthlyActualsTrend'] = BudgetActual::where('status', 'confirmed')
                ->selectRaw('month, SUM(amount) as total')
                ->groupBy('month')->orderBy('month')
                ->pluck('total', 'month')->toArray();
        } else {
            $data['monthlyActualsTrend'] = $currentPeriod
                ? BudgetActual::where('budget_period_id', $currentPeriod->id)
                    ->where('status', 'confirmed')
                    ->selectRaw('month, SUM(amount) as total')
                    ->groupBy('month')->orderBy('month')
                    ->pluck('total', 'month')->toArray()
                : [];
        }

        return $data;
    }
}
