<?php

namespace App\Imports;

use App\Models\AccountCode;
use App\Models\AccountCategory;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class AccountCodeImport implements ToCollection, WithHeadingRow
{
    public array $errors   = [];
    public int   $imported = 0;
    public int   $updated  = 0;
    public int   $skipped  = 0;

    public function collection(Collection $rows)
    {
        // Pre-load all lookups — 2 queries for entire import
        $categories = AccountCategory::all()->keyBy(fn($c) => strtoupper($c->code));
        $existing   = AccountCode::all()->keyBy(fn($c) => strtoupper($c->code));

        $toInsert = [];
        $toUpdate = [];
        $now      = now();

        foreach ($rows as $index => $row) {
            try {
                $catCode  = strtoupper(trim($row['category_code'] ?? ''));
                $code     = strtoupper(trim($row['code'] ?? ''));
                $name     = trim($row['name'] ?? '');

                if (!$catCode && !$code && !$name) {
                    $this->skipped++;
                    continue;
                }

                if (!$catCode || !$code || !$name) {
                    $this->errors[] = "Row " . ($index + 2) . ": category_code, code and name are all required.";
                    continue;
                }

                $category = $categories->get($catCode);
                if (!$category) {
                    $this->errors[] = "Row " . ($index + 2) . ": Category code '{$catCode}' not found.";
                    continue;
                }

                if ($existing->has($code)) {
                    $toUpdate[] = [
                        'id'                  => $existing->get($code)->id,
                        'account_category_id' => $category->id,
                        'name'                => $name,
                        'description'         => trim($row['description'] ?? '') ?: null,
                        'updated_at'          => $now,
                    ];
                    $this->updated++;
                } else {
                    $toInsert[] = [
                        'account_category_id' => $category->id,
                        'code'                => $code,
                        'name'                => $name,
                        'description'         => trim($row['description'] ?? '') ?: null,
                        'is_active'           => true,
                        'created_at'          => $now,
                        'updated_at'          => $now,
                    ];
                    $this->imported++;
                }
            } catch (\Throwable $e) {
                $this->errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        foreach (array_chunk($toInsert, 500) as $chunk) {
            DB::table('account_codes')->insert($chunk);
        }

        foreach (array_chunk($toUpdate, 500) as $chunk) {
            DB::table('account_codes')->upsert(
                $chunk,
                ['id'],
                ['account_category_id', 'name', 'description', 'updated_at']
            );
        }
    }
}
