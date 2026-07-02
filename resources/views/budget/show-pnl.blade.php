@extends('layouts.app')
@section('title', 'Budget Entry — P&L View')

@section('content')

{{-- Page header --}}
<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0">
            {{ $budgetVersion->department->name }} —
            {{ $budgetVersion->period->name }}
            <span class="badge bg-goil-orange ms-1">v{{ $budgetVersion->version_number }}</span>
        </h5>
        <p class="text-muted small mb-0">
            Status:
            <span class="badge bg-{{ match($budgetVersion->status) {
                'draft'        => 'secondary',
                'submitted'    => 'primary',
                'under_review' => 'warning',
                'approved'     => 'success',
                'rejected'     => 'danger',
                default        => 'secondary'
            } }}">{{ ucfirst(str_replace('_',' ',$budgetVersion->status)) }}</span>
            @if($prevPeriod)
            <span class="ms-2 text-muted">Comparing with {{ $prevPeriod->year }}</span>
            @endif
        </p>
    </div>

    <div class="d-flex gap-2 align-items-center flex-wrap">
        <div class="btn-group btn-group-sm" role="group">
            <a href="{{ route('budget.show', $budgetVersion) }}" class="btn btn-outline-secondary">Classic View</a>
            <span class="btn btn-secondary" style="pointer-events:none;">P&amp;L View</span>
        </div>
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-download"></i> Export
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="{{ route('ie.budget.export', $budgetVersion) }}">
                    <i class="bi bi-file-earmark-excel me-1"></i> Classic Export (.xlsx)
                </a></li>
                <li><a class="dropdown-item" href="{{ route('ie.budget.export-pnl', $budgetVersion) }}">
                    <i class="bi bi-file-earmark-excel me-1"></i> P&amp;L Export (.xlsx)
                </a></li>
            </ul>
        </div>
        @if($budgetVersion->isEditable())
        <span id="save-status" class="text-muted small"></span>
        <button id="save-btn" class="btn btn-outline-primary btn-sm" onclick="saveBudget()">Save</button>
        <a href="{{ route('budget.confirm', $budgetVersion) }}" class="btn bg-goil-orange btn-sm">
            Submit for Approval →
        </a>
        @endif
    </div>
</div>

{{-- Summary bar --}}
<div class="card mb-3 border-0" style="background:var(--navy)">
    <div class="card-body py-2">
        <div class="row text-center text-white">
            <div class="col">
                <div class="small" style="color:rgba(255,255,255,.5)">Revenue Budget</div>
                <div class="fw-bold" id="bar-rev">
                    {{ currency() }} {{ number_format($pnlData['revenue']['totals']['effective'], 2) }}
                </div>
            </div>
            <div class="col border-start border-secondary">
                <div class="small" style="color:rgba(255,255,255,.5)">Expense Budget</div>
                <div class="fw-bold" id="bar-exp">
                    {{ currency() }} {{ number_format($pnlData['expense']['totals']['effective'], 2) }}
                </div>
            </div>
            <div class="col border-start border-secondary">
                <div class="small" style="color:rgba(255,255,255,.5)">Net Income</div>
                @php $net = $pnlData['revenue']['totals']['effective'] - $pnlData['expense']['totals']['effective']; @endphp
                <div class="fw-bold fs-5" id="bar-net" style="color:{{ $net >= 0 ? '#6EE7B7' : '#FCA5A5' }}">
                    {{ currency() }} {{ number_format($net, 2) }}
                </div>
            </div>
            @if($prevPeriod)
            <div class="col border-start border-secondary">
                <div class="small" style="color:rgba(255,255,255,.5)">Prev Net ({{ $prevPeriod->year }})</div>
                @php $prevNet = $pnlData['revenue']['totals']['prev_actual'] - $pnlData['expense']['totals']['prev_actual']; @endphp
                <div class="fw-bold" style="color:rgba(255,255,255,.7)">
                    {{ currency() }} {{ number_format($prevNet, 2) }}
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@if($budgetVersion->isEditable())
{{-- Import / Export panel --}}
<div class="chart-card mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <div style="font-size:13px;font-weight:600;color:var(--navy)">Excel Import / Export (P&L Format)</div>
            <div style="font-size:12px;color:var(--slate)">Download P&L template, fill in Excel by section, then upload.</div>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('ie.budget.download-pnl', $budgetVersion) }}"
               class="btn btn-sm btn-outline-success">↓ Download P&L Template</a>
            <a href="{{ route('ie.budget.download', $budgetVersion) }}"
               class="btn btn-sm btn-outline-secondary">↓ Classic Template</a>
            <button type="button"
                    onclick="document.getElementById('uploadPanel').classList.toggle('d-none')"
                    class="btn btn-sm btn-outline-primary">↑ Upload Excel</button>
        </div>
    </div>

    <div id="uploadPanel" class="d-none mt-3 pt-3 border-top">
        <form method="POST" action="{{ route('ie.budget.upload', $budgetVersion) }}" enctype="multipart/form-data">
            @csrf
            <div class="d-flex gap-2 align-items-end">
                <div class="flex-grow-1">
                    <label class="form-label small fw-semibold mb-1">Select filled Excel file</label>
                    <input type="file" name="file" accept=".xlsx,.xls" class="form-control form-control-sm">
                </div>
                <button type="submit" class="btn btn-sm btn-primary">Upload & Save</button>
            </div>
            <div class="form-text">Both P&L and Classic templates are accepted. Max 5MB.</div>
        </form>
    </div>

    @if(session('import_errors'))
    <div class="mt-3 pt-3 border-top">
        <div style="font-size:12px;font-weight:600;color:#991B1B;margin-bottom:6px">Import Errors:</div>
        @foreach(session('import_errors') as $err)
        <div style="font-size:11px;color:#991B1B;padding:2px 0">⚠ {{ $err }}</div>
        @endforeach
    </div>
    @endif
