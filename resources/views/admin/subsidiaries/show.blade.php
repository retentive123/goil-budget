@extends('layouts.app')
@section('title', $subsidiary->name)
@section('content')

<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item">
                <a href="{{ route('admin.subsidiaries.index') }}" class="text-decoration-none text-muted">
                    <i class="bi bi-building"></i> Subsidiaries
                </a>
            </li>
            <li class="breadcrumb-item active">{{ $subsidiary->name }}</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h5 class="fw-bold mb-0">{{ $subsidiary->name }}</h5>
            <div class="d-flex align-items-center gap-2 mt-1">
                <code class="small">{{ $subsidiary->code }}</code>
                <span class="badge" style="background:#EFF6FF;color:#1D4ED8">{{ $subsidiary->category->name ?? '—' }}</span>
                @if($subsidiary->is_active)
                    <span class="badge" style="background:#D1FAE5;color:#065F46">Active</span>
                @else
                    <span class="badge" style="background:#FEE2E2;color:#991B1B">Inactive</span>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.subsidiaries.account-codes', $subsidiary) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-hash me-1"></i>Manage Codes
            </a>
            <a href="{{ route('admin.subsidiaries.edit', $subsidiary) }}" class="btn btn-sm btn-primary">
                <i class="bi bi-pencil me-1"></i>Edit
            </a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}</div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="chart-card text-center py-3">
            <div style="font-size:28px;font-weight:700;color:var(--navy)">{{ $subsidiary->account_codes_count }}</div>
            <div class="text-muted small">Account Codes</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="chart-card text-center py-3">
            <div style="font-size:28px;font-weight:700;color:var(--navy)">{{ $subsidiary->users_count }}</div>
            <div class="text-muted small">Users</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="chart-card text-center py-3">
            <div style="font-size:28px;font-weight:700;color:var(--navy)">{{ $subsidiary->budget_versions_count }}</div>
            <div class="text-muted small">Budget Versions</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="chart-card text-center py-3">
            <div style="font-size:28px;font-weight:700;color:var(--navy)">
                {{ $subsidiary->category->name ?? '—' }}
            </div>
            <div class="text-muted small">Category</div>
        </div>
    </div>
</div>

@if($subsidiary->description)
<div class="chart-card mb-4">
    <div class="fw-semibold small text-muted mb-1 text-uppercase" style="letter-spacing:.05em">Description</div>
    <p class="mb-0">{{ $subsidiary->description }}</p>
</div>
@endif

<div class="d-flex gap-2">
    <a href="{{ route('admin.subsidiaries.account-codes', $subsidiary) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-hash me-1"></i>Manage Account Codes
    </a>
    <a href="{{ route('admin.subsidiaries.export-account-codes', $subsidiary) }}" class="btn btn-outline-success btn-sm">
        <i class="bi bi-download me-1"></i>Export Codes
    </a>
</div>

@endsection
