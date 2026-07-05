@extends('layouts.app')
@section('title', 'Account Code Explorer')
@section('content')

<div class="mb-4">
    <h5 class="fw-bold mb-0">Account Code Explorer</h5>
    <p class="text-muted small">
        <a href="{{ route('reports.index') }}" class="text-muted">Reports</a>
        / Code Explorer
    </p>
</div>

{{-- ── Filters ── --}}
<form method="GET" class="chart-card mb-4">
    <div class="row g-3 align-items-end">

        {{-- Budget Type filter --}}
        <div class="col-12 col-md-2">
            <label class="form-label small fw-semibold mb-1">Budget Type</label>
            <select name="budget_type" class="form-select form-select-sm"
                    onchange="
                        this.form.querySelector('[name=category_id]').value='';
                        this.form.querySelector('[name=account_code_id]').value='';
                        this.form.submit()
                    ">
                <option value="">— All Types —</option>
                @foreach($budgetTypeLabels as $key => $label)
                <option value="{{ $key }}"
                    {{ $selectedBudgetType === $key ? 'selected' : '' }}>
                    {{ $label }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="col-12 col-md-3">
            <label class="form-label small fw-semibold mb-1">View by Category</label>
            <select name="category_id" class="form-select form-select-sm"
                    onchange="
                        this.form.querySelector('[name=account_code_id]').value='';
                        this.form.submit()
                    ">
                <option value="">— Select a category —</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}"
                    {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }} ({{ $cat->accountCodes->count() }})
                </option>
                @endforeach
            </select>
        </div>

        <div class="col-auto d-none d-md-flex align-items-end pb-1">
            <span style="font-size:12px;color:var(--slate);font-weight:600">OR</span>
        </div>

        <div class="col-12 col-md-3">
            <label class="form-label small fw-semibold mb-1">View Specific Code</label>
            <select name="account_code_id" class="form-select form-select-sm"
                    onchange="
                        this.form.querySelector('[name=category_id]').value='';
                        this.form.submit()
                    ">
                <option value="">— Select a code —</option>
                @foreach($categories as $cat)
                <optgroup label="{{ $cat->name }}">
                    @foreach($cat->accountCodes as $code)
                    <option value="{{ $code->id }}"
                        {{ request('account_code_id') == $code->id ? 'selected' : '' }}>
                        {{ $code->code }} — {{ $code->name }}
                    </option>
                    @endforeach
                </optgroup>
                @endforeach
            </select>
        </div>

        <div class="col-12 col-md-2">
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

        @if($canViewAll)
        <div class="col-12 col-md-2">
            <label class="form-label small fw-semibold mb-1">Department</label>
            <select name="department_id" class="form-select form-select-sm"
                    onchange="this.form.submit()">
                <option value="">All Departments</option>
                @foreach($departments as $d)
                <option value="{{ $d->id }}"
                    {{ request('department_id') == $d->id ? 'selected' : '' }}>
                    {{ $d->name }}
                </option>
                @endforeach
            </select>
        </div>
        @endif

    </div>
</form>

{{-- ── Default state ── --}}
@if(!$selectedCategory && !$selectedCode)
<div class="chart-card text-center py-5 text-muted">
    <div style="font-size:48px;margin-bottom:12px">🔢</div>
    <div style="font-size:16px;font-weight:600;color:var(--navy);margin-bottom:8px">
        Select a Category or Account Code
    </div>
    <div style="font-size:13px;color:var(--slate)">
        Choose a <strong>category</strong> to see a combined summary of all codes under it,
        or pick a <strong>specific code</strong> to drill in with quarterly detail.
    </div>
</div>
@endif

@if(count($reportData))
<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('reports.code-explorer.export', request()->query()) }}"
       class="btn btn-sm btn-outline-success">
        ↓ Export to Excel
    </a>
</div>
@endif

{{-- ══════════════════════════════════════════════════════
     REPORT OUTPUT
     ══════════════════════════════════════════════════════ --}}
@if(count($reportData))

