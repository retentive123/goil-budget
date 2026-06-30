@extends('layouts.app')
@section('title', 'Year-over-Year Comparison')
@section('content')

<div class="mb-4">
    <h5 class="fw-bold mb-0">Year-over-Year Comparison</h5>
    <p class="text-muted small">
        <a href="{{ route('reports.index') }}" class="text-muted">Reports</a> / YoY
    </p>
</div>

{{-- Filters --}}
<form method="GET" class="chart-card mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">
                Period A <span style="color:var(--slate)">(Base)</span>
            </label>
            <select name="period_a" class="form-select form-select-sm">
                @foreach($periods as $p)
                <option value="{{ $p->id }}"
                    {{ request('period_a', $periodA?->id) == $p->id ? 'selected' : '' }}>
                    {{ $p->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">
                Period B <span style="color:var(--slate)">(Compare)</span>
            </label>
            <select name="period_b" class="form-select form-select-sm">
                @foreach($periods as $p)
                <option value="{{ $p->id }}"
                    {{ request('period_b', $periodB?->id) == $p->id ? 'selected' : '' }}>
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
                <option value="{{ $d->id }}"
                    {{ request('department_id') == $d->id ? 'selected' : '' }}>
                    {{ $d->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm w-100"
                    style="background:var(--navy);color:#fff;border-radius:8px">
                Compare
            </button>
        </div>
    </div>
</form>

@if($comparison && isset($comparison['rows']) && count($comparison['rows']))

{{-- ── Grand total KPI cards ── --}}
@php
    $budgetChangeTotal = $comparison['budget_change'];
    $actualChangeTotal = $comparison['actual_change'];
    $budgetChangePctTotal = $comparison['budget_total_a'] > 0
        ? round(($budgetChangeTotal / $comparison['budget_total_a']) * 100, 1) : 0;
    $actualChangePctTotal = $comparison['actual_total_a'] > 0
        ? round(($actualChangeTotal / $comparison['actual_total_a']) * 100, 1) : 0;
    $utilA = $comparison['budget_total_a'] > 0
        ? round(($comparison['actual_total_a'] / $comparison['budget_total_a']) * 100, 1) : 0;
    $utilB = $comparison['budget_total_b'] > 0
        ? round(($comparison['actual_total_b'] / $comparison['budget_total_b']) * 100, 1) : 0;
@endphp

<div class="row g-3 mb-4">

    {{-- Period A --}}
    <div class="col-md-3">
        <div class="chart-card h-100"
             style="border-top:4px solid var(--slate)">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;
                        letter-spacing:.5px;color:var(--slate);margin-bottom:8px">
                {{ $periodA->name }} — Budget
            </div>
            <div style="font-size:22px;font-weight:700;color:var(--navy)">
                GHS {{ number_format($comparison['budget_total_a'], 0) }}
            </div>

            @if($comparison['supplementary_total_a'] > 0)
        <div style="font-size:11px;color:#92400E;margin-top:2px">
            (Original: {{ number_format($comparison['original_total_a'],0) }}
             + Suppl: {{ number_format($comparison['supplementary_total_a'],0) }})
        </div>
        @endif

            <div style="font-size:12px;color:#10B981;margin-top:4px">
                Actual: GHS {{ number_format($comparison['actual_total_a'], 0) }}
            </div>
            <div style="font-size:11px;color:var(--slate);margin-top:2px">
                Utilisation: {{ $utilA }}%
            </div>
            <div class="progress mt-2" style="height:4px">
                <div class="progress-bar"
                     style="width:{{ min($utilA,100) }}%;
                            background:{{ $utilA>90?'#F43F5E':($utilA>70?'#F59E0B':'#10B981') }}">
                </div>
            </div>
        </div>
    </div>

    {{-- Period B --}}
    <div class="col-md-3">
        <div class="chart-card h-100"
             style="border-top:4px solid var(--navy)">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;
                        letter-spacing:.5px;color:var(--navy);margin-bottom:8px">
                {{ $periodB->name }} — Budget
            </div>
            <div style="font-size:22px;font-weight:700;color:var(--navy)">
                GHS {{ number_format($comparison['budget_total_b'], 0) }}
            </div>
            <div style="font-size:12px;color:#10B981;margin-top:4px">
                Actual: GHS {{ number_format($comparison['actual_total_b'], 0) }}
            </div>
            <div style="font-size:11px;color:var(--slate);margin-top:2px">
                Utilisation: {{ $utilB }}%
            </div>
            <div class="progress mt-2" style="height:4px">
                <div class="progress-bar"
                     style="width:{{ min($utilB,100) }}%;
                            background:{{ $utilB>90?'#F43F5E':($utilB>70?'#F59E0B':'#10B981') }}">
                </div>
            </div>
        </div>
    </div>

    {{-- Budget change --}}
    <div class="col-md-3">
        <div class="chart-card h-100"
             style="border-top:4px solid {{ $budgetChangeTotal >= 0 ? '#F43F5E' : '#10B981' }}">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;
                        letter-spacing:.5px;color:var(--slate);margin-bottom:8px">
                Budget Change
            </div>
            <div style="font-size:22px;font-weight:700;
                        color:{{ $budgetChangeTotal >= 0 ? '#F43F5E' : '#10B981' }}">
                {{ $budgetChangeTotal >= 0 ? '+' : '' }}GHS {{ number_format($budgetChangeTotal, 0) }}
            </div>
            <div style="font-size:12px;color:var(--slate);margin-top:4px">
                {{ $budgetChangePctTotal >= 0 ? '+' : '' }}{{ $budgetChangePctTotal }}%
                from {{ $periodA->name }}
            </div>
            <div style="font-size:11px;margin-top:6px">
                @if($budgetChangeTotal > 0)
                    <span style="color:#F43F5E">↑ Budget increased</span>
                @elseif($budgetChangeTotal < 0)
                    <span style="color:#10B981">↓ Budget decreased</span>
                @else
                    <span style="color:var(--slate)">→ No change</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Actual change --}}
    <div class="col-md-3">
        <div class="chart-card h-100"
             style="border-top:4px solid {{ $actualChangeTotal >= 0 ? '#F43F5E' : '#10B981' }}">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;
                        letter-spacing:.5px;color:var(--slate);margin-bottom:8px">
                Actual Spend Change
            </div>
            <div style="font-size:22px;font-weight:700;
                        color:{{ $actualChangeTotal >= 0 ? '#F43F5E' : '#10B981' }}">
                {{ $actualChangeTotal >= 0 ? '+' : '' }}GHS {{ number_format($actualChangeTotal, 0) }}
            </div>
            <div style="font-size:12px;color:var(--slate);margin-top:4px">
                {{ $actualChangePctTotal >= 0 ? '+' : '' }}{{ $actualChangePctTotal }}%
                from {{ $periodA->name }}
            </div>
            <div style="font-size:11px;margin-top:6px">
                @if($actualChangeTotal > 0)
                    <span style="color:#F43F5E">↑ Spending increased</span>
                @elseif($actualChangeTotal < 0)
                    <span style="color:#10B981">↓ Spending decreased</span>
                @else
                    <span style="color:var(--slate)">→ No change</span>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- ── Charts row ── --}}
