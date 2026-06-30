@extends('layouts.app')
@section('title', 'User — ' . $user->name)
@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.users.index') }}"
       class="text-muted text-decoration-none small">← Users</a>
    <span class="text-muted">/</span>
    <span class="small">{{ $user->name }}</span>
</div>

<div class="row g-4">

    {{-- Left — Profile --}}
    <div class="col-md-4">

        {{-- Avatar card --}}
        <div class="chart-card mb-4 text-center">
            <div style="width:72px;height:72px;border-radius:50%;
                        background:var(--navy);color:var(--gold);
                        font-size:26px;font-weight:800;
                        display:flex;align-items:center;justify-content:center;
                        margin:0 auto 16px">
                {{ strtoupper(substr($user->name,0,2)) }}
            </div>
            <div style="font-size:18px;font-weight:700;color:var(--navy)">
                {{ $user->name }}
            </div>
            <div style="font-size:13px;color:var(--slate);margin-top:4px">
                {{ $user->email }}
            </div>

            @foreach($user->roles as $role)
            <span style="display:inline-block;margin-top:10px;
                         padding:4px 14px;border-radius:20px;font-size:12px;
                         font-weight:700;background:#EFF6FF;color:#1E40AF">
                {{ ucfirst(str_replace('_',' ',$role->name)) }}
            </span>
            @endforeach

            <div class="mt-3">
                <span style="padding:3px 12px;border-radius:20px;font-size:12px;
                             font-weight:600;
                             background:{{ $user->is_active?'#D1FAE5':'#FEE2E2' }};
                             color:{{ $user->is_active?'#065F46':'#991B1B' }}">
                    {{ $user->is_active ? 'Active' : 'Deactivated' }}
                </span>
            </div>

            <div class="d-flex gap-2 justify-content-center mt-4">
                <a href="{{ route('admin.users.edit', $user) }}"
                   class="btn btn-sm"
                   style="background:var(--navy);color:#fff;border-radius:8px">
                    Edit User
                </a>
                @if($user->id !== auth()->id())
                <form method="POST"
                      action="{{ route('admin.users.toggle-active', $user) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm {{ $user->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                            style="border-radius:8px">
                        {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                </form>
                @endif
            </div>
        </div>

        {{-- Details --}}
        <div class="chart-card mb-4">
            <div class="chart-title">Account Details</div>
            <div class="row g-2" style="font-size:13px">
                <div class="col-5" style="color:var(--slate)">Employee ID</div>
                <div class="col-7 fw-semibold">{{ $user->employee_id ?? '—' }}</div>

                <div class="col-5" style="color:var(--slate)">Phone</div>
                <div class="col-7">{{ $user->phone ?? '—' }}</div>

                <div class="col-5" style="color:var(--slate)">Department</div>
                <div class="col-7 fw-semibold">
                    {{ $user->department?->name ?? '—' }}
                </div>

                <div class="col-5" style="color:var(--slate)">Last Login</div>
                <div class="col-7">
                    {{ $user->last_login_at?->format('d M Y H:i') ?? 'Never' }}
                    @if($user->last_login_at)
                    <div style="font-size:10px;color:var(--slate)">
                        {{ $user->last_login_at->diffForHumans() }}
                    </div>
                    @endif
                </div>

                <div class="col-5" style="color:var(--slate)">Member Since</div>
                <div class="col-7">{{ $user->created_at->format('d M Y') }}</div>

                <div class="col-5" style="color:var(--slate)">Pwd Changed</div>
                <div class="col-7">
                    {{ $user->password_changed_at?->format('d M Y') ?? 'Never' }}
                </div>
            </div>
        </div>

        {{-- Permissions (direct) --}}
        @php $directPerms = $user->getDirectPermissions(); @endphp
        @if($directPerms->count())
        <div class="chart-card">
            <div class="chart-title">Direct Permissions</div>
            <div style="display:flex;flex-wrap:wrap;gap:6px">
                @foreach($directPerms as $perm)
                <span style="background:#F1F5F9;border-radius:4px;padding:2px 8px;
                             font-size:11px;font-family:monospace;color:var(--navy)">
                    {{ $perm->name }}
                </span>
                @endforeach
            </div>
        </div>
        @endif

    </div>

    {{-- Right — Activity --}}
    <div class="col-md-8">

        {{-- Budget submissions --}}
        @php
            $submissions = \App\Models\BudgetVersion::where('submitted_by', $user->id)
                ->with('department','period')
                ->orderByDesc('submitted_at')
                ->limit(10)->get();
        @endphp

        <div class="chart-card mb-4">
            <div class="chart-title">Budget Submissions</div>
            @if($submissions->count())
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead style="font-size:11px;text-transform:uppercase;
                                  letter-spacing:.5px;color:var(--slate)">
                        <tr>
                            <th>Period</th>
                            <th>Department</th>
                            <th>Version</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($submissions as $sub)
                        <tr>
                            <td class="small">{{ $sub->period->name }}</td>
                            <td class="small fw-semibold">{{ $sub->department->name }}</td>
                            <td class="small">v{{ $sub->version_number }}</td>
                            <td>
                                <span style="padding:1px 8px;border-radius:20px;font-size:10px;
                                             font-weight:600;
                                             background:{{
                                                match($sub->status){
                                                    'approved'=>'#D1FAE5','rejected'=>'#FEE2E2',
                                                    'submitted'=>'#DBEAFE','under_review'=>'#FEF3C7',
                                                    default=>'#F1F5F9'
                                                }
                                             }};
                                             color:{{
                                                match($sub->status){
                                                    'approved'=>'#065F46','rejected'=>'#991B1B',
                                                    'submitted'=>'#1E40AF','under_review'=>'#92400E',
                                                    default=>'#475569'
                                                }
                                             }}">
                                    {{ ucfirst(str_replace('_',' ',$sub->status)) }}
                                </span>
                            </td>
                            <td class="small text-muted">
                                {{ $sub->submitted_at?->format('d M Y') ?? '—' }}
                            </td>
                            <td>
                                <a href="{{ route('approvals.history',$sub) }}"
                                   style="font-size:11px;color:var(--navy)">
                                    View →
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-muted small text-center py-3">
                No budget submissions yet.
            </div>
            @endif
        </div>

        {{-- Approval decisions --}}
        @php
            $decisions = \App\Models\ApprovalDecision::where('decided_by', $user->id)
                ->with('budgetVersion.department','budgetVersion.period','stage')
                ->orderByDesc('decided_at')
                ->limit(10)->get();
        @endphp

        <div class="chart-card mb-4">
            <div class="chart-title">Approval Decisions</div>
            @if($decisions->count())
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead style="font-size:11px;text-transform:uppercase;
                                  letter-spacing:.5px;color:var(--slate)">
                        <tr>
                            <th>Department</th>
                            <th>Period</th>
                            <th>Stage</th>
                            <th>Decision</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($decisions as $dec)
                        <tr>
                            <td class="small fw-semibold">
                                {{ $dec->budgetVersion->department->name }}
                            </td>
                            <td class="small">{{ $dec->budgetVersion->period->name }}</td>
                            <td class="small">{{ $dec->stage->name }}</td>
                            <td>
                                <span style="padding:1px 8px;border-radius:20px;font-size:10px;
                                             font-weight:600;
                                             background:{{ $dec->decision==='approved'?'#D1FAE5':($dec->decision==='rejected'?'#FEE2E2':'#FEF3C7') }};
                                             color:{{ $dec->decision==='approved'?'#065F46':($dec->decision==='rejected'?'#991B1B':'#92400E') }}">
                                    {{ ucfirst($dec->decision) }}
                                </span>
                            </td>
                            <td class="small text-muted">
                                {{ $dec->decided_at->format('d M Y') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-muted small text-center py-3">
                No approval decisions yet.
            </div>
            @endif
        </div>

        {{-- Recent audit log --}}
        @php
            $auditLogs = \App\Models\SystemAuditLog::where('user_id',$user->id)
                ->orderByDesc('created_at')->limit(8)->get();
        @endphp

        <div class="chart-card">
            <div class="chart-title">Recent Activity (Audit Log)</div>
            @if($auditLogs->count())
            @foreach($auditLogs as $log)
            @php $sev = $log->severityConfig(); @endphp
            <div style="display:flex;align-items:start;gap:12px;padding:8px 0;
                        border-bottom:1px solid var(--border);font-size:12px">
                <span style="padding:2px 8px;border-radius:4px;font-size:10px;
                             font-weight:600;white-space:nowrap;
                             background:{{ $sev['bg'] }};color:{{ $sev['color'] }}">
                    {{ $sev['label'] }}
                </span>
                <div class="flex-grow-1">
                    <div style="font-family:monospace;color:var(--navy)">
                        {{ $log->event }}
                    </div>
                    <div style="color:var(--slate)">
                        {{ $log->subject_label ?? '—' }}
                    </div>
                </div>
                <div style="color:var(--slate);white-space:nowrap">
                    {{ $log->created_at->diffForHumans() }}
                </div>
            </div>
            @endforeach
            @else
            <div class="text-muted small text-center py-3">No activity recorded.</div>
            @endif
        </div>

    </div>

</div>

@endsection
