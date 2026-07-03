@extends('layouts.app')
@section('title', 'Pending Supplementary Requests')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Pending Supplementary Budget Approvals</h5>
        <p class="text-muted small mb-0">
            {{ $batches->count() }} batch(es) &mdash; {{ $pending->total() }} item(s) awaiting review
        </p>
    </div>
    <a href="{{ route('supplementary.index') }}"
       class="btn btn-sm btn-outline-secondary">← All Requests</a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show py-2">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@forelse($batches as $batchIdx => $items)
@php
    $first   = $items->first();
    $count   = $items->count();
    $totReq  = $items->sum('requested_amount');
    $bId     = 'pb-' . $batchIdx;
    $isBatch = $first->batch_id !== null;
@endphp

<div class="chart-card mb-3 p-0" style="overflow:hidden">

    {{-- Batch header --}}
    <div class="d-flex align-items-center gap-3 px-4 py-3"
         style="background:#FAFAFA;border-bottom:1px solid #E2E8F0">

        {{-- Toggle --}}
        <button type="button"
                class="btn btn-link p-0 text-muted"
                onclick="toggleBatch('{{ $bId }}', this)"
                style="flex-shrink:0">
            <span class="bt-arr"
                  style="display:inline-block;transition:transform .2s;font-size:12px">▶</span>
        </button>

        {{-- Meta --}}
        <div style="flex:1;min-width:0;cursor:pointer" onclick="toggleBatch('{{ $bId }}', this.previousElementSibling)">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span style="font-size:14px;font-weight:700;color:var(--navy)">
                    {{ $first->department->name }}
                </span>
                <span style="font-size:11px;color:var(--slate)">{{ $first->period->name }}</span>
                <span style="font-size:11px;background:#FEF3C7;color:#92400E;
                             border-radius:12px;padding:1px 9px;font-weight:600">
                    {{ $count }} {{ Str::plural('item', $count) }}
                </span>
            </div>
            <div style="font-size:11px;color:var(--slate);margin-top:2px">
                Submitted by <strong>{{ $first->requestedBy->name }}</strong>
                &middot; {{ $first->submitted_at?->format('d M Y H:i') }}
                &middot; {{ $first->submitted_at?->diffForHumans() }}
            </div>
        </div>

        {{-- Total --}}
        <div class="text-end" style="flex-shrink:0;min-width:110px">
            <div style="font-size:10px;color:var(--slate)">Total Requested</div>
            <div style="font-size:15px;font-weight:700;color:#92400E">
                GHS {{ number_format($totReq, 2) }}
            </div>
        </div>

        {{-- Batch actions --}}
        <div class="d-flex gap-2" style="flex-shrink:0">
            <button type="button"
                    class="btn btn-sm btn-success"
                    style="font-size:12px;padding:5px 16px;font-weight:600"
                    onclick="batchApprove(
                        '{{ $isBatch ? route('supplementary.approve-batch', $first->batch_id) : route('supplementary.approve', $first->id) }}',
                        {{ $count }}, '{{ addslashes($first->department->name) }}',
                        {{ $isBatch ? 'true' : 'false' }}, '{{ $bId }}'
                    )">
                ✔ {{ $count > 1 ? 'Approve All' : 'Approve' }}
            </button>
            <button type="button"
                    class="btn btn-sm btn-danger"
                    style="font-size:12px;padding:5px 16px;font-weight:600"
                    onclick="batchReject(
                        '{{ $isBatch ? route('supplementary.reject-batch', $first->batch_id) : route('supplementary.reject', $first->id) }}',
                        {{ $count }}, '{{ addslashes($first->department->name) }}'
                    )">
                ✘ {{ $count > 1 ? 'Reject All' : 'Reject' }}
            </button>
            <button type="button"
                    class="btn btn-sm btn-outline-danger"
                    style="font-size:12px;padding:5px 12px"
                    onclick="confirmBatchDelete(
                        '{{ $isBatch ? route('supplementary.destroy-batch', $first->batch_id) : route('supplementary.destroy', $first->id) }}',
                        {{ $count }}, '{{ addslashes($first->department->name) }}'
                    )">
                🗑
            </button>
        </div>
    </div>

    {{-- Justification (always visible) --}}
    <div class="px-4 py-2" style="font-size:12px;color:var(--slate);border-bottom:1px solid #F1F5F9">
        <span class="fw-semibold">Justification:</span>
        {{ Str::limit($first->justification, 300) }}
        @if($first->supporting_evidence)
        &nbsp;&middot;&nbsp;
        <span class="fw-semibold">Evidence:</span>
        {{ Str::limit($first->supporting_evidence, 120) }}
        @endif
    </div>

    {{-- Expandable items table --}}
    <div id="{{ $bId }}" style="display:none">
        <div class="table-responsive">
            <table class="table table-sm mb-0" style="font-size:12px">
                <thead>
                    <tr style="background:#F8FAFC;color:var(--slate);font-size:11px;
                               text-transform:uppercase;letter-spacing:.4px">
                        <th class="ps-4">Account Code</th>
                        <th>Name</th>
                        <th class="text-end">Original (GHS)</th>
                        <th class="text-end">Requested (GHS)</th>
                        <th>Approve Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($items as $s)
                <tr>
                    <td class="ps-4 align-middle">
                        <code style="font-size:11px">{{ $s->accountCode->code }}</code>
                    </td>
                    <td class="align-middle" style="color:#374151">
                        {{ $s->accountCode->name }}
                        @if($s->accountCode->category)
                        <div style="font-size:10px;color:var(--slate)">
                            {{ $s->accountCode->category->name }}
                        </div>
                        @endif
                    </td>
                    <td class="text-end align-middle">{{ number_format($s->original_amount, 2) }}</td>
                    <td class="text-end align-middle fw-semibold" style="color:#92400E">
                        +{{ number_format($s->requested_amount, 2) }}
                    </td>
                    {{-- Individual approve --}}
                    <td class="align-middle py-1">
                        <form method="POST"
                              action="{{ route('supplementary.approve', $s) }}"
                              class="d-flex gap-1 align-items-center">
                            @csrf
                            <input type="number"
                                   name="approved_amount"
                                   value="{{ $s->requested_amount }}"
                                   class="form-control form-control-sm batch-amt-input"
                                   data-batch="{{ $bId }}"
                                   data-item-id="{{ $s->id }}"
                                   min="1" step="0.01"
                                   style="width:110px">
                            <input type="hidden" name="review_notes" value="">
                            <button type="submit"
                                    class="btn btn-sm btn-success"
                                    style="font-size:11px;padding:2px 10px;white-space:nowrap">
                                ✔ Approve
                            </button>
                        </form>
                    </td>
                    {{-- Individual reject --}}
                    <td class="align-middle py-1">
                        <form method="POST"
                              action="{{ route('supplementary.reject', $s) }}"
                              onsubmit="return promptReject(event, this)">
                            @csrf
                            <input type="hidden" name="rejection_reason" class="rej-reason">
                            <button type="submit"
                                    class="btn btn-sm btn-outline-danger"
                                    style="font-size:11px;padding:2px 10px">
                                ✘ Reject
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:#F8FAFC;font-weight:700;font-size:12px">
                        <td class="ps-4" colspan="2" style="color:var(--slate)">Total</td>
                        <td class="text-end">{{ number_format($items->sum('original_amount'), 2) }}</td>
                        <td class="text-end" style="color:#92400E">
                            +{{ number_format($totReq, 2) }}
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@empty
<div class="chart-card text-center py-5 text-muted">
    <div style="font-size:36px;margin-bottom:12px">✅</div>
    <div style="font-size:15px;font-weight:600;color:var(--navy)">No pending supplementary requests</div>
    <p class="small mt-2">All requests have been reviewed.</p>
