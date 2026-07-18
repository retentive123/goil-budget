@extends('layouts.app')
@section('title', 'All Budgets')
@section('content')

<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
    <div>
        <h5 class="fw-bold mb-0">All Budgets</h5>
        <p class="text-muted small mb-0">
            Complete view of all department budgets across all periods.
        </p>
    </div>
    <div class="d-flex gap-2">
        @can('export reports')
        <a href="{{ route('budgets.export', request()->query()) }}"
           class="btn btn-sm btn-outline-success">
            ↓ Export Excel
        </a>
        @endcan
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="chart-card mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Period</label>
            <select name="period_id" class="form-select form-select-sm"
                    onchange="this.form.submit()">
                <option value="">All Periods</option>
                @foreach($periods as $p)
                <option value="{{ $p->id }}"
                    {{ request('period_id', $period?->id) == $p->id ? 'selected' : '' }}>
                    {{ $p->name }}
                    @if($p->status === 'open') (Active) @endif
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Dept / Station</label>
            @include('reports._dept_filter', [
                'filterName' => 'department_id',
                'selectedId' => request('department_id'),
                'allowEmpty' => true,
                'emptyLabel' => 'All Entities',
                'autoSubmit' => true,
                'selectId'   => 'allBudgetsDeptSel',
            ])
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Status</label>
            <select name="status" class="form-select form-select-sm"
                    onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="draft"        {{ request('status')==='draft'?'selected':'' }}>Draft</option>
                <option value="submitted"    {{ request('status')==='submitted'?'selected':'' }}>Submitted</option>
                <option value="under_review" {{ request('status')==='under_review'?'selected':'' }}>Under Review</option>
                <option value="approved"     {{ request('status')==='approved'?'selected':'' }}>Approved</option>
                <option value="rejected"     {{ request('status')==='rejected'?'selected':'' }}>Rejected</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control form-control-sm"
                   placeholder="Department name or code…">
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-sm w-100"
                    style="background:var(--navy);color:#fff;border-radius:8px">
                Filter
            </button>
        </div>
        @if(request()->hasAny(['department_id','status','search','period_id']))
        <div class="col-md-1">
            <a href="{{ route('budgets.index') }}"
               class="btn btn-sm btn-outline-secondary w-100">
                Clear
            </a>
        </div>
        @endif
    </div>
</form>

{{-- KPI strip --}}
@if($period)
<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="stat-card text-center">
            <div class="stat-label">Departments</div>
            <div class="stat-value">{{ $stats['total_depts'] }}</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card text-center">
            <div class="stat-accent" style="background:#10B981"></div>
            <div class="stat-label">Approved</div>
            <div class="stat-value" style="color:#10B981">{{ $stats['approved'] }}</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card text-center">
            <div class="stat-accent" style="background:#F59E0B"></div>
            <div class="stat-label">In Review</div>
            <div class="stat-value" style="color:#F59E0B">{{ $stats['in_review'] }}</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card text-center">
            <div class="stat-accent" style="background:#F43F5E"></div>
            <div class="stat-label">Rejected</div>
            <div class="stat-value" style="color:#F43F5E">{{ $stats['rejected'] }}</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card text-center">
            <div class="stat-accent" style="background:#64748B"></div>
            <div class="stat-label">Not Started</div>
            <div class="stat-value" style="color:#64748B">{{ $stats['not_started'] }}</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card text-center">
            <div class="stat-accent" style="background:var(--navy)"></div>
            <div class="stat-label">Total Approved</div>
            <div class="stat-value" style="font-size:14px">
                GHS {{ number_format($stats['total_value'],0) }}
            </div>
        </div>
    </div>
</div>

{{-- Health bar --}}
@php
    $total = max(1,$stats['total_depts']);
    $ap = round(($stats['approved']/$total)*100);
    $rv = round(($stats['in_review']/$total)*100);
    $re = round(($stats['rejected']/$total)*100);
    $dr = round(($stats['draft']/$total)*100);
    $ns = round(($stats['not_started']/$total)*100);
