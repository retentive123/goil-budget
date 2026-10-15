<?php

namespace App\Services;

use App\Models\BudgetActual;
use App\Models\BudgetVersion;
use App\Models\BudgetLineItem;
use App\Models\BudgetPeriod;
use App\Models\IncomeStatementConfig;

class BudgetCalculationService
{
    /**
     * Auto-populate line items for a new budget version.
     * Pre-fills rate / frequency from account code defaults when the
     * admin_sets_rate / admin_sets_freq settings are enabled.
     */
    public function populateLineItems(BudgetVersion $version): void
    {
        // Support both department-owned and subsidiary-owned budget versions.
        $entity = $version->subsidiary ?? $version->department;

        if (!$entity) {
            return;
        }

        // Read calc settings from the period's own snapshot (falls back to global
        // system settings for periods that pre-date the budget_period_settings table)
        $period        = $version->period;
        $calcMode      = $period->calcMode();
        $adminSetsRate = $period->adminSetsRate();
        $adminSetsFreq = $period->adminSetsFreq();

        $accountCodes = $entity->accountCodes()
                               ->where('account_codes.is_active', true)
                               ->with('category')
                               ->get();

        // Pre-load per-period rate snapshots so we don't N+1 inside the loop.
        // Fallback chain: period code rate → global code rate
        //                 → period category rate → global category rate
        $period->loadMissing('codeRates', 'categoryRates');
        $periodCodeRates = $period->codeRates->keyBy('account_code_id');
        $periodCatRates  = $period->categoryRates->keyBy('account_category_id');

        foreach ($accountCodes as $code) {
            $lineType = match($code->category->budget_type) {
                'revenue'              => 'revenue',
                'both'                 => 'expense',
                'capital_expenditure'  => 'capex',
                'assets'               => 'asset',
                'liabilities'          => 'liability',
                default                => 'expense',
            };

            // Determine default rate / frequency for new line items.
            // Four-level fallback: period code → global code → period category → global category
            $pCode = $periodCodeRates[$code->id] ?? null;
            $pCat  = $periodCatRates[$code->account_category_id] ?? null;

            // Always use period/global rates as editable defaults when calc mode is active.
            // adminSetsRate only controls whether the input is readonly (locked); the default
            // is populated regardless so users see the suggested value, not a blank.
            // When admin locks the rate, fall back to 1 (never zero a locked field);
            // when not locked, fall back to null (user fills in their own value).
            $defaultRate = $calcMode !== 'none'
                ? ($pCode?->default_rate
                    ?? $code->default_rate
                    ?? $pCat?->default_rate
                    ?? $code->category->default_rate
                    ?? ($adminSetsRate ? 1 : null))
                : null;

            $defaultFreq = $calcMode === 'qty_rate_freq'
                ? ($pCode?->default_frequency
                    ?? $code->default_frequency
                    ?? $pCat?->default_frequency
                    ?? $code->category->default_frequency
                    ?? ($adminSetsFreq ? 1 : null))
                : null;

            BudgetLineItem::firstOrCreate(
                [
                    'budget_version_id' => $version->id,
                    'account_code_id'   => $code->id,
                ],
                [
                    'm1_amount'  => 0, 'm2_amount'  => 0, 'm3_amount'  => 0,
                    'm4_amount'  => 0, 'm5_amount'  => 0, 'm6_amount'  => 0,
                    'm7_amount'  => 0, 'm8_amount'  => 0, 'm9_amount'  => 0,
                    'm10_amount' => 0, 'm11_amount' => 0, 'm12_amount' => 0,
                    'rate'            => $defaultRate,
                    'frequency'       => $defaultFreq,
                    'line_type'       => $lineType,
                    'last_updated_by' => auth()->id(),
                ]
            );

            // Sync rate/frequency into existing draft line items:
            // - When admin LOCKS the value: always overwrite so the latest period rate is used.
            // - When not locked: only fill in NULL values (don't overwrite what the user typed).
            // IMPORTANT: Only do this for DRAFT versions — submitted/approved budgets
            // must retain the rates that were in effect when they were entered.
            if ($calcMode !== 'none' && $version->status === BudgetVersion::STATUS_DRAFT) {
                $updates = [];

                $periodRate = $pCode?->default_rate
                    ?? $code->default_rate
                    ?? $pCat?->default_rate
                    ?? $code->category->default_rate;

                $periodFreq = $calcMode === 'qty_rate_freq'
                    ? ($pCode?->default_frequency
                        ?? $code->default_frequency
                        ?? $pCat?->default_frequency
                        ?? $code->category->default_frequency)
                    : null;

                if ($adminSetsRate && $periodRate !== null) {
                    // Locked: always overwrite with the period rate (or 1 if none set).
                    $updates['rate'] = $periodRate ?? 1;
                } elseif (!$adminSetsRate && $periodRate !== null) {
                    // Not locked: fill in null/zero cells — don't overwrite a value the user typed.
                    BudgetLineItem::where('budget_version_id', $version->id)
                        ->where('account_code_id', $code->id)
                        ->where(fn($q) => $q->whereNull('rate')->orWhere('rate', 0))
                        ->update(['rate' => $periodRate]);
                }

                if ($calcMode === 'qty_rate_freq') {
                    if ($adminSetsFreq && $periodFreq !== null) {
                        $updates['frequency'] = $periodFreq ?? 1;
                    } elseif (!$adminSetsFreq && $periodFreq !== null) {
                        BudgetLineItem::where('budget_version_id', $version->id)
                            ->where('account_code_id', $code->id)
                            ->where(fn($q) => $q->whereNull('frequency')->orWhere('frequency', 0))
                            ->update(['frequency' => $periodFreq]);
                    }
                }

                if (!empty($updates)) {
                    BudgetLineItem::where('budget_version_id', $version->id)
                        ->where('account_code_id', $code->id)
                        ->update($updates);
                }
            }
        }
    }

