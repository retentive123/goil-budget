@extends('layouts.app')
@section('title', 'Edit Role — ' . $role->name)
@section('content')

@php
    $systemRoles = ['super_admin','department_user','department_head',
                    'finance_reviewer','gceo','board','bdu_admin'];
    $isSystem    = in_array($role->name, $systemRoles);
    $totalPerms  = $permissions->flatten()->count();
    $assignedCount = count(old('permissions', $assignedPermissions));
@endphp

<div class="row justify-content-center">
<div class="col-xl-9 col-lg-10">

    {{-- Breadcrumb --}}
    <nav class="mb-4">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
                <a href="{{ route('admin.roles.index') }}" class="text-decoration-none">Roles</a>
            </li>
            <li class="breadcrumb-item active">{{ $role->name }}</li>
        </ol>
    </nav>

    @if($isSystem)
    <div class="mb-4 px-4 py-3 rounded-3"
         style="background:#EFF6FF;border:1px solid #BFDBFE;font-size:13px;color:#1E40AF">
        <strong>System role</strong> — the name is locked. You may adjust permissions and approval settings.
    </div>
    @endif

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2 mb-4">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">

        {{-- Header --}}
        <div class="px-5 py-4 d-flex align-items-center justify-content-between"
             style="background:var(--navy);color:#fff">
            <div>
                <div style="font-size:18px;font-weight:700;letter-spacing:-.2px">
                    {{ ucfirst(str_replace('_',' ',$role->name)) }}
                </div>
                <div style="font-size:13px;color:rgba(255,255,255,.6);margin-top:2px">
                    Edit permissions and approval behaviour
                </div>
            </div>
            <div class="text-end">
                <div style="font-size:22px;font-weight:700" id="hdr-count">{{ $assignedCount }}</div>
                <div style="font-size:11px;color:rgba(255,255,255,.55)">of {{ $totalPerms }} permissions</div>
            </div>
        </div>

        <div class="card-body px-5 py-4">
            <form method="POST" action="{{ route('admin.roles.update', $role) }}">
                @csrf @method('PUT')

                {{-- ── Role Name ── --}}
                <div class="mb-4">
                    <label class="form-label fw-semibold" style="font-size:13px;color:var(--navy)">
                        Role Name
                    </label>
                    <input type="text" name="name"
                           value="{{ old('name', $role->name) }}"
                           class="form-control @error('name') is-invalid @enderror"
                           style="max-width:340px"
                           {{ $isSystem ? 'readonly' : '' }}
                           placeholder="e.g. budget_officer">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @if($isSystem)
                    <div style="font-size:11px;color:#94A3B8;margin-top:4px">System roles cannot be renamed.</div>
                    @else
                    <div style="font-size:11px;color:#94A3B8;margin-top:4px">Lowercase letters, numbers and underscores only.</div>
                    @endif
                </div>

                {{-- ── Description ── --}}
                <div class="mb-4">
                    <label class="form-label fw-semibold" style="font-size:13px;color:var(--navy)">
                        Description
                        <span class="fw-normal text-muted">(optional)</span>
                    </label>
                    <textarea name="description" rows="2"
                              class="form-control @error('description') is-invalid @enderror"
                              style="resize:vertical"
                              placeholder="Describe the purpose of this role…">{{ old('description', $role->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- ── Approval Behaviour ── --}}
                <div class="mb-4 pt-3 border-top">
                    <div class="fw-semibold mb-3" style="font-size:13px;color:var(--navy)">
                        Approval Behaviour
                    </div>

                    {{-- Scope --}}
                    <div class="mb-3">
                        <div style="font-size:12px;color:#64748B;font-weight:600;
                                    text-transform:uppercase;letter-spacing:.4px;margin-bottom:10px">
                            Budget Scope
                        </div>
                        <div class="d-flex gap-3">
                            @foreach(['all' => ['label' => 'All Departments', 'desc' => 'Can action budgets from any department'],
                                      'own' => ['label' => 'Own Department Only', 'desc' => 'Limited to their own department']] as $val => $opt)
                            @php $scopeVal = old('scope', $role->scope); @endphp
                            <label class="scope-card flex-fill {{ $scopeVal === $val ? 'scope-on' : '' }}"
                                   for="scope_{{ $val }}">
                                <input type="radio" name="scope" value="{{ $val }}"
                                       id="scope_{{ $val }}"
                                       {{ $scopeVal === $val ? 'checked' : '' }} hidden>
                                <div class="fw-semibold" style="font-size:13px">{{ $opt['label'] }}</div>
                                <div style="font-size:11px;color:#64748B;margin-top:2px">{{ $opt['desc'] }}</div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Toggles --}}
                    <div class="row g-3">
                        @foreach([
                            'can_partial_approve' => ['label' => 'Partial Approval',
                                'desc' => 'Can approve individual line items while rejecting others'],
                            'can_reduce_amounts'  => ['label' => 'Reduce Amounts',
                                'desc' => 'Can approve a line item at a lower amount than requested'],
                        ] as $field => $meta)
                        <div class="col-md-6">
                            <div class="p-3 rounded-3" style="background:#F8FAFC;border:1px solid #E2E8F0">
                                <div class="d-flex align-items-start justify-content-between gap-3">
                                    <div>
                                        <div class="fw-semibold" style="font-size:13px;color:var(--navy)">
                                            {{ $meta['label'] }}
                                        </div>
                                        <div style="font-size:11px;color:#64748B;margin-top:2px">
                                            {{ $meta['desc'] }}
                                        </div>
                                    </div>
                                    <div class="form-check form-switch" style="flex-shrink:0;padding-top:2px">
                                        <input class="form-check-input" type="checkbox"
                                               role="switch" id="{{ $field }}"
                                               name="{{ $field }}" value="1"
                                               {{ old($field, $role->$field) ? 'checked' : '' }}
                                               style="width:2.5em;height:1.3em;cursor:pointer">
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- ── Permissions ── --}}
                <div class="mb-4 pt-3 border-top">
                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                        <div class="fw-semibold" style="font-size:13px;color:var(--navy)">
                            Permissions
                            <span id="perm-summary" class="ms-2 fw-normal"
                                  style="font-size:12px;color:#64748B"></span>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="text" id="permSearch"
                                   class="form-control form-control-sm"
                                   placeholder="Search permissions…"
                                   style="width:180px;font-size:12px"
                                   oninput="filterPerms(this.value)">
                            <button type="button" class="btn btn-sm fw-semibold"
                                    style="background:var(--navy);color:#fff;font-size:12px;padding:5px 14px;border-radius:6px"
                                    onclick="toggleAll(true)">
                                Select all
                            </button>
                            <button type="button" class="btn btn-sm fw-semibold"
                                    style="background:#F1F5F9;color:#475569;font-size:12px;
                                           padding:5px 14px;border-radius:6px;border:1px solid #E2E8F0"
                                    onclick="toggleAll(false)">
                                Clear all
                            </button>
                        </div>
                    </div>

                    <div id="permGroups">
                    @foreach($permissions as $group => $groupPerms)
                    <div class="perm-group mb-4" data-group="{{ Str::slug($group) }}">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span style="font-size:11px;font-weight:700;text-transform:uppercase;
                                         letter-spacing:.6px;color:#94A3B8">
                                {{ $group }}
                            </span>
                            <div class="d-flex align-items-center gap-3">
                                <span class="group-count" id="gc-{{ Str::slug($group) }}"
                                      style="font-size:11px;color:#64748B"></span>
                                <button type="button"
                                        style="font-size:11px;color:var(--navy);background:none;
                                               border:none;padding:0;cursor:pointer;text-decoration:underline"
                                        onclick="toggleGroup('{{ Str::slug($group) }}')">
                                    Toggle group
                                </button>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($groupPerms as $perm)
                            @php $on = in_array($perm->name, old('permissions', $assignedPermissions)); @endphp
                            <label class="perm-pill {{ $on ? 'perm-on' : '' }}"
                                   data-group="{{ Str::slug($group) }}"
                                   data-name="{{ $perm->name }}">
                                <input type="checkbox" name="permissions[]"
                                       value="{{ $perm->name }}"
                                       class="perm-check group-{{ Str::slug($group) }}"
                                       {{ $on ? 'checked' : '' }}
                                       hidden>
                                {{ $perm->name }}
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                    </div>

                    <div id="noResults" style="display:none;font-size:13px;color:#94A3B8;padding:12px 0">
                        No permissions match your search.
                    </div>
                </div>

                {{-- ── Actions ── --}}
                <div class="d-flex gap-2 pt-3 border-top">
                    <button type="submit" class="btn px-4 py-2 fw-semibold"
                            style="background:var(--navy);color:#fff;border-radius:8px;border:none">
                        Save Changes
                    </button>
                    <a href="{{ route('admin.roles.index') }}"
                       class="btn px-4 py-2 fw-semibold"
                       style="background:#F1F5F9;color:#475569;border-radius:8px;border:1px solid #E2E8F0">
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>

