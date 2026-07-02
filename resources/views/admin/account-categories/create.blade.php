@extends('layouts.app')
@section('title', 'Add Account Category')
@section('content')

<div class="row justify-content-center">
<div class="col-md-6">

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.account-categories.index') }}"
       class="text-muted text-decoration-none">Account Categories</a>
    <span class="text-muted">/</span>
    <span>Add</span>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-4">Add Account Category</h5>

        <form method="POST" action="{{ route('admin.account-categories.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label small fw-semibold">Category name</label>
                <input type="text" name="name" value="{{ old('name') }}"
                    class="form-control @error('name') is-invalid @enderror"
                    placeholder="e.g. Operating Expenses">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Category code</label>
                <input type="text" name="code" value="{{ old('code') }}"
                    class="form-control @error('code') is-invalid @enderror"
                    placeholder="e.g. OPEX">
                <div class="form-text">Short uppercase code. Used in reports.</div>
                @error('code')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Add before description field --}}
            <div class="mb-3">
                <label class="form-label small fw-semibold">Budget Type</label>
                <select name="budget_type"
                        class="form-select @error('budget_type') is-invalid @enderror">
                    <optgroup label="Income Statement">
                        <option value="revenue" {{ old('budget_type')==='revenue' ?'selected':'' }}>
                            📈 Revenue — income and receipts
                        </option>
                        <option value="expense" {{ old('budget_type','expense')==='expense' ?'selected':'' }}>
                            📉 Expense — costs and expenditures
                        </option>
                        <option value="both" {{ old('budget_type')==='both' ?'selected':'' }}>
                            📊 Both — mixed revenue and expense
                        </option>
                    </optgroup>
                    <optgroup label="Balance Sheet &amp; CapEx">
                        <option value="assets" {{ old('budget_type')==='assets' ?'selected':'' }}>
                            🏦 Assets — resources owned or controlled
                        </option>
                        <option value="liabilities" {{ old('budget_type')==='liabilities' ?'selected':'' }}>
                            📋 Liabilities — obligations and payables
                        </option>
                        <option value="capital_expenditure" {{ old('budget_type')==='capital_expenditure' ?'selected':'' }}>
                            🏗️ Capital Expenditure — long-term investment
                        </option>
                    </optgroup>
                </select>
                @error('budget_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold">
                    Description <span class="text-muted">(optional)</span>
                </label>
                <textarea name="description" rows="3"
                    class="form-control @error('description') is-invalid @enderror"
                    placeholder="Brief description of what this category covers…">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Create Category</button>
                <a href="{{ route('admin.account-categories.index') }}"
                   class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

</div>
</div>
@endsection