    /**
     * Check if a department has exceeded its expense budget
     */
public function isOverBudget(int $departmentId, int $periodId): array
{
    $version = \App\Models\BudgetVersion::where('budget_period_id', $periodId)
        ->where('department_id', $departmentId)
        ->where('status', 'approved')
        ->orderByDesc('version_number')
        ->first();

    if (!$version) return ['over_budget' => false, 'items' => []];

    $overItems = [];

    foreach ($version->lineItems()->with('accountCode.category')->get() as $item) {
        if ($item->line_type !== 'expense') continue;

        $actual = \App\Models\BudgetActual::where('budget_line_item_id', $item->id)
            ->where('status', 'confirmed')
            ->sum('amount');

        // ── Use the single helper — no manual re-adding ──
        $effectiveBudget = $item->effectiveBudget();

        if ($actual > $effectiveBudget) {
            $overItems[] = [
                'item'        => $item,
                'code'        => $item->accountCode->code,
                'name'        => $item->accountCode->name,
                'original'    => $item->total_amount,
                'supplementary' => $item->approvedSupplementaryTotal(),
                'budget'      => $effectiveBudget,
                'actual'      => $actual,
                'overrun'     => $actual - $effectiveBudget,
                'overrun_pct' => $effectiveBudget > 0
                    ? round((($actual - $effectiveBudget) / $effectiveBudget) * 100, 1)
                    : 0,
            ];
        }
    }

    return [
        'over_budget' => count($overItems) > 0,
        'items'       => $overItems,
    ];
}

    /**
     * Check deadline for a department
     */
    public function isDeadlinePassed(int $periodId, ?int $departmentId = null): array
    {
        // ✅ If no department ID, return early with safe values
        if ($departmentId === null) {
            return [
                'passed' => false,
                'deadline' => null,
                'has_override' => false,
                'message' => 'No department assigned'
            ];
        }

        $deadlineDays = (int) \App\Models\SystemSetting::get('budget_entry_deadline_days', 0);

        if ($deadlineDays === 0) {
            return [
                'passed' => false,
                'deadline' => null,
                'has_override' => false,
                'message' => 'Deadline checking is disabled'
            ];
        }

        $period = BudgetPeriod::find($periodId);

        if (!$period || !$period->opened_at) {
            return [
                'passed' => false,
                'deadline' => null,
                'has_override' => false,
                'message' => 'Period not open'
            ];
        }

        $deadline = $period->opened_at->addDays($deadlineDays);

        // Check for override
        $override = \App\Models\SubmissionDeadlineOverride::where('budget_period_id', $periodId)
            ->where('department_id', $departmentId)
            ->first();

        if ($override && $override->isValid()) {
            $effectiveDeadline = $override->new_deadline ?? $deadline->addDays(30);
            return [
                'passed'         => now()->isAfter($effectiveDeadline),
                'deadline'       => $effectiveDeadline,
                'has_override'   => true,
                'override'       => $override,
                'message'        => 'Override active'
            ];
        }

        return [
            'passed'       => now()->isAfter($deadline),
            'deadline'     => $deadline,
            'has_override' => false,
            'message'      => 'Standard deadline'
        ];
    }

