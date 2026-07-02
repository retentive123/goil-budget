@extends('layouts.app')
@section('title', 'Budget P&L — ' . $budgetVersion->department->name)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('budgets.index') }}" class="text-muted text-decoration-none small">← All Budgets</a>
        <span class="text-muted">/</span>
        <a href="{{ route('budgets.department', $budgetVersion->department) }}" class="text-muted text-decoration-none small">
            {{ $budgetVersion->department->name }}
        </a>
        <span class="text-muted">/</span>
        <span class="small">v{{ $budgetVersion->version_number }} — {{ $budgetVersion->period->name }}</span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <div class="btn-group btn-group-sm" role="group">
            <a href="{{ route('budgets.show', $budgetVersion) }}" class="btn btn-outline-secondary">Classic View</a>
            <span class="btn btn-secondary" style="pointer-events:none;">P&amp;L View</span>
        </div>
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-download"></i> Export
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="{{ route('ie.budget.export', $budgetVersion) }}">
                    <i class="bi bi-file-earmark-excel me-1"></i> Classic Export (.xlsx)
                </a></li>
                <li><a class="dropdown-item" href="{{ route('ie.budget.export-pnl', $budgetVersion) }}">
                    <i class="bi bi-file-earmark-excel me-1"></i> P&amp;L Export (.xlsx)
                </a></li>
            </ul>
        </div>
    </div>
</div>

{{-- Version switcher --}}
@if($allVersions->count() > 1)
<div class="d-flex gap-2 mb-4 flex-wrap">
    @foreach($allVersions as $v)
    <a href="{{ route('budgets.show-pnl', $v) }}"
       style="padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;
              background:{{ $v->id === $budgetVersion->id ? 'var(--navy)' : '#F1F5F9' }};
              color:{{ $v->id === $budgetVersion->id ? '#fff' : 'var(--slate)' }}">
        v{{ $v->version_number }}
        <span style="opacity:.7;font-size:10px"> — {{ ucfirst(str_replace('_',' ',$v->status)) }}</span>
    </a>
    @endforeach
</div>
@endif

{{-- Header card --}}
<div class="chart-card mb-4" style="border-left:4px solid {{ match($budgetVersion->status) {
    'approved' => '#10B981', 'rejected' => '#F43F5E', 'submitted' => '#3B82F6',
    'under_review' => '#F59E0B', default => '#64748B'
} }}">
    <div class="row align-items-center">
        <div class="col">
            <div style="font-size:19px;font-weight:700;color:var(--navy)">
                {{ $budgetVersion->department->name }}
            </div>
            <div style="font-size:13px;color:var(--slate);margin-top:4px">
                {{ $budgetVersion->period->name }} &middot; Version {{ $budgetVersion->version_number }}
                @if($budgetVersion->submittedBy)
                &middot; Submitted by {{ $budgetVersion->submittedBy->name }}
                @if($budgetVersion->submitted_at)
                    on {{ $budgetVersion->submitted_at->format('d M Y H:i') }}
                @endif
                @endif
                @if($prevPeriod)
                &middot; <span class="text-muted">Comparing with {{ $prevPeriod->year }}</span>
                @endif
            </div>
        </div>
        <div class="col-auto d-flex gap-2 align-items-center">
            <span style="padding:6px 16px;border-radius:20px;font-size:13px;font-weight:700;
                         background:{{ match($budgetVersion->status) {
                             'approved' => '#D1FAE5', 'rejected' => '#FEE2E2',
                             'submitted' => '#DBEAFE', 'under_review' => '#FEF3C7',
                             default => '#F1F5F9'
                         } }};
                         color:{{ match($budgetVersion->status) {
                             'approved' => '#065F46', 'rejected' => '#991B1B',
                             'submitted' => '#1E40AF', 'under_review' => '#92400E',
                             default => '#475569'
                         } }}">
                {{ ucfirst(str_replace('_',' ',$budgetVersion->status)) }}
            </span>
            @if($canDecide)
            <a href="{{ route('approvals.show-pnl', $budgetVersion) }}" class="btn btn-sm"
               style="background:#F59E0B;color:#fff;border-radius:8px;padding:6px 14px">
                Review & Decide →
            </a>
            @endif
        </div>
    </div>
