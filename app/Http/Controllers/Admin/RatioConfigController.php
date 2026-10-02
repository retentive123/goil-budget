<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RatioConfig;
use Illuminate\Http\Request;

class RatioConfigController extends Controller
{
    public function index()
    {
        $ratios = RatioConfig::orderBy('sort_order')->orderBy('name')->get();
        return view('admin.ratio-configs.index', compact('ratios'));
    }

    public function create()
    {
        $ratio     = new RatioConfig;
        $allTypes  = RatioConfig::allTypes();
        return view('admin.ratio-configs.form', compact('ratio', 'allTypes'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        RatioConfig::create($data);
        return redirect()->route('admin.ratio-configs.index')
            ->with('success', 'Ratio created.');
    }

    public function edit(RatioConfig $ratioConfig)
    {
        $ratio    = $ratioConfig;
        $allTypes = RatioConfig::allTypes();
        return view('admin.ratio-configs.form', compact('ratio', 'allTypes'));
    }

    public function update(Request $request, RatioConfig $ratioConfig)
    {
        $data = $this->validated($request);
        $ratioConfig->update($data);
        return redirect()->route('admin.ratio-configs.index')
            ->with('success', 'Ratio updated.');
    }

    public function destroy(RatioConfig $ratioConfig)
    {
        $ratioConfig->delete();
        return redirect()->route('admin.ratio-configs.index')
            ->with('success', 'Ratio deleted.');
    }

    private function validated(Request $request): array
    {
        $request->validate([
            'name'               => 'required|string|max:120',
            'description'        => 'nullable|string|max:500',
            'numerator_source'   => 'required|in:budget,actual',
            'numerator_types'    => 'required|array|min:1',
            'numerator_types.*'  => 'string',
            'denominator_source' => 'required|in:budget,actual',
            'denominator_types'  => 'required|array|min:1',
            'denominator_types.*'=> 'string',
            'multiply_by'        => 'required|numeric',
            'unit'               => 'required|string|max:10',
            'higher_is_better'   => 'boolean',
            'sort_order'         => 'integer|min:0',
            'is_active'          => 'boolean',
        ]);

        return [
            'name'               => $request->name,
            'description'        => $request->description,
            'numerator_source'   => $request->numerator_source,
            'numerator_types'    => $request->numerator_types,
            'denominator_source' => $request->denominator_source,
            'denominator_types'  => $request->denominator_types,
            'multiply_by'        => $request->multiply_by,
            'unit'               => $request->unit,
            'higher_is_better'   => $request->boolean('higher_is_better'),
            'sort_order'         => $request->sort_order ?? 0,
            'is_active'          => $request->boolean('is_active', true),
        ];
    }
}
