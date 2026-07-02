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
use Illuminate\Support\Collection;

class BudgetPnlTemplateExport implements WithMultipleSheets
{
    public function __construct(protected BudgetVersion $version) {}

    public function sheets(): array
    {
        return [
            new BudgetPnlDataSheet($this->version),
            new BudgetPnlInstructionsSheet(),
        ];
    }
}

class BudgetPnlDataSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    private array $rowTypes = [];

    public function __construct(protected BudgetVersion $version) {}

    public function title(): string { return 'Budget Entry (P&L)'; }

    public function headings(): array
    {
        return [
            'line_item_id', 'Type', 'Category', 'Account Code', 'Account Name',
            'Q1', 'Q2', 'Q3', 'Q4', 'Total', 'Justification',
        ];
    }

    public function collection(): Collection
    {
        $items = BudgetLineItem::where('budget_version_id', $this->version->id)
            ->with('accountCode.category')
            ->get();

        $sections = ['revenue' => [], 'expense' => []];
        $capexSections  = [];
        $balanceSections = [];

        foreach ($items as $item) {
            $cat     = $item->accountCode->category;
            $catName = $cat->name;

            if (in_array($cat->budget_type, ['revenue', 'both'])) {
                $sections['revenue'][$catName][] = $item;
            } elseif ($cat->budget_type === 'capital_expenditure') {
                $capexSections[$catName][] = $item;
            } elseif (in_array($cat->budget_type, ['assets', 'liabilities'])) {
                $balanceSections[$catName][] = $item;
            } else {
                $sections['expense'][$catName][] = $item;
            }
        }

        $rows = collect();
        $this->rowTypes = [];

        // ── Revenue & Expense ──
        foreach (['revenue' => 'REVENUE', 'expense' => 'EXPENSE'] as $type => $label) {
            $rows->push([null, "=== {$label} ===", '', '', '', '', '', '', '', '', '']);
            $this->rowTypes[] = "section_{$type}";

            foreach ($sections[$type] as $catName => $catItems) {
                $rows->push([null, '', $catName, '', '', '', '', '', '', '', '']);
                $this->rowTypes[] = 'category';

                $catQ1 = $catQ2 = $catQ3 = $catQ4 = 0;
                foreach ($catItems as $item) {
                    $rows->push([
                        $item->id, ucfirst($type),
                        $item->accountCode->category->name,
                        $item->accountCode->code, $item->accountCode->name,
                        $item->q1_amount, $item->q2_amount, $item->q3_amount, $item->q4_amount,
                        $item->total_amount, $item->justification,
                    ]);
                    $this->rowTypes[] = 'item';
                    $catQ1 += $item->q1_amount; $catQ2 += $item->q2_amount;
                    $catQ3 += $item->q3_amount; $catQ4 += $item->q4_amount;
                }
                $rows->push([null, '', $catName . ' SUBTOTAL', '', '', $catQ1, $catQ2, $catQ3, $catQ4, $catQ1+$catQ2+$catQ3+$catQ4, '']);
                $this->rowTypes[] = 'subtotal';
            }

            $secItems = collect(array_merge(...(array_values($sections[$type]) ?: [[]])));
            $rows->push([null, "{$label} TOTAL", '', '', '',
                $secItems->sum('q1_amount'), $secItems->sum('q2_amount'),
                $secItems->sum('q3_amount'), $secItems->sum('q4_amount'),
                $secItems->sum('total_amount'), '']);
            $this->rowTypes[] = 'section_total';

            $rows->push([null, '', '', '', '', '', '', '', '', '', '']);
            $this->rowTypes[] = 'spacer';
        }

        // ── Capital Expenditure ──
        if (!empty($capexSections)) {
            $rows->push([null, '=== CAPITAL EXPENDITURE ===', '', '', '', '', '', '', '', '', '']);
            $this->rowTypes[] = 'section_capex';

            foreach ($capexSections as $catName => $catItems) {
                $rows->push([null, '', $catName, '', '', '', '', '', '', '', '']);
                $this->rowTypes[] = 'category';

                $catQ1 = $catQ2 = $catQ3 = $catQ4 = 0;
                foreach ($catItems as $item) {
                    $rows->push([
                        $item->id, 'CapEx',
                        $item->accountCode->category->name,
                        $item->accountCode->code, $item->accountCode->name,
                        $item->q1_amount, $item->q2_amount, $item->q3_amount, $item->q4_amount,
                        $item->total_amount, $item->justification,
                    ]);
                    $this->rowTypes[] = 'item';
                    $catQ1 += $item->q1_amount; $catQ2 += $item->q2_amount;
                    $catQ3 += $item->q3_amount; $catQ4 += $item->q4_amount;
                }
                $rows->push([null, '', $catName . ' SUBTOTAL', '', '', $catQ1, $catQ2, $catQ3, $catQ4, $catQ1+$catQ2+$catQ3+$catQ4, '']);
                $this->rowTypes[] = 'subtotal';
            }

            $cxAll = collect(array_merge(...array_values($capexSections)));
            $rows->push([null, 'CAPITAL EXPENDITURE TOTAL', '', '', '',
                $cxAll->sum('q1_amount'), $cxAll->sum('q2_amount'),
                $cxAll->sum('q3_amount'), $cxAll->sum('q4_amount'),
                $cxAll->sum('total_amount'), '']);
            $this->rowTypes[] = 'section_total';

            $rows->push([null, '', '', '', '', '', '', '', '', '', '']);
            $this->rowTypes[] = 'spacer';
        }

        // ── Assets & Liabilities ──
        if (!empty($balanceSections)) {
            $rows->push([null, '=== ASSETS & LIABILITIES ===', '', '', '', '', '', '', '', '', '']);
            $this->rowTypes[] = 'section_balance';

            foreach ($balanceSections as $catName => $catItems) {
                $rows->push([null, '', $catName, '', '', '', '', '', '', '', '']);
                $this->rowTypes[] = 'category';

                $catQ1 = $catQ2 = $catQ3 = $catQ4 = 0;
                foreach ($catItems as $item) {
                    $rows->push([
                        $item->id, ucfirst($item->accountCode->category->budget_type),
                        $item->accountCode->category->name,
                        $item->accountCode->code, $item->accountCode->name,
                        $item->q1_amount, $item->q2_amount, $item->q3_amount, $item->q4_amount,
                        $item->total_amount, $item->justification,
                    ]);
                    $this->rowTypes[] = 'item';
                    $catQ1 += $item->q1_amount; $catQ2 += $item->q2_amount;
                    $catQ3 += $item->q3_amount; $catQ4 += $item->q4_amount;
                }
                $rows->push([null, '', $catName . ' SUBTOTAL', '', '', $catQ1, $catQ2, $catQ3, $catQ4, $catQ1+$catQ2+$catQ3+$catQ4, '']);
                $this->rowTypes[] = 'subtotal';
            }

            $blAll = collect(array_merge(...array_values($balanceSections)));
            $rows->push([null, 'ASSETS & LIABILITIES TOTAL', '', '', '',
                $blAll->sum('q1_amount'), $blAll->sum('q2_amount'),
                $blAll->sum('q3_amount'), $blAll->sum('q4_amount'),
                $blAll->sum('total_amount'), '']);
            $this->rowTypes[] = 'section_total';
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): void
    {
        $lastRow = $sheet->getHighestRow();

        // Heading row styling
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1B2A4A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Insert instruction row above heading
        $sheet->insertNewRowBefore(1, 1);
        $sheet->setCellValue('A1',
            'GOIL BUDGET TOOL (P&L FORMAT) — Fill in Q1–Q4 columns only. Do not edit grey columns. Upload this file when done.'
        );
        $sheet->mergeCells('A1:K1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FF92400E']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFEF3C7']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // data rows start at row 3 (row 1=instruction, row 2=heading)
        $dataStart = 3;

        foreach ($this->rowTypes as $i => $rowType) {
            $rowNum = $dataStart + $i;

            if ($rowType === 'section_revenue') {
                $sheet->getStyle("A{$rowNum}:K{$rowNum}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1B2A4A']],
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
                ]);
                $sheet->mergeCells("A{$rowNum}:K{$rowNum}");

            } elseif ($rowType === 'section_expense') {
                $sheet->getStyle("A{$rowNum}:K{$rowNum}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF7C2D12']],
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
                ]);
                $sheet->mergeCells("A{$rowNum}:K{$rowNum}");

            } elseif ($rowType === 'section_capex') {
                $sheet->getStyle("A{$rowNum}:K{$rowNum}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF78350F']],
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
                ]);
                $sheet->mergeCells("A{$rowNum}:K{$rowNum}");

            } elseif ($rowType === 'section_balance') {
                $sheet->getStyle("A{$rowNum}:K{$rowNum}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4C1D95']],
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
                ]);
                $sheet->mergeCells("A{$rowNum}:K{$rowNum}");

            } elseif ($rowType === 'category') {
                $sheet->getStyle("A{$rowNum}:K{$rowNum}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE8F0FE']],
                    'font' => ['bold' => true, 'color' => ['argb' => 'FF1E3A5F']],
                ]);

            } elseif ($rowType === 'item') {
                // Lock ID, Type, Category, Code, Name
                $sheet->getStyle("A{$rowNum}:E{$rowNum}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8FAFC']],
                    'font' => ['color' => ['argb' => 'FF475569']],
                ]);
                // Editable Q columns
                $sheet->getStyle("F{$rowNum}:I{$rowNum}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']]],
                ]);
                // Total formula
                $sheet->setCellValue("J{$rowNum}", "=F{$rowNum}+G{$rowNum}+H{$rowNum}+I{$rowNum}");
                $sheet->getStyle("J{$rowNum}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0FDF4']],
                    'font' => ['color' => ['argb' => 'FF065F46'], 'bold' => true],
                ]);

            } elseif ($rowType === 'subtotal') {
                $sheet->getStyle("A{$rowNum}:K{$rowNum}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD1FAE5']],
                    'font' => ['bold' => true, 'color' => ['argb' => 'FF065F46']],
                ]);

            } elseif ($rowType === 'section_total') {
                $sheet->getStyle("A{$rowNum}:K{$rowNum}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0F172A']],
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FFFFFFFF']]],
                ]);
            }
        }

        // Number format for Q, Total columns
        $newLastRow = $sheet->getHighestRow();
        $sheet->getStyle("F3:J{$newLastRow}")
            ->getNumberFormat()->setFormatCode('#,##0.00');

        // Hide column A (line_item_id)
        $sheet->getColumnDimension('A')->setVisible(false);

        // Freeze pane
        $sheet->freezePane('F3');
    }
}

