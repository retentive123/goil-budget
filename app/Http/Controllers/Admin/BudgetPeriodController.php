<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BudgetPeriod;
use Illuminate\Http\Request;
use App\Services\AuditLogger;

class BudgetPeriodController extends Controller
{
    public function index()
    {
        $periods = BudgetPeriod::withCount('budgetVersions')
                               ->orderByDesc('year')
                               ->paginate(20);

        return view('admin.budget-periods.index', compact('periods'));
    }

    public function create()
    {
        return view('admin.budget-periods.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255',  'unique:budget_periods,name'],
            'year'       => ['required', 'integer', 'min:2020', 'max:2100'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after:start_date'],
        ]);

        BudgetPeriod::create([
            ...$validated,
            'status'     => 'draft',
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.budget-periods.index')
            ->with('success', 'Budget period created successfully.');
    }

    public function show(BudgetPeriod $budgetPeriod)
    {
        $budgetPeriod->load('budgetVersions.department');
        return view('admin.budget-periods.show', compact('budgetPeriod'));
    }

    public function edit(BudgetPeriod $budgetPeriod)
    {
        if ($budgetPeriod->status !== 'draft') {
            return back()->with('error', 'Only draft periods can be edited.');
        }

        return view('admin.budget-periods.edit', compact('budgetPeriod'));
    }

    public function update(Request $request, BudgetPeriod $budgetPeriod)
    {
        if ($budgetPeriod->status !== 'draft') {
            return back()->with('error', 'Only draft periods can be edited.');
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('budget_periods', 'name')->ignore($budgetPeriod->id),
            ],
            'year'       => ['required', 'integer', 'min:2020', 'max:2100'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after:start_date'],
        ]);

        $budgetPeriod->update($validated);

        return redirect()->route('admin.budget-periods.index')
            ->with('success', 'Budget period updated successfully.');
    }

    public function destroy(BudgetPeriod $budgetPeriod)
    {
        if ($budgetPeriod->status !== 'draft') {
            return back()->with('error', 'Only draft periods can be deleted.');
        }

        $budgetPeriod->delete();

        return redirect()->route('admin.budget-periods.index')
            ->with('success', 'Budget period deleted.');
    }

    public function open(BudgetPeriod $budgetPeriod)
    {
        // Only one period can be open at a time
        if (BudgetPeriod::where('status', 'open')->exists()) {
            return back()->with('error', 'Another budget period is already open. Close it first.');
        }

        if ($budgetPeriod->status !== 'draft') {
            return back()->with('error', 'Only draft periods can be opened.');
        }

        $budgetPeriod->update([
            'status'    => 'open',
            'opened_at' => now(),
        ]);

        AuditLogger::periodOpened($budgetPeriod, auth()->user());

        return back()->with('success', "{$budgetPeriod->name} is now open for budget submissions.");
    }

    public function close(BudgetPeriod $budgetPeriod)
    {
        if ($budgetPeriod->status !== 'open') {
            return back()->with('error', 'Only open periods can be closed.');
        }

        $budgetPeriod->update([
            'status'    => 'closed',
            'closed_at' => now(),
        ]);

        AuditLogger::periodClosed($budgetPeriod, auth()->user());

        return back()->with('success', "{$budgetPeriod->name} has been closed.");
    }
}
