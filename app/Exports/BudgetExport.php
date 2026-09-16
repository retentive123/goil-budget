<?php

namespace App\Exports;

use App\Models\BudgetActual;
use App\Models\BudgetVersion;
use App\Models\Virement;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class BudgetExport implements FromCollection, WithHeadings, WithTitle, WithStyles, WithEvents, ShouldAutoSize
{
    /**
     * Tracks the type and status of each data row for the utilisation export.
     * Populated by utilisationData(), consumed by AfterSheet.
     * Format: [['type' => 'dept'|'line', 'status' => 'healthy'|'warning'|'critical'], ...]
     */
    protected array $rowMeta = [];

    public function __construct(
        protected ?int    $periodId,
        protected ?int    $departmentId,
        protected string  $type = 'approved'
    ) {}

    public function collection(): Collection
    {
        if ($this->type === 'virement') {
            return $this->virementData();
        }

        if ($this->type === 'utilisation') {
            return $this->utilisationData();
        }

        return $this->approvedData();
    }

    public function headings(): array
    {
        return match($this->type) {
            'virement'    => [
                'Department', 'From Account', 'To Account',
                'Amount', 'Status', 'Requested By', 'Approved By', 'Date',
            ],
            'utilisation' => [
                'Department', 'Category', 'Account Code', 'Account Name',
                'Approved Budget', 'Actual Spend', 'Utilisation %', 'Remaining',
            ],
            default => [
                'Category', 'Account Code', 'Account Name',
                'Q1', 'Q2', 'Q3', 'Q4', 'Total',
            ],
        };
    }

    public function title(): string
    {
        return match($this->type) {
            'virement'    => 'Virement Report',
            'utilisation' => 'Utilisation Report',
            default       => 'Approved Budget',
        };
    }

    /** Basic header row style (row 1). Per-row utilisation styles are applied in AfterSheet. */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1D3557']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                if ($this->type !== 'utilisation' || empty($this->rowMeta)) {
                    return;
                }

                $ws = $event->sheet->getDelegate();

                // Tell Excel the summary (dept) row is ABOVE its detail rows,
                // so the [+] expand button appears on the dept row itself.
                $ws->setShowSummaryBelow(false);

                // Heading is row 1; data starts at row 2
                $dataRow = 2;
                $colCount = 8; // A–H
                $lastCol  = 'H';

                foreach ($this->rowMeta as $meta) {
                    $range = "A{$dataRow}:{$lastCol}{$dataRow}";

                    if ($meta['type'] === 'dept') {
                        // ── Department summary row: navy background, white bold text ────
                        $ws->getStyle($range)->applyFromArray([
                            'font' => [
                                'bold'  => true,
                                'color' => ['argb' => 'FFFFFFFF'],
                                'size'  => 11,
                            ],
                            'fill' => [
                                'fillType'   => 'solid',
                                'startColor' => ['argb' => 'FF1D3557'],  // navy
                            ],
                            'borders' => [
                                'bottom' => ['borderStyle' => 'thin', 'color' => ['argb' => 'FFCBD5E1']],
                            ],
                        ]);

                        // Bold the utilisation % column (col G) with accent colour
                        $ws->getStyle("G{$dataRow}")->getFont()->setColor(
                            new \PhpOffice\PhpSpreadsheet\Style\Color('FFFBBF24') // amber
                        );

                    } else {
                        // ── Line item row: tinted by status, grouped, collapsed by default ──
                        $bgArgb = match($meta['status']) {
                            'critical' => 'FFFFF1F2',  // very light red
                            'warning'  => 'FFFFFBEB',  // very light amber
                            default    => 'FFF0FDF4',  // very light green
                        };
                        $pctArgb = match($meta['status']) {
                            'critical' => 'FFB91C1C',  // deep red
                            'warning'  => 'FFB45309',  // deep amber
                            default    => 'FF065F46',  // deep green
                        };

                        $ws->getStyle($range)->applyFromArray([
                            'fill' => [
                                'fillType'   => 'solid',
                                'startColor' => ['argb' => $bgArgb],
                            ],
                            'borders' => [
                                'bottom' => ['borderStyle' => 'thin', 'color' => ['argb' => 'FFE2E8F0']],
                            ],
                        ]);

                        // Colour the % cell
                        $ws->getStyle("G{$dataRow}")->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['argb' => $pctArgb]],
                        ]);

                        // Colour the actual spend cell (col F)
                        $ws->getStyle("F{$dataRow}")->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['argb' => $pctArgb]],
                        ]);

                        // Indent the department column (col A) for visual hierarchy
                        $ws->getStyle("A{$dataRow}")->getAlignment()
                            ->setIndent(4);

                        // ── Excel outline grouping: level 1, collapsed by default ──
                        $ws->getRowDimension($dataRow)
                            ->setOutlineLevel(1)
                            ->setVisible(false)
                            ->setCollapsed(true);
                    }

                    $dataRow++;
                }

                // Freeze the header row
                $ws->freezePane('A2');
            },
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Data builders
    // ─────────────────────────────────────────────────────────────────────────

    private function approvedData(): Collection
    {
        $versions = BudgetVersion::with('department', 'lineItems.accountCode.category')
            ->when($this->periodId,     fn($q) => $q->where('budget_period_id', $this->periodId))
            ->when($this->departmentId, fn($q) => $q->where('department_id', $this->departmentId))
            ->where('status', BudgetVersion::STATUS_APPROVED)
            ->get();

        $rows = collect();

        foreach ($versions as $version) {
            foreach ($version->lineItems as $item) {
                $rows->push([
                    $item->accountCode->category->name,
                    $item->accountCode->code,
                    $item->accountCode->name,
                    number_format($item->q1_amount, 2),
                    number_format($item->q2_amount, 2),
                    number_format($item->q3_amount, 2),
                    number_format($item->q4_amount, 2),
                    number_format($item->total_amount, 2),
                ]);
            }
        }

        return $rows;
    }

    private function utilisationData(): Collection
    {
        $versions = BudgetVersion::with('department', 'lineItems.accountCode.category')
            ->when($this->periodId,     fn($q) => $q->where('budget_period_id', $this->periodId))
            ->when($this->departmentId, fn($q) => $q->where('department_id', $this->departmentId))
            ->where('status', BudgetVersion::STATUS_APPROVED)
            ->get();

        $rows           = collect();
        $this->rowMeta  = [];

        foreach ($versions as $version) {
            $lineRows   = collect();
            $lineMeta   = [];
            $deptBudget = 0;
            $deptActual = 0;

            foreach ($version->lineItems->sortBy(fn($i) => $i->accountCode->code ?? '') as $item) {
                $itemBudget = (float) $item->total_amount;
                $itemActual = (float) BudgetActual::where('budget_line_item_id', $item->id)
                                        ->where('status', 'confirmed')
                                        ->sum('amount');
                $itemPct    = $itemBudget > 0 ? round(($itemActual / $itemBudget) * 100, 1) : 0;
                $category   = $item->accountCode->category->name ?? '—';
                $status     = $itemPct > 90 ? 'critical' : ($itemPct > 70 ? 'warning' : 'healthy');

                $deptBudget += $itemBudget;
                $deptActual += $itemActual;

                $lineRows->push([
                    $version->department->name,  // col A (indented in AfterSheet)
                    $category,
                    $item->accountCode->code ?? '—',
                    $item->accountCode->name ?? '—',
                    number_format($itemBudget, 2),
                    number_format($itemActual, 2),
                    $itemPct . '%',
                    number_format($itemBudget - $itemActual, 2),
                ]);

                $lineMeta[] = ['type' => 'line', 'status' => $status];
            }

            $deptPct    = $deptBudget > 0 ? round(($deptActual / $deptBudget) * 100, 1) : 0;
            $deptStatus = $deptPct > 90 ? 'critical' : ($deptPct > 70 ? 'warning' : 'healthy');

            // Department summary row — push FIRST so it sits above its detail rows
            $rows->push([
                $version->department->name,
                '',
                '',
                'DEPT TOTAL',
                number_format($deptBudget, 2),
                number_format($deptActual, 2),
                $deptPct . '%',
                number_format($deptBudget - $deptActual, 2),
            ]);
            $this->rowMeta[] = ['type' => 'dept', 'status' => $deptStatus];

            // Line item rows follow immediately
            $rows          = $rows->concat($lineRows);
            $this->rowMeta = array_merge($this->rowMeta, $lineMeta);
        }

        return $rows;
    }

    private function virementData(): Collection
    {
        return Virement::with(
                'department',
                'fromLineItem.accountCode',
                'toLineItem.accountCode',
                'requestedBy',
                'approvedBy'
            )
            ->when($this->periodId, fn($q) => $q->where('budget_period_id', $this->periodId))
            ->get()
            ->map(fn($v) => [
                $v->department->name,
                $v->fromLineItem->accountCode->code . ' — ' . $v->fromLineItem->accountCode->name,
                $v->toLineItem->accountCode->code  . ' — ' . $v->toLineItem->accountCode->name,
                number_format($v->amount, 2),
                ucfirst($v->status),
                $v->requestedBy->name,
                $v->approvedBy?->name ?? '—',
                $v->created_at->format('d M Y'),
            ]);
    }
}
