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
    public array $errors              = [];
    public int   $imported            = 0;
    /** Number of rows where an admin-locked Rate or Frequency was changed in the file and silently ignored. */
    public int   $skippedAdminOverrides = 0;

    protected string $entryMode;
    protected string $calcMode;
    protected bool   $adminSetsRate;
    protected bool   $adminSetsFreq;
    /** Period rate snapshots keyed by account_code_id for fast lookup. */
    protected \Illuminate\Support\Collection $periodCodeRates;

    public function __construct(protected BudgetVersion $version)
    {
        $period              = $version->period;
        $this->entryMode     = $period->entry_mode ?? 'quarterly';
        $this->calcMode      = $period->calcMode();
        $this->adminSetsRate = $period->adminSetsRate();
        $this->adminSetsFreq = $period->adminSetsFreq();

        // Pre-load period rate snapshots (keyed by account_code_id) so the
        // null-rate fallback in buildCalcModeUpdate never hits N+1 queries.
        $this->periodCodeRates = ($this->adminSetsRate || $this->adminSetsFreq)
            ? $period->codeRates()->get()->keyBy('account_code_id')
            : collect();
    }

    // The data sheet has an instruction banner at row 1, headings at row 2
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
                    $this->errors[] = "Row ".($index + 3).": Line item ID {$lineItemId} not found.";
                    continue;
                }

                // ── Qty × Rate [× Freq] mode ──────────────────────────────────
                if ($this->calcMode !== 'none') {
                    $update = $this->buildCalcModeUpdate($row, $item);
                }

                // ── Monthly direct-entry ──────────────────────────────────────
                elseif ($this->entryMode === 'monthly') {
                    $update = $this->buildMonthlyUpdate($row);
                }

                // ── Quarterly direct-entry ────────────────────────────────────
                else {
                    $update = $this->buildQuarterlyUpdate($row);
                }

                $item->update([
                    ...$update,
                    'justification'   => $row['justification'] ?? $row['Justification'] ?? null,
                    'last_updated_by' => auth()->id(),
                ]);

                $this->imported++;

            } catch (\Exception $e) {
                $this->errors[] = "Row ".($index + 3).": " . $e->getMessage();
            }
        }
    }

    // ── Validation rules ──────────────────────────────────────────────────────

    public function rules(): array
    {
        if ($this->calcMode !== 'none') {
            $rules = [
                '*.quantity' => ['nullable', 'numeric', 'min:0'],
            ];
            // Only validate rate from file if inputters can set it
            if (!$this->adminSetsRate) {
                $rules['*.rate'] = ['nullable', 'numeric', 'min:0'];
            }
            if ($this->calcMode === 'qty_rate_freq' && !$this->adminSetsFreq) {
                $rules['*.frequency'] = ['nullable', 'numeric', 'min:0'];
            }
            return $rules;
        }

        if ($this->entryMode === 'monthly') {
            $months = ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'];
            return array_fill_keys(
                array_map(fn($m) => "*.$m", $months),
                ['nullable', 'numeric', 'min:0']
            );
        }

        // Quarterly
        return [
            '*.q1_jan_mar' => ['nullable', 'numeric', 'min:0'],
            '*.q2_apr_jun' => ['nullable', 'numeric', 'min:0'],
            '*.q3_jul_sep' => ['nullable', 'numeric', 'min:0'],
            '*.q4_oct_dec' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Build the DB update array for Qty × Rate [× Freq] mode.
     * Admin-controlled Rate/Freq columns are read from the stored line item
     * (not from the uploaded file) so the budget inputter cannot override them.
     * Any attempt to change an admin-locked value is counted in $skippedAdminOverrides.
     */
    private function buildCalcModeUpdate(Collection $row, BudgetLineItem $item): array
    {
        $qty = max(0, (float) ($row['quantity'] ?? 0));

        // Rate: use file value only when the inputter is allowed to set it.
        // When admin-locked, detect if the file value was changed and tally it.
        if ($this->adminSetsRate) {
            // Use the stored rate; if null (code added after period opened),
            // fall back to the period's snapshot rate for that code.
            $storedRate = $item->rate;
            if ($storedRate === null) {
                $snap       = $this->periodCodeRates->get($item->account_code_id);
                $storedRate = $snap?->rate ?? $item->accountCode?->default_rate ?? 1;
            }
            $rate     = (float) $storedRate;
            $fileRate = trim((string) ($row['rate'] ?? '')) !== '' ? (float) $row['rate'] : null;
            if ($fileRate !== null && abs($fileRate - $rate) > 0.0001) {
                $this->skippedAdminOverrides++;
            }
        } else {
            $rate = max(0, (float) ($row['rate'] ?? $item->rate ?? 0));
        }

        // Frequency: only relevant for qty_rate_freq mode.
        if ($this->calcMode === 'qty_rate_freq') {
            if ($this->adminSetsFreq) {
                $freq     = (float) ($item->frequency ?? 1);
                $fileFreq = trim((string) ($row['frequency'] ?? '')) !== '' ? (float) $row['frequency'] : null;
                if ($fileFreq !== null && abs($fileFreq - $freq) > 0.0001) {
                    $this->skippedAdminOverrides++;
                }
            } else {
                $freq = max(0, (float) ($row['frequency'] ?? $item->frequency ?? 1));
            }
            if ($freq <= 0) $freq = 1.0;
        } else {
            $freq = 1.0;
        }

        // Annual total spread equally across 12 months
        $annual = $qty * $rate * $freq;
        $share  = round($annual / 12, 2);
        $last   = round($annual - $share * 11, 2);

        return [
            'quantity'    => $qty,
            'rate'        => $rate,
            'frequency'   => $freq,
            'm1_amount'   => $share, 'm2_amount'  => $share, 'm3_amount'  => $share,
            'm4_amount'   => $share, 'm5_amount'  => $share, 'm6_amount'  => $share,
            'm7_amount'   => $share, 'm8_amount'  => $share, 'm9_amount'  => $share,
            'm10_amount'  => $share, 'm11_amount' => $share, 'm12_amount' => $last,
        ];
    }

    private function buildMonthlyUpdate(Collection $row): array
    {
        $months = ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'];
        $update = [];
        foreach ($months as $i => $label) {
            $m = $i + 1;
            // Accept both capitalised and lower-case keys (WithHeadingRow normalises to lower)
            $update["m{$m}_amount"] = max(0, (float) ($row[$label] ?? $row[ucfirst($label)] ?? 0));
        }
        return $update;
    }

    private function buildQuarterlyUpdate(Collection $row): array
    {
        // WithHeadingRow normalises "Q1 (Jan-Mar)" → "q1_jan_mar" (Str::slug style)
        // Accept the normalised key and several fallbacks for resilience
        $q1 = max(0, (float) ($row['q1_jan_mar'] ?? $row['q1'] ?? $row['Q1'] ?? $row['Q1 (Jan-Mar)'] ?? 0));
        $q2 = max(0, (float) ($row['q2_apr_jun'] ?? $row['q2'] ?? $row['Q2'] ?? $row['Q2 (Apr-Jun)'] ?? 0));
        $q3 = max(0, (float) ($row['q3_jul_sep'] ?? $row['q3'] ?? $row['Q3'] ?? $row['Q3 (Jul-Sep)'] ?? 0));
        $q4 = max(0, (float) ($row['q4_oct_dec'] ?? $row['q4'] ?? $row['Q4'] ?? $row['Q4 (Oct-Dec)'] ?? 0));

        $spread = fn(float $q) => [
            round($q / 3, 2),
            round($q / 3, 2),
            round($q - round($q / 3, 2) * 2, 2),
        ];

        [$m1, $m2, $m3]    = $spread($q1);
        [$m4, $m5, $m6]    = $spread($q2);
        [$m7, $m8, $m9]    = $spread($q3);
        [$m10, $m11, $m12] = $spread($q4);

        return [
            'm1_amount'  => $m1,  'm2_amount'  => $m2,  'm3_amount'  => $m3,
            'm4_amount'  => $m4,  'm5_amount'  => $m5,  'm6_amount'  => $m6,
            'm7_amount'  => $m7,  'm8_amount'  => $m8,  'm9_amount'  => $m9,
            'm10_amount' => $m10, 'm11_amount' => $m11, 'm12_amount' => $m12,
        ];
    }
}
