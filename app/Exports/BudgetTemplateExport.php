<?php

namespace App\Exports;

use App\Models\BudgetVersion;
use App\Models\BudgetLineItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Protection;

class BudgetTemplateExport implements WithMultipleSheets
{
    public function __construct(
        protected BudgetVersion $version
    ) {}

    public function sheets(): array
    {
        return [
            new BudgetDataSheet($this->version),
            new BudgetInstructionsSheet($this->version),
        ];
    }
}

// ─────────────────────────────────────────────────────────────────────────────

class BudgetDataSheet implements
    FromCollection, WithHeadings, WithTitle,
    WithStyles, ShouldAutoSize
{
    protected string $entryMode;
    protected string $calcMode;
    protected bool   $adminSetsRate;
    protected bool   $adminSetsFreq;

    public function __construct(protected BudgetVersion $version)
    {
        $period              = $version->period;
        $this->entryMode     = $period->entry_mode ?? 'quarterly';
        $this->calcMode      = $period->calcMode();
        $this->adminSetsRate = $period->adminSetsRate();
        $this->adminSetsFreq = $period->adminSetsFreq();
    }

    public function title(): string { return 'Budget Entry'; }

    // ── Headings ──────────────────────────────────────────────────────────────

    public function headings(): array
    {
        // Qty × Rate [× Freq] mode — period-based calc
        if ($this->calcMode !== 'none') {
            $h = ['line_item_id', 'Category', 'Account Code', 'Account Name', 'Quantity', 'Rate'];
            if ($this->calcMode === 'qty_rate_freq') {
                $h[] = 'Frequency';
            }
            $h[] = 'Year Total';
            $h[] = 'Justification';
            return $h;
        }

        // Direct-entry monthly
        if ($this->entryMode === 'monthly') {
            return [
                'line_item_id', 'Category', 'Account Code', 'Account Name',
                'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
                'Total', 'Justification',
            ];
        }

        // Direct-entry quarterly (default)
        return [
            'line_item_id', 'Category', 'Account Code', 'Account Name',
            'Q1 (Jan-Mar)', 'Q2 (Apr-Jun)', 'Q3 (Jul-Sep)', 'Q4 (Oct-Dec)',
            'Total', 'Justification',
        ];
    }

    // ── Data ──────────────────────────────────────────────────────────────────

    public function collection()
    {
        $items = BudgetLineItem::where('budget_version_id', $this->version->id)
            ->with('accountCode.category')
            ->get();

        // ── Qty × Rate [× Freq] mode ──
        if ($this->calcMode !== 'none') {
            return $items->map(function ($item) {
                $row = [
                    $item->id,
                    $item->accountCode->category->name,
                    $item->accountCode->code,
                    $item->accountCode->name,
                    $item->quantity ?? 0,
                    $item->rate    ?? '',
                ];
                if ($this->calcMode === 'qty_rate_freq') {
                    $row[] = $item->frequency ?? '';
                }
                $row[] = '';           // Year Total — formula written in styles()
                $row[] = $item->justification;
                return $row;
            });
        }

        // ── Monthly direct-entry ──
        if ($this->entryMode === 'monthly') {
            return $items->map(fn($item) => [
                $item->id,
                $item->accountCode->category->name,
                $item->accountCode->code,
                $item->accountCode->name,
                $item->m1_amount,  $item->m2_amount,  $item->m3_amount,
                $item->m4_amount,  $item->m5_amount,  $item->m6_amount,
                $item->m7_amount,  $item->m8_amount,  $item->m9_amount,
                $item->m10_amount, $item->m11_amount, $item->m12_amount,
                $item->total_amount,
                $item->justification,
            ]);
        }

        // ── Quarterly direct-entry ──
        return $items->map(fn($item) => [
            $item->id,
            $item->accountCode->category->name,
            $item->accountCode->code,
            $item->accountCode->name,
            $item->q1_amount, $item->q2_amount,
            $item->q3_amount, $item->q4_amount,
            $item->total_amount,
            $item->justification,
        ]);
    }

    // ── Styles ────────────────────────────────────────────────────────────────

    public function styles(Worksheet $sheet): void
    {
        if ($this->calcMode !== 'none') {
            $this->styleCalcMode($sheet);
            return;
        }

        $isMonthly = $this->entryMode === 'monthly';
        $lastRow   = $sheet->getHighestRow();

        // Quarterly: A=id B=cat C=code D=name E-H=Q1-Q4   I=Total  J=Just
        // Monthly:   A=id B=cat C=code D=name E-P=Jan-Dec Q=Total  R=Just
        $editEnd  = $isMonthly ? 'P' : 'H';
        $totalCol = $isMonthly ? 'Q' : 'I';
        $lastCol  = $isMonthly ? 'R' : 'J';

        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1B2A4A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle("A2:A{$lastRow}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE2E8F0']],
            'font' => ['color' => ['argb' => 'FF94A3B8']],
        ]);

        $sheet->getStyle("B2:D{$lastRow}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8FAFC']],
            'font' => ['color' => ['argb' => 'FF475569']],
        ]);

        $sheet->getStyle("E2:{$editEnd}{$lastRow}")->applyFromArray([
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                           'color'       => ['argb' => 'FFE2E8F0']]],
        ]);

        $sheet->getStyle("{$totalCol}2:{$totalCol}{$lastRow}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0FDF4']],
            'font' => ['color' => ['argb' => 'FF065F46'], 'bold' => true],
        ]);

        $totalFormula = $isMonthly
            ? fn(int $r) => "=E{$r}+F{$r}+G{$r}+H{$r}+I{$r}+J{$r}+K{$r}+L{$r}+M{$r}+N{$r}+O{$r}+P{$r}"
            : fn(int $r) => "=E{$r}+F{$r}+G{$r}+H{$r}";
        for ($row = 2; $row <= $lastRow; $row++) {
            $sheet->setCellValue("{$totalCol}{$row}", $totalFormula($row));
        }

        $sheet->getColumnDimension('A')->setVisible(false);

        // Insert instruction banner at row 1
        $sheet->insertNewRowBefore(1, 1);
        $instrText = $isMonthly
            ? 'GOIL BUDGET TOOL — Fill in Jan–Dec columns only. Do not edit grey columns. Upload this file when done.'
            : 'GOIL BUDGET TOOL — Fill in Q1–Q4 columns only. Do not edit grey columns. Upload this file when done.';
        $sheet->setCellValue('A1', $instrText);
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FF92400E']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFEF3C7']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $newLastRow = $sheet->getHighestRow();
        $sheet->getStyle("E3:{$totalCol}{$newLastRow}")
              ->getNumberFormat()->setFormatCode('#,##0.00');

        // ── Worksheet protection: lock grey and total columns ──
        // Unlock only the period-entry and justification columns; everything else stays locked.
        $sheet->getProtection()->setSheet(true);
        $pUnlocked = Protection::PROTECTION_UNPROTECTED;
        $sheet->getStyle("E3:{$editEnd}{$newLastRow}")->getProtection()->setLocked($pUnlocked);
        $sheet->getStyle("{$lastCol}3:{$lastCol}{$newLastRow}")->getProtection()->setLocked($pUnlocked);

        $sheet->freezePane('E3');
    }

    /**
     * Apply styles for Qty × Rate [× Freq] calc mode.
     *
     * Column layout:
     *   A=id(hidden)  B=Cat(grey)  C=Code(grey)  D=Name(grey)
     *   E=Qty(white)
     *   F=Rate (white if inputter-editable, purple if admin-locked)
     *   G=Freq (only for qty_rate_freq; same lock logic as Rate)
     *   G or H = Year Total (green, formula)
     *   H or I = Justification (white)
     */
    private function styleCalcMode(Worksheet $sheet): void
    {
        $hasFreq  = $this->calcMode === 'qty_rate_freq';

        // Column letters
        $rateCol  = 'F';
        $freqCol  = 'G';                         // only used when $hasFreq
        $totalCol = $hasFreq ? 'H' : 'G';
        $justCol  = $hasFreq ? 'I' : 'H';
        $lastCol  = $justCol;

        $lastRow = $sheet->getHighestRow();

        // ── Header row ──
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1B2A4A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // ── ID (hidden), Category, Code, Name — read-only look ──
        $sheet->getStyle("A2:A{$lastRow}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE2E8F0']],
            'font' => ['color' => ['argb' => 'FF94A3B8']],
        ]);
        $sheet->getStyle("B2:D{$lastRow}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8FAFC']],
            'font' => ['color' => ['argb' => 'FF475569']],
        ]);

        // ── Quantity — always editable ──
        $sheet->getStyle("E2:E{$lastRow}")->applyFromArray([
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                           'color'       => ['argb' => 'FFE2E8F0']]],
        ]);

        // ── Rate — editable or admin-locked (purple) ──
        if ($this->adminSetsRate) {
            $sheet->getStyle("{$rateCol}1")->getFont()->setBold(true);
            $sheet->getStyle("{$rateCol}2:{$rateCol}{$lastRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEDE9FE']],
                'font' => ['color' => ['argb' => 'FF5B21B6']],
            ]);
        } else {
            $sheet->getStyle("{$rateCol}2:{$rateCol}{$lastRow}")->applyFromArray([
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                               'color'       => ['argb' => 'FFE2E8F0']]],
            ]);
        }

        // ── Frequency (qty_rate_freq only) — editable or admin-locked ──
        if ($hasFreq) {
            if ($this->adminSetsFreq) {
                $sheet->getStyle("{$freqCol}1")->getFont()->setBold(true);
                $sheet->getStyle("{$freqCol}2:{$freqCol}{$lastRow}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEDE9FE']],
                    'font' => ['color' => ['argb' => 'FF5B21B6']],
                ]);
            } else {
                $sheet->getStyle("{$freqCol}2:{$freqCol}{$lastRow}")->applyFromArray([
                    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                                   'color'       => ['argb' => 'FFE2E8F0']]],
                ]);
            }
        }

        // ── Year Total — green, formula ──
        $sheet->getStyle("{$totalCol}2:{$totalCol}{$lastRow}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0FDF4']],
            'font' => ['color' => ['argb' => 'FF065F46'], 'bold' => true],
        ]);
        $totalFormula = $hasFreq
            ? fn(int $r) => "=E{$r}*F{$r}*G{$r}"
            : fn(int $r) => "=E{$r}*F{$r}";
        for ($row = 2; $row <= $lastRow; $row++) {
            $sheet->setCellValue("{$totalCol}{$row}", $totalFormula($row));
        }

        // ── Justification ──
        $sheet->getStyle("{$justCol}2:{$justCol}{$lastRow}")->applyFromArray([
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                           'color'       => ['argb' => 'FFE2E8F0']]],
        ]);

        // ── Format numbers ──
        $sheet->getStyle("E2:{$totalCol}{$lastRow}")
              ->getNumberFormat()->setFormatCode('#,##0.0000');

        $sheet->getColumnDimension('A')->setVisible(false);

        // ── Insert instruction banner at row 1 ──
        $sheet->insertNewRowBefore(1, 1);

        $parts = ['GOIL BUDGET TOOL'];
        if ($hasFreq) {
            $parts[] = 'Enter Quantity, Rate, and Frequency. Year Total = Qty × Rate × Freq.';
        } else {
            $parts[] = 'Enter Quantity and Rate. Year Total = Qty × Rate.';
        }
        if ($this->adminSetsRate) {
            $parts[] = 'Rate (purple) is admin-set — do not change it.';
        }
        if ($hasFreq && $this->adminSetsFreq) {
            $parts[] = 'Frequency (purple) is admin-set — do not change it.';
        }
        $parts[] = 'Do not edit grey columns or add/remove rows. Upload when done.';

        $sheet->setCellValue('A1', implode(' ', $parts));
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FF92400E']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFEF3C7']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                            'wrapText'   => true],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $newLastRow = $sheet->getHighestRow();
        $sheet->getStyle("E3:{$totalCol}{$newLastRow}")
              ->getNumberFormat()->setFormatCode('#,##0.0000');

        // ── Worksheet protection ──────────────────────────────────────────────
        // When sheet protection is on, ALL cells are locked by default.
        // We unlock only the columns the budget inputter is allowed to edit.
        $sheet->getProtection()->setSheet(true);
        $pUnlocked = Protection::PROTECTION_UNPROTECTED;

        // Quantity — always editable
        $sheet->getStyle("E3:E{$newLastRow}")->getProtection()->setLocked($pUnlocked);

        // Rate — editable only when the inputter is allowed to change it
        if (!$this->adminSetsRate) {
            $sheet->getStyle("{$rateCol}3:{$rateCol}{$newLastRow}")->getProtection()->setLocked($pUnlocked);
        }

        // Frequency — editable only when the inputter is allowed to change it
        if ($hasFreq && !$this->adminSetsFreq) {
            $sheet->getStyle("{$freqCol}3:{$freqCol}{$newLastRow}")->getProtection()->setLocked($pUnlocked);
        }

        // Justification — always editable
        $sheet->getStyle("{$justCol}3:{$justCol}{$newLastRow}")->getProtection()->setLocked($pUnlocked);

        $sheet->freezePane('E3');
    }
}

