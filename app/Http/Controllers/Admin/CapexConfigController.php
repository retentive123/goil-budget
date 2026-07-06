<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountSubCategory;
use App\Models\CapexConfig;
use Illuminate\Http\Request;

class CapexConfigController extends Controller
{
    public function index()
    {
        $configs = CapexConfig::withCount('lines')
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();

        return view('admin.capex-config.index', compact('configs'));
    }

    public function create()
    {
        return view('admin.capex-config.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);

        $config = CapexConfig::create(['name' => $request->name]);

        return redirect()->route('admin.capex-configs.edit', $config)
            ->with('success', 'Layout created. Add your CapEx lines below.');
    }

    public function edit(CapexConfig $capexConfig)
    {
        $config = $capexConfig->load('lines.subCategory');

        $subCategories = AccountSubCategory::where('is_active', true)
            ->where('budget_type', 'capital_expenditure')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.capex-config.edit', compact('config', 'subCategories'));
    }

    public function update(Request $request, CapexConfig $capexConfig)
    {
        $request->validate([
            'name'                    => 'required|string|max:255',
            'lines'                   => 'array',
            'lines.*.line_type'       => 'required|in:sub_category,subtotal,spacer',
            'lines.*.sub_category_id' => 'nullable|exists:account_sub_categories,id',
            'lines.*.label'           => 'nullable|string|max:255',
        ]);

        $capexConfig->update(['name' => $request->name]);

        $capexConfig->lines()->delete();

        foreach ($request->input('lines', []) as $i => $line) {
            $capexConfig->lines()->create([
                'line_type'       => $line['line_type'],
                'sub_category_id' => $line['sub_category_id'] ?: null,
                'label'           => $line['label'] ?: null,
                'sort_order'      => $i,
            ]);
        }

        return redirect()->route('admin.capex-configs.edit', $capexConfig)
            ->with('success', 'Layout saved successfully.');
    }

    public function activate(CapexConfig $capexConfig)
    {
        CapexConfig::query()->update(['is_active' => false]);
        $capexConfig->update(['is_active' => true]);

        return redirect()->route('admin.capex-configs.index')
            ->with('success', '"' . $capexConfig->name . '" is now the active CapEx layout.');
    }

    public function deactivate(CapexConfig $capexConfig)
    {
        $capexConfig->update(['is_active' => false]);

        return redirect()->route('admin.capex-configs.index')
            ->with('success', 'Layout deactivated — the standard CapEx view will be used.');
    }

    public function destroy(CapexConfig $capexConfig)
    {
        $capexConfig->lines()->delete();
        $capexConfig->delete();

        return redirect()->route('admin.capex-configs.index')
            ->with('success', 'Layout deleted.');
    }
}
