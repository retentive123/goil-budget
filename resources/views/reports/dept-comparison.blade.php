@extends('layouts.app')
@section('title', 'Department Comparison')
@section('content')

<div class="mb-4">
    <h5 class="fw-bold mb-0">Department Comparison</h5>
    <p class="text-muted small">
        <a href="{{ route('reports.index') }}" class="text-muted">Reports</a>
        / Department Comparison
    </p>
</div>

<form method="GET" class="chart-card mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Period</label>
            <select name="period_id" class="form-select form-select-sm">
                @foreach($periods as $p)
                <option value="{{ $p->id }}"
                    {{ request('period_id',$period?->id) == $p->id ? 'selected':'' }}>
                    {{ $p->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold mb-1">
                Dept / Station (select up to 5)
            </label>
            @include('reports._dept_filter', [
                'filterName' => 'dept_ids',
                'selectedId' => $selectedDeptIds,
                'multiple'   => true,
                'maxItems'   => 5,
                'emptyLabel' => 'Search departments & stations…',
                'selectId'   => 'rptCompDeptSel',
            ])
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm w-100"
                    style="background:var(--navy);color:#fff;border-radius:8px">
                Compare
            </button>
        </div>
    </div>
</form>

@if(count($compData))

{{-- Totals bar chart --}}
<div class="chart-card mb-4">
    <div class="chart-title">Total Budget by Department</div>
    <canvas id="totalBar" height="120"></canvas>
</div>

{{-- Stacked quarterly chart --}}
<div class="chart-card mb-4">
    <div class="chart-title">Quarterly Breakdown</div>
    <canvas id="stackedBar" height="140"></canvas>
</div>

{{-- Summary table --}}
<div class="chart-card">
    <div class="chart-title">Side-by-Side Summary</div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0">
            <thead style="background:var(--navy);color:#fff;font-size:12px">
                <tr>
                    <th>Metric</th>
                    @foreach($compData as $row)
                    <th class="text-center">{{ $row['code'] }}<br>
                        <small style="font-weight:400;opacity:.8">{{ $row['dept'] }}</small>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="fw-semibold small">Original Budget ({{ currency() }})</td>
                    @foreach($compData as $row)
                    <td class="text-end small">
                        {{ number_format($row['original'] ?? $row['total'], 0) }}
                    </td>
                    @endforeach
                </tr>
                <tr>
                    <td class="fw-semibold small" style="color:#10B981;">Supplementary ({{ currency() }})</td>
                    @foreach($compData as $row)
                    <td class="text-end small" style="color:{{ ($row['supplementary'] ?? 0) > 0 ? '#10B981' : 'inherit' }}">
                        {{ ($row['supplementary'] ?? 0) > 0 ? '+'.number_format($row['supplementary'], 0) : '—' }}
                    </td>
                    @endforeach
                </tr>
                <tr style="border-top:2px solid #1B2A4A;">
                    <td class="fw-semibold small" style="font-size:13px;">Effective Total ({{ currency() }})</td>
                    @foreach($compData as $row)
                    <td class="text-end small fw-bold" style="font-size:13px;color:var(--navy)">
                        {{ number_format($row['total'], 0) }}
                    </td>
                    @endforeach
                </tr>
                @foreach(['q1'=>'Q1','q2'=>'Q2','q3'=>'Q3','q4'=>'Q4'] as $k=>$label)
                <tr>
                    <td class="small text-muted">{{ $label }}</td>
                    @foreach($compData as $row)
                    <td class="text-end small">{{ number_format($row[$k], 0) }}</td>
                    @endforeach
                </tr>
                @endforeach
                <tr style="border-top:2px solid #1B2A4A;">
                    <td class="fw-semibold small">Grand Totals</td>
                    @foreach($compData as $row)
                    <td class="text-end small fw-semibold" style="color:var(--navy)">
                        {{ number_format($row['total'], 0) }}
                    </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
const labels  = {!! json_encode(array_column($compData,'dept')) !!};
const totals  = {!! json_encode(array_column($compData,'total')) !!};
const originals = {!! json_encode(array_column($compData,'original') ?? array_column($compData,'total')) !!};
const q1data  = {!! json_encode(array_column($compData,'q1')) !!};
const q2data  = {!! json_encode(array_column($compData,'q2')) !!};
const q3data  = {!! json_encode(array_column($compData,'q3')) !!};
const q4data  = {!! json_encode(array_column($compData,'q4')) !!};
const palette = ['#1B2A4A','#C9A84C','#10B981','#6366F1','#F59E0B'];

// Total bar
new Chart(document.getElementById('totalBar'), {
    type: 'bar',
    data: {
        labels,
        datasets: [{
            label: 'Effective Total ({{ currency() }})',
            data: totals,
            backgroundColor: palette,
            borderRadius: 8, borderSkipped: false,
        }]
    },
    options: {
        responsive:true,
        plugins:{ legend:{display:false} },
        scales:{
            y:{ beginAtZero:true, grid:{color:'#F1F5F9'},
                ticks:{ callback:v=>v>=1000000?(v/1000000).toFixed(1)+'M':v>=1000?(v/1000).toFixed(0)+'K':v }
            },
            x:{ grid:{display:false} }
        }
    }
});

// Stacked quarterly
new Chart(document.getElementById('stackedBar'), {
    type: 'bar',
    data: {
        labels,
        datasets: [
            { label:'Q1', data:q1data, backgroundColor:'#1B2A4A', borderRadius:4, borderSkipped:false },
            { label:'Q2', data:q2data, backgroundColor:'#C9A84C', borderRadius:4, borderSkipped:false },
            { label:'Q3', data:q3data, backgroundColor:'#10B981', borderRadius:4, borderSkipped:false },
            { label:'Q4', data:q4data, backgroundColor:'#6366F1', borderRadius:4, borderSkipped:false },
        ]
    },
    options: {
        responsive:true,
        scales:{
            x:{ stacked:true, grid:{display:false} },
            y:{ stacked:true, beginAtZero:true, grid:{color:'#F1F5F9'},
                ticks:{ callback:v=>v>=1000000?(v/1000000).toFixed(1)+'M':v>=1000?(v/1000).toFixed(0)+'K':v }
            }
        },
        plugins:{ legend:{ position:'top', labels:{font:{size:11},boxWidth:12} } }
    }
});
</script>

@else
<div class="chart-card text-center py-5 text-muted">
    Select departments and click Compare.
</div>
@endif
@endsection
