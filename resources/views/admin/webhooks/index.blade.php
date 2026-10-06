@extends('layouts.app')
@section('title', 'Webhooks')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Webhooks</h5>
        <p class="text-muted small mb-0">POST to external URLs when budget events occur.</p>
    </div>
    <a href="{{ route('admin.webhooks.create') }}" class="btn btn-primary btn-sm">+ Add Webhook</a>
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
                    <th>Name</th>
                    <th>URL</th>
                    <th>Events</th>
                    <th>Last Fired</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($webhooks as $wh)
                <tr>
                    <td class="fw-semibold">{{ $wh->name }}</td>
                    <td>
                        <span class="text-muted" style="font-size:12px;word-break:break-all">
                            {{ Str::limit($wh->url, 55) }}
                        </span>
                    </td>
                    <td>
                        @foreach($wh->events as $ev)
                            <span class="badge bg-light text-dark border" style="font-size:11px">
                                {{ $events[$ev] ?? $ev }}
                            </span>
                        @endforeach
                    </td>
                    <td class="text-muted" style="font-size:12px;white-space:nowrap">
                        @if($wh->last_fired_at)
                            {{ $wh->last_fired_at->diffForHumans() }}
                            @if($wh->last_status_code)
                                <span class="badge {{ $wh->last_status_code < 300 ? 'bg-success' : 'bg-danger' }} ms-1">
                                    {{ $wh->last_status_code }}
                                </span>
                            @endif
                        @else
                            <span class="text-muted">Never</span>
                        @endif
                    </td>
                    <td>
                        @if($wh->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.webhooks.edit', $wh) }}"
                               class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="POST" action="{{ route('admin.webhooks.test', $wh) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary"
                                        title="Send test payload">Test</button>
                            </form>
                            <form method="POST" action="{{ route('admin.webhooks.destroy', $wh) }}"
                                  id="delWh{{ $wh->id }}">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        onclick="confirmDelete({{ $wh->id }}, '{{ addslashes($wh->name) }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        No webhooks configured yet.
                        <a href="{{ route('admin.webhooks.create') }}">Add one</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    Swal.fire({
        title: 'Delete webhook?',
        html: `<p class="text-muted">Remove <strong>${name}</strong>? This cannot be undone.</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#64748B',
        confirmButtonText: 'Delete',
        reverseButtons: true,
    }).then(r => { if (r.isConfirmed) document.getElementById('delWh' + id).submit(); });
}
</script>

@endsection