</div>

<div class="row g-4">

    {{-- Left: P&L table --}}
    <div class="col-lg-9">

        {{-- Summary bar --}}
        <div class="card mb-3 border-0" style="background:var(--navy)">
            <div class="card-body py-2">
                <div class="row text-center text-white">
                    <div class="col">
                        <div class="small" style="color:rgba(255,255,255,.5)">Revenue Budget</div>
                        <div class="fw-bold" id="bar-rev">
                            {{ currency() }} {{ number_format($pnlData['revenue']['totals']['effective'], 2) }}
                        </div>
                    </div>
                    <div class="col border-start border-secondary">
                        <div class="small" style="color:rgba(255,255,255,.5)">Expense Budget</div>
                        <div class="fw-bold" id="bar-exp">
                            {{ currency() }} {{ number_format($pnlData['expense']['totals']['effective'], 2) }}
                        </div>
                    </div>
                    <div class="col border-start border-secondary">
                        <div class="small" style="color:rgba(255,255,255,.5)">Net Income</div>
                        @php $net = $pnlData['revenue']['totals']['effective'] - $pnlData['expense']['totals']['effective']; @endphp
                        <div class="fw-bold fs-5" style="color:{{ $net >= 0 ? '#6EE7B7' : '#FCA5A5' }}">
                            {{ currency() }} {{ number_format($net, 2) }}
                        </div>
                    </div>
                    @php $totalActual = $actualsPerItem->sum(); @endphp
                    <div class="col border-start border-secondary">
                        <div class="small" style="color:rgba(255,255,255,.5)">Actual (YTD)</div>
                        <div class="fw-bold" style="color:#6EE7B7">{{ currency() }} {{ number_format($totalActual, 2) }}</div>
                    </div>
                    @if($prevPeriod)
                    <div class="col border-start border-secondary">
                        <div class="small" style="color:rgba(255,255,255,.5)">Prev Net ({{ $prevPeriod->year }})</div>
                        @php $prevNet = $pnlData['revenue']['totals']['prev_actual'] - $pnlData['expense']['totals']['prev_actual']; @endphp
                        <div class="fw-bold" style="color:rgba(255,255,255,.7)">
                            {{ currency() }} {{ number_format($prevNet, 2) }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Split non-PnL items --}}
        @php
            $abCapex   = array_filter($summary, fn($d) =>
                ($d['items']->first()?->accountCode?->category?->budget_type ?? '') === 'capital_expenditure');
            $abBalance = array_filter($summary, fn($d) => in_array(
                $d['items']->first()?->accountCode?->category?->budget_type ?? '', ['assets', 'liabilities']));
        @endphp

        {{-- Tab navigation --}}
        <ul class="nav nav-tabs mb-0" id="abPnlTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#abtab-is" type="button">
                    Income Statement
                </button>
            </li>
            @if(!empty($abCapex))
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#abtab-capex" type="button">
                    Capital Expenditure
                    <span class="badge bg-secondary ms-1" style="font-size:10px">{{ count($abCapex) }}</span>
                </button>
            </li>
            @endif
            @if(!empty($abBalance))
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#abtab-balance" type="button">
                    Assets &amp; Liabilities
                    <span class="badge bg-secondary ms-1" style="font-size:10px">{{ count($abBalance) }}</span>
                </button>
            </li>
            @endif
        </ul>

        <div class="tab-content border border-top-0 rounded-bottom" id="abPnlTabsContent">

        {{-- Tab 1: Income Statement --}}
        <div class="tab-pane fade show active p-0" id="abtab-is" role="tabpanel">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                <span class="fw-semibold small text-uppercase">Income Statement — {{ $budgetVersion->period->name }}</span>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary" onclick="expandAll()">Expand All</button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="collapseAll()">Collapse All</button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0" id="pnl-table">
                        <thead>
                            <tr style="background:#1B2A4A;color:#fff;font-size:12px">
                                <th rowspan="2" style="min-width:220px;vertical-align:middle">Account</th>
                                <th colspan="6" class="text-center border-start border-secondary py-2">
                                    {{ $budgetVersion->period->year }} Budget
                                </th>
                                <th class="text-center border-start border-secondary py-2" rowspan="2"
                                    style="vertical-align:middle;min-width:90px">Actual (YTD)</th>
                                @if($prevPeriod)
                                <th colspan="3" class="text-center border-start border-secondary py-2">
                                    {{ $prevPeriod->year }} Reference
                                </th>
                                @endif
                            </tr>
                            <tr style="background:#243B55;color:#CBD5E1;font-size:11px">
                                <th class="text-end border-start border-secondary" style="min-width:85px">Q1</th>
                                <th class="text-end" style="min-width:85px">Q2</th>
                                <th class="text-end" style="min-width:85px">Q3</th>
                                <th class="text-end" style="min-width:85px">Q4</th>
                                <th class="text-end" style="min-width:100px">Total</th>
                                <th class="text-end" style="min-width:55px">CS&nbsp;%</th>
                                @if($prevPeriod)
                                <th class="text-end border-start border-secondary" style="min-width:95px">Prev Budget</th>
                                <th class="text-end" style="min-width:95px">Prev Actual</th>
                                <th class="text-end" style="min-width:65px">Growth&nbsp;%</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody id="pnl-tbody">
                            <tr><td colspan="20" class="text-center text-muted py-4">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        </div>

        {{-- Tab 2: Capital Expenditure --}}
        @if(!empty($abCapex))
        <div class="tab-pane fade p-3" id="abtab-capex" role="tabpanel">
            @foreach($abCapex as $categoryName => $categoryData)
            @php
                $catSuppTotal      = $categoryData['items']->sum(fn($i) => $i->approvedSupplementaryTotal());
                $catEffectiveTotal = $categoryData['items']->sum(fn($i) => $i->effectiveBudget());
                $catActualTotal    = $categoryData['items']->sum(fn($i) => $actualsPerItem->get($i->id, 0));
            @endphp
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light py-2 d-flex justify-content-between">
                    <span class="fw-semibold small text-uppercase">{{ $categoryName }}</span>
                    <span class="small text-muted">Total: <strong>{{ currency() }} {{ number_format($catEffectiveTotal, 2) }}</strong>
                        <span style="color:#10B981"> | Actual: {{ number_format($catActualTotal, 2) }}</span>
                    </span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:30%">Account</th>
                                <th class="text-end">Q1</th><th class="text-end">Q2</th>
                                <th class="text-end">Q3</th><th class="text-end">Q4</th>
                                <th class="text-end">Supplementary</th><th class="text-end">Effective</th>
                                <th class="text-end">Actual</th><th class="text-end">Variance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categoryData['items'] as $item)
                            @php
                                $itemSupp     = $item->approvedSupplementaryTotal();
                                $itemEff      = $item->effectiveBudget();
                                $itemActual   = $actualsPerItem->get($item->id, 0);
                                $itemVariance = $itemActual - $itemEff;
                            @endphp
                            <tr>
                                <td class="small"><code>{{ $item->accountCode->code }}</code> {{ $item->accountCode->name }}</td>
                                <td class="text-end small">{{ number_format($item->q1_amount, 2) }}</td>
                                <td class="text-end small">{{ number_format($item->q2_amount, 2) }}</td>
                                <td class="text-end small">{{ number_format($item->q3_amount, 2) }}</td>
                                <td class="text-end small">{{ number_format($item->q4_amount, 2) }}</td>
                                <td class="text-end small" style="color:{{ $itemSupp > 0 ? '#10B981' : 'inherit' }}">
                                    {{ $itemSupp > 0 ? '+'.number_format($itemSupp, 2) : '—' }}
                                </td>
                                <td class="text-end small fw-semibold" style="color:var(--navy)">{{ number_format($itemEff, 2) }}</td>
                                <td class="text-end small" style="color:#10B981">{{ number_format($itemActual, 2) }}</td>
                                <td class="text-end small fw-semibold"
                                    style="color:{{ $itemVariance > 0 ? '#F43F5E' : ($itemVariance < 0 ? '#10B981' : 'inherit') }}">
                                    {{ $itemVariance >= 0 ? '+' : '' }}{{ number_format($itemVariance, 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Tab 3: Assets & Liabilities --}}
        @if(!empty($abBalance))
        <div class="tab-pane fade p-3" id="abtab-balance" role="tabpanel">
            @foreach($abBalance as $categoryName => $categoryData)
            @php
                $catSuppTotal      = $categoryData['items']->sum(fn($i) => $i->approvedSupplementaryTotal());
                $catEffectiveTotal = $categoryData['items']->sum(fn($i) => $i->effectiveBudget());
                $catActualTotal    = $categoryData['items']->sum(fn($i) => $actualsPerItem->get($i->id, 0));
            @endphp
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light py-2 d-flex justify-content-between">
                    <span class="fw-semibold small text-uppercase">{{ $categoryName }}</span>
                    <span class="small text-muted">Total: <strong>{{ currency() }} {{ number_format($catEffectiveTotal, 2) }}</strong>
                        <span style="color:#10B981"> | Actual: {{ number_format($catActualTotal, 2) }}</span>
                    </span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:30%">Account</th>
                                <th class="text-end">Q1</th><th class="text-end">Q2</th>
                                <th class="text-end">Q3</th><th class="text-end">Q4</th>
                                <th class="text-end">Supplementary</th><th class="text-end">Effective</th>
                                <th class="text-end">Actual</th><th class="text-end">Variance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categoryData['items'] as $item)
                            @php
                                $itemSupp     = $item->approvedSupplementaryTotal();
                                $itemEff      = $item->effectiveBudget();
                                $itemActual   = $actualsPerItem->get($item->id, 0);
                                $itemVariance = $itemActual - $itemEff;
                            @endphp
                            <tr>
                                <td class="small"><code>{{ $item->accountCode->code }}</code> {{ $item->accountCode->name }}</td>
                                <td class="text-end small">{{ number_format($item->q1_amount, 2) }}</td>
                                <td class="text-end small">{{ number_format($item->q2_amount, 2) }}</td>
                                <td class="text-end small">{{ number_format($item->q3_amount, 2) }}</td>
                                <td class="text-end small">{{ number_format($item->q4_amount, 2) }}</td>
                                <td class="text-end small" style="color:{{ $itemSupp > 0 ? '#10B981' : 'inherit' }}">
                                    {{ $itemSupp > 0 ? '+'.number_format($itemSupp, 2) : '—' }}
                                </td>
                                <td class="text-end small fw-semibold" style="color:var(--navy)">{{ number_format($itemEff, 2) }}</td>
                                <td class="text-end small" style="color:#10B981">{{ number_format($itemActual, 2) }}</td>
                                <td class="text-end small fw-semibold"
                                    style="color:{{ $itemVariance > 0 ? '#F43F5E' : ($itemVariance < 0 ? '#10B981' : 'inherit') }}">
                                    {{ $itemVariance >= 0 ? '+' : '' }}{{ number_format($itemVariance, 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        </div>{{-- end tab-content --}}

    </div>

    {{-- Right: approval + supplementary + actions --}}
    <div class="col-lg-3">

        {{-- Approval progress --}}
        <div class="chart-card mb-4">
            <div class="chart-title">Approval Progress</div>
            @foreach($progress as $idx => $step)
            @php
                $isLast  = $idx === count($progress)-1;
                $iconBg  = match($step['status']) {
                    'approved' => '#10B981', 'rejected' => '#F43F5E',
                    'pending'  => '#F59E0B', default    => '#E2E8F0',
                };
                $icon = match($step['status']) {
                    'approved' => '✔', 'rejected' => '✘',
                    'pending'  => '●', default    => '○',
                };
            @endphp
            <div class="d-flex gap-3" style="position:relative">
                @if(!$isLast)
                <div style="position:absolute;left:15px;top:32px;width:2px;
                            height:calc(100% - 16px);
                            background:{{ $step['status']==='approved'?'#10B981':'#E2E8F0' }};z-index:0"></div>
                @endif
                <div style="width:32px;height:32px;border-radius:50%;background:{{ $iconBg }};color:#fff;
                            display:flex;align-items:center;justify-content:center;
                            font-size:13px;font-weight:700;flex-shrink:0;position:relative;z-index:1">
                    {{ $icon }}
                </div>
                <div class="flex-grow-1 pb-4">
                    <div style="font-size:13px;font-weight:700;color:var(--navy)">
                        {{ $step['stage']->name }}
                        @if(!$step['is_active'])
                        <span style="font-size:10px;color:#94A3B8;font-weight:400"> (inactive)</span>
                        @endif
                    </div>
                    @if($step['decision'])
                    <div style="font-size:11px;color:var(--slate)">
                        {{ $step['decision']->decidedBy->name }}
                        &middot; {{ $step['decision']->decided_at->format('d M Y H:i') }}
                    </div>
                    @if($step['decision']->comments)
                    <div style="font-size:11px;color:var(--slate);font-style:italic;
                                background:#F8FAFC;border-radius:6px;padding:6px 8px;margin-top:4px">
                        "{{ $step['decision']->comments }}"
                    </div>
                    @endif
                    @elseif($step['status']==='pending')
                    <div style="font-size:11px;color:#F59E0B">Awaiting decision</div>
                    @else
                    <div style="font-size:11px;color:#94A3B8">Not yet reached</div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        {{-- Supplementary budgets --}}
        @if($supplementaries->count())
        <div class="chart-card mb-4">
            <div class="chart-title">
                Supplementary Requests
                <span style="background:#F1F5F9;border-radius:20px;padding:1px 8px;
                             font-size:11px;font-weight:400;color:var(--slate)">
                    {{ $supplementaries->count() }}
                </span>
            </div>
            @foreach($supplementaries as $s)
            <div style="padding:8px 0;border-bottom:1px solid var(--border);font-size:12px">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <code style="font-size:11px;color:var(--navy)">{{ $s->accountCode->code }}</code>
                        <span style="color:var(--slate)"> — {{ $s->accountCode->name }}</span>
                    </div>
                    <span style="padding:1px 8px;border-radius:20px;font-size:10px;font-weight:600;
                                 background:{{ match($s->status) {
                                     'approved' => '#D1FAE5', 'rejected' => '#FEE2E2',
                                     'submitted' => '#DBEAFE', default => '#F1F5F9'
                                 } }};
                                 color:{{ match($s->status) {
                                     'approved' => '#065F46', 'rejected' => '#991B1B',
                                     'submitted' => '#1E40AF', default => '#475569'
                                 } }}">
                        {{ ucfirst($s->status) }}
                    </span>
                </div>
                <div style="color:var(--slate);margin-top:3px">
                    Requested: GHS {{ number_format($s->requested_amount,2) }}
                    @if($s->approved_amount)
                    &nbsp;→&nbsp;<span style="color:#10B981;font-weight:600">
                        Approved: GHS {{ number_format($s->approved_amount,2) }}
                    </span>
                    @endif
                </div>
                <div style="color:#94A3B8;font-size:10px;margin-top:2px">
                    By {{ $s->requestedBy->name }} · {{ $s->submitted_at?->format('d M Y') }}
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Quick actions --}}
        <div class="chart-card">
            <div class="chart-title">Actions</div>
            <div class="d-grid gap-2">
                @if($canDecide)
                <a href="{{ route('approvals.show-pnl', $budgetVersion) }}" class="btn btn-sm text-start"
                   style="background:var(--navy);color:#fff;border-radius:8px;padding:10px 14px">
                    ✅ &nbsp; Review & Decide
                </a>
                @endif

                <a href="{{ route('approvals.history', $budgetVersion) }}" class="btn btn-sm text-start"
                   style="background:var(--surface);border:1px solid var(--border);
                          border-radius:8px;padding:10px 14px;font-size:13px;color:var(--navy)">
                    📋 &nbsp; Full Approval History
                </a>

                <a href="{{ route('budgets.department', $budgetVersion->department) }}" class="btn btn-sm text-start"
                   style="background:var(--surface);border:1px solid var(--border);
                          border-radius:8px;padding:10px 14px;font-size:13px;color:var(--navy)">
                    🏢 &nbsp; All Versions — {{ $budgetVersion->department->name }}
                </a>

                <a href="{{ route('reports.department', ['department_id'=>$budgetVersion->department_id,'period_id'=>$budgetVersion->budget_period_id]) }}"
                   class="btn btn-sm text-start"
                   style="background:var(--surface);border:1px solid var(--border);
                          border-radius:8px;padding:10px 14px;font-size:13px;color:var(--navy)">
                    📊 &nbsp; Department Report
                </a>

                <hr style="margin:4px 0">
                <div style="font-size:11px;color:var(--slate);font-weight:600;padding:2px 0">Export</div>

                <a href="{{ route('ie.budget.export', $budgetVersion) }}" class="btn btn-sm text-start"
                   style="background:var(--surface);border:1px solid var(--border);
                          border-radius:8px;padding:10px 14px;font-size:13px;color:var(--navy)">
                    ↓ &nbsp; Classic Export (.xlsx)
                </a>
                <a href="{{ route('ie.budget.export-pnl', $budgetVersion) }}" class="btn btn-sm text-start"
                   style="background:var(--surface);border:1px solid var(--border);
                          border-radius:8px;padding:10px 14px;font-size:13px;color:var(--navy)">
                    ↓ &nbsp; P&amp;L Export (.xlsx)
                </a>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
const PNL      = @json($pnlData);
const ACTUALS  = @json($actualsPerItem);
const HAS_PREV = {{ $prevPeriod ? 'true' : 'false' }};
const CUR      = "{{ currency() }}";

function numFmt(v) {
    return parseFloat(v || 0).toLocaleString('en-GH', {minimumFractionDigits:2, maximumFractionDigits:2});
}

function escHtml(s) {
    const d = document.createElement('div');
    d.textContent = s || '';
    return d.innerHTML;
}

function growthHtml(type, current, prev) {
    if (!prev) return '<span class="text-muted">—</span>';
    const g = ((current - prev) / Math.abs(prev)) * 100;
    const isGood = type === 'revenue' ? g >= 0 : g <= 0;
    const color  = isGood ? '#10B981' : '#F43F5E';
    const sign   = g >= 0 ? '+' : '';
    return `<span style="color:${color}">${sign}${g.toFixed(1)}%</span>`;
}

function renderPnl() {
    let html = '';
    html += renderSection('revenue', 'REVENUE INCOME');
    html += '<tr style="height:6px;background:#F8FAFC"><td colspan="99"></td></tr>';
    html += renderSection('expense', 'OPERATING EXPENSES');
    html += renderNetRow();
    document.getElementById('pnl-tbody').innerHTML = html;
}

function renderSection(type, label) {
    let html = '';
    const sec   = PNL[type];
    const bg    = type === 'revenue' ? '#1B2A4A' : '#7C2D12';
    const light = type === 'revenue' ? '#EFF3F9' : '#FFF7ED';

    html += `<tr style="background:${bg};color:#fff;">
        <td colspan="99" style="font-size:11px;font-weight:700;letter-spacing:1px;padding:8px 12px">${label}</td>
    </tr>`;

    sec.categories.forEach((cat, catIdx) => {
        const catId     = `${type}_${catIdx}`;
        const collapsed = cat.items.length > 8;

        // Category totals for actual
        const catActual = cat.items.reduce((s, it) => s + (ACTUALS[it.id] || 0), 0);

        html += `<tr class="pnl-cat-row" id="crow_${catId}" data-type="${type}"
                     style="background:${light};cursor:pointer;font-size:12px;font-weight:600"
                     onclick="toggleCat('${catId}')">
            <td style="padding-left:14px">
                <i class="fas fa-chevron-${collapsed ? 'right' : 'down'} me-2 small" id="icon_${catId}"></i>
                ${escHtml(cat.name)}
                <span class="text-muted fw-normal ms-1" style="font-size:11px">(${cat.items.length})</span>
            </td>
            <td class="text-end border-start" id="cq1_${catId}">${numFmt(cat.totals.q1)}</td>
            <td class="text-end" id="cq2_${catId}">${numFmt(cat.totals.q2)}</td>
            <td class="text-end" id="cq3_${catId}">${numFmt(cat.totals.q3)}</td>
            <td class="text-end" id="cq4_${catId}">${numFmt(cat.totals.q4)}</td>
            <td class="text-end fw-bold" id="ceff_${catId}">${numFmt(cat.totals.effective)}</td>
            <td class="text-end text-muted" id="ccs_${catId}">${numFmt(cat.totals.common_size)}%</td>
            <td class="text-end border-start" style="color:#10B981">${numFmt(catActual)}</td>
            ${HAS_PREV ? `
            <td class="text-end border-start text-muted" id="cpb_${catId}">${numFmt(cat.totals.prev_budget)}</td>
            <td class="text-end text-muted" id="cpa_${catId}">${numFmt(cat.totals.prev_actual)}</td>
            <td class="text-end" id="cgr_${catId}">${growthHtml(type, cat.totals.effective, cat.totals.prev_actual)}</td>
            ` : ''}
        </tr>`;

        cat.items.forEach(item => {
            const actual    = ACTUALS[item.id] || 0;
            const suppBadge = item.supp > 0
                ? `<span class="badge ms-1" style="background:#D1FAE5;color:#065F46;font-size:10px">+${numFmt(item.supp)} supp</span>`
                : '';

            html += `<tr class="pnl-item-row ${collapsed ? 'd-none' : ''}" style="font-size:12px"
                        data-item-id="${item.id}" data-cat-id="${catId}" data-type="${type}">
                <td style="padding-left:2.2rem" class="small">
                    <code class="text-muted me-1" style="font-size:11px">${escHtml(item.code)}</code>${escHtml(item.name)}${suppBadge}
                    ${item.justification ? `<div class="text-muted" style="font-size:11px">${escHtml(item.justification)}</div>` : ''}
                </td>
                <td class="text-end border-start">${numFmt(item.q1)}</td>
                <td class="text-end">${numFmt(item.q2)}</td>
                <td class="text-end">${numFmt(item.q3)}</td>
                <td class="text-end">${numFmt(item.q4)}</td>
                <td class="text-end fw-semibold">${numFmt(item.effective)}</td>
                <td class="text-end text-muted small">${numFmt(item.common_size)}%</td>
                <td class="text-end border-start" style="color:#10B981">${numFmt(actual)}</td>
                ${HAS_PREV ? `
                <td class="text-end text-muted small border-start">${numFmt(item.prev_budget)}</td>
                <td class="text-end text-muted small">${numFmt(item.prev_actual)}</td>
                <td class="text-end small">${growthHtml(type, item.effective, item.prev_actual)}</td>
                ` : ''}
            </tr>`;
        });
    });

    const t   = sec.totals;
    const bg2 = type === 'revenue' ? '#1E3A5F' : '#431407';
    html += `<tr id="st_${type}" style="background:${bg2};color:#fff;font-weight:700;font-size:12px;border-top:2px solid #fff">
        <td style="padding-left:12px;font-size:13px">TOTAL ${label}</td>
        <td class="text-end border-start border-secondary">${numFmt(t.q1)}</td>
        <td class="text-end">${numFmt(t.q2)}</td>
        <td class="text-end">${numFmt(t.q3)}</td>
        <td class="text-end">${numFmt(t.q4)}</td>
        <td class="text-end">${numFmt(t.effective)}</td>
        <td class="text-end">100%</td>
        <td class="text-end border-start"></td>
        ${HAS_PREV ? `
        <td class="text-end border-start border-secondary">${numFmt(t.prev_budget)}</td>
        <td class="text-end">${numFmt(t.prev_actual)}</td>
        <td class="text-end">${growthHtml(type, t.effective, t.prev_actual)}</td>
        ` : ''}
    </tr>`;

    return html;
}

function renderNetRow() {
    const revT = PNL.revenue.totals;
    const expT = PNL.expense.totals;
    const net  = revT.effective - expT.effective;
    const prevNetB = revT.prev_budget - expT.prev_budget;
    const prevNetA = revT.prev_actual - expT.prev_actual;
    const netColor = net >= 0 ? '#6EE7B7' : '#FCA5A5';

    const totalActual = Object.values(ACTUALS).reduce((s, v) => s + v, 0);

    return `<tr style="background:#0F172A;color:#fff;font-weight:700;font-size:13px;border-top:3px solid #E2E8F0">
        <td style="padding-left:12px">NET INCOME / (LOSS)</td>
        <td class="text-end border-start border-secondary">${numFmt(revT.q1 - expT.q1)}</td>
        <td class="text-end">${numFmt(revT.q2 - expT.q2)}</td>
        <td class="text-end">${numFmt(revT.q3 - expT.q3)}</td>
        <td class="text-end">${numFmt(revT.q4 - expT.q4)}</td>
        <td class="text-end fs-6 fw-bold" style="color:${netColor}">${numFmt(net)}</td>
        <td class="text-end">—</td>
        <td class="text-end border-start" style="color:#6EE7B7">${numFmt(totalActual)}</td>
        ${HAS_PREV ? `
        <td class="text-end border-start border-secondary">${numFmt(prevNetB)}</td>
        <td class="text-end">${numFmt(prevNetA)}</td>
        <td class="text-end">${growthHtml('revenue', net, prevNetA)}</td>
        ` : ''}
    </tr>`;
}

function toggleCat(catId) {
    const icon = document.getElementById(`icon_${catId}`);
    const rows = document.querySelectorAll(`.pnl-item-row[data-cat-id="${catId}"]`);
    const isCollapsed = rows.length > 0 && rows[0].classList.contains('d-none');
    rows.forEach(r => r.classList.toggle('d-none', !isCollapsed));
    if (icon) {
        icon.classList.toggle('fa-chevron-right', !isCollapsed);
        icon.classList.toggle('fa-chevron-down', isCollapsed);
    }
}

function expandAll() {
    document.querySelectorAll('.pnl-item-row').forEach(r => r.classList.remove('d-none'));
    document.querySelectorAll('[id^="icon_"]').forEach(i => {
        i.classList.remove('fa-chevron-right');
        i.classList.add('fa-chevron-down');
    });
}

function collapseAll() {
    document.querySelectorAll('.pnl-item-row').forEach(r => r.classList.add('d-none'));
    document.querySelectorAll('[id^="icon_"]').forEach(i => {
        i.classList.remove('fa-chevron-down');
        i.classList.add('fa-chevron-right');
    });
}

document.addEventListener('DOMContentLoaded', renderPnl);
</script>
@endpush

@endsection
