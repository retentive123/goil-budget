<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountCode;
use App\Models\AccountCategory;
use Illuminate\Http\Request;

class AccountCodeController extends Controller
{
   public function index(Request $request)
    {
        // Start the query with category and departments relationships
        $query = AccountCode::with(['category', 'departments']);

        // Apply category filter if present
        if ($request->filled('category')) {
            $query->where('account_category_id', $request->category);
        }

        // Apply search if present
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                  ->orWhere('name', 'LIKE', "%{$search}%");
            });
        }

        // Apply status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Get per_page value (default 10)
        $perPage = $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100, 200]) ? $perPage : 10;

        // Order and paginate with query string preservation
        $codes = $query->orderBy('code')
                       ->paginate($perPage)
                       ->withQueryString(); // This preserves filters in pagination links

        $categories = AccountCategory::orderBy('name')->get();

        return view('admin.account-codes.index', compact('codes', 'categories'));
    }

    public function create()
    {
        $categories = AccountCategory::where('is_active', true)->orderBy('name')->get();
        return view('admin.account-codes.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_category_id' => ['required', 'exists:account_categories,id'],
            'code'                => ['required', 'string', 'max:50', 'unique:account_codes,code'],
            'name'                => ['required', 'string', 'max:255'],
            'description'         => ['nullable', 'string'],
        ]);

        AccountCode::create([...$validated, 'is_active' => true]);

        return redirect()->route('admin.account-codes.index')
            ->with('success', 'Account code created successfully.');
    }

    public function show(AccountCode $accountCode)
    {
        $accountCode->load('category', 'departments');
        return view('admin.account-codes.show', compact('accountCode'));
    }

    public function edit(AccountCode $accountCode)
    {
        $categories = AccountCategory::where('is_active', true)->orderBy('name')->get();
        return view('admin.account-codes.edit', compact('accountCode', 'categories'));
    }

    public function update(Request $request, AccountCode $accountCode)
    {
        $validated = $request->validate([
            'account_category_id' => ['required', 'exists:account_categories,id'],
            'code'                => ['required', 'string', 'max:50', 'unique:account_codes,code,' . $accountCode->id],
            'name'                => ['required', 'string', 'max:255'],
            'description'         => ['nullable', 'string'],
            'is_active'           => ['boolean'],
        ]);

        $accountCode->update($validated);

        return redirect()->route('admin.account-codes.index')
            ->with('success', 'Account code updated successfully.');
    }

    public function destroy(AccountCode $accountCode)
    {
        if ($accountCode->lineItems()->count()) {
            return back()->with('error', 'Cannot delete an account code that is used in budget entries.');
        }

        $accountCode->delete();

        return redirect()->route('admin.account-codes.index')
            ->with('success', 'Account code deleted successfully.');
    }
}
