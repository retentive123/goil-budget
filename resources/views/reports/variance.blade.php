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

{{-- Summary --}}
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
            <div class="stat-accent" style="background:#64748B"></div>
            <div class="stat-label">Total Actual</div>
            <div class="stat-value" style="font-size:18px">
                {{ currency() }} {{ number_format($summary['total_actual'],0) }}
            </div>
            <div class="stat-sub">Actuals module coming soon</div>
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

{{-- Variance horizontal bar chart --}}
<div class="chart-card mb-4">
    <div class="chart-title">Variance by Account Code (Top 20)</div>
    <canvas id="varianceBar" height="140"></canvas>
</div>

{{-- Pre-compute flat item arrays from varianceData --}}
@php
    $varianceCatsData = [];
    foreach ($varianceData as $catName => $codes) {
        $items = [];
        foreach ($codes as $code => $row) {
            $items[] = [
                'code'   => $code,
                'name'   => $row['name'],
                'orig'   => $row['original'] ?? $row['budget'],
                'supp'   => $row['supplementary'],
                'budget' => $row['budget'],
                'actual' => $row['actual'],
                'var'    => $row['variance'],
                'pct'    => $row['pct'],
            ];
        }
        $varianceCatsData[] = ['name' => $catName, 'items' => $items];
    }
@endphp

{{-- Search + export toolbar --}}
<div class="chart-card mb-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-search text-muted" style="font-size:13px"></i>
            <input type="text" id="varSearch"
                   class="form-control form-control-sm"
                   placeholder="Search code or account name across all categories…"
                   style="width:340px">
            <span class="small text-muted" id="varSearchInfo" style="white-space:nowrap"></span>
        </div>
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                    data-bs-toggle="dropdown" style="font-size:12px">
                <i class="fas fa-download me-1"></i>Export
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:13px">
                <li>
                    <a class="dropdown-item" href="#" onclick="varExport('csv');return false">
                        <i class="fas fa-file-csv me-2 text-success"></i>CSV (all codes)
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="#" onclick="varExport('json');return false">
                        <i class="fas fa-file-code me-2 text-primary"></i>JSON (all codes)
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
    </div>
</div>

{{-- Search results container (hidden until user types) --}}
<div id="varSearchResults" class="chart-card mb-3" style="display:none">
    <div class="chart-title mb-2" id="varSearchTitle"></div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#64748B">
                <tr>
                    <th>Code</th>
                    <th>Account</th>
                    <th>Category</th>
                    <th class="text-end">Original Budget</th>
                    <th class="text-end">Supplementary</th>
                    <th class="text-end">Effective Budget</th>
                    <th class="text-end">Actual</th>
                    <th class="text-end">Variance</th>
                    <th class="text-end">%</th>
                </tr>
            </thead>
            <tbody id="varSearchBody"></tbody>
        </table>
    </div>
</div>

{{-- Category cards — static shells; rows injected by JS --}}
@foreach($varianceCatsData as $catIndex => $catInfo)
<div class="chart-card mb-3 var-cat-card" id="var_card_{{ $catIndex }}">
    <div class="chart-title">{{ $catInfo['name'] }}</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#64748B">
                <tr>
                    <th>Code</th>
                    <th>Account</th>
                    <th class="text-end">Original Budget</th>
                    <th class="text-end">Supplementary</th>
                    <th class="text-end">Effective Budget</th>
                    <th class="text-end">Actual</th>
                    <th class="text-end">Variance</th>
                    <th class="text-end">%</th>
                </tr>
            </thead>
            <tbody id="tbody_var_{{ $catIndex }}"></tbody>
        </table>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-2"
         id="var_footer_{{ $catIndex }}">
        <span class="small text-muted" id="var_info_{{ $catIndex }}"></span>
        <button class="btn btn-sm btn-outline-secondary" style="font-size:12px"
                id="var_toggle_{{ $catIndex }}"
                onclick="toggleVarCat({{ $catIndex }})"></button>
    </div>
</div>
@endforeach

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Variance category row renderer ─────────────────────────────────────────
const VAR_CATS    = @json($varianceCatsData);
const VAR_INITIAL = 10;
const varExpanded = new Array(VAR_CATS.length).fill(false);

