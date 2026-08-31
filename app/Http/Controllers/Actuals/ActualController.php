<?php

namespace App\Http\Controllers\Actuals;

use App\Http\Controllers\Controller;
use App\Models\BudgetActual;
use App\Models\BudgetVersion;
use App\Models\BudgetPeriod;
use App\Models\BudgetLineItem;
use App\Models\Department;
use App\Models\SupplementaryBudget;
use App\Models\BudgetNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActualController extends Controller
{
    // Landing — pick dept + month to record
    public function index(Request $request)
    {
        $user          = auth()->user();
        $currentPeriod = BudgetPeriod::current()
            ?? BudgetPeriod::orderByDesc('year')->first();

        $periods     = BudgetPeriod::orderByDesc('year')->get();
        $departments = Department::where('is_active', true)->with('zone')->orderBy('name')->get();

        $period = $request->period_id
            ? BudgetPeriod::find($request->period_id)
            : $currentPeriod;

        $department = $request->department_id
            ? Department::find($request->department_id)
            : ($user->hasAnyRole(['department_user', 'department_head'])
                ? $user->department
                : $departments->first());

        $selectedMonth = (int) $request->get('month', now()->month);
        $selectedYear  = (int) $request->get('year',  now()->year);

        $approvalFlow   = \App\Models\SystemSetting::get('actuals_approval_flow', 'simple');
        $monthlySummary = [];

        if ($department && $period) {
            // Two batch queries instead of 12 individual ones
            $confirmedTotals = BudgetActual::where('department_id',    $department->id)
                ->where('budget_period_id', $period->id)
                ->where('status', 'confirmed')
                ->selectRaw('month, SUM(amount) as total')
                ->groupBy('month')
                ->pluck('total', 'month');

            // Highest-priority status per month (confirmed > head_confirmed > submitted > draft)
            $statusByMonth = BudgetActual::where('department_id',    $department->id)
                ->where('budget_period_id', $period->id)
                ->selectRaw("month, MAX(CASE status
                    WHEN 'confirmed'      THEN 4
                    WHEN 'head_confirmed' THEN 3
                    WHEN 'submitted'      THEN 2
                    ELSE 1 END) as priority")
                ->groupBy('month')
                ->get()
                ->mapWithKeys(fn($r) => [$r->month => match((int) $r->priority) {
                    4 => 'confirmed', 3 => 'head_confirmed', 2 => 'submitted', default => 'draft',
                }]);

            for ($m = 1; $m <= 12; $m++) {
                $total  = (float) $confirmedTotals->get($m, 0);
                $status = $statusByMonth->get($m);   // null = no rows at all
                $monthlySummary[$m] = [
                    'name'     => BudgetActual::MONTHS[$m],
                    'total'    => $total,
                    'has_data' => $status !== null,   // any row recorded
                    'status'   => $status,
                ];
            }
        }

        // ── Pending actuals approval queue (multi-stage flow only) ──────────────
        // Dept head sees: submitted months from their own department.
        // Finance / admin see: head-confirmed months from every department.
        $pendingApprovals = collect();
        if ($approvalFlow === 'multi_stage' && $period) {
            if ($user->hasAnyRole(['finance_reviewer', 'bdu_admin', 'super_admin'])) {
                $pendingApprovals = BudgetActual::where('budget_period_id', $period->id)
                    ->where('status', 'head_confirmed')
                    ->with('department')
                    ->select('department_id', 'subsidiary_id', 'budget_period_id', 'month', 'year')
                    ->selectRaw('COUNT(*) as entry_count, SUM(amount) as total_amount')
                    ->groupBy('department_id', 'subsidiary_id', 'budget_period_id', 'month', 'year')
                    ->orderBy('year')->orderBy('month')
                    ->get();
            } elseif ($user->hasRole('department_head') && $user->department_id) {
                $pendingApprovals = BudgetActual::where('budget_period_id', $period->id)
                    ->where('status', 'submitted')
                    ->where('department_id', $user->department_id)
                    ->select('department_id', 'subsidiary_id', 'budget_period_id', 'month', 'year')
                    ->selectRaw('COUNT(*) as entry_count, SUM(amount) as total_amount')
                    ->groupBy('department_id', 'subsidiary_id', 'budget_period_id', 'month', 'year')
                    ->orderBy('year')->orderBy('month')
                    ->get();
            }
        }

        return view('actuals.index', compact(
            'period', 'periods', 'departments', 'department',
            'selectedMonth', 'selectedYear', 'monthlySummary', 'approvalFlow',
            'pendingApprovals'
        ));
    }

    /**
     * Entry form — record actuals for a dept/month.
     *
     * IMPORTANT: builds $lineRemaining keyed by line_item_id with
     * original / supplementary / effective / ytd / remaining for every
     * line item in the budget. This is the SINGLE SOURCE OF TRUTH used
     * by the view — never recalculate budget figures inline in blade.
     */
    public function entry(Request $request)
    {
        $user = auth()->user();

        $period = $request->period_id
            ? BudgetPeriod::find($request->period_id)
            : BudgetPeriod::current();

        $month = (int) $request->get('month', now()->month);
        $year  = (int) $request->get('year',  now()->year);

        // Resolve entity — subsidiary users go through their subsidiary
        $subsidiary = null;
        $department = null;

        if ($user->isSubsidiaryUser()) {
            $subsidiaryId = $request->subsidiary_id ?? $user->subsidiary_id;
            $subsidiary   = \App\Models\Subsidiary::find($subsidiaryId);
        } else {
            $department = $request->department_id
                ? Department::find($request->department_id)
                : $user->department;
        }

        $entityName = $subsidiary?->name ?? $department?->name;

        if (!$period || (!$subsidiary && !$department)) {
            return redirect()->route('actuals.index')
                ->with('error', 'Please select a period and department.');
        }

        $version = BudgetVersion::with('lineItems.accountCode.category')
            ->where('budget_period_id', $period->id)
            ->when($subsidiary, fn($q) => $q->where('subsidiary_id', $subsidiary->id))
            ->when($department, fn($q) => $q->where('department_id', $department->id))
            ->where('status', 'approved')
            ->orderByDesc('version_number')
            ->first();

        if (!$version) {
            return redirect()->route('actuals.index')
                ->with('error', "No approved budget found for {$entityName} in {$period->name}.");
        }

        // Existing actuals recorded for THIS specific month/year
        $existingActuals = BudgetActual::when($subsidiary, fn($q) => $q->where('subsidiary_id', $subsidiary->id))
            ->when($department, fn($q) => $q->where('department_id', $department->id))
            ->where('budget_period_id', $period->id)
            ->where('month', $month)
            ->where('year',  $year)
            ->get()
            ->keyBy('budget_line_item_id');

        // Group by category, then sort: revenue first, then expense, then others;
        // alphabetical by category name within each type; account code order within each category.
        $byCategory = $version->lineItems->groupBy('accountCode.category.name');

        $typePriority = [
            'revenue'             => 0,
            'both'                => 1,
            'expense'             => 2,
            'ex_pump_item'        => 3,
            'capital_expenditure' => 4,
            'assets'              => 5,
            'liabilities'         => 6,
        ];
        $byCategory = $byCategory
            ->sortBy(function ($items, $catName) use ($typePriority) {
                $type = $items->first()?->accountCode?->category?->budget_type ?? 'expense';
                return sprintf('%d_%s', $typePriority[$type] ?? 9, $catName);
            })
            ->map(fn($items) => $items->sortBy(
                fn($item) => $item->accountCode?->code ?? $item->accountCode?->name ?? ''
            ));

        // YTD confirmed actuals up to and including this month
        $ytdActuals = BudgetActual::when($subsidiary, fn($q) => $q->where('subsidiary_id', $subsidiary->id))
            ->when($department, fn($q) => $q->where('department_id', $department->id))
            ->where('budget_period_id', $period->id)
            ->where('month', '<=', $month)
            ->where('status', 'confirmed')
            ->get()
            ->groupBy('budget_line_item_id')
            ->map(fn($g) => $g->sum('amount'));

        // ── Build the single source of truth for every line item's budget figures ──
        $lineRemaining = [];
        foreach ($version->lineItems as $item) {
            $original      = (float) $item->total_amount;
            $supplementary = $item->approvedSupplementaryTotal();
            $effective     = $original + $supplementary;
            $ytd           = (float) ($ytdActuals->get($item->id, 0));

            $lineRemaining[$item->id] = [
                'original'      => $original,
                'supplementary' => $supplementary,
                'effective'     => $effective,
                'ytd'           => $ytd,
                'remaining'     => $effective - $ytd,
            ];
        }

        $periods     = BudgetPeriod::orderByDesc('year')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        // Derive the month's current status from any existing actual row.
        // All rows in a month share the same status (they're updated in bulk),
        // so first() is enough; fall back to null when no rows exist yet.
        $monthStatus  = $existingActuals->max(fn($a) => BudgetActual::STATUS_PRIORITY[$a->status] ?? 1);
        $monthStatus  = $monthStatus
            ? array_search($monthStatus, BudgetActual::STATUS_PRIORITY)
            : null;

        $approvalFlow = \App\Models\SystemSetting::get('actuals_approval_flow', 'simple');

        return view('actuals.entry', compact(
            'period', 'periods', 'departments', 'department', 'subsidiary',
            'version', 'byCategory', 'existingActuals', 'ytdActuals',
            'month', 'year', 'lineRemaining',
            'monthStatus', 'approvalFlow'
        ));
    }

    /**
     * Save actuals for a month.
     *
     * Over-budget check uses BudgetLineItem::effectiveBudget() exclusively —
     * never re-add supplementary manually here.
     *
     * Accepts both regular form POST and JSON (for the Save & Confirm AJAX flow).
     * When called with Accept: application/json, returns JSON instead of redirects
     * so the JS can chain a confirm step after a successful save.
     */
    public function store(Request $request)
    {
        $request->validate([
            'period_id'              => ['required', 'exists:budget_periods,id'],
            'department_id'          => ['nullable', 'exists:departments,id'],
            'subsidiary_id'          => ['nullable', 'exists:subsidiaries,id'],
            'month'                  => ['required', 'integer', 'min:1', 'max:12'],
            'year'                   => ['required', 'integer', 'min:2000', 'max:2100'],
            'actuals'                => ['required', 'array'],
            'actuals.*.line_item_id' => ['required', 'exists:budget_line_items,id'],
            'actuals.*.amount'       => ['nullable', 'numeric', 'min:0'],
            'actuals.*.reference'    => ['nullable', 'string', 'max:100'],
            'actuals.*.description'  => ['nullable', 'string', 'max:500'],
        ]);

        $wantsJson = $request->expectsJson();

        $this->assertDeptOwnership($request->department_id, $request->subsidiary_id);

        $checkMode       = \App\Models\SystemSetting::get('actuals_budget_check_mode', 'annual');
        $overBudgetItems = [];

        foreach ($request->actuals as $data) {
            // Skip empty/zero rows — nothing to validate or save
            if (!isset($data['amount']) || (float) $data['amount'] === 0.0) continue;

            $lineItem = BudgetLineItem::with('accountCode')->find($data['line_item_id']);
            if (!$lineItem) continue;

            $hit = $this->checkLineOverBudget(
                $lineItem,
                (float) $data['amount'],
                (int) $request->month,
                (int) $request->year,
                $checkMode,
            );

            if ($hit) $overBudgetItems[] = $hit;
        }

        if (!empty($overBudgetItems)) {
            BudgetNotification::create([
                'user_id'         => auth()->id(),
                'type'            => 'over_budget_blocked',
                'subject'         => 'Actual submission blocked — budget overrun detected',
                'message'         => 'Your actual submission was blocked because one or more ' .
                                     'expense lines would exceed the approved budget (including any ' .
                                     'approved supplementary). Please request a supplementary budget to proceed.',
                'notifiable_id'   => (int) $request->department_id,
                'notifiable_type' => Department::class,
            ]);

            if ($wantsJson) {
                return response()->json([
                    'status'  => 'over_budget',
                    'message' => 'Submission blocked: ' . count($overBudgetItems) .
                                 ' expense line(s) would exceed the approved budget.',
                    'items'   => $overBudgetItems,
                ], 422);
            }

            return back()
                ->withInput()
                ->with('over_budget_items', $overBudgetItems)
                ->with('error',
                    'Submission blocked: ' . count($overBudgetItems) .
                    ' expense line(s) would exceed the approved budget. ' .
                    'Request a supplementary budget for the affected lines.'
                );
        }

        $saved = 0;

        DB::transaction(function () use ($request, &$saved) {
            foreach ($request->actuals as $data) {
                if (!isset($data['line_item_id'])) continue;

                $lineItem = BudgetLineItem::find($data['line_item_id']);
                if (!$lineItem) continue;

                // Skip empty rows entirely — do not create zero-value rows that didn't exist
                $amount = isset($data['amount']) ? (float) $data['amount'] : null;
                if (is_null($amount)) continue;

                BudgetActual::updateOrCreate(
                    [
                        'budget_line_item_id' => (int) $data['line_item_id'],
                        'month'               => (int) $request->month,
                        'year'                => (int) $request->year,
                    ],
                    [
                        'budget_period_id' => (int) $request->period_id,
                        'department_id'    => $request->department_id ? (int) $request->department_id : null,
                        'subsidiary_id'    => $request->subsidiary_id ? (int) $request->subsidiary_id : null,
                        'account_code_id'  => $lineItem->account_code_id,
                        'amount'           => $amount,
                        'reference'        => $data['reference']   ?? null,
                        'description'      => $data['description'] ?? null,
                        'recorded_by'      => auth()->id(),
                        'status'           => 'draft',
                    ]
                );

                $saved++;
            }
        });

        $monthName = BudgetActual::MONTHS[(int) $request->month];

        if ($wantsJson) {
            return response()->json([
                'status'  => 'ok',
                'saved'   => $saved,
                'message' => "{$saved} actuals saved as draft for {$monthName} {$request->year}.",
            ]);
        }

        return redirect()->route('actuals.entry', [
            'period_id'     => $request->period_id,
            'department_id' => $request->department_id,
            'month'         => $request->month,
            'year'          => $request->year,
        ])->with('success', "{$saved} actuals saved as draft for {$monthName} {$request->year}.");
    }

    /**
     * Autosave actuals — JSON endpoint. Saves without blocking, but returns an
     * `over_budget_lines` array in the response so the JS can surface a warning
     * badge. Expense lines only; revenue lines are never flagged.
     */
    public function autosave(Request $request)
    {
        $request->validate([
            'period_id'              => ['required', 'exists:budget_periods,id'],
            'department_id'          => ['nullable', 'exists:departments,id'],
            'subsidiary_id'          => ['nullable', 'exists:subsidiaries,id'],
            'month'                  => ['required', 'integer', 'min:1', 'max:12'],
            'year'                   => ['required', 'integer', 'min:2000', 'max:2100'],
            'actuals'                => ['required', 'array'],
            'actuals.*.line_item_id' => ['required', 'exists:budget_line_items,id'],
            'actuals.*.amount'       => ['nullable', 'numeric', 'min:0'],
            'actuals.*.reference'    => ['nullable', 'string', 'max:100'],
            'actuals.*.description'  => ['nullable', 'string', 'max:500'],
        ]);

        $this->assertDeptOwnership($request->department_id, $request->subsidiary_id);

        $saved           = 0;
        $overBudgetLines = [];

        DB::transaction(function () use ($request, &$saved) {
            foreach ($request->actuals as $item) {
                // Skip if no amount provided
                if (!array_key_exists('amount', $item) || $item['amount'] === '' || is_null($item['amount'])) {
                    continue;
                }

                $lineItem = BudgetLineItem::find($item['line_item_id']);
                if (!$lineItem) continue;

                BudgetActual::updateOrCreate(
                    [
                        'budget_line_item_id' => (int) $item['line_item_id'],
                        'month'               => (int) $request->month,
                        'year'                => (int) $request->year,
                    ],
                    [
                        'budget_period_id' => (int) $request->period_id,
                        'department_id'    => $request->department_id ? (int) $request->department_id : null,
                        'subsidiary_id'    => $request->subsidiary_id ? (int) $request->subsidiary_id : null,
                        'account_code_id'  => $lineItem->account_code_id,
                        'amount'           => (float) $item['amount'],
                        'reference'        => $item['reference']   ?? null,
                        'description'      => $item['description'] ?? null,
                        'recorded_by'      => auth()->id(),
                        'status'           => 'draft',
                    ]
                );

                $saved++;
            }
        });

        // Non-blocking over-budget check — compute after save (advisory only, does not block).
        $checkMode = \App\Models\SystemSetting::get('actuals_budget_check_mode', 'annual');

        foreach ($request->actuals as $item) {
            $amount = isset($item['amount']) ? (float) $item['amount'] : 0.0;
            if ($amount <= 0) continue;

            $lineItem = BudgetLineItem::with('accountCode')->find($item['line_item_id']);
            if (!$lineItem) continue;

            $hit = $this->checkLineOverBudget(
                $lineItem, $amount,
                (int) $request->month, (int) $request->year,
                $checkMode,
            );

            if ($hit) {
                $overBudgetLines[] = [
                    'line_item_id' => $hit['line_item_id'],
                    'code'         => $hit['code'],
                    'name'         => $hit['name'],
                    'budget'       => $hit['budget'],
                    'projected'    => $hit['projected_total'],
                    'overrun'      => $hit['overrun'],
                ];
            }
        }

        return response()->json([
            'success'          => true,
            'saved'            => $saved,
            'saved_at'         => now()->format('H:i:s'),
            'over_budget_lines' => $overBudgetLines,
        ]);
    }

    // ── Multi-stage approval helpers ─────────────────────────────────────────

    /** Shared validation rules for all actuals workflow POST actions. */
    private function workflowValidation(): array
    {
        return [
            'period_id'     => ['required', 'exists:budget_periods,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'subsidiary_id' => ['nullable', 'exists:subsidiaries,id'],
            'month'         => ['required', 'integer', 'min:1', 'max:12'],
            'year'          => ['required', 'integer', 'min:2000', 'max:2100'],
        ];
    }

    /** Base query for all rows of a specific month/entity. */
    private function monthQuery(Request $request)
    {
        return BudgetActual::when($request->department_id, fn($q) => $q->where('department_id', $request->department_id))
            ->when($request->subsidiary_id, fn($q) => $q->where('subsidiary_id', $request->subsidiary_id))
            ->where('budget_period_id', $request->period_id)
            ->where('month', $request->month)
            ->where('year',  $request->year);
    }

    /**
     * Over-budget check on rows with a given status.
     * Returns array of over-budget items (empty = all OK).
     */
    private function overBudgetCheckForStatus(Request $request, string $status): array
    {
        $rows      = $this->monthQuery($request)->where('status', $status)->with('lineItem.accountCode')->get();
        $checkMode = \App\Models\SystemSetting::get('actuals_budget_check_mode', 'annual');
        $items     = [];

        foreach ($rows as $row) {
            if (!$row->lineItem) continue;
            $hit = $this->checkLineOverBudget($row->lineItem, (float) $row->amount, (int) $request->month, (int) $request->year, $checkMode);
            if ($hit) $items[] = $hit;
        }

        return $items;
    }

    /**
     * Dept user submits their draft entries for department head review.
     * Multi-stage flow only.  draft → submitted.
     */
    public function submit(Request $request)
    {
        $request->validate($this->workflowValidation());
        $this->assertDeptOwnership($request->department_id, $request->subsidiary_id);

        if (\App\Models\SystemSetting::get('actuals_approval_flow', 'simple') !== 'multi_stage') {
            return back()->with('error', 'Multi-stage approval is not enabled. Use Save & Confirm instead.');
        }

        $monthName = BudgetActual::MONTHS[(int) $request->month];

        $count = $this->monthQuery($request)
            ->where('status', 'draft')
            ->update([
                'status'       => 'submitted',
                'submitted_by' => auth()->id(),
                'submitted_at' => now(),
            ]);

        if ($count === 0) {
            return back()->with('error', "No draft entries found for {$monthName}. Save entries first.");
        }

        return redirect()->route('actuals.entry', [
            'period_id'     => $request->period_id,
            'department_id' => $request->department_id,
            'month'         => $request->month,
            'year'          => $request->year,
        ])->with('success', "{$count} {$monthName} entries submitted for department head review.");
    }

    /**
     * Department head confirms submitted entries, forwarding them to Finance.
     * Multi-stage flow only.  submitted → head_confirmed.
     */
    public function headConfirm(Request $request)
    {
        $request->validate($this->workflowValidation());
        $this->assertDeptOwnership($request->department_id, $request->subsidiary_id);

        if (\App\Models\SystemSetting::get('actuals_approval_flow', 'simple') !== 'multi_stage') {
            return back()->with('error', 'Multi-stage approval is not enabled.');
        }

        $monthName = BudgetActual::MONTHS[(int) $request->month];

        // Segregation of duties — head cannot confirm entries they themselves recorded.
        // This is a hard block regardless of role, preventing privilege escalation.
        $recorders = $this->monthQuery($request)->where('status', 'submitted')->pluck('recorded_by')->unique();
        if ($recorders->contains(auth()->id())) {
            return back()->with('error',
                "You cannot confirm the {$monthName} actuals because you recorded one or more of the entries. " .
                "A different approver must confirm them. (Segregation of duties)"
            );
        }

        // Over-budget check before the head locks in
        $overBudgetItems = $this->overBudgetCheckForStatus($request, 'submitted');
        if (!empty($overBudgetItems)) {
            return back()
                ->with('over_budget_items', $overBudgetItems)
                ->with('error', count($overBudgetItems) . " expense line(s) would exceed the budget for {$monthName}. "
                    . "Ask the department to adjust entries or request a supplementary budget.");
        }

        $count = $this->monthQuery($request)
            ->where('status', 'submitted')
            ->update([
                'status'             => 'head_confirmed',
                'head_confirmed_by'  => auth()->id(),
                'head_confirmed_at'  => now(),
            ]);

        if ($count === 0) {
            return back()->with('error', "No submitted entries found for {$monthName}.");
        }

        return redirect()->route('actuals.entry', [
            'period_id'     => $request->period_id,
            'department_id' => $request->department_id,
            'month'         => $request->month,
            'year'          => $request->year,
        ])->with('success', "{$monthName} entries confirmed. Awaiting Finance approval.");
    }

    /**
     * Finance gives final approval — locks the month.
     * Multi-stage flow only.  head_confirmed → confirmed.
     */
    public function approveActuals(Request $request)
    {
        $request->validate($this->workflowValidation());
        $this->assertDeptOwnership($request->department_id, $request->subsidiary_id);

        if (\App\Models\SystemSetting::get('actuals_approval_flow', 'simple') !== 'multi_stage') {
            return back()->with('error', 'Multi-stage approval is not enabled.');
        }

        $monthName = BudgetActual::MONTHS[(int) $request->month];

        // Segregation of duties — Finance cannot approve entries they recorded or submitted.
        // Hard block regardless of role assignment, preventing self-approval via privilege escalation.
        $selfInvolved = $this->monthQuery($request)
            ->where('status', 'head_confirmed')
            ->where(fn($q) => $q->where('recorded_by', auth()->id())
                                ->orWhere('submitted_by', auth()->id()))
            ->exists();

        if ($selfInvolved) {
            return back()->with('error',
                "You cannot give final approval for {$monthName} because you recorded or submitted " .
                "one or more of the entries. Another Finance user must approve them. (Segregation of duties)"
            );
        }

        // Final over-budget check
        $overBudgetItems = $this->overBudgetCheckForStatus($request, 'head_confirmed');
        if (!empty($overBudgetItems)) {
            return back()
                ->with('over_budget_items', $overBudgetItems)
                ->with('error', count($overBudgetItems) . " expense line(s) would exceed the budget for {$monthName}. "
                    . "Reopen the month and ask the department to revise entries.");
        }

        $count = $this->monthQuery($request)
            ->where('status', 'head_confirmed')
            ->update([
                'status'      => 'confirmed',
                'approved_by' => auth()->id(),
            ]);

        if ($count === 0) {
            return back()->with('error', "No head-confirmed entries found for {$monthName}.");
        }

        return redirect()->route('actuals.entry', [
            'period_id'     => $request->period_id,
            'department_id' => $request->department_id,
            'month'         => $request->month,
            'year'          => $request->year,
        ])->with('success', "{$count} {$monthName} entries approved and locked.");
    }

    /**
     * Finance reopens a confirmed month, resetting all rows back to draft
     * so the department user can edit and resubmit.
     * Works in both simple and multi-stage flows.
     */
    public function reopen(Request $request)
    {
        $request->validate($this->workflowValidation());

        $monthName = BudgetActual::MONTHS[(int) $request->month];

        $count = $this->monthQuery($request)
            ->where('status', 'confirmed')
            ->update([
                'status'             => 'draft',
                'approved_by'        => null,
                'submitted_by'       => null,
                'submitted_at'       => null,
                'head_confirmed_by'  => null,
                'head_confirmed_at'  => null,
            ]);

        if ($count === 0) {
            return back()->with('error', "No confirmed entries found for {$monthName}.");
        }

        return redirect()->route('actuals.entry', [
            'period_id'     => $request->period_id,
            'department_id' => $request->department_id,
            'month'         => $request->month,
            'year'          => $request->year,
        ])->with('success', "{$monthName} actuals reopened. The department can now edit and resubmit.");
    }

    // Confirm (lock) a month's actuals — simple flow
    public function confirm(Request $request)
    {
        $request->validate([
            'period_id'     => ['required', 'exists:budget_periods,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'subsidiary_id' => ['nullable', 'exists:subsidiaries,id'],
            'month'         => ['required', 'integer', 'min:1', 'max:12'],
            'year'          => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $this->assertDeptOwnership($request->department_id, $request->subsidiary_id);

        $monthName = BudgetActual::MONTHS[(int) $request->month];

        // Segregation of duties — confirmer cannot have recorded any of these drafts.
        // Exemption: department_user role is expected to record AND confirm their own
        // entries in simple flow. Segregation for dept users is achieved via the
        // multi-stage flow (user → head → finance) when that setting is enabled.
        $user = auth()->user();
        if (\App\Services\SegregationService::enabled() && !$user->hasRole('department_user')) {
            $recorders = BudgetActual::where('department_id',    $request->department_id)
                ->where('budget_period_id', $request->period_id)
                ->where('month',            $request->month)
                ->where('year',             $request->year)
                ->where('status',           'draft')
                ->pluck('recorded_by')
                ->unique();

            if ($recorders->contains($user->id)) {
                return back()->with('error',
                    "You cannot confirm the {$monthName} actuals because you recorded " .
                    "one or more of the entries. A different user must confirm them. " .
                    "(Segregation of duties)"
                );
            }
        }

        // Over-budget check — confirm is the point-of-no-return; block if any
        // expense draft would breach the budget cap when confirmed.
        $drafts = BudgetActual::with(['lineItem.accountCode'])
            ->where('department_id',    $request->department_id)
            ->where('budget_period_id', $request->period_id)
            ->where('month',            $request->month)
            ->where('year',             $request->year)
            ->where('status',           'draft')
            ->get();

        $checkMode       = \App\Models\SystemSetting::get('actuals_budget_check_mode', 'annual');
        $overBudgetItems = [];

        foreach ($drafts as $draft) {
            $lineItem = $draft->lineItem;
            if (!$lineItem) continue;

            $hit = $this->checkLineOverBudget(
                $lineItem,
                (float) $draft->amount,
                (int) $request->month,
                (int) $request->year,
                $checkMode,
            );

            if ($hit) $overBudgetItems[] = $hit;
        }

        if (!empty($overBudgetItems)) {
            BudgetNotification::create([
                'user_id'         => auth()->id(),
                'type'            => 'over_budget_blocked',
                'subject'         => 'Confirmation blocked — budget overrun detected',
                'message'         => 'The confirmation of ' . $monthName . ' actuals was blocked because ' .
                                     count($overBudgetItems) . ' expense line(s) would exceed the approved budget.',
                'notifiable_id'   => (int) $request->department_id,
                'notifiable_type' => Department::class,
            ]);

            return back()
                ->with('over_budget_items', $overBudgetItems)
                ->with('error',
                    "Confirmation blocked: " . count($overBudgetItems) .
                    " expense line(s) would exceed the approved budget for {$monthName}. " .
                    "Edit the draft entries or request a supplementary budget."
                );
        }

        $count = BudgetActual::where('department_id',    $request->department_id)
            ->where('budget_period_id', $request->period_id)
            ->where('month',            $request->month)
            ->where('year',             $request->year)
            ->where('status',           'draft')
            ->update([
                'status'      => 'confirmed',
                'approved_by' => auth()->id(),
            ]);

        return redirect()->route('actuals.entry', [
            'period_id'     => $request->period_id,
            'department_id' => $request->department_id,
            'month'         => $request->month,
            'year'          => $request->year,
        ])->with('success', "{$count} actual entries confirmed for {$monthName} {$request->year}.");
    }

    // Overview of all actuals — for finance
    public function overview(Request $request)
    {
        $periods     = BudgetPeriod::orderByDesc('year')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        $period = $request->period_id
            ? BudgetPeriod::find($request->period_id)
            : (BudgetPeriod::current() ?? $periods->first());

        $grid = [];
        if ($period) {
            foreach ($departments as $dept) {
                $row = [
                    'dept'   => $dept->name,
                    'code'   => $dept->code,
                    'months' => [],
                    'ytd'    => 0,
                ];

                for ($m = 1; $m <= 12; $m++) {
                    $amt = BudgetActual::where('department_id',    $dept->id)
                        ->where('budget_period_id', $period->id)
                        ->where('month',            $m)
                        ->where('status',           'confirmed')
                        ->sum('amount');

                    $row['months'][$m] = $amt;
                    $row['ytd']       += $amt;
                }

                $version = BudgetVersion::where('budget_period_id', $period->id)
                    ->where('department_id', $dept->id)
                    ->where('status', 'approved')
                    ->first();

                // Effective budget = original + supplementary
                $original      = $version?->lineItems()->sum('total_amount') ?? 0;
                $supplementary = $version
                    ? $version->lineItems->sum(fn($i) => $i->approvedSupplementaryTotal())
                    : 0;

                $row['budget_original']  = $original;
                $row['budget_supplementary'] = $supplementary;
                $row['budget']           = $original + $supplementary; // effective
                $row['variance']         = $row['ytd'] - $row['budget'];
                $row['pct']              = $row['budget'] > 0
                    ? round(($row['ytd'] / $row['budget']) * 100, 1) : 0;

                $grid[] = $row;
            }
        }

        return view('actuals.overview', compact(
            'period', 'periods', 'departments', 'grid'
        ));
    }

    /**
     * Ensure the user may record actuals for the requested entity.
     * Accepts either a department_id or subsidiary_id depending on what was passed.
     * Finance / admin roles bypass all checks.
     */
    private function assertDeptOwnership(int|string $requestedDeptId, int|string|null $requestedSubId = null): void
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['finance_reviewer', 'bdu_admin', 'super_admin'])) {
            return;
        }

        if ($user->isSubsidiaryUser()) {
            abort_unless(
                $requestedSubId && (int) $requestedSubId === (int) $user->subsidiary_id,
                403,
                'You can only record actuals for your own subsidiary.'
            );
            return;
        }

        abort_unless((int) $requestedDeptId === (int) $user->department_id, 403,
            'You can only record actuals for your own department.');
    }

    /**
     * Check whether a single expense line item would breach its budget cap.
     *
     * Mode `annual` (flexible, default):
     *   YTD confirmed actuals BEFORE this month + this entry vs effectiveBudget() (annual total).
     *   A month can exceed its monthly allocation as long as the annual total is not breached.
     *
     * Mode `monthly` (strict):
     *   This entry alone vs the line item's budgeted amount for this specific month (m{N}_amount).
     *   Revenue lines are never checked regardless of mode.
     *
     * Returns an associative array with over-budget details, or null if within budget.
     */
    private function checkLineOverBudget(
        BudgetLineItem $lineItem,
        float          $amount,
        int            $month,
        int            $year,
        string         $mode,
    ): ?array {
        // Revenue lines are never capped
        if ($lineItem->line_type !== 'expense') return null;

        if ($mode === 'monthly') {
            // Strict: compare this entry against this month's specific budget amount
            $monthBudget = (float) ($lineItem->{"m{$month}_amount"} ?? 0);
            if ($amount <= $monthBudget) return null;

            return [
                'code'            => $lineItem->accountCode->code,
                'name'            => $lineItem->accountCode->name,
                'original_budget' => $lineItem->total_amount,
                'supplementary'   => $lineItem->approvedSupplementaryTotal(),
                'budget'          => $monthBudget,                // the monthly cap
                'ytd_before'      => 0,
                'this_entry'      => $amount,
                'projected_total' => $amount,
                'overrun'         => $amount - $monthBudget,
                'line_item_id'    => $lineItem->id,
                'check_mode'      => 'monthly',
            ];
        }

        // Annual (flexible): YTD confirmed before this month + this entry vs annual budget
        $ytd = BudgetActual::where('budget_line_item_id', $lineItem->id)
            ->where('status', 'confirmed')
            ->where(function ($q) use ($month, $year) {
                $q->where('year', '<', $year)
                  ->orWhere(function ($q2) use ($month, $year) {
                      $q2->where('year', $year)->where('month', '<', $month);
                  });
            })
            ->sum('amount');

        $effectiveBudget = $lineItem->effectiveBudget();
        $projected       = (float) $ytd + $amount;

        if ($projected <= $effectiveBudget) return null;

        return [
            'code'            => $lineItem->accountCode->code,
            'name'            => $lineItem->accountCode->name,
            'original_budget' => $lineItem->total_amount,
            'supplementary'   => $lineItem->approvedSupplementaryTotal(),
            'budget'          => $effectiveBudget,
            'ytd_before'      => (float) $ytd,
            'this_entry'      => $amount,
            'projected_total' => $projected,
            'overrun'         => $projected - $effectiveBudget,
            'line_item_id'    => $lineItem->id,
            'check_mode'      => 'annual',
        ];
    }
}
