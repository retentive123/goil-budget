@extends('layouts.app')
@section('title', 'My Budget')
@section('content')

@php
$statusColor = fn($s) => match($s) {
    'draft'        => ['bg' => '#F1F5F9', 'text' => '#475569'],
    'submitted'    => ['bg' => '#DBEAFE', 'text' => '#1E40AF'],
    'under_review' => ['bg' => '#FEF3C7', 'text' => '#92400E'],
    'approved'     => ['bg' => '#D1FAE5', 'text' => '#065F46'],
    'rejected'     => ['bg' => '#FEE2E2', 'text' => '#991B1B'],
    default        => ['bg' => '#F1F5F9', 'text' => '#64748B'],
};
@endphp

{{-- ── Page header + period filter ── --}}
<div class="d-flex align-items-start justify-content-between mb-4 gap-3 flex-wrap">
    <div>
        <h5 class="fw-bold mb-0">My Budget</h5>
        <p class="text-muted small mb-0">
            @if($currentPeriod)
                {{ $currentPeriod->name }}
                ({{ $currentPeriod->start_date->format('d M Y') }} –
                 {{ $currentPeriod->end_date->format('d M Y') }})
                @if($currentPeriod->status === 'open')
                    <span style="background:#D1FAE5;color:#065F46;border-radius:4px;
                                 padding:1px 6px;font-size:10px;font-weight:600;margin-left:4px">
                        OPEN
                    </span>
                @endif
            @else
                No budget period found
            @endif
        </p>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
        {{-- Period filter --}}
        @if($periods->count() > 1)
        <form method="GET" class="d-flex align-items-center gap-2">
            <select name="period_id" onchange="this.form.submit()"
                    class="form-select form-select-sm"
                    style="min-width:160px;border-radius:8px;font-size:13px">
                @foreach($periods as $p)
                <option value="{{ $p->id }}"
                    {{ $currentPeriod?->id == $p->id ? 'selected' : '' }}>
                    {{ $p->name }}{{ $p->status === 'open' ? ' ✦' : '' }}
                </option>
                @endforeach
            </select>
        </form>
        @endif

        {{-- Start budget button — only on open period when no draft/submitted version is in progress --}}
        @if($currentPeriod?->status === 'open')
            @php
                $hasActiveDraft = $allVersions->whereIn('status', ['draft','submitted','under_review'])->isNotEmpty();
                $latestRejected = !$hasActiveDraft ? $allVersions->where('status','rejected')->first() : null;
            @endphp
            @if(!$hasActiveDraft)
                @if(!$version || \App\Models\BudgetVersion::canCreateNew($currentPeriod->id, $user->department_id ?? $user->subsidiary_id))
                <form method="POST" action="{{ route('budget.start') }}"
                      onsubmit="this.querySelector('button').disabled=true">
                    @csrf
                    <button class="btn btn-sm btn-primary" style="border-radius:8px">
                        <i class="bi bi-plus-lg me-1"></i>
                        {{ $version ? 'Start New Version' : 'Start Budget' }}
                    </button>
                </form>
                @endif
            @endif
        @endif
    </div>
</div>

{{-- ── Deadline banner ── --}}
@if($currentPeriod)
@php
    $deadlineCheck = app(\App\Services\BudgetCalculationService::class)
        ->isDeadlinePassed($currentPeriod->id, $user->department_id);
    $hasDraft      = $version && $version->isEditable();
    $softBlock     = $deadlineCheck['passed'] && $hasDraft;
@endphp

@if($deadlineCheck['deadline'])
<div style="border-radius:10px;padding:12px 16px;margin-bottom:16px;
            background:{{ $deadlineCheck['passed'] ? ($softBlock ? '#FFFBEB' : '#FEE2E2') : '#FEF3C7' }};
            border:1px solid {{ $deadlineCheck['passed'] ? ($softBlock ? '#FDE68A' : '#FECACA') : '#FDE68A' }}">
    <div style="font-size:13px;font-weight:600;
                color:{{ $deadlineCheck['passed'] ? ($softBlock ? '#92400E' : '#991B1B') : '#92400E' }}">
        @if($deadlineCheck['passed'])
            @if($softBlock)
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                Submission deadline has passed — you can still edit your draft, but
                <strong>you will not be able to submit</strong> until Finance grants an extension.
                @if($deadlineCheck['has_override'])
                    <span style="background:#FEE2E2;color:#991B1B;border-radius:4px;
                                 padding:1px 6px;font-size:11px;margin-left:4px">Extension also expired</span>
                @endif
            @else
                <i class="bi bi-x-circle-fill me-1"></i>
                Submission deadline has passed
                @if($deadlineCheck['has_override']) (including your extension) @endif
                — Contact Finance to request an extension.
            @endif
        @else
            <i class="bi bi-clock me-1"></i>
            Submission deadline:
            <strong>{{ $deadlineCheck['deadline']->format('d M Y H:i') }}</strong>
            ({{ $deadlineCheck['deadline']->diffForHumans() }})
            @if($deadlineCheck['has_override'])
                <span style="background:#D1FAE5;color:#065F46;border-radius:4px;
                             padding:1px 6px;font-size:11px;margin-left:6px">Extended</span>
            @endif
        @endif
    </div>
