<?php

namespace App\Imports;

use App\Models\BudgetLineItem;
use App\Models\BudgetVersion;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Collection;

class BudgetImport implements
    ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    public array $errors   = [];
    public int   $imported = 0;

    protected string $mode;

    public function __construct(protected BudgetVersion $version)
    {
        $this->mode = $version->period->entry_mode ?? 'quarterly';
    }

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
                    $this->errors[] = "Row ".($index+3).": Line item ID {$lineItemId} not found.";
                    continue;
                }

                if ($this->mode === 'monthly') {
                    $monthNames = ['Jan','Feb','Mar','Apr','May','Jun',
                                   'Jul','Aug','Sep','Oct','Nov','Dec'];
                    $update = [];
                    foreach ($monthNames as $i => $label) {
                        $m = $i + 1;
                        $update["m{$m}_amount"] = max(0, (float) ($row[$label] ?? $row[strtolower($label)] ?? 0));
                    }
                } else {
                    // Quarterly: read Q1–Q4, spread equally into months
                    $q1 = max(0, (float) ($row['Q1 (Jan-Mar)'] ?? $row['q1'] ?? $row['Q1'] ?? 0));
                    $q2 = max(0, (float) ($row['Q2 (Apr-Jun)'] ?? $row['q2'] ?? $row['Q2'] ?? 0));
                    $q3 = max(0, (float) ($row['Q3 (Jul-Sep)'] ?? $row['q3'] ?? $row['Q3'] ?? 0));
                    $q4 = max(0, (float) ($row['Q4 (Oct-Dec)'] ?? $row['q4'] ?? $row['Q4'] ?? 0));

                    $spread = fn(float $q) => [round($q/3, 2), round($q/3, 2), round($q - round($q/3, 2)*2, 2)];
                    [$m1,$m2,$m3]    = $spread($q1);
                    [$m4,$m5,$m6]    = $spread($q2);
                    [$m7,$m8,$m9]    = $spread($q3);
                    [$m10,$m11,$m12] = $spread($q4);

                    $update = compact('m1','m2','m3','m4','m5','m6','m7','m8','m9','m10','m11','m12');
                    $update = array_combine(
                        array_map(fn($k) => "{$k}_amount", array_keys($update)),
                        array_values($update)
                    );
                }

                $item->update([
                    ...$update,
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
        if ($this->mode === 'monthly') {
            $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            return array_fill_keys(array_map(fn($m) => "*.$m", $months), ['nullable','numeric','min:0']);
        }

        return [
            '*.Q1 (Jan-Mar)' => ['nullable', 'numeric', 'min:0'],
            '*.Q2 (Apr-Jun)' => ['nullable', 'numeric', 'min:0'],
            '*.Q3 (Jul-Sep)' => ['nullable', 'numeric', 'min:0'],
            '*.Q4 (Oct-Dec)' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
