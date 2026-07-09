<?php

namespace App\Services;

use App\Models\BudgetActual;
use App\Models\BudgetVersion;
use App\Models\BudgetLineItem;
use App\Models\BudgetPeriod;

class BudgetCalculationService
{
    /**
     * Auto-populate line items for a new budget version
     */
    public function populateLineItems(BudgetVersion $version): void
    {
        $department = $version->department;

        // ✅ If department is null, skip population
        if (!$department) {
            return;
        }

        $accountCodes = $department->accountCodes()
                                   ->where('account_codes.is_active', true)
                                   ->with('category')
                                   ->get();

        foreach ($accountCodes as $code) {
            $lineType = match($code->category->budget_type) {
                'revenue'              => 'revenue',
                'both'                 => 'expense',
                'capital_expenditure'  => 'capex',
                'assets'               => 'asset',
                'liabilities'          => 'liability',
                default                => 'expense',
            };

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
                    'line_type'       => $lineType,
                    'last_updated_by' => auth()->id(),
                ]
            );
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

        return [
            'q1'    => $items->sum('q1_amount'),
            'q2'    => $items->sum('q2_amount'),
            'q3'    => $items->sum('q3_amount'),
            'q4'    => $items->sum('q4_amount'),
            'total' => $version->effectiveTotal(),
        ];
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
                ->where('department_id', $version->department_id)
                ->where('status', 'confirmed')
                ->selectRaw('account_code_id, sum(amount) as total')
                ->groupBy('account_code_id')
                ->pluck('total', 'account_code_id')
                ->map(fn($v) => (float) $v)
                ->toArray();

            $prevVersionIds = BudgetVersion::where('budget_period_id', $prevPeriod->id)
                ->where('department_id', $version->department_id)
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

        $blank = ['q1'=>0,'q2'=>0,'q3'=>0,'q4'=>0,'total'=>0,'effective'=>0,'prev_budget'=>0,'prev_actual'=>0];
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
                    'totals' => ['q1'=>0,'q2'=>0,'q3'=>0,'q4'=>0,'total'=>0,'effective'=>0,'prev_budget'=>0,'prev_actual'=>0,'common_size'=>0],
                ];
            }

            $idx       = $catIndex[$type][$catName];
            $supp      = $item->approvedSupplementaryTotal();
            $effective = (float) $item->total_amount + $supp;
            $prevB     = $prevBudgets[$codeId] ?? 0.0;
            $prevA     = $prevActuals[$codeId] ?? 0.0;

            $sections[$type]['categories'][$idx]['items'][] = [
                'id'            => $item->id,
                'code'          => $item->accountCode->code,
                'name'          => $item->accountCode->name,
                'q1'            => (float) $item->q1_amount,
                'q2'            => (float) $item->q2_amount,
                'q3'            => (float) $item->q3_amount,
                'q4'            => (float) $item->q4_amount,
                'total'         => (float) $item->total_amount,
                'supp'          => $supp,
                'effective'     => $effective,
                'prev_budget'   => $prevB,
                'prev_actual'   => $prevA,
                'justification' => $item->justification ?? '',
                'line_type'     => $item->line_type,
                'common_size'   => 0,
            ];

            foreach (['q1','q2','q3','q4'] as $k) {
                $sections[$type]['totals'][$k] += (float) $item->{$k.'_amount'};
            }
            $sections[$type]['totals']['total']       += (float) $item->total_amount;
            $sections[$type]['totals']['effective']   += $effective;
            $sections[$type]['totals']['prev_budget'] += $prevB;
            $sections[$type]['totals']['prev_actual'] += $prevA;
        }

        // Recompute category totals cleanly
        foreach (['revenue','expense','capex','balance'] as $type) {
            foreach ($sections[$type]['categories'] as &$cat) {
                $cat['totals'] = ['q1'=>0,'q2'=>0,'q3'=>0,'q4'=>0,'total'=>0,'effective'=>0,'prev_budget'=>0,'prev_actual'=>0,'common_size'=>0];
                foreach ($cat['items'] as $it) {
                    $cat['totals']['q1']          += $it['q1'];
                    $cat['totals']['q2']          += $it['q2'];
                    $cat['totals']['q3']          += $it['q3'];
                    $cat['totals']['q4']          += $it['q4'];
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
}
