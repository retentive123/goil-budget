@extends('layouts.app')
@section('title', 'My Budget')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">My Budget</h5>
        <p class="text-muted small mb-0">
            {{ $currentPeriod->name }} —
            {{ $currentPeriod->start_date->format('d M Y') }} to
            {{ $currentPeriod->end_date->format('d M Y') }}
        </p>
    </div>

    @if(!$version || $version->status === 'rejected')
        @if(!$version || \App\Models\BudgetVersion::canCreateNew($currentPeriod->id, $user->department_id))
            <form method="POST" action="{{ route('budget.start') }}">
                @csrf
                <button class="btn btn-primary">
                    {{ $version ? 'Start Revision' : 'Start Budget' }}
                </button>
            </form>
        @endif
    @endif
</div>

{{-- Deadline status --}}
@if($currentPeriod)
@php
    $deadlineCheck = app(\App\Services\BudgetCalculationService::class)
        ->isDeadlinePassed($currentPeriod->id, $user->department_id);
@endphp

@if($deadlineCheck['deadline'])
<div style="border-radius:10px;padding:12px 16px;margin-bottom:16px;
            background:{{ $deadlineCheck['passed'] ? '#FEE2E2' : '#FEF3C7' }};
            border:1px solid {{ $deadlineCheck['passed'] ? '#FECACA' : '#FDE68A' }}">
    <div style="font-size:13px;font-weight:600;
                color:{{ $deadlineCheck['passed'] ? '#991B1B' : '#92400E' }}">
        @if($deadlineCheck['passed'])
            🚫 Submission deadline has passed
            @if($deadlineCheck['has_override'])
                (including your extension)
            @endif
            — Contact Finance to request an extension.
        @else
            ⏰ Submission deadline:
            <strong>{{ $deadlineCheck['deadline']->format('d M Y H:i') }}</strong>
            ({{ $deadlineCheck['deadline']->diffForHumans() }})
            @if($deadlineCheck['has_override'])
                <span style="background:#D1FAE5;color:#065F46;border-radius:4px;
                             padding:1px 6px;font-size:11px;margin-left:6px">
                    Extended
                </span>
            @endif
        @endif
    </div>
</div>
@endif
@endif

@if($version)
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col">
                <p class="mb-1 small text-muted">Current Version</p>
                <h6 class="fw-bold mb-0">Version {{ $version->version_number }}</h6>
            </div>
            <div class="col-auto">
                <span class="badge bg-{{
                    match($version->status) {
                        'draft'        => 'secondary',
                        'submitted'    => 'primary',
                        'under_review' => 'warning',
                        'approved'     => 'success',
                        'rejected'     => 'danger',
                        default        => 'secondary'
                    }
                }} fs-6">{{ ucfirst(str_replace('_', ' ', $version->status)) }}</span>
            </div>
            <div class="col-auto">
                @if($version->isEditable())
                    <a href="{{ route('budget.show', $version) }}" class="btn btn-primary">
                        Continue Editing
                    </a>
                @else
                    <a href="{{ route('budget.show', $version) }}" class="btn btn-outline-primary">
                        View Budget
                    </a>
                @endif
            </div>
        </div>

        @if($version->submission_notes)
        <div class="mt-3 p-3 bg-light rounded small">
            <strong>Submission notes:</strong> {{ $version->submission_notes }}
        </div>
        @endif
    </div>
</div>
@else
<div class="text-center py-5 text-muted">
    <p>No budget started yet for this period.</p>
</div>
@endif

@endsection
