@extends('layouts.app')
@section('title', 'Submission Deadline Overrides')
@section('content')

<div class="mb-4">
    <h5 class="fw-bold mb-0">Submission Deadline Overrides</h5>
    <p class="text-muted small mb-0">
        Grant departments permission to submit after the deadline.
        @if($deadlineDays > 0 && $deadline)
            Current deadline for {{ $period?->name }}:
            <strong>{{ $deadline->format('d M Y H:i') }}</strong>
            ({{ $deadlineDays }} days after period opened).
        @elseif($deadlineDays === 0)
            <span style="color:#10B981">No deadline enforced (budget_entry_deadline_days = 0).</span>
        @endif
    </p>
</div>

{{-- Period selector --}}
<form method="GET" class="chart-card mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Period</label>
            <select name="period_id" class="form-select form-select-sm"
                    onchange="this.form.submit()">
                @foreach($periods as $p)
                <option value="{{ $p->id }}"
                    {{ $period?->id == $p->id ? 'selected' : '' }}>
                    {{ $p->name }}
                </option>
                @endforeach
            </select>
        </div>
    </div>
</form>

<div class="row g-4">

{{-- Left: dept status list --}}
<div class="col-md-7">
    <div class="chart-card">
        <div class="chart-title">Department Deadline Status</div>
        @foreach($departments as $row)
        @php
            $dept     = $row['dept'];
            $info     = $row['deadline_info'];
            $override = $row['override'];
        @endphp
        <div style="display:flex;align-items:center;gap:12px;
                    padding:10px 0;border-bottom:1px solid var(--border)">
            <div style="width:40px;height:40px;border-radius:8px;
                        background:var(--surface);border:1px solid var(--border);
                        display:flex;align-items:center;justify-content:center;
                        font-size:10px;font-weight:700;color:var(--navy);
                        flex-shrink:0">
                {{ $dept->code }}
            </div>
            <div class="flex-grow-1">
                <div style="font-size:13px;font-weight:600;color:var(--navy)">
                    {{ $dept->name }}
                </div>
                <div style="font-size:11px;color:var(--slate)">
                    @if($info['has_override'] && $override?->isValid())
                        Extended until:
                        {{ $info['deadline']?->format('d M Y H:i') ?? 'Indefinite' }}
                        · Granted by {{ $override->grantedBy->name }}
                    @elseif($info['passed'])
                        Deadline passed
                    @elseif($info['deadline'])
                        Deadline: {{ $info['deadline']->format('d M Y H:i') }}
                        ({{ $info['deadline']->diffForHumans() }})
                    @else
                        No deadline set
                    @endif
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if($info['has_override'] && $override?->isValid())
                    <span style="padding:2px 10px;border-radius:20px;font-size:11px;
                                 font-weight:600;background:#D1FAE5;color:#065F46">
                        Extended
                    </span>
                    <form method="POST"
                          action="{{ route('admin.deadline-overrides.revoke', $override) }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-danger"
                                style="font-size:11px;padding:2px 8px"
                                onclick="return confirm('Revoke override for {{ $dept->name }}?')">
                            Revoke
                        </button>
                    </form>
                @elseif($info['passed'])
                    <span style="padding:2px 10px;border-radius:20px;font-size:11px;
                                 font-weight:600;background:#FEE2E2;color:#991B1B">
                        Overdue
                    </span>
                    <button class="btn btn-sm btn-outline-primary"
                            style="font-size:11px;padding:2px 8px"
                            onclick="openGrantForm({{ $dept->id }}, '{{ $dept->name }}')">
                        Grant Extension
                    </button>
                @else
                    <span style="padding:2px 10px;border-radius:20px;font-size:11px;
                                 font-weight:600;background:#F1F5F9;color:#64748B">
                        On Time
                    </span>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Right: grant override form --}}
<div class="col-md-5">
    <div class="chart-card" id="grantFormCard">
        <div class="chart-title">Grant Deadline Extension</div>
        <p class="small text-muted mb-3">
            Use this to unlock a specific department to submit after the deadline.
            The department will be notified immediately.
        </p>

        <form method="POST" action="{{ route('admin.deadline-overrides.store') }}">
            @csrf

            <input type="hidden" name="budget_period_id" value="{{ $period?->id }}">

            <div class="mb-3">
                <label class="form-label small fw-semibold">Department</label>
                <select name="department_id" id="deptSelect"
                        class="form-select form-select-sm @error('department_id') is-invalid @enderror">
                    <option value="">— Select department —</option>
                    @foreach($departments as $row)
                    <option value="{{ $row['dept']->id }}">
                        {{ $row['dept']->name }}
                    </option>
                    @endforeach
                </select>
                @error('department_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">
                    New Submission Deadline
                    <span style="color:var(--slate);font-weight:400">(optional — leave blank for open-ended)</span>
                </label>
                <input type="datetime-local" name="new_deadline"
                       class="form-control form-control-sm"
                       value="{{ old('new_deadline') }}">
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">
                    Override Expires At
                    <span style="color:var(--slate);font-weight:400">(default: 7 days)</span>
                </label>
                <input type="datetime-local" name="expires_at"
                       class="form-control form-control-sm"
                       value="{{ old('expires_at', now()->addDays(7)->format('Y-m-d\TH:i')) }}">
                <div class="form-text">
                    After this time, the department cannot submit even with this override.
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold">
                    Reason for Extension <span style="color:#F43F5E">*</span>
                </label>
                <textarea name="reason" rows="4"
                          class="form-control form-control-sm @error('reason') is-invalid @enderror"
                          placeholder="Explain why this department is being given an extension…"
                          required>{{ old('reason') }}</textarea>
                @error('reason')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-sm w-100"
                    style="background:var(--navy);color:#fff;border-radius:8px;padding:10px">
                Grant Extension
            </button>
        </form>
    </div>

    {{-- Active overrides summary --}}
    @if($overrides->count())
    <div class="chart-card mt-4">
        <div class="chart-title">Active Extensions</div>
        @foreach($overrides->where(fn($o) => $o->isValid()) as $o)
        <div style="padding:8px 0;border-bottom:1px solid var(--border);font-size:12px">
            <div class="d-flex justify-content-between">
                <div class="fw-semibold">{{ $o->department->name }}</div>
                <form method="POST"
                      action="{{ route('admin.deadline-overrides.revoke', $o) }}">
                    @csrf
                    <button style="background:none;border:none;color:#F43F5E;
                                   font-size:11px;cursor:pointer">
                        Revoke
                    </button>
                </form>
            </div>
            <div style="color:var(--slate)">
                Granted by {{ $o->grantedBy->name }}
                · Expires {{ $o->expires_at?->format('d M Y') }}
            </div>
            <div style="color:var(--slate);font-style:italic;margin-top:2px">
                "{{ Str::limit($o->reason, 60) }}"
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

</div>

<script>
function openGrantForm(deptId, deptName) {
    const sel = document.getElementById('deptSelect');
    sel.value = deptId;
    document.getElementById('grantFormCard').scrollIntoView({ behavior: 'smooth' });
}
</script>

@endsection