    /**
     * Recalculate totals per category for display
     */
   public function summaryByCategory(BudgetVersion $version): array
{
    $items = $version->lineItems()
                     ->with('accountCode.category')
                     ->get();

    $summary = [];

    foreach ($items as $item) {
        $categoryName = $item->accountCode->category->name;

        if (!isset($summary[$categoryName])) {
            $summary[$categoryName] = [
                'q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0,
                'm1'  => 0, 'm2'  => 0, 'm3'  => 0, 'm4'  => 0,
                'm5'  => 0, 'm6'  => 0, 'm7'  => 0, 'm8'  => 0,
                'm9'  => 0, 'm10' => 0, 'm11' => 0, 'm12' => 0,
                'total' => 0,
                'items' => collect(),
            ];
        }

        $summary[$categoryName]['q1']    += $item->q1_amount;
        $summary[$categoryName]['q2']    += $item->q2_amount;
        $summary[$categoryName]['q3']    += $item->q3_amount;
        $summary[$categoryName]['q4']    += $item->q4_amount;
        foreach (range(1, 12) as $m) {
            $summary[$categoryName]["m{$m}"] += $item->{"m{$m}_amount"};
        }
        $summary[$categoryName]['total'] += $item->total_amount;
        $summary[$categoryName]['items']->push($item);
    }

    return $summary;
}

    /**
     * Grand totals across all line items
     */
    public function grandTotals(BudgetVersion $version): array
    {
        $items = $version->lineItems()->get();

        $result = [
            'q1'    => $items->sum('q1_amount'),
            'q2'    => $items->sum('q2_amount'),
            'q3'    => $items->sum('q3_amount'),
            'q4'    => $items->sum('q4_amount'),
            'total' => $version->effectiveTotal(),
        ];
        foreach (range(1, 12) as $n) {
            $result["m{$n}"] = $items->sum("m{$n}_amount");
        }
        return $result;
    }

