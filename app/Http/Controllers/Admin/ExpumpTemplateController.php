<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountCategory;
use App\Models\AccountCode;
use App\Models\ExpumpTemplate;
use App\Models\ExpumpValue;
use Illuminate\Http\Request;

class ExpumpTemplateController extends Controller
{
    public function index()
    {
        $templates = ExpumpTemplate::orderByDesc('is_active')->orderBy('name')->get();
        return view('admin.expump-template.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.expump-template.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $template = ExpumpTemplate::create($validated);

        return redirect()->route('admin.expump-templates.edit', $template)
            ->with('success', 'Template created. Now fill in the values.');
    }

    public function show(ExpumpTemplate $expumpTemplate)
    {
        $expumpCatIds  = AccountCategory::where('budget_type', 'ex_pump_item')->pluck('id');
        $revenueCatIds = AccountCategory::whereIn('budget_type', ['revenue', 'both'])->pluck('id');

        $expumpCodes  = AccountCode::whereIn('account_category_id', $expumpCatIds)
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('name')
            ->get();

        $revenueCodes = AccountCode::whereIn('account_category_id', $revenueCatIds)
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('name')
            ->get();

        $savedValues = [];
        foreach ($expumpTemplate->values as $v) {
            $savedValues[$v->expump_code_id][$v->revenue_code_id] = $v->value;
        }

        return view('admin.expump-template.show', compact(
            'expumpTemplate', 'expumpCodes', 'revenueCodes', 'savedValues'
        ));
    }

    public function edit(ExpumpTemplate $expumpTemplate)
    {
        $expumpCatIds  = AccountCategory::where('budget_type', 'ex_pump_item')->pluck('id');
        $revenueCatIds = AccountCategory::whereIn('budget_type', ['revenue', 'both'])->pluck('id');

        $expumpCodes  = AccountCode::whereIn('account_category_id', $expumpCatIds)
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('name')
            ->get();

        $revenueCodes = AccountCode::whereIn('account_category_id', $revenueCatIds)
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('name')
            ->get();

        // Keyed lookup: [expump_code_id][revenue_code_id] => value
        $savedValues = [];
        foreach ($expumpTemplate->values as $v) {
            $savedValues[$v->expump_code_id][$v->revenue_code_id] = $v->value;
        }

        return view('admin.expump-template.edit', compact(
            'expumpTemplate', 'expumpCodes', 'revenueCodes', 'savedValues'
        ));
    }

    public function update(Request $request, ExpumpTemplate $expumpTemplate)
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $expumpTemplate->update($request->only('name', 'description'));

        // Replace all values
        ExpumpValue::where('expump_template_id', $expumpTemplate->id)->delete();

        $rows = $request->input('values', []);
        $inserts = [];
        $now = now();
        foreach ($rows as $expumpCodeId => $cols) {
            foreach ($cols as $revenueCodeId => $val) {
                if ($val === null || $val === '') continue;
                $inserts[] = [
                    'expump_template_id' => $expumpTemplate->id,
                    'expump_code_id'     => (int) $expumpCodeId,
                    'revenue_code_id'    => (int) $revenueCodeId,
                    'value'              => (float) $val,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ];
            }
        }
        if ($inserts) {
            ExpumpValue::insert($inserts);
        }

        if ($request->ajax()) {
            return response()->json(['status' => 'ok']);
        }

        return redirect()->route('admin.expump-templates.edit', $expumpTemplate)
            ->with('success', 'Template saved successfully.');
    }

    public function activate(ExpumpTemplate $expumpTemplate)
    {
        ExpumpTemplate::query()->update(['is_active' => false]);
        $expumpTemplate->update(['is_active' => true]);

        return back()->with('success', "Template \"{$expumpTemplate->name}\" is now active.");
    }

    public function deactivate(ExpumpTemplate $expumpTemplate)
    {
        $expumpTemplate->update(['is_active' => false]);
        return back()->with('success', "Template deactivated.");
    }

    public function destroy(ExpumpTemplate $expumpTemplate)
    {
        $expumpTemplate->delete();
        return redirect()->route('admin.expump-templates.index')
            ->with('success', 'Template deleted.');
    }
}
