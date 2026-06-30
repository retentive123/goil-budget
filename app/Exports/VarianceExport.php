<?php

namespace App\Exports;

use App\Models\BudgetVersion;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class VarianceExport implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected ?int $periodId,
        protected ?int $departmentId
    ) {}

    public function collection(): Collection
    {
        $versions = BudgetVersion::with('department', 'lineItems.accountCode.category')
            ->when($this->periodId,     fn($q) => $q->where('budget_period_id', $this->periodId))
            ->when($this->departmentId, fn($q) => $q->where('department_id', $this->departmentId))
            ->where('status', BudgetVersion::STATUS_APPROVED)
            ->get();

        $rows = collect();

        foreach ($versions as $version) {
            foreach ($version->lineItems as $item) {
                $actual   = 0; // Placeholder until actuals module
                $variance = $actual - $item->total_amount;
                $pct      = $item->total_amount > 0
                    ? round(($variance / $item->total_amount) * 100, 1)
                    : 0;

                $rows->push([
                    $version->department->name,
                    $item->accountCode->category->name,
                    $item->accountCode->code,
                    $item->accountCode->name,
                    number_format($item->total_amount, 2),
                    number_format($actual, 2),
                    number_format($variance, 2),
                    $pct . '%',
                    $variance < 0 ? 'Underspend' : ($variance > 0 ? 'Overspend' : 'On Budget'),
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Department', 'Category', 'Code', 'Account Name',
            'Budget ({{ currency() }})', 'Actual ({{ currency() }})', 'Variance ({{ currency() }})',
            'Variance %', 'Status',
        ];
    }

    public function title(): string
    {
        return 'Variance Analysis';
    }

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
}
