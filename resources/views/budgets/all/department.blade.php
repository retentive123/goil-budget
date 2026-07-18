@extends('layouts.app')
@section('title', $department->name . ' — All Budget Versions')
@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('budgets.index') }}"
       class="text-muted text-decoration-none small">← All Budgets</a>
    <span class="text-muted">/</span>
    <span class="small">{{ $department->name }}</span>
</div>

{{-- Dept header --}}
<div class="chart-card mb-4">
    <div class="d-flex align-items-start justify-content-between">
        <div>
            <div style="font-size:20px;font-weight:700;color:var(--navy)">
                {{ $department->name }}
            </div>
            <div style="font-size:13px;color:var(--slate);margin-top:4px">
                Code: <strong>{{ $department->code }}</strong>
                &middot; Budget Type:
                <span style="padding:2px 8px;border-radius:20px;font-size:11px;
                             font-weight:600;
                             background:{{ match($department->budget_type) {
                                 'revenue' => '#D1FAE5',
                                 'both'    => '#DBEAFE',
                                 default   => '#FEE2E2'
                             } }};
                             color:{{ match($department->budget_type) {
                                 'revenue' => '#065F46',
                                 'both'    => '#1E40AF',
                                 default   => '#991B1B'
                             } }}">
                    {{ ucfirst($department->budget_type) }}
                </span>
                &middot; {{ $department->users()->count() }} user(s)
            </div>
        </div>
        <a href="{{ route('reports.department', ['department_id'=>$department->id]) }}"
           class="btn btn-sm btn-outline-secondary"
           style="border-radius:8px">
            📊 Department Report
        </a>
    </div>
</div>

{{-- Year-over-year summary --}}
@if($allPeriodSummary->count())
<div class="chart-card mb-4">
    <div class="chart-title">Budget History Across All Periods</div>
    <div class="row g-3 mb-3">
        @foreach($allPeriodSummary as $periodRow)
        @php
            $util = $periodRow['budget'] > 0
                ? round(($periodRow['actual']/$periodRow['budget'])*100,1) : 0;
        @endphp
        <div class="col-md-3">
            <div style="background:#F8FAFC;border:1px solid var(--border);
                        border-radius:10px;padding:14px">
                <div style="font-size:11px;color:var(--slate);margin-bottom:4px">
                    {{ $periodRow['period']->name }}
                </div>
                <div style="font-size:15px;font-weight:700;color:var(--navy)">
                    GHS {{ number_format($periodRow['budget'],0) }}
                </div>
                <div style="font-size:11px;color:#10B981">
                    Actual: GHS {{ number_format($periodRow['actual'],0) }}
                </div>
                <div class="progress mt-2" style="height:4px">
                    <div class="progress-bar"
                         style="width:{{ min($util,100) }}%;
                                background:{{ $util>90?'#F43F5E':($util>70?'#F59E0B':'#10B981') }}">
                    </div>
                </div>
                <div style="font-size:10px;color:var(--slate);margin-top:3px">
                    {{ $util }}% utilised
                    &middot;
                    <span style="padding:1px 6px;border-radius:4px;font-size:9px;
                                 font-weight:600;
                                 background:{{
                                    match($periodRow['latest']?->status) {
                                        'approved'=>'#D1FAE5','rejected'=>'#FEE2E2',
                                        'submitted'=>'#DBEAFE',default=>'#F1F5F9'
                                    }
                                 }};
                                 color:{{
                                    match($periodRow['latest']?->status) {
                                        'approved'=>'#065F46','rejected'=>'#991B1B',
                                        'submitted'=>'#1E40AF',default=>'#475569'
                                    }
                                 }}">
                        {{ ucfirst($periodRow['latest']?->status ?? '—') }}
                    </span>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- YoY chart --}}
    <canvas id="yoyChart" height="80"></canvas>
</div>
@endif

{{-- Period filter --}}
<form method="GET" class="d-flex gap-2 mb-4">
    <select name="period_id" class="form-select form-select-sm" style="max-width:200px"
            onchange="this.form.submit()">
        <option value="">All Periods</option>
        @foreach($periods as $p)
        <option value="{{ $p->id }}"
            {{ $period?->id == $p->id ? 'selected' : '' }}>
            {{ $p->name }}
        </option>
        @endforeach
    </select>
