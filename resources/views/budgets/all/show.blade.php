@extends('layouts.app')
@section('title', 'Budget — ' . $budgetVersion->department->name)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('budgets.index') }}"
           class="text-muted text-decoration-none small">← All Budgets</a>
        <span class="text-muted">/</span>
        <a href="{{ route('budgets.department', $budgetVersion->department) }}"
           class="text-muted text-decoration-none small">
            {{ $budgetVersion->department->name }}
        </a>
        <span class="text-muted">/</span>
        <span class="small">v{{ $budgetVersion->version_number }} — {{ $budgetVersion->period->name }}</span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <div class="btn-group btn-group-sm" role="group">
            <span class="btn btn-secondary" style="pointer-events:none;">Classic View</span>
            <a href="{{ route('budgets.show-pnl', $budgetVersion) }}" class="btn btn-outline-secondary">P&amp;L View</a>
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
    <a href="{{ route('budgets.show', $v) }}"
       style="padding:6px 14px;border-radius:8px;font-size:12px;
              font-weight:600;text-decoration:none;
              background:{{ $v->id === $budgetVersion->id ? 'var(--navy)' : '#F1F5F9' }};
              color:{{ $v->id === $budgetVersion->id ? '#fff' : 'var(--slate)' }}">
        v{{ $v->version_number }}
        <span style="opacity:.7;font-size:10px">
            — {{ ucfirst(str_replace('_',' ',$v->status)) }}
        </span>
    </a>
    @endforeach
</div>
@endif

{{-- Header --}}
<div class="chart-card mb-4"
     style="border-left:4px solid {{
        match($budgetVersion->status) {
            'approved'     => '#10B981',
            'rejected'     => '#F43F5E',
            'submitted'    => '#3B82F6',
            'under_review' => '#F59E0B',
            default        => '#64748B'
        }
     }}">
    <div class="row align-items-center">
        <div class="col">
            <div style="font-size:19px;font-weight:700;color:var(--navy)">
                {{ $budgetVersion->department->name }}
            </div>
            <div style="font-size:13px;color:var(--slate);margin-top:4px">
                {{ $budgetVersion->period->name }}
                &middot; Version {{ $budgetVersion->version_number }}
                &middot; Type:
                <strong>{{ ucfirst($budgetVersion->department->budget_type) }}</strong>
                @if($budgetVersion->submittedBy)
                &middot; Submitted by {{ $budgetVersion->submittedBy->name }}
                @if($budgetVersion->submitted_at)
                    on {{ $budgetVersion->submitted_at->format('d M Y H:i') }}
                @endif
                @endif
            </div>
            @if($budgetVersion->submission_notes)
            <div style="font-size:12px;color:var(--slate);margin-top:6px;
                        background:#F8FAFC;border-radius:6px;padding:8px 12px">
                "{{ $budgetVersion->submission_notes }}"
            </div>
            @endif
        </div>
        <div class="col-auto d-flex gap-2 align-items-center flex-wrap">
            <span style="padding:6px 16px;border-radius:20px;font-size:13px;font-weight:700;
                         background:{{
                            match($budgetVersion->status) {
                                'approved'     => '#D1FAE5',
                                'rejected'     => '#FEE2E2',
                                'submitted'    => '#DBEAFE',
                                'under_review' => '#FEF3C7',
                                default        => '#F1F5F9'
                            }
                         }};
                         color:{{
                            match($budgetVersion->status) {
                                'approved'     => '#065F46',
                                'rejected'     => '#991B1B',
                                'submitted'    => '#1E40AF',
                                'under_review' => '#92400E',
                                default        => '#475569'
                            }
                         }}">
                {{ ucfirst(str_replace('_',' ',$budgetVersion->status)) }}
            </span>

            @if($canDecide)
            <a href="{{ route('approvals.show', $budgetVersion) }}"
               class="btn btn-sm"
               style="background:#F59E0B;color:#fff;border-radius:8px;padding:6px 14px">
                Review & Decide →
            </a>
            @endif

            <a href="{{ route('approvals.history', $budgetVersion) }}"
               class="btn btn-sm btn-outline-secondary"
               style="border-radius:8px;padding:6px 14px;font-size:12px">
                Full History
            </a>
        </div>
    </div>
</div>

