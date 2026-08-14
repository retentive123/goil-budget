@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')

{{-- ════════════════════════════════════════════════════════
     FINANCE / ADMIN VIEW
     ════════════════════════════════════════════════════════ --}}
@if(isset($periodStats))

@php
    $total   = max(1, $periodStats['total']);
    $ap      = round(($periodStats['approved']  / $total) * 100);
    $su      = round(($periodStats['submitted'] / $total) * 100);
    $re      = round(($periodStats['rejected']  / $total) * 100);
    $dr      = round(($periodStats['draft']     / $total) * 100);
    $ns      = round(($periodStats['not_started'] / $total) * 100);
    $utilPct = $totalApprovedValue > 0
               ? round(($totalActualValue / $totalApprovedValue) * 100, 1)
               : 0;
    $hour    = (int) now()->format('H');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
@endphp

{{-- ── Hero banner (light GOIL gradient) ───────────────────────────── --}}
<div style="background:linear-gradient(135deg,#EDF1F8 0%,#F8F5EC 100%);
            border-radius:16px;border:1px solid #D4CCB8;
            border-top:4px solid #C9A84C;padding:24px 28px;margin-bottom:20px;
            box-shadow:0 2px 12px rgba(27,42,74,.1)">

    <div class="row align-items-start gy-3">

        {{-- Left: greeting + period context --}}
        <div class="col-md-7">
            <div style="font-size:10px;text-transform:uppercase;letter-spacing:1.5px;
                        color:#1B2A4A;font-weight:700;margin-bottom:4px">
                GOIL Budget Management System
            </div>
            <div style="font-size:22px;font-weight:700;color:var(--navy);line-height:1.2">
                {{ $greeting }}, {{ explode(' ', Auth::user()->name)[0] }}
            </div>
            <div style="color:var(--slate);font-size:13px;margin-top:4px">
                {{ now()->format('l, d F Y') }}
            </div>

            {{-- Show specific period info only when a period filter is active --}}
            @if(!$isAllPeriods && $currentPeriod)
            <div class="d-flex align-items-center gap-2 mt-3 flex-wrap">
                <i class="bi bi-calendar3" style="color:var(--gold)"></i>
                <span style="color:var(--navy);font-size:13px;font-weight:600">
                    {{ $currentPeriod->name }}
                </span>
                <span style="color:var(--slate);font-size:12px">
                    {{ $currentPeriod->start_date->format('d M') }} –
                    {{ $currentPeriod->end_date->format('d M Y') }}
                </span>
                <span style="padding:2px 10px;border-radius:20px;font-size:10px;font-weight:700;
                             background:{{ match($currentPeriod->status) {
                                'open'   => '#D1FAE5', 'closed' => '#FEF3C7',
                                default  => '#F1F5F9' } }};
                             color:{{ match($currentPeriod->status) {
                                'open'   => '#065F46', 'closed' => '#92400E',
                                default  => '#475569' } }}">
                    {{ ucfirst($currentPeriod->status) }}
                </span>
            </div>
            @elseif($isAllPeriods)
            <div class="d-flex align-items-center gap-2 mt-3">
                <i class="bi bi-layers" style="color:var(--gold)"></i>
                <span style="color:var(--slate);font-size:13px">
                    Showing aggregated data across all budget periods
                </span>
            </div>
            @endif
        </div>

        {{-- Right: snapshot stat tiles --}}
        <div class="col-md-5">
            <div class="row g-2">
                <div class="col-6">
                    <div style="background:#F0FDF4;border-radius:10px;padding:12px 14px;
                                border:1px solid #BBF7D0">
                        <div style="font-size:9px;text-transform:uppercase;letter-spacing:.8px;
                                    color:#15803D;font-weight:700;margin-bottom:4px">Approved</div>
                        <div style="font-size:16px;font-weight:700;color:#15803D;line-height:1">
                            GHS {{ number_format($totalApprovedValue, 0) }}
                        </div>
                        <div style="font-size:10px;color:#15803D;opacity:.7;margin-top:3px">
                            @php
                                $apDepts    = $periodStats['approved_depts']    ?? 0;
                                $apStations = $periodStats['approved_stations'] ?? 0;
                                $apParts = [];
                                if ($apDepts)    $apParts[] = $apDepts    . ' dept' . ($apDepts    > 1 ? 's' : '');
                                if ($apStations) $apParts[] = $apStations . ' station' . ($apStations > 1 ? 's' : '');
                                echo $apParts ? implode(', ', $apParts) : '0 depts';
                            @endphp
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div style="background:#FFFBEB;border-radius:10px;padding:12px 14px;
                                border:1px solid #FDE68A">
                        <div style="font-size:9px;text-transform:uppercase;letter-spacing:.8px;
                                    color:#92400E;font-weight:700;margin-bottom:4px">Utilisation</div>
                        <div style="font-size:16px;font-weight:700;line-height:1;
                                    color:{{ $utilPct > 90 ? '#991B1B' : ($utilPct > 70 ? '#92400E' : '#15803D') }}">
                            {{ $utilPct }}%
                        </div>
                        <div style="font-size:10px;color:#92400E;opacity:.7;margin-top:3px">
                            of approved spend
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div style="background:{{ $pendingApprovals->count() > 0 ? '#FEF2F2' : '#F0FDF4' }};
                                border-radius:10px;padding:12px 14px;
                                border:1px solid {{ $pendingApprovals->count() > 0 ? '#FECACA' : '#BBF7D0' }}">
                        <div style="font-size:9px;text-transform:uppercase;letter-spacing:.8px;
                                    color:{{ $pendingApprovals->count() > 0 ? '#991B1B' : '#15803D' }};
                                    font-weight:700;margin-bottom:4px">Pending Action</div>
                        <div style="font-size:16px;font-weight:700;line-height:1;
                                    color:{{ $pendingApprovals->count() > 0 ? '#991B1B' : '#15803D' }}">
                            {{ $pendingApprovals->count() }}
                        </div>
                        <div style="font-size:10px;opacity:.7;margin-top:3px;
                                    color:{{ $pendingApprovals->count() > 0 ? '#991B1B' : '#15803D' }}">
                            awaiting approval
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div style="background:#EFF6FF;border-radius:10px;padding:12px 14px;
                                border:1px solid #BFDBFE">
                        <div style="font-size:9px;text-transform:uppercase;letter-spacing:.8px;
                                    color:#1E40AF;font-weight:700;margin-bottom:4px">In Review</div>
                        <div style="font-size:16px;font-weight:700;color:#1E40AF;line-height:1">
                            {{ $periodStats['submitted'] }}
                        </div>
                        <div style="font-size:10px;color:#1E40AF;opacity:.7;margin-top:3px">
                            submitted dept(s)
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Period filter bar — on white background, selects render correctly --}}
    <div class="d-flex align-items-center gap-2 mt-4 pt-3"
         style="border-top:1px solid #E8E0CB">
        <i class="bi bi-funnel" style="color:var(--slate);font-size:12px"></i>
        <span style="font-size:11px;color:var(--slate);white-space:nowrap">Filter by:</span>
        <form method="GET" action="{{ route('dashboard') }}"
              class="d-flex gap-2 align-items-center flex-wrap">
            <select name="period_id"
                    class="form-select form-select-sm"
                    style="min-width:160px;font-size:12px"
                    onchange="this.form.querySelector('[name=year]').value='';this.form.submit()">
                <option value="">All Periods</option>
                @foreach($periods as $p)
                <option value="{{ $p->id }}" {{ $selectedPeriodId == $p->id ? 'selected' : '' }}>
                    {{ $p->name }}{{ $p->status === 'open' ? ' ✦' : '' }}
                </option>
                @endforeach
            </select>
            <span style="color:var(--slate);font-size:12px">or year</span>
            <select name="year"
                    class="form-select form-select-sm"
                    style="min-width:90px;font-size:12px"
                    onchange="this.form.querySelector('[name=period_id]').value='';this.form.submit()">
                <option value="">All Years</option>
                @foreach($years as $yr)
                <option value="{{ $yr }}" {{ $selectedYear == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                @endforeach
            </select>
            @if($selectedPeriodId || $selectedYear)
            <a href="{{ route('dashboard') }}"
               style="font-size:11px;color:var(--slate);text-decoration:none;
                      display:flex;align-items:center;gap:4px">
                <i class="bi bi-x-circle"></i>Clear
            </a>
            @endif
        </form>
    </div>
</div>

{{-- ── Budget Health ─────────────────────────────────────────────── --}}
<div class="health-bar-wrap mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="health-bar-title mb-0">
            <i class="bi bi-activity me-2"></i>Budget Submission Health
            <span style="color:rgba(255,255,255,.5);font-weight:400;font-size:11px;margin-left:6px">
                — {{ $isAllPeriods ? 'All Periods' : ($currentPeriod?->name ?? '—') }}
            </span>
        </div>
        <div style="font-size:13px;color:var(--gold);font-weight:700">
            {{ $ap }}% approved &nbsp;·&nbsp; {{ $utilPct }}% utilised
        </div>
    </div>
    <div class="health-segments" style="height:16px">
        @if($ap) <div class="health-segment" style="width:{{ $ap }}%;background:#10B981"></div> @endif
        @if($su) <div class="health-segment" style="width:{{ $su }}%;background:#C9A84C"></div> @endif
        @if($re) <div class="health-segment" style="width:{{ $re }}%;background:#F43F5E"></div> @endif
        @if($dr) <div class="health-segment" style="width:{{ $dr }}%;background:#475569"></div> @endif
        @if($ns) <div class="health-segment" style="width:{{ $ns }}%;background:rgba(255,255,255,.12)"></div> @endif
    </div>
    <div class="health-legend mt-2">
        <div class="health-legend-item"><div class="health-legend-dot" style="background:#10B981"></div>Approved ({{ $periodStats['approved'] }})</div>
        <div class="health-legend-item"><div class="health-legend-dot" style="background:#C9A84C"></div>In Review ({{ $periodStats['submitted'] }})</div>
        <div class="health-legend-item"><div class="health-legend-dot" style="background:#F43F5E"></div>Rejected ({{ $periodStats['rejected'] }})</div>
        <div class="health-legend-item"><div class="health-legend-dot" style="background:#475569"></div>Draft ({{ $periodStats['draft'] }})</div>
        <div class="health-legend-item"><div class="health-legend-dot" style="background:rgba(255,255,255,.25)"></div>Not Started ({{ $periodStats['not_started'] }})</div>
    </div>
</div>

{{-- ── Section: Financial Overview ──────────────────────────────── --}}
<div class="d-flex align-items-center gap-3 mb-3">
    <span style="font-size:10px;text-transform:uppercase;letter-spacing:1px;
                 font-weight:700;color:var(--slate);white-space:nowrap">
        <i class="bi bi-bar-chart-fill me-1" style="color:var(--navy)"></i>Financial Overview
    </span>
    <div style="flex:1;height:1px;background:var(--border)"></div>
</div>

{{-- KPI cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card" style="border-top:3px solid #10B981">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Approved Budget</div>
                    <div class="stat-value" style="color:#10B981;font-size:22px">
                        GHS {{ number_format($totalApprovedValue, 0) }}
                    </div>
                    <div class="stat-sub">
                        @php
                            $kpiDepts    = $periodStats['approved_depts']    ?? 0;
                            $kpiStations = $periodStats['approved_stations'] ?? 0;
                            $kpiParts = [];
                            if ($kpiDepts)    $kpiParts[] = $kpiDepts    . ' dept' . ($kpiDepts    > 1 ? 's' : '');
                            if ($kpiStations) $kpiParts[] = $kpiStations . ' station' . ($kpiStations > 1 ? 's' : '');
                            echo ($kpiParts ? implode(', ', $kpiParts) : '0 depts') . ' approved';
                        @endphp
                    </div>
                </div>
                <div style="width:42px;height:42px;border-radius:10px;background:#D1FAE5;
                            display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="bi bi-check-circle-fill" style="color:#10B981;font-size:18px"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="border-top:3px solid #C9A84C">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Actual Spend (YTD)</div>
                    <div class="stat-value" style="color:var(--navy);font-size:22px">
                        GHS {{ number_format($totalActualValue, 0) }}
                    </div>
                    <div class="stat-sub">{{ $utilPct }}% of approved budget</div>
                </div>
                <div style="width:42px;height:42px;border-radius:10px;background:#FEF3C7;
                            display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="bi bi-cash-coin" style="color:#D97706;font-size:18px"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="border-top:3px solid #F43F5E">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Awaiting My Action</div>
                    <div class="stat-value" style="color:{{ $pendingApprovals->count() > 0 ? '#F43F5E' : 'var(--navy)' }};font-size:32px">
                        {{ $pendingApprovals->count() }}
                    </div>
                    <div class="stat-sub">budget(s) pending decision</div>
                </div>
                <div style="width:42px;height:42px;border-radius:10px;background:#FEE2E2;
                            display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="bi bi-hourglass-split" style="color:#F43F5E;font-size:18px"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="border-top:3px solid #6366F1">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-label">Pending Virements</div>
                    <div class="stat-value" style="color:{{ $pendingVirements > 0 ? '#6366F1' : 'var(--navy)' }};font-size:32px">
                        {{ $pendingVirements }}
                    </div>
                    <div class="stat-sub">
                        <a href="{{ route('virements.pending') }}"
                           style="color:#6366F1;text-decoration:none;font-weight:600">
                            Review requests <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div style="width:42px;height:42px;border-radius:10px;background:#EEF2FF;
                            display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="bi bi-arrow-left-right" style="color:#6366F1;font-size:18px"></i>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Section breakdown (P&L snapshot) --}}
@if(!empty($sectionTotals))
@php
    $stRev = $sectionTotals['revenue'] ?? 0;
    $stExp = $sectionTotals['expense'] ?? 0;
    $stNet = $stRev - $stExp;
    $stCx  = $sectionTotals['capex']   ?? 0;
    $stBl  = $sectionTotals['balance'] ?? 0;
@endphp
<div class="row g-3 mb-4">
    <div class="col">
        <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:12px;padding:16px 20px">
            <div style="font-size:10px;text-transform:uppercase;letter-spacing:.8px;
                        color:#15803D;font-weight:700;margin-bottom:6px">
                <i class="bi bi-arrow-up-circle-fill me-1"></i>Total Revenue
            </div>
            <div style="font-size:20px;font-weight:700;color:#15803D">
                GHS {{ number_format($stRev, 0) }}
            </div>
        </div>
    </div>
    <div class="col">
        <div style="background:#FFF7F0;border:1px solid #FED7AA;border-radius:12px;padding:16px 20px">
            <div style="font-size:10px;text-transform:uppercase;letter-spacing:.8px;
                        color:#9A3412;font-weight:700;margin-bottom:6px">
                <i class="bi bi-arrow-down-circle-fill me-1"></i>Total Expenses
            </div>
            <div style="font-size:20px;font-weight:700;color:#9A3412">
                GHS {{ number_format($stExp, 0) }}
            </div>
        </div>
    </div>
    <div class="col">
        <div style="background:{{ $stNet >= 0 ? '#F0FDF4' : '#FFF1F2' }};
                    border:1px solid {{ $stNet >= 0 ? '#BBF7D0' : '#FECDD3' }};
                    border-radius:12px;padding:16px 20px">
            <div style="font-size:10px;text-transform:uppercase;letter-spacing:.8px;
                        color:{{ $stNet >= 0 ? '#15803D' : '#9F1239' }};
                        font-weight:700;margin-bottom:6px">
                <i class="bi bi-graph-up-arrow me-1"></i>Net Income
            </div>
            <div style="font-size:20px;font-weight:700;
                        color:{{ $stNet >= 0 ? '#15803D' : '#9F1239' }}">
                {{ $stNet < 0 ? '–' : '' }}GHS {{ number_format(abs($stNet), 0) }}
            </div>
        </div>
    </div>
    @if($stCx > 0)
    <div class="col">
        <div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:12px;padding:16px 20px">
            <div style="font-size:10px;text-transform:uppercase;letter-spacing:.8px;
                        color:#7C2D12;font-weight:700;margin-bottom:6px">
                <i class="bi bi-buildings-fill me-1"></i>Capital Expenditure
            </div>
            <div style="font-size:20px;font-weight:700;color:#7C2D12">
                GHS {{ number_format($stCx, 0) }}
            </div>
        </div>
    </div>
    @endif
    @if($stBl > 0)
    <div class="col">
        <div style="background:#F5F3FF;border:1px solid #DDD6FE;border-radius:12px;padding:16px 20px">
            <div style="font-size:10px;text-transform:uppercase;letter-spacing:.8px;
                        color:#4C1D95;font-weight:700;margin-bottom:6px">
                <i class="bi bi-bank me-1"></i>Assets &amp; Liabilities
            </div>
            <div style="font-size:20px;font-weight:700;color:#4C1D95">
                GHS {{ number_format($stBl, 0) }}
            </div>
        </div>
    </div>
    @endif
</div>
@endif

{{-- ── Section: Analytics ────────────────────────────────────────── --}}
<div class="d-flex align-items-center gap-3 mb-3 mt-2">
    <span style="font-size:10px;text-transform:uppercase;letter-spacing:1px;
                 font-weight:700;color:var(--slate);white-space:nowrap">
        <i class="bi bi-graph-up me-1" style="color:var(--navy)"></i>Analytics
    </span>
    <div style="flex:1;height:1px;background:var(--border)"></div>
</div>

{{-- Charts row 1 --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="chart-card h-100">
            <div class="chart-title">Submission Status</div>
            <canvas id="statusDonut" height="200"></canvas>
        </div>
    </div>
    <div class="col-md-5">
        <div class="chart-card h-100">
            <div class="chart-title">Monthly Actual Spend (GHS)</div>
            <canvas id="monthlyTrend" height="200"></canvas>
        </div>
    </div>
    <div class="col-md-4">
        <div class="chart-card h-100">
            <div class="chart-title">Budget Utilisation</div>
            <canvas id="utilisationDonut" height="200"></canvas>
            <div class="text-center mt-2" style="font-size:12px;color:var(--slate)">
                GHS {{ number_format($totalActualValue, 0) }} of
                GHS {{ number_format($totalApprovedValue, 0) }} used
            </div>
        </div>
    </div>
</div>

{{-- Charts row 2 --}}
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="chart-card h-100">
            <div class="chart-title">Budget vs Actual by Department</div>
            <canvas id="deptBudgetBar" height="200"></canvas>
        </div>
    </div>
    <div class="col-md-4">
        <div class="chart-card h-100">
            <div class="chart-title">Budget by Section</div>
            <canvas id="sectionDonut" height="200"></canvas>
            @if(!empty($sectionTotals))
            @php $sdNet = ($sectionTotals['revenue'] ?? 0) - ($sectionTotals['expense'] ?? 0); @endphp
            <div class="mt-2" style="font-size:11px;color:var(--slate);line-height:1.8">
                <div>
                    <span style="display:inline-block;width:10px;height:10px;
                                 background:#1B2A4A;border-radius:2px;margin-right:5px"></span>
                    IS Net:
                    <strong style="color:{{ $sdNet >= 0 ? '#10B981' : '#F43F5E' }}">
                        {{ $sdNet < 0 ? '–' : '' }}GHS {{ number_format(abs($sdNet), 0) }}
                    </strong>
                </div>
                @if(($sectionTotals['capex'] ?? 0) > 0)
                <div>
                    <span style="display:inline-block;width:10px;height:10px;
                                 background:#78350F;border-radius:2px;margin-right:5px"></span>
                    CapEx: <strong>GHS {{ number_format($sectionTotals['capex'], 0) }}</strong>
                </div>
                @endif
                @if(($sectionTotals['balance'] ?? 0) > 0)
                <div>
                    <span style="display:inline-block;width:10px;height:10px;
                                 background:#4C1D95;border-radius:2px;margin-right:5px"></span>
                    Balance: <strong>GHS {{ number_format($sectionTotals['balance'], 0) }}</strong>
                </div>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>

{{-- YoY trend --}}
@if(count($yoySummary) > 1)
<div class="chart-card mb-4">
    <div class="chart-title">
        <i class="bi bi-calendar-range me-1" style="color:var(--navy)"></i>
        Year-over-Year: Budget vs Actual
    </div>
    <canvas id="yoyLine" height="100"></canvas>
</div>
@endif

{{-- ── Section: Submissions & Approvals ─────────────────────────── --}}
<div class="d-flex align-items-center gap-3 mb-3 mt-2">
    <span style="font-size:10px;text-transform:uppercase;letter-spacing:1px;
                 font-weight:700;color:var(--slate);white-space:nowrap">
        <i class="bi bi-people-fill me-1" style="color:var(--navy)"></i>Submissions &amp; Approvals
    </span>
    <div style="flex:1;height:1px;background:var(--border)"></div>
</div>

<div class="row g-3 mb-4">
    {{-- Dept status grid --}}
    <div class="col-md-7">
        <div class="chart-card h-100">
            <div class="chart-title">Department Status</div>
            <div style="max-height:340px;overflow-y:auto">
                @foreach($deptStatuses as $dept)
                <div class="dept-row">
                    <div class="dept-code">{{ $dept['code'] }}</div>
                    <div class="flex-grow-1">
                        <div class="dept-name">{{ $dept['name'] }}</div>
                        <div class="dept-meta">
                            @if($dept['total'] > 0)
                                GHS {{ number_format($dept['total'], 0) }}
                                &middot; v{{ $dept['version'] }}
                            @else
                                No budget submitted
                            @endif
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="status-pill pill-{{ $dept['status'] }}">
                            {{ ucfirst(str_replace('_', ' ', $dept['status'])) }}
                        </span>
                        @if($dept['version_id'])
                        <a href="{{ route('approvals.show', $dept['version_id']) }}"
                           style="font-size:11px;color:var(--navy)">
                            View <i class="bi bi-arrow-right"></i>
                        </a>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Pending approvals --}}
    <div class="col-md-5">
        <div class="chart-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="chart-title mb-0">
                    <i class="bi bi-clock-history me-1" style="color:#F43F5E"></i>
                    Awaiting Your Action
                </div>
                <a href="{{ route('approvals.index') }}"
                   style="font-size:12px;color:var(--navy);text-decoration:none">
                    View all <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            @forelse($pendingApprovals as $v)
            <div style="padding:10px 0;border-bottom:1px solid var(--border)">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div style="font-size:13px;font-weight:600;color:var(--navy)">
                            {{ $v->department->name }}
                        </div>
                        <div style="font-size:11px;color:var(--slate)">
                            v{{ $v->version_number }}
                            &middot; {{ $v->submitted_at?->diffForHumans() ?? '—' }}
                        </div>
                    </div>
                    <a href="{{ route('approvals.show', $v) }}"
                       class="btn btn-sm"
                       style="background:var(--navy);color:#fff;font-size:11px;
                              border-radius:6px;padding:4px 14px">
                        <i class="bi bi-eye me-1"></i>Review
                    </a>
                </div>
            </div>
            @empty
            <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
                <i class="bi bi-check-circle" style="font-size:32px;color:#D1FAE5;margin-bottom:8px"></i>
                <div class="small">All clear — no budgets awaiting approval.</div>
            </div>
            @endforelse
        </div>
    </div>
</div>

{{-- ── Section: Recent Activity ──────────────────────────────────── --}}
@if($recentNotifs->count())
<div class="d-flex align-items-center gap-3 mb-3 mt-2">
    <span style="font-size:10px;text-transform:uppercase;letter-spacing:1px;
                 font-weight:700;color:var(--slate);white-space:nowrap">
        <i class="bi bi-bell-fill me-1" style="color:var(--navy)"></i>Recent Activity
    </span>
    <div style="flex:1;height:1px;background:var(--border)"></div>
    <a href="{{ route('notifications.index') }}"
       style="font-size:11px;color:var(--navy);text-decoration:none;white-space:nowrap">
        View all <i class="bi bi-arrow-right"></i>
    </a>
</div>

<div class="chart-card mb-4">
    @foreach($recentNotifs as $notif)
    @php
        $notifIcon = match(true) {
            str_contains($notif->type, 'approved') => ['icon' => 'bi-check-circle-fill', 'color' => '#10B981', 'bg' => '#D1FAE5'],
            str_contains($notif->type, 'rejected') => ['icon' => 'bi-x-circle-fill',     'color' => '#F43F5E', 'bg' => '#FEE2E2'],
            str_contains($notif->type, 'virement') => ['icon' => 'bi-arrow-left-right',   'color' => '#6366F1', 'bg' => '#EEF2FF'],
            default                                 => ['icon' => 'bi-file-earmark-text',  'color' => '#64748B', 'bg' => '#F1F5F9'],
        };
    @endphp
    <div class="d-flex gap-3 align-items-start p-2 rounded mb-1
         {{ $notif->isRead() ? '' : '' }}"
         style="background:{{ $notif->isRead() ? 'transparent' : '#FAFBFF' }}">
        <div style="width:32px;height:32px;border-radius:8px;flex-shrink:0;
                    background:{{ $notifIcon['bg'] }};
                    display:flex;align-items:center;justify-content:center">
            <i class="bi {{ $notifIcon['icon'] }}"
               style="color:{{ $notifIcon['color'] }};font-size:14px"></i>
        </div>
        <div class="flex-grow-1" style="font-size:12px">
            <div class="fw-semibold {{ $notif->isRead() ? 'text-muted' : '' }}"
                 style="color:{{ $notif->isRead() ? '' : 'var(--navy)' }}">
                {{ $notif->subject }}
            </div>
            <div class="text-muted" style="font-size:11px">
                {{ $notif->created_at->diffForHumans() }}
            </div>
        </div>
        @if(!$notif->isRead())
        <div style="width:6px;height:6px;border-radius:50%;background:#6366F1;
                    flex-shrink:0;margin-top:5px"></div>
        @endif
    </div>
    @endforeach
</div>
@endif

{{-- ── Chart JS (unchanged) ──────────────────────────────────────── --}}
<script>
const NAVY    = '#1B2A4A';
const GOLD    = '#C9A84C';
const EMERALD = '#10B981';
const ROSE    = '#F43F5E';
const SLATE   = '#64748B';
const PALETTE = ['#1B2A4A','#C9A84C','#10B981','#6366F1','#F59E0B',
                 '#EC4899','#14B8A6','#8B5CF6','#F97316','#06B6D4'];

const fmt    = v => v>=1000000?(v/1000000).toFixed(1)+'M':v>=1000?(v/1000).toFixed(0)+'K':v;
const yScale = { beginAtZero:true, grid:{color:'#F1F5F9'},
                 ticks:{font:{size:10},callback:fmt} };

new Chart(document.getElementById('statusDonut'),{
    type:'doughnut',
    data:{
        labels:['Approved','In Review','Rejected','Draft','Not Started'],
        datasets:[{
            data:[{{ $periodStats['approved'] }},{{ $periodStats['submitted'] }},
                  {{ $periodStats['rejected'] }},{{ $periodStats['draft'] }},
                  {{ $periodStats['not_started'] }}],
            backgroundColor:['#10B981','#C9A84C','#F43F5E','#64748B','#E2E8F0'],
            borderWidth:0,hoverOffset:6,
        }]
    },
    options:{cutout:'65%',plugins:{legend:{position:'bottom',labels:{font:{size:10},padding:8,boxWidth:10}}}}
});

@php
    $mLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    $mData   = array_map(fn($m) => $monthlyActualsTrend[$m] ?? 0, range(1,12));
@endphp
new Chart(document.getElementById('monthlyTrend'),{
    type:'bar',
    data:{
        labels:{!! json_encode($mLabels) !!},
        datasets:[{
            label:'Actual Spend',
            data:{!! json_encode($mData) !!},
            backgroundColor:{!! json_encode(array_map(fn($v)=>$v>0?'#1B2A4A':'#E2E8F0',$mData)) !!},
            borderRadius:5,borderSkipped:false,
        }]
    },
    options:{
        responsive:true,
        plugins:{legend:{display:false}},
        scales:{y:yScale,x:{grid:{display:false},ticks:{font:{size:10}}}}
    }
});

new Chart(document.getElementById('utilisationDonut'),{
    type:'doughnut',
    data:{
        labels:['Actual Spend','Remaining'],
        datasets:[{
            data:[{{ $totalActualValue }},{{ max(0,$totalApprovedValue-$totalActualValue) }}],
            backgroundColor:['#10B981','#E2E8F0'],
            borderWidth:0,
        }]
    },
    options:{
        cutout:'72%',
        plugins:{
            legend:{position:'bottom',labels:{font:{size:10},padding:8,boxWidth:10}},
            tooltip:{callbacks:{label:ctx=>'GHS '+ctx.parsed.toLocaleString('en-GH',{minimumFractionDigits:0})}}
        }
    }
});

@if(count($deptBudgets))
new Chart(document.getElementById('deptBudgetBar'),{
    type:'bar',
    data:{
        labels:{!! json_encode(array_column($deptBudgets,'name')) !!},
        datasets:[
            {label:'Budget',data:{!! json_encode(array_column($deptBudgets,'total')) !!},
             backgroundColor:'#1B2A4A',borderRadius:4,borderSkipped:false},
            {label:'Actual',data:{!! json_encode(array_column($deptBudgets,'actual')) !!},
             backgroundColor:'#10B981',borderRadius:4,borderSkipped:false},
        ]
    },
    options:{
        responsive:true,
        plugins:{legend:{position:'top',labels:{font:{size:11},boxWidth:12}}},
        scales:{y:yScale,x:{grid:{display:false},ticks:{font:{size:10}}}}
    }
});
@endif

@if(!empty($sectionTotals))
@php
    $sdLabels = ['Income Statement'];
    $sdData   = [($sectionTotals['revenue'] ?? 0) + ($sectionTotals['expense'] ?? 0)];
    $sdColors = ['#1B2A4A'];
    if (($sectionTotals['capex'] ?? 0) > 0)   { $sdLabels[] = 'Capital Expenditure'; $sdData[] = $sectionTotals['capex'];   $sdColors[] = '#78350F'; }
    if (($sectionTotals['balance'] ?? 0) > 0) { $sdLabels[] = 'Assets & Liabilities'; $sdData[] = $sectionTotals['balance']; $sdColors[] = '#4C1D95'; }
@endphp
new Chart(document.getElementById('sectionDonut'),{
    type:'doughnut',
    data:{
        labels:{!! json_encode($sdLabels) !!},
        datasets:[{
            data:{!! json_encode($sdData) !!},
            backgroundColor:{!! json_encode($sdColors) !!},
            borderWidth:3,borderColor:'#fff',hoverOffset:6,
        }]
    },
    options:{
        cutout:'65%',
        plugins:{
            legend:{position:'bottom',labels:{font:{size:10},padding:8,boxWidth:10}},
            tooltip:{callbacks:{label:ctx=>'GHS '+ctx.parsed.toLocaleString('en-GH',{minimumFractionDigits:0})}}
        }
    }
});
@endif

@if(count($yoySummary) > 1)
new Chart(document.getElementById('yoyLine'),{
    type:'line',
    data:{
        labels:{!! json_encode($yoySummary->pluck('name')->toArray()) !!},
        datasets:[
            {label:'Budget',
             data:{!! json_encode($yoySummary->pluck('budget')->toArray()) !!},
             borderColor:'#1B2A4A',backgroundColor:'rgba(27,42,74,.08)',
             borderWidth:2.5,pointBackgroundColor:'#C9A84C',pointRadius:5,fill:true,tension:.4},
            {label:'Actual',
             data:{!! json_encode($yoySummary->pluck('actual')->toArray()) !!},
             borderColor:'#10B981',backgroundColor:'rgba(16,185,129,.08)',
             borderWidth:2.5,pointBackgroundColor:'#10B981',pointRadius:5,fill:true,tension:.4},
        ]
    },
    options:{
        responsive:true,
        plugins:{legend:{position:'top',labels:{font:{size:11},boxWidth:12}}},
        scales:{y:yScale,x:{grid:{display:false},ticks:{font:{size:11}}}}
    }
});
@endif
</script>
@endif


{{-- ════════════════════════════════════════════════════════
     DEPARTMENT USER VIEW
     ════════════════════════════════════════════════════════ --}}
@if(isset($myBudget) || isset($versionHistory))

{{-- Hero --}}
<div style="background:linear-gradient(135deg,#EDF1F8 0%,#F8F5EC 100%);
            border-radius:16px;border:1px solid #D4CCB8;
            border-top:4px solid #C9A84C;padding:24px 28px;margin-bottom:24px;
            box-shadow:0 2px 12px rgba(27,42,74,.1)">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <div style="font-size:10px;text-transform:uppercase;letter-spacing:1.5px;
                        color:#1B2A4A;font-weight:700;margin-bottom:4px">
                GOIL Budget Management
            </div>
            <div style="font-size:20px;font-weight:700;color:var(--navy)">
                {{ Auth::user()->department?->name }}
            </div>
            <div style="color:var(--slate);font-size:13px;margin-top:4px">
                <i class="bi bi-calendar3 me-1" style="color:var(--gold)"></i>
                {{ $currentPeriod?->name ?? 'No active period' }}
                &nbsp;·&nbsp; {{ now()->format('d F Y') }}
            </div>
        </div>
        @if(!$myBudget || $myBudget->status === 'rejected')
            @if($currentPeriod && \App\Models\BudgetVersion::canCreateNew($currentPeriod->id, Auth::user()->department_id))
            <form method="POST" action="{{ route('budget.start') }}">
                @csrf
                <button type="submit"
                        style="background:var(--gold);color:var(--navy);border:none;
                               border-radius:8px;padding:10px 20px;font-weight:700;font-size:13px">
                    <i class="bi bi-plus-circle me-1"></i>
                    {{ $myBudget ? 'Start Revision' : 'Start Budget' }}
                </button>
            </form>
            @endif
        @endif
    </div>
</div>

{{-- Budget status card --}}
@if($myBudget)
<div class="chart-card mb-4"
     style="border-left:4px solid {{
        match($myBudget->status) {
            'approved'     => '#10B981',
            'rejected'     => '#F43F5E',
            'submitted'    => '#3B82F6',
            'under_review' => '#F59E0B',
            default        => '#64748B'
        }
     }}">
    <div class="row align-items-center">
        <div class="col">
            <div class="d-flex align-items-center gap-2">
                <span style="font-size:22px;font-weight:700;color:var(--navy)">
                    Version {{ $myBudget->version_number }}
                </span>
                <span class="status-pill pill-{{ $myBudget->status }}">
                    {{ ucfirst(str_replace('_', ' ', $myBudget->status)) }}
                </span>
            </div>
            @if($myBudget->submission_notes)
            <div style="font-size:12px;color:var(--slate);margin-top:4px">
                "{{ $myBudget->submission_notes }}"
            </div>
            @endif
        </div>
        <div class="col-auto d-flex gap-2">
            @if($myBudget->isEditable())
            <a href="{{ route('budget.show-pnl', $myBudget) }}"
               class="btn btn-sm"
               style="background:var(--navy);color:#fff;border-radius:8px">
                <i class="bi bi-pencil me-1"></i>Continue Editing
            </a>
            <a href="{{ route('budget.confirm', $myBudget) }}"
               class="btn btn-sm"
               style="background:#10B981;color:#fff;border-radius:8px">
                <i class="bi bi-send me-1"></i>Submit
            </a>
            @else
            <a href="{{ route('budget.show-pnl', $myBudget) }}"
               class="btn btn-sm btn-outline-secondary"
               style="border-radius:8px">
                <i class="bi bi-eye me-1"></i>View Budget
            </a>
            @endif
        </div>
    </div>
