<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\Zone;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class ServiceStationImport implements ToCollection, WithHeadingRow
{
    public array $errors   = [];
    public int   $imported = 0;
    public int   $updated  = 0;
    public int   $skipped  = 0;

    public function collection(Collection $rows)
    {
        // Pre-load all lookups — 3 queries total for entire import
        $zones    = Zone::where('is_active', true)->get()->keyBy(fn($z) => strtoupper($z->code));
        $existing = Department::serviceStations()->get()->keyBy(fn($s) => strtoupper($s->code));
        // Name collision map (lowercase → true); updated as we queue new inserts
        $allNames = Department::pluck('name')
            ->map(fn($n) => strtolower(trim($n)))
            ->flip()
            ->all();

        $toInsert = [];
        $toUpdate = [];
        $now      = now();

        foreach ($rows as $index => $row) {
            try {
                $zoneCode    = strtoupper(trim($row['zone_code'] ?? ''));
                $code        = strtoupper(trim($row['code'] ?? ''));
                $name        = trim($row['name'] ?? '');
                $budgetType  = strtolower(trim($row['budget_type'] ?? '')) ?: 'both';
                $description = trim($row['description'] ?? '') ?: null;
                $rowNum      = $index + 2;

                if (!$zoneCode && !$code && !$name) {
                    $this->skipped++;
                    continue;
                }

                if (!$zoneCode || !$code || !$name) {
                    $this->errors[] = "Row {$rowNum}: zone_code, code and name are all required.";
                    continue;
                }

                $zone = $zones->get($zoneCode);
                if (!$zone) {
                    $this->errors[] = "Row {$rowNum}: Zone code '{$zoneCode}' not found or inactive.";
                    continue;
                }

                if (!in_array($budgetType, ['revenue', 'expense', 'both'])) {
                    $this->errors[] = "Row {$rowNum}: budget_type must be revenue, expense, or both (got '{$budgetType}').";
                    continue;
                }

                $isActiveRaw = strtolower(trim($row['is_active'] ?? ''));
                $isActive    = !in_array($isActiveRaw, ['no', '0', 'false']);

                if ($existing->has($code)) {
                    $toUpdate[] = [
                        'id'          => $existing->get($code)->id,
                        'zone_id'     => $zone->id,
                        'name'        => $name,
                        'description' => $description,
                        'budget_type' => $budgetType,
                        'is_active'   => $isActive,
                        'updated_at'  => $now,
                    ];
                    $this->updated++;
                } else {
                    $nameKey = strtolower($name);
                    if (isset($allNames[$nameKey])) {
                        $this->errors[] = "Row {$rowNum}: Name '{$name}' is already in use by another department or station.";
                        continue;
                    }
                    $toInsert[] = [
                        'zone_id'     => $zone->id,
                        'code'        => $code,
                        'name'        => $name,
                        'description' => $description,
                        'budget_type' => $budgetType,
                        'is_active'   => $isActive,
                        'entity_type' => 'service_station',
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ];
                    $allNames[$nameKey] = true; // prevent duplicates within same upload
                    $this->imported++;
                }
            } catch (\Throwable $e) {
                $this->errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        // Bulk write — one INSERT batch + one UPSERT batch
        foreach (array_chunk($toInsert, 500) as $chunk) {
            DB::table('departments')->insert($chunk);
        }

        foreach (array_chunk($toUpdate, 500) as $chunk) {
            DB::table('departments')->upsert(
                $chunk,
                ['id'],
                ['zone_id', 'name', 'description', 'budget_type', 'is_active', 'updated_at']
            );
        }
    }
}