{{-- Header --}}
@php
    $grandBudget = collect($reportData)->sum('budget_total');
    $grandActual = collect($reportData)->sum('actual_total');
    $grandVar    = $grandActual - $grandBudget;
    $grandUtil   = $grandBudget > 0 ? round(($grandActual / $grandBudget) * 100, 1) : 0;
@endphp
<div class="chart-card mb-4">
    <div class="d-flex align-items-start gap-3">
        <div style="background:var(--navy);color:var(--gold);border-radius:10px;
                    padding:10px 18px;font-weight:700;font-size:16px;
                    font-family:monospace;white-space:nowrap">
            {{ $selectedCategory ? $selectedCategory->code : $selectedCode->code }}
        </div>
        <div class="flex-grow-1">
            <div style="font-size:18px;font-weight:700;color:var(--navy)">
                {{ $reportTitle }}
            </div>
            <div style="font-size:13px;color:var(--slate)">
                {{ $reportSubtitle }}
                @if($period) &middot; {{ $period->name }} @endif
                @if(!$canViewAll)
                    &middot; {{ auth()->user()->department?->name }}
                @elseif(request('department_id'))
                    &middot; {{ $departments->firstWhere('id', request('department_id'))?->name }}
                @else
                    &middot; All Departments
                @endif
            </div>
        </div>
        <div class="text-end">
            <div style="font-size:11px;color:var(--slate)">Total Budget</div>
            <div style="font-size:20px;font-weight:700;color:var(--navy)">
                {{ currency() }} {{ number_format($grandBudget, 0) }}
            </div>
            <div style="font-size:12px;color:#10B981">
                Actual: {{ currency() }} {{ number_format($grandActual, 0) }}
                &nbsp;|&nbsp;
                <span style="color:{{ $grandVar > 0 ? '#F43F5E' : '#10B981' }}">
                    {{ $grandVar >= 0 ? '+' : '' }}{{ currency() }} {{ number_format($grandVar, 0) }}
                </span>
            </div>
        </div>
    </div>
</div>

@if($selectedCategory)
{{-- ══════════════════════════════════════════════════════
     COMBINED CATEGORY VIEW
     ══════════════════════════════════════════════════════ --}}

{{-- Summary charts --}}
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="chart-card h-100">
            <div class="chart-title">Budget vs Actual by Code</div>
            <canvas id="summaryGrouped" height="200"></canvas>
        </div>
    </div>
    <div class="col-md-4">
        <div class="chart-card h-100">
            <div class="chart-title">Overall Utilisation</div>
            <canvas id="summaryDonut" height="200"></canvas>
        </div>
    </div>
</div>

{{-- Combined summary table --}}
<div class="chart-card">
    <div class="chart-title">Code Summary — {{ $selectedCategory->name }}</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0" style="font-size:12px">
            <thead style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--slate)">
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th class="text-end">Budget ({{ currency() }})</th>
                    <th class="text-end">Actual ({{ currency() }})</th>
                    <th class="text-end">Variance</th>
                    <th style="min-width:120px">Utilisation</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData as $row)
                @php
                    $varColor = $row['variance'] > 0 ? '#F43F5E'
                        : ($row['variance'] < 0 ? '#10B981' : 'inherit');
                    $utilColor = $row['utilisation'] > 90 ? '#F43F5E'
                        : ($row['utilisation'] > 70 ? '#F59E0B' : '#10B981');
                @endphp
                <tr>
                    <td>
                        <span style="background:var(--surface);border:1px solid var(--border);
                                     border-radius:4px;padding:2px 8px;font-family:monospace;
                                     font-weight:700;font-size:11px;color:var(--navy)">
                            {{ $row['code'] }}
                        </span>
                    </td>
                    <td class="fw-semibold small">{{ $row['name'] }}</td>
                    <td class="text-end small">{{ number_format($row['budget_total'], 0) }}</td>
                    <td class="text-end small fw-semibold" style="color:#10B981">
                        {{ number_format($row['actual_total'], 0) }}
                    </td>
                    <td class="text-end small fw-semibold" style="color:{{ $varColor }}">
                        {{ $row['variance'] >= 0 ? '+' : '' }}{{ number_format($row['variance'], 0) }}
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height:5px">
                                <div class="progress-bar"
                                     style="width:{{ min($row['utilisation'],100) }}%;
                                            background:{{ $utilColor }}">
                                </div>
                            </div>
                            <span style="font-size:11px;color:{{ $utilColor }};min-width:36px;font-weight:600">
                                {{ $row['utilisation'] }}%
                            </span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot style="background:#F8FAFC;font-weight:700;font-size:12px">
                <tr>
                    <td colspan="2">Total ({{ count($reportData) }} codes)</td>
                    <td class="text-end">{{ currency() }} {{ number_format($grandBudget, 0) }}</td>
                    <td class="text-end" style="color:#10B981">
                        {{ currency() }} {{ number_format($grandActual, 0) }}
                    </td>
                    <td class="text-end"
                        style="color:{{ $grandVar > 0 ? '#F43F5E' : '#10B981' }}">
                        {{ $grandVar >= 0 ? '+' : '' }}{{ currency() }} {{ number_format($grandVar, 0) }}
                    </td>
                    <td>
                        <span style="color:{{ $grandUtil > 90 ? '#F43F5E' : ($grandUtil > 70 ? '#F59E0B' : '#10B981') }}">
                            {{ $grandUtil }}%
                        </span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@else
{{-- ══════════════════════════════════════════════════════
     SINGLE CODE VIEW — full quarterly detail per code
     ══════════════════════════════════════════════════════ --}}
