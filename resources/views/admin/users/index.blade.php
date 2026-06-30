@extends('layouts.app')
@section('title', 'Users')
@section('content')

<div class="row">
    <div class="container-fluid px-4">


        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-0" style="color: #1B2A4A;">
                    <i class="fas fa-users" style="color: #E65C00;"></i> Users
                </h5>
                <p class="text-muted small mb-0">
                    Manage system users and their access permissions
                </p>
            </div>
            <div class="d-flex gap-2">

                <a href="{{ route('admin.users.create') }}" class="btn btn-sm" style="background: #E65C00; color: #fff; border-radius: 8px; border: none;">
                    <i class="fas fa-user-plus"></i> Add User
                </a>
            </div>
        </div>

        {{-- Stats Row --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <div class="stat-card text-center">
                    <div class="stat-accent" style="background: #1B2A4A;"></div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-value">{{ $users->total() }}</div>
                    <div class="stat-sub">All registered users</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card text-center">
                    <div class="stat-accent" style="background: #10B981;"></div>
                    <div class="stat-label">Active</div>
                    <div class="stat-value" style="color: #10B981;">{{ $users->where('is_active', true)->count() }}</div>
                    <div class="stat-sub">Active users</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card text-center">
                    <div class="stat-accent" style="background: #F43F5E;"></div>
                    <div class="stat-label">Inactive</div>
                    <div class="stat-value" style="color: #F43F5E;">{{ $users->where('is_active', false)->count() }}</div>
                    <div class="stat-sub">Deactivated users</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card text-center">
                    <div class="stat-accent" style="background: #6366F1;"></div>
                    <div class="stat-label">Online Now</div>
                    <div class="stat-value" style="color: #6366F1;">{{ $onlineCount ?? 0 }}</div>
                    <div class="stat-sub">Active in last 15 minutes</div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="chart-card mb-4">
            <form method="GET" action="{{ route('admin.users.index') }}" id="filterForm">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1" style="color: #1B2A4A;">
                            <i class="fas fa-search" style="color: #E65C00;"></i> Search
                        </label>
                        <input type="text" name="search" value="{{ request('search') }}"
                               class="form-control form-control-sm"
                               style="border-radius: 8px; border-color: #E2E8F0;"
                               placeholder="Name or email…">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold mb-1" style="color: #1B2A4A;">
                            <i class="fas fa-building" style="color: #E65C00;"></i> Department
                        </label>
                        <select name="department_id" class="form-select form-select-sm" style="border-radius: 8px; border-color: #E2E8F0;">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold mb-1" style="color: #1B2A4A;">
                            <i class="fas fa-user-tag" style="color: #E65C00;"></i> Role
                        </label>
                        <select name="role" class="form-select form-select-sm" style="border-radius: 8px; border-color: #E2E8F0;">
                            <option value="">All Roles</option>
                            @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold mb-1" style="color: #1B2A4A;">
                            <i class="fas fa-circle" style="color: #E65C00;"></i> Status
                        </label>
                        <select name="status" class="form-select form-select-sm" style="border-radius: 8px; border-color: #E2E8F0;">
                            <option value="">All</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm w-100" style="background: #E65C00; color: #fff; border-radius: 8px; border: none;">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </div>
                @if(request()->hasAny(['search', 'department_id', 'role', 'status']))
                <div class="mt-2">
                    <a href="{{ route('admin.users.index') }}" class="small text-muted text-decoration-none">
                        <i class="fas fa-times"></i> Clear filters
                    </a>
                </div>
                @endif
            </form>
        </div>

        {{-- Users Table --}}
        <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size: 13px;">
                        <thead style="background: #F8FAFC; border-bottom: 2px solid #E65C00;">
                            <tr>
                                <th style="color: #1B2A4A; font-weight: 600; padding: 12px 16px;">User</th>
                                <th style="color: #1B2A4A; font-weight: 600; padding: 12px 16px;">Employee ID</th>
                                <th style="color: #1B2A4A; font-weight: 600; padding: 12px 16px;">Department</th>
                                <th style="color: #1B2A4A; font-weight: 600; padding: 12px 16px;">Role</th>
                                <th style="color: #1B2A4A; font-weight: 600; padding: 12px 16px;">Status</th>
                                <th style="color: #1B2A4A; font-weight: 600; padding: 12px 16px;">Last Login</th>
                                <th style="color: #1B2A4A; font-weight: 600; padding: 12px 16px; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                            <tr style="transition: background 0.2s;">
                                <td style="padding: 10px 16px;">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="d-flex align-items-center justify-content-center rounded-circle"
                                             style="width: 36px; height: 36px; background: {{ $user->is_active ? '#E65C00' : '#94A3B8' }}; color: #fff; font-size: 13px; font-weight: 700;">
                                            {{ strtoupper(substr($user->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div style="font-weight: 600; color: #1B2A4A;">{{ $user->name }}</div>
                                            <div style="font-size: 11px; color: #64748B;">
                                                <i class="fas fa-envelope" style="width: 14px;"></i> {{ $user->email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 10px 16px; color: #475569; font-family: monospace; font-size: 12px;">
                                    {{ $user->employee_id ?? '—' }}
                                </td>
                                <td style="padding: 10px 16px;">
                                    <span style="padding: 2px 10px; border-radius: 6px; font-size: 12px; background: #F1F5F9; color: #1B2A4A;">
                                        {{ $user->department?->name ?? '—' }}
                                    </span>
                                </td>
                                <td style="padding: 10px 16px;">
                                    @foreach($user->roles as $role)
                                    <span class="badge px-2 py-1 me-1" style="background: #E65C00; color: #fff; font-weight: 500; font-size: 11px; border-radius: 6px;">
                                        {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                                    </span>
                                    @endforeach
                                </td>
                                <td style="padding: 10px 16px;">
                                    <span class="badge px-3 py-1" style="border-radius: 20px; font-size: 11px; font-weight: 600; background: {{ $user->is_active ? '#D1FAE5' : '#FEE2E2' }}; color: {{ $user->is_active ? '#065F46' : '#991B1B' }};">
                                        <i class="fas fa-{{ $user->is_active ? 'circle' : 'circle' }}" style="font-size: 8px; margin-right: 4px; color: {{ $user->is_active ? '#10B981' : '#F43F5E' }};"></i>
                                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td style="padding: 10px 16px; font-size: 12px; color: #64748B;">
                                    @if($user->last_login_at)
                                        <i class="fas fa-clock" style="color: #94A3B8;"></i>
                                        {{ $user->last_login_at->diffForHumans() }}
                                    @else
                                        <span style="color: #94A3B8;">Never</span>
                                    @endif
                                </td>
                                <td style="padding: 10px 16px; text-align: center;">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="{{ route('admin.users.show', $user) }}"
                                           class="btn btn-sm btn-outline-secondary"
                                           style="border-radius: 6px; font-size: 11px; padding: 2px 8px;"
                                           title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.users.edit', $user) }}"
                                           class="btn btn-sm btn-outline-primary"
                                           style="border-radius: 6px; font-size: 11px; padding: 2px 8px;"
                                           title="Edit">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                        @if($user->id !== auth()->id())
                                        <form method="POST"
                                              action="{{ route('admin.users.toggle-active', $user) }}"
                                              class="d-inline">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-sm {{ $user->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                                    style="border-radius: 6px; font-size: 11px; padding: 2px 8px;"
                                                    title="{{ $user->is_active ? 'Deactivate' : 'Activate' }}">
                                                <i class="fas fa-{{ $user->is_active ? 'pause' : 'play' }}"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <div style="font-size: 48px; margin-bottom: 12px; color: #94A3B8;">
                                        <i class="fas fa-users-slash"></i>
                                    </div>
                                    <div style="font-weight: 600; color: #1B2A4A; font-size: 16px;">No users found</div>
                                    <div style="font-size: 13px; color: #64748B;">
                                        @if(request()->hasAny(['search', 'department_id', 'role', 'status']))
                                            Try adjusting your filters or <a href="{{ route('admin.users.index') }}" class="text-decoration-none" style="color: #E65C00;">clear them</a>
                                        @else
                                            Click <strong>"Add User"</strong> to create your first user.
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
                    Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }}
                    of {{ $users->total() }} users
                </div>
                <div>
                    {{ $users->appends(request()->query())->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>

    </div>
</div>

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
