@extends('layouts.app')
@section('title', 'Variance Analysis')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Variance Analysis</h5>
        <p class="text-muted small mb-0">
            <a href="{{ route('reports.index') }}" class="text-muted">Reports</a>
            / Variance
        </p>
    </div>
    @can('export reports')
    <a href="{{ route('reports.export.variance', request()->query()) }}"
       class="btn btn-sm btn-outline-success">Export Excel</a>
    @endcan
</div>

<form method="GET" class="chart-card mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Period</label>
            <select name="period_id" class="form-select form-select-sm">
                @foreach($periods as $p)
                <option value="{{ $p->id }}"
                    {{ request('period_id',$period?->id)==$p->id?'selected':'' }}>
                    {{ $p->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Department</label>
            <select name="department_id" class="form-select form-select-sm">
                <option value="">All</option>
                @foreach($departments as $d)
                <option value="{{ $d->id }}"
                    {{ request('department_id')==$d->id?'selected':'' }}>
                    {{ $d->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Show</label>
            <select name="variance_filter" class="form-select form-select-sm">
                <option value="all"   {{ $varianceFilter=='all'  ?'selected':'' }}>All</option>
                <option value="over"  {{ $varianceFilter=='over' ?'selected':'' }}>Overspend only</option>
                <option value="under" {{ $varianceFilter=='under'?'selected':'' }}>Underspend only</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Min Variance %</label>
            <input type="number" name="min_variance"
                   value="{{ $minVariancePct }}"
                   class="form-control form-control-sm"
                   min="0" max="100" step="1"
                   placeholder="0">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm w-100"
                    style="background:var(--navy);color:#fff;border-radius:8px">
                Apply
            </button>
        </div>
    </div>
</form>

{{-- Summary --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-accent" style="background:var(--navy)"></div>
            <div class="stat-label">Total Budget</div>
            <div class="stat-value" style="font-size:18px">
                {{ currency() }} {{ number_format($summary['total_budget'],0) }}
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-accent" style="background:#64748B"></div>
            <div class="stat-label">Total Actual</div>
            <div class="stat-value" style="font-size:18px">
                {{ currency() }} {{ number_format($summary['total_actual'],0) }}
            </div>
            <div class="stat-sub">Actuals module coming soon</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-accent" style="background:#10B981"></div>
            <div class="stat-label">On Budget Lines</div>
            <div class="stat-value">{{ $summary['on_budget'] }}</div>
        </div>
    </div>
</div>

@if(empty($varianceData))
<div class="chart-card text-center py-5 text-muted">
    No data matching the selected filters.
</div>
@else

{{-- Variance horizontal bar chart --}}
<div class="chart-card mb-4">
    <div class="chart-title">Variance by Account Code (Top 20)</div>
    <canvas id="varianceBar" height="140"></canvas>
</div>

{{-- Tables by category --}}
@foreach($varianceData as $catName => $codes)
<div class="chart-card mb-3">
    <div class="chart-title">{{ $catName }}</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Account</th>
                    <th class="text-end">Original Budget</th>
                    <th class="text-end">Supplementary</th>
                    <th class="text-end">Effective Budget</th>
                    <th class="text-end">Actual</th>
                    <th class="text-end">Variance</th>
                    <th class="text-end">%</th>
                </tr>
            </thead>
            <tbody>
                @foreach($codes as $code => $row)
                <tr>
                    <td><code>{{ $code }}</code></td>
                    <td>{{ $row['name'] }}</td>
                    <td class="text-end">{{ number_format($row['original'] ?? $row['budget'], 2) }}</td>
                    <td class="text-end" style="color:{{ $row['supplementary'] > 0 ? '#92400E' : 'inherit' }}">
                        {{ $row['supplementary'] > 0 ? '+'.number_format($row['supplementary'], 2) : '—' }}
                    </td>
                    <td class="text-end fw-bold">{{ number_format($row['budget'], 2) }}</td>
                    <td class="text-end" style="color:#10B981">{{ number_format($row['actual'], 2) }}</td>
                    <td class="text-end" style="color:{{ $row['variance'] > 0 ? '#F43F5E' : '#10B981' }}">
                        {{ $row['variance'] >= 0 ? '+' : '' }}{{ number_format($row['variance'], 2) }}
                    </td>
                    <td class="text-end">{{ $row['pct'] }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endforeach

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
@php
    $allRows = [];
    foreach($varianceData as $cat => $codes) {
        foreach($codes as $code => $row) {
            $allRows[] = ['code'=>$code,'variance'=>$row['variance'],'budget'=>$row['budget']];
        }
    }
    usort($allRows, fn($a,$b) => abs($b['variance']) <=> abs($a['variance']));
    $top20 = array_slice($allRows, 0, 20);
@endphp
new Chart(document.getElementById('varianceBar'), {
    type: 'bar',
    data: {
        labels: {!! json_encode(array_column($top20,'code')) !!},
        datasets: [
            {
                label: 'Budget',
                data:  {!! json_encode(array_column($top20,'budget')) !!},
                backgroundColor: '#E2E8F0',
                borderRadius: 4, borderSkipped: false,
            },
            {
                label: 'Variance',
                data:  {!! json_encode(array_column($top20,'variance')) !!},
                backgroundColor: {!! json_encode(array_map(
                    fn($r) => $r['variance'] > 0 ? '#FCA5A5' : '#6EE7B7',
                    $top20
                )) !!},
                borderRadius: 4, borderSkipped: false,
            }
        ]
    },
    options: {
        responsive:true,
        plugins:{ legend:{ position:'top', labels:{font:{size:11},boxWidth:12} } },
        scales:{
            y:{ beginAtZero:true, grid:{color:'#F1F5F9'},
                ticks:{ font:{size:11},
                    callback:v=>v>=1000000?(v/1000000).toFixed(1)+'M':v>=1000?(v/1000).toFixed(0)+'K':v
                }
            },
            x:{ grid:{display:false}, ticks:{font:{size:10}} }
        }
    }
});
</script>
@endif
@endsection
