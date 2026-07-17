@extends('layouts.app')
@section('title', 'Year-over-Year Comparison')
@section('content')

<div class="mb-4">
    <h5 class="fw-bold mb-0">Year-over-Year Comparison</h5>
    <p class="text-muted small">
        <a href="{{ route('reports.index') }}" class="text-muted">Reports</a> / YoY
    </p>
</div>

{{-- Filters --}}
<form method="GET" class="chart-card mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">
                Period A <span style="color:var(--slate)">(Base)</span>
            </label>
            <select name="period_a" class="form-select form-select-sm">
                @foreach($periods as $p)
                <option value="{{ $p->id }}"
                    {{ request('period_a', $periodA?->id) == $p->id ? 'selected' : '' }}>
                    {{ $p->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">
                Period B <span style="color:var(--slate)">(Compare)</span>
            </label>
            <select name="period_b" class="form-select form-select-sm">
                @foreach($periods as $p)
                <option value="{{ $p->id }}"
                    {{ request('period_b', $periodB?->id) == $p->id ? 'selected' : '' }}>
                    {{ $p->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Dept / Station</label>
            @include('reports._dept_filter', [
                'selectedId' => request('department_id'),
                'selectId'   => 'rptYoyDeptSel',
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

@if($comparison && isset($comparison['rows']) && count($comparison['rows']))

{{-- ── Grand total KPI cards ── --}}
@php
    $budgetChangeTotal = $comparison['budget_change'];
    $actualChangeTotal = $comparison['actual_change'];
    $budgetChangePctTotal = $comparison['budget_total_a'] > 0
        ? round(($budgetChangeTotal / $comparison['budget_total_a']) * 100, 1) : 0;
    $actualChangePctTotal = $comparison['actual_total_a'] > 0
        ? round(($actualChangeTotal / $comparison['actual_total_a']) * 100, 1) : 0;
    $utilA = $comparison['budget_total_a'] > 0
        ? round(($comparison['actual_total_a'] / $comparison['budget_total_a']) * 100, 1) : 0;
    $utilB = $comparison['budget_total_b'] > 0
        ? round(($comparison['actual_total_b'] / $comparison['budget_total_b']) * 100, 1) : 0;
@endphp

<div class="row g-3 mb-4">

    {{-- Period A --}}
    <div class="col-md-3">
        <div class="chart-card h-100"
             style="border-top:4px solid var(--slate)">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;
                        letter-spacing:.5px;color:var(--slate);margin-bottom:8px">
                {{ $periodA->name }} — Budget
            </div>
            <div style="font-size:22px;font-weight:700;color:var(--navy)">
                GHS {{ number_format($comparison['budget_total_a'], 0) }}
            </div>

            @if($comparison['supplementary_total_a'] > 0)
        <div style="font-size:11px;color:#92400E;margin-top:2px">
            (Original: {{ number_format($comparison['original_total_a'],0) }}
             + Suppl: {{ number_format($comparison['supplementary_total_a'],0) }})
        </div>
        @endif

            <div style="font-size:12px;color:#10B981;margin-top:4px">
                Actual: GHS {{ number_format($comparison['actual_total_a'], 0) }}
            </div>
            <div style="font-size:11px;color:var(--slate);margin-top:2px">
                Utilisation: {{ $utilA }}%
            </div>
            <div class="progress mt-2" style="height:4px">
                <div class="progress-bar"
                     style="width:{{ min($utilA,100) }}%;
                            background:{{ $utilA>90?'#F43F5E':($utilA>70?'#F59E0B':'#10B981') }}">
                </div>
            </div>
        </div>
    </div>

    {{-- Period B --}}
    <div class="col-md-3">
        <div class="chart-card h-100"
             style="border-top:4px solid var(--navy)">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;
                        letter-spacing:.5px;color:var(--navy);margin-bottom:8px">
                {{ $periodB->name }} — Budget
            </div>
            <div style="font-size:22px;font-weight:700;color:var(--navy)">
                GHS {{ number_format($comparison['budget_total_b'], 0) }}
            </div>
            <div style="font-size:12px;color:#10B981;margin-top:4px">
                Actual: GHS {{ number_format($comparison['actual_total_b'], 0) }}
            </div>
            <div style="font-size:11px;color:var(--slate);margin-top:2px">
                Utilisation: {{ $utilB }}%
            </div>
            <div class="progress mt-2" style="height:4px">
                <div class="progress-bar"
                     style="width:{{ min($utilB,100) }}%;
                            background:{{ $utilB>90?'#F43F5E':($utilB>70?'#F59E0B':'#10B981') }}">
                </div>
            </div>
        </div>
    </div>

    {{-- Budget change --}}
    <div class="col-md-3">
        <div class="chart-card h-100"
             style="border-top:4px solid {{ $budgetChangeTotal >= 0 ? '#F43F5E' : '#10B981' }}">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;
                        letter-spacing:.5px;color:var(--slate);margin-bottom:8px">
                Budget Change
            </div>
            <div style="font-size:22px;font-weight:700;
                        color:{{ $budgetChangeTotal >= 0 ? '#F43F5E' : '#10B981' }}">
                {{ $budgetChangeTotal >= 0 ? '+' : '' }}GHS {{ number_format($budgetChangeTotal, 0) }}
            </div>
            <div style="font-size:12px;color:var(--slate);margin-top:4px">
                {{ $budgetChangePctTotal >= 0 ? '+' : '' }}{{ $budgetChangePctTotal }}%
                from {{ $periodA->name }}
            </div>
            <div style="font-size:11px;margin-top:6px">
                @if($budgetChangeTotal > 0)
                    <span style="color:#F43F5E">↑ Budget increased</span>
                @elseif($budgetChangeTotal < 0)
                    <span style="color:#10B981">↓ Budget decreased</span>
                @else
                    <span style="color:var(--slate)">→ No change</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Actual change --}}
    <div class="col-md-3">
        <div class="chart-card h-100"
             style="border-top:4px solid {{ $actualChangeTotal >= 0 ? '#F43F5E' : '#10B981' }}">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;
                        letter-spacing:.5px;color:var(--slate);margin-bottom:8px">
                Actual Spend Change
            </div>
            <div style="font-size:22px;font-weight:700;
                        color:{{ $actualChangeTotal >= 0 ? '#F43F5E' : '#10B981' }}">
                {{ $actualChangeTotal >= 0 ? '+' : '' }}GHS {{ number_format($actualChangeTotal, 0) }}
            </div>
            <div style="font-size:12px;color:var(--slate);margin-top:4px">
                {{ $actualChangePctTotal >= 0 ? '+' : '' }}{{ $actualChangePctTotal }}%
                from {{ $periodA->name }}
            </div>
            <div style="font-size:11px;margin-top:6px">
                @if($actualChangeTotal > 0)
                    <span style="color:#F43F5E">↑ Spending increased</span>
                @elseif($actualChangeTotal < 0)
                    <span style="color:#10B981">↓ Spending decreased</span>
                @else
                    <span style="color:var(--slate)">→ No change</span>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- ── Full code table ── --}}
@php
    $tableRows = array_map(
        fn($r) => array_diff_key($r, array_flip(['budget_a_q','budget_b_q','actual_a_q','actual_b_q'])),
        $comparison['rows']
    );
    $yoyTypes = array_values(array_unique(array_filter(array_column($tableRows, 'line_type'))));
    sort($yoyTypes);
@endphp

<div class="chart-card" id="yoyTableCard">
    {{-- Header row: title + export + search --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="chart-title mb-0">
            Full Code-Level Comparison
            <span class="text-muted fw-normal" style="font-size:12px" id="rowCount"></span>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                        data-bs-toggle="dropdown" style="font-size:12px">
                    <i class="fas fa-download me-1"></i>Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:13px">
                    <li>
                        <a class="dropdown-item" href="#" onclick="exportYOY('csv');return false">
                            <i class="fas fa-file-csv me-2 text-success"></i>CSV
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#" onclick="exportYOY('json');return false">
                            <i class="fas fa-file-code me-2 text-primary"></i>JSON
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item" href="#" id="copyBtn"
                           onclick="exportYOY('copy');return false">
                            <i class="fas fa-copy me-2 text-muted"></i>Copy (TSV)
                        </a>
                    </li>
                </ul>
            </div>
            <input type="text" id="codeSearch"
                   class="form-control form-control-sm"
                   placeholder="Search code, account or category…"
                   style="width:240px">
        </div>
    </div>

    {{-- View toggle + page size --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="d-flex gap-2">
            <button type="button" onclick="showView('budget')" id="btn_budget"
                    class="btn btn-sm"
                    style="background:var(--navy);color:#fff;border-radius:6px;font-size:12px">
                Budget View
            </button>
            <button type="button" onclick="showView('actual')" id="btn_actual"
                    class="btn btn-sm btn-outline-secondary"
                    style="border-radius:6px;font-size:12px">
                Actual View
            </button>
            <button type="button" onclick="showView('combined')" id="btn_combined"
                    class="btn btn-sm btn-outline-secondary"
                    style="border-radius:6px;font-size:12px">
                Combined View
            </button>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="small text-muted">Show</span>
            <select id="yoyPageSize" class="form-select form-select-sm" style="width:auto">
                <option value="5">5</option>
                <option value="25">25</option>
                <option value="50" selected>50</option>
                <option value="100">100</option>
                <option value="all">All</option>
            </select>
            <span class="small text-muted">per page</span>
        </div>
    </div>

    {{-- Type filter tabs --}}
    <div class="d-flex gap-2 mb-3 flex-wrap" style="border-bottom:1px solid #E2E8F0;padding-bottom:10px">
        <button type="button" class="yoy-type-btn active-type-btn"
                onclick="setYoyType('')" data-type=""
                style="font-size:11px;font-weight:600;padding:3px 14px;border-radius:20px;
                       border:1.5px solid var(--navy);background:var(--navy);color:#fff;cursor:pointer">
            All Types
        </button>
        @foreach($yoyTypes as $t)
        <button type="button" class="yoy-type-btn"
                onclick="setYoyType('{{ $t }}')" data-type="{{ $t }}"
                style="font-size:11px;font-weight:600;padding:3px 14px;border-radius:20px;
                       border:1.5px solid #E2E8F0;background:#F8FAFC;color:#475569;cursor:pointer">
            {{ $t }}
        </button>
        @endforeach
    </div>

    {{-- Budget view --}}
    <div id="view_budget" class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead style="font-size:11px;text-transform:uppercase;
                          letter-spacing:.5px;color:var(--slate)">
                <tr>
                    <th>Code</th>
                    <th>Account</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th class="text-end">{{ $periodA->name }} Budget (eff.)</th>
                    <th class="text-end">{{ $periodB->name }} Budget (eff.)</th>
                    <th class="text-end">Change (GHS)</th>
                    <th class="text-end">Change %</th>
                    <th class="text-center">Trend</th>
                </tr>
            </thead>
            <tbody id="tbody_budget"></tbody>
            <tfoot style="background:#F8FAFC;font-weight:700;font-size:12px">
                <tr>
                    <td colspan="4">Total</td>
                    <td class="text-end">GHS {{ number_format($comparison['budget_total_a'], 0) }}</td>
                    <td class="text-end">GHS {{ number_format($comparison['budget_total_b'], 0) }}</td>
                    <td class="text-end"
                        style="color:{{ $budgetChangeTotal >= 0 ? '#F43F5E' : '#10B981' }}">
                        {{ $budgetChangeTotal >= 0 ? '+' : '' }}GHS {{ number_format($budgetChangeTotal, 0) }}
                    </td>
                    <td class="text-end"
                        style="color:{{ $budgetChangePctTotal >= 0 ? '#F43F5E' : '#10B981' }}">
                        {{ $budgetChangePctTotal >= 0 ? '+' : '' }}{{ $budgetChangePctTotal }}%
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Actual view --}}
    <div id="view_actual" class="table-responsive" style="display:none">
        <table class="table table-sm table-hover mb-0">
            <thead style="font-size:11px;text-transform:uppercase;
                          letter-spacing:.5px;color:var(--slate)">
                <tr>
                    <th>Code</th>
                    <th>Account</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th class="text-end">{{ $periodA->name }} Actual</th>
                    <th class="text-end">{{ $periodB->name }} Actual</th>
                    <th class="text-end">Change (GHS)</th>
                    <th class="text-end">Change %</th>
                    <th class="text-center">Trend</th>
                </tr>
            </thead>
            <tbody id="tbody_actual"></tbody>
            <tfoot style="background:#F8FAFC;font-weight:700;font-size:12px">
                <tr>
                    <td colspan="4">Total</td>
                    <td class="text-end" style="color:#10B981">
                        GHS {{ number_format($comparison['actual_total_a'], 0) }}
                    </td>
                    <td class="text-end" style="color:#10B981">
                        GHS {{ number_format($comparison['actual_total_b'], 0) }}
                    </td>
                    <td class="text-end"
                        style="color:{{ $actualChangeTotal >= 0 ? '#F43F5E' : '#10B981' }}">
                        {{ $actualChangeTotal >= 0 ? '+' : '' }}GHS {{ number_format($actualChangeTotal, 0) }}
                    </td>
                    <td class="text-end"
                        style="color:{{ $actualChangePctTotal >= 0 ? '#F43F5E' : '#10B981' }}">
                        {{ $actualChangePctTotal >= 0 ? '+' : '' }}{{ $actualChangePctTotal }}%
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Combined view --}}
    <div id="view_combined" class="table-responsive" style="display:none">
        <table class="table table-sm table-hover mb-0">
            <thead style="font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:var(--slate)">
                <tr>
                    <th rowspan="2">Code</th>
                    <th rowspan="2">Account</th>
                    <th rowspan="2">Type</th>
                    <th colspan="4" class="text-center"
                        style="background:#F1F5F9;border-bottom:2px solid var(--slate)">
                        {{ $periodA->name }}
                    </th>
                    <th colspan="4" class="text-center"
                        style="background:#EFF6FF;border-bottom:2px solid var(--navy)">
                        {{ $periodB->name }}
                    </th>
                    <th colspan="2" class="text-center"
                        style="background:#F0FDF4;border-bottom:2px solid #10B981">
                        Change
                    </th>
                </tr>
                <tr>
                    <th class="text-end" style="background:#F1F5F9">Original</th>
                    <th class="text-end" style="background:#F1F5F9">Suppl.</th>
                    <th class="text-end" style="background:#F1F5F9">Actual</th>
                    <th class="text-end" style="background:#F1F5F9">Util%</th>
                    <th class="text-end" style="background:#EFF6FF">Original</th>
                    <th class="text-end" style="background:#EFF6FF">Suppl.</th>
                    <th class="text-end" style="background:#EFF6FF">Actual</th>
                    <th class="text-end" style="background:#EFF6FF">Util%</th>
                    <th class="text-end" style="background:#F0FDF4">Budget</th>
                    <th class="text-end" style="background:#F0FDF4">Actual</th>
                </tr>
            </thead>
            <tbody id="tbody_combined"></tbody>
            <tfoot style="background:#F8FAFC;font-weight:700;font-size:11px">
                <tr>
                    <td colspan="3">Total</td>
                    <td class="text-end">{{ number_format($comparison['original_total_a'],0) }}</td>
                    <td class="text-end" style="color:#92400E">
                        {{ $comparison['supplementary_total_a']>0 ? '+'.number_format($comparison['supplementary_total_a'],0) : '—' }}
                    </td>
                    <td class="text-end" style="color:#10B981">{{ number_format($comparison['actual_total_a'],0) }}</td>
                    <td class="text-end">{{ $utilA }}%</td>
                    <td class="text-end">{{ number_format($comparison['original_total_b'],0) }}</td>
                    <td class="text-end" style="color:#92400E">
                        {{ $comparison['supplementary_total_b']>0 ? '+'.number_format($comparison['supplementary_total_b'],0) : '—' }}
                    </td>
                    <td class="text-end" style="color:#10B981">{{ number_format($comparison['actual_total_b'],0) }}</td>
                    <td class="text-end">{{ $utilB }}%</td>
                    <td class="text-end" style="color:{{ $budgetChangeTotal>=0?'#F43F5E':'#10B981' }}">
                        {{ $budgetChangeTotal>=0?'+':'' }}{{ number_format($budgetChangeTotal,0) }}
                    </td>
                    <td class="text-end" style="color:{{ $actualChangeTotal>=0?'#F43F5E':'#10B981' }}">
                        {{ $actualChangeTotal>=0?'+':'' }}{{ number_format($actualChangeTotal,0) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Pagination bar --}}
    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
        <span class="small text-muted" id="yoyPageInfo"></span>
        <div id="yoyPagControls" class="d-flex gap-1 flex-wrap"></div>
    </div>
</div>

<script>
// ── Client-side paginated table renderer ──
const YOY_ROWS      = @json($tableRows);
const PERIOD_A_NAME = @json($periodA->name);
const PERIOD_B_NAME = @json($periodB->name);

let yoyView      = 'budget';
let yoyPage      = 1;
let yoyPageSize  = 50;
let yoyFiltered  = YOY_ROWS;

function numFmt(n) {
    return Number(n ?? 0).toLocaleString('en-GH', { maximumFractionDigits: 0 });
}

function esc(s) {
    return String(s ?? '')
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function trendArrow(t) {
    if (t === 'up')   return '<span style="color:#F43F5E;font-size:16px">↑</span>';
    if (t === 'down') return '<span style="color:#10B981;font-size:16px">↓</span>';
    return '<span style="color:#94A3B8;font-size:16px">→</span>';
}

function changePct(pct) {
    if (pct === null || pct === undefined)
        return '<span class="text-muted">New</span>';
    const c = pct > 0 ? '#F43F5E' : '#10B981';
    return `<span style="color:${c}">${pct >= 0 ? '+' : ''}${pct}%</span>`;
}

function chgColor(v) {
    return v > 0 ? '#F43F5E' : v < 0 ? '#10B981' : 'inherit';
}

function utilColor(u) {
    return u > 90 ? '#F43F5E' : u > 70 ? '#F59E0B' : '#10B981';
}

function typePill(t) {
    return `<span style="font-size:10px;padding:1px 8px;border-radius:10px;
                         background:#F1F5F9;color:#475569;white-space:nowrap">${esc(t??'')}</span>`;
}

function rowBudget(r) {
    const sA = r.supplementary_a > 0
        ? `<div style="font-size:9px;color:#92400E">incl. +${numFmt(r.supplementary_a)} suppl.</div>` : '';
    const sB = r.supplementary_b > 0
        ? `<div style="font-size:9px;color:#92400E">incl. +${numFmt(r.supplementary_b)} suppl.</div>` : '';
    return `<tr>
        <td style="font-family:monospace;font-weight:600;font-size:12px">${esc(r.code)}</td>
        <td class="small">${esc(r.name)}</td>
        <td class="small text-muted">${esc(r.category)}</td>
        <td>${typePill(r.line_type)}</td>
        <td class="text-end small">GHS ${numFmt(r.budget_a)}${sA}</td>
        <td class="text-end small">GHS ${numFmt(r.budget_b)}${sB}</td>
        <td class="text-end small fw-semibold" style="color:${chgColor(r.budget_change)}">
            ${r.budget_change >= 0 ? '+' : ''}GHS ${numFmt(r.budget_change)}
        </td>
        <td class="text-end small">${changePct(r.budget_change_pct)}</td>
        <td class="text-center">${trendArrow(r.budget_trend)}</td>
    </tr>`;
}

function rowActual(r) {
    return `<tr>
        <td style="font-family:monospace;font-weight:600;font-size:12px">${esc(r.code)}</td>
        <td class="small">${esc(r.name)}</td>
        <td class="small text-muted">${esc(r.category)}</td>
        <td>${typePill(r.line_type)}</td>
        <td class="text-end small" style="color:#10B981">GHS ${numFmt(r.actual_a)}</td>
        <td class="text-end small" style="color:#10B981">GHS ${numFmt(r.actual_b)}</td>
        <td class="text-end small fw-semibold" style="color:${chgColor(r.actual_change)}">
            ${r.actual_change >= 0 ? '+' : ''}GHS ${numFmt(r.actual_change)}
        </td>
        <td class="text-end small">${changePct(r.actual_change_pct)}</td>
        <td class="text-center">${trendArrow(r.actual_trend)}</td>
    </tr>`;
}

function rowCombined(r) {
    return `<tr>
        <td style="font-family:monospace;font-weight:600;font-size:11px">${esc(r.code)}</td>
        <td style="font-size:11px">${esc(r.name)}</td>
        <td style="font-size:11px">${typePill(r.line_type)}</td>
        <td class="text-end" style="font-size:11px;background:#FAFAFA">${numFmt(r.original_a)}</td>
        <td class="text-end" style="font-size:11px;background:#FAFAFA;color:#92400E">
            ${r.supplementary_a > 0 ? '+' + numFmt(r.supplementary_a) : '—'}
        </td>
        <td class="text-end" style="font-size:11px;background:#FAFAFA;color:#10B981">${numFmt(r.actual_a)}</td>
        <td class="text-end" style="font-size:11px;background:#FAFAFA">
            <span style="color:${utilColor(r.utilisation_a)}">${r.utilisation_a}%</span>
        </td>
        <td class="text-end" style="font-size:11px;background:#F0F7FF">${numFmt(r.original_b)}</td>
        <td class="text-end" style="font-size:11px;background:#F0F7FF;color:#92400E">
            ${r.supplementary_b > 0 ? '+' + numFmt(r.supplementary_b) : '—'}
        </td>
        <td class="text-end" style="font-size:11px;background:#F0F7FF;color:#10B981">${numFmt(r.actual_b)}</td>
        <td class="text-end" style="font-size:11px;background:#F0F7FF">
            <span style="color:${utilColor(r.utilisation_b)}">${r.utilisation_b}%</span>
        </td>
        <td class="text-end fw-semibold" style="font-size:11px;background:#F0FDF4;color:${chgColor(r.budget_change)}">
            ${r.budget_change >= 0 ? '+' : ''}${numFmt(r.budget_change)}
        </td>
        <td class="text-end fw-semibold" style="font-size:11px;background:#F0FDF4;color:${chgColor(r.actual_change)}">
            ${r.actual_change >= 0 ? '+' : ''}${numFmt(r.actual_change)}
        </td>
    </tr>`;
}

function renderTable() {
    const isAll    = yoyPageSize === Infinity;
    const start    = isAll ? 0 : (yoyPage - 1) * yoyPageSize;
    const end      = isAll ? yoyFiltered.length : Math.min(start + yoyPageSize, yoyFiltered.length);
    const pageRows = yoyFiltered.slice(start, end);
    const renderer = yoyView === 'budget' ? rowBudget
                   : yoyView === 'actual' ? rowActual
                   : rowCombined;

    const tbody = document.getElementById('tbody_' + yoyView);
    tbody.innerHTML = pageRows.length
        ? pageRows.map(renderer).join('')
        : '<tr><td colspan="20" class="text-center text-muted py-4">No matching records.</td></tr>';

    const total = yoyFiltered.length;
    document.getElementById('yoyPageInfo').textContent =
        total === 0 ? 'No records found'
                    : `Showing ${start + 1}–${end} of ${total} codes`;
    document.getElementById('rowCount').textContent =
        yoyFiltered.length < YOY_ROWS.length
            ? ` — ${total} of ${YOY_ROWS.length} codes`
            : ` — ${total} codes`;

    renderPagination();
}

function renderPagination() {
    const el = document.getElementById('yoyPagControls');
    if (yoyPageSize === Infinity) { el.innerHTML = ''; return; }
    const totalPages = Math.ceil(yoyFiltered.length / yoyPageSize);
    if (totalPages <= 1) { el.innerHTML = ''; return; }

    const pages = (() => {
        if (totalPages <= 7) return Array.from({ length: totalPages }, (_, i) => i + 1);
        if (yoyPage <= 4)             return [1,2,3,4,5,'…',totalPages];
        if (yoyPage >= totalPages - 3) return [1,'…',totalPages-4,totalPages-3,totalPages-2,totalPages-1,totalPages];
        return [1,'…',yoyPage-1,yoyPage,yoyPage+1,'…',totalPages];
    })();

    const navBtn = (label, page, disabled) =>
        `<button class="btn btn-sm btn-outline-secondary" ${disabled ? 'disabled' : ''}
         onclick="yoyGoPage(${page})" style="padding:2px 8px">${label}</button>`;

    const pageBtn = (p) => p === '…'
        ? '<span class="btn btn-sm disabled" style="padding:2px 6px">…</span>'
        : `<button class="btn btn-sm" onclick="yoyGoPage(${p})"
               style="padding:2px 8px;${p === yoyPage ? 'background:var(--navy);color:#fff' : ''}">${p}</button>`;

    el.innerHTML =
        navBtn('‹', yoyPage - 1, yoyPage === 1) +
        pages.map(pageBtn).join('') +
        navBtn('›', yoyPage + 1, yoyPage === totalPages);
}

function yoyGoPage(p) {
    yoyPage = p;
    renderTable();
    document.getElementById('yoyTableCard').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// ── Type filter tabs ──
let yoyTypeFilter = '';

function setYoyType(type) {
    yoyTypeFilter = type;
    document.querySelectorAll('.yoy-type-btn').forEach(b => {
        const on = b.dataset.type === type;
        b.style.background  = on ? 'var(--navy)' : '#F8FAFC';
        b.style.color       = on ? '#fff'        : '#475569';
        b.style.borderColor = on ? 'var(--navy)' : '#E2E8F0';
    });
    applyFilters();
}

function applyFilters() {
    const q = document.getElementById('codeSearch').value.toLowerCase();
    yoyFiltered = YOY_ROWS.filter(r => {
        if (yoyTypeFilter && (r.line_type ?? '') !== yoyTypeFilter) return false;
        if (!q) return true;
        return (r.code      ?? '').toLowerCase().includes(q) ||
               (r.name      ?? '').toLowerCase().includes(q) ||
               (r.category  ?? '').toLowerCase().includes(q) ||
               (r.line_type ?? '').toLowerCase().includes(q);
    });
    yoyPage = 1;
    renderTable();
}

document.getElementById('codeSearch').addEventListener('input', applyFilters);

document.getElementById('yoyPageSize').addEventListener('change', function () {
    yoyPageSize = this.value === 'all' ? Infinity : parseInt(this.value);
    yoyPage = 1;
    renderTable();
});

// ── Export helpers ──
function getExportConfig() {
    if (yoyView === 'budget') {
        return {
            headers: ['Code','Account','Category','Type',
                      `${PERIOD_A_NAME} Budget`,`${PERIOD_B_NAME} Budget`,
                      'Change (GHS)','Change %','Trend'],
            row: r => [r.code, r.name, r.category, r.line_type,
                       r.budget_a, r.budget_b,
                       r.budget_change, r.budget_change_pct ?? 'New', r.budget_trend],
        };
    }
    if (yoyView === 'actual') {
        return {
            headers: ['Code','Account','Category','Type',
                      `${PERIOD_A_NAME} Actual`,`${PERIOD_B_NAME} Actual`,
                      'Change (GHS)','Change %','Trend'],
            row: r => [r.code, r.name, r.category, r.line_type,
                       r.actual_a, r.actual_b,
                       r.actual_change, r.actual_change_pct ?? 'New', r.actual_trend],
        };
    }
    return {
        headers: ['Code','Account','Type',
                  `${PERIOD_A_NAME} Original`,`${PERIOD_A_NAME} Suppl`,
                  `${PERIOD_A_NAME} Actual`,`${PERIOD_A_NAME} Util%`,
                  `${PERIOD_B_NAME} Original`,`${PERIOD_B_NAME} Suppl`,
                  `${PERIOD_B_NAME} Actual`,`${PERIOD_B_NAME} Util%`,
                  'Budget Change','Actual Change'],
        row: r => [r.code, r.name, r.line_type,
                   r.original_a, r.supplementary_a, r.actual_a, r.utilisation_a,
                   r.original_b, r.supplementary_b, r.actual_b, r.utilisation_b,
                   r.budget_change, r.actual_change],
    };
}

function downloadBlob(content, filename, mime) {
    const blob = new Blob([content], { type: mime });
    const url  = URL.createObjectURL(blob);
    const a    = Object.assign(document.createElement('a'), { href: url, download: filename });
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

function exportYOY(format) {
    const { headers, row } = getExportConfig();
    const data     = yoyFiltered;
    const datestamp = new Date().toISOString().slice(0, 10);
    const filename  = `yoy-${yoyView}-${datestamp}`;

    if (format === 'csv') {
        const csv = [headers, ...data.map(row)]
            .map(cells => cells.map(c => `"${String(c ?? '').replace(/"/g, '""')}"`).join(','))
            .join('\n');
        downloadBlob(csv, filename + '.csv', 'text/csv;charset=utf-8;');
    }

    if (format === 'json') {
        const json = JSON.stringify(
            data.map(r => Object.fromEntries(headers.map((h, i) => [h, row(r)[i]]))),
            null, 2
        );
        downloadBlob(json, filename + '.json', 'application/json');
    }

    if (format === 'copy') {
        const tsv = [headers, ...data.map(row)]
            .map(cells => cells.join('\t'))
            .join('\n');
        navigator.clipboard.writeText(tsv).then(() => {
            const btn = document.getElementById('copyBtn');
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check me-2 text-success"></i>Copied!';
            setTimeout(() => { btn.innerHTML = orig; }, 2000);
        });
    }
}

// Initial render on page load
renderTable();

// ── Table view toggle ──
function showView(view) {
    ['budget','actual','combined'].forEach(v => {
        document.getElementById('view_' + v).style.display = v === view ? '' : 'none';
        const btn = document.getElementById('btn_' + v);
        btn.style.background   = v === view ? 'var(--navy)' : '';
        btn.style.color        = v === view ? '#fff'        : '';
        btn.className          = v === view ? 'btn btn-sm' : 'btn btn-sm btn-outline-secondary';
        btn.style.borderRadius = '6px';
        btn.style.fontSize     = '12px';
    });
    yoyView = view;
    yoyPage = 1;
    renderTable();
}
</script>

@else
<div class="chart-card text-center py-5 text-muted">
    <div style="font-size:36px;margin-bottom:12px">📅</div>
    <div style="font-size:15px;font-weight:600;color:var(--navy);margin-bottom:8px">
        Select two periods and click Compare
    </div>
    <div style="font-size:13px">
        The report will show budget and actual comparisons side by side
        across all account codes for both periods.
    </div>
</div>
@endif

@endsection
