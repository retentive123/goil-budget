@extends('layouts.app')
@section('title', 'Budget Compliance Report')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Budget Compliance Report</h5>
        <p class="text-muted small mb-0">
            <a href="{{ route('reports.index') }}" class="text-muted">Reports</a>
            / Compliance
        </p>
    </div>
    @if($period && isset($rows) && $rows->isNotEmpty())
    <a href="{{ route('reports.compliance', array_merge(request()->only(['period_id','department_id']), ['export'=>'csv'])) }}"
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

@if($period && isset($summary) && $summary['total'] > 0)

{{-- Summary pills --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="chart-card text-center p-3">
            <div class="text-muted small mb-1">Total Depts</div>
            <div class="fs-4 fw-bold" style="color:var(--navy)">{{ $summary['total'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="chart-card text-center p-3">
            <div class="text-muted small mb-1">Approved</div>
            <div class="fs-4 fw-bold text-success">{{ $summary['approved'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="chart-card text-center p-3">
            <div class="text-muted small mb-1">Pending</div>
            <div class="fs-4 fw-bold text-warning">{{ $summary['pending'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="chart-card text-center p-3">
            <div class="text-muted small mb-1">Not Submitted</div>
            <div class="fs-4 fw-bold text-danger">{{ $summary['not_submitted'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="chart-card text-center p-3">
            <div class="text-muted small mb-1">Avg Submit Days</div>
            <div class="fs-4 fw-bold" style="color:var(--orange)">{{ $summary['avg_days'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="chart-card text-center p-3">
            <div class="text-muted small mb-1">Avg Approval Days</div>
            <div class="fs-4 fw-bold text-info">{{ $summary['avg_approval'] }}</div>
        </div>
    </div>
</div>

<div class="chart-card">
    <h6 class="fw-semibold mb-3">Departmental Compliance — {{ $period->name }}</h6>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle" style="font-size:.85rem">
            <thead>
                <tr class="table-light">
                    <th>Department</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th class="text-center">Days to Submit</th>
                    <th class="text-center">On Time?</th>
                    <th class="text-center">Approval Days</th>
                    <th class="text-center">Revisions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows->sortBy(fn($r) => $r['days_to_submit'] ?? 999) as $row)
                <tr>
                    <td class="fw-semibold">{{ $row['department']->name }}</td>
                    <td>
                        @php
                            $statusColors = [
                                'approved'     => 'success',
                                'under_review' => 'info',
                                'submitted'    => 'primary',
                                'rejected'     => 'danger',
                                'draft'        => 'secondary',
                                'not_submitted'=> 'danger',
                            ];
                            $c = $statusColors[$row['status']] ?? 'secondary';
                        @endphp
                        <span class="badge bg-{{ $c }}-subtle text-{{ $c }}">
                            {{ ucfirst(str_replace('_',' ',$row['status'])) }}
                        </span>
                    </td>
                    <td class="text-muted small">
                        {{ $row['submitted_at'] ? $row['submitted_at']->format('d M Y') : '—' }}
                    </td>
                    <td class="text-center">
                        {{ $row['days_to_submit'] !== null ? $row['days_to_submit'].' d' : '—' }}
                    </td>
                    <td class="text-center">
                        @if($row['on_time'] === true)
                            <i class="bi bi-check-circle-fill text-success"></i>
                        @elseif($row['on_time'] === false)
                            <i class="bi bi-x-circle-fill text-danger"></i>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        {{ $row['approval_days'] !== null ? $row['approval_days'].' d' : '—' }}
                    </td>
                    <td class="text-center">
                        @if($row['revisions'] > 0)
                        <span class="badge bg-warning-subtle text-warning">{{ $row['revisions'] }}</span>
                        @else
                        <span class="text-muted">0</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@elseif($period)
<div class="alert alert-info">No budget submissions found for this period.</div>
@else
<div class="alert alert-warning">Select a period to view compliance data.</div>
@endif

@endsection
