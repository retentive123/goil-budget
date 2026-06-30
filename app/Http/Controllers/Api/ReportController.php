<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BudgetVersion;
use App\Models\BudgetPeriod;
use App\Models\BudgetLineItem;
use App\Models\BudgetActual;
use App\Models\Department;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    // GET /api/reports/summary
    public function summary(Request $request)
    {
        $period = $request->period_id
            ? BudgetPeriod::find($request->period_id)
            : (BudgetPeriod::current() ?? BudgetPeriod::orderByDesc('year')->first());

        if (!$period) {
            return response()->json(['message' => 'No period found.'], 404);
        }

        $versions     = BudgetVersion::where('budget_period_id', $period->id)->get();
        $totalDepts   = Department::where('is_active', true)->count();
        $totalBudget  = BudgetVersion::where('budget_period_id', $period->id)
            ->where('status','approved')->get()
            ->sum(fn($v) => $v->lineItems()->sum('total_amount'));
        $totalActual  = BudgetActual::where('budget_period_id', $period->id)
            ->where('status','confirmed')->sum('amount');

        return response()->json([
            'period'   => ['id' => $period->id, 'name' => $period->name, 'year' => $period->year],
            'stats'    => [
                'total_departments' => $totalDepts,
                'approved'          => $versions->where('status','approved')->unique('department_id')->count(),
                'in_review'         => $versions->whereIn('status',['submitted','under_review'])->unique('department_id')->count(),
                'rejected'          => $versions->where('status','rejected')->unique('department_id')->count(),
                'draft'             => $versions->where('status','draft')->unique('department_id')->count(),
                'not_started'       => max(0, $totalDepts - $versions->unique('department_id')->count()),
            ],
            'financials' => [
                'total_approved_budget' => $totalBudget,
                'total_actual_spend'    => $totalActual,
                'utilisation_pct'       => $totalBudget > 0
                    ? round(($totalActual / $totalBudget) * 100, 1) : 0,
                'remaining'             => $totalBudget - $totalActual,
            ],
        ]);
    }

    // GET /api/reports/departments
    public function departments(Request $request)
    {
        $period = $request->period_id
            ? BudgetPeriod::find($request->period_id)
            : BudgetPeriod::current();

        $data = Department::where('is_active', true)->orderBy('name')->get()
            ->map(function ($dept) use ($period) {
                $version = $period
                    ? BudgetVersion::where('budget_period_id', $period->id)
                        ->where('department_id', $dept->id)
                        ->where('status', 'approved')
                        ->first()
                    : null;

                $budget = $version?->lineItems()->sum('total_amount') ?? 0;
                $actual = $period
                    ? BudgetActual::where('department_id', $dept->id)
                        ->where('budget_period_id', $period?->id)
                        ->where('status','confirmed')->sum('amount')
                    : 0;

                return [
                    'department'      => $dept->name,
                    'code'            => $dept->code,
                    'budget'          => $budget,
                    'actual'          => $actual,
                    'utilisation_pct' => $budget > 0 ? round(($actual/$budget)*100,1) : 0,
                    'variance'        => $actual - $budget,
                ];
            });

        return response()->json(['data' => $data, 'period' => $period?->name]);
    }

    // GET /api/reports/variance
    public function variance(Request $request)
    {
        $period = $request->period_id
            ? BudgetPeriod::find($request->period_id)
            : BudgetPeriod::current();

        $items = BudgetLineItem::whereHas('budgetVersion', fn($q) =>
                $q->where('budget_period_id', $period?->id)->where('status','approved')
            )
            ->with('accountCode.category','budgetVersion.department')
            ->get()
            ->map(function ($item) {
                $actual = BudgetActual::where('budget_line_item_id', $item->id)
                    ->where('status','confirmed')->sum('amount');

                return [
                    'department'   => $item->budgetVersion->department->name,
                    'category'     => $item->accountCode->category->name,
                    'code'         => $item->accountCode->code,
                    'name'         => $item->accountCode->name,
                    'budget'       => $item->total_amount,
                    'actual'       => $actual,
                    'variance'     => $actual - $item->total_amount,
                    'variance_pct' => $item->total_amount > 0
                        ? round((($actual - $item->total_amount) / $item->total_amount) * 100, 1)
                        : 0,
                ];
            });

        return response()->json(['data' => $items, 'period' => $period?->name]);
    }
}
