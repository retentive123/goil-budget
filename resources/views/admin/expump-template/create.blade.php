@extends('layouts.app')
@section('title', 'New Ex-pump Template')

@section('content')
<div class="row justify-content-center">
<div class="col-md-5">

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.expump-templates.index') }}" class="text-muted text-decoration-none">Ex-pump Templates</a>
    <span class="text-muted">/</span>
    <span>New</span>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-4">New Template</h5>

        <form method="POST" action="{{ route('admin.expump-templates.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label small fw-semibold">Template Name</label>
                <input type="text" name="name" value="{{ old('name') }}"
                    class="form-control @error('name') is-invalid @enderror"
                    placeholder="e.g. Q1 2026 Ex-pump Forecast" autofocus>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold">Description <span class="text-muted fw-normal">(optional)</span></label>
                <textarea name="description" rows="2" class="form-control"
                    placeholder="Brief note about this template">{{ old('description') }}</textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Create &amp; Add Values</button>
                <a href="{{ route('admin.expump-templates.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

</div>
</div>
@endsection
