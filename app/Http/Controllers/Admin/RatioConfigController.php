<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountCategory;
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
        $ratio           = new RatioConfig;
        $allTypes        = RatioConfig::allTypes();
        $categoriesByType = $this->categoriesByType();
        return view('admin.ratio-configs.form', compact('ratio', 'allTypes', 'categoriesByType'));
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
        $ratio            = $ratioConfig;
        $allTypes         = RatioConfig::allTypes();
        $categoriesByType = $this->categoriesByType();
        return view('admin.ratio-configs.form', compact('ratio', 'allTypes', 'categoriesByType'));
    }

    public function update(Request $request, RatioConfig $ratioConfig)
    {
        $data = $this->validated($request);
        $ratioConfig->update($data);
        return redirect()->route('admin.ratio-configs.index')
            ->with('success', 'Ratio updated.');
    }

    private function categoriesByType(): \Illuminate\Support\Collection
    {
        return AccountCategory::with(['accountCodes' => fn($q) => $q->where('is_active', true)->orderBy('code')])
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->groupBy('budget_type');
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
            'name'                => 'required|string|max:120',
            'description'         => 'nullable|string|max:500',
            'numerator_source'    => 'required|in:budget,actual',
            'numerator_types'     => 'nullable|array',
            'numerator_types.*'   => 'string',
            'numerator_codes'              => 'nullable|array',
            'numerator_codes.*'            => 'integer|exists:account_codes,id',
            'numerator_category_ids'       => 'nullable|array',
            'numerator_category_ids.*'     => 'integer|exists:account_categories,id',
            'denominator_source'           => 'required|in:budget,actual',
            'denominator_types'            => 'nullable|array',
            'denominator_types.*'          => 'string',
            'denominator_codes'            => 'nullable|array',
            'denominator_codes.*'          => 'integer|exists:account_codes,id',
            'denominator_category_ids'     => 'nullable|array',
            'denominator_category_ids.*'   => 'integer|exists:account_categories,id',
            'multiply_by'         => 'required|numeric',
            'unit'                => 'required|string|max:10',
            'higher_is_better'    => 'boolean',
            'sort_order'          => 'integer|min:0',
            'is_active'           => 'boolean',
        ]);

        $numTypes    = $request->numerator_types          ?? [];
        $numCatIds   = $request->numerator_category_ids   ?? [];
        $numCodes    = $request->numerator_codes          ?? [];
        $denTypes    = $request->denominator_types        ?? [];
        $denCatIds   = $request->denominator_category_ids ?? [];
        $denCodes    = $request->denominator_codes        ?? [];

        if (empty($numTypes) && empty($numCatIds) && empty($numCodes)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'numerator_types' => 'Select at least one item for the numerator.',
            ]);
        }
        if (empty($denTypes) && empty($denCatIds) && empty($denCodes)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'denominator_types' => 'Select at least one item for the denominator.',
            ]);
        }

        return [
            'name'                     => $request->name,
            'description'              => $request->description,
            'numerator_source'         => $request->numerator_source,
            'numerator_types'          => $numTypes,
            'numerator_category_ids'   => $numCatIds,
            'numerator_codes'          => $numCodes,
            'denominator_source'       => $request->denominator_source,
            'denominator_types'        => $denTypes,
            'denominator_category_ids' => $denCatIds,
            'denominator_codes'        => $denCodes,
            'multiply_by'              => $request->multiply_by,
            'unit'                     => $request->unit,
            'higher_is_better'         => $request->boolean('higher_is_better'),
            'sort_order'               => $request->sort_order ?? 0,
            'is_active'                => $request->boolean('is_active', true),
        ];
    }
}
