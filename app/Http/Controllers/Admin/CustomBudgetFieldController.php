<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomBudgetField;
use Illuminate\Http\Request;

class CustomBudgetFieldController extends Controller
{
    public function index()
    {
        $fields = CustomBudgetField::orderBy('display_order')->orderBy('label')->get();
        return view('admin.custom-budget-fields.index', compact('fields'));
    }

    public function create()
    {
        return view('admin.custom-budget-fields.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'label'         => ['required', 'string', 'max:100'],
            'field_type'    => ['required', 'in:text,number,select,boolean'],
            'options'       => ['nullable', 'string'],
            'is_required'   => ['nullable', 'boolean'],
            'placeholder'   => ['nullable', 'string', 'max:150'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $name = \Illuminate\Support\Str::slug($validated['label'], '_');

        $options = null;
        if ($validated['field_type'] === 'select' && !empty($validated['options'])) {
            $options = array_filter(array_map('trim', explode("\n", $validated['options'])));
        }

        CustomBudgetField::create([
            'name'          => $name,
            'label'         => $validated['label'],
            'field_type'    => $validated['field_type'],
            'options'       => $options ? array_values($options) : null,
            'is_required'   => (bool) ($validated['is_required'] ?? false),
            'is_active'     => true,
            'placeholder'   => $validated['placeholder'] ?? null,
            'display_order' => (int) ($validated['display_order'] ?? 0),
        ]);

        return redirect()->route('admin.custom-budget-fields.index')
            ->with('success', 'Custom field "' . $validated['label'] . '" created.');
    }

    public function edit(CustomBudgetField $customBudgetField)
    {
        $optionsText = $customBudgetField->options
            ? implode("\n", $customBudgetField->options)
            : '';

        return view('admin.custom-budget-fields.edit', [
            'field'       => $customBudgetField,
            'optionsText' => $optionsText,
        ]);
    }

    public function update(Request $request, CustomBudgetField $customBudgetField)
    {
        $validated = $request->validate([
            'label'         => ['required', 'string', 'max:100'],
            'field_type'    => ['required', 'in:text,number,select,boolean'],
            'options'       => ['nullable', 'string'],
            'is_required'   => ['nullable', 'boolean'],
            'is_active'     => ['nullable', 'boolean'],
            'placeholder'   => ['nullable', 'string', 'max:150'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $options = null;
        if ($validated['field_type'] === 'select' && !empty($validated['options'])) {
            $options = array_values(array_filter(array_map('trim', explode("\n", $validated['options']))));
        }

        $customBudgetField->update([
            'label'         => $validated['label'],
            'field_type'    => $validated['field_type'],
            'options'       => $options,
            'is_required'   => (bool) ($validated['is_required'] ?? false),
            'is_active'     => (bool) ($validated['is_active'] ?? false),
            'placeholder'   => $validated['placeholder'] ?? null,
            'display_order' => (int) ($validated['display_order'] ?? 0),
        ]);

        return redirect()->route('admin.custom-budget-fields.index')
            ->with('success', 'Custom field "' . $customBudgetField->label . '" updated.');
    }

    public function destroy(CustomBudgetField $customBudgetField)
    {
        $label = $customBudgetField->label;
        $customBudgetField->delete();
        return redirect()->route('admin.custom-budget-fields.index')
            ->with('success', 'Custom field "' . $label . '" deleted.');
    }
}
