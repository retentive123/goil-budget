@extends('layouts.app')
@section('title', 'New Subsidiary')
@section('content')

<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item">
                <a href="{{ route('admin.subsidiaries.index') }}" class="text-decoration-none text-muted">
                    <i class="bi bi-building"></i> Subsidiaries
                </a>
            </li>
            <li class="breadcrumb-item active">New Subsidiary</li>
        </ol>
    </nav>
    <h5 class="fw-bold mb-0">New Subsidiary</h5>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="chart-card">
            <form method="POST" action="{{ route('admin.subsidiaries.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Category <span class="text-danger">*</span></label>
                    <select name="subsidiary_category_id"
                            class="form-select @error('subsidiary_category_id') is-invalid @enderror">
                        <option value="">Select a category…</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('subsidiary_category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('subsidiary_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">
                        <a href="{{ route('admin.subsidiary-categories.create') }}" target="_blank">Create a new category</a>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-8">
                        <label class="form-label fw-semibold small">Subsidiary Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="e.g. GOIL Upstream Ltd" autofocus>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-semibold small">Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" value="{{ old('code') }}"
                               class="form-control @error('code') is-invalid @enderror"
                               placeholder="e.g. GOIL-UP" style="text-transform:uppercase"
                               oninput="this.value=this.value.toUpperCase()">
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Description</label>
                    <textarea name="description" rows="3"
                              class="form-control @error('description') is-invalid @enderror"
                              placeholder="Optional description">{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row mb-4">
                    <div class="col-6">
                        <label class="form-label fw-semibold small">Sort Order</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}"
                               class="form-control form-control-sm" min="0">
                    </div>
                    <div class="col-6 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" class="form-check-input" id="is_active"
                                   name="is_active" value="1"
                                   {{ old('is_active', '1') ? 'checked' : '' }}
                                   style="width:40px;height:20px">
                            <label class="form-check-label small ms-1" for="is_active">Active</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Create Subsidiary
                    </button>
                    <a href="{{ route('admin.subsidiaries.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
