@extends('layouts.app')
@section('title', 'Users')
@section('content')

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0" style="color:#1B2A4A">Users</h5>
        <p class="text-muted small mb-0">Manage system users and their access permissions</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.users.import') }}"
           class="btn btn-sm btn-outline-secondary" style="border-radius:8px">
            Bulk Import CSV
        </a>
        <a href="{{ route('admin.users.create') }}"
           class="btn btn-sm" style="background:#E65C00;color:#fff;border-radius:8px;border:none">
            + Add User
        </a>
    </div>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card text-center">
            <div class="stat-accent" style="background:#1B2A4A"></div>
            <div class="stat-label">Total Users</div>
            <div class="stat-value">{{ $users->total() }}</div>
            <div class="stat-sub">All registered</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card text-center">
            <div class="stat-accent" style="background:#10B981"></div>
            <div class="stat-label">Active</div>
            <div class="stat-value" style="color:#10B981">{{ $users->where('is_active', true)->count() }}</div>
            <div class="stat-sub">Active accounts</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card text-center">
            <div class="stat-accent" style="background:#F43F5E"></div>
            <div class="stat-label">Inactive</div>
            <div class="stat-value" style="color:#F43F5E">{{ $users->where('is_active', false)->count() }}</div>
            <div class="stat-sub">Deactivated</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card text-center">
            <div class="stat-accent" style="background:#6366F1"></div>
            <div class="stat-label">Online Now</div>
            <div class="stat-value" style="color:#6366F1">{{ $onlineCount ?? 0 }}</div>
            <div class="stat-sub">Last 15 minutes</div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="chart-card mb-4">
    <form method="GET" action="{{ route('admin.users.index') }}">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1" style="color:#1B2A4A">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-control form-control-sm"
                       placeholder="Name or email…">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1" style="color:#1B2A4A">Dept / Station</label>
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @php $grouped = $departments->groupBy(fn($d) => $d->isServiceStation() ? 'Service Stations' : 'Departments'); @endphp
                    @foreach($grouped as $groupLabel => $items)
                    <optgroup label="{{ $groupLabel }}">
                        @foreach($items as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1" style="color:#1B2A4A">Role</label>
                <select name="role" class="form-select form-select-sm">
                    <option value="">All Roles</option>
                    @foreach($roles as $role)
                    <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1" style="color:#1B2A4A">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="active"   {{ request('status') == 'active'   ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm w-100"
                        style="background:#E65C00;color:#fff;border-radius:8px;border:none">
                    Filter
                </button>
            </div>
        </div>
        @if(request()->hasAny(['search','department_id','role','status']))
        <div class="mt-2">
            <a href="{{ route('admin.users.index') }}" class="small text-muted text-decoration-none">
                Clear filters
            </a>
        </div>
        @endif
    </form>
</div>

{{-- Table --}}
<div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:13px">
                <thead style="background:#F8FAFC;border-bottom:2px solid #E65C00">
                    <tr>
                        <th class="px-4 py-3" style="color:#1B2A4A;font-weight:600">User</th>
                        <th class="px-4 py-3" style="color:#1B2A4A;font-weight:600">Employee ID</th>
                        <th class="px-4 py-3" style="color:#1B2A4A;font-weight:600">Dept / Station</th>
                        <th class="px-4 py-3" style="color:#1B2A4A;font-weight:600">Role</th>
                        <th class="px-4 py-3" style="color:#1B2A4A;font-weight:600">Status</th>
                        <th class="px-4 py-3" style="color:#1B2A4A;font-weight:600">Last Login</th>
                        <th class="px-4 py-3" style="color:#1B2A4A;font-weight:600"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                                     style="width:34px;height:34px;background:{{ $user->is_active ? '#E65C00' : '#94A3B8' }};
                                            color:#fff;font-size:12px;font-weight:700">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="fw-semibold" style="color:#1B2A4A">{{ $user->name }}</div>
                                    <div style="font-size:11px;color:#64748B">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3" style="color:#475569;font-family:monospace;font-size:12px">
                            {{ $user->employee_id ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span style="padding:2px 10px;border-radius:6px;font-size:12px;
                                         background:#F1F5F9;color:#1B2A4A">
                                {{ $user->department?->name ?? ($user->subsidiary?->name ?? '—') }}
                            </span>
                            @if($user->department?->isServiceStation())
                            <span style="font-size:10px;color:#2563EB;display:block;margin-top:1px">station</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @foreach($user->roles as $role)
                            <span class="badge me-1"
                                  style="background:#E65C00;color:#fff;font-weight:500;
                                         font-size:11px;border-radius:6px;padding:3px 8px">
                                {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                            </span>
                            @endforeach
                        </td>
                        <td class="px-4 py-3">
                            <span class="badge"
                                  style="border-radius:20px;font-size:11px;font-weight:600;padding:4px 10px;
                                         background:{{ $user->is_active ? '#D1FAE5' : '#FEE2E2' }};
                                         color:{{ $user->is_active ? '#065F46' : '#991B1B' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3" style="font-size:12px;color:#64748B">
                            {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="{{ route('admin.users.show', $user) }}"
                                   class="btn btn-sm btn-outline-secondary"
                                   style="border-radius:6px;font-size:11px;padding:3px 10px">
                                    View
                                </a>
                                <a href="{{ route('admin.users.edit', $user) }}"
                                   class="btn btn-sm btn-outline-primary"
                                   style="border-radius:6px;font-size:11px;padding:3px 10px">
                                    Edit
                                </a>
                                @if($user->id !== auth()->id())
                                <form method="POST"
                                      action="{{ route('admin.users.toggle-active', $user) }}"
                                      class="d-inline">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm {{ $user->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                            style="border-radius:6px;font-size:11px;padding:3px 10px">
                                        {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <div class="fw-semibold mb-1" style="color:#1B2A4A">No users found</div>
                            <div class="small">
                                @if(request()->hasAny(['search','department_id','role','status']))
                                    Try adjusting your filters or
                                    <a href="{{ route('admin.users.index') }}"
                                       class="text-decoration-none" style="color:#E65C00">clear them</a>
                                @else
                                    Click <strong>Add User</strong> to get started.
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center flex-wrap gap-2 py-3 px-4">
        <div class="small text-muted">
            Showing {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }}
            of {{ $users->total() }} users
        </div>
        {{ $users->appends(request()->query())->links('pagination::bootstrap-5') }}
    </div>
</div>

<style>
.stat-card {
    background:#fff;border-radius:12px;padding:16px 12px;
    border:1px solid #E2E8F0;position:relative;overflow:hidden;
}
.stat-card .stat-accent { position:absolute;top:0;left:0;right:0;height:3px; }
.stat-card .stat-label  { font-size:11px;font-weight:600;color:#94A3B8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px; }
.stat-card .stat-value  { font-size:24px;font-weight:700;color:#1B2A4A; }
.stat-card .stat-sub    { font-size:11px;color:#94A3B8;margin-top:2px; }
.table tbody tr:hover   { background:#F8FAFC; }
.form-control:focus,.form-select:focus { border-color:#E65C00;box-shadow:0 0 0 3px rgba(230,92,0,.1); }
.btn-outline-primary:hover { background:#E65C00;border-color:#E65C00;color:#fff; }
.pagination .page-item.active .page-link { background-color:#E65C00;border-color:#E65C00;color:#fff; }
.pagination .page-link { color:#1B2A4A; }
.pagination .page-link:hover { color:#E65C00; }
</style>

@endsection
