<?php

namespace App\Exports;

use App\Models\Department;
use App\Models\Zone;
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

class DepartmentExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new DepartmentTemplateSheet(),
            new DepartmentDataSheet(),
            new DepartmentZoneRefSheet(),
        ];
    }
}

class DepartmentTemplateSheet implements WithTitle, WithStyles, ShouldAutoSize
{
    public function title(): string { return 'Import Template'; }

    public function styles(Worksheet $sheet)
    {
        $sheet->fromArray([[
            'zone_code', 'code', 'name', 'description', 'budget_type', 'is_active',
        ]], null, 'A1');

        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1B2A4A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $samples = [
            ['HQ', 'FIN',  'Finance',    'Finance department',        'both',    'Yes'],
            ['HQ', 'OPS',  'Operations', 'Operations department',     'expense', 'Yes'],
            ['HQ', 'MKTG', 'Marketing',  'Marketing & Sales',         'both',    'Yes'],
        ];

        foreach ($samples as $idx => $row) {
            $sheet->fromArray([$row], null, 'A' . ($idx + 2));
        }

        $sheet->getStyle('A2:F4')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF9C4']],
            'font' => ['color' => ['argb' => 'FF666666']],
        ]);

        $sheet->setCellValue('H1', 'See "Zones" sheet for valid zone codes');
        $sheet->setCellValue('H2', 'budget_type: revenue | expense | both');
        $sheet->setCellValue('H3', 'is_active: Yes | No  (leave blank = Yes for new)');
        $sheet->setCellValue('H4', '← Sample rows. Delete before uploading.');

        $sheet->getStyle('H1:H4')->applyFromArray([
            'font' => ['italic' => true, 'color' => ['argb' => 'FF999999']],
        ]);

        $sheet->getStyle('A1:F' . ($sheet->getHighestRow()))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]],
        ]);

        return [];
    }
}

class DepartmentDataSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function title(): string { return 'Existing Departments'; }

    public function collection()
    {
        return Department::departments()
            ->with('zone')
            ->orderBy('code')
            ->get()
            ->map(fn($d) => [
                $d->zone?->code ?? '',
                $d->code,
                $d->name,
                $d->description ?? '',
                $d->budget_type ?? '',
                $d->is_active ? 'Yes' : 'No',
            ]);
    }

    public function headings(): array
    {
        return ['Zone Code', 'Code', 'Name', 'Description', 'Budget Type', 'Active'];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1B2A4A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle('A1:F' . ($sheet->getHighestRow()))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]],
        ]);

        return [];
    }
}

class DepartmentZoneRefSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    public function title(): string { return 'Zones'; }

    public function collection()
    {
        return Zone::where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(fn($z) => [$z->code, $z->name]);
    }

    public function headings(): array { return ['Zone Code', 'Zone Name']; }
}