</div>

{{-- Quarterly KPIs --}}
@if(isset($quarterTotals))
<div class="row g-3 mb-4">
    @foreach(['q1'=>'Q1 (Jan–Mar)','q2'=>'Q2 (Apr–Jun)','q3'=>'Q3 (Jul–Sep)','q4'=>'Q4 (Oct–Dec)'] as $k=>$label)
    <div class="col-md-3">
        <div class="quarter-pill">
            <div class="q-label">{{ $label }}</div>
            <div class="q-value">GHS {{ number_format($quarterTotals->$k ?? 0, 0) }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- Section breakdown --}}
@if(!empty($mySectionTotals))
<div class="row g-2 mb-4">
    <div class="col">
        <div class="quarter-pill"><div class="q-label">Revenue</div>
            <div class="q-value" style="font-size:14px;color:#15803D">GHS {{ number_format($mySectionTotals['revenue'],0) }}</div>
        </div>
    </div>
    <div class="col">
        <div class="quarter-pill"><div class="q-label">Expenses</div>
            <div class="q-value" style="font-size:14px;color:#7C2D12">GHS {{ number_format($mySectionTotals['expense'],0) }}</div>
        </div>
    </div>
    <div class="col">
        <div class="quarter-pill"><div class="q-label">Net Income</div>
            <div class="q-value" style="font-size:14px;color:{{ $mySectionTotals['net'] >= 0 ? '#10B981' : '#F43F5E' }}">
                {{ $mySectionTotals['net'] < 0 ? '–' : '' }}GHS {{ number_format(abs($mySectionTotals['net']),0) }}
            </div>
        </div>
    </div>
    @if($mySectionTotals['capex'] > 0)
    <div class="col">
        <div class="quarter-pill"><div class="q-label">CapEx</div>
            <div class="q-value" style="font-size:14px;color:#7C2D12">GHS {{ number_format($mySectionTotals['capex'],0) }}</div>
        </div>
    </div>
    @endif
