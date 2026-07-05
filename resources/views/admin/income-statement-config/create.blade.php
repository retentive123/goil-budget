@extends('layouts.app')
@section('title', 'New P&L Layout')
@section('content')

<div class="row justify-content-center">
<div class="col-md-5">

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.income-statement-configs.index') }}" class="text-muted text-decoration-none">
        P&L Layouts
    </a>
    <span class="text-muted">/</span>
    <span>New</span>
</div>

<div class="card shadow-sm border-0" style="border-radius:12px">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-1" style="color:#1B2A4A">New P&L Layout</h5>
        <p class="text-muted small mb-4">Give this layout a name. You'll add the statement lines next.</p>

        <form method="POST" action="{{ route('admin.income-statement-configs.store') }}">
            @csrf
            <div class="mb-4">
                <label class="form-label small fw-semibold">Layout Name</label>
                <input type="text" name="name" value="{{ old('name') }}"
                       class="form-control @error('name') is-invalid @enderror"
                       placeholder="e.g. Standard Income Statement"
                       autofocus>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn fw-semibold"
                        style="background:#E65C00;color:#fff;border-radius:8px;border:none">
                    Continue →
                </button>
                <a href="{{ route('admin.income-statement-configs.index') }}"
                   class="btn btn-outline-secondary" style="border-radius:8px">Cancel</a>
            </div>
        </form>
    </div>
</div>

</div>
</div>
@endsection
