@extends('layouts.app')
@section('title', 'Add Account Code')
@section('content')

<div class="row justify-content-center">
<div class="col-md-6">

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.account-codes.index') }}" class="text-muted text-decoration-none">Account Codes</a>
    <span class="text-muted">/</span>
    <span>Add</span>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-4">Add Account Code</h5>

        <form method="POST" action="{{ route('admin.account-codes.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label small fw-semibold">Category</label>
                <select name="account_category_id"
                    class="form-select @error('account_category_id') is-invalid @enderror">
                    <option value="">— Select category —</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}"
                            {{ old('account_category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
                @error('account_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Code</label>
                <input type="text" name="code" value="{{ old('code') }}"
                    class="form-control @error('code') is-invalid @enderror"
                    placeholder="e.g. 4001">
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Name</label>
                <input type="text" name="name" value="{{ old('name') }}"
                    class="form-control @error('name') is-invalid @enderror"
                    placeholder="e.g. Office Supplies">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold">Description</label>
                <textarea name="description" rows="2"
                    class="form-control">{{ old('description') }}</textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Create</button>
                <a href="{{ route('admin.account-codes.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

</div>
</div>
@endsection