<div class="row g-4">

    {{-- ── Left: Budget Detail ── --}}
    <div class="col-lg-9">

        {{-- Grand totals bar --}}
        @php
            $grandQ1    = $budgetVersion->lineItems->sum('q1_amount');
            $grandQ2    = $budgetVersion->lineItems->sum('q2_amount');
            $grandQ3    = $budgetVersion->lineItems->sum('q3_amount');
            $grandQ4    = $budgetVersion->lineItems->sum('q4_amount');

            $totalSupplementary = $budgetVersion->lineItems->sum(fn($i) => $i->approvedSupplementaryTotal());
            $grandTotal = $grandTotals['total'] - $totalSupplementary;
            $effectiveTotal = $grandTotal + $totalSupplementary;
            $totalActual = $actualsPerItem->sum();
            $utilPct    = $effectiveTotal > 0 ? round(($totalActual/$effectiveTotal)*100,1) : 0;
        @endphp

        <div style="background:var(--navy);border-radius:12px;padding:16px 20px;
                    color:#fff;margin-bottom:16px">
            <div class="row g-0 text-center">
                @foreach(['Q1'=>$grandQ1,'Q2'=>$grandQ2,'Q3'=>$grandQ3,'Q4'=>$grandQ4] as $q=>$val)
                <div class="col border-end" style="border-color:rgba(255,255,255,.1)!important">
                    <div style="font-size:10px;color:rgba(255,255,255,.5)">{{ $q }}</div>
                    <div style="font-size:13px;font-weight:700">
                        GHS {{ number_format($val,0) }}
                    </div>
                </div>
                @endforeach
                <div class="col border-end" style="border-color:rgba(255,255,255,.1)!important">
                    <div style="font-size:10px;color:var(--gold)">Original Budget</div>
                    <div style="font-size:14px;font-weight:700;color:var(--gold)">
                        GHS {{ number_format($grandTotal,0) }}
                    </div>
                    @if($totalSupplementary > 0)
                    <div style="font-size:10px;color:#6EE7B7">
                        +GHS {{ number_format($totalSupplementary,0) }} supp.
                    </div>
                    @endif
                </div>
                <div class="col border-end" style="border-color:rgba(255,255,255,.1)!important">
                    <div style="font-size:10px;color:#6EE7B7">Effective Budget</div>
                    <div style="font-size:16px;font-weight:700;color:#6EE7B7">
                        GHS {{ number_format($effectiveTotal,0) }}
                    </div>
                </div>
                <div class="col">
                    <div style="font-size:10px;color:#6EE7B7">Actual (YTD)</div>
                    <div style="font-size:16px;font-weight:700;color:#6EE7B7">
                        GHS {{ number_format($totalActual,0) }}
                    </div>
                    <div style="font-size:10px;color:rgba(255,255,255,.5)">
                        {{ $utilPct }}% utilised
                    </div>
                </div>
            </div>

            {{-- Utilisation bar --}}
            <div class="progress mt-3" style="height:6px;background:rgba(255,255,255,.15);border-radius:3px">
                <div class="progress-bar"
                     style="width:{{ min($utilPct,100) }}%;
                            background:{{ $utilPct>90?'#F43F5E':($utilPct>70?'#F59E0B':'#6EE7B7') }};
                            border-radius:3px">
                </div>
            </div>
        </div>

        {{-- Type aggregates for charts --}}
        @php
            $typeMap = [
                'revenue'             => ['label' => 'Revenue',             'color' => '#1B2A4A'],
                'both'                => ['label' => 'Revenue',             'color' => '#1B2A4A'],
                'expense'             => ['label' => 'Expense',             'color' => '#7C2D12'],
                'capital_expenditure' => ['label' => 'Capital Expenditure', 'color' => '#78350F'],
                'assets'              => ['label' => 'Assets',              'color' => '#4C1D95'],
                'liabilities'         => ['label' => 'Liabilities',         'color' => '#6D28D9'],
            ];
            $typeAgg = [];
            foreach ($summary as $catData) {
                $firstItem = $catData['items']->first();
                $rawType   = $firstItem->accountCode->category->budget_type ?? 'expense';
                $key       = ($rawType === 'both') ? 'revenue' : $rawType;
                if (!isset($typeAgg[$key])) {
                    $meta = $typeMap[$key] ?? ['label' => ucfirst(str_replace('_', ' ', $key)), 'color' => '#64748B'];
                    $typeAgg[$key] = ['label' => $meta['label'], 'color' => $meta['color'], 'effective' => 0, 'actual' => 0];
                }
                foreach ($catData['items'] as $_ci) {
                    $typeAgg[$key]['effective'] += $_ci->effectiveBudget();
                    $typeAgg[$key]['actual']    += $actualsPerItem->get($_ci->id, 0);
                }
            }
            $typeAgg = array_values(array_filter($typeAgg, function($d) {
                return $d['effective'] > 0 || $d['actual'] > 0;
            }));
            $btLabels    = array_column($typeAgg, 'label');
            $btColors    = array_column($typeAgg, 'color');
            $btEffective = array_column($typeAgg, 'effective');
            $btActuals   = array_column($typeAgg, 'actual');
        @endphp

        {{-- Tab group split --}}
        @php
            $pnlBudgetTypes = ['revenue', 'expense', 'both'];
            $allSumPnl      = [];
            $allSumCapex    = [];
            $allSumBalance  = [];
            foreach ($summary as $ck => $cd) {
                $bt = $cd['items']->first()->accountCode->category->budget_type ?? 'expense';
                if (in_array($bt, $pnlBudgetTypes))        $allSumPnl[$ck]     = $cd;
                elseif ($bt === 'capital_expenditure')      $allSumCapex[$ck]   = $cd;
                elseif (in_array($bt, ['assets','liabilities'])) $allSumBalance[$ck] = $cd;
            }
            $allTabGroups = [
                ['id' => 'altab-pnl',     'label' => 'Revenue &amp; Expenses',   'summary' => $allSumPnl,
                 'active' => true,  'catBg' => '#1B2A4A', 'totalBg' => '#0F172A', 'textColor' => '#BFDBFE'],
                ['id' => 'altab-capex',   'label' => 'Capital Expenditure',      'summary' => $allSumCapex,
                 'active' => false, 'catBg' => '#78350F', 'totalBg' => '#451A03', 'textColor' => '#FEF3C7'],
                ['id' => 'altab-balance', 'label' => 'Assets &amp; Liabilities', 'summary' => $allSumBalance,
                 'active' => false, 'catBg' => '#4C1D95', 'totalBg' => '#2E1065', 'textColor' => '#EDE9FE'],
            ];
        @endphp

        {{-- Charts by Budget Type --}}
        @if(!empty($typeAgg))
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="chart-card h-100">
                    <div class="chart-title">Budget by Type</div>
                    <canvas id="typeDonut" height="220"></canvas>
                </div>
            </div>
            <div class="col-md-8">
                <div class="chart-card h-100">
                    <div class="chart-title">Effective Budget vs Actual by Type</div>
                    <canvas id="typeBar" height="220"></canvas>
                </div>
            </div>
        </div>
        @endif

        {{-- Tab navigation --}}
        <ul class="nav nav-tabs mb-0" id="allBudgetTabs" role="tablist">
            @foreach($allTabGroups as $altab)
            @if($altab['active'] || !empty($altab['summary']))
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $altab['active'] ? 'active' : '' }}"
                        data-bs-toggle="tab" data-bs-target="#{{ $altab['id'] }}"
                        type="button" role="tab">
                    {!! $altab['label'] !!}
                    @if(!empty($altab['summary']))
                    <span class="badge bg-secondary ms-1" style="font-size:10px">{{ count($altab['summary']) }}</span>
                    @endif
                </button>
            </li>
            @endif
            @endforeach
        </ul>

        {{-- Tab content --}}
        <div class="tab-content border border-top-0 rounded-bottom mb-4" id="allBudgetTabsContent">
        @foreach($allTabGroups as $altab)
        @if($altab['active'] || !empty($altab['summary']))
        <div class="tab-pane fade {{ $altab['active'] ? 'show active' : '' }}"
             id="{{ $altab['id'] }}" role="tabpanel">

            @if(!empty($altab['summary']))
            @php
                $tabTotBudget = $tabTotSupp = $tabTotEff = $tabTotAct = 0;
                $tabTotQ1 = $tabTotQ2 = $tabTotQ3 = $tabTotQ4 = 0;
                foreach ($altab['summary'] as $cd) {
                    $tabTotBudget += $cd['total'];
                    $tabTotQ1 += $cd['items']->sum('q1_amount');
                    $tabTotQ2 += $cd['items']->sum('q2_amount');
                    $tabTotQ3 += $cd['items']->sum('q3_amount');
                    $tabTotQ4 += $cd['items']->sum('q4_amount');
                    foreach ($cd['items'] as $_ti) {
                        $tabTotSupp += $_ti->approvedSupplementaryTotal();
                        $tabTotEff  += $_ti->effectiveBudget();
                        $tabTotAct  += $actualsPerItem->get($_ti->id, 0);
                    }
                }
                $tabUtil = $tabTotEff > 0 ? round(($tabTotAct/$tabTotEff)*100,1) : 0;
                $tabVar  = $tabTotAct - $tabTotEff;
            @endphp

            {{-- Toolbar --}}
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom"
                 style="background:#F8FAFC">
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary" style="font-size:11px"
                            onclick="expandAll('{{ $altab['id'] }}')">
                        <i class="bi bi-arrows-expand"></i> Expand All
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" style="font-size:11px"
                            onclick="collapseAll('{{ $altab['id'] }}')">
                        <i class="bi bi-arrows-collapse"></i> Collapse All
                    </button>
                </div>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-success dropdown-toggle" style="font-size:11px"
                            type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-download"></i> Export
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="font-size:12px">
                        <li><a class="dropdown-item" href="#"
                               onclick="tabExportCSV('{{ $altab['id'] }}');return false">
                            <i class="bi bi-filetype-csv me-1 text-success"></i> CSV
                        </a></li>
                        <li><a class="dropdown-item" href="#"
                               onclick="tabExportExcel('{{ $altab['id'] }}');return false">
                            <i class="bi bi-file-earmark-excel me-1 text-success"></i> Excel
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#"
                               onclick="tabPrint('{{ $altab['id'] }}');return false">
                            <i class="bi bi-printer me-1"></i> Print
                        </a></li>
                    </ul>
                </div>
            </div>

            <div class="table-responsive" style="max-height:520px;overflow-y:auto">
            <table id="tbl-{{ $altab['id'] }}" class="table table-sm mb-0" style="font-size:12px;min-width:900px">
                <thead style="position:sticky;top:0;z-index:2">
                    <tr style="background:{{ $altab['catBg'] }};color:#fff">
                        <th style="min-width:220px">Account</th>
                        <th style="min-width:60px">Type</th>
                        <th class="text-end" style="min-width:72px">Q1</th>
                        <th class="text-end" style="min-width:72px">Q2</th>
                        <th class="text-end" style="min-width:72px">Q3</th>
                        <th class="text-end" style="min-width:72px">Q4</th>
                        <th class="text-end" style="min-width:88px">Original</th>
                        <th class="text-end" style="min-width:80px">Supp.</th>
                        <th class="text-end" style="min-width:88px">Effective</th>
                        <th class="text-end" style="min-width:80px">Actual</th>
                        <th class="text-end" style="min-width:80px">Variance</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($altab['summary'] as $catName => $catData)
                @php
                    $cid  = $altab['id'].'-'.($loop->index);
                    $cBud = $catData['total'];
                    $cAct = $cEff = $cSupp = 0;
                    foreach ($catData['items'] as $_ci) {
                        $cAct  += $actualsPerItem->get($_ci->id, 0);
                        $cEff  += $_ci->effectiveBudget();
                        $cSupp += $_ci->approvedSupplementaryTotal();
                    }
                    $cUtil = $cEff > 0 ? round(($cAct/$cEff)*100,1) : 0;
                    $cVar  = $cAct - $cEff;
                @endphp

                {{-- Category header (clickable) --}}
                <tr class="cat-hdr" style="background:{{ $altab['catBg'] }};color:#fff;cursor:pointer"
                    onclick="toggleCat('{{ $cid }}',this)">
                    <td colspan="11" style="padding:7px 12px;font-size:12px">
                        <span class="ct-arr"
                              style="display:inline-block;font-size:10px;margin-right:6px;
                                     transition:transform .15s">▼</span>
                        <strong>{{ $catName }}</strong>
                        <span style="font-size:10px;opacity:.6;margin-left:6px">
                            ({{ $catData['items']->count() }} items)
                        </span>
                        <span style="float:right;font-size:11px;opacity:.85">
                            Eff: GHS {{ number_format($cEff,0) }}
                            &nbsp;·&nbsp;Act: GHS {{ number_format($cAct,0) }}
                            &nbsp;·&nbsp;{{ $cUtil }}%
                            @if($cSupp > 0)
                            &nbsp;·&nbsp;+{{ number_format($cSupp,0) }} supp
                            @endif
                        </span>
                    </td>
                </tr>

                {{-- Item rows --}}
                @foreach($catData['items'] as $item)
                @php
                    $iAct  = $actualsPerItem->get($item->id, 0);
                    $iSupp = $item->approvedSupplementaryTotal();
                    $iEff  = $item->effectiveBudget();
                    $iVar  = $iAct - $iEff;
                @endphp
                <tr class="ci-{{ $cid }}">
                    <td style="padding-left:28px">
                        <div style="font-family:monospace;font-weight:700;font-size:11px;color:var(--navy)">
                            {{ $item->accountCode->code }}
                        </div>
                        <div style="font-size:11px;color:var(--slate)">{{ $item->accountCode->name }}</div>
                    </td>
                    <td>
                        <span style="padding:1px 5px;border-radius:3px;font-size:10px;font-weight:600;
                                     background:{{ $item->line_type==='revenue'?'#D1FAE5':'#FEE2E2' }};
                                     color:{{ $item->line_type==='revenue'?'#065F46':'#991B1B' }}">
                            {{ ucfirst($item->line_type) }}
                        </span>
                    </td>
                    <td class="text-end">{{ number_format($item->q1_amount,2) }}</td>
                    <td class="text-end">{{ number_format($item->q2_amount,2) }}</td>
                    <td class="text-end">{{ number_format($item->q3_amount,2) }}</td>
                    <td class="text-end">{{ number_format($item->q4_amount,2) }}</td>
                    <td class="text-end">{{ number_format($item->total_amount,2) }}</td>
                    <td class="text-end" style="color:{{ $iSupp>0?'#10B981':'var(--slate)' }}">
                        {{ $iSupp>0?'+'.number_format($iSupp,2):'—' }}
                    </td>
                    <td class="text-end fw-semibold" style="color:var(--navy)">{{ number_format($iEff,2) }}</td>
                    <td class="text-end" style="color:#10B981">{{ number_format($iAct,2) }}</td>
                    <td class="text-end fw-semibold"
                        style="color:{{ $iVar>0?'#F43F5E':($iVar<0?'#10B981':'inherit') }}">
                        {{ $iVar>=0?'+':'' }}{{ number_format($iVar,2) }}
                    </td>
                </tr>
                @endforeach

                {{-- Category subtotal --}}
                <tr class="ci-{{ $cid }}"
                    style="background:#F8FAFC;font-weight:700;font-size:11px;border-top:1px solid #E2E8F0">
                    <td colspan="2" style="padding-left:14px;color:var(--slate)">
                        {{ $catName }} — Subtotal
                    </td>
                    <td class="text-end">{{ number_format($catData['items']->sum('q1_amount'),2) }}</td>
                    <td class="text-end">{{ number_format($catData['items']->sum('q2_amount'),2) }}</td>
                    <td class="text-end">{{ number_format($catData['items']->sum('q3_amount'),2) }}</td>
                    <td class="text-end">{{ number_format($catData['items']->sum('q4_amount'),2) }}</td>
                    <td class="text-end">{{ number_format($cBud,2) }}</td>
                    <td class="text-end" style="color:{{ $cSupp>0?'#10B981':'inherit' }}">
                        {{ $cSupp>0?'+'.number_format($cSupp,2):'—' }}
                    </td>
                    <td class="text-end" style="color:var(--navy)">{{ number_format($cEff,2) }}</td>
                    <td class="text-end" style="color:#10B981">{{ number_format($cAct,2) }}</td>
                    <td class="text-end"
                        style="color:{{ $cVar>0?'#F43F5E':'#10B981' }}">
                        {{ $cVar>=0?'+':'' }}{{ number_format($cVar,2) }}
                    </td>
                </tr>
                <tr style="height:3px;background:#F1F5F9"><td colspan="11"></td></tr>

                @endforeach

                {{-- Section total --}}
                <tr style="background:{{ $altab['totalBg'] }};color:#fff;font-weight:700;
                            font-size:13px;border-top:2px solid rgba(255,255,255,.2)">
                    <td colspan="2" style="padding-left:14px">Section Total</td>
                    <td class="text-end">{{ number_format($tabTotQ1,2) }}</td>
                    <td class="text-end">{{ number_format($tabTotQ2,2) }}</td>
                    <td class="text-end">{{ number_format($tabTotQ3,2) }}</td>
                    <td class="text-end">{{ number_format($tabTotQ4,2) }}</td>
                    <td class="text-end">{{ number_format($tabTotBudget,2) }}</td>
                    <td class="text-end"
                        style="color:{{ $tabTotSupp>0?'#6EE7B7':'rgba(255,255,255,.35)' }}">
                        {{ $tabTotSupp>0?'+'.number_format($tabTotSupp,2):'—' }}
                    </td>
                    <td class="text-end" style="color:#6EE7B7">{{ number_format($tabTotEff,2) }}</td>
                    <td class="text-end" style="color:#6EE7B7">{{ number_format($tabTotAct,2) }}</td>
                    <td class="text-end"
                        style="color:{{ $tabVar>0?'#FCA5A5':'#6EE7B7' }}">
                        {{ $tabVar>=0?'+':'' }}{{ number_format($tabVar,2) }}
                    </td>
                </tr>
                </tbody>
            </table>
            </div>

            @else
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox d-block mb-2" style="font-size:2rem;opacity:.3"></i>
                No items in this section.
            </div>
            @endif
        </div>
        @endif
        @endforeach
        </div>

        {{-- Grand total footer --}}
        <div class="chart-card" style="background:var(--navy);color:#fff">
            <div class="row align-items-center">
                <div class="col">
                    <div style="font-size:12px;color:rgba(255,255,255,.6)">Grand Total</div>
                    @if($totalSupplementary > 0)
                    <div style="font-size:10px;color:#6EE7B7">
                        incl. {{ number_format($totalSupplementary,2) }} supplementary
                    </div>
                    @endif
                </div>
                <div class="col-auto text-end">
                    <div style="font-size:11px;color:rgba(255,255,255,.5)">Budget</div>
                    <div style="font-size:18px;font-weight:700;color:var(--gold)">
                        GHS {{ number_format($grandTotal,2) }}
                    </div>
                </div>
                <div class="col-auto text-end">
                    <div style="font-size:11px;color:rgba(255,255,255,.5)">Effective</div>
                    <div style="font-size:18px;font-weight:700;color:#6EE7B7">
                        GHS {{ number_format($effectiveTotal,2) }}
                    </div>
                </div>
                <div class="col-auto text-end">
                    <div style="font-size:11px;color:rgba(255,255,255,.5)">Actual</div>
                    <div style="font-size:18px;font-weight:700;color:#6EE7B7">
                        GHS {{ number_format($totalActual,2) }}
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Right: Approval + Supplementary ── --}}
    <div class="col-lg-3">

        {{-- Approval progress --}}
        <div class="chart-card mb-4">
            <div class="chart-title">Approval Progress</div>
            @foreach($progress as $idx => $step)
            @php
                $isLast   = $idx === count($progress)-1;
                $iconBg   = match($step['status']) {
                    'approved' => '#10B981',
                    'rejected' => '#F43F5E',
                    'pending'  => '#F59E0B',
                    default    => '#E2E8F0',
                };
                $icon = match($step['status']) {
                    'approved' => '✔',
                    'rejected' => '✘',
                    'pending'  => '●',
                    default    => '○',
                };
            @endphp
            <div class="d-flex gap-3" style="position:relative">
                @if(!$isLast)
                <div style="position:absolute;left:15px;top:32px;width:2px;
                            height:calc(100% - 16px);
                            background:{{ $step['status']==='approved'?'#10B981':'#E2E8F0' }};
                            z-index:0">
                </div>
                @endif
                <div style="width:32px;height:32px;border-radius:50%;
                            background:{{ $iconBg }};color:#fff;
                            display:flex;align-items:center;justify-content:center;
                            font-size:13px;font-weight:700;flex-shrink:0;
                            position:relative;z-index:1">
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
                    <span style="padding:1px 8px;border-radius:20px;font-size:10px;
                                 font-weight:600;
                                 background:{{
                                    match($s->status) {
                                        'approved'     => '#D1FAE5',
                                        'rejected'     => '#FEE2E2',
                                        'submitted'    => '#DBEAFE',
                                        default        => '#F1F5F9'
                                    }
                                 }};
                                 color:{{
                                    match($s->status) {
                                        'approved'     => '#065F46',
                                        'rejected'     => '#991B1B',
                                        'submitted'    => '#1E40AF',
                                        default        => '#475569'
                                    }
                                 }}">
                        {{ ucfirst($s->status) }}
                    </span>
                </div>
                <div style="color:var(--slate);margin-top:3px">
                    Requested: GHS {{ number_format($s->requested_amount,2) }}
                    @if($s->approved_amount)
                    &nbsp;→&nbsp;
                    <span style="color:#10B981;font-weight:600">
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
                <a href="{{ route('approvals.show', $budgetVersion) }}"
                   class="btn btn-sm text-start"
                   style="background:var(--navy);color:#fff;border-radius:8px;padding:10px 14px">
                    ✅ &nbsp; Review & Decide
                </a>
                @endif

                <a href="{{ route('approvals.history', $budgetVersion) }}"
                   class="btn btn-sm text-start"
                   style="background:var(--surface);border:1px solid var(--border);
                          border-radius:8px;padding:10px 14px;font-size:13px;color:var(--navy)">
                    📋 &nbsp; Full Approval History
                </a>

                <a href="{{ route('budgets.department', $budgetVersion->department) }}"
                   class="btn btn-sm text-start"
                   style="background:var(--surface);border:1px solid var(--border);
                          border-radius:8px;padding:10px 14px;font-size:13px;color:var(--navy)">
                    🏢 &nbsp; All Versions
                </a>

                <a href="{{ route('reports.department', ['department_id'=>$budgetVersion->department_id,'period_id'=>$budgetVersion->budget_period_id]) }}"
                   class="btn btn-sm text-start"
                   style="background:var(--surface);border:1px solid var(--border);
                          border-radius:8px;padding:10px 14px;font-size:13px;color:var(--navy)">
                    📊 &nbsp; Department Report
                </a>

                <a href="{{ route('actuals.entry', ['period_id'=>$budgetVersion->budget_period_id,'department_id'=>$budgetVersion->department_id,'month'=>now()->month,'year'=>now()->year]) }}"
                   class="btn btn-sm text-start"
                   style="background:var(--surface);border:1px solid var(--border);
                          border-radius:8px;padding:10px 14px;font-size:13px;color:var(--navy)">
                    💰 &nbsp; Record Actuals
                </a>
            </div>
        </div>

    </div>
