@extends('layouts.app')
@section('title', 'Record Actuals')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Actual Expenditure</h5>
        <p class="text-muted small mb-0">
            Record monthly actual spend against approved budgets
        </p>
    </div>
    @can('view all budgets')
    <a href="{{ route('actuals.overview') }}"
       class="btn btn-sm"
       style="background:var(--navy);color:#fff;border-radius:8px">
        Full Overview →
    </a>
    @endcan
</div>

{{-- Filters --}}
<form method="GET" class="chart-card mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Period</label>
            <select name="period_id" class="form-select form-select-sm"
                    onchange="this.form.submit()">
                @foreach($periods as $p)
                <option value="{{ $p->id }}"
                    {{ $period?->id == $p->id ? 'selected' : '' }}>
                    {{ $p->name }}
                </option>
                @endforeach
            </select>
        </div>
        @can('view all budgets')
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Department</label>
            <select name="department_id" class="form-select form-select-sm"
                    onchange="this.form.submit()">
                @foreach($departments as $d)
                <option value="{{ $d->id }}"
                    {{ $department?->id == $d->id ? 'selected' : '' }}>
                    {{ $d->name }}
                </option>
                @endforeach
            </select>
        </div>
        @endcan
    </div>
</form>

@if($department && $period)

{{-- Monthly summary grid --}}
<div class="chart-card mb-4">
    <div class="chart-title">
        {{ $department->name }} — {{ $period->name }} Monthly Summary
    </div>

    <div class="row g-2 mb-4">
        @foreach($monthlySummary as $m => $summary)
        @php
            $isCurrentMonth = $m == now()->month && $period->year == now()->year;
            $isPast         = ($period->year < now()->year) ||
                              ($period->year == now()->year && $m < now()->month);
        @endphp
        <div class="col-md-2 col-4">
            <a href="{{ route('actuals.entry', [
                    'period_id'     => $period->id,
                    'department_id' => $department->id,
                    'month'         => $m,
                    'year'          => $period->year,
                ]) }}"
               style="text-decoration:none">
                <div style="border:1px solid {{ $isCurrentMonth ? 'var(--navy)' : 'var(--border)' }};
                            border-radius:10px;padding:12px;text-align:center;
                            background:{{ $summary['has_data'] ? '#F0FDF4' : ($isCurrentMonth ? '#F0F4FF' : '#fff') }};
                            transition:.2s">
                    <div style="font-size:11px;font-weight:600;text-transform:uppercase;
                                letter-spacing:.5px;color:var(--slate)">
                        {{ substr($summary['name'],0,3) }}
                    </div>
                    @if($summary['has_data'])
                    <div style="font-size:13px;font-weight:700;color:#065F46;margin-top:4px">
                        {{ currency() }} {{ number_format($summary['total'],0) }}
                    </div>
                    <div style="font-size:10px;color:#10B981">✔ Recorded</div>
                    @elseif($isPast || $isCurrentMonth)
                    <div style="font-size:12px;color:var(--slate);margin-top:4px">—</div>
                    <div style="font-size:10px;color:#F59E0B">Pending</div>
                    @else
                    <div style="font-size:12px;color:#CBD5E1;margin-top:4px">—</div>
                    <div style="font-size:10px;color:#CBD5E1">Future</div>
                    @endif
                </div>
            </a>
        </div>
        @endforeach
    </div>

    {{-- YTD total --}}
    @php $ytd = collect($monthlySummary)->sum('total'); @endphp
    <div class="d-flex justify-content-between align-items-center
                pt-3 border-top">
        <span class="small fw-semibold">Year-to-Date Total</span>
        <span style="font-size:18px;font-weight:700;color:var(--navy)">
            {{ currency() }} {{ number_format($ytd, 2) }}
        </span>
    </div>
</div>

{{-- Quick entry buttons --}}
<div class="chart-card">
    <div class="chart-title">Quick Entry</div>
    <div class="row g-2">
        @foreach(\App\Models\BudgetActual::MONTHS as $m => $name)
        <div class="col-md-3">
            <a href="{{ route('actuals.entry', [
                    'period_id'     => $period->id,
                    'department_id' => $department->id,
                    'month'         => $m,
                    'year'          => $period->year,
                ]) }}"
               class="btn btn-sm w-100 text-start"
               style="background:{{ $monthlySummary[$m]['has_data'] ? '#D1FAE5' : 'var(--surface)' }};
                      border:1px solid var(--border);border-radius:8px;
                      padding:8px 12px;font-size:13px;color:var(--navy)">
                {{ $name }}
                @if($monthlySummary[$m]['has_data'])
                    <span style="float:right;color:#10B981;font-size:11px">
                        {{ currency() }} {{ number_format($monthlySummary[$m]['total'],0) }}
                    </span>
                @endif
            </a>
        </div>
        @endforeach
    </div>
</div>

@endif
@endsection
