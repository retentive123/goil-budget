@extends('layouts.app')
@section('title', 'Supplementary Budgets')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Supplementary Budget Requests</h5>
        <p class="text-muted small mb-0">
            Requests for additional budget beyond the approved amount.
        </p>
    </div>
    <div class="d-flex gap-2">
        @can('approve supplementary budget')
        <a href="{{ route('supplementary.pending') }}"
           class="btn btn-sm btn-outline-warning">
            Pending Approvals
        </a>
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
            <select name="period_id" class="form-select form-select-sm"
                    onchange="this.form.submit()">
                <option value="">All Periods</option>
                @foreach($periods as $p)
                <option value="{{ $p->id }}"
                    {{ request('period_id') == $p->id ? 'selected' : '' }}>
                    {{ $p->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Status</label>
            <select name="status" class="form-select form-select-sm"
                    onchange="this.form.submit()">
                <option value="">All</option>
                <option value="submitted"   {{ request('status')==='submitted'?'selected':'' }}>Submitted</option>
                <option value="under_review"{{ request('status')==='under_review'?'selected':'' }}>Under Review</option>
                <option value="approved"    {{ request('status')==='approved'?'selected':'' }}>Approved</option>
                <option value="rejected"    {{ request('status')==='rejected'?'selected':'' }}>Rejected</option>
            </select>
        </div>
    </div>
</form>

<div class="chart-card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead style="font-size:11px;text-transform:uppercase;
                          letter-spacing:.5px;color:var(--slate)">
                <tr>
                    <th>Department</th>
                    <th>Account Code</th>
                    <th>Type</th>
                    <th class="text-end">Original (GHS)</th>
                    <th class="text-end">Requested (GHS)</th>
                    <th class="text-end">Approved (GHS)</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($supplementaries as $s)
                <tr>
                    <td class="small fw-semibold">{{ $s->department->name }}</td>
                    <td class="small">
                        <code>{{ $s->accountCode->code }}</code>
                        <div style="font-size:10px;color:var(--slate)">
                            {{ $s->accountCode->name }}
                        </div>
                    </td>
                    <td>
                        <span style="padding:2px 8px;border-radius:20px;font-size:10px;
                                     font-weight:600;
                                     background:{{ $s->line_type==='revenue'?'#D1FAE5':'#FEE2E2' }};
                                     color:{{ $s->line_type==='revenue'?'#065F46':'#991B1B' }}">
                            {{ ucfirst($s->line_type) }}
                        </span>
                    </td>
                    <td class="text-end small">{{ number_format($s->original_amount,2) }}</td>
                    <td class="text-end small fw-semibold" style="color:var(--navy)">
                        {{ number_format($s->requested_amount,2) }}
                    </td>
                    <td class="text-end small" style="color:#10B981">
                        {{ $s->approved_amount ? number_format($s->approved_amount,2) : '—' }}
                    </td>
                    <td>
                        <span style="padding:2px 10px;border-radius:20px;font-size:11px;
                                     font-weight:600;
                                     background:{{
                                        match($s->status) {
                                            'approved'     => '#D1FAE5',
                                            'rejected'     => '#FEE2E2',
                                            'submitted'    => '#DBEAFE',
                                            'under_review' => '#FEF3C7',
                                            default        => '#F1F5F9'
                                        }
                                     }};
                                     color:{{
                                        match($s->status) {
                                            'approved'     => '#065F46',
                                            'rejected'     => '#991B1B',
                                            'submitted'    => '#1E40AF',
                                            'under_review' => '#92400E',
                                            default        => '#475569'
                                        }
                                     }}">
                            {{ ucfirst(str_replace('_',' ',$s->status)) }}
                        </span>
                    </td>
                    <td class="small text-muted">
                        {{ $s->submitted_at?->format('d M Y') ?? '—' }}
                    </td>
                    <td>
                        <a href="{{ route('supplementary.show', $s) }}"
                           class="btn btn-sm btn-outline-secondary"
                           style="font-size:11px;padding:2px 10px">
                            View
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-5">
                        No supplementary budget requests.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $supplementaries->links() }}</div>
</div>
@endsection
