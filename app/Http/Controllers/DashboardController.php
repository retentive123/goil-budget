<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\BudgetPeriod;
use App\Models\BudgetVersion;
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
        $user    = Auth::user();
        $periods = BudgetPeriod::orderByDesc('year')->get();

        // Resolve selected period
        $selectedPeriodId = $request->period_id;
        $selectedYear     = $request->year;

        if ($selectedPeriodId) {
            $currentPeriod = BudgetPeriod::find($selectedPeriodId);
        } elseif ($selectedYear) {
            $currentPeriod = BudgetPeriod::where('year', $selectedYear)->first();
        } else {
            $currentPeriod = BudgetPeriod::current()
                ?? BudgetPeriod::orderByDesc('year')->first();
        }

        $years = BudgetPeriod::distinct()->orderByDesc('year')->pluck('year');

        $data = [
            'currentPeriod'    => $currentPeriod,
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

        // ── Department user / head ──
        if ($user->hasAnyRole(['department_user', 'department_head'])) {
            $data = array_merge($data, $this->getDepartmentData($currentPeriod, $user));
        }

        // ── Finance / Admin / Approver ──
        if ($user->hasAnyRole(['finance_reviewer', 'gceo', 'board', 'bdu_admin', 'super_admin'])) {
            $data = array_merge($data, $this->getFinanceData($currentPeriod, $user));
        }

        return view('dashboard', $data);
    }

    /**
     * Get department user data
     */
    private function getDepartmentData($currentPeriod, $user): array
    {
        $data = [];

        $myBudget = $currentPeriod
            ? BudgetVersion::where('budget_period_id', $currentPeriod->id)
                ->where('department_id', $user->department_id)
                ->orderByDesc('version_number')->first()
            : null;

        $data['myBudget'] = $myBudget;
        $data['myVirements'] = Virement::where('department_id', $user->department_id)
                                       ->where('status', 'pending')->count();

        $data['versionHistory'] = $currentPeriod
            ? BudgetVersion::where('budget_period_id', $currentPeriod->id)
                ->where('department_id', $user->department_id)
                ->orderByDesc('version_number')->get()
            : collect();

        if ($myBudget) {
            $data['quarterTotals'] = BudgetLineItem::where('budget_version_id', $myBudget->id)
                ->selectRaw('SUM(q1_amount) as q1, SUM(q2_amount) as q2,
                             SUM(q3_amount) as q3, SUM(q4_amount) as q4')
                ->first();

            $data['topItems'] = BudgetLineItem::where('budget_version_id', $myBudget->id)
                ->with('accountCode.category')
                ->orderByDesc('total_amount')
                ->limit(5)->get();

            // Section totals for department view
            $allItems = BudgetLineItem::where('budget_version_id', $myBudget->id)
                ->with('accountCode.category')->get();
            $rev = $allItems->filter(fn($i) => in_array($i->accountCode->category->budget_type, ['revenue','both']))->sum(fn($i) => $i->effectiveBudget());
            $exp = $allItems->filter(fn($i) => $i->accountCode->category->budget_type === 'expense')->sum(fn($i) => $i->effectiveBudget());
            $cx  = $allItems->filter(fn($i) => $i->accountCode->category->budget_type === 'capital_expenditure')->sum(fn($i) => $i->effectiveBudget());
            $bl  = $allItems->filter(fn($i) => in_array($i->accountCode->category->budget_type, ['assets','liabilities']))->sum(fn($i) => $i->effectiveBudget());
            $data['mySectionTotals'] = [
                'revenue' => $rev, 'expense' => $exp,
                'net'     => $rev - $exp, 'capex' => $cx, 'balance' => $bl,
            ];

            // Monthly actuals for dept
            $data['monthlyActuals'] = \App\Models\BudgetActual::where('department_id', $user->department_id)
                ->where('budget_period_id', $currentPeriod->id)
                ->where('status', 'confirmed')
                ->selectRaw('month, SUM(amount) as total')
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month')
                ->toArray();
        }

        // All periods summary for this dept
        $data['deptPeriodSummary'] = BudgetVersion::where('department_id', $user->department_id)
            ->where('status', 'approved')
            ->with('period', 'lineItems')
            ->get()
            ->map(fn($v) => [
                'period' => $v->period->name,
                'year'   => $v->period->year,
                // ✅ Use effectiveTotal() here
                'budget' => $v->effectiveTotal(),
                'actual' => \App\Models\BudgetActual::where('department_id', $user->department_id)
                    ->where('budget_period_id', $v->budget_period_id)
                    ->where('status', 'confirmed')->sum('amount'),
            ])
            ->sortBy('year')->values();

        return $data;
    }

    /**
     * Get finance/admin data with caching
     */
    private function getFinanceData($currentPeriod, $user): array
    {
        $data = [];

        // ── Cache Key ──
        $cacheKey = "dashboard.finance." . ($currentPeriod?->id ?? 'all') . "." . $user->id;

        // ── Expensive queries wrapped in cache ──
        $expensiveData = Cache::remember($cacheKey, 300, function () use ($currentPeriod) {

            // Dept budgets for chart
            $deptBudgets = [];
            if ($currentPeriod) {
                BudgetVersion::where('budget_period_id', $currentPeriod->id)
                    ->where('status', 'approved')
                    ->with('department', 'lineItems')
                    ->get()
                    ->each(function ($v) use (&$deptBudgets) {
                        $actual = \App\Models\BudgetActual::where('department_id', $v->department_id)
                            ->where('budget_period_id', $v->budget_period_id)
                            ->where('status', 'confirmed')->sum('amount');
                        $deptBudgets[] = [
                            'name'   => $v->department->name,
                            'code'   => $v->department->code,
                            // ✅ Use effectiveTotal() here
                            'total'  => $v->effectiveTotal(),
                            'actual' => $actual,
                            'q1'     => $v->lineItems->sum('q1_amount'),
                            'q2'     => $v->lineItems->sum('q2_amount'),
                            'q3'     => $v->lineItems->sum('q3_amount'),
                            'q4'     => $v->lineItems->sum('q4_amount'),
                        ];
                    });
                usort($deptBudgets, fn($a, $b) => $b['total'] <=> $a['total']);
            }

            // Category breakdown + section totals
            $categoryBreakdown = [];
            $sectionTotals = ['revenue' => 0, 'expense' => 0, 'capex' => 0, 'balance' => 0];
            if ($currentPeriod) {
                $items = BudgetLineItem::whereHas('budgetVersion', fn($q) =>
                        $q->where('budget_period_id', $currentPeriod->id)->where('status', 'approved')
                    )
                    ->with('accountCode.category')->get()
                    ->groupBy('accountCode.category.name');

                foreach ($items as $cat => $catItems) {
                    $budgetType = $catItems->first()->accountCode->category->budget_type ?? 'expense';
                    $total      = $catItems->sum(fn($item) => $item->effectiveBudget());
                    $categoryBreakdown[] = [
                        'name'        => $cat,
                        'total'       => $total,
                        'budget_type' => $budgetType,
                    ];
                    $section = match(true) {
                        in_array($budgetType, ['revenue', 'both'])        => 'revenue',
                        $budgetType === 'capital_expenditure'             => 'capex',
                        in_array($budgetType, ['assets', 'liabilities'])  => 'balance',
                        default                                           => 'expense',
                    };
                    $sectionTotals[$section] += $total;
                }
                usort($categoryBreakdown, fn($a, $b) => $b['total'] <=> $a['total']);
            }

            // Year-over-year summary
            $yoySummary = BudgetPeriod::orderBy('year')->get()->map(function ($p) {
                $budget = BudgetVersion::where('budget_period_id', $p->id)
                    ->where('status', 'approved')->get()
                    // ✅ Use effectiveTotal() here
                    ->sum(fn($v) => $v->effectiveTotal());
                $actual = \App\Models\BudgetActual::where('budget_period_id', $p->id)
                    ->where('status', 'confirmed')->sum('amount');
                return [
                    'year'   => $p->year,
                    'name'   => $p->name,
                    'budget' => $budget,
                    'actual' => $actual
                ];
            })->filter(fn($r) => $r['budget'] > 0)->values();

            return compact('deptBudgets', 'categoryBreakdown', 'sectionTotals', 'yoySummary');
        });

        // ── Merge cached data ──
        $data = array_merge($data, $expensiveData);

        // ── Non-cached finance data ──
        $allVersions = $currentPeriod
            ? BudgetVersion::where('budget_period_id', $currentPeriod->id)
                ->with('department')->get()
            : collect();

        $data['pendingApprovals'] = $allVersions
            ->filter(fn($v) => in_array($v->status, ['submitted', 'under_review']))
            ->filter(fn($v) => $this->approvalService->canCurrentUserDecide($v));

        $totalDepts     = Department::where('is_active', true)->count();
        $submittedDepts = $allVersions->whereIn('status', ['submitted', 'under_review', 'approved'])
                                      ->unique('department_id')->count();
        $approvedDepts  = $allVersions->where('status', 'approved')->unique('department_id')->count();
        $rejectedDepts  = $allVersions->where('status', 'rejected')->unique('department_id')->count();

        $data['periodStats'] = [
            'total'       => $totalDepts,
            'submitted'   => $submittedDepts,
            'approved'    => $approvedDepts,
            'rejected'    => $rejectedDepts,
            'draft'       => $allVersions->where('status', 'draft')->unique('department_id')->count(),
            'not_started' => max(0, $totalDepts - $allVersions->unique('department_id')->count()),
        ];

        // ✅ Use effectiveTotal() here
        $data['totalApprovedValue'] = $currentPeriod
            ? BudgetVersion::where('budget_period_id', $currentPeriod->id)
                ->where('status', 'approved')->get()
                ->sum(fn($v) => $v->effectiveTotal())
            : 0;

        $data['totalActualValue'] = $currentPeriod
            ? \App\Models\BudgetActual::where('budget_period_id', $currentPeriod->id)
                ->where('status', 'confirmed')->sum('amount')
            : 0;

        // Dept statuses
        $data['deptStatuses'] = Department::where('is_active', true)->orderBy('name')->get()
            ->map(function ($dept) use ($currentPeriod) {
                $version = $currentPeriod
                    ? BudgetVersion::where('budget_period_id', $currentPeriod->id)
                        ->where('department_id', $dept->id)
                        ->orderByDesc('version_number')->first()
                    : null;
                return [
                    'name'    => $dept->name,
                    'code'    => $dept->code,
                    'id'      => $dept->id,
                    'status'  => $version?->status ?? 'not_started',
                    'version' => $version?->version_number ?? 0,
                    // ✅ Use effectiveTotal() here
                    'total'   => $version ? $version->effectiveTotal() : 0,
                    'version_id' => $version?->id,
                ];
            });

        $data['pendingVirements'] = Virement::where('status', 'pending')->count();

        // Monthly actuals trend (this is lightweight, no need to cache)
        $data['monthlyActualsTrend'] = $currentPeriod
            ? \App\Models\BudgetActual::where('budget_period_id', $currentPeriod->id)
                ->where('status', 'confirmed')
                ->selectRaw('month, SUM(amount) as total')
                ->groupBy('month')->orderBy('month')
                ->pluck('total', 'month')->toArray()
            : [];

        return $data;
    }
}
