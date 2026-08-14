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
@php
    $deptRows    = $deptMatrix->filter(fn($r) => $r['dept']->entity_type !== 'service_station')->values();
    $stationRows = $deptMatrix->filter(fn($r) => $r['dept']->entity_type === 'service_station')->values();
@endphp

<div id="view_matrix" style="display:none">

    {{-- Matrix tab bar + page-size selector --}}
    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
        <button id="mTab_depts" onclick="switchMatrixTab('depts')"
                class="btn btn-sm"
                style="background:#1B2A4A;color:#fff;border-radius:8px;font-size:12px">
            <i class="bi bi-building me-1"></i>
            Departments <span style="background:rgba(255,255,255,.2);border-radius:10px;
                                     padding:1px 7px;margin-left:3px">{{ $deptRows->count() }}</span>
        </button>
        <button id="mTab_stations" onclick="switchMatrixTab('stations')"
                class="btn btn-sm btn-outline-secondary"
                style="border-radius:8px;font-size:12px">
            <i class="bi bi-fuel-pump me-1"></i>
            Service Stations <span id="stationBadge"
                                   style="background:#E2E8F0;border-radius:10px;
                                          padding:1px 7px;margin-left:3px">{{ $stationRows->count() }}</span>
        </button>
        <div class="ms-auto d-flex align-items-center gap-2">
            <label style="font-size:12px;color:var(--slate);white-space:nowrap">Show:</label>
            <select id="matrixPageSize" onchange="onPageSizeChange(this.value)"
                    class="form-select form-select-sm" style="width:auto;font-size:12px">
                <option value="15">15 per page</option>
                <option value="25">25 per page</option>
                <option value="50">50 per page</option>
                <option value="0">Show all</option>
            </select>
        </div>
    </div>

    {{-- ── Departments panel ── --}}
    <div id="mPanel_depts">
        <div class="chart-card p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--slate)">
                        <tr>
                            <th style="min-width:180px;padding:12px 16px">Department</th>
                            <th>Type</th>
                            <th>Latest Status</th>
                            <th class="text-end">Budget (GHS)</th>
                            <th class="text-end">Actual (GHS)</th>
                            <th style="min-width:130px">Utilisation</th>
                            <th class="text-center">Ver.</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="mBody_depts">
                        @forelse($deptRows as $row)
                        @php
                            $dept    = $row['dept'];
                            $latest  = $row['latest'];
                            $total   = $row['total'];
                            $actual  = $row['actual'];  // pre-computed in controller (no N+1)
                            $utilPct = $total > 0 ? round(($actual/$total)*100,1) : 0;
                            $status  = $latest?->status ?? 'not_started';
                        @endphp
                        @include('budgets.all._matrix_row', compact('dept','latest','total','actual','utilPct','status','row'))
                        @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No departments found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div id="mPager_depts" class="d-flex gap-1 justify-content-center mt-3 flex-wrap"></div>
        <div id="mInfo_depts" class="text-center mt-1"
             style="font-size:11px;color:var(--slate)"></div>
    </div>

    {{-- ── Service Stations panel ── --}}
    <div id="mPanel_stations" style="display:none">
        <div class="chart-card p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--slate)">
                        <tr>
                            <th style="min-width:180px;padding:12px 16px">Service Station</th>
                            <th>Type</th>
                            <th>Latest Status</th>
                            <th class="text-end">Budget (GHS)</th>
                            <th class="text-end">Actual (GHS)</th>
                            <th style="min-width:130px">Utilisation</th>
                            <th class="text-center">Ver.</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="mBody_stations">
                        @forelse($stationRows as $row)
                        @php
                            $dept    = $row['dept'];
                            $latest  = $row['latest'];
                            $total   = $row['total'];
                            $actual  = $row['actual'];  // pre-computed in controller (no N+1)
                            $utilPct = $total > 0 ? round(($actual/$total)*100,1) : 0;
                            $status  = $latest?->status ?? 'not_started';
                        @endphp
                        @include('budgets.all._matrix_row', compact('dept','latest','total','actual','utilPct','status','row'))
                        @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No service stations found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div id="mPager_stations" class="d-flex gap-1 justify-content-center mt-3 flex-wrap"></div>
        <div id="mInfo_stations" class="text-center mt-1"
             style="font-size:11px;color:var(--slate)"></div>
    </div>
</div>

