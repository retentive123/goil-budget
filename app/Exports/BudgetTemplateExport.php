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

class BudgetTemplateExport implements WithMultipleSheets
{
    public function __construct(
        protected BudgetVersion $version
    ) {}

    public function sheets(): array
    {
        $mode = $this->version->period->entry_mode ?? 'quarterly';
        return [
            new BudgetDataSheet($this->version),
            new BudgetInstructionsSheet($mode),
        ];
    }
}

class BudgetDataSheet implements
    FromCollection, WithHeadings, WithTitle,
    WithStyles, ShouldAutoSize
{
    protected string $mode;

    public function __construct(protected BudgetVersion $version)
    {
        $this->mode = $version->period->entry_mode ?? 'quarterly';
    }

    public function title(): string { return 'Budget Entry'; }

    public function headings(): array
    {
        if ($this->mode === 'monthly') {
            return [
                'line_item_id', 'Category', 'Account Code', 'Account Name',
                'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
                'Total', 'Justification',
            ];
        }
        return [
            'line_item_id', 'Category', 'Account Code', 'Account Name',
            'Q1 (Jan-Mar)', 'Q2 (Apr-Jun)', 'Q3 (Jul-Sep)', 'Q4 (Oct-Dec)',
            'Total', 'Justification',
        ];
    }

    public function collection()
    {
        $items = BudgetLineItem::where('budget_version_id', $this->version->id)
            ->with('accountCode.category')
            ->get();

        if ($this->mode === 'monthly') {
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

        return $items->map(fn($item) => [
            $item->id,
            $item->accountCode->category->name,
            $item->accountCode->code,
            $item->accountCode->name,
            $item->q1_amount,
            $item->q2_amount,
            $item->q3_amount,
            $item->q4_amount,
            $item->total_amount,
            $item->justification,
        ]);
    }

    public function styles(Worksheet $sheet): void
    {
        $isMonthly = $this->mode === 'monthly';
        $lastRow   = $sheet->getHighestRow();

        // Quarterly: A=id B=cat C=code D=name E-H=Q1-Q4   I=Total J=Justification
        // Monthly:   A=id B=cat C=code D=name E-P=Jan-Dec Q=Total R=Justification
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

        $sheet->freezePane('E3');
    }
}

class BudgetInstructionsSheet implements WithTitle
{
    public function __construct(protected string $mode = 'quarterly') {}

    public function title(): string { return 'Instructions'; }

    public function __invoke(Worksheet $sheet): void
    {
        $colLabel = $this->mode === 'monthly' ? 'Jan, Feb, ..., Dec' : 'Q1, Q2, Q3, Q4';

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

        foreach ($instructions as $rowIdx => $row) {
            $sheet->fromArray([$row], null, 'A'.($rowIdx + 1));
        }

        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14,
                       'color' => ['argb' => 'FF1B2A4A']],
        ]);

        $sheet->getStyle('A3:B3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID,
                       'startColor' => ['argb' => 'FF1B2A4A']],
        ]);

        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(70);
    }
}
