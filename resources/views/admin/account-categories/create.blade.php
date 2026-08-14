@extends('layouts.app')
@section('title', 'Add Account Category')
@php
    $calcMode      = \App\Models\SystemSetting::get('line_item_calc_mode', 'none');
    $adminSetsRate = \App\Models\SystemSetting::get('admin_sets_rate', false);
    $adminSetsFreq = \App\Models\SystemSetting::get('admin_sets_freq', false);
    $showRateField = $calcMode !== 'none' && $adminSetsRate;
    $showFreqField = $calcMode === 'qty_rate_freq' && $adminSetsFreq;
@endphp
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

            <div class="mb-3">
                <label class="form-label small fw-semibold">Budget Type</label>
                <select name="budget_type" id="budgetType"
                        class="form-select @error('budget_type') is-invalid @enderror"
                        onchange="updateSubCats()">
                    <optgroup label="Income Statement">
                        <option value="revenue" {{ old('budget_type')==='revenue' ?'selected':'' }}>
                            Revenue — income and receipts
                        </option>
                        <option value="expense" {{ old('budget_type','expense')==='expense' ?'selected':'' }}>
                            Expense — costs and expenditures
                        </option>
                    </optgroup>
                    <optgroup label="Balance Sheet &amp; CapEx">
                        <option value="assets" {{ old('budget_type')==='assets' ?'selected':'' }}>
                            Assets — resources owned or controlled
                        </option>
                        <option value="liabilities" {{ old('budget_type')==='liabilities' ?'selected':'' }}>
                            Liabilities — obligations and payables
                        </option>
                        <option value="capital_expenditure" {{ old('budget_type')==='capital_expenditure' ?'selected':'' }}>
                            Capital Expenditure — long-term investment
                        </option>
                    </optgroup>
                    <optgroup label="Pricing">
                        <option value="ex_pump_item" {{ old('budget_type')==='ex_pump_item' ?'selected':'' }}>
                            Ex-pump Item — ex-pump price forecasting
                        </option>
                    </optgroup>
                </select>
                @error('budget_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3" id="subCatWrap">
                <label class="form-label small fw-semibold">
                    Sub-Category <span class="text-muted fw-normal">(optional)</span>
                </label>
                <select name="account_sub_category_id" id="subCatSelect"
                        class="form-select @error('account_sub_category_id') is-invalid @enderror">
                    <option value="">— None —</option>
                </select>
                @error('account_sub_category_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text" id="subCatHint">Select a budget type first.</div>
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

            {{-- ── Category-level Default Rate / Frequency (fallback when account codes have none) ── --}}
            @if($showRateField || $showFreqField)
            <div class="mb-4 p-3 rounded" style="background:#F5F3FF;border:1px solid #C4B5FD;">
                <div style="font-size:12px;font-weight:700;color:#4C1D95;margin-bottom:4px;">
                    <i class="bi bi-lock-fill"></i> Category Budget Defaults
                </div>
                <div style="font-size:11px;color:#6D28D9;margin-bottom:10px;">
                    Used as a fallback when individual account codes in this category don't have their own default.
                </div>
                <div class="row g-3">
                    @if($showRateField)
                    <div class="col-sm-6">
                        <label class="form-label small fw-semibold">Default Rate
                            <span class="text-muted fw-normal">({{ currency() }} per unit)</span>
                        </label>
                        <input type="number" name="default_rate"
                            value="{{ old('default_rate') }}"
                            class="form-control form-control-sm @error('default_rate') is-invalid @enderror"
                            min="0" step="any" placeholder="0.00">
                        <div class="form-text" style="font-size:11px;">
                            Applies to all codes in this category that have no code-level rate.
                        </div>
                        @error('default_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @endif
                    @if($showFreqField)
                    <div class="col-sm-6">
                        <label class="form-label small fw-semibold">Default Frequency
                            <span class="text-muted fw-normal">(times per year)</span>
                        </label>
                        <input type="number" name="default_frequency"
                            value="{{ old('default_frequency') }}"
                            class="form-control form-control-sm @error('default_frequency') is-invalid @enderror"
                            min="0" step="any" placeholder="1">
                        <div class="form-text" style="font-size:11px;">
                            Applies to all codes in this category that have no code-level frequency.
                        </div>
                        @error('default_frequency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @endif
                </div>
            </div>
            @endif

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
@push('scripts')
<script>
const SUB_CATS = @json($subCategories->map(fn($group) => $group->map(fn($s) => ['id' => $s->id, 'name' => $s->name])));
const OLD_SUB  = {{ old('account_sub_category_id', 'null') }};

function updateSubCats() {
    const type   = document.getElementById('budgetType').value;
    const select = document.getElementById('subCatSelect');
    const hint   = document.getElementById('subCatHint');
    const subs   = SUB_CATS[type] ?? [];

    select.innerHTML = '<option value="">— None —</option>';
    subs.forEach(s => {
        const opt = new Option(s.name, s.id, false, s.id == OLD_SUB);
        select.appendChild(opt);
    });

    if (subs.length === 0) {
        hint.textContent = type ? 'No sub-categories defined for this type yet.' : 'Select a budget type first.';
        document.getElementById('subCatWrap').style.opacity = '.5';
    } else {
        hint.textContent = '';
        document.getElementById('subCatWrap').style.opacity = '1';
    }
}

document.addEventListener('DOMContentLoaded', updateSubCats);
</script>
@endpush

@endsection