@endphp
<div class="health-bar-wrap mb-4">
    <div class="d-flex justify-content-between mb-2">
        <div class="health-bar-title">Period Completion — {{ $period->name }}</div>
        <div style="font-size:12px;color:var(--gold);font-weight:600">
            {{ $ap }}% approved
        </div>
    </div>
    <div class="health-segments">
        @if($ap)<div class="health-segment" style="width:{{ $ap }}%;background:#10B981"></div>@endif
        @if($rv)<div class="health-segment" style="width:{{ $rv }}%;background:var(--gold)"></div>@endif
        @if($re)<div class="health-segment" style="width:{{ $re }}%;background:#F43F5E"></div>@endif
        @if($dr)<div class="health-segment" style="width:{{ $dr }}%;background:#64748B"></div>@endif
        @if($ns)<div class="health-segment" style="width:{{ $ns }}%;background:rgba(255,255,255,.15)"></div>@endif
    </div>
    <div class="health-legend mt-2">
        <div class="health-legend-item"><div class="health-legend-dot" style="background:#10B981"></div>Approved ({{ $stats['approved'] }})</div>
        <div class="health-legend-item"><div class="health-legend-dot" style="background:var(--gold)"></div>In Review ({{ $stats['in_review'] }})</div>
        <div class="health-legend-item"><div class="health-legend-dot" style="background:#F43F5E"></div>Rejected ({{ $stats['rejected'] }})</div>
        <div class="health-legend-item"><div class="health-legend-dot" style="background:#64748B"></div>Draft ({{ $stats['draft'] }})</div>
        <div class="health-legend-item"><div class="health-legend-dot" style="background:rgba(255,255,255,.3)"></div>Not Started ({{ $stats['not_started'] }})</div>
    </div>
</div>
@endif

{{-- View toggle --}}
<div class="d-flex gap-2 mb-3">
    <button onclick="showView('list')" id="btn_list"
            class="btn btn-sm"
            style="background:var(--navy);color:#fff;border-radius:6px;font-size:12px">
        List View
    </button>
    <button onclick="showView('matrix')" id="btn_matrix"
            class="btn btn-sm btn-outline-secondary"
            style="border-radius:6px;font-size:12px">
        Matrix View
    </button>
</div>

{{-- ── LIST VIEW ── --}}
<div id="view_list">
    <div class="chart-card">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead style="font-size:11px;text-transform:uppercase;
                              letter-spacing:.5px;color:var(--slate)">
                    <tr>
                        <th>Department</th>
                        <th>Period</th>
                        <th class="text-center">Version</th>
                        <th>Status</th>
                        <th class="text-end">Budget Total (GHS)</th>
                        <th>Submitted By</th>
                        <th>Submitted At</th>
                        <th class="text-center">Versions</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($budgets as $v)
                    <tr>
                        <td>
                            <div style="font-size:13px;font-weight:600;color:var(--navy)">
                                {{ $v->department->name }}
                            </div>
                            <div style="font-size:10px;color:var(--slate)">
                                {{ $v->department->code }}
                                @if($v->department->budget_type !== 'expense')
                                <span style="background:#D1FAE5;color:#065F46;border-radius:4px;
                                             padding:0px 5px;font-size:9px;font-weight:600">
                                    {{ strtoupper($v->department->budget_type) }}
                                </span>
                                @endif
                            </div>
                        </td>
                        <td class="small">{{ $v->period->name }}</td>
                        <td class="text-center">
                            <span style="background:var(--navy);color:#fff;border-radius:50%;
                                         width:24px;height:24px;display:inline-flex;
                                         align-items:center;justify-content:center;
                                         font-size:11px;font-weight:700">
                                {{ $v->version_number }}
                            </span>
                        </td>
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
                            {{ number_format($v->lineItems->sum('total_amount'), 2) }}
                        </td>
                        <td class="small">
                            {{ $v->submittedBy?->name ?? '—' }}
                        </td>
                        <td class="small text-muted">
                            {{ $v->submitted_at?->format('d M Y') ?? '—' }}
                            @if($v->submitted_at)
                            <div style="font-size:10px">
                                {{ $v->submitted_at->diffForHumans() }}
                            </div>
                            @endif
                        </td>
                        <td class="text-center small text-muted">
                            @php
                                $versionCount = \App\Models\BudgetVersion::where('budget_period_id', $v->budget_period_id)
                                    ->where('department_id', $v->department_id)->count();
                            @endphp
                            {{ $versionCount }}
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('budgets.show', $v) }}"
                                   class="btn btn-sm"
                                   style="background:var(--navy);color:#fff;
                                          font-size:11px;border-radius:6px;
                                          padding:3px 12px">
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
                        <td colspan="9" class="text-center text-muted py-5">
                            No budgets found matching the selected filters.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3 px-2">{{ $budgets->links() }}</div>
    </div>
</div>

