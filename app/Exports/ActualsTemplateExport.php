<?php

namespace App\Exports;

use App\Models\BudgetVersion;
use App\Models\BudgetActual;
use App\Models\BudgetLineItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ActualsTemplateExport implements
    FromCollection, WithHeadings, WithTitle,
    WithStyles, ShouldAutoSize
{
    public function __construct(
        protected BudgetVersion $version,
        protected int           $month,
        protected int           $year
    ) {}

    public function title(): string
    {
        return BudgetActual::MONTHS[$this->month] . ' ' . $this->year;
    }

    public function collection()
    {
        $items = BudgetLineItem::where('budget_version_id', $this->version->id)
            ->with('accountCode.category')
            ->get();

        return $items->map(function ($item) {
            $existing = $item->actualForMonth($this->month, $this->year);

            return [
                $item->id,
                $item->accountCode->category->name,
                $item->accountCode->code,
                $item->accountCode->name,
                $item->total_amount,                    // E — budget
                round($item->total_amount / 12, 2),     // F — monthly budget guide
                $existing?->amount ?? 0,                // G — actual (editable)
                $existing?->reference ?? '',            // H — reference
                $existing?->description ?? '',          // I — notes
            ];
        });
    }

    public function headings(): array
    {
        return [
            'line_item_id',
            'Category',
            'Account Code',
            'Account Name',
            'Annual Budget (GHS)',
            'Monthly Guide (GHS)',
            'Actual Amount (GHS)',
            'Reference/Voucher',
            'Notes',
        ];
    }

    public function styles(Worksheet $sheet): void
    {
        $lastRow = $sheet->getHighestRow();

        // Instruction banner
        $sheet->insertNewRowBefore(1, 1);
        $sheet->setCellValue('A1',
            'GOIL ACTUALS — ' . BudgetActual::MONTHS[$this->month] . ' ' . $this->year .
            ' — Fill in "Actual Amount" and "Reference" columns only.'
        );
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FF065F46']],
            'fill' => ['fillType' => Fill::FILL_SOLID,
                       'startColor' => ['argb' => 'FFD1FAE5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Header
        $sheet->getStyle('A2:I2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID,
                       'startColor' => ['argb' => 'FF1B2A4A']],
        ]);

        // Locked columns
        $sheet->getStyle('A3:F'.$lastRow)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID,
                       'startColor' => ['argb' => 'FFF8FAFC']],
            'font' => ['color' => ['argb' => 'FF64748B']],
        ]);

        // Editable actual column
        $sheet->getStyle('G3:I'.$lastRow)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID,
                       'startColor' => ['argb' => 'FFFFFFFF']],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                 'color' => ['argb' => 'FFE2E8F0']],
            ],
        ]);

        $sheet->getColumnDimension('A')->setVisible(false);
        $sheet->getStyle('E3:G'.$lastRow)
              ->getNumberFormat()->setFormatCode('#,##0.00');

        $sheet->freezePane('G3');
    }
}