@foreach($reportData as $idx => $row)
@php
    $varColor  = $row['variance'] > 0 ? '#F43F5E'
        : ($row['variance'] < 0 ? '#10B981' : 'inherit');
    $chartId   = 'code_' . $idx;
@endphp

<div class="chart-card mb-4"
     style="border-left:4px solid {{ $row['utilisation'] > 90 ? '#F43F5E' : ($row['utilisation'] > 70 ? '#F59E0B' : '#10B981') }}">

    {{-- Code header --}}
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div class="d-flex align-items-center gap-2">
            <span style="background:var(--surface);border:1px solid var(--border);
                         border-radius:6px;padding:3px 10px;font-family:monospace;
                         font-weight:700;font-size:13px;color:var(--navy)">
                {{ $row['code'] }}
            </span>
            <div>
                <div style="font-size:15px;font-weight:700;color:var(--navy)">
                    {{ $row['name'] }}
                </div>
                <div style="font-size:11px;color:var(--slate)">
                    {{ $row['category'] }}
                </div>
            </div>
        </div>
        <div class="text-end">
            <div style="font-size:11px;color:var(--slate)">Budget / Actual / Variance</div>
            <div style="font-size:14px;font-weight:700">
                {{ currency() }} {{ number_format($row['budget_total'], 0) }}

                @if($row['budget_supplementary'] > 0)
                <div style="font-size:10px;color:#92400E">
                    (Original: {{ number_format($row['budget_original'],0) }}
                    + Suppl: {{ number_format($row['budget_supplementary'],0) }})
                </div>
                @endif

                <span style="color:var(--slate);font-weight:400">/</span>
                <span style="color:#10B981">{{ currency() }} {{ number_format($row['actual_total'], 0) }}</span>
                <span style="color:var(--slate);font-weight:400">/</span>
                <span style="color:{{ $varColor }}">
                    {{ $row['variance'] >= 0 ? '+' : '' }}{{ currency() }} {{ number_format($row['variance'], 0) }}
                </span>
            </div>
            <div style="font-size:11px;color:var(--slate)">
                {{ $row['utilisation'] }}% utilised
            </div>
        </div>
    </div>

    {{-- Utilisation bar --}}
    <div class="d-flex align-items-center gap-2 mb-4">
        <div class="progress flex-grow-1" style="height:8px;border-radius:4px">
            <div class="progress-bar" style="width:{{ min($row['utilisation'],100) }}%;
                 background:{{ $row['utilisation'] > 90 ? '#F43F5E' : ($row['utilisation'] > 70 ? '#F59E0B' : '#10B981') }};
                 border-radius:4px">
            </div>
        </div>
        <span style="font-size:12px;color:var(--slate);white-space:nowrap">
            {{ $row['utilisation'] }}% of budget used
        </span>
    </div>

    {{-- ── Quarterly Comparison Table ── --}}
    <div class="mb-4">
        <div style="font-size:12px;font-weight:600;text-transform:uppercase;
                    letter-spacing:.5px;color:var(--slate);margin-bottom:10px">
            Quarterly Review
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0"
                   style="font-size:12px;border:1px solid var(--border);border-radius:8px">
                <thead style="background:var(--navy);color:#fff">
                    <tr>
                        <th style="width:130px">Quarter / Metric</th>
                        <th class="text-center">
                            Q1<br>
                            <span style="font-size:10px;opacity:.7">Jan · Feb · Mar</span>
                        </th>
                        <th class="text-center">
                            Q2<br>
                            <span style="font-size:10px;opacity:.7">Apr · May · Jun</span>
                        </th>
                        <th class="text-center">
                            Q3<br>
                            <span style="font-size:10px;opacity:.7">Jul · Aug · Sep</span>
                        </th>
                        <th class="text-center">
                            Q4<br>
                            <span style="font-size:10px;opacity:.7">Oct · Nov · Dec</span>
                        </th>
                        <th class="text-center" style="background:rgba(255,255,255,.1)">
                            Full Year
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Budget row --}}
                    <tr style="background:#F8FAFC">
                        <td class="fw-semibold" style="color:var(--navy)">Budget ({{ currency() }})</td>
                        <td class="text-center">{{ number_format($row['budget_q1'], 2) }}</td>
                        <td class="text-center">{{ number_format($row['budget_q2'], 2) }}</td>
                        <td class="text-center">{{ number_format($row['budget_q3'], 2) }}</td>
                        <td class="text-center">{{ number_format($row['budget_q4'], 2) }}</td>
                        <td class="text-center fw-bold" style="color:var(--navy)">
                            {{ number_format($row['budget_total'], 2) }}

                            @if($row['budget_supplementary'] > 0)
                            <div style="font-size:10px;color:#92400E">
                                (Original: {{ number_format($row['budget_original'],0) }}
                                + Suppl: {{ number_format($row['budget_supplementary'],0) }})
                            </div>
                            @endif

                        </td>
                    </tr>

                    {{-- Monthly actuals within each quarter --}}
                    @php
                        $quarters = [
                            'Q1' => [1,2,3],
                            'Q2' => [4,5,6],
                            'Q3' => [7,8,9],
                            'Q4' => [10,11,12],
                        ];
                        $monthNames = \App\Models\BudgetActual::MONTHS;
                    @endphp

                    <tr style="background:#FFFBEB">
                        <td class="fw-semibold" style="color:#92400E;font-size:11px">
                            Monthly Actuals
                        </td>
                        @foreach($quarters as $qLabel => $months)
                        <td>
                            @foreach($months as $m)
                            <div class="d-flex justify-content-between"
                                 style="font-size:10px;padding:1px 0;
                                        border-bottom:{{ !$loop->last ? '1px dashed #FDE68A' : 'none' }}">
                                <span style="color:var(--slate)">
                                    {{ substr($monthNames[$m], 0, 3) }}
                                </span>
                                <span style="color:{{ ($row['monthly_actuals'][$m] ?? 0) > 0 ? '#065F46' : '#CBD5E1' }};
                                             font-weight:{{ ($row['monthly_actuals'][$m] ?? 0) > 0 ? '600' : '400' }}">
                                    {{ ($row['monthly_actuals'][$m] ?? 0) > 0
                                        ? number_format($row['monthly_actuals'][$m], 0)
                                        : '—' }}
                                </span>
                            </div>
                            @endforeach
                        </td>
                        @endforeach
                        <td class="text-center fw-bold" style="color:#10B981">
                            {{ number_format($row['actual_total'], 2) }}
                        </td>
                    </tr>

                    {{-- Actual (quarterly total) row --}}
                    <tr>
                        <td class="fw-semibold" style="color:#10B981">Actual ({{ currency() }})</td>
                        <td class="text-center" style="color:#10B981">
                            {{ number_format($row['actual_q1'], 2) }}
                        </td>
                        <td class="text-center" style="color:#10B981">
                            {{ number_format($row['actual_q2'], 2) }}
                        </td>
                        <td class="text-center" style="color:#10B981">
                            {{ number_format($row['actual_q3'], 2) }}
                        </td>
                        <td class="text-center" style="color:#10B981">
                            {{ number_format($row['actual_q4'], 2) }}
                        </td>
                        <td class="text-center fw-bold" style="color:#10B981">
                            {{ number_format($row['actual_total'], 2) }}
                        </td>
                    </tr>

                    {{-- Variance row --}}
                    @php
                        $qVars = [
                            $row['actual_q1'] - $row['budget_q1'],
                            $row['actual_q2'] - $row['budget_q2'],
                            $row['actual_q3'] - $row['budget_q3'],
                            $row['actual_q4'] - $row['budget_q4'],
                        ];
                    @endphp
                    <tr style="background:#F8FAFC">
                        <td class="fw-semibold" style="color:var(--slate)">Variance ({{ currency() }})</td>
                        @foreach($qVars as $qv)
                        <td class="text-center fw-semibold"
                            style="color:{{ $qv > 0 ? '#F43F5E' : ($qv < 0 ? '#10B981' : '#94A3B8') }}">
                            {{ $qv >= 0 ? '+' : '' }}{{ number_format($qv, 2) }}
                        </td>
                        @endforeach
                        <td class="text-center fw-bold"
                            style="color:{{ $row['variance'] > 0 ? '#F43F5E' : ($row['variance'] < 0 ? '#10B981' : '#94A3B8') }}">
                            {{ $row['variance'] >= 0 ? '+' : '' }}{{ number_format($row['variance'], 2) }}
                        </td>
                    </tr>

                    {{-- Utilisation % row --}}
                    @php
                        $qUtils = [
                            $row['budget_q1'] > 0 ? round(($row['actual_q1']/$row['budget_q1'])*100,1) : 0,
                            $row['budget_q2'] > 0 ? round(($row['actual_q2']/$row['budget_q2'])*100,1) : 0,
                            $row['budget_q3'] > 0 ? round(($row['actual_q3']/$row['budget_q3'])*100,1) : 0,
                            $row['budget_q4'] > 0 ? round(($row['actual_q4']/$row['budget_q4'])*100,1) : 0,
                        ];
                    @endphp
                    <tr>
                        <td class="fw-semibold" style="color:var(--slate)">Utilisation %</td>
                        @foreach($qUtils as $qu)
                        <td class="text-center">
                            <div class="progress mx-auto mb-1" style="height:4px;max-width:80px">
                                <div class="progress-bar"
                                     style="width:{{ min($qu,100) }}%;
                                            background:{{ $qu > 90 ? '#F43F5E' : ($qu > 70 ? '#F59E0B' : '#10B981') }}">
                                </div>
                            </div>
                            <span style="font-size:10px;color:var(--slate)">{{ $qu }}%</span>
                        </td>
                        @endforeach
                        <td class="text-center">
                            <div class="progress mx-auto mb-1" style="height:4px;max-width:80px">
                                <div class="progress-bar"
                                     style="width:{{ min($row['utilisation'],100) }}%;
                                            background:{{ $row['utilisation'] > 90 ? '#F43F5E' : ($row['utilisation'] > 70 ? '#F59E0B' : '#10B981') }}">
                                </div>
                            </div>
                            <span style="font-size:11px;font-weight:600;
                                         color:{{ $row['utilisation'] > 90 ? '#F43F5E' : ($row['utilisation'] > 70 ? '#F59E0B' : '#10B981') }}">
                                {{ $row['utilisation'] }}%
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Charts row ── --}}
    <div class="row g-3 mb-4">

        {{-- Quarterly grouped bar --}}
        <div class="col-md-6">
            <div style="font-size:12px;font-weight:600;text-transform:uppercase;
                        letter-spacing:.5px;color:var(--slate);margin-bottom:8px">
                Budget vs Actual by Quarter
            </div>
            <canvas id="{{ $chartId }}_qbar" height="180"></canvas>
        </div>

        {{-- Year-over-year trend --}}
        <div class="col-md-6">
            <div style="font-size:12px;font-weight:600;text-transform:uppercase;
                        letter-spacing:.5px;color:var(--slate);margin-bottom:8px">
                Year-over-Year Trend
            </div>
            @if(count($row['year_trend']) > 0)
            <canvas id="{{ $chartId }}_yoy" height="180"></canvas>
            @else
            <div class="text-muted small text-center py-4">
                Only one period available.
            </div>
            @endif
        </div>

    </div>

    {{-- ── Department breakdown ── --}}
    @if(count($row['dept_breakdown']) > 0)
    <div>
        <div style="font-size:12px;font-weight:600;text-transform:uppercase;
                    letter-spacing:.5px;color:var(--slate);margin-bottom:10px">
            Department Breakdown
        </div>

        @if(count($row['dept_breakdown']) > 1)
        <div class="mb-3">
            <canvas id="{{ $chartId }}_deptbar" height="100"></canvas>
        </div>
        @endif

        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="font-size:11px">
                <thead style="font-size:10px;text-transform:uppercase;
                              letter-spacing:.5px;color:var(--slate)">
                    <tr>
                        <th>Department</th>
                        <th class="text-center">Q1 Budget</th>
                        <th class="text-center">Q1 Actual</th>
                        <th class="text-center">Q2 Budget</th>
                        <th class="text-center">Q2 Actual</th>
                        <th class="text-center">Q3 Budget</th>
                        <th class="text-center">Q3 Actual</th>
                        <th class="text-center">Q4 Budget</th>
                        <th class="text-center">Q4 Actual</th>
                        <th class="text-center">Original Total</th>
                        <th class="text-center">Supplementary</th>
                        <th class="text-center">Effective Total</th>
                        <th class="text-center">Total Actual</th>
                        <th class="text-center">Variance</th>
                        <th class="text-center">Util%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($row['dept_breakdown'] as $dRow)
                    @php
                        $dUtil   = $dRow['budget_total'] > 0
                            ? round(($dRow['actual_total']/$dRow['budget_total'])*100,1) : 0;
                        $dVarCol = $dRow['variance'] > 0 ? '#F43F5E' : ($dRow['variance'] < 0 ? '#10B981' : 'inherit');
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $dRow['dept'] }}</div>
                            <div style="font-size:10px;color:var(--slate)">{{ $dRow['dept_code'] }}</div>
                        </td>
                        <td class="text-center text-muted">{{ number_format($dRow['budget_q1'],0) }}</td>
                        <td class="text-center" style="color:#10B981">{{ number_format($dRow['actual_q1'],0) }}</td>
                        <td class="text-center text-muted">{{ number_format($dRow['budget_q2'],0) }}</td>
                        <td class="text-center" style="color:#10B981">{{ number_format($dRow['actual_q2'],0) }}</td>
                        <td class="text-center text-muted">{{ number_format($dRow['budget_q3'],0) }}</td>
                        <td class="text-center" style="color:#10B981">{{ number_format($dRow['actual_q3'],0) }}</td>
                        <td class="text-center text-muted">{{ number_format($dRow['budget_q4'],0) }}</td>
                        <td class="text-center" style="color:#10B981">{{ number_format($dRow['actual_q4'],0) }}</td>
                        <td class="text-center">{{ number_format($dRow['budget_original'],0) }}</td>
                        <td class="text-center" style="color:{{ $dRow['supplementary']>0?'#92400E':'inherit' }}">
                            {{ $dRow['supplementary']>0 ? '+'.number_format($dRow['supplementary'],0) : '—' }}
                        </td>
                        <td class="text-center fw-semibold">{{ number_format($dRow['budget_total'],0) }}</td>
                        <td class="text-center fw-semibold" style="color:#10B981">
                            {{ number_format($dRow['actual_total'],0) }}
                        </td>
                        <td class="text-center fw-semibold" style="color:{{ $dVarCol }}">
                            {{ $dRow['variance'] >= 0 ? '+' : '' }}{{ number_format($dRow['variance'],0) }}
                        </td>
                        <td class="text-center">
                            <div class="progress mx-auto mb-1" style="height:4px;max-width:60px">
                                <div class="progress-bar"
                                    style="width:{{ min($dUtil,100) }}%;
                                            background:{{ $dUtil>90?'#F43F5E':($dUtil>70?'#F59E0B':'#10B981') }}">
                                </div>
                            </div>
                            <span style="font-size:10px">{{ $dUtil }}%</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                @if(count($row['dept_breakdown']) > 1)
                <tfoot style="background:#F8FAFC;font-weight:700;font-size:11px">
                    <tr>
                        <td>Total</td>
                        <td class="text-center">{{ number_format($row['budget_q1'],0) }}</td>
                        <td class="text-center" style="color:#10B981">{{ number_format($row['actual_q1'],0) }}</td>
                        <td class="text-center">{{ number_format($row['budget_q2'],0) }}</td>
                        <td class="text-center" style="color:#10B981">{{ number_format($row['actual_q2'],0) }}</td>
                        <td class="text-center">{{ number_format($row['budget_q3'],0) }}</td>
                        <td class="text-center" style="color:#10B981">{{ number_format($row['actual_q3'],0) }}</td>
                        <td class="text-center">{{ number_format($row['budget_q4'],0) }}</td>
                        <td class="text-center" style="color:#10B981">{{ number_format($row['actual_q4'],0) }}</td>
                        <td class="text-center" style="color:var(--navy)">
                            {{ number_format($row['budget_total'],0) }}

                            @if($row['budget_supplementary'] > 0)
                            <div style="font-size:10px;color:#92400E">
                                (Original: {{ number_format($row['budget_original'],0) }}
                                + Suppl: {{ number_format($row['budget_supplementary'],0) }})
                            </div>
                            @endif

                        </td>
                        <td class="text-center" style="color:#10B981">
                            {{ number_format($row['actual_total'],0) }}
                        </td>
                        <td class="text-center"
                            style="color:{{ $row['variance'] > 0 ? '#F43F5E' : '#10B981' }}">
                            {{ $row['variance'] >= 0 ? '+' : '' }}{{ number_format($row['variance'],0) }}
                        </td>
                        <td class="text-center">{{ $row['utilisation'] }}%</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
    @endif

