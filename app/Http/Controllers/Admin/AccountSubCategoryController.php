<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountSubCategory;
use Illuminate\Http\Request;

class AccountSubCategoryController extends Controller
{
    private const TYPES = [
        'revenue'             => 'Revenue',
        'expense'             => 'Expense',
        'assets'              => 'Assets',
        'liabilities'         => 'Liabilities',
        'capital_expenditure' => 'Capital Expenditure',
    ];

    public function index()
    {
        $subCategories = AccountSubCategory::withCount('categories')
            ->orderBy('budget_type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy('budget_type');

        return view('admin.account-sub-categories.index', [
            'subCategories' => $subCategories,
            'typeLabels'    => self::TYPES,
        ]);
    }

    public function create()
    {
        return view('admin.account-sub-categories.create', [
            'typeLabels' => self::TYPES,
            'nextSort'   => [],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'budget_type' => ['required', 'in:' . implode(',', array_keys(self::TYPES))],
            'sort_order'  => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $validated['sort_order'] = $validated['sort_order']
            ?? AccountSubCategory::where('budget_type', $validated['budget_type'])->max('sort_order') + 1;

        AccountSubCategory::create([...$validated, 'is_active' => true]);

        return redirect()->route('admin.account-sub-categories.index')
            ->with('success', "Sub-category \"{$validated['name']}\" created.");
    }

    public function edit(AccountSubCategory $accountSubCategory)
    {
        return view('admin.account-sub-categories.edit', [
            'sub'        => $accountSubCategory,
            'typeLabels' => self::TYPES,
        ]);
    }

    public function update(Request $request, AccountSubCategory $accountSubCategory)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'budget_type' => ['required', 'in:' . implode(',', array_keys(self::TYPES))],
            'sort_order'  => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active'   => ['boolean'],
        ]);

        $accountSubCategory->update($validated);

        return redirect()->route('admin.account-sub-categories.index')
            ->with('success', "Sub-category updated.");
    }

    public function destroy(AccountSubCategory $accountSubCategory)
    {
        if ($accountSubCategory->categories()->count()) {
            return back()->with('error',
                "Cannot delete \"{$accountSubCategory->name}\" — it is assigned to {$accountSubCategory->categories()->count()} category/categories. Reassign them first.");
        }

        $accountSubCategory->delete();

        return redirect()->route('admin.account-sub-categories.index')
            ->with('success', "Sub-category deleted.");
    }
}
