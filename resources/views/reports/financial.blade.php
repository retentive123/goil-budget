@extends('layouts.app')
@section('title', 'Financial Statements')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Financial Statements</h5>
        <p class="text-muted small mb-0">
            <a href="{{ route('reports.index') }}" class="text-muted">Reports</a>
            / Financial Statements
            @if($period) · <span class="fw-semibold">{{ $period->name }}</span> @endif
        </p>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="chart-card mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Period</label>
            <select name="period_id" class="form-select form-select-sm">
                @foreach($periods as $p)
                <option value="{{ $p->id }}"
                    {{ request('period_id', $period?->id) == $p->id ? 'selected' : '' }}>
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
                Apply
            </button>
        </div>
    </div>
</form>

@if(!$period || !$pnl)
<div class="chart-card text-center py-5 text-muted">
    No approved budget data available. Select a period or open a budget period first.
</div>
@else

{{-- KPI strip --}}
@php
    $netColor = $pnl['net_actual'] >= 0 ? '#10B981' : '#F43F5E';
    $budColor = $pnl['net_budget'] >= 0 ? '#10B981' : '#F43F5E';
    $rt = $pnl['totals']['revenue'];
    $et = $pnl['totals']['expense'];
@endphp
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#10B981"></div>
            <div class="stat-label">Total Revenue (Budget)</div>
            <div class="stat-value" style="font-size:16px">
                {{ currency() }} {{ number_format($rt['effective'], 0) }}
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#F43F5E"></div>
            <div class="stat-label">Total Expenses (Budget)</div>
            <div class="stat-value" style="font-size:16px">
                {{ currency() }} {{ number_format($et['effective'], 0) }}
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:{{ $budColor }}"></div>
            <div class="stat-label">Net Position (Budget)</div>
            <div class="stat-value" style="font-size:16px;color:{{ $budColor }}">
                {{ $pnl['net_budget'] >= 0 ? '+' : '' }}{{ currency() }} {{ number_format($pnl['net_budget'], 0) }}
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:{{ $netColor }}"></div>
            <div class="stat-label">Net Actual (YTD)</div>
            <div class="stat-value" style="font-size:16px;color:{{ $netColor }}">
                {{ $pnl['net_actual'] >= 0 ? '+' : '' }}{{ currency() }} {{ number_format($pnl['net_actual'], 0) }}
            </div>
        </div>
    </div>
</div>

{{-- Tab navigation --}}
<ul class="nav nav-tabs mb-0" id="finTabs" role="tablist"
    style="border-bottom:2px solid #E2E8F0">
    <li class="nav-item">
        <button class="nav-link active" id="tab-is" data-bs-toggle="tab"
                data-bs-target="#pane-is" type="button" role="tab"
                style="font-size:13px;font-weight:600">
            <i class="fas fa-file-invoice-dollar me-1"></i>Income Statement
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="tab-cf" data-bs-toggle="tab"
                data-bs-target="#pane-cf" type="button" role="tab"
                style="font-size:13px;font-weight:600">
            <i class="fas fa-water me-1"></i>Cash Flow
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="tab-bs" data-bs-toggle="tab"
                data-bs-target="#pane-bs" type="button" role="tab"
                style="font-size:13px;font-weight:600">
            <i class="fas fa-balance-scale me-1"></i>Balance Sheet
        </button>
    </li>
</ul>

<div class="tab-content" id="finTabContent">