</div>
@endif

{{-- Tab navigation --}}
<ul class="nav nav-tabs mb-0" id="pnlViewTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pvtab-is" type="button">
            Income Statement
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pvtab-capex" type="button">
            Capital Expenditure
            @if(!empty($pnlData['capex']['categories']))
            <span class="badge bg-secondary ms-1" style="font-size:10px">{{ count($pnlData['capex']['categories']) }}</span>
            @endif
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pvtab-balance" type="button">
            Assets &amp; Liabilities
            @if(!empty($pnlData['balance']['categories']))
            <span class="badge bg-secondary ms-1" style="font-size:10px">{{ count($pnlData['balance']['categories']) }}</span>
            @endif
        </button>
    </li>
</ul>

<div class="tab-content border border-top-0 rounded-bottom" id="pnlViewTabsContent">

{{-- Tab 1: Income Statement --}}
<div class="tab-pane fade show active p-0" id="pvtab-is" role="tabpanel">
<div class="card shadow-sm border-0">
    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold small text-uppercase">Income Statement — {{ $budgetVersion->period->name }}</span>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" onclick="expandAll()">Expand All</button>
            <button class="btn btn-sm btn-outline-secondary" onclick="collapseAll()">Collapse All</button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0" id="pnl-table">
                <thead>
                    <tr style="background:#1B2A4A;color:#fff;font-size:12px">
                        <th rowspan="2" style="min-width:220px;vertical-align:middle">Account</th>
                        <th colspan="6" class="text-center border-start border-secondary py-2">
                            {{ $budgetVersion->period->year }} Budget
                        </th>
                        @if($prevPeriod)
                        <th colspan="3" class="text-center border-start border-secondary py-2">
                            {{ $prevPeriod->year }} Reference
                        </th>
                        @endif
                        @if($budgetVersion->isEditable())
                        <th rowspan="2" style="vertical-align:middle;min-width:120px">Notes</th>
                        @endif
                    </tr>
                    <tr style="background:#243B55;color:#CBD5E1;font-size:11px">
                        <th class="text-end border-start border-secondary" style="min-width:90px">Q1</th>
                        <th class="text-end" style="min-width:90px">Q2</th>
                        <th class="text-end" style="min-width:90px">Q3</th>
                        <th class="text-end" style="min-width:90px">Q4</th>
                        <th class="text-end" style="min-width:110px">Total</th>
                        <th class="text-end" style="min-width:60px">CS&nbsp;%</th>
                        @if($prevPeriod)
                        <th class="text-end border-start border-secondary" style="min-width:100px">Prev Budget</th>
                        <th class="text-end" style="min-width:100px">Prev Actual</th>
                        <th class="text-end" style="min-width:70px">Growth&nbsp;%</th>
                        @endif
                    </tr>
                </thead>
                <tbody id="pnl-tbody">
                    <tr><td colspan="20" class="text-center text-muted py-4">Loading…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

