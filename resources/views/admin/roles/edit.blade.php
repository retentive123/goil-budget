@extends('layouts.app')
@section('title', 'Edit Role')
@section('content')

<div class="row justify-content-center">
    <div class="col-lg-9">

        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb" class="mb-4" >
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('admin.roles.index') }}" class="text-decoration-none">
                        <i class="bi bi-shield-lock"></i> Roles
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page" >
                    Edit — {{ $role->name }}
                </li>
            </ol>
        </nav>

        {{-- System Role Alert --}}
        @php
            $systemRoles = [
                'super_admin','department_user','department_head',
                'finance_reviewer','gceo','board','bdu_admin'
            ];
            $isSystem = in_array($role->name, $systemRoles);
        @endphp

        @if($isSystem)
        <div class="alert d-flex align-items-start gap-3 mb-4"
             style="background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 12px; padding: 14px 18px;">
            <i class="bi bi-info-circle" style="color: #2563EB; font-size: 20px;"></i>
            <div>
                <div style="font-weight: 600; color: #1E40AF; font-size: 13px;">System Role</div>
                <div style="font-size: 13px; color: #1E3A5F;">
                    The role name cannot be changed, but you can adjust its permissions and approval behaviour.
                </div>
            </div>
        </div>
        @endif

        {{-- Main Card --}}
        <div class="card border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            {{-- Card Header --}}
            <div class="card-header border-0 px-4 py-3"
                 style="background: #E65C00; color: #fff;">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-circle"
                         style="width: 44px; height: 44px; background: rgba(255,255,255,0.12); font-size: 20px;">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0" style="color: #fff;">Edit Role</h5>
                        <p class="mb-0" style="font-size: 13px; color: rgba(255,255,255,0.7);">
                            {{ $role->name }} — Manage permissions and approval rules
                        </p>
                    </div>
                    <div class="ms-auto">
                        <span class="badge px-3 py-2" style="background: rgba(255,255,255,0.15); color: #fff; font-size: 12px; border-radius: 20px;">
                            <i class="bi bi-people"></i> {{ $role->users()->count() }} users
                        </span>
                    </div>
                </div>
            </div>

            {{-- Card Body --}}
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.roles.update', $role) }}">
                    @csrf @method('PUT')

                    {{-- Role Name --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="color: #1B2A4A; font-size: 13px;">
                            <i class="bi bi-tag" style="color: #E65C00;"></i> Role Name
                        </label>
                        <div class="input-group" style="border-radius: 10px; overflow: hidden;">
                            <span class="input-group-text" style="background: #F8FAFC; border-color: #E2E8F0; color: #64748B;">
                                <i class="bi bi-shield"></i>
                            </span>
                            <input type="text" name="name"
                                   value="{{ old('name', $role->name) }}"
                                   class="form-control @error('name') is-invalid @enderror"
                                   style="border-color: #E2E8F0; padding: 10px 14px;"
                                   {{ $isSystem ? 'readonly' : '' }}>
                        </div>
                        @error('name')
                            <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                        @enderror
                        @if($isSystem)
                            <div style="font-size: 11px; color: #64748B; margin-top: 4px;">
                                <i class="bi bi-lock"></i> System roles cannot be renamed
                            </div>
                        @endif
                    </div>

                    {{-- Approval Behaviour Section --}}
                    <div class="mb-4" style="background: #F8FAFC; border-radius: 12px; border: 1px solid #E2E8F0; overflow: hidden;">
                        <div class="px-4 py-3" style="background: #E65C00; color: #fff;">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-check2-circle" style="font-size: 18px;"></i>
                                <span style="font-weight: 600; font-size: 13px;">Approval Behaviour</span>
                            </div>
                        </div>
                        <div class="p-4">

                            {{-- Budget Scope --}}
                            <div class="mb-4">
                                <label class="form-label fw-semibold" style="color: #1B2A4A; font-size: 13px;">
                                    <i class="bi bi-globe2" style="color: #E65C00;"></i> Budget Scope
                                </label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-check p-3 rounded-3"
                                             style="border: 2px solid {{ old('scope', $role->scope) === 'all' ? '#E65C00' : '#E2E8F0' }};
                                                    background: {{ old('scope', $role->scope) === 'all' ? 'rgba(230, 92, 0, 0.05)' : '#fff' }};
                                                    border-radius: 10px; transition: all 0.2s;">
                                            <input type="radio" name="scope" value="all"
                                                   id="scope_all" class="form-check-input"
                                                   {{ old('scope', $role->scope) === 'all' ? 'checked' : '' }}>
                                            <label for="scope_all" class="form-check-label" style="cursor: pointer;">
                                                <div style="font-weight: 600; color: #1B2A4A;">All Departments</div>
                                                <div style="font-size: 12px; color: #64748B;">
                                                    <i class="bi bi-globe"></i> Can approve budgets from any department
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check p-3 rounded-3"
                                             style="border: 2px solid {{ old('scope', $role->scope) === 'own' ? '#E65C00' : '#E2E8F0' }};
                                                    background: {{ old('scope', $role->scope) === 'own' ? 'rgba(230, 92, 0, 0.05)' : '#fff' }};
                                                    border-radius: 10px; transition: all 0.2s;">
                                            <input type="radio" name="scope" value="own"
                                                   id="scope_own" class="form-check-input"
                                                   {{ old('scope', $role->scope) === 'own' ? 'checked' : '' }}>
                                            <label for="scope_own" class="form-check-label" style="cursor: pointer;">
                                                <div style="font-weight: 600; color: #1B2A4A;">Own Department Only</div>
                                                <div style="font-size: 12px; color: #64748B;">
                                                    <i class="bi bi-building"></i> Only approves budgets from user's department
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Approval Options --}}
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check p-3 rounded-3"
                                         style="border: 1px solid {{ old('can_partial_approve', $role->can_partial_approve) ? '#10B981' : '#E2E8F0' }};
                                                background: {{ old('can_partial_approve', $role->can_partial_approve) ? 'rgba(16, 185, 129, 0.05)' : '#fff' }};
                                                border-radius: 10px;">
                                        <input type="checkbox" name="can_partial_approve" value="1"
                                               id="can_partial_approve" class="form-check-input"
                                               {{ old('can_partial_approve', $role->can_partial_approve) ? 'checked' : '' }}
                                               style="border-color: #10B981;">
                                        <label for="can_partial_approve" class="form-check-label" style="cursor: pointer;">
                                            <div style="font-weight: 600; color: #1B2A4A;">
                                                <i class="bi bi-check2-square" style="color: #10B981;"></i> Partial Approval
                                            </div>
                                            <div style="font-size: 12px; color: #64748B;">
                                                Can approve some line items and reject others
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check p-3 rounded-3"
                                         style="border: 1px solid {{ old('can_reduce_amounts', $role->can_reduce_amounts) ? '#F59E0B' : '#E2E8F0' }};
                                                background: {{ old('can_reduce_amounts', $role->can_reduce_amounts) ? 'rgba(245, 158, 11, 0.05)' : '#fff' }};
                                                border-radius: 10px;">
                                        <input type="checkbox" name="can_reduce_amounts" value="1"
                                               id="can_reduce_amounts" class="form-check-input"
                                               {{ old('can_reduce_amounts', $role->can_reduce_amounts) ? 'checked' : '' }}
                                               style="border-color: #F59E0B;">
                                        <label for="can_reduce_amounts" class="form-check-label" style="cursor: pointer;">
                                            <div style="font-weight: 600; color: #1B2A4A;">
                                                <i class="bi bi-dash-circle" style="color: #F59E0B;"></i> Reduce Amounts
                                            </div>
                                            <div style="font-size: 12px; color: #64748B;">
                                                Can approve a line item at a lower amount than requested
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- Description --}}
                            <div class="mt-3">
                                <label class="form-label fw-semibold" style="color: #1B2A4A; font-size: 13px;">
                                    <i class="bi bi-card-text" style="color: #E65C00;"></i> Description
                                </label>
                                <textarea name="description" rows="2"
                                        class="form-control @error('description') is-invalid @enderror"
                                        style="border-radius: 10px; border-color: #E2E8F0; padding: 10px 14px; resize: vertical;"
                                        placeholder="Describe the purpose of this role…">{{ old('description', $role->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Permissions Section --}}
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="form-label fw-semibold mb-0" style="color: #1B2A4A; font-size: 13px;">
                                <i class="bi bi-key" style="color: #E65C00;"></i> Permissions
                            </label>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm px-3 py-1"
                                        style="background: #E65C00; color: #fff; border-radius: 6px; border: none; font-size: 12px;"
                                        onclick="toggleAll(true)">
                                    <i class="bi bi-check-all"></i> Select All
                                </button>
                                <button type="button" class="btn btn-sm px-3 py-1"
                                        style="background: #F1F5F9; color: #475569; border-radius: 6px; border: 1px solid #E2E8F0; font-size: 12px;"
                                        onclick="toggleAll(false)">
                                    <i class="bi bi-x-circle"></i> Clear All
                                </button>
                            </div>
                        </div>

                        @foreach($permissions as $group => $groupPerms)
                        <div class="card mb-2 border-0" style="background: #F8FAFC; border-radius: 10px; overflow: hidden;">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small fw-semibold text-uppercase" style="color: #1B2A4A; letter-spacing: 0.5px;">
                                        <i class="bi bi-tag" style="color: #E65C00; font-size: 12px;"></i>
                                        {{ $group }}
                                    </span>
                                    <button type="button"
                                            class="btn btn-link btn-sm p-0 text-decoration-none"
                                            style="color: #64748B; font-size: 12px;"
                                            onclick="toggleGroup('{{ Str::slug($group) }}')">
                                        <i class="bi bi-arrow-repeat"></i> Toggle group
                                    </button>
                                </div>
                                <div class="row g-2">
                                    @foreach($groupPerms as $perm)
                                    <div class="col-lg-4 col-md-6">
                                        <div class="form-check p-2 rounded-2"
                                             style="transition: background 0.2s; {{ in_array($perm->name, old('permissions', $assignedPermissions)) ? 'background: rgba(230, 92, 0, 0.05);' : '' }}">
                                            <input type="checkbox"
                                                   name="permissions[]"
                                                   value="{{ $perm->name }}"
                                                   id="perm_{{ $perm->id }}"
                                                   class="form-check-input perm-check group-{{ Str::slug($group) }}"
                                                   {{ in_array($perm->name, old('permissions', $assignedPermissions)) ? 'checked' : '' }}
                                                   style="border-color: #E65C00;">
                                            <label for="perm_{{ $perm->id }}"
                                                   class="form-check-label small"
                                                   style="color: #1B2A4A; cursor: pointer;">
                                                {{ $perm->name }}
                                            </label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Action Buttons --}}
                    <div class="d-flex gap-2 pt-3 border-top">
                        <button type="submit" class="btn px-4 py-2 fw-semibold"
                                style="background: #E65C00; color: #fff; border-radius: 10px; border: none; transition: all 0.3s ease;">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                        <a href="{{ route('admin.roles.index') }}"
                           class="btn px-4 py-2 fw-semibold"
                           style="background: #F1F5F9; color: #475569; border-radius: 10px; border: 1px solid #E2E8F0; transition: all 0.3s ease;">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleAll(state) {
    document.querySelectorAll('.perm-check').forEach(cb => cb.checked = state);
}

function toggleGroup(group) {
    const boxes = document.querySelectorAll('.group-' + group);
    const allChecked = Array.from(boxes).every(cb => cb.checked);
    boxes.forEach(cb => cb.checked = !allChecked);
}

// Auto-highlight selected permissions
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.perm-check').forEach(cb => {
        cb.addEventListener('change', function() {
            const parent = this.closest('.form-check');
            if (this.checked) {
                parent.style.background = 'rgba(230, 92, 0, 0.05)';
            } else {
                parent.style.background = 'transparent';
            }
        });
    });
});
</script>

<style>
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

    .form-control:focus {
        border-color: #E65C00;
        box-shadow: 0 0 0 0.2rem rgba(230, 92, 0, 0.15);
    }
</style>
@endsection
