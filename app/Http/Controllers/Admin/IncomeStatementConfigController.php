<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountSubCategory;
use App\Models\IncomeStatementConfig;
use Illuminate\Http\Request;

class IncomeStatementConfigController extends Controller
{
    public function index()
    {
        $configs = IncomeStatementConfig::withCount('lines')
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();

        return view('admin.income-statement-config.index', compact('configs'));
    }

    public function create()
    {
        return view('admin.income-statement-config.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);

        $config = IncomeStatementConfig::create(['name' => $request->name]);

        return redirect()->route('admin.income-statement-configs.edit', $config)
            ->with('success', 'Layout created. Add your statement lines below.');
    }

    public function edit(IncomeStatementConfig $incomeStatementConfig)
    {
        $config = $incomeStatementConfig->load('lines.subCategory', 'lines.csBase');

        $subCategories = AccountSubCategory::where('is_active', true)
            ->whereIn('budget_type', ['revenue', 'expense'])
            ->orderBy('budget_type')
            ->orderBy('sort_order')
            ->get();

        return view('admin.income-statement-config.edit', compact('config', 'subCategories'));
    }

    public function update(Request $request, IncomeStatementConfig $incomeStatementConfig)
    {
        $request->validate([
            'name'                           => 'required|string|max:255',
            'lines'                          => 'array',
            'lines.*.line_type'              => 'required|in:sub_category,subtotal,spacer',
            'lines.*.sub_category_id'        => 'nullable|exists:account_sub_categories,id',
            'lines.*.label'                  => 'nullable|string|max:255',
            'lines.*.operator'               => 'nullable|in:add,subtract',
            'lines.*.cs_base_sub_category_id'=> 'nullable|exists:account_sub_categories,id',
            'lines.*.cs_base_subtotal_label' => 'nullable|string|max:255',
        ]);

        $incomeStatementConfig->update(['name' => $request->name]);

        // Replace all lines with the submitted ordered set
        $incomeStatementConfig->lines()->delete();

        foreach ($request->input('lines', []) as $i => $line) {
            $incomeStatementConfig->lines()->create([
                'line_type'                => $line['line_type'],
                'sub_category_id'          => $line['sub_category_id'] ?: null,
                'label'                    => $line['label'] ?: null,
                'operator'                 => $line['operator'] ?: null,
                'sort_order'               => $i,
                'cs_base_sub_category_id'  => $line['cs_base_sub_category_id'] ?: null,
                'cs_base_subtotal_label'   => $line['cs_base_subtotal_label']  ?: null,
            ]);
        }

        return redirect()->route('admin.income-statement-configs.edit', $incomeStatementConfig)
            ->with('success', 'Layout saved successfully.');
    }

    public function activate(IncomeStatementConfig $incomeStatementConfig)
    {
        // Only one config active at a time
        IncomeStatementConfig::query()->update(['is_active' => false]);
        $incomeStatementConfig->update(['is_active' => true]);

        return redirect()->route('admin.income-statement-configs.index')
            ->with('success', '"' . $incomeStatementConfig->name . '" is now the active layout.');
    }

    public function deactivate(IncomeStatementConfig $incomeStatementConfig)
    {
        $incomeStatementConfig->update(['is_active' => false]);

        return redirect()->route('admin.income-statement-configs.index')
            ->with('success', 'Layout deactivated — the standard income statement will be used.');
    }

    public function destroy(IncomeStatementConfig $incomeStatementConfig)
    {
        $incomeStatementConfig->lines()->delete();
        $incomeStatementConfig->delete();

        return redirect()->route('admin.income-statement-configs.index')
            ->with('success', 'Layout deleted.');
    }
}
