<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountCategory;
use App\Models\AccountCode;
use App\Models\BudgetPeriod;
use App\Models\BudgetPeriodCategoryRate;
use App\Models\BudgetPeriodCodeRate;
use App\Models\BudgetPeriodSetting;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\AuditLogger;

class BudgetPeriodController extends Controller
{
    public function index()
    {
        $periods = BudgetPeriod::withCount('budgetVersions')
                               ->with('setting')
                               ->orderByDesc('year')
                               ->paginate(20);

        return view('admin.budget-periods.index', compact('periods'));
    }

    public function create()
    {
        // Pre-fill calc settings from current global defaults
        $globalCalcMode      = SystemSetting::get('line_item_calc_mode', 'none');
        $globalAdminSetsRate = SystemSetting::get('admin_sets_rate', false);
        $globalAdminSetsFreq = SystemSetting::get('admin_sets_freq', false);

        return view('admin.budget-periods.create', compact(
            'globalCalcMode', 'globalAdminSetsRate', 'globalAdminSetsFreq'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:255', 'unique:budget_periods,name'],
            'year'                => ['required', 'integer', 'min:2020', 'max:2100'],
            'start_date'          => ['required', 'date'],
            'end_date'            => ['required', 'date', 'after:start_date'],
            'entry_mode'          => ['required', 'in:quarterly,monthly'],
            'line_item_calc_mode' => ['required', 'in:none,qty_rate,qty_rate_freq'],
            'admin_sets_rate'     => ['nullable', 'boolean'],
            'admin_sets_freq'     => ['nullable', 'boolean'],
        ]);

        $period = DB::transaction(function () use ($validated) {
            $period = BudgetPeriod::create([
                'name'       => $validated['name'],
                'year'       => $validated['year'],
                'start_date' => $validated['start_date'],
                'end_date'   => $validated['end_date'],
                'entry_mode' => $validated['entry_mode'],
                'status'     => 'draft',
                'created_by' => auth()->id(),
            ]);

            // Snapshot the calc settings for this period
            BudgetPeriodSetting::create([
                'budget_period_id'    => $period->id,
                'line_item_calc_mode' => $validated['line_item_calc_mode'],
                'admin_sets_rate'     => (bool) ($validated['admin_sets_rate'] ?? false),
                'admin_sets_freq'     => (bool) ($validated['admin_sets_freq'] ?? false),
            ]);

            // Snapshot current account code and category rates for this period
            // so they are preserved as a historical record independent of global defaults
            if ($validated['line_item_calc_mode'] !== 'none') {
                $this->snapshotRates($period);
            }

            return $period;
        });

        return redirect()->route('admin.budget-periods.show', $period)
            ->with('success', 'Budget period created. Review and adjust the period rate settings below.');
    }

    public function show(BudgetPeriod $budgetPeriod)
    {
        $budgetPeriod->load('budgetVersions.department');
        return view('admin.budget-periods.show', compact('budgetPeriod'));
    }

    public function edit(BudgetPeriod $budgetPeriod)
    {
        if ($budgetPeriod->status !== 'draft') {
            return back()->with('error', 'Only draft periods can be edited.');
        }

        $budgetPeriod->load('setting');

        return view('admin.budget-periods.edit', compact('budgetPeriod'));
    }

