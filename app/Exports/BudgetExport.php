<?php

namespace App\Exports;

use App\Models\BudgetVersion;
use App\Models\Virement;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class BudgetExport implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
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
                'Amount ({{ currency() }})', 'Status', 'Requested By',
                'Approved By', 'Date',
            ],
            'utilisation' => [
                'Department', 'Approved Budget ({{ currency() }})',
                'Actual Spend ({{ currency() }})', 'Utilisation %', 'Remaining ({{ currency() }})',
            ],
            default => [
                'Category', 'Account Code', 'Account Name',
                'Q1 ({{ currency() }})', 'Q2 ({{ currency() }})', 'Q3 ({{ currency() }})', 'Q4 ({{ currency() }})', 'Total ({{ currency() }})',
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
        $versions = BudgetVersion::with('department', 'lineItems')
            ->when($this->periodId, fn($q) => $q->where('budget_period_id', $this->periodId))
            ->where('status', BudgetVersion::STATUS_APPROVED)
            ->get();

        return $versions->map(function ($version) {
            $approved = $version->lineItems->sum('total_amount');
            $actual   = 0;
            $pct      = $approved > 0 ? round(($actual / $approved) * 100, 1) : 0;

            return [
                $version->department->name,
                number_format($approved, 2),
                number_format($actual, 2),
                $pct . '%',
                number_format($approved - $actual, 2),
            ];
        });
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
