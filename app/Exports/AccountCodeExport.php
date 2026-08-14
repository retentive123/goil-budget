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
            'category_code', 'code', 'name', 'description', 'default_rate', 'default_frequency',
        ]], null, 'A1');

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

        // Sample rows
        $samples = [
            ['OPEX', '4001', 'Office Supplies', 'Stationery and office consumables', '', ''],
            ['OPEX', '4002', 'Utilities', 'Electricity, water and internet', 500.00, 12],
            ['CAPEX', '5001', 'Equipment Purchase', 'Machinery and equipment', 15000.00, 1],
        ];

        foreach ($samples as $idx => $row) {
            $sheet->fromArray([$row], null, 'A' . ($idx + 2));
        }

        // Style the sample rows
        $sheet->getStyle('A2:F4')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFFFF9C4'], // Light yellow
            ],
            'font' => [
                'color' => ['argb' => 'FF666666'],
            ],
        ]);

        // Add helper text
        $sheet->setCellValue('H1', 'See "Categories" sheet for valid category codes');
        $sheet->setCellValue('H2', '← Sample rows. Delete before uploading.');
        $sheet->setCellValue('H3', 'default_rate / default_frequency: leave blank to inherit from the category.');

        // Style helper text
        $sheet->getStyle('H1:H3')->applyFromArray([
            'font' => [
                'italic' => true,
                'color' => ['argb' => 'FF999999'],
            ],
        ]);

        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add borders to data range
        $sheet->getStyle('A1:F' . ($sheet->getHighestRow()))->applyFromArray([
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
                $c->default_rate,
                $c->default_frequency,
            ]);
    }

    public function headings(): array
    {
        return ['Category Code', 'Code', 'Name', 'Description', 'Active', 'Default Rate', 'Default Frequency'];
    }

    public function styles(Worksheet $sheet)
    {
        // Style the header row
        $sheet->getStyle('A1:G1')->applyFromArray([
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
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add borders
        $sheet->getStyle('A1:G' . ($sheet->getHighestRow()))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFCBD5E1'],
                ],
            ],
        ]);

        // Style data rows
        $sheet->getStyle('A2:G' . ($sheet->getHighestRow()))->applyFromArray([
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
