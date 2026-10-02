@extends('layouts.app')
@section('title', 'Ratio Analysis')
@section('content')

@php
$trendIcon  = ['good-up'=>'↑','bad-up'=>'↑','good-down'=>'↓','bad-down'=>'↓','flat'=>'→'];
$trendColor = ['good-up'=>'#16A34A','bad-up'=>'#DC2626','good-down'=>'#DC2626','bad-down'=>'#16A34A','flat'=>'#94A3B8'];
$trendLabel = ['good-up'=>'Improved','bad-up'=>'Worsened','good-down'=>'Worsened','bad-down'=>'Improved','flat'=>'No change'];
$typeLabels = \App\Models\RatioConfig::allTypes();
@endphp

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h5 class="fw-bold mb-0" style="color:#1B2A4A">Ratio Analysis</h5>
        <p class="text-muted small mb-0">Financial ratios based on approved budgets and actuals</p>
    </div>
    @can('manage users')
    <a href="{{ route('admin.ratio-configs.index') }}"
       class="btn btn-sm"
       style="background:#F1F5F9;color:#475569;border:1px solid #E2E8F0;border-radius:8px;font-size:12px">
        Configure Ratios
    </a>
    @endcan
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('reports.ratios') }}" class="mb-4">
    <div class="card border-0 shadow-sm p-3" style="border-radius:12px;border:1px solid #E2E8F0!important">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold" style="color:#1B2A4A">Budget Period</label>
                <select name="period_id" class="form-select form-select-sm">
                    @foreach($periods as $p)
                    <option value="{{ $p->id }}" {{ $period && $p->id == $period->id ? 'selected' : '' }}>
                        {{ $p->name ?? $p->year }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold" style="color:#1B2A4A">Department</label>
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">All Departments</option>
                    @foreach($departments as $d)
                    <option value="{{ $d->id }}" {{ $department && $d->id == $department->id ? 'selected' : '' }}>
                        {{ $d->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold" style="color:#1B2A4A">Budget Basis</label>
                <select name="budget_basis" class="form-select form-select-sm">
                    <option value="original" {{ $basis === 'original' ? 'selected' : '' }}>Original Budget</option>
                    <option value="revised"  {{ $basis === 'revised'  ? 'selected' : '' }}>Latest Approved</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-sm w-100"
                        style="background:#E65C00;color:#fff;border:none;border-radius:8px">
                    Apply Filters
                </button>
            </div>
        </div>
    </div>
</form>

@if($period)
<div class="small mb-3" style="color:#64748B">
    Period: <strong style="color:#1B2A4A">{{ $period->name ?? $period->year }}</strong>
    @if($prevPeriod)
    &nbsp;·&nbsp; Compared with: <strong style="color:#1B2A4A">{{ $prevPeriod->name ?? $prevPeriod->year }}</strong>
    @endif
    @if($department)
    &nbsp;·&nbsp; Department: <strong style="color:#1B2A4A">{{ $department->name }}</strong>
    @endif
</div>
@endif

@if($ratios->isEmpty())
<div class="text-center py-5" style="color:#94A3B8">
    <p>No active ratios configured.</p>
    @can('manage users')
    <a href="{{ route('admin.ratio-configs.create') }}" style="color:#E65C00">Add a ratio →</a>
    @endcan
</div>
@else

{{-- Ratio cards --}}
<div class="row g-3 mb-4">
    @foreach($ratios as $ratio)
    @php
        $r       = $results[$ratio->id] ?? null;
        $value   = $r['value'] ?? null;
        $prev    = $r['prev_value'] ?? null;
        $trend   = $r['trend'] ?? null;
        $numT    = collect($ratio->numerator_types)->map(fn($t) => $typeLabels[$t] ?? $t)->join(', ');
        $denT    = collect($ratio->denominator_types)->map(fn($t) => $typeLabels[$t] ?? $t)->join(', ');
        $pct     = $ratio->unit === '%';
    @endphp
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100"
             style="border-radius:12px;border:1px solid #E2E8F0!important;border-top:3px solid #E65C00!important">
            <div class="card-body p-4">

                <div class="fw-semibold mb-1" style="color:#1B2A4A;font-size:14px">{{ $ratio->name }}</div>
                @if($ratio->description)
                <div style="font-size:11px;color:#94A3B8;margin-bottom:12px">{{ $ratio->description }}</div>
                @endif

                {{-- Value --}}
                <div class="d-flex align-items-baseline gap-2 mb-2">
                    <span style="font-size:32px;font-weight:700;color:#1B2A4A;line-height:1">
                        {{ $value !== null ? number_format($value, 1) : '—' }}
                    </span>
                    <span style="font-size:14px;color:#64748B">{{ $ratio->unit }}</span>
                    @if($trend && isset($trendIcon[$trend]))
                    <span style="font-size:14px;font-weight:700;color:{{ $trendColor[$trend] }}"
                          title="{{ $trendLabel[$trend] }}">
                        {{ $trendIcon[$trend] }}
                        <span style="font-size:11px">{{ $trendLabel[$trend] }}</span>
                    </span>
                    @endif
                </div>

                {{-- Previous period --}}
                @if($prev !== null)
                <div style="font-size:11px;color:#94A3B8" class="mb-3">
                    Previous: {{ number_format($prev, 1) }}{{ $ratio->unit }}
                    @php $delta = $value !== null ? $value - $prev : null; @endphp
                    @if($delta !== null)
                    <span style="color:{{ $delta >= 0 ? '#16A34A' : '#DC2626' }}">
                        ({{ $delta >= 0 ? '+' : '' }}{{ number_format($delta, 1) }})
                    </span>
                    @endif
                </div>
                @endif

                {{-- Mini bar if percentage --}}
                @if($pct && $value !== null)
                @php $barW = min(100, max(0, $value)); @endphp
                <div style="height:4px;background:#E2E8F0;border-radius:2px;margin-bottom:12px">
                    <div style="width:{{ $barW }}%;height:4px;background:#E65C00;border-radius:2px;transition:width .4s"></div>
                </div>
                @endif

                {{-- Formula chip --}}
                <div style="font-size:10px;color:#94A3B8;border-top:1px solid #F1F5F9;padding-top:8px;margin-top:4px">
                    <span style="background:#EFF6FF;color:#1E40AF;padding:1px 5px;border-radius:3px">
                        {{ ucfirst($ratio->numerator_source) }}: {{ $numT }}
                    </span>
                    <span class="mx-1">÷</span>
                    <span style="background:#F0FDF4;color:#166534;padding:1px 5px;border-radius:3px">
                        {{ ucfirst($ratio->denominator_source) }}: {{ $denT }}
                    </span>
                    @if($ratio->multiply_by != 1)
                    <span>× {{ number_format($ratio->multiply_by, 0) }}</span>
                    @endif
                </div>

                {{-- Raw figures --}}
                @if($r)
                <div style="font-size:10px;color:#CBD5E1;margin-top:6px">
                    Numerator: {{ number_format($r['numerator'], 0) }}
                    &nbsp;·&nbsp;
                    Denominator: {{ number_format($r['denominator'], 0) }}
                </div>
                @endif

            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Summary table --}}
<div class="card border-0 shadow-sm" style="border-radius:12px;border:1px solid #E2E8F0!important">
    <div class="card-header px-4 py-3 border-0"
         style="background:#1B2A4A;color:#fff;border-radius:11px 11px 0 0">
        <span class="fw-semibold" style="font-size:13px">Summary Table</span>
    </div>
    <div style="overflow-x:auto">
        <table class="table table-hover mb-0" style="font-size:13px">
            <thead style="background:#F8FAFC;border-bottom:2px solid #E2E8F0">
                <tr>
                    <th class="px-4 py-3" style="color:#1B2A4A;font-weight:600">Ratio</th>
                    <th class="px-4 py-3 text-end" style="color:#1B2A4A;font-weight:600">
                        {{ $period ? ($period->name ?? $period->year) : '—' }}
                    </th>
                    @if($prevPeriod)
                    <th class="px-4 py-3 text-end" style="color:#1B2A4A;font-weight:600">
                        {{ $prevPeriod->name ?? $prevPeriod->year }}
                    </th>
                    <th class="px-4 py-3 text-center" style="color:#1B2A4A;font-weight:600">Trend</th>
                    @endif
                    <th class="px-4 py-3" style="color:#1B2A4A;font-weight:600">Formula</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ratios as $ratio)
                @php
                    $r     = $results[$ratio->id] ?? null;
                    $val   = $r['value'] ?? null;
                    $prev  = $r['prev_value'] ?? null;
                    $trend = $r['trend'] ?? null;
                    $numT2 = collect($ratio->numerator_types)->map(fn($t)=>$typeLabels[$t]??$t)->join(', ');
                    $denT2 = collect($ratio->denominator_types)->map(fn($t)=>$typeLabels[$t]??$t)->join(', ');
                @endphp
                <tr>
                    <td class="px-4 py-3">
                        <div class="fw-semibold" style="color:#1B2A4A">{{ $ratio->name }}</div>
                        @if($ratio->description)
                        <div style="font-size:11px;color:#94A3B8">{{ $ratio->description }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-end fw-semibold" style="color:#1B2A4A">
                        {{ $val !== null ? number_format($val, 2) . ' ' . $ratio->unit : '—' }}
                    </td>
                    @if($prevPeriod)
                    <td class="px-4 py-3 text-end" style="color:#64748B">
                        {{ $prev !== null ? number_format($prev, 2) . ' ' . $ratio->unit : '—' }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($trend && isset($trendIcon[$trend]))
                        <span style="font-size:13px;font-weight:700;color:{{ $trendColor[$trend] }}">
                            {{ $trendIcon[$trend] }} {{ $trendLabel[$trend] }}
                        </span>
                        @else
                        <span style="color:#CBD5E1">—</span>
                        @endif
                    </td>
                    @endif
                    <td class="px-4 py-3" style="font-size:11px">
                        <span style="background:#EFF6FF;color:#1E40AF;padding:1px 5px;border-radius:3px">
                            {{ ucfirst($ratio->numerator_source) }}: {{ $numT2 }}
                        </span>
                        <span class="mx-1 text-muted">÷</span>
                        <span style="background:#F0FDF4;color:#166534;padding:1px 5px;border-radius:3px">
                            {{ ucfirst($ratio->denominator_source) }}: {{ $denT2 }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endif

<style>
.form-select{border-color:#E2E8F0;border-radius:8px;padding:6px 10px;font-size:13px}
.form-select:focus{border-color:#E65C00;box-shadow:0 0 0 3px rgba(230,92,0,.1)}
</style>

@endsection
