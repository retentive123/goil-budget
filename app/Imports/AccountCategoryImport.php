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

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                $code = strtoupper(trim($row['code'] ?? ''));
                $name = trim($row['name'] ?? '');

                if (!$code || !$name) {
                    $this->errors[] = "Row ".($index+2).": Code and Name are required.";
                    continue;
                }

                $existing = AccountCategory::where('code', $code)->first();

                if ($existing) {
                    $existing->update([
                        'name'        => $name,
                        'description' => $row['description'] ?? null,
                    ]);
                    $this->updated++;
                } else {
                    AccountCategory::create([
                        'code'        => $code,
                        'name'        => $name,
                        'description' => $row['description'] ?? null,
                        'is_active'   => true,
                    ]);
                    $this->imported++;
                }

            } catch (\Exception $e) {
                $this->errors[] = "Row ".($index+2).": ".$e->getMessage();
            }
        }
    }
}
