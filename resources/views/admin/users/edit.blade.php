@extends('layouts.app')
@section('title', 'Edit User')
@section('content')

<div class="row justify-content-center">
    <div class="col-lg-7">

        {{-- Breadcrumb --}}
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <div class="small mb-1">
                    <a href="{{ route('admin.users.index') }}"
                       style="color:#64748B;text-decoration:none">Users</a>
                    <span class="mx-1" style="color:#CBD5E1">/</span>
                    <span style="color:#1B2A4A;font-weight:600">{{ $user->name }}</span>
                </div>
                <h5 class="fw-bold mb-0" style="color:#1B2A4A">Edit User</h5>
            </div>
            <span style="font-size:11px;color:#94A3B8;background:#F1F5F9;
                         padding:4px 10px;border-radius:20px;border:1px solid #E2E8F0">
                ID {{ $user->id }}
            </span>
        </div>

        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf @method('PUT')

            {{-- Basic Information --}}
            <div class="card border-0 shadow-sm mb-3" style="border-radius:12px;border:1px solid #E2E8F0!important">
                <div class="card-header px-4 py-3 border-0"
                     style="background:#E65C00;color:#fff;border-radius:11px 11px 0 0">
                    <span class="fw-semibold" style="font-size:13px">Basic Information</span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" style="color:#1B2A4A">Full Name</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}"
                                   class="form-control @error('name') is-invalid @enderror"
                                   placeholder="Full name">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" style="color:#1B2A4A">Email Address</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}"
                                   class="form-control @error('email') is-invalid @enderror"
                                   placeholder="email@example.com">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" style="color:#1B2A4A">Employee ID</label>
                            <input type="text" name="employee_id" value="{{ old('employee_id', $user->employee_id) }}"
                                   class="form-control @error('employee_id') is-invalid @enderror"
                                   placeholder="Employee ID">
                            @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" style="color:#1B2A4A">Phone Number</label>
                            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   placeholder="Phone number">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Entity & Role --}}
            <div class="card border-0 shadow-sm mb-3" style="border-radius:12px;border:1px solid #E2E8F0!important">
                <div class="card-header px-4 py-3 border-0"
                     style="background:#E65C00;color:#fff;border-radius:11px 11px 0 0">
                    <span class="fw-semibold" style="font-size:13px">Entity &amp; Role</span>
                </div>
                <div class="card-body p-4">
                    @php $isSub = filled(old('subsidiary_id', $user->subsidiary_id)); @endphp
                    <div class="d-flex gap-3 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="entity_type"
                                   id="et_dept_edit" value="department"
                                   {{ $isSub ? '' : 'checked' }}
                                   onchange="onEntityTypeChange(this.value)">
                            <label class="form-check-label small" for="et_dept_edit">Department / Station</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="entity_type"
                                   id="et_sub_edit" value="subsidiary"
                                   {{ $isSub ? 'checked' : '' }}
                                   onchange="onEntityTypeChange(this.value)">
                            <label class="form-check-label small" for="et_sub_edit">Subsidiary</label>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6" id="deptField" {{ $isSub ? 'style=display:none' : '' }}>
                            <label class="form-label small fw-semibold" style="color:#1B2A4A">Dept / Station</label>
                            @include('admin.users._dept_select', [
                                'selectedId' => old('department_id', $user->department_id),
                                'inputName'  => 'department_id',
                            ])
                        </div>
                        <div class="col-md-6" id="subField" {{ $isSub ? '' : 'style=display:none' }}>
                            <label class="form-label small fw-semibold" style="color:#1B2A4A">Subsidiary</label>
                            <select name="subsidiary_id"
                                    class="form-select @error('subsidiary_id') is-invalid @enderror"
                                    id="subsidiarySelect">
                                <option value="">— Select subsidiary —</option>
                                @foreach($subsidiaries->groupBy('category.name') as $catName => $subs)
                                    <optgroup label="{{ $catName ?? 'Uncategorised' }}">
                                        @foreach($subs as $sub)
                                            <option value="{{ $sub->id }}"
                                                {{ old('subsidiary_id', $user->subsidiary_id) == $sub->id ? 'selected' : '' }}>
                                                {{ $sub->name }} ({{ $sub->code }})
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('subsidiary_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" style="color:#1B2A4A">Role</label>
                            <select name="role" class="form-select @error('role') is-invalid @enderror">
                                <option value="">— Select role —</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}"
                                        {{ old('role', $user->roles->first()?->name) == $role->name ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Status & Security --}}
            <div class="card border-0 shadow-sm mb-3" style="border-radius:12px;border:1px solid #E2E8F0!important">
                <div class="card-header px-4 py-3 border-0"
                     style="background:#E65C00;color:#fff;border-radius:11px 11px 0 0">
                    <span class="fw-semibold" style="font-size:13px">Status &amp; Security</span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded-2" style="background:#F8FAFC;border:1px solid #E2E8F0">
                                <div class="small fw-semibold mb-2" style="color:#1B2A4A">Account Status</div>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input type="radio" name="is_active" value="1"
                                               id="status_active" class="form-check-input"
                                               {{ old('is_active', $user->is_active ? '1' : '0') === '1' ? 'checked' : '' }}
                                               {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                                        <label for="status_active" class="form-check-label small fw-semibold"
                                               style="color:#10B981">Active</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" name="is_active" value="0"
                                               id="status_inactive" class="form-check-input"
                                               {{ old('is_active', $user->is_active ? '1' : '0') === '0' ? 'checked' : '' }}
                                               {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                                        <label for="status_inactive" class="form-check-label small fw-semibold"
                                               style="color:#F43F5E">Inactive</label>
                                    </div>
                                </div>
                                @if($user->id === auth()->id())
                                <div style="font-size:11px;color:#94A3B8;margin-top:6px">
                                    You cannot deactivate your own account.
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-2 h-100" style="background:#F8FAFC;border:1px solid #E2E8F0">
                                <div class="d-flex align-items-start justify-content-between gap-2">
                                    <div style="flex:1">
                                        <div class="small fw-semibold" style="color:#1B2A4A">Two-Factor Authentication</div>
                                        <div style="font-size:11px;color:#64748B;margin-top:2px">
                                            @if($user->two_factor_secret)
                                                Authenticator app configured.
                                                {{ $user->two_factor_enabled ? 'Currently enforced.' : 'Currently off.' }}
                                            @else
                                                No authenticator app set up yet.
                                            @endif
                                        </div>
                                    </div>
                                    <div class="form-check form-switch ms-2" style="flex-shrink:0;padding-top:2px">
                                        <input class="form-check-input" type="checkbox"
                                               role="switch" id="tfa_edit"
                                               name="two_factor_enabled" value="1"
                                               {{ old('two_factor_enabled', $user->two_factor_enabled ? '1' : '0') === '1' ? 'checked' : '' }}
                                               style="width:2.5em;height:1.3em;cursor:pointer">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Direct Permission Overrides (Super Admin Only) --}}
            @if(auth()->user()->hasRole('super_admin'))
            <div class="card border-0 shadow-sm mb-3" style="border-radius:12px;border:1px solid #E2E8F0!important">
                <div class="card-header px-4 py-3 border-0 d-flex align-items-center justify-content-between"
                     style="background:#1B2A4A;color:#fff;cursor:pointer;border-radius:11px 11px 0 0"
                     onclick="togglePerms(this)">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-semibold" style="font-size:13px">Direct Permission Overrides</span>
                        <span class="badge"
                              style="background:rgba(255,255,255,.15);color:#fff;font-size:10px;border-radius:4px">
                            Super Admin only
                        </span>
                    </div>
                    <span id="perms-chevron" style="font-size:13px;transition:transform .2s">▼</span>
                </div>
                <div id="perms-body" style="display:none">
                    <div class="card-body p-4">
                        <p class="small text-muted mb-3">These apply on top of the role's permissions.</p>
                        @php
                            $allPerms        = \Spatie\Permission\Models\Permission::orderBy('name')->get();
                            $userDirectPerms = $user->getDirectPermissions()->pluck('name')->toArray();
                        @endphp
                        <div class="row g-2">
                            @foreach($allPerms as $perm)
                            <div class="col-lg-4 col-md-6">
                                <div class="form-check p-2 rounded-2"
                                     style="{{ in_array($perm->name, $userDirectPerms) ? 'background:rgba(230,92,0,.05)' : '' }}">
                                    <input type="checkbox"
                                           name="direct_permissions[]"
                                           value="{{ $perm->name }}"
                                           id="dp_{{ $perm->id }}"
                                           class="form-check-input"
                                           {{ in_array($perm->name, $userDirectPerms) ? 'checked' : '' }}>
                                    <label for="dp_{{ $perm->id }}"
                                           class="form-check-label small" style="color:#1B2A4A;cursor:pointer">
                                        {{ $perm->name }}
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Actions --}}
            <div class="d-flex gap-2 pt-1">
                <button type="submit" class="btn fw-semibold px-4"
                        style="background:#E65C00;color:#fff;border:none;border-radius:8px">
                    Save Changes
                </button>
                <a href="{{ route('admin.users.index') }}"
                   class="btn fw-semibold px-4"
                   style="background:#F1F5F9;color:#475569;border:1px solid #E2E8F0;border-radius:8px">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</div>