{{-- ══════════════════════════════════════════════════════
     TAB 1 · INCOME STATEMENT
══════════════════════════════════════════════════════ --}}
<div class="tab-pane fade show active" id="pane-is" role="tabpanel">
<div class="chart-card" style="border-radius:0 0 12px 12px;border-top:none">

    {{-- Toolbar --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <input type="text" id="pnlSearch"
                   class="form-control form-control-sm"
                   placeholder="Search code or account…"
                   style="width:260px">
            <span id="pnlSearchInfo" class="small text-muted"></span>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <button class="btn btn-sm btn-outline-secondary" id="toggleQtrsBtn"
                    onclick="toggleQuarterly()" style="font-size:12px">
                <i class="fas fa-columns me-1"></i>Show Q1–Q4
            </button>
            <button class="btn btn-sm btn-outline-secondary"
                    onclick="expandAll()" style="font-size:12px">
                <i class="fas fa-expand-alt me-1"></i>Expand All
            </button>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                        data-bs-toggle="dropdown" style="font-size:12px">
                    <i class="fas fa-download me-1"></i>Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:13px">
                    <li>
                        <a class="dropdown-item" href="#"
                           onclick="pnlExport('csv');return false">
                            <i class="fas fa-file-csv me-2 text-success"></i>CSV
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#"
                           onclick="pnlExport('json');return false">
                            <i class="fas fa-file-code me-2 text-primary"></i>JSON
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#" id="pnlCopyBtn"
                           onclick="pnlExport('copy');return false">
                            <i class="fas fa-copy me-2 text-muted"></i>Copy (TSV)
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Column-group legend --}}
    @if($prevPeriod)
    <div class="d-flex gap-3 mb-2 flex-wrap" style="font-size:11px">
        <span class="badge" style="background:#EFF6FF;color:#1D4ED8;font-weight:500">
            Current: {{ $period->name }}
        </span>
        <span class="badge" style="background:#F5F3FF;color:#6D28D9;font-weight:500">
            Prior: {{ $prevPeriod->name }}
        </span>
    </div>
    @endif

    {{-- ── P&L Table ── --}}
    {{-- COLSPAN reference:
         1=Account, 2=Eff Budget, 3=YTD Actual, 4=Common Size%,
         5=Variance, 6=Var%, 7=Growth%,
         8=Prev Budget, 9=Prev Actual, 10=Prev Var%,
         11-14=Q1-Q4 (toggle)
         Total fixed = 10, with Q = 14
    --}}
    <div class="table-responsive" style="max-height:70vh;overflow-y:auto">
    <table class="table table-sm mb-0" id="pnlTable" style="min-width:1100px">
        <thead style="font-size:10px;text-transform:uppercase;letter-spacing:.4px;color:#64748B;
                      position:sticky;top:0;background:#fff;z-index:2">
            <tr>
                {{-- Group labels --}}
                <th rowspan="2" style="min-width:260px;vertical-align:bottom">Account</th>
                <th colspan="3" class="text-center" style="border-bottom:1px solid #E2E8F0;
                    background:#EFF6FF;color:#1D4ED8">
                    {{ $period->name }}
                </th>
                <th colspan="3" class="text-center pnl-prev"
                    style="border-bottom:1px solid #E2E8F0;background:#F5F3FF;color:#6D28D9;
                    {{ !$prevPeriod ? 'display:none' : '' }}">
                    {{ $prevPeriod?->name ?? 'Prior Year' }}
                </th>
                <th colspan="4" class="text-center pnl-qtr"
                    style="display:none;border-bottom:1px solid #E2E8F0;background:#F0FDF4;color:#059669">
                    Q1–Q4 Budget
                </th>
            </tr>
            <tr style="border-top:none">
                {{-- Current year --}}
                <th class="text-end" style="background:#EFF6FF;min-width:110px">Eff. Budget</th>
                <th class="text-end" style="background:#EFF6FF;min-width:110px">YTD Actual</th>
                <th class="text-end" style="background:#EFF6FF;min-width:100px">Variance&nbsp;/ %</th>
                {{-- Common size sits between sections --}}
                {{-- Previous year --}}
                <th class="text-end pnl-prev" style="background:#F5F3FF;min-width:110px;
                    {{ !$prevPeriod ? 'display:none' : '' }}">Prev Budget</th>
                <th class="text-end pnl-prev" style="background:#F5F3FF;min-width:110px;
                    {{ !$prevPeriod ? 'display:none' : '' }}">Prev Actual</th>
                <th class="text-end pnl-prev" style="background:#F5F3FF;min-width:120px;
                    {{ !$prevPeriod ? 'display:none' : '' }}">Growth / Prev Var%</th>
                {{-- Quarterly --}}
                <th class="text-end pnl-qtr" style="display:none;min-width:85px;background:#F0FDF4">Q1</th>
                <th class="text-end pnl-qtr" style="display:none;min-width:85px;background:#F0FDF4">Q2</th>
                <th class="text-end pnl-qtr" style="display:none;min-width:85px;background:#F0FDF4">Q3</th>
                <th class="text-end pnl-qtr" style="display:none;min-width:85px;background:#F0FDF4">Q4</th>
            </tr>
        </thead>

        {{-- ── REVENUE section ── --}}
        <tbody>
            <tr style="background:#F0FDF4">
                <td colspan="14" style="font-size:11px;font-weight:700;color:#059669;
                                        text-transform:uppercase;letter-spacing:1px;padding:8px 12px">
                    <i class="fas fa-arrow-trend-up me-2"></i>I. Revenue
                </td>
            </tr>
        </tbody>
        <tbody id="pnl_revenue"></tbody>
        <tbody>
            <tr style="background:#DCFCE7;font-weight:700;border-top:2px solid #86EFAC">
                <td style="padding-left:12px;color:#065F46">
                    Total Revenue
                    <small class="fw-normal text-muted ms-2">100%</small>
                </td>
                <td class="text-end">{{ number_format($rt['effective'], 0) }}</td>
                <td class="text-end">{{ number_format($rt['actual'], 0) }}</td>
                <td class="text-end" style="color:{{ $rt['variance'] >= 0 ? '#10B981' : '#F43F5E' }}">
                    {{ $rt['variance'] >= 0 ? '+' : '' }}{{ number_format($rt['variance'], 0) }}
                    <small>({{ $rt['pct'] >= 0 ? '+' : '' }}{{ $rt['pct'] }}%)</small>
                </td>
                <td class="text-end pnl-prev" style="{{ !$prevPeriod ? 'display:none' : '' }}">
                    {{ $prevPeriod ? number_format($rt['prev_budget'], 0) : '—' }}
                </td>
                <td class="text-end pnl-prev" style="{{ !$prevPeriod ? 'display:none' : '' }}">
                    {{ $prevPeriod ? number_format($rt['prev_actual'], 0) : '—' }}
                </td>
                <td class="text-end pnl-prev" style="{{ !$prevPeriod ? 'display:none' : '' }}">
                    @if($prevPeriod)
                    @php
                        $rGrow = $rt['growth_pct'];
                        $rGrowColor = $rGrow === null ? '#64748B' : ($rGrow >= 0 ? '#10B981' : '#F43F5E');
                    @endphp
                    <span style="color:{{ $rGrowColor }}">
                        {{ $rGrow !== null ? ($rGrow >= 0 ? '+' : '') . $rGrow . '%' : '—' }}
                    </span>
                    <small class="text-muted ms-1">
                        / {{ $rt['prev_var_pct'] >= 0 ? '+' : '' }}{{ $rt['prev_var_pct'] ?? 0 }}%
                    </small>
                    @else —
                    @endif
                </td>
                <td class="text-end pnl-qtr" style="display:none">—</td>
                <td class="text-end pnl-qtr" style="display:none">—</td>
                <td class="text-end pnl-qtr" style="display:none">—</td>
                <td class="text-end pnl-qtr" style="display:none">—</td>
            </tr>
        </tbody>

        {{-- ── EXPENSES section ── --}}
        <tbody>
            <tr><td colspan="14" style="padding:3px;background:#F8FAFC"></td></tr>
            <tr style="background:#FFF7ED">
                <td colspan="14" style="font-size:11px;font-weight:700;color:#D97706;
                                        text-transform:uppercase;letter-spacing:1px;padding:8px 12px">
                    <i class="fas fa-arrow-trend-down me-2"></i>II. Expenses
                </td>
            </tr>
        </tbody>
        <tbody id="pnl_expense"></tbody>
        <tbody>
            <tr style="background:#FEE2E2;font-weight:700;border-top:2px solid #FCA5A5">
                <td style="padding-left:12px;color:#991B1B">
                    Total Expenses
                    <small class="fw-normal text-muted ms-2">100%</small>
                </td>
                <td class="text-end">{{ number_format($et['effective'], 0) }}</td>
                <td class="text-end">{{ number_format($et['actual'], 0) }}</td>
                <td class="text-end" style="color:{{ $et['variance'] >= 0 ? '#10B981' : '#F43F5E' }}">
                    {{ $et['variance'] >= 0 ? '+' : '' }}{{ number_format($et['variance'], 0) }}
                    <small>({{ $et['pct'] >= 0 ? '+' : '' }}{{ $et['pct'] }}%)</small>
                </td>
                <td class="text-end pnl-prev" style="{{ !$prevPeriod ? 'display:none' : '' }}">
                    {{ $prevPeriod ? number_format($et['prev_budget'], 0) : '—' }}
                </td>
                <td class="text-end pnl-prev" style="{{ !$prevPeriod ? 'display:none' : '' }}">
                    {{ $prevPeriod ? number_format($et['prev_actual'], 0) : '—' }}
                </td>
                <td class="text-end pnl-prev" style="{{ !$prevPeriod ? 'display:none' : '' }}">
                    @if($prevPeriod)
                    @php
                        $eGrow = $et['growth_pct'];
                        $eGrowColor = $eGrow === null ? '#64748B' : ($eGrow >= 0 ? '#F43F5E' : '#10B981');
                    @endphp
                    <span style="color:{{ $eGrowColor }}">
                        {{ $eGrow !== null ? ($eGrow >= 0 ? '+' : '') . $eGrow . '%' : '—' }}
                    </span>
                    <small class="text-muted ms-1">
                        / {{ $et['prev_var_pct'] >= 0 ? '+' : '' }}{{ $et['prev_var_pct'] ?? 0 }}%
                    </small>
                    @else —
                    @endif
                </td>
                <td class="text-end pnl-qtr" style="display:none">—</td>
                <td class="text-end pnl-qtr" style="display:none">—</td>
                <td class="text-end pnl-qtr" style="display:none">—</td>
                <td class="text-end pnl-qtr" style="display:none">—</td>
            </tr>

            {{-- Net Income / Loss --}}
            @php
                $netBg  = $pnl['net_actual'] >= 0 ? '#064E3B' : '#7F1D1D';
                $netGrw = $pnl['net_growth_pct'];
                $netGrwColor = $netGrw === null ? '#94A3B8'
                    : ($pnl['net_actual'] >= 0
                        ? ($netGrw >= 0 ? '#6EE7B7' : '#FCA5A5')
                        : ($netGrw >= 0 ? '#FCA5A5' : '#6EE7B7'));
            @endphp
            <tr style="background:{{ $netBg }};color:#fff;font-size:13px;
                       font-weight:700;border-top:3px solid rgba(0,0,0,.3)">
                <td style="padding-left:12px">
                    {{ $pnl['net_actual'] >= 0 ? 'NET INCOME' : 'NET LOSS' }}
                </td>
                <td class="text-end">{{ currency() }} {{ number_format($pnl['net_budget'], 0) }}</td>
                <td class="text-end">{{ currency() }} {{ number_format($pnl['net_actual'], 0) }}</td>
                <td class="text-end">
                    {{ $pnl['net_variance'] >= 0 ? '+' : '' }}{{ currency() }} {{ number_format($pnl['net_variance'], 0) }}
                    <small class="opacity-75">({{ $pnl['net_pct'] >= 0 ? '+' : '' }}{{ $pnl['net_pct'] }}%)</small>
                </td>
                <td class="text-end pnl-prev" style="{{ !$prevPeriod ? 'display:none' : '' }}">
                    {{ $prevPeriod ? currency().' '.number_format($pnl['prev_net_budget'], 0) : '—' }}
                </td>
                <td class="text-end pnl-prev" style="{{ !$prevPeriod ? 'display:none' : '' }}">
                    {{ $prevPeriod ? currency().' '.number_format($pnl['prev_net_actual'], 0) : '—' }}
                </td>
                <td class="text-end pnl-prev" style="{{ !$prevPeriod ? 'display:none' : '' }}">
                    @if($prevPeriod && $netGrw !== null)
                    <span style="color:{{ $netGrwColor }}">
                        {{ $netGrw >= 0 ? '+' : '' }}{{ $netGrw }}%
                    </span>
                    @else —
                    @endif
                </td>
                <td colspan="4" class="pnl-qtr" style="display:none"></td>
            </tr>
        </tbody>
    </table>
    </div>

