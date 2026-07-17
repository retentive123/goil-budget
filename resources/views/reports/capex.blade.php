@extends('layouts.app')
@section('title', 'Capital Expenditure Report')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Capital Expenditure</h5>
        <p class="text-muted small mb-0">
            <a href="{{ route('reports.index') }}" class="text-muted">Reports</a>
            / Capital Expenditure
            @if($period) · <span class="fw-semibold">{{ $period->name }}</span> @endif
        </p>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="chart-card mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Period</label>
            <select name="period_id" class="form-select form-select-sm">
                @foreach($periods as $p)
                <option value="{{ $p->id }}"
                    {{ request('period_id', $period?->id) == $p->id ? 'selected' : '' }}>
                    {{ $p->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Dept / Station</label>
            @include('reports._dept_filter', [
                'selectedId' => request('department_id'),
                'selectId'   => 'rptCapexDeptSel',
            ])
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm w-100"
                    style="background:var(--navy);color:#fff;border-radius:8px">
                Apply
            </button>
        </div>
    </div>
</form>

@if(!$period)
<div class="chart-card text-center py-5 text-muted">
    <i class="fas fa-hard-hat fa-2x mb-3 opacity-25"></i>
    <p class="mb-1">No Capital Expenditure budget data for this period.</p>
    <p class="small">Add account categories with type <strong>Capital Expenditure</strong> to populate this report.</p>
</div>

{{-- ══ Configured CapEx layout ══ --}}
@elseif($configuredCapex)
@php
    $ct      = $capex ? $capex['totals'] : ['effective'=>0,'actual'=>0,'prev_actual'=>0,'growth_pct'=>null];
    $hasPrev = $prevPeriod !== null;
    $colCount = $hasPrev ? 4 : 2;
@endphp

{{-- KPI strip --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#1B2A4A"></div>
            <div class="stat-label">Total CapEx Budget</div>
            <div class="stat-value" style="font-size:16px">{{ currency() }} {{ number_format($ct['effective'], 0) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#E65C00"></div>
            <div class="stat-label">YTD Actual Spend</div>
            <div class="stat-value" style="font-size:16px">{{ currency() }} {{ number_format($ct['actual'], 0) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            @php
                $util = $ct['effective'] > 0 ? round($ct['actual'] / $ct['effective'] * 100, 1) : 0;
                $utc  = $util > 90 ? '#F43F5E' : ($util > 70 ? '#F59E0B' : '#10B981');
            @endphp
            <div class="stat-accent" style="background:{{ $utc }}"></div>
            <div class="stat-label">Budget Utilisation</div>
            <div class="stat-value" style="font-size:16px;color:{{ $utc }}">{{ $util }}%</div>
        </div>
    </div>
    @if($hasPrev)
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#6D28D9"></div>
            <div class="stat-label">Prior Year ({{ $prevPeriod->name }})</div>
            <div class="stat-value" style="font-size:16px">{{ currency() }} {{ number_format($ct['prev_actual'], 0) }}</div>
            <div class="stat-sub">
                @if($ct['growth_pct'] !== null)
                    Growth: {{ $ct['growth_pct'] >= 0 ? '+' : '' }}{{ $ct['growth_pct'] }}%
                @else vs prior
                @endif
            </div>
        </div>
    </div>
    @endif
</div>

<div class="chart-card">
    {{-- Toolbar --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="d-flex gap-3 flex-wrap align-items-center" style="font-size:11px">
            @if($hasPrev)
            <span class="badge" style="background:#FFF7ED;color:#C2410C;font-weight:500">Current: {{ $period->name }}</span>
            <span class="badge" style="background:#F5F3FF;color:#6D28D9;font-weight:500">Prior: {{ $prevPeriod->name }}</span>
            @endif
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" onclick="ccxExpandAll()" style="font-size:12px">
                <i class="fas fa-expand-alt me-1"></i>Expand All
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="ccxCollapseAll()" style="font-size:12px">
                <i class="fas fa-compress-alt me-1"></i>Collapse All
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="ccxExport()" style="font-size:12px">
                <i class="fas fa-file-csv me-1"></i>Export CSV
            </button>
        </div>
    </div>

    {{-- Configured CapEx Table --}}
    <div class="table-responsive" style="max-height:70vh;overflow-y:auto">
    <table class="table table-sm mb-0" id="ccxTable" style="min-width:800px">
        <thead style="font-size:10px;text-transform:uppercase;letter-spacing:.4px;color:#64748B;
                      position:sticky;top:0;background:#fff;z-index:2">
            <tr>
                <th rowspan="2" style="min-width:280px;vertical-align:bottom">Account</th>
                <th colspan="2" class="text-center"
                    style="border-bottom:1px solid #E2E8F0;background:#FFF7ED;color:#C2410C">
                    {{ $period->name }}
                </th>
                @if($hasPrev)
                <th colspan="2" class="text-center"
                    style="border-bottom:1px solid #E2E8F0;background:#F5F3FF;color:#6D28D9">
                    {{ $prevPeriod->name }}
                </th>
                @endif
            </tr>
            <tr>
                <th class="text-end" style="background:#FFF7ED;min-width:120px">Eff. Budget</th>
                <th class="text-end" style="background:#FFF7ED;min-width:120px">YTD Actual</th>
                @if($hasPrev)
                <th class="text-end" style="background:#F5F3FF;min-width:120px">Prev Budget</th>
                <th class="text-end" style="background:#F5F3FF;min-width:120px">Growth %</th>
                @endif
            </tr>
        </thead>
        <tbody id="ccxBody">
        @foreach($configuredCapex['lines'] as $ccxLine)
            @if($ccxLine['type'] === 'spacer')
            <tr><td colspan="{{ $colCount + 2 }}" style="height:14px;border:none"></td></tr>

            @elseif($ccxLine['type'] === 'sub_category')
            @php
                $ccxUtil = $ccxLine['budget'] > 0 ? round($ccxLine['actual'] / $ccxLine['budget'] * 100) : 0;
                $ccxUtc  = $ccxUtil > 90 ? '#F43F5E' : ($ccxUtil > 70 ? '#F59E0B' : '#10B981');
            @endphp
            <tr class="ccx-subcat-row"
                data-subcat="{{ $ccxLine['sub_cat_id'] ?? '' }}"
                onclick="ccxToggleRow(this)"
                style="cursor:pointer">
                <td style="padding-left:12px;font-weight:600;font-size:13px;color:#C2410C">
                    <span class="ccx-chevron" style="margin-right:6px;opacity:.5;display:inline-block;transition:transform .15s">▸</span>
                    {{ $ccxLine['label'] }}
                </td>
                <td class="text-end fw-semibold">{{ number_format($ccxLine['budget'], 0) }}</td>
                <td class="text-end fw-semibold">
                    {{ number_format($ccxLine['actual'], 0) }}
                    <div style="height:2px;background:#E2E8F0;border-radius:1px;margin-top:2px">
                        <div style="height:2px;width:{{ min($ccxUtil,100) }}%;background:{{ $ccxUtc }};border-radius:1px"></div>
                    </div>
                </td>
                @if($hasPrev)
                <td class="text-end fw-semibold">{{ number_format($ccxLine['prev_budget'], 0) }}</td>
                <td class="text-end fw-semibold"
                    style="color:{{ ($ccxLine['growth_pct'] ?? 0) >= 0 ? '#F43F5E' : '#10B981' }}">
                    @if($ccxLine['growth_pct'] !== null)
                        {{ $ccxLine['growth_pct'] >= 0 ? '+' : '' }}{{ $ccxLine['growth_pct'] }}%
                    @else —
                    @endif
                </td>
                @endif
            </tr>

            @elseif($ccxLine['type'] === 'subtotal')
            <tr style="background:#FFF7ED;border-top:1.5px solid #FED7AA">
                <td style="padding-left:20px;font-weight:700;font-size:13px;color:#C2410C;letter-spacing:.3px">
                    {{ $ccxLine['label'] }}
                </td>
                <td class="text-end fw-bold" style="color:#C2410C">{{ number_format($ccxLine['budget'], 0) }}</td>
                <td class="text-end fw-bold" style="color:#C2410C">{{ number_format($ccxLine['actual'], 0) }}</td>
                @if($hasPrev)
                <td class="text-end fw-bold" style="color:#9A3412">{{ number_format($ccxLine['prev_budget'], 0) }}</td>
                <td class="text-end fw-bold"
                    style="color:{{ ($ccxLine['growth_pct'] ?? 0) >= 0 ? '#F43F5E' : '#10B981' }}">
                    @if($ccxLine['growth_pct'] !== null)
                        {{ $ccxLine['growth_pct'] >= 0 ? '+' : '' }}{{ $ccxLine['growth_pct'] }}%
                    @else —
                    @endif
                </td>
                @endif
            </tr>
            @endif
        @endforeach
        </tbody>

        {{-- Grand Total --}}
        <tbody>
            <tr style="background:#0F172A;font-weight:700;border-top:3px solid #C9A84C">
                <td style="padding-left:12px;color:#C9A84C;font-size:13px">TOTAL CAPITAL EXPENDITURE</td>
                <td class="text-end" style="color:#E2E8F0">{{ number_format($configuredCapex['grand_budget'], 0) }}</td>
                <td class="text-end" style="color:#E2E8F0">{{ number_format($configuredCapex['grand_actual'], 0) }}</td>
                @if($hasPrev)
                <td class="text-end" style="color:#94A3B8">{{ number_format($configuredCapex['grand_prev_bud'], 0) }}</td>
                <td class="text-end"
                    style="color:{{ ($configuredCapex['grand_growth_pct'] ?? 0) >= 0 ? '#F43F5E' : '#10B981' }}">
                    @if($configuredCapex['grand_growth_pct'] !== null)
                        {{ $configuredCapex['grand_growth_pct'] >= 0 ? '+' : '' }}{{ $configuredCapex['grand_growth_pct'] }}%
                    @else —
                    @endif
                </td>
                @endif
            </tr>
        </tbody>
    </table>
    </div>
</div>

<script>
const CCX_LINES  = @json($configuredCapex['lines'] ?? []);
const CCX_EXPAND = @json($configuredCapex['capex_expand'] ?? []);
const CCX_PREV   = {{ $hasPrev ? 'true' : 'false' }};

// Build lookup: sub_cat_id (string) → array of categories
const CCX_BY_SC = {};
CCX_EXPAND.forEach(cat => {
    const sc = String(cat.sub_cat_id);
    if (!CCX_BY_SC[sc]) CCX_BY_SC[sc] = [];
    CCX_BY_SC[sc].push(cat);
});

const ccxRowState = {};

function n0(v) { return Number(v ?? 0).toLocaleString('en-GH', {minimumFractionDigits:0, maximumFractionDigits:0}); }
function esc(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function ccxToggleRow(tr) {
    const scId   = String(tr.dataset.subcat || '');
    const isOpen = !!ccxRowState[scId];
    const chev   = tr.querySelector('.ccx-chevron');

    // Remove existing detail rows
    let sib = tr.nextElementSibling;
    while (sib && sib.classList.contains('ccx-detail-row')) {
        const rem = sib; sib = sib.nextElementSibling; rem.remove();
    }

    if (isOpen) {
        ccxRowState[scId] = false;
        if (chev) chev.style.transform = '';
        return;
    }

    const cats = CCX_BY_SC[scId] || [];
    if (cats.length === 0) { return; }

    let insertAfter = tr;
    cats.forEach(cat => {
        const catTr = document.createElement('tr');
        catTr.className = 'ccx-detail-row';
        catTr.innerHTML = `
            <td style="padding:7px 12px 7px 32px;font-size:12.5px;font-weight:600;background:#FFF7ED;color:#9A3412">
                ${esc(cat.name)}
            </td>
            <td class="text-end" style="background:#FFF7ED;font-size:12px;font-weight:600">${n0(cat.effective)}</td>
            <td class="text-end" style="background:#FFF7ED;font-size:12px;font-weight:600">${n0(cat.actual)}</td>
            ${CCX_PREV ? `<td class="text-end" style="background:#FFF7ED;font-size:12px">${n0(cat.prev_budget)}</td>
            <td class="text-end" style="background:#FFF7ED;font-size:12px"></td>` : ''}`;
        insertAfter.insertAdjacentElement('afterend', catTr);
        insertAfter = catTr;

        cat.codes.forEach(r => {
            const codeTr = document.createElement('tr');
            codeTr.className = 'ccx-detail-row';
            const gpct = r.growth_pct !== null
                ? `<span style="color:${(r.growth_pct??0)>=0?'#F43F5E':'#10B981'}">${(r.growth_pct>=0?'+':'')+r.growth_pct}%</span>`
                : '—';
            codeTr.innerHTML = `
                <td style="padding:5px 12px 5px 52px;font-size:11.5px">
                    <span style="color:#64748B;font-family:monospace;margin-right:6px">${esc(r.code)}</span>${esc(r.name)}
                </td>
                <td class="text-end" style="font-size:11.5px">${n0(r.effective)}</td>
                <td class="text-end" style="font-size:11.5px">${n0(r.actual)}</td>
                ${CCX_PREV ? `<td class="text-end" style="font-size:11.5px">${n0(r.prev_budget)}</td>
                <td class="text-end" style="font-size:11.5px">${gpct}</td>` : ''}`;
            insertAfter.insertAdjacentElement('afterend', codeTr);
            insertAfter = codeTr;
        });
    });

    ccxRowState[scId] = true;
    if (chev) chev.style.transform = 'rotate(90deg)';
}

function ccxExpandAll() {
    document.querySelectorAll('.ccx-subcat-row').forEach(tr => {
        const scId = String(tr.dataset.subcat || '');
        if (!ccxRowState[scId]) ccxToggleRow(tr);
    });
}

function ccxCollapseAll() {
    document.querySelectorAll('.ccx-subcat-row').forEach(tr => {
        const scId = String(tr.dataset.subcat || '');
        if (ccxRowState[scId]) ccxToggleRow(tr);
    });
}

function ccxExport() {
    const hdrs = ['Account', 'Eff. Budget', 'YTD Actual'];
    if (CCX_PREV) hdrs.push('Prev Budget', 'Growth %');

    const rows = [];
    CCX_LINES.forEach(line => {
        if (line.type === 'spacer') return;
        if (line.type === 'sub_category') {
            rows.push([
                `"${line.label}"`, line.budget, line.actual,
                ...(CCX_PREV ? [line.prev_budget, line.growth_pct !== null ? (line.growth_pct >= 0 ? '+' : '') + line.growth_pct + '%' : ''] : [])
            ]);
            const cats = CCX_BY_SC[String(line.sub_cat_id)] || [];
            cats.forEach(cat => {
                rows.push([
                    `"  ${cat.name}"`, cat.effective, cat.actual,
                    ...(CCX_PREV ? [cat.prev_budget, ''] : [])
                ]);
                cat.codes.forEach(r => {
                    rows.push([
                        `"    ${r.code} – ${r.name}"`, r.effective, r.actual,
                        ...(CCX_PREV ? [r.prev_budget, r.growth_pct !== null ? (r.growth_pct >= 0 ? '+' : '') + r.growth_pct + '%' : ''] : [])
                    ]);
                });
            });
        } else {
            rows.push([
                `"${line.label}"`, line.budget, line.actual,
                ...(CCX_PREV ? [line.prev_budget, line.growth_pct !== null ? (line.growth_pct >= 0 ? '+' : '') + line.growth_pct + '%' : ''] : [])
            ]);
        }
    });

    const stamp = new Date().toISOString().slice(0,10);
    let csv = hdrs.join(',') + '\n';
    rows.forEach(r => { csv += r.join(',') + '\n'; });
    const a = document.createElement('a');
    a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
    a.download = `capex-configured-${stamp}.csv`;
    a.click();
}
</script>

{{-- ══ Standard CapEx layout ══ --}}
@elseif(!$capex || empty($capex['sections']))
<div class="chart-card text-center py-5 text-muted">
    <i class="fas fa-hard-hat fa-2x mb-3 opacity-25"></i>
    <p class="mb-1">No Capital Expenditure budget data for this period.</p>
    <p class="small">Add account categories with type <strong>Capital Expenditure</strong> to populate this report.</p>
</div>
@else
@php
    $ct = $capex['totals'];
    $hasPrev = $prevPeriod !== null;
    $colCount = $hasPrev ? 4 : 2;
@endphp

{{-- KPI strip --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#1B2A4A"></div>
            <div class="stat-label">Total CapEx Budget</div>
            <div class="stat-value" style="font-size:16px">{{ currency() }} {{ number_format($ct['effective'], 0) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#E65C00"></div>
            <div class="stat-label">YTD Actual Spend</div>
            <div class="stat-value" style="font-size:16px">{{ currency() }} {{ number_format($ct['actual'], 0) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            @php
                $util = $ct['effective'] > 0 ? round($ct['actual'] / $ct['effective'] * 100, 1) : 0;
                $utc  = $util > 90 ? '#F43F5E' : ($util > 70 ? '#F59E0B' : '#10B981');
            @endphp
            <div class="stat-accent" style="background:{{ $utc }}"></div>
            <div class="stat-label">Budget Utilisation</div>
            <div class="stat-value" style="font-size:16px;color:{{ $utc }}">{{ $util }}%</div>
        </div>
    </div>
    @if($hasPrev)
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#6D28D9"></div>
            <div class="stat-label">Prior Year ({{ $prevPeriod->name }})</div>
            <div class="stat-value" style="font-size:16px">{{ currency() }} {{ number_format($ct['prev_actual'], 0) }}</div>
            <div class="stat-sub">
                @if($ct['growth_pct'] !== null)
                    Growth: {{ $ct['growth_pct'] >= 0 ? '+' : '' }}{{ $ct['growth_pct'] }}%
                @else
                    vs prior
                @endif
            </div>
        </div>
    </div>
    @endif
</div>

<div class="chart-card">
    {{-- Toolbar --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="d-flex gap-3 flex-wrap align-items-center" style="font-size:11px">
            @if($hasPrev)
            <span class="badge" style="background:#EFF6FF;color:#1D4ED8;font-weight:500">Current: {{ $period->name }}</span>
            <span class="badge" style="background:#F5F3FF;color:#6D28D9;font-weight:500">Prior: {{ $prevPeriod->name }}</span>
            @endif
            {{-- Pagination size --}}
            <div class="d-flex align-items-center gap-1">
                <span style="color:#64748B">Show</span>
                <select id="capexPageSize" class="form-select form-select-sm" style="width:80px;font-size:12px"
                        onchange="capexSetPageSize(this.value)">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="all">All</option>
                </select>
                <span style="color:#64748B">per page</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" onclick="capexExpandAll()" style="font-size:12px">
                <i class="fas fa-expand-alt me-1"></i>Expand All
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="capexCollapseAll()" style="font-size:12px">
                <i class="fas fa-compress-alt me-1"></i>Collapse All
            </button>
            {{-- Export --}}
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" style="font-size:12px">
                    <i class="fas fa-download me-1"></i>Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="#" onclick="capexExportCSV();return false">
                            <i class="fas fa-file-csv me-2 text-success"></i>CSV
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#" onclick="capexExportExcel();return false">
                            <i class="fas fa-file-excel me-2 text-success"></i>Excel
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="#" onclick="window.print();return false">
                            <i class="fas fa-print me-2 text-secondary"></i>Print
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- CapEx Table --}}
    <div class="table-responsive" id="capexTableWrap" style="max-height:70vh;overflow-y:auto">
    <table class="table table-sm mb-0" id="capexTable" style="min-width:800px">
        <thead style="font-size:10px;text-transform:uppercase;letter-spacing:.4px;color:#64748B;
                      position:sticky;top:0;background:#fff;z-index:2">
            <tr>
                <th rowspan="2" style="min-width:280px;vertical-align:bottom">Account</th>
                <th colspan="2" class="text-center"
                    style="border-bottom:1px solid #E2E8F0;background:#FFF7ED;color:#C2410C">
                    {{ $period->name }}
                </th>
                @if($hasPrev)
                <th colspan="2" class="text-center"
                    style="border-bottom:1px solid #E2E8F0;background:#F5F3FF;color:#6D28D9">
                    {{ $prevPeriod->name }}
                </th>
                @endif
            </tr>
            <tr>
                <th class="text-end" style="background:#FFF7ED;min-width:120px">Eff. Budget</th>
                <th class="text-end" style="background:#FFF7ED;min-width:120px">YTD Actual</th>
                @if($hasPrev)
                <th class="text-end" style="background:#F5F3FF;min-width:120px">Prev Budget</th>
                <th class="text-end" style="background:#F5F3FF;min-width:120px">Growth %</th>
                @endif
            </tr>
        </thead>

        {{-- Section header --}}
        <tbody>
            <tr style="background:#FFF7ED">
                <td colspan="{{ $colCount + 2 }}"
                    style="font-size:11px;font-weight:700;color:#C2410C;text-transform:uppercase;
                           letter-spacing:1px;padding:8px 12px">
                    <i class="fas fa-hard-hat me-2"></i>Capital Expenditure
                </td>
            </tr>
        </tbody>
        <tbody id="capex_body"></tbody>

        {{-- Grand Total --}}
        <tbody>
            <tr style="background:#0F172A;font-weight:700;border-top:3px solid #C9A84C">
                <td style="padding-left:12px;color:#C9A84C;font-size:13px">TOTAL CAPITAL EXPENDITURE</td>
                <td class="text-end" style="color:#E2E8F0">{{ number_format($ct['effective'], 0) }}</td>
                <td class="text-end" style="color:#E2E8F0">{{ number_format($ct['actual'], 0) }}</td>
                @if($hasPrev)
                <td class="text-end" style="color:#94A3B8">{{ number_format($ct['prev_budget'], 0) }}</td>
                <td class="text-end" style="color:{{ ($ct['growth_pct'] ?? 0) >= 0 ? '#F43F5E' : '#10B981' }}">
                    @if($ct['growth_pct'] !== null)
                        {{ $ct['growth_pct'] >= 0 ? '+' : '' }}{{ $ct['growth_pct'] }}%
                    @else —
                    @endif
                </td>
                @endif
            </tr>
        </tbody>
    </table>
    </div>
</div>

{{-- Pagination controls --}}
<div class="d-flex justify-content-between align-items-center mt-2 px-1" id="capexPagination">
    <div style="font-size:12px;color:#64748B" id="capexPagInfo"></div>
    <div class="d-flex gap-1" id="capexPagButtons"></div>
</div>

<script>
const CAPEX_DATA = @json($capex['sections']);
const CAPEX_PREV = {{ $hasPrev ? 'true' : 'false' }};
const capexState = {};
let capexPage     = 1;
let capexPageSize = 10;

function n0(v) { return Number(v ?? 0).toLocaleString('en-GH', {minimumFractionDigits:0, maximumFractionDigits:0}); }
function esc(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function renderCapex() {
    const tbody = document.getElementById('capex_body');
    let html = '';

    const all   = capexPageSize === 'all';
    const start = all ? 0 : (capexPage - 1) * capexPageSize;
    const end   = all ? CAPEX_DATA.length : start + capexPageSize;
    const page  = CAPEX_DATA.slice(start, end);

    page.forEach((cat, localIdx) => {
        const ci    = start + localIdx;
        const st    = capexState[ci] || {expanded: false};
        const arrow = st.expanded ? '▾' : '▸';
        const util  = cat.total.effective > 0
            ? Math.round(cat.total.actual / cat.total.effective * 100) : 0;
        const utc   = util > 90 ? '#F43F5E' : (util > 70 ? '#F59E0B' : '#10B981');

        html += `<tr style="background:#F8FAFC;cursor:pointer" onclick="capexToggle(${ci})">
            <td style="padding-left:12px;font-weight:600;font-size:13px;color:#C2410C">
                <span style="margin-right:6px;opacity:.6">${arrow}</span>${esc(cat.name)}
                <small class="text-muted fw-normal ms-1">${cat.total.common_size}%</small>
            </td>
            <td class="text-end fw-semibold">${n0(cat.total.effective)}</td>
            <td class="text-end fw-semibold">
                ${n0(cat.total.actual)}
                <div style="height:2px;background:#E2E8F0;border-radius:1px;margin-top:2px">
                    <div style="height:2px;width:${Math.min(util,100)}%;background:${utc};border-radius:1px"></div>
                </div>
            </td>
            ${CAPEX_PREV ? `<td class="text-end fw-semibold">${n0(cat.total.prev_budget)}</td>
            <td class="text-end fw-semibold" style="color:${(cat.total.growth_pct??0)>=0?'#F43F5E':'#10B981'}">
                ${cat.total.growth_pct!==null?(cat.total.growth_pct>=0?'+':'')+cat.total.growth_pct+'%':'—'}
            </td>` : ''}
        </tr>`;

        if (st.expanded) {
            cat.codes.forEach(row => {
                html += `<tr>
                    <td style="padding-left:36px;font-size:12px">
                        <span style="color:#64748B;font-family:monospace;margin-right:6px">${esc(row.code)}</span>
                        ${esc(row.name)}
                        <small class="text-muted ms-1">${row.common_size}%</small>
                    </td>
                    <td class="text-end" style="font-size:12px">${n0(row.effective)}</td>
                    <td class="text-end" style="font-size:12px">${n0(row.actual)}</td>
                    ${CAPEX_PREV ? `<td class="text-end" style="font-size:12px">${n0(row.prev_budget)}</td>
                    <td class="text-end" style="font-size:12px;color:${(row.growth_pct??0)>=0?'#F43F5E':'#10B981'}">
                        ${row.growth_pct!==null?(row.growth_pct>=0?'+':'')+row.growth_pct+'%':'—'}
                    </td>` : ''}
                </tr>`;
            });
        }
    });

    tbody.innerHTML = html;
    renderPagination();
}

function renderPagination() {
    const total    = CAPEX_DATA.length;
    const info     = document.getElementById('capexPagInfo');
    const btns     = document.getElementById('capexPagButtons');
    const pgEl     = document.getElementById('capexPagination');

    if (capexPageSize === 'all' || total <= capexPageSize) {
        if (info) info.textContent = `Showing all ${total} categories`;
        if (btns) btns.innerHTML  = '';
        return;
    }

    const totalPages = Math.ceil(total / capexPageSize);
    const start      = (capexPage - 1) * capexPageSize + 1;
    const end        = Math.min(capexPage * capexPageSize, total);

    if (info) info.textContent = `Showing ${start}–${end} of ${total} categories`;

    let bHtml = `<button class="btn btn-sm btn-outline-secondary" style="font-size:11px"
                     onclick="capexGoPage(${capexPage - 1})" ${capexPage === 1 ? 'disabled' : ''}>‹</button>`;

    for (let p = 1; p <= totalPages; p++) {
        if (totalPages > 7 && Math.abs(p - capexPage) > 2 && p !== 1 && p !== totalPages) {
            if (p === capexPage - 3 || p === capexPage + 3) bHtml += `<button class="btn btn-sm btn-outline-secondary disabled" style="font-size:11px">…</button>`;
            continue;
        }
        bHtml += `<button class="btn btn-sm ${p === capexPage ? 'btn-secondary' : 'btn-outline-secondary'}"
                          style="font-size:11px;min-width:32px" onclick="capexGoPage(${p})">${p}</button>`;
    }

    bHtml += `<button class="btn btn-sm btn-outline-secondary" style="font-size:11px"
                  onclick="capexGoPage(${capexPage + 1})" ${capexPage === totalPages ? 'disabled' : ''}>›</button>`;

    if (btns) btns.innerHTML = bHtml;
}

function capexGoPage(p) {
    const totalPages = Math.ceil(CAPEX_DATA.length / capexPageSize);
    if (p < 1 || p > totalPages) return;
    capexPage = p;
    renderCapex();
}

function capexSetPageSize(val) {
    capexPageSize = val === 'all' ? 'all' : parseInt(val);
    capexPage = 1;
    renderCapex();
}

function capexToggle(ci) {
    capexState[ci] = {expanded: !(capexState[ci]?.expanded)};
    renderCapex();
}

function capexExpandAll() {
    CAPEX_DATA.forEach((_, i) => { capexState[i] = {expanded: true}; });
    renderCapex();
}

function capexCollapseAll() {
    CAPEX_DATA.forEach((_, i) => { capexState[i] = {expanded: false}; });
    renderCapex();
}

// ── Export helpers ──────────────────────────────────────────
function capexBuildRows(includeItems) {
    const rows = [];
    CAPEX_DATA.forEach(cat => {
        rows.push({
            category: cat.name, code: '', name: '',
            effective: cat.total.effective, actual: cat.total.actual,
            prev_budget: cat.total.prev_budget ?? '',
            growth: cat.total.growth_pct !== null ? (cat.total.growth_pct >= 0 ? '+' : '') + cat.total.growth_pct + '%' : '',
            is_total: true
        });
        if (includeItems) {
            cat.codes.forEach(row => {
                rows.push({
                    category: cat.name, code: row.code, name: row.name,
                    effective: row.effective, actual: row.actual,
                    prev_budget: row.prev_budget ?? '',
                    growth: row.growth_pct !== null ? (row.growth_pct >= 0 ? '+' : '') + row.growth_pct + '%' : '',
                    is_total: false
                });
            });
        }
    });
    return rows;
}

function capexExportCSV() {
    const rows  = capexBuildRows(true);
    let headers = ['Category','Code','Account Name','Eff. Budget','YTD Actual'];
    if (CAPEX_PREV) headers = headers.concat(['Prev Budget','Growth %']);

    let csv = headers.join(',') + '\n';
    rows.forEach(r => {
        const cells = [
            `"${r.category}"`, `"${r.code}"`, `"${r.name}"`,
            r.effective, r.actual,
            ...(CAPEX_PREV ? [r.prev_budget, `"${r.growth}"`] : [])
        ];
        csv += cells.join(',') + '\n';
    });

    const a   = document.createElement('a');
    a.href    = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
    a.download = 'capex_report.csv';
    a.click();
}

function capexExportExcel() {
    const rows  = capexBuildRows(true);
    let headers = ['Category','Code','Account Name','Eff. Budget','YTD Actual'];
    if (CAPEX_PREV) headers = headers.concat(['Prev Budget','Growth %']);

    let html  = '<table border="1"><thead><tr>';
    headers.forEach(h => { html += `<th>${h}</th>`; });
    html += '</tr></thead><tbody>';
    rows.forEach(r => {
        const style = r.is_total ? ' style="font-weight:bold;background:#FFF7ED"' : '';
        html += `<tr${style}>
            <td>${r.category}</td><td>${r.code}</td><td>${r.name}</td>
            <td>${r.effective}</td><td>${r.actual}</td>
            ${CAPEX_PREV ? `<td>${r.prev_budget}</td><td>${r.growth}</td>` : ''}
        </tr>`;
    });
    html += '</tbody></table>';

    const blob = new Blob(["﻿" + html], {type:'application/vnd.ms-excel;charset=utf-8'});
    const a    = document.createElement('a');
    a.href     = URL.createObjectURL(blob);
    a.download = 'capex_report.xls';
    a.click();
}

renderCapex();
</script>
@endif
@endsection
