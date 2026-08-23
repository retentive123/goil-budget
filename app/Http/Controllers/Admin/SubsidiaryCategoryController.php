<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubsidiaryCategory;
use Illuminate\Http\Request;

class SubsidiaryCategoryController extends Controller
{
    public function index()
    {
        $categories = SubsidiaryCategory::withCount('subsidiaries')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.subsidiary-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.subsidiary-categories.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:subsidiary_categories,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active'   => ['nullable', 'boolean'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ]);

        $data['is_active']  = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        SubsidiaryCategory::create($data);

        return redirect()->route('admin.subsidiary-categories.index')
            ->with('success', "Category \"{$data['name']}\" created.");
    }

    public function edit(SubsidiaryCategory $subsidiaryCategory)
    {
        return view('admin.subsidiary-categories.edit', compact('subsidiaryCategory'));
    }

    public function update(Request $request, SubsidiaryCategory $subsidiaryCategory)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:subsidiary_categories,name,' . $subsidiaryCategory->id],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active'   => ['nullable', 'boolean'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ]);

        $data['is_active']  = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? $subsidiaryCategory->sort_order;

        $subsidiaryCategory->update($data);

        return redirect()->route('admin.subsidiary-categories.index')
            ->with('success', "Category \"{$subsidiaryCategory->name}\" updated.");
    }

    public function destroy(SubsidiaryCategory $subsidiaryCategory)
    {
        if ($subsidiaryCategory->subsidiaries()->exists()) {
            return back()->with('error', "Cannot delete \"{$subsidiaryCategory->name}\" — it has subsidiaries assigned to it. Reassign or remove them first.");
        }

        $subsidiaryCategory->delete();

        return redirect()->route('admin.subsidiary-categories.index')
            ->with('success', "Category deleted.");
    }
}