</div>
@endforelse

<div class="mt-3">{{ $pending->links() }}</div>

{{-- Hidden forms for batch + delete actions --}}
<form id="batchApproveForm" method="POST" style="display:none">
    @csrf
    <input type="hidden" name="approved_amount" id="batchApprovedAmount">
    <input type="hidden" name="review_notes"    id="batchReviewNotes">
</form>
<form id="batchRejectForm" method="POST" style="display:none">
    @csrf
    <input type="hidden" name="rejection_reason" id="batchRejectionReason">
</form>
<form id="deleteForm" method="POST" style="display:none">
    @csrf
    @method('DELETE')
</form>

<script>
function toggleBatch(id, btn) {
    const body = document.getElementById(id);
    // btn may be the button element or the arrow span inside it
    const arr  = btn.classList.contains('bt-arr') ? btn : btn.querySelector('.bt-arr');
    if (!arr) return;
    const open = body.style.display === 'none';
    body.style.display  = open ? '' : 'none';
    arr.style.transform = open ? 'rotate(90deg)' : '';
}

function batchApprove(url, count, dept, isBatch, bId) {
    Swal.fire({
        title: count > 1 ? `Approve All ${count} Items?` : 'Approve Request?',
        html: `Approve ${count > 1 ? '<strong>' + count + '</strong> supplementary requests' : 'this request'} from <strong>${dept}</strong> using the amounts shown in the table.`,
        input: 'textarea',
        inputLabel: 'Review Notes (optional)',
        inputPlaceholder: 'Add any notes for the department…',
        inputAttributes: { rows: 2 },
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10B981',
        cancelButtonColor: '#64748B',
        confirmButtonText: count > 1 ? `✔ Approve All ${count}` : '✔ Approve',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then(result => {
        if (!result.isConfirmed) return;

        const form = document.getElementById('batchApproveForm');
        // Remove any previously injected amount inputs
        form.querySelectorAll('.dyn-amt').forEach(el => el.remove());

        // Collect current values from the expanded table amount inputs
        document.querySelectorAll(`.batch-amt-input[data-batch="${bId}"]`).forEach(input => {
            const h = document.createElement('input');
            h.type      = 'hidden';
            h.name      = `amounts[${input.dataset.itemId}]`;
            h.value     = input.value || input.defaultValue;
            h.className = 'dyn-amt';
            form.appendChild(h);
        });

        document.getElementById('batchReviewNotes').value = result.value || '';
        form.action = url;
        form.submit();
    });
}

function batchReject(url, count, dept) {
    Swal.fire({
        title: count > 1 ? `Reject All ${count} Items?` : 'Reject Request?',
        html: `Reject ${count > 1 ? '<strong>' + count + '</strong> supplementary requests' : 'this request'} from <strong>${dept}</strong>?`,
        input: 'textarea',
        inputLabel: 'Rejection Reason (required)',
        inputPlaceholder: 'Explain why this is being rejected…',
        inputAttributes: { rows: 3 },
        inputValidator: v => (!v || v.trim().length < 10) ? 'Please provide a reason (min 10 characters).' : null,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#64748B',
        confirmButtonText: count > 1 ? `✘ Reject All ${count}` : '✘ Reject',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then(result => {
        if (result.isConfirmed) {
            const form = document.getElementById('batchRejectForm');
            document.getElementById('batchRejectionReason').value = result.value;
            form.action = url;
            form.submit();
        }
    });
}

function promptReject(event, form) {
    event.preventDefault();
    Swal.fire({
        title: 'Reject this item?',
        input: 'textarea',
        inputLabel: 'Rejection Reason (required)',
        inputPlaceholder: 'Explain why this is being rejected…',
        inputAttributes: { rows: 3 },
        inputValidator: v => (!v || v.trim().length < 10) ? 'Please provide a reason (min 10 characters).' : null,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#64748B',
        confirmButtonText: '✘ Reject',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then(result => {
        if (result.isConfirmed) {
            form.querySelector('.rej-reason').value = result.value;
            form.submit();
        }
    });
    return false;
}

function confirmBatchDelete(url, count, dept) {
    Swal.fire({
        title: count > 1 ? 'Delete Entire Batch?' : 'Delete Request?',
        html: `Permanently delete ${count > 1 ? '<strong>' + count + '</strong> pending requests' : 'this request'} from <strong>${dept}</strong>? This cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#64748B',
        confirmButtonText: `Yes, delete ${count > 1 ? 'all ' + count : 'it'}`,
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then(result => {
        if (result.isConfirmed) {
            const form = document.getElementById('deleteForm');
            form.action = url;
            form.submit();
        }
    });
}
</script>
@endsection
