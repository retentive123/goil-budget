@extends('layouts.app')
@section('title', 'Add Approval Stage')
@section('content')

<div class="row justify-content-center">
<div class="col-md-6">

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.approval-stages.index') }}"
       class="text-muted text-decoration-none small">← Approval Stages</a>
    <span class="text-muted">/</span>
    <span class="small">Add Stage</span>
</div>

<div class="chart-card">
    <h5 class="fw-bold mb-1">Add Approval Stage</h5>
    <p class="text-muted small mb-4">
        New stages are inserted at the position you specify.
        Existing stages shift down automatically.
    </p>

    <form method="POST" action="{{ route('admin.approval-stages.store') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label small fw-semibold">Stage Name</label>
            <input type="text" name="name" value="{{ old('name') }}"
                   class="form-control @error('name') is-invalid @enderror"
                   placeholder="e.g. Finance Review">
            <div class="form-text">
                A clear, descriptive name shown to all users.
            </div>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label small fw-semibold">Role</label>
            <select name="role_name"
                    class="form-select @error('role_name') is-invalid @enderror">
                <option value="">— Select the role that approves at this stage —</option>
                @foreach($roles as $role)
                <option value="{{ $role->name }}"
                    {{ old('role_name') === $role->name ? 'selected' : '' }}>
                    {{ ucfirst(str_replace('_',' ', $role->name)) }}
                </option>
                @endforeach
            </select>
            <div class="form-text">
                Users with this role will see budgets waiting at this stage.
            </div>
            @error('role_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label small fw-semibold">Position in Chain</label>
            <input type="number" name="order"
                   value="{{ old('order', $nextOrder) }}"
                   class="form-control @error('order') is-invalid @enderror"
                   min="1" style="max-width:100px">
            <div class="form-text">
                1 = first stage after submission. Currently {{ $nextOrder - 1 }} stage(s) exist.
                Inserting here will shift later stages down.
            </div>
            @error('order')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <div class="form-check">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       id="is_active" class="form-check-input"
                       {{ old('is_active', true) ? 'checked' : '' }}>
                <label for="is_active" class="form-check-label small">
                    Active — budgets must pass through this stage
                </label>
            </div>
            <div class="form-text">
                Inactive stages are skipped in the approval chain
                but preserved in historical records.
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-sm"
                    style="background:var(--navy);color:#fff;
                           border-radius:8px;padding:8px 20px">
                Add Stage
            </button>
            <a href="{{ route('admin.approval-stages.index') }}"
               class="btn btn-sm btn-outline-secondary"
               style="border-radius:8px;padding:8px 20px">
                Cancel
            </a>
        </div>
    </form>
</div>

</div>
</div>
@endsection
