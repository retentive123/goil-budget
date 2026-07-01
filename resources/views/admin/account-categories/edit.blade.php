@extends('layouts.app')
@section('title', 'Edit Category')
@section('content')


<div class="row justify-content-center">
<div class="col-md-6">

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.account-categories.index') }}"
       class="text-muted text-decoration-none">Account Categories</a>
    <span class="text-muted">/</span>
    <span>Edit</span>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-4">Edit — {{ $accountCategory->name }}</h5>

        <form method="POST"
              action="{{ route('admin.account-categories.update', $accountCategory) }}">
            @csrf @method('PUT')

            <div class="mb-3">
                <label class="form-label small fw-semibold">Category name</label>
                <input type="text" name="name"
                    value="{{ old('name', $accountCategory->name) }}"
                    class="form-control @error('name') is-invalid @enderror">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Category code</label>
                <input type="text" name="code"
                    value="{{ old('code', $accountCategory->code) }}"
                    class="form-control @error('code') is-invalid @enderror">
                @error('code')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Add before description field --}}
            <div class="mb-3">
                <label class="form-label small fw-semibold">Budget Type</label>
                <select name="budget_type"
                        class="form-select @error('budget_type') is-invalid @enderror">
                    <option value="expense"
                            {{ old('budget_type', $accountCategory->budget_type ?? 'expense') === 'expense' ? 'selected' : '' }}>
                        Expense — costs and expenditures
                    </option>
                    <option value="revenue"
                            {{ old('budget_type', $accountCategory->budget_type ?? 'expense') === 'revenue' ? 'selected' : '' }}>
                        Revenue — income and receipts
                    </option>
                    <option value="both"
                            {{ old('budget_type', $accountCategory->budget_type ?? 'expense') === 'both' ? 'selected' : '' }}>
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
                    class="form-control">{{ old('description', $accountCategory->description) }}</textarea>
            </div>

            <div class="mb-4">
                <div class="form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                        id="is_active"
                        class="form-check-input"
                        {{ old('is_active', $accountCategory->is_active) ? 'checked' : '' }}>
                    <label for="is_active" class="form-check-label small">Active</label>
                </div>
                <div class="form-text">
                    Inactive categories are hidden from budget entry forms.
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('admin.account-categories.index') }}"
                   class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

</div>
</div>
@endsection