function numFmt2(n) {
    return Number(n ?? 0).toLocaleString('en-GH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function buildVarRow(r, withCat) {
    const suppCell = r.supp > 0
        ? `<td class="text-end" style="color:#92400E">+${numFmt2(r.supp)}</td>`
        : `<td class="text-end">—</td>`;
    // negative variance (actual > budget) = bad = red; positive = good = green
    const varColor = r.var < 0 ? '#F43F5E' : '#10B981';
    const catCell  = withCat ? `<td class="small text-muted">${esc(r._cat)}</td>` : '';
    return `<tr>
        <td><code>${esc(r.code)}</code></td>
        <td>${esc(r.name)}</td>
        ${catCell}
        <td class="text-end">${numFmt2(r.orig)}</td>
        ${suppCell}
        <td class="text-end fw-bold">${numFmt2(r.budget)}</td>
        <td class="text-end">${numFmt2(r.actual)}</td>
        <td class="text-end" style="color:${varColor}">${r.var > 0 ? '+' : ''}${numFmt2(r.var)}</td>
        <td class="text-end" style="color:${varColor}">${r.pct > 0 ? '+' : ''}${r.pct}%</td>
    </tr>`;
}

function renderVarCat(idx) {
    const cat      = VAR_CATS[idx];
    const expanded = varExpanded[idx];
    const items    = cat.items;
    const shown    = expanded ? items : items.slice(0, VAR_INITIAL);

    document.getElementById(`tbody_var_${idx}`).innerHTML =
        shown.length
            ? shown.map(r => buildVarRow(r, false)).join('')
            : '<tr><td colspan="8" class="text-muted text-center py-3">No items.</td></tr>';

    const footer   = document.getElementById(`var_footer_${idx}`);
    const infoEl   = document.getElementById(`var_info_${idx}`);
    const toggleEl = document.getElementById(`var_toggle_${idx}`);

    if (items.length <= VAR_INITIAL) {
        footer.style.display = 'none';
    } else {
        footer.style.display = '';
        infoEl.textContent   = expanded
            ? `All ${items.length} items shown`
            : `Showing ${VAR_INITIAL} of ${items.length} items`;
        toggleEl.textContent = expanded
            ? 'Show less'
            : `Show all ${items.length} items`;
    }
}

function toggleVarCat(idx) {
    varExpanded[idx] = !varExpanded[idx];
    renderVarCat(idx);
}

VAR_CATS.forEach((_, i) => renderVarCat(i));

// ── Global search ──────────────────────────────────────────────────────────
document.getElementById('varSearch').addEventListener('input', function () {
    const q          = this.value.trim().toLowerCase();
    const catCards   = document.querySelectorAll('.var-cat-card');
    const resultsDiv = document.getElementById('varSearchResults');
    const infoEl     = document.getElementById('varSearchInfo');

    if (!q) {
        catCards.forEach(el => el.style.display = '');
        resultsDiv.style.display = 'none';
        infoEl.textContent = '';
        return;
    }

    catCards.forEach(el => el.style.display = 'none');
    resultsDiv.style.display = '';

    const matches = [];
    VAR_CATS.forEach(cat => {
        cat.items.forEach(r => {
            if (r.code.toLowerCase().includes(q) || r.name.toLowerCase().includes(q)) {
                matches.push({ ...r, _cat: cat.name });
            }
        });
    });

    document.getElementById('varSearchTitle').textContent =
        `${matches.length} result${matches.length !== 1 ? 's' : ''} for "${this.value}"`;
    infoEl.textContent = `${matches.length} match${matches.length !== 1 ? 'es' : ''}`;

    document.getElementById('varSearchBody').innerHTML =
        matches.length
            ? matches.map(r => buildVarRow(r, true)).join('')
            : '<tr><td colspan="9" class="text-center text-muted py-4">No matching codes found.</td></tr>';
});

// ── Export ─────────────────────────────────────────────────────────────────
function varExport(format) {
    const q = document.getElementById('varSearch').value.trim().toLowerCase();
    const rows = [];
    VAR_CATS.forEach(cat => {
        cat.items.forEach(r => {
            if (!q || r.code.toLowerCase().includes(q) || r.name.toLowerCase().includes(q)) {
                rows.push({ ...r, category: cat.name });
            }
        });
    });

    const headers = ['Category','Code','Account','Original Budget','Supplementary',
                     'Effective Budget','Actual','Variance','Variance %'];
    const rowData = r => [r.category, r.code, r.name, r.orig, r.supp,
                          r.budget, r.actual, r.var, r.pct];
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

// ── Chart.js ───────────────────────────────────────────────────────────────
@php
    $allRows = [];
    foreach($varianceData as $cat => $codes) {
        foreach($codes as $code => $row) {
            $allRows[] = ['code'=>$code,'variance'=>$row['variance'],'budget'=>$row['budget']];
        }
    }
    usort($allRows, fn($a,$b) => abs($b['variance']) <=> abs($a['variance']));
    $top20 = array_slice($allRows, 0, 20);
@endphp
new Chart(document.getElementById('varianceBar'), {
    type: 'bar',
    data: {
        labels: {!! json_encode(array_column($top20,'code')) !!},
        datasets: [
            {
                label: 'Budget',
                data:  {!! json_encode(array_column($top20,'budget')) !!},
                backgroundColor: '#E2E8F0',
                borderRadius: 4, borderSkipped: false,
            },
            {
                label: 'Variance',
                data:  {!! json_encode(array_column($top20,'variance')) !!},
                backgroundColor: {!! json_encode(array_map(
                    fn($r) => $r['variance'] < 0 ? '#FCA5A5' : '#6EE7B7',
                    $top20
                )) !!},
                borderRadius: 4, borderSkipped: false,
            }
        ]
    },
    options: {
        responsive:true,
        plugins:{ legend:{ position:'top', labels:{font:{size:11},boxWidth:12} } },
        scales:{
            y:{ beginAtZero:true, grid:{color:'#F1F5F9'},
                ticks:{ font:{size:11},
                    callback:v=>v>=1000000?(v/1000000).toFixed(1)+'M':v>=1000?(v/1000).toFixed(0)+'K':v
                }
            },
            x:{ grid:{display:false}, ticks:{font:{size:10}} }
        }
    }
});
</script>
@endif
@endsection
