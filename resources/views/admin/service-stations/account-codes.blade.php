@extends('layouts.app')
@section('title', 'Station Account Codes')
@section('content')

<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item">
                <a href="{{ route('admin.service-stations.index') }}" class="text-decoration-none">
                    <i class="fas fa-gas-pump"></i> Service Stations
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('admin.service-stations.show', $station) }}" class="text-decoration-none">
                    {{ $station->name }}
                </a>
            </li>
            <li class="breadcrumb-item active">
                Account Codes
            </li>
        </ol>
    </nav>
    <h5 class="fw-bold mb-0">{{ $station->name }}</h5>
    <p class="text-muted small">Manage which account codes this service station can access for budgeting</p>
</div>

<div class="row">
    {{-- Main Content --}}
    <div class="col-lg-9">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                <div>
                    <span class="fw-semibold">Account Code Mappings</span>
                    <span class="badge bg-secondary ms-2" id="totalSelected">0</span>
                    <span class="text-muted small ms-1">selected</span>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllCategories()">
                        <i class="bi bi-check-all"></i> Toggle All
                    </button>
                </div>
            </div>
            <div class="card-body p-4">

                <div class="alert alert-info alert-dismissible fade show small" role="alert">
                    <i class="bi bi-info-circle"></i>
                    Select the account codes that <strong>{{ $station->name }}</strong> should see when preparing their budget.
                    Codes not selected will be hidden from this service station.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                <form method="POST" action="{{ route('admin.service-stations.sync-account-codes', $station) }}" id="mappingForm">
                    @csrf

                    @if($all->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <div style="font-size: 48px; margin-bottom: 12px; color: #CBD5E1;">
                                <i class="fas fa-hashtag"></i>
                            </div>
                            <p class="fw-semibold">No Account Codes Available</p>
                            <p class="small">Please create account codes first before assigning them to service stations.</p>
                            <a href="{{ route('admin.account-codes.create') }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-circle"></i> Create Account Code
                            </a>
                        </div>
                    @else

                        {{-- Tab counts --}}
                        @php
                            $cntRevenue = $all->filter(fn($c) => in_array($c->category->budget_type, ['revenue','both']))->count();
                            $cntExpense = $all->filter(fn($c) => in_array($c->category->budget_type, ['expense','both']))->count();
                            $cntCapex   = $all->filter(fn($c) => $c->category->budget_type === 'capital_expenditure')->count();
                            $cntAssets  = $all->filter(fn($c) => in_array($c->category->budget_type, ['assets','liabilities']))->count();
                        @endphp
                        <div class="type-tabs" id="typeTabs">
                            <button type="button" class="type-tab active" data-tab="all" onclick="setTab('all')">
                                All <span class="tab-count">{{ $all->count() }}</span>
                            </button>
                            @if($cntRevenue > 0)
                            <button type="button" class="type-tab" data-tab="revenue" onclick="setTab('revenue')">
                                Revenue <span class="tab-count">{{ $cntRevenue }}</span>
                            </button>
                            @endif
                            @if($cntExpense > 0)
                            <button type="button" class="type-tab" data-tab="expense" onclick="setTab('expense')">
                                Expense <span class="tab-count">{{ $cntExpense }}</span>
                            </button>
                            @endif
                            @if($cntCapex > 0)
                            <button type="button" class="type-tab" data-tab="capex" onclick="setTab('capex')">
                                CapEx <span class="tab-count">{{ $cntCapex }}</span>
                            </button>
                            @endif
                            @if($cntAssets > 0)
                            <button type="button" class="type-tab" data-tab="assets-liabilities" onclick="setTab('assets-liabilities')">
                                Assets &amp; Liabilities <span class="tab-count">{{ $cntAssets }}</span>
                            </button>
                            @endif
                        </div>

                        {{-- Code search --}}
                        <div class="mb-4 code-search-wrap">
                            <div class="input-group">
                                <span class="input-group-text rounded-start-2">
                                    <i class="bi bi-search" style="font-size:13px"></i>
                                </span>
                                <input type="text"
                                       id="codeSearch"
                                       class="form-control"
                                       placeholder="Search by code number or name…"
                                       autocomplete="off"
                                       oninput="filterCodes(this.value)">
                                <button type="button"
                                        id="clearSearchBtn"
                                        class="input-group-text rounded-end-2"
                                        onclick="clearSearch()"
                                        title="Clear search"
                                        style="display:none">
                                    <i class="bi bi-x-lg" style="font-size:12px;color:#64748B"></i>
                                </button>
                            </div>
                        </div>
                        <div id="noSearchResults" class="text-center py-5" style="display:none">
                            <i class="bi bi-search" style="font-size:36px;color:#CBD5E1;display:block;margin-bottom:8px"></i>
                            <p class="text-muted small fw-semibold mb-2">No codes match your search</p>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearSearch()">Clear Search</button>
                        </div>

                        {{-- Group by category --}}
                        @foreach($all->groupBy('account_category_id') as $categoryId => $codes)
                            @php $category = $codes->first()->category @endphp
                            <div class="category-group mb-4" data-type="{{ $category->budget_type }}">
                                <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="fw-semibold text-muted small text-uppercase mb-0">
                                            {{ $category->name }}
                                        </h6>
                                        <span class="badge bg-light text-muted small" id="categoryCount_{{ $categoryId }}">
                                            {{ collect($assigned)->intersect($codes->pluck('id'))->count() }}/{{ $codes->count() }}
                                        </span>
                                    </div>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary toggle-category"
                                            data-category="{{ $categoryId }}"
                                            onclick="toggleCategory({{ $categoryId }})">
                                        <i class="bi bi-check2-square"></i> Select All
                                    </button>
                                </div>

                                <div class="row g-2">
                                    @foreach($codes as $code)
                                    <div class="col-md-4 col-lg-3 code-item"
                                         data-code="{{ strtolower($code->code) }}"
                                         data-name="{{ strtolower($code->name) }}">
                                        <div class="form-check form-check-custom">
                                            <input type="checkbox"
                                                name="account_codes[]"
                                                value="{{ $code->id }}"
                                                id="code_{{ $code->id }}"
                                                class="form-check-input code-checkbox"
                                                data-category="{{ $categoryId }}"
                                                {{ in_array($code->id, $assigned) ? 'checked' : '' }}
                                                onchange="updateCounter()">
                                            <label for="code_{{ $code->id }}" class="form-check-label small d-flex align-items-center gap-2">
                                                <code class="bg-light px-1 py-0 rounded small">{{ $code->code }}</code>
                                                <span class="text-truncate" style="max-width: 120px;">{{ $code->name }}</span>
                                            </label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        {{-- Summary Bar --}}
                        <div class="d-flex flex-wrap justify-content-between align-items-center mt-4 pt-3 border-top">
                            <div>
                                <span class="text-muted small">Selected:</span>
                                <span class="fw-bold" id="selectedCount">0</span>
                                <span class="text-muted small">of {{ $all->count() }} account codes</span>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearAll()">
                                    <i class="bi bi-x-circle"></i> Clear All
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success" onclick="selectAll()">
                                    <i class="bi bi-check-all"></i> Select All
                                </button>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Mappings
                            </button>
                            <a href="{{ route('admin.service-stations.show', $station) }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                            @if(count($assigned) > 0)
                            <button type="button" class="btn btn-outline-danger ms-auto"
                                    onclick="confirmClearAll()">
                                <i class="bi bi-trash"></i> Unassign All
                            </button>
                            @endif
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-3">
        <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-semibold mb-0">
                    <i class="bi bi-info-circle"></i> Summary
                </h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Station</span>
                    <span class="fw-semibold small">{{ $station->name }}</span>
                </div>
                @if($station->zone)
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Zone</span>
                    <span class="fw-semibold small">{{ $station->zone->name }}</span>
                </div>
                @endif
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Total Codes</span>
                    <span class="fw-semibold small">{{ $all->count() }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Assigned Codes</span>
                    <span class="fw-semibold small" id="sidebarSelected">{{ count($assigned) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Categories</span>
                    <span class="fw-semibold small">{{ $all->groupBy('account_category_id')->count() }}</span>
                </div>
                <div class="mt-3 pt-3 border-top">
                    <div class="progress" style="height: 6px;">
                        @php $percentage = $all->count() > 0 ? round((count($assigned) / $all->count()) * 100) : 0; @endphp
                        <div class="progress-bar bg-success" role="progressbar"
                             style="width: {{ $percentage }}%;"
                             aria-valuenow="{{ $percentage }}"
                             aria-valuemin="0"
                             aria-valuemax="100">
                        </div>
                    </div>
                    <div class="text-center mt-1">
                        <span class="small text-muted">{{ $percentage }}% assigned</span>
                    </div>
                </div>
                @if(count($assigned) > 0)
                <div class="mt-3 pt-3 border-top">
                    <div class="text-muted small mb-1">Quick Actions:</div>
                    <a href="{{ route('admin.service-stations.show', $station) }}" class="btn btn-sm btn-outline-info w-100 mb-1">
                        <i class="bi bi-eye"></i> View Station
                    </a>
                    <a href="{{ route('admin.service-stations.edit', $station) }}" class="btn btn-sm btn-outline-primary w-100">
                        <i class="bi bi-pencil"></i> Edit Station
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
/* ── Code search ──────────────────────────────────────────────────────────── */
.code-search-wrap .form-control,
.code-search-wrap .input-group-text { border-color: #E65C00 !important; }
.code-search-wrap .input-group-text  { background: #fff; color: #E65C00; }
.code-search-wrap .form-control:focus {
    box-shadow: 0 0 0 3px rgba(230,92,0,.12);
    border-color: #E65C00 !important;
}
#clearSearchBtn { cursor: pointer; }

/* ── Checkbox card ────────────────────────────────────────────────────────── */
.form-check-custom { padding:8px 12px;border:1.5px solid #E2E8F0;border-radius:8px;transition:all .2s ease;background:white }
.form-check-custom:hover { border-color:#E65C00;background:#FFF8F5 }
.form-check-custom .form-check-input { border:2px solid #E65C00;margin-top:1px }
.form-check-custom .form-check-input:focus { box-shadow:0 0 0 3px rgba(230,92,0,.15);border-color:#E65C00 }
.form-check-custom .form-check-input:checked { background-color:#10B981;border-color:#10B981 }
.form-check-custom:has(.form-check-input:checked) { border-color:#10B981;background:#F0FDF4 }
.form-check-custom .form-check-input:checked ~ .form-check-label { color:#065F46;font-weight:600 }
.form-check-custom .form-check-input:checked ~ .form-check-label code { background-color:#D1FAE5!important;color:#065F46 }
.form-check-custom .form-check-label { cursor:pointer;width:100%;margin-bottom:0 }

/* ── Category groups ──────────────────────────────────────────────────────── */
.category-group { background:#F8FAFC;padding:16px;border-radius:10px;border:1px solid #E2E8F0;transition:all .2s ease }
.category-group:hover { border-color:#E65C00 }
.toggle-category { font-size:.75rem;padding:2px 10px }
.toggle-category .bi { margin-right:4px }
.breadcrumb-item a { color:#64748B;transition:color .2s }
.breadcrumb-item a:hover { color:#1B2A4A }
.breadcrumb-item.active { color:#1B2A4A;font-weight:600 }
.sticky-top { z-index:1 }
.text-truncate { white-space:nowrap;overflow:hidden;text-overflow:ellipsis }
/* ── Type tabs ─────────────────────────────────────────────────────── */
.type-tabs { display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px }
.type-tab { font-size:12px;font-weight:600;padding:5px 14px;border-radius:20px;border:1.5px solid #E2E8F0;background:#F8FAFC;color:#64748B;cursor:pointer;transition:all .15s;display:flex;align-items:center;gap:5px }
.type-tab:hover { border-color:#E65C00;color:#E65C00 }
.type-tab.active { background:#E65C00;border-color:#E65C00;color:#fff }
.tab-count { background:rgba(0,0,0,.1);border-radius:10px;padding:0 6px;font-size:10px;font-weight:700 }
.type-tab.active .tab-count { background:rgba(255,255,255,.25) }
</style>
@endpush

@push('scripts')
<script>
    let allCheckboxes = document.querySelectorAll('.code-checkbox');
    let selectedCountSpan  = document.getElementById('selectedCount');
    let totalSelectedSpan  = document.getElementById('totalSelected');
    let sidebarSelectedSpan = document.getElementById('sidebarSelected');

    function updateCounter() {
        const checked = document.querySelectorAll('.code-checkbox:checked').length;
        const total   = allCheckboxes.length;

        if (selectedCountSpan)   selectedCountSpan.textContent  = checked;
        if (totalSelectedSpan)   totalSelectedSpan.textContent  = checked;
        if (sidebarSelectedSpan) sidebarSelectedSpan.textContent = checked;

        document.querySelectorAll('.category-group').forEach(group => {
            const categoryId = group.querySelector('.toggle-category')?.dataset.category;
            if (categoryId) {
                const checkboxes       = group.querySelectorAll('.code-checkbox');
                const checkedInCat     = group.querySelectorAll('.code-checkbox:checked').length;
                const countSpan        = document.getElementById(`categoryCount_${categoryId}`);
                if (countSpan) countSpan.textContent = `${checkedInCat}/${checkboxes.length}`;
            }
        });

        const progressBar = document.querySelector('.progress-bar');
        if (progressBar) {
            const percentage = total > 0 ? Math.round((checked / total) * 100) : 0;
            progressBar.style.width = `${percentage}%`;
            progressBar.setAttribute('aria-valuenow', percentage);
        }
    }

    function toggleCategory(categoryId) {
        const checkboxes = document.querySelectorAll(`.code-checkbox[data-category="${categoryId}"]`);
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        checkboxes.forEach(cb => { cb.checked = !allChecked; });

        const button = document.querySelector(`.toggle-category[data-category="${categoryId}"]`);
        if (button) {
            button.innerHTML = !allChecked
                ? '<i class="bi bi-x-square"></i> Deselect All'
                : '<i class="bi bi-check2-square"></i> Select All';
        }
        updateCounter();
    }

    function toggleAllCategories() {
        const allChecked = Array.from(allCheckboxes).every(cb => cb.checked);
        const newState   = !allChecked;
        allCheckboxes.forEach(cb => { cb.checked = newState; });

        document.querySelectorAll('.toggle-category').forEach(button => {
            const categoryId = button.dataset.category;
            const checkboxes = document.querySelectorAll(`.code-checkbox[data-category="${categoryId}"]`);
            const allCatChecked = Array.from(checkboxes).every(cb => cb.checked);
            button.innerHTML = allCatChecked
                ? '<i class="bi bi-x-square"></i> Deselect All'
                : '<i class="bi bi-check2-square"></i> Select All';
        });
        updateCounter();
    }

    const TAB_TYPES = {
        'all':                null,
        'revenue':            ['revenue','both'],
        'expense':            ['expense','both'],
        'capex':              ['capital_expenditure'],
        'assets-liabilities': ['assets','liabilities'],
    };
    let activeTab = 'all';

    function setTab(tab) {
        activeTab = tab;
        document.querySelectorAll('.type-tab').forEach(btn => btn.classList.toggle('active', btn.dataset.tab === tab));
        applyFilters();
    }

    function filterCodes() { applyFilters(); }

    function applyFilters() {
        const query    = ((document.getElementById('codeSearch')||{}).value||'').trim().toLowerCase();
        const tabTypes = TAB_TYPES[activeTab] || null;
        const clearBtn = document.getElementById('clearSearchBtn');
        if (clearBtn) clearBtn.style.display = query ? '' : 'none';
        let anyVisible = false;
        document.querySelectorAll('.category-group').forEach(group => {
            if (tabTypes && !tabTypes.includes(group.dataset.type||'')) { group.style.display='none'; return; }
            let visible = 0;
            group.querySelectorAll('.code-item').forEach(item => {
                const match = !query || (item.dataset.code||'').includes(query) || (item.dataset.name||'').includes(query);
                item.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            group.style.display = visible > 0 ? '' : 'none';
            if (visible > 0) anyVisible = true;
        });
        const noRes = document.getElementById('noSearchResults');
        if (noRes) noRes.style.display = anyVisible ? 'none' : '';
    }

    function clearSearch() {
        const inp = document.getElementById('codeSearch');
        if (inp) inp.value = '';
        applyFilters();
        if (inp) inp.focus();
    }

    function selectAll() {
        allCheckboxes.forEach(cb => cb.checked = true);
        document.querySelectorAll('.toggle-category').forEach(button => {
            button.innerHTML = '<i class="bi bi-x-square"></i> Deselect All';
        });
        updateCounter();
    }

    function clearAll() {
        allCheckboxes.forEach(cb => cb.checked = false);
        document.querySelectorAll('.toggle-category').forEach(button => {
            button.innerHTML = '<i class="bi bi-check2-square"></i> Select All';
        });
        updateCounter();
    }

    function confirmClearAll() {
        if (confirm('Are you sure you want to unassign all account codes from this service station?')) {
            clearAll();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateCounter();

        allCheckboxes.forEach(cb => {
            cb.addEventListener('change', function () {
                const categoryId = this.dataset.category;
                const checkboxes = document.querySelectorAll(`.code-checkbox[data-category="${categoryId}"]`);
                const allChecked = Array.from(checkboxes).every(c => c.checked);
                const button     = document.querySelector(`.toggle-category[data-category="${categoryId}"]`);
                if (button) {
                    button.innerHTML = allChecked
                        ? '<i class="bi bi-x-square"></i> Deselect All'
                        : '<i class="bi bi-check2-square"></i> Select All';
                }
            });
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.ctrlKey && e.key === 'a') { e.preventDefault(); selectAll(); }
        if (e.ctrlKey && e.key === 'd') { e.preventDefault(); clearAll(); }
    });
</script>
@endpush
