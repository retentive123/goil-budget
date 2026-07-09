<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountCategory;
use App\Models\AccountSubCategory;
use Illuminate\Http\Request;

class AccountCategoryController extends Controller
{
    public function index()
    {
        $categories = AccountCategory::withCount('accountCodes')
                                     ->with('subCategory')
                                     ->orderBy('code')
                                     ->get();

        $subCategories = AccountSubCategory::where('is_active', true)
            ->orderBy('budget_type')->orderBy('sort_order')->orderBy('name')
            ->get()->groupBy('budget_type');

        return view('admin.account-categories.index', compact('categories', 'subCategories'));
    }

    public function create()
    {
        $subCategories = AccountSubCategory::where('is_active', true)
            ->orderBy('budget_type')->orderBy('sort_order')->orderBy('name')
            ->get()->groupBy('budget_type');

        return view('admin.account-categories.create', compact('subCategories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                    => ['required', 'string', 'max:255', 'unique:account_categories,name'],
            'code'                    => ['required', 'string', 'max:20',  'unique:account_categories,code'],
            'description'             => ['nullable', 'string'],
            'budget_type'             => ['required','in:revenue,expense,both,capital_expenditure,assets,liabilities,ex_pump_item'],
            'account_sub_category_id' => ['nullable', 'exists:account_sub_categories,id'],
        ]);

        AccountCategory::create([...$validated, 'is_active' => true]);

        return redirect()->route('admin.account-categories.index')
            ->with('success', 'Account category created successfully.');
    }

    public function show(AccountCategory $accountCategory)
    {
        $accountCategory->loadCount('accountCodes');
        $accountCategory->load('accountCodes');

        return view('admin.account-categories.show', compact('accountCategory'));
    }

    public function edit(AccountCategory $accountCategory)
    {
        $subCategories = AccountSubCategory::where('is_active', true)
            ->orderBy('budget_type')->orderBy('sort_order')->orderBy('name')
            ->get()->groupBy('budget_type');

        return view('admin.account-categories.edit', compact('accountCategory', 'subCategories'));
    }

    public function update(Request $request, AccountCategory $accountCategory)
    {
        $validated = $request->validate([
            'name'                    => ['required', 'string', 'max:255',
                                          'unique:account_categories,name,' . $accountCategory->id],
            'code'                    => ['required', 'string', 'max:20',
                                          'unique:account_categories,code,' . $accountCategory->id],
            'description'             => ['nullable', 'string'],
            'is_active'               => ['boolean'],
            'budget_type'             => ['required','in:revenue,expense,both,capital_expenditure,assets,liabilities,ex_pump_item'],
            'account_sub_category_id' => ['nullable', 'exists:account_sub_categories,id'],
        ]);

        $accountCategory->update($validated);

        return redirect()->route('admin.account-categories.index')
            ->with('success', 'Account category updated successfully.');
    }

    public function destroy(AccountCategory $accountCategory)
    {
        if ($accountCategory->accountCodes()->count()) {
            return back()->with('error',
                'Cannot delete a category that has account codes assigned to it. ' .
                'Deactivate it instead, or reassign its codes first.'
            );
        }

        $accountCategory->delete();

        return redirect()->route('admin.account-categories.index')
            ->with('success', 'Account category deleted.');
    }

    public function bulkAssignSubCategory(Request $request)
    {
        $request->validate([
            'ids'                     => 'required|string',
            'account_sub_category_id' => 'nullable|exists:account_sub_categories,id',
        ]);

        $ids = array_filter(explode(',', $request->input('ids', '')));

        if (empty($ids)) {
            return back()->with('error', 'No categories selected.');
        }

        $subCatId = $request->input('account_sub_category_id') ?: null;

        AccountCategory::whereIn('id', $ids)->update(['account_sub_category_id' => $subCatId]);

        $count   = count($ids);
        $subName = $subCatId
            ? AccountSubCategory::find($subCatId)?->name
            : 'None';

        return redirect()->route('admin.account-categories.index')
            ->with('success', "Sub-category set to \"{$subName}\" for {$count} " . str('category')->plural($count) . '.');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = array_filter(explode(',', $request->input('ids', '')));

        if (empty($ids)) {
            return back()->with('error', 'No categories selected.');
        }

        $deleted  = 0;
        $skipped  = 0;

        foreach (AccountCategory::whereIn('id', $ids)->get() as $cat) {
            if ($cat->accountCodes()->count() > 0) {
                $skipped++;
            } else {
                $cat->delete();
                $deleted++;
            }
        }

        $msg = "{$deleted} " . str('category')->plural($deleted) . " deleted.";
        if ($skipped) {
            $msg .= " {$skipped} skipped (have codes assigned).";
        }

        return redirect()->route('admin.account-categories.index')
            ->with($skipped && !$deleted ? 'error' : 'success', $msg);
    }
}