</div>
@endif

{{-- Charts --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="chart-card">
            <div class="chart-title">Quarterly Budget Distribution</div>
            <canvas id="deptQBar" height="200"></canvas>
        </div>
    </div>
    <div class="col-md-6">
        <div class="chart-card">
            <div class="chart-title">
                Monthly Actuals vs Budget
                @if($currentPeriod) — {{ $currentPeriod->name }} @endif
            </div>
            <canvas id="deptMonthly" height="200"></canvas>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    @if(isset($topItems) && $topItems->count())
    <div class="col-md-6">
        <div class="chart-card">
            <div class="chart-title">Top 5 Budget Lines</div>
            <canvas id="topItemsBar" height="200"></canvas>
        </div>
    </div>
    @endif
    @if(isset($deptPeriodSummary) && $deptPeriodSummary->count() > 1)
    <div class="col-md-6">
        <div class="chart-card">
            <div class="chart-title">My Budget History (All Periods)</div>
            <canvas id="deptYoY" height="200"></canvas>
        </div>
    </div>
    @endif
</div>
@endif

{{-- Bottom row --}}
<div class="row g-3">
    <div class="col-md-6">
        <div class="chart-card">
            <div class="chart-title">
                <i class="bi bi-clock-history me-1" style="color:var(--navy)"></i>Version History
            </div>
            @forelse($versionHistory as $v)
            <div class="dept-row">
                <div class="dept-code" style="font-size:14px;font-weight:700">v{{ $v->version_number }}</div>
                <div>
                    <div class="dept-name">Version {{ $v->version_number }}</div>
                    <div class="dept-meta">{{ $v->submitted_at?->format('d M Y') ?? 'Not submitted' }}</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="status-pill pill-{{ $v->status }}">
                        {{ ucfirst(str_replace('_', ' ', $v->status)) }}
                    </span>
                    <a href="{{ route('budget.show-pnl', $v) }}"
                       style="font-size:11px;color:var(--navy)">
                        View <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
            @empty
            <div class="text-muted small text-center py-3">No versions yet.</div>
            @endforelse
        </div>
    </div>

    <div class="col-md-6">
        <div class="chart-card">
            <div class="chart-title">
                <i class="bi bi-lightning-fill me-1" style="color:var(--gold)"></i>Quick Actions
            </div>
            <div class="d-grid gap-2">
                <a href="{{ isset($myBudget) && $myBudget ? route('budget.show-pnl', $myBudget) : route('budget.index') }}"
                   class="btn btn-sm text-start d-flex align-items-center gap-2"
                   style="background:var(--surface);border:1px solid var(--border);
                          border-radius:8px;padding:10px 14px;font-size:13px;color:var(--navy)">
                    <div style="width:28px;height:28px;border-radius:7px;background:#EFF6FF;
                                display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="bi bi-file-earmark-text" style="color:#3B82F6"></i>
                    </div>
                    View / Edit My Budget
                </a>
                <a href="{{ route('actuals.index') }}"
                   class="btn btn-sm text-start d-flex align-items-center gap-2"
                   style="background:var(--surface);border:1px solid var(--border);
                          border-radius:8px;padding:10px 14px;font-size:13px;color:var(--navy)">
                    <div style="width:28px;height:28px;border-radius:7px;background:#F0FDF4;
                                display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="bi bi-cash-coin" style="color:#10B981"></i>
                    </div>
                    Record Actuals
                </a>
                <a href="{{ route('virements.index') }}"
                   class="btn btn-sm text-start d-flex align-items-center gap-2"
                   style="background:var(--surface);border:1px solid var(--border);
                          border-radius:8px;padding:10px 14px;font-size:13px;color:var(--navy)">
                    <div style="width:28px;height:28px;border-radius:7px;background:#F5F3FF;
                                display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="bi bi-arrow-left-right" style="color:#6366F1"></i>
                    </div>
                    Virement Requests
                    @if($myVirements)
                    <span class="badge ms-auto"
                          style="background:#F43F5E;color:#fff;font-size:10px">
                        {{ $myVirements }} pending
                    </span>
                    @endif
                </a>
                <a href="{{ route('reports.department') }}"
                   class="btn btn-sm text-start d-flex align-items-center gap-2"
                   style="background:var(--surface);border:1px solid var(--border);
                          border-radius:8px;padding:10px 14px;font-size:13px;color:var(--navy)">
                    <div style="width:28px;height:28px;border-radius:7px;background:#FFF7ED;
                                display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="bi bi-bar-chart-line" style="color:#F97316"></i>
                    </div>
                    My Department Report
                </a>
                <a href="{{ route('notifications.index') }}"
                   class="btn btn-sm text-start d-flex align-items-center gap-2"
                   style="background:var(--surface);border:1px solid var(--border);
                          border-radius:8px;padding:10px 14px;font-size:13px;color:var(--navy)">
                    <div style="width:28px;height:28px;border-radius:7px;background:#FEF3C7;
                                display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="bi bi-bell" style="color:#D97706"></i>
                    </div>
                    Notifications
                    @if($unreadCount)
                    <span class="badge ms-auto"
                          style="background:#F43F5E;color:#fff;font-size:10px">
                        {{ $unreadCount }} new
                    </span>
                    @endif
                </a>
            </div>
        </div>
    </div>
</div>

<script>
const fmt    = v => v>=1000000?(v/1000000).toFixed(1)+'M':v>=1000?(v/1000).toFixed(0)+'K':v;
const yScale = {beginAtZero:true,grid:{color:'#F1F5F9'},ticks:{font:{size:10},callback:fmt}};

new Chart(document.getElementById('deptQBar'),{
    type:'bar',
    data:{
        labels:['Q1','Q2','Q3','Q4'],
        datasets:[{
            data:[{{ $quarterTotals->q1??0 }},{{ $quarterTotals->q2??0 }},
                  {{ $quarterTotals->q3??0 }},{{ $quarterTotals->q4??0 }}],
            backgroundColor:['#1B2A4A','#C9A84C','#10B981','#6366F1'],
            borderRadius:8,borderSkipped:false,
        }]
    },
    options:{responsive:true,plugins:{legend:{display:false}},
             scales:{y:yScale,x:{grid:{display:false}}}}
});

@php
    $deptMonthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    $deptMonthData   = isset($monthlyActuals)
        ? array_map(fn($m) => $monthlyActuals[$m] ?? 0, range(1,12))
        : array_fill(0,12,0);
    $monthlyBudget   = isset($quarterTotals)
        ? ($quarterTotals->q1+$quarterTotals->q2+$quarterTotals->q3+$quarterTotals->q4) / 12
        : 0;
@endphp
new Chart(document.getElementById('deptMonthly'),{
    type:'bar',
    data:{
        labels:{!! json_encode($deptMonthLabels) !!},
        datasets:[
            {label:'Actual',data:{!! json_encode($deptMonthData) !!},
             backgroundColor:'#10B981',borderRadius:4,borderSkipped:false},
            {label:'Monthly Budget',
             data:Array(12).fill({{ round($monthlyBudget,2) }}),
             type:'line',borderColor:'#C9A84C',borderWidth:2,
             borderDash:[5,4],pointRadius:0,fill:false},
        ]
    },
    options:{responsive:true,
             plugins:{legend:{position:'top',labels:{font:{size:10},boxWidth:10}}},
             scales:{y:yScale,x:{grid:{display:false},ticks:{font:{size:10}}}}}
});

@if(isset($topItems) && $topItems->count())
new Chart(document.getElementById('topItemsBar'),{
    type:'bar',
    data:{
        labels:{!! json_encode($topItems->map(fn($i)=>$i->accountCode->code)->toArray()) !!},
        datasets:[{
            label:'Budget (GHS)',
            data:{!! json_encode($topItems->map(fn($i)=>$i->total_amount)->toArray()) !!},
            backgroundColor:'#1B2A4A',borderRadius:4,borderSkipped:false,
        }]
    },
    options:{indexAxis:'y',responsive:true,
             plugins:{legend:{display:false}},
             scales:{x:yScale,y:{grid:{display:false},ticks:{font:{size:10}}}}}
});
@endif

@if(isset($deptPeriodSummary) && $deptPeriodSummary->count() > 1)
new Chart(document.getElementById('deptYoY'),{
    type:'line',
    data:{
        labels:{!! json_encode($deptPeriodSummary->pluck('name')->toArray()) !!},
        datasets:[
            {label:'Budget',
             data:{!! json_encode($deptPeriodSummary->pluck('budget')->toArray()) !!},
             borderColor:'#1B2A4A',backgroundColor:'rgba(27,42,74,.08)',
             borderWidth:2.5,pointBackgroundColor:'#C9A84C',
             pointRadius:5,fill:true,tension:.4},
            {label:'Actual',
             data:{!! json_encode($deptPeriodSummary->pluck('actual')->toArray()) !!},
             borderColor:'#10B981',backgroundColor:'rgba(16,185,129,.08)',
             borderWidth:2.5,pointBackgroundColor:'#10B981',
             pointRadius:5,fill:true,tension:.4},
        ]
    },
    options:{responsive:true,
             plugins:{legend:{position:'top',labels:{font:{size:11},boxWidth:12}}},
             scales:{y:yScale,x:{grid:{display:false},ticks:{font:{size:11}}}}}
});
@endif
</script>
@endif
@endif

@endsection
