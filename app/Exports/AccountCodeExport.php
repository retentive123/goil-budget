<?php

namespace App\Exports;

use App\Models\AccountCode;
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

class AccountCodeExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new AccountCodeTemplateSheet(),
            new AccountCodeDataSheet(),
            new CategoryReferenceSheet(),
        ];
    }
}

class AccountCodeTemplateSheet implements
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
            'category_code', 'code', 'name', 'description',
        ]], null, 'A1');

        // Style the header row
        $sheet->getStyle('A1:D1')->applyFromArray([
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

        // Sample rows
        $samples = [
            ['OPEX', '4001', 'Office Supplies', 'Stationery and office consumables'],
            ['OPEX', '4002', 'Utilities', 'Electricity, water and internet'],
            ['CAPEX', '5001', 'Equipment Purchase', 'Machinery and equipment'],
        ];

        foreach ($samples as $idx => $row) {
            $sheet->fromArray([$row], null, 'A' . ($idx + 2));
        }

        // Style the sample rows
        $sheet->getStyle('A2:D4')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFFFF9C4'], // Light yellow
            ],
            'font' => [
                'color' => ['argb' => 'FF666666'],
            ],
        ]);

        // Add helper text
        $sheet->setCellValue('F1', 'See "Categories" sheet for valid category codes');
        $sheet->setCellValue('F2', '← Sample rows. Delete before uploading.');

        // Style helper text
        $sheet->getStyle('F1:F2')->applyFromArray([
            'font' => [
                'italic' => true,
                'color' => ['argb' => 'FF999999'],
            ],
        ]);

        // Auto-size columns
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add borders to data range
        $sheet->getStyle('A1:D' . ($sheet->getHighestRow()))->applyFromArray([
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

class AccountCodeDataSheet implements
    FromCollection,
    WithHeadings,
    WithTitle,
    WithStyles,
    ShouldAutoSize
{
    public function title(): string
    {
        return 'Existing Codes';
    }

    public function collection()
    {
        return AccountCode::with('category')
            ->orderBy('code')
            ->get()
            ->map(fn($c) => [
                $c->category->code,
                $c->code,
                $c->name,
                $c->description,
                $c->is_active ? 'Yes' : 'No',
            ]);
    }

    public function headings(): array
    {
        return ['Category Code', 'Code', 'Name', 'Description', 'Active'];
    }

    public function styles(Worksheet $sheet)
    {
        // Style the header row
        $sheet->getStyle('A1:E1')->applyFromArray([
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

        // Auto-size columns
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add borders
        $sheet->getStyle('A1:E' . ($sheet->getHighestRow()))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFCBD5E1'],
                ],
            ],
        ]);

        // Style data rows
        $sheet->getStyle('A2:E' . ($sheet->getHighestRow()))->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        return [];
    }
}

class CategoryReferenceSheet implements
    FromCollection,
    WithHeadings,
    WithTitle,
    ShouldAutoSize
{
    public function title(): string
    {
        return 'Categories';
    }

    public function collection()
    {
        return AccountCategory::orderBy('code')
            ->get()
            ->map(fn($c) => [$c->code, $c->name]);
    }

    public function headings(): array
    {
        return ['Category Code', 'Category Name'];
    }

    // This doesn't have WithStyles interface, so no styles() method needed
}