<div class="row g-3 mb-4">

    {{-- Grouped bar: Budget A vs Budget B vs Actual A vs Actual B (top 12) --}}
    <div class="col-md-8">
        <div class="chart-card h-100">
            <div class="chart-title">
                Budget & Actual Comparison — Top 12 Codes by Change
            </div>
            <canvas id="yoyGrouped" height="200"></canvas>
        </div>
    </div>

    {{-- Quarterly comparison donut pair --}}
    <div class="col-md-4">
        <div class="chart-card h-100">
            <div class="chart-title">Overall Budget vs Actual</div>

            {{-- Period A mini bar --}}
            <div style="font-size:11px;font-weight:600;color:var(--slate);
                        margin-bottom:6px">
                {{ $periodA->name }}
            </div>
            <canvas id="periodABar" height="80"></canvas>

            <div style="border-top:1px solid var(--border);margin:14px 0"></div>

            <div style="font-size:11px;font-weight:600;color:var(--slate);
                        margin-bottom:6px">
                {{ $periodB->name }}
            </div>
            <canvas id="periodBBar" height="80"></canvas>
        </div>
    </div>

</div>

{{-- ── Quarterly side-by-side ── --}}
<div class="chart-card mb-4">
    <div class="chart-title">
        Quarterly Budget vs Actual —
        {{ $periodA->name }} vs {{ $periodB->name }}
    </div>
    <canvas id="quarterlyCompare" height="130"></canvas>
