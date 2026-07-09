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
    private array  $rowTypes = [];
    private string $mode;
    private string $lastCol;
    private int    $totalCols;

    public function __construct(
        protected BudgetVersion $version,
        protected Collection $actualsPerItem
    ) {
        $this->mode      = $version->period->entry_mode ?? 'quarterly';
        // Quarterly: cat code name type Q1 Q2 Q3 Q4 OrigTotal Supp EffTotal Actual = 12 cols (A-L)
        // Monthly:   cat code name type Jan..Dec OrigTotal Supp EffTotal Actual = 20 cols (A-T)
        $this->lastCol   = $this->mode === 'monthly' ? 'T' : 'L';
        $this->totalCols = $this->mode === 'monthly' ? 20 : 12;
    }

    public function title(): string { return 'Budget'; }

    public function headings(): array
    {
        $periodCols = $this->mode === 'monthly'
            ? ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
            : ['Q1', 'Q2', 'Q3', 'Q4'];

        return array_merge(
            ['Category', 'Code', 'Account Name', 'Type'],
            $periodCols,
            ['Original Total', 'Supplementary', 'Effective Total', 'Actual (YTD)']
        );
    }

    public function collection(): Collection
    {
        $items     = $this->version->lineItems;
        $isMonthly = $this->mode === 'monthly';
        $n         = $this->totalCols;

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

        $rows          = collect();
        $this->rowTypes = [];
        $grandPeriod   = array_fill(0, $isMonthly ? 12 : 4, 0.0);
        $grandOrig = $grandSupp = $grandEff = $grandActual = 0.0;

        foreach ($sections as $sectionType => $categories) {
            if (empty($categories)) continue;

            $sectionLabel = $sectionLabels[$sectionType];
            $rows->push(array_pad([$sectionLabel], $n, ''));
            $this->rowTypes[] = "section_{$sectionType}";

            foreach ($categories as $catName => $catItems) {
                $cc          = collect($catItems);
                $catPeriod   = $this->sumPeriodCols($cc, $isMonthly);
                $catOrig     = (float) $cc->sum('total_amount');
                $catSupp     = (float) $cc->sum(fn($i) => $i->approvedSupplementaryTotal());
                $catEff      = (float) $cc->sum(fn($i) => $i->effectiveBudget());
                $catActual   = (float) $cc->sum(fn($i) => $this->actualsPerItem->get($i->id, 0));

                $rows->push(array_merge([$catName, '', '', ''], $catPeriod, [$catOrig, $catSupp, $catEff, $catActual]));
                $this->rowTypes[] = 'category';

                foreach ($catItems as $item) {
                    $supp        = (float) $item->approvedSupplementaryTotal();
                    $eff         = (float) $item->effectiveBudget();
                    $actual      = (float) $this->actualsPerItem->get($item->id, 0);
                    $itemPeriod  = $this->itemPeriodCols($item, $isMonthly);

                    $rows->push(array_merge(
                        ['', $item->accountCode->code, $item->accountCode->name, ucfirst($item->line_type)],
                        $itemPeriod,
                        [(float) $item->total_amount, $supp, $eff, $actual]
                    ));
                    $this->rowTypes[] = 'item';

                    foreach ($itemPeriod as $k => $v) { $grandPeriod[$k] += $v; }
                    $grandOrig   += $item->total_amount;
                    $grandSupp   += $supp;
                    $grandEff    += $eff;
                    $grandActual += $actual;
                }
            }
        }

        $rows->push(array_merge(['GRAND TOTAL', '', '', ''], $grandPeriod, [$grandOrig, $grandSupp, $grandEff, $grandActual]));
        $this->rowTypes[] = 'grand_total';

        return $rows;
    }

    private function sumPeriodCols($cc, bool $isMonthly): array
    {
        if ($isMonthly) {
            return array_map(fn($m) => (float) $cc->sum("m{$m}_amount"), range(1, 12));
        }
        return [
            (float) $cc->sum('q1_amount'),
            (float) $cc->sum('q2_amount'),
            (float) $cc->sum('q3_amount'),
            (float) $cc->sum('q4_amount'),
        ];
    }

    private function itemPeriodCols($item, bool $isMonthly): array
    {
        if ($isMonthly) {
            return array_map(fn($m) => (float) $item->{"m{$m}_amount"}, range(1, 12));
        }
        return [
            (float) $item->q1_amount,
            (float) $item->q2_amount,
            (float) $item->q3_amount,
            (float) $item->q4_amount,
        ];
    }

    public function styles(Worksheet $sheet): void
    {
        $lc = $this->lastCol;

        $sheet->insertNewRowBefore(1, 2);

        $dept   = $this->version->department->name;
        $period = $this->version->period->name;
        $ver    = $this->version->version_number;
        $status = ucfirst(str_replace('_', ' ', $this->version->status));

        $sheet->setCellValue('A1', "{$dept} — Budget {$period} v{$ver}");
        $sheet->setCellValue('A2', "Status: {$status} | Exported: " . now()->format('d M Y H:i'));
        $sheet->mergeCells("A1:{$lc}1");
        $sheet->mergeCells("A2:{$lc}2");

        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1B2A4A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['size' => 10, 'color' => ['argb' => 'FF64748B']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8FAFC']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle("A3:{$lc}3")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF243B55']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

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
                $sheet->getStyle("A{$r}:{$lc}{$r}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $sectionColors[$rowType]]],
                ]);
                $sheet->mergeCells("A{$r}:{$lc}{$r}");

            } elseif ($rowType === 'category') {
                $sheet->getStyle("A{$r}:{$lc}{$r}")->applyFromArray([
                    'font'    => ['bold' => true, 'color' => ['argb' => 'FF1E3A5F']],
                    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE8F0FE']],
                    'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]],
                ]);
                $sheet->getStyle("A{$r}")->getFont()->setBold(true);

            } elseif ($rowType === 'item') {
                $sheet->getStyle("B{$r}")->getFont()->getColor()->setARGB('FF1B2A4A');
                $sheet->getStyle("B{$r}")->getFont()->setBold(true);
                $sheet->getStyle("C{$r}:D{$r}")->getFont()->getColor()->setARGB('FF475569');

            } elseif ($rowType === 'grand_total') {
                $sheet->getStyle("A{$r}:{$lc}{$r}")->applyFromArray([
                    'font'    => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFC9A84C']],
                    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0F172A']],
                    'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FFC9A84C']]],
                ]);
            }
        }

        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle("E4:{$lc}{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("E3:{$lc}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->freezePane('E4');
    }
}
