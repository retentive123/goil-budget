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

class ServiceStationExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new ServiceStationTemplateSheet(),
            new ServiceStationDataSheet(),
            new ZoneReferenceSheet(),
        ];
    }
}

class ServiceStationTemplateSheet implements WithTitle, WithStyles, ShouldAutoSize
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
            ['HQ', 'SS001', 'Tema Station',   'Tema branch service station', 'both',    'Yes'],
            ['HQ', 'SS002', 'Accra Central',  'Central Accra station',       'revenue', 'Yes'],
            ['HQ', 'SS003', 'Kumasi Station', 'Kumasi branch',               'expense', 'Yes'],
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

class ServiceStationDataSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function title(): string { return 'Existing Stations'; }

    public function collection()
    {
        return Department::serviceStations()
            ->with('zone')
            ->orderBy('code')
            ->get()
            ->map(fn($s) => [
                $s->zone?->code ?? '',
                $s->code,
                $s->name,
                $s->description ?? '',
                $s->budget_type ?? '',
                $s->is_active ? 'Yes' : 'No',
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

class ZoneReferenceSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
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
