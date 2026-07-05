@extends('layouts.app')
@section('title', 'Account Sub-Categories')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Account Sub-Categories</h5>
        <p class="text-muted small mb-0">Group categories within each budget type</p>
    </div>
    <a href="{{ route('admin.account-sub-categories.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle"></i> Add Sub-Category
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

@php
$typeConfig = [
    'revenue'             => ['label' => 'Revenue',             'bg' => '#D1FAE5', 'color' => '#065F46'],
    'expense'             => ['label' => 'Expense',             'bg' => '#FEE2E2', 'color' => '#991B1B'],
    'assets'              => ['label' => 'Assets',              'bg' => '#EDE9FE', 'color' => '#5B21B6'],
    'liabilities'         => ['label' => 'Liabilities',         'bg' => '#F3E8FF', 'color' => '#7C3AED'],
    'capital_expenditure' => ['label' => 'Capital Expenditure', 'bg' => '#FEF3C7', 'color' => '#92400E'],
];
@endphp

@forelse($subCategories as $type => $subs)
@php $tc = $typeConfig[$type] ?? ['label' => ucfirst($type), 'bg' => '#F1F5F9', 'color' => '#475569']; @endphp
<div class="card shadow-sm mb-4">
    <div class="card-header py-2 px-3 d-flex align-items-center gap-2"
         style="background:{{ $tc['bg'] }};border-bottom:1px solid rgba(0,0,0,.06)">
        <span style="font-size:12px;font-weight:700;color:{{ $tc['color'] }};text-transform:uppercase;letter-spacing:.5px">
            {{ $tc['label'] }}
        </span>
        <span class="badge ms-1" style="background:{{ $tc['color'] }};color:#fff;font-size:10px">
            {{ $subs->count() }}
        </span>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead style="font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:#64748B;background:#F8FAFC">
                <tr>
                    <th class="ps-3">Name</th>
                    <th class="text-center" style="width:80px">Order</th>
                    <th class="text-center" style="width:90px">Categories</th>
                    <th class="text-center" style="width:90px">Status</th>
                    <th style="width:120px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($subs->sortBy('sort_order') as $sub)
                <tr>
                    <td class="ps-3 fw-semibold small">{{ $sub->name }}</td>
                    <td class="text-center small text-muted">{{ $sub->sort_order }}</td>
                    <td class="text-center">
                        <span class="badge bg-secondary">{{ $sub->categories_count }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-{{ $sub->is_active ? 'success' : 'secondary' }}">
                            {{ $sub->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="pe-3">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="{{ route('admin.account-sub-categories.edit', $sub) }}"
                               class="btn btn-sm btn-outline-primary" style="font-size:11px;padding:2px 10px">Edit</a>
                            @if($sub->categories_count === 0)
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                    style="font-size:11px;padding:2px 10px"
                                    onclick="deleteSub('{{ route('admin.account-sub-categories.destroy', $sub) }}', '{{ addslashes($sub->name) }}')">
                                Delete
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@empty
<div class="chart-card text-center py-5 text-muted">
    <i class="fas fa-layer-group fa-2x mb-3 opacity-25"></i>
    <p class="mb-0">No sub-categories yet.</p>
    <a href="{{ route('admin.account-sub-categories.create') }}" class="btn btn-sm btn-primary mt-3">
        Add your first sub-category
    </a>
</div>
@endforelse

<form id="subDeleteForm" method="POST" action="">
    @csrf @method('DELETE')
</form>

@endsection

@push('scripts')
<script>
function deleteSub(action, name) {
    Swal.fire({
        title: 'Delete sub-category?',
        html: `<strong>${name}</strong> will be permanently deleted.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then(result => {
        if (result.isConfirmed) {
            document.getElementById('subDeleteForm').action = action;
            document.getElementById('subDeleteForm').submit();
        }
    });
}
</script>
@endpush
