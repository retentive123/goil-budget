@extends('layouts.app')
@section('title', 'Supplementary Budget Request')
@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('supplementary.index') }}"
       class="text-muted text-decoration-none small">← Supplementary</a>
    <span class="text-muted">/</span>
    <span class="small">Request #{{ $supplementary->id }}</span>
</div>

<div class="row g-4">
<div class="col-md-8">

{{-- Header --}}
<div class="chart-card mb-4"
     style="border-left:4px solid {{
        match($supplementary->status) {
            'approved'     => '#10B981',
            'rejected'     => '#F43F5E',
            'submitted'    => '#3B82F6',
            'under_review' => '#F59E0B',
            default        => '#64748B'
        }
     }}">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <div style="font-size:18px;font-weight:700;color:var(--navy)">
                {{ $supplementary->accountCode->code }} —
                {{ $supplementary->accountCode->name }}
            </div>
            <div style="font-size:13px;color:var(--slate);margin-top:4px">
                {{ $supplementary->department->name }}
                &middot; {{ $supplementary->period->name }}
                &middot;
                <span style="padding:2px 8px;border-radius:20px;font-size:11px;
                             font-weight:600;
                             background:{{ $supplementary->line_type==='revenue'?'#D1FAE5':'#FEE2E2' }};
                             color:{{ $supplementary->line_type==='revenue'?'#065F46':'#991B1B' }}">
                    {{ ucfirst($supplementary->line_type) }}
                </span>
            </div>
        </div>
        <span style="padding:6px 16px;border-radius:20px;font-size:13px;font-weight:700;
                     background:{{
                        match($supplementary->status) {
                            'approved'     => '#D1FAE5',
                            'rejected'     => '#FEE2E2',
                            'submitted'    => '#DBEAFE',
                            'under_review' => '#FEF3C7',
                            default        => '#F1F5F9'
                        }
                     }};
                     color:{{
                        match($supplementary->status) {
                            'approved'     => '#065F46',
                            'rejected'     => '#991B1B',
                            'submitted'    => '#1E40AF',
                            'under_review' => '#92400E',
                            default        => '#475569'
                        }
                     }}">
            {{ ucfirst(str_replace('_',' ',$supplementary->status)) }}
        </span>
    </div>
</div>

{{-- Financials --}}
<div class="chart-card mb-4">
    <div class="chart-title">Budget Impact</div>
    <div class="row g-3">
        <div class="col-md-3">
            <div style="font-size:11px;color:var(--slate)">Original Budget</div>
            <div style="font-size:16px;font-weight:700;color:var(--navy)">
                GHS {{ number_format($supplementary->original_amount,2) }}
            </div>
        </div>
        <div class="col-md-3">
            <div style="font-size:11px;color:var(--slate)">Additional Requested</div>
            <div style="font-size:16px;font-weight:700;color:#F59E0B">
                +GHS {{ number_format($supplementary->requested_amount,2) }}
            </div>
        </div>
        <div class="col-md-3">
            <div style="font-size:11px;color:var(--slate)">Approved Additional</div>
            <div style="font-size:16px;font-weight:700;color:#10B981">
                {{ $supplementary->approved_amount
                    ? '+GHS '.number_format($supplementary->approved_amount,2)
                    : '—' }}
            </div>
        </div>
        <div class="col-md-3">
            <div style="font-size:11px;color:var(--slate)">YTD Actual</div>
            <div style="font-size:16px;font-weight:700;
                        color:{{ $ytdActual > $supplementary->original_amount ? '#F43F5E' : '#10B981' }}">
                GHS {{ number_format($ytdActual,2) }}
            </div>
        </div>
    </div>
</div>

