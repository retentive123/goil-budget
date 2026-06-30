@extends('layouts.app')
@section('title', 'Role Detail')
@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.roles.index') }}"
       class="text-muted text-decoration-none">Roles</a>
    <span class="text-muted">/</span>
    <span>{{ $role->name }}</span>
</div>

<div class="row g-4">

    {{-- Permissions --}}
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light py-2 d-flex justify-content-between">
                <span class="fw-semibold small">
                    Permissions
                    <span class="badge bg-secondary ms-1">
                        {{ $role->permissions->count() }}
                    </span>
                </span>
                <a href="{{ route('admin.roles.edit', $role) }}"
                   class="small text-primary">Edit</a>
            </div>
            <div class="card-body">
                @php
                    $grouped = $role->permissions->groupBy(function($p) {
                        return ucfirst(explode(' ', $p->name)[0]);
                    });
                @endphp

                @forelse($grouped as $group => $perms)
                <div class="mb-3">
                    <div class="small text-muted text-uppercase fw-semibold mb-1">
                        {{ $group }}
                    </div>
                    @foreach($perms as $perm)
                    <span class="badge bg-light text-dark border me-1 mb-1">
                        {{ $perm->name }}
                    </span>
                    @endforeach
                </div>
                @empty
                <div class="text-muted small">No permissions assigned.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Add below the permissions card --}}
    <div class="chart-card mb-3">
        <div class="chart-title">Approval Behaviour</div>

        <div class="row g-3">
            <div class="col-6">
                <div style="font-size:11px;color:var(--slate)">Budget Scope</div>
                <div style="font-size:13px;font-weight:600;color:var(--navy);margin-top:4px">
                    {{ $role->scope === 'all' ? 'All Departments' : 'Own Department Only' }}
                </div>
            </div>
            <div class="col-6">
                <div style="font-size:11px;color:var(--slate)">Partial Approve</div>
                <div style="font-size:13px;font-weight:600;margin-top:4px;
                            color:{{ $role->can_partial_approve ? '#10B981' : '#94A3B8' }}">
                    {{ $role->can_partial_approve ? 'Yes' : 'No' }}
                </div>
            </div>
            <div class="col-6">
                <div style="font-size:11px;color:var(--slate)">Can Reduce Amounts</div>
                <div style="font-size:13px;font-weight:600;margin-top:4px;
                            color:{{ $role->can_reduce_amounts ? '#10B981' : '#94A3B8' }}">
                    {{ $role->can_reduce_amounts ? 'Yes' : 'No' }}
                </div>
            </div>
            @if($role->description)
            <div class="col-12">
                <div style="font-size:11px;color:var(--slate)">Description</div>
                <div style="font-size:13px;color:var(--navy);margin-top:4px">
                    {{ $role->description }}
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Users with this role --}}
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light py-2">
                <span class="fw-semibold small">
                    Users with this role
                    <span class="badge bg-secondary ms-1">{{ $users->count() }}</span>
                </span>
            </div>
            <div class="card-body p-0">
                @forelse($users as $user)
                <div class="d-flex justify-content-between align-items-center
                            px-3 py-2 border-bottom">
                    <div>
                        <div class="small fw-semibold">{{ $user->name }}</div>
                        <div class="small text-muted">
                            {{ $user->email }}
                            @if($user->department)
                                &middot; {{ $user->department->name }}
                            @endif
                        </div>
                    </div>
                    <span class="badge bg-{{ $user->is_active ? 'success' : 'secondary' }}">
                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                @empty
                <div class="text-muted small p-3">No users assigned to this role.</div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection
