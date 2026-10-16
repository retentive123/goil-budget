@extends('layouts.app')
@section('title', 'Review Budget')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('approvals.index') }}" class="text-muted text-decoration-none">Approvals</a>
        <span class="text-muted">/</span>
        <span>{{ $budgetVersion->subsidiary?->name ?? $budgetVersion->department?->name }} — v{{ $budgetVersion->version_number }}</span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <div class="btn-group btn-group-sm" role="group">
            <span class="btn btn-secondary" style="pointer-events:none;">Classic View</span>
            <a href="{{ route('approvals.show-pnl', $budgetVersion) }}" class="btn btn-outline-secondary">P&amp;L View</a>
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

<div class="row g-4">

    {{-- Left: budget details --}}
    <div class="col-lg-9">

        {{-- Info bar --}}
        <div class="card shadow-sm mb-3">
            <div class="card-body py-2">
                <div class="row text-center">
                    <div class="col border-end">
                        <div class="small text-muted">Department / Entity</div>
                        <div class="fw-semibold small">{{ $budgetVersion->subsidiary?->name ?? $budgetVersion->department?->name }}</div>
                    </div>
                    <div class="col border-end">
                        <div class="small text-muted">Period</div>
                        <div class="fw-semibold small">{{ $budgetVersion->period->name }}</div>
                    </div>
                    <div class="col border-end">
                        <div class="small text-muted">Version</div>
                        <div class="fw-semibold small">v{{ $budgetVersion->version_number }}</div>
                    </div>
                    <div class="col border-end">
                        <div class="small text-muted">Submitted by</div>
                        <div class="fw-semibold small">{{ $budgetVersion->submittedBy?->name ?? '—' }}</div>
                    </div>
                    <div class="col">
                        <div class="small text-muted">Total Budget</div>
                        <div class="fw-bold text-success">{{ currency() }} {{ number_format($adjustedGrandTotal ?? $grandTotals['total'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Submission notes --}}
        @if($budgetVersion->submission_notes)
        <div class="alert alert-light border small mb-3">
            <strong>Submission notes:</strong> {{ $budgetVersion->submission_notes }}
        </div>
        @endif

        {{-- Split summary by budget type --}}
        @php
            // Effective total per item — honours any prior-stage reduction
            $effTotal = fn($item) => ($priorReductions ?? collect())->has($item->id)
                ? ($priorReductions ?? collect())->get($item->id)
                : $item->effectiveBudget();

            // Effective period amount per item per month — honours LIA period data
            $effMonth = function($item, int $mn) use ($priorReductionLias) {
                $lia = ($priorReductionLias ?? collect())->get($item->id);
                if ($lia && $lia->m1_amount !== null) {
                    return (float) ($lia->{'m'.$mn.'_amount'} ?? 0);
                }
                return (float) $item->{'m'.$mn.'_amount'};
            };
            $effQtr = function($item, string $qk) use ($priorReductionLias) {
                $lia = ($priorReductionLias ?? collect())->get($item->id);
                if ($lia && $lia->m1_amount !== null) {
                    $map = [
                        'q1' => $lia->m1_amount + $lia->m2_amount + $lia->m3_amount,
                        'q2' => $lia->m4_amount + $lia->m5_amount + $lia->m6_amount,
                        'q3' => $lia->m7_amount + $lia->m8_amount + $lia->m9_amount,
                        'q4' => $lia->m10_amount + $lia->m11_amount + $lia->m12_amount,
                    ];
                    return (float) ($map[$qk] ?? 0);
                }
                return (float) $item->{$qk.'_amount'};
            };

            // Adjusted grand total for the info bar
            $adjustedGrandTotal = $budgetVersion->lineItems->sum($effTotal);

            $pnlTypes       = ['revenue', 'expense', 'both'];
            $summaryPnl     = array_filter($summary, fn($d) => in_array(
                $d['items']->first()?->accountCode?->category?->budget_type ?? 'expense', $pnlTypes));
            $summaryCapex   = array_filter($summary, fn($d) =>
                ($d['items']->first()?->accountCode?->category?->budget_type ?? '') === 'capital_expenditure');
            $summaryBalance = array_filter($summary, fn($d) => in_array(
                $d['items']->first()?->accountCode?->category?->budget_type ?? '', ['assets', 'liabilities']));
            $aprvTabGroups = [
                ['id'=>'atab-pnl',     'label'=>'Revenue &amp; Expenses',   'summary'=>$summaryPnl,     'active'=>true],
                ['id'=>'atab-capex',   'label'=>'Capital Expenditure',      'summary'=>$summaryCapex,   'active'=>false],
                ['id'=>'atab-balance', 'label'=>'Assets &amp; Liabilities', 'summary'=>$summaryBalance, 'active'=>false],
            ];
        @endphp

        {{-- Tab navigation --}}
        <ul class="nav nav-tabs mb-0" id="aprvTabs" role="tablist">
            @foreach($aprvTabGroups as $atab)
            @if($atab['active'] || !empty($atab['summary']))
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $atab['active'] ? 'active' : '' }}"
                        data-bs-toggle="tab" data-bs-target="#{{ $atab['id'] }}"
                        type="button" role="tab">
                    {!! $atab['label'] !!}
                    @if(!empty($atab['summary']))
                    <span class="badge bg-secondary ms-1" style="font-size:10px">{{ count($atab['summary']) }}</span>
                    @endif
                </button>
            </li>
            @endif
            @endforeach
        </ul>

        <div class="tab-content border border-top-0 rounded-bottom mb-3" id="aprvTabsContent">
        @foreach($aprvTabGroups as $atab)
        @if($atab['active'] || !empty($atab['summary']))
        @php
            $tabGrandEff  = collect(array_values($atab['summary']))->sum(fn($c) => $c['items']->sum($effTotal));
            $tabGrandSupp = collect(array_values($atab['summary']))->sum(fn($c) => $c['items']->sum(fn($i) =>
                ($priorReductions ?? collect())->has($i->id) ? 0 : $i->approvedSupplementaryTotal()
            ));
        @endphp
        <div class="tab-pane fade {{ $atab['active'] ? 'show active' : '' }} p-3"
             id="{{ $atab['id'] }}" role="tabpanel">
            @forelse($atab['summary'] as $categoryName => $categoryData)
            @php
                $catEffectiveTotal = $categoryData['items']->sum($effTotal);
                $catSuppTotal = $categoryData['items']->sum(fn($item) =>
                    ($priorReductions ?? collect())->has($item->id) ? 0 : $item->approvedSupplementaryTotal()
                );
            @endphp
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light py-2 d-flex justify-content-between">
                    <span class="fw-semibold small text-uppercase">{{ $categoryName }}</span>
                    <span class="small text-muted">
                        Total: <strong>{{ currency() }} {{ number_format($catEffectiveTotal, 2) }}</strong>
                        @if($catSuppTotal > 0)
                        <span style="color:#10B981;font-size:11px;">
                            (+{{ number_format($catSuppTotal, 2) }} supp.)
                        </span>
                        @endif
                    </span>
                </div>
                <div class="card-body p-0" style="overflow-x:auto">
                    <table class="table table-sm table-hover mb-0" style="{{ $entryMode === 'monthly' ? 'min-width:1400px' : '' }}">
                        <thead class="table-light" style="position:sticky;top:0;z-index:2">
                            <tr>
                                <th style="width:{{ $calcMode !== 'none' ? '22%' : '30%' }}">Account</th>
                                @if($calcMode !== 'none')
                                <th class="text-end" style="color:#6D28D9" title="Quantity">Qty</th>
                                <th class="text-end" style="color:#6D28D9" title="Rate">Rate</th>
                                @if($calcMode === 'qty_rate_freq')
                                <th class="text-end" style="color:#6D28D9" title="Frequency">Freq</th>
                                @endif
                                <th class="text-end" style="color:#6D28D9">Year Total</th>
                                @endif
                                @if($entryMode === 'monthly')
                                    @foreach(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $ml)
                                    <th class="text-end" style="min-width:70px">{{ $ml }}</th>
                                    @endforeach
                                @else
                                    <th class="text-end">Q1</th>
                                    <th class="text-end">Q2</th>
                                    <th class="text-end">Q3</th>
                                    <th class="text-end">Q4</th>
                                @endif
                                <th class="text-end">Supplementary</th>
                                <th class="text-end">Total</th>
                                @if($canDecide)<th>Decision</th><th>Adjusted</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categoryData['items'] as $item)
                            @php
                                $itemSupp          = $item->approvedSupplementaryTotal();
                                $itemOrigEffective = $item->effectiveBudget(); // before any reduction
                                $priorReduction    = ($priorReductions ?? collect())->get($item->id);
                                $priorLia          = ($priorReductionLias ?? collect())->get($item->id);
                                // Cap: prior reduction (unless restore is allowed, then use original)
                                $itemEffective     = ($priorReduction !== null && !($allowRestore ?? false))
                                    ? $priorReduction
                                    : $itemOrigEffective;
                                $liaHasPeriods     = $priorLia && $priorLia->m1_amount !== null;
                                $liaQ              = $liaHasPeriods ? [
                                    'q1' => $priorLia->m1_amount + $priorLia->m2_amount + $priorLia->m3_amount,
                                    'q2' => $priorLia->m4_amount + $priorLia->m5_amount + $priorLia->m6_amount,
                                    'q3' => $priorLia->m7_amount + $priorLia->m8_amount + $priorLia->m9_amount,
                                    'q4' => $priorLia->m10_amount + $priorLia->m11_amount + $priorLia->m12_amount,
                                ] : null;
                                $itemQty  = $item->quantity  ?? '—';
                                $itemRate = $item->rate      ?? '—';
                                $itemFreq = $item->frequency ?? '—';
                                $itemYearTotal = ($item->quantity !== null && $item->rate !== null)
                                    ? ($item->quantity * $item->rate * ($calcMode === 'qty_rate_freq' ? ($item->frequency ?? 1) : 1))
                                    : null;
                            @endphp
                            <tr>
                                <td class="small">
                                    <code>{{ $item->accountCode->code }}</code>
                                    {{ $item->accountCode->name }}
                                    @if($item->justification)
                                        <div class="text-muted" style="font-size:11px">
                                            {{ $item->justification }}
                                        </div>
                                    @endif
                                </td>
                                @if($calcMode !== 'none')
                                <td class="text-end small" style="color:#6D28D9">
                                    {{ is_numeric($itemQty) ? number_format((float)$itemQty, 2) : $itemQty }}
                                </td>
                                <td class="text-end small" style="color:#6D28D9">
                                    {{ is_numeric($itemRate) ? number_format((float)$itemRate, 4) : $itemRate }}
                                </td>
                                @if($calcMode === 'qty_rate_freq')
                                <td class="text-end small" style="color:#6D28D9">
                                    {{ is_numeric($itemFreq) ? number_format((float)$itemFreq, 2) : $itemFreq }}
                                </td>
                                @endif
                                <td class="text-end small fw-semibold" style="color:#6D28D9">
                                    {{ $itemYearTotal !== null ? number_format($itemYearTotal, 2) : '—' }}
                                </td>
                                @endif
                                @if($entryMode === 'monthly')
                                    @foreach(range(1,12) as $mn)
                                    @php
                                        $cellAmt   = $liaHasPeriods ? (float)($priorLia->{'m'.$mn.'_amount'} ?? 0) : $item->{'m'.$mn.'_amount'};
                                        $cellStyle = $priorReduction !== null ? ($liaHasPeriods ? 'color:#B45309;font-weight:600' : 'color:#94A3B8') : '';
                                        $cellTitle = $priorReduction !== null ? ($liaHasPeriods ? 'Reduced by prior approver' : 'Original period; total reduced') : '';
                                    @endphp
                                    <td class="text-end small" style="{{ $cellStyle }}" title="{{ $cellTitle }}">{{ number_format($cellAmt, 2) }}</td>
                                    @endforeach
                                @else
                                    @foreach(['q1','q2','q3','q4'] as $qi => $qk)
                                    @php
                                        if ($liaHasPeriods && $liaQ) {
                                            $cellAmt   = $liaQ[$qk];
                                            $cellStyle = 'color:#B45309;font-weight:600';
                                            $cellTitle = 'Reduced by prior approver';
                                        } else {
                                            $cellAmt   = $item->{$qk.'_amount'};
                                            $cellStyle = $priorReduction !== null ? 'color:#94A3B8' : '';
                                            $cellTitle = $priorReduction !== null ? 'Original period; total reduced' : '';
                                        }
                                    @endphp
                                    <td class="text-end small" style="{{ $cellStyle }}" title="{{ $cellTitle }}">{{ number_format($cellAmt, 2) }}</td>
                                    @endforeach
                                @endif
                                <td class="text-end small" style="color:{{ $itemSupp > 0 ? '#10B981' : 'inherit' }}">
                                    {{ $itemSupp > 0 ? '+'.number_format($itemSupp, 2) : '—' }}
                                </td>
                                <td class="text-end small fw-semibold">
                                    @if($priorReduction !== null)
                                        <span style="text-decoration:line-through;color:#94A3B8;font-size:10px">{{ number_format($itemOrigEffective, 2) }}</span><br>
                                        <span style="color:#B45309" title="Reduced by prior approver">
                                            {{ number_format($priorReduction, 2) }}
                                            <i class="bi bi-arrow-down-circle-fill" style="font-size:9px"></i>
                                        </span>
                                    @else
                                        <span style="color:var(--navy)">{{ number_format($itemEffective, 2) }}</span>
                                    @endif
                                </td>
                                @if($canDecide)
                                @php
                                    $oldStatus  = old("line_items.{$item->id}.status", '');
                                    $hasSubs    = $item->subEntries->isNotEmpty();
                                @endphp
                                <td>
                                    <select name="line_items[{{ $item->id }}][status]"
                                        class="form-select form-select-sm li-decision"
                                        form="decision-form"
                                        data-item="{{ $item->id }}">
                                        <option value="">—</option>
                                        <option value="approved" @selected($oldStatus === 'approved')>Approve</option>
                                        @if(!$hasSubs)
                                        <option value="reduced"  @selected($oldStatus === 'reduced')>Reduce</option>
                                        @endif
                                        <option value="rejected" @selected($oldStatus === 'rejected')>Reject</option>
                                    </select>
                                    @if($hasSubs)
                                    <div class="text-muted" style="font-size:10px;margin-top:2px">Has sub-items; reduce not allowed</div>
                                    @endif
                                </td>
                                <td style="min-width:180px">
                                    @if(!$hasSubs)
                                    <input type="number"
                                        name="line_items[{{ $item->id }}][approved_amount]"
                                        class="form-control form-control-sm li-amount"
                                        form="decision-form"
                                        id="amount-{{ $item->id }}"
                                        placeholder="{{ currency() }}"
                                        min="0.01" step="0.01"
                                        max="{{ $itemEffective }}"
                                        value="{{ old("line_items.{$item->id}.approved_amount") }}"
                                        title="Enter a reduced amount (max: {{ currency() }} {{ number_format($itemEffective, 2) }})"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        style="{{ $oldStatus === 'reduced' ? 'display:block' : 'display:none' }}">

                                    {{-- Restore hint (restore allowed + item was previously reduced) --}}
                                    @if(($allowRestore ?? false) && $priorReduction !== null)
                                    <div style="font-size:9px;color:#6D28D9;margin-top:3px;display:none" id="restore-hint-{{ $item->id }}">
                                        <i class="bi bi-arrow-up-circle-fill" style="font-size:9px"></i>
                                        Can restore up to {{ currency() }} {{ number_format($itemOrigEffective, 2) }}
                                        (prior: {{ number_format($priorReduction, 2) }})
                                    </div>
                                    @endif

                                    {{-- Period breakdown (shown when Reduce is chosen) --}}
                                    <div id="periods-{{ $item->id }}"
                                         style="{{ $oldStatus === 'reduced' ? 'display:block' : 'display:none' }};margin-top:6px">
                                        <div style="font-size:9px;color:#64748B;font-weight:600;margin-bottom:3px;text-transform:uppercase;letter-spacing:.5px">
                                            Period Breakdown
                                        </div>
                                        @if($entryMode === 'monthly')
                                        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:2px">
                                            @foreach(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $mi => $ml)
                                            <div>
                                                <div style="font-size:8px;color:#94A3B8;text-align:center">{{ $ml }}</div>
                                                <input type="number"
                                                    name="line_items[{{ $item->id }}][period_amounts][m{{ $mi + 1 }}]"
                                                    class="form-control form-control-sm period-input"
                                                    data-period-for="{{ $item->id }}"
                                                    form="decision-form"
                                                    placeholder="0" min="0" step="0.01"
                                                    value="{{ old('line_items.' . $item->id . '.period_amounts.m' . ($mi + 1)) }}"
                                                    style="padding:2px 3px;font-size:10px;text-align:right">
                                            </div>
                                            @endforeach
                                        </div>
                                        @else
                                        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:3px">
                                            @foreach(['Q1','Q2','Q3','Q4'] as $qi => $ql)
                                            <div>
                                                <div style="font-size:8px;color:#94A3B8;text-align:center">{{ $ql }}</div>
                                                <input type="number"
                                                    name="line_items[{{ $item->id }}][period_amounts][q{{ $qi + 1 }}]"
                                                    class="form-control form-control-sm period-input"
                                                    data-period-for="{{ $item->id }}"
                                                    form="decision-form"
                                                    placeholder="0" min="0" step="0.01"
                                                    value="{{ old('line_items.' . $item->id . '.period_amounts.q' . ($qi + 1)) }}"
                                                    style="padding:2px 4px;font-size:11px;text-align:right">
                                            </div>
                                            @endforeach
                                        </div>
                                        @endif
                                        <div class="d-flex justify-content-between align-items-center" style="margin-top:4px">
                                            <button type="button"
                                                onclick="splitEqual('{{ $item->id }}')"
                                                style="font-size:9px;padding:1px 7px;border:1px solid #6D28D9;color:#6D28D9;background:#EDE9FE;border-radius:10px;cursor:pointer;line-height:1.6">
                                                ÷ Split Equal
                                            </button>
                                            <span style="font-size:10px;color:#94A3B8">
                                                Balance: <span id="period-balance-{{ $item->id }}" style="font-weight:600">—</span>
                                            </span>
                                        </div>
                                    </div>
                                    @endif
                                </td>
                                @endif
                            </tr>
                            @foreach($item->subEntries as $si => $sub)
                            @php
                                $subQ1 = $sub->m1_amount + $sub->m2_amount + $sub->m3_amount;
                                $subQ2 = $sub->m4_amount + $sub->m5_amount + $sub->m6_amount;
                                $subQ3 = $sub->m7_amount + $sub->m8_amount + $sub->m9_amount;
                                $subQ4 = $sub->m10_amount + $sub->m11_amount + $sub->m12_amount;
                                $subQty  = $sub->quantity  ?? '—';
                                $subRate = $sub->rate      ?? '—';
                                $subFreq = $sub->frequency ?? '—';
                            @endphp
                            <tr style="background:#F0F4FF;border-left:3px solid #818CF8">
                                <td style="padding-left:28px;font-size:11px">
                                    <span style="color:#818CF8;font-weight:700;margin-right:4px">{{ $si + 1 }}</span>
                                    <span style="color:#4338CA">↳ {{ $sub->name }}</span>
                                </td>
                                @if($calcMode !== 'none')
                                <td class="text-end small" style="color:#818CF8">
                                    {{ is_numeric($subQty) ? number_format((float)$subQty, 2) : $subQty }}
                                </td>
                                <td class="text-end small" style="color:#818CF8">
                                    {{ is_numeric($subRate) ? number_format((float)$subRate, 4) : $subRate }}
                                </td>
                                @if($calcMode === 'qty_rate_freq')
                                <td class="text-end small" style="color:#818CF8">
                                    {{ is_numeric($subFreq) ? number_format((float)$subFreq, 2) : $subFreq }}
                                </td>
                                @endif
                                <td class="text-end small fw-semibold" style="color:#818CF8">{{ number_format($sub->total_amount, 2) }}</td>
                                @endif
                                @if($entryMode === 'monthly')
                                    @foreach(range(1,12) as $mn)
                                    <td class="text-end small" style="color:#818CF8">{{ number_format($sub->{'m'.$mn.'_amount'}, 2) }}</td>
                                    @endforeach
                                @else
                                    <td class="text-end small" style="color:#818CF8">{{ number_format($subQ1, 2) }}</td>
                                    <td class="text-end small" style="color:#818CF8">{{ number_format($subQ2, 2) }}</td>
                                    <td class="text-end small" style="color:#818CF8">{{ number_format($subQ3, 2) }}</td>
                                    <td class="text-end small" style="color:#818CF8">{{ number_format($subQ4, 2) }}</td>
                                @endif
                                <td></td>
                                <td class="text-end small fw-semibold" style="color:#4338CA">{{ number_format($sub->total_amount, 2) }}</td>
                                @if($canDecide)
                                <td colspan="2"></td>
                                @endif
                            </tr>
                            @endforeach
                            @endforeach
                        </tbody>
                        <tfoot style="background:#F8FAFC;font-weight:700;font-size:11px">
                            <tr>
                                <td>Category Total</td>
                                @if($calcMode !== 'none')
                                <td colspan="{{ $calcMode === 'qty_rate_freq' ? 3 : 2 }}"></td>
                                <td class="text-end" style="color:#6D28D9">
                                    {{ number_format($categoryData['items']->sum('total_amount'), 2) }}
                                </td>
                                @endif
                                @if($entryMode === 'monthly')
                                    @foreach(range(1,12) as $mn)
                                    <td class="text-end">{{ number_format($categoryData['items']->sum(fn($i) => $effMonth($i, $mn)), 2) }}</td>
                                    @endforeach
                                @else
                                    @foreach(['q1','q2','q3','q4'] as $qk)
                                    <td class="text-end">{{ number_format($categoryData['items']->sum(fn($i) => $effQtr($i, $qk)), 2) }}</td>
                                    @endforeach
                                @endif
                                <td class="text-end" style="color:{{ $catSuppTotal > 0 ? '#10B981' : 'inherit' }}">
                                    {{ $catSuppTotal > 0 ? '+'.number_format($catSuppTotal, 2) : '—' }}
                                </td>
                                <td class="text-end" style="color:var(--navy)">
                                    {{ currency() }} {{ number_format($catEffectiveTotal, 2) }}
                                </td>
                                @if($canDecide)<td colspan="2"></td>@endif
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @empty
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox d-block mb-2" style="font-size:2rem;opacity:.3"></i>
                No items in this section yet.
            </div>
            @endforelse

            {{-- Tab grand total --}}
            @if(!empty($atab['summary']))
            <div class="card shadow-sm" style="background:var(--navy);color:#fff;border:none;">
                <div class="card-body py-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <div style="font-size:13px;color:rgba(255,255,255,.6)">
                                {!! $atab['label'] !!} — Total
                                @if($tabGrandSupp > 0)
                                <span style="color:#10B981;font-size:12px;">
                                    (incl. {{ number_format($tabGrandSupp, 2) }} supplementary)
                                </span>
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <div style="font-size:22px;font-weight:700;color:var(--gold)">
                                {{ currency() }} {{ number_format($tabGrandEff, 2) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
        @endif
        @endforeach
        </div>

    </div>

    {{-- Right: approval progress + decision form --}}
    <div class="col-lg-3">

        {{-- Approval progress --}}
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Approval Progress</h6>
                @foreach($progress as $step)
                <div class="d-flex align-items-start gap-2 mb-3">
                    <div class="mt-1">
                        @if($step['status'] === 'approved')
                            <i class="bi bi-check-circle-fill text-success" style="font-size:16px"></i>
                        @elseif($step['status'] === 'rejected')
                            <i class="bi bi-x-circle-fill text-danger" style="font-size:16px"></i>
                        @elseif($step['status'] === 'pending')
                            <i class="bi bi-hourglass-split text-warning" style="font-size:16px"></i>
                        @else
                            <i class="bi bi-circle text-muted" style="font-size:16px"></i>
                        @endif
                    </div>
                    <div class="flex-grow-1">
                        <div class="small fw-semibold d-flex align-items-center gap-2">
                            {{ $step['stage']->name }}
                            @if(!$step['is_active'])
                            <span style="background:#F1F5F9;color:#94A3B8;font-size:10px;
                                        border-radius:4px;padding:1px 6px;font-weight:400">
                                Inactive
                            </span>
                            @endif
                        </div>
                        @if($step['decision'])
                            <div class="small text-muted">
                                {{ $step['decision']->decidedBy->name }}
                                &middot; {{ $step['decision']->decided_at->format('d M Y H:i') }}
                            </div>
                            @if($step['decision']->comments)
                            <div class="small text-muted fst-italic mt-1">
                                "{{ $step['decision']->comments }}"
                            </div>
                            @endif
                        @elseif($step['status'] === 'pending')
                            <div class="small text-warning">Awaiting decision</div>
                        @elseif(!$step['is_active'])
                            <div class="small text-muted">Skipped (stage inactive)</div>
                        @else
                            <div class="small text-muted">Not yet reached</div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ── Decision Form ── --}}
        @if($canDecide)
            @php $roleConfig = $approvalService->currentRoleConfig($budgetVersion) ?? null; @endphp
            <div class="card border-0 shadow mb-4" style="border-radius:12px;overflow:hidden;border-top:3px solid #E65C00">
                <div class="card-header border-0 px-3 py-3" style="background:#E65C00;color:#fff">
                    <div class="fw-bold" style="font-size:14px">Your Decision — {{ $currentStage->name }}</div>
                    @if($roleConfig)
                    <div style="font-size:11px;color:rgba(255,255,255,.75);margin-top:3px">
                        Scope: {{ $roleConfig->scope === 'all' ? 'All departments' : 'Own department only' }}
                        @if($roleConfig->can_partial_approve) &middot; Partial approval enabled @endif
                        @if($roleConfig->can_reduce_amounts) &middot; Can reduce amounts @endif
                    </div>
                    @endif
                </div>

                <div class="card-body p-3">
                    <form id="decision-form" method="POST" action="{{ route('approvals.decide', $budgetVersion) }}">
                        @csrf

                        {{-- Decision toggle cards --}}
                        <div class="mb-3">
                            <div class="small fw-semibold mb-2" style="color:#64748B">Decision</div>
                            <div class="d-flex gap-2">
                                <label class="dec-card flex-fill text-center py-2 px-2 rounded-2"
                                       for="dec-approve" id="lbl-approve"
                                       style="cursor:pointer;border:2px solid #BBF7D0;background:#F0FDF4;color:#15803D;font-weight:600;font-size:13px;transition:all .15s">
                                    <input type="radio" name="decision" id="dec-approve" value="approved" class="d-none" required>
                                    Approve
                                </label>
                                <label class="dec-card flex-fill text-center py-2 px-2 rounded-2"
                                       for="dec-reject" id="lbl-reject"
                                       style="cursor:pointer;border:2px solid #FECDD3;background:#FFF1F2;color:#BE123C;font-weight:600;font-size:13px;transition:all .15s">
                                    <input type="radio" name="decision" id="dec-reject" value="rejected" class="d-none">
                                    Reject
                                </label>
                            </div>
                        </div>

                        {{-- Comments --}}
                        <div class="mb-3">
                            <label class="small fw-semibold mb-1 d-block" style="color:#64748B">
                                Comments
                                <span class="text-danger fw-normal" id="comments-required" style="display:none">(required)</span>
                            </label>
                            <textarea name="comments" rows="3"
                                    class="form-control @error('comments') is-invalid @enderror"
                                    style="border-radius:8px;border-color:#E2E8F0;padding:10px;resize:vertical;font-size:13px"
                                    placeholder="Add comments…">{{ old('comments') }}</textarea>
                            @error('comments')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Submit Button --}}
                        <button type="button" id="submit-decision-btn" class="btn w-100 py-2 fw-semibold"
                                style="background:#E65C00;color:#fff;border-radius:8px;border:none;font-size:14px">
                            Submit Decision
                        </button>
                    </form>
                </div>
            </div>

        {{-- ── Cannot Action / Access Denied ── --}}
        @else
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px; border: 2px solid #E2E8F0; background: #F8FAFC;">
                <div class="card-body p-4 text-center">
                    <div style="font-size: 42px; margin-bottom: 12px; color:#94A3B8"><i class="bi bi-lock-fill"></i></div>
                    <div style="font-size: 15px; font-weight: 700; color: #1B2A4A; margin-bottom: 4px;">
                        You cannot action this budget
                    </div>
                    <div style="font-size: 13px; color: #64748B; margin-bottom: 16px;">
                        {{ $currentStage?->name ?? 'No active stage' }}
                    </div>

                    @php
                        $version    = $budgetVersion;
                        $isSelf     = \App\Services\SegregationService::enabled()
                            && $version->submitted_by === auth()->id();
                        $wrongRole  = !auth()->user()->hasRole($currentStage?->role_name ?? '');
                        $wrongScope = false;

                        if ($currentStage) {
                            $role = \Spatie\Permission\Models\Role::where('name', $currentStage->role_name)->first();
                            if ($role && $role->scope === 'own') {
                                $authUser = auth()->user();
                                if ($version->subsidiary_id !== null) {
                                    $wrongScope = $authUser->subsidiary_id !== $version->subsidiary_id;
                                } else {
                                    $authUser->loadMissing('approvalDepartments');
                                    $wrongScope = !in_array($version->department_id, $authUser->approvalDepartmentIds());
                                }
                            }
                        }
                    @endphp

                    <div class="text-start">
                        @if($isSelf)
                        <div class="d-flex align-items-start gap-2 p-3 rounded-3 mb-2" style="background: #FEE2E2; border-left: 4px solid #F43F5E;">
                            <i class="bi bi-shield-exclamation" style="color: #991B1B; font-size: 18px;"></i>
                            <div>
                                <div style="font-weight: 600; color: #991B1B; font-size: 13px;">Segregation of Duties</div>
                                <div style="font-size: 12px; color: #7F1D1D;">You submitted this budget. A different user must approve it.</div>
                            </div>
                        </div>
                        @endif

                        @if($wrongRole)
                        <div class="d-flex align-items-start gap-2 p-3 rounded-3 mb-2" style="background: #FEF3C7; border-left: 4px solid #F59E0B;">
                            <i class="bi bi-exclamation-triangle" style="color: #92400E; font-size: 18px;"></i>
                            <div>
                                <div style="font-weight: 600; color: #92400E; font-size: 13px;">Role Mismatch</div>
                                <div style="font-size: 12px; color: #78350F;">Your role does not match the current approval stage ({{ $currentStage?->name }}).</div>
                            </div>
                        </div>
                        @endif

                        @if($wrongScope)
                        <div class="d-flex align-items-start gap-2 p-3 rounded-3" style="background: #FEF3C7; border-left: 4px solid #F59E0B;">
                            <i class="bi bi-building" style="color: #92400E; font-size: 18px;"></i>
                            <div>
                                <div style="font-weight: 600; color: #92400E; font-size: 13px;">Department Scope</div>
                                <div style="font-size: 12px; color: #78350F;">This budget belongs to a different department and your role only covers your own department.</div>
                            </div>
                        </div>
                        @endif

                        @if(!$isSelf && !$wrongRole && !$wrongScope)
                        <div class="d-flex align-items-start gap-2 p-3 rounded-3" style="background: #F1F5F9; border-left: 4px solid #94A3B8;">
                            <i class="bi bi-info-circle" style="color: #475569; font-size: 18px;"></i>
                            <div>
                                <div style="font-weight: 600; color: #475569; font-size: 13px;">Not Actionable</div>
                                <div style="font-size: 12px; color: #64748B;">This budget is not at a stage you can action, or has already been processed.</div>
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- View Only Button --}}
                    <div class="mt-3">
                        <a href="{{ route('budget.show', $budgetVersion) }}" class="btn btn-outline-secondary btn-sm px-4" style="border-radius: 8px;">
                            <i class="bi bi-eye"></i> View Budget
                        </a>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>

