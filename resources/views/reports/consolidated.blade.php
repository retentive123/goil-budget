@extends('layouts.app')
@section('title', 'Consolidated Group Report')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Consolidated Group Report</h5>
        <p class="text-muted small mb-0">
            <a href="{{ route('reports.index') }}" class="text-muted">Reports</a>
            / Consolidated Group
        </p>
    </div>
    @if($period && $rows->isNotEmpty())
    <a href="{{ route('reports.consolidated', array_merge(request()->only(['period_id','budget_basis']), ['export'=>'csv'])) }}"
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
            <label class="form-label small fw-semibold mb-1">Budget Basis</label>
            <select name="budget_basis" class="form-select form-select-sm">
                <option value="original" {{ $basis=='original'?'selected':'' }}>Original Approved</option>
                <option value="revised"  {{ $basis=='revised' ?'selected':'' }}>Latest Revision</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm w-100"
                    style="background:var(--navy);color:#fff;border-radius:8px">Apply</button>
        </div>
    </div>
</form>

@if($period && $rows->isNotEmpty())

{{-- Summary cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="chart-card text-center p-3">
            <div class="text-muted small mb-1">Total Entities</div>
            <div class="fs-4 fw-bold" style="color:var(--navy)">{{ $rows->count() }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="chart-card text-center p-3">
            <div class="text-muted small mb-1">Departments</div>
            <div class="fs-4 fw-bold text-primary">{{ $rows->where('type','department')->count() }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="chart-card text-center p-3">
            <div class="text-muted small mb-1">Subsidiaries</div>
            <div class="fs-4 fw-bold text-info">{{ $rows->where('type','subsidiary')->count() }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="chart-card text-center p-3">
            <div class="text-muted small mb-1">Grand Total (GHS)</div>
            <div class="fs-5 fw-bold" style="color:var(--orange)">
                {{ number_format($grandTotals['total'] + $grandTotals['supp'], 2) }}
            </div>
        </div>
    </div>
</div>

<div class="chart-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-semibold mb-0">All Entities — {{ $period->name }}</h6>
        <span class="badge" style="background:var(--navy);color:#fff">
            {{ ucfirst($basis) }} basis
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle" style="font-size:.85rem">
            <thead>
                <tr class="table-light">
                    <th>Entity</th>
                    <th>Type</th>
                    @if(($entryMode ?? 'quarterly') === 'monthly')
                        @foreach(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $mLabel)
                        <th class="text-end">{{ $mLabel }}</th>
                        @endforeach
                    @else
                        <th class="text-end">Q1</th>
                        <th class="text-end">Q2</th>
                        <th class="text-end">Q3</th>
                        <th class="text-end">Q4</th>
                    @endif
                    <th class="text-end">Original</th>
                    <th class="text-end">Supplementary</th>
                    <th class="text-end fw-bold">Effective Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $i => $row)
                <tr>
                    <td class="fw-semibold">
                        {{ $row['name'] }}
                        @if($row['code'])
                        <span class="text-muted small ms-1">({{ $row['code'] }})</span>
                        @endif
                    </td>
                    <td>
                        @if($row['type'] === 'department')
                        <span class="badge bg-primary-subtle text-primary">Department</span>
                        @else
                        <span class="badge bg-info-subtle text-info">Subsidiary</span>
                        @endif
                    </td>
                    @if(($entryMode ?? 'quarterly') === 'monthly')
                        @foreach(range(1,12) as $m)
                        <td class="text-end">{{ number_format($row['m'.$m] ?? 0, 0) }}</td>
                        @endforeach
                    @else
                        <td class="text-end">{{ number_format($row['q1'], 0) }}</td>
                        <td class="text-end">{{ number_format($row['q2'], 0) }}</td>
                        <td class="text-end">{{ number_format($row['q3'], 0) }}</td>
                        <td class="text-end">{{ number_format($row['q4'], 0) }}</td>
                    @endif
                    <td class="text-end">{{ number_format($row['total'], 0) }}</td>
                    <td class="text-end {{ $row['supp'] > 0 ? 'text-warning fw-semibold' : 'text-muted' }}">
                        {{ $row['supp'] > 0 ? number_format($row['supp'], 0) : '—' }}
                    </td>
                    <td class="text-end fw-bold" style="color:var(--navy)">
                        {{ number_format($row['total'] + $row['supp'], 0) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="table-dark fw-bold">
                <tr>
                    <td colspan="2">Grand Total</td>
                    @if(($entryMode ?? 'quarterly') === 'monthly')
                        @foreach(range(1,12) as $m)
                        <td class="text-end">{{ number_format($grandTotals['m'.$m] ?? 0, 0) }}</td>
                        @endforeach
                    @else
                        <td class="text-end">{{ number_format($grandTotals['q1'], 0) }}</td>
                        <td class="text-end">{{ number_format($grandTotals['q2'], 0) }}</td>
                        <td class="text-end">{{ number_format($grandTotals['q3'], 0) }}</td>
                        <td class="text-end">{{ number_format($grandTotals['q4'], 0) }}</td>
                    @endif
                    <td class="text-end">{{ number_format($grandTotals['total'], 0) }}</td>
                    <td class="text-end">{{ number_format($grandTotals['supp'], 0) }}</td>
                    <td class="text-end" style="color:#FFB347">
                        {{ number_format($grandTotals['total'] + $grandTotals['supp'], 0) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@elseif($period)
<div class="alert alert-info">No approved budgets found for this period.</div>
@else
<div class="alert alert-warning">Select a period to view the consolidated group report.</div>
@endif

@endsection
