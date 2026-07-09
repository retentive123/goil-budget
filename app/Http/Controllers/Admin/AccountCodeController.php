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
        $categories  = AccountCategory::where('is_active', true)->orderBy('name')->get();
        $expumpCodes = $this->expumpCodeOptions();
        return view('admin.account-codes.create', compact('categories', 'expumpCodes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_category_id' => ['required', 'exists:account_categories,id'],
            'code'                => ['required', 'string', 'max:50', 'unique:account_codes,code'],
            'name'                => ['required', 'string', 'max:255'],
            'description'         => ['nullable', 'string'],
            'unit'                => ['nullable', 'string', 'max:50'],
            'calc_type'           => ['nullable', 'string', 'in:values,calculation'],
            'sort_order'          => ['nullable', 'integer'],
        ]);

        $calcConfig = null;
        if (($validated['calc_type'] ?? 'values') === 'calculation' && $request->filled('calc_config')) {
            $calcConfig = json_decode($request->input('calc_config'), true) ?: null;
        }

        AccountCode::create([...$validated, 'calc_config' => $calcConfig, 'is_active' => true]);

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
        $categories  = AccountCategory::where('is_active', true)->orderBy('name')->get();
        $expumpCodes = $this->expumpCodeOptions($accountCode->id);
        return view('admin.account-codes.edit', compact('accountCode', 'categories', 'expumpCodes'));
    }

    public function update(Request $request, AccountCode $accountCode)
    {
        $validated = $request->validate([
            'account_category_id' => ['required', 'exists:account_categories,id'],
            'code'                => ['required', 'string', 'max:50', 'unique:account_codes,code,' . $accountCode->id],
            'name'                => ['required', 'string', 'max:255'],
            'description'         => ['nullable', 'string'],
            'is_active'           => ['boolean'],
            'unit'                => ['nullable', 'string', 'max:50'],
            'calc_type'           => ['nullable', 'string', 'in:values,calculation'],
            'sort_order'          => ['nullable', 'integer'],
        ]);

        $calcConfig = null;
        if (($validated['calc_type'] ?? $accountCode->calc_type) === 'calculation' && $request->filled('calc_config')) {
            $calcConfig = json_decode($request->input('calc_config'), true) ?: null;
        }
        $validated['calc_config'] = $calcConfig;

        $accountCode->update($validated);

        return redirect()->route('admin.account-codes.index')
            ->with('success', 'Account code updated successfully.');
    }

    private function expumpCodeOptions(?int $excludeId = null)
    {
        $catIds = AccountCategory::where('budget_type', 'ex_pump_item')->pluck('id');
        return AccountCode::whereIn('account_category_id', $catIds)
            ->where('is_active', true)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'code', 'name', 'calc_type']);
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

    public function bulkDestroy(Request $request)
    {
        $ids = array_filter(explode(',', $request->input('ids', '')));

        if (empty($ids)) {
            return back()->with('error', 'No codes selected.');
        }

        $deleted = 0;
        $skipped = 0;

        foreach (AccountCode::whereIn('id', $ids)->get() as $code) {
            if ($code->lineItems()->count() > 0) {
                $skipped++;
            } else {
                $code->delete();
                $deleted++;
            }
        }

        $msg = "{$deleted} " . str('code')->plural($deleted) . " deleted.";
        if ($skipped) {
            $msg .= " {$skipped} skipped (used in budget entries).";
        }

        return redirect()->route('admin.account-codes.index')
            ->with($skipped && !$deleted ? 'error' : 'success', $msg)
            ->withInput(request()->except('ids', '_method', '_token'));
    }
}
