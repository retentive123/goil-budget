<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\BudgetVersion;
use App\Models\BudgetPeriod;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::where('is_active', true)
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn($d) => [
                'id'         => $d->id,
                'name'       => $d->name,
                'code'       => $d->code,
                'user_count' => $d->users_count,
            ]);

        return response()->json(['data' => $departments]);
    }

    public function show(Request $request, Department $department)
    {
        $period = BudgetPeriod::current() ?? BudgetPeriod::orderByDesc('year')->first();

        $version = $period
            ? BudgetVersion::where('budget_period_id', $period->id)
                ->where('department_id', $department->id)
                ->where('status', 'approved')
                ->with('lineItems.accountCode.category')
                ->first()
            : null;

        return response()->json([
            'id'             => $department->id,
            'name'           => $department->name,
            'code'           => $department->code,
            'current_period' => $period?->name,
            'budget_status'  => $version?->status ?? 'no_budget',
            'budget_total'   => $version?->lineItems->sum('total_amount') ?? 0,
        ]);
    }
}
