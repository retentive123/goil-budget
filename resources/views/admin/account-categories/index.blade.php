@extends('layouts.app')
@section('title', 'Account Categories')
@section('content')

@php
$typeConfig = [
    'revenue'             => ['label' => 'Revenue',             'bg' => '#D1FAE5', 'color' => '#065F46'],
    'expense'             => ['label' => 'Expense',             'bg' => '#FEE2E2', 'color' => '#991B1B'],
    'both'                => ['label' => 'Both',                'bg' => '#DBEAFE', 'color' => '#1E40AF'],
    'assets'              => ['label' => 'Assets',              'bg' => '#EDE9FE', 'color' => '#5B21B6'],
    'liabilities'         => ['label' => 'Liabilities',         'bg' => '#F3E8FF', 'color' => '#7C3AED'],
    'capital_expenditure' => ['label' => 'Capital Expenditure', 'bg' => '#FEF3C7', 'color' => '#92400E'],
];
$usedTypes = $categories->pluck('budget_type')->unique()->sort()->values();
@endphp

{{-- Page header --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Account Categories</h5>
        <p class="text-muted small mb-0" id="catCount"></p>
    </div>
    <a href="{{ route('admin.account-categories.create') }}"
       class="btn btn-sm" style="background:var(--navy);color:#fff;border-radius:8px">
        + Add Category
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2 mb-3">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show py-2 mb-3">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Toolbar --}}
<div class="chart-card mb-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

        {{-- Left: search + type pills --}}
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="position-relative">
                <i class="fas fa-search position-absolute"
                   style="left:10px;top:50%;transform:translateY(-50%);color:#94A3B8;font-size:12px"></i>
                <input type="text" id="catSearch"
                       class="form-control form-control-sm"
                       placeholder="Search code, name, description…"
                       style="padding-left:30px;width:260px"
                       oninput="applyFilters()">
            </div>

            <div class="d-flex gap-2 flex-wrap" id="typeFilterBtns">
                <button type="button" class="cat-type-btn"
                        data-type="" onclick="setType('')"
                        style="font-size:11px;font-weight:600;padding:3px 14px;
                               border-radius:20px;border:1.5px solid var(--navy);
                               background:var(--navy);color:#fff;cursor:pointer">
                    All Types
                </button>
                @foreach($usedTypes as $t)
                @php $tc = $typeConfig[$t] ?? ['label' => ucfirst($t), 'bg' => '#F1F5F9', 'color' => '#475569']; @endphp
                <button type="button" class="cat-type-btn"
                        data-type="{{ $t }}" onclick="setType('{{ $t }}')"
                        style="font-size:11px;font-weight:600;padding:3px 14px;
                               border-radius:20px;border:1.5px solid #E2E8F0;
                               background:#F8FAFC;color:#475569;cursor:pointer">
                    {{ $tc['label'] }}
                </button>
                @endforeach
            </div>
        </div>

        {{-- Right: export + import --}}
        <div class="d-flex gap-2">
            <a href="{{ route('ie.categories.download') }}"
               class="btn btn-sm btn-outline-success" style="font-size:12px">
                <i class="fas fa-file-download me-1"></i>Export / Template
            </a>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                        data-bs-toggle="dropdown" style="font-size:12px">
                    <i class="fas fa-download me-1"></i>Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:13px">
                    <li>
                        <a class="dropdown-item" href="#"
                           onclick="exportCats('csv');return false">
                            <i class="fas fa-file-csv me-2 text-success"></i>CSV
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#"
                           onclick="exportCats('json');return false">
                            <i class="fas fa-file-code me-2 text-primary"></i>JSON
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item" href="#" id="catCopyBtn"
                           onclick="exportCats('copy');return false">
                            <i class="fas fa-copy me-2 text-muted"></i>Copy (TSV)
                        </a>
                    </li>
                </ul>
            </div>

            <button type="button"
                    onclick="document.getElementById('catUpload').classList.toggle('d-none')"
                    class="btn btn-sm btn-outline-primary" style="font-size:12px">
                <i class="fas fa-upload me-1"></i>Import
            </button>
        </div>
    </div>