</div>
@endif
@endif

{{-- ── Version list for selected period ── --}}
@if($allVersions->isEmpty())
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-inbox" style="font-size:40px;opacity:.3"></i>
        <p class="mt-2 mb-0">No budget started for <strong>{{ $currentPeriod?->name ?? 'this period' }}</strong>.</p>
        @if($currentPeriod?->status !== 'open')
        <p class="small mt-1">This period is closed — use the dropdown above to switch periods.</p>
        @endif
    </div>
</div>

@else

<div class="card border-0 shadow-sm mb-4" style="border-radius:14px;overflow:hidden">
    <div class="card-header border-0 py-3 px-4"
         style="background:var(--navy);color:#fff">
        <div class="d-flex align-items-center justify-content-between">
            <span class="fw-semibold" style="font-size:13px">
                <i class="bi bi-layers me-2"></i>
                Budget Versions — {{ $currentPeriod->name }}
            </span>
            <span style="font-size:11px;opacity:.6">{{ $allVersions->count() }} version(s)</span>
        </div>
    </div>

    @foreach($allVersions as $v)
    @php
        $sc      = $statusColor($v->status);
        $isLatest = $loop->first;
        $total   = $v->lineItems->sum('total_amount') ?? 0;
    @endphp
    <div class="px-4 py-3 {{ $loop->last ? '' : 'border-bottom' }}"
         style="{{ $isLatest ? 'background:#F8FAFC;' : '' }}">
        <div class="d-flex align-items-center gap-3 flex-wrap">

            {{-- Version circle --}}
            <div style="min-width:44px;height:44px;
                        background:{{ $isLatest ? 'var(--navy)' : '#E2E8F0' }};
                        border-radius:10px;display:flex;align-items:center;
                        justify-content:center;flex-shrink:0">
                <span style="font-size:12px;font-weight:700;
                             color:{{ $isLatest ? '#fff' : '#64748B' }}">
                    v{{ $v->version_number }}
                </span>
            </div>

            {{-- Info --}}
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span style="font-size:13px;font-weight:600;color:var(--navy)">
                        Version {{ $v->version_number }}
                        @if($isLatest)
                        <span style="font-size:10px;background:var(--gold);color:#fff;
                                     border-radius:4px;padding:1px 6px;margin-left:4px">
                            LATEST
                        </span>
                        @endif
                    </span>
                    @if($v->is_revision)
                    <span style="font-size:10px;background:#EDE9FE;color:#5B21B6;
                                 border-radius:4px;padding:1px 6px;font-weight:600">
                        <i class="bi bi-pencil-square me-1"></i>REVISION
                        @if($v->originalVersion) of v{{ $v->originalVersion->version_number }} @endif
                    </span>
                    @endif
                    <span style="font-size:11px;padding:2px 10px;border-radius:20px;font-weight:600;
                                 background:{{ $sc['bg'] }};color:{{ $sc['text'] }}">
                        {{ ucfirst(str_replace('_', ' ', $v->status)) }}
                    </span>
                </div>
                <div style="font-size:11px;color:#94A3B8;margin-top:3px">
                    @if($v->submitted_at)
                        Submitted {{ $v->submitted_at->format('d M Y') }}
                        @if($v->submittedBy) by {{ $v->submittedBy->name }} @endif &nbsp;·&nbsp;
                    @endif
                    Total: <strong style="color:var(--navy)">{{ currency() }} {{ number_format($total, 0) }}</strong>
                </div>
                @if($v->status === 'rejected' && $v->rejection_reason)
                <div style="font-size:11px;color:#991B1B;margin-top:4px">
                    <i class="bi bi-x-circle me-1"></i>{{ $v->rejection_reason }}
                </div>
                @endif
                @if($v->is_revision && $v->revision_notes)
                <div style="font-size:11px;color:#6B7280;margin-top:3px">
                    <i class="bi bi-chat-left-text me-1"></i>{{ $v->revision_notes }}
                </div>
                @endif
            </div>

            {{-- Action --}}
            <div class="flex-shrink-0 d-flex gap-1">
                @if($v->isEditable())
                <a href="{{ route('budget.show', $v) }}"
                   class="btn btn-sm fw-semibold"
                   style="background:var(--navy);color:#fff;border-radius:8px;font-size:12px">
                    <i class="bi bi-pencil me-1"></i>Continue Editing
                </a>
                @elseif($v->status === 'rejected')
                {{-- Reopen the rejected version to draft so it can be edited & resubmitted --}}
                <form method="POST" action="{{ route('budget.reopen', $v) }}" id="reopen-form-{{ $v->id }}">
                    @csrf
                    <button type="button" class="btn btn-sm fw-semibold"
                            style="background:#F59E0B;color:#fff;border-radius:8px;font-size:12px;border:none"
                            onclick="confirmReopen({{ $v->id }}, {{ $v->version_number }})">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Edit & Resubmit
                    </button>
                </form>
                <a href="{{ route('budget.show', $v) }}"
                   class="btn btn-sm btn-outline-secondary"
                   style="border-radius:8px;font-size:12px">
                    <i class="bi bi-eye me-1"></i>View
                </a>
                @else
                <a href="{{ route('budget.show', $v) }}"
                   class="btn btn-sm btn-outline-secondary"
                   style="border-radius:8px;font-size:12px">
                    <i class="bi bi-eye me-1"></i>View
                </a>
                @endif
            </div>

        </div>
    </div>
    @endforeach
