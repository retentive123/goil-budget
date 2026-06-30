@extends('layouts.app')
@section('title', 'Virements')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Virement Requests</h5>
    <div class="d-flex gap-2">
        @can('approve virement')
        <a href="{{ route('virements.pending') }}" class="btn btn-sm btn-outline-warning">
            Pending Approvals
        </a>
        @endcan
        @can('request virement')
        <a href="{{ route('virements.create') }}" class="btn btn-sm btn-primary">
            + New Request
        </a>
        @endcan
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Department</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Amount ({{ currency() }})</th>
                    <th>Status</th>
                    <th>Requested</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($virements as $v)
                <tr>
                    <td class="small">{{ $v->department->name }}</td>
                    <td class="small">
                        <code>{{ $v->fromLineItem->accountCode->code }}</code>
                    </td>
                    <td class="small">
                        <code>{{ $v->toLineItem->accountCode->code }}</code>
                    </td>
                    <td class="small fw-semibold">{{ number_format($v->amount, 2) }}</td>
                    <td>
                        <span class="badge bg-{{
                            match($v->status) {
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default    => 'warning'
                            }
                        }}">{{ ucfirst($v->status) }}</span>
                    </td>
                    <td class="small text-muted">{{ $v->created_at->diffForHumans() }}</td>
                    <td>
                        <a href="{{ route('virements.show', $v) }}"
                           class="btn btn-sm btn-outline-secondary">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        No virement requests found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $virements->links() }}</div>
@endsection