</div>
</div>

<style>
/* ── Form controls ── */
.form-control, .form-select {
    border-color: #E2E8F0;
    border-radius: 8px;
}
.form-control:focus, .form-select:focus {
    border-color: var(--navy);
    box-shadow: 0 0 0 .2rem rgba(27,42,74,.12);
}
.form-check-input:checked {
    background-color: var(--navy);
    border-color: var(--navy);
}
.form-check-input:focus {
    box-shadow: 0 0 0 .2rem rgba(27,42,74,.12);
}

/* ── Scope cards ── */
.scope-card {
    cursor: pointer;
    padding: 14px 16px;
    border-radius: 10px;
    border: 2px solid #E2E8F0;
    background: #fff;
    transition: border-color .15s, background .15s;
}
.scope-card:hover  { border-color: var(--navy); }
.scope-card.scope-on {
    border-color: var(--navy);
    background: rgba(27,42,74,.04);
}

/* ── Permission pills ── */
.perm-pill {
    cursor: pointer;
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    border: 1.5px solid #E2E8F0;
    color: #64748B;
    background: #F8FAFC;
    transition: border-color .12s, background .12s, color .12s;
    user-select: none;
    white-space: nowrap;
}
.perm-pill:hover:not(.perm-on) {
    border-color: var(--navy);
    color: var(--navy);
}
.perm-pill.perm-on {
    background: var(--navy);
    color: #fff;
    border-color: var(--navy);
}
.perm-pill.perm-hidden { display: none !important; }

