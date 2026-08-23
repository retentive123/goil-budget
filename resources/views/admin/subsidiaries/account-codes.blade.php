@extends('layouts.app')
@section('title', $subsidiary->name . ' — Account Codes')
@section('content')

<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item">
                <a href="{{ route('admin.subsidiaries.index') }}" class="text-decoration-none text-muted">
                    <i class="bi bi-building"></i> Subsidiaries
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('admin.subsidiaries.show', $subsidiary) }}" class="text-decoration-none text-muted">
                    {{ $subsidiary->name }}
                </a>
            </li>
            <li class="breadcrumb-item active">Account Codes</li>
        </ol>
    </nav>
    <h5 class="fw-bold mb-0">{{ $subsidiary->name }}</h5>
    <p class="text-muted small">Select which account codes this subsidiary can use when preparing its budget</p>
</div>

<div class="row">
    <div class="col-lg-9">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                <div>
                    <span class="fw-semibold">Account Code Mappings</span>
                    <span class="badge bg-secondary ms-2" id="totalSelected">0</span>
                    <span class="text-muted small ms-1">selected</span>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.subsidiaries.export-account-codes', $subsidiary) }}"
                       class="btn btn-sm btn-outline-success" title="Download assigned codes as CSV">
                        <i class="bi bi-download me-1"></i>Export Codes
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllCategories()">
                        <i class="bi bi-check-all"></i> Toggle All
                    </button>
                </div>
            </div>
            <div class="card-body p-4">
                <form method="POST"
                      action="{{ route('admin.subsidiaries.sync-account-codes', $subsidiary) }}"
                      id="mappingForm">
                    @csrf

                    @if($all->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-clipboard2" style="font-size:48px;color:#CBD5E1;display:block;margin-bottom:12px"></i>
                            <p class="fw-semibold">No Account Codes Available</p>
                            <a href="{{ route('admin.account-codes.create') }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-circle"></i> Create Account Code
                            </a>
                        </div>
                    @else
                        @foreach($all->groupBy('account_category_id') as $categoryId => $codes)
                            @php $category = $codes->first()->category @endphp
                            <div class="category-group mb-4">
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
                                    <div class="col-md-4 col-lg-3">
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
                                                <span class="text-truncate" style="max-width:120px">{{ $code->name }}</span>
                                            </label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <div class="d-flex flex-wrap justify-content-between align-items-center mt-4 pt-3 border-top">
                            <span class="text-muted small">
                                <span class="fw-bold" id="selectedCount">0</span> of {{ $all->count() }} codes selected
                            </span>
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
                                <i class="bi bi-save me-1"></i>Save Mappings
                            </button>
                            <a href="{{ route('admin.subsidiaries.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-3">
        <div class="card shadow-sm border-0 sticky-top" style="top:20px">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-semibold mb-0"><i class="bi bi-info-circle me-1"></i>Summary</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Subsidiary</span>
                    <span class="fw-semibold small">{{ $subsidiary->name }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Category</span>
                    <span class="fw-semibold small">{{ $subsidiary->category->name ?? '—' }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Total Codes</span>
                    <span class="fw-semibold small">{{ $all->count() }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Assigned</span>
                    <span class="fw-semibold small" id="sidebarSelected">{{ count($assigned) }}</span>
                </div>
                <div class="mt-3 pt-3 border-top">
                    <a href="{{ route('admin.subsidiaries.export-account-codes', $subsidiary) }}"
                       class="btn btn-sm btn-outline-success w-100 mb-1">
                        <i class="bi bi-download me-1"></i>Export Assigned Codes
                    </a>
                    <a href="{{ route('admin.subsidiaries.show', $subsidiary) }}"
                       class="btn btn-sm btn-outline-info w-100">
                        <i class="bi bi-eye me-1"></i>View Subsidiary
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.form-check-custom { padding:8px 12px;border:1px solid #E2E8F0;border-radius:8px;transition:all .2s;background:#fff }
.form-check-custom:hover { border-color:#1B2A4A;background:#F8FAFC }
.form-check-custom .form-check-input:checked { background-color:#1B2A4A;border-color:#1B2A4A }
.category-group { background:#F8FAFC;padding:16px;border-radius:10px;border:1px solid #E2E8F0 }
.category-group:hover { border-color:#1B2A4A }
</style>
@endpush

@push('scripts')
<script>
let allCheckboxes = document.querySelectorAll('.code-checkbox');

function updateCounter() {
    const checked = document.querySelectorAll('.code-checkbox:checked').length;
    ['selectedCount','totalSelected','sidebarSelected'].forEach(id => {
        const el = document.getElementById(id); if (el) el.textContent = checked;
    });
    document.querySelectorAll('.category-group').forEach(group => {
        const catId = group.querySelector('.toggle-category')?.dataset.category;
        if (!catId) return;
        const total   = group.querySelectorAll('.code-checkbox').length;
        const checked = group.querySelectorAll('.code-checkbox:checked').length;
        const span = document.getElementById('categoryCount_' + catId);
        if (span) span.textContent = checked + '/' + total;
    });
}

function toggleCategory(categoryId) {
    const boxes = document.querySelectorAll('.code-checkbox[data-category="' + categoryId + '"]');
    const allChecked = Array.from(boxes).every(cb => cb.checked);
    boxes.forEach(cb => cb.checked = !allChecked);
    const btn = document.querySelector('.toggle-category[data-category="' + categoryId + '"]');
    if (btn) btn.innerHTML = (!allChecked ? '<i class="bi bi-x-square"></i> Deselect All' : '<i class="bi bi-check2-square"></i> Select All');
    updateCounter();
}

function toggleAllCategories() {
    const allChecked = Array.from(allCheckboxes).every(cb => cb.checked);
    allCheckboxes.forEach(cb => cb.checked = !allChecked);
    updateCounter();
}

function selectAll() { allCheckboxes.forEach(cb => cb.checked = true); updateCounter(); }
function clearAll()  { allCheckboxes.forEach(cb => cb.checked = false); updateCounter(); }

document.addEventListener('DOMContentLoaded', updateCounter);
</script>
@endpush