{{-- ── MATRIX VIEW ── --}}
<div id="view_matrix" style="display:none">
    <div class="chart-card">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead style="font-size:11px;text-transform:uppercase;
                              letter-spacing:.5px;color:var(--slate)">
                    <tr>
                        <th style="min-width:160px">Department</th>
                        <th>Type</th>
                        <th>Latest Status</th>
                        <th class="text-end">Budget Total (GHS)</th>
                        <th class="text-end">Actual (GHS)</th>
                        <th>Utilisation</th>
                        <th class="text-center">Versions</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($deptMatrix as $row)
                    @php
                        $dept    = $row['dept'];
                        $latest  = $row['latest'];
                        $total   = $row['total'];
                        $actual  = $period
                            ? \App\Models\BudgetActual::where('department_id', $dept->id)
                                ->where('budget_period_id', $period->id)
                                ->where('status','confirmed')->sum('amount')
                            : 0;
                        $utilPct = $total > 0 ? round(($actual/$total)*100,1) : 0;
                        $status  = $latest?->status ?? 'not_started';
                    @endphp
                    <tr>
                        <td>
                            <div style="font-size:13px;font-weight:600;color:var(--navy)">
                                {{ $dept->name }}
                            </div>
                            <div style="font-size:10px;color:var(--slate)">{{ $dept->code }}</div>
                        </td>
                        <td>
                            <span style="padding:2px 8px;border-radius:20px;font-size:10px;
                                         font-weight:600;
                                         background:{{ match($dept->budget_type) {
                                             'revenue' => '#D1FAE5',
                                             'both'    => '#DBEAFE',
                                             default   => '#FEE2E2'
                                         } }};
                                         color:{{ match($dept->budget_type) {
                                             'revenue' => '#065F46',
                                             'both'    => '#1E40AF',
                                             default   => '#991B1B'
                                         } }}">
                                {{ ucfirst($dept->budget_type) }}
                            </span>
                        </td>
                        <td>
                            <span style="padding:2px 10px;border-radius:20px;font-size:11px;
                                         font-weight:600;
                                         background:{{
                                            match($status) {
                                                'approved'     => '#D1FAE5',
                                                'rejected'     => '#FEE2E2',
                                                'submitted'    => '#DBEAFE',
                                                'under_review' => '#FEF3C7',
                                                'draft'        => '#F1F5F9',
                                                default        => '#F8FAFC'
                                            }
                                         }};
                                         color:{{
                                            match($status) {
                                                'approved'     => '#065F46',
                                                'rejected'     => '#991B1B',
                                                'submitted'    => '#1E40AF',
                                                'under_review' => '#92400E',
                                                'draft'        => '#475569',
                                                default        => '#94A3B8'
                                            }
                                         }}">
                                {{ ucfirst(str_replace('_',' ',$status)) }}
                            </span>
                        </td>
                        <td class="text-end small fw-semibold">
                            {{ $total > 0 ? number_format($total,0) : '—' }}
                        </td>
                        <td class="text-end small" style="color:#10B981">
                            {{ $actual > 0 ? number_format($actual,0) : '—' }}
                        </td>
                        <td style="min-width:140px">
                            @if($total > 0)
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:6px">
                                    <div class="progress-bar"
                                         style="width:{{ min($utilPct,100) }}%;
                                                background:{{ $utilPct>90?'#F43F5E':($utilPct>70?'#F59E0B':'#10B981') }}">
                                    </div>
                                </div>
                                <span style="font-size:11px;color:var(--slate)">{{ $utilPct }}%</span>
                            </div>
                            @else
                            <span style="color:#94A3B8;font-size:11px">—</span>
                            @endif
                        </td>
                        <td class="text-center small text-muted">
                            {{ $row['versions']->count() }}
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                @if($latest)
                                <a href="{{ route('budgets.show', $latest) }}"
                                   class="btn btn-sm"
                                   style="background:var(--navy);color:#fff;
                                          font-size:11px;border-radius:6px;padding:3px 12px">
                                    View
                                </a>
                                @endif
                                <a href="{{ route('budgets.department', $dept) }}"
                                   class="btn btn-sm btn-outline-secondary"
                                   style="font-size:11px;border-radius:6px;padding:3px 10px">
                                    All Versions
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function showView(view) {
    ['list','matrix'].forEach(v => {
        document.getElementById('view_'+v).style.display = v===view ? '' : 'none';
        const btn = document.getElementById('btn_'+v);
        btn.style.background   = v===view ? 'var(--navy)' : '';
        btn.style.color        = v===view ? '#fff'        : '';
        btn.className = v===view
            ? 'btn btn-sm'
            : 'btn btn-sm btn-outline-secondary';
        btn.style.borderRadius = '6px';
        btn.style.fontSize     = '12px';
    });
}
</script>

@endsection
