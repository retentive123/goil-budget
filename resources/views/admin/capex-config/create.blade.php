@extends('layouts.app')
@section('title', 'New CapEx Layout')
@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.capex-configs.index') }}" class="text-muted text-decoration-none">
        CapEx Layouts
    </a>
    <span class="text-muted">/</span>
    <span class="fw-semibold">New Layout</span>
</div>

<div class="chart-card" style="max-width:480px">
    <h6 class="fw-bold mb-3" style="color:#1B2A4A">Create CapEx Layout</h6>
    <form method="POST" action="{{ route('admin.capex-configs.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label small fw-semibold">Layout Name</label>
            <input type="text" name="name" value="{{ old('name') }}"
                   class="form-control @error('name') is-invalid @enderror"
                   placeholder="e.g. Capital Expenditure 2026">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-sm fw-semibold"
                    style="background:#1B2A4A;color:#fff;border-radius:8px">
                Create &amp; Configure Lines
            </button>
            <a href="{{ route('admin.capex-configs.index') }}"
               class="btn btn-sm btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>

@endsection
