@extends('layouts.app')
@section('title', 'Department Drill-down')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Department Drill-down</h5>
        <p class="text-muted small mb-0">
            <a href="{{ route('reports.index') }}" class="text-muted">Reports</a>
            / Department
        </p>
    </div>
    @can('export reports')
    <a href="{{ route('reports.export.approved', request()->query()) }}"
       class="btn btn-sm btn-outline-success">Export Excel</a>
    @endcan
</div>

{{-- Filters --}}
<form method="GET" class="chart-card mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Period</label>
            <select name="period_id" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach($periods as $p)
                <option value="{{ $p->id }}"
                    {{ request('period_id', $period?->id) == $p->id ? 'selected' : '' }}>
                    {{ $p->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Department</label>
            <select name="department_id" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach($departments as $d)
                <option value="{{ $d->id }}"
                    {{ request('department_id', $department?->id) == $d->id ? 'selected' : '' }}>
                    {{ $d->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Category</label>
            <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Categories</option>
                @foreach($categories as $c)
                <option value="{{ $c->id }}"
                    {{ request('category_id') == $c->id ? 'selected' : '' }}>
                    {{ $c->name }}
                </option>
                @endforeach
            </select>
        </div>
    </div>
</form>

@if(!$version)
<div class="chart-card text-center py-5 text-muted">
    No approved budget found for {{ $department?->name }} in {{ $period?->name }}.
</div>
@else

{{-- KPI strip --}}
@php
    $totalBudget = $quarterSums['total'];
    $totalOriginal = $quarterSums['original'] ?? $quarterSums['total'];
    $totalSupplementary = $quarterSums['supplementary'] ?? 0;
@endphp
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:var(--navy)"></div>
            <div class="stat-label">Effective Budget</div>
            <div class="stat-value" style="font-size:20px">
                {{ currency() }} {{ number_format($totalBudget,0) }}
            </div>
            @if($totalSupplementary > 0)
            <div class="stat-sub" style="color:#10B981">
                +{{ number_format($totalSupplementary,0) }} supplementary
            </div>
            @endif
        </div>
    </div>
    @foreach(['q1'=>'Q1','q2'=>'Q2','q3'=>'Q3','q4'=>'Q4'] as $k => $label)
    <div class="col-md-2">
        <div class="stat-card">
            <div class="stat-label">{{ $label }}</div>
            <div class="stat-value" style="font-size:16px">
                {{ number_format($quarterSums[$k],0) }}
            </div>
            <div class="stat-sub">
                @php $pct = $totalBudget > 0 ? round(($quarterSums[$k]/$totalBudget)*100,1) : 0; @endphp
                {{ $pct }}% of total
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Charts --}}
<div class="row g-3 mb-4">

    {{-- Quarterly bar --}}
    <div class="col-md-5">
        <div class="chart-card h-100">
            <div class="chart-title">Quarterly Split</div>
            <canvas id="quarterSplit" height="200"></canvas>
        </div>
    </div>

    {{-- Category donut --}}
    <div class="col-md-3">
        <div class="chart-card h-100">
            <div class="chart-title">By Category</div>
            <canvas id="catDonut" height="200"></canvas>
        </div>
    </div>

    {{-- YoY trend --}}
    <div class="col-md-4">
        <div class="chart-card h-100">
            <div class="chart-title">Year-over-Year Trend</div>
            @if(count($yoyData) > 1)
            <canvas id="yoyLine" height="200"></canvas>
            @else
            <div class="text-muted small text-center py-4">
                Only one period available.
            </div>
            @endif
        </div>
    </div>

</div>

{{-- Pre-compute all item data in PHP (avoids calling model methods inside the render loop) --}}
@php
    $grandTotal        = $quarterSums['total'];
    $codeExplorerUrl   = route('reports.code-explorer');
    $categoriesData    = [];

    foreach ($byCategory as $catName => $catData) {
        $catSupp  = $catData['supplementary'] ?? 0;
        $items    = [];
        foreach ($catData['items'] as $item) {
            $supp = $item->approvedSupplementaryTotal();
            $eff  = $item->effectiveBudget();
            $pct  = $grandTotal > 0 ? round(($eff / $grandTotal) * 100, 1) : 0;
            $items[] = [
                'code'      => $item->accountCode->code,
                'name'      => $item->accountCode->name,
                'code_id'   => $item->account_code_id,
                'q1'        => $item->q1_amount,
                'q2'        => $item->q2_amount,
                'q3'        => $item->q3_amount,
                'q4'        => $item->q4_amount,
                'supp'      => $supp,
                'effective' => $eff,
                'pct'       => $pct,
            ];
        }
        $categoriesData[] = [
            'name'  => $catName,
            'total' => $catData['total'],
            'supp'  => $catSupp,
            'q1'    => $catData['q1'],
            'q2'    => $catData['q2'],
            'q3'    => $catData['q3'],
            'q4'    => $catData['q4'],
            'items' => $items,
        ];
    }
@endphp

{{-- Search + export toolbar --}}
<div class="chart-card mb-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-search text-muted" style="font-size:13px"></i>
            <input type="text" id="deptSearch"
                   class="form-control form-control-sm"
                   placeholder="Search code or account name across all categories…"
                   style="width:340px">
            <span class="small text-muted" id="deptSearchInfo" style="white-space:nowrap"></span>
        </div>
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                    data-bs-toggle="dropdown" style="font-size:12px">
                <i class="fas fa-download me-1"></i>Export
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:13px">
                <li>
                    <a class="dropdown-item" href="#" onclick="deptExport('csv');return false">
                        <i class="fas fa-file-csv me-2 text-success"></i>CSV (all codes)
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="#" onclick="deptExport('json');return false">
                        <i class="fas fa-file-code me-2 text-primary"></i>JSON (all codes)
                    </a>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <a class="dropdown-item" href="#" id="deptCopyBtn"
                       onclick="deptExport('copy');return false">
                        <i class="fas fa-copy me-2 text-muted"></i>Copy (TSV)
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

{{-- Search results container (hidden until user types) --}}
<div id="deptSearchResults" class="chart-card mb-3" style="display:none">
    <div class="chart-title mb-2" id="deptSearchTitle"></div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--slate)">
                <tr>
                    <th>Code</th>
                    <th>Account Name</th>
                    <th>Category</th>
                    <th class="text-end">Q1</th>
                    <th class="text-end">Q2</th>
                    <th class="text-end">Q3</th>
                    <th class="text-end">Q4</th>
                    <th class="text-end">Supplementary</th>
                    <th class="text-end">Total</th>
                    <th>Split</th>
                </tr>
            </thead>
            <tbody id="deptSearchBody"></tbody>
        </table>
    </div>
</div>

{{-- Category cards — static shells; rows injected by JS --}}
@foreach($categoriesData as $catIndex => $catInfo)
@php $catSupp = $catInfo['supp']; @endphp
<div class="chart-card mb-3 dept-cat-card" id="cat_card_{{ $catIndex }}">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="chart-title mb-0">{{ $catInfo['name'] }}</div>
        <div style="font-size:13px;font-weight:600;color:var(--navy)">
            {{ currency() }} {{ number_format($catInfo['total'],2) }}
            @if($catSupp > 0)
            <span style="font-size:11px;color:#10B981;font-weight:400">
                (+{{ number_format($catSupp,2) }} supp.)
            </span>
            @endif
            <span style="font-size:11px;color:var(--slate);font-weight:400">
                &nbsp;(Q1: {{ number_format($catInfo['q1'],0) }}
                / Q2: {{ number_format($catInfo['q2'],0) }}
                / Q3: {{ number_format($catInfo['q3'],0) }}
                / Q4: {{ number_format($catInfo['q4'],0) }})
            </span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead style="font-size:11px;text-transform:uppercase;
                          letter-spacing:.5px;color:var(--slate)">
                <tr>
                    <th>Code</th>
                    <th>Account Name</th>
                    <th class="text-end">Q1</th>
                    <th class="text-end">Q2</th>
                    <th class="text-end">Q3</th>
                    <th class="text-end">Q4</th>
                    <th class="text-end">Supplementary</th>
                    <th class="text-end">Total</th>
                    <th>Split</th>
                </tr>
            </thead>
            <tbody id="tbody_cat_{{ $catIndex }}"></tbody>
            <tfoot style="background:#F8FAFC;font-weight:700;font-size:12px">
                <tr>
                    <td colspan="2">Category Total</td>
                    <td class="text-end">{{ number_format($catInfo['q1'], 2) }}</td>
                    <td class="text-end">{{ number_format($catInfo['q2'], 2) }}</td>
                    <td class="text-end">{{ number_format($catInfo['q3'], 2) }}</td>
                    <td class="text-end">{{ number_format($catInfo['q4'], 2) }}</td>
                    <td class="text-end" style="color:{{ $catSupp > 0 ? '#10B981' : 'inherit' }}">
                        {{ $catSupp > 0 ? '+'.number_format($catSupp, 2) : '—' }}
                    </td>
                    <td class="text-end">{{ number_format($catInfo['total'], 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    {{-- Show more / show less toggle --}}
    <div class="d-flex justify-content-between align-items-center mt-2"
         id="cat_footer_{{ $catIndex }}">
        <span class="small text-muted" id="cat_info_{{ $catIndex }}"></span>
        <button class="btn btn-sm btn-outline-secondary" style="font-size:12px"
                id="cat_toggle_{{ $catIndex }}"
                onclick="toggleCat({{ $catIndex }})"></button>
    </div>
</div>
@endforeach

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Category row renderer ──────────────────────────────────────────────────
const DEPT_CATS        = @json($categoriesData);
const CODE_EXPLORER_URL = @json($codeExplorerUrl);
const DEPT_PERIOD_ID   = {{ $period->id }};
const CAT_INITIAL      = 10;

const catExpanded = new Array(DEPT_CATS.length).fill(false);

function numFmt(n) {
    return Number(n ?? 0).toLocaleString('en-GH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function buildRow(item, catName) {
    const link = `${CODE_EXPLORER_URL}?period_id=${DEPT_PERIOD_ID}&account_code_id=${item.code_id}`;
    const suppCell = item.supp > 0
        ? `<td class="text-end small" style="color:#10B981">+${numFmt(item.supp)}</td>`
        : `<td class="text-end small">—</td>`;
    const catCell  = catName !== undefined ? `<td class="small text-muted">${esc(catName)}</td>` : '';
    return `<tr>
        <td><a href="${link}" style="color:var(--navy);font-weight:600;font-size:12px;font-family:monospace">${esc(item.code)}</a></td>
        <td class="small">${esc(item.name)}</td>
        ${catCell}
        <td class="text-end small">${numFmt(item.q1)}</td>
        <td class="text-end small">${numFmt(item.q2)}</td>
        <td class="text-end small">${numFmt(item.q3)}</td>
        <td class="text-end small">${numFmt(item.q4)}</td>
        ${suppCell}
        <td class="text-end small fw-semibold">${numFmt(item.effective)}</td>
        <td style="min-width:80px">
            <div class="progress" style="height:5px">
                <div class="progress-bar" style="width:${item.pct}%;background:var(--navy)"></div>
            </div>
            <div style="font-size:10px;color:#64748B">${item.pct}%</div>
        </td>
    </tr>`;
}

function renderCat(idx) {
    const cat      = DEPT_CATS[idx];
    const expanded = catExpanded[idx];
    const items    = cat.items;
    const shown    = expanded ? items : items.slice(0, CAT_INITIAL);

    document.getElementById(`tbody_cat_${idx}`).innerHTML =
        shown.length ? shown.map(r => buildRow(r)).join('')
                     : '<tr><td colspan="9" class="text-muted text-center py-3">No items.</td></tr>';

    const footer   = document.getElementById(`cat_footer_${idx}`);
    const infoEl   = document.getElementById(`cat_info_${idx}`);
    const toggleEl = document.getElementById(`cat_toggle_${idx}`);

    if (items.length <= CAT_INITIAL) {
        footer.style.display = 'none';
    } else {
        footer.style.display = '';
        infoEl.textContent   = expanded
            ? `All ${items.length} items shown`
            : `Showing ${CAT_INITIAL} of ${items.length} items`;
        toggleEl.textContent = expanded
            ? 'Show less'
            : `Show all ${items.length} items`;
    }
}

function toggleCat(idx) {
    catExpanded[idx] = !catExpanded[idx];
    renderCat(idx);
}

// Initial render
DEPT_CATS.forEach((_, i) => renderCat(i));

// ── Global search ──────────────────────────────────────────────────────────
document.getElementById('deptSearch').addEventListener('input', function () {
    const q           = this.value.trim().toLowerCase();
    const catCards    = document.querySelectorAll('.dept-cat-card');
    const resultsDiv  = document.getElementById('deptSearchResults');
    const infoEl      = document.getElementById('deptSearchInfo');

    if (!q) {
        catCards.forEach(el => el.style.display = '');
        resultsDiv.style.display = 'none';
        infoEl.textContent = '';
        return;
    }

    catCards.forEach(el => el.style.display = 'none');
    resultsDiv.style.display = '';

    const matches = [];
    DEPT_CATS.forEach(cat => {
        cat.items.forEach(item => {
            if (item.code.toLowerCase().includes(q) || item.name.toLowerCase().includes(q)) {
                matches.push({ ...item, _cat: cat.name });
            }
        });
    });

    document.getElementById('deptSearchTitle').textContent =
        `${matches.length} result${matches.length !== 1 ? 's' : ''} for "${this.value}"`;
    infoEl.textContent = `${matches.length} match${matches.length !== 1 ? 'es' : ''}`;

    document.getElementById('deptSearchBody').innerHTML =
        matches.length
            ? matches.map(r => buildRow(r, r._cat)).join('')
            : '<tr><td colspan="10" class="text-center text-muted py-4">No matching codes found.</td></tr>';
});

// ── Export ─────────────────────────────────────────────────────────────────
function deptExport(format) {
    const q = document.getElementById('deptSearch').value.trim().toLowerCase();
    const allItems = [];
    DEPT_CATS.forEach(cat => {
        cat.items.forEach(item => {
            if (!q || item.code.toLowerCase().includes(q) || item.name.toLowerCase().includes(q)) {
                allItems.push({ ...item, category: cat.name });
            }
        });
    });

    const headers = ['Category','Code','Account','Q1','Q2','Q3','Q4','Supplementary','Effective Total','% of Dept'];
    const rowData = r => [r.category, r.code, r.name, r.q1, r.q2, r.q3, r.q4, r.supp, r.effective, r.pct];
    const datestamp = new Date().toISOString().slice(0, 10);
    const filename  = `dept-report-${datestamp}`;

    if (format === 'csv') {
        const csv = [headers, ...allItems.map(rowData)]
            .map(cells => cells.map(c => `"${String(c ?? '').replace(/"/g, '""')}"`).join(','))
            .join('\n');
        dlBlob(csv, filename + '.csv', 'text/csv;charset=utf-8;');
    }
    if (format === 'json') {
        const json = JSON.stringify(
            allItems.map(r => Object.fromEntries(headers.map((h, i) => [h, rowData(r)[i]]))),
            null, 2
        );
        dlBlob(json, filename + '.json', 'application/json');
    }
    if (format === 'copy') {
        const tsv = [headers, ...allItems.map(rowData)].map(c => c.join('\t')).join('\n');
        navigator.clipboard.writeText(tsv).then(() => {
            const btn  = document.getElementById('deptCopyBtn');
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

const COLORS = ['#1B2A4A','#C9A84C','#10B981','#6366F1','#F59E0B',
                '#EC4899','#14B8A6','#8B5CF6','#F97316','#06B6D4'];

// Quarterly split
new Chart(document.getElementById('quarterSplit'), {
    type: 'bar',
    data: {
        labels: ['Q1','Q2','Q3','Q4'],
        datasets: [{
            data: [{{ $quarterSums['q1'] }},{{ $quarterSums['q2'] }},{{ $quarterSums['q3'] }},{{ $quarterSums['q4'] }}],
            backgroundColor: ['#1B2A4A','#C9A84C','#10B981','#6366F1'],
            borderRadius: 8, borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend:{ display:false } },
        scales: {
            y:{ beginAtZero:true, grid:{color:'#F1F5F9'},
                ticks:{ font:{size:11},
                    callback:v=>v>=1000000?(v/1000000).toFixed(1)+'M':v>=1000?(v/1000).toFixed(0)+'K':v
                }
            },
            x:{ grid:{display:false} }
        }
    }
});

// Category donut
@php $catNames=[]; $catVals=[]; foreach($byCategory as $c=>$d){$catNames[]=$c; $catVals[]=$d['total'];} @endphp
new Chart(document.getElementById('catDonut'), {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($catNames) !!},
        datasets: [{
            data: {!! json_encode($catVals) !!},
            backgroundColor: COLORS,
            borderWidth: 2, borderColor: '#fff',
        }]
    },
    options: {
        cutout: '60%',
        plugins: { legend:{ position:'bottom', labels:{ font:{size:10}, padding:8, boxWidth:10 } } }
    }
});

// YoY Line
@if(count($yoyData) > 1)
new Chart(document.getElementById('yoyLine'), {
    type: 'line',
    data: {
        labels: {!! json_encode(array_column($yoyData,'name')) !!},
        datasets: [{
            label: 'Total Budget ({{ currency() }})',
            data:  {!! json_encode(array_column($yoyData,'total')) !!},
            borderColor: '#1B2A4A',
            backgroundColor: 'rgba(27,42,74,.08)',
            borderWidth: 2,
            pointBackgroundColor: '#C9A84C',
            pointRadius: 5,
            fill: true,
            tension: 0.4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend:{ display:false } },
        scales: {
            y:{ beginAtZero:true, grid:{color:'#F1F5F9'},
                ticks:{ font:{size:11},
                    callback:v=>v>=1000000?(v/1000000).toFixed(1)+'M':v>=1000?(v/1000).toFixed(0)+'K':v
                }
            },
            x:{ grid:{display:false}, ticks:{font:{size:11}} }
        }
    }
});
@endif
</script>
@endif
@endsection