{{-- Tab 2: Capital Expenditure --}}
<div class="tab-pane fade p-0" id="pvtab-capex" role="tabpanel">
<div class="card shadow-sm border-0">
    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold small text-uppercase">Capital Expenditure — {{ $budgetVersion->period->name }}</span>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" onclick="expandAll('capex')">Expand All</button>
            <button class="btn btn-sm btn-outline-secondary" onclick="collapseAll('capex')">Collapse All</button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0" id="capex-table">
                <thead>
                    <tr style="background:#1B2A4A;color:#fff;font-size:12px">
                        <th rowspan="2" style="min-width:220px;vertical-align:middle">Account</th>
                        <th colspan="6" class="text-center border-start border-secondary py-2">
                            {{ $budgetVersion->period->year }} Budget
                        </th>
                        @if($prevPeriod)
                        <th colspan="3" class="text-center border-start border-secondary py-2">
                            {{ $prevPeriod->year }} Reference
                        </th>
                        @endif
                        @if($budgetVersion->isEditable())
                        <th rowspan="2" style="vertical-align:middle;min-width:120px">Notes</th>
                        @endif
                    </tr>
                    <tr style="background:#243B55;color:#CBD5E1;font-size:11px">
                        <th class="text-end border-start border-secondary" style="min-width:90px">Q1</th>
                        <th class="text-end" style="min-width:90px">Q2</th>
                        <th class="text-end" style="min-width:90px">Q3</th>
                        <th class="text-end" style="min-width:90px">Q4</th>
                        <th class="text-end" style="min-width:110px">Total</th>
                        <th class="text-end" style="min-width:60px">CS&nbsp;%</th>
                        @if($prevPeriod)
                        <th class="text-end border-start border-secondary" style="min-width:100px">Prev Budget</th>
                        <th class="text-end" style="min-width:100px">Prev Actual</th>
                        <th class="text-end" style="min-width:70px">Growth&nbsp;%</th>
                        @endif
                    </tr>
                </thead>
                <tbody id="capex-tbody">
                    <tr><td colspan="20" class="text-center text-muted py-4">Loading…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

{{-- Tab 3: Assets & Liabilities --}}
<div class="tab-pane fade p-0" id="pvtab-balance" role="tabpanel">
<div class="card shadow-sm border-0">
    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold small text-uppercase">Assets &amp; Liabilities — {{ $budgetVersion->period->name }}</span>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" onclick="expandAll('balance')">Expand All</button>
            <button class="btn btn-sm btn-outline-secondary" onclick="collapseAll('balance')">Collapse All</button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0" id="balance-table">
                <thead>
                    <tr style="background:#1B2A4A;color:#fff;font-size:12px">
                        <th rowspan="2" style="min-width:220px;vertical-align:middle">Account</th>
                        <th colspan="6" class="text-center border-start border-secondary py-2">
                            {{ $budgetVersion->period->year }} Budget
                        </th>
                        @if($prevPeriod)
                        <th colspan="3" class="text-center border-start border-secondary py-2">
                            {{ $prevPeriod->year }} Reference
                        </th>
                        @endif
                        @if($budgetVersion->isEditable())
                        <th rowspan="2" style="vertical-align:middle;min-width:120px">Notes</th>
                        @endif
                    </tr>
                    <tr style="background:#243B55;color:#CBD5E1;font-size:11px">
                        <th class="text-end border-start border-secondary" style="min-width:90px">Q1</th>
                        <th class="text-end" style="min-width:90px">Q2</th>
                        <th class="text-end" style="min-width:90px">Q3</th>
                        <th class="text-end" style="min-width:90px">Q4</th>
                        <th class="text-end" style="min-width:110px">Total</th>
                        <th class="text-end" style="min-width:60px">CS&nbsp;%</th>
                        @if($prevPeriod)
                        <th class="text-end border-start border-secondary" style="min-width:100px">Prev Budget</th>
                        <th class="text-end" style="min-width:100px">Prev Actual</th>
                        <th class="text-end" style="min-width:70px">Growth&nbsp;%</th>
                        @endif
                    </tr>
                </thead>
                <tbody id="balance-tbody">
                    <tr><td colspan="20" class="text-center text-muted py-4">Loading…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

</div>{{-- end tab-content --}}

@push('scripts')
<script>
const PNL      = @json($pnlData);
const HAS_PREV = {{ $prevPeriod ? 'true' : 'false' }};
const EDITABLE = {{ $budgetVersion->isEditable() ? 'true' : 'false' }};
const SAVE_URL = "{{ route('budget.save', $budgetVersion) }}";
const CSRF     = "{{ csrf_token() }}";
const CUR      = "{{ currency() }}";

let autoSaveTimer = null;
let isSaving = false;

