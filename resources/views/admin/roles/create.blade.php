@extends('layouts.app')
@section('title', 'Add Role')
@section('content')

<div class="row justify-content-center">
<div class="col-md-8">

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.roles.index') }}"
       class="text-muted text-decoration-none">Roles</a>
    <span class="text-muted">/</span>
    <span>Add Role</span>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-4">Add New Role</h5>

        <form method="POST" action="{{ route('admin.roles.store') }}">
            @csrf

            <div class="mb-4">
                <label class="form-label small fw-semibold">Role name</label>
                <input type="text" name="name" value="{{ old('name') }}"
                    class="form-control @error('name') is-invalid @enderror"
                    placeholder="e.g. budget_analyst">
                <div class="form-text">
                    Lowercase letters, numbers and underscores only.
                    e.g. <code>budget_analyst</code>
                </div>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold">Permissions</label>
                <div class="small text-muted mb-2">
                    Select the permissions this role should have.
                </div>

                <div class="d-flex gap-2 mb-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                            onclick="toggleAll(true)">Select All</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                            onclick="toggleAll(false)">Clear All</button>
                </div>

                {{-- Approval Scope --}}
                <div class="mb-4 p-3" style="background:#F8FAFC;border-radius:10px;
                                            border:1px solid var(--border)">
                    <div style="font-size:13px;font-weight:700;color:var(--navy);margin-bottom:12px">
                        Approval Behaviour
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Budget Scope</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input type="radio" name="scope" value="all"
                                    id="scope_all" class="form-check-input"
                                    {{ old('scope','all') === 'all' ? 'checked' : '' }}>
                                <label for="scope_all" class="form-check-label small">
                                    <strong>All Departments</strong>
                                    <div style="font-size:11px;color:var(--slate)">
                                        Can approve budgets from any department
                                    </div>
                                </label>
                            </div>
                            <div class="form-check ms-4">
                                <input type="radio" name="scope" value="own"
                                    id="scope_own" class="form-check-input"
                                    {{ old('scope') === 'own' ? 'checked' : '' }}>
                                <label for="scope_own" class="form-check-label small">
                                    <strong>Own Department Only</strong>
                                    <div style="font-size:11px;color:var(--slate)">
                                        Can only approve budgets from the user's assigned department
                                    </div>
                                </label>
                            </div>
                        </div>
                        @error('scope')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" name="can_partial_approve" value="1"
                                    id="can_partial_approve" class="form-check-input"
                                    {{ old('can_partial_approve') ? 'checked' : '' }}>
                                <label for="can_partial_approve" class="form-check-label small">
                                    <strong>Can partially approve</strong>
                                    <div style="font-size:11px;color:var(--slate)">
                                        Can approve some line items and reject others
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" name="can_reduce_amounts" value="1"
                                    id="can_reduce_amounts" class="form-check-input"
                                    {{ old('can_reduce_amounts') ? 'checked' : '' }}>
                                <label for="can_reduce_amounts" class="form-check-label small">
                                    <strong>Can reduce amounts</strong>
                                    <div style="font-size:11px;color:var(--slate)">
                                        Can approve a line item at a lower amount than requested
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label small fw-semibold">Description</label>
                        <textarea name="description" rows="2"
                                class="form-control form-control-sm"
                                placeholder="Brief description of this role's responsibilities…">{{ old('description') }}</textarea>
                    </div>
                </div>

                @foreach($permissions as $group => $groupPerms)
                <div class="card mb-2 border-0 bg-light">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-semibold text-uppercase text-muted">
                                {{ $group }}
                            </span>
                            <button type="button"
                                    class="btn btn-link btn-sm p-0 text-muted"
                                    onclick="toggleGroup('{{ $group }}')">
                                Toggle group
                            </button>
                        </div>
                        <div class="row g-2">
                            @foreach($groupPerms as $perm)
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="checkbox"
                                        name="permissions[]"
                                        value="{{ $perm->name }}"
                                        id="perm_{{ $perm->id }}"
                                        class="form-check-input perm-check group-{{ $group }}"
                                        {{ in_array($perm->name, old('permissions', [])) ? 'checked' : '' }}>
                                    <label for="perm_{{ $perm->id }}"
                                           class="form-check-label small">
                                        {{ $perm->name }}
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach

                @error('permissions')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Create Role</button>
                <a href="{{ route('admin.roles.index') }}"
                   class="btn btn-outline-secondary">Cancel</a>
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
</script>
@endsection
