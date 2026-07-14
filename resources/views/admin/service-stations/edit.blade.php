@extends('layouts.app')
@section('title', 'Edit Service Station')
@section('content')

<div class="row justify-content-center">
<div class="col-md-6">

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.service-stations.index') }}" class="text-decoration-none" style="color:#E65C00;">
        <i class="fas fa-gas-pump me-1"></i>Service Stations
    </a>
    <span class="text-muted">/</span>
    <span style="color:#1B2A4A;font-weight:600;">Edit</span>
</div>

<div class="card border-0 shadow-sm" style="border-radius:12px;">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-4" style="color:#1B2A4A;">
            <i class="fas fa-pencil-alt me-2" style="color:#E65C00;"></i>Edit — {{ $station->name }}
        </h5>

        @if($errors->any())
            <div class="alert alert-danger small mb-3">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.service-stations.update', $station) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label small fw-semibold" style="color:#1B2A4A;">Station Name</label>
                <input type="text" name="name" value="{{ old('name', $station->name) }}"
                    class="form-control @error('name') is-invalid @enderror"
                    style="border-radius:8px;border-color:#E2E8F0;">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold" style="color:#1B2A4A;">Station Code</label>
                <input type="text" name="code" value="{{ old('code', $station->code) }}"
                    class="form-control @error('code') is-invalid @enderror"
                    style="border-radius:8px;border-color:#E2E8F0;">
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold" style="color:#1B2A4A;">Zone</label>
                <select name="zone_id"
                        class="form-select @error('zone_id') is-invalid @enderror"
                        style="border-radius:8px;border-color:#E2E8F0;">
                    <option value="">— Select a zone —</option>
                    @foreach($zones as $zone)
                        <option value="{{ $zone->id }}" {{ old('zone_id', $station->zone_id) == $zone->id ? 'selected' : '' }}>
                            {{ $zone->name }} ({{ $zone->code }})
                        </option>
                    @endforeach
                </select>
                @error('zone_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold" style="color:#1B2A4A;">Budget Type</label>
                <select name="budget_type"
                        class="form-select @error('budget_type') is-invalid @enderror"
                        style="border-radius:8px;border-color:#E2E8F0;">
                    <option value="expense" {{ old('budget_type', $station->budget_type ?? 'expense') === 'expense' ? 'selected' : '' }}>
                        Expense — costs and expenditures
                    </option>
                    <option value="revenue" {{ old('budget_type', $station->budget_type ?? 'expense') === 'revenue' ? 'selected' : '' }}>
                        Revenue — income and receipts
                    </option>
                    <option value="both"    {{ old('budget_type', $station->budget_type ?? 'expense') === 'both'    ? 'selected' : '' }}>
                        Both — mixed revenue and expense
                    </option>
                </select>
                @error('budget_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold" style="color:#1B2A4A;">Description</label>
                <textarea name="description" rows="3"
                    class="form-control @error('description') is-invalid @enderror"
                    style="border-radius:8px;border-color:#E2E8F0;">{{ old('description', $station->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <div class="form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" id="is_active"
                        class="form-check-input"
                        {{ old('is_active', $station->is_active) ? 'checked' : '' }}>
                    <label for="is_active" class="form-check-label small fw-semibold" style="color:#1B2A4A;">
                        Active
                        <span class="text-muted fw-normal">(uncheck to deactivate this station)</span>
                    </label>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-sm px-4"
                        style="background:#E65C00;color:#fff;border-radius:8px;border:none;">
                    <i class="fas fa-save me-1"></i>Save Changes
                </button>
                <a href="{{ route('admin.service-stations.show', $station) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

</div>
</div>

<style>
.form-control:focus, .form-select:focus {
    border-color: #E65C00;
    box-shadow: 0 0 0 0.2rem rgba(230,92,0,0.15);
}
.form-check-input:checked {
    background-color: #E65C00;
    border-color: #E65C00;
}
</style>

@endsection
