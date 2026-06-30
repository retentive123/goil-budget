@extends('layouts.app')
@section('title', 'Category Detail')
@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.account-categories.index') }}"
       class="text-muted text-decoration-none">Account Categories</a>
    <span class="text-muted">/</span>
    <span>{{ $accountCategory->name }}</span>
</div>

<div class="row">
<div class="col-md-8">

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h5 class="fw-bold mb-1">{{ $accountCategory->name }}</h5>
                <p class="text-muted small mb-2">
                    Code: <code>{{ $accountCategory->code }}</code>
                </p>
                @if($accountCategory->description)
                <p class="small mb-0">{{ $accountCategory->description }}</p>
                @endif
            </div>
            <div class="d-flex gap-2 align-items-center">
                <span class="badge bg-{{ $accountCategory->is_active ? 'success' : 'secondary' }}">
                    {{ $accountCategory->is_active ? 'Active' : 'Inactive' }}
                </span>
                <a href="{{ route('admin.account-categories.edit', $accountCategory) }}"
                   class="btn btn-sm btn-outline-primary">Edit</a>
            </div>
        </div>
    </div>
</div>

{{-- Account codes in this category --}}
<div class="card shadow-sm">
    <div class="card-header bg-light py-2 d-flex justify-content-between">
        <span class="fw-semibold small">
            Account Codes
            <span class="badge bg-secondary ms-1">
                {{ $accountCategory->accountCodes->count() }}
            </span>
        </span>
        <a href="{{ route('admin.account-codes.create') }}"
           class="small text-primary">+ Add code</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($accountCategory->accountCodes as $code)
                <tr>
                    <td><code>{{ $code->code }}</code></td>
                    <td class="small">{{ $code->name }}</td>
                    <td>
                        <span class="badge bg-{{ $code->is_active ? 'success' : 'secondary' }}">
                            {{ $code->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('admin.account-codes.edit', $code) }}"
                           class="btn btn-sm btn-outline-secondary">Edit</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-3">
                        No account codes in this category yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

</div>
</div>
@endsection
