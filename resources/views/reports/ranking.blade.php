@extends('layouts.app')
@section('title', 'Departmental Ranking')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Departmental Ranking</h5>
        <p class="text-muted small mb-0">
            <a href="{{ route('reports.index') }}" class="text-muted">Reports</a>
            / Departmental Ranking
        </p>
    </div>
    @if($period && isset($rows) && $rows->isNotEmpty())
    <a href="{{ route('reports.ranking', array_merge(request()->only(['period_id']), ['export'=>'csv'])) }}"
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

@if($period && $rows->isNotEmpty())

<div class="chart-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-semibold mb-0">Departmental League Table — {{ $period->name }}</h6>
        <small class="text-muted">Sorted by effective budget (highest first)</small>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle" style="font-size:.85rem">
            <thead>
                <tr class="table-light">
                    <th class="text-center" style="width:40px">#</th>
                    <th>Department</th>
                    <th class="text-end">Approved Budget</th>
                    <th class="text-end">Supplementary</th>
                    <th class="text-end">Effective Budget</th>
                    <th class="text-end">Actuals</th>
                    <th class="text-end">Variance</th>
                    <th class="text-center">Util %</th>
                    <th class="text-center">Days to Submit</th>
                    <th class="text-center">Virements</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $i => $row)
                @php
                    $util = $row['util_pct'];
                    $utilClass = $util === null ? 'text-muted'
                        : ($util > 100 ? 'text-danger fw-bold'
                        : ($util >= 80 ? 'text-success fw-semibold'
                        : 'text-warning'));
                    $varClass = ($row['variance'] ?? 0) >= 0 ? 'text-success' : 'text-danger';
                @endphp
                <tr>
                    <td class="text-center">
                        @if($i === 0)
                        <span style="font-size:1.1rem">🥇</span>
                        @elseif($i === 1)
                        <span style="font-size:1.1rem">🥈</span>
                        @elseif($i === 2)
                        <span style="font-size:1.1rem">🥉</span>
                        @else
                        <span class="text-muted">{{ $i + 1 }}</span>
                        @endif
                    </td>
                    <td class="fw-semibold">{{ $row['department']?->name ?? '—' }}</td>
                    <td class="text-end">{{ number_format($row['approved'], 0) }}</td>
                    <td class="text-end {{ $row['supplementary'] > 0 ? 'text-warning' : 'text-muted' }}">
                        {{ $row['supplementary'] > 0 ? number_format($row['supplementary'], 0) : '—' }}
                    </td>
                    <td class="text-end fw-bold" style="color:var(--navy)">
                        {{ number_format($row['effective'], 0) }}
                    </td>
                    <td class="text-end">{{ number_format($row['actuals'], 0) }}</td>
                    <td class="text-end {{ $varClass }}">
                        {{ number_format($row['variance'], 0) }}
                        @if($row['var_pct'] !== null)
                        <small>({{ $row['var_pct'] }}%)</small>
                        @endif
                    </td>
                    <td class="text-center {{ $utilClass }}">
                        @if($util !== null)
                        {{ $util }}%
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        {{ $row['days_to_submit'] !== null ? $row['days_to_submit'].' d' : '—' }}
                    </td>
                    <td class="text-center">
                        @if($row['virements'] > 0)
                        <span class="badge bg-warning-subtle text-warning">{{ $row['virements'] }}</span>
                        @else
                        <span class="text-muted">0</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-3 small text-muted">
        <i class="bi bi-info-circle me-1"></i>
        <strong>Utilisation %</strong>: Actuals ÷ Effective Budget &nbsp;&middot;&nbsp;
        <span class="text-success fw-semibold">80–100%</span> = healthy &nbsp;&middot;&nbsp;
        <span class="text-danger fw-bold">&gt;100%</span> = over-budget &nbsp;&middot;&nbsp;
        <span class="text-warning">&lt;80%</span> = under-utilised
    </div>
</div>

@elseif($period)
<div class="alert alert-info">No approved budgets found for this period.</div>
@else
<div class="alert alert-warning">Select a period to view departmental rankings.</div>
@endif

@endsection
