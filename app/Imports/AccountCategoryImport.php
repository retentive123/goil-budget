<?php

namespace App\Imports;

use App\Models\AccountCategory;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class AccountCategoryImport implements ToCollection, WithHeadingRow
{
    public array $errors   = [];
    public int   $imported = 0;
    public int   $updated  = 0;
    public int   $skipped  = 0;

    private const VALID_TYPES = ['revenue', 'expense', 'both', 'assets', 'liabilities', 'capital_expenditure'];

    public function collection(Collection $rows)
    {
        // Pre-load all existing categories — 1 query for entire import
        $existing = AccountCategory::all()->keyBy(fn($c) => strtoupper($c->code));

        $toInsert = [];
        $toUpdate = [];
        $now      = now();

        foreach ($rows as $index => $row) {
            try {
                $code = strtoupper(trim($row['code'] ?? ''));
                $name = trim($row['name'] ?? '');

                if (!$code && !$name) {
                    $this->skipped++;
                    continue;
                }

                if (!$code || !$name) {
                    $this->errors[] = "Row " . ($index + 2) . ": Code and Name are required.";
                    continue;
                }

                $budgetType = strtolower(trim($row['budget_type'] ?? '')) ?: 'expense';
                if (!in_array($budgetType, self::VALID_TYPES)) {
                    $this->errors[] = "Row " . ($index + 2) . ": Invalid budget_type '{$budgetType}'. "
                        . "Must be one of: " . implode(', ', self::VALID_TYPES) . ". Row skipped.";
                    continue;
                }

                $description = trim($row['description'] ?? '') ?: null;

                if ($existing->has($code)) {
                    $toUpdate[] = [
                        'id'          => $existing->get($code)->id,
                        'name'        => $name,
                        'budget_type' => $budgetType,
                        'description' => $description,
                        'updated_at'  => $now,
                    ];
                    $this->updated++;
                } else {
                    $toInsert[] = [
                        'code'        => $code,
                        'name'        => $name,
                        'budget_type' => $budgetType,
                        'description' => $description,
                        'is_active'   => true,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ];
                    $this->imported++;
                }
            } catch (\Throwable $e) {
                $this->errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        foreach (array_chunk($toInsert, 500) as $chunk) {
            DB::table('account_categories')->insert($chunk);
        }

        foreach (array_chunk($toUpdate, 500) as $chunk) {
            DB::table('account_categories')->upsert(
                $chunk,
                ['id'],
                ['name', 'budget_type', 'description', 'updated_at']
            );
        }
    }
}
