@extends('layouts.app')
@section('title', 'Roles')
@section('content')

<div class="px-3 px-lg-4">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0" style="color: #1B2A4A;">
                <i class="fas fa-user-tag" style="color: #E65C00;"></i> Roles
            </h5>
            <p class="text-muted small mb-0">
                Manage user roles and their permissions across the system
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.permissions.index') }}"
               class="btn btn-sm btn-outline-secondary" style="border-radius: 8px;">
                <i class="fas fa-key"></i> Permissions
            </a>
            <a href="{{ route('admin.roles.create') }}"
               class="btn btn-sm" style="background: #E65C00; color: #fff; border-radius: 8px; border: none;">
                <i class="fas fa-plus-circle"></i> Add Role
            </a>
        </div>
    </div>

    {{-- Stats Row --}}
    @php
        $totalRoles = $roles->count();
        $systemRolesCount = $roles->filter(fn($r) => in_array($r->name, ['super_admin','department_user','department_head','finance_reviewer','gceo','board','bdu_admin']))->count();
        $customRolesCount = $totalRoles - $systemRolesCount;
        $totalUsers = $roles->sum('users_count');
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-card text-center">
                <div class="stat-accent" style="background: #1B2A4A;"></div>
                <div class="stat-label">Total Roles</div>
                <div class="stat-value">{{ $totalRoles }}</div>
                <div class="stat-sub">All system roles</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card text-center">
                <div class="stat-accent" style="background: #6366F1;"></div>
                <div class="stat-label">System Roles</div>
                <div class="stat-value" style="color: #6366F1;">{{ $systemRolesCount }}</div>
                <div class="stat-sub">Built-in roles</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card text-center">
                <div class="stat-accent" style="background: #10B981;"></div>
                <div class="stat-label">Custom Roles</div>
                <div class="stat-value" style="color: #10B981;">{{ $customRolesCount }}</div>
                <div class="stat-sub">User-defined roles</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card text-center">
                <div class="stat-accent" style="background: #F59E0B;"></div>
                <div class="stat-label">Total Users</div>
                <div class="stat-value" style="color: #F59E0B;">{{ $totalUsers }}</div>
                <div class="stat-sub">Assigned to roles</div>
            </div>
        </div>
    </div>

    {{-- Roles Table --}}
    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size: 13px;">
                    <thead style="background: #F8FAFC; border-bottom: 2px solid #E65C00;">
                        <tr>
                            <th style="color: #1B2A4A; font-weight: 600; padding: 12px 16px;">
                                <i class="fas fa-tag" style="color: #E65C00;"></i> Role
                            </th>
                            <th style="color: #1B2A4A; font-weight: 600; padding: 12px 16px;">
                                <i class="fas fa-key" style="color: #E65C00;"></i> Permissions
                            </th>
                            <th style="color: #1B2A4A; font-weight: 600; padding: 12px 16px;">
                                <i class="fas fa-users" style="color: #E65C00;"></i> Users
                            </th>
                            <th style="color: #1B2A4A; font-weight: 600; padding: 12px 16px;">
                                <i class="fas fa-cog" style="color: #E65C00;"></i> Type
                            </th>
                            <th style="color: #1B2A4A; font-weight: 600; padding: 12px 16px;">
                                <i class="fas fa-globe" style="color: #E65C00;"></i> Scope
                            </th>
                            <th style="color: #1B2A4A; font-weight: 600; padding: 12px 16px;">
                                <i class="fas fa-check-circle" style="color: #E65C00;"></i> Behaviour
                            </th>
                            <th style="color: #1B2A4A; font-weight: 600; padding: 12px 16px; text-align: center;">
                                <i class="fas fa-tools" style="color: #E65C00;"></i> Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $systemRoles = [
                                'super_admin','department_user','department_head',
                                'finance_reviewer','gceo','board','bdu_admin'
                            ];
                        @endphp

                        @forelse($roles as $role)
                        <tr style="transition: background 0.2s;">
                            <td style="padding: 10px 16px;">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex align-items-center justify-content-center rounded-circle"
                                         style="width: 32px; height: 32px; background: {{ in_array($role->name, $systemRoles) ? '#E65C00' : '#E65C00' }}; color: #fff; font-size: 14px;">
                                        <i class="fas fa-{{ in_array($role->name, $systemRoles) ? 'shield-alt' : 'user-tag' }}"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: #1B2A4A;">
                                            {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                                        </div>
                                        @if($role->description)
                                        <div style="font-size: 11px; color: #94A3B8;">
                                            {{ $role->description }}
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 10px 16px;">
                                <span class="badge px-3 py-1" style="background: #F1F5F9; color: #1B2A4A; font-weight: 500; font-size: 12px; border-radius: 6px;">
                                    <i class="fas fa-key" style="color: #E65C00; font-size: 10px;"></i>
                                    {{ $role->permissions_count }} permissions
                                </span>
                            </td>
                            <td style="padding: 10px 16px;">
                                <span class="badge px-3 py-1" style="background: {{ $role->users_count > 0 ? '#DBEAFE' : '#F1F5F9' }}; color: {{ $role->users_count > 0 ? '#1E40AF' : '#94A3B8' }}; font-weight: 500; font-size: 12px; border-radius: 6px;">
                                    <i class="fas fa-users" style="font-size: 10px;"></i>
                                    {{ $role->users_count }} user{{ $role->users_count != 1 ? 's' : '' }}
                                </span>
                            </td>
                            <td style="padding: 10px 16px;">
                                @if(in_array($role->name, $systemRoles))
                                    <span class="badge px-3 py-1" style="background: #E65C00; color: #fff; font-weight: 500; font-size: 11px; border-radius: 6px;">
                                        <i class="fas fa-shield"></i> System
                                    </span>
                                @else
                                    <span class="badge px-3 py-1" style="background: #FEF3C7; color: #92400E; font-weight: 500; font-size: 11px; border-radius: 6px;">
                                        <i class="fas fa-pencil-alt"></i> Custom
                                    </span>
                                @endif
                            </td>
                            <td style="padding: 10px 16px;">
                                <span class="badge px-3 py-1" style="background: {{ $role->scope === 'all' ? '#DBEAFE' : '#FEF3C7' }}; color: {{ $role->scope === 'all' ? '#1E40AF' : '#92400E' }}; font-weight: 500; font-size: 11px; border-radius: 6px;">
                                    <i class="fas fa-{{ $role->scope === 'all' ? 'globe' : 'building' }}"></i>
                                    {{ $role->scope === 'all' ? 'All Departments' : 'Own Dept' }}
                                </span>
                            </td>
                            <td style="padding: 10px 16px;">
                                <div class="d-flex gap-1 flex-wrap">
                                    @if($role->can_partial_approve)
                                    <span class="badge px-2 py-1" style="background: #D1FAE5; color: #065F46; font-size: 10px; border-radius: 4px;">
                                        <i class="fas fa-check"></i> Partial
                                    </span>
                                    @endif
                                    @if($role->can_reduce_amounts)
                                    <span class="badge px-2 py-1" style="background: #FEF3C7; color: #92400E; font-size: 10px; border-radius: 4px;">
                                        <i class="fas fa-edit"></i> Reduce
                                    </span>
                                    @endif
                                    @if(!$role->can_partial_approve && !$role->can_reduce_amounts)
                                    <span style="font-size: 11px; color: #94A3B8;">
                                        <i class="fas fa-circle" style="font-size: 6px;"></i> All-or-nothing
                                    </span>
                                    @endif
                                </div>
                            </td>
                            <td style="padding: 10px 16px; text-align: center;">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="{{ route('admin.roles.show', $role) }}"
                                       class="btn btn-sm btn-outline-secondary"
                                       style="border-radius: 6px; font-size: 11px; padding: 2px 8px;"
                                       title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.roles.edit', $role) }}"
                                       class="btn btn-sm btn-outline-primary"
                                       style="border-radius: 6px; font-size: 11px; padding: 2px 8px;"
                                       title="Edit Role">
                                        <i class="fas fa-pencil-alt"></i>
                                    </a>
                                    @if(!in_array($role->name, $systemRoles))
                                    <form method="POST"
                                          action="{{ route('admin.roles.destroy', $role) }}"
                                          class="d-inline"
                                          onsubmit="return confirmDelete('{{ $role->name }}')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"
                                                style="border-radius: 6px; font-size: 11px; padding: 2px 8px;"
                                                title="Delete Role">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @else
                                    <button class="btn btn-sm btn-outline-secondary"
                                            style="border-radius: 6px; font-size: 11px; padding: 2px 8px; opacity: 0.5;"
                                            title="System roles cannot be deleted"
                                            disabled>
                                        <i class="fas fa-lock"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <div style="font-size: 48px; margin-bottom: 12px; color: #94A3B8;">
                                    <i class="fas fa-user-tag"></i>
                                </div>
                                <div style="font-weight: 600; color: #1B2A4A; font-size: 16px;">No roles found</div>
                                <div style="font-size: 13px; color: #64748B;">
                                    Click <strong>"Add Role"</strong> to create your first role.
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Footer with info --}}
        <div class="card-footer bg-white border-top py-3 px-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted small">
                    <i class="fas fa-info-circle"></i>
                    Showing {{ $roles->count() }} roles
                </div>
                <div class="text-muted small">
                    <i class="fas fa-shield-alt" style="color: #E65C00;"></i>
                    <span class="fw-semibold">System roles</span> are protected and cannot be deleted
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function confirmDelete(roleName) {
    return confirm(`Are you sure you want to delete the role "${roleName}"?\n\nThis action cannot be undone and will remove this role from all users.`);
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
</style>

@endsection
