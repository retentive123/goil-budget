<?php

namespace App\Imports;

use App\Models\AccountCategory;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Collection;

class AccountCategoryImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public array $errors   = [];
    public int   $imported = 0;
    public int   $updated  = 0;

    private const VALID_TYPES = ['revenue', 'expense', 'both', 'assets', 'liabilities', 'capital_expenditure'];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                $code = strtoupper(trim($row['code'] ?? ''));
                $name = trim($row['name'] ?? '');

                if (!$code || !$name) {
                    $this->errors[] = "Row " . ($index + 2) . ": Code and Name are required.";
                    continue;
                }

                // Read and validate budget_type — default to 'expense' if blank
                $budgetType = strtolower(trim($row['budget_type'] ?? ''));
                if ($budgetType === '') {
                    $budgetType = 'expense';
                } elseif (!in_array($budgetType, self::VALID_TYPES)) {
                    $this->errors[] = "Row " . ($index + 2) . ": Invalid budget_type '{$budgetType}'. "
                        . "Must be one of: " . implode(', ', self::VALID_TYPES) . ". Row skipped.";
                    continue;
                }

                $existing = AccountCategory::where('code', $code)->first();

                if ($existing) {
                    $existing->update([
                        'name'        => $name,
                        'budget_type' => $budgetType,
                        'description' => $row['description'] ?? null,
                    ]);
                    $this->updated++;
                } else {
                    AccountCategory::create([
                        'code'        => $code,
                        'name'        => $name,
                        'budget_type' => $budgetType,
                        'description' => $row['description'] ?? null,
                        'is_active'   => true,
                    ]);
                    $this->imported++;
                }

            } catch (\Exception $e) {
                $this->errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }
    }
}