<script>
// Initialise Bootstrap tooltips on amount inputs (only if bootstrap is a global)
if (typeof bootstrap !== 'undefined') {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el);
    });
}

// Show/hide reduced amount field and period inputs based on line item decision
document.querySelectorAll('.li-decision').forEach(sel => {
    sel.addEventListener('change', function () {
        const itemId     = this.dataset.item;
        const amountField  = document.getElementById('amount-' + itemId);
        const periodsDiv   = document.getElementById('periods-' + itemId);
        const restoreHint  = document.getElementById('restore-hint-' + itemId);
        if (!amountField) return;
        const isReduced = this.value === 'reduced';
        amountField.style.display = isReduced ? 'block' : 'none';
        if (periodsDiv)  periodsDiv.style.display  = isReduced ? 'block' : 'none';
        if (restoreHint) restoreHint.style.display = isReduced ? 'block' : 'none';
        if (isReduced) amountField.focus();
        checkPeriodBalance(itemId);
    });
});

// Live period balance indicator
function checkPeriodBalance(itemId) {
    const amountField = document.getElementById('amount-' + itemId);
    const balanceEl   = document.getElementById('period-balance-' + itemId);
    if (!amountField || !balanceEl) return;
    const target = parseFloat(amountField.value) || 0;
    let sum = 0;
    document.querySelectorAll('.period-input[data-period-for="' + itemId + '"]').forEach(inp => {
        sum += parseFloat(inp.value) || 0;
    });
    sum = Math.round(sum * 100) / 100;
    const diff = Math.abs(sum - target);
    const ok   = target > 0 && diff <= 0.01;
    balanceEl.textContent = sum.toFixed(2) + ' / ' + target.toFixed(2);
    balanceEl.style.color = ok ? '#10B981' : (sum === 0 && target === 0 ? '#94A3B8' : '#EF4444');
}