<style>
.form-control, .form-select {
    border-color: #E2E8F0;
    border-radius: 8px;
    padding: 9px 12px;
    font-size: 13px;
}
.form-control:focus, .form-select:focus {
    border-color: #E65C00;
    box-shadow: 0 0 0 3px rgba(230,92,0,.1);
}
.form-check-input:checked {
    background-color: #E65C00;
    border-color: #E65C00;
}
.form-check-input:focus {
    box-shadow: 0 0 0 3px rgba(230,92,0,.1);
}
</style>

<script>
function onEntityTypeChange(value) {
    const deptField = document.getElementById('deptField');
    const subField  = document.getElementById('subField');
    if (value === 'subsidiary') {
        deptField.style.display = 'none';
        subField.style.display  = '';
        const s = deptField.querySelector('select');
        if (s) s.value = '';
    } else {
        deptField.style.display = '';
        subField.style.display  = 'none';
        const s = document.getElementById('subsidiarySelect');
        if (s) s.value = '';
    }
}

function togglePerms(header) {
    const body    = document.getElementById('perms-body');
    const chevron = document.getElementById('perms-chevron');
    const open    = body.style.display !== 'none';
    body.style.display    = open ? 'none' : '';
    chevron.style.transform = open ? '' : 'rotate(180deg)';
}
</script>

@endsection
