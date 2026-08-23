@extends('layouts.app')
@section('title', 'Subsidiaries')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Subsidiaries</h5>
        <p class="text-muted small mb-0">Manage subsidiary entities — each can submit their own budget</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.subsidiary-categories.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-folder2 me-1"></i>Categories
        </a>
        <a href="{{ route('admin.subsidiaries.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-circle me-1"></i>New Subsidiary
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger"><i class="bi bi-x-circle-fill me-2"></i>{{ session('error') }}</div>
@endif

{{-- Filters --}}
<div class="chart-card mb-3 py-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control form-control-sm" placeholder="Search name or code…">
        </div>
        <div class="col-md-3">
            <select name="category_id" class="form-select form-select-sm">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select form-select-sm">
                <option value="">All Status</option>
                <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
            <a href="{{ route('admin.subsidiaries.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Clear</a>
        </div>
    </form>
</div>

<div class="chart-card">
    @if($subsidiaries->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-building" style="font-size:48px;color:#CBD5E1;display:block;margin-bottom:12px"></i>
            <p class="fw-semibold">No subsidiaries found</p>
            <a href="{{ route('admin.subsidiaries.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i>Create First Subsidiary
            </a>
        </div>
    @else
        <table class="table table-hover mb-0">
            <thead>
                <tr style="font-size:12px;text-transform:uppercase;color:var(--slate)">
                    <th style="padding:10px 16px">Subsidiary</th>
                    <th style="padding:10px 16px">Category</th>
                    <th style="padding:10px 16px;text-align:center">Codes</th>
                    <th style="padding:10px 16px;text-align:center">Users</th>
                    <th style="padding:10px 16px;text-align:center">Status</th>
                    <th style="padding:10px 16px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($subsidiaries as $sub)
                <tr>
                    <td style="padding:12px 16px">
                        <div class="fw-semibold" style="font-size:14px">{{ $sub->name }}</div>
                        <code class="small text-muted">{{ $sub->code }}</code>
                    </td>
                    <td style="padding:12px 16px">
                        <span class="badge" style="background:#EFF6FF;color:#1D4ED8">
                            {{ $sub->category->name ?? '—' }}
                        </span>
                    </td>
                    <td style="padding:12px 16px;text-align:center">
                        <a href="{{ route('admin.subsidiaries.account-codes', $sub) }}"
                           class="badge" style="background:#F0FDF4;color:#166534;text-decoration:none">
                            {{ $sub->account_codes_count }}
                        </a>
                    </td>
                    <td style="padding:12px 16px;text-align:center;font-size:13px">{{ $sub->users_count }}</td>
                    <td style="padding:12px 16px;text-align:center">
                        @if($sub->is_active)
                            <span class="badge" style="background:#D1FAE5;color:#065F46">Active</span>
                        @else
                            <span class="badge" style="background:#FEE2E2;color:#991B1B">Inactive</span>
                        @endif
                    </td>
                    <td style="padding:12px 16px;text-align:right">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="{{ route('admin.subsidiaries.account-codes', $sub) }}"
                               class="btn btn-sm btn-outline-secondary" title="Manage Account Codes"
                               style="font-size:11px;padding:2px 8px">
                                <i class="bi bi-hash"></i>
                            </a>
                            <a href="{{ route('admin.subsidiaries.export-account-codes', $sub) }}"
                               class="btn btn-sm btn-outline-success" title="Export Codes (CSV)"
                               style="font-size:11px;padding:2px 8px">
                                <i class="bi bi-download"></i>
                            </a>
                            <a href="{{ route('admin.subsidiaries.edit', $sub) }}"
                               class="btn btn-sm btn-outline-primary" style="font-size:11px;padding:2px 8px">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.subsidiaries.destroy', $sub) }}"
                                  data-confirm-name="{{ $sub->name }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:11px;padding:2px 8px">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-3">{{ $subsidiaries->links() }}</div>
    @endif
</div>
@endsection