</div>

@endif

{{-- ── All-periods history ── --}}
@if($periodHistory->count() > 1 || ($periodHistory->count() === 1 && $periodHistory->first()['period']?->id !== $currentPeriod?->id))
<div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
    <div class="card-header border-0 py-3 px-4"
         style="background:#F8FAFC;border-bottom:1px solid var(--border)">
        <span class="fw-semibold" style="font-size:13px;color:var(--navy)">
            <i class="bi bi-clock-history me-2"></i>Budget History — All Periods
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0" style="font-size:13px">
            <thead style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;
                          color:var(--slate);background:#F8FAFC">
                <tr>
                    <th class="px-4 py-2">Period</th>
                    <th>Versions</th>
                    <th>Latest Status</th>
                    <th class="text-end">Approved Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($periodHistory as $row)
                @php $sc2 = $statusColor($row['latest']->status); @endphp
                <tr class="{{ $row['period']?->id === $currentPeriod?->id ? 'table-active' : '' }}">
                    <td class="px-4 py-2 fw-semibold" style="color:var(--navy)">
                        {{ $row['period']?->name ?? '—' }}
                        @if($row['period']?->status === 'open')
                        <span style="font-size:9px;background:#D1FAE5;color:#065F46;
                                     border-radius:3px;padding:1px 5px;margin-left:4px">OPEN</span>
                        @endif
                    </td>
                    <td class="text-muted small">{{ $row['count'] }}</td>
                    <td>
                        <span style="font-size:11px;padding:2px 9px;border-radius:20px;font-weight:600;
                                     background:{{ $sc2['bg'] }};color:{{ $sc2['text'] }}">
                            {{ ucfirst(str_replace('_', ' ', $row['latest']->status)) }}
                        </span>
                    </td>
                    <td class="text-end fw-semibold" style="color:var(--navy)">
                        @if($row['approved'])
                            {{ currency() }} {{ number_format($row['total'], 0) }}
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-end pe-3">
                        <a href="{{ route('budget.index', ['period_id' => $row['period']?->id]) }}"
                           class="btn btn-sm btn-outline-secondary"
                           style="border-radius:6px;font-size:11px;padding:2px 10px">
                            View
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
function confirmReopen(versionId, versionNumber) {
    Swal.fire({
        title: 'Reopen v' + versionNumber + ' for editing?',
        text: 'It will go back through the full approval process after you resubmit.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, reopen it',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#F59E0B',
        cancelButtonColor: '#64748B',
        reverseButtons: true,
        focusCancel: true,
    }).then(function (result) {
        if (result.isConfirmed) {
            document.getElementById('reopen-form-' + versionId).submit();
        }
    });
}
</script>
@endpush
