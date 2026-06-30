@extends('layouts.app')
@section('title', 'Budget Period — ' . $budgetPeriod->name)
@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.budget-periods.index') }}"
       class="text-muted text-decoration-none small">← Budget Periods</a>
    <span class="text-muted">/</span>
    <span class="small">{{ $budgetPeriod->name }}</span>
</div>

{{-- Header --}}
<div class="chart-card mb-4"
     style="border-left:4px solid {{
        match($budgetPeriod->status) {
            'open'     => '#10B981',
            'closed'   => '#F59E0B',
            'approved' => '#6366F1',
            default    => '#64748B'
        }
     }}">
    <div class="row align-items-center">
        <div class="col">
            <div style="font-size:20px;font-weight:700;color:var(--navy)">
                {{ $budgetPeriod->name }}
            </div>
            <div style="font-size:13px;color:var(--slate);margin-top:4px">
                {{ $budgetPeriod->start_date->format('d M Y') }} —
                {{ $budgetPeriod->end_date->format('d M Y') }}
                &middot; Created by {{ $budgetPeriod->createdBy?->name ?? '—' }}
            </div>
        </div>
        <div class="col-auto d-flex gap-2 align-items-center">
            <span style="padding:6px 16px;border-radius:20px;font-size:13px;font-weight:700;
                         background:{{
                            match($budgetPeriod->status) {
                                'open'     => '#D1FAE5',
                                'closed'   => '#FEF3C7',
                                'approved' => '#EDE9FE',
                                default    => '#F1F5F9'
                            }
                         }};
                         color:{{
                            match($budgetPeriod->status) {
                                'open'     => '#065F46',
                                'closed'   => '#92400E',
                                'approved' => '#5B21B6',
                                default    => '#475569'
                            }
                         }}">
                {{ ucfirst($budgetPeriod->status) }}
            </span>

            @if($budgetPeriod->status === 'draft')
            <form method="POST"
                  action="{{ route('admin.budget-periods.open', $budgetPeriod) }}">
                @csrf @method('PATCH')
                <button class="btn btn-sm"
                        style="background:#10B981;color:#fff;border-radius:8px">
                    Open Period
                </button>
            </form>
            @endif

            @if($budgetPeriod->status === 'open')
            <button class="btn btn-sm"
                    style="background:#F59E0B;color:#fff;border-radius:8px"
                    onclick="confirmClose()">
                Close Period
            </button>
            @endif
        </div>
    </div>
</div>

{{-- Stats --}}
@php
    $versions      = $budgetPeriod->budgetVersions;
    $approved      = $versions->where('status','approved')->unique('department_id');
    $inReview      = $versions->whereIn('status',['submitted','under_review'])->unique('department_id');
    $rejected      = $versions->where('status','rejected')->unique('department_id');
    $draft         = $versions->where('status','draft')->unique('department_id');
    $totalDepts    = \App\Models\Department::where('is_active',true)->count();
    $notStarted    = $totalDepts - $versions->unique('department_id')->count();
    $totalBudget   = $versions->where('status','approved')->sum(fn($v)=>$v->lineItems()->sum('total_amount'));
@endphp

<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="stat-card text-center">
            <div class="stat-label">Total Depts</div>
            <div class="stat-value">{{ $totalDepts }}</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card text-center">
            <div class="stat-accent" style="background:#10B981"></div>
            <div class="stat-label">Approved</div>
            <div class="stat-value" style="color:#10B981">{{ $approved->count() }}</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card text-center">
            <div class="stat-accent" style="background:#F59E0B"></div>
            <div class="stat-label">In Review</div>
            <div class="stat-value" style="color:#F59E0B">{{ $inReview->count() }}</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card text-center">
            <div class="stat-accent" style="background:#F43F5E"></div>
            <div class="stat-label">Rejected</div>
            <div class="stat-value" style="color:#F43F5E">{{ $rejected->count() }}</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card text-center">
            <div class="stat-accent" style="background:#64748B"></div>
            <div class="stat-label">Not Started</div>
            <div class="stat-value" style="color:#64748B">{{ $notStarted }}</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card text-center">
            <div class="stat-accent" style="background:var(--navy)"></div>
            <div class="stat-label">Total Approved</div>
            <div class="stat-value" style="font-size:14px">
                GHS {{ number_format($totalBudget,0) }}
            </div>
        </div>
    </div>
</div>

{{-- Submissions table --}}
<div class="chart-card">
    <div class="chart-title">Department Submissions</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead style="font-size:11px;text-transform:uppercase;
                          letter-spacing:.5px;color:var(--slate)">
                <tr>
                    <th>Department</th>
                    <th>Version</th>
                    <th>Status</th>
                    <th class="text-end">Budget Total</th>
                    <th>Submitted</th>
                    <th>Submitted By</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                {{-- Show all depts including not started --}}
                @php
                    $departments = \App\Models\Department::where('is_active',true)
                        ->orderBy('name')->get();
                    $latestByDept = $versions->groupBy('department_id')
                        ->map(fn($g) => $g->sortByDesc('version_number')->first());
                @endphp

                @foreach($departments as $dept)
                @php $version = $latestByDept->get($dept->id); @endphp
                <tr>
                    <td>
                        <div style="font-size:13px;font-weight:600;color:var(--navy)">
                            {{ $dept->name }}
                        </div>
                        <div style="font-size:10px;color:var(--slate)">
                            {{ $dept->code }}
                        </div>
                    </td>
                    <td class="small">
                        {{ $version ? 'v'.$version->version_number : '—' }}
                    </td>
                    <td>
                        @php $status = $version?->status ?? 'not_started'; @endphp
                        <span style="padding:2px 10px;border-radius:20px;font-size:11px;
                                     font-weight:600;
                                     background:{{
                                        match($status) {
                                            'approved'     => '#D1FAE5',
                                            'submitted'    => '#DBEAFE',
                                            'under_review' => '#FEF3C7',
                                            'rejected'     => '#FEE2E2',
                                            'draft'        => '#F1F5F9',
                                            default        => '#F8FAFC'
                                        }
                                     }};
                                     color:{{
                                        match($status) {
                                            'approved'     => '#065F46',
                                            'submitted'    => '#1E40AF',
                                            'under_review' => '#92400E',
                                            'rejected'     => '#991B1B',
                                            'draft'        => '#475569',
                                            default        => '#94A3B8'
                                        }
                                     }}">
                            {{ ucfirst(str_replace('_',' ',$status)) }}
                        </span>
                    </td>
                    <td class="text-end small">
                        @if($version)
                            GHS {{ number_format($version->lineItems()->sum('total_amount'),0) }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="small text-muted">
                        {{ $version?->submitted_at?->format('d M Y') ?? '—' }}
                    </td>
                    <td class="small">
                        {{ $version?->submittedBy?->name ?? '—' }}
                    </td>
                    <td>
                        @if($version)
                        <div class="d-flex gap-1">
                            <a href="{{ route('approvals.show', $version) }}"
                               class="btn btn-sm"
                               style="background:var(--navy);color:#fff;
                                      font-size:11px;border-radius:6px;
                                      padding:3px 10px">
                                View Budget
                            </a>
                            <a href="{{ route('approvals.history', $version) }}"
                               class="btn btn-sm btn-outline-secondary"
                               style="font-size:11px;border-radius:6px;
                                      padding:3px 10px">
                                History
                            </a>
                        </div>
                        @else
                        <span style="font-size:11px;color:#94A3B8">Not submitted</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Hidden close form --}}
<form method="POST"
      action="{{ route('admin.budget-periods.close', $budgetPeriod) }}"
      id="closeForm">
    @csrf @method('PATCH')
</form>

{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmClose() {
    @php
        $notApproved    = $inReview->count() + $rejected->count() + $draft->count();
        $notSubmitted   = $notStarted;
        $warnings       = [];
        if($inReview->count())  $warnings[] = $inReview->count().' department(s) still in review';
        if($rejected->count())  $warnings[] = $rejected->count().' department(s) rejected (not revised)';
        if($draft->count())     $warnings[] = $draft->count().' department(s) in draft (not submitted)';
        if($notStarted > 0)     $warnings[] = $notStarted.' department(s) have not started';
    @endphp

    @php $warningList = implode('\n• ', $warnings); @endphp

    @if(count($warnings) > 0)
    Swal.fire({
        title: 'Close Budget Period?',
        html: `
            <p style="color:#64748B;margin-bottom:12px">
                <strong>{{ $budgetPeriod->name }}</strong> has the following outstanding items:
            </p>
            <div style="background:#FEF3C7;border-radius:8px;padding:12px;
                        text-align:left;font-size:13px;color:#92400E;margin-bottom:12px">
                @foreach($warnings as $w)
                <div>⚠ {{ $w }}</div>
                @endforeach
            </div>
            <p style="color:#64748B;font-size:13px">
                Closing this period will prevent any further submissions or edits.
                Are you sure you want to proceed?
            </p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#F59E0B',
        cancelButtonColor: '#64748B',
        confirmButtonText: 'Yes, Close Period',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('closeForm').submit();
        }
    });
    @else
    Swal.fire({
        title: 'Close Budget Period?',
        html: `
            <p style="color:#64748B">
                All departments have submitted and been processed.
                Close <strong>{{ $budgetPeriod->name }}</strong>?
            </p>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10B981',
        cancelButtonColor: '#64748B',
        confirmButtonText: 'Yes, Close',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('closeForm').submit();
        }
    });
    @endif
}
</script>
@endsection