// ─────────────────────────────────────────────────────────────────────────────

class BudgetInstructionsSheet implements WithTitle
{
    protected string $entryMode;
    protected string $calcMode;
    protected bool   $adminSetsRate;
    protected bool   $adminSetsFreq;

    public function __construct(BudgetVersion $version)
    {
        $period              = $version->period;
        $this->entryMode     = $period->entry_mode ?? 'quarterly';
        $this->calcMode      = $period->calcMode();
        $this->adminSetsRate = $period->adminSetsRate();
        $this->adminSetsFreq = $period->adminSetsFreq();
    }

    public function title(): string { return 'Instructions'; }

    public function __invoke(Worksheet $sheet): void
    {
        $hasFreq = $this->calcMode === 'qty_rate_freq';

        if ($this->calcMode !== 'none') {
            // ── Calc mode instructions ──
            $editableFields = 'Quantity';
            if (!$this->adminSetsRate) $editableFields .= ', Rate';
            if ($hasFreq && !$this->adminSetsFreq) $editableFields .= ', Frequency';

            $formula = $hasFreq ? 'Qty × Rate × Frequency' : 'Qty × Rate';

            $instructions = [
                ['GOIL Budget Tool — Upload Instructions', ''],
                ['', ''],
                ['Step', 'Instruction'],
                ['1',   'Go to the "Budget Entry" sheet'],
                ['2',   "Fill in the {$editableFields} column(s) for each account code"],
                ['3',   "Year Total is calculated automatically as {$formula} — do not edit it"],
                ['4',   'Add justification notes in the Justification column (optional)'],
                ['5',   'Do NOT edit grey columns (Category, Code, Name)'],
                ['6',   'Do NOT add or remove rows'],
                ['7',   'Save the file and upload it on the budget entry page'],
                ['', ''],
                ['Column', 'Description'],
                ['Quantity',    'Number of units / occurrences'],
                ['Rate',        $this->adminSetsRate
                                    ? 'Admin-set unit rate (purple) — do not change'
                                    : 'Unit cost or rate (enter your value)'],
            ];

            if ($hasFreq) {
                $instructions[] = ['Frequency', $this->adminSetsFreq
                    ? 'Admin-set frequency (purple) — do not change'
                    : 'How many times per year (e.g. 12 = monthly, 4 = quarterly)'];
            }

            $instructions = array_merge($instructions, [
                ['Year Total',    "Calculated: {$formula}"],
                ['', ''],
                ['Notes', ''],
                ['•', 'All amounts must be in Ghana Cedis (GHS)'],
                ['•', 'Negative values are not allowed'],
                ['•', 'Purple columns are admin-controlled — values are read-only'],
                ['•', 'The system will validate all data before saving'],
            ]);

        } else {
            // ── Direct-entry (monthly/quarterly) instructions ──
            $colLabel = $this->entryMode === 'monthly' ? 'Jan, Feb, ..., Dec' : 'Q1, Q2, Q3, Q4';

            $instructions = [
                ['GOIL Budget Tool — Upload Instructions', ''],
                ['', ''],
                ['Step', 'Instruction'],
                ['1', 'Go to the "Budget Entry" sheet'],
                ['2', "Fill in {$colLabel} amounts for each account code"],
                ['3', 'The Total column calculates automatically — do not edit it'],
                ['4', 'Add justification notes in the last column (optional)'],
                ['5', 'Do NOT edit the grey columns (Category, Code, Name)'],
                ['6', 'Do NOT add or remove rows'],
                ['7', 'Save the file and upload it back on the budget entry page'],
                ['', ''],
                ['Notes', ''],
                ['•', 'All amounts must be in Ghana Cedis (GHS)'],
                ['•', 'Negative values are not allowed'],
                ['•', 'The system will validate all data before saving'],
            ];
        }

        foreach ($instructions as $rowIdx => $row) {
            $sheet->fromArray([$row], null, 'A'.($rowIdx + 1));
        }

        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14,
                       'color' => ['argb' => 'FF1B2A4A']],
        ]);

        $stepHeader = 3; // row 3 is the "Step / Instruction" header in both calc and direct-entry modes
        $sheet->getStyle("A{$stepHeader}:B{$stepHeader}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID,
                       'startColor' => ['argb' => 'FF1B2A4A']],
        ]);

        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(70);
    }
}
