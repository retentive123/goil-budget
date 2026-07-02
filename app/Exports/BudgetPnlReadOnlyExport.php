<?php

namespace App\Exports;

use App\Models\BudgetVersion;
use App\Models\BudgetPeriod;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Collection;

class BudgetPnlReadOnlyExport implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    private array $rowTypes = [];
    private bool  $hasPrev  = false;
    private int   $colCount = 8; // without prev

    public function __construct(
        protected BudgetVersion  $version,
        protected array          $pnlData,
        protected ?BudgetPeriod  $prevPeriod,
        protected Collection     $actualsPerItem
    ) {
        $this->hasPrev  = $prevPeriod !== null;
        $this->colCount = $this->hasPrev ? 11 : 8;
    }

    public function title(): string { return 'P&L View'; }

    public function headings(): array
    {
        $h = ['Account', 'Q1', 'Q2', 'Q3', 'Q4', 'Total', 'CS %', 'Actual (YTD)'];
        if ($this->hasPrev) {
            $h[] = "Prev Budget ({$this->prevPeriod->name})";
            $h[] = "Prev Actual ({$this->prevPeriod->name})";
            $h[] = 'Growth %';
        }
        return $h;
    }

    public function collection(): Collection
    {
        $rows = collect();
        $this->rowTypes = [];
        $n = $this->colCount;
        $pad = fn(array $r) => array_pad($r, $n, '');

        $revTotals = $this->pnlData['revenue']['totals'] ?? [];
        $expTotals = $this->pnlData['expense']['totals'] ?? [];

        foreach (['revenue', 'expense'] as $sType) {
            $section = $this->pnlData[$sType] ?? [];
            if (empty($section['categories'])) continue;

            // Section header
            $label = $sType === 'revenue' ? 'REVENUE' : 'EXPENSES';
            $rows->push($pad([$label]));
            $this->rowTypes[] = "section_{$sType}";

            foreach ($section['categories'] as $cat) {
                $catTotals = $cat['totals'];
                $catActual = (float) collect($cat['items'])->sum(fn($i) => $this->actualsPerItem->get($i['id'], 0));

                $catRow = [
                    $cat['name'],
                    (float) $catTotals['q1'],
                    (float) $catTotals['q2'],
                    (float) $catTotals['q3'],
                    (float) $catTotals['q4'],
                    (float) $catTotals['effective'],
                    round((float) ($catTotals['common_size'] ?? 0), 2),
                    $catActual,
                ];
                if ($this->hasPrev) {
                    $prevBudget = (float) ($catTotals['prev_budget'] ?? 0);
                    $prevActual = (float) ($catTotals['prev_actual'] ?? 0);
                    $growth     = $this->growthPct($catTotals['effective'], $prevActual);
                    $catRow[] = $prevBudget;
                    $catRow[] = $prevActual;
                    $catRow[] = $growth !== null ? round($growth, 2) : '';
                }
                $rows->push($catRow);
                $this->rowTypes[] = 'category';

                foreach ($cat['items'] as $item) {
                    $effective = (float) ($item['effective'] ?? $item['total']);
                    $actual    = (float) $this->actualsPerItem->get($item['id'], 0);
                    $itemRow   = [
                        "{$item['code']} — {$item['name']}",
                        (float) $item['q1'],
                        (float) $item['q2'],
                        (float) $item['q3'],
                        (float) $item['q4'],
                        $effective,
                        round((float) ($item['common_size'] ?? 0), 2),
                        $actual,
                    ];
                    if ($this->hasPrev) {
                        $prevBudget = (float) ($item['prev_budget'] ?? 0);
                        $prevActual = (float) ($item['prev_actual'] ?? 0);
                        $growth     = $this->growthPct($effective, $prevActual);
                        $itemRow[] = $prevBudget;
                        $itemRow[] = $prevActual;
                        $itemRow[] = $growth !== null ? round($growth, 2) : '';
                    }
                    $rows->push($itemRow);
                    $this->rowTypes[] = 'item';
                }
            }

            // Section total row
            $secEff    = (float) ($section['totals']['effective'] ?? 0);
            $secActual = 0.0;
            foreach ($section['categories'] as $cat) {
                $secActual += (float) collect($cat['items'])->sum(fn($i) => $this->actualsPerItem->get($i['id'], 0));
            }
            $secRow = [
                "Total {$label}",
                (float) ($section['totals']['q1'] ?? 0),
                (float) ($section['totals']['q2'] ?? 0),
                (float) ($section['totals']['q3'] ?? 0),
                (float) ($section['totals']['q4'] ?? 0),
                $secEff,
                '',
                $secActual,
            ];
            if ($this->hasPrev) {
                $prevB = (float) ($section['totals']['prev_budget'] ?? 0);
                $prevA = (float) ($section['totals']['prev_actual'] ?? 0);
                $g     = $this->growthPct($secEff, $prevA);
                $secRow[] = $prevB;
                $secRow[] = $prevA;
                $secRow[] = $g !== null ? round($g, 2) : '';
            }
            $rows->push($secRow);
            $this->rowTypes[] = "total_{$sType}";
        }

        // Net income row
        $revEff = (float) ($revTotals['effective'] ?? 0);
        $expEff = (float) ($expTotals['effective'] ?? 0);
        $netEff = $revEff - $expEff;

        $netRow = ['NET INCOME / (LOSS)', '', '', '', '', $netEff, '', ''];
        if ($this->hasPrev) {
            $prevNetB  = (float) ($revTotals['prev_budget'] ?? 0) - (float) ($expTotals['prev_budget'] ?? 0);
            $prevNetA  = (float) ($revTotals['prev_actual'] ?? 0) - (float) ($expTotals['prev_actual'] ?? 0);
            $netGrowth = $this->growthPct($netEff, $prevNetA);
            $netRow[] = $prevNetB;
            $netRow[] = $prevNetA;
            $netRow[] = $netGrowth !== null ? round($netGrowth, 2) : '';
        }
        $rows->push($netRow);
        $this->rowTypes[] = 'net_income';

        return $rows;
    }

    private function growthPct(float $current, float $prev): ?float
    {
        if ($prev == 0) return null;
        return ($current - $prev) / abs($prev) * 100;
    }

    public function styles(Worksheet $sheet): void
    {
        // Insert 2 title rows
        $sheet->insertNewRowBefore(1, 2);

        $dept    = $this->version->department->name;
        $period  = $this->version->period->name;
        $ver     = $this->version->version_number;
        $status  = ucfirst(str_replace('_', ' ', $this->version->status));
        $lastCol = Coordinate::stringFromColumnIndex($this->colCount);

        $sheet->setCellValue('A1', "{$dept} — P&L Budget {$period} v{$ver}");
        $sheet->setCellValue('A2', "Status: {$status} | Exported: " . now()->format('d M Y H:i'));
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->mergeCells("A2:{$lastCol}2");

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

        // Heading row = row 3
        $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF243B55']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Data rows start at 4
        $dataStart = 4;
        foreach ($this->rowTypes as $i => $rowType) {
            $r    = $dataStart + $i;
            $rng  = "A{$r}:{$lastCol}{$r}";

            switch ($rowType) {
                case 'section_revenue':
                    $sheet->getStyle($rng)->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1B2A4A']],
                    ]);
                    $sheet->mergeCells($rng);
                    break;

                case 'section_expense':
                    $sheet->getStyle($rng)->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF7C2D12']],
                    ]);
                    $sheet->mergeCells($rng);
                    break;

                case 'category':
                    $sheet->getStyle($rng)->applyFromArray([
                        'font'    => ['bold' => true, 'color' => ['argb' => 'FF1E3A5F']],
                        'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE8F0FE']],
                        'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]],
                    ]);
                    break;

                case 'item':
                    $sheet->getStyle("A{$r}")->getFont()->getColor()->setARGB('FF334155');
                    break;

                case 'total_revenue':
                    $sheet->getStyle($rng)->applyFromArray([
                        'font'    => ['bold' => true, 'color' => ['argb' => 'FF1E3A5F']],
                        'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFBFDBFE']],
                        'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF1B2A4A']]],
                    ]);
                    break;

                case 'total_expense':
                    $sheet->getStyle($rng)->applyFromArray([
                        'font'    => ['bold' => true, 'color' => ['argb' => 'FF7C2D12']],
                        'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFECDD3']],
                        'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF7C2D12']]],
                    ]);
                    break;

                case 'net_income':
                    $sheet->getStyle($rng)->applyFromArray([
                        'font'    => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFC9A84C']],
                        'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0F172A']],
                        'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FFC9A84C']]],
                    ]);
                    break;
            }
        }

        // Number format for numeric columns: B through lastCol, except G (CS%) and K (Growth%) which use percentage format
        $lastRow = $sheet->getHighestRow();
        $numRange   = "B4:{$lastCol}{$lastRow}";
        $sheet->getStyle($numRange)->getNumberFormat()->setFormatCode('#,##0.00');

        // CS% and Growth% columns as percentage
        $sheet->getStyle("G4:G{$lastRow}")->getNumberFormat()->setFormatCode('0.00"%"');
        if ($this->hasPrev) {
            $sheet->getStyle("K4:K{$lastRow}")->getNumberFormat()->setFormatCode('0.00"%"');
        }

        // Right-align numerics
        $sheet->getStyle("B3:{$lastCol}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->freezePane('B4');
    }
}
