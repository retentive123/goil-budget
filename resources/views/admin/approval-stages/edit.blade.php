@extends('layouts.app')
@section('title', 'Edit Approval Stage')
@section('content')

<div class="row justify-content-center">
<div class="col-md-6">

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.approval-stages.index') }}"
       class="text-muted text-decoration-none small">← Approval Stages</a>
    <span class="text-muted">/</span>
    <span class="small">Edit — {{ $approvalStage->name }}</span>
</div>

<div class="chart-card">
    <h5 class="fw-bold mb-1">Edit Approval Stage</h5>
    <p class="text-muted small mb-4">
        To change the position, use the ↑ ↓ buttons or drag on the main list.
    </p>

    <form method="POST"
          action="{{ route('admin.approval-stages.update', $approvalStage) }}">
        @csrf @method('PUT')

        <div class="mb-3">
            <label class="form-label small fw-semibold">Stage Name</label>
            <input type="text" name="name"
                   value="{{ old('name', $approvalStage->name) }}"
                   class="form-control @error('name') is-invalid @enderror">
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label small fw-semibold">Role</label>
            <select name="role_name"
                    class="form-select @error('role_name') is-invalid @enderror">
                @foreach($roles as $role)
                <option value="{{ $role->name }}"
                    {{ old('role_name', $approvalStage->role_name) === $role->name
                        ? 'selected' : '' }}>
                    {{ ucfirst(str_replace('_',' ', $role->name)) }}
                </option>
                @endforeach
            </select>
            @error('role_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label small fw-semibold">Current Position</label>
            <div style="background:#F8FAFC;border-radius:8px;padding:10px 14px;
                        font-size:13px;color:var(--navy);font-weight:600">
                Stage {{ $approvalStage->order }} of {{ $maxOrder }}
            </div>
            <div class="form-text">
                Use the ↑ ↓ arrows on the list page to change position.
            </div>
        </div>

        <div class="mb-4">
            <div class="form-check">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       id="is_active" class="form-check-input"
                       {{ old('is_active', $approvalStage->is_active) ? 'checked' : '' }}>
                <label for="is_active" class="form-check-label small">
                    Active — budgets must pass through this stage
                </label>
            </div>
            <div class="form-text">
                Deactivating a stage skips it for future submissions.
                Historical records are preserved.
            </div>
        </div>

        {{-- Show impact warning if changing role --}}
        <div style="background:#FEF3C7;border-radius:8px;padding:12px 14px;
                    font-size:12px;color:#92400E;margin-bottom:20px">
            <strong>Note:</strong> Changing the role affects which users
            see pending approvals at this stage. Users already assigned
            a decision here are not affected retroactively.
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-sm"
                    style="background:var(--navy);color:#fff;
                           border-radius:8px;padding:8px 20px">
                Save Changes
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
