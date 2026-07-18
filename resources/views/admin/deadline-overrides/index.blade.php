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

{{-- Left: entity status list --}}
@php
    $_dRows = $departments->filter(fn($r) => !$r['dept']->isServiceStation());
    $_sRows = $departments->filter(fn($r) =>  $r['dept']->isServiceStation())
                         ->groupBy(fn($r) => $r['dept']->zone?->name ?? 'No Zone')
                         ->sortKeys();
@endphp
<div class="col-md-7">
    <div class="chart-card">
        <div class="d-flex align-items-center justify-content-between mb-2" style="gap:8px">
            <div class="chart-title mb-0">Deadline Status</div>
            <span id="dsCount" style="font-size:11px;color:var(--slate)"></span>
        </div>

        {{-- Search + filter toolbar --}}
        <div class="d-flex gap-2 mb-3">
            <div style="position:relative;flex:1">
                <input id="dsSearch" type="text" placeholder="Search by name or code…"
                       class="form-control form-control-sm"
                       style="padding-left:28px;font-size:12px">
                <svg style="position:absolute;left:8px;top:50%;transform:translateY(-50%);
                            width:13px;height:13px;color:#94A3B8" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1116.65 16.65z"/>
                </svg>
            </div>
            <select id="dsFilter" class="form-select form-select-sm" style="width:auto;font-size:12px">
                <option value="">All</option>
                <option value="on-time">On Time</option>
                <option value="overdue">Overdue</option>
                <option value="extended">Extended</option>
            </select>
        </div>

        <div id="dsEmpty" style="display:none;text-align:center;padding:24px;
                                  font-size:13px;color:var(--slate)">
            No entities match the filter.
        </div>

        <div style="max-height:440px;overflow-y:auto;padding-right:4px" id="dsList">

        {{-- Departments --}}
        @if($_dRows->isNotEmpty())
        <div data-section-header
             style="font-size:10px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;
                    color:#94A3B8;padding:8px 0 4px;margin-top:4px">
            Departments
        </div>
        @foreach($_dRows as $row)
        @php $dept = $row['dept']; $info = $row['deadline_info']; $override = $row['override']; @endphp
        @include('admin.deadline-overrides._row', compact('dept','info','override'))
        @endforeach
        @endif

        {{-- Service Stations grouped by zone --}}
        @foreach($_sRows as $zoneName => $zoneRows)
        <div data-section-header
             style="font-size:10px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;
                    color:#94A3B8;padding:10px 0 4px;margin-top:4px">
            {{ $zoneName }}
        </div>
        @foreach($zoneRows as $row)
        @php $dept = $row['dept']; $info = $row['deadline_info']; $override = $row['override']; @endphp
        @include('admin.deadline-overrides._row', compact('dept','info','override'))
        @endforeach
        @endforeach

        </div>{{-- end scrollable --}}
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
                <label class="form-label small fw-semibold">Department / Station</label>
                @php
                    $_gDepts  = $departments->filter(fn($r) => !$r['dept']->isServiceStation())->sortBy(fn($r) => $r['dept']->name);
                    $_gByZone = $departments->filter(fn($r) =>  $r['dept']->isServiceStation())
                                           ->groupBy(fn($r) => $r['dept']->zone?->name ?? 'No Zone')
                                           ->sortKeys();
                @endphp
                <select name="department_id" id="deptSelect" style="width:100%"
                        class="@error('department_id') is-invalid @enderror">
                    <option value="">— Select entity —</option>
                    @if($_gDepts->isNotEmpty())
                    <optgroup label="── Departments ──">
                        @foreach($_gDepts as $row)
                        <option value="{{ $row['dept']->id }}">{{ $row['dept']->name }}</option>
                        @endforeach
                    </optgroup>
                    @endif
                    @foreach($_gByZone as $_gz => $_gStations)
                    <optgroup label="{{ $_gz }}">
                        @foreach($_gStations->sortBy(fn($r) => $r['dept']->name) as $row)
                        <option value="{{ $row['dept']->id }}">{{ $row['dept']->name }}</option>
                        @endforeach
                    </optgroup>
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

<style>
.ts-wrapper.single .ts-control { border-color:#E2E8F0!important;border-radius:6px!important;box-shadow:none!important;min-height:34px;font-size:13px; }
.ts-wrapper.focus .ts-control   { border-color:var(--navy)!important;box-shadow:0 0 0 .2rem rgba(27,42,74,.15)!important; }
.ts-dropdown .optgroup-header    { font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94A3B8;padding:6px 10px 2px;pointer-events:none; }
.ts-dropdown .option             { font-size:13px;padding:5px 12px;color:#1B2A4A; }
.ts-dropdown .option:hover,.ts-dropdown .option.active { background:rgba(27,42,74,.07);color:#1B2A4A; }
</style>
<script>
(function () {
    var el = document.getElementById('deptSelect');
    if (el && !el._tomSelect) {
        new TomSelect(el, {
            plugins: ['clear_button'],
            placeholder: '— Select entity —',
            allowEmptyOption: true,
            searchField: ['text'],
            maxOptions: null,
            onInitialize: function () { this.control.style.minHeight = '34px'; },
        });
    }
})();

function openGrantForm(deptId, deptName) {
    var el = document.getElementById('deptSelect');
    if (el && el.tomselect) { el.tomselect.setValue(String(deptId)); }
    else if (el) { el.value = deptId; }
    document.getElementById('grantFormCard').scrollIntoView({ behavior: 'smooth' });
}

// ── Deadline Status: search + filter ──
(function () {
    var searchEl  = document.getElementById('dsSearch');
    var filterEl  = document.getElementById('dsFilter');
    var countEl   = document.getElementById('dsCount');
    var emptyEl   = document.getElementById('dsEmpty');
    var listEl    = document.getElementById('dsList');

    function applyFilter() {
        var q      = searchEl.value.trim().toLowerCase();
        var status = filterEl.value;

        var rows    = listEl.querySelectorAll('[data-row]');
        var headers = listEl.querySelectorAll('[data-section-header]');
        var visible = 0;

        rows.forEach(function (row) {
            var nameMatch   = !q      || row.dataset.name.includes(q);
            var statusMatch = !status || row.dataset.status === status;
            var show        = nameMatch && statusMatch;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        // Hide section headers whose rows are all hidden
        headers.forEach(function (hdr) {
            var sibling = hdr.nextElementSibling;
            var anyVisible = false;
            while (sibling && !sibling.hasAttribute('data-section-header')) {
                if (sibling.hasAttribute('data-row') && sibling.style.display !== 'none') {
                    anyVisible = true;
                    break;
                }
                sibling = sibling.nextElementSibling;
            }
            hdr.style.display = anyVisible ? '' : 'none';
        });

        var total = rows.length;
        countEl.textContent = visible === total ? total + ' entities' : visible + ' of ' + total;
        emptyEl.style.display = visible === 0 ? '' : 'none';
        listEl.style.display  = visible === 0 ? 'none' : '';
    }

    searchEl.addEventListener('input',  applyFilter);
    filterEl.addEventListener('change', applyFilter);
    applyFilter(); // init count
})();
</script>

@endsection
