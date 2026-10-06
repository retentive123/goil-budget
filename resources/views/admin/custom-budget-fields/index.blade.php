@extends('layouts.app')
@section('title', 'Custom Budget Fields')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Custom Budget Fields</h5>
        <p class="text-muted small mb-0">Extra columns shown on every budget line item entry form.</p>
    </div>
    <a href="{{ route('admin.custom-budget-fields.create') }}" class="btn btn-primary btn-sm">+ Add Field</a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:40px">#</th>
                    <th>Label</th>
                    <th>Type</th>
                    <th>Required</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($fields as $field)
                <tr>
                    <td class="text-muted">{{ $field->display_order }}</td>
                    <td>
                        <div class="fw-semibold">{{ $field->label }}</div>
                        @if($field->placeholder)
                            <small class="text-muted">{{ $field->placeholder }}</small>
                        @endif
                    </td>
                    <td>
                        @php
                        $typeColors = [
                            'text'    => 'bg-light text-dark',
                            'number'  => 'bg-primary bg-opacity-10 text-primary',
                            'select'  => 'bg-warning bg-opacity-10 text-warning',
                            'boolean' => 'bg-success bg-opacity-10 text-success',
                        ];
                        @endphp
                        <span class="badge {{ $typeColors[$field->field_type] ?? 'bg-secondary' }}">
                            {{ ucfirst($field->field_type) }}
                        </span>
                        @if($field->field_type === 'select' && $field->options)
                            <span class="text-muted ms-1" style="font-size:11px">
                                ({{ count($field->options) }} options)
                            </span>
                        @endif
                    </td>
                    <td>
                        @if($field->is_required)
                            <span class="badge bg-danger bg-opacity-10 text-danger">Required</span>
                        @else
                            <span class="text-muted small">Optional</span>
                        @endif
                    </td>
                    <td>
                        @if($field->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.custom-budget-fields.edit', $field) }}"
                               class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="POST"
                                  action="{{ route('admin.custom-budget-fields.destroy', $field) }}"
                                  id="delCbf{{ $field->id }}">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        onclick="confirmDeleteField({{ $field->id }}, '{{ addslashes($field->label) }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        No custom fields yet.
                        <a href="{{ route('admin.custom-budget-fields.create') }}">Add your first field</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-3 border-0" style="background:#FFFBEB">
    <div class="card-body py-2 px-3">
        <small class="text-muted">
            <i class="bi bi-info-circle"></i>
            Custom fields appear below standard line item columns on all budget entry forms.
            Deleting a field removes all saved values for that field across all budgets.
        </small>
    </div>
</div>

<script>
function confirmDeleteField(id, label) {
    Swal.fire({
        title: 'Delete field?',
        html: `<p class="text-muted">This will permanently remove <strong>${label}</strong> and all its saved values. This cannot be undone.</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#64748B',
        confirmButtonText: 'Delete Field',
        reverseButtons: true,
    }).then(r => { if (r.isConfirmed) document.getElementById('delCbf' + id).submit(); });
}
</script>

@endsection