</div>

{{-- ── Full code table ── --}}
<div class="chart-card">
    <div class="chart-title">Full Code-Level Comparison</div>

    {{-- Tab toggle --}}
    <div class="d-flex gap-2 mb-3">
        <button type="button" onclick="showView('budget')"
                id="btn_budget"
                class="btn btn-sm"
                style="background:var(--navy);color:#fff;border-radius:6px;font-size:12px">
            Budget View
        </button>
        <button type="button" onclick="showView('actual')"
                id="btn_actual"
                class="btn btn-sm btn-outline-secondary"
                style="border-radius:6px;font-size:12px">
            Actual View
        </button>
        <button type="button" onclick="showView('combined')"
                id="btn_combined"
                class="btn btn-sm btn-outline-secondary"
                style="border-radius:6px;font-size:12px">
            Combined View
        </button>
    </div>

    {{-- Budget view --}}
    <div id="view_budget" class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead style="font-size:11px;text-transform:uppercase;
                          letter-spacing:.5px;color:var(--slate)">
                <tr>
                    <th>Code</th>
                    <th>Account</th>
                    <th>Category</th>
                    <th class="text-end">{{ $periodA->name }} Budget (eff.)</th>
                    <th class="text-end">{{ $periodB->name }} Budget (eff.)</th>
                    <th class="text-end">Change (GHS)</th>
                    <th class="text-end">Change %</th>
                    <th class="text-center">Trend</th>
                </tr>
            </thead>
            <tbody>
                @foreach($comparison['rows'] as $row)
                <tr>
                    <td style="font-family:monospace;font-weight:600;font-size:12px">{{ $row['code'] }}</td>
                    <td class="small">{{ $row['name'] }}</td>
                    <td class="small text-muted">{{ $row['category'] }}</td>
                    <td class="text-end small">
                        GHS {{ number_format($row['budget_a'], 0) }}
                        @if($row['supplementary_a'] > 0)
                        <div style="font-size:9px;color:#92400E">incl. +{{ number_format($row['supplementary_a'],0) }} suppl.</div>
                        @endif
                    </td>
                    <td class="text-end small">
                        GHS {{ number_format($row['budget_b'], 0) }}
                        @if($row['supplementary_b'] > 0)
                        <div style="font-size:9px;color:#92400E">incl. +{{ number_format($row['supplementary_b'],0) }} suppl.</div>
                        @endif
                    </td>
                    <td class="text-end small fw-semibold"
                        style="color:{{ $row['budget_change'] > 0 ? '#F43F5E' : ($row['budget_change'] < 0 ? '#10B981' : 'inherit') }}">
                        {{ $row['budget_change'] >= 0 ? '+' : '' }}GHS {{ number_format($row['budget_change'], 0) }}
                    </td>
                    <td class="text-end small">
                        @if($row['budget_change_pct'] !== null)
                            <span style="color:{{ $row['budget_change_pct'] > 0 ? '#F43F5E' : '#10B981' }}">
                                {{ $row['budget_change_pct'] >= 0 ? '+' : '' }}{{ $row['budget_change_pct'] }}%
                            </span>
                        @else
                            <span class="text-muted">New</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($row['budget_trend'] === 'up')
                            <span style="color:#F43F5E;font-size:16px">↑</span>
                        @elseif($row['budget_trend'] === 'down')
                            <span style="color:#10B981;font-size:16px">↓</span>
                        @else
                            <span style="color:#94A3B8;font-size:16px">→</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot style="background:#F8FAFC;font-weight:700;font-size:12px">
                <tr>
                    <td colspan="3">Total</td>
                    <td class="text-end">GHS {{ number_format($comparison['budget_total_a'], 0) }}</td>
                    <td class="text-end">GHS {{ number_format($comparison['budget_total_b'], 0) }}</td>
                    <td class="text-end"
                        style="color:{{ $budgetChangeTotal >= 0 ? '#F43F5E' : '#10B981' }}">
                        {{ $budgetChangeTotal >= 0 ? '+' : '' }}GHS {{ number_format($budgetChangeTotal, 0) }}
                    </td>
                    <td class="text-end"
                        style="color:{{ $budgetChangePctTotal >= 0 ? '#F43F5E' : '#10B981' }}">
                        {{ $budgetChangePctTotal >= 0 ? '+' : '' }}{{ $budgetChangePctTotal }}%
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Actual view --}}
    <div id="view_actual" class="table-responsive" style="display:none">
        <table class="table table-sm table-hover mb-0">
            <thead style="font-size:11px;text-transform:uppercase;
                          letter-spacing:.5px;color:var(--slate)">
                <tr>
                    <th>Code</th>
                    <th>Account</th>
                    <th>Category</th>
                    <th class="text-end">{{ $periodA->name }} Actual</th>
                    <th class="text-end">{{ $periodB->name }} Actual</th>
                    <th class="text-end">Change (GHS)</th>
                    <th class="text-end">Change %</th>
                    <th class="text-center">Trend</th>
                </tr>
            </thead>
            <tbody>
                @foreach($comparison['rows'] as $row)
                <tr>
                    <td style="font-family:monospace;font-weight:600;font-size:12px">
                        {{ $row['code'] }}
                    </td>
                    <td class="small">{{ $row['name'] }}</td>
                    <td class="small text-muted">{{ $row['category'] }}</td>
                    <td class="text-end small" style="color:#10B981">
                        GHS {{ number_format($row['actual_a'], 0) }}
                    </td>
                    <td class="text-end small" style="color:#10B981">
                        GHS {{ number_format($row['actual_b'], 0) }}
                    </td>
                    <td class="text-end small fw-semibold"
                        style="color:{{ $row['actual_change'] > 0 ? '#F43F5E' : ($row['actual_change'] < 0 ? '#10B981' : 'inherit') }}">
                        {{ $row['actual_change'] >= 0 ? '+' : '' }}GHS {{ number_format($row['actual_change'], 0) }}
                    </td>
                    <td class="text-end small">
                        @if($row['actual_change_pct'] !== null)
                            <span style="color:{{ $row['actual_change_pct'] > 0 ? '#F43F5E' : '#10B981' }}">
                                {{ $row['actual_change_pct'] >= 0 ? '+' : '' }}{{ $row['actual_change_pct'] }}%
                            </span>
                        @else
                            <span class="text-muted">New</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($row['actual_trend'] === 'up')
                            <span style="color:#F43F5E;font-size:16px">↑</span>
                        @elseif($row['actual_trend'] === 'down')
                            <span style="color:#10B981;font-size:16px">↓</span>
                        @else
                            <span style="color:#94A3B8;font-size:16px">→</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot style="background:#F8FAFC;font-weight:700;font-size:12px">
                <tr>
                    <td colspan="3">Total</td>
                    <td class="text-end" style="color:#10B981">
                        GHS {{ number_format($comparison['actual_total_a'], 0) }}
                    </td>
                    <td class="text-end" style="color:#10B981">
                        GHS {{ number_format($comparison['actual_total_b'], 0) }}
                    </td>
                    <td class="text-end"
                        style="color:{{ $actualChangeTotal >= 0 ? '#F43F5E' : '#10B981' }}">
                        {{ $actualChangeTotal >= 0 ? '+' : '' }}GHS {{ number_format($actualChangeTotal, 0) }}
                    </td>
                    <td class="text-end"
                        style="color:{{ $actualChangePctTotal >= 0 ? '#F43F5E' : '#10B981' }}">
                        {{ $actualChangePctTotal >= 0 ? '+' : '' }}{{ $actualChangePctTotal }}%
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Combined view --}}
<div id="view_combined" class="table-responsive" style="display:none">
    <table class="table table-sm table-hover mb-0">
        <thead style="font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:var(--slate)">
            <tr>
                <th rowspan="2">Code</th>
                <th rowspan="2">Account</th>
                <th colspan="4" class="text-center" style="background:#F1F5F9;border-bottom:2px solid var(--slate)">
                    {{ $periodA->name }}
                </th>
                <th colspan="4" class="text-center" style="background:#EFF6FF;border-bottom:2px solid var(--navy)">
                    {{ $periodB->name }}
                </th>
                <th colspan="2" class="text-center" style="background:#F0FDF4;border-bottom:2px solid #10B981">
                    Change
                </th>
            </tr>
            <tr>
                <th class="text-end" style="background:#F1F5F9">Original</th>
                <th class="text-end" style="background:#F1F5F9">Suppl.</th>
                <th class="text-end" style="background:#F1F5F9">Actual</th>
                <th class="text-end" style="background:#F1F5F9">Util%</th>
                <th class="text-end" style="background:#EFF6FF">Original</th>
                <th class="text-end" style="background:#EFF6FF">Suppl.</th>
                <th class="text-end" style="background:#EFF6FF">Actual</th>
                <th class="text-end" style="background:#EFF6FF">Util%</th>
                <th class="text-end" style="background:#F0FDF4">Budget</th>
                <th class="text-end" style="background:#F0FDF4">Actual</th>
            </tr>
        </thead>
        <tbody>
            @foreach($comparison['rows'] as $row)
            <tr>
                <td style="font-family:monospace;font-weight:600;font-size:11px">{{ $row['code'] }}</td>
                <td style="font-size:11px">{{ $row['name'] }}</td>

                <td class="text-end" style="font-size:11px;background:#FAFAFA">
                    {{ number_format($row['original_a'],0) }}
                </td>
                <td class="text-end" style="font-size:11px;background:#FAFAFA;color:#92400E">
                    {{ $row['supplementary_a']>0 ? '+'.number_format($row['supplementary_a'],0) : '—' }}
                </td>
                <td class="text-end" style="font-size:11px;background:#FAFAFA;color:#10B981">
                    {{ number_format($row['actual_a'],0) }}
                </td>
                <td class="text-end" style="font-size:11px;background:#FAFAFA">
                    <span style="color:{{ $row['utilisation_a']>90?'#F43F5E':($row['utilisation_a']>70?'#F59E0B':'#10B981') }}">
                        {{ $row['utilisation_a'] }}%
                    </span>
                </td>

                <td class="text-end" style="font-size:11px;background:#F0F7FF">
                    {{ number_format($row['original_b'],0) }}
                </td>
                <td class="text-end" style="font-size:11px;background:#F0F7FF;color:#92400E">
                    {{ $row['supplementary_b']>0 ? '+'.number_format($row['supplementary_b'],0) : '—' }}
                </td>
                <td class="text-end" style="font-size:11px;background:#F0F7FF;color:#10B981">
                    {{ number_format($row['actual_b'],0) }}
                </td>
                <td class="text-end" style="font-size:11px;background:#F0F7FF">
                    <span style="color:{{ $row['utilisation_b']>90?'#F43F5E':($row['utilisation_b']>70?'#F59E0B':'#10B981') }}">
                        {{ $row['utilisation_b'] }}%
                    </span>
                </td>

                <td class="text-end fw-semibold" style="font-size:11px;background:#F0FDF4;
                    color:{{ $row['budget_change']>0?'#F43F5E':($row['budget_change']<0?'#10B981':'inherit') }}">
                    {{ $row['budget_change']>=0?'+':'' }}{{ number_format($row['budget_change'],0) }}
                </td>
                <td class="text-end fw-semibold" style="font-size:11px;background:#F0FDF4;
                    color:{{ $row['actual_change']>0?'#F43F5E':($row['actual_change']<0?'#10B981':'inherit') }}">
                    {{ $row['actual_change']>=0?'+':'' }}{{ number_format($row['actual_change'],0) }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot style="background:#F8FAFC;font-weight:700;font-size:11px">
            <tr>
                <td colspan="2">Total</td>
                <td class="text-end">{{ number_format($comparison['original_total_a'],0) }}</td>
                <td class="text-end" style="color:#92400E">
                    {{ $comparison['supplementary_total_a']>0 ? '+'.number_format($comparison['supplementary_total_a'],0) : '—' }}
                </td>
                <td class="text-end" style="color:#10B981">{{ number_format($comparison['actual_total_a'],0) }}</td>
                <td class="text-end">{{ $utilA }}%</td>
                <td class="text-end">{{ number_format($comparison['original_total_b'],0) }}</td>
                <td class="text-end" style="color:#92400E">
                    {{ $comparison['supplementary_total_b']>0 ? '+'.number_format($comparison['supplementary_total_b'],0) : '—' }}
                </td>
                <td class="text-end" style="color:#10B981">{{ number_format($comparison['actual_total_b'],0) }}</td>
                <td class="text-end">{{ $utilB }}%</td>
                <td class="text-end" style="color:{{ $budgetChangeTotal>=0?'#F43F5E':'#10B981' }}">
                    {{ $budgetChangeTotal>=0?'+':'' }}{{ number_format($budgetChangeTotal,0) }}
                </td>
                <td class="text-end" style="color:{{ $actualChangeTotal>=0?'#F43F5E':'#10B981' }}">
                    {{ $actualChangeTotal>=0?'+':'' }}{{ number_format($actualChangeTotal,0) }}
                </td>
            </tr>
        </tfoot>
    </table>
</div>

</div>

{{-- ── Charts JS ── --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const NAVY    = '#1B2A4A';
const SLATE   = '#64748B';
const EMERALD = '#10B981';
const GOLD    = '#C9A84C';
const ROSE    = '#F43F5E';
const EMERALD_LIGHT = 'rgba(16,185,129,.5)';
const NAVY_LIGHT    = 'rgba(27,42,74,.5)';

function fmt(v) {
    return v >= 1000000 ? (v/1000000).toFixed(1)+'M'
         : v >= 1000    ? (v/1000).toFixed(0)+'K'
         : v;
}

const yScale = {
    beginAtZero: true,
    grid: { color: '#F1F5F9' },
    ticks: { font:{ size:10 }, callback: fmt }
};

// ── Top 12 grouped bar ──
@php
    $top12 = array_slice($comparison['rows'], 0, 12);
@endphp
new Chart(document.getElementById('yoyGrouped'), {
    type: 'bar',
    data: {
        labels: {!! json_encode(array_column($top12, 'code')) !!},
        datasets: [
            {
                label: '{{ $periodA->name }} Budget',
                data:  {!! json_encode(array_column($top12, 'budget_a')) !!},
                backgroundColor: SLATE,
                borderRadius: 3, borderSkipped: false,
            },
            {
                label: '{{ $periodA->name }} Actual',
                data:  {!! json_encode(array_column($top12, 'actual_a')) !!},
                backgroundColor: EMERALD_LIGHT,
                borderRadius: 3, borderSkipped: false,
            },
            {
                label: '{{ $periodB->name }} Budget',
                data:  {!! json_encode(array_column($top12, 'budget_b')) !!},
                backgroundColor: NAVY,
                borderRadius: 3, borderSkipped: false,
            },
            {
                label: '{{ $periodB->name }} Actual',
                data:  {!! json_encode(array_column($top12, 'actual_b')) !!},
                backgroundColor: EMERALD,
                borderRadius: 3, borderSkipped: false,
            },
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position:'top', labels:{ font:{size:11}, boxWidth:12 } }
        },
        scales: {
            y: yScale,
            x: { grid:{ display:false }, ticks:{ font:{size:10} } }
        }
    }
});

// ── Period A mini bar ──
new Chart(document.getElementById('periodABar'), {
    type: 'bar',
    data: {
        labels: ['Budget','Actual'],
        datasets: [{
            data: [
                {{ $comparison['budget_total_a'] }},
                {{ $comparison['actual_total_a'] }},
            ],
            backgroundColor: [SLATE, EMERALD],
            borderRadius: 6, borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend:{ display:false } },
        scales: {
            y: { ...yScale, display:false },
            x: { grid:{ display:false }, ticks:{ font:{size:11} } }
        }
    }
});

// ── Period B mini bar ──
new Chart(document.getElementById('periodBBar'), {
    type: 'bar',
    data: {
        labels: ['Budget','Actual'],
        datasets: [{
            data: [
                {{ $comparison['budget_total_b'] }},
                {{ $comparison['actual_total_b'] }},
            ],
            backgroundColor: [NAVY, EMERALD],
            borderRadius: 6, borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend:{ display:false } },
        scales: {
            y: { ...yScale, display:false },
            x: { grid:{ display:false }, ticks:{ font:{size:11} } }
        }
    }
});

// ── Quarterly comparison ──
@php
    $qLabels = ['Q1 (Jan-Mar)','Q2 (Apr-Jun)','Q3 (Jul-Sep)','Q4 (Oct-Dec)'];
    $qKeys   = ['q1','q2','q3','q4'];

    $bAQ_totals = ['q1'=>0,'q2'=>0,'q3'=>0,'q4'=>0];
    $bBQ_totals = ['q1'=>0,'q2'=>0,'q3'=>0,'q4'=>0];
    $aAQ_totals = ['q1'=>0,'q2'=>0,'q3'=>0,'q4'=>0];
    $aBQ_totals = ['q1'=>0,'q2'=>0,'q3'=>0,'q4'=>0];

    foreach($comparison['rows'] as $row) {
        foreach($qKeys as $q) {
            $bAQ_totals[$q] += $row['budget_a_q'][$q] ?? 0;
            $bBQ_totals[$q] += $row['budget_b_q'][$q] ?? 0;
            $aAQ_totals[$q] += $row['actual_a_q'][$q] ?? 0;
            $aBQ_totals[$q] += $row['actual_b_q'][$q] ?? 0;
        }
    }
@endphp
new Chart(document.getElementById('quarterlyCompare'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($qLabels) !!},
        datasets: [
            {
                label: '{{ $periodA->name }} Budget',
                data:  {!! json_encode(array_values($bAQ_totals)) !!},
                backgroundColor: SLATE,
                borderRadius: 4, borderSkipped: false,
            },
            {
                label: '{{ $periodA->name }} Actual',
                data:  {!! json_encode(array_values($aAQ_totals)) !!},
                backgroundColor: EMERALD_LIGHT,
                borderRadius: 4, borderSkipped: false,
            },
            {
                label: '{{ $periodB->name }} Budget',
                data:  {!! json_encode(array_values($bBQ_totals)) !!},
                backgroundColor: NAVY,
                borderRadius: 4, borderSkipped: false,
            },
            {
                label: '{{ $periodB->name }} Actual',
                data:  {!! json_encode(array_values($aBQ_totals)) !!},
                backgroundColor: EMERALD,
                borderRadius: 4, borderSkipped: false,
            },
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position:'top', labels:{ font:{size:11}, boxWidth:12 } }
        },
        scales: {
            y: yScale,
            x: { grid:{ display:false }, ticks:{ font:{size:11} } }
        }
    }
});

// ── Table view toggle ──
function showView(view) {
    ['budget','actual','combined'].forEach(v => {
        document.getElementById('view_' + v).style.display = v === view ? '' : 'none';
        const btn = document.getElementById('btn_' + v);
        btn.style.background = v === view ? 'var(--navy)' : '';
        btn.style.color      = v === view ? '#fff'        : '';
        btn.className = v === view
            ? 'btn btn-sm'
            : 'btn btn-sm btn-outline-secondary';
        btn.style.borderRadius = '6px';
        btn.style.fontSize     = '12px';
    });
}
</script>

@else
<div class="chart-card text-center py-5 text-muted">
    <div style="font-size:36px;margin-bottom:12px">📅</div>
    <div style="font-size:15px;font-weight:600;color:var(--navy);margin-bottom:8px">
        Select two periods and click Compare
    </div>
    <div style="font-size:13px">
        The report will show budget and actual comparisons side by side
        across all account codes for both periods.
    </div>
</div>
@endif

@endsection
