@extends('layouts.app')
@section('title', 'Add Sub-Category')
@section('content')

<div class="row justify-content-center">
<div class="col-md-5">

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.account-sub-categories.index') }}" class="text-muted text-decoration-none">
        Sub-Categories
    </a>
    <span class="text-muted">/</span>
    <span>Add</span>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-4">Add Sub-Category</h5>

        <form method="POST" action="{{ route('admin.account-sub-categories.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label small fw-semibold">Budget Type</label>
                <select name="budget_type" id="budgetType"
                        class="form-select @error('budget_type') is-invalid @enderror">
                    <option value="">— Select type —</option>
                    @foreach($typeLabels as $value => $label)
                    <option value="{{ $value }}" {{ old('budget_type') === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                    @endforeach
                </select>
                @error('budget_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Name</label>
                <input type="text" name="name" value="{{ old('name') }}"
                    class="form-control @error('name') is-invalid @enderror"
                    placeholder="e.g. Main Income">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold">
                    Sort Order <span class="text-muted fw-normal">(optional)</span>
                </label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}"
                    class="form-control @error('sort_order') is-invalid @enderror"
                    min="0" max="999" style="width:120px">
                <div class="form-text">Lower numbers appear first within the type.</div>
                @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Create</button>
                <a href="{{ route('admin.account-sub-categories.index') }}"
                   class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

</div>
</div>
@endsection
