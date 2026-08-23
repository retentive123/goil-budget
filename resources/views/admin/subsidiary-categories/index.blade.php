@extends('layouts.app')
@section('title', 'Subsidiary Categories')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Subsidiary Categories</h5>
        <p class="text-muted small mb-0">Group subsidiaries into named categories for reporting</p>
    </div>
    <a href="{{ route('admin.subsidiary-categories.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>New Category
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger"><i class="bi bi-x-circle-fill me-2"></i>{{ session('error') }}</div>
@endif

<div class="chart-card">
    @if($categories->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-folder2" style="font-size:48px;color:#CBD5E1;display:block;margin-bottom:12px"></i>
            <p class="fw-semibold">No categories yet</p>
            <a href="{{ route('admin.subsidiary-categories.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i>Create First Category
            </a>
        </div>
    @else
        <table class="table table-hover mb-0">
            <thead>
                <tr style="font-size:12px;text-transform:uppercase;color:var(--slate)">
                    <th style="padding:10px 16px">Category</th>
                    <th style="padding:10px 16px;text-align:center">Subsidiaries</th>
                    <th style="padding:10px 16px;text-align:center">Status</th>
                    <th style="padding:10px 16px;text-align:center">Sort</th>
                    <th style="padding:10px 16px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $cat)
                <tr>
                    <td style="padding:12px 16px">
                        <div class="fw-semibold" style="font-size:14px">{{ $cat->name }}</div>
                        @if($cat->description)
                            <div class="text-muted small">{{ $cat->description }}</div>
                        @endif
                    </td>
                    <td style="padding:12px 16px;text-align:center">
                        <a href="{{ route('admin.subsidiaries.index', ['category_id' => $cat->id]) }}"
                           class="badge" style="background:#EFF6FF;color:#1D4ED8;text-decoration:none">
                            {{ $cat->subsidiaries_count }}
                        </a>
                    </td>
                    <td style="padding:12px 16px;text-align:center">
                        @if($cat->is_active)
                            <span class="badge" style="background:#D1FAE5;color:#065F46">Active</span>
                        @else
                            <span class="badge" style="background:#FEE2E2;color:#991B1B">Inactive</span>
                        @endif
                    </td>
                    <td style="padding:12px 16px;text-align:center;color:var(--slate);font-size:13px">{{ $cat->sort_order }}</td>
                    <td style="padding:12px 16px;text-align:right">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="{{ route('admin.subsidiary-categories.edit', $cat) }}"
                               class="btn btn-sm btn-outline-primary" style="font-size:11px;padding:2px 8px">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.subsidiary-categories.destroy', $cat) }}"
                                  data-confirm-name="{{ $cat->name }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:11px;padding:2px 8px">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
