<?php

namespace App\Exports;

use App\Models\AccountCategory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AccountCategoryExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new AccountCategoryDataSheet(),
            new AccountCategoryTemplateSheet(),
        ];
    }
}

class AccountCategoryDataSheet implements
    FromCollection,
    WithHeadings,
    WithTitle,
    WithStyles,
    ShouldAutoSize
{
    public function title(): string
    {
        return 'Existing Categories';
    }

    public function collection()
    {
        return AccountCategory::orderBy('name')
            ->get()
            ->map(fn($c) => [
                $c->id,
                $c->code,
                $c->name,
                $c->description,
                $c->is_active ? 'Yes' : 'No',
                $c->accountCodes()->count(),
            ]);
    }

    public function headings(): array
    {
        return ['ID', 'Code', 'Name', 'Description', 'Active', 'Code Count'];
    }

    public function styles(Worksheet $sheet)
    {
        // Style the header row
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1B2A4A'], // Navy blue
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Hide the ID column (column A)
        $sheet->getColumnDimension('A')->setVisible(false);

        // Auto-size other columns
        foreach (range('B', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add borders to data range
        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle('A1:F' . $highestRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFCBD5E1'],
                ],
            ],
        ]);

        return [];
    }
}

class AccountCategoryTemplateSheet implements
    WithTitle,
    WithStyles,
    ShouldAutoSize
{
    public function title(): string
    {
        return 'Import Template';
    }

    /**
     *  FIX: Use styles() method instead of __invoke()
     */
    public function styles(Worksheet $sheet)
    {
        // Set column headers
        $sheet->fromArray([[
            'code', 'name', 'description',
        ]], null, 'A1');

        // Style the header row
        $sheet->getStyle('A1:C1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1B2A4A'], // Navy blue
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Sample row
        $sampleRow = [
            'OPEX', 'Operating Expenses', 'Day-to-day operational costs',
        ];
        $sheet->fromArray([$sampleRow], null, 'A2');

        // Style the sample row
        $sheet->getStyle('A2:C2')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFFFF9C4'], // Light yellow
            ],
            'font' => [
                'color' => ['argb' => 'FF666666'],
            ],
        ]);

        // Add helper text
        $sheet->setCellValue('E1', '← Sample row. Delete before uploading.');

        // Style helper text
        $sheet->getStyle('E1')->applyFromArray([
            'font' => [
                'italic' => true,
                'color' => ['argb' => 'FF999999'],
            ],
        ]);

        // Auto-size columns
        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add borders to data range
        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle('A1:C' . $highestRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFCBD5E1'],
                ],
            ],
        ]);

        return [];
    }
}