</div>

{{-- Charts JS --}}
@if(!empty($typeAgg))
<script>
const fmt = v => v>=1000000?(v/1000000).toFixed(1)+'M':v>=1000?(v/1000).toFixed(0)+'K':v;

// Budget by Type — donut
new Chart(document.getElementById('typeDonut'), {
    type: 'doughnut',
    data: {
        labels:   {!! json_encode($btLabels) !!},
        datasets: [{
            data:            {!! json_encode($btEffective) !!},
            backgroundColor: {!! json_encode($btColors) !!},
            borderWidth: 3, borderColor: '#fff', hoverOffset: 6,
        }]
    },
    options: {
        cutout: '65%',
        plugins: {
            legend: { position:'bottom', labels:{ font:{size:10}, padding:8, boxWidth:10 } },
            tooltip: { callbacks: { label: ctx => 'GHS ' + ctx.parsed.toLocaleString('en-GH', {minimumFractionDigits:0}) } }
        }
    }
});

// Budget vs Actual by Type — bar
new Chart(document.getElementById('typeBar'), {
    type: 'bar',
    data: {
        labels:   {!! json_encode($btLabels) !!},
        datasets: [
            {
                label: 'Effective Budget',
                data:  {!! json_encode($btEffective) !!},
                backgroundColor: {!! json_encode($btColors) !!},
                borderRadius: 4, borderSkipped: false,
            },
            {
                label: 'Actual (YTD)',
                data:  {!! json_encode($btActuals) !!},
                backgroundColor: '#10B981',
                borderRadius: 4, borderSkipped: false,
            },
        ]
    },
    options: {
        responsive: true,
        plugins: { legend:{ position:'top', labels:{ font:{size:11}, boxWidth:12 } } },
        scales: {
            y: { beginAtZero:true, grid:{ color:'#F1F5F9' },
                 ticks:{ font:{size:10}, callback: fmt } },
            x: { grid:{ display:false }, ticks:{ font:{ size:10 } } }
        }
    }
});
</script>
@endif

