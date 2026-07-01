@extends('layouts.app')
@section('title', 'Audit Log')

@section('content')

{{-- Bootstrap Table CSS (loaded here since layout has no @stack('styles')) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-table@1.22.4/dist/bootstrap-table.min.css">

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Audit Log</h5>
        <p class="text-muted small mb-0">
            Complete record of all system activity
        </p>
    </div>
    <a href="{{ route('admin.audit-log.export', request()->query()) }}"
       class="btn btn-sm btn-outline-success">
        <i class="fas fa-file-csv me-1"></i>Export All (CSV)
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

{{-- Server-side filters --}}
<form method="GET" class="chart-card mb-4">
    <div class="d-flex align-items-center mb-2 gap-2">
        <i class="fas fa-filter text-muted" style="font-size:12px"></i>
        <span class="fw-semibold small text-muted">Filter Records</span>
        @if(request()->hasAny(['search','user_id','module','severity','date_from','date_to']))
        <a href="{{ route('admin.audit-log.index') }}" class="small text-muted ms-2">
            <i class="fas fa-times-circle me-1"></i>Clear filters
        </a>
        @endif
    </div>
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
</form>

{{-- Log table --}}
<div class="chart-card">

    {{-- Table controls: per-page selector --}}
    <div class="d-flex align-items-center gap-2 mb-3">
        <label class="small text-muted mb-0" for="perPageSelect">Show</label>
        <select id="perPageSelect" class="form-select form-select-sm" style="width:auto">
            @foreach([10, 25, 50, 100] as $n)
            <option value="{{ $n }}" {{ request('per_page', 10) == $n ? 'selected' : '' }}>
                {{ $n }}
            </option>
            @endforeach
        </select>
        <span class="small text-muted">entries per page</span>
        <span class="ms-auto small text-muted">
            Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }} records
        </span>
    </div>

    <div class="table-responsive">
        <table
            id="auditTable"
            class="table table-sm table-hover mb-0"
            data-toggle="table"
            data-search="true"
            data-search-placeholder="Quick search on this page…"
            data-show-columns="true"
            data-show-columns-toggle-all="true"
            data-show-export="true"
            data-export-types='["copy","csv","excel","pdf","json"]'
            data-export-options='{"fileName":"audit-log-{{ now()->format('Y-m-d') }}"}'
            data-show-search-clear-button="true"
            data-search-align="left"
            data-buttons-class="btn-sm btn-outline-secondary"
            data-minimum-count-columns="1"
        >
            <thead style="font-size:11px;text-transform:uppercase;
                          letter-spacing:.5px;color:var(--slate)">
                <tr>
                    <th data-field="datetime"  data-title="Date / Time"  style="width:140px">Date / Time</th>
                    <th data-field="user"      data-title="User">User</th>
                    <th data-field="module"    data-title="Module">Module</th>
                    <th data-field="event"     data-title="Event">Event</th>
                    <th data-field="subject"   data-title="Subject">Subject</th>
                    <th data-field="severity"  data-title="Severity">Severity</th>
                    <th data-field="ip"        data-title="IP Address">IP</th>
                    <th data-field="actions"   data-title="Actions"
                        data-switchable="false" data-visible-export="false"></th>
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

@push('scripts')
{{-- jQuery (required by tableexport plugin) --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
{{-- Bootstrap Table --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.22.4/dist/bootstrap-table.min.js"></script>
{{-- Export extension dependencies --}}
<script src="https://cdn.jsdelivr.net/npm/tableexport.jquery.plugin@1.10.21/libs/jsPDF/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tableexport.jquery.plugin@1.10.21/tableExport.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.22.4/dist/extensions/export/bootstrap-table-export.min.js"></script>
<script>
$(function () {
    $('#auditTable').bootstrapTable();

    // Per-page selector: redirect with updated per_page param
    $('#perPageSelect').on('change', function () {
        const url = new URL(window.location.href);
        url.searchParams.set('per_page', this.value);
        url.searchParams.delete('page');
        window.location.href = url.toString();
    });
});
</script>
@endpush
