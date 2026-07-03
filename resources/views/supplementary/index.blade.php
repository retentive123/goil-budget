@extends('layouts.app')
@section('title', 'Supplementary Budgets')
@section('content')

@php
$statusStyle = [
    'approved'     => ['bg' => '#D1FAE5', 'text' => '#065F46',  'label' => 'Approved'],
    'rejected'     => ['bg' => '#FEE2E2', 'text' => '#991B1B',  'label' => 'Rejected'],
    'submitted'    => ['bg' => '#DBEAFE', 'text' => '#1E40AF',  'label' => 'Submitted'],
    'under_review' => ['bg' => '#FEF3C7', 'text' => '#92400E',  'label' => 'Under Review'],
    'draft'        => ['bg' => '#F1F5F9', 'text' => '#475569',  'label' => 'Draft'],
    'mixed'        => ['bg' => '#E2E8F0', 'text' => '#334155',  'label' => 'Mixed'],
];
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Supplementary Budget Requests</h5>
        <p class="text-muted small mb-0">Requests for additional budget beyond the approved amount.</p>
    </div>
    <div class="d-flex gap-2">
        @can('approve supplementary budget')
        <a href="{{ route('supplementary.pending') }}"
           class="btn btn-sm btn-outline-warning">Pending Approvals</a>
        @endcan
        @can('request supplementary budget')
        <a href="{{ route('supplementary.create') }}"
           class="btn btn-sm"
           style="background:var(--navy);color:#fff;border-radius:8px">
            + New Request
        </a>
        @endcan
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="chart-card mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Period</label>
            <select name="period_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Periods</option>
                @foreach($periods as $p)
                <option value="{{ $p->id }}" {{ request('period_id') == $p->id ? 'selected' : '' }}>
                    {{ $p->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Status</label>
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="submitted"    {{ request('status')==='submitted'    ? 'selected':'' }}>Submitted</option>
                <option value="under_review" {{ request('status')==='under_review' ? 'selected':'' }}>Under Review</option>
                <option value="approved"     {{ request('status')==='approved'     ? 'selected':'' }}>Approved</option>
                <option value="rejected"     {{ request('status')==='rejected'     ? 'selected':'' }}>Rejected</option>
            </select>
        </div>
    </div>
</form>

{{-- Batch list --}}
@forelse($batches as $batchIdx => $items)
@php
    $first      = $items->first();
    $count      = $items->count();
    $statuses   = $items->pluck('status')->unique();
    $status     = $statuses->count() === 1 ? $statuses->first() : 'mixed';
    $ss         = $statusStyle[$status] ?? $statusStyle['draft'];
    $totReq     = $items->sum('requested_amount');
    $totApproved = $items->sum('approved_amount');
    $hasApproved = $items->whereNotNull('approved_amount')->isNotEmpty();
    $bId            = 'batch-' . $batchIdx;
    $deletableCount = $items->whereIn('status', ['submitted','under_review','draft'])->count();
    $isBatch        = $first->batch_id !== null;
@endphp

<div class="chart-card mb-3 p-0" style="overflow:hidden">

    {{-- Batch header row --}}
    <div class="d-flex align-items-center gap-3 px-4 py-3"
         style="cursor:pointer;user-select:none"
         onclick="toggleBatch('{{ $bId }}', this)">

        {{-- Toggle arrow --}}
        <span class="bt-arr"
              style="font-size:12px;color:var(--slate);display:inline-block;
                     transition:transform .2s;flex-shrink:0">▶</span>

        {{-- Dept + meta --}}
        <div style="flex:1;min-width:0">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span style="font-size:14px;font-weight:700;color:var(--navy)">
                    {{ $first->department->name }}
                </span>
                <span style="font-size:11px;color:var(--slate)">
                    {{ $first->period->name }}
                </span>
                <span style="font-size:11px;background:#F1F5F9;color:var(--slate);
                             border-radius:12px;padding:1px 9px">
                    {{ $count }} {{ Str::plural('item', $count) }}
                </span>
            </div>
            <div style="font-size:11px;color:var(--slate);margin-top:2px">
                Submitted by {{ $first->requestedBy->name }}
                &middot; {{ $first->submitted_at?->format('d M Y H:i') }}
            </div>
        </div>

        {{-- Totals --}}
        <div class="text-end" style="flex-shrink:0;min-width:130px">
            <div style="font-size:11px;color:var(--slate)">Requested</div>
            <div style="font-size:14px;font-weight:700;color:var(--navy)">
                GHS {{ number_format($totReq, 2) }}
            </div>
        </div>

        @if($hasApproved)
        <div class="text-end" style="flex-shrink:0;min-width:130px">
            <div style="font-size:11px;color:var(--slate)">Approved</div>
            <div style="font-size:14px;font-weight:700;color:#10B981">
                GHS {{ number_format($totApproved, 2) }}
            </div>
        </div>
        @endif

        {{-- Status badge --}}
        <span style="flex-shrink:0;padding:4px 14px;border-radius:20px;font-size:11px;
                     font-weight:700;white-space:nowrap;
                     background:{{ $ss['bg'] }};color:{{ $ss['text'] }}">
            {{ $ss['label'] }}
        </span>

        {{-- Batch / solo delete --}}
        @can('approve supplementary budget')
        @if($deletableCount > 0)
        <button type="button"
                class="btn btn-sm btn-outline-danger"
                style="flex-shrink:0;font-size:11px;padding:3px 12px;white-space:nowrap"
                onclick="event.stopPropagation();
                         @if($isBatch)
                         confirmBatchDelete(
                             '{{ route('supplementary.destroy-batch', $first->batch_id) }}',
                             {{ $deletableCount }},
                             '{{ addslashes($first->department->name) }}'
                         )
                         @else
                         confirmDelete(
                             '{{ route('supplementary.destroy', $first->id) }}',
                             '{{ addslashes($first->accountCode->code) }}',
                             '{{ addslashes($first->department->name) }}'
                         )
                         @endif">
            🗑 {{ $isBatch ? 'Delete Batch' : 'Delete' }}
            @if($isBatch && $deletableCount < $count)
                <span style="opacity:.7">({{ $deletableCount }}/{{ $count }})</span>
            @endif
        </button>
        @endif
        @endcan
    </div>

    {{-- Expandable items table --}}
    <div id="{{ $bId }}" style="display:none;border-top:1px solid #E2E8F0">

        {{-- Justification (shared across batch) --}}
        <div class="px-4 py-2" style="background:#FAFAFA;border-bottom:1px solid #E2E8F0;
                                       font-size:12px;color:var(--slate)">
            <span class="fw-semibold">Justification:</span>
            {{ Str::limit($first->justification, 300) }}
        </div>

        <div class="table-responsive">
            <table class="table table-sm mb-0" style="font-size:12px">
                <thead>
                    <tr style="background:#F8FAFC;color:var(--slate);font-size:11px;
                               text-transform:uppercase;letter-spacing:.4px">
                        <th class="ps-4">Account Code</th>
                        <th>Name</th>
                        <th class="text-end">Original (GHS)</th>
                        <th class="text-end">Requested (GHS)</th>
                        <th class="text-end">Approved (GHS)</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($items as $s)
                @php $ist = $statusStyle[$s->status] ?? $statusStyle['draft']; @endphp
                <tr>
                    <td class="align-middle ps-4">
                        <code style="font-size:11px">{{ $s->accountCode->code }}</code>
                    </td>
                    <td class="align-middle" style="color:#374151">
                        {{ $s->accountCode->name }}
                    </td>
                    <td class="text-end align-middle">
                        {{ number_format($s->original_amount, 2) }}
                    </td>
                    <td class="text-end align-middle fw-semibold" style="color:var(--navy)">
                        {{ number_format($s->requested_amount, 2) }}
                    </td>
                    <td class="text-end align-middle" style="color:#10B981">
                        {{ $s->approved_amount ? number_format($s->approved_amount, 2) : '—' }}
                    </td>
                    <td class="align-middle">
                        <span style="padding:2px 10px;border-radius:20px;font-size:10px;
                                     font-weight:700;
                                     background:{{ $ist['bg'] }};color:{{ $ist['text'] }}">
                            {{ $ist['label'] }}
                        </span>
                        @if($s->status === 'rejected' && $s->rejection_reason)
                        <div style="font-size:10px;color:#991B1B;margin-top:2px">
                            {{ Str::limit($s->rejection_reason, 60) }}
                        </div>
                        @endif
                    </td>
                    <td class="align-middle text-end pe-3" style="white-space:nowrap">
                        <a href="{{ route('supplementary.show', $s) }}"
                           class="btn btn-sm btn-outline-secondary"
                           style="font-size:11px;padding:2px 10px">
                            View
                        </a>
                        @if(in_array($s->status, ['submitted','under_review','draft']))
                        @can('approve supplementary budget')
                        <button type="button"
                                class="btn btn-sm btn-outline-danger ms-1"
                                style="font-size:11px;padding:2px 10px"
                                onclick="confirmDelete('{{ route('supplementary.destroy', $s) }}',
                                                      '{{ addslashes($s->accountCode->code) }}',
                                                      '{{ addslashes($s->department->name) }}')">
                            Delete
                        </button>
                        @endcan
                        @endif
                    </td>
                </tr>
                @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:#F8FAFC;font-size:12px;font-weight:700">
                        <td class="ps-4" colspan="2" style="color:var(--slate)">Total</td>
                        <td class="text-end" style="color:var(--navy)">
                            {{ number_format($items->sum('original_amount'), 2) }}
                        </td>
                        <td class="text-end" style="color:var(--navy)">
                            {{ number_format($totReq, 2) }}
                        </td>
                        <td class="text-end" style="color:#10B981">
                            {{ $hasApproved ? number_format($totApproved, 2) : '—' }}
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
    No supplementary budget requests found.
</div>
@endforelse

<div class="mt-3">{{ $supplementaries->links() }}</div>

{{-- Hidden delete form --}}
<form id="deleteForm" method="POST" style="display:none">
    @csrf
    @method('DELETE')
</form>

<script>
function toggleBatch(id, hdr) {
    const body = document.getElementById(id);
    const arr  = hdr.querySelector('.bt-arr');
    const open = body.style.display === 'none';
    body.style.display  = open ? '' : 'none';
    arr.style.transform = open ? 'rotate(90deg)' : '';
}

function confirmDelete(url, code, dept) {
    Swal.fire({
        title: 'Delete Request?',
        html: `Remove the supplementary request for <strong>${code}</strong> from <strong>${dept}</strong>? This cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#64748B',
        confirmButtonText: 'Yes, delete it',
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

function confirmBatchDelete(url, count, dept) {
    Swal.fire({
        title: 'Delete Entire Batch?',
        html: `This will permanently delete all <strong>${count} pending request(s)</strong> from <strong>${dept}</strong>. This cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#64748B',
        confirmButtonText: `Yes, delete ${count} item(s)`,
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
