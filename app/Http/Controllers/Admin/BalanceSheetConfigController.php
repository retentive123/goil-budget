<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountSubCategory;
use App\Models\BalanceSheetConfig;
use Illuminate\Http\Request;

class BalanceSheetConfigController extends Controller
{
    public function index()
    {
        $configs = BalanceSheetConfig::withCount('lines')
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();

        return view('admin.balance-sheet-config.index', compact('configs'));
    }

    public function create()
    {
        return view('admin.balance-sheet-config.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);

        $config = BalanceSheetConfig::create(['name' => $request->name]);

        return redirect()->route('admin.balance-sheet-configs.edit', $config)
            ->with('success', 'Layout created. Add your balance sheet lines below.');
    }

    public function edit(BalanceSheetConfig $balanceSheetConfig)
    {
        $config = $balanceSheetConfig->load('lines.subCategory');

        $subCategories = AccountSubCategory::where('is_active', true)
            ->whereIn('budget_type', ['assets', 'liabilities'])
            ->orderBy('budget_type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy('budget_type');

        return view('admin.balance-sheet-config.edit', compact('config', 'subCategories'));
    }

    public function update(Request $request, BalanceSheetConfig $balanceSheetConfig)
    {
        $request->validate([
            'name'                 => 'required|string|max:255',
            'lines'                => 'array',
            'lines.*.line_type'    => 'required|in:sub_category,subtotal,spacer',
            'lines.*.sub_category_id' => 'nullable|exists:account_sub_categories,id',
            'lines.*.label'        => 'nullable|string|max:255',
            'lines.*.section'      => 'nullable|in:assets,liabilities',
        ]);

        $balanceSheetConfig->update(['name' => $request->name]);

        $balanceSheetConfig->lines()->delete();

        foreach ($request->input('lines', []) as $i => $line) {
            $balanceSheetConfig->lines()->create([
                'line_type'       => $line['line_type'],
                'sub_category_id' => $line['sub_category_id'] ?: null,
                'label'           => $line['label'] ?: null,
                'section'         => $line['section'] ?: null,
                'sort_order'      => $i,
            ]);
        }

        return redirect()->route('admin.balance-sheet-configs.edit', $balanceSheetConfig)
            ->with('success', 'Layout saved successfully.');
    }

    public function activate(BalanceSheetConfig $balanceSheetConfig)
    {
        BalanceSheetConfig::query()->update(['is_active' => false]);
        $balanceSheetConfig->update(['is_active' => true]);

        return redirect()->route('admin.balance-sheet-configs.index')
            ->with('success', '"' . $balanceSheetConfig->name . '" is now the active balance sheet layout.');
    }

    public function deactivate(BalanceSheetConfig $balanceSheetConfig)
    {
        $balanceSheetConfig->update(['is_active' => false]);

        return redirect()->route('admin.balance-sheet-configs.index')
            ->with('success', 'Layout deactivated — the standard balance sheet will be used.');
    }

    public function destroy(BalanceSheetConfig $balanceSheetConfig)
    {
        $balanceSheetConfig->lines()->delete();
        $balanceSheetConfig->delete();

        return redirect()->route('admin.balance-sheet-configs.index')
            ->with('success', 'Layout deleted.');
    }
}
