@extends('layouts.app')
@section('title', 'Departments')
@section('content')

<div class="px-3 px-lg-4">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0" style="color: #1B2A4A;">
                <i class="fas fa-building" style="color: #E65C00;"></i> Departments
            </h5>
            <p class="text-muted small mb-0">
                Manage organizational departments and their account code mappings
            </p>
        </div>
        <div class="d-flex gap-2">
            
            <a href="{{ route('admin.departments.create') }}" class="btn btn-sm" style="background: #E65C00; color: #fff; border-radius: 8px; border: none;">
                <i class="fas fa-plus-circle"></i> Add Department
            </a>
        </div>
    </div>

    {{-- Stats Row --}}
    @php
        $totalDepts = $departments->total();
        $activeDepts = $departments->where('is_active', true)->count();
        $inactiveDepts = $departments->where('is_active', false)->count();
        $totalUsers = $departments->sum('users_count');
        $totalCodes = $departments->sum('account_codes_count');
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-card text-center">
                <div class="stat-accent" style="background: #1B2A4A;"></div>
                <div class="stat-label">Total Departments</div>
                <div class="stat-value">{{ $totalDepts }}</div>
                <div class="stat-sub">All departments</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card text-center">
                <div class="stat-accent" style="background: #10B981;"></div>
                <div class="stat-label">Active</div>
                <div class="stat-value" style="color: #10B981;">{{ $activeDepts }}</div>
                <div class="stat-sub">Active departments</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card text-center">
                <div class="stat-accent" style="background: #F43F5E;"></div>
                <div class="stat-label">Inactive</div>
                <div class="stat-value" style="color: #F43F5E;">{{ $inactiveDepts }}</div>
                <div class="stat-sub">Inactive departments</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card text-center">
                <div class="stat-accent" style="background: #6366F1;"></div>
                <div class="stat-label">Total Users</div>
                <div class="stat-value" style="color: #6366F1;">{{ $totalUsers }}</div>
                <div class="stat-sub">Across all departments</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="chart-card mb-4">
        <form method="GET" action="{{ route('admin.departments.index') }}" id="filterForm">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1" style="color: #1B2A4A;">
                        <i class="fas fa-search" style="color: #E65C00;"></i> Search
                    </label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="form-control form-control-sm"
                           style="border-radius: 8px; border-color: #E2E8F0;"
                           placeholder="Department name or code…">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1" style="color: #1B2A4A;">
                        <i class="fas fa-circle" style="color: #E65C00;"></i> Status
                    </label>
                    <select name="status" class="form-select form-select-sm" style="border-radius: 8px; border-color: #E2E8F0;">
                        <option value="">All</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1" style="color: #1B2A4A;">
                        <i class="fas fa-sort" style="color: #E65C00;"></i> Sort By
                    </label>
                    <select name="sort" class="form-select form-select-sm" style="border-radius: 8px; border-color: #E2E8F0;">
                        <option value="name" {{ request('sort') == 'name' || !request('sort') ? 'selected' : '' }}>Name</option>
                        <option value="code" {{ request('sort') == 'code' ? 'selected' : '' }}>Code</option>
                        <option value="users_count" {{ request('sort') == 'users_count' ? 'selected' : '' }}>Users</option>
                        <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Created Date</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm w-100" style="background: #E65C00; color: #fff; border-radius: 8px; border: none;">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </div>
            @if(request()->hasAny(['search', 'status', 'sort']))
            <div class="mt-2">
                <a href="{{ route('admin.departments.index') }}" class="small text-muted text-decoration-none">
                    <i class="fas fa-times"></i> Clear filters
                </a>
            </div>
            @endif
        </form>
    </div>

    {{-- Departments Table --}}
    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size: 13px;">
                    <thead style="background: #F8FAFC; border-bottom: 2px solid #E65C00;">
                        <tr>
                            <th style="color: #1B2A4A; font-weight: 600; padding: 12px 16px;">
                                <i class="fas fa-building" style="color: #E65C00;"></i> Department
                            </th>
                            <th style="color: #1B2A4A; font-weight: 600; padding: 12px 16px;">
                                <i class="fas fa-tag" style="color: #E65C00;"></i> Code
                            </th>
                            <th style="color: #1B2A4A; font-weight: 600; padding: 12px 16px; text-align: center;">
                                <i class="fas fa-users" style="color: #E65C00;"></i> Users
                            </th>
                            <th style="color: #1B2A4A; font-weight: 600; padding: 12px 16px; text-align: center;">
                                <i class="fas fa-hashtag" style="color: #E65C00;"></i> Account Codes
                            </th>
                            <th style="color: #1B2A4A; font-weight: 600; padding: 12px 16px;">
                                <i class="fas fa-circle" style="color: #E65C00;"></i> Status
                            </th>
                            <th style="color: #1B2A4A; font-weight: 600; padding: 12px 16px; text-align: center;">
                                <i class="fas fa-tools" style="color: #E65C00;"></i> Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departments as $dept)
                        <tr style="transition: background 0.2s;">
                            <td style="padding: 10px 16px;">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="d-flex align-items-center justify-content-center rounded-circle"
                                         style="width: 36px; height: 36px; background: {{ $dept->is_active ? '#E65C00' : '#94A3B8' }}; color: #fff; font-size: 14px; font-weight: 700;">
                                        {{ strtoupper(substr($dept->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: #1B2A4A;">{{ $dept->name }}</div>
                                        <div style="font-size: 11px; color: #94A3B8;">
                                            <i class="fas fa-clock"></i>
                                            Created {{ $dept->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 10px 16px;">
                                <code class="px-2 py-1 rounded" style="background: #F1F5F9; color: #1B2A4A; font-size: 12px;">
                                    {{ $dept->code }}
                                </code>
                            </td>
                            <td style="padding: 10px 16px; text-align: center;">
                                <span class="badge px-3 py-1" style="background: {{ $dept->users_count > 0 ? '#DBEAFE' : '#F1F5F9' }}; color: {{ $dept->users_count > 0 ? '#1E40AF' : '#94A3B8' }}; font-weight: 500; font-size: 12px; border-radius: 6px;">
                                    <i class="fas fa-users" style="font-size: 10px;"></i>
                                    {{ $dept->users_count }}
                                </span>
                            </td>
                            <td style="padding: 10px 16px; text-align: center;">
                                <span class="badge px-3 py-1" style="background: {{ $dept->account_codes_count > 0 ? '#FEF3C7' : '#F1F5F9' }}; color: {{ $dept->account_codes_count > 0 ? '#92400E' : '#94A3B8' }}; font-weight: 500; font-size: 12px; border-radius: 6px;">
                                    <i class="fas fa-hashtag" style="font-size: 10px;"></i>
                                    {{ $dept->account_codes_count }}
                                </span>
                            </td>
                            <td style="padding: 10px 16px;">
                                <span class="badge px-3 py-1" style="border-radius: 20px; font-size: 11px; font-weight: 600; background: {{ $dept->is_active ? '#D1FAE5' : '#FEE2E2' }}; color: {{ $dept->is_active ? '#065F46' : '#991B1B' }};">
                                    <i class="fas fa-{{ $dept->is_active ? 'circle' : 'circle' }}" style="font-size: 8px; margin-right: 4px; color: {{ $dept->is_active ? '#10B981' : '#F43F5E' }};"></i>
                                    {{ $dept->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td style="padding: 10px 16px; text-align: center;">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="{{ route('admin.departments.account-codes', $dept) }}"
                                       class="btn btn-sm btn-outline-secondary"
                                       style="border-radius: 6px; font-size: 11px; padding: 2px 8px;"
                                       title="Manage Account Codes">
                                        <i class="fas fa-hashtag"></i>
                                    </a>
                                    <a href="{{ route('admin.departments.edit', $dept) }}"
                                       class="btn btn-sm btn-outline-primary"
                                       style="border-radius: 6px; font-size: 11px; padding: 2px 8px;"
                                       title="Edit Department">
                                        <i class="fas fa-pencil-alt"></i>
                                    </a>
                                    @if($dept->users_count == 0)
                                    <form method="POST"
                                          action="{{ route('admin.departments.destroy', $dept) }}"
                                          class="d-inline"
                                          onsubmit="return confirmDelete('{{ $dept->name }}')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"
                                                style="border-radius: 6px; font-size: 11px; padding: 2px 8px;"
                                                title="Delete Department">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @else
                                    <button class="btn btn-sm btn-outline-secondary"
                                            style="border-radius: 6px; font-size: 11px; padding: 2px 8px; opacity: 0.5;"
                                            title="Cannot delete - has users assigned"
                                            disabled>
                                        <i class="fas fa-lock"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <div style="font-size: 48px; margin-bottom: 12px; color: #94A3B8;">
                                    <i class="fas fa-building"></i>
                                </div>
                                <div style="font-weight: 600; color: #1B2A4A; font-size: 16px;">No departments found</div>
                                <div style="font-size: 13px; color: #64748B;">
                                    @if(request()->hasAny(['search', 'status']))
                                        Try adjusting your filters or <a href="{{ route('admin.departments.index') }}" class="text-decoration-none" style="color: #E65C00;">clear them</a>
                                    @else
                                        Click <strong>"Add Department"</strong> to create your first department.
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center flex-wrap gap-2 py-3 px-4">
            <div class="text-muted small">
                <i class="fas fa-info-circle"></i>
                Showing {{ $departments->firstItem() ?? 0 }} to {{ $departments->lastItem() ?? 0 }}
                of {{ $departments->total() }} departments
            </div>
            <div>
                {{ $departments->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>

</div>

<script>
function confirmDelete(deptName) {
    return confirm(`Are you sure you want to delete the department "${deptName}"?\n\nThis action cannot be undone.`);
}
</script>

<style>
    .stat-card {
        background: #fff;
        border-radius: 12px;
        padding: 16px 12px;
        border: 1px solid #E2E8F0;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    .stat-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        transform: translateY(-1px);
    }

    .stat-card .stat-accent {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        border-radius: 0 0 4px 4px;
    }

    .stat-card .stat-label {
        font-size: 11px;
        font-weight: 600;
        color: #94A3B8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .stat-card .stat-value {
        font-size: 24px;
        font-weight: 700;
        color: #1B2A4A;
    }

    .stat-card .stat-sub {
        font-size: 11px;
        color: #94A3B8;
        margin-top: 2px;
    }

    .table tbody tr:hover {
        background: #F8FAFC;
    }

    .form-control:focus, .form-select:focus {
        border-color: #E65C00;
        box-shadow: 0 0 0 0.2rem rgba(230, 92, 0, 0.15);
    }

    .btn-outline-primary:hover {
        background: #E65C00;
        border-color: #E65C00;
        color: #fff;
    }

    .btn-outline-danger:hover {
        background: #F43F5E;
        border-color: #F43F5E;
        color: #fff;
    }

    .pagination .page-item.active .page-link {
        background-color: #E65C00;
        border-color: #E65C00;
        color: #fff;
    }

    .pagination .page-link {
        color: #1B2A4A;
    }

    .pagination .page-link:hover {
        color: #E65C00;
    }
</style>

@endsection