</form>

{{-- Versions table --}}
<div class="chart-card">
    <div class="chart-title">
        Budget Versions
        @if($period) — {{ $period->name }} @endif
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead style="font-size:11px;text-transform:uppercase;
                          letter-spacing:.5px;color:var(--slate)">
                <tr>
                    <th>Version</th>
                    <th>Period</th>
                    <th>Status</th>
                    <th class="text-end">Budget Total (GHS)</th>
                    <th>Submitted By</th>
                    <th>Submitted At</th>
                    <th>Submission Notes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($versions as $v)
                <tr>
                    <td>
                        <span style="background:var(--navy);color:#fff;border-radius:50%;
                                     width:28px;height:28px;display:inline-flex;
                                     align-items:center;justify-content:center;
                                     font-size:12px;font-weight:700">
                            {{ $v->version_number }}
                        </span>
                    </td>
                    <td class="small">{{ $v->period->name }}</td>
                    <td>
                        <span style="padding:2px 10px;border-radius:20px;font-size:11px;
                                     font-weight:600;
                                     background:{{
                                        match($v->status) {
                                            'approved'     => '#D1FAE5',
                                            'rejected'     => '#FEE2E2',
                                            'submitted'    => '#DBEAFE',
                                            'under_review' => '#FEF3C7',
                                            default        => '#F1F5F9'
                                        }
                                     }};
                                     color:{{
                                        match($v->status) {
                                            'approved'     => '#065F46',
                                            'rejected'     => '#991B1B',
                                            'submitted'    => '#1E40AF',
                                            'under_review' => '#92400E',
                                            default        => '#475569'
                                        }
                                     }}">
                            {{ ucfirst(str_replace('_',' ',$v->status)) }}
                        </span>
                    </td>
                    <td class="text-end small fw-semibold">
                        {{ number_format($v->lineItems->sum('total_amount'),2) }}
                    </td>
                    <td class="small">{{ $v->submittedBy?->name ?? '—' }}</td>
                    <td class="small text-muted">
                        {{ $v->submitted_at?->format('d M Y H:i') ?? '—' }}
                    </td>
                    <td class="small text-muted" style="max-width:200px">
                        {{ $v->submission_notes ? Str::limit($v->submission_notes,60) : '—' }}
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('budgets.show', $v) }}"
                               class="btn btn-sm"
                               style="background:var(--navy);color:#fff;
                                      font-size:11px;border-radius:6px;padding:3px 12px">
                                View
                            </a>
                            <a href="{{ route('approvals.history', $v) }}"
                               class="btn btn-sm btn-outline-secondary"
                               style="font-size:11px;border-radius:6px;padding:3px 10px">
                                History
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        No budget versions found for this selection.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($allPeriodSummary->count())
<script>
new Chart(document.getElementById('yoyChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($allPeriodSummary->pluck('period.name')->toArray()) !!},
        datasets: [
            {
                label: 'Budget',
                data:  {!! json_encode($allPeriodSummary->pluck('budget')->toArray()) !!},
                backgroundColor: '#1B2A4A',
                borderRadius: 6,
                borderSkipped: false,
            },
            {
                label: 'Actual',
                data:  {!! json_encode($allPeriodSummary->pluck('actual')->toArray()) !!},
                backgroundColor: '#10B981',
                borderRadius: 6,
                borderSkipped: false,
            },
        ]
    },
    options: {
        responsive: true,
        plugins: { legend:{ position:'top', labels:{ font:{size:11}, boxWidth:12 } } },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color:'#F1F5F9' },
                ticks: {
                    font: { size:10 },
                    callback: v => v>=1000000?(v/1000000).toFixed(1)+'M':v>=1000?(v/1000).toFixed(0)+'K':v
                }
            },
            x: { grid:{ display:false }, ticks:{ font:{ size:11 } } }
        }
    }
});
</script>
@endif

@endsection
