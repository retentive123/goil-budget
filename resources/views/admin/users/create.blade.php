@extends('layouts.app')
@section('title', 'Add User')
@section('content')

<div class="row justify-content-center">
<div class="col-lg-8">

    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
                <a href="{{ route('admin.users.index') }}" class="text-decoration-none">Users</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Add User</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-lg" style="border-radius:16px;overflow:hidden">

        {{-- Header --}}
        <div class="card-header border-0 px-4 py-3" style="background:var(--navy);color:#fff">
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center justify-content-center rounded-circle"
                     style="width:44px;height:44px;background:rgba(255,255,255,0.12);font-size:20px">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0" style="color:#fff">Add New User</h5>
                    <p class="mb-0" style="font-size:13px;color:rgba(255,255,255,0.7)">
                        Create a system account and assign a role
                    </p>
                </div>
            </div>
        </div>

        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf

                {{-- ── Section 1: Basic Information ── --}}
                <div class="mb-4">
                    <h6 class="fw-semibold mb-3" style="color:var(--navy);font-size:13px">
                        <i class="fas fa-user-circle" style="color:var(--orange)"></i> Basic Information
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="color:var(--navy);font-size:13px">
                                <i class="fas fa-user" style="color:var(--orange)"></i> Full Name
                            </label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:#F8FAFC;border-color:#E2E8F0;color:#64748B">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text" name="name" value="{{ old('name') }}"
                                       class="form-control @error('name') is-invalid @enderror"
                                       placeholder="Enter full name">
                            </div>
                            @error('name')<div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="color:var(--navy);font-size:13px">
                                <i class="fas fa-envelope" style="color:var(--orange)"></i> Email Address
                            </label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:#F8FAFC;border-color:#E2E8F0;color:#64748B">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" name="email" value="{{ old('email') }}"
                                       class="form-control @error('email') is-invalid @enderror"
                                       placeholder="Enter email address">
                            </div>
                            @error('email')<div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="color:var(--navy);font-size:13px">
                                <i class="fas fa-id-card" style="color:var(--orange)"></i> Employee ID
                                <span class="fw-normal text-muted">(optional)</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:#F8FAFC;border-color:#E2E8F0;color:#64748B">
                                    <i class="fas fa-id-card"></i>
                                </span>
                                <input type="text" name="employee_id" value="{{ old('employee_id') }}"
                                       class="form-control @error('employee_id') is-invalid @enderror"
                                       placeholder="e.g. EMP-0042">
                            </div>
                            @error('employee_id')<div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="color:var(--navy);font-size:13px">
                                <i class="fas fa-phone" style="color:var(--orange)"></i> Phone Number
                                <span class="fw-normal text-muted">(optional)</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:#F8FAFC;border-color:#E2E8F0;color:#64748B">
                                    <i class="fas fa-phone"></i>
                                </span>
                                <input type="text" name="phone" value="{{ old('phone') }}"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       placeholder="e.g. 024 000 0000">
                            </div>
                            @error('phone')<div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- ── Section 2: Department & Role ── --}}
                <div class="mb-4 pt-3 border-top">
                    <h6 class="fw-semibold mb-3" style="color:var(--navy);font-size:13px">
                        <i class="fas fa-building" style="color:var(--orange)"></i> Department &amp; Role
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="color:var(--navy);font-size:13px">
                                <i class="fas fa-building" style="color:var(--orange)"></i> Department
                            </label>
                            <select name="department_id"
                                    class="form-select @error('department_id') is-invalid @enderror">
                                <option value="">— None —</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}"
                                        {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')<div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="color:var(--navy);font-size:13px">
                                <i class="fas fa-user-tag" style="color:var(--orange)"></i> Role
                            </label>
                            <select name="role"
                                    class="form-select @error('role') is-invalid @enderror">
                                <option value="">— Select role —</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}"
                                        {{ old('role') == $role->name ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role')<div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- ── Section 3: Password ── --}}
                <div class="mb-4 pt-3 border-top">
                    <h6 class="fw-semibold mb-3" style="color:var(--navy);font-size:13px">
                        <i class="fas fa-lock" style="color:var(--orange)"></i> Password
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold" style="color:var(--navy);font-size:13px">
                                Temporary Password
                            </label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:#F8FAFC;border-color:#E2E8F0;color:#64748B">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" name="password" id="passwordInput"
                                       class="form-control @error('password') is-invalid @enderror"
                                       placeholder="Min 8 chars, uppercase, number &amp; symbol">
                                <button type="button" class="btn btn-outline-secondary"
                                        style="border-color:#E2E8F0"
                                        onclick="togglePwd()">
                                    <i class="fas fa-eye" id="pwdEyeIcon"></i>
                                </button>
                            </div>
                            <div class="mt-1" style="font-size:11px;color:#64748B">
                                Must include uppercase, number and symbol.
                            </div>
                            @error('password')<div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- ── Section 4: Security ── --}}
                <div class="mb-4 pt-3 border-top">
                    <h6 class="fw-semibold mb-3" style="color:var(--navy);font-size:13px">
                        <i class="fas fa-shield-alt" style="color:var(--orange)"></i> Security
                    </h6>
                    <div class="p-3 rounded-3" style="background:#F8FAFC;border:1px solid #E2E8F0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="fw-semibold" style="font-size:13px;color:var(--navy)">
                                    Two-Factor Authentication (2FA)
                                </div>
                                <div style="font-size:11px;color:#64748B;margin-top:2px">
                                    When enabled, this user must verify via an authenticator app each login.
                                    Takes effect after they complete the QR setup.
                                </div>
                            </div>
                            <div class="form-check form-switch ms-3" style="flex-shrink:0">
                                <input class="form-check-input" type="checkbox"
                                       role="switch" id="tfa_create"
                                       name="two_factor_enabled" value="1"
                                       {{ old('two_factor_enabled') ? 'checked' : '' }}
                                       style="width:2.5em;height:1.3em;cursor:pointer">
                                <label class="form-check-label fw-semibold"
                                       for="tfa_create"
                                       style="font-size:12px;color:var(--navy);cursor:pointer">
                                    On
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Actions ── --}}
                <div class="d-flex gap-2 pt-3 border-top">
                    <button type="submit" class="btn px-4 py-2 fw-semibold"
                            style="background:var(--navy);color:#fff;border-radius:10px;border:none">
                        <i class="fas fa-user-plus"></i> Create User
                    </button>
                    <a href="{{ route('admin.users.index') }}"
                       class="btn px-4 py-2 fw-semibold"
                       style="background:#F1F5F9;color:#475569;border-radius:10px;border:1px solid #E2E8F0">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

</div>
</div>

<style>
.form-control, .form-select { border-color:#E2E8F0; padding:10px 14px; }
.form-control:focus, .form-select:focus {
    border-color:var(--orange);
    box-shadow:0 0 0 0.2rem rgba(230,92,0,.15);
}
.form-check-input:checked { background-color:var(--orange); border-color:var(--orange); }
.form-check-input:focus   { box-shadow:0 0 0 0.2rem rgba(230,92,0,.15); }
.breadcrumb { background:transparent; padding:0; }
.breadcrumb-item a { color:#64748B; text-decoration:none; }
.breadcrumb-item a:hover { color:var(--orange); }
.breadcrumb-item.active { color:var(--navy); font-weight:600; }
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
</script>
@endsection
