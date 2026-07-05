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
            <!--<button class="btn btn-sm btn-outline-secondary" id="toggleQtrsBtn"
                    onclick="toggleQuarterly()" style="font-size:12px">
                <i class="fas fa-columns me-1"></i>Show Q1–Q4
            </button>
            <button id="pnlExpandBtn" class="btn btn-sm btn-outline-secondary"
                    onclick="toggleExpandAll()" style="font-size:12px">
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
            </div>-->
            @can('manage users')
            <a href="{{ route('admin.income-statement-configs.index') }}"
               class="btn btn-sm btn-outline-secondary" style="font-size:12px"
               title="Configure P&L Layout">
                <i class="fas fa-sliders-h me-1"></i>Layout
            </a>
            @endcan
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

    {{-- ════════════════════════════════════════════════════════
         CONFIGURED STATEMENT (when an active layout exists)
    ════════════════════════════════════════════════════════ --}}
    @if($configuredStatement)
    @php $cs = $configuredStatement; @endphp

    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
        <span class="badge" style="background:#EFF6FF;color:#1D4ED8;font-size:10px;border-radius:4px">
            <i class="fas fa-sliders-h me-1"></i>Layout: {{ $activeConfig->name }}
        </span>
        <div class="d-flex gap-2 align-items-center">
            <button id="cfgExpandBtn" class="btn btn-sm btn-outline-secondary"
                    onclick="cfgToggleExpandAll()" style="font-size:12px">
                <i class="fas fa-expand-alt me-1"></i>Expand All
            </button>
            @if($cs['has_cs'])
            <button id="cfgCsBtn" class="btn btn-sm btn-outline-secondary"
                    onclick="cfgToggleCs()" style="font-size:12px">
                <i class="fas fa-percentage me-1"></i>CS%
            </button>
            @endif
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                        data-bs-toggle="dropdown" style="font-size:12px">
                    <i class="fas fa-download me-1"></i>Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:13px">
                    <li>
                        <a class="dropdown-item" href="#"
                           onclick="cfgExport();return false">
                            <i class="fas fa-file-excel me-2 text-success"></i>Excel
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="table-responsive mb-3">
    <table class="table table-sm mb-0" id="cfgTable"
           style="min-width:700px;border-collapse:separate;border-spacing:0">
        <thead style="font-size:10px;text-transform:uppercase;letter-spacing:.4px;color:#64748B;
                      position:sticky;top:0;background:#fff;z-index:2">
            <tr>
                <th style="min-width:240px;padding:8px 12px">Account</th>
                <th class="text-end" style="min-width:110px;background:#EFF6FF;color:#1D4ED8;padding:8px 12px">
                    Eff. Budget
                </th>
                @if($cs['has_cs'])
                <th class="text-end cfg-cs-col d-none"
                    style="min-width:80px;background:#FFFBEB;color:#92400E;padding:8px 12px">
                    CS% Bud
                </th>
                @endif
                <th class="text-end" style="min-width:110px;background:#EFF6FF;color:#1D4ED8;padding:8px 12px">
                    YTD Actual
                </th>
                <th class="text-end" style="min-width:110px;background:#EFF6FF;color:#1D4ED8;padding:8px 12px">
                    Variance&nbsp;/ %
                </th>
                @if($prevPeriod)
                <th class="text-end" style="min-width:110px;background:#F5F3FF;color:#6D28D9;padding:8px 12px">
                    Prev Budget
                </th>
                <th class="text-end" style="min-width:110px;background:#F5F3FF;color:#6D28D9;padding:8px 12px">
                    Prev Actual
                </th>
                <th class="text-end" style="min-width:100px;background:#F5F3FF;color:#6D28D9;padding:8px 12px">
                    Growth
                </th>
                @endif
                @if($cs['has_cs'] && $prevPeriod)
                <th class="text-end cfg-cs-col d-none"
                    style="min-width:80px;background:#FFFBEB;color:#92400E;padding:8px 12px">
                    CS% Prev
                </th>
                @endif
            </tr>
        </thead>
        <tbody>
        @foreach($cs['lines'] as $csRow)

        @if($csRow['type'] === 'spacer')
        <tr><td colspan="99" style="padding:4px 0;background:#F8FAFC"></td></tr>

        @elseif($csRow['type'] === 'subtotal')
        {{-- Bold subtotal row with top border --}}
        <tr style="background:#F8FAFC;font-weight:700;border-top:2px solid #CBD5E1">
            <td style="padding:9px 16px;color:#1B2A4A;font-size:13px">
                <i class="fas fa-equals me-2" style="color:#64748B;font-size:10px"></i>
                {{ $csRow['label'] }}
            </td>
            <td class="text-end" style="padding:9px 12px;font-variant-numeric:tabular-nums">
                {{ number_format($csRow['budget'], 0) }}
            </td>
            @if($cs['has_cs'])
            <td class="text-end cfg-cs-col d-none"
                style="padding:9px 12px;font-size:12px;color:#92400E;font-variant-numeric:tabular-nums">
                {{ $csRow['cs_bud'] !== null ? $csRow['cs_bud'].'%' : '—' }}
            </td>
            @endif
            <td class="text-end" style="padding:9px 12px;font-variant-numeric:tabular-nums">
                {{ number_format($csRow['actual'], 0) }}
            </td>
            @php
                $csVarColor = $csRow['variance'] >= 0 ? '#10B981' : '#F43F5E';
            @endphp
            <td class="text-end" style="padding:9px 12px;color:{{ $csVarColor }};font-variant-numeric:tabular-nums">
                {{ $csRow['variance'] >= 0 ? '+' : '' }}{{ number_format($csRow['variance'], 0) }}
                <small class="ms-1">({{ $csRow['pct'] >= 0 ? '+' : '' }}{{ $csRow['pct'] }}%)</small>
            </td>
            @if($prevPeriod)
            <td class="text-end" style="padding:9px 12px;font-variant-numeric:tabular-nums">
                {{ number_format($csRow['prev_budget'], 0) }}
            </td>
            <td class="text-end" style="padding:9px 12px;font-variant-numeric:tabular-nums">
                {{ number_format($csRow['prev_actual'], 0) }}
            </td>
            <td class="text-end" style="padding:9px 12px">
                @if($csRow['growth_pct'] !== null)
                <span style="color:{{ $csRow['growth_pct'] >= 0 ? '#10B981' : '#F43F5E' }}">
                    {{ $csRow['growth_pct'] >= 0 ? '+' : '' }}{{ $csRow['growth_pct'] }}%
                </span>
                @else —
                @endif
            </td>
            @endif
            @if($cs['has_cs'] && $prevPeriod)
            <td class="text-end cfg-cs-col d-none"
                style="padding:9px 12px;font-size:12px;color:#92400E;font-variant-numeric:tabular-nums">
                {{ ($csRow['cs_prev_bud'] ?? null) !== null ? $csRow['cs_prev_bud'].'%' : '—' }}
            </td>
            @endif
        </tr>

        @else {{-- sub_category --}}
        @php
            $isLess  = ($csRow['operator'] === 'subtract');
            $varClr  = $csRow['variance'] >= 0 ? '#10B981' : '#F43F5E';
        @endphp
        <tr class="cfg-subcat-row"
            data-subcat="{{ $csRow['sub_cat_id'] ?? '' }}"
            onclick="toggleCfgSubCat(this)"
            style="border-bottom:1px solid #F1F5F9;cursor:pointer">
            <td style="padding:8px 12px 8px 16px;font-size:13px;color:#374151">
                <i class="fas fa-chevron-right cfg-chevron"
                   style="font-size:9px;margin-right:6px;transition:transform .15s;color:#94A3B8"></i>
                @if($isLess)
                <span style="color:#94A3B8;font-size:11px;margin-right:4px">(Less)</span>
                @endif
                {{ $csRow['label'] }}
            </td>
            <td class="text-end" style="padding:8px 12px;font-size:13px;font-variant-numeric:tabular-nums">
                {{ number_format($csRow['budget'], 0) }}
            </td>
            @if($cs['has_cs'])
            <td class="text-end cfg-cs-col d-none"
                style="padding:8px 12px;font-size:12px;color:#92400E;font-variant-numeric:tabular-nums">
                {{ $csRow['cs_bud'] !== null ? $csRow['cs_bud'].'%' : '—' }}
            </td>
            @endif
            <td class="text-end" style="padding:8px 12px;font-size:13px;font-variant-numeric:tabular-nums">
                {{ number_format($csRow['actual'], 0) }}
            </td>
            <td class="text-end" style="padding:8px 12px;font-size:12px;color:{{ $varClr }};font-variant-numeric:tabular-nums">
                {{ $csRow['variance'] >= 0 ? '+' : '' }}{{ number_format($csRow['variance'], 0) }}
                <small>({{ $csRow['pct'] >= 0 ? '+' : '' }}{{ $csRow['pct'] }}%)</small>
            </td>
            @if($prevPeriod)
            <td class="text-end" style="padding:8px 12px;font-size:12px;color:#8B5CF6;font-variant-numeric:tabular-nums">
                {{ number_format($csRow['prev_budget'], 0) }}
            </td>
            <td class="text-end" style="padding:8px 12px;font-size:12px;color:#8B5CF6;font-variant-numeric:tabular-nums">
                {{ number_format($csRow['prev_actual'], 0) }}
            </td>
            <td class="text-end" style="padding:8px 12px;font-size:12px">
                @if($csRow['growth_pct'] !== null)
                <span style="color:{{ $csRow['growth_pct'] >= 0 ? '#10B981' : '#F43F5E' }}">
                    {{ $csRow['growth_pct'] >= 0 ? '+' : '' }}{{ $csRow['growth_pct'] }}%
                </span>
                @else —
                @endif
            </td>
            @endif
            @if($cs['has_cs'] && $prevPeriod)
            <td class="text-end cfg-cs-col d-none"
                style="padding:8px 12px;font-size:12px;color:#92400E;font-variant-numeric:tabular-nums">
                {{ ($csRow['cs_prev_bud'] ?? null) !== null ? $csRow['cs_prev_bud'].'%' : '—' }}
            </td>
            @endif
        </tr>
        @endif

        @endforeach

        {{-- Final net profit row --}}
        @php
            $finalVar   = $cs['final_actual'] - $cs['final_budget'];
            $finalClr   = $finalVar >= 0 ? '#10B981' : '#F43F5E';
            $finalPct   = $cs['final_budget'] != 0
                ? round(($finalVar / abs($cs['final_budget'])) * 100, 1) : 0;
            $finalGrow  = ($cs['final_prev_act'] != 0)
                ? round((($cs['final_actual'] - $cs['final_prev_act']) / abs($cs['final_prev_act'])) * 100, 1)
                : null;
        @endphp
        <tr style="background:{{ $cs['final_actual'] >= 0 ? '#DCFCE7' : '#FEE2E2' }};
                   font-weight:700;border-top:3px double #CBD5E1">
            <td style="padding:11px 16px;font-size:14px;
                       color:{{ $cs['final_actual'] >= 0 ? '#065F46' : '#991B1B' }}">
                Net Income / (Loss)
            </td>
            <td class="text-end" style="padding:11px 12px;font-variant-numeric:tabular-nums">
                {{ currency() }} {{ number_format($cs['final_budget'], 0) }}
            </td>
            @if($cs['has_cs'])
            <td class="text-end cfg-cs-col d-none"
                style="padding:11px 12px;font-size:12px;color:#92400E;font-variant-numeric:tabular-nums">
                {{ $cs['final_cs_bud'] !== null ? $cs['final_cs_bud'].'%' : '—' }}
            </td>
            @endif
            <td class="text-end" style="padding:11px 12px;font-variant-numeric:tabular-nums;
                                        color:{{ $finalClr }}">
                {{ currency() }} {{ number_format($cs['final_actual'], 0) }}
            </td>
            <td class="text-end" style="padding:11px 12px;color:{{ $finalClr }};font-variant-numeric:tabular-nums">
                {{ $finalVar >= 0 ? '+' : '' }}{{ number_format($finalVar, 0) }}
                <small>({{ $finalPct >= 0 ? '+' : '' }}{{ $finalPct }}%)</small>
            </td>
            @if($prevPeriod)
            <td class="text-end" style="padding:11px 12px;font-variant-numeric:tabular-nums">
                {{ number_format($cs['final_prev_bud'], 0) }}
            </td>
            <td class="text-end" style="padding:11px 12px;font-variant-numeric:tabular-nums">
                {{ number_format($cs['final_prev_act'], 0) }}
            </td>
            <td class="text-end" style="padding:11px 12px">
                @if($finalGrow !== null)
                <span style="color:{{ $finalGrow >= 0 ? '#10B981' : '#F43F5E' }}">
                    {{ $finalGrow >= 0 ? '+' : '' }}{{ $finalGrow }}%
                </span>
                @else —
                @endif
            </td>
            @endif
            @if($cs['has_cs'] && $prevPeriod)
            <td class="text-end cfg-cs-col d-none"
                style="padding:11px 12px;font-size:12px;color:#92400E;font-variant-numeric:tabular-nums">
                {{ ($cs['final_cs_prev'] ?? null) !== null ? $cs['final_cs_prev'].'%' : '—' }}
            </td>
            @endif
        </tr>
        </tbody>
    </table>
    </div>

    @endif

    {{-- ── P&L Table (hidden when configured layout active; data still used by JS) ── --}}
    @if($configuredStatement)<div style="display:none">@endif
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

    @if($configuredStatement)</div>@endif

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

    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="chart-title mb-0">Monthly Cash Flow Detail</div>
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                    data-bs-toggle="dropdown" style="font-size:12px">
                <i class="fas fa-download me-1"></i>Export
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:13px">
                <li><a class="dropdown-item" href="#" onclick="cfExport('csv');return false">
                    <i class="fas fa-file-csv me-2 text-success"></i>CSV</a></li>
                <li><a class="dropdown-item" href="#" onclick="cfExport('json');return false">
                    <i class="fas fa-file-code me-2 text-primary"></i>JSON</a></li>
                <li><a class="dropdown-item" href="#" id="cfCopyBtn" onclick="cfExport('copy');return false">
                    <i class="fas fa-copy me-2 text-muted"></i>Copy (TSV)</a></li>
            </ul>
        </div>
    </div>
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
        <button id="bsExpandBtn" class="btn btn-sm btn-outline-secondary"
                onclick="bsToggleExpandAll()" style="font-size:12px">
            <i class="fas fa-expand-alt me-1"></i>Expand All
        </button>
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                    data-bs-toggle="dropdown" style="font-size:12px">
                <i class="fas fa-download me-1"></i>Export
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:13px">
                <li><a class="dropdown-item" href="#" onclick="bsExport('csv');return false">
                    <i class="fas fa-file-csv me-2 text-success"></i>CSV</a></li>
                <li><a class="dropdown-item" href="#" onclick="bsExport('json');return false">
                    <i class="fas fa-file-code me-2 text-primary"></i>JSON</a></li>
                <li><a class="dropdown-item" href="#" id="bsCopyBtn" onclick="bsExport('copy');return false">
                    <i class="fas fa-copy me-2 text-muted"></i>Copy (TSV)</a></li>
            </ul>
        </div>
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
<script>
// ── Data ──────────────────────────────────────────────────────────────────
const PNL_REVENUE  = @json($pnl['sections']['revenue']);
const PNL_EXPENSE  = @json($pnl['sections']['expense']);
const PNL_TOTALS   = @json($pnl['totals']);
const PNL_NET      = {
    budget:          {{ $pnl['net_budget'] }},
    actual:          {{ $pnl['net_actual'] }},
    variance:        {{ $pnl['net_variance'] }},
    pct:             {{ $pnl['net_pct'] }},
    prev_net_budget: {{ $pnl['prev_net_budget'] ?? 0 }},
    prev_net_actual: {{ $pnl['prev_net_actual'] ?? 0 }},
    growth_pct:      @json($pnl['net_growth_pct']),
};
const CF_QUARTERLY = @json($cashflow['quarterly']);
const CF_MONTHLY   = @json($cashflow['monthly']);
const HAS_PREV     = {{ $prevPeriod ? 'true' : 'false' }};
const BS_ASSETS      = @json($balanceSheet['sections']['assets'] ?? []);
const BS_LIABILITIES = @json($balanceSheet['sections']['liabilities'] ?? []);
const BS_TOTALS      = @json($balanceSheet['totals'] ?? []);
const BS_NET         = {
    budget: {{ $balanceSheet['net_budget'] ?? 0 }},
    actual: {{ $balanceSheet['net_actual'] ?? 0 }},
    prev_budget: {{ ($balanceSheet['totals']['assets']['prev_budget'] ?? 0) - ($balanceSheet['totals']['liabilities']['prev_budget'] ?? 0) }},
};

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

