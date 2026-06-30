@extends('layouts.app')
@section('title', 'Pending Virements')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Pending Virement Approvals</h5>
    <a href="{{ route('virements.index') }}" class="btn btn-sm btn-outline-secondary">
        All Virements
    </a>
</div>

@forelse($pending as $v)
<div class="card shadow-sm mb-3">
    <div class="card-body">

        <div class="row align-items-start">
            <div class="col-md-8">
                <div class="d-flex gap-3 mb-3">
                    <div>
                        <div class="small text-muted">Department</div>
                        <div class="fw-semibold">{{ $v->department->name }}</div>
                    </div>
                    <div>
                        <div class="small text-muted">Amount</div>
                        <div class="fw-bold text-primary">
                            {{ currency() }} {{ number_format($v->amount, 2) }}
                        </div>
                    </div>
                    <div>
                        <div class="small text-muted">Requested By</div>
                        <div class="fw-semibold">{{ $v->requestedBy->name }}</div>
                    </div>
                    <div>
                        <div class="small text-muted">Date</div>
                        <div class="small">{{ $v->created_at->format('d M Y') }}</div>
                    </div>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <div class="border rounded p-2 small">
                            <div class="text-muted mb-1">FROM</div>
                            <code>{{ $v->fromLineItem->accountCode->code }}</code>
                            {{ $v->fromLineItem->accountCode->name }}<br>
                            <span class="text-muted">
                                Total: {{ currency() }} {{ number_format($v->fromLineItem->total_amount, 2) }}
                                &nbsp;|&nbsp;
                                Max: {{ currency() }} {{ number_format($v->fromLineItem->maxVirementAmount(), 2) }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-2 small">
                            <div class="text-muted mb-1">TO</div>
                            <code>{{ $v->toLineItem->accountCode->code }}</code>
                            {{ $v->toLineItem->accountCode->name }}<br>
                            <span class="text-muted">
                                Total: {{ currency() }} {{ number_format($v->toLineItem->total_amount, 2) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="small bg-light rounded p-2">
                    <strong>Justification:</strong> {{ $v->justification }}
                </div>
            </div>

            <div class="col-md-4">

                {{-- Approve form --}}
                <form method="POST" action="{{ route('virements.approve', $v) }}" class="mb-2">
                    @csrf
                    <div class="mb-2">
                        <textarea name="comments" rows="2"
                            class="form-control form-control-sm"
                            placeholder="Approval comments (optional)"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        ✔ Approve
                    </button>
                </form>

                {{-- Reject form --}}
                <form method="POST" action="{{ route('virements.reject', $v) }}">
                    @csrf
                    <div class="mb-2">
                        <textarea name="comments" rows="2"
                            class="form-control form-control-sm"
                            placeholder="Reason for rejection (required)"
                            required></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger btn-sm w-100">
                        ✘ Reject
                    </button>
                </form>

            </div>
        </div>

    </div>
</div>
@empty
<div class="text-center py-5 text-muted">
    <p>No pending virement requests.</p>
</div>
@endforelse

<div class="mt-3">{{ $pending->links() }}</div>
@endsection
