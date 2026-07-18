@extends('layouts.app')
@section('title', 'Virement Report')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Virement Report</h5>
        <p class="text-muted small mb-0">
            <a href="{{ route('reports.index') }}" class="text-muted">Reports</a>
            / Virement
        </p>
    </div>
    @can('export reports')
    <a href="{{ route('reports.export.virement', request()->query()) }}"
       class="btn btn-sm btn-outline-success">Export Excel</a>
    @endcan
</div>

<form method="GET" class="chart-card mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Period</label>
            <select name="period_id" class="form-select form-select-sm">
                <option value="">All Periods</option>
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
                'emptyLabel' => 'All',
                'selectId'   => 'rptVirementDeptSel',
            ])
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="pending"  {{ request('status')=='pending' ?'selected':'' }}>Pending</option>
                <option value="approved" {{ request('status')=='approved'?'selected':'' }}>Approved</option>
                <option value="rejected" {{ request('status')=='rejected'?'selected':'' }}>Rejected</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm w-100"
                    style="background:var(--navy);color:#fff;border-radius:8px">
                Filter
            </button>
        </div>
    </div>
</form>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:var(--navy)"></div>
            <div class="stat-label">Total Requests</div>
            <div class="stat-value">{{ $stats['total'] }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#10B981"></div>
            <div class="stat-label">Approved</div>
            <div class="stat-value">{{ $stats['approved'] }}</div>
            <div class="stat-sub">{{ currency() }} {{ number_format($stats['value'],0) }} transferred</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#F59E0B"></div>
            <div class="stat-label">Pending</div>
            <div class="stat-value">{{ $stats['pending'] }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#F43F5E"></div>
            <div class="stat-label">Rejected</div>
            <div class="stat-value">{{ $stats['rejected'] }}</div>
        </div>
    </div>
</div>

{{-- Status donut --}}
@if($stats['total'])
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="chart-card">
            <div class="chart-title">Status Breakdown</div>
            <canvas id="virDonut" height="200"></canvas>
        </div>
    </div>
    <div class="col-md-8">
        <div class="chart-card">
            <div class="chart-title">Virement Amounts by Department</div>
            <canvas id="virDeptBar" height="200"></canvas>
        </div>
    </div>
</div>
@endif

{{-- Table --}}
<div class="chart-card">
    <div class="chart-title">All Virement Requests</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead style="font-size:11px;text-transform:uppercase;
                          letter-spacing:.5px;color:var(--slate)">
                <tr>
                    <th>Dept</th>
                    <th>From Account</th>
                    <th>To Account</th>
                    <th class="text-end">Amount ({{ currency() }})</th>
                    <th>Justification</th>
                    <th>Status</th>
                    <th>Requested By</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($virements as $v)
                <tr>
                    <td class="small fw-semibold">{{ $v->department->name }}</td>
                    <td class="small">
                        <code>{{ $v->fromLineItem->accountCode->code }}</code>
                        <div style="font-size:10px;color:var(--slate)">
                            {{ $v->fromLineItem->accountCode->name }}
                        </div>
                    </td>
                    <td class="small">
                        <code>{{ $v->toLineItem->accountCode->code }}</code>
                        <div style="font-size:10px;color:var(--slate)">
                            {{ $v->toLineItem->accountCode->name }}
                        </div>
                    </td>
                    <td class="text-end small fw-semibold">
                        {{ number_format($v->amount,2) }}
                    </td>
                    <td class="small" style="max-width:180px">
                        {{ Str::limit($v->justification,60) }}
                    </td>
                    <td>
                        <span style="padding:2px 8px;border-radius:20px;font-size:11px;
                                     font-weight:600;
                                     background:{{ match($v->status) {
                                        'approved'=>'#D1FAE5',
                                        'rejected'=>'#FEE2E2',
                                        default=>'#FEF3C7'
                                     } }};
                                     color:{{ match($v->status) {
                                        'approved'=>'#065F46',
                                        'rejected'=>'#991B1B',
                                        default=>'#92400E'
                                     } }}">
                            {{ ucfirst($v->status) }}
                        </span>
                    </td>
                    <td class="small">{{ $v->requestedBy->name }}</td>
                    <td class="small text-muted">
                        {{ $v->created_at->format('d M Y') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        No virement requests found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($stats['total'])
<script>
new Chart(document.getElementById('virDonut'), {
    type: 'doughnut',
    data: {
        labels: ['Approved','Pending','Rejected'],
        datasets: [{
            data:  [{{ $stats['approved'] }},{{ $stats['pending'] }},{{ $stats['rejected'] }}],
            backgroundColor: ['#10B981','#F59E0B','#F43F5E'],
            borderWidth: 0,
        }]
    },
    options: {
        cutout:'65%',
        plugins:{ legend:{ position:'bottom', labels:{font:{size:11},padding:10,boxWidth:10} } }
    }
});

@php
    $virByDept = $virements->where('status','approved')
        ->groupBy('department.name')
        ->map(fn($g)=>$g->sum('amount'))
        ->sortDesc();
@endphp
new Chart(document.getElementById('virDeptBar'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($virByDept->keys()->toArray()) !!},
        datasets: [{
            label: 'Approved Amount ({{ currency() }})',
            data:  {!! json_encode($virByDept->values()->toArray()) !!},
            backgroundColor: '#1B2A4A',
            borderRadius: 6, borderSkipped: false,
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
</script>
@endif
@endsection