function splitEqual(itemId) {
    const amountField = document.getElementById('amount-' + itemId);
    if (!amountField) return;
    const total = Math.round(parseFloat(amountField.value || 0) * 100) / 100;
    if (total <= 0) return;
    const inputs = [...document.querySelectorAll('.period-input[data-period-for="' + itemId + '"]')];
    if (!inputs.length) return;
    const n    = inputs.length;
    const each = Math.floor(total / n * 100) / 100;
    const last = Math.round((total - each * (n - 1)) * 100) / 100;
    inputs.forEach((inp, i) => { inp.value = i === n - 1 ? last : each; });
    checkPeriodBalance(itemId);
}

document.querySelectorAll('.period-input').forEach(inp => {
    inp.addEventListener('input', () => checkPeriodBalance(inp.dataset.periodFor));
});

// Real-time red border on amount inputs when value is invalid
function validateAmountInput(inp) {
    const max = parseFloat(inp.getAttribute('max'));
    const val = parseFloat(inp.value);
    const invalid = inp.value !== '' && (isNaN(val) || val <= 0 || (!isNaN(max) && val >= max));
    inp.style.borderColor  = invalid ? '#EF4444' : '';
    inp.style.background   = invalid ? '#FFF1F2' : '';
    inp.style.color        = invalid ? '#991B1B' : '';
}
document.querySelectorAll('.li-amount').forEach(inp => {
    inp.addEventListener('input', () => {
        validateAmountInput(inp);
        checkPeriodBalance(inp.id.replace('amount-', ''));
    });
    // Highlight immediately if page reloaded with an invalid old value
    if (inp.value !== '') validateAmountInput(inp);
});

