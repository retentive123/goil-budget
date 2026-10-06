@extends('layouts.app')
@section('title', 'Service Station Performance')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Service Station Performance</h5>
        <p class="text-muted small mb-0">
            <a href="{{ route('reports.index') }}" class="text-muted">Reports</a>
            / Station Performance
        </p>
    </div>
    @if($period && isset($rows) && $rows->isNotEmpty())
    <a href="{{ route('reports.station-performance', array_merge(request()->only(['period_id']), ['export'=>'csv'])) }}"
       class="btn btn-sm" style="background:#F1F5F9;color:#475569;border:1px solid #E2E8F0;border-radius:8px;font-size:12px">
        <i class="fas fa-download me-1"></i> Export CSV
    </a>
    @endif
</div>

@if($stations->isEmpty())
<div class="alert alert-info">
    <i class="bi bi-info-circle me-1"></i>
    No service stations are configured. Departments with entity type <strong>Service Station</strong>
    will appear here.
</div>
@else

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

@if($period && $rows->isNotEmpty())

{{-- Grand total cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="chart-card text-center p-3">
            <div class="text-muted small mb-1">Stations</div>
            <div class="fs-4 fw-bold" style="color:var(--navy)">{{ $rows->count() }}</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="chart-card text-center p-3">
            <div class="text-muted small mb-1">Approved (GHS)</div>
            <div class="fw-bold" style="color:var(--navy);font-size:1rem">
                {{ number_format($grandTotals['effective'], 0) }}
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="chart-card text-center p-3">
            <div class="text-muted small mb-1">Actuals (GHS)</div>
            <div class="fw-bold text-success" style="font-size:1rem">
                {{ number_format($grandTotals['actuals'], 0) }}
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="chart-card text-center p-3">
            <div class="text-muted small mb-1">Variance (GHS)</div>
            <div class="fw-bold {{ $grandTotals['variance'] >= 0 ? 'text-success' : 'text-danger' }}" style="font-size:1rem">
                {{ number_format($grandTotals['variance'], 0) }}
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="chart-card text-center p-3">
            <div class="text-muted small mb-1">Total Virements (GHS)</div>
            <div class="fw-bold text-warning" style="font-size:1rem">
                {{ number_format($grandTotals['virements'], 0) }}
            </div>
        </div>
    </div>
</div>

{{-- Zone breakdown --}}
@foreach($zoneGroups as $zone => $zoneRows)
<div class="chart-card mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-semibold mb-0">
            <i class="bi bi-geo-alt me-1" style="color:var(--orange)"></i>
            {{ $zone }}
        </h6>
        <small class="text-muted">{{ $zoneRows->count() }} station(s)</small>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle" style="font-size:.85rem">
            <thead>
                <tr class="table-light">
                    <th>Station</th>
                    <th>Status</th>
                    <th class="text-end">Approved (GHS)</th>
                    <th class="text-end">Supp (GHS)</th>
                    <th class="text-end">Effective (GHS)</th>
                    <th class="text-end">Actuals (GHS)</th>
                    <th class="text-end">Variance (GHS)</th>
                    <th class="text-center">Util %</th>
                    <th class="text-end">Virements (GHS)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($zoneRows->sortByDesc('effective') as $row)
                @php
                    $util = $row['util_pct'];
                    $utilClass = $util === null ? 'text-muted'
                        : ($util > 100 ? 'text-danger fw-bold'
                        : ($util >= 80 ? 'text-success fw-semibold'
                        : 'text-warning'));
                    $statuses = ['approved'=>'success','under_review'=>'info','submitted'=>'primary',
                                 'rejected'=>'danger','not_submitted'=>'danger','draft'=>'secondary'];
                    $sc = $statuses[$row['status']] ?? 'secondary';
                @endphp
                <tr>
                    <td class="fw-semibold">{{ $row['station']->name }}</td>
                    <td>
                        <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }}">
                            {{ ucfirst(str_replace('_',' ',$row['status'])) }}
                        </span>
                    </td>
                    <td class="text-end">{{ number_format($row['approved'], 0) }}</td>
                    <td class="text-end {{ $row['supp'] > 0 ? 'text-warning' : 'text-muted' }}">
                        {{ $row['supp'] > 0 ? number_format($row['supp'], 0) : '—' }}
                    </td>
                    <td class="text-end fw-bold" style="color:var(--navy)">
                        {{ number_format($row['effective'], 0) }}
                    </td>
                    <td class="text-end">{{ number_format($row['actuals'], 0) }}</td>
                    <td class="text-end {{ $row['variance'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($row['variance'], 0) }}
                    </td>
                    <td class="text-center {{ $utilClass }}">
                        {{ $util !== null ? $util.'%' : '—' }}
                    </td>
                    <td class="text-end {{ $row['virements'] > 0 ? 'text-warning' : 'text-muted' }}">
                        {{ $row['virements'] > 0 ? number_format($row['virements'], 0) : '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="table-light fw-bold">
                    <td colspan="4">Zone Total</td>
                    <td class="text-end">{{ number_format($zoneRows->sum('effective'), 0) }}</td>
                    <td class="text-end">{{ number_format($zoneRows->sum('actuals'), 0) }}</td>
                    <td class="text-end">{{ number_format($zoneRows->sum('variance'), 0) }}</td>
                    <td></td>
                    <td class="text-end">{{ number_format($zoneRows->sum('virements'), 0) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endforeach

@elseif($period)
<div class="alert alert-info">No approved budgets found for service stations in this period.</div>
@else
<div class="alert alert-warning">Select a period to view station performance data.</div>
@endif

@endif
@endsection
