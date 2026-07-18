@extends('layouts.app')
@section('title', 'Actuals Overview')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Actuals Overview</h5>
        <p class="text-muted small mb-0">
            Monthly actual spend across all departments
        </p>
    </div>
</div>

<form method="GET" class="chart-card mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Period</label>
            <select name="period_id" class="form-select form-select-sm"
                    onchange="this.form.submit()">
                @foreach($periods as $p)
                <option value="{{ $p->id }}"
                    {{ $period?->id==$p->id?'selected':'' }}>
                    {{ $p->name }}
                </option>
                @endforeach
            </select>
        </div>
    </div>
</form>

{{-- Summary KPIs --}}
@php
    $totalBudget = collect($grid)->sum('budget');
    $totalYtd    = collect($grid)->sum('ytd');
    $totalVar    = $totalYtd - $totalBudget;
    $totalPct    = $totalBudget > 0 ? round(($totalYtd/$totalBudget)*100,1) : 0;
@endphp

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:var(--navy)"></div>
            <div class="stat-label">Total Approved Budget</div>
            <div class="stat-value" style="font-size:18px">
                {{ currency() }} {{ number_format($totalBudget,0) }}
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:var(--gold)"></div>
            <div class="stat-label">YTD Actual</div>
            <div class="stat-value" style="font-size:18px">
                {{ currency() }} {{ number_format($totalYtd,0) }}
            </div>
            <div class="stat-sub">{{ $totalPct }}% utilised</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent"
                 style="background:{{ $totalVar > 0 ? '#F43F5E' : '#10B981' }}">
            </div>
            <div class="stat-label">YTD Variance</div>
            <div class="stat-value" style="font-size:18px;
                color:{{ $totalVar > 0 ? '#F43F5E' : '#10B981' }}">
                {{ $totalVar >= 0 ? '+' : '' }}{{ currency() }} {{ number_format($totalVar,0) }}
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#6366F1"></div>
            <div class="stat-label">Remaining Budget</div>
            <div class="stat-value" style="font-size:18px">
                {{ currency() }} {{ number_format($totalBudget - $totalYtd,0) }}
            </div>
        </div>
    </div>
</div>

{{-- Monthly trend chart --}}
@if(count($grid))
<div class="chart-card mb-4">
    <div class="chart-title">Monthly Spend Trend — All Departments</div>
    <canvas id="trendChart" height="120"></canvas>
</div>
@endif

{{-- Grid table --}}
<div class="chart-card">
    <div class="chart-title">Department × Month Grid ({{ currency() }})</div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0"
               style="font-size:12px">
            <thead style="background:var(--navy);color:#fff;position:sticky;top:0">
                <tr>
                    <th style="min-width:140px">Department</th>
                    @foreach(\App\Models\BudgetActual::MONTHS as $m => $name)
                    <th class="text-center">{{ substr($name,0,3) }}</th>
                    @endforeach
                    <th class="text-end">YTD</th>
                    <th class="text-end">Budget</th>
                    <th class="text-end">Variance</th>
                    <th class="text-center">%</th>
                </tr>
            </thead>
            <tbody>
                @foreach($grid as $row)
                @php
                    $varColor = $row['variance'] > 0 ? '#F43F5E'
                        : ($row['variance'] < 0 ? '#10B981' : 'inherit');
                @endphp
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $row['dept'] }}</div>
                        <div style="font-size:10px;color:var(--slate)">
                            <a href="{{ route('actuals.index', [
                                    'period_id'     => $period->id,
                                    'department_id' => collect($departments)->firstWhere('name',$row['dept'])?->id,
                                ]) }}"
                               style="color:var(--slate)">
                                Record →
                            </a>
                        </div>
                    </td>
                    @foreach(\App\Models\BudgetActual::MONTHS as $m => $name)
                    <td class="text-end"
                        style="{{ $row['months'][$m] > 0 ? 'background:#F0FDF4;color:#065F46' : '' }}">
                        {{ $row['months'][$m] > 0 ? number_format($row['months'][$m],0) : '—' }}
                    </td>
                    @endforeach
                    <td class="text-end fw-semibold">
                        {{ number_format($row['ytd'],0) }}
                    </td>
                    <td class="text-end text-muted">
                        {{ number_format($row['budget'],0) }}
                    </td>
                    <td class="text-end fw-semibold"
                        style="color:{{ $varColor }}">
                        {{ $row['variance'] >= 0 ? '+' : '' }}{{ number_format($row['variance'],0) }}
                    </td>
                    <td class="text-center">
                        <div class="progress" style="height:6px;width:60px;margin:auto">
                            <div class="progress-bar"
                                 style="width:{{ min($row['pct'],100) }}%;
                                        background:{{ $row['pct'] > 90 ? '#F43F5E' : ($row['pct'] > 70 ? '#F59E0B' : '#10B981') }}">
                            </div>
                        </div>
                        <div style="font-size:10px;color:var(--slate)">{{ $row['pct'] }}%</div>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot style="background:#F8FAFC;font-weight:700;font-size:12px">
                <tr>
                    <td>Total</td>
                    @foreach(\App\Models\BudgetActual::MONTHS as $m => $name)
                    <td class="text-end">
                        @php $colTotal = collect($grid)->sum("months.$m"); @endphp
                        {{ $colTotal > 0 ? number_format($colTotal,0) : '—' }}
                    </td>
                    @endforeach
                    <td class="text-end">{{ number_format($totalYtd,0) }}</td>
                    <td class="text-end">{{ number_format($totalBudget,0) }}</td>
                    <td class="text-end"
                        style="color:{{ $totalVar > 0 ? '#F43F5E' : '#10B981' }}">
                        {{ $totalVar >= 0 ? '+' : '' }}{{ number_format($totalVar,0) }}
                    </td>
                    <td class="text-center">{{ $totalPct }}%</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@if(count($grid))
<script>
@php
    $monthTotals = [];
    foreach(\App\Models\BudgetActual::MONTHS as $m => $name) {
        $monthTotals[] = collect($grid)->sum("months.$m");
    }
@endphp
new Chart(document.getElementById('trendChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode(array_values(\App\Models\BudgetActual::MONTHS)) !!},
        datasets: [{
            label: 'Actual Spend ({{ currency() }})',
            data:  {!! json_encode($monthTotals) !!},
            backgroundColor: {!! json_encode(array_map(
                fn($v) => $v > 0 ? '#1B2A4A' : '#E2E8F0',
                $monthTotals
            )) !!},
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#F1F5F9' },
                ticks: {
                    font: { size: 11 },
                    callback: v => v >= 1000000
                        ? (v/1000000).toFixed(1)+'M'
                        : v >= 1000 ? (v/1000).toFixed(0)+'K' : v
                }
            },
            x: { grid: { display: false }, ticks: { font: { size: 11 } } }
        }
    }
});
</script>
@endif

@endsection
