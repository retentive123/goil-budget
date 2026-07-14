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

<div id="catUpload" class="{{ session('import_errors') ? '' : 'd-none' }} chart-card mb-4">
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
            Existing codes with the same code will be updated.
            Download the template first to see the correct format.
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

{{-- Shared delete forms (submitted by SweetAlert callbacks) --}}
<form id="codesBulkForm" method="POST"
      action="{{ route('admin.account-codes.bulk-destroy') }}">
    @csrf @method('DELETE')
    <input type="hidden" name="ids" id="codesBulkIds">
</form>
<form id="codeSingleDeleteForm" method="POST" action="">
    @csrf @method('DELETE')
</form>

<div class="card shadow-sm">
    <div class="card-body p-0">

        {{-- Bulk action bar --}}
        <div id="codesBulkBar" class="d-none d-flex align-items-center gap-3 px-4 py-2"
             style="background:#FFF7ED;border-bottom:1px solid #FED7AA">
            <span class="small fw-semibold" style="color:#C2410C" id="codesBulkCount"></span>
            <button type="button" class="btn btn-sm btn-danger"
                    style="font-size:12px" onclick="codesConfirmBulk()">
                <i class="bi bi-trash me-1"></i>Delete Selected
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary"
                    style="font-size:12px" onclick="codesClearSel()">
                Clear selection
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:36px" class="ps-3">
                            <input type="checkbox" id="codesSelectAll" class="form-check-input"
                                   style="cursor:pointer" onchange="codesToggleAll(this)">
                        </th>
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
                    @php $canDel = $code->lineItems()->count() == 0; @endphp
                    <tr>
                        <td class="ps-3">
                            <input type="checkbox" class="form-check-input codes-cb"
                                   value="{{ $code->id }}"
                                   data-deletable="{{ $canDel ? '1' : '0' }}"
                                   style="cursor:pointer" onchange="codesUpdateBar()">
                        </td>
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
                                @if($canDel)
                                <button type="button" class="btn btn-outline-danger"
                                        onclick="deleteCode('{{ route('admin.account-codes.destroy', $code) }}', '{{ $code->code }}')"
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
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
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
        </div>{{-- /.table-responsive --}}
    </div>{{-- /.card-body --}}
</div>{{-- /.card --}}

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
    document.querySelectorAll('.filter-select').forEach(sel => {
        sel.addEventListener('change', () => document.getElementById('filterForm').submit());
    });
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('keyup', e => {
            if (e.key === 'Enter') document.getElementById('filterForm').submit();
        });
    }
});

// ── Multi-select / bulk delete ────────────────────────────────────────────
function codesToggleAll(master) {
    document.querySelectorAll('.codes-cb').forEach(cb => cb.checked = master.checked);
    codesUpdateBar();
}

function codesUpdateBar() {
    const checked   = [...document.querySelectorAll('.codes-cb:checked')];
    const all       = [...document.querySelectorAll('.codes-cb')];
    const bar       = document.getElementById('codesBulkBar');
    const countEl   = document.getElementById('codesBulkCount');
    const master    = document.getElementById('codesSelectAll');

    if (checked.length === 0) {
        bar.classList.add('d-none');
        master.indeterminate = false;
        master.checked = false;
    } else {
        bar.classList.remove('d-none');
        const deletable = checked.filter(cb => cb.dataset.deletable === '1').length;
        countEl.textContent = `${checked.length} selected`
            + (deletable < checked.length
               ? ` (${checked.length - deletable} in use — will be skipped)` : '');
        master.checked       = checked.length === all.length;
        master.indeterminate = checked.length > 0 && checked.length < all.length;
    }
}

function codesClearSel() {
    document.querySelectorAll('.codes-cb').forEach(cb => cb.checked = false);
    const master = document.getElementById('codesSelectAll');
    master.checked = false;
    master.indeterminate = false;
    codesUpdateBar();
}

function deleteCode(action, code) {
    Swal.fire({
        title: 'Delete account code?',
        html: `<strong>${code}</strong> will be permanently deleted.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then(result => {
        if (result.isConfirmed) {
            const form = document.getElementById('codeSingleDeleteForm');
            form.action = action;
            form.submit();
        }
    });
}

function codesConfirmBulk() {
    const checked   = [...document.querySelectorAll('.codes-cb:checked')];
    const deletable = checked.filter(cb => cb.dataset.deletable === '1').length;

    if (deletable === 0) {
        Swal.fire({
            title: 'Cannot delete',
            text: 'None of the selected codes can be deleted — all are used in budget entries.',
            icon: 'info',
            confirmButtonColor: '#6B7280',
        });
        return;
    }

    const skipped = checked.length - deletable;
    const text = skipped > 0
        ? `${deletable} of ${checked.length} selected codes will be deleted. ${skipped} will be skipped (used in budget entries).`
        : `${deletable} selected ${deletable === 1 ? 'code' : 'codes'} will be permanently deleted.`;

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
            document.getElementById('codesBulkIds').value = checked.map(cb => cb.value).join(',');
            document.getElementById('codesBulkForm').submit();
        }
    });
}
</script>
@endpush
