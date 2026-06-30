@extends('layouts.app')
@section('title', 'Audit Log')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Audit Log</h5>
        <p class="text-muted small mb-0">
            Complete record of all system activity
        </p>
    </div>
    <a href="{{ route('admin.audit-log.export', request()->query()) }}"
       class="btn btn-sm btn-outline-success">
        Export CSV
    </a>
</div>

{{-- Stats strip --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:var(--navy)"></div>
            <div class="stat-label">Events Today</div>
            <div class="stat-value">{{ $stats['today'] }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#6366F1"></div>
            <div class="stat-label">This Week</div>
            <div class="stat-value">{{ $stats['week'] }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#10B981"></div>
            <div class="stat-label">Logins Today</div>
            <div class="stat-value">{{ $stats['logins'] }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#F43F5E"></div>
            <div class="stat-label">Critical Events Today</div>
            <div class="stat-value" style="color:{{ $stats['critical'] > 0 ? '#F43F5E' : 'inherit' }}">
                {{ $stats['critical'] }}
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="chart-card mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control form-control-sm"
                   placeholder="Event, subject…">
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">User</label>
            <select name="user_id" class="form-select form-select-sm">
                <option value="">All Users</option>
                @foreach($users as $u)
                <option value="{{ $u->id }}"
                    {{ request('user_id') == $u->id ? 'selected' : '' }}>
                    {{ $u->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Module</label>
            <select name="module" class="form-select form-select-sm">
                <option value="">All Modules</option>
                @foreach($modules as $key => $label)
                <option value="{{ $key }}"
                    {{ request('module') === $key ? 'selected' : '' }}>
                    {{ $label }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Severity</label>
            <select name="severity" class="form-select form-select-sm">
                <option value="">All</option>
                @foreach($severities as $key => $cfg)
                <option value="{{ $key }}"
                    {{ request('severity') === $key ? 'selected' : '' }}>
                    {{ $cfg['label'] }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-1">
            <label class="form-label small fw-semibold mb-1">From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   class="form-control form-control-sm">
        </div>
        <div class="col-md-1">
            <label class="form-label small fw-semibold mb-1">To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                   class="form-control form-control-sm">
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-sm w-100"
                    style="background:var(--navy);color:#fff;border-radius:8px">
                Filter
            </button>
        </div>
    </div>
    @if(request()->hasAny(['search','user_id','module','severity','date_from','date_to']))
    <div class="mt-2">
        <a href="{{ route('admin.audit-log.index') }}"
           class="small text-muted">Clear filters</a>
    </div>
    @endif
</form>

{{-- Log table --}}
<div class="chart-card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead style="font-size:11px;text-transform:uppercase;
                          letter-spacing:.5px;color:var(--slate)">
                <tr>
                    <th style="width:140px">Date / Time</th>
                    <th>User</th>
                    <th>Module</th>
                    <th>Event</th>
                    <th>Subject</th>
                    <th>Severity</th>
                    <th>IP</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                @php $sev = $log->severityConfig(); @endphp
                <tr>
                    <td style="white-space:nowrap">
                        <div style="font-size:12px;font-weight:600;color:var(--navy)">
                            {{ $log->created_at->format('d M Y') }}
                        </div>
                        <div style="font-size:11px;color:var(--slate)">
                            {{ $log->created_at->format('H:i:s') }}
                        </div>
                    </td>
                    <td>
                        <div style="font-size:12px;font-weight:600">
                            {{ $log->user?->name ?? 'System' }}
                        </div>
                        @if($log->user)
                        <div style="font-size:10px;color:var(--slate)">
                            {{ $log->user->email }}
                        </div>
                        @endif
                    </td>
                    <td>
                        <span style="padding:2px 8px;border-radius:4px;font-size:11px;
                                     background:#F1F5F9;color:var(--navy);font-weight:600">
                            {{ $log->moduleLabel() }}
                        </span>
                    </td>
                    <td>
                        <span style="font-size:12px;font-family:monospace;color:var(--slate)">
                            {{ $log->event }}
                        </span>
                    </td>
                    <td style="max-width:200px">
                        <div style="font-size:12px;overflow:hidden;
                                    text-overflow:ellipsis;white-space:nowrap">
                            {{ $log->subject_label ?? '—' }}
                        </div>
                    </td>
                    <td>
                        <span style="padding:2px 8px;border-radius:20px;font-size:11px;
                                     font-weight:600;background:{{ $sev['bg'] }};
                                     color:{{ $sev['color'] }}">
                            {{ $sev['label'] }}
                        </span>
                    </td>
                    <td style="font-size:11px;color:var(--slate)">
                        {{ $log->ip_address ?? '—' }}
                    </td>
                    <td>
                        <a href="{{ route('admin.audit-log.show', $log->id) }}"
                           class="btn btn-sm btn-outline-secondary"
                           style="font-size:11px;padding:2px 10px">
                            Detail
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-5">
                        No audit records found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3 px-2">
    {{ $logs->appends(request()->query())->links('pagination::bootstrap-5') }}
</div>
</div>

@endsection
