<?php

namespace App\Imports;

use App\Models\BudgetActual;
use App\Models\BudgetLineItem;
use App\Models\BudgetVersion;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Collection;

class ActualsImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public array $errors   = [];
    public int   $imported = 0;

    public function __construct(
        protected BudgetVersion $version,
        protected int           $month,
        protected int           $year
    ) {}

    public function headingRow(): int { return 2; }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                $lineItemId = (int) $row['line_item_id'];
                if (!$lineItemId) continue;

                $item = BudgetLineItem::where('id', $lineItemId)
                    ->where('budget_version_id', $this->version->id)
                    ->first();

                if (!$item) {
                    $this->errors[] = "Row ".($index+3).": Line item not found.";
                    continue;
                }

                $amount = max(0, (float) ($row['actual_amount_ghs'] ?? $row['actual_amount'] ?? 0));

                // Skip zero rows unless there's existing data
                $existing = BudgetActual::where('budget_line_item_id', $item->id)
                    ->where('month', $this->month)->where('year', $this->year)->first();

                if ($amount == 0 && !$existing) continue;

                BudgetActual::updateOrCreate(
                    [
                        'budget_line_item_id' => $item->id,
                        'month'               => $this->month,
                        'year'                => $this->year,
                    ],
                    [
                        'budget_period_id' => $this->version->budget_period_id,
                        'department_id'    => $this->version->department_id,
                        'account_code_id'  => $item->account_code_id,
                        'amount'           => $amount,
                        'reference'        => $row['referencevoucher'] ?? $row['reference'] ?? null,
                        'description'      => $row['notes'] ?? null,
                        'recorded_by'      => auth()->id(),
                        'status'           => 'draft',
                    ]
                );

                $this->imported++;

            } catch (\Exception $e) {
                $this->errors[] = "Row ".($index+3).": ".$e->getMessage();
            }
        }
    }
}