</div>
</div>{{-- /tab-pane income statement --}}

{{-- ══════════════════════════════════════════════════════
     TAB 2 · CASH FLOW
══════════════════════════════════════════════════════ --}}
<div class="tab-pane fade" id="pane-cf" role="tabpanel">
<div class="chart-card" style="border-radius:0 0 12px 12px;border-top:none">

    <div class="row g-3 mb-4">
        @foreach([1,2,3,4] as $q)
        @php $qd = $cashflow['quarterly'][$q]; $qc = $qd['net_budget'] >= 0 ? '#10B981' : '#F43F5E'; @endphp
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-accent" style="background:{{ $qc }}"></div>
                <div class="stat-label">Q{{ $q }} Net (Budget)</div>
                <div class="stat-value" style="font-size:15px;color:{{ $qc }}">
                    {{ $qd['net_budget'] >= 0 ? '+' : '' }}{{ currency() }} {{ number_format($qd['net_budget'], 0) }}
                </div>
                <div class="stat-sub">
                    In: {{ number_format($qd['budget_in'], 0) }} / Out: {{ number_format($qd['budget_out'], 0) }}
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mb-4">
        <div class="chart-title">Quarterly Cash Flow — Budget vs Actual</div>
        <canvas id="cfBarChart" height="100"></canvas>
    </div>

    <div class="chart-title mb-2">Monthly Cash Flow Detail</div>
    <div class="table-responsive">
    <table class="table table-sm table-hover mb-0">
        <thead style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#64748B">
            <tr>
                <th>Month</th>
                <th class="text-end">Budget In</th>
                <th class="text-end">Budget Out</th>
                <th class="text-end">Net (Budget)</th>
                <th class="text-end">Actual In</th>
                <th class="text-end">Actual Out</th>
                <th class="text-end">Net (Actual)</th>
                <th class="text-end">Cumulative</th>
            </tr>
        </thead>
        <tbody>
            @php $monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']; @endphp
            @foreach($cashflow['monthly'] as $m => $md)
            @php
                $nbc = $md['net_budget'] >= 0 ? '#10B981' : '#F43F5E';
                $nac = $md['net_actual'] >= 0 ? '#10B981' : '#F43F5E';
                $cc  = $md['cum_actual'] >= 0 ? '#10B981' : '#F43F5E';
                $qe  = in_array($m, [3,6,9,12]);
            @endphp
            <tr style="{{ $qe ? 'border-bottom:2px solid #E2E8F0' : '' }}">
                <td style="font-weight:{{ $qe ? 600 : 400 }}">
                    {{ $monthNames[$m - 1] }}
                    @if($qe)
                    <span class="badge ms-1" style="background:#F1F5F9;color:#64748B;font-size:10px">Q{{ ceil($m/3) }}</span>
                    @endif
                </td>
                <td class="text-end">{{ number_format($md['budget_in'], 0) }}</td>
                <td class="text-end">{{ number_format($md['budget_out'], 0) }}</td>
                <td class="text-end" style="color:{{ $nbc }};font-weight:600">
                    {{ $md['net_budget'] >= 0 ? '+' : '' }}{{ number_format($md['net_budget'], 0) }}
                </td>
                <td class="text-end">{{ number_format($md['actual_in'], 0) }}</td>
                <td class="text-end">{{ number_format($md['actual_out'], 0) }}</td>
                <td class="text-end" style="color:{{ $nac }};font-weight:600">
                    {{ $md['net_actual'] >= 0 ? '+' : '' }}{{ number_format($md['net_actual'], 0) }}
                </td>
                <td class="text-end" style="color:{{ $cc }};font-weight:600">
                    {{ $md['cum_actual'] >= 0 ? '+' : '' }}{{ number_format($md['cum_actual'], 0) }}
                </td>
            </tr>
            @endforeach
            @php $ann = $cashflow['annual']; $annNet = $ann['actual_in'] - $ann['actual_out']; @endphp
            <tr style="background:#F8FAFC;font-weight:700;border-top:3px solid #CBD5E1">
                <td>Full Year</td>
                <td class="text-end">{{ number_format($ann['budget_in'], 0) }}</td>
                <td class="text-end">{{ number_format($ann['budget_out'], 0) }}</td>
                <td class="text-end" style="color:{{ ($ann['budget_in']-$ann['budget_out'])>=0?'#10B981':'#F43F5E' }}">
                    {{ number_format($ann['budget_in'] - $ann['budget_out'], 0) }}
                </td>
                <td class="text-end">{{ number_format($ann['actual_in'], 0) }}</td>
                <td class="text-end">{{ number_format($ann['actual_out'], 0) }}</td>
                <td class="text-end" style="color:{{ $annNet>=0?'#10B981':'#F43F5E' }}">{{ number_format($annNet, 0) }}</td>
                <td class="text-end" style="color:{{ $annNet>=0?'#10B981':'#F43F5E' }}">{{ number_format($annNet, 0) }}</td>
            </tr>
        </tbody>
    </table>
    </div>
