@extends('layouts.app')
@section('title', 'Edit Account Code')
@section('content')

<div class="row justify-content-center">
<div class="col-md-6">

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.account-codes.index') }}" class="text-muted text-decoration-none">Account Codes</a>
    <span class="text-muted">/</span>
    <span>Edit</span>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-4">Edit — {{ $accountCode->code }}</h5>

        <form method="POST" action="{{ route('admin.account-codes.update', $accountCode) }}">
            @csrf @method('PUT')

            <div class="mb-3">
                <label class="form-label small fw-semibold">Category</label>
                <select name="account_category_id"
                    class="form-select @error('account_category_id') is-invalid @enderror">
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}"
                            {{ old('account_category_id', $accountCode->account_category_id) == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
                @error('account_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Code</label>
                <input type="text" name="code" value="{{ old('code', $accountCode->code) }}"
                    class="form-control @error('code') is-invalid @enderror">
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Name</label>
                <input type="text" name="name" value="{{ old('name', $accountCode->name) }}"
                    class="form-control @error('name') is-invalid @enderror">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Description</label>
                <textarea name="description" rows="2"
                    class="form-control">{{ old('description', $accountCode->description) }}</textarea>
            </div>

            <div class="mb-4">
                <div class="form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" id="is_active"
                        class="form-check-input"
                        {{ old('is_active', $accountCode->is_active) ? 'checked' : '' }}>
                    <label for="is_active" class="form-check-label small">Active</label>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('admin.account-codes.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

</div>
</div>
@endsection