</div>
{{-- end per-code panel --}}
@endforeach
@endif
{{-- end category vs single-code branch --}}

{{-- ══════════════════════
     Charts (Chart.js)
     ══════════════════════ --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const NAVY    = '#1B2A4A';
const GOLD    = '#C9A84C';
const EMERALD = '#10B981';
const SLATE   = '#64748B';

function fmtVal(v) {
    return v >= 1000000 ? (v/1000000).toFixed(1)+'M'
         : v >= 1000    ? (v/1000).toFixed(0)+'K'
         : v;
}

const yScaleCfg = {
    beginAtZero: true,
    grid: { color: '#F1F5F9' },
    ticks: { font:{ size:10 }, callback: fmtVal }
};

@if($selectedCategory)
{{-- ── Combined category charts ── --}}
(function() {
    const codeNames = {!! json_encode(array_column($reportData, 'name', 'code')) !!};
    const ctx = document.getElementById('summaryGrouped');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode(array_column($reportData, 'code')) !!},
            datasets: [
                {
                    label: 'Budget',
                    data:  {!! json_encode(array_column($reportData, 'budget_total')) !!},
                    backgroundColor: NAVY,
                    borderRadius: 4, borderSkipped: false,
                },
                {
                    label: 'Actual',
                    data:  {!! json_encode(array_column($reportData, 'actual_total')) !!},
                    backgroundColor: EMERALD,
                    borderRadius: 4, borderSkipped: false,
                },
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position:'top', labels:{ font:{size:11}, boxWidth:12 } },
                tooltip: {
                    callbacks: {
                        title: (items) => {
                            const code = items[0].label;
                            return codeNames[code] ? `${code} — ${codeNames[code]}` : code;
                        }
                    }
                }
            },
            scales: {
                y: yScaleCfg,
                x: { grid:{ display:false }, ticks:{ font:{size:10} } }
            }
        }
    });
})();