// ── Formatting helpers ───────────────────────────────────
function numFmt(v) {
    return parseFloat(v || 0).toLocaleString('en-GH', {minimumFractionDigits:2, maximumFractionDigits:2});
}

function escHtml(s) {
    const d = document.createElement('div');
    d.textContent = s || '';
    return d.innerHTML;
}

function escAttr(s) {
    return (s || '').replace(/&/g,'&amp;').replace(/"/g,'&quot;');
}

function growthHtml(type, current, prev) {
    if (!prev) return '<span class="text-muted">—</span>';
    const g = ((current - prev) / Math.abs(prev)) * 100;
    // Revenue: positive growth = good (green); Expense: positive growth = bad (red)
    const isGood = type === 'revenue' ? g >= 0 : g <= 0;
    const color  = isGood ? '#10B981' : '#F43F5E';
    const sign   = g >= 0 ? '+' : '';
    return `<span style="color:${color}">${sign}${g.toFixed(1)}%</span>`;
}

// ── Render the full table ────────────────────────────────
function renderPnl() {
    // Income Statement tab
    let html = '';
    html += renderSection('revenue', 'REVENUE INCOME');
    html += '<tr style="height:6px;background:#F8FAFC"><td colspan="99"></td></tr>';
    html += renderSection('expense', 'OPERATING EXPENSES');
    html += renderNetRow();
    document.getElementById('pnl-tbody').innerHTML = html;

    // CapEx tab
    const capexTbody = document.getElementById('capex-tbody');
    if (capexTbody) {
        if (PNL.capex && PNL.capex.categories.length > 0) {
            capexTbody.innerHTML = renderSection('capex', 'CAPITAL EXPENDITURE');
        } else {
            capexTbody.innerHTML = '<tr><td colspan="20" class="text-center text-muted py-5">' +
                '<i class="bi bi-inbox d-block mb-2" style="font-size:2rem;opacity:.3"></i>' +
                'No Capital Expenditure items in this section yet.</td></tr>';
        }
    }

    // Balance tab
    const balanceTbody = document.getElementById('balance-tbody');
    if (balanceTbody) {
        if (PNL.balance && PNL.balance.categories.length > 0) {
            balanceTbody.innerHTML = renderSection('balance', 'ASSETS & LIABILITIES');
        } else {
            balanceTbody.innerHTML = '<tr><td colspan="20" class="text-center text-muted py-5">' +
                '<i class="bi bi-inbox d-block mb-2" style="font-size:2rem;opacity:.3"></i>' +
                'No Assets &amp; Liabilities items in this section yet.</td></tr>';
        }
    }

    // Attach listeners across all rendered tbodies
    document.querySelectorAll('tr[data-item-id] .q-input').forEach(inp => {
        inp.addEventListener('input', function() {
            const row  = this.closest('tr');
            const type = row.dataset.type;
            recomputeRow(row);
            recomputeCategory(row.dataset.catId, type);
            recomputeSection(type);
            if (type === 'revenue' || type === 'expense') recomputeNetRow();
            scheduleAutoSave();
        });
    });

    document.querySelectorAll('tr[data-item-id] .notes-input').forEach(inp => {
        inp.addEventListener('input', scheduleAutoSave);
    });
}

function getSectionStyle(type) {
    return {
        revenue: { bg:'#1B2A4A', light:'#EFF3F9', totalBg:'#1E3A5F' },
        expense: { bg:'#7C2D12', light:'#FFF7ED', totalBg:'#431407' },
        capex:   { bg:'#78350F', light:'#FEF3C7', totalBg:'#92400E' },
        balance: { bg:'#4C1D95', light:'#EDE9FE', totalBg:'#5B21B6' },
    }[type] || { bg:'#1B2A4A', light:'#EFF3F9', totalBg:'#1E3A5F' };
}

function renderSection(type, label) {
    let html = '';
    const sec   = PNL[type];
    const { bg, light } = getSectionStyle(type);

    html += `<tr style="background:${bg};color:#fff;">
        <td colspan="99" style="font-size:11px;font-weight:700;letter-spacing:1px;padding:8px 12px">
            ${label}
        </td>
    </tr>`;

    sec.categories.forEach((cat, catIdx) => {
        const catId     = `${type}_${catIdx}`;
        const collapsed = cat.items.length > 8;

        // Category row
        html += `<tr class="pnl-cat-row" id="crow_${catId}" data-type="${type}"
                     style="background:${light};cursor:pointer;font-size:12px;font-weight:600"
                     onclick="toggleCat('${catId}')">
            <td style="padding-left:14px">
                <i class="fas fa-chevron-${collapsed ? 'right' : 'down'} me-2 small" id="icon_${catId}"></i>
                ${escHtml(cat.name)}
                <span class="text-muted fw-normal ms-1" style="font-size:11px">(${cat.items.length})</span>
            </td>
            <td class="text-end border-start" id="cq1_${catId}">${numFmt(cat.totals.q1)}</td>
            <td class="text-end" id="cq2_${catId}">${numFmt(cat.totals.q2)}</td>
            <td class="text-end" id="cq3_${catId}">${numFmt(cat.totals.q3)}</td>
            <td class="text-end" id="cq4_${catId}">${numFmt(cat.totals.q4)}</td>
            <td class="text-end fw-bold" id="ceff_${catId}">${numFmt(cat.totals.effective)}</td>
            <td class="text-end text-muted" id="ccs_${catId}">${numFmt(cat.totals.common_size)}%</td>
            ${HAS_PREV ? `
            <td class="text-end border-start text-muted" id="cpb_${catId}">${numFmt(cat.totals.prev_budget)}</td>
            <td class="text-end text-muted" id="cpa_${catId}">${numFmt(cat.totals.prev_actual)}</td>
            <td class="text-end" id="cgr_${catId}">${growthHtml(type, cat.totals.effective, cat.totals.prev_actual)}</td>
            ` : ''}
            ${EDITABLE ? '<td></td>' : ''}
        </tr>`;

        // Item rows
        cat.items.forEach(item => {
            const suppBadge = item.supp > 0
                ? `<span class="badge ms-1" style="background:#D1FAE5;color:#065F46;font-size:10px">+${numFmt(item.supp)} supp</span>`
                : '';

            const q1i = EDITABLE
                ? `<input type="number" class="form-control form-control-sm q-input q1" value="${item.q1}" min="0" step="0.01" style="min-width:85px;text-align:right">`
                : `<span class="text-end d-block">${numFmt(item.q1)}</span>`;
            const q2i = EDITABLE
                ? `<input type="number" class="form-control form-control-sm q-input q2" value="${item.q2}" min="0" step="0.01" style="min-width:85px;text-align:right">`
                : `<span class="text-end d-block">${numFmt(item.q2)}</span>`;
            const q3i = EDITABLE
                ? `<input type="number" class="form-control form-control-sm q-input q3" value="${item.q3}" min="0" step="0.01" style="min-width:85px;text-align:right">`
                : `<span class="text-end d-block">${numFmt(item.q3)}</span>`;
            const q4i = EDITABLE
                ? `<input type="number" class="form-control form-control-sm q-input q4" value="${item.q4}" min="0" step="0.01" style="min-width:85px;text-align:right">`
                : `<span class="text-end d-block">${numFmt(item.q4)}</span>`;
            const noteI = EDITABLE
                ? `<input type="text" class="form-control form-control-sm notes-input" value="${escAttr(item.justification)}" placeholder="Notes">`
                : (escHtml(item.justification) || '');

            html += `<tr class="pnl-item-row ${collapsed ? 'd-none' : ''}" style="font-size:12px"
                        data-item-id="${item.id}" data-cat-id="${catId}" data-type="${type}" data-supp="${item.supp}">
                <td style="padding-left:2.2rem" class="small">
                    <code class="text-muted me-1" style="font-size:11px">${escHtml(item.code)}</code>${escHtml(item.name)}${suppBadge}
                </td>
                ${EDITABLE
                    ? `<td class="p-1 border-start">${q1i}</td><td class="p-1">${q2i}</td><td class="p-1">${q3i}</td><td class="p-1">${q4i}</td>`
                    : `<td class="text-end border-start">${q1i}</td><td class="text-end">${q2i}</td><td class="text-end">${q3i}</td><td class="text-end">${q4i}</td>`}
                <td class="text-end fw-semibold item-effective">${numFmt(item.effective)}</td>
                <td class="text-end text-muted small item-cs">${numFmt(item.common_size)}%</td>
                ${HAS_PREV ? `
                <td class="text-end text-muted small border-start">${numFmt(item.prev_budget)}</td>
                <td class="text-end text-muted small">${numFmt(item.prev_actual)}</td>
                <td class="text-end small">${growthHtml(type, item.effective, item.prev_actual)}</td>
                ` : ''}
                ${EDITABLE ? `<td class="p-1">${noteI}</td>` : ''}
            </tr>`;
        });
    });

    // Section total row
    const t  = sec.totals;
    const { totalBg: bg2 } = getSectionStyle(type);
    html += `<tr id="st_${type}" style="background:${bg2};color:#fff;font-weight:700;font-size:12px;border-top:2px solid #fff">
        <td style="padding-left:12px;font-size:13px">TOTAL ${label}</td>
        <td class="text-end border-start border-secondary" id="sq1_${type}">${numFmt(t.q1)}</td>
        <td class="text-end" id="sq2_${type}">${numFmt(t.q2)}</td>
        <td class="text-end" id="sq3_${type}">${numFmt(t.q3)}</td>
        <td class="text-end" id="sq4_${type}">${numFmt(t.q4)}</td>
        <td class="text-end" id="seff_${type}">${numFmt(t.effective)}</td>
        <td class="text-end">100%</td>
        ${HAS_PREV ? `
        <td class="text-end border-start border-secondary" id="spb_${type}">${numFmt(t.prev_budget)}</td>
        <td class="text-end" id="spa_${type}">${numFmt(t.prev_actual)}</td>
        <td class="text-end" id="sgr_${type}">${growthHtml(type, t.effective, t.prev_actual)}</td>
        ` : ''}
        ${EDITABLE ? '<td></td>' : ''}
    </tr>`;

    return html;
}

function renderNetRow() {
    const revT = PNL.revenue.totals;
    const expT = PNL.expense.totals;
    const net  = revT.effective - expT.effective;
    const prevNetB = revT.prev_budget - expT.prev_budget;
    const prevNetA = revT.prev_actual - expT.prev_actual;
    const netColor = net >= 0 ? '#6EE7B7' : '#FCA5A5';

    return `<tr id="net-row" style="background:#0F172A;color:#fff;font-weight:700;font-size:13px;border-top:3px solid #E2E8F0">
        <td style="padding-left:12px">NET INCOME / (LOSS)</td>
        <td class="text-end border-start border-secondary" id="ni-q1">${numFmt(revT.q1 - expT.q1)}</td>
        <td class="text-end" id="ni-q2">${numFmt(revT.q2 - expT.q2)}</td>
        <td class="text-end" id="ni-q3">${numFmt(revT.q3 - expT.q3)}</td>
        <td class="text-end" id="ni-q4">${numFmt(revT.q4 - expT.q4)}</td>
        <td class="text-end fs-6 fw-bold" id="ni-eff" style="color:${netColor}">${numFmt(net)}</td>
        <td class="text-end">—</td>
        ${HAS_PREV ? `
        <td class="text-end border-start border-secondary" id="ni-prev-b">${numFmt(prevNetB)}</td>
        <td class="text-end" id="ni-prev-a">${numFmt(prevNetA)}</td>
        <td class="text-end" id="ni-growth">${growthHtml('revenue', net, prevNetA)}</td>
        ` : ''}
        ${EDITABLE ? '<td></td>' : ''}
    </tr>`;
}

// ── Live recalculation ───────────────────────────────────
function recomputeRow(row) {
    const q1   = parseFloat(row.querySelector('.q1')?.value) || 0;
    const q2   = parseFloat(row.querySelector('.q2')?.value) || 0;
    const q3   = parseFloat(row.querySelector('.q3')?.value) || 0;
    const q4   = parseFloat(row.querySelector('.q4')?.value) || 0;
    const supp = parseFloat(row.dataset.supp) || 0;
    const total     = q1 + q2 + q3 + q4;
    const effective = total + supp;
    const el = s => row.querySelector(s);
    if (el('.item-effective')) el('.item-effective').textContent = numFmt(effective);
}

function recomputeCategory(catId, type) {
    let q1=0,q2=0,q3=0,q4=0,effective=0;
    document.querySelectorAll(`tr.pnl-item-row[data-cat-id="${catId}"]`).forEach(row => {
        const rq1 = parseFloat(row.querySelector('.q1')?.value) || 0;
        const rq2 = parseFloat(row.querySelector('.q2')?.value) || 0;
        const rq3 = parseFloat(row.querySelector('.q3')?.value) || 0;
        const rq4 = parseFloat(row.querySelector('.q4')?.value) || 0;
        const supp = parseFloat(row.dataset.supp) || 0;
        q1 += rq1; q2 += rq2; q3 += rq3; q4 += rq4;
        effective += rq1+rq2+rq3+rq4+supp;
    });
    const g = id => document.getElementById(id);
    if (g(`cq1_${catId}`))  g(`cq1_${catId}`).textContent  = numFmt(q1);
    if (g(`cq2_${catId}`))  g(`cq2_${catId}`).textContent  = numFmt(q2);
    if (g(`cq3_${catId}`))  g(`cq3_${catId}`).textContent  = numFmt(q3);
    if (g(`cq4_${catId}`))  g(`cq4_${catId}`).textContent  = numFmt(q4);
    if (g(`ceff_${catId}`)) g(`ceff_${catId}`).textContent = numFmt(effective);
}

function recomputeSection(type) {
    let q1=0,q2=0,q3=0,q4=0,effective=0;
    document.querySelectorAll(`tr.pnl-item-row[data-type="${type}"]`).forEach(row => {
        const rq1 = parseFloat(row.querySelector('.q1')?.value) || 0;
        const rq2 = parseFloat(row.querySelector('.q2')?.value) || 0;
        const rq3 = parseFloat(row.querySelector('.q3')?.value) || 0;
        const rq4 = parseFloat(row.querySelector('.q4')?.value) || 0;
        const supp = parseFloat(row.dataset.supp) || 0;
        q1 += rq1; q2 += rq2; q3 += rq3; q4 += rq4;
        effective += rq1+rq2+rq3+rq4+supp;
    });

    const g = id => document.getElementById(id);
    if (g(`sq1_${type}`))  g(`sq1_${type}`).textContent  = numFmt(q1);
    if (g(`sq2_${type}`))  g(`sq2_${type}`).textContent  = numFmt(q2);
    if (g(`sq3_${type}`))  g(`sq3_${type}`).textContent  = numFmt(q3);
    if (g(`sq4_${type}`))  g(`sq4_${type}`).textContent  = numFmt(q4);
    if (g(`seff_${type}`)) g(`seff_${type}`).textContent = numFmt(effective);

    // Update common size for all items and categories in this section
    if (effective > 0) {
        document.querySelectorAll(`tr.pnl-item-row[data-type="${type}"]`).forEach(row => {
            const rq1 = parseFloat(row.querySelector('.q1')?.value) || 0;
            const rq2 = parseFloat(row.querySelector('.q2')?.value) || 0;
            const rq3 = parseFloat(row.querySelector('.q3')?.value) || 0;
            const rq4 = parseFloat(row.querySelector('.q4')?.value) || 0;
            const supp = parseFloat(row.dataset.supp) || 0;
            const reff = rq1+rq2+rq3+rq4+supp;
            const csEl = row.querySelector('.item-cs');
            if (csEl) csEl.textContent = (reff / effective * 100).toFixed(2) + '%';
        });

        document.querySelectorAll(`tr.pnl-cat-row[data-type="${type}"]`).forEach(catRow => {
            const catId = catRow.id.replace('crow_', '');
            const ceffEl = document.getElementById(`ceff_${catId}`);
            const ccsEl  = document.getElementById(`ccs_${catId}`);
            if (ceffEl && ccsEl) {
                const ceff = parseFloat(ceffEl.textContent.replace(/,/g, '')) || 0;
                ccsEl.textContent = (ceff / effective * 100).toFixed(2) + '%';
            }
        });
    }

    // Update summary bar
    const barRev = type === 'revenue' ? effective : null;
    const barExp = type === 'expense' ? effective : null;
    updateSummaryBar(barRev, barExp);
}

function recomputeNetRow() {
    const getNum = id => {
        const el = document.getElementById(id);
        return el ? parseFloat(el.textContent.replace(/,/g,'')) || 0 : 0;
    };
    const q1  = getNum('sq1_revenue')   - getNum('sq1_expense');
    const q2  = getNum('sq2_revenue')   - getNum('sq2_expense');
    const q3  = getNum('sq3_revenue')   - getNum('sq3_expense');
    const q4  = getNum('sq4_revenue')   - getNum('sq4_expense');
    const eff = getNum('seff_revenue') - getNum('seff_expense');

    const g = id => document.getElementById(id);
    if (g('ni-q1'))    g('ni-q1').textContent    = numFmt(q1);
    if (g('ni-q2'))    g('ni-q2').textContent    = numFmt(q2);
    if (g('ni-q3'))    g('ni-q3').textContent    = numFmt(q3);
    if (g('ni-q4'))    g('ni-q4').textContent    = numFmt(q4);
    if (g('ni-eff')) {
        g('ni-eff').textContent = numFmt(eff);
        g('ni-eff').style.color = eff >= 0 ? '#6EE7B7' : '#FCA5A5';
    }
}

// Update summary bar from current DOM totals
function updateSummaryBar(forceRev, forceExp) {
    const getNum = id => {
        const el = document.getElementById(id);
        return el ? parseFloat(el.textContent.replace(/,/g,'')) || 0 : 0;
    };
    const rev = forceRev !== null ? forceRev : getNum('seff_revenue');
    const exp = forceExp !== null ? forceExp : getNum('seff_expense');
    const net = rev - exp;

    const barRev = document.getElementById('bar-rev');
    const barExp = document.getElementById('bar-exp');
    const barNet = document.getElementById('bar-net');

    if (barRev) barRev.textContent = CUR + ' ' + numFmt(rev);
    if (barExp) barExp.textContent = CUR + ' ' + numFmt(exp);
    if (barNet) {
        barNet.textContent = CUR + ' ' + numFmt(net);
        barNet.style.color = net >= 0 ? '#6EE7B7' : '#FCA5A5';
    }
}

// ── Category expand/collapse ─────────────────────────────
function toggleCat(catId) {
    const icon = document.getElementById(`icon_${catId}`);
    const rows = document.querySelectorAll(`.pnl-item-row[data-cat-id="${catId}"]`);
    const isCollapsed = rows.length > 0 && rows[0].classList.contains('d-none');
    rows.forEach(r => r.classList.toggle('d-none', !isCollapsed));
    if (icon) {
        icon.classList.toggle('fa-chevron-right', !isCollapsed);
        icon.classList.toggle('fa-chevron-down', isCollapsed);
    }
}

function expandAll(type) {
    const sel = type ? `.pnl-item-row[data-type="${type}"]` : '.pnl-item-row';
    const iSel = type ? `tr.pnl-cat-row[data-type="${type}"] i[id^="icon_"]` : '[id^="icon_"]';
    document.querySelectorAll(sel).forEach(r => r.classList.remove('d-none'));
    document.querySelectorAll(iSel).forEach(i => {
        i.classList.remove('fa-chevron-right');
        i.classList.add('fa-chevron-down');
    });
}

function collapseAll(type) {
    const sel = type ? `.pnl-item-row[data-type="${type}"]` : '.pnl-item-row';
    const iSel = type ? `tr.pnl-cat-row[data-type="${type}"] i[id^="icon_"]` : '[id^="icon_"]';
    document.querySelectorAll(sel).forEach(r => r.classList.add('d-none'));
    document.querySelectorAll(iSel).forEach(i => {
        i.classList.remove('fa-chevron-down');
        i.classList.add('fa-chevron-right');
    });
}

// ── Auto-save ────────────────────────────────────────────
function collectItems() {
    return Array.from(document.querySelectorAll('tr[data-item-id]')).map(row => ({
        id:    row.dataset.itemId,
        q1:    parseFloat(row.querySelector('.q1')?.value)    || 0,
        q2:    parseFloat(row.querySelector('.q2')?.value)    || 0,
        q3:    parseFloat(row.querySelector('.q3')?.value)    || 0,
        q4:    parseFloat(row.querySelector('.q4')?.value)    || 0,
        notes: row.querySelector('.notes-input')?.value       || '',
    }));
}

function scheduleAutoSave() {
    clearTimeout(autoSaveTimer);
    const status = document.getElementById('save-status');
    if (status) status.textContent = 'Unsaved changes…';
    autoSaveTimer = setTimeout(saveBudget, 3000);
}

async function saveBudget() {
    if (isSaving) return;
    isSaving = true;

    const btn    = document.getElementById('save-btn');
    const status = document.getElementById('save-status');

    if (btn)    btn.disabled = true;
    if (status) status.textContent = 'Saving…';

    try {
        const res = await fetch(SAVE_URL, {
            method:  'POST',
            headers: {
                'Content-Type':  'application/json',
                'X-CSRF-TOKEN':  CSRF,
                'Accept':        'application/json',
            },
            body: JSON.stringify({ items: collectItems() }),
        });

        if (!res.ok) {
            if (status) status.textContent = 'Save failed (' + res.status + ')';
            return;
        }

        const data = await res.json();

        if (data.success) {
            if (status) status.textContent = 'Saved at ' + data.saved_at;
        } else {
            if (status) status.textContent = 'Save failed.';
        }
    } catch (e) {
        console.error('Network error:', e);
        if (status) status.textContent = 'Network error — not saved.';
    } finally {
        isSaving = false;
        if (btn) btn.disabled = false;
    }
}

document.addEventListener('DOMContentLoaded', renderPnl);
</script>
@endpush

@endsection
