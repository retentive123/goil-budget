@extends('layouts.app')
@section('title', 'Account Categories')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Account Categories</h5>
    <a href="{{ route('admin.account-categories.create') }}"
       class="btn btn-primary btn-sm">+ Add Category</a>
</div>

{{-- Import/Export buttons --}}
<div class="d-flex gap-2 mb-3">
    <a href="{{ route('ie.categories.download') }}"
       class="btn btn-sm btn-outline-success">
        ↓ Download Template
    </a>
    <button type="button"
            onclick="document.getElementById('catUpload').classList.toggle('d-none')"
            class="btn btn-sm btn-outline-primary">
        ↑ Import from Excel
    </button>
</div>

<div id="catUpload" class="d-none chart-card mb-4">
    <div style="font-size:13px;font-weight:600;color:var(--navy);margin-bottom:8px">
        Import Categories from Excel
    </div>
    <form method="POST" action="{{ route('ie.categories.upload') }}"
          enctype="multipart/form-data">
        @csrf
        <div class="d-flex gap-2 align-items-end">
            <div class="flex-grow-1">
                <input type="file" name="file" accept=".xlsx,.xls,.csv"
                       class="form-control form-control-sm">
            </div>
            <button type="submit" class="btn btn-sm btn-primary">Upload</button>
        </div>
        <div class="form-text">
            Existing categories with the same code will be updated.
            Download the template first to see the correct format.
        </div>
    </form>

    @if(session('import_errors'))
    <div class="mt-2">
        @foreach(session('import_errors') as $err)
        <div style="font-size:11px;color:#991B1B">⚠ {{ $err }}</div>
        @endforeach
    </div>
    @endif
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Description</th>
                    <th>Account Codes</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                <tr>
                    <td class="fw-semibold small">{{ $category->name }}</td>
                    <td><code>{{ $category->code }}</code></td>
                    <td class="small text-muted">
                        {{ $category->description ?? '—' }}
                    </td>
                    <td>
                        <span class="badge bg-secondary">
                            {{ $category->account_codes_count }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-{{ $category->is_active ? 'success' : 'secondary' }}">
                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.account-categories.show', $category) }}"
                               class="btn btn-sm btn-outline-secondary">View</a>
                            <a href="{{ route('admin.account-categories.edit', $category) }}"
                               class="btn btn-sm btn-outline-primary">Edit</a>
                            @if($category->account_codes_count === 0)
                            <form method="POST"
                                  action="{{ route('admin.account-categories.destroy', $category) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Delete this category?')">
                                    Delete
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        No categories yet.
                        <a href="{{ route('admin.account-categories.create') }}">Add one</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $categories->appends(request()->query())->links('pagination::bootstrap-5') }}
</div>
@endsection