(function() {
    const ctx = document.getElementById('summaryDonut');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Actual', 'Remaining'],
            datasets: [{
                data: [
                    {{ $grandActual }},
                    {{ max(0, $grandBudget - $grandActual) }},
                ],
                backgroundColor: [EMERALD, '#E2E8F0'],
                borderWidth: 0,
            }]
        },
        options: {
            cutout: '68%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font:{ size:11 }, padding:10, boxWidth:10 }
                },
                tooltip: {
                    callbacks: {
                        label: (ctx) => {
                            const total = {{ $grandBudget }};
                            const pct   = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                            return ` ${ctx.label}: {{ currency() }} ${ctx.parsed.toLocaleString()} (${pct}%)`;
                        }
                    }
                }
            }
        }
    });
})();

@else
{{-- ── Per-code charts (single code view) ── --}}
@foreach($reportData as $idx => $row)
@php $chartId = 'code_' . $idx; @endphp

// ── Quarterly grouped bar ──
(function() {
    const ctx = document.getElementById('{{ $chartId }}_qbar');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Q1\n(Jan·Feb·Mar)','Q2\n(Apr·May·Jun)',
                     'Q3\n(Jul·Aug·Sep)','Q4\n(Oct·Nov·Dec)'],
            datasets: [
                {
                    label: 'Budget',
                    data: [
                        {{ $row['budget_q1'] }},
                        {{ $row['budget_q2'] }},
                        {{ $row['budget_q3'] }},
                        {{ $row['budget_q4'] }},
                    ],
                    backgroundColor: NAVY,
                    borderRadius: 4, borderSkipped: false,
                },
                {
                    label: 'Actual',
                    data: [
                        {{ $row['actual_q1'] }},
                        {{ $row['actual_q2'] }},
                        {{ $row['actual_q3'] }},
                        {{ $row['actual_q4'] }},
                    ],
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
                y: yScaleCfg,
                x: { grid:{ display:false }, ticks:{ font:{size:10} } }
            }
        }
    });
})();

