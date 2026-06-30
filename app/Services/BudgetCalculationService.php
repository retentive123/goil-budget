<?php

namespace App\Services;

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
                'revenue' => 'revenue',
                'both'    => 'expense',
                default   => 'expense',
            };

            BudgetLineItem::firstOrCreate(
                [
                    'budget_version_id' => $version->id,
                    'account_code_id'   => $code->id,
                ],
                [
                    'q1_amount'       => 0,
                    'q2_amount'       => 0,
                    'q3_amount'       => 0,
                    'q4_amount'       => 0,
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
                'q1'    => 0,
                'q2'    => 0,
                'q3'    => 0,
                'q4'    => 0,
                'total' => 0,
                'items' => collect(), // ✅ Initialize as a Collection
            ];
        }

        $summary[$categoryName]['q1']    += $item->q1_amount;
        $summary[$categoryName]['q2']    += $item->q2_amount;
        $summary[$categoryName]['q3']    += $item->q3_amount;
        $summary[$categoryName]['q4']    += $item->q4_amount;
        $summary[$categoryName]['total'] += $item->total_amount;
        $summary[$categoryName]['items']->push($item); // ✅ Use push() on Collection
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
