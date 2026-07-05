@extends('layouts.app')
@section('title', 'Edit Sub-Category')
@section('content')

<div class="row justify-content-center">
<div class="col-md-5">

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.account-sub-categories.index') }}" class="text-muted text-decoration-none">
        Sub-Categories
    </a>
    <span class="text-muted">/</span>
    <span>{{ $sub->name }}</span>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-4">Edit Sub-Category</h5>

        <form method="POST" action="{{ route('admin.account-sub-categories.update', $sub) }}">
            @csrf @method('PUT')

            <div class="mb-3">
                <label class="form-label small fw-semibold">Budget Type</label>
                <select name="budget_type"
                        class="form-select @error('budget_type') is-invalid @enderror">
                    @foreach($typeLabels as $value => $label)
                    <option value="{{ $value }}"
                        {{ old('budget_type', $sub->budget_type) === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                    @endforeach
                </select>
                @error('budget_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if($sub->categories()->count())
                <div class="form-text text-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Changing the type won't update the {{ $sub->categories()->count() }} category/categories already using this sub-category.
                </div>
                @endif
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Name</label>
                <input type="text" name="name" value="{{ old('name', $sub->name) }}"
                    class="form-control @error('name') is-invalid @enderror">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $sub->sort_order) }}"
                    class="form-control @error('sort_order') is-invalid @enderror"
                    min="0" max="999" style="width:120px">
                @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4 p-3 rounded-3" style="background:#F8FAFC;border:1px solid #E2E8F0">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="small fw-semibold">Status</div>
                        <div class="form-text">Inactive sub-categories are hidden from category forms.</div>
                    </div>
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" id="is_active"
                               class="form-check-input" style="width:44px;height:22px;cursor:pointer"
                               {{ old('is_active', $sub->is_active) ? 'checked' : '' }}>
                        <label for="is_active" class="form-check-label fw-semibold">
                            {{ old('is_active', $sub->is_active) ? 'Active' : 'Inactive' }}
                        </label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('admin.account-sub-categories.index') }}"
                   class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<div class="mt-3 p-3 rounded-3 small text-muted" style="background:#F8FAFC;border:1px solid #E2E8F0">
    Used by <strong>{{ $sub->categories()->count() }}</strong> {{ Str::plural('category', $sub->categories()->count()) }}.
    Created {{ $sub->created_at->format('d M Y') }}.
</div>

</div>
</div>
@endsection