<script>
/* ── Category toggle ── */
function toggleCat(id, hdrEl) {
    const rows = document.querySelectorAll('.ci-' + id);
    if (!rows.length) return;
    const isHidden = rows[0].style.display === 'none';
    rows.forEach(r => r.style.display = isHidden ? '' : 'none');
    const arrow = hdrEl.querySelector('.ct-arr');
    if (arrow) arrow.style.transform = isHidden ? '' : 'rotate(-90deg)';
}

/* ── Expand / Collapse all within a tab ── */
function expandAll(tabId) {
    document.querySelectorAll('#' + tabId + ' tr[class*="ci-' + tabId + '"]')
            .forEach(r => r.style.display = '');
    document.querySelectorAll('#' + tabId + ' .ct-arr')
            .forEach(a => a.style.transform = '');
}
function collapseAll(tabId) {
    document.querySelectorAll('#' + tabId + ' tr[class*="ci-' + tabId + '"]')
            .forEach(r => r.style.display = 'none');
    document.querySelectorAll('#' + tabId + ' .ct-arr')
            .forEach(a => a.style.transform = 'rotate(-90deg)');
}

/* ── Export helpers ── */
function _tabShowAll(tabId) {
    const hidden = [];
    document.querySelectorAll('#' + tabId + ' tr[class*="ci-' + tabId + '"]').forEach(r => {
        if (r.style.display === 'none') { hidden.push(r); r.style.display = ''; }
    });
    return hidden;
}
function _tabHide(hidden) { hidden.forEach(r => r.style.display = 'none'); }