</div>

{{-- Import panel --}}
<div id="catUpload" class="{{ session('import_errors') ? '' : 'd-none' }} chart-card mb-3"
     style="border-left:4px solid var(--navy)">
    <div style="font-size:13px;font-weight:600;color:var(--navy);margin-bottom:8px">
        Import Categories from Excel
    </div>
    <form method="POST" action="{{ route('ie.categories.upload') }}"
          enctype="multipart/form-data">
        @csrf
        <div class="d-flex gap-2 align-items-end">
            <div class="flex-grow-1">
                <input type="file" name="file" accept=".xlsx,.xls,.csv"
                       class="form-control form-control-sm">
            </div>
            <button type="submit" class="btn btn-sm btn-primary">Upload</button>
            <button type="button"
                    onclick="document.getElementById('catUpload').classList.add('d-none')"
                    class="btn btn-sm btn-outline-secondary">Cancel</button>
        </div>
        <div class="form-text">
            Existing categories with the same code will be updated. Download the template first.
        </div>
    </form>
</div>

@if(session('import_errors'))
<div class="card border-0 mb-3" style="border-radius:10px;background:#FEF2F2;border:1px solid #FECACA !important;">
    <div class="card-body py-3 px-4">
        <div class="fw-semibold mb-2" style="font-size:13px;color:#991B1B;">
            <i class="fas fa-exclamation-triangle"></i> The following rows were skipped:
        </div>
        @foreach(session('import_errors') as $err)
        <div style="font-size:12px;color:#7F1D1D;line-height:1.6;">{{ $err }}</div>
        @endforeach
    </div>
</div>
@endif

{{-- Bulk-delete form (hidden, submitted by JS) --}}
<form id="bulkDeleteForm" method="POST"
      action="{{ route('admin.account-categories.bulk-destroy') }}">
    @csrf @method('DELETE')
    <input type="hidden" name="ids" id="bulkIds">
</form>

{{-- Bulk-assign sub-category form (hidden, submitted by JS) --}}
<form id="bulkAssignForm" method="POST"
      action="{{ route('admin.account-categories.bulk-assign-sub-category') }}">
    @csrf
    <input type="hidden" name="ids" id="assignIds">
    <input type="hidden" name="account_sub_category_id" id="assignSubCatId">
</form>

{{-- Hidden trigger (Bootstrap data-attribute listener handles the show) --}}
<button id="bulkAssignTrigger" class="d-none"
        data-bs-toggle="modal" data-bs-target="#bulkAssignModal"></button>

