@extends('layouts.app')
@section('title', 'Flexed Budget')
@section('content')

<div class="mb-4">
    <h5 class="fw-bold mb-0">Flexed Budget</h5>
    <p class="text-muted small">
        <a href="{{ route('reports.index') }}" class="text-muted">Reports</a>
        / Flexed Budget
    </p>
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
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Dept / Station</label>
            @include('reports._dept_filter', [
                'selectedId' => request('department_id'),
                'selectId'   => 'rptFlexedDeptSel',
            ])
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">
                Activity Level: <strong id="actLabel">{{ $activityLevel }}%</strong>
            </label>
            <input type="range" name="activity_level" id="actSlider"
                   value="{{ $activityLevel }}"
                   min="50" max="150" step="5"
                   class="form-range"
                   oninput="document.getElementById('actLabel').textContent=this.value+'%'">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm w-100"
                    style="background:var(--navy);color:#fff;border-radius:8px">
                Apply
            </button>
        </div>
    </div>
    <div class="small text-muted mt-2">
        Flexed budget = Approved budget × {{ $activityLevel / 100 }}.
        Values below 100% represent a budget reduction; above 100% an increase.
    </div>
</form>

@if(empty($flexed))
<div class="chart-card text-center py-5 text-muted">
    No approved budgets found.
</div>
@else

{{-- Summary totals --}}
@php
    $origTotal  = 0;
    $flexTotal  = 0;
    foreach($flexed as $cat=>$codes) {
        foreach($codes as $code=>$vals) {
            $origTotal += $vals['original'];
            $flexTotal += $vals['flexed'];
        }
    }
    $diffTotal = $flexTotal - $origTotal;
@endphp

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-accent" style="background:var(--slate)"></div>
            <div class="stat-label">Original Budget</div>
            <div class="stat-value" style="font-size:18px">
                {{ currency() }} {{ number_format($origTotal,0) }}
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-accent" style="background:var(--navy)"></div>
            <div class="stat-label">Flexed Budget ({{ $activityLevel }}%)</div>
            <div class="stat-value" style="font-size:18px">
                {{ currency() }} {{ number_format($flexTotal,0) }}
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-accent"
                 style="background:{{ $diffTotal >= 0 ? '#F43F5E' : '#10B981' }}">
            </div>
            <div class="stat-label">Difference</div>
            <div class="stat-value" style="font-size:18px;
                color:{{ $diffTotal >= 0 ? '#F43F5E' : '#10B981' }}">
                {{ $diffTotal >= 0 ? '+' : '' }}{{ currency() }} {{ number_format($diffTotal,0) }}
            </div>
        </div>
    </div>
</div>

{{-- Chart --}}
<div class="chart-card mb-4">
    <div class="chart-title">Original vs Flexed by Category</div>
    <canvas id="flexChart" height="120"></canvas>
</div>

{{-- Tables --}}
@foreach($flexed as $catName => $codes)
<div class="chart-card mb-3">
    <div class="chart-title">{{ $catName }}</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead style="font-size:11px;text-transform:uppercase;
                          letter-spacing:.5px;color:var(--slate)">
                <tr>
                    <th>Code</th>
                    <th>Account</th>
                    <th class="text-end">Q1 Orig</th>
                    <th class="text-end">Q1 Flexed</th>
                    <th class="text-end">Q2 Orig</th>
                    <th class="text-end">Q2 Flexed</th>
                    <th class="text-end">Q3 Orig</th>
                    <th class="text-end">Q3 Flexed</th>
                    <th class="text-end">Q4 Orig</th>
                    <th class="text-end">Q4 Flexed</th>
                    <th class="text-end">Total Orig</th>
                    <th class="text-end">Total Flexed</th>
                    <th class="text-end">Diff</th>
                </tr>
            </thead>
            <tbody>
                @foreach($codes as $code => $vals)
                <tr>
                    <td style="font-family:monospace;font-weight:600;font-size:12px">
                        {{ $code }}
                    </td>
                    <td class="small">{{ $vals['name'] }}</td>
                    <td class="text-end small text-muted">{{ number_format($vals['q1'],0) }}</td>
                    <td class="text-end small">{{ number_format($vals['q1_flexed'],0) }}</td>
                    <td class="text-end small text-muted">{{ number_format($vals['q2'],0) }}</td>
                    <td class="text-end small">{{ number_format($vals['q2_flexed'],0) }}</td>
                    <td class="text-end small text-muted">{{ number_format($vals['q3'],0) }}</td>
                    <td class="text-end small">{{ number_format($vals['q3_flexed'],0) }}</td>
                    <td class="text-end small text-muted">{{ number_format($vals['q4'],0) }}</td>
                    <td class="text-end small">{{ number_format($vals['q4_flexed'],0) }}</td>
                    <td class="text-end small text-muted fw-semibold">
                        {{ number_format($vals['original'],0) }}
                    </td>
                    <td class="text-end small fw-semibold" style="color:var(--navy)">
                        {{ number_format($vals['flexed'],0) }}
                    </td>
                    <td class="text-end small fw-semibold"
                        style="color:{{ $vals['difference'] >= 0 ? '#F43F5E' : '#10B981' }}">
                        {{ $vals['difference'] >= 0 ? '+' : '' }}{{ number_format($vals['difference'],0) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endforeach

<script>
@php
    $catOrig  = [];
    $catFlex  = [];
    $catNames = [];
    foreach($flexed as $cat => $codes) {
        $catNames[] = $cat;
        $o = 0; $f = 0;
        foreach($codes as $vals) { $o += $vals['original']; $f += $vals['flexed']; }
        $catOrig[] = $o;
        $catFlex[] = $f;
    }
@endphp
new Chart(document.getElementById('flexChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($catNames) !!},
        datasets: [
            {
                label: 'Original',
                data:  {!! json_encode($catOrig) !!},
                backgroundColor: '#E2E8F0',
                borderRadius: 4, borderSkipped: false,
            },
            {
                label: 'Flexed ({{ $activityLevel }}%)',
                data:  {!! json_encode($catFlex) !!},
                backgroundColor: '#1B2A4A',
                borderRadius: 4, borderSkipped: false,
            }
        ]
    },
    options: {
        responsive:true,
        plugins:{ legend:{ position:'top', labels:{font:{size:11},boxWidth:12} } },
        scales:{
            y:{ beginAtZero:true, grid:{color:'#F1F5F9'},
                ticks:{ callback:v=>v>=1000000?(v/1000000).toFixed(1)+'M':v>=1000?(v/1000).toFixed(0)+'K':v }
            },
            x:{ grid:{display:false}, ticks:{font:{size:11}} }
        }
    }
});
</script>
@endif
@endsection
