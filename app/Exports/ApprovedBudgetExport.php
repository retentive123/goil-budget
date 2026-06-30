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

class ApprovedBudgetExport implements
    FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(protected ?int $periodId, protected ?int $departmentId) {}

    public function title(): string { return 'Approved Budget'; }

    public function collection()
    {
        $versions = BudgetVersion::where('status', 'approved')
            ->when($this->periodId,     fn($q) => $q->where('budget_period_id', $this->periodId))
            ->when($this->departmentId, fn($q) => $q->where('department_id', $this->departmentId))
            ->with('department', 'period', 'lineItems.accountCode.category')
            ->get();

        $rows = collect();

        foreach ($versions as $v) {
            foreach ($v->lineItems as $item) {
                $original      = $item->total_amount;
                $supplementary = $item->approvedSupplementaryTotal();
                $effective     = $original + $supplementary;

                $rows->push([
                    $v->department->name,
                    $v->period->name,
                    $item->accountCode->category->name,
                    $item->accountCode->code,
                    $item->accountCode->name,
                    $item->q1_amount,
                    $item->q2_amount,
                    $item->q3_amount,
                    $item->q4_amount,
                    $original,
                    $supplementary,   // ← NEW
                    $effective,       // ← NEW — what is actually available to spend
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Department', 'Period', 'Category', 'Code', 'Account Name',
            'Q1', 'Q2', 'Q3', 'Q4',
            'Original Budget',
            'Supplementary',
            'Effective Budget',
        ];
    }

    public function styles(Worksheet $sheet): void
    {
        $sheet->getStyle('A1:L1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1B2A4A']],
        ]);

        // Highlight supplementary column
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle("K2:K{$lastRow}")->applyFromArray([
            'font' => ['color' => ['argb' => 'FF92400E']],
        ]);
    }
}
