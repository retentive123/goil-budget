@extends('layouts.app')
@section('title', 'Ex-pump Templates')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0">Ex-pump Price Templates</h4>
    <a href="{{ route('admin.expump-templates.create') }}" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i>New Template
    </a>
</div>

@push('scripts')
<script>
function confirmActivate(id, name) {
    Swal.fire({
        title: 'Activate template?',
        html: `<strong>${name}</strong> will become the active ex-pump template.<br>Any currently active template will be deactivated.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#F59E0B',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, activate',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then(result => {
        if (result.isConfirmed) document.getElementById(`activateForm-${id}`).submit();
    });
}

function confirmDeactivate(id, name) {
    Swal.fire({
        title: 'Deactivate template?',
        html: `<strong>${name}</strong> will be saved but no longer active.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#6B7280',
        cancelButtonColor: '#1B2A4A',
        confirmButtonText: 'Yes, deactivate',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then(result => {
        if (result.isConfirmed) document.getElementById(`deactivateForm-${id}`).submit();
    });
}

function confirmDelete(id, name) {
    Swal.fire({
        title: 'Delete template?',
        html: `<strong>${name}</strong> and all its values will be permanently deleted.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then(result => {
        if (result.isConfirmed) document.getElementById(`deleteForm-${id}`).submit();
    });
}
</script>
@endpush

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($templates->isEmpty())
<div class="card shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="fas fa-gas-pump fa-2x mb-3 d-block"></i>
        No templates yet. Create one to get started.
    </div>
</div>
@else
<div class="row g-3">
    @foreach($templates as $tpl)
    <div class="col-md-4">
        <div class="card shadow-sm h-100 {{ $tpl->is_active ? 'border-warning' : '' }}">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-2">
                    <div>
                        <h6 class="fw-bold mb-1">{{ $tpl->name }}</h6>
                        @if($tpl->is_active)
                            <span class="badge" style="background:#FEF9EC;color:#92400E;font-size:10px;">
                                <i class="fas fa-check-circle me-1"></i>ACTIVE
                            </span>
                        @endif
                    </div>
                </div>
                @if($tpl->description)
                    <p class="text-muted small mb-3">{{ $tpl->description }}</p>
                @endif
                <div class="d-flex gap-2 flex-wrap mt-auto">
                    <a href="{{ route('admin.expump-templates.show', $tpl) }}"
                       class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye me-1"></i>View
                    </a>
                    <a href="{{ route('admin.expump-templates.edit', $tpl) }}"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-edit me-1"></i>Edit
                    </a>

                    @if(!$tpl->is_active)
                    <form method="POST" action="{{ route('admin.expump-templates.activate', $tpl) }}"
                          id="activateForm-{{ $tpl->id }}">
                        @csrf
                        <button type="button" class="btn btn-sm btn-warning text-white"
                            onclick="confirmActivate({{ $tpl->id }}, '{{ addslashes($tpl->name) }}')">
                            Activate
                        </button>
                    </form>
                    @else
                    <form method="POST" action="{{ route('admin.expump-templates.deactivate', $tpl) }}"
                          id="deactivateForm-{{ $tpl->id }}">
                        @csrf
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                            onclick="confirmDeactivate({{ $tpl->id }}, '{{ addslashes($tpl->name) }}')">
                            Deactivate
                        </button>
                    </form>
                    @endif

                    <form method="POST" action="{{ route('admin.expump-templates.destroy', $tpl) }}"
                          id="deleteForm-{{ $tpl->id }}" class="ms-auto">
                        @csrf @method('DELETE')
                        <button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="confirmDelete({{ $tpl->id }}, '{{ addslashes($tpl->name) }}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection
