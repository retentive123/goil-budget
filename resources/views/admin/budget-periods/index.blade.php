@extends('layouts.app')
@section('title', 'Budget Periods')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Budget Periods</h5>
    <a href="{{ route('admin.budget-periods.create') }}" class="btn btn-primary btn-sm">+ Add Period</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Year</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Status</th>
                    <th>Submissions</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($periods as $period)
                <tr>
                    <td>{{ $period->name }}</td>
                    <td>{{ $period->year }}</td>
                    <td>{{ $period->start_date->format('d M Y') }}</td>
                    <td>{{ $period->end_date->format('d M Y') }}</td>
                    <td>
                        <span class="badge bg-{{
                            match($period->status) {
                                'draft'    => 'secondary',
                                'open'     => 'success',
                                'closed'   => 'warning',
                                'approved' => 'primary',
                                default    => 'secondary'
                            }
                        }}">{{ ucfirst($period->status) }}</span>
                    </td>
                    <td>{{ $period->budget_versions_count }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            {{-- ✅ DRAFT: Edit & Open with Alert --}}
                            @if($period->status === 'draft')
                                <a href="{{ route('admin.budget-periods.edit', $period) }}"
                                   class="btn btn-sm btn-outline-primary">Edit</a>

                                {{-- ✅ Open button with alert --}}
                                <button class="btn btn-sm btn-success"
                                        onclick="confirmOpenPeriod({{ $period->id }}, '{{ $period->name }}')">
                                    Open
                                </button>
                            @endif

                            {{-- ✅ OPEN: Close with Alert --}}
                            @if($period->status === 'open')
                                @php
                                    // Calculate stats for this period
                                    $versions = $period->budgetVersions;
                                    $inReview = $versions->whereIn('status', ['submitted', 'under_review'])->unique('department_id')->count();
                                    $rejected = $versions->where('status', 'rejected')->unique('department_id')->count();
                                    $draft = $versions->where('status', 'draft')->unique('department_id')->count();
                                    $totalDepts = \App\Models\Department::where('is_active', true)->count();
                                    $notStarted = $totalDepts - $versions->unique('department_id')->count();

                                    $warnings = [];
                                    if($inReview > 0) $warnings[] = $inReview.' department(s) still in review';
                                    if($rejected > 0) $warnings[] = $rejected.' department(s) rejected (not revised)';
                                    if($draft > 0) $warnings[] = $draft.' department(s) in draft (not submitted)';
                                    if($notStarted > 0) $warnings[] = $notStarted.' department(s) have not started';
                                @endphp

                                <button class="btn btn-sm btn-warning"
                                        onclick="confirmClosePeriod({{ $period->id }}, '{{ $period->name }}', {{ json_encode($warnings) }})">
                                    Close
                                </button>
                            @endif

                            <a href="{{ route('admin.budget-periods.show', $period) }}"
                               class="btn btn-sm btn-outline-secondary">View</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No budget periods found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $periods->links() }}</div>

{{-- Hidden forms --}}
@foreach($periods as $period)
    {{-- Open form --}}
    <form method="POST"
          action="{{ route('admin.budget-periods.open', $period) }}"
          id="openForm_{{ $period->id }}">
        @csrf @method('PATCH')
    </form>

    {{-- Close form --}}
    <form method="POST"
          action="{{ route('admin.budget-periods.close', $period) }}"
          id="closeForm_{{ $period->id }}">
        @csrf @method('PATCH')
    </form>
@endforeach

{{-- SweetAlert2 --}}
<script>
/**
 * Confirm Open Period
 */
function confirmOpenPeriod(periodId, periodName) {
    Swal.fire({
        title: 'Open Budget Period?',
        html: `
            <p style="color:#64748B;margin-bottom:12px">
                You are about to open <strong>${periodName}</strong>.
            </p>
            <div style="background:#D1FAE5;border-radius:8px;padding:12px;
                        text-align:left;font-size:13px;color:#065F46;margin-bottom:12px">
                <div>✅ Once opened, departments can start submitting their budgets.</div>
                <div style="margin-top:4px;">✅ The period will be available for budget entry.</div>
            </div>
            <p style="color:#64748B;font-size:13px">
                Are you sure you want to open this period?
            </p>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10B981',
        cancelButtonColor: '#64748B',
        confirmButtonText: 'Yes, Open Period',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('openForm_' + periodId).submit();
        }
    });
}

/**
 * Confirm Close Period
 */
function confirmClosePeriod(periodId, periodName, warnings) {
    let warningHtml = '';

    if (warnings && warnings.length > 0) {
        warningHtml = `
            <p style="color:#64748B;margin-bottom:12px">
                <strong>${periodName}</strong> has the following outstanding items:
            </p>
            <div style="background:#FEF3C7;border-radius:8px;padding:12px;
                        text-align:left;font-size:13px;color:#92400E;margin-bottom:12px">
                ${warnings.map(w => `<div>⚠ ${w}</div>`).join('')}
            </div>
            <p style="color:#64748B;font-size:13px">
                Closing this period will prevent any further submissions or edits.
                Are you sure you want to proceed?
            </p>
        `;
    } else {
        warningHtml = `
            <p style="color:#64748B;margin-bottom:12px">
                All departments have submitted and been processed.
                Close <strong>${periodName}</strong>?
            </p>
        `;
    }

    Swal.fire({
        title: 'Close Budget Period?',
        html: warningHtml,
        icon: warnings && warnings.length > 0 ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonColor: warnings && warnings.length > 0 ? '#F59E0B' : '#10B981',
        cancelButtonColor: '#64748B',
        confirmButtonText: 'Yes, Close Period',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('closeForm_' + periodId).submit();
        }
    });
}
</script>

@endsection
