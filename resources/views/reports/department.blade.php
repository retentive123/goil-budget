@extends('layouts.app')
@section('title', 'Department Drill-down')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Department Drill-down</h5>
        <p class="text-muted small mb-0">
            <a href="{{ route('reports.index') }}" class="text-muted">Reports</a>
            / Department
        </p>
    </div>
    @can('export reports')
    <a href="{{ route('reports.export.approved', request()->query()) }}"
       class="btn btn-sm btn-outline-success">Export Excel</a>
    @endcan
</div>

{{-- Filters --}}
<form method="GET" class="chart-card mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Period</label>
            <select name="period_id" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach($periods as $p)
                <option value="{{ $p->id }}"
                    {{ request('period_id', $period?->id) == $p->id ? 'selected' : '' }}>
                    {{ $p->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Department</label>
            <select name="department_id" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach($departments as $d)
                <option value="{{ $d->id }}"
                    {{ request('department_id', $department?->id) == $d->id ? 'selected' : '' }}>
                    {{ $d->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Category</label>
            <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Categories</option>
                @foreach($categories as $c)
                <option value="{{ $c->id }}"
                    {{ request('category_id') == $c->id ? 'selected' : '' }}>
                    {{ $c->name }}
                </option>
                @endforeach
            </select>
        </div>
    </div>
</form>

@if(!$version)
<div class="chart-card text-center py-5 text-muted">
    No approved budget found for {{ $department?->name }} in {{ $period?->name }}.
</div>
@else

{{-- KPI strip --}}
@php
    $totalBudget = $quarterSums['total'];
    $totalOriginal = $quarterSums['original'] ?? $quarterSums['total'];
    $totalSupplementary = $quarterSums['supplementary'] ?? 0;
@endphp
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:var(--navy)"></div>
            <div class="stat-label">Effective Budget</div>
            <div class="stat-value" style="font-size:20px">
                {{ currency() }} {{ number_format($totalBudget,0) }}
            </div>
            @if($totalSupplementary > 0)
            <div class="stat-sub" style="color:#10B981">
                +{{ number_format($totalSupplementary,0) }} supplementary
            </div>
            @endif
        </div>
    </div>
    @foreach(['q1'=>'Q1','q2'=>'Q2','q3'=>'Q3','q4'=>'Q4'] as $k => $label)
    <div class="col-md-2">
        <div class="stat-card">
            <div class="stat-label">{{ $label }}</div>
            <div class="stat-value" style="font-size:16px">
                {{ number_format($quarterSums[$k],0) }}
            </div>
            <div class="stat-sub">
                @php $pct = $totalBudget > 0 ? round(($quarterSums[$k]/$totalBudget)*100,1) : 0; @endphp
                {{ $pct }}% of total
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Charts --}}
<div class="row g-3 mb-4">

    {{-- Quarterly bar --}}
    <div class="col-md-5">
        <div class="chart-card h-100">
            <div class="chart-title">Quarterly Split</div>
            <canvas id="quarterSplit" height="200"></canvas>
        </div>
    </div>

    {{-- Category donut --}}
    <div class="col-md-3">
        <div class="chart-card h-100">
            <div class="chart-title">By Category</div>
            <canvas id="catDonut" height="200"></canvas>
        </div>
    </div>

    {{-- YoY trend --}}
    <div class="col-md-4">
        <div class="chart-card h-100">
            <div class="chart-title">Year-over-Year Trend</div>
            @if(count($yoyData) > 1)
            <canvas id="yoyLine" height="200"></canvas>
            @else
            <div class="text-muted small text-center py-4">
                Only one period available.
            </div>
            @endif
        </div>
    </div>

</div>

{{-- Line items by category --}}
@foreach($byCategory as $catName => $catData)
<div class="chart-card mb-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="chart-title mb-0">{{ $catName }}</div>
        <div style="font-size:13px;font-weight:600;color:var(--navy)">
            {{ currency() }} {{ number_format($catData['total'],2) }}
            @php
                $catSupp = $catData['supplementary'] ?? 0;
            @endphp
            @if($catSupp > 0)
            <span style="font-size:11px;color:#10B981;font-weight:400">
                (+{{ number_format($catSupp,2) }} supp.)
            </span>
            @endif
            <span style="font-size:11px;color:var(--slate);font-weight:400">
                &nbsp;(Q1: {{ number_format($catData['q1'],0) }}
                / Q2: {{ number_format($catData['q2'],0) }}
                / Q3: {{ number_format($catData['q3'],0) }}
                / Q4: {{ number_format($catData['q4'],0) }})
            </span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead style="font-size:11px;text-transform:uppercase;
                          letter-spacing:.5px;color:var(--slate)">
                <tr>
                    <th>Code</th>
                    <th>Account Name</th>
                    <th class="text-end">Q1</th>
                    <th class="text-end">Q2</th>
                    <th class="text-end">Q3</th>
                    <th class="text-end">Q4</th>
                    <th class="text-end">Supplementary</th>
                    <th class="text-end">Total</th>
                    <th>Split</th>
                </tr>
            </thead>
            <tbody>
                @foreach($catData['items'] as $item)
                @php
                    $itemSupp = $item->approvedSupplementaryTotal();
                    $itemEffective = $item->effectiveBudget();
                    $itemPct = $quarterSums['total'] > 0
                        ? round(($itemEffective / $quarterSums['total']) * 100, 1) : 0;
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('reports.code-explorer', ['account_code_id'=>$item->account_code_id,'period_id'=>$period->id]) }}"
                           style="color:var(--navy);font-weight:600;font-size:12px;
                                  font-family:monospace">
                            {{ $item->accountCode->code }}
                        </a>
                    </td>
                    <td class="small">{{ $item->accountCode->name }}</td>
                    <td class="text-end small">{{ number_format($item->q1_amount, 2) }}</td>
                    <td class="text-end small">{{ number_format($item->q2_amount, 2) }}</td>
                    <td class="text-end small">{{ number_format($item->q3_amount, 2) }}</td>
                    <td class="text-end small">{{ number_format($item->q4_amount, 2) }}</td>
                    <td class="text-end small" style="color:{{ $itemSupp > 0 ? '#10B981' : 'inherit' }}">
                        {{ $itemSupp > 0 ? '+'.number_format($itemSupp, 2) : '—' }}
                    </td>
                    <td class="text-end small fw-semibold">
                        {{ number_format($itemEffective, 2) }}
                    </td>
                    <td style="min-width:80px">
                        <div class="progress" style="height:5px">
                            <div class="progress-bar"
                                 style="width:{{ $itemPct }}%;background:var(--navy)"></div>
                        </div>
                        <div style="font-size:10px;color:var(--slate)">{{ $itemPct }}%</div>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot style="background:#F8FAFC;font-weight:700;font-size:12px">
                <tr>
                    <td colspan="2">Category Total</td>
                    <td class="text-end">{{ number_format($catData['q1'], 2) }}</td>
                    <td class="text-end">{{ number_format($catData['q2'], 2) }}</td>
                    <td class="text-end">{{ number_format($catData['q3'], 2) }}</td>
                    <td class="text-end">{{ number_format($catData['q4'], 2) }}</td>
                    <td class="text-end" style="color:{{ $catSupp > 0 ? '#10B981' : 'inherit' }}">
                        {{ $catSupp > 0 ? '+'.number_format($catSupp, 2) : '—' }}
                    </td>
                    <td class="text-end">{{ number_format($catData['total'], 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endforeach

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const COLORS = ['#1B2A4A','#C9A84C','#10B981','#6366F1','#F59E0B',
                '#EC4899','#14B8A6','#8B5CF6','#F97316','#06B6D4'];

// Quarterly split
new Chart(document.getElementById('quarterSplit'), {
    type: 'bar',
    data: {
        labels: ['Q1','Q2','Q3','Q4'],
        datasets: [{
            data: [{{ $quarterSums['q1'] }},{{ $quarterSums['q2'] }},{{ $quarterSums['q3'] }},{{ $quarterSums['q4'] }}],
            backgroundColor: ['#1B2A4A','#C9A84C','#10B981','#6366F1'],
            borderRadius: 8, borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend:{ display:false } },
        scales: {
            y:{ beginAtZero:true, grid:{color:'#F1F5F9'},
                ticks:{ font:{size:11},
                    callback:v=>v>=1000000?(v/1000000).toFixed(1)+'M':v>=1000?(v/1000).toFixed(0)+'K':v
                }
            },
            x:{ grid:{display:false} }
        }
    }
});

// Category donut
@php $catNames=[]; $catVals=[]; foreach($byCategory as $c=>$d){$catNames[]=$c; $catVals[]=$d['total'];} @endphp
new Chart(document.getElementById('catDonut'), {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($catNames) !!},
        datasets: [{
            data: {!! json_encode($catVals) !!},
            backgroundColor: COLORS,
            borderWidth: 2, borderColor: '#fff',
        }]
    },
    options: {
        cutout: '60%',
        plugins: { legend:{ position:'bottom', labels:{ font:{size:10}, padding:8, boxWidth:10 } } }
    }
});

// YoY Line
@if(count($yoyData) > 1)
new Chart(document.getElementById('yoyLine'), {
    type: 'line',
    data: {
        labels: {!! json_encode(array_column($yoyData,'name')) !!},
        datasets: [{
            label: 'Total Budget ({{ currency() }})',
            data:  {!! json_encode(array_column($yoyData,'total')) !!},
            borderColor: '#1B2A4A',
            backgroundColor: 'rgba(27,42,74,.08)',
            borderWidth: 2,
            pointBackgroundColor: '#C9A84C',
            pointRadius: 5,
            fill: true,
            tension: 0.4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend:{ display:false } },
        scales: {
            y:{ beginAtZero:true, grid:{color:'#F1F5F9'},
                ticks:{ font:{size:11},
                    callback:v=>v>=1000000?(v/1000000).toFixed(1)+'M':v>=1000?(v/1000).toFixed(0)+'K':v
                }
            },
            x:{ grid:{display:false}, ticks:{font:{size:11}} }
        }
    }
});
@endif
</script>
@endif
@endsection
