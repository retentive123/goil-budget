<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\BudgetActual;
use App\Models\BudgetLineItem;
use App\Models\BudgetPeriod;
use App\Models\BudgetVersion;
use App\Models\Department;
use App\Models\RatioConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class RatioAnalysisController extends Controller
{
    public function index(Request $request)
    {
        $periods     = BudgetPeriod::orderByDesc('year')->get();
        $period      = $request->period_id
            ? BudgetPeriod::find($request->period_id)
            : BudgetPeriod::current() ?? $periods->first();

        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $department  = $request->department_id
            ? Department::find($request->department_id)
            : null;

        $basis       = $request->input('budget_basis', 'original');
        $ratios      = RatioConfig::active()->get();

        // Previous period for trend
        $prevPeriod = $period
            ? (BudgetPeriod::where('year', $period->year - 1)->orderByDesc('id')->first()
               ?? BudgetPeriod::where('id', '<', $period->id)->orderByDesc('year')->orderByDesc('id')->first())
            : null;

        $results     = $this->computeRatios($ratios, $period, $prevPeriod, $department, $basis);

        return view('reports.ratios', compact(
            'period', 'periods', 'departments', 'department',
            'basis', 'ratios', 'results', 'prevPeriod'
        ));
    }

    public function export(Request $request)
    {
        $periods    = BudgetPeriod::orderByDesc('year')->get();
        $period     = $request->period_id
            ? BudgetPeriod::find($request->period_id)
            : BudgetPeriod::current() ?? $periods->first();

        $department = $request->department_id
            ? Department::find($request->department_id)
            : null;

        $basis   = $request->input('budget_basis', 'original');
        $ratios  = RatioConfig::active()->get();

        $prevPeriod = $period
            ? (BudgetPeriod::where('year', $period->year - 1)->orderByDesc('id')->first()
               ?? BudgetPeriod::where('id', '<', $period->id)->orderByDesc('year')->orderByDesc('id')->first())
            : null;

        $results    = $this->computeRatios($ratios, $period, $prevPeriod, $department, $basis);
        $typeLabels = RatioConfig::allTypes();

        $trendLabel = [
            'good-up'   => 'Improved ↑',
            'bad-up'    => 'Worsened ↑',
            'good-down' => 'Worsened ↓',
            'bad-down'  => 'Improved ↓',
            'flat'      => 'No change →',
        ];

        $filename = 'ratio-analysis'
            . ($period    ? '-' . str_replace(' ', '_', $period->name ?? $period->year)    : '')
            . ($department ? '-' . str_replace(' ', '_', $department->name) : '')
            . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($ratios, $results, $period, $prevPeriod, $department, $basis, $typeLabels, $trendLabel) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM so Excel opens the file correctly without mojibake
            fwrite($out, "\xEF\xBB\xBF");

            // Meta rows
            fputcsv($out, ['Ratio Analysis Report']);
            fputcsv($out, ['Period',       $period    ? ($period->name    ?? $period->year)    : '-']);
            fputcsv($out, ['Department',   $department ? $department->name                      : 'All']);
            fputcsv($out, ['Budget Basis', $basis === 'revised' ? 'Latest Approved' : 'Original Budget']);
            fputcsv($out, ['Generated',    now()->format('d M Y H:i')]);
            fputcsv($out, []);

            // Column headers
            $cols = ['Ratio', 'Description', 'Unit', 'Value (' . ($period?->name ?? $period?->year ?? '—') . ')'];
            if ($prevPeriod) {
                $cols[] = 'Value (' . ($prevPeriod->name ?? $prevPeriod->year) . ')';
                $cols[] = 'Change';
                $cols[] = 'Trend';
            }
            $cols = array_merge($cols, ['Numerator Source', 'Denominator Source', 'Raw Numerator', 'Raw Denominator']);
            fputcsv($out, $cols);

            foreach ($ratios as $ratio) {
                $r     = $results[$ratio->id] ?? null;
                $val   = $r['value']      ?? null;
                $prev  = $r['prev_value'] ?? null;
                $trend = $r['trend']      ?? null;

                $numTypes = collect($ratio->numerator_types)  ->map(fn($t) => $typeLabels[$t] ?? $t)->join(', ');
                $denTypes = collect($ratio->denominator_types)->map(fn($t) => $typeLabels[$t] ?? $t)->join(', ');

                $row = [
                    $ratio->name,
                    $ratio->description ?? '',
                    $ratio->unit,
                    $val !== null ? number_format($val, 2) : '-',
                ];

                if ($prevPeriod) {
                    $delta  = ($val !== null && $prev !== null) ? $val - $prev : null;
                    $row[]  = $prev !== null ? number_format($prev, 2) : '-';
                    $row[]  = $delta !== null ? number_format($delta, 2) : '-';
                    $row[]  = ($trend && isset($trendLabel[$trend])) ? $trendLabel[$trend] : '-';
                }

                $row[] = ucfirst($ratio->numerator_source)   . ': ' . $numTypes;
                $row[] = ucfirst($ratio->denominator_source) . ': ' . $denTypes;
                $row[] = $r ? number_format($r['numerator'],   2) : '-';
                $row[] = $r ? number_format($r['denominator'], 2) : '-';

                fputcsv($out, $row);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function computeRatios(Collection $ratios, ?BudgetPeriod $period, ?BudgetPeriod $prevPeriod, ?Department $department, string $basis): array
    {
        $results = [];

        foreach ($ratios as $ratio) {
            $numCodes   = $ratio->numerator_codes          ?? [];
            $numCatIds  = $ratio->numerator_category_ids   ?? [];
            $denCodes   = $ratio->denominator_codes        ?? [];
            $denCatIds  = $ratio->denominator_category_ids ?? [];

            $numerator   = $this->sumSide($ratio->numerator_source,   $ratio->numerator_types,   $numCatIds, $numCodes, $period,     $department, $basis);
            $denominator = $this->sumSide($ratio->denominator_source, $ratio->denominator_types, $denCatIds, $denCodes, $period,     $department, $basis);
            $value       = $denominator != 0 ? ($numerator / $denominator) * $ratio->multiply_by : null;

            $prevValue = null;
            if ($prevPeriod) {
                $pNum   = $this->sumSide($ratio->numerator_source,   $ratio->numerator_types,   $numCatIds, $numCodes, $prevPeriod, $department, $basis);
                $pDen   = $this->sumSide($ratio->denominator_source, $ratio->denominator_types, $denCatIds, $denCodes, $prevPeriod, $department, $basis);
                $prevValue = $pDen != 0 ? ($pNum / $pDen) * $ratio->multiply_by : null;
            }

            $results[$ratio->id] = [
                'numerator'   => $numerator,
                'denominator' => $denominator,
                'value'       => $value,
                'prev_value'  => $prevValue,
                'trend'       => $this->trend($value, $prevValue, $ratio->higher_is_better),
            ];
        }

        return $results;
    }

    private function sumSide(string $source, array $types, array $catIds, array $codes, ?BudgetPeriod $period, ?Department $department, string $basis): float
    {
        if (!$period) return 0;

        $hasAll   = in_array('all', $types);
        $hasTypes = !$hasAll && !empty($types);
        $hasCats  = !empty($catIds);
        $hasCodes = !empty($codes);

        if ($source === 'actual') {
            $q = BudgetActual::where('budget_period_id', $period->id)
                ->where('status', 'confirmed');
            if ($department) $q->where('department_id', $department->id);
            if (!$hasAll && ($hasTypes || $hasCats || $hasCodes)) {
                $q->where(function ($sub) use ($types, $catIds, $codes, $hasTypes, $hasCats, $hasCodes) {
                    if ($hasTypes) $sub->orWhereHas('accountCode.category', fn($c) => $c->whereIn('budget_type', $types));
                    if ($hasCats)  $sub->orWhereHas('accountCode', fn($c) => $c->whereIn('account_category_id', $catIds));
                    if ($hasCodes) $sub->orWhereHas('accountCode', fn($c) => $c->whereIn('id', $codes));
                });
            }
            return (float) $q->sum('amount');
        }

        $versionIds = $this->effectiveVersionIds($period, $basis, $department?->id);
        if (empty($versionIds)) return 0;

        $q = BudgetLineItem::whereIn('budget_version_id', $versionIds);
        if (!$hasAll && ($hasTypes || $hasCats || $hasCodes)) {
            $q->where(function ($sub) use ($types, $catIds, $codes, $hasTypes, $hasCats, $hasCodes) {
                if ($hasTypes) $sub->orWhereHas('accountCode.category', fn($c) => $c->whereIn('budget_type', $types));
                if ($hasCats)  $sub->orWhereHas('accountCode', fn($c) => $c->whereIn('account_category_id', $catIds));
                if ($hasCodes) $sub->orWhereHas('accountCode', fn($c) => $c->whereIn('id', $codes));
            });
        }

        return (float) $q->sum('total_amount');
    }

    private function effectiveVersionIds(BudgetPeriod $period, string $basis, ?int $departmentId = null): array
    {
        $q = BudgetVersion::where('budget_period_id', $period->id)
            ->where('status', 'approved');
        if ($departmentId) {
            $q->where('department_id', $departmentId);
        }
        if ($basis === 'original') {
            $q->where('is_revision', false);
        } else {
            // latest approved per department
            $q->whereIn('id', function ($sub) use ($period, $departmentId) {
                $sub->selectRaw('MAX(id)')
                    ->from('budget_versions')
                    ->where('budget_period_id', $period->id)
                    ->where('status', 'approved')
                    ->when($departmentId, fn($s) => $s->where('department_id', $departmentId))
                    ->groupBy('department_id');
            });
        }
        return $q->pluck('id')->toArray();
    }

    private function trend(?float $value, ?float $prev, bool $higherIsBetter): ?string
    {
        if ($value === null || $prev === null || $prev == 0) return null;
        $delta = $value - $prev;
        if (abs($delta) < 0.01) return 'flat';
        $up = $delta > 0;
        return match(true) {
            $up  &&  $higherIsBetter => 'good-up',
            $up  && !$higherIsBetter => 'bad-up',
            !$up &&  $higherIsBetter => 'bad-down',
            default                  => 'good-down',
        };
    }
}