let pnlAllExpanded = false;
function toggleExpandAll() {
    pnlAllExpanded = !pnlAllExpanded;
    const btn = document.getElementById('pnlExpandBtn');
    btn.innerHTML = pnlAllExpanded
        ? '<i class="fas fa-compress-alt me-1"></i>Collapse All'
        : '<i class="fas fa-expand-alt me-1"></i>Expand All';
    ['revenue','expense'].forEach(type => {
        const cats = type === 'revenue' ? PNL_REVENUE : PNL_EXPENSE;
        cats.forEach((_, i) => {
            catState[type][i] = {expanded: pnlAllExpanded, showAll: pnlAllExpanded};
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

// ── Configured statement inline expand/collapse ────────────────────────────
@if($configuredStatement ?? false)
const cfgExpandState = {};

function toggleCfgSubCat(row) {
    const subCatId = String(row.dataset.subcat || '');
    if (!subCatId) return;

    const chevron = row.querySelector('.cfg-chevron');
    const isOpen  = !!cfgExpandState[subCatId];

    // Remove any existing detail rows for this sub-cat
    let sib = row.nextElementSibling;
    while (sib && sib.classList.contains('cfg-detail-row')) {
        const del = sib;
        sib = sib.nextElementSibling;
        del.remove();
    }

    if (isOpen) {
        cfgExpandState[subCatId] = false;
        if (chevron) chevron.style.transform = 'rotate(0deg)';
        return;
    }

    // Gather matching categories from the already-computed PNL data
    const allCats = [...PNL_REVENUE, ...PNL_EXPENSE]
        .filter(c => String(c.sub_cat_id) === subCatId);

    if (allCats.length === 0) {
        // No category data mapped — nothing to show
        cfgExpandState[subCatId] = false;
        return;
    }

    // Build column helpers — match cfgTable column count
    const numCols = HAS_PREV ? 7 : 4;

    const csHidden = () => !cfgCsVisible;
    const csPct    = (val, base) => base > 0 ? (val / base * 100).toFixed(1) + '%' : '—';
    const csStyle  = (hide) => `padding:6px 10px;font-size:12px;color:#92400E;font-variant-numeric:tabular-nums${hide ? ';display:none' : ''}`;

    // subCatId passed so we look up the correct per-line base
    function catCols(t, subCatId) {
        const base = CFG_HAS_CS ? (CFG_CS_BASES[subCatId] ?? null) : null;
        let h = `<td class="text-end" style="padding:6px 10px;font-size:12px;font-variant-numeric:tabular-nums">${n0(t.effective)}</td>`;
        if (CFG_HAS_CS) h += `<td class="text-end cfg-cs-col" style="${csStyle(csHidden())}">${base ? csPct(t.effective, base.bud) : '—'}</td>`;
        h += `<td class="text-end" style="padding:6px 10px;font-size:12px;font-variant-numeric:tabular-nums">${n0(t.actual)}</td>
              <td class="text-end" style="padding:6px 10px;font-size:12px;color:${vs(t.variance)};font-variant-numeric:tabular-nums">${pfx(t.variance)}${n0(t.variance)}</td>`;
        if (HAS_PREV) h += `<td class="text-end" style="padding:6px 10px;font-size:12px;color:#8B5CF6;font-variant-numeric:tabular-nums">${n0(t.prev_budget)}</td>
                            <td class="text-end" style="padding:6px 10px;font-size:12px;color:#8B5CF6;font-variant-numeric:tabular-nums">${n0(t.prev_actual)}</td>
                            <td class="text-end" style="padding:6px 10px;font-size:12px">
                                ${t.growth_pct != null ? `<span style="color:${vs(t.growth_pct)}">${pfx(t.growth_pct)}${t.growth_pct}%</span>` : '—'}
                            </td>`;
        if (CFG_HAS_CS && HAS_PREV) h += `<td class="text-end cfg-cs-col" style="${csStyle(csHidden())}">${base ? csPct(t.prev_budget, base.prev) : '—'}</td>`;
        return h;
    }

    function codeCols(r, subCatId) {
        const base = CFG_HAS_CS ? (CFG_CS_BASES[subCatId] ?? null) : null;
        let h = `<td class="text-end" style="padding:5px 10px;font-size:12px;font-variant-numeric:tabular-nums">${n0(r.effective)}</td>`;
        if (CFG_HAS_CS) h += `<td class="text-end cfg-cs-col" style="${csStyle(csHidden())}">${base ? csPct(r.effective, base.bud) : '—'}</td>`;
        h += `<td class="text-end" style="padding:5px 10px;font-size:12px;font-variant-numeric:tabular-nums">${n0(r.actual)}</td>
              <td class="text-end" style="padding:5px 10px;font-size:12px;color:${vs(r.variance)};font-variant-numeric:tabular-nums">${pfx(r.variance)}${n0(r.variance)}</td>`;
        if (HAS_PREV) h += `<td class="text-end" style="padding:5px 10px;font-size:12px;color:#8B5CF6;font-variant-numeric:tabular-nums">${n0(r.prev_budget)}</td>
                            <td class="text-end" style="padding:5px 10px;font-size:12px;color:#8B5CF6;font-variant-numeric:tabular-nums">${n0(r.prev_actual)}</td>
                            <td class="text-end" style="padding:5px 10px;font-size:12px">
                                ${r.growth_pct != null ? `<span style="color:${vs(r.growth_pct)}">${pfx(r.growth_pct)}${r.growth_pct}%</span>` : '—'}
                            </td>`;
        if (CFG_HAS_CS && HAS_PREV) h += `<td class="text-end cfg-cs-col" style="${csStyle(csHidden())}">${base ? csPct(r.prev_budget, base.prev) : '—'}</td>`;
        return h;
    }

    let insertAfter = row;

    allCats.forEach(cat => {
        // Category summary row
        const catTr = document.createElement('tr');
        catTr.className = 'cfg-detail-row';
        catTr.style.cssText = 'background:#F1F5F9;';
        catTr.innerHTML =
            `<td style="padding:6px 12px 6px 36px;font-weight:600;font-size:12px;color:#374151">
                ${esc(cat.name)}
                <span class="badge ms-1" style="background:#E2E8F0;color:#64748B;font-size:9px">${cat.codes.length}</span>
             </td>${catCols(cat.total, subCatId)}`;
        insertAfter.insertAdjacentElement('afterend', catTr);
        insertAfter = catTr;

        // Code rows
        cat.codes.forEach(code => {
            const codeTr = document.createElement('tr');
            codeTr.className = 'cfg-detail-row';
            codeTr.style.cssText = 'font-size:12px;border-bottom:1px solid #F1F5F9;';
            codeTr.innerHTML =
                `<td style="padding:5px 12px 5px 52px">
                    <code style="font-size:11px;color:#64748B">${esc(code.code)}</code>
                    <span class="ms-2" style="color:#374151">${esc(code.name)}</span>
                 </td>${codeCols(code, subCatId)}`;
            insertAfter.insertAdjacentElement('afterend', codeTr);
            insertAfter = codeTr;
        });
    });

    cfgExpandState[subCatId] = true;
    if (chevron) chevron.style.transform = 'rotate(90deg)';
}

let cfgAllExpanded = false;
let cfgCsVisible   = false;

function cfgToggleCs() {
    cfgCsVisible = !cfgCsVisible;
    document.querySelectorAll('#cfgTable .cfg-cs-col').forEach(el => {
        el.classList.toggle('d-none', !cfgCsVisible);
        el.style.display = ''; // clear any inline display from JS-built rows
    });
    const btn = document.getElementById('cfgCsBtn');
    if (btn) btn.classList.toggle('active', cfgCsVisible);
}

function cfgToggleExpandAll() {
    const btn = document.getElementById('cfgExpandBtn');
    if (cfgAllExpanded) {
        document.querySelectorAll('#cfgTable .cfg-subcat-row').forEach(row => {
            const id = String(row.dataset.subcat || '');
            if (id && cfgExpandState[id]) toggleCfgSubCat(row);
        });
        cfgAllExpanded = false;
        if (btn) btn.innerHTML = '<i class="fas fa-expand-alt me-1"></i>Expand All';
    } else {
        document.querySelectorAll('#cfgTable .cfg-subcat-row').forEach(row => {
            const id = String(row.dataset.subcat || '');
            if (id && !cfgExpandState[id]) toggleCfgSubCat(row);
        });
        cfgAllExpanded = true;
        if (btn) btn.innerHTML = '<i class="fas fa-compress-alt me-1"></i>Collapse All';
    }
}
@endif

// ── Shared export helper ───────────────────────────────────────────────────
function doExport(rows, headers, filename, format, copyBtnId) {
    if (format === 'csv') {
        const csv = [headers, ...rows.map(r => headers.map(h => r[h] ?? ''))]
            .map(c => c.map(v => `"${String(v ?? '').replace(/"/g,'""')}"`).join(','))
            .join('\n');
        dlBlob('﻿' + csv, filename + '.csv', 'text/csv;charset=utf-8;');
    }
    if (format === 'json') {
        dlBlob(JSON.stringify(rows, null, 2), filename + '.json', 'application/json');
    }
    if (format === 'copy') {
        const tsv = [headers, ...rows.map(r => headers.map(h => r[h] ?? ''))].map(c => c.join('\t')).join('\n');
        navigator.clipboard.writeText(tsv).then(() => {
            const btn = document.getElementById(copyBtnId);
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check me-2 text-success"></i>Copied!';
            setTimeout(() => { btn.innerHTML = orig; }, 2000);
        });
    }
}

// ── Configured Statement Export ────────────────────────────────────────────
@if($configuredStatement)
const CFG_LINES        = @json($configuredStatement['lines']);
const CFG_FINAL_BUDGET = {{ $configuredStatement['final_budget'] }};
const CFG_FINAL_ACTUAL = {{ $configuredStatement['final_actual'] }};
const CFG_HAS_CS  = {{ $configuredStatement['has_cs'] ? 'true' : 'false' }};
const CFG_CS_BASES = @json($configuredStatement['cs_bases'] ?? []);
// CFG_CS_BASES: { sub_cat_id: { bud: amount, prev: amount }, ... }

function cfgExport() {
    const stamp   = new Date().toISOString().slice(0,10);
    const allCats = [...PNL_REVENUE, ...PNL_EXPENSE];

    // Each entry: { _type: 'subcat'|'category'|'code'|'subtotal'|'spacer'|'total', label, nums... }
    const rows = [];

    CFG_LINES.forEach(line => {
        if (line.type === 'spacer') { rows.push({ _type: 'spacer' }); return; }

        // CS% for expanded inline category/code rows uses the same base as the sub_category line
        const base    = CFG_CS_BASES[String(line.sub_cat_id ?? '')] ?? null;
        const xcs     = v => base && base.bud  > 0 ? (v / base.bud  * 100).toFixed(1) + '%' : '';
        const xcsp    = v => base && base.prev > 0 ? (v / base.prev * 100).toFixed(1) + '%' : '';

        if (line.type === 'subtotal') {
            rows.push({ _type: 'subtotal',
                label: line.label,
                bud: line.budget, act: line.actual,
                vari: line.variance, pct: line.pct,
                prevBud: line.prev_budget, prevAct: line.prev_actual,
                csBud: line.cs_bud != null ? line.cs_bud + '%' : '',
                csPrev: line.cs_prev_bud != null ? line.cs_prev_bud + '%' : '' });
            return;
        }

        // sub_category line
        rows.push({ _type: 'subcat',
            label: (line.operator === 'subtract' ? '(Less) ' : '') + line.label,
            bud: line.budget, act: line.actual,
            vari: line.variance, pct: line.pct,
            prevBud: line.prev_budget, prevAct: line.prev_actual,
            csBud: line.cs_bud != null ? line.cs_bud + '%' : '',
            csPrev: line.cs_prev_bud != null ? line.cs_prev_bud + '%' : '' });

        const subCatId = String(line.sub_cat_id ?? '');
        if (!subCatId || !cfgExpandState[subCatId]) return;

        allCats.filter(c => String(c.sub_cat_id) === subCatId).forEach(cat => {
            // category — bold in Excel
            rows.push({ _type: 'category',
                label: cat.name,
                bud: cat.total.effective, act: cat.total.actual,
                vari: cat.total.variance, pct: cat.total.pct,
                prevBud: cat.total.prev_budget, prevAct: cat.total.prev_actual,
                csBud: xcs(cat.total.effective), csPrev: xcsp(cat.total.prev_budget) });

            cat.codes.forEach(code => {
                rows.push({ _type: 'code',
                    label: '[' + code.code + '] ' + code.name,
                    bud: code.effective, act: code.actual,
                    vari: code.variance, pct: code.pct,
                    prevBud: code.prev_budget, prevAct: code.prev_actual,
                    csBud: xcs(code.effective), csPrev: xcsp(code.prev_budget) });
            });
        });
    });

    const finalVar = CFG_FINAL_ACTUAL - CFG_FINAL_BUDGET;
    rows.push({ _type: 'spacer' });
    rows.push({ _type: 'total',
        label: 'Net Income / (Loss)',
        bud: CFG_FINAL_BUDGET, act: CFG_FINAL_ACTUAL,
        vari: finalVar,
        pct: CFG_FINAL_BUDGET !== 0 ? (finalVar / Math.abs(CFG_FINAL_BUDGET) * 100).toFixed(1) : 0,
        prevBud: {{ $configuredStatement['final_prev_bud'] ?? 0 }},
        prevAct: {{ $configuredStatement['final_prev_act'] ?? 0 }},
        csBud: '', csPrev: '' });

    // ── Build HTML table (Excel will open it with formatting) ──────────────
    const fmtNum = v => v == null || v === '' ? '' : Number(v).toLocaleString('en-GH', {minimumFractionDigits:0, maximumFractionDigits:0});
    const esc2   = s => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

    const colBg  = { subcat: '#EFF6FF', category: '#F1F5F9', code: '#FFFFFF', subtotal: '#F8FAFC', spacer: '', total: '#1B2A4A' };
    const colFg  = { total: '#FFFFFF' };
    const indent = { subcat: 0, category: 16, code: 32, subtotal: 0, spacer: 0, total: 0 };

    let th = `<th style="background:#1B2A4A;color:#fff;padding:6px 10px;text-align:left">Account</th>
              <th style="background:#1B4ED8;color:#fff;padding:6px 10px;text-align:right">Eff Budget</th>`;
    if (CFG_HAS_CS) th += `<th style="background:#92400E;color:#fff;padding:6px 10px;text-align:right">CS% Bud</th>`;
    th += `<th style="background:#1B4ED8;color:#fff;padding:6px 10px;text-align:right">YTD Actual</th>
              <th style="background:#1B4ED8;color:#fff;padding:6px 10px;text-align:right">Variance</th>
              <th style="background:#1B4ED8;color:#fff;padding:6px 10px;text-align:right">Var %</th>`;
    if (HAS_PREV) th += `<th style="background:#6D28D9;color:#fff;padding:6px 10px;text-align:right">Prev Budget</th>
                          <th style="background:#6D28D9;color:#fff;padding:6px 10px;text-align:right">Prev Actual</th>`;
    if (CFG_HAS_CS && HAS_PREV) th += `<th style="background:#92400E;color:#fff;padding:6px 10px;text-align:right">CS% Prev</th>`;

    const trs = rows.map(r => {
        if (r._type === 'spacer') {
            return `<tr><td colspan="99" style="padding:4px"></td></tr>`;
        }
        const bg      = colBg[r._type] ?? '';
        const fg      = colFg[r._type] ?? '#1B2A4A';
        const pad     = indent[r._type] ?? 0;
        const isBold  = ['subcat','category','subtotal','total'].includes(r._type);
        const isBig   = r._type === 'subtotal' || r._type === 'total';
        const border  = isBig ? 'border-top:2px solid #CBD5E1;' : '';
        const labelCell = `<td style="padding:6px 10px 6px ${10 + pad}px;${border}background:${bg};color:${fg};${isBold ? 'font-weight:700;' : ''}${isBig ? 'font-size:13px;' : 'font-size:12px;'}">
            ${esc2(r.label)}</td>`;
        const numStyle = `padding:6px 10px;text-align:right;${border}background:${bg};color:${fg};font-size:12px;${isBold ? 'font-weight:700;' : ''}font-variant-numeric:tabular-nums`;
        const csStyle2 = `padding:6px 10px;text-align:right;${border}background:#FFFBEB;color:#92400E;font-size:12px;${isBold ? 'font-weight:700;' : ''}`;
        let tds = labelCell
            + `<td style="${numStyle}">${fmtNum(r.bud)}</td>`;
        if (CFG_HAS_CS) tds += `<td style="${csStyle2}">${r.csBud ?? ''}</td>`;
        tds += `<td style="${numStyle}">${fmtNum(r.act)}</td>`
            + `<td style="${numStyle};color:${r.vari >= 0 ? '#059669' : '#DC2626'}">${fmtNum(r.vari)}</td>`
            + `<td style="${numStyle};color:${r.vari >= 0 ? '#059669' : '#DC2626'}">${r.pct ?? ''}</td>`;
        if (HAS_PREV) tds += `<td style="${numStyle}">${fmtNum(r.prevBud)}</td>`
                           + `<td style="${numStyle}">${fmtNum(r.prevAct)}</td>`;
        if (CFG_HAS_CS && HAS_PREV) tds += `<td style="${csStyle2}">${r.csPrev ?? ''}</td>`;
        return `<tr>${tds}</tr>`;
    }).join('\n');

    const html = `<html xmlns="http://www.w3.org/TR/REC-html40">
    <head><meta charset="UTF-8"></head><body>
    <table border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-family:Calibri,Arial,sans-serif">
    <thead><tr>${th}</tr></thead>
    <tbody>${trs}</tbody>
    </table></body></html>`;

    dlBlob(html, 'income-statement-' + stamp + '.xls', 'application/vnd.ms-excel;charset=utf-8');
}
@endif

// ── Income Statement Export ────────────────────────────────────────────────
function pnlExport(format) {
    const stamp = new Date().toISOString().slice(0,10);
    const IS_HEADERS = [
        'Account', 'Eff Budget', 'YTD Actual', 'Variance', 'Var %', 'CS %',
        'Prev Budget', 'Prev Actual', 'Growth %', 'Prev Var %',
        'Q1', 'Q2', 'Q3', 'Q4',
    ];
    const blank = h => Object.fromEntries(IS_HEADERS.map(k => [k, '']));
    const rows  = [];

    ['revenue','expense'].forEach(type => {
        const cats  = type === 'revenue' ? PNL_REVENUE : PNL_EXPENSE;
        const tot   = PNL_TOTALS[type];
        const label = type === 'revenue' ? 'REVENUE' : 'EXPENSES';

        // Section header
        rows.push({...blank(), Account: label});

        cats.forEach(cat => {
            const t  = cat.total;
            const cq = {q1:0,q2:0,q3:0,q4:0};
            cat.codes.forEach(r => { cq.q1+=r.q1; cq.q2+=r.q2; cq.q3+=r.q3; cq.q4+=r.q4; });

            // Category row
            rows.push({
                Account:        cat.name,
                'Eff Budget':   t.effective,  'YTD Actual': t.actual,
                Variance:       t.variance,   'Var %': t.pct,  'CS %': t.common_size,
                'Prev Budget':  HAS_PREV ? t.prev_budget : '',
                'Prev Actual':  HAS_PREV ? t.prev_actual : '',
                'Growth %':     HAS_PREV ? (t.growth_pct ?? '') : '',
                'Prev Var %':   HAS_PREV ? (t.prev_var_pct ?? '') : '',
                Q1: cq.q1, Q2: cq.q2, Q3: cq.q3, Q4: cq.q4,
            });

            // Code rows
            cat.codes.forEach(r => rows.push({
                Account:       `  ${r.code} — ${r.name}`,
                'Eff Budget':  r.effective,  'YTD Actual': r.actual,
                Variance:      r.variance,   'Var %': r.pct,  'CS %': r.common_size,
                'Prev Budget': HAS_PREV ? r.prev_budget : '',
                'Prev Actual': HAS_PREV ? r.prev_actual : '',
                'Growth %':    HAS_PREV ? (r.growth_pct ?? '') : '',
                'Prev Var %':  HAS_PREV ? (r.prev_var_pct ?? '') : '',
                Q1: r.q1, Q2: r.q2, Q3: r.q3, Q4: r.q4,
            }));
        });

        // Section total
        rows.push({
            Account:       `Total ${label}`,
            'Eff Budget':  tot.effective,  'YTD Actual': tot.actual,
            Variance:      tot.variance,   'Var %': tot.pct,  'CS %': '',
            'Prev Budget': HAS_PREV ? tot.prev_budget : '',
            'Prev Actual': HAS_PREV ? tot.prev_actual : '',
            'Growth %':    HAS_PREV ? (tot.growth_pct ?? '') : '',
            'Prev Var %':  HAS_PREV ? (tot.prev_var_pct ?? '') : '',
            Q1: '', Q2: '', Q3: '', Q4: '',
        });
        rows.push({...blank()});  // blank spacer
    });

    // Net income row
    rows.push({
        Account:       'NET INCOME / (LOSS)',
        'Eff Budget':  PNL_NET.budget,  'YTD Actual': PNL_NET.actual,
        Variance:      PNL_NET.variance, 'Var %': PNL_NET.pct,  'CS %': '',
        'Prev Budget': HAS_PREV ? PNL_NET.prev_net_budget : '',
        'Prev Actual': HAS_PREV ? PNL_NET.prev_net_actual : '',
        'Growth %':    HAS_PREV ? (PNL_NET.growth_pct ?? '') : '',
        'Prev Var %':  '', Q1: '', Q2: '', Q3: '', Q4: '',
    });

    doExport(rows, IS_HEADERS, `income-statement-${stamp}`, format, 'pnlCopyBtn');
}

function dlBlob(content, filename, mime) {
    const a = Object.assign(document.createElement('a'), {
        href: URL.createObjectURL(new Blob([content], {type: mime})), download: filename,
    });
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
}

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

let bsAllExpanded = false;
function bsToggleExpandAll() {
    bsAllExpanded = !bsAllExpanded;
    const btn = document.getElementById('bsExpandBtn');
    btn.innerHTML = bsAllExpanded
        ? '<i class="fas fa-compress-alt me-1"></i>Collapse All'
        : '<i class="fas fa-expand-alt me-1"></i>Expand All';
    ['assets','liabilities'].forEach(type => {
        const data = type === 'assets' ? BS_ASSETS : BS_LIABILITIES;
        data.forEach((_, i) => { bsState[type][i] = {expanded: bsAllExpanded}; });
        renderBsSection(type);
    });
}

// Initial render on tab activation
document.querySelector('[data-bs-target="#pane-bs"]')?.addEventListener('shown.bs.tab', () => {
    renderBsSection('assets');
    renderBsSection('liabilities');
});

// ── Cash Flow Export ───────────────────────────────────────────────────────
function cfExport(format) {
    const stamp = new Date().toISOString().slice(0,10);
    const CF_HEADERS = [
        'Period', 'Month', 'Quarter',
        'Budget In', 'Budget Out', 'Net Budget',
        'Actual In', 'Actual Out', 'Net Actual', 'Cumulative',
    ];
    const monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const rows = [];

    // Monthly detail
    Object.entries(CF_MONTHLY).forEach(([m, d]) => {
        const mi = parseInt(m);
        rows.push({
            Period:        'Monthly',
            Month:         monthNames[mi - 1],
            Quarter:       `Q${Math.ceil(mi / 3)}`,
            'Budget In':   Math.round(d.budget_in),
            'Budget Out':  Math.round(d.budget_out),
            'Net Budget':  Math.round(d.net_budget),
            'Actual In':   Math.round(d.actual_in),
            'Actual Out':  Math.round(d.actual_out),
            'Net Actual':  Math.round(d.net_actual),
            Cumulative:    Math.round(d.cum_actual),
        });
    });

    // Blank spacer
    rows.push(Object.fromEntries(CF_HEADERS.map(h => [h, ''])));

    // Quarterly summary
    Object.entries(CF_QUARTERLY).forEach(([q, d]) => {
        rows.push({
            Period:        'Quarterly',
            Month:         `Q${q}`,
            Quarter:       `Q${q}`,
            'Budget In':   Math.round(d.budget_in),
            'Budget Out':  Math.round(d.budget_out),
            'Net Budget':  Math.round(d.net_budget),
            'Actual In':   Math.round(d.actual_in),
            'Actual Out':  Math.round(d.actual_out),
            'Net Actual':  Math.round(d.net_actual),
            Cumulative:    '',
        });
    });

    doExport(rows, CF_HEADERS, `cash-flow-${stamp}`, format, 'cfCopyBtn');
}

// ── Balance Sheet Export ───────────────────────────────────────────────────
function bsExport(format) {
    const stamp = new Date().toISOString().slice(0,10);
    const BS_HEADERS = ['Account', 'Eff Budget', 'YTD Actual', 'Prev Budget', 'Growth %'];
    const blank = () => Object.fromEntries(BS_HEADERS.map(h => [h, '']));
    const rows  = [];

    ['assets', 'liabilities'].forEach(type => {
        const data  = type === 'assets' ? BS_ASSETS : BS_LIABILITIES;
        const tot   = BS_TOTALS[type] ?? {};
        const label = type === 'assets' ? 'ASSETS' : 'LIABILITIES';

        // Section header
        rows.push({...blank(), Account: label});

        data.forEach(cat => {
            const t = cat.total;
            rows.push({
                Account:        cat.name,
                'Eff Budget':   t.effective,
                'YTD Actual':   t.actual,
                'Prev Budget':  HAS_PREV ? (t.prev_budget ?? '') : '',
                'Growth %':     HAS_PREV ? (t.growth_pct ?? '') : '',
            });
            cat.codes.forEach(r => rows.push({
                Account:       `  ${r.code} — ${r.name}`,
                'Eff Budget':  r.effective,
                'YTD Actual':  r.actual,
                'Prev Budget': HAS_PREV ? (r.prev_budget ?? '') : '',
                'Growth %':    HAS_PREV ? (r.growth_pct ?? '') : '',
            }));
        });

        rows.push({
            Account:       `Total ${label}`,
            'Eff Budget':  tot.effective ?? '',
            'YTD Actual':  tot.actual ?? '',
            'Prev Budget': HAS_PREV ? (tot.prev_budget ?? '') : '',
            'Growth %':    HAS_PREV ? (tot.growth_pct ?? '') : '',
        });
        rows.push({...blank()});
    });

    // Net position
    rows.push({
        Account:       'NET ASSETS / (LIABILITIES)',
        'Eff Budget':  BS_NET.budget,
        'YTD Actual':  BS_NET.actual,
        'Prev Budget': HAS_PREV ? BS_NET.prev_budget : '',
        'Growth %':    '',
    });

    doExport(rows, BS_HEADERS, `balance-sheet-${stamp}`, format, 'bsCopyBtn');
}
</script>
@endif
@endsection
