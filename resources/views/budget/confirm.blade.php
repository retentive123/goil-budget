@extends('layouts.app')
@section('title', 'Confirm Submission')
@section('content')

@php
    $rev    = $pnlData['revenue']['totals'];
    $exp    = $pnlData['expense']['totals'];
    $capex  = $pnlData['capex']['totals'];
    $bal    = $pnlData['balance']['totals'];
    $net    = $rev['effective'] - $exp['effective'];
    $hasPrev = !!$prevPeriod;

    $hasCapex   = !empty($pnlData['capex']['categories']);
    $hasBalance = !empty($pnlData['balance']['categories']);
@endphp

<div class="row justify-content-center">
<div class="col-xl-10 col-lg-11">

    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
                <a href="{{ route('budget.show-pnl', $budgetVersion) }}" class="text-decoration-none">
                    <i class="bi bi-arrow-left"></i> Budget
                </a>
            </li>
            <li class="breadcrumb-item active">Confirm Submission</li>
        </ol>
    </nav>

    {{-- Page Header --}}
    <div class="card border-0 shadow-lg mb-4" style="border-radius:16px;overflow:hidden">
        <div class="card-header border-0 px-4 py-3" style="background:#E65C00;color:#fff">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-circle"
                         style="width:44px;height:44px;background:rgba(255,255,255,.2);font-size:20px">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0" style="color:#fff">Confirm Submission</h5>
                        <p class="mb-0" style="font-size:13px;color:rgba(255,255,255,.85)">
                            {{ $budgetVersion->department->name }} &middot;
                            {{ $budgetVersion->period->name }} &middot;
                            Version {{ $budgetVersion->version_number }}
                        </p>
                    </div>
                </div>
                <span class="badge px-3 py-2"
                      style="background:rgba(255,255,255,.2);color:#fff;font-size:12px;border-radius:20px">
                    <i class="bi bi-file-earmark-text"></i> Ready for Review
                </span>
            </div>
        </div>

        <div class="card-body p-4">

            {{-- ── Section metric cards ─────────────────────────────────── --}}
            <h6 class="fw-semibold mb-3" style="color:#1B2A4A;font-size:13px">
                <i class="bi bi-pie-chart" style="color:#E65C00"></i> Budget Totals by Section
            </h6>
            <div class="row g-2 mb-4">

                {{-- Revenue --}}
                <div class="col-6 col-md">
                    <div class="p-3 rounded-3 text-center h-100"
                         style="background:#EFF3F9;border:1px solid #CBD5E1">
                        <div style="font-size:10px;font-weight:700;color:#1B2A4A;letter-spacing:.5px;text-transform:uppercase">Revenue</div>
                        <div class="fw-bold mt-1" style="font-size:14px;color:#1B2A4A">
                            {{ currency() }} {{ number_format($rev['effective'], 2) }}
                        </div>
                        @if($hasPrev && $rev['prev_actual'] > 0)
                        @php $g = (($rev['effective'] - $rev['prev_actual']) / $rev['prev_actual']) * 100; @endphp
                        <div style="font-size:10px;color:{{ $g >= 0 ? '#10B981' : '#F43F5E' }}">
                            {{ $g >= 0 ? '+' : '' }}{{ number_format($g,1) }}% vs prev
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Expense --}}
                <div class="col-6 col-md">
                    <div class="p-3 rounded-3 text-center h-100"
                         style="background:#FFF7ED;border:1px solid #FDBA74">
                        <div style="font-size:10px;font-weight:700;color:#7C2D12;letter-spacing:.5px;text-transform:uppercase">Expense</div>
                        <div class="fw-bold mt-1" style="font-size:14px;color:#7C2D12">
                            {{ currency() }} {{ number_format($exp['effective'], 2) }}
                        </div>
                        @if($hasPrev && $exp['prev_actual'] > 0)
                        @php $g = (($exp['effective'] - $exp['prev_actual']) / $exp['prev_actual']) * 100; @endphp
                        <div style="font-size:10px;color:{{ $g <= 0 ? '#10B981' : '#F43F5E' }}">
                            {{ $g >= 0 ? '+' : '' }}{{ number_format($g,1) }}% vs prev
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Net Income --}}
                <div class="col-12 col-md">
                    <div class="p-3 rounded-3 text-center h-100"
                         style="background:{{ $net >= 0 ? '#ECFDF5' : '#FFF1F2' }};border:1px solid {{ $net >= 0 ? '#6EE7B7' : '#FECDD3' }}">
                        <div style="font-size:10px;font-weight:700;color:{{ $net >= 0 ? '#065F46' : '#9F1239' }};letter-spacing:.5px;text-transform:uppercase">
                            Net Income
                        </div>
                        <div class="fw-bold mt-1" style="font-size:16px;color:{{ $net >= 0 ? '#059669' : '#E11D48' }}">
                            {{ currency() }} {{ number_format($net, 2) }}
                        </div>
                    </div>
                </div>

                @if($hasCapex)
                {{-- CapEx --}}
                <div class="col-6 col-md">
                    <div class="p-3 rounded-3 text-center h-100"
                         style="background:#FEF3C7;border:1px solid #FCD34D">
                        <div style="font-size:10px;font-weight:700;color:#78350F;letter-spacing:.5px;text-transform:uppercase">Capital Exp.</div>
                        <div class="fw-bold mt-1" style="font-size:14px;color:#78350F">
                            {{ currency() }} {{ number_format($capex['effective'], 2) }}
                        </div>
                        @if($hasPrev && $capex['prev_actual'] > 0)
                        @php $g = (($capex['effective'] - $capex['prev_actual']) / $capex['prev_actual']) * 100; @endphp
                        <div style="font-size:10px;color:{{ $g <= 0 ? '#10B981' : '#F43F5E' }}">
                            {{ $g >= 0 ? '+' : '' }}{{ number_format($g,1) }}% vs prev
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                @if($hasBalance)
                {{-- Balance --}}
                <div class="col-6 col-md">
                    <div class="p-3 rounded-3 text-center h-100"
                         style="background:#EDE9FE;border:1px solid #C4B5FD">
                        <div style="font-size:10px;font-weight:700;color:#4C1D95;letter-spacing:.5px;text-transform:uppercase">Assets & Liab.</div>
                        <div class="fw-bold mt-1" style="font-size:14px;color:#4C1D95">
                            {{ currency() }} {{ number_format($bal['effective'], 2) }}
                        </div>
                        @if($hasPrev && $bal['prev_actual'] > 0)
                        @php $g = (($bal['effective'] - $bal['prev_actual']) / $bal['prev_actual']) * 100; @endphp
                        <div style="font-size:10px;color:{{ $g <= 0 ? '#10B981' : '#F43F5E' }}">
                            {{ $g >= 0 ? '+' : '' }}{{ number_format($g,1) }}% vs prev
                        </div>
                        @endif
                    </div>
                </div>
                @endif

            </div>

            {{-- ── 3-Tab Line Item Preview ──────────────────────────────── --}}
            <h6 class="fw-semibold mb-2" style="color:#1B2A4A;font-size:13px">
                <i class="bi bi-list-ul" style="color:#E65C00"></i> Budget Breakdown
                <span class="badge ms-2" style="background:#F1F5F9;color:#64748B;font-weight:500;font-size:11px">
                    {{ $budgetVersion->lineItems->count() }} line items
                </span>
            </h6>

            <ul class="nav nav-tabs mb-0" id="confirmTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#ctab-is" type="button">
                        <i class="bi bi-bar-chart-line me-1"></i>Income Statement
                    </button>
                </li>
                @if($hasCapex)
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#ctab-capex" type="button">
                        <i class="bi bi-tools me-1"></i>Capital Expenditure
                        <span class="badge ms-1" style="background:#FEF3C7;color:#78350F;font-size:10px">
                            {{ count($pnlData['capex']['categories']) }}
                        </span>
                    </button>
                </li>
                @endif
                @if($hasBalance)
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#ctab-balance" type="button">
                        <i class="bi bi-bank me-1"></i>Assets &amp; Liabilities
                        <span class="badge ms-1" style="background:#EDE9FE;color:#4C1D95;font-size:10px">
                            {{ count($pnlData['balance']['categories']) }}
                        </span>
                    </button>
                </li>
                @endif
            </ul>

            <div class="tab-content border border-top-0 rounded-bottom mb-4" id="confirmTabsContent"
                 style="max-height:380px;overflow-y:auto">

                {{-- ── Tab 1: Income Statement ── --}}
                <div class="tab-pane fade show active" id="ctab-is" role="tabpanel">
                    <table class="table table-sm mb-0 confirm-table" style="font-size:12px">
                        <thead class="sticky-top" style="top:0">
                            <tr style="background:#1B2A4A;color:#fff">
                                <th style="min-width:200px">Category</th>
                                <th class="text-end border-start border-secondary" style="min-width:70px">Q1</th>
                                <th class="text-end" style="min-width:70px">Q2</th>
                                <th class="text-end" style="min-width:70px">Q3</th>
                                <th class="text-end" style="min-width:70px">Q4</th>
                                <th class="text-end" style="min-width:100px">Total</th>
                                @if($hasPrev)
                                <th class="text-end border-start border-secondary" style="min-width:90px">Prev Budget</th>
                                <th class="text-end" style="min-width:90px">Prev Actual</th>
                                <th class="text-end" style="min-width:65px">Growth</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Revenue section --}}
                            <tr style="background:#1B2A4A;color:#fff">
                                <td colspan="99" style="font-size:11px;font-weight:700;letter-spacing:1px;padding:7px 12px">
                                    REVENUE INCOME
                                </td>
                            </tr>
                            @forelse($pnlData['revenue']['categories'] as $cat)
                            @php
                                $t = $cat['totals'];
                                $growth = ($hasPrev && $t['prev_actual'] > 0)
                                    ? (($t['effective'] - $t['prev_actual']) / abs($t['prev_actual'])) * 100
                                    : null;
                            @endphp
                            <tr style="background:#EFF3F9">
                                <td style="padding-left:14px;font-weight:600;color:#1B2A4A">
                                    {{ $cat['name'] }}
                                    <span class="text-muted fw-normal ms-1" style="font-size:10px">({{ count($cat['items']) }})</span>
                                </td>
                                <td class="text-end border-start text-muted small">{{ number_format($t['q1'], 2) }}</td>
                                <td class="text-end text-muted small">{{ number_format($t['q2'], 2) }}</td>
                                <td class="text-end text-muted small">{{ number_format($t['q3'], 2) }}</td>
                                <td class="text-end text-muted small">{{ number_format($t['q4'], 2) }}</td>
                                <td class="text-end fw-semibold" style="color:#1B2A4A">{{ number_format($t['effective'], 2) }}</td>
                                @if($hasPrev)
                                <td class="text-end text-muted small border-start">{{ number_format($t['prev_budget'], 2) }}</td>
                                <td class="text-end text-muted small">{{ number_format($t['prev_actual'], 2) }}</td>
                                <td class="text-end small">
                                    @if($growth !== null)
                                    <span style="color:{{ $growth >= 0 ? '#10B981' : '#F43F5E' }}">
                                        {{ $growth >= 0 ? '+' : '' }}{{ number_format($growth, 1) }}%
                                    </span>
                                    @else <span class="text-muted">—</span> @endif
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr><td colspan="99" class="text-center text-muted py-3 small">No revenue items</td></tr>
                            @endforelse
                            {{-- Revenue total --}}
                            <tr style="background:#1E3A5F;color:#fff;font-weight:700">
                                <td style="padding-left:12px">TOTAL REVENUE</td>
                                <td class="text-end border-start border-secondary">{{ number_format($rev['q1'], 2) }}</td>
                                <td class="text-end">{{ number_format($rev['q2'], 2) }}</td>
                                <td class="text-end">{{ number_format($rev['q3'], 2) }}</td>
                                <td class="text-end">{{ number_format($rev['q4'], 2) }}</td>
                                <td class="text-end">{{ number_format($rev['effective'], 2) }}</td>
                                @if($hasPrev)
                                <td class="text-end border-start border-secondary">{{ number_format($rev['prev_budget'], 2) }}</td>
                                <td class="text-end">{{ number_format($rev['prev_actual'], 2) }}</td>
                                <td class="text-end">
                                    @if($rev['prev_actual'] > 0)
                                    @php $g = (($rev['effective'] - $rev['prev_actual']) / abs($rev['prev_actual'])) * 100; @endphp
                                    <span style="color:{{ $g >= 0 ? '#6EE7B7' : '#FCA5A5' }}">{{ $g >= 0 ? '+' : '' }}{{ number_format($g,1) }}%</span>
                                    @else <span class="text-muted">—</span> @endif
                                </td>
                                @endif
                            </tr>

                            {{-- Spacer --}}
                            <tr style="height:4px;background:#F8FAFC"><td colspan="99"></td></tr>

                            {{-- Expense section --}}
                            <tr style="background:#7C2D12;color:#fff">
                                <td colspan="99" style="font-size:11px;font-weight:700;letter-spacing:1px;padding:7px 12px">
                                    OPERATING EXPENSES
                                </td>
                            </tr>
                            @forelse($pnlData['expense']['categories'] as $cat)
                            @php
                                $t = $cat['totals'];
                                $growth = ($hasPrev && $t['prev_actual'] > 0)
                                    ? (($t['effective'] - $t['prev_actual']) / abs($t['prev_actual'])) * 100
                                    : null;
                            @endphp
                            <tr style="background:#FFF7ED">
                                <td style="padding-left:14px;font-weight:600;color:#7C2D12">
                                    {{ $cat['name'] }}
                                    <span class="text-muted fw-normal ms-1" style="font-size:10px">({{ count($cat['items']) }})</span>
                                </td>
                                <td class="text-end border-start text-muted small">{{ number_format($t['q1'], 2) }}</td>
                                <td class="text-end text-muted small">{{ number_format($t['q2'], 2) }}</td>
                                <td class="text-end text-muted small">{{ number_format($t['q3'], 2) }}</td>
                                <td class="text-end text-muted small">{{ number_format($t['q4'], 2) }}</td>
                                <td class="text-end fw-semibold" style="color:#7C2D12">{{ number_format($t['effective'], 2) }}</td>
                                @if($hasPrev)
                                <td class="text-end text-muted small border-start">{{ number_format($t['prev_budget'], 2) }}</td>
                                <td class="text-end text-muted small">{{ number_format($t['prev_actual'], 2) }}</td>
                                <td class="text-end small">
                                    @if($growth !== null)
                                    <span style="color:{{ $growth <= 0 ? '#10B981' : '#F43F5E' }}">
                                        {{ $growth >= 0 ? '+' : '' }}{{ number_format($growth, 1) }}%
                                    </span>
                                    @else <span class="text-muted">—</span> @endif
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr><td colspan="99" class="text-center text-muted py-3 small">No expense items</td></tr>
                            @endforelse
                            {{-- Expense total --}}
                            <tr style="background:#431407;color:#fff;font-weight:700">
                                <td style="padding-left:12px">TOTAL EXPENSES</td>
                                <td class="text-end border-start border-secondary">{{ number_format($exp['q1'], 2) }}</td>
                                <td class="text-end">{{ number_format($exp['q2'], 2) }}</td>
                                <td class="text-end">{{ number_format($exp['q3'], 2) }}</td>
                                <td class="text-end">{{ number_format($exp['q4'], 2) }}</td>
                                <td class="text-end">{{ number_format($exp['effective'], 2) }}</td>
                                @if($hasPrev)
                                <td class="text-end border-start border-secondary">{{ number_format($exp['prev_budget'], 2) }}</td>
                                <td class="text-end">{{ number_format($exp['prev_actual'], 2) }}</td>
                                <td class="text-end">
                                    @if($exp['prev_actual'] > 0)
                                    @php $g = (($exp['effective'] - $exp['prev_actual']) / abs($exp['prev_actual'])) * 100; @endphp
                                    <span style="color:{{ $g <= 0 ? '#6EE7B7' : '#FCA5A5' }}">{{ $g >= 0 ? '+' : '' }}{{ number_format($g,1) }}%</span>
                                    @else <span class="text-muted">—</span> @endif
                                </td>
                                @endif
                            </tr>

                            {{-- Net Income --}}
                            @php
                                $netPrevB = $rev['prev_budget'] - $exp['prev_budget'];
                                $netPrevA = $rev['prev_actual'] - $exp['prev_actual'];
                            @endphp
                            <tr style="background:#0F172A;color:#fff;font-weight:700;font-size:13px;border-top:2px solid #E2E8F0">
                                <td style="padding-left:12px">NET INCOME / (LOSS)</td>
                                <td class="text-end border-start border-secondary">{{ number_format($rev['q1'] - $exp['q1'], 2) }}</td>
                                <td class="text-end">{{ number_format($rev['q2'] - $exp['q2'], 2) }}</td>
                                <td class="text-end">{{ number_format($rev['q3'] - $exp['q3'], 2) }}</td>
                                <td class="text-end">{{ number_format($rev['q4'] - $exp['q4'], 2) }}</td>
                                <td class="text-end fs-6" style="color:{{ $net >= 0 ? '#6EE7B7' : '#FCA5A5' }}">
                                    {{ number_format($net, 2) }}
                                </td>
                                @if($hasPrev)
                                <td class="text-end border-start border-secondary">{{ number_format($netPrevB, 2) }}</td>
                                <td class="text-end">{{ number_format($netPrevA, 2) }}</td>
                                <td class="text-end">
                                    @if($netPrevA != 0)
                                    @php $g = (($net - $netPrevA) / abs($netPrevA)) * 100; @endphp
                                    <span style="color:{{ $g >= 0 ? '#6EE7B7' : '#FCA5A5' }}">{{ $g >= 0 ? '+' : '' }}{{ number_format($g,1) }}%</span>
                                    @else <span class="text-muted">—</span> @endif
                                </td>
                                @endif
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- ── Tab 2: Capital Expenditure ── --}}
                @if($hasCapex)
                <div class="tab-pane fade" id="ctab-capex" role="tabpanel">
                    <table class="table table-sm mb-0 confirm-table" style="font-size:12px">
                        <thead class="sticky-top" style="top:0">
                            <tr style="background:#1B2A4A;color:#fff">
                                <th style="min-width:200px">Category</th>
                                <th class="text-end border-start border-secondary" style="min-width:70px">Q1</th>
                                <th class="text-end" style="min-width:70px">Q2</th>
                                <th class="text-end" style="min-width:70px">Q3</th>
                                <th class="text-end" style="min-width:70px">Q4</th>
                                <th class="text-end" style="min-width:100px">Total</th>
                                @if($hasPrev)
                                <th class="text-end border-start border-secondary" style="min-width:90px">Prev Budget</th>
                                <th class="text-end" style="min-width:90px">Prev Actual</th>
                                <th class="text-end" style="min-width:65px">Growth</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="background:#78350F;color:#fff">
                                <td colspan="99" style="font-size:11px;font-weight:700;letter-spacing:1px;padding:7px 12px">
                                    CAPITAL EXPENDITURE
                                </td>
                            </tr>
                            @foreach($pnlData['capex']['categories'] as $cat)
                            @php
                                $t = $cat['totals'];
                                $growth = ($hasPrev && $t['prev_actual'] > 0)
                                    ? (($t['effective'] - $t['prev_actual']) / abs($t['prev_actual'])) * 100
                                    : null;
                            @endphp
                            <tr style="background:#FEF3C7">
                                <td style="padding-left:14px;font-weight:600;color:#78350F">
                                    {{ $cat['name'] }}
                                    <span class="text-muted fw-normal ms-1" style="font-size:10px">({{ count($cat['items']) }})</span>
                                </td>
                                <td class="text-end border-start text-muted small">{{ number_format($t['q1'], 2) }}</td>
                                <td class="text-end text-muted small">{{ number_format($t['q2'], 2) }}</td>
                                <td class="text-end text-muted small">{{ number_format($t['q3'], 2) }}</td>
                                <td class="text-end text-muted small">{{ number_format($t['q4'], 2) }}</td>
                                <td class="text-end fw-semibold" style="color:#78350F">{{ number_format($t['effective'], 2) }}</td>
                                @if($hasPrev)
                                <td class="text-end text-muted small border-start">{{ number_format($t['prev_budget'], 2) }}</td>
                                <td class="text-end text-muted small">{{ number_format($t['prev_actual'], 2) }}</td>
                                <td class="text-end small">
                                    @if($growth !== null)
                                    <span style="color:{{ $growth <= 0 ? '#10B981' : '#F43F5E' }}">
                                        {{ $growth >= 0 ? '+' : '' }}{{ number_format($growth, 1) }}%
                                    </span>
                                    @else <span class="text-muted">—</span> @endif
                                </td>
                                @endif
                            </tr>
                            @endforeach
                            <tr style="background:#92400E;color:#fff;font-weight:700">
                                <td style="padding-left:12px">TOTAL CAPITAL EXPENDITURE</td>
                                <td class="text-end border-start border-secondary">{{ number_format($capex['q1'], 2) }}</td>
                                <td class="text-end">{{ number_format($capex['q2'], 2) }}</td>
                                <td class="text-end">{{ number_format($capex['q3'], 2) }}</td>
                                <td class="text-end">{{ number_format($capex['q4'], 2) }}</td>
                                <td class="text-end">{{ number_format($capex['effective'], 2) }}</td>
                                @if($hasPrev)
                                <td class="text-end border-start border-secondary">{{ number_format($capex['prev_budget'], 2) }}</td>
                                <td class="text-end">{{ number_format($capex['prev_actual'], 2) }}</td>
                                <td class="text-end">
                                    @if($capex['prev_actual'] > 0)
                                    @php $g = (($capex['effective'] - $capex['prev_actual']) / abs($capex['prev_actual'])) * 100; @endphp
                                    <span style="color:{{ $g <= 0 ? '#6EE7B7' : '#FCA5A5' }}">{{ $g >= 0 ? '+' : '' }}{{ number_format($g,1) }}%</span>
                                    @else <span class="text-muted">—</span> @endif
                                </td>
                                @endif
                            </tr>
                        </tbody>
                    </table>
                </div>
                @endif

                {{-- ── Tab 3: Assets & Liabilities ── --}}
                @if($hasBalance)
                <div class="tab-pane fade" id="ctab-balance" role="tabpanel">
                    <table class="table table-sm mb-0 confirm-table" style="font-size:12px">
                        <thead class="sticky-top" style="top:0">
                            <tr style="background:#1B2A4A;color:#fff">
                                <th style="min-width:200px">Category</th>
                                <th class="text-end border-start border-secondary" style="min-width:70px">Q1</th>
                                <th class="text-end" style="min-width:70px">Q2</th>
                                <th class="text-end" style="min-width:70px">Q3</th>
                                <th class="text-end" style="min-width:70px">Q4</th>
                                <th class="text-end" style="min-width:100px">Total</th>
                                @if($hasPrev)
                                <th class="text-end border-start border-secondary" style="min-width:90px">Prev Budget</th>
                                <th class="text-end" style="min-width:90px">Prev Actual</th>
                                <th class="text-end" style="min-width:65px">Growth</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="background:#4C1D95;color:#fff">
                                <td colspan="99" style="font-size:11px;font-weight:700;letter-spacing:1px;padding:7px 12px">
                                    ASSETS &amp; LIABILITIES
                                </td>
                            </tr>
                            @foreach($pnlData['balance']['categories'] as $cat)
                            @php
                                $t = $cat['totals'];
                                $growth = ($hasPrev && $t['prev_actual'] > 0)
                                    ? (($t['effective'] - $t['prev_actual']) / abs($t['prev_actual'])) * 100
                                    : null;
                            @endphp
                            <tr style="background:#EDE9FE">
                                <td style="padding-left:14px;font-weight:600;color:#4C1D95">
                                    {{ $cat['name'] }}
                                    <span class="text-muted fw-normal ms-1" style="font-size:10px">({{ count($cat['items']) }})</span>
                                </td>
                                <td class="text-end border-start text-muted small">{{ number_format($t['q1'], 2) }}</td>
                                <td class="text-end text-muted small">{{ number_format($t['q2'], 2) }}</td>
                                <td class="text-end text-muted small">{{ number_format($t['q3'], 2) }}</td>
                                <td class="text-end text-muted small">{{ number_format($t['q4'], 2) }}</td>
                                <td class="text-end fw-semibold" style="color:#4C1D95">{{ number_format($t['effective'], 2) }}</td>
                                @if($hasPrev)
                                <td class="text-end text-muted small border-start">{{ number_format($t['prev_budget'], 2) }}</td>
                                <td class="text-end text-muted small">{{ number_format($t['prev_actual'], 2) }}</td>
                                <td class="text-end small">
                                    @if($growth !== null)
                                    <span style="color:{{ $growth <= 0 ? '#10B981' : '#F43F5E' }}">
                                        {{ $growth >= 0 ? '+' : '' }}{{ number_format($growth, 1) }}%
                                    </span>
                                    @else <span class="text-muted">—</span> @endif
                                </td>
                                @endif
                            </tr>
                            @endforeach
                            <tr style="background:#5B21B6;color:#fff;font-weight:700">
                                <td style="padding-left:12px">TOTAL ASSETS &amp; LIABILITIES</td>
                                <td class="text-end border-start border-secondary">{{ number_format($bal['q1'], 2) }}</td>
                                <td class="text-end">{{ number_format($bal['q2'], 2) }}</td>
                                <td class="text-end">{{ number_format($bal['q3'], 2) }}</td>
                                <td class="text-end">{{ number_format($bal['q4'], 2) }}</td>
                                <td class="text-end">{{ number_format($bal['effective'], 2) }}</td>
                                @if($hasPrev)
                                <td class="text-end border-start border-secondary">{{ number_format($bal['prev_budget'], 2) }}</td>
                                <td class="text-end">{{ number_format($bal['prev_actual'], 2) }}</td>
                                <td class="text-end">
                                    @if($bal['prev_actual'] > 0)
                                    @php $g = (($bal['effective'] - $bal['prev_actual']) / abs($bal['prev_actual'])) * 100; @endphp
                                    <span style="color:{{ $g <= 0 ? '#6EE7B7' : '#FCA5A5' }}">{{ $g >= 0 ? '+' : '' }}{{ number_format($g,1) }}%</span>
                                    @else <span class="text-muted">—</span> @endif
                                </td>
                                @endif
                            </tr>
                        </tbody>
                    </table>
                </div>
                @endif

            </div>
            {{-- end tab-content --}}

            {{-- ── Warning ───────────────────────────────────────────────── --}}
            <div class="alert d-flex align-items-start gap-2 mb-4"
                 style="background:#FEF3C7;border:1px solid #F59E0B;border-radius:10px;padding:14px 18px">
                <i class="bi bi-exclamation-triangle" style="color:#92400E;font-size:18px"></i>
                <div>
                    <div style="font-weight:600;color:#92400E;font-size:13px">
                        <i class="bi bi-lock"></i> Important
                    </div>
                    <div style="font-size:13px;color:#78350F">
                        Once submitted, you will not be able to edit this budget unless it is rejected and sent back for revision.
                    </div>
                </div>
            </div>

            {{-- ── Submission Form ──────────────────────────────────────── --}}
            <form method="POST" action="{{ route('budget.submit', $budgetVersion) }}">
                @csrf
                <div class="mb-4">
                    <label class="form-label fw-semibold" style="color:#1B2A4A;font-size:13px">
                        <i class="bi bi-pencil-square" style="color:#E65C00"></i>
                        Submission Notes <span class="text-muted fw-normal">(optional)</span>
                    </label>
                    <textarea name="submission_notes" rows="3"
                        class="form-control @error('submission_notes') is-invalid @enderror"
                        style="border-radius:10px;border-color:#E2E8F0;padding:12px;resize:vertical"
                        placeholder="Any notes or context for the approver…">{{ old('submission_notes') }}</textarea>
                    @error('submission_notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn px-4 py-2 fw-semibold"
                            style="background:#E65C00;color:#fff;border-radius:10px;border:none">
                        <i class="bi bi-send"></i> Confirm &amp; Submit
                    </button>
                    <a href="{{ route('budget.show-pnl', $budgetVersion) }}"
                       class="btn px-4 py-2 fw-semibold"
                       style="background:#F1F5F9;color:#475569;border-radius:10px;border:1px solid #E2E8F0">
                        <i class="bi bi-arrow-left"></i> Go Back
                    </a>
                </div>
            </form>

        </div>
    </div>

    {{-- Tips --}}
    <div class="p-3 rounded-3" style="background:#F8FAFC;border:1px solid #E2E8F0">
        <div class="d-flex gap-2 align-items-start">
            <i class="bi bi-lightbulb" style="color:#C9A84C;font-size:18px"></i>
            <div>
                <div style="font-size:12px;font-weight:600;color:#1B2A4A">Tips for a Smooth Approval</div>
                <ul class="list-unstyled mb-0" style="font-size:12px;color:#64748B">
                    <li><i class="bi bi-check2-circle" style="color:#10B981"></i> Double-check all amounts for accuracy</li>
                    <li><i class="bi bi-check2-circle" style="color:#10B981"></i> Provide clear notes explaining any significant changes</li>
                    <li><i class="bi bi-check2-circle" style="color:#10B981"></i> Ensure all required justifications are included</li>
                </ul>
            </div>
        </div>
    </div>

</div>
</div>

<style>
.form-control:focus {
    border-color: #E65C00;
    box-shadow: 0 0 0 .2rem rgba(230,92,0,.15);
}
.confirm-table thead tr th {
    position: sticky;
    top: 0;
    z-index: 2;
}
.tab-content .table-responsive::-webkit-scrollbar,
.tab-content::-webkit-scrollbar { width:4px; height:4px; }
.tab-content::-webkit-scrollbar-thumb { background:#CBD5E1; border-radius:4px; }
.tab-content::-webkit-scrollbar-track { background:#F1F5F9; }
</style>

@endsection
