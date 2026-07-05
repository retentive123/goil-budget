@extends('layouts.app')
@section('title', 'Add Account Code')

@push('styles')
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
<style>
.ts-wrapper .ts-control {
    border-color: #E2E8F0;
    border-radius: 6px;
    min-height: 38px;
    padding: 4px 8px;
    font-size: 14px;
}
.ts-wrapper.focus .ts-control {
    border-color: #E65C00;
    box-shadow: 0 0 0 .2rem rgba(230,92,0,.15);
}
.ts-wrapper.is-invalid .ts-control { border-color: #dc3545; }
.ts-dropdown { border-color: #E2E8F0; border-radius: 6px; margin-top: 2px; }
.ts-dropdown .ts-dropdown-content { max-height: 260px; }
.ts-option { padding: 8px 12px; display: flex; align-items: center; gap: 10px; }
.ts-option:hover, .ts-option.active { background: #F8FAFC; }
.cat-option-name { font-weight: 600; font-size: 13px; color: #1B2A4A; flex: 1; }
.cat-type-badge {
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 10px;
    white-space: nowrap;
    flex-shrink: 0;
}
.ts-selected-item { display: flex; align-items: center; gap: 8px; }
</style>
@endpush

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

            {{-- Category — searchable Tom Select --}}
            <div class="mb-3">
                <label class="form-label small fw-semibold">Category</label>
                <select id="categorySelect" name="account_category_id"
                    class="form-select @error('account_category_id') is-invalid @enderror">
                    <option value="">— Select category —</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}"
                                data-type="{{ $cat->budget_type }}"
                            {{ old('account_category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
                @error('account_category_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
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

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
const TYPE_CONFIG = {
    revenue:             { label: 'Revenue',    color: '#065F46', bg: '#D1FAE5' },
    expense:             { label: 'Expense',    color: '#991B1B', bg: '#FEE2E2' },
    assets:              { label: 'Assets',     color: '#5B21B6', bg: '#EDE9FE' },
    liabilities:         { label: 'Liabilities',color: '#7C3AED', bg: '#F3E8FF' },
    capital_expenditure: { label: 'CapEx',      color: '#92400E', bg: '#FEF3C7' },
};

function typeBadge(typeKey) {
    const cfg = TYPE_CONFIG[typeKey] || { label: typeKey, color: '#475569', bg: '#F1F5F9' };
    return `<span class="cat-type-badge"
                  style="background:${cfg.bg};color:${cfg.color}">
                ${cfg.label}
            </span>`;
}

new TomSelect('#categorySelect', {
    placeholder: '— Search or select a category —',
    allowEmptyOption: true,
    maxOptions: null,

    render: {
        option: function(data, escape) {
            const typeKey = data.element?.dataset?.type || '';
            return `<div class="ts-option">
                        <span class="cat-option-name">${escape(data.text)}</span>
                        ${typeKey ? typeBadge(typeKey) : ''}
                    </div>`;
        },
        item: function(data, escape) {
            const typeKey = data.element?.dataset?.type || '';
            return `<div class="ts-selected-item">
                        <span style="font-size:13px;font-weight:600;color:#1B2A4A">
                            ${escape(data.text)}
                        </span>
                        ${typeKey ? typeBadge(typeKey) : ''}
                    </div>`;
        },
    },
});
</script>

@endsection
