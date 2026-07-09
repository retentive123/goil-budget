@extends('layouts.app')
@section('title', 'Variance Analysis')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Variance Analysis</h5>
        <p class="text-muted small mb-0">
            <a href="{{ route('reports.index') }}" class="text-muted">Reports</a>
            / Variance
        </p>
    </div>
    @can('export reports')
    <a href="{{ route('reports.export.variance', request()->query()) }}"
       class="btn btn-sm btn-outline-success">Export Excel</a>
    @endcan
</div>

<form method="GET" class="chart-card mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Period</label>
            <select name="period_id" class="form-select form-select-sm">
                @foreach($periods as $p)
                <option value="{{ $p->id }}"
                    {{ request('period_id',$period?->id)==$p->id?'selected':'' }}>
                    {{ $p->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Department</label>
            <select name="department_id" class="form-select form-select-sm">
                <option value="">All</option>
                @foreach($departments as $d)
                <option value="{{ $d->id }}"
                    {{ request('department_id')==$d->id?'selected':'' }}>
                    {{ $d->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Show</label>
            <select name="variance_filter" class="form-select form-select-sm">
                <option value="all"   {{ $varianceFilter=='all'  ?'selected':'' }}>All</option>
                <option value="over"  {{ $varianceFilter=='over' ?'selected':'' }}>Overspend only</option>
                <option value="under" {{ $varianceFilter=='under'?'selected':'' }}>Underspend only</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Min Variance %</label>
            <input type="number" name="min_variance"
                   value="{{ $minVariancePct }}"
                   class="form-control form-control-sm"
                   min="0" max="100" step="1"
                   placeholder="0">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm w-100"
                    style="background:var(--navy);color:#fff;border-radius:8px">
                Apply
            </button>
        </div>
    </div>
</form>

{{-- Summary KPI cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-accent" style="background:var(--navy)"></div>
            <div class="stat-label">Total Budget</div>
            <div class="stat-value" style="font-size:18px">
                {{ currency() }} {{ number_format($summary['total_budget'],0) }}
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-accent" style="background:#10B981"></div>
            <div class="stat-label">Total Actual</div>
            <div class="stat-value" style="font-size:18px">
                {{ currency() }} {{ number_format($summary['total_actual'],0) }}
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-accent" style="background:#10B981"></div>
            <div class="stat-label">On Budget Lines</div>
            <div class="stat-value">{{ $summary['on_budget'] }}</div>
        </div>
    </div>
</div>

@if(empty($varianceData))
<div class="chart-card text-center py-5 text-muted">
    No data matching the selected filters.
</div>
@else

{{-- Charts row 1: Budget Type grouped bar + Status donut --}}
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="chart-card h-100">
            <div class="chart-title">Budget vs Actual by Type</div>
            <canvas id="typeBar" height="160"></canvas>
        </div>
    </div>
    <div class="col-md-4">
        <div class="chart-card h-100">
            <div class="chart-title">Variance Status</div>
            <canvas id="statusDonut" height="160"></canvas>
        </div>
    </div>
</div>

{{-- Charts row 2: Category + Department --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="chart-card h-100">
            <div class="chart-title">Variance by Category</div>
            <canvas id="catBar" height="200"></canvas>
        </div>
    </div>
    <div class="col-md-6">
        <div class="chart-card h-100">
            <div class="chart-title">Variance by Department</div>
            <canvas id="deptBar" height="200"></canvas>
        </div>
    </div>
</div>

{{-- Flatten varianceData into a single array with category + budget_type attached --}}
@php
    $varAllRows    = [];
    $varCatNames   = [];
    $varTypeMap    = []; // budget_type_key => human label

    $budgetTypeLabels = [
        'revenue'             => 'Revenue',
        'expense'             => 'Expense',
        'both'                => 'Revenue & Expense',
        'capital_expenditure' => 'Capital Expenditure',
        'assets'              => 'Assets',
        'liabilities'         => 'Liabilities',
    ];

    foreach ($varianceData as $catName => $codes) {
        $varCatNames[] = $catName;
        foreach ($codes as $code => $row) {
            $typeKey   = $row['budget_type'] ?? 'expense';
            $typeLabel = $budgetTypeLabels[$typeKey] ?? ucfirst(str_replace('_', ' ', $typeKey));
            $varTypeMap[$typeKey] = $typeLabel;
            $varAllRows[] = [
                'cat'       => $catName,
                'type_key'  => $typeKey,
                'type_label'=> $typeLabel,
                'code'      => $code,
                'name'      => $row['name'],
                'orig'      => $row['original'] ?? $row['budget'],
                'supp'      => $row['supplementary'],
                'budget'    => $row['budget'],
                'actual'    => $row['actual'],
                'var'       => $row['variance'],
                'pct'       => $row['pct'],
            ];
        }
    }
    sort($varCatNames);
    ksort($varTypeMap);
@endphp

@push('styles')
<style>
.var-check-dd .dropdown-menu {
    min-width: 220px;
    max-height: 280px;
    overflow-y: auto;
    padding: 8px 0;
}
.var-check-dd .dd-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 14px;
    cursor: pointer;
    font-size: 12px;
    color: #374151;
    white-space: nowrap;
}
.var-check-dd .dd-item:hover { background: #F8FAFC; }
.var-check-dd .dd-item input[type=checkbox] { cursor: pointer; accent-color: #1B2A4A; }
.var-check-dd .dd-divider { border-top: 1px solid #E2E8F0; margin: 4px 0; }
.var-check-dd .dd-label-all { font-weight: 700; color: #1B2A4A; }
.var-filter-badge {
    display: inline-flex;
    align-items: center;
    background: #1B2A4A;
    color: #fff;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 700;
    padding: 1px 7px;
    margin-left: 4px;
    vertical-align: middle;
}
</style>
@endpush

{{-- Combined table card --}}
<div class="chart-card" id="varianceTableCard">

    {{-- Row 1: title + search + export --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="chart-title mb-0">
            All Variance Lines
            <span class="text-muted fw-normal small" id="varRowCount"></span>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                        data-bs-toggle="dropdown" data-bs-auto-close="outside"
                        style="font-size:12px">
                    <i class="fas fa-download me-1"></i>Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:13px">
                    <li>
                        <a class="dropdown-item" href="#"
                           onclick="varExport('csv');return false">
                            <i class="fas fa-file-csv me-2 text-success"></i>CSV (filtered)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#"
                           onclick="varExport('json');return false">
                            <i class="fas fa-file-code me-2 text-primary"></i>JSON (filtered)
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item" href="#" id="varCopyBtn"
                           onclick="varExport('copy');return false">
                            <i class="fas fa-copy me-2 text-muted"></i>Copy (TSV)
                        </a>
                    </li>
                </ul>
            </div>
            <input type="text" id="varSearch"
                   class="form-control form-control-sm"
                   placeholder="Search code, account or category…"
                   style="width:260px">
        </div>
    </div>

    {{-- Row 2: filter dropdowns + page size --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="d-flex gap-2 flex-wrap">

            {{-- Budget Type dropdown --}}
            <div class="dropdown var-check-dd">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                        id="typeDdBtn"
                        data-bs-toggle="dropdown" data-bs-auto-close="outside"
                        style="font-size:12px">
                    Budget Type
                </button>
                <div class="dropdown-menu shadow-sm" id="typeDdMenu">
                    <label class="dd-item dd-label-all">
                        <input type="checkbox" id="typeAll" checked
                               onchange="handleTypeAll(this)">
                        All Types
                    </label>
                    <div class="dd-divider"></div>
                    @foreach($varTypeMap as $tKey => $tLabel)
                    <label class="dd-item">
                        <input type="checkbox" class="type-cb" value="{{ $tKey }}"
                               onchange="handleTypeCb()">
                        {{ $tLabel }}
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Category dropdown --}}
            <div class="dropdown var-check-dd">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                        id="catDdBtn"
                        data-bs-toggle="dropdown" data-bs-auto-close="outside"
                        style="font-size:12px">
                    Category
                </button>
                <div class="dropdown-menu shadow-sm" id="catDdMenu">
                    <label class="dd-item dd-label-all">
                        <input type="checkbox" id="catAll" checked
                               onchange="handleCatAll(this)">
                        All Categories
                    </label>
                    <div class="dd-divider"></div>
                    @foreach($varCatNames as $cn)
                    <label class="dd-item">
                        <input type="checkbox" class="cat-cb" value="{{ $cn }}"
                               onchange="handleCatCb()">
                        {{ $cn }}
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Active filter pills --}}
            <div id="varActivePills" class="d-flex align-items-center gap-1 flex-wrap"></div>

        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="small text-muted">Show</span>
            <select id="varPageSize" class="form-select form-select-sm" style="width:auto"
                    onchange="varCurrentPage=1;varRender()">
                <option value="25">25</option>
                <option value="50" selected>50</option>
                <option value="100">100</option>
                <option value="all">All</option>
            </select>
            <span class="small text-muted">per page</span>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead style="font-size:11px;text-transform:uppercase;
                          letter-spacing:.5px;color:var(--slate)">
                <tr>
                    <th>Code</th>
                    <th>Account</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th class="text-end">Orig Budget</th>
                    <th class="text-end">Supplementary</th>
                    <th class="text-end">Effective Budget</th>
                    <th class="text-end">Actual</th>
                    <th class="text-end">Variance</th>
                    <th class="text-end">Var %</th>
                </tr>
            </thead>
            <tbody id="varTableBody"></tbody>
            <tfoot style="background:#F8FAFC;font-weight:700;font-size:12px">
                <tr>
                    <td colspan="6">Total (visible)</td>
                    <td class="text-end" id="varFootBudget"></td>
                    <td class="text-end" id="varFootActual"></td>
                    <td class="text-end" id="varFootVar"></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
        <span class="small text-muted" id="varPageInfo"></span>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary"
                    id="varPrev" onclick="varPage(-1)">← Prev</button>
            <button class="btn btn-sm btn-outline-secondary"
                    id="varNext" onclick="varPage(1)">Next →</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Data ───────────────────────────────────────────────────────────────────
const VAR_ROWS = @json($varAllRows);
const TYPE_LABELS = @json($varTypeMap);      // { expense: 'Expense', ... }

// ── State ──────────────────────────────────────────────────────────────────
let varSelTypes    = new Set();  // empty = all
let varSelCats     = new Set();  // empty = all
let varSearchTerm  = '';
let varCurrentPage = 1;

// ── Filter helpers ──────────────────────────────────────────────────────────
function getFiltered() {
    const q = varSearchTerm.toLowerCase();
    return VAR_ROWS.filter(r => {
        const typeOk   = varSelTypes.size === 0 || varSelTypes.has(r.type_key);
        const catOk    = varSelCats.size  === 0 || varSelCats.has(r.cat);
        const termOk   = !q ||
            r.code.toLowerCase().includes(q) ||
            r.name.toLowerCase().includes(q) ||
            r.cat.toLowerCase().includes(q) ||
            r.type_label.toLowerCase().includes(q);
        return typeOk && catOk && termOk;
    });
}

// ── Budget type checkbox handlers ──────────────────────────────────────────
function handleTypeAll(el) {
    document.querySelectorAll('.type-cb').forEach(cb => cb.checked = false);
    varSelTypes.clear();
    varCurrentPage = 1;
    updateTypeBtnLabel();
    updatePills();
    varRender();
}
function handleTypeCb() {
    const checked = [...document.querySelectorAll('.type-cb:checked')];
    varSelTypes   = new Set(checked.map(cb => cb.value));
    document.getElementById('typeAll').checked = varSelTypes.size === 0;
    varCurrentPage = 1;
    updateTypeBtnLabel();
    updatePills();
    varRender();
}

// ── Category checkbox handlers ─────────────────────────────────────────────
function handleCatAll(el) {
    document.querySelectorAll('.cat-cb').forEach(cb => cb.checked = false);
    varSelCats.clear();
    varCurrentPage = 1;
    updateCatBtnLabel();
    updatePills();
    varRender();
}
function handleCatCb() {
    const checked = [...document.querySelectorAll('.cat-cb:checked')];
    varSelCats    = new Set(checked.map(cb => cb.value));
    document.getElementById('catAll').checked = varSelCats.size === 0;
    varCurrentPage = 1;
    updateCatBtnLabel();
    updatePills();
    varRender();
}

// ── Button label updaters ──────────────────────────────────────────────────
function updateTypeBtnLabel() {
    const btn = document.getElementById('typeDdBtn');
    if (varSelTypes.size === 0) {
        btn.innerHTML = 'Budget Type';
    } else {
        btn.innerHTML = `Budget Type <span class="var-filter-badge">${varSelTypes.size}</span>`;
    }
}
function updateCatBtnLabel() {
    const btn = document.getElementById('catDdBtn');
    if (varSelCats.size === 0) {
        btn.innerHTML = 'Category';
    } else {
        btn.innerHTML = `Category <span class="var-filter-badge">${varSelCats.size}</span>`;
    }
}

// ── Active filter pills ────────────────────────────────────────────────────
function updatePills() {
    const container = document.getElementById('varActivePills');
    container.innerHTML = '';

    varSelTypes.forEach(t => {
        const label = TYPE_LABELS[t] || t;
        const pill  = document.createElement('span');
        pill.style.cssText = `display:inline-flex;align-items:center;gap:4px;
            background:#EFF6FF;color:#1D4ED8;border:1px solid #BFDBFE;
            border-radius:12px;font-size:11px;font-weight:600;padding:2px 8px;
            cursor:pointer;white-space:nowrap`;
        pill.innerHTML = `${esc(label)} <span style="opacity:.7">×</span>`;
        pill.onclick   = () => {
            document.querySelector(`.type-cb[value="${t}"]`).checked = false;
            handleTypeCb();
        };
        container.appendChild(pill);
    });

    varSelCats.forEach(c => {
        const pill = document.createElement('span');
        pill.style.cssText = `display:inline-flex;align-items:center;gap:4px;
            background:#F0FDF4;color:#15803D;border:1px solid #BBF7D0;
            border-radius:12px;font-size:11px;font-weight:600;padding:2px 8px;
            cursor:pointer;white-space:nowrap`;
        pill.innerHTML = `${esc(c)} <span style="opacity:.7">×</span>`;
        pill.onclick   = () => {
            document.querySelector(`.cat-cb[value="${CSS.escape(c)}"]`).checked = false;
            handleCatCb();
        };
        container.appendChild(pill);
    });
}

// ── Row builder ────────────────────────────────────────────────────────────
function numFmt(n) {
    return Number(n ?? 0).toLocaleString('en-GH', {
        minimumFractionDigits: 2, maximumFractionDigits: 2
    });
}
function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function buildRow(r) {
    const suppCell = r.supp > 0
        ? `<td class="text-end" style="color:#92400E">+${numFmt(r.supp)}</td>`
        : `<td class="text-end text-muted">—</td>`;
    const varColor = r.var < 0 ? '#F43F5E' : '#10B981';
    return `<tr>
        <td><code style="font-size:11px;font-weight:700;color:#1B2A4A">${esc(r.code)}</code></td>
        <td class="small fw-semibold">${esc(r.name)}</td>
        <td>
            <span style="font-size:10px;background:#F1F5F9;color:#475569;
                         border-radius:10px;padding:2px 8px;white-space:nowrap">
                ${esc(r.cat)}
            </span>
        </td>
        <td>
            <span style="font-size:10px;background:#EFF6FF;color:#1D4ED8;
                         border-radius:10px;padding:2px 8px;white-space:nowrap">
                ${esc(r.type_label)}
            </span>
        </td>
        <td class="text-end small text-muted">${numFmt(r.orig)}</td>
        ${suppCell}
        <td class="text-end small fw-semibold">${numFmt(r.budget)}</td>
        <td class="text-end small" style="color:#10B981">${numFmt(r.actual)}</td>
        <td class="text-end small fw-semibold" style="color:${varColor}">
            ${r.var > 0 ? '+' : ''}${numFmt(r.var)}
        </td>
        <td class="text-end small fw-semibold" style="color:${varColor}">
            ${r.pct > 0 ? '+' : ''}${r.pct}%
        </td>
    </tr>`;
}

// ── Render ─────────────────────────────────────────────────────────────────
function varRender() {
    const filtered = getFiltered();
    const pageSzEl = document.getElementById('varPageSize');
    const pageSz   = pageSzEl.value === 'all' ? Infinity : parseInt(pageSzEl.value);
    const total    = filtered.length;
    const pages    = pageSz === Infinity ? 1 : Math.max(1, Math.ceil(total / pageSz));

    if (varCurrentPage > pages) varCurrentPage = 1;

    const start = pageSz === Infinity ? 0 : (varCurrentPage - 1) * pageSz;
    const end   = pageSz === Infinity ? total : Math.min(start + pageSz, total);
    const slice = filtered.slice(start, end);

    document.getElementById('varTableBody').innerHTML =
        slice.length
            ? slice.map(buildRow).join('')
            : '<tr><td colspan="10" class="text-center text-muted py-4">No matching lines.</td></tr>';

    // Footer totals
    const fBudget = slice.reduce((s, r) => s + Number(r.budget), 0);
    const fActual = slice.reduce((s, r) => s + Number(r.actual), 0);
    const fVar    = slice.reduce((s, r) => s + Number(r.var),    0);
    document.getElementById('varFootBudget').textContent =
        '{{ currency() }} ' + numFmt(fBudget);
    document.getElementById('varFootActual').style.color  = '#10B981';
    document.getElementById('varFootActual').textContent  =
        '{{ currency() }} ' + numFmt(fActual);
    document.getElementById('varFootVar').style.color    = fVar < 0 ? '#F43F5E' : '#10B981';
    document.getElementById('varFootVar').textContent    =
        (fVar >= 0 ? '+' : '') + '{{ currency() }} ' + numFmt(fVar);

    document.getElementById('varRowCount').textContent =
        total > 0 ? `— ${total} line${total !== 1 ? 's' : ''}` : '';
    document.getElementById('varPageInfo').textContent =
        pageSz === Infinity
            ? `Showing all ${total} lines`
            : `Showing ${total === 0 ? 0 : start + 1}–${end} of ${total}`;

    document.getElementById('varPrev').disabled = varCurrentPage <= 1;
    document.getElementById('varNext').disabled = varCurrentPage >= pages;
}

function varPage(dir) {
    varCurrentPage += dir;
    varRender();
}

document.getElementById('varSearch').addEventListener('input', function() {
    varSearchTerm  = this.value.trim();
    varCurrentPage = 1;
    varRender();
});

// Initial render
varRender();

// ── Export ─────────────────────────────────────────────────────────────────
function varExport(format) {
    const rows    = getFiltered();
    const headers = ['Category','Budget Type','Code','Account','Original Budget',
                     'Supplementary','Effective Budget','Actual','Variance','Variance %'];
    const rowData = r => [r.cat, r.type_label, r.code, r.name,
                          r.orig, r.supp, r.budget, r.actual, r.var, r.pct];
    const stamp   = new Date().toISOString().slice(0, 10);

    if (format === 'csv') {
        const csv = [headers, ...rows.map(rowData)]
            .map(c => c.map(v => `"${String(v ?? '').replace(/"/g,'""')}"`).join(','))
            .join('\n');
        dlBlob(csv, `variance-${stamp}.csv`, 'text/csv;charset=utf-8;');
    }
    if (format === 'json') {
        const json = JSON.stringify(
            rows.map(r => Object.fromEntries(headers.map((h, i) => [h, rowData(r)[i]]))),
            null, 2
        );
        dlBlob(json, `variance-${stamp}.json`, 'application/json');
    }
    if (format === 'copy') {
        const tsv = [headers, ...rows.map(rowData)].map(c => c.join('\t')).join('\n');
        navigator.clipboard.writeText(tsv).then(() => {
            const btn  = document.getElementById('varCopyBtn');
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check me-2 text-success"></i>Copied!';
            setTimeout(() => { btn.innerHTML = orig; }, 2000);
        });
    }
}

function dlBlob(content, filename, mime) {
    const a = Object.assign(document.createElement('a'), {
        href:     URL.createObjectURL(new Blob([content], { type: mime })),
        download: filename,
    });
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

// ── Shared helpers ─────────────────────────────────────────────────────────
const NAVY    = '#1B2A4A';
const EMERALD = '#10B981';
const SLATE   = '#64748B';

function fmtK(v) {
    v = Number(v);
    return v >= 1e6  ? (v/1e6).toFixed(1)+'M'
         : v >= 1000 ? (v/1000).toFixed(0)+'K'
         : v.toFixed(0);
}

const scaleY = {
    beginAtZero: true,
    grid: { color: '#F1F5F9' },
    ticks: { font:{ size:10 }, callback: fmtK }
};
const scaleYH = {   // for horizontal charts (index axis = y)
    grid: { display: false },
    ticks: { font:{ size:10 } }
};
const scaleXH = {
    beginAtZero: true,
    grid: { color: '#F1F5F9' },
    ticks: { font:{ size:10 }, callback: fmtK }
};

// ── 1. Budget vs Actual by Type (grouped vertical bar) ────────────────────
@php
    $typeKeys     = array_keys($typeSummary);
    $typeLabelsJs = array_column($typeSummary, 'label');
    $typeBudgets  = array_column($typeSummary, 'budget');
    $typeActuals  = array_column($typeSummary, 'actual');
@endphp
new Chart(document.getElementById('typeBar'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($typeLabelsJs) !!},
        datasets: [
            {
                label: 'Budget',
                data:  {!! json_encode($typeBudgets) !!},
                backgroundColor: NAVY,
                borderRadius: 4, borderSkipped: false,
            },
            {
                label: 'Actual',
                data:  {!! json_encode($typeActuals) !!},
                backgroundColor: EMERALD,
                borderRadius: 4, borderSkipped: false,
            },
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position:'top', labels:{ font:{size:11}, boxWidth:12 } },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${ctx.dataset.label}: {{ currency() }} ${ctx.parsed.y.toLocaleString()}`
                }
            }
        },
        scales: { y: scaleY, x: { grid:{ display:false }, ticks:{ font:{size:11} } } }
    }
});

// ── 2. Variance Status donut ───────────────────────────────────────────────
new Chart(document.getElementById('statusDonut'), {
    type: 'doughnut',
    data: {
        labels: ['Underspend / Favorable', 'On Budget', 'Overspend / Unfavorable'],
        datasets: [{
            data: [
                {{ $summary['underspend'] }},
                {{ $summary['on_budget'] }},
                {{ $summary['overspend'] }},
            ],
            backgroundColor: ['#6EE7B7', '#CBD5E1', '#FCA5A5'],
            borderWidth: 2,
            borderColor: '#fff',
            hoverOffset: 6,
        }]
    },
    options: {
        cutout: '62%',
        plugins: {
            legend: { position:'bottom', labels:{ font:{size:11}, padding:10, boxWidth:10 } },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${ctx.label}: ${ctx.parsed} line${ctx.parsed !== 1 ? 's' : ''}`
                }
            }
        }
    }
});

// ── 3. Variance by Category (horizontal bar) ──────────────────────────────
@php
    $catNames     = array_keys($catSummary);
    $catVariances = array_column($catSummary, 'variance');
    $catBgColors  = array_map(fn($v) => $v < 0 ? '#FCA5A5' : '#6EE7B7', $catSummary);
@endphp
new Chart(document.getElementById('catBar'), {
    type: 'bar',
    indexAxis: 'y',
    data: {
        labels: {!! json_encode($catNames) !!},
        datasets: [{
            label: 'Variance',
            data:  {!! json_encode($catVariances) !!},
            backgroundColor: {!! json_encode(array_values($catBgColors)) !!},
            borderRadius: 4,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => {
                        const v = ctx.parsed.x;
                        return ` ${v >= 0 ? 'Underspend' : 'Overspend'}: {{ currency() }} ${Math.abs(v).toLocaleString()}`;
                    }
                }
            }
        },
        scales: { y: scaleYH, x: scaleXH }
    }
});

// ── 4. Variance by Department (horizontal bar) ────────────────────────────
@php
    $deptNames     = array_keys($deptSummary);
    $deptVariances = array_column($deptSummary, 'variance');
    $deptBgColors  = array_map(fn($v) => $v < 0 ? '#FCA5A5' : '#6EE7B7', $deptSummary);
@endphp
new Chart(document.getElementById('deptBar'), {
    type: 'bar',
    indexAxis: 'y',
    data: {
        labels: {!! json_encode($deptNames) !!},
        datasets: [{
            label: 'Variance',
            data:  {!! json_encode($deptVariances) !!},
            backgroundColor: {!! json_encode(array_values($deptBgColors)) !!},
            borderRadius: 4,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => {
                        const v = ctx.parsed.x;
                        return ` ${v >= 0 ? 'Underspend' : 'Overspend'}: {{ currency() }} ${Math.abs(v).toLocaleString()}`;
                    }
                }
            }
        },
        scales: { y: scaleYH, x: scaleXH }
    }
});
</script>
@endif
@endsection