{{-- Bulk assign modal --}}
<div class="modal fade" id="bulkAssignModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px">
        <div class="modal-content" style="border-radius:12px;border:none;box-shadow:0 8px 32px rgba(0,0,0,.14)">
            <div class="modal-header" style="background:#1B2A4A;border-radius:12px 12px 0 0;padding:14px 20px">
                <h6 class="modal-title fw-bold text-white mb-0">
                    <i class="fas fa-tags me-2"></i>Assign Sub-Category
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3" id="assignModalDesc"></p>
                <label class="form-label small fw-semibold mb-1">Sub-Category</label>
                <select id="assignSubCatSel" class="form-select form-select-sm">
                    <option value="">— None (clear assignment) —</option>
                    @foreach($subCategories as $type => $subs)
                    @php
                        $typeLabels = [
                            'revenue'             => 'Revenue',
                            'expense'             => 'Expense',
                            'both'                => 'Both',
                            'assets'              => 'Assets',
                            'liabilities'         => 'Liabilities',
                            'capital_expenditure' => 'Capital Expenditure',
                        ];
                    @endphp
                    <optgroup label="{{ $typeLabels[$type] ?? ucfirst($type) }}">
                        @foreach($subs as $sub)
                        <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
            </div>
            <div class="modal-footer border-0 pt-0 pb-3 px-4">
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm fw-semibold"
                        style="background:#1B2A4A;color:#fff;border-radius:8px"
                        onclick="submitBulkAssign()">
                    Apply
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Table --}}
<div class="chart-card p-0" style="overflow:hidden">

    {{-- Bulk action bar (hidden until rows are checked) --}}
    <div id="bulkBar" class="d-none d-flex align-items-center gap-3 px-4 py-2"
         style="background:#FFF7ED;border-bottom:1px solid #FED7AA">
        <span class="small fw-semibold" style="color:#C2410C" id="bulkCount"></span>
        <button type="button" class="btn btn-sm btn-danger"
                style="font-size:12px" onclick="confirmBulkDelete()">
            <i class="fas fa-trash me-1"></i>Delete Selected
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary"
                style="font-size:12px" onclick="openBulkAssign()">
            <i class="fas fa-tags me-1"></i>Assign Sub-Category
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary"
                style="font-size:12px" onclick="clearSelection()">
            Clear selection
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0" id="catTable">
            <thead style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;
                          color:var(--slate);background:#F8FAFC">
                <tr>
                    <th class="ps-3" style="width:36px">
                        <input type="checkbox" id="selectAll" class="form-check-input"
                               style="cursor:pointer" onchange="toggleAll(this)">
                    </th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Sub-Category</th>
                    <th>Description</th>
                    <th class="text-center">Codes</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="catTbody">
                @forelse($categories as $category)
                @php
                    $tc  = $typeConfig[$category->budget_type]
                           ?? ['label' => ucfirst($category->budget_type), 'bg' => '#F1F5F9', 'color' => '#475569'];
                    $canDelete = $category->account_codes_count === 0;
                @endphp
                <tr class="cat-row"
                    data-code="{{ strtolower($category->code) }}"
                    data-name="{{ strtolower($category->name) }}"
                    data-desc="{{ strtolower($category->description ?? '') }}"
                    data-type="{{ $category->budget_type }}">
                    <td class="ps-3">
                        <input type="checkbox" class="form-check-input row-cb"
                               value="{{ $category->id }}"
                               data-deletable="{{ $canDelete ? '1' : '0' }}"
                               style="cursor:pointer" onchange="updateBulkBar()">
                    </td>
                    <td>
                        <code style="font-size:12px;color:var(--navy);font-weight:600">
                            {{ $category->code }}
                        </code>
                    </td>
                    <td class="fw-semibold small">{{ $category->name }}</td>
                    <td>
                        <span style="font-size:11px;font-weight:600;padding:2px 10px;
                                     border-radius:20px;
                                     background:{{ $tc['bg'] }};color:{{ $tc['color'] }}">
                            {{ $tc['label'] }}
                        </span>
                    </td>
                    <td class="small">
                        @if($category->subCategory)
                        <span style="font-size:11px;padding:2px 8px;border-radius:20px;
                                     background:#F0F9FF;color:#0369A1;font-weight:500">
                            {{ $category->subCategory->name }}
                        </span>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="small text-muted" style="max-width:220px">
                        {{ $category->description ?? '—' }}
                    </td>
                    <td class="text-center">
                        <span class="badge bg-secondary">{{ $category->account_codes_count }}</span>
                    </td>
                    <td>
                        <span class="badge bg-{{ $category->is_active ? 'success' : 'secondary' }}">
                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="pe-3">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="{{ route('admin.account-categories.show', $category) }}"
                               class="btn btn-sm btn-outline-secondary"
                               style="font-size:11px;padding:2px 10px">View</a>
                            <a href="{{ route('admin.account-categories.edit', $category) }}"
                               class="btn btn-sm btn-outline-primary"
                               style="font-size:11px;padding:2px 10px">Edit</a>
                            @if($canDelete)
                            <form id="del-cat-{{ $category->id }}" method="POST"
                                  action="{{ route('admin.account-categories.destroy', $category) }}">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        style="font-size:11px;padding:2px 10px"
                                        onclick="deleteCat('del-cat-{{ $category->id }}', '{{ addslashes($category->name) }}')">
                                    Delete
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr id="emptyRow">
                    <td colspan="9" class="text-center text-muted py-4">
                        No categories yet.
                        <a href="{{ route('admin.account-categories.create') }}">Add one</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- No-results row (hidden by default) --}}
    <div id="noResults" class="text-center py-4 text-muted" style="display:none;font-size:13px">
        No categories match your search.
    </div>

    {{-- Pagination bar --}}
    <div class="d-flex justify-content-between align-items-center px-4 py-3 flex-wrap gap-2"
         style="border-top:1px solid #F1F5F9" id="catPagerBar">
        <div class="d-flex align-items-center gap-2">
            <span style="font-size:12px;color:var(--slate)">Show</span>
            <select id="catPageSize" class="form-select form-select-sm" style="width:auto;font-size:12px"
                    onchange="setPageSize(this.value)">
                <option value="10">10</option>
                <option value="20" selected>20</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="all">All</option>
            </select>
            <span style="font-size:12px;color:var(--slate)" id="catPageInfo"></span>
        </div>
        <div class="d-flex gap-1" id="catPageBtns"></div>
    </div>
