@extends('layouts.app')
@section('title', 'Budget Utilisation')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Budget Utilisation</h5>
        <p class="text-muted small mb-0">
            <a href="{{ route('reports.index') }}" class="text-muted">Reports</a>
            / Utilisation
        </p>
    </div>
    @can('export reports')
    <a href="{{ route('reports.export.utilisation', request()->query()) }}"
       class="btn btn-sm btn-outline-success">Export Excel</a>
    @endcan
</div>

<form method="GET" class="chart-card mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Period</label>
            <select name="period_id" class="form-select form-select-sm"
                    onchange="this.form.submit()">
                @foreach($periods as $p)
                <option value="{{ $p->id }}"
                    {{ request('period_id',$period?->id)==$p->id?'selected':'' }}>
                    {{ $p->name }}
                </option>
                @endforeach
            </select>
        </div>
    </div>
</form>

{{-- Status summary --}}
<div class="row g-3 mb-4">
    @php
        $critical = $utilisation->where('status','critical')->count();
        $warning  = $utilisation->where('status','warning')->count();
        $healthy  = $utilisation->where('status','healthy')->count();
    @endphp
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-accent" style="background:#F43F5E"></div>
            <div class="stat-label">Critical (>90%)</div>
            <div class="stat-value" style="color:#F43F5E">{{ $critical }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-accent" style="background:#F59E0B"></div>
            <div class="stat-label">Warning (70–90%)</div>
            <div class="stat-value" style="color:#F59E0B">{{ $warning }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-accent" style="background:#10B981"></div>
            <div class="stat-label">Healthy (<70%)</div>
            <div class="stat-value" style="color:#10B981">{{ $healthy }}</div>
        </div>
    </div>
</div>

{{-- Utilisation gauge chart --}}
@if($utilisation->count())
<div class="chart-card mb-4">
    <div class="chart-title">Utilisation by Department</div>
    <canvas id="utilisationBar" height="120"></canvas>
</div>

{{-- Detail cards --}}
<div class="row g-3">
    @foreach($utilisation as $row)
    <div class="col-md-6">
        <div class="chart-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <div class="fw-semibold small">{{ $row['department'] }}</div>
                    <div style="font-size:11px;color:var(--slate)">{{ $row['code'] }}</div>
                </div>
                <span style="padding:3px 10px;border-radius:20px;font-size:11px;
                             font-weight:600;
                             background:{{ match($row['status']) {
                                'critical' => '#FEE2E2',
                                'warning'  => '#FEF3C7',
                                default    => '#D1FAE5'
                             } }};
                             color:{{ match($row['status']) {
                                'critical' => '#991B1B',
                                'warning'  => '#92400E',
                                default    => '#065F46'
                             } }}">
                    {{ $row['utilisation_pct'] }}% used
                </span>
            </div>
            <div class="progress mb-2" style="height:8px;border-radius:4px">
                <div class="progress-bar" role="progressbar"
                     style="width:{{ min($row['utilisation_pct'],100) }}%;
                            background:{{ match($row['status']) {
                                'critical' => '#F43F5E',
                                'warning'  => '#F59E0B',
                                default    => '#10B981'
                            } }};border-radius:4px">
                </div>
            </div>
            <div class="d-flex justify-content-between"
                 style="font-size:11px;color:var(--slate)">
                <span>Approved: {{ currency() }} {{ number_format($row['approved'],0) }}</span>
                <span>Remaining: {{ currency() }} {{ number_format($row['remaining'],0) }}</span>
            </div>
        </div>
    </div>
    @endforeach
</div>

<script>
new Chart(document.getElementById('utilisationBar'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($utilisation->pluck('department')->toArray()) !!},
        datasets: [
            {
                label: 'Utilised %',
                data:  {!! json_encode($utilisation->pluck('utilisation_pct')->toArray()) !!},
                backgroundColor: {!! json_encode($utilisation->map(fn($r) =>
                    match($r['status']) {
                        'critical' => '#F43F5E',
                        'warning'  => '#F59E0B',
                        default    => '#10B981'
                    }
                )->toArray()) !!},
                borderRadius: 6, borderSkipped: false,
            }
        ]
    },
    options: {
        responsive:true,
        plugins:{ legend:{display:false} },
        scales:{
            y:{
                beginAtZero:true, max:100,
                grid:{ color:'#F1F5F9' },
                ticks:{ callback: v => v+'%', font:{size:11} }
            },
            x:{ grid:{display:false}, ticks:{font:{size:11}} }
        }
    }
});
</script>
@else
<div class="chart-card text-center py-5 text-muted">
    No approved budgets found for this period.
</div>
@endif
@endsection
