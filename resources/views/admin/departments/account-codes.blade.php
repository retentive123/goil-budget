@extends('layouts.app')
@section('title', 'Department Account Codes')
@section('content')

<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item">
                <a href="{{ route('admin.departments.index') }}" class="text-decoration-none">
                    <i class="bi bi-building"></i> Departments
                </a>
            </li>
            <li class="breadcrumb-item active">
                {{ $department->name }} — Account Codes
            </li>
        </ol>
    </nav>
    <h5 class="fw-bold mb-0">{{ $department->name }}</h5>
    <p class="text-muted small">Manage which account codes this department can access for budgeting</p>
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
                    Select the account codes that <strong>{{ $department->name }}</strong> should see when preparing their budget.
                    Codes not selected will be hidden from this department.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                <form method="POST" action="{{ route('admin.departments.sync-account-codes', $department) }}" id="mappingForm">
                    @csrf

                    @if($all->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <div style="font-size: 48px; margin-bottom: 12px;">📋</div>
                            <p class="fw-semibold">No Account Codes Available</p>
                            <p class="small">Please create account codes first before assigning them to departments.</p>
                            <a href="{{ route('admin.account-codes.create') }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-circle"></i> Create Account Code
                            </a>
                        </div>
                    @else

                        {{-- Group by category --}}
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
                            <a href="{{ route('admin.departments.index') }}" class="btn btn-outline-secondary">
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
                    <span class="text-muted small">Department</span>
                    <span class="fw-semibold small">{{ $department->name }}</span>
                </div>
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
                    <a href="{{ route('admin.departments.show', $department) }}" class="btn btn-sm btn-outline-info w-100 mb-1">
                        <i class="bi bi-eye"></i> View Department
                    </a>
                    <a href="{{ route('admin.departments.edit', $department) }}" class="btn btn-sm btn-outline-primary w-100">
                        <i class="bi bi-pencil"></i> Edit Department
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
    /* Custom Checkbox Styling */
    .form-check-custom {
        padding: 8px 12px;
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        transition: all 0.2s ease;
        background: white;
    }

    .form-check-custom:hover {
        border-color: #1B2A4A;
        background: #F8FAFC;
    }

    .form-check-custom .form-check-input:checked {
        background-color: #1B2A4A;
        border-color: #1B2A4A;
    }

    .form-check-custom .form-check-input:checked ~ .form-check-label {
        color: #1B2A4A;
        font-weight: 600;
    }

    .form-check-custom .form-check-input:checked ~ .form-check-label code {
        background-color: #E2E8F0 !important;
    }

    .form-check-custom .form-check-label {
        cursor: pointer;
        width: 100%;
        margin-bottom: 0;
    }

    .category-group {
        background: #F8FAFC;
        padding: 16px;
        border-radius: 10px;
        border: 1px solid #E2E8F0;
        transition: all 0.2s ease;
    }

    .category-group:hover {
        border-color: #1B2A4A;
    }

    .toggle-category {
        font-size: 0.75rem;
        padding: 2px 10px;
    }

    .toggle-category .bi {
        margin-right: 4px;
    }

    .breadcrumb-item a {
        color: #64748B;
        transition: color 0.2s;
    }

    .breadcrumb-item a:hover {
        color: #1B2A4A;
    }

    .breadcrumb-item.active {
        color: #1B2A4A;
        font-weight: 600;
    }

    .sticky-top {
        z-index: 1;
    }

    .text-truncate {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>
@endpush

@push('scripts')
<script>
    let allCheckboxes = document.querySelectorAll('.code-checkbox');
    let selectedCountSpan = document.getElementById('selectedCount');
    let totalSelectedSpan = document.getElementById('totalSelected');
    let sidebarSelectedSpan = document.getElementById('sidebarSelected');

    /**
     * Update the counter display
     */
    function updateCounter() {
        const checked = document.querySelectorAll('.code-checkbox:checked').length;
        const total = allCheckboxes.length;

        if (selectedCountSpan) {
            selectedCountSpan.textContent = checked;
        }
        if (totalSelectedSpan) {
            totalSelectedSpan.textContent = checked;
        }
        if (sidebarSelectedSpan) {
            sidebarSelectedSpan.textContent = checked;
        }

        // Update category counts
        const categories = document.querySelectorAll('.category-group');
        categories.forEach(group => {
            const categoryId = group.querySelector('.toggle-category')?.dataset.category;
            if (categoryId) {
                const checkboxes = group.querySelectorAll('.code-checkbox');
                const checkedInCategory = group.querySelectorAll('.code-checkbox:checked').length;
                const countSpan = document.getElementById(`categoryCount_${categoryId}`);
                if (countSpan) {
                    countSpan.textContent = `${checkedInCategory}/${checkboxes.length}`;
                }
            }
        });

        // Update progress bar in sidebar (optional)
        const progressBar = document.querySelector('.progress-bar');
        if (progressBar) {
            const percentage = total > 0 ? Math.round((checked / total) * 100) : 0;
            progressBar.style.width = `${percentage}%`;
            progressBar.setAttribute('aria-valuenow', percentage);
        }
    }

    /**
     * Toggle all checkboxes in a specific category
     */
    function toggleCategory(categoryId) {
        const checkboxes = document.querySelectorAll(`.code-checkbox[data-category="${categoryId}"]`);
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);

        checkboxes.forEach(cb => {
            cb.checked = !allChecked;
        });

        // Update the button text
        const button = document.querySelector(`.toggle-category[data-category="${categoryId}"]`);
        if (button) {
            const newState = !allChecked;
            button.innerHTML = newState
                ? '<i class="bi bi-x-square"></i> Deselect All'
                : '<i class="bi bi-check2-square"></i> Select All';
        }

        updateCounter();
    }

    /**
     * Toggle all categories (global)
     */
    function toggleAllCategories() {
        const allChecked = Array.from(allCheckboxes).every(cb => cb.checked);
        const newState = !allChecked;

        allCheckboxes.forEach(cb => {
            cb.checked = newState;
        });

        // Update all category buttons
        document.querySelectorAll('.toggle-category').forEach(button => {
            const categoryId = button.dataset.category;
            const checkboxes = document.querySelectorAll(`.code-checkbox[data-category="${categoryId}"]`);
            const allCategoryChecked = Array.from(checkboxes).every(cb => cb.checked);

            button.innerHTML = allCategoryChecked
                ? '<i class="bi bi-x-square"></i> Deselect All'
                : '<i class="bi bi-check2-square"></i> Select All';
        });

        updateCounter();
    }

    /**
     * Select all checkboxes
     */
    function selectAll() {
        allCheckboxes.forEach(cb => cb.checked = true);

        document.querySelectorAll('.toggle-category').forEach(button => {
            button.innerHTML = '<i class="bi bi-x-square"></i> Deselect All';
        });

        updateCounter();
    }

    /**
     * Clear all checkboxes
     */
    function clearAll() {
        allCheckboxes.forEach(cb => cb.checked = false);

        document.querySelectorAll('.toggle-category').forEach(button => {
            button.innerHTML = '<i class="bi bi-check2-square"></i> Select All';
        });

        updateCounter();
    }

    /**
     * Confirm clear all with confirmation dialog
     */
    function confirmClearAll() {
        if (confirm('Are you sure you want to unassign all account codes from this department?')) {
            clearAll();
            // Optionally auto-submit the form
            // document.getElementById('mappingForm').submit();
        }
    }

    /**
     * Handle individual checkbox click to update button states
     */
    document.addEventListener('DOMContentLoaded', function() {
        // Initial counter update
        updateCounter();

        // Add click handlers to individual checkboxes to update category button text
        allCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                const categoryId = this.dataset.category;
                const checkboxes = document.querySelectorAll(`.code-checkbox[data-category="${categoryId}"]`);
                const allChecked = Array.from(checkboxes).every(c => c.checked);
                const button = document.querySelector(`.toggle-category[data-category="${categoryId}"]`);

                if (button) {
                    button.innerHTML = allChecked
                        ? '<i class="bi bi-x-square"></i> Deselect All'
                        : '<i class="bi bi-check2-square"></i> Select All';
                }
            });
        });
    });

    /**
     * Keyboard shortcuts (optional)
     */
    document.addEventListener('keydown', function(e) {
        // Ctrl+A to select all
        if (e.ctrlKey && e.key === 'a') {
            e.preventDefault();
            selectAll();
        }
        // Ctrl+D to deselect all
        if (e.ctrlKey && e.key === 'd') {
            e.preventDefault();
            clearAll();
        }
    });
</script>
@endpush
