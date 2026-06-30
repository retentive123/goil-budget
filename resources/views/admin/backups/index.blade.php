@extends('layouts.app')
@section('title', 'Database Backups')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Database Backups</h5>
        <p class="text-muted small mb-0">
            Manual and scheduled backups of all budget data.
            Next scheduled: <strong>{{ $nextScheduled }}</strong>
        </p>
    </div>
    <button type="button" onclick="document.getElementById('newBackupPanel').classList.toggle('d-none')"
            class="btn btn-sm"
            style="background:var(--navy);color:#fff;border-radius:8px;padding:8px 18px">
        + Create Backup Now
    </button>
</div>

{{-- New backup form --}}
<div id="newBackupPanel" class="d-none chart-card mb-4">
    <div style="font-size:13px;font-weight:600;color:var(--navy);margin-bottom:12px">
        Create Manual Backup
    </div>
    <form method="POST" action="{{ route('admin.backups.store') }}">
        @csrf
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label small fw-semibold mb-1">Notes (optional)</label>
                <input type="text" name="notes"
                       class="form-control form-control-sm"
                       placeholder="e.g. Pre-deployment backup, before major changes…">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-success">
                    Start Backup
                </button>
            </div>
        </div>
        <div class="form-text mt-1">
            ⚠ Backup may take a few seconds depending on database size.
            The page will reload when complete.
        </div>
    </form>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:var(--navy)"></div>
            <div class="stat-label">Total Backups</div>
            <div class="stat-value">{{ $backups->total() }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#10B981"></div>
            <div class="stat-label">Total Size</div>
            <div class="stat-value" style="font-size:18px">
                @php
                    $mb = round($totalSize / 1048576, 1);
                    echo $mb >= 1024 ? round($mb/1024,2).' GB' : $mb.' MB';
                @endphp
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#10B981"></div>
            <div class="stat-label">Last Backup</div>
            <div class="stat-value" style="font-size:14px">
                {{ $backups->first()?->created_at->diffForHumans() ?? 'Never' }}
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#6366F1"></div>
            <div class="stat-label">Schedule</div>
            <div class="stat-value" style="font-size:14px">
                {{ ucfirst(\App\Models\SystemSetting::get('backup_frequency','daily')) }}
            </div>
        </div>
    </div>
</div>

{{-- Backups table --}}
<div class="chart-card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead style="font-size:11px;text-transform:uppercase;
                          letter-spacing:.5px;color:var(--slate)">
                <tr>
                    <th>Filename</th>
                    <th>Type</th>
                    <th>Size</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th>Date</th>
                    <th>Notes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($backups as $backup)
                <tr>
                    <td style="font-family:monospace;font-size:11px;color:var(--navy)">
                        {{ $backup->filename }}
                    </td>
                    <td>
                        <span style="padding:2px 8px;border-radius:4px;font-size:11px;
                                     font-weight:600;
                                     background:{{ $backup->type==='manual'?'#DBEAFE':'#F0FDF4' }};
                                     color:{{ $backup->type==='manual'?'#1E40AF':'#065F46' }}">
                            {{ ucfirst($backup->type) }}
                        </span>
                    </td>
                    <td class="small">
                        {{ $backup->sizeForHumans() }}
                    </td>
                    <td>
                        <span style="padding:2px 8px;border-radius:20px;font-size:11px;
                                     font-weight:600;
                                     background:{{
                                        match($backup->status) {
                                            'completed' => '#D1FAE5',
                                            'failed'    => '#FEE2E2',
                                            'running'   => '#FEF3C7',
                                            default     => '#F1F5F9'
                                        }
                                     }};
                                     color:{{
                                        match($backup->status) {
                                            'completed' => '#065F46',
                                            'failed'    => '#991B1B',
                                            'running'   => '#92400E',
                                            default     => '#475569'
                                        }
                                     }}">
                            {{ ucfirst($backup->status) }}
                        </span>
                    </td>
                    <td class="small">
                        {{ $backup->createdBy?->name ?? 'System' }}
                    </td>
                    <td class="small text-muted">
                        {{ $backup->created_at->format('d M Y H:i') }}
                        <div style="font-size:10px">{{ $backup->created_at->diffForHumans() }}</div>
                    </td>
                    <td class="small text-muted">
                        {{ $backup->notes ?? '—' }}
                        @if($backup->error_message)
                        <div style="color:#991B1B;font-size:10px">
                            {{ $backup->error_message }}
                        </div>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            @if($backup->status === 'completed')
                            <a href="{{ route('admin.backups.download', $backup) }}"
                               class="btn btn-sm btn-outline-success"
                               style="font-size:11px;padding:2px 10px">
                                ↓ Download
                            </a>
                            @endif
                            <form method="POST"
                                  action="{{ route('admin.backups.destroy', $backup) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"
                                        style="font-size:11px;padding:2px 10px"
                                        onclick="return confirm('Delete this backup?')">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-5">
                        No backups yet.
                        <form method="POST" action="{{ route('admin.backups.store') }}"
                              class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary ms-2">
                                Create first backup
                            </button>
                        </form>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $backups->links() }}</div>
</div>

@endsection
