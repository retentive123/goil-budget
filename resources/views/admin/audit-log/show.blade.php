@extends('layouts.app')
@section('title', 'Audit Entry Detail')
@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.audit-log.index') }}"
       class="text-muted text-decoration-none small">← Audit Log</a>
</div>

@php $sev = $log->severityConfig(); @endphp

<div class="row g-4">

    {{-- Main detail --}}
    <div class="col-md-8">
        <div class="chart-card">

            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <div style="font-size:18px;font-weight:700;color:var(--navy);
                                font-family:monospace">
                        {{ $log->event }}
                    </div>
                    <div style="font-size:13px;color:var(--slate);margin-top:4px">
                        {{ $log->moduleLabel() }} / {{ $log->action }}
                    </div>
                </div>
                <span style="padding:4px 14px;border-radius:20px;font-size:12px;
                             font-weight:700;background:{{ $sev['bg'] }};
                             color:{{ $sev['color'] }}">
                    {{ $sev['label'] }}
                </span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div style="font-size:11px;color:var(--slate);font-weight:600;
                                text-transform:uppercase;letter-spacing:.5px">
                        Date & Time
                    </div>
                    <div style="font-size:14px;font-weight:600;margin-top:4px">
                        {{ $log->created_at->format('d M Y \a\t H:i:s') }}
                    </div>
                    <div style="font-size:12px;color:var(--slate)">
                        {{ $log->created_at->diffForHumans() }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="font-size:11px;color:var(--slate);font-weight:600;
                                text-transform:uppercase;letter-spacing:.5px">
                        Performed By
                    </div>
                    <div style="font-size:14px;font-weight:600;margin-top:4px">
                        {{ $log->user?->name ?? 'System' }}
                    </div>
                    <div style="font-size:12px;color:var(--slate)">
                        {{ $log->user?->email ?? '' }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="font-size:11px;color:var(--slate);font-weight:600;
                                text-transform:uppercase;letter-spacing:.5px">
                        Subject
                    </div>
                    <div style="font-size:14px;font-weight:600;margin-top:4px">
                        {{ $log->subject_label ?? '—' }}
                    </div>
                    @if($log->subject_type)
                    <div style="font-size:11px;color:var(--slate);font-family:monospace">
                        {{ class_basename($log->subject_type) }}
                        @if($log->subject_id) #{{ $log->subject_id }} @endif
                    </div>
                    @endif
                </div>
                <div class="col-md-6">
                    <div style="font-size:11px;color:var(--slate);font-weight:600;
                                text-transform:uppercase;letter-spacing:.5px">
                        Network Info
                    </div>
                    <div style="font-size:13px;font-family:monospace;margin-top:4px">
                        {{ $log->ip_address ?? '—' }}
                    </div>
                    <div style="font-size:11px;color:var(--slate);
                                overflow:hidden;text-overflow:ellipsis">
                        {{ $log->user_agent ?? '—' }}
                    </div>
                </div>
            </div>

            {{-- Old vs New values --}}
            @if($log->old_values || $log->new_values)
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;
                        letter-spacing:.5px;color:var(--slate);margin-bottom:10px">
                Changes
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0"
                       style="font-size:12px">
                    <thead style="background:#F8FAFC">
                        <tr>
                            <th>Field</th>
                            <th style="color:#F43F5E">Before</th>
                            <th style="color:#10B981">After</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $old  = $log->old_values ?? [];
                            $new  = $log->new_values ?? [];
                            $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
                        @endphp
                        @foreach($keys as $field)
                        <tr>
                            <td class="fw-semibold">{{ $field }}</td>
                            <td style="color:#F43F5E;font-family:monospace">
                                {{ isset($old[$field]) ? (is_array($old[$field]) ? json_encode($old[$field]) : $old[$field]) : '—' }}
                            </td>
                            <td style="color:#10B981;font-family:monospace">
                                {{ isset($new[$field]) ? (is_array($new[$field]) ? json_encode($new[$field]) : $new[$field]) : '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            {{-- Meta --}}
            @if($log->meta)
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;
                        letter-spacing:.5px;color:var(--slate);
                        margin-top:20px;margin-bottom:10px">
                Additional Context
            </div>
            <div style="background:#F8FAFC;border-radius:8px;
                        padding:14px;font-family:monospace;font-size:12px">
                @foreach($log->meta as $key => $val)
                <div class="d-flex gap-3 mb-1">
                    <span style="color:var(--slate);min-width:120px">{{ $key }}</span>
                    <span style="color:var(--navy)">
                        {{ is_array($val) ? json_encode($val) : $val }}
                    </span>
                </div>
                @endforeach
            </div>
            @endif

        </div>
    </div>

    {{-- Sidebar: same user recent activity --}}
    <div class="col-md-4">
        @if($log->user)
        <div class="chart-card">
            <div class="chart-title">
                Recent Activity — {{ $log->user->name }}
            </div>
            @php
                $recent = \App\Models\SystemAuditLog::where('user_id', $log->user_id)
                    ->where('id', '!=', $log->id)
                    ->orderByDesc('created_at')
                    ->limit(10)
                    ->get();
            @endphp
            @forelse($recent as $r)
            @php $rs = $r->severityConfig(); @endphp
            <div style="padding:8px 0;border-bottom:1px solid var(--border);
                        font-size:12px">
                <div class="d-flex justify-content-between align-items-start">
                    <span style="font-family:monospace;color:var(--navy)">
                        {{ $r->event }}
                    </span>
                    <span style="padding:1px 6px;border-radius:10px;font-size:10px;
                                 background:{{ $rs['bg'] }};color:{{ $rs['color'] }}">
                        {{ $rs['label'] }}
                    </span>
                </div>
                <div style="color:var(--slate);font-size:11px;margin-top:2px">
                    {{ $r->subject_label ?? '—' }}
                    &middot; {{ $r->created_at->diffForHumans() }}
                </div>
                <a href="{{ route('admin.audit-log.show', $r->id) }}"
                   style="font-size:10px;color:var(--navy)">View →</a>
            </div>
            @empty
            <div class="text-muted small">No other activity.</div>
            @endforelse
        </div>
        @endif
    </div>

</div>

@endsection
