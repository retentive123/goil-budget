@extends('layouts.app')
@section('title', 'Add User')
@section('content')

<div class="row justify-content-center">
<div class="col-lg-8">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <div class="small mb-1">
                <a href="{{ route('admin.users.index') }}"
                   style="color:#64748B;text-decoration:none">Users</a>
                <span class="mx-1" style="color:#CBD5E1">/</span>
                <span style="color:#1B2A4A;font-weight:600">Add User</span>
            </div>
            <h5 class="fw-bold mb-0" style="color:#1B2A4A">Add New User</h5>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf

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
                        <input type="text" name="name" value="{{ old('name') }}"
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="Enter full name">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" style="color:#1B2A4A">Email Address</label>
                        <input type="email" name="email" value="{{ old('email') }}"
                               class="form-control @error('email') is-invalid @enderror"
                               placeholder="email@example.com">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" style="color:#1B2A4A">
                            Employee ID <span class="fw-normal text-muted">(optional)</span>
                        </label>
                        <input type="text" name="employee_id" value="{{ old('employee_id') }}"
                               class="form-control @error('employee_id') is-invalid @enderror"
                               placeholder="e.g. EMP-0042">
                        @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" style="color:#1B2A4A">
                            Phone Number <span class="fw-normal text-muted">(optional)</span>
                        </label>
                        <input type="text" name="phone" value="{{ old('phone') }}"
                               class="form-control @error('phone') is-invalid @enderror"
                               placeholder="e.g. 024 000 0000">
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
                <div class="d-flex gap-3 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="entity_type"
                               id="et_dept" value="department"
                               {{ old('subsidiary_id') ? '' : 'checked' }}
                               onchange="onEntityTypeChange(this.value)">
                        <label class="form-check-label small" for="et_dept">Department / Station</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="entity_type"
                               id="et_sub" value="subsidiary"
                               {{ old('subsidiary_id') ? 'checked' : '' }}
                               onchange="onEntityTypeChange(this.value)">
                        <label class="form-check-label small" for="et_sub">Subsidiary</label>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6" id="deptField" {{ old('subsidiary_id') ? 'style=display:none' : '' }}>
                        <label class="form-label small fw-semibold" style="color:#1B2A4A">Dept / Station</label>
                        @include('admin.users._dept_select', [
                            'selectedId' => old('department_id'),
                            'inputName'  => 'department_id',
                        ])
                    </div>
                    <div class="col-md-6" id="subField" {{ old('subsidiary_id') ? '' : 'style=display:none' }}>
                        <label class="form-label small fw-semibold" style="color:#1B2A4A">Subsidiary</label>
                        <select name="subsidiary_id"
                                class="form-select @error('subsidiary_id') is-invalid @enderror"
                                id="subsidiarySelect">
                            <option value="">— Select subsidiary —</option>
                            @foreach($subsidiaries->groupBy('category.name') as $catName => $subs)
                                <optgroup label="{{ $catName ?? 'Uncategorised' }}">
                                    @foreach($subs as $sub)
                                        <option value="{{ $sub->id }}"
                                            {{ old('subsidiary_id') == $sub->id ? 'selected' : '' }}>
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
                                    {{ old('role') == $role->name ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                                </option>
                            @endforeach
                        </select>
                        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Password --}}
        <div class="card border-0 shadow-sm mb-3" style="border-radius:12px;border:1px solid #E2E8F0!important">
            <div class="card-header px-4 py-3 border-0"
                 style="background:#E65C00;color:#fff;border-radius:11px 11px 0 0">
                <span class="fw-semibold" style="font-size:13px">Temporary Password</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label small fw-semibold" style="color:#1B2A4A">Password</label>
                        <div class="input-group">
                            <input type="password" name="password" id="passwordInput"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="Min 8 chars, uppercase, number &amp; symbol">
                            <button type="button" class="btn btn-outline-secondary"
                                    style="border-color:#E2E8F0;border-radius:0 8px 8px 0"
                                    onclick="togglePwd()">
                                <i class="fas fa-eye" id="pwdEyeIcon"></i>
                            </button>
                        </div>
                        <div class="mt-1" style="font-size:11px;color:#94A3B8">
                            Must include uppercase, number and symbol. User will be asked to change on first login.
                        </div>
                        @error('password')<div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Security --}}
        <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;border:1px solid #E2E8F0!important">
            <div class="card-header px-4 py-3 border-0"
                 style="background:#E65C00;color:#fff;border-radius:11px 11px 0 0">
                <span class="fw-semibold" style="font-size:13px">Security</span>
            </div>
            <div class="card-body p-4">
                <div class="p-3 rounded-2" style="background:#F8FAFC;border:1px solid #E2E8F0">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="small fw-semibold" style="color:#1B2A4A">Two-Factor Authentication (2FA)</div>
                            <div style="font-size:11px;color:#64748B;margin-top:2px">
                                When enabled, the user must verify via an authenticator app each login.
                            </div>
                        </div>
                        <div class="form-check form-switch ms-2" style="flex-shrink:0">
                            <input class="form-check-input" type="checkbox"
                                   role="switch" id="tfa_create"
                                   name="two_factor_enabled" value="1"
                                   {{ old('two_factor_enabled') ? 'checked' : '' }}
                                   style="width:2.5em;height:1.3em;cursor:pointer">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="d-flex gap-2">
            <button type="submit" class="btn fw-semibold px-4"
                    style="background:#E65C00;color:#fff;border:none;border-radius:8px">
                Create User
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
.form-control,.form-select{border-color:#E2E8F0;border-radius:8px;padding:9px 12px;font-size:13px}
.form-control:focus,.form-select:focus{border-color:#E65C00;box-shadow:0 0 0 3px rgba(230,92,0,.1)}
.form-check-input:checked{background-color:#E65C00;border-color:#E65C00}
.form-check-input:focus{box-shadow:0 0 0 3px rgba(230,92,0,.1)}
</style>

<script>
function togglePwd() {
    const inp  = document.getElementById('passwordInput');
    const icon = document.getElementById('pwdEyeIcon');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        inp.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

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
</script>
@endsection