    /**
     * Build P&L-structured data for budget entry/review views
     */
    public function buildPnlData(BudgetVersion $version, ?BudgetPeriod $prevPeriod): array
    {
        $items = $version->lineItems;

        $prevActuals = [];
        $prevBudgets = [];

        if ($prevPeriod) {
            $prevActuals = BudgetActual::where('budget_period_id', $prevPeriod->id)
                ->when($version->subsidiary_id,
                    fn($q) => $q->where('subsidiary_id', $version->subsidiary_id),
                    fn($q) => $q->where('department_id', $version->department_id)
                )
                ->where('status', 'confirmed')
                ->selectRaw('account_code_id, sum(amount) as total')
                ->groupBy('account_code_id')
                ->pluck('total', 'account_code_id')
                ->map(fn($v) => (float) $v)
                ->toArray();

            $prevVersionIds = BudgetVersion::where('budget_period_id', $prevPeriod->id)
                ->when($version->subsidiary_id,
                    fn($q) => $q->where('subsidiary_id', $version->subsidiary_id),
                    fn($q) => $q->where('department_id', $version->department_id)
                )
                ->where('status', 'approved')
                ->pluck('id');

            if ($prevVersionIds->isNotEmpty()) {
                $prevBudgets = BudgetLineItem::whereIn('budget_version_id', $prevVersionIds)
                    ->selectRaw('account_code_id, sum(total_amount) as total')
                    ->groupBy('account_code_id')
                    ->pluck('total', 'account_code_id')
                    ->map(fn($v) => (float) $v)
                    ->toArray();
            }
        }

        $blank = [
            'q1'=>0,'q2'=>0,'q3'=>0,'q4'=>0,
            'm1'=>0,'m2'=>0,'m3'=>0,'m4'=>0,'m5'=>0,'m6'=>0,
            'm7'=>0,'m8'=>0,'m9'=>0,'m10'=>0,'m11'=>0,'m12'=>0,
            'total'=>0,'effective'=>0,'prev_budget'=>0,'prev_actual'=>0,
        ];
        $sections = [
            'revenue' => ['categories' => [], 'totals' => $blank],
            'expense' => ['categories' => [], 'totals' => $blank],
            'capex'   => ['categories' => [], 'totals' => $blank],
            'balance' => ['categories' => [], 'totals' => $blank],
        ];
        $catIndex = [];

        foreach ($items as $item) {
            $cat  = $item->accountCode->category;
            $type = match(true) {
                in_array($cat->budget_type, ['revenue', 'both'])         => 'revenue',
                $cat->budget_type === 'capital_expenditure'              => 'capex',
                in_array($cat->budget_type, ['assets', 'liabilities'])   => 'balance',
                default                                                  => 'expense',
            };
            $catName = $cat->name;
            $codeId  = $item->account_code_id;

            if (!isset($catIndex[$type][$catName])) {
                $catIndex[$type][$catName] = count($sections[$type]['categories']);
                $sections[$type]['categories'][] = [
                    'name'   => $catName,
                    'items'  => [],
                    'totals' => ['q1'=>0,'q2'=>0,'q3'=>0,'q4'=>0,'m1'=>0,'m2'=>0,'m3'=>0,'m4'=>0,'m5'=>0,'m6'=>0,'m7'=>0,'m8'=>0,'m9'=>0,'m10'=>0,'m11'=>0,'m12'=>0,'total'=>0,'effective'=>0,'prev_budget'=>0,'prev_actual'=>0,'common_size'=>0],
                ];
            }

            $idx       = $catIndex[$type][$catName];
            $supp      = $item->approvedSupplementaryTotal();
            $effective = (float) $item->total_amount + $supp;
            $prevB     = $prevBudgets[$codeId] ?? 0.0;
            $prevA     = $prevActuals[$codeId] ?? 0.0;

            $monthlyAmounts = [];
            foreach (range(1, 12) as $n) {
                $monthlyAmounts["m{$n}"] = (float) $item->{"m{$n}_amount"};
            }
            $sections[$type]['categories'][$idx]['items'][] = array_merge([
                'id'            => $item->id,
                'code'          => $item->accountCode->code,
                'name'          => $item->accountCode->name,
                'q1'            => (float) $item->q1_amount,
                'q2'            => (float) $item->q2_amount,
                'q3'            => (float) $item->q3_amount,
                'q4'            => (float) $item->q4_amount,
            ], $monthlyAmounts, [
                'total'         => (float) $item->total_amount,
                'supp'          => $supp,
                'effective'     => $effective,
                'prev_budget'   => $prevB,
                'prev_actual'   => $prevA,
                'justification' => $item->justification ?? '',
                'line_type'     => $item->line_type,
                'common_size'   => 0,
            ]);

            foreach (['q1','q2','q3','q4'] as $k) {
                $sections[$type]['totals'][$k] += (float) $item->{$k.'_amount'};
            }
            foreach (range(1, 12) as $n) {
                $sections[$type]['totals']["m{$n}"] += (float) $item->{"m{$n}_amount"};
            }
            $sections[$type]['totals']['total']       += (float) $item->total_amount;
            $sections[$type]['totals']['effective']   += $effective;
            $sections[$type]['totals']['prev_budget'] += $prevB;
            $sections[$type]['totals']['prev_actual'] += $prevA;
        }

        // Recompute category totals cleanly
        foreach (['revenue','expense','capex','balance'] as $type) {
            foreach ($sections[$type]['categories'] as &$cat) {
                $cat['totals'] = ['q1'=>0,'q2'=>0,'q3'=>0,'q4'=>0,'m1'=>0,'m2'=>0,'m3'=>0,'m4'=>0,'m5'=>0,'m6'=>0,'m7'=>0,'m8'=>0,'m9'=>0,'m10'=>0,'m11'=>0,'m12'=>0,'total'=>0,'effective'=>0,'prev_budget'=>0,'prev_actual'=>0,'common_size'=>0];
                foreach ($cat['items'] as $it) {
                    $cat['totals']['q1']          += $it['q1'];
                    $cat['totals']['q2']          += $it['q2'];
                    $cat['totals']['q3']          += $it['q3'];
                    $cat['totals']['q4']          += $it['q4'];
                    foreach (range(1, 12) as $n) {
                        $cat['totals']["m{$n}"] += $it["m{$n}"] ?? 0;
                    }
                    $cat['totals']['total']       += $it['total'];
                    $cat['totals']['effective']   += $it['effective'];
                    $cat['totals']['prev_budget'] += $it['prev_budget'];
                    $cat['totals']['prev_actual'] += $it['prev_actual'];
                }
            }
        }

        // Second pass: common_size per section
        foreach (['revenue','expense','capex','balance'] as $type) {
            $sectionEff = $sections[$type]['totals']['effective'];
            foreach ($sections[$type]['categories'] as &$cat) {
                $cat['totals']['common_size'] = $sectionEff > 0
                    ? round($cat['totals']['effective'] / $sectionEff * 100, 2) : 0;
                foreach ($cat['items'] as &$it) {
                    $it['common_size'] = $sectionEff > 0
                        ? round($it['effective'] / $sectionEff * 100, 2) : 0;
                }
            }
        }

        return $sections;
    }