</div>

@php
$catExportData = $categories->map(fn($c) => [
    'code'        => $c->code,
    'name'        => $c->name,
    'budget_type' => $c->budget_type,
    'description' => $c->description ?? '',
    'codes_count' => $c->account_codes_count,
    'is_active'   => $c->is_active ? 'Active' : 'Inactive',
]);
$typeLabelsJson = collect($typeConfig)->map(fn($v) => $v['label']);
@endphp

{{-- Export data --}}
<script>
const CAT_DATA    = @json($catExportData);
const TYPE_LABELS = @json($typeLabelsJson);

let pageSize     = 20;
let activeType   = '';
let currentPage  = 1;
let filteredRows = [];

function setPageSize(val) {
    pageSize    = val === 'all' ? Infinity : parseInt(val);
    currentPage = 1;
    renderPage();
}

// ── Type filter ──────────────────────────────────────────────────────────
function setType(type) {
    activeType = type;
    document.querySelectorAll('.cat-type-btn').forEach(b => {
        const on = b.dataset.type === type;
        b.style.background  = on ? 'var(--navy)' : '#F8FAFC';
        b.style.color       = on ? '#fff'        : '#475569';
        b.style.borderColor = on ? 'var(--navy)' : '#E2E8F0';
    });
    currentPage = 1;
    applyFilters();
}

// ── Search + filter + paginate ───────────────────────────────────────────
function applyFilters() {
    const q    = document.getElementById('catSearch').value.toLowerCase().trim();
    const rows = Array.from(document.querySelectorAll('.cat-row'));

    filteredRows = rows.filter(row => {
        const typeMatch   = !activeType || row.dataset.type === activeType;
        const searchMatch = !q
            || row.dataset.code.includes(q)
            || row.dataset.name.includes(q)
            || row.dataset.desc.includes(q);
        return typeMatch && searchMatch;
    });

    renderPage();
    updateBulkBar();
}

