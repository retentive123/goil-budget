<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CodeExplorerExport implements
    FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(protected array $reportData) {}

    public function title(): string { return 'Code Explorer'; }

    public function collection()
    {
        $rows = collect();

        foreach ($this->reportData as $row) {
            $rows->push([
                $row['code'],
                $row['name'],
                $row['category'],
                $row['budget_q1'],
                $row['budget_q2'],
                $row['budget_q3'],
                $row['budget_q4'],
                $row['budget_original'],
                $row['budget_supplementary'],
                $row['budget_total'], // effective
                $row['actual_q1'],
                $row['actual_q2'],
                $row['actual_q3'],
                $row['actual_q4'],
                $row['actual_total'],
                $row['variance'],
                $row['utilisation'] . '%',
            ]);

            // Per-department breakdown rows, indented
            foreach ($row['dept_breakdown'] as $d) {
                $rows->push([
                    '',
                    '  → ' . $d['dept'],
                    '',
                    $d['budget_q1'], $d['budget_q2'], $d['budget_q3'], $d['budget_q4'],
                    $d['budget_original'],
                    $d['supplementary'],
                    $d['budget_total'],
                    $d['actual_q1'], $d['actual_q2'], $d['actual_q3'], $d['actual_q4'],
                    $d['actual_total'],
                    $d['variance'],
                    '',
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Code', 'Account / Department', 'Category',
            'Q1 Budget', 'Q2 Budget', 'Q3 Budget', 'Q4 Budget',
            'Original Total', 'Supplementary', 'Effective Total',
            'Q1 Actual', 'Q2 Actual', 'Q3 Actual', 'Q4 Actual', 'Total Actual',
            'Variance', 'Utilisation',
        ];
    }

    public function styles(Worksheet $sheet): void
    {
        $sheet->getStyle('A1:Q1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1B2A4A']],
        ]);

        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle("I2:I{$lastRow}")->applyFromArray([
            'font' => ['color' => ['argb' => 'FF92400E']],
        ]);
        $sheet->getStyle("J2:J{$lastRow}")->applyFromArray([
            'font' => ['bold' => true],
        ]);

        $sheet->freezePane('A2');
    }
}