    public function update(Request $request, BudgetPeriod $budgetPeriod)
    {
        if ($budgetPeriod->status !== 'draft') {
            return back()->with('error', 'Only draft periods can be edited.');
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('budget_periods', 'name')->ignore($budgetPeriod->id),
            ],
            'year'                => ['required', 'integer', 'min:2020', 'max:2100'],
            'start_date'          => ['required', 'date'],
            'end_date'            => ['required', 'date', 'after:start_date'],
            'entry_mode'          => ['required', 'in:quarterly,monthly'],
            'line_item_calc_mode' => ['required', 'in:none,qty_rate,qty_rate_freq'],
            'admin_sets_rate'     => ['nullable', 'boolean'],
            'admin_sets_freq'     => ['nullable', 'boolean'],
        ]);

        // Lock entry_mode once any entries exist
        if ($validated['entry_mode'] !== $budgetPeriod->entry_mode && $budgetPeriod->hasEntries()) {
            return back()->withInput()
                ->with('error', 'Entry mode cannot be changed after budget entries have been saved.');
        }

        $budgetPeriod->update([
            'name'       => $validated['name'],
            'year'       => $validated['year'],
            'start_date' => $validated['start_date'],
            'end_date'   => $validated['end_date'],
            'entry_mode' => $validated['entry_mode'],
        ]);

        // Upsert the calc settings snapshot for this period
        BudgetPeriodSetting::updateOrCreate(
            ['budget_period_id' => $budgetPeriod->id],
            [
                'line_item_calc_mode' => $validated['line_item_calc_mode'],
                'admin_sets_rate'     => (bool) ($validated['admin_sets_rate'] ?? false),
                'admin_sets_freq'     => (bool) ($validated['admin_sets_freq'] ?? false),
            ]
        );

        return redirect()->route('admin.budget-periods.index')
            ->with('success', 'Budget period updated successfully.');
    }

    // ── Period Rate Management ──────────────────────────────────────────────────

    /**
     * Show the per-period rate management page.
     */
    public function rates(BudgetPeriod $budgetPeriod)
    {
        $calcMode = $budgetPeriod->calcMode();

        // Load all active categories with their codes, sorted by budget type then name.
        // Type order: Revenue → Revenue & Expenditure → Expenditure → Assets → Liabilities → Capex
        $typeOrder = [
            'revenue'             => 1,
            'both'                => 2,
            'expense'             => 3,
            'assets'              => 4,
            'liabilities'         => 5,
            'capital_expenditure' => 6,
        ];

        $categories = AccountCategory::where('is_active', true)
            ->with(['accountCodes' => fn($q) => $q->where('is_active', true)->orderBy('code')])
            ->orderBy('name')
            ->get()
            ->sortBy(fn($cat) => $typeOrder[$cat->budget_type] ?? 99)
            ->values();

        $budgetPeriod->load('codeRates', 'categoryRates', 'setting');

        // Index by id for quick lookups in the view
        $codeRateMap     = $budgetPeriod->codeRates->keyBy('account_code_id');
        $categoryRateMap = $budgetPeriod->categoryRates->keyBy('account_category_id');

        return view('admin.budget-periods.rates', compact(
            'budgetPeriod', 'calcMode', 'categories', 'codeRateMap', 'categoryRateMap'
        ));
    }

    /**
     * Save per-period rate edits.
     */
    public function updateRates(Request $request, BudgetPeriod $budgetPeriod)
    {
        $request->validate([
            'codes'        => ['nullable', 'array'],
            'codes.*'      => ['array'],
            'codes.*.rate' => ['nullable', 'numeric', 'min:0'],
            'codes.*.freq' => ['nullable', 'numeric', 'min:0'],
            'cats'         => ['nullable', 'array'],
            'cats.*'       => ['array'],
            'cats.*.rate'  => ['nullable', 'numeric', 'min:0'],
            'cats.*.freq'  => ['nullable', 'numeric', 'min:0'],
        ]);

        // Only accept IDs that actually exist as active records —
        // prevents orphaned rows from crafted POST bodies.
        $validCodeIds = \App\Models\AccountCode::where('is_active', true)
            ->pluck('id')->flip();
        $validCatIds  = \App\Models\AccountCategory::where('is_active', true)
            ->pluck('id')->flip();

        DB::transaction(function () use ($request, $budgetPeriod, $validCodeIds, $validCatIds) {
            foreach ($request->input('codes', []) as $codeId => $values) {
                if (!$validCodeIds->has((int) $codeId)) continue; // reject unknown IDs
                $rate = $values['rate'] !== '' ? $values['rate'] : null;
                $freq = $values['freq'] !== '' ? $values['freq'] : null;

                BudgetPeriodCodeRate::updateOrCreate(
                    [
                        'budget_period_id' => $budgetPeriod->id,
                        'account_code_id'  => (int) $codeId,
                    ],
                    [
                        'default_rate'      => $rate,
                        'default_frequency' => $freq,
                    ]
                );
            }

            foreach ($request->input('cats', []) as $catId => $values) {
                if (!$validCatIds->has((int) $catId)) continue; // reject unknown IDs
                $rate = $values['rate'] !== '' ? $values['rate'] : null;
                $freq = $values['freq'] !== '' ? $values['freq'] : null;

                BudgetPeriodCategoryRate::updateOrCreate(
                    [
                        'budget_period_id'   => $budgetPeriod->id,
                        'account_category_id' => (int) $catId,
                    ],
                    [
                        'default_rate'      => $rate,
                        'default_frequency' => $freq,
                    ]
                );
            }
        });

        return back()->with('success', 'Period rates saved. New budget drafts in this period will use these rates.');
    }

    /**
     * Snapshot the current global account code / category rates into the
     * per-period tables so they start as a copy of today's values.
     * Uses firstOrCreate so re-running on an existing period is safe.
     */
    private function snapshotRates(BudgetPeriod $period): void
    {
        AccountCode::where('is_active', true)
            ->each(function (AccountCode $code) use ($period) {
                BudgetPeriodCodeRate::firstOrCreate(
                    [
                        'budget_period_id' => $period->id,
                        'account_code_id'  => $code->id,
                    ],
                    [
                        'default_rate'      => $code->default_rate,
                        'default_frequency' => $code->default_frequency,
                    ]
                );
            });

        AccountCategory::where('is_active', true)
            ->each(function (AccountCategory $cat) use ($period) {
                BudgetPeriodCategoryRate::firstOrCreate(
                    [
                        'budget_period_id'    => $period->id,
                        'account_category_id' => $cat->id,
                    ],
                    [
                        'default_rate'      => $cat->default_rate,
                        'default_frequency' => $cat->default_frequency,
                    ]
                );
            });
    }

    public function destroy(BudgetPeriod $budgetPeriod)
    {
        if ($budgetPeriod->status !== 'draft') {
            return back()->with('error', 'Only draft periods can be deleted.');
        }

        $budgetPeriod->delete();

        return redirect()->route('admin.budget-periods.index')
            ->with('success', 'Budget period deleted.');
    }

    public function open(BudgetPeriod $budgetPeriod)
    {
        // Only one period can be open at a time
        if (BudgetPeriod::where('status', 'open')->exists()) {
            return back()->with('error', 'Another budget period is already open. Close it first.');
        }

        if ($budgetPeriod->status !== 'draft') {
            return back()->with('error', 'Only draft periods can be opened.');
        }

        $budgetPeriod->update([
            'status'    => 'open',
            'opened_at' => now(),
        ]);

        AuditLogger::periodOpened($budgetPeriod, auth()->user());

        return back()->with('success', "{$budgetPeriod->name} is now open for budget submissions.");
    }

    public function close(BudgetPeriod $budgetPeriod)
    {
        if ($budgetPeriod->status !== 'open') {
            return back()->with('error', 'Only open periods can be closed.');
        }

        $budgetPeriod->update([
            'status'    => 'closed',
            'closed_at' => now(),
        ]);

        AuditLogger::periodClosed($budgetPeriod, auth()->user());

        return back()->with('success', "{$budgetPeriod->name} has been closed.");
    }
}
