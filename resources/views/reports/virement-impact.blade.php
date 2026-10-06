@extends('layouts.app')
@section('title', 'Virement Impact Report')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Virement Impact Report</h5>
        <p class="text-muted small mb-0">
            <a href="{{ route('reports.index') }}" class="text-muted">Reports</a>
            / Virement Impact
        </p>
    </div>
    @if($period && isset($rows) && $rows->isNotEmpty())
    <a href="{{ route('reports.virement-impact', array_merge(request()->only(['period_id','department_id']), ['export'=>'csv'])) }}"
       class="btn btn-sm" style="background:#F1F5F9;color:#475569;border:1px solid #E2E8F0;border-radius:8px;font-size:12px">
        <i class="fas fa-download me-1"></i> Export CSV
    </a>
    @endif
</div>

<form method="GET" class="chart-card mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Period</label>
            <select name="period_id" class="form-select form-select-sm">
                @foreach($periods as $p)
                <option value="{{ $p->id }}" {{ request('period_id', $period?->id) == $p->id ? 'selected' : '' }}>
                    {{ $p->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Department</label>
            <select name="department_id" class="form-select form-select-sm">
                <option value="">All Departments</option>
                @foreach($departments as $d)
                <option value="{{ $d->id }}" {{ $deptId == $d->id ? 'selected' : '' }}>
                    {{ $d->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm w-100"
                    style="background:var(--navy);color:#fff;border-radius:8px">Filter</button>
        </div>
    </div>
</form>

@if($period && isset($summary))

{{-- Summary --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="chart-card text-center p-3">
            <div class="text-muted small mb-1">Total Virements</div>
            <div class="fs-4 fw-bold" style="color:var(--navy)">{{ $summary['total'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="chart-card text-center p-3">
            <div class="text-muted small mb-1">Approved</div>
            <div class="fs-4 fw-bold text-success">{{ $summary['approved'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="chart-card text-center p-3">
            <div class="text-muted small mb-1">Pending</div>
            <div class="fs-4 fw-bold text-warning">{{ $summary['pending'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="chart-card text-center p-3">
            <div class="text-muted small mb-1">Total Moved (GHS)</div>
            <div class="fs-5 fw-bold" style="color:var(--orange)">
                {{ number_format($summary['total_amount'], 0) }}
            </div>
        </div>
    </div>
</div>

@if($rows->isNotEmpty())
@foreach($rows as $deptRow)
<div class="chart-card mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-semibold mb-0">{{ $deptRow['department']?->name ?? '—' }}</h6>
        <div class="d-flex gap-2">
            <span class="badge bg-success-subtle text-success">
                {{ $deptRow['approved']->count() }} approved
            </span>
            @if($deptRow['pending']->count())
            <span class="badge bg-warning-subtle text-warning">
                {{ $deptRow['pending']->count() }} pending
            </span>
            @endif
            <span class="badge" style="background:var(--navy);color:#fff">
                GHS {{ number_format($deptRow['total_moved'], 0) }} moved
            </span>
        </div>
    </div>

    {{-- Net impact per account code --}}
    @if(!empty($deptRow['net_impact']))
    <div class="mb-3">
        <p class="small fw-semibold text-muted mb-2">Net Impact Per Account Code (approved virements only)</p>
        <div class="d-flex flex-wrap gap-2">
            @foreach($deptRow['net_impact'] as $code => $impact)
            <span class="badge {{ $impact >= 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                {{ $code }}: {{ $impact >= 0 ? '+' : '' }}{{ number_format($impact, 0) }}
            </span>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Individual virements --}}
    <div class="table-responsive">
        <table class="table table-sm align-middle" style="font-size:.82rem">
            <thead>
                <tr class="table-light">
                    <th>From Code</th>
                    <th>To Code</th>
                    <th class="text-end">Amount (GHS)</th>
                    <th>Status</th>
                    <th>Requested By</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($deptRow['all'] as $v)
                @php
                    $vc = ['approved'=>'success','submitted'=>'primary','under_review'=>'info','rejected'=>'danger'][$v->status] ?? 'secondary';
                @endphp
                <tr>
                    <td>{{ $v->fromLineItem?->accountCode?->code ?? '—' }}</td>
                    <td>{{ $v->toLineItem?->accountCode?->code ?? '—' }}</td>
                    <td class="text-end fw-semibold">{{ number_format($v->amount, 0) }}</td>
                    <td><span class="badge bg-{{ $vc }}-subtle text-{{ $vc }}">{{ ucfirst($v->status) }}</span></td>
                    <td>{{ $v->requestedBy?->name ?? '—' }}</td>
                    <td class="text-muted small">{{ $v->created_at?->format('d M Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endforeach
@else
<div class="alert alert-info">No virements found for the selected filters.</div>
@endif

@elseif($period)
<div class="alert alert-info">No virement data found for this period.</div>
@else
<div class="alert alert-warning">Select a period to view virement impact data.</div>
@endif

@endsection
