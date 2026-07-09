@extends('layouts.app')
@section('title', 'Add Budget Period')
@section('content')

<div class="row justify-content-center">
<div class="col-md-6">

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.budget-periods.index') }}" class="text-muted text-decoration-none">Budget Periods</a>
    <span class="text-muted">/</span>
    <span>Add</span>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-4">Add Budget Period</h5>

        <form method="POST" action="{{ route('admin.budget-periods.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label small fw-semibold">Name</label>
                <input type="text" name="name" value="{{ old('name') }}"
                    class="form-control @error('name') is-invalid @enderror"
                    placeholder="e.g. FY 2026">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Year</label>
                <input type="number" name="year" value="{{ old('year', date('Y')) }}"
                    class="form-control @error('year') is-invalid @enderror"
                    min="2020" max="2100">
                @error('year')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Start date</label>
                <input type="date" name="start_date" value="{{ old('start_date') }}"
                    class="form-control @error('start_date') is-invalid @enderror">
                @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">End date</label>
                <input type="date" name="end_date" value="{{ old('end_date') }}"
                    class="form-control @error('end_date') is-invalid @enderror">
                @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold">Budget Entry Mode</label>
                <div class="d-flex gap-4 mt-1">
                    <div class="form-check">
                        <input type="radio" name="entry_mode" value="quarterly" id="mode_q"
                            class="form-check-input"
                            {{ old('entry_mode', 'quarterly') === 'quarterly' ? 'checked' : '' }}>
                        <label for="mode_q" class="form-check-label">
                            <span class="fw-semibold">Quarterly</span>
                            <div class="text-muted small">Enter totals for Q1, Q2, Q3, Q4</div>
                        </label>
                    </div>
                    <div class="form-check">
                        <input type="radio" name="entry_mode" value="monthly" id="mode_m"
                            class="form-check-input"
                            {{ old('entry_mode') === 'monthly' ? 'checked' : '' }}>
                        <label for="mode_m" class="form-check-label">
                            <span class="fw-semibold">Monthly</span>
                            <div class="text-muted small">Enter amounts for each month (Jan–Dec)</div>
                        </label>
                    </div>
                </div>
                @error('entry_mode')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Create Period</button>
                <a href="{{ route('admin.budget-periods.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

</div>
</div>
@endsection
