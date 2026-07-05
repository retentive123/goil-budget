@extends('layouts.app')
@section('title', 'Executive Summary')
@section('content')

@include('reports._toolbar', [
    'title'      => 'Executive Summary',
    'exportType' => 'approved',
    'period'     => $period,
    'periods'    => $periods,
    'showDept'   => false,
])

{{-- KPI cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#10B981"></div>
            <div class="stat-label">Grand Total ({{ currency() }})</div>
            <div class="stat-value" style="font-size:20px">
                {{ number_format($grandTotal, 0) }}
            </div>
            <div class="stat-sub">Across {{ $versions->count() }} dept(s)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#10B981"></div>
            <div class="stat-label">Fully Approved</div>
            <div class="stat-value">{{ $submissionStats['approved'] }}</div>
            <div class="stat-sub">of {{ $submissionStats['total'] }} departments</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#F59E0B"></div>
            <div class="stat-label">In Review</div>
            <div class="stat-value">{{ $submissionStats['under_review'] }}</div>
            <div class="stat-sub">awaiting decisions</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#94A3B8"></div>
            <div class="stat-label">Not Started</div>
            <div class="stat-value">{{ $submissionStats['not_started'] }}</div>
            <div class="stat-sub">departments</div>
        </div>
    </div>
</div>

{{-- Charts row 1 --}}
<div class="row g-3 mb-4">

    {{-- Status donut --}}
    <div class="col-md-3">
        <div class="chart-card h-100">
            <div class="chart-title">Submission Status</div>
            <canvas id="statusDonut" height="220"></canvas>
        </div>
    </div>

    {{-- Quarterly totals --}}
    <div class="col-md-5">
        <div class="chart-card h-100">
            <div class="chart-title">Quarterly Distribution ({{ currency() }})</div>
            <canvas id="quarterBar" height="220"></canvas>
        </div>
    </div>

    {{-- Budget type pie --}}
    <div class="col-md-4">
        <div class="chart-card h-100">
            <div class="chart-title">Budget by Type</div>
            <canvas id="budgetTypePie" height="220"></canvas>
        </div>
    </div>
</div>

{{-- Department ranking bar --}}
<div class="chart-card mb-4">
    <div class="chart-title">Department Budget Rankings ({{ currency() }})</div>
    <canvas id="deptRankBar" height="120"></canvas>
</div>

{{-- Budget type breakdown table --}}
@php
$typeColors = [
    'Revenue'              => '#10B981',
    'Expense'              => '#F43F5E',
    'Revenue & Expense'    => '#14B8A6',
    'Capital Expenditure'  => '#3B82F6',
    'Assets'               => '#8B5CF6',
    'Liabilities'          => '#F59E0B',
];
@endphp
<div class="chart-card mb-4">
    <div class="chart-title">Budget by Type Breakdown</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--slate)">
                <tr>
                    <th>Budget Type</th>
                    <th class="text-end">Total ({{ currency() }})</th>
                    <th>Share of Grand Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($budgetTypeTotals as $type => $total)
                @php
                    $share = $grandTotal > 0 ? round(($total / $grandTotal) * 100, 1) : 0;
                    $color = $typeColors[$type] ?? '#64748B';
                @endphp
                <tr>
                    <td>
                        <span style="display:inline-block;width:10px;height:10px;border-radius:50%;
                                     background:{{ $color }};margin-right:6px"></span>
                        <span class="fw-semibold small">{{ $type }}</span>
                    </td>
                    <td class="text-end small fw-semibold">{{ number_format($total, 0) }}</td>
                    <td style="min-width:160px">
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height:6px">
                                <div class="progress-bar" style="width:{{ $share }}%;background:{{ $color }}"></div>
                            </div>
                            <span style="font-size:11px;color:var(--slate);min-width:36px">{{ $share }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot style="background:#F8FAFC;font-weight:700;font-size:13px">
                <tr>
                    <td>Grand Total</td>
                    <td class="text-end">{{ currency() }} {{ number_format($grandTotal, 0) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

{{-- Department table --}}
<div class="chart-card">
    <div class="chart-title">Department Breakdown</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead style="font-size:11px;text-transform:uppercase;
                          letter-spacing:.5px;color:var(--slate)">
                <tr>
                    <th>Department</th>
                    <th class="text-end">Q1 ({{ currency() }})</th>
                    <th class="text-end">Q2 ({{ currency() }})</th>
                    <th class="text-end">Q3 ({{ currency() }})</th>
                    <th class="text-end">Q4 ({{ currency() }})</th>
                    <th class="text-end">Original</th>
                    <th class="text-end">Supplementary</th>
                    <th class="text-end">Effective Total ({{ currency() }})</th>
                    <th>Share</th>
                </tr>
            </thead>
            <tbody>
                @foreach($deptTotals as $row)
                @php
                    $share = $grandTotal > 0 ? round(($row['total']/$grandTotal)*100,1) : 0;
                    $original = $row['original'] ?? $row['total'];
                    $supplementary = $row['supplementary'] ?? 0;
                @endphp
                <tr>
                    <td>
                        <div class="fw-semibold small">{{ $row['name'] }}</div>
                        <div style="font-size:10px;color:var(--slate)">{{ $row['code'] }}</div>
                    </td>
                    <td class="text-end small">{{ number_format($row['q1'],0) }}</td>
                    <td class="text-end small">{{ number_format($row['q2'],0) }}</td>
                    <td class="text-end small">{{ number_format($row['q3'],0) }}</td>
                    <td class="text-end small">{{ number_format($row['q4'],0) }}</td>
                    <td class="text-end small text-muted">{{ number_format($original,0) }}</td>
                    <td class="text-end small" style="color:{{ $supplementary > 0 ? '#10B981' : 'inherit' }}">
                        {{ $supplementary > 0 ? '+'.number_format($supplementary,0) : '—' }}
                    </td>
                    <td class="text-end small fw-semibold">{{ number_format($row['total'],0) }}</td>
                    <td style="min-width:100px">
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height:6px">
                                <div class="progress-bar"
                                     style="width:{{ $share }}%;background:var(--navy)"></div>
                            </div>
                            <span style="font-size:11px;color:var(--slate)">{{ $share }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot style="background:#F8FAFC;font-weight:700;font-size:13px">
                <tr>
                    <td>Grand Total</td>
                    <td class="text-end">{{ number_format($quarterlyTotals['q1'],0) }}</td>
                    <td class="text-end">{{ number_format($quarterlyTotals['q2'],0) }}</td>
                    <td class="text-end">{{ number_format($quarterlyTotals['q3'],0) }}</td>
                    <td class="text-end">{{ number_format($quarterlyTotals['q4'],0) }}</td>
                    <td class="text-end">{{ number_format($grandTotal - $totalSupplementary,0) }}</td>
                    <td class="text-end" style="color:#10B981">
                        +{{ number_format($totalSupplementary ?? 0,0) }}
                    </td>
                    <td class="text-end" style="color:var(--navy)">
                        {{ currency() }} {{ number_format($grandTotal,0) }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const COLORS = ['#1B2A4A','#C9A84C','#10B981','#6366F1','#F59E0B',
                '#EC4899','#14B8A6','#8B5CF6','#F97316','#06B6D4'];

// Status donut
new Chart(document.getElementById('statusDonut'), {
    type: 'doughnut',
    data: {
        labels: ['Approved','In Review','Rejected','Draft','Not Started'],
        datasets: [{
            data: [
                {{ $submissionStats['approved'] }},
                {{ $submissionStats['under_review'] }},
                {{ $submissionStats['rejected'] }},
                {{ $submissionStats['draft'] }},
                {{ $submissionStats['not_started'] }},
            ],
            backgroundColor: ['#10B981','#F59E0B','#F43F5E','#64748B','#E2E8F0'],
            borderWidth: 0,
            hoverOffset: 6,
        }]
    },
    options: {
        cutout: '65%',
        plugins: {
            legend: { position:'bottom', labels:{ font:{size:11}, padding:10, boxWidth:10 } }
        }
    }
});

// Quarterly bar
new Chart(document.getElementById('quarterBar'), {
    type: 'bar',
    data: {
        labels: ['Q1','Q2','Q3','Q4'],
        datasets: [{
            data: [
                {{ $quarterlyTotals['q1'] }},
                {{ $quarterlyTotals['q2'] }},
                {{ $quarterlyTotals['q3'] }},
                {{ $quarterlyTotals['q4'] }},
            ],
            backgroundColor: ['#1B2A4A','#C9A84C','#10B981','#6366F1'],
            borderRadius: 8,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend:{ display:false } },
        scales: {
            y: {
                beginAtZero: true,
                grid:{ color:'#F1F5F9' },
                ticks:{ font:{size:11},
                    callback: v => '{{ currency() }} '+(v>=1000000?(v/1000000).toFixed(1)+'M':v>=1000?(v/1000).toFixed(0)+'K':v)
                }
            },
            x:{ grid:{ display:false }, ticks:{ font:{size:13}, color:'#1B2A4A' } }
        }
    }
});

// Budget type pie — semantic colours per type
const TYPE_COLORS = {
    'Revenue':             '#10B981',
    'Expense':             '#F43F5E',
    'Revenue & Expense':   '#14B8A6',
    'Capital Expenditure': '#3B82F6',
    'Assets':              '#8B5CF6',
    'Liabilities':         '#F59E0B',
};
const typeLabels = {!! json_encode(array_keys($budgetTypeTotals)) !!};
const typeData   = {!! json_encode(array_values($budgetTypeTotals)) !!};
const typeBgColors = typeLabels.map(l => TYPE_COLORS[l] || '#64748B');

new Chart(document.getElementById('budgetTypePie'), {
    type: 'pie',
    data: {
        labels: typeLabels,
        datasets: [{
            data: typeData,
            backgroundColor: typeBgColors,
            borderWidth: 2,
            borderColor: '#fff',
        }]
    },
    options: {
        plugins: {
            legend:{ position:'bottom', labels:{ font:{size:10}, padding:8, boxWidth:10 } },
            tooltip: {
                callbacks: {
                    label: ctx => {
                        const total = ctx.dataset.data.reduce((a,b) => a+b, 0);
                        const pct   = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                        return ` ${ctx.label}: {{ currency() }} ${ctx.parsed.toLocaleString()} (${pct}%)`;
                    }
                }
            }
        }
    }
});

// Dept ranking horizontal bar
new Chart(document.getElementById('deptRankBar'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($deptTotals->pluck('name')->toArray()) !!},
        datasets: [
            {
                label: 'Q1', data: {!! json_encode($deptTotals->pluck('q1')->toArray()) !!},
                backgroundColor:'#1B2A4A', borderRadius:4, borderSkipped:false,
            },
            {
                label: 'Q2', data: {!! json_encode($deptTotals->pluck('q2')->toArray()) !!},
                backgroundColor:'#C9A84C', borderRadius:4, borderSkipped:false,
            },
            {
                label: 'Q3', data: {!! json_encode($deptTotals->pluck('q3')->toArray()) !!},
                backgroundColor:'#10B981', borderRadius:4, borderSkipped:false,
            },
            {
                label: 'Q4', data: {!! json_encode($deptTotals->pluck('q4')->toArray()) !!},
                backgroundColor:'#6366F1', borderRadius:4, borderSkipped:false,
            },
        ]
    },
    options: {
        responsive: true,
        scales: {
            x:{ stacked:true, grid:{ display:false }, ticks:{ font:{size:11} } },
            y:{
                stacked:true,
                beginAtZero:true,
                grid:{ color:'#F1F5F9' },
                ticks:{
                    font:{size:11},
                    callback: v => v>=1000000?(v/1000000).toFixed(1)+'M':v>=1000?(v/1000).toFixed(0)+'K':v
                }
            }
        },
        plugins: {
            legend:{ position:'top', labels:{ font:{size:11}, boxWidth:12 } }
        }
    }
});
</script>
@endsection
