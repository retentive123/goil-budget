@extends('layouts.app')
@section('title', 'Balance Sheet Layouts')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Balance Sheet Layouts</h5>
        <p class="text-muted small mb-0">
            Configure how the Balance Sheet is structured in financial reports.
            Only one layout can be active at a time.
        </p>
    </div>
    <a href="{{ route('admin.balance-sheet-configs.create') }}"
       class="btn btn-sm fw-semibold"
       style="background:#1B2A4A;color:#fff;border-radius:8px;border:none">
        <i class="fas fa-plus me-1"></i> New Layout
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($configs->isEmpty())
<div class="chart-card text-center py-5 text-muted">
    <i class="fas fa-balance-scale fa-3x mb-3 opacity-25"></i>
    <p class="mb-2">No layouts defined yet.</p>
    <a href="{{ route('admin.balance-sheet-configs.create') }}"
       class="btn btn-sm" style="background:#1B2A4A;color:#fff;border-radius:8px">
        Create your first layout
    </a>
</div>
@else
<div class="row g-3">
    @foreach($configs as $config)
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;overflow:hidden">
            <div style="height:4px;background:{{ $config->is_active ? '#10B981' : '#E2E8F0' }}"></div>
            <div class="card-body p-4">
                <div class="d-flex align-items-start justify-content-between mb-2">
                    <h6 class="fw-bold mb-0" style="color:#1B2A4A">{{ $config->name }}</h6>
                    @if($config->is_active)
                    <span class="badge" style="background:#DCFCE7;color:#065F46;font-size:10px;border-radius:20px">
                        <i class="fas fa-circle me-1" style="font-size:7px"></i>Active
                    </span>
                    @endif
                </div>
                <p class="small text-muted mb-3">
                    {{ $config->lines_count }} {{ Str::plural('line', $config->lines_count) }} defined
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.balance-sheet-configs.edit', $config) }}"
                       class="btn btn-sm"
                       style="background:#1B2A4A;color:#fff;border-radius:7px;font-size:12px">
                        <i class="fas fa-edit me-1"></i>Edit
                    </a>

                    @if($config->is_active)
                    <form method="POST" id="deactivateConfig{{ $config->id }}"
                          action="{{ route('admin.balance-sheet-configs.deactivate', $config) }}">
                        @csrf
                        <button type="button" class="btn btn-sm"
                                style="background:#F1F5F9;color:#475569;border-radius:7px;font-size:12px;border:none"
                                onclick="confirmDeactivate({{ $config->id }}, '{{ addslashes($config->name) }}')">
                            <i class="fas fa-toggle-off me-1"></i>Deactivate
                        </button>
                    </form>
                    @else
                    <form method="POST" id="activateConfig{{ $config->id }}"
                          action="{{ route('admin.balance-sheet-configs.activate', $config) }}">
                        @csrf
                        <button type="button" class="btn btn-sm"
                                style="background:#DCFCE7;color:#065F46;border-radius:7px;font-size:12px;border:none"
                                onclick="confirmActivate({{ $config->id }}, '{{ addslashes($config->name) }}')">
                            <i class="fas fa-toggle-on me-1"></i>Activate
                        </button>
                    </form>
                    @endif

                    <form method="POST"
                          action="{{ route('admin.balance-sheet-configs.destroy', $config) }}"
                          id="delConfig{{ $config->id }}">
                        @csrf @method('DELETE')
                        <button type="button" class="btn btn-sm"
                                style="background:#FEE2E2;color:#991B1B;border-radius:7px;font-size:12px;border:none"
                                onclick="deleteConfig({{ $config->id }}, '{{ addslashes($config->name) }}')">
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

<div class="mt-4 p-3 rounded-3 small text-muted" style="background:#F8FAFC;border:1px solid #E2E8F0">
    <i class="fas fa-info-circle me-1" style="color:#1B2A4A"></i>
    When a layout is <strong>active</strong>, the Balance Sheet tab in Financial Reports renders using
    that layout instead of the default assets / liabilities grouping.
    If no layout is active, the standard view is used.
</div>

<script>
function confirmActivate(id, name) {
    Swal.fire({
        title: 'Activate layout?',
        html: `<span class="text-muted">This will make <strong>${name}</strong> the active balance sheet layout and deactivate any currently active one.</span>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#065F46',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, activate',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then(r => {
        if (r.isConfirmed) document.getElementById('activateConfig' + id).submit();
    });
}

function confirmDeactivate(id, name) {
    Swal.fire({
        title: 'Deactivate layout?',
        html: `<span class="text-muted">The standard balance sheet will be used instead of <strong>${name}</strong>.</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#92400E',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, deactivate',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then(r => {
        if (r.isConfirmed) document.getElementById('deactivateConfig' + id).submit();
    });
}

function deleteConfig(id, name) {
    Swal.fire({
        title: 'Delete layout?',
        html: `<span class="text-muted">Layout: <strong>${name}</strong></span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#991B1B',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then(r => {
        if (r.isConfirmed) document.getElementById('delConfig' + id).submit();
    });
}
</script>

@endsection