// ── Year-over-year line ──
@if(count($row['year_trend']) > 0)
(function() {
    const ctx = document.getElementById('{{ $chartId }}_yoy');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode(array_keys($row['year_trend'])) !!},
            datasets: [
                {
                    label: 'Budget',
                    data: {!! json_encode(array_column($row['year_trend'], 'budget')) !!},
                    borderColor: NAVY,
                    backgroundColor: 'rgba(27,42,74,.08)',
                    borderWidth: 2.5,
                    pointBackgroundColor: GOLD,
                    pointRadius: 5,
                    fill: true,
                    tension: 0.4,
                },
                {
                    label: 'Actual',
                    data: {!! json_encode(array_column($row['year_trend'], 'actual')) !!},
                    borderColor: EMERALD,
                    backgroundColor: 'rgba(16,185,129,.08)',
                    borderWidth: 2.5,
                    pointBackgroundColor: EMERALD,
                    pointRadius: 5,
                    fill: true,
                    tension: 0.4,
                },
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position:'top', labels:{ font:{size:11}, boxWidth:12 } }
            },
            scales: {
                y: yScaleCfg,
                x: { grid:{ display:false }, ticks:{ font:{size:11} } }
            }
        }
    });
})();
@endif

// ── Dept comparison bar ──
@if(count($row['dept_breakdown']) > 1)
(function() {
    const ctx = document.getElementById('{{ $chartId }}_deptbar');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode(array_column($row['dept_breakdown'], 'dept')) !!},
            datasets: [
                {
                    label: 'Budget',
                    data: {!! json_encode(array_column($row['dept_breakdown'], 'budget_total')) !!},
                    backgroundColor: NAVY,
                    borderRadius: 4, borderSkipped: false,
                },
                {
                    label: 'Actual',
                    data: {!! json_encode(array_column($row['dept_breakdown'], 'actual_total')) !!},
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
                y: yScaleCfg,
                x: { grid:{ display:false }, ticks:{ font:{size:10} } }
            }
        }
    });
})();
@endif

@endforeach
@endif
{{-- end category vs single-code chart branch --}}

</script>

@elseif($selectedCategory || $selectedCode)
<div class="chart-card text-center py-5 text-muted">
    No approved budget data found for the selected filters.
</div>
@endif

@endsection
