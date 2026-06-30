<?php

namespace App\Imports;

use App\Models\AccountCode;
use App\Models\AccountCategory;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Collection;

class AccountCodeImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public array $errors   = [];
    public int   $imported = 0;
    public int   $updated  = 0;

    public function collection(Collection $rows)
    {
        // Cache categories by code for performance
        $categories = AccountCategory::all()->keyBy('code');

        foreach ($rows as $index => $row) {
            try {
                $catCode  = strtoupper(trim($row['category_code'] ?? ''));
                $code     = strtoupper(trim($row['code'] ?? ''));
                $name     = trim($row['name'] ?? '');

                if (!$catCode || !$code || !$name) {
                    $this->errors[] = "Row ".($index+2).
                        ": category_code, code and name are all required.";
                    continue;
                }

                $category = $categories->get($catCode);
                if (!$category) {
                    $this->errors[] = "Row ".($index+2).
                        ": Category code '{$catCode}' not found.";
                    continue;
                }

                $existing = AccountCode::where('code', $code)->first();

                if ($existing) {
                    $existing->update([
                        'account_category_id' => $category->id,
                        'name'                => $name,
                        'description'         => $row['description'] ?? null,
                    ]);
                    $this->updated++;
                } else {
                    AccountCode::create([
                        'account_category_id' => $category->id,
                        'code'                => $code,
                        'name'                => $name,
                        'description'         => $row['description'] ?? null,
                        'is_active'           => true,
                    ]);
                    $this->imported++;
                }

            } catch (\Exception $e) {
                $this->errors[] = "Row ".($index+2).": ".$e->getMessage();
            }
        }
    }
}
