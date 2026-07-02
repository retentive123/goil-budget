<?php

namespace App\Exports;

use App\Models\BudgetVersion;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Collection;

class BudgetReadOnlyExport implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    private array $rowTypes = [];

    public function __construct(
        protected BudgetVersion $version,
        protected Collection $actualsPerItem
    ) {}

    public function title(): string { return 'Budget'; }

    public function headings(): array
    {
        return [
            'Category', 'Code', 'Account Name', 'Type',
            'Q1', 'Q2', 'Q3', 'Q4',
            'Original Total', 'Supplementary', 'Effective Total', 'Actual (YTD)',
        ];
    }

    public function collection(): Collection
    {
        $items = $this->version->lineItems;

        // Group into sections preserving category order
        $sections = ['revenue' => [], 'expense' => [], 'assets' => [], 'liabilities' => [], 'capital_expenditure' => []];
        foreach ($items as $item) {
            $type    = match($item->accountCode->category->budget_type) {
                'revenue'             => 'revenue',
                'both'                => 'revenue',
                'assets'              => 'assets',
                'liabilities'         => 'liabilities',
                'capital_expenditure' => 'capital_expenditure',
                default               => 'expense',
            };
            $catName = $item->accountCode->category->name;
            $sections[$type][$catName][] = $item;
        }

        $sectionLabels = [
            'revenue'             => 'REVENUE',
            'expense'             => 'EXPENSES',
            'assets'              => 'ASSETS',
            'liabilities'         => 'LIABILITIES',
            'capital_expenditure' => 'CAPITAL EXPENDITURE',
        ];

        $rows = collect();
        $this->rowTypes = [];
        $grandQ1 = $grandQ2 = $grandQ3 = $grandQ4 = 0.0;
        $grandOrig = $grandSupp = $grandEff = $grandActual = 0.0;

        foreach ($sections as $sectionType => $categories) {
            if (empty($categories)) continue;

            $sectionLabel = $sectionLabels[$sectionType];
            $rows->push(array_pad([$sectionLabel], 12, ''));
            $this->rowTypes[] = "section_{$sectionType}";

            foreach ($categories as $catName => $catItems) {
                $cc = collect($catItems);
                $catQ1     = (float) $cc->sum('q1_amount');
                $catQ2     = (float) $cc->sum('q2_amount');
                $catQ3     = (float) $cc->sum('q3_amount');
                $catQ4     = (float) $cc->sum('q4_amount');
                $catOrig   = (float) $cc->sum('total_amount');
                $catSupp   = (float) $cc->sum(fn($i) => $i->approvedSupplementaryTotal());
                $catEff    = (float) $cc->sum(fn($i) => $i->effectiveBudget());
                $catActual = (float) $cc->sum(fn($i) => $this->actualsPerItem->get($i->id, 0));

                $rows->push([$catName, '', '', '', $catQ1, $catQ2, $catQ3, $catQ4, $catOrig, $catSupp, $catEff, $catActual]);
                $this->rowTypes[] = 'category';

                foreach ($catItems as $item) {
                    $supp   = (float) $item->approvedSupplementaryTotal();
                    $eff    = (float) $item->effectiveBudget();
                    $actual = (float) $this->actualsPerItem->get($item->id, 0);

                    $rows->push([
                        '',
                        $item->accountCode->code,
                        $item->accountCode->name,
                        ucfirst($item->line_type),
                        (float) $item->q1_amount,
                        (float) $item->q2_amount,
                        (float) $item->q3_amount,
                        (float) $item->q4_amount,
                        (float) $item->total_amount,
                        $supp,
                        $eff,
                        $actual,
                    ]);
                    $this->rowTypes[] = 'item';

                    $grandQ1     += $item->q1_amount;
                    $grandQ2     += $item->q2_amount;
                    $grandQ3     += $item->q3_amount;
                    $grandQ4     += $item->q4_amount;
                    $grandOrig   += $item->total_amount;
                    $grandSupp   += $supp;
                    $grandEff    += $eff;
                    $grandActual += $actual;
                }
            }
        }

        // Grand total row
        $rows->push(['GRAND TOTAL', '', '', '', $grandQ1, $grandQ2, $grandQ3, $grandQ4, $grandOrig, $grandSupp, $grandEff, $grandActual]);
        $this->rowTypes[] = 'grand_total';

        return $rows;
    }

    public function styles(Worksheet $sheet): void
    {
        // Insert 2 title rows above the heading
        $sheet->insertNewRowBefore(1, 2);

        $dept   = $this->version->department->name;
        $period = $this->version->period->name;
        $ver    = $this->version->version_number;
        $status = ucfirst(str_replace('_', ' ', $this->version->status));

        $sheet->setCellValue('A1', "{$dept} — Budget {$period} v{$ver}");
        $sheet->setCellValue('A2', "Status: {$status} | Exported: " . now()->format('d M Y H:i'));
        $sheet->mergeCells('A1:L1');
        $sheet->mergeCells('A2:L2');

        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1B2A4A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['size' => 10, 'color' => ['argb' => 'FF64748B']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8FAFC']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Heading row is now row 3
        $sheet->getStyle('A3:L3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF243B55']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Data starts at row 4
        $dataStart = 4;
        foreach ($this->rowTypes as $i => $rowType) {
            $r = $dataStart + $i;

            $sectionColors = [
                'section_revenue'             => 'FF1B2A4A',
                'section_expense'             => 'FF7C2D12',
                'section_assets'              => 'FF1E3A8A',
                'section_liabilities'         => 'FF6B21A8',
                'section_capital_expenditure' => 'FF92400E',
            ];

            if (isset($sectionColors[$rowType])) {
                $sheet->getStyle("A{$r}:L{$r}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $sectionColors[$rowType]]],
                ]);
                $sheet->mergeCells("A{$r}:L{$r}");

            } elseif ($rowType === 'category') {
                $sheet->getStyle("A{$r}:L{$r}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FF1E3A5F']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE8F0FE']],
                    'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]],
                ]);
                $sheet->getStyle("A{$r}")->getFont()->setBold(true);

            } elseif ($rowType === 'item') {
                $sheet->getStyle("B{$r}")->getFont()->getColor()->setARGB('FF1B2A4A');
                $sheet->getStyle("B{$r}")->getFont()->setBold(true);
                $sheet->getStyle("C{$r}:D{$r}")->getFont()->getColor()->setARGB('FF475569');

            } elseif ($rowType === 'grand_total') {
                $sheet->getStyle("A{$r}:L{$r}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFC9A84C']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0F172A']],
                    'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FFC9A84C']]],
                ]);
            }
        }

        // Number format: Q1–Actual columns (E to L)
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle("E4:L{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00');

        // Align right for numeric columns
        $sheet->getStyle("E3:L{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->freezePane('E4');
    }
}
