@extends('layouts.app')
@section('title', 'Virement Detail')
@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('virements.index') }}" class="text-muted text-decoration-none">Virements</a>
    <span class="text-muted">/</span>
    <span>Request #{{ $virement->id }}</span>
</div>

<div class="row justify-content-center">
<div class="col-md-7">

<div class="card shadow-sm">
    <div class="card-body p-4">

        <div class="d-flex justify-content-between align-items-start mb-4">
            <h5 class="fw-bold mb-0">Virement Request #{{ $virement->id }}</h5>
            <span class="badge bg-{{
                match($virement->status) {
                    'approved' => 'success',
                    'rejected' => 'danger',
                    default    => 'warning'
                }
            }} fs-6">{{ ucfirst($virement->status) }}</span>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="small text-muted">Department</div>
                <div class="fw-semibold">{{ $virement->department->name }}</div>
            </div>
            <div class="col-md-6">
                <div class="small text-muted">Amount</div>
                <div class="fw-bold text-primary fs-5">
                    {{ currency() }} {{ number_format($virement->amount, 2) }}
                </div>
            </div>
            <div class="col-md-6">
                <div class="small text-muted">From Account</div>
                <div class="fw-semibold">
                    <code>{{ $virement->fromLineItem->accountCode->code }}</code>
                    {{ $virement->fromLineItem->accountCode->name }}
                </div>
                <div class="small text-muted">
                    Balance: {{ currency() }} {{ number_format($virement->fromLineItem->total_amount, 2) }}
                    &nbsp;|&nbsp; Max 10%: {{ currency() }} {{ number_format($virement->fromLineItem->maxVirementAmount(), 2) }}
                </div>
            </div>
            <div class="col-md-6">
                <div class="small text-muted">To Account</div>
                <div class="fw-semibold">
                    <code>{{ $virement->toLineItem->accountCode->code }}</code>
                    {{ $virement->toLineItem->accountCode->name }}
                </div>
                <div class="small text-muted">
                    Balance: {{ currency() }} {{ number_format($virement->toLineItem->total_amount, 2) }}
                </div>
            </div>
            <div class="col-12">
                <div class="small text-muted">Justification</div>
                <div class="bg-light rounded p-3 small">{{ $virement->justification }}</div>
            </div>
            <div class="col-md-6">
                <div class="small text-muted">Requested By</div>
                <div class="fw-semibold">{{ $virement->requestedBy->name }}</div>
                <div class="small text-muted">{{ $virement->created_at->format('d M Y H:i') }}</div>
            </div>
            @if($virement->approvedBy)
            <div class="col-md-6">
                <div class="small text-muted">
                    {{ $virement->status === 'approved' ? 'Approved' : 'Rejected' }} By
                </div>
                <div class="fw-semibold">{{ $virement->approvedBy->name }}</div>
                <div class="small text-muted">{{ $virement->approved_at->format('d M Y H:i') }}</div>
            </div>
            @endif
            @if($virement->approval_comments)
            <div class="col-12">
                <div class="small text-muted">Finance Comments</div>
                <div class="bg-light rounded p-3 small">{{ $virement->approval_comments }}</div>
            </div>
            @endif
        </div>

    </div>
</div>

</div>
</div>
@endsection