{{-- Justification --}}
<div class="chart-card mb-4">
    <div class="chart-title">Justification</div>
    <div style="font-size:13px;color:var(--navy);line-height:1.7;
                background:#F8FAFC;border-radius:8px;padding:14px">
        {{ $supplementary->justification }}
    </div>

    @if($supplementary->supporting_evidence)
    <div class="mt-3">
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;
                    letter-spacing:.5px;color:var(--slate);margin-bottom:6px">
            Supporting Evidence
        </div>
        <div style="font-size:12px;color:var(--slate);background:#F8FAFC;
                    border-radius:8px;padding:12px">
            {{ $supplementary->supporting_evidence }}
        </div>
    </div>
    @endif
</div>

{{-- Rejection reason --}}
@if($supplementary->rejection_reason)
<div class="chart-card mb-4"
     style="background:#FEE2E2;border-color:#FECACA">
    <div style="font-size:13px;font-weight:700;color:#991B1B;margin-bottom:6px">
        Rejection Reason
    </div>
    <div style="font-size:13px;color:#991B1B">
        {{ $supplementary->rejection_reason }}
    </div>
</div>
@endif

</div>

{{-- Right sidebar --}}
<div class="col-md-4">

    {{-- Timeline --}}
    <div class="chart-card mb-4">
        <div class="chart-title">Timeline</div>
        <div style="font-size:12px">
            <div class="d-flex gap-2 mb-3">
                <div style="color:#10B981;font-size:18px">●</div>
                <div>
                    <div class="fw-semibold">Submitted</div>
                    <div style="color:var(--slate)">{{ $supplementary->requestedBy->name }}</div>
                    <div style="color:var(--slate)">
                        {{ $supplementary->submitted_at?->format('d M Y H:i') ?? '—' }}
                    </div>
                </div>
            </div>

            @if($supplementary->reviewedBy)
            <div class="d-flex gap-2 mb-3">
                <div style="color:{{ $supplementary->status==='approved'?'#10B981':'#F43F5E' }};font-size:18px">●</div>
                <div>
                    <div class="fw-semibold">
                        {{ $supplementary->status === 'approved' ? 'Approved' : 'Rejected' }}
                    </div>
                    <div style="color:var(--slate)">{{ $supplementary->reviewedBy->name }}</div>
                    <div style="color:var(--slate)">
                        {{ $supplementary->reviewed_at?->format('d M Y H:i') ?? '—' }}
                    </div>
                </div>
            </div>
            @else
            <div class="d-flex gap-2">
                <div style="color:#F59E0B;font-size:18px">○</div>
                <div style="color:var(--slate)">Awaiting Finance review</div>
            </div>
            @endif
        </div>
    </div>

    {{-- Finance decision form --}}
    @can('approve supplementary budget')
    @if(in_array($supplementary->status, ['submitted','under_review']))
    <div class="chart-card mb-4" style="border:2px solid var(--navy)">
        <div style="font-size:13px;font-weight:700;color:var(--navy);margin-bottom:12px">
            Finance Decision
        </div>

        {{-- Approve --}}
        <form method="POST"
              action="{{ route('supplementary.approve', $supplementary) }}"
              class="mb-3">
            @csrf
            <div class="mb-2">
                <label class="form-label small fw-semibold">Approved Amount (GHS)</label>
                <input type="number" name="approved_amount"
                       class="form-control form-control-sm"
                       value="{{ $supplementary->requested_amount }}"
                       min="1" step="0.01">
                <div class="form-text">Can be less than requested.</div>
            </div>
            <div class="mb-2">
                <textarea name="review_notes" rows="2"
                          class="form-control form-control-sm"
                          placeholder="Approval notes (optional)"></textarea>
            </div>
            <button type="submit" class="btn btn-sm btn-success w-100">
                ✔ Approve
            </button>
        </form>

        {{-- Reject --}}
        <form method="POST"
              action="{{ route('supplementary.reject', $supplementary) }}">
            @csrf
            <div class="mb-2">
                <textarea name="rejection_reason" rows="3"
                          class="form-control form-control-sm"
                          placeholder="Reason for rejection (required)"
                          required></textarea>
            </div>
            <button type="submit" class="btn btn-sm btn-danger w-100">
                ✘ Reject
            </button>
        </form>
    </div>
    @endif
    @endcan

</div>
</div>

@endsection
