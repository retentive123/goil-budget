@extends('layouts.app')
@section('title', 'New Service Station')
@section('content')

<div class="row justify-content-center">
<div class="col-md-6">

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.service-stations.index') }}" class="text-decoration-none" style="color:#E65C00;">
        <i class="fas fa-gas-pump me-1"></i>Service Stations
    </a>
    <span class="text-muted">/</span>
    <span style="color:#1B2A4A;font-weight:600;">New Station</span>
</div>

<div class="card border-0 shadow-sm" style="border-radius:12px;">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-4" style="color:#1B2A4A;">
            <i class="fas fa-gas-pump me-2" style="color:#E65C00;"></i>Create Service Station
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

        <form method="POST" action="{{ route('admin.service-stations.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label small fw-semibold" style="color:#1B2A4A;">Station Name</label>
                <input type="text" name="name" value="{{ old('name') }}"
                    class="form-control @error('name') is-invalid @enderror"
                    style="border-radius:8px;border-color:#E2E8F0;"
                    placeholder="e.g. Accra Central Station">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold" style="color:#1B2A4A;">Station Code</label>
                <input type="text" name="code" value="{{ old('code') }}"
                    class="form-control @error('code') is-invalid @enderror"
                    style="border-radius:8px;border-color:#E2E8F0;"
                    placeholder="e.g. ACC-C01">
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold" style="color:#1B2A4A;">Zone</label>
                <select name="zone_id"
                        class="form-select @error('zone_id') is-invalid @enderror"
                        style="border-radius:8px;border-color:#E2E8F0;">
                    <option value="">— Select a zone —</option>
                    @foreach($zones as $zone)
                        <option value="{{ $zone->id }}" {{ old('zone_id') == $zone->id ? 'selected' : '' }}>
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
                    <option value="expense" {{ old('budget_type', 'expense') === 'expense' ? 'selected' : '' }}>
                        Expense — cost centre only
                    </option>
                    <option value="revenue" {{ old('budget_type') === 'revenue' ? 'selected' : '' }}>
                        Revenue — income generating only
                    </option>
                    <option value="both" {{ old('budget_type') === 'both' ? 'selected' : '' }}>
                        Both — revenue and expense
                    </option>
                </select>
                @error('budget_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold" style="color:#1B2A4A;">Description</label>
                <textarea name="description" rows="3"
                    class="form-control @error('description') is-invalid @enderror"
                    style="border-radius:8px;border-color:#E2E8F0;"
                    placeholder="Optional description…">{{ old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-sm px-4"
                        style="background:#E65C00;color:#fff;border-radius:8px;border:none;">
                    <i class="fas fa-plus-circle me-1"></i>Create Station
                </button>
                <a href="{{ route('admin.service-stations.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
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
</style>

@endsection
