<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountCategory;
use Illuminate\Http\Request;

class AccountCategoryController extends Controller
{
    public function index()
    {
        $categories = AccountCategory::withCount('accountCodes')
                                     ->orderBy('name')
                                     ->paginate(20);

        return view('admin.account-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.account-categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:account_categories,name'],
            'code'        => ['required', 'string', 'max:20',  'unique:account_categories,code'],
            'description' => ['nullable', 'string'],
            'budget_type' => ['required','in:revenue,expense,both'],
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
        return view('admin.account-categories.edit', compact('accountCategory'));
    }

    public function update(Request $request, AccountCategory $accountCategory)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255',
                              'unique:account_categories,name,' . $accountCategory->id],
            'code'        => ['required', 'string', 'max:20',
                              'unique:account_categories,code,' . $accountCategory->id],
            'description' => ['nullable', 'string'],
            'is_active'   => ['boolean'],
            'budget_type' => ['required','in:revenue,expense,both'],
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
}