</div>
</div>

{{-- ══════════════════════════════════════════════════════
     TAB 3 · BALANCE SHEET
══════════════════════════════════════════════════════ --}}
<div class="tab-pane fade" id="pane-bs" role="tabpanel">
<div class="chart-card" style="border-radius:0 0 12px 12px;border-top:none">

@if(!$balanceSheet || (empty($balanceSheet['sections']['assets']) && empty($balanceSheet['sections']['liabilities'])))
<div class="text-center py-5 text-muted">
    <i class="fas fa-balance-scale fa-2x mb-3 opacity-25"></i>
    <p class="mb-1">No Assets or Liabilities budget data for this period.</p>
    <p class="small">Add account categories with type <strong>Assets</strong> or <strong>Liabilities</strong> to populate this report.</p>
</div>
@else
@php
    $bsAt = $balanceSheet['totals']['assets'];
    $bsLt = $balanceSheet['totals']['liabilities'];
    $bsNetA = $balanceSheet['net_actual'];
    $bsNetB = $balanceSheet['net_budget'];
    $bsnc  = $bsNetA >= 0 ? '#10B981' : '#F43F5E';
@endphp
{{-- KPI strip --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#1B2A4A"></div>
            <div class="stat-label">Total Assets (Budget)</div>
            <div class="stat-value" style="font-size:16px">{{ currency() }} {{ number_format($bsAt['effective'], 0) }}</div>
            <div class="stat-sub">YTD Actual: {{ currency() }} {{ number_format($bsAt['actual'], 0) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#7C2D12"></div>
            <div class="stat-label">Total Liabilities (Budget)</div>
            <div class="stat-value" style="font-size:16px">{{ currency() }} {{ number_format($bsLt['effective'], 0) }}</div>
            <div class="stat-sub">YTD Actual: {{ currency() }} {{ number_format($bsLt['actual'], 0) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:{{ $bsnc }}"></div>
            <div class="stat-label">Net Position (Budget)</div>
            <div class="stat-value" style="font-size:16px;color:{{ $bsNetB >= 0 ? '#10B981' : '#F43F5E' }}">
                {{ $bsNetB >= 0 ? '+' : '' }}{{ currency() }} {{ number_format($bsNetB, 0) }}
            </div>
            <div class="stat-sub">Assets − Liabilities</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:{{ $bsnc }}"></div>
            <div class="stat-label">Net Position (YTD Actual)</div>
            <div class="stat-value" style="font-size:16px;color:{{ $bsnc }}">
                {{ $bsNetA >= 0 ? '+' : '' }}{{ currency() }} {{ number_format($bsNetA, 0) }}
            </div>
        </div>
    </div>
</div>

{{-- Toolbar --}}
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    @if($prevPeriod)
    <div class="d-flex gap-3 flex-wrap" style="font-size:11px">
        <span class="badge" style="background:#EFF6FF;color:#1D4ED8;font-weight:500">Current: {{ $period->name }}</span>
        <span class="badge" style="background:#F5F3FF;color:#6D28D9;font-weight:500">Prior: {{ $prevPeriod->name }}</span>
    </div>
    @else
    <div></div>
    @endif
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-secondary" onclick="bsExpandAll()" style="font-size:12px">
            <i class="fas fa-expand-alt me-1"></i>Expand All
        </button>
    </div>
</div>

{{-- Balance Sheet Table --}}
<div class="table-responsive" style="max-height:70vh;overflow-y:auto">
<table class="table table-sm mb-0" id="bsTable" style="min-width:900px">
    <thead style="font-size:10px;text-transform:uppercase;letter-spacing:.4px;color:#64748B;
                  position:sticky;top:0;background:#fff;z-index:2">
        <tr>
            <th rowspan="2" style="min-width:260px;vertical-align:bottom">Account</th>
            <th colspan="{{ $prevPeriod ? 2 : 1 }}" class="text-center"
                style="border-bottom:1px solid #E2E8F0;background:#EFF6FF;color:#1D4ED8">
                {{ $period->name }}
            </th>
            @if($prevPeriod)
            <th colspan="2" class="text-center"
                style="border-bottom:1px solid #E2E8F0;background:#F5F3FF;color:#6D28D9">
                {{ $prevPeriod->name }}
            </th>
            @endif
        </tr>
        <tr>
            <th class="text-end" style="background:#EFF6FF;min-width:120px">Eff. Budget</th>
            <th class="text-end" style="background:#EFF6FF;min-width:120px">YTD Actual</th>
            @if($prevPeriod)
            <th class="text-end" style="background:#F5F3FF;min-width:120px">Prev Budget</th>
            <th class="text-end" style="background:#F5F3FF;min-width:120px">Growth %</th>
            @endif
        </tr>
    </thead>

    {{-- ASSETS section --}}
    <tbody>
        <tr style="background:#EFF6FF">
            <td colspan="{{ $prevPeriod ? 5 : 3 }}"
                style="font-size:11px;font-weight:700;color:#1B2A4A;text-transform:uppercase;
                       letter-spacing:1px;padding:8px 12px">
                <i class="fas fa-landmark me-2"></i>I. Assets
            </td>
        </tr>
    </tbody>
    <tbody id="bs_assets"></tbody>
    <tbody>
        <tr style="background:#DBEAFE;font-weight:700;border-top:2px solid #93C5FD">
            <td style="padding-left:12px;color:#1E3A5F">Total Assets</td>
            <td class="text-end">{{ number_format($bsAt['effective'], 0) }}</td>
            <td class="text-end">{{ number_format($bsAt['actual'], 0) }}</td>
            @if($prevPeriod)
            <td class="text-end">{{ number_format($bsAt['prev_budget'], 0) }}</td>
            <td class="text-end" style="color:{{ ($bsAt['growth_pct'] ?? 0) >= 0 ? '#10B981' : '#F43F5E' }}">
                @if($bsAt['growth_pct'] !== null)
                    {{ $bsAt['growth_pct'] >= 0 ? '+' : '' }}{{ $bsAt['growth_pct'] }}%
                @else —
                @endif
            </td>
            @endif
        </tr>
    </tbody>

    {{-- LIABILITIES section --}}
    <tbody>
        <tr style="background:#FFF1F2">
            <td colspan="{{ $prevPeriod ? 5 : 3 }}"
                style="font-size:11px;font-weight:700;color:#7C2D12;text-transform:uppercase;
                       letter-spacing:1px;padding:8px 12px">
                <i class="fas fa-file-invoice me-2"></i>II. Liabilities
            </td>
        </tr>
    </tbody>
    <tbody id="bs_liabilities"></tbody>
    <tbody>
        <tr style="background:#FFE4E6;font-weight:700;border-top:2px solid #FCA5A5">
            <td style="padding-left:12px;color:#7C2D12">Total Liabilities</td>
            <td class="text-end">{{ number_format($bsLt['effective'], 0) }}</td>
            <td class="text-end">{{ number_format($bsLt['actual'], 0) }}</td>
            @if($prevPeriod)
            <td class="text-end">{{ number_format($bsLt['prev_budget'], 0) }}</td>
            <td class="text-end" style="color:{{ ($bsLt['growth_pct'] ?? 0) >= 0 ? '#F43F5E' : '#10B981' }}">
                @if($bsLt['growth_pct'] !== null)
                    {{ $bsLt['growth_pct'] >= 0 ? '+' : '' }}{{ $bsLt['growth_pct'] }}%
                @else —
                @endif
            </td>
            @endif
        </tr>
    </tbody>

    {{-- NET POSITION --}}
    <tbody>
        <tr style="background:#0F172A;font-weight:700;border-top:3px solid #C9A84C">
            <td style="padding-left:12px;color:#C9A84C;font-size:13px">NET ASSETS / (LIABILITIES)</td>
            <td class="text-end" style="color:{{ $bsNetB >= 0 ? '#10B981' : '#F43F5E' }}">
                {{ $bsNetB >= 0 ? '+' : '' }}{{ number_format($bsNetB, 0) }}
            </td>
            <td class="text-end" style="color:{{ $bsNetA >= 0 ? '#10B981' : '#F43F5E' }}">
                {{ $bsNetA >= 0 ? '+' : '' }}{{ number_format($bsNetA, 0) }}
            </td>
            @if($prevPeriod)
            <td class="text-end" style="color:#94A3B8">
                {{ number_format($balanceSheet['totals']['assets']['prev_budget'] - $balanceSheet['totals']['liabilities']['prev_budget'], 0) }}
            </td>
            <td class="text-end" style="color:#94A3B8">—</td>
            @endif
        </tr>
    </tbody>
</table>
</div>
@endif
</div>
</div>

</div>{{-- /tab-content --}}

{{-- ══ Scripts ══ --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Data ──────────────────────────────────────────────────────────────────
const PNL_REVENUE  = @json($pnl['sections']['revenue']);
const PNL_EXPENSE  = @json($pnl['sections']['expense']);
const CF_QUARTERLY = @json($cashflow['quarterly']);
const CF_MONTHLY   = @json($cashflow['monthly']);
const HAS_PREV     = {{ $prevPeriod ? 'true' : 'false' }};
const BS_ASSETS      = @json($balanceSheet['sections']['assets'] ?? []);
const BS_LIABILITIES = @json($balanceSheet['sections']['liabilities'] ?? []);

// ── Formatting helpers ─────────────────────────────────────────────────────
function n0(v)  { return Number(v ?? 0).toLocaleString('en-GH', {minimumFractionDigits:0, maximumFractionDigits:0}); }
function esc(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function vs(v)  { return v >= 0 ? '#10B981' : '#F43F5E'; }
function pfx(v) { return v > 0 ? '+' : ''; }
function pct(v) { return `<small class="opacity-75">(${pfx(v)}${v}%)</small>`; }

// ── Toggle state ───────────────────────────────────────────────────────────
let showQtrs = false;
const catState = {revenue:{}, expense:{}};
const PNL_LIMIT = 10;

function toggleQuarterly() {
    showQtrs = !showQtrs;
    document.querySelectorAll('.pnl-qtr').forEach(el => {
        el.style.display = showQtrs ? '' : 'none';
    });
    document.getElementById('toggleQtrsBtn').innerHTML =
        `<i class="fas fa-columns me-1"></i>${showQtrs ? 'Hide' : 'Show'} Q1–Q4`;
    renderSection('revenue');
    renderSection('expense');
}

function expandAll() {
    ['revenue','expense'].forEach(type => {
        const cats = type === 'revenue' ? PNL_REVENUE : PNL_EXPENSE;
        cats.forEach((_, i) => {
            catState[type][i] = {expanded: true, showAll: true};
        });
        renderSection(type);
    });
}

// ── Row builders ───────────────────────────────────────────────────────────
function qCols(r) {
    if (!showQtrs) return '';
    return `<td class="text-end pnl-qtr">${n0(r.q1)}</td>
            <td class="text-end pnl-qtr">${n0(r.q2)}</td>
            <td class="text-end pnl-qtr">${n0(r.q3)}</td>
            <td class="text-end pnl-qtr">${n0(r.q4)}</td>`;
}
function prevCols(r, type) {
    if (!HAS_PREV) return '';
    const gc = type === 'revenue'
        ? (r.growth_pct == null ? '#64748B' : (r.growth_pct >= 0 ? '#10B981' : '#F43F5E'))
        : (r.growth_pct == null ? '#64748B' : (r.growth_pct >= 0 ? '#F43F5E' : '#10B981'));
    const growStr = r.growth_pct == null ? '—'
        : `<span style="color:${gc}">${pfx(r.growth_pct)}${r.growth_pct}%</span>`;
    const pvpStr  = r.prev_var_pct == null ? '—'
        : `<small class="text-muted ms-1">/ ${pfx(r.prev_var_pct)}${r.prev_var_pct}%</small>`;
    return `<td class="text-end pnl-prev">${n0(r.prev_budget)}</td>
            <td class="text-end pnl-prev">${n0(r.prev_actual)}</td>
            <td class="text-end pnl-prev">${growStr}${pvpStr}</td>`;
}

function buildCatRow(cat, type, idx) {
    const state  = catState[type][idx] ?? {};
    const isOpen = !!state.expanded;
    const bg     = type === 'revenue' ? '#F0FDF4' : '#FFF7ED';
    const clr    = type === 'revenue' ? '#059669' : '#D97706';
    const t      = cat.total;
    return `<tr style="background:${bg};cursor:pointer;user-select:none"
                onclick="toggleCat('${type}',${idx})">
        <td style="padding-left:12px;font-weight:600;color:${clr}">
            <i class="fas fa-chevron-right me-2"
               style="font-size:10px;transform:${isOpen?'rotate(90deg)':'rotate(0)'};transition:transform .15s"></i>
            ${esc(cat.name)}
            <span class="badge ms-2" style="background:#E2E8F0;color:#475569;font-size:10px">${cat.codes.length}</span>
            <small class="fw-normal text-muted ms-2">${t.common_size}%</small>
        </td>
        <td class="text-end fw-semibold">${n0(t.effective)}</td>
        <td class="text-end">${n0(t.actual)}</td>
        <td class="text-end" style="color:${vs(t.variance)}">${pfx(t.variance)}${n0(t.variance)} ${pct(t.pct)}</td>
        ${prevCols(t, type)}
        ${qCols({q1:cat.codes.reduce((s,r)=>s+r.q1,0),q2:cat.codes.reduce((s,r)=>s+r.q2,0),q3:cat.codes.reduce((s,r)=>s+r.q3,0),q4:cat.codes.reduce((s,r)=>s+r.q4,0)})}
    </tr>`;
}

function buildCodeRow(r, type) {
    return `<tr style="font-size:12px">
        <td style="padding-left:36px">
            <code style="font-size:11px;color:#64748B">${esc(r.code)}</code>
            <span class="ms-2">${esc(r.name)}</span>
            <small class="text-muted ms-1">${r.common_size}%</small>
        </td>
        <td class="text-end">${n0(r.effective)}</td>
        <td class="text-end">${n0(r.actual)}</td>
        <td class="text-end" style="color:${vs(r.variance)}">${pfx(r.variance)}${n0(r.variance)} ${pct(r.pct)}</td>
        ${prevCols(r, type)}
        ${qCols(r)}
    </tr>`;
}

function buildShowMore(type, idx, total, shown) {
    return `<tr style="background:#F8FAFC;font-size:12px">
        <td colspan="14" style="padding-left:36px">
            <button class="btn btn-sm btn-link p-0 text-muted" style="font-size:12px"
                    onclick="showAllCat('${type}',${idx});event.stopPropagation()">
                Show all ${total} items (${total - shown} more)
            </button>
        </td>
    </tr>`;
}

// ── Section renderer ───────────────────────────────────────────────────────
function renderSection(type) {
    const cats  = type === 'revenue' ? PNL_REVENUE : PNL_EXPENSE;
    const tbody = document.getElementById(`pnl_${type}`);
    const q     = document.getElementById('pnlSearch').value.trim().toLowerCase();
    let html    = '';

    cats.forEach((cat, idx) => {
        const state   = catState[type][idx] ?? {};
        const isOpen  = !!(state.expanded || q);
        const showAll = !!state.showAll;

        const codes = q
            ? cat.codes.filter(r => r.code.toLowerCase().includes(q) || r.name.toLowerCase().includes(q))
            : cat.codes;

        if (q && codes.length === 0) return;

        html += buildCatRow(cat, type, idx);

        if (isOpen) {
            const limit   = showAll ? codes.length : Math.min(PNL_LIMIT, codes.length);
            codes.slice(0, limit).forEach(r => { html += buildCodeRow(r, type); });
            if (!showAll && codes.length > PNL_LIMIT) {
                html += buildShowMore(type, idx, codes.length, limit);
            }
        }
    });

    tbody.innerHTML = html || `<tr><td colspan="14" class="text-center text-muted py-3">No items.</td></tr>`;
}

function toggleCat(type, idx) {
    if (!catState[type][idx]) catState[type][idx] = {};
    catState[type][idx].expanded = !catState[type][idx].expanded;
    renderSection(type);
}
function showAllCat(type, idx) {
    catState[type][idx] = {expanded: true, showAll: true};
    renderSection(type);
}

// ── Search ─────────────────────────────────────────────────────────────────
document.getElementById('pnlSearch').addEventListener('input', function () {
    renderSection('revenue');
    renderSection('expense');
});

// Initial render
renderSection('revenue');
renderSection('expense');

// ── Export ─────────────────────────────────────────────────────────────────
function pnlExport(format) {
    const q    = document.getElementById('pnlSearch').value.trim().toLowerCase();
    const rows = [];
    ['revenue','expense'].forEach(type => {
        const cats = type === 'revenue' ? PNL_REVENUE : PNL_EXPENSE;
        cats.forEach(cat => {
            const codes = q
                ? cat.codes.filter(r => r.code.toLowerCase().includes(q) || r.name.toLowerCase().includes(q))
                : cat.codes;
            codes.forEach(r => rows.push({
                Type: type === 'revenue' ? 'Revenue' : 'Expense',
                Category: cat.name, Code: r.code, Account: r.name,
                'Eff Budget': r.effective, 'YTD Actual': r.actual,
                'Common Size%': r.common_size,
                'Variance': r.variance, 'Var%': r.pct,
                'Growth%': r.growth_pct ?? '',
                'Prev Budget': r.prev_budget, 'Prev Actual': r.prev_actual,
                'Prev Var%': r.prev_var_pct ?? '',
                Q1: r.q1, Q2: r.q2, Q3: r.q3, Q4: r.q4,
            }));
        });
    });

    const headers = Object.keys(rows[0] ?? {});
    const stamp   = new Date().toISOString().slice(0,10);

    if (format === 'csv') {
        const csv = [headers, ...rows.map(r => Object.values(r))]
            .map(c => c.map(v => `"${String(v ?? '').replace(/"/g,'""')}"`).join(','))
            .join('\n');
        dlBlob(csv, `income-statement-${stamp}.csv`, 'text/csv;charset=utf-8;');
    }
    if (format === 'json') {
        dlBlob(JSON.stringify(rows, null, 2), `income-statement-${stamp}.json`, 'application/json');
    }
    if (format === 'copy') {
        const tsv = [headers, ...rows.map(r => Object.values(r))].map(c => c.join('\t')).join('\n');
        navigator.clipboard.writeText(tsv).then(() => {
            const btn = document.getElementById('pnlCopyBtn');
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check me-2 text-success"></i>Copied!';
            setTimeout(() => { btn.innerHTML = orig; }, 2000);
        });
    }
}

function dlBlob(content, filename, mime) {
    const a = Object.assign(document.createElement('a'), {
        href: URL.createObjectURL(new Blob([content], {type: mime})), download: filename,
    });
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
}

// ── Cash Flow chart ────────────────────────────────────────────────────────
new Chart(document.getElementById('cfBarChart'), {
    type: 'bar',
    data: {
        labels: ['Q1','Q2','Q3','Q4'],
        datasets: [
            {
                label: 'Budget Net',
                data: [1,2,3,4].map(q => CF_QUARTERLY[q].net_budget),
                backgroundColor: [1,2,3,4].map(q =>
                    CF_QUARTERLY[q].net_budget >= 0 ? '#6EE7B7' : '#FCA5A5'),
                borderRadius: 4,
            },
            {
                label: 'Actual Net',
                data: [1,2,3,4].map(q => CF_QUARTERLY[q].net_actual),
                backgroundColor: [1,2,3,4].map(q =>
                    CF_QUARTERLY[q].net_actual >= 0 ? '#34D399' : '#F87171'),
                borderRadius: 4,
            },
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top', labels: { font: { size: 11 }, boxWidth: 12 } } },
        scales: {
            y: { grid: { color: '#F1F5F9' },
                 ticks: { font: { size: 11 },
                     callback: v => v>=1e6?(v/1e6).toFixed(1)+'M':v>=1e3?(v/1e3).toFixed(0)+'K':v } },
            x: { grid: { display: false }, ticks: { font: { size: 11 } } }
        }
    }
});

// ── Balance Sheet rendering ───────────────────────────────────────────────
const bsState = {assets:{}, liabilities:{}};

function renderBsSection(type) {
    const data = type === 'assets' ? BS_ASSETS : BS_LIABILITIES;
    const tbody = document.getElementById('bs_' + type);
    if (!tbody) return;
    let html = '';
    data.forEach((cat, ci) => {
        const st    = bsState[type][ci] || {expanded: false};
        const arrow = st.expanded ? '▾' : '▸';
        html += `<tr style="background:#F8FAFC;cursor:pointer" onclick="bsToggleCat('${type}',${ci})">
            <td style="padding-left:12px;font-weight:600;font-size:13px;color:#1B2A4A">
                <span style="margin-right:6px;opacity:.6">${arrow}</span>${esc(cat.name)}
                <small class="text-muted fw-normal ms-1">${cat.total.common_size}%</small>
            </td>
            <td class="text-end fw-semibold">${n0(cat.total.effective)}</td>
            <td class="text-end fw-semibold">${n0(cat.total.actual)}</td>
            ${HAS_PREV ? `<td class="text-end fw-semibold">${n0(cat.total.prev_budget)}</td>
            <td class="text-end fw-semibold" style="color:${(cat.total.growth_pct??0)>=0?'#10B981':'#F43F5E'}">
                ${cat.total.growth_pct!==null?(cat.total.growth_pct>=0?'+':'')+cat.total.growth_pct+'%':'—'}
            </td>` : ''}
        </tr>`;
        if (st.expanded) {
            cat.codes.forEach(row => {
                html += `<tr>
                    <td style="padding-left:36px;font-size:12px">
                        <span style="color:#64748B;font-family:monospace;margin-right:6px">${esc(row.code)}</span>
                        ${esc(row.name)}
                        <small class="text-muted ms-1">${row.common_size}%</small>
                    </td>
                    <td class="text-end" style="font-size:12px">${n0(row.effective)}</td>
                    <td class="text-end" style="font-size:12px">${n0(row.actual)}</td>
                    ${HAS_PREV ? `<td class="text-end" style="font-size:12px">${n0(row.prev_budget)}</td>
                    <td class="text-end" style="font-size:12px;color:${(row.growth_pct??0)>=0?'#10B981':'#F43F5E'}">
                        ${row.growth_pct!==null?(row.growth_pct>=0?'+':'')+row.growth_pct+'%':'—'}
                    </td>` : ''}
                </tr>`;
            });
        }
    });
    tbody.innerHTML = html;
}

function bsToggleCat(type, ci) {
    bsState[type][ci] = {expanded: !(bsState[type][ci]?.expanded)};
    renderBsSection(type);
}

function bsExpandAll() {
    ['assets','liabilities'].forEach(type => {
        const data = type === 'assets' ? BS_ASSETS : BS_LIABILITIES;
        data.forEach((_, i) => { bsState[type][i] = {expanded: true}; });
        renderBsSection(type);
    });
}

// Initial render on tab activation
document.querySelector('[data-bs-target="#pane-bs"]')?.addEventListener('shown.bs.tab', () => {
    renderBsSection('assets');
    renderBsSection('liabilities');
});
</script>
@endif
@endsection