// Initial balance check for pages loaded with old values after failed submit
document.querySelectorAll('.li-amount').forEach(inp => {
    if (inp.value !== '') checkPeriodBalance(inp.id.replace('amount-', ''));
});

// Decision toggle cards — highlight selected, show comments hint on reject
document.querySelectorAll('input[name="decision"]').forEach(radio => {
    radio.addEventListener('change', function () {
        document.getElementById('comments-required').style.display =
            this.value === 'rejected' ? 'inline' : 'none';
        // Active styles
        const lblApprove = document.getElementById('lbl-approve');
        const lblReject  = document.getElementById('lbl-reject');
        if (lblApprove) {
            const on = this.value === 'approved';
            lblApprove.style.background = on ? '#16A34A' : '#F0FDF4';
            lblApprove.style.color      = on ? '#fff'    : '#15803D';
            lblApprove.style.borderColor= on ? '#16A34A' : '#BBF7D0';
        }
        if (lblReject) {
            const on = this.value === 'rejected';
            lblReject.style.background = on ? '#BE123C' : '#FFF1F2';
            lblReject.style.color      = on ? '#fff'    : '#BE123C';
            lblReject.style.borderColor= on ? '#BE123C' : '#FECDD3';
        }
    });
});

// SweetAlert2 confirmation before submitting the decision
document.getElementById('submit-decision-btn')?.addEventListener('click', function () {
    const form     = document.getElementById('decision-form');
    const selected = form.querySelector('input[name="decision"]:checked');

    if (!selected) {
        Swal.fire({
            icon:             'warning',
            title:            'No decision selected',
            text:             'Please choose Approve or Reject before submitting.',
            confirmButtonColor: '#E65C00',
        });
        return;
    }

    const isApprove = selected.value === 'approved';
    const comments  = (form.querySelector('textarea[name="comments"]')?.value ?? '').trim();

    if (!isApprove && !comments) {
        Swal.fire({
            icon:             'warning',
            title:            'Comments required',
            text:             'Please add a reason before rejecting.',
            confirmButtonColor: '#E65C00',
        });
        return;
    }

    Swal.fire({
        title:              isApprove ? 'Approve this budget?' : 'Reject this budget?',
        text:               isApprove
                                ? 'This will advance the budget through the approval workflow.'
                                : 'The department will be notified to revise and resubmit.',
        icon:               isApprove ? 'question' : 'warning',
        showCancelButton:   true,
        confirmButtonText:  isApprove ? 'Yes, Approve' : 'Yes, Reject',
        cancelButtonText:   'Cancel',
        confirmButtonColor: isApprove ? '#10B981' : '#EF4444',
        cancelButtonColor:  '#64748B',
        reverseButtons:     true,
        focusCancel:        true,
    }).then(function (result) {
        if (result.isConfirmed) {
            form.submit();
        }
    });
});
</script>

@endsection