    /**
     * Safe helper to check deadline status
     */
    public function getDeadlineStatus(?int $periodId, ?int $departmentId): array
    {
        if ($periodId === null || $departmentId === null) {
            return [
                'passed' => false,
                'deadline' => null,
                'has_override' => false,
                'message' => 'Missing period or department'
            ];
        }

        return $this->isDeadlinePassed($periodId, $departmentId);
    }

    // ── Configured P&L statement builder ──────────────────────────────────────
    // Builds the structured income statement array used by both the Financial
    // Report and the budget P&L entry/review view.
    public function buildConfiguredStatement(
        IncomeStatementConfig $config,
        $versions,
        BudgetPeriod $period,
        ?int $deptId,
        ?BudgetPeriod $prevPeriod = null,
        ?int $subsidiaryId = null
    ): array {
        // Effective budget amounts by sub_category_id from provided versions
        $budgetBySubCat = [];
        foreach ($versions as $version) {
            foreach ($version->lineItems()->with('accountCode.category')->get() as $item) {
                $subCatId = $item->accountCode->category->account_sub_category_id ?? null;
                if (!$subCatId) continue;
                $eff = $item->total_amount + $item->approvedSupplementaryTotal();
                $budgetBySubCat[$subCatId] = ($budgetBySubCat[$subCatId] ?? 0) + $eff;
            }
        }

        // YTD confirmed actuals by sub_category_id
        $actualsBySubCat = BudgetActual::where('budget_period_id', $period->id)
            ->when($deptId,       fn($q) => $q->where('department_id', $deptId))
            ->when($subsidiaryId, fn($q) => $q->where('subsidiary_id', $subsidiaryId))
            ->where('status', 'confirmed')
            ->join('account_codes', 'budget_actuals.account_code_id', '=', 'account_codes.id')
            ->join('account_categories', 'account_codes.account_category_id', '=', 'account_categories.id')
            ->whereNotNull('account_categories.account_sub_category_id')
            ->selectRaw('account_categories.account_sub_category_id as sc, SUM(budget_actuals.amount) as total')
            ->groupBy('account_categories.account_sub_category_id')
            ->pluck('total', 'sc')
            ->toArray();

        // Prev-period data
        $prevBudgetBySubCat  = [];
        $prevActualsBySubCat = [];
        if ($prevPeriod) {
            $prevVersions = BudgetVersion::where('budget_period_id', $prevPeriod->id)
                ->where('status', 'approved')
                ->where('is_revision', false)
                ->when($deptId,       fn($q) => $q->where('department_id', $deptId))
                ->when($subsidiaryId, fn($q) => $q->where('subsidiary_id', $subsidiaryId))
                ->get();

            foreach ($prevVersions as $pv) {
                foreach ($pv->lineItems()->with('accountCode.category')->get() as $item) {
                    $subCatId = $item->accountCode->category->account_sub_category_id ?? null;
                    if (!$subCatId) continue;
                    $eff = $item->total_amount + $item->approvedSupplementaryTotal();
                    $prevBudgetBySubCat[$subCatId] = ($prevBudgetBySubCat[$subCatId] ?? 0) + $eff;
                }
            }

            $prevActualsBySubCat = BudgetActual::where('budget_period_id', $prevPeriod->id)
                ->when($deptId,       fn($q) => $q->where('department_id', $deptId))
                ->when($subsidiaryId, fn($q) => $q->where('subsidiary_id', $subsidiaryId))
                ->where('status', 'confirmed')
                ->join('account_codes', 'budget_actuals.account_code_id', '=', 'account_codes.id')
                ->join('account_categories', 'account_codes.account_category_id', '=', 'account_categories.id')
                ->whereNotNull('account_categories.account_sub_category_id')
                ->selectRaw('account_categories.account_sub_category_id as sc, SUM(budget_actuals.amount) as total')
                ->groupBy('account_categories.account_sub_category_id')
                ->pluck('total', 'sc')
                ->toArray();
        }

        $cs = fn(float $val, float $base): ?float =>
            $base != 0 ? round($val / $base * 100, 1) : null;

        // Pre-pass: record the running total at each subtotal point
        $subtotalRunBudgets     = [];
        $subtotalRunPrevBudgets = [];
        {
            $tmpBud  = 0.0;
            $tmpPrev = 0.0;
            foreach ($config->lines as $ln) {
                if ($ln->line_type === 'sub_category') {
                    $sign     = $ln->operator === 'subtract' ? -1 : 1;
                    $tmpBud  += $sign * (float)($budgetBySubCat[$ln->sub_category_id]     ?? 0);
                    $tmpPrev += $sign * (float)($prevBudgetBySubCat[$ln->sub_category_id] ?? 0);
                } elseif ($ln->line_type === 'subtotal' && $ln->label) {
                    $subtotalRunBudgets[$ln->label]     = $tmpBud;
                    $subtotalRunPrevBudgets[$ln->label] = $tmpPrev;
                }
            }
        }

        $hasCs         = false;
        $csBases       = [];
        $lines         = [];
        $runBudget     = 0.0;
        $runActual     = 0.0;
        $runPrevBudget = 0.0;
        $runPrevActual = 0.0;

        foreach ($config->lines as $line) {
            if ($line->line_type === 'sub_category') {
                $scId    = $line->sub_category_id;
                $budget  = (float)($budgetBySubCat[$scId]      ?? 0);
                $actual  = (float)($actualsBySubCat[$scId]     ?? 0);
                $prevBud = (float)($prevBudgetBySubCat[$scId]  ?? 0);
                $prevAct = (float)($prevActualsBySubCat[$scId] ?? 0);
                $sign    = $line->operator === 'subtract' ? -1 : 1;

                $runBudget     += $sign * $budget;
                $runActual     += $sign * $actual;
                $runPrevBudget += $sign * $prevBud;
                $runPrevActual += $sign * $prevAct;

                $isExpense = $line->operator === 'subtract';
                $variance  = $isExpense ? $budget - $actual : $actual - $budget;
                $pct       = $budget != 0 ? round(($variance / abs($budget)) * 100, 1) : 0;
                $prevVar   = $isExpense ? $prevBud - $prevAct : $prevAct - $prevBud;
                $prevPct   = $prevBud != 0 ? round(($prevVar / abs($prevBud)) * 100, 1) : 0;
                $growthPct = $prevAct != 0 ? round((($actual - $prevAct) / abs($prevAct)) * 100, 1) : null;

                $displayLabel = $line->label
                    ?: (($line->operator === 'subtract' ? 'Less: ' : '') . ($line->subCategory?->name ?? ''));

                $csBaseId    = $line->cs_base_sub_category_id;
                $csBaseLabel = $line->cs_base_subtotal_label;
                if ($csBaseId) {
                    $csBaseBud = (float)($budgetBySubCat[$csBaseId]     ?? 0);
                    $csPrevBud = (float)($prevBudgetBySubCat[$csBaseId] ?? 0);
                } elseif ($csBaseLabel) {
                    $csBaseBud = (float)($subtotalRunBudgets[$csBaseLabel]     ?? 0);
                    $csPrevBud = (float)($subtotalRunPrevBudgets[$csBaseLabel] ?? 0);
                } else {
                    $csBaseBud = 0;
                    $csPrevBud = 0;
                }

                if ($csBaseId || $csBaseLabel) {
                    $hasCs = true;
                    $csBases[$scId] = ['bud' => $csBaseBud, 'prev' => $csPrevBud];
                }

                $lines[] = [
                    'type'        => 'sub_category',
                    'label'       => $displayLabel,
                    'operator'    => $line->operator,
                    'sub_cat_id'  => $scId,
                    'budget'      => $budget,
                    'actual'      => $actual,
                    'prev_budget' => $prevBud,
                    'prev_actual' => $prevAct,
                    'variance'    => $variance,
                    'pct'         => $pct,
                    'prev_var'    => $prevVar,
                    'prev_pct'    => $prevPct,
                    'growth_pct'  => $growthPct,
                    'cs_bud'      => $cs($budget,  $csBaseBud),
                    'cs_prev_bud' => $cs($prevBud, $csPrevBud),
                ];

            } elseif ($line->line_type === 'subtotal') {
                $variance  = $runActual - $runBudget;
                $pct       = $runBudget != 0 ? round(($variance / abs($runBudget)) * 100, 1) : 0;
                $prevVar   = $runPrevActual - $runPrevBudget;
                $prevPct   = $runPrevBudget != 0 ? round(($prevVar / abs($runPrevBudget)) * 100, 1) : 0;
                $growthPct = $runPrevActual != 0
                    ? round((($runActual - $runPrevActual) / abs($runPrevActual)) * 100, 1) : null;

                $stCsBaseId    = $line->cs_base_sub_category_id;
                $stCsBaseLabel = $line->cs_base_subtotal_label;
                if ($stCsBaseId) {
                    $stCsBaseBud = (float)($budgetBySubCat[$stCsBaseId]     ?? 0);
                    $stCsPrevBud = (float)($prevBudgetBySubCat[$stCsBaseId] ?? 0);
                } elseif ($stCsBaseLabel) {
                    $stCsBaseBud = (float)($subtotalRunBudgets[$stCsBaseLabel]     ?? 0);
                    $stCsPrevBud = (float)($subtotalRunPrevBudgets[$stCsBaseLabel] ?? 0);
                } else {
                    $stCsBaseBud = 0;
                    $stCsPrevBud = 0;
                }
                if ($stCsBaseId || $stCsBaseLabel) {
                    $hasCs = true;
                }

                $lines[] = [
                    'type'        => 'subtotal',
                    'label'       => $line->label ?? 'Subtotal',
                    'budget'      => $runBudget,
                    'actual'      => $runActual,
                    'prev_budget' => $runPrevBudget,
                    'prev_actual' => $runPrevActual,
                    'variance'    => $variance,
                    'pct'         => $pct,
                    'prev_var'    => $prevVar,
                    'prev_pct'    => $prevPct,
                    'growth_pct'  => $growthPct,
                    'cs_bud'      => $cs($runBudget,     $stCsBaseBud),
                    'cs_prev_bud' => $cs($runPrevBudget, $stCsPrevBud),
                ];

            } else { // spacer
                $lines[] = ['type' => 'spacer'];
            }
        }

        return [
            'lines'          => $lines,
            'has_cs'         => $hasCs,
            'cs_bases'       => $csBases,
            'final_budget'   => $runBudget,
            'final_actual'   => $runActual,
            'final_prev_bud' => $runPrevBudget,
            'final_prev_act' => $runPrevActual,
            'final_cs_bud'   => null,
            'final_cs_prev'  => null,
        ];
    }
}
