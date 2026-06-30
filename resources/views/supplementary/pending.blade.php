@extends('layouts.app')
@section('title', 'Pending Supplementary Requests')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Pending Supplementary Budget Approvals</h5>
    <a href="{{ route('supplementary.index') }}"
       class="btn btn-sm btn-outline-secondary">
        All Requests
    </a>
</div>

@forelse($pending as $s)
<div class="chart-card mb-3">
    <div class="row align-items-start">
        <div class="col-md-7">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span style="font-size:15px;font-weight:700;color:var(--navy)">
                    {{ $s->department->name }}
                </span>
                <span style="color:var(--slate)">·</span>
                <code style="font-size:13px">{{ $s->accountCode->code }}</code>
                <span style="padding:2px 8px;border-radius:20px;font-size:10px;
                             font-weight:600;
                             background:{{ $s->line_type==='revenue'?'#D1FAE5':'#FEE2E2' }};
                             color:{{ $s->line_type==='revenue'?'#065F46':'#991B1B' }}">
                    {{ ucfirst($s->line_type) }}
                </span>
            </div>

            <div style="font-size:13px;color:var(--slate);margin-bottom:12px">
                {{ $s->accountCode->name }} — {{ $s->period->name }}
            </div>

            <div class="row g-2 mb-3">
                <div class="col-4">
                    <div style="background:#F8FAFC;border-radius:8px;padding:10px;text-align:center">
                        <div style="font-size:10px;color:var(--slate)">Original Budget</div>
                        <div style="font-size:14px;font-weight:700;color:var(--navy)">
                            GHS {{ number_format($s->original_amount,0) }}
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div style="background:#FEF3C7;border-radius:8px;padding:10px;text-align:center">
                        <div style="font-size:10px;color:#92400E">Requested Additional</div>
                        <div style="font-size:14px;font-weight:700;color:#92400E">
                            +GHS {{ number_format($s->requested_amount,0) }}
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div style="background:#F8FAFC;border-radius:8px;padding:10px;text-align:center">
                        <div style="font-size:10px;color:var(--slate)">New Total if Approved</div>
                        <div style="font-size:14px;font-weight:700;color:#10B981">
                            GHS {{ number_format($s->original_amount + $s->requested_amount,0) }}
                        </div>
                    </div>
                </div>
            </div>

            <div style="font-size:12px;background:#F8FAFC;border-radius:8px;
                        padding:12px;color:var(--slate)">
                <strong>Justification:</strong>
                {{ Str::limit($s->justification, 200) }}
            </div>

            <div style="font-size:11px;color:var(--slate);margin-top:8px">
                Submitted by {{ $s->requestedBy->name }}
                on {{ $s->submitted_at?->format('d M Y H:i') }}
                ({{ $s->submitted_at?->diffForHumans() }})
            </div>
        </div>

        <div class="col-md-5">
            {{-- Approve --}}
            <form method="POST"
                  action="{{ route('supplementary.approve', $s) }}"
                  class="mb-2">
                @csrf
                <label class="form-label small fw-semibold mb-1">
                    Approved Amount (GHS)
                </label>
                <input type="number" name="approved_amount"
                       class="form-control form-control-sm mb-2"
                       value="{{ $s->requested_amount }}" min="1" step="0.01">
                <textarea name="review_notes" rows="2"
                          class="form-control form-control-sm mb-2"
                          placeholder="Approval notes…"></textarea>
                <button type="submit" class="btn btn-success btn-sm w-100">
                    ✔ Approve
                </button>
            </form>

            {{-- Reject --}}
            <form method="POST"
                  action="{{ route('supplementary.reject', $s) }}">
                @csrf
                <textarea name="rejection_reason" rows="2"
                          class="form-control form-control-sm mb-2"
                          placeholder="Reason for rejection (required)"
                          required></textarea>
                <button type="submit" class="btn btn-danger btn-sm w-100">
                    ✘ Reject
                </button>
            </form>

            <div class="mt-2 text-center">
                <a href="{{ route('supplementary.show', $s) }}"
                   style="font-size:11px;color:var(--navy)">
                    View full detail →
                </a>
            </div>
        </div>
    </div>
</div>
@empty
<div class="chart-card text-center py-5 text-muted">
    <div style="font-size:36px;margin-bottom:12px">✅</div>
    <div style="font-size:15px;font-weight:600;color:var(--navy)">
        No pending supplementary requests
    </div>
</div>
@endforelse

<div class="mt-3">{{ $pending->links() }}</div>
@endsection
