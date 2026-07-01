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
    public int    $headingRow = 2;

    public function __construct(
        protected BudgetVersion $version
    ) {}

    public function headingRow(): int { return 2; }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                $lineItemId = (int) $row['line_item_id'];

                if (!$lineItemId) {
                    $this->errors[] = "Row ".($index+3).": Missing line_item_id.";
                    continue;
                }

                $item = BudgetLineItem::where('id', $lineItemId)
                    ->where('budget_version_id', $this->version->id)
                    ->first();

                if (!$item) {
                    $this->errors[] = "Row ".($index+3).": Line item ID {$lineItemId} not found.";
                    continue;
                }

                // ✅ EXACT column names from your Excel
                $q1 = max(0, (float) ($row['Q1 (Jan-Mar)'] ?? 0));
                $q2 = max(0, (float) ($row['Q2 (Apr-Jun)'] ?? 0));
                $q3 = max(0, (float) ($row['Q3 (Jul-Sep)'] ?? 0));
                $q4 = max(0, (float) ($row['Q4 (Oct-Dec)'] ?? 0));

                // ✅ Fallback for alternative column names
                if ($q1 === 0 && $q2 === 0 && $q3 === 0 && $q4 === 0) {
                    $q1 = max(0, (float) ($row['q1'] ?? $row['Q1'] ?? 0));
                    $q2 = max(0, (float) ($row['q2'] ?? $row['Q2'] ?? 0));
                    $q3 = max(0, (float) ($row['q3'] ?? $row['Q3'] ?? 0));
                    $q4 = max(0, (float) ($row['q4'] ?? $row['Q4'] ?? 0));
                }

                $item->update([
                    'q1_amount'       => $q1,
                    'q2_amount'       => $q2,
                    'q3_amount'       => $q3,
                    'q4_amount'       => $q4,
                    'justification'   => $row['justification'] ?? $row['Justification'] ?? null,
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
            // ✅ EXACT column names from your Excel
            '*.Q1 (Jan-Mar)' => ['nullable', 'numeric', 'min:0'],
            '*.Q2 (Apr-Jun)' => ['nullable', 'numeric', 'min:0'],
            '*.Q3 (Jul-Sep)' => ['nullable', 'numeric', 'min:0'],
            '*.Q4 (Oct-Dec)' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
