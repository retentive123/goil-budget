<?php

namespace App\Imports;

use App\Models\BudgetLineItem;
use App\Models\BudgetVersion;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class BudgetImport implements
    ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    public array  $errors   = [];
    public int    $imported = 0;
    public int    $headingRow = 2; // Skip instruction row

    public function __construct(
        protected BudgetVersion $version
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
                    $this->errors[] = "Row ".($index+3).": Line item ID {$lineItemId} not found in this budget.";
                    continue;
                }

                $q1 = max(0, (float) ($row['q1_jan-mar'] ?? $row['q1'] ?? 0));
                $q2 = max(0, (float) ($row['q2_apr-jun'] ?? $row['q2'] ?? 0));
                $q3 = max(0, (float) ($row['q3_jul-sep'] ?? $row['q3'] ?? 0));
                $q4 = max(0, (float) ($row['q4_oct-dec'] ?? $row['q4'] ?? 0));

                $item->update([
                    'q1_amount'       => $q1,
                    'q2_amount'       => $q2,
                    'q3_amount'       => $q3,
                    'q4_amount'       => $q4,
                    'justification'   => $row['justification'] ?? null,
                    'last_updated_by' => auth()->id(),
                ]);

                $this->imported++;

            } catch (\Exception $e) {
                $this->errors[] = "Row ".($index+3).": " . $e->getMessage();
            }
        }
    }

    public function rules(): array
    {
        return [
            '*.q1_jan-mar' => ['nullable','numeric','min:0'],
            '*.q2_apr-jun' => ['nullable','numeric','min:0'],
            '*.q3_jul-sep' => ['nullable','numeric','min:0'],
            '*.q4_oct-dec' => ['nullable','numeric','min:0'],
        ];
    }
}
