@extends('layouts.app')
@section('title', 'Edit Category')
@section('content')


<div class="row justify-content-center">
    <div class="col-lg-7">

        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('admin.account-categories.index') }}" class="text-decoration-none">
                        <i class="fas fa-folder"></i> Categories
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Edit — {{ $accountCategory->name }}
                </li>
            </ol>
        </nav>

        {{-- Main Card --}}
        <div class="card border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            {{-- Card Header --}}
            <div class="card-header border-0 px-4 py-3" style="background: #1B2A4A; color: #fff;">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-circle"
                         style="width: 44px; height: 44px; background: rgba(255,255,255,0.12); font-size: 20px;">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0" style="color: #fff;">Edit Category</h5>
                        <p class="mb-0" style="font-size: 13px; color: rgba(255,255,255,0.7);">
                            {{ $accountCategory->code }} — {{ $accountCategory->name }}
                        </p>
                    </div>
                    <div class="ms-auto">
                        <span class="badge px-3 py-2" style="background: rgba(255,255,255,0.15); color: #fff; font-size: 11px; border-radius: 20px;">
                            <i class="fas fa-hashtag"></i> ID: {{ $accountCategory->id }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Card Body --}}
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.account-categories.update', $accountCategory) }}">
                    @csrf @method('PUT')

                    {{-- Basic Information --}}
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-3" style="color: #1B2A4A; font-size: 13px;">
                            <i class="fas fa-info-circle" style="color: #E65C00;"></i> Basic Information
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="color: #1B2A4A; font-size: 13px;">
                                    <i class="fas fa-tag" style="color: #E65C00;"></i> Category Name
                                </label>
                                <div class="input-group" style="border-radius: 10px; overflow: hidden;">
                                    <span class="input-group-text" style="background: #F8FAFC; border-color: #E2E8F0; color: #64748B;">
                                        <i class="fas fa-folder"></i>
                                    </span>
                                    <input type="text" name="name"
                                        value="{{ old('name', $accountCategory->name) }}"
                                        class="form-control @error('name') is-invalid @enderror"
                                        style="border-color: #E2E8F0; padding: 10px 14px;"
                                        placeholder="Enter category name">
                                </div>
                                @error('name')
                                    <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="color: #1B2A4A; font-size: 13px;">
                                    <i class="fas fa-code" style="color: #E65C00;"></i> Category Code
                                </label>
                                <div class="input-group" style="border-radius: 10px; overflow: hidden;">
                                    <span class="input-group-text" style="background: #F8FAFC; border-color: #E2E8F0; color: #64748B;">
                                        <i class="fas fa-hashtag"></i>
                                    </span>
                                    <input type="text" name="code"
                                        value="{{ old('code', $accountCategory->code) }}"
                                        class="form-control @error('code') is-invalid @enderror"
                                        style="border-color: #E2E8F0; padding: 10px 14px; font-family: monospace;"
                                        placeholder="Enter category code">
                                </div>
                                @error('code')
                                    <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Budget Type --}}
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-3" style="color: #1B2A4A; font-size: 13px;">
                            <i class="fas fa-wallet" style="color: #E65C00;"></i> Budget Type
                        </h6>
                        <select name="budget_type"
                                class="form-select @error('budget_type') is-invalid @enderror"
                                style="border-radius: 10px; border-color: #E2E8F0; padding: 10px 14px;">
                            <option value="expense"
                                    {{ old('budget_type', $accountCategory->budget_type ?? 'expense') === 'expense' ? 'selected' : '' }}>
                                📉 Expense — costs and expenditures
                            </option>
                            <option value="revenue"
                                    {{ old('budget_type', $accountCategory->budget_type ?? 'expense') === 'revenue' ? 'selected' : '' }}>
                                📈 Revenue — income and receipts
                            </option>
                            <option value="both"
                                    {{ old('budget_type', $accountCategory->budget_type ?? 'expense') === 'both' ? 'selected' : '' }}>
                                📊 Both — mixed revenue and expense
                            </option>
                        </select>
                        <div style="font-size: 12px; color: #64748B; margin-top: 4px;">
                            <i class="fas fa-info-circle"></i> Determines how line items in this category are treated.
                        </div>
                        @error('budget_type')
                            <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="color: #1B2A4A; font-size: 13px;">
                            <i class="fas fa-align-left" style="color: #E65C00;"></i> Description
                            <span class="text-muted fw-normal" style="font-size: 11px;">(optional)</span>
                        </label>
                        <textarea name="description" rows="3"
                            class="form-control @error('description') is-invalid @enderror"
                            style="border-radius: 10px; border-color: #E2E8F0; padding: 12px; resize: vertical;"
                            placeholder="Brief description of this category…">{{ old('description', $accountCategory->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Status --}}
                    <div class="mb-4 p-3 rounded-3" style="background: #F8FAFC; border: 1px solid #E2E8F0;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div style="font-weight: 600; color: #1B2A4A; font-size: 13px;">
                                    <i class="fas fa-circle" style="color: #E65C00;"></i> Status
                                </div>
                                <div style="font-size: 12px; color: #64748B;">
                                    Inactive categories are hidden from budget entry forms.
                                </div>
                            </div>
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1"
                                    id="is_active"
                                    class="form-check-input"
                                    style="width: 44px; height: 22px; cursor: pointer;"
                                    {{ old('is_active', $accountCategory->is_active) ? 'checked' : '' }}
                                    onchange="updateStatusLabel(this)">
                                <label for="is_active" class="form-check-label fw-semibold" id="statusLabel"
                                       style="color: {{ old('is_active', $accountCategory->is_active) ? '#10B981' : '#94A3B8' }};">
                                    {{ old('is_active', $accountCategory->is_active) ? 'Active' : 'Inactive' }}
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="d-flex gap-2 pt-3 border-top">
                        <button type="submit" class="btn px-4 py-2 fw-semibold"
                                style="background: #E65C00; color: #fff; border-radius: 10px; border: none; transition: all 0.3s ease;">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="{{ route('admin.account-categories.index') }}"
                           class="btn px-4 py-2 fw-semibold"
                           style="background: #F1F5F9; color: #475569; border-radius: 10px; border: 1px solid #E2E8F0; transition: all 0.3s ease;">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        @if($accountCategory->account_codes_count === 0)
                        <form method="POST"
                              action="{{ route('admin.account-categories.destroy', $accountCategory) }}"
                              class="ms-auto"
                              onsubmit="return confirm('Delete this category?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn px-4 py-2 fw-semibold"
                                    style="background: #FEE2E2; color: #991B1B; border-radius: 10px; border: 1px solid #FCA5A5; transition: all 0.3s ease;">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- Stats Card --}}
        <div class="mt-3 p-3 rounded-3" style="background: #F8FAFC; border: 1px solid #E2E8F0;">
            <div class="row text-center">
                <div class="col-4">
                    <div style="font-size: 11px; color: #94A3B8;">Account Codes</div>
                    <div style="font-size: 16px; font-weight: 700; color: #1B2A4A;">
                        {{ $accountCategory->account_codes_count }}
                    </div>
                </div>
                <div class="col-4">
                    <div style="font-size: 11px; color: #94A3B8;">Created</div>
                    <div style="font-size: 13px; font-weight: 600; color: #1B2A4A;">
                        {{ $accountCategory->created_at->format('d M Y') }}
                    </div>
                </div>
                <div class="col-4">
                    <div style="font-size: 11px; color: #94A3B8;">Last Updated</div>
                    <div style="font-size: 13px; font-weight: 600; color: #1B2A4A;">
                        {{ $accountCategory->updated_at->format('d M Y') }}
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function updateStatusLabel(checkbox) {
    const label = document.getElementById('statusLabel');
    if (checkbox.checked) {
        label.textContent = 'Active';
        label.style.color = '#10B981';
    } else {
        label.textContent = 'Inactive';
        label.style.color = '#94A3B8';
    }
}
</script>

<style>
    .breadcrumb {
        background: transparent;
        padding: 0;
    }

    .breadcrumb-item a {
        color: #64748B;
        text-decoration: none;
        transition: color 0.2s;
    }

    .breadcrumb-item a:hover {
        color: #E65C00;
    }

    .breadcrumb-item.active {
        color: #1B2A4A;
        font-weight: 600;
    }

    .form-control:focus, .form-select:focus {
        border-color: #E65C00;
        box-shadow: 0 0 0 0.2rem rgba(230, 92, 0, 0.15);
    }

    .btn-goil-orange:hover {
        background: #C44D00 !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(230, 92, 0, 0.3);
    }

    .form-check-input:checked {
        background-color: #E65C00;
        border-color: #E65C00;
    }

    .form-check-input:focus {
        border-color: #E65C00;
        box-shadow: 0 0 0 0.2rem rgba(230, 92, 0, 0.15);
    }
</style>

@endsection
