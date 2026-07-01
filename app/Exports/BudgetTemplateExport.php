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
        return [
            new BudgetDataSheet($this->version),
            new BudgetInstructionsSheet(),
        ];
    }
}

class BudgetDataSheet implements
    FromCollection, WithHeadings, WithTitle,
    WithStyles, ShouldAutoSize
{
    public function __construct(protected BudgetVersion $version) {}

    public function title(): string { return 'Budget Entry'; }

    public function collection()
    {
        $items = BudgetLineItem::where('budget_version_id', $this->version->id)
            ->with('accountCode.category')
            ->get();

        return $items->map(fn($item) => [
            $item->id,                                 // A — hidden ID
            $item->accountCode->category->name,        // B
            $item->accountCode->code,                  // C
            $item->accountCode->name,                  // D
            $item->q1_amount,                          // E
            $item->q2_amount,                          // F
            $item->q3_amount,                          // G
            $item->q4_amount,                          // H
            $item->total_amount,                       // I — formula
            $item->justification,                      // J
        ]);
    }

    public function headings(): array
    {
        return [
            'line_item_id',    // A — do not edit
            'Category',        // B
            'Account Code',    // C
            'Account Name',    // D
            'Q1',    // E
            'Q2',    // F
            'Q3',    // G
            'Q4',    // H
            'Total',           // I
            'Justification',   // J
        ];
    }

    public function styles(Worksheet $sheet): void
    {
        $lastRow = $sheet->getHighestRow();

        // Header row styling
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID,
                       'startColor' => ['argb' => 'FF1B2A4A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Lock column A (IDs) — gray it out
        $sheet->getStyle('A2:A'.$lastRow)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID,
                       'startColor' => ['argb' => 'FFE2E8F0']],
            'font' => ['color' => ['argb' => 'FF94A3B8']],
        ]);

        // Lock columns B, C, D (read-only info) — light gray
        $sheet->getStyle('B2:D'.$lastRow)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID,
                       'startColor' => ['argb' => 'FFF8FAFC']],
            'font' => ['color' => ['argb' => 'FF475569']],
        ]);

        // Editable Q columns — white with light border
        $sheet->getStyle('E2:H'.$lastRow)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID,
                       'startColor' => ['argb' => 'FFFFFFFF']],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                 'color' => ['argb' => 'FFE2E8F0']],
            ],
        ]);

        // Total column — formula color
        $sheet->getStyle('I2:I'.$lastRow)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID,
                       'startColor' => ['argb' => 'FFF0FDF4']],
            'font' => ['color' => ['argb' => 'FF065F46'], 'bold' => true],
        ]);

        // Set total column formulas
        for ($row = 2; $row <= $lastRow; $row++) {
            $sheet->setCellValue("I{$row}", "=E{$row}+F{$row}+G{$row}+H{$row}");
        }

        // Hide column A
        $sheet->getColumnDimension('A')->setVisible(false);

        // Instruction row above header
        $sheet->insertNewRowBefore(1, 1);
        $sheet->setCellValue('A1',
            'GOIL BUDGET TOOL — Fill in Q1–Q4 columns only. Do not edit grey columns. Upload this file when done.'
        );
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FF92400E']],
            'fill' => ['fillType' => Fill::FILL_SOLID,
                       'startColor' => ['argb' => 'FFFEF3C7']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Number format for Q columns
        $sheet->getStyle('E3:I'.$lastRow)
              ->getNumberFormat()
              ->setFormatCode('#,##0.00');

        // Freeze panes — lock header
        $sheet->freezePane('E3');
    }
}

class BudgetInstructionsSheet implements WithTitle
{
    public function title(): string { return 'Instructions'; }

    public function __invoke(Worksheet $sheet): void
    {
        $instructions = [
            ['GOIL Budget Tool — Upload Instructions', ''],
            ['', ''],
            ['Step', 'Instruction'],
            ['1', 'Go to the "Budget Entry" sheet'],
            ['2', 'Fill in Q1, Q2, Q3, Q4 amounts for each account code'],
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