function renderPage() {
    const total      = filteredRows.length;
    const allRows    = document.querySelectorAll('.cat-row');
    const isAll      = pageSize === Infinity;
    const totalPages = isAll ? 1 : Math.max(1, Math.ceil(total / pageSize));
    if (currentPage > totalPages) currentPage = totalPages;

    const start = isAll ? 0 : (currentPage - 1) * pageSize;
    const end   = isAll ? total : Math.min(start + pageSize, total);

    // Show/hide rows
    allRows.forEach(r => r.style.display = 'none');
    filteredRows.slice(start, end).forEach(r => r.style.display = '');

    // Count label
    const grandTotal = allRows.length;
    document.getElementById('catCount').textContent =
        total === grandTotal
            ? `${grandTotal} categories`
            : `${total} of ${grandTotal} categories`;

    // No-results
    document.getElementById('noResults').style.display =
        total === 0 ? '' : 'none';

    // Pagination bar
    const bar      = document.getElementById('catPagerBar');
    const infoEl   = document.getElementById('catPageInfo');
    const btnsEl   = document.getElementById('catPageBtns');

    if (total === 0 || totalPages <= 1) {
        bar.style.display = total === 0 ? 'none' : '';
        infoEl.textContent = total > 0 ? `${total} categories` : '';
        btnsEl.innerHTML   = '';
        return;
    }

    bar.style.display  = '';
    infoEl.textContent = `Showing ${start + 1}–${end} of ${total}`;

    // Page buttons (window of 7)
    const pages = (() => {
        if (totalPages <= 7) return Array.from({ length: totalPages }, (_, i) => i + 1);
        if (currentPage <= 4)             return [1,2,3,4,5,'…',totalPages];
        if (currentPage >= totalPages - 3) return [1,'…',totalPages-4,totalPages-3,totalPages-2,totalPages-1,totalPages];
        return [1,'…',currentPage-1,currentPage,currentPage+1,'…',totalPages];
    })();

    const navBtn = (label, page, disabled) =>
        `<button class="btn btn-sm btn-outline-secondary" ${disabled ? 'disabled' : ''}
         onclick="goPage(${page})" style="padding:2px 8px;font-size:12px">${label}</button>`;

    const pageBtn = p => p === '…'
        ? `<span class="btn btn-sm disabled" style="padding:2px 6px;font-size:12px">…</span>`
        : `<button class="btn btn-sm" onclick="goPage(${p})"
               style="padding:2px 8px;font-size:12px;${p === currentPage ? 'background:var(--navy);color:#fff' : ''}">${p}</button>`;

    btnsEl.innerHTML =
        navBtn('‹', currentPage - 1, currentPage === 1) +
        pages.map(pageBtn).join('') +
        navBtn('›', currentPage + 1, currentPage === totalPages);
}

function goPage(p) {
    currentPage = p;
    renderPage();
}

// ── Export ───────────────────────────────────────────────────────────────
function getVisibleData() {
    const q = document.getElementById('catSearch').value.toLowerCase().trim();
    return CAT_DATA.filter(r => {
        const typeMatch   = !activeType || r.budget_type === activeType;
        const searchMatch = !q
            || r.code.toLowerCase().includes(q)
            || r.name.toLowerCase().includes(q)
            || r.description.toLowerCase().includes(q);
        return typeMatch && searchMatch;
    });
}

function exportCats(format) {
    const data    = getVisibleData();
    const headers = ['Code', 'Name', 'Type', 'Description', 'Account Codes', 'Status'];
    const row     = r => [
        r.code,
        r.name,
        TYPE_LABELS[r.budget_type] ?? r.budget_type,
        r.description,
        r.codes_count,
        r.is_active,
    ];
    const datestamp = new Date().toISOString().slice(0, 10);
    const filename  = `account-categories-${datestamp}`;

    if (format === 'csv') {
        const csv = [headers, ...data.map(row)]
            .map(cells => cells.map(c => `"${String(c ?? '').replace(/"/g, '""')}"`).join(','))
            .join('\n');
        dlBlob(csv, filename + '.csv', 'text/csv;charset=utf-8;');
    }
    if (format === 'json') {
        const json = JSON.stringify(
            data.map(r => Object.fromEntries(headers.map((h, i) => [h, row(r)[i]]))),
            null, 2
        );
        dlBlob(json, filename + '.json', 'application/json');
    }
    if (format === 'copy') {
        const tsv = [headers, ...data.map(row)].map(c => c.join('\t')).join('\n');
        navigator.clipboard.writeText(tsv).then(() => {
            const btn  = document.getElementById('catCopyBtn');
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check me-2 text-success"></i>Copied!';
            setTimeout(() => { btn.innerHTML = orig; }, 2000);
        });
    }
}

