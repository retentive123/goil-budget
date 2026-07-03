@extends('layouts.app')
@section('title', 'Edit User')
@section('content')

<div class="row justify-content-center">
    <div class="col-lg-8">

        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('admin.users.index') }}" class="text-decoration-none">
                        <i class="fas fa-users"></i> Users
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Edit — {{ $user->name }}
                </li>
            </ol>
        </nav>

        {{-- Main Card --}}
        <div class="card border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            {{-- Card Header --}}
            <div class="card-header border-0 px-4 py-3" style="background: #E65C00; color: #fff;">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-circle"
                         style="width: 44px; height: 44px; background: rgba(255,255,255,0.12); font-size: 20px;">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0" style="color: #fff;">Edit User</h5>
                        <p class="mb-0" style="font-size: 13px; color: rgba(255,255,255,0.7);">
                            {{ $user->name }} — Update user details and permissions
                        </p>
                    </div>
                    <div class="ms-auto">
                        <span class="badge px-3 py-2" style="background: rgba(255,255,255,0.15); color: #fff; font-size: 11px; border-radius: 20px;">
                            <i class="fas fa-user"></i> ID: {{ $user->id }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Card Body --}}
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.users.update', $user) }}">
                    @csrf @method('PUT')

                    {{-- Basic Information --}}
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-3" style="color: #1B2A4A; font-size: 13px;">
                            <i class="fas fa-user-circle" style="color: #E65C00;"></i> Basic Information
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="color: #1B2A4A; font-size: 13px;">
                                    <i class="fas fa-user" style="color: #E65C00;"></i> Full Name
                                </label>
                                <div class="input-group" style="border-radius: 10px; overflow: hidden;">
                                    <span class="input-group-text" style="background: #F8FAFC; border-color: #E2E8F0; color: #64748B;">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                                           class="form-control @error('name') is-invalid @enderror"
                                           style="border-color: #E2E8F0; padding: 10px 14px;"
                                           placeholder="Enter full name">
                                </div>
                                @error('name')<div class="invalid-feedback d-block mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="color: #1B2A4A; font-size: 13px;">
                                    <i class="fas fa-envelope" style="color: #E65C00;"></i> Email Address
                                </label>
                                <div class="input-group" style="border-radius: 10px; overflow: hidden;">
                                    <span class="input-group-text" style="background: #F8FAFC; border-color: #E2E8F0; color: #64748B;">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                                           class="form-control @error('email') is-invalid @enderror"
                                           style="border-color: #E2E8F0; padding: 10px 14px;"
                                           placeholder="Enter email address">
                                </div>
                                @error('email')<div class="invalid-feedback d-block mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="color: #1B2A4A; font-size: 13px;">
                                    <i class="fas fa-id-card" style="color: #E65C00;"></i> Employee ID
                                </label>
                                <div class="input-group" style="border-radius: 10px; overflow: hidden;">
                                    <span class="input-group-text" style="background: #F8FAFC; border-color: #E2E8F0; color: #64748B;">
                                        <i class="fas fa-id-card"></i>
                                    </span>
                                    <input type="text" name="employee_id" value="{{ old('employee_id', $user->employee_id) }}"
                                           class="form-control @error('employee_id') is-invalid @enderror"
                                           style="border-color: #E2E8F0; padding: 10px 14px;"
                                           placeholder="Enter employee ID">
                                </div>
                                @error('employee_id')<div class="invalid-feedback d-block mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="color: #1B2A4A; font-size: 13px;">
                                    <i class="fas fa-phone" style="color: #E65C00;"></i> Phone Number
                                </label>
                                <div class="input-group" style="border-radius: 10px; overflow: hidden;">
                                    <span class="input-group-text" style="background: #F8FAFC; border-color: #E2E8F0; color: #64748B;">
                                        <i class="fas fa-phone"></i>
                                    </span>
                                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                                           class="form-control @error('phone') is-invalid @enderror"
                                           style="border-color: #E2E8F0; padding: 10px 14px;"
                                           placeholder="Enter phone number">
                                </div>
                                @error('phone')<div class="invalid-feedback d-block mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    {{-- Department & Role --}}
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-3" style="color: #1B2A4A; font-size: 13px;">
                            <i class="fas fa-building" style="color: #E65C00;"></i> Department & Role
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="color: #1B2A4A; font-size: 13px;">
                                    <i class="fas fa-building" style="color: #E65C00;"></i> Department
                                </label>
                                <select name="department_id"
                                        class="form-select @error('department_id') is-invalid @enderror"
                                        style="border-radius: 10px; border-color: #E2E8F0; padding: 10px 14px;">
                                    <option value="">— None —</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}"
                                            {{ old('department_id', $user->department_id) == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('department_id')<div class="invalid-feedback d-block mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="color: #1B2A4A; font-size: 13px;">
                                    <i class="fas fa-user-tag" style="color: #E65C00;"></i> Role
                                </label>
                                <select name="role"
                                        class="form-select @error('role') is-invalid @enderror"
                                        style="border-radius: 10px; border-color: #E2E8F0; padding: 10px 14px;">
                                    <option value="">— Select role —</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}"
                                            {{ old('role', $user->roles->first()?->name) == $role->name ? 'selected' : '' }}>
                                            {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role')<div class="invalid-feedback d-block mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    {{-- Status & Security --}}
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-3" style="color: #1B2A4A; font-size: 13px;">
                            <i class="fas fa-shield-alt" style="color: #E65C00;"></i> Status &amp; Security
                        </h6>

                        <div class="row g-3">
                            {{-- Account Status --}}
                            <div class="col-md-6">
                                <div class="p-3 rounded-3 h-100" style="background:#F8FAFC;border:1px solid #E2E8F0">
                                    <div class="fw-semibold mb-2" style="font-size:13px;color:#1B2A4A">
                                        Account Status
                                    </div>
                                    <div class="d-flex gap-4">
                                        <div class="form-check">
                                            <input type="radio" name="is_active" value="1"
                                                   id="status_active" class="form-check-input"
                                                   {{ old('is_active', $user->is_active ? '1' : '0') === '1' ? 'checked' : '' }}
                                                   {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                                            <label for="status_active" class="form-check-label fw-semibold"
                                                   style="color:#10B981;font-size:13px">
                                                <i class="fas fa-check-circle"></i> Active
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input type="radio" name="is_active" value="0"
                                                   id="status_inactive" class="form-check-input"
                                                   {{ old('is_active', $user->is_active ? '1' : '0') === '0' ? 'checked' : '' }}
                                                   {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                                            <label for="status_inactive" class="form-check-label fw-semibold"
                                                   style="color:#F43F5E;font-size:13px">
                                                <i class="fas fa-times-circle"></i> Inactive
                                            </label>
                                        </div>
                                    </div>
                                    @if($user->id === auth()->id())
                                    <div style="font-size:11px;color:#94A3B8;margin-top:6px">
                                        You cannot deactivate your own account.
                                    </div>
                                    @endif
                                </div>
                            </div>

                            {{-- 2FA Toggle --}}
                            <div class="col-md-6">
                                <div class="p-3 rounded-3 h-100" style="background:#F8FAFC;border:1px solid #E2E8F0">
                                    <div class="d-flex align-items-start justify-content-between gap-2">
                                        <div style="flex:1">
                                            <div class="fw-semibold" style="font-size:13px;color:#1B2A4A">
                                                Two-Factor Authentication
                                            </div>
                                            <div style="font-size:11px;color:#64748B;margin-top:2px">
                                                @if($user->two_factor_secret)
                                                    Authenticator app configured.
                                                    {{ $user->two_factor_enabled ? 'Currently enforced.' : 'Currently off.' }}
                                                @else
                                                    No authenticator app set up yet.
                                                    Enabling will take effect after setup.
                                                @endif
                                            </div>
                                        </div>
                                        <div class="form-check form-switch ms-2" style="flex-shrink:0;padding-top:2px">
                                            <input class="form-check-input" type="checkbox"
                                                   role="switch" id="tfa_edit"
                                                   name="two_factor_enabled" value="1"
                                                   {{ old('two_factor_enabled', $user->two_factor_enabled ? '1' : '0') === '1' ? 'checked' : '' }}
                                                   style="width:2.5em;height:1.3em;cursor:pointer">
                                            <label class="form-check-label fw-semibold"
                                                   for="tfa_edit"
                                                   style="font-size:12px;color:#1B2A4A;cursor:pointer">
                                                On
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Direct Permission Overrides (Super Admin Only) --}}
                    @if(auth()->user()->hasRole('super_admin'))
                    <div class="mb-4 pt-3 border-top">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="fas fa-key" style="color: #E65C00;"></i>
                            <span class="fw-semibold" style="color: #1B2A4A; font-size: 13px;">
                                Direct Permission Overrides
                            </span>
                            <span class="badge px-2 py-1" style="background: #FEF3C7; color: #92400E; font-size: 10px; border-radius: 4px;">
                                Super Admin Only
                            </span>
                        </div>
                        <div style="font-size: 12px; color: #64748B; margin-bottom: 12px;">
                            <i class="fas fa-info-circle"></i>
                            These apply on top of the role's permissions.
                        </div>
                        @php
                            $allPerms = \Spatie\Permission\Models\Permission::orderBy('name')->get();
                            $userDirectPerms = $user->getDirectPermissions()->pluck('name')->toArray();
                        @endphp
                        <div class="row g-2">
                            @foreach($allPerms as $perm)
                            <div class="col-lg-4 col-md-6">
                                <div class="form-check p-2 rounded-2"
                                     style="transition: background 0.2s; {{ in_array($perm->name, $userDirectPerms) ? 'background: rgba(230, 92, 0, 0.05);' : '' }}">
                                    <input type="checkbox"
                                        name="direct_permissions[]"
                                        value="{{ $perm->name }}"
                                        id="dp_{{ $perm->id }}"
                                        class="form-check-input"
                                        {{ in_array($perm->name, $userDirectPerms) ? 'checked' : '' }}
                                        style="border-color: #E65C00;">
                                    <label for="dp_{{ $perm->id }}"
                                           class="form-check-label small"
                                           style="color: #1B2A4A; cursor: pointer;">
                                        {{ $perm->name }}
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Action Buttons --}}
                    <div class="d-flex gap-2 pt-3 border-top">
                        <button type="submit" class="btn px-4 py-2 fw-semibold"
                                style="background: #E65C00; color: #fff; border-radius: 10px; border: none; transition: all 0.3s ease;">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="{{ route('admin.users.index') }}"
                           class="btn px-4 py-2 fw-semibold"
                           style="background: #F1F5F9; color: #475569; border-radius: 10px; border: 1px solid #E2E8F0; transition: all 0.3s ease;">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Security Tip --}}
        <div class="mt-3 p-3 rounded-3" style="background: #F8FAFC; border: 1px solid #E2E8F0;">
            <div class="d-flex gap-2 align-items-start">
                <i class="fas fa-shield-alt" style="color: #C9A84C; font-size: 18px;"></i>
                <div>
                    <div style="font-size: 12px; font-weight: 600; color: #1B2A4A;">Security Tip</div>
                    <div style="font-size: 12px; color: #64748B;">
                        Changing a user's role or permissions takes effect immediately.
                        Users will need to re-login to see permission changes.
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    .form-control:focus, .form-select:focus {
        border-color: #E65C00;
        box-shadow: 0 0 0 0.2rem rgba(230, 92, 0, 0.15);
    }

    .form-check-input:checked {
        background-color: #E65C00;
        border-color: #E65C00;
    }

    .form-check-input:focus {
        border-color: #E65C00;
        box-shadow: 0 0 0 0.2rem rgba(230, 92, 0, 0.15);
    }

    .btn-goil-orange:hover {
        background: #C44D00 !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(230, 92, 0, 0.3);
    }

    .breadcrumb {
        background: transparent;
        padding: 0;
    }

    .breadcrumb-item a {
        color: #64748B;
        text-decoration: none;
        transition: color 0.2s;
    }

    .breadcrumb-item a:hover {
        color: #E65C00;
    }

    .breadcrumb-item.active {
        color: #1B2A4A;
        font-weight: 600;
    }
</style>

@endsection
