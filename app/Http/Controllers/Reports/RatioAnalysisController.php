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

    private function computeRatios(Collection $ratios, ?BudgetPeriod $period, ?BudgetPeriod $prevPeriod, ?Department $department, string $basis): array
    {
        $results = [];

        foreach ($ratios as $ratio) {
            $numerator   = $this->sumSide($ratio->numerator_source,   $ratio->numerator_types,   $period,     $department, $basis);
            $denominator = $this->sumSide($ratio->denominator_source, $ratio->denominator_types, $period,     $department, $basis);
            $value       = $denominator != 0 ? ($numerator / $denominator) * $ratio->multiply_by : null;

            $prevValue = null;
            if ($prevPeriod) {
                $pNum   = $this->sumSide($ratio->numerator_source,   $ratio->numerator_types,   $prevPeriod, $department, $basis);
                $pDen   = $this->sumSide($ratio->denominator_source, $ratio->denominator_types, $prevPeriod, $department, $basis);
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

    private function sumSide(string $source, array $types, ?BudgetPeriod $period, ?Department $department, string $basis): float
    {
        if (!$period) return 0;

        if ($source === 'actual') {
            $q = BudgetActual::where('budget_period_id', $period->id)
                ->where('status', 'confirmed');
            if ($department) {
                $q->where('department_id', $department->id);
            }
            if (!in_array('all', $types)) {
                $q->whereHas('accountCode.category', fn($c) => $c->whereIn('budget_type', $types));
            }
            return (float) $q->sum('amount');
        }

        // budget source
        $versionIds = $this->effectiveVersionIds($period, $basis, $department?->id);
        if (empty($versionIds)) return 0;

        $q = BudgetLineItem::whereIn('budget_version_id', $versionIds);
        if (!in_array('all', $types)) {
            $q->whereHas('accountCode.category', fn($c) => $c->whereIn('budget_type', $types));
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
