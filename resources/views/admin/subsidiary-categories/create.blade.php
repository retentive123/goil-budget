@extends('layouts.app')
@section('title', 'New Subsidiary Category')
@section('content')

<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item">
                <a href="{{ route('admin.subsidiary-categories.index') }}" class="text-decoration-none text-muted">
                    <i class="bi bi-folder2"></i> Subsidiary Categories
                </a>
            </li>
            <li class="breadcrumb-item active">New Category</li>
        </ol>
    </nav>
    <h5 class="fw-bold mb-0">New Subsidiary Category</h5>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="chart-card">
            <form method="POST" action="{{ route('admin.subsidiary-categories.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Category Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="form-control @error('name') is-invalid @enderror"
                           placeholder="e.g. Upstream, Downstream, Retail" autofocus>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Description</label>
                    <textarea name="description" rows="3"
                              class="form-control @error('description') is-invalid @enderror"
                              placeholder="Optional — what subsidiaries belong in this group?">{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row mb-3">
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
                        <i class="bi bi-save me-1"></i>Create Category
                    </button>
                    <a href="{{ route('admin.subsidiary-categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