function _tabRows(tabId) {
    const tbl = document.getElementById('tbl-' + tabId);
    if (!tbl) return [];
    const rows = [];
    tbl.querySelectorAll('tr').forEach(tr => {
        const cells = tr.querySelectorAll('th,td');
        if (!cells.length) return;
        // skip pure spacer rows (single td, no text)
        if (cells.length === 1 && !cells[0].textContent.trim()) return;
        const row = [];
        cells.forEach(td => row.push(td.textContent.trim().replace(/\s+/g, ' ')));
        rows.push(row);
    });
    return rows;
}

function tabExportCSV(tabId) {
    const hidden = _tabShowAll(tabId);
    const rows   = _tabRows(tabId);
    _tabHide(hidden);
    const csv = rows.map(r => r.map(c => '"' + c.replace(/"/g,'""') + '"').join(',')).join('\r\n');
    const a = document.createElement('a');
    a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
    a.download = tabId + '.csv';
    a.click();
}

function tabExportExcel(tabId) {
    const hidden = _tabShowAll(tabId);
    const tbl    = document.getElementById('tbl-' + tabId);
    if (!tbl) { _tabHide(hidden); return; }
    const xls = '<html xmlns:o="urn:schemas-microsoft-com:office:office" '
              + 'xmlns:x="urn:schemas-microsoft-com:office:excel">'
              + '<head><meta charset="UTF-8">'
              + '<style>th{background:#1B2A4A;color:#fff;font-weight:bold}'
              + 'td,th{border:1px solid #ccc;padding:4px 8px;font-size:11px}'
              + '</style></head><body>' + tbl.outerHTML + '</body></html>';
    _tabHide(hidden);
    const blob = new Blob([xls], { type: 'application/vnd.ms-excel' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = tabId + '.xls';
    a.click();
}

function tabPrint(tabId) {
    const hidden = _tabShowAll(tabId);
    const tbl    = document.getElementById('tbl-' + tabId);
    if (!tbl) { _tabHide(hidden); return; }
    const clone = tbl.cloneNode(true);
    _tabHide(hidden);
    const w = window.open('', '_blank', 'width=1100,height=700');
    w.document.write('<html><head><title>Budget — {{ $budgetVersion->department->name }} — {{ $budgetVersion->period->name }}</title>'
        + '<style>*{font-family:sans-serif}table{border-collapse:collapse;width:100%;font-size:10px}'
        + 'th,td{border:1px solid #ccc;padding:3px 6px}'
        + 'th{background:#1B2A4A;color:#fff}'
        + '@media print{@page{size:landscape}}</style></head>'
        + '<body>' + clone.outerHTML + '</body></html>');
    w.document.close();
    w.focus();
    w.print();
}
</script>

@endsection