class BudgetPnlInstructionsSheet implements WithTitle
{
    public function title(): string { return 'Instructions'; }

    public function __invoke(Worksheet $sheet): void
    {
        $instructions = [
            ['GOIL Budget Tool (P&L Format) — Upload Instructions', ''],
            ['', ''],
            ['Step', 'Instruction'],
            ['1', 'Go to the "Budget Entry (P&L)" sheet'],
            ['2', 'Fill in Q1–Q4 amounts for each account code row (white cells)'],
            ['3', 'Section headers and subtotal rows are read-only — do not edit them'],
            ['4', 'The Total column calculates automatically'],
            ['5', 'Add notes in the Justification column (optional)'],
            ['6', 'Do NOT edit grey/coloured columns or add/remove rows'],
            ['7', 'Save the file and upload it on the P&L budget entry page'],
            ['', ''],
            ['Notes', ''],
            ['•', 'Both P&L and Classic templates upload to the same budget'],
            ['•', 'All amounts must be in Ghana Cedis (GHS)'],
            ['•', 'Negative values are not allowed'],
        ];

        foreach ($instructions as $i => $row) {
            $sheet->fromArray([$row], null, 'A' . ($i + 1));
        }

        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF1B2A4A']],
        ]);
        $sheet->getStyle('A3:B3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1B2A4A']],
        ]);
        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(70);
    }
}
