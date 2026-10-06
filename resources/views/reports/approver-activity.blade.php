@extends('layouts.app')
@section('title', 'Approver Activity Report')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Approver Activity Report</h5>
        <p class="text-muted small mb-0">
            <a href="{{ route('reports.index') }}" class="text-muted">Reports</a>
            / Approver Activity
        </p>
    </div>
    @if($period && isset($rows) && $rows->isNotEmpty())
    <a href="{{ route('reports.approver-activity', array_merge(request()->only(['period_id']), ['export'=>'csv'])) }}"
       class="btn btn-sm" style="background:#F1F5F9;color:#475569;border:1px solid #E2E8F0;border-radius:8px;font-size:12px">
        <i class="fas fa-download me-1"></i> Export CSV
    </a>
    @endif
</div>

<form method="GET" class="chart-card mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small fw-semibold mb-1">Period</label>
            <select name="period_id" class="form-select form-select-sm">
                @foreach($periods as $p)
                <option value="{{ $p->id }}" {{ request('period_id', $period?->id) == $p->id ? 'selected' : '' }}>
                    {{ $p->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm w-100"
                    style="background:var(--navy);color:#fff;border-radius:8px">Apply</button>
        </div>
    </div>
</form>

@if($period && isset($summary))

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="chart-card text-center p-3">
            <div class="text-muted small mb-1">Active Approvers</div>
            <div class="fs-4 fw-bold" style="color:var(--navy)">{{ $summary['total_approvers'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="chart-card text-center p-3">
            <div class="text-muted small mb-1">Total Actions</div>
            <div class="fs-4 fw-bold text-primary">{{ $summary['total_actions'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="chart-card text-center p-3">
            <div class="text-muted small mb-1">Approvals</div>
            <div class="fs-4 fw-bold text-success">{{ $summary['total_approved'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="chart-card text-center p-3">
            <div class="text-muted small mb-1">Rejections</div>
            <div class="fs-4 fw-bold text-danger">{{ $summary['total_rejected'] }}</div>
        </div>
    </div>
</div>

<div class="chart-card">
    <h6 class="fw-semibold mb-3">Approver Activity — {{ $period->name }}</h6>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle" style="font-size:.85rem">
            <thead>
                <tr class="table-light">
                    <th>Approver</th>
                    <th>Roles</th>
                    <th class="text-center">Approvals</th>
                    <th class="text-center">Rejections</th>
                    <th class="text-center">Total Actions</th>
                    <th class="text-center">Pending Items</th>
                    <th class="text-center">Activity</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                @php
                    $total = $row['total_actions'];
                    $maxTotal = $rows->max('total_actions');
                    $barPct = $maxTotal > 0 ? round(($total / $maxTotal) * 100) : 0;
                @endphp
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $row['approver']->name }}</div>
                        <small class="text-muted">{{ $row['approver']->email }}</small>
                    </td>
                    <td>
                        @foreach(explode(', ', $row['roles']) as $role)
                        <span class="badge bg-secondary-subtle text-secondary me-1" style="font-size:.72rem">
                            {{ str_replace('_', ' ', $role) }}
                        </span>
                        @endforeach
                    </td>
                    <td class="text-center">
                        @if($row['approved'] > 0)
                        <span class="badge bg-success-subtle text-success">{{ $row['approved'] }}</span>
                        @else
                        <span class="text-muted">0</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($row['rejected'] > 0)
                        <span class="badge bg-danger-subtle text-danger">{{ $row['rejected'] }}</span>
                        @else
                        <span class="text-muted">0</span>
                        @endif
                    </td>
                    <td class="text-center fw-bold">
                        {{ $total }}
                    </td>
                    <td class="text-center">
                        @if($row['pending'] > 0)
                        <span class="badge bg-warning-subtle text-warning">{{ $row['pending'] }}</span>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td style="min-width:120px">
                        <div style="background:#e9ecef;border-radius:4px;height:8px;overflow:hidden">
                            <div style="background:var(--orange);height:100%;width:{{ $barPct }}%;border-radius:4px;
                                        transition:width .3s"></div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="small text-muted mt-2">
        <i class="bi bi-info-circle me-1"></i>
        Action counts are derived from audit logs for the period
        {{ $period->opened_at?->format('d M Y') ?? 'opened' }}
        – {{ $period->closed_at?->format('d M Y') ?? 'present' }}.
    </p>
</div>

@elseif($period)
<div class="alert alert-info">No approver activity data found for this period.</div>
@else
<div class="alert alert-warning">Select a period to view approver activity.</div>
@endif

@endsection
