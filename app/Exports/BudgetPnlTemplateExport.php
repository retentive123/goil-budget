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
        $mode = $this->version->period->entry_mode ?? 'quarterly';
        return [
            new BudgetPnlDataSheet($this->version),
            new BudgetPnlInstructionsSheet($mode),
        ];
    }
}

class BudgetPnlDataSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    private array  $rowTypes = [];
    private string $mode;
    private string $lastCol;
    private string $totalCol;

    public function __construct(protected BudgetVersion $version)
    {
        $this->mode     = $version->period->entry_mode ?? 'quarterly';
        // Quarterly: A=id B=type C=cat D=code E=name F-I=Q1-Q4 J=Total K=Justification (11 cols)
        // Monthly:   A=id B=type C=cat D=code E=name F-Q=Jan-Dec R=Total S=Justification (19 cols)
        $this->totalCol = $this->mode === 'monthly' ? 'R' : 'J';
        $this->lastCol  = $this->mode === 'monthly' ? 'S' : 'K';
    }

    public function title(): string { return 'Budget Entry (P&L)'; }

    public function headings(): array
    {
        $periodCols = $this->mode === 'monthly'
            ? ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
            : ['Q1 (Jan-Mar)', 'Q2 (Apr-Jun)', 'Q3 (Jul-Sep)', 'Q4 (Oct-Dec)'];

        return array_merge(
            ['line_item_id', 'Type', 'Category', 'Account Code', 'Account Name'],
            $periodCols,
            ['Total', 'Justification']
        );
    }

    public function collection(): Collection
    {
        $items = BudgetLineItem::where('budget_version_id', $this->version->id)
            ->with('accountCode.category')
            ->get();

        $isMonthly   = $this->mode === 'monthly';
        $totalCols   = $isMonthly ? 19 : 11;
        $emptyPeriod = array_fill(0, $isMonthly ? 12 : 4, '');

        $sections        = ['revenue' => [], 'expense' => []];
        $capexSections   = [];
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
            $rows->push(array_pad([null, "=== {$label} ==="], $totalCols, ''));
            $this->rowTypes[] = "section_{$type}";

            foreach ($sections[$type] as $catName => $catItems) {
                $rows->push(array_pad([null, '', $catName], $totalCols, ''));
                $this->rowTypes[] = 'category';

                $catPeriod = array_fill(0, $isMonthly ? 12 : 4, 0.0);
                foreach ($catItems as $item) {
                    $itemPeriod = $this->itemPeriodCols($item, $isMonthly);
                    $rows->push(array_merge(
                        [$item->id, ucfirst($type), $item->accountCode->category->name,
                         $item->accountCode->code, $item->accountCode->name],
                        $itemPeriod,
                        [$item->total_amount, $item->justification]
                    ));
                    $this->rowTypes[] = 'item';
                    foreach ($itemPeriod as $k => $v) { $catPeriod[$k] += $v; }
                }
                $rows->push(array_merge(
                    [null, '', $catName . ' SUBTOTAL', '', ''],
                    $catPeriod,
                    [array_sum($catPeriod), '']
                ));
                $this->rowTypes[] = 'subtotal';
            }

            $secItems  = collect(array_merge(...(array_values($sections[$type]) ?: [[]])));
            $secPeriod = $this->sumPeriodCols($secItems, $isMonthly);
            $rows->push(array_merge(
                [null, "{$label} TOTAL", '', '', ''],
                $secPeriod,
                [$secItems->sum('total_amount'), '']
            ));
            $this->rowTypes[] = 'section_total';

            $rows->push(array_pad([], $totalCols, ''));
            $this->rowTypes[] = 'spacer';
        }

        // ── Capital Expenditure ──
        if (!empty($capexSections)) {
            $rows->push(array_pad([null, '=== CAPITAL EXPENDITURE ==='], $totalCols, ''));
            $this->rowTypes[] = 'section_capex';

            foreach ($capexSections as $catName => $catItems) {
                $rows->push(array_pad([null, '', $catName], $totalCols, ''));
                $this->rowTypes[] = 'category';

                $catPeriod = array_fill(0, $isMonthly ? 12 : 4, 0.0);
                foreach ($catItems as $item) {
                    $itemPeriod = $this->itemPeriodCols($item, $isMonthly);
                    $rows->push(array_merge(
                        [$item->id, 'CapEx', $item->accountCode->category->name,
                         $item->accountCode->code, $item->accountCode->name],
                        $itemPeriod,
                        [$item->total_amount, $item->justification]
                    ));
                    $this->rowTypes[] = 'item';
                    foreach ($itemPeriod as $k => $v) { $catPeriod[$k] += $v; }
                }
                $rows->push(array_merge(
                    [null, '', $catName . ' SUBTOTAL', '', ''],
                    $catPeriod,
                    [array_sum($catPeriod), '']
                ));
                $this->rowTypes[] = 'subtotal';
            }

            $cxAll    = collect(array_merge(...array_values($capexSections)));
            $cxPeriod = $this->sumPeriodCols($cxAll, $isMonthly);
            $rows->push(array_merge(
                [null, 'CAPITAL EXPENDITURE TOTAL', '', '', ''],
                $cxPeriod,
                [$cxAll->sum('total_amount'), '']
            ));
            $this->rowTypes[] = 'section_total';

            $rows->push(array_pad([], $totalCols, ''));
            $this->rowTypes[] = 'spacer';
        }

        // ── Assets & Liabilities ──
        if (!empty($balanceSections)) {
            $rows->push(array_pad([null, '=== ASSETS & LIABILITIES ==='], $totalCols, ''));
            $this->rowTypes[] = 'section_balance';

            foreach ($balanceSections as $catName => $catItems) {
                $rows->push(array_pad([null, '', $catName], $totalCols, ''));
                $this->rowTypes[] = 'category';

                $catPeriod = array_fill(0, $isMonthly ? 12 : 4, 0.0);
                foreach ($catItems as $item) {
                    $itemPeriod = $this->itemPeriodCols($item, $isMonthly);
                    $rows->push(array_merge(
                        [$item->id, ucfirst($item->accountCode->category->budget_type),
                         $item->accountCode->category->name,
                         $item->accountCode->code, $item->accountCode->name],
                        $itemPeriod,
                        [$item->total_amount, $item->justification]
                    ));
                    $this->rowTypes[] = 'item';
                    foreach ($itemPeriod as $k => $v) { $catPeriod[$k] += $v; }
                }
                $rows->push(array_merge(
                    [null, '', $catName . ' SUBTOTAL', '', ''],
                    $catPeriod,
                    [array_sum($catPeriod), '']
                ));
                $this->rowTypes[] = 'subtotal';
            }

            $blAll    = collect(array_merge(...array_values($balanceSections)));
            $blPeriod = $this->sumPeriodCols($blAll, $isMonthly);
            $rows->push(array_merge(
                [null, 'ASSETS & LIABILITIES TOTAL', '', '', ''],
                $blPeriod,
                [$blAll->sum('total_amount'), '']
            ));
            $this->rowTypes[] = 'section_total';
        }

        return $rows;
    }

    private function itemPeriodCols($item, bool $isMonthly): array
    {
        if ($isMonthly) {
            return array_map(fn($m) => (float) $item->{"m{$m}_amount"}, range(1, 12));
        }
        return [(float) $item->q1_amount, (float) $item->q2_amount,
                (float) $item->q3_amount, (float) $item->q4_amount];
    }

    private function sumPeriodCols($cc, bool $isMonthly): array
    {
        if ($isMonthly) {
            return array_map(fn($m) => (float) $cc->sum("m{$m}_amount"), range(1, 12));
        }
        return [(float) $cc->sum('q1_amount'), (float) $cc->sum('q2_amount'),
                (float) $cc->sum('q3_amount'), (float) $cc->sum('q4_amount')];
    }

    public function styles(Worksheet $sheet): void
    {
        $isMonthly = $this->mode === 'monthly';
        $lc        = $this->lastCol;
        $tc        = $this->totalCol;
        $editEnd   = $isMonthly ? 'Q' : 'I';
        $lastRow   = $sheet->getHighestRow();

        $sheet->getStyle("A1:{$lc}1")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1B2A4A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->insertNewRowBefore(1, 1);
        $instrText = $isMonthly
            ? 'GOIL BUDGET TOOL (P&L FORMAT) — Fill in Jan–Dec columns only. Do not edit grey columns. Upload this file when done.'
            : 'GOIL BUDGET TOOL (P&L FORMAT) — Fill in Q1–Q4 columns only. Do not edit grey columns. Upload this file when done.';
        $sheet->setCellValue('A1', $instrText);
        $sheet->mergeCells("A1:{$lc}1");
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FF92400E']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFEF3C7']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sectionFills = [
            'section_revenue' => 'FF1B2A4A',
            'section_expense' => 'FF7C2D12',
            'section_capex'   => 'FF78350F',
            'section_balance' => 'FF4C1D95',
        ];

        $dataStart = 3;
        foreach ($this->rowTypes as $i => $rowType) {
            $r = $dataStart + $i;

            if (isset($sectionFills[$rowType])) {
                $sheet->getStyle("A{$r}:{$lc}{$r}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $sectionFills[$rowType]]],
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
                ]);
                $sheet->mergeCells("A{$r}:{$lc}{$r}");

            } elseif ($rowType === 'category') {
                $sheet->getStyle("A{$r}:{$lc}{$r}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE8F0FE']],
                    'font' => ['bold' => true, 'color' => ['argb' => 'FF1E3A5F']],
                ]);

            } elseif ($rowType === 'item') {
                $sheet->getStyle("A{$r}:E{$r}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8FAFC']],
                    'font' => ['color' => ['argb' => 'FF475569']],
                ]);
                $sheet->getStyle("F{$r}:{$editEnd}{$r}")->applyFromArray([
                    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']]],
                ]);
                $totalFormula = $isMonthly
                    ? "=F{$r}+G{$r}+H{$r}+I{$r}+J{$r}+K{$r}+L{$r}+M{$r}+N{$r}+O{$r}+P{$r}+Q{$r}"
                    : "=F{$r}+G{$r}+H{$r}+I{$r}";
                $sheet->setCellValue("{$tc}{$r}", $totalFormula);
                $sheet->getStyle("{$tc}{$r}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0FDF4']],
                    'font' => ['color' => ['argb' => 'FF065F46'], 'bold' => true],
                ]);

            } elseif ($rowType === 'subtotal') {
                $sheet->getStyle("A{$r}:{$lc}{$r}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD1FAE5']],
                    'font' => ['bold' => true, 'color' => ['argb' => 'FF065F46']],
                ]);

            } elseif ($rowType === 'section_total') {
                $sheet->getStyle("A{$r}:{$lc}{$r}")->applyFromArray([
                    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0F172A']],
                    'font'    => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FFFFFFFF']]],
                ]);
            }
        }

        $newLastRow = $sheet->getHighestRow();
        $sheet->getStyle("F3:{$tc}{$newLastRow}")
            ->getNumberFormat()->setFormatCode('#,##0.00');

        $sheet->getColumnDimension('A')->setVisible(false);
        $sheet->freezePane('F3');
    }
}

class BudgetPnlInstructionsSheet implements WithTitle
{
    public function __construct(protected string $mode = 'quarterly') {}

    public function title(): string { return 'Instructions'; }

    public function __invoke(Worksheet $sheet): void
    {
        $colLabel = $this->mode === 'monthly' ? 'Jan–Dec' : 'Q1–Q4';

        $instructions = [
            ['GOIL Budget Tool (P&L Format) — Upload Instructions', ''],
            ['', ''],
            ['Step', 'Instruction'],
            ['1', 'Go to the "Budget Entry (P&L)" sheet'],
            ["2", "Fill in {$colLabel} amounts for each account code row (white cells)"],
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