function dlBlob(content, filename, mime) {
    const a = Object.assign(document.createElement('a'), {
        href: URL.createObjectURL(new Blob([content], { type: mime })),
        download: filename,
    });
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

// Init count
document.addEventListener('DOMContentLoaded', () => applyFilters());

// ── Multi-select / bulk delete ────────────────────────────────────────────
function visibleCheckboxes() {
    return [...document.querySelectorAll('.cat-row')]
        .filter(r => r.style.display !== 'none')
        .map(r => r.querySelector('.row-cb'));
}

function toggleAll(master) {
    visibleCheckboxes().forEach(cb => cb.checked = master.checked);
    updateBulkBar();
}

function updateBulkBar() {
    const checked = [...document.querySelectorAll('.row-cb:checked')];
    const bar     = document.getElementById('bulkBar');
    const countEl = document.getElementById('bulkCount');
    const master  = document.getElementById('selectAll');

    if (checked.length === 0) {
        bar.classList.add('d-none');
        master.indeterminate = false;
        master.checked = false;
    } else {
        bar.classList.remove('d-none');
        const deletable = checked.filter(cb => cb.dataset.deletable === '1').length;
        countEl.textContent = `${checked.length} selected`
            + (deletable < checked.length
               ? ` (${checked.length - deletable} have codes — will be skipped)` : '');
        const all = visibleCheckboxes();
        master.checked       = checked.length === all.length;
        master.indeterminate = checked.length > 0 && checked.length < all.length;
    }
}

function clearSelection() {
    document.querySelectorAll('.row-cb').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked       = false;
    document.getElementById('selectAll').indeterminate = false;
    updateBulkBar();
}

function deleteCat(formId, name) {
    Swal.fire({
        title: 'Delete category?',
        html: `<strong>${name}</strong> will be permanently deleted.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then(result => {
        if (result.isConfirmed) document.getElementById(formId).submit();
    });
}

function openBulkAssign() {
    const checked = [...document.querySelectorAll('.row-cb:checked')];
    if (checked.length === 0) return;
    const n = checked.length;
    document.getElementById('assignModalDesc').textContent =
        `Choose a sub-category to assign to the ${n} selected ${n === 1 ? 'category' : 'categories'}.`;
    document.getElementById('assignSubCatSel').value = '';
    document.getElementById('bulkAssignTrigger').click();
}

function submitBulkAssign() {
    const checked  = [...document.querySelectorAll('.row-cb:checked')];
    const ids      = checked.map(cb => cb.value).join(',');
    const subCatId = document.getElementById('assignSubCatSel').value;
    document.getElementById('assignIds').value      = ids;
    document.getElementById('assignSubCatId').value = subCatId;
    document.getElementById('bulkAssignForm').submit();
}

function confirmBulkDelete() {
    const checked   = [...document.querySelectorAll('.row-cb:checked')];
    const ids       = checked.map(cb => cb.value).join(',');
    const deletable = checked.filter(cb => cb.dataset.deletable === '1').length;

    if (deletable === 0) {
        Swal.fire({
            title: 'Cannot delete',
            text: 'None of the selected categories can be deleted — all have codes assigned.',
            icon: 'info',
            confirmButtonColor: '#6B7280',
        });
        return;
    }

    const skipped = checked.length - deletable;
    const text = skipped > 0
        ? `${deletable} of ${checked.length} selected will be deleted. ${skipped} will be skipped (have codes assigned).`
        : `${deletable} selected ${deletable === 1 ? 'category' : 'categories'} will be permanently deleted.`;

    Swal.fire({
        title: 'Delete selected?',
        text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then(result => {
        if (result.isConfirmed) {
            document.getElementById('bulkIds').value = ids;
            document.getElementById('bulkDeleteForm').submit();
        }
    });
}


</script>

@endsection
