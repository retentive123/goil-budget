@extends('layouts.app')
@section('title', 'Add Department')
@section('content')

<div class="row justify-content-center">
<div class="col-md-6">

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.departments.index') }}" class="text-muted text-decoration-none">Departments</a>
    <span class="text-muted">/</span>
    <span>Add</span>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-4">Add Department</h5>

        <form method="POST" action="{{ route('admin.departments.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label small fw-semibold">Department name</label>
                <input type="text" name="name" value="{{ old('name') }}"
                    class="form-control @error('name') is-invalid @enderror">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Department code</label>
                <input type="text" name="code" value="{{ old('code') }}"
                    class="form-control @error('code') is-invalid @enderror"
                    placeholder="e.g. FIN, HR, IT">
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Add before description field --}}
            <div class="mb-3">
                <label class="form-label small fw-semibold">Department Budget Type</label>
                <select name="budget_type"
                        class="form-select @error('budget_type') is-invalid @enderror">
                    <option value="expense" {{ old('budget_type','expense')==='expense'?'selected':'' }}>
                        Expense — cost centre only
                    </option>
                    <option value="revenue" {{ old('budget_type')==='revenue'?'selected':'' }}>
                        Revenue — income generating only
                    </option>
                    <option value="both"    {{ old('budget_type')==='both'?'selected':'' }}>
                        Both — revenue and expense
                    </option>
                </select>
                @error('budget_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Zone</label>
                <select name="zone_id"
                        class="form-select @error('zone_id') is-invalid @enderror">
                    <option value="">— Select a zone —</option>
                    @foreach($zones as $zone)
                        <option value="{{ $zone->id }}" {{ old('zone_id') == $zone->id ? 'selected' : '' }}>
                            {{ $zone->name }}
                        </option>
                    @endforeach
                </select>
                @error('zone_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold">Description</label>
                <textarea name="description" rows="3"
                    class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Create Department</button>
                <a href="{{ route('admin.departments.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

</div>
</div>
@endsection
