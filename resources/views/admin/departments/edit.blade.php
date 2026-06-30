@extends('layouts.app')
@section('title', 'Edit Department')
@section('content')

<div class="row justify-content-center">
<div class="col-md-6">

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.departments.index') }}" class="text-muted text-decoration-none">Departments</a>
    <span class="text-muted">/</span>
    <span>Edit</span>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-4">Edit — {{ $department->name }}</h5>

        <form method="POST" action="{{ route('admin.departments.update', $department) }}">
            @csrf @method('PUT')

            <div class="mb-3">
                <label class="form-label small fw-semibold">Department name</label>
                <input type="text" name="name" value="{{ old('name', $department->name) }}"
                    class="form-control @error('name') is-invalid @enderror">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Department code</label>
                <input type="text" name="code" value="{{ old('code', $department->code) }}"
                    class="form-control @error('code') is-invalid @enderror">
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Budget Type</label>
                <select name="budget_type"
                        class="form-select @error('budget_type') is-invalid @enderror">
                    <option value="expense"
                            {{ old('budget_type', $department->budget_type ?? 'expense') === 'expense' ? 'selected' : '' }}>
                        Expense — costs and expenditures
                    </option>
                    <option value="revenue"
                            {{ old('budget_type', $department->budget_type ?? 'expense') === 'revenue' ? 'selected' : '' }}>
                        Revenue — income and receipts
                    </option>
                    <option value="both"
                            {{ old('budget_type', $department->budget_type ?? 'expense') === 'both' ? 'selected' : '' }}>
                        Both — mixed revenue and expense
                    </option>
                </select>
                @error('budget_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Description</label>
                <textarea name="description" rows="3"
                    class="form-control">{{ old('description', $department->description) }}</textarea>
            </div>

            <div class="mb-4">
                <div class="form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" id="is_active"
                        class="form-check-input"
                        {{ old('is_active', $department->is_active) ? 'checked' : '' }}>
                    <label for="is_active" class="form-check-label small">Active</label>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('admin.departments.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

</div>
</div>
@endsection
