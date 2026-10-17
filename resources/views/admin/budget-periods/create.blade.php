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

            <div class="mb-3">
                <label class="form-label small fw-semibold">
                    Submission Deadline
                    <span class="text-muted fw-normal">(optional)</span>
                </label>
                <input type="datetime-local" name="submission_deadline"
                    value="{{ old('submission_deadline') }}"
                    class="form-control @error('submission_deadline') is-invalid @enderror">
                <div class="form-text">
                    The date and time after which departments can no longer submit budgets.
                    If left blank, the global deadline setting (days after opening) is used.
                </div>
                @error('submission_deadline')<div class="invalid-feedback">{{ $message }}</div>@enderror
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

            {{-- ── Quantity × Rate calculation settings ── --}}
            <div class="mb-4 p-3 rounded" style="background:#F5F3FF;border:1px solid #C4B5FD;">
                <div style="font-size:13px;font-weight:700;color:#4C1D95;margin-bottom:4px;">
                    <i class="bi bi-calculator"></i> Budget Entry Calculation Mode
                </div>
                <div style="font-size:12px;color:#6D28D9;margin-bottom:12px;">
                    These settings are <strong>locked to this period</strong> and preserved in history —
                    changing the global defaults later won't affect approved budgets.
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Calculation Mode</label>
                    <select name="line_item_calc_mode" id="calcModeSelect"
                            class="form-select form-select-sm @error('line_item_calc_mode') is-invalid @enderror"
                            style="max-width:300px" onchange="updateCalcFields()">
                        <option value="none"          {{ old('line_item_calc_mode', $globalCalcMode) === 'none'          ? 'selected' : '' }}>Direct entry (no Qty / Rate)</option>
                        <option value="qty_rate"      {{ old('line_item_calc_mode', $globalCalcMode) === 'qty_rate'      ? 'selected' : '' }}>Qty × Rate</option>
                        <option value="qty_rate_freq" {{ old('line_item_calc_mode', $globalCalcMode) === 'qty_rate_freq' ? 'selected' : '' }}>Qty × Rate × Frequency</option>
                    </select>
                    @error('line_item_calc_mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div id="adminLockFields" style="display:none">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="form-check form-switch">
                                <input type="hidden" name="admin_sets_rate" value="0">
                                <input type="checkbox" class="form-check-input" role="switch"
                                       name="admin_sets_rate" value="1" id="adminSetsRate"
                                       style="width:44px;height:22px"
                                       {{ old('admin_sets_rate', $globalAdminSetsRate ? '1' : '0') === '1' ? 'checked' : '' }}>
                                <label for="adminSetsRate" class="form-check-label small fw-semibold">
                                    Admin Controls Rate
                                </label>
                            </div>
                            <div class="form-text" style="font-size:11px;margin-left:52px">
                                Rate comes from the account code (read-only for budget inputters).
                            </div>
                        </div>
                        <div class="col-sm-6" id="adminFreqWrap">
                            <div class="form-check form-switch">
                                <input type="hidden" name="admin_sets_freq" value="0">
                                <input type="checkbox" class="form-check-input" role="switch"
                                       name="admin_sets_freq" value="1" id="adminSetsFreq"
                                       style="width:44px;height:22px"
                                       {{ old('admin_sets_freq', $globalAdminSetsFreq ? '1' : '0') === '1' ? 'checked' : '' }}>
                                <label for="adminSetsFreq" class="form-check-label small fw-semibold">
                                    Admin Controls Frequency
                                </label>
                            </div>
                            <div class="form-text" style="font-size:11px;margin-left:52px">
                                Frequency comes from the account code (read-only for budget inputters).
                            </div>
                        </div>
                    </div>
                </div>
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

@push('scripts')
<script>
function updateCalcFields() {
    const mode = document.getElementById('calcModeSelect').value;
    const lockFields = document.getElementById('adminLockFields');
    const freqWrap   = document.getElementById('adminFreqWrap');
    lockFields.style.display = mode === 'none' ? 'none' : '';
    if (freqWrap) freqWrap.style.display = mode === 'qty_rate_freq' ? '' : 'none';
}
document.addEventListener('DOMContentLoaded', updateCalcFields);
</script>
@endpush

@endsection
