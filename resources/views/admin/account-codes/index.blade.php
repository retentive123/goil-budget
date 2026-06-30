@extends('layouts.app')
@section('title', 'Account Codes')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Account Codes</h5>
        <p class="text-muted small mb-0">
            Manage account codes and their category assignments
        </p>
    </div>
    <a href="{{ route('admin.account-codes.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle"></i> Add Code
    </a>
</div>

{{-- Import/Export buttons --}}
<div class="d-flex gap-2 mb-3">
    <a href="{{ route('ie.codes.download') }}"
       class="btn btn-sm btn-outline-success">
        ↓ Download Template
    </a>
    <button type="button"
            onclick="document.getElementById('catUpload').classList.toggle('d-none')"
            class="btn btn-sm btn-outline-primary">
        ↑ Import from Excel
    </button>
</div>

<div id="catUpload" class="d-none chart-card mb-4">
    <div style="font-size:13px;font-weight:600;color:var(--navy);margin-bottom:8px">
        Import Codes from Excel
    </div>
    <form method="POST" action="{{ route('ie.codes.upload') }}"
          enctype="multipart/form-data">
        @csrf
        <div class="d-flex gap-2 align-items-end">
            <div class="flex-grow-1">
                <input type="file" name="file" accept=".xlsx,.xls,.csv"
                       class="form-control form-control-sm">
            </div>
            <button type="submit" class="btn btn-sm btn-primary">Upload</button>
        </div>
        <div class="form-text">
            Existing categories with the same code will be updated.
            Download the template first to see the correct format.
        </div>
    </form>

    @if(session('import_errors'))
    <div class="mt-2">
        @foreach(session('import_errors') as $err)
        <div style="font-size:11px;color:#991B1B">⚠ {{ $err }}</div>
        @endforeach
    </div>
    @endif
</div>

{{-- Filters --}}
<form method="GET" class="mb-3" id="filterForm">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-0">Category</label>
            <select name="category" class="form-select form-select-sm filter-select">
                <option value="">All categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}"
                        {{ request('category') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                        <span class="text-muted">({{ $cat->accountCodes->count() }} codes)</span>
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-0">Search</label>
            <div class="input-group input-group-sm">
                <input type="text" name="search" class="form-control"
                       placeholder="Search code or name..."
                       value="{{ request('search') }}">
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="bi bi-search"></i>
                </button>
                @if(request('search') || request('category') || request('status'))
                <a href="{{ route('admin.account-codes.index') }}"
                   class="btn btn-outline-secondary">
                    <i class="bi bi-x"></i> Clear
                </a>
                @endif
            </div>
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-0">Status</label>
            <select name="status" class="form-select form-select-sm filter-select">
                <option value="">All</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-0">Per Page</label>
            <select name="per_page" class="form-select form-select-sm filter-select">
                <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ request('per_page') == 50 || !request('per_page') ? 'selected' : '' }}>50</option>
                <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                <option value="200" {{ request('per_page') == 200 ? 'selected' : '' }}>200</option>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-0">&nbsp;</label>
            <div>
                <span class="badge bg-secondary">
                    <i class="bi bi-database"></i> {{ $codes->total() }} codes
                </span>
            </div>
        </div>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px">#</th>
                        <th style="width:120px">Code</th>
                        <th>Name</th>
                        <th style="width:180px">Category</th>
                        <th style="width:150px">Status</th>
                        <th style="width:140px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($codes as $code)
                    <tr>
                        <td class="text-muted small">
                            {{ $codes->firstItem() + $loop->index }}
                        </td>
                        <td>
                            <code class="bg-light px-2 py-1 rounded text-nowrap">{{ $code->code }}</code>
                        </td>
                        <td>{{ $code->name }}</td>
                        <td>
                            <span class="badge bg-info bg-opacity-10 text-info">
                                {{ $code->category->name }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                <span class="badge bg-{{ $code->is_active ? 'success' : 'secondary' }}">
                                    <i class="bi bi-{{ $code->is_active ? 'check-circle' : 'x-circle' }}"></i>
                                    {{ $code->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                @if($code->departments->count() > 0)
                                <span class="badge bg-primary bg-opacity-10 text-primary"
                                      title="Assigned to departments">
                                    <i class="bi bi-building"></i> {{ $code->departments->count() }}
                                </span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">


                                <a href="{{ route('admin.account-codes.edit', $code) }}"
                                   class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="{{ route('admin.account-codes.show', $code) }}"
                                   class="btn btn-outline-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($code->lineItems()->count() == 0)
                                <button type="button" class="btn btn-outline-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteModal{{ $code->id }}"
                                        title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @else
                                <button type="button" class="btn btn-outline-secondary" disabled
                                        title="Cannot delete - used in budgets">
                                    <i class="bi bi-lock"></i>
                                </button>
                                @endif
                            </div>

                            {{-- Delete Modal --}}
                            @if($code->lineItems()->count() == 0)
                            <div class="modal fade" id="deleteModal{{ $code->id }}" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h6 class="modal-title">Delete Account Code</h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Are you sure you want to delete <strong>{{ $code->code }}</strong>?</p>
                                            <p class="text-muted small">This action cannot be undone.</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <form action="{{ route('admin.account-codes.destroy', $code) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <div style="font-size:48px;margin-bottom:12px">📋</div>
                            <div style="font-size:16px;font-weight:600;color:#1B2A4A;margin-bottom:8px">
                                No account codes found
                            </div>
                            <div style="font-size:13px;color:#64748B">
                                @if(request('category') || request('search') || request('status'))
                                    Try adjusting your filters or
                                    <a href="{{ route('admin.account-codes.index') }}">clear all filters</a>
                                @else
                                    Click <strong>"Add Code"</strong> to create your first account code.
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Pagination --}}
@if($codes->hasPages())
<div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">
    <div class="text-muted small">
        <i class="bi bi-info-circle"></i>
        Showing {{ $codes->firstItem() ?? 0 }} to {{ $codes->lastItem() ?? 0 }}
        of {{ $codes->total() }} codes
    </div>
    <div>
        {{ $codes->appends(request()->query())->links('pagination::bootstrap-5') }}
    </div>
</div>
@else
<div class="d-flex justify-content-end mt-3">
    <div class="text-muted small">
        <i class="bi bi-info-circle"></i>
        Showing all {{ $codes->total() }} codes
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-submit form when filter changes
        const filterSelects = document.querySelectorAll('.filter-select');
        const filterForm = document.getElementById('filterForm');

        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                filterForm.submit();
            });
        });

        // Optional: Debounce search input
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            let timeout = null;
            searchInput.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    filterForm.submit();
                }
            });
        }
    });
</script>
@endpush