/* ── Breadcrumb ── */
.breadcrumb { background: transparent; padding: 0; }
.breadcrumb-item a { color: #64748B; text-decoration: none; }
.breadcrumb-item a:hover { color: var(--navy); }
.breadcrumb-item.active { color: var(--navy); font-weight: 600; }
</style>

<script>
// ── Pill toggle ──
document.querySelectorAll('.perm-pill').forEach(pill => {
    pill.addEventListener('click', function () {
        const cb = this.querySelector('input[type=checkbox]');
        cb.checked = !cb.checked;
        this.classList.toggle('perm-on', cb.checked);
        refreshCounts();
    });
});

// ── Select / clear all ──
function toggleAll(state) {
    document.querySelectorAll('.perm-pill:not(.perm-hidden)').forEach(pill => {
        pill.querySelector('input').checked = state;
        pill.classList.toggle('perm-on', state);
    });
    refreshCounts();
}

// ── Toggle group ──
function toggleGroup(group) {
    const pills  = document.querySelectorAll(`.perm-pill[data-group="${group}"]:not(.perm-hidden)`);
    const allOn  = Array.from(pills).every(p => p.classList.contains('perm-on'));
    pills.forEach(p => {
        p.querySelector('input').checked = !allOn;
        p.classList.toggle('perm-on', !allOn);
    });
    refreshCounts();
}

// ── Search ──
function filterPerms(q) {
    q = q.toLowerCase().trim();
    let anyVisible = false;

    document.querySelectorAll('.perm-group').forEach(group => {
        let groupHasMatch = false;
        group.querySelectorAll('.perm-pill').forEach(pill => {
            const match = !q || pill.dataset.name.includes(q);
            pill.classList.toggle('perm-hidden', !match);
            if (match) groupHasMatch = true;
        });
        group.style.display = groupHasMatch ? '' : 'none';
        if (groupHasMatch) anyVisible = true;
    });

    document.getElementById('noResults').style.display = anyVisible ? 'none' : '';
    refreshCounts();
}

// ── Scope card highlight on radio change ──
document.querySelectorAll('input[name="scope"]').forEach(radio => {
    radio.addEventListener('change', function () {
        document.querySelectorAll('.scope-card').forEach(c => c.classList.remove('scope-on'));
        this.closest('.scope-card').classList.add('scope-on');
    });
});

// ── Counts ──
function refreshCounts() {
    // Per-group counts
    document.querySelectorAll('.perm-group').forEach(group => {
        const g     = group.dataset.group;
        const pills = group.querySelectorAll(`.perm-pill:not(.perm-hidden)`);
        const on    = Array.from(pills).filter(p => p.classList.contains('perm-on')).length;
        const el    = document.getElementById('gc-' + g);
        if (el) el.textContent = pills.length ? `${on} / ${pills.length}` : '';
    });

    // Header total
    const total   = document.querySelectorAll('.perm-check').length;
    const checked = document.querySelectorAll('.perm-check:checked').length;
    const hdr     = document.getElementById('hdr-count');
    const summary = document.getElementById('perm-summary');
    if (hdr)     hdr.textContent = checked;
    if (summary) summary.textContent = `${checked} of ${total} selected`;
}

document.addEventListener('DOMContentLoaded', refreshCounts);
</script>
@endsection