<script>
// ── View toggle (list / matrix) ──────────────────────────────────────
function showView(view) {
    ['list','matrix'].forEach(v => {
        document.getElementById('view_'+v).style.display = v===view ? '' : 'none';
        const btn = document.getElementById('btn_'+v);
        btn.style.background   = v===view ? '#1B2A4A' : '';
        btn.style.color        = v===view ? '#fff'    : '';
        btn.className = v===view ? 'btn btn-sm' : 'btn btn-sm btn-outline-secondary';
        btn.style.borderRadius = '6px';
        btn.style.fontSize     = '12px';
    });
    if (view === 'matrix') renderMatrix();
}

// ── Matrix pagination state ──────────────────────────────────────────
const mState = { tab: 'depts', page: { depts: 1, stations: 1 }, ps: 15 };

function switchMatrixTab(tab) {
    mState.tab = tab;
    ['depts','stations'].forEach(t => {
        document.getElementById('mPanel_'+t).style.display = t===tab ? '' : 'none';
        const btn = document.getElementById('mTab_'+t);
        if (t === tab) {
            btn.style.background = '#1B2A4A'; btn.style.color = '#fff';
            btn.className = 'btn btn-sm';
        } else {
            btn.style.background = ''; btn.style.color = '';
            btn.className = 'btn btn-sm btn-outline-secondary';
        }
        btn.style.borderRadius = '8px'; btn.style.fontSize = '12px';
    });
    renderMatrix();
}

function onPageSizeChange(val) {
    mState.ps = parseInt(val) || 0; // 0 = show all
    mState.page.depts    = 1;
    mState.page.stations = 1;
    renderMatrix();
}

function goMatrixPage(tab, page) {
    mState.page[tab] = page;
    renderMatrix();
    // Scroll to top of the panel
    document.getElementById('mPanel_'+tab).scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function renderMatrix() {
    ['depts','stations'].forEach(tab => {
        const rows      = Array.from(document.querySelectorAll('#mBody_'+tab+' tr'));
        const total     = rows.length;
        const ps        = mState.ps;
        const page      = mState.page[tab];
        const showAll   = ps === 0;
        const totalPages = showAll ? 1 : Math.ceil(total / ps);

        // Show / hide rows
        rows.forEach((r, i) => {
            r.style.display = (showAll || (i >= (page-1)*ps && i < page*ps)) ? '' : 'none';
        });

        // Pager
        const pagerEl = document.getElementById('mPager_'+tab);
        const infoEl  = document.getElementById('mInfo_'+tab);
        pagerEl.innerHTML = '';

        if (!showAll && totalPages > 1) {
            // Prev
            const prev = makePagerBtn('‹', () => goMatrixPage(tab, page-1), page === 1);
            pagerEl.appendChild(prev);

            // Page numbers (show max 7, with ellipsis)
            const range = pageRange(page, totalPages);
            range.forEach(p => {
                if (p === '…') {
                    const el = document.createElement('span');
                    el.textContent = '…';
                    el.style.cssText = 'padding:4px 6px;font-size:12px;color:var(--slate)';
                    pagerEl.appendChild(el);
                } else {
                    pagerEl.appendChild(makePagerBtn(p, () => goMatrixPage(tab, p), false, p === page));
                }
            });

            // Next
            pagerEl.appendChild(makePagerBtn('›', () => goMatrixPage(tab, page+1), page === totalPages));
        }

        // Info text
        if (showAll) {
            infoEl.textContent = total > 0 ? `Showing all ${total} rows` : '';
        } else {
            const from = Math.min((page-1)*ps+1, total);
            const to   = Math.min(page*ps, total);
            infoEl.textContent = total > 0 ? `${from}–${to} of ${total}` : '';
        }
    });
}

function makePagerBtn(label, onclick, disabled, active) {
    const btn = document.createElement('button');
    btn.textContent = label;
    btn.onclick     = disabled ? null : onclick;
    btn.disabled    = disabled;
    btn.style.cssText = `padding:4px 10px;font-size:12px;border-radius:6px;border:1px solid #CBD5E1;
                          cursor:${disabled?'default':'pointer'};
                          background:${active?'#1B2A4A':'#fff'};
                          color:${active?'#fff':disabled?'#CBD5E1':'#1B2A4A'}`;
    return btn;
}

function pageRange(current, total) {
    if (total <= 7) return Array.from({length:total},(_,i)=>i+1);
    const pages = [1];
    if (current > 3)          pages.push('…');
    for (let p = Math.max(2,current-1); p <= Math.min(total-1,current+1); p++) pages.push(p);
    if (current < total-2)    pages.push('…');
    pages.push(total);
    return pages;
}

// Init
renderMatrix();
</script>

@endsection
