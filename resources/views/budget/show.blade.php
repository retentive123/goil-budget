@extends('layouts.app')
@section('title', 'Budget Entry')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="fw-bold mb-0">
            {{ $budgetVersion->ownerName() }}
            @if($budgetVersion->department?->isServiceStation())
                <span class="badge ms-1" style="background:#EFF6FF;color:#1D4ED8;font-size:10px;font-weight:600;">Station</span>
            @endif
            @if($budgetVersion->subsidiary)
                <span class="badge ms-1" style="background:#EDE9FE;color:#5B21B6;font-size:10px;font-weight:600;">Subsidiary</span>
            @endif
            — {{ $budgetVersion->period->name }}
            <span class="badge bg-goil-orange ms-1">v{{ $budgetVersion->version_number }}</span>
        </h5>
        <p class="text-muted small mb-0">
            Status:
            <span class="badge bg-{{
                match($budgetVersion->status) {
                    'draft'        => 'secondary',
                    'submitted'    => 'primary',
                    'under_review' => 'warning',
                    'approved'     => 'success',
                    'rejected'     => 'danger',
                    default        => 'secondary'
                }
            }}">{{ ucfirst(str_replace('_',' ',$budgetVersion->status)) }}</span>
            @if($budgetVersion->is_revision)
            <span class="badge ms-1" style="background:#7C3AED;color:#fff;font-size:10px">
                <i class="bi bi-pencil-square me-1"></i>Revision
            </span>
            @if($budgetVersion->originalVersion)
            <span class="text-muted" style="font-size:11px">
                — revised from
                <a href="{{ route('budgets.show', $budgetVersion->originalVersion) }}"
                   class="text-muted">v{{ $budgetVersion->originalVersion->version_number }}</a>
            </span>
            @endif
            @endif
            &nbsp;
            @if($calcMode !== 'none')
                <span class="badge" style="background:#7C3AED;font-size:10px;color:#fff;">
                    {{ $calcMode === 'qty_rate_freq' ? 'Qty × Rate × Freq' : 'Qty × Rate' }}
                </span>
            @else
                <span class="badge" style="background:{{ $entryMode === 'monthly' ? '#0369A1' : '#6B7280' }};font-size:10px;">
                    {{ $entryMode === 'monthly' ? 'Monthly entry' : 'Quarterly entry' }}
                </span>
            @endif
        </p>
    </div>

    <div class="d-flex gap-2 align-items-center flex-wrap">
        <div class="btn-group btn-group-sm" role="group" aria-label="View mode">
            <span class="btn btn-secondary" style="pointer-events:none;">Classic View</span>
            <a href="{{ route('budget.show-pnl', $budgetVersion) }}" class="btn btn-outline-secondary">P&amp;L View</a>
        </div>
        <button class="btn btn-sm btn-outline-secondary" onclick="window.print()" title="Print budget summary">
            <i class="bi bi-printer"></i> Print
        </button>
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
        @if($budgetVersion->isEditable())
        <span id="save-status" class="text-muted small"></span>
        <button id="save-btn" class="btn btn-outline-primary btn-sm" onclick="saveBudget()">Save</button>
        <a href="{{ route('budget.confirm', $budgetVersion) }}"
           id="submit-btn"
           class="btn bg-goil-orange btn-sm">
            Submit for Approval →
        </a>
        @endif
        @if($budgetVersion->status === 'approved' &&
            (!$budgetVersion->is_revision || \App\Models\SystemSetting::get('allow_revision_of_revision', false)))
        @can('submit budget')
        <a href="{{ route('budgets.revise.create', $budgetVersion) }}"
           class="btn btn-sm btn-outline-warning"
           title="{{ $budgetVersion->is_revision ? 'Create a further revision of this approved revision' : 'Create a mid-year revision of this approved budget' }}">
            <i class="bi bi-pencil-square me-1"></i>{{ $budgetVersion->is_revision ? 'Revise Again' : 'Revise Budget' }}
        </a>
        @endcan
        @endif
    </div>
</div>

{{-- Grand total bar --}}
@php
    $lineItems = $budgetVersion->lineItems ?? collect();
    $totalSupplementary = $lineItems->sum(fn($i) => $i->approvedSupplementaryTotal());
    $effectiveTotal = $grandTotals['total'];
@endphp

@php
    $gtIsMonthly  = ($entryMode ?? 'quarterly') === 'monthly';
    $gtPeriodCols = $gtIsMonthly
        ? ['m1'=>'Jan','m2'=>'Feb','m3'=>'Mar','m4'=>'Apr','m5'=>'May','m6'=>'Jun',
           'm7'=>'Jul','m8'=>'Aug','m9'=>'Sep','m10'=>'Oct','m11'=>'Nov','m12'=>'Dec']
        : ['q1'=>'Q1','q2'=>'Q2','q3'=>'Q3','q4'=>'Q4'];
@endphp
<div class="card mb-3 border-0 bg-goil-orange" style="{{ $gtIsMonthly ? 'overflow-x:auto;' : '' }}">
    <div class="card-body py-2" style="{{ $gtIsMonthly ? 'min-width:900px;' : '' }}">
        <div class="row text-center flex-nowrap">
            @foreach($gtPeriodCols as $pk => $pl)
            <div class="col">
                <div class="small text-white-50">{{ $pl }}</div>
                <div class="fw-bold {{ $gtIsMonthly ? '' : '' }}" id="gt-{{ $pk }}"
                     style="{{ $gtIsMonthly ? 'font-size:12px;' : '' }}">
                    {{ currency() }} {{ number_format($grandTotals[$pk] ?? 0, 2) }}
                </div>
            </div>
            @endforeach
            <div class="col border-start border-secondary">
                <div class="small text-white-50">Original Total</div>
                <div class="fw-bold" id="gt-total">{{ currency() }} {{ number_format($grandTotals['total'], 2) }}</div>
                @if($totalSupplementary > 0)
                <div class="small" style="color:#6EE7B7;">+{{ currency() }} {{ number_format($totalSupplementary, 2) }} supp.</div>
                @endif
            </div>
            <div class="col border-start border-secondary">
                <div class="small text-white-50">Effective Total</div>
                <div class="fw-bold fs-5" style="color:var(--gold);" id="gt-effective">
                    {{ currency() }} {{ number_format($effectiveTotal, 2) }}
                </div>
                @if($totalSupplementary > 0)
                <div class="small" style="color:rgba(255,255,255,0.5);">
                    incl. {{ number_format($totalSupplementary, 2) }} supplementary
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($budgetVersion->isEditable())
{{-- Import/Export panel --}}
<div class="chart-card mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <div style="font-size:13px;font-weight:600;color:var(--navy)">Excel Import / Export</div>
            <div style="font-size:12px;color:var(--slate)">
                Download the template, fill it in Excel, then upload to save time.
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('ie.budget.download', $budgetVersion) }}" class="btn btn-sm btn-outline-success">
                ↓ Download Template
            </a>
            <button type="button" onclick="document.getElementById('uploadPanel').classList.toggle('d-none')"
                    class="btn btn-sm btn-outline-primary">
                ↑ Upload Excel
            </button>
        </div>
    </div>

    <div id="uploadPanel" class="d-none mt-3 pt-3 border-top">
        <form method="POST" action="{{ route('ie.budget.upload', $budgetVersion) }}" enctype="multipart/form-data">
            @csrf
            <div class="d-flex gap-2 align-items-end">
                <div class="flex-grow-1">
                    <label class="form-label small fw-semibold mb-1">Select filled Excel file</label>
                    <input type="file" name="file" accept=".xlsx,.xls" class="form-control form-control-sm">
                </div>
                <button type="submit" class="btn btn-sm btn-primary">Upload & Save</button>
            </div>
            <div class="form-text">Only .xlsx and .xls files accepted. Max 5MB. Use the downloaded template.</div>
        </form>
    </div>

    @if(session('import_errors'))
    <div class="mt-3 pt-3 border-top">
        <div style="font-size:12px;font-weight:600;color:#991B1B;margin-bottom:6px">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>Import Errors:
        </div>
        @foreach(session('import_errors') as $err)
        <div style="font-size:11px;color:#991B1B;padding:2px 0">
            <i class="bi bi-x-circle me-1"></i>{{ $err }}
        </div>
        @endforeach
    </div>
    @endif

    @if(session('admin_override_note'))
    <div class="mt-3 pt-3 border-top d-flex align-items-start gap-2"
         style="background:#FEF3C7;border-radius:8px;padding:10px 12px;margin-top:8px!important">
        <i class="bi bi-lock-fill" style="color:#92400E;flex-shrink:0;margin-top:1px"></i>
        <div style="font-size:12px;color:#92400E">
            <strong>Admin-locked values ignored:</strong>
            {{ session('admin_override_note') }}
        </div>
    </div>
    @endif
</div>
@endif

{{-- ──────────────────────────────────────────────────────────
     Calc-mode legend (shown when not 'none')
     ────────────────────────────────────────────────────────── --}}
@if($calcMode !== 'none' && $budgetVersion->isEditable())
<div class="alert mb-3 d-flex align-items-start gap-3"
     style="background:#F5F3FF;border:1px solid #C4B5FD;border-radius:10px;
            color:#4C1D95;font-size:13px;">
    <div style="font-size:22px;line-height:1">🧮</div>
    <div>
        <div class="fw-semibold mb-1">
            @if($calcMode === 'qty_rate_freq')
                Budget amounts are computed as <strong>Quantity × Rate × Frequency</strong>.
            @else
                Budget amounts are computed as <strong>Quantity × Rate</strong>.
            @endif
            @if($manualSplit)
                You can manually distribute the total across {{ $entryMode === 'monthly' ? 'months' : 'quarters' }} — the split must balance.
            @else
                The annual total is spread equally across all 12 months.
            @endif
        </div>
        <div style="font-size:12px;opacity:.8">
            @if($adminSetsRate && $adminSetsFreq)
                Rate and Frequency are set by your administrator and cannot be changed.
            @elseif($adminSetsRate)
                Rate is set by your administrator and cannot be changed. Enter Quantity (and Frequency if shown).
            @elseif($adminSetsFreq && $calcMode === 'qty_rate_freq')
                Frequency is set by your administrator and cannot be changed. Enter Quantity and Rate.
            @else
                Enter Quantity{{ $calcMode === 'qty_rate_freq' ? ', Rate, and Frequency' : ' and Rate' }} for each line item.
            @endif
            @if($manualSplit)
                <strong>Manual split mode:</strong>
                {{ $entryMode === 'monthly' ? 'Month' : 'Quarter' }} totals must sum to the computed year total.
            @endif
        </div>
    </div>
</div>
@endif

{{-- Split summary by budget type --}}
@php
    $pnlTypes       = ['revenue', 'expense', 'both'];
    $summaryPnl     = array_filter($summary, fn($d) => in_array(
        $d['items']->first()?->accountCode?->category?->budget_type ?? 'expense', $pnlTypes));
    $summaryCapex   = array_filter($summary, fn($d) =>
        ($d['items']->first()?->accountCode?->category?->budget_type ?? '') === 'capital_expenditure');
    $summaryBalance = array_filter($summary, fn($d) => in_array(
        $d['items']->first()?->accountCode?->category?->budget_type ?? '', ['assets', 'liabilities']));
    $budgetTabGroups = [
        ['id'=>'tab-pnl',     'label'=>'Revenue &amp; Expenses',   'summary'=>$summaryPnl,     'active'=>true],
        ['id'=>'tab-capex',   'label'=>'Capital Expenditure',      'summary'=>$summaryCapex,   'active'=>false],
        ['id'=>'tab-balance', 'label'=>'Assets &amp; Liabilities', 'summary'=>$summaryBalance, 'active'=>false],
    ];
@endphp

{{-- Tab navigation --}}
<ul class="nav nav-tabs mb-0" id="budgetTabs" role="tablist">
    @foreach($budgetTabGroups as $btab)
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $btab['active'] ? 'active' : '' }}"
                data-bs-toggle="tab" data-bs-target="#{{ $btab['id'] }}"
                type="button" role="tab">
            {!! $btab['label'] !!}
            @if(!empty($btab['summary']))
            <span class="badge bg-secondary ms-1" style="font-size:10px">{{ count($btab['summary']) }}</span>
            @endif
        </button>
    </li>
    @endforeach
</ul>

<div class="tab-content border border-top-0 rounded-bottom mb-3" id="budgetTabsContent">
@foreach($budgetTabGroups as $btab)
<div class="tab-pane fade {{ $btab['active'] ? 'show active' : '' }} p-0"
     id="{{ $btab['id'] }}" role="tabpanel">
    @php $monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']; @endphp
    @if(!empty($btab['summary']))
    <div class="card shadow-sm border-0">
        <div class="card-body p-0" style="overflow-x:auto;">
            <table class="table table-sm table-hover mb-0"
                   style="{{ $calcMode !== 'none' ? 'min-width:900px;' : ($entryMode === 'monthly' ? 'min-width:1400px;' : '') }}">
                <thead class="table-light sticky-top" style="z-index:1">
                    <tr>
                        <th style="min-width:180px;">Account</th>

                        @if($calcMode !== 'none')
                            {{-- ── Qty × Rate [× Freq] headers ── --}}
                            <th class="text-end" style="min-width:90px;">
                                Qty
                            </th>
                            <th class="text-end" style="min-width:110px;">
                                Rate
                                @if($adminSetsRate)
                                    <i class="bi bi-lock-fill" title="Set by admin" style="cursor:help;opacity:.55;font-size:11px"></i>
                                @endif
                            </th>
                            @if($calcMode === 'qty_rate_freq')
                            <th class="text-end" style="min-width:90px;">
                                Freq
                                @if($adminSetsFreq)
                                    <i class="bi bi-lock-fill" title="Set by admin" style="cursor:help;opacity:.55;font-size:11px"></i>
                                @endif
                            </th>
                            @endif
                            <th class="text-end" style="min-width:130px;">Year Total</th>
                            @if($entryMode === 'monthly')
                                @foreach(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $ml)
                                <th class="text-end" style="min-width:80px;">{{ $ml }}{{ $manualSplit ? ' ✎' : '' }}</th>
                                @endforeach
                            @else
                            <th class="text-end" style="min-width:100px;">Q1{{ $manualSplit ? ' ✎' : '' }}</th>
                            <th class="text-end" style="min-width:100px;">Q2{{ $manualSplit ? ' ✎' : '' }}</th>
                            <th class="text-end" style="min-width:100px;">Q3{{ $manualSplit ? ' ✎' : '' }}</th>
                            <th class="text-end" style="min-width:100px;">Q4{{ $manualSplit ? ' ✎' : '' }}</th>
                            @endif
                        @elseif($entryMode === 'monthly')
                            @foreach($monthLabels as $ml)
                                <th class="text-end" style="min-width:90px;">{{ $ml }} ({{ currency() }})</th>
                            @endforeach
                        @else
                            <th class="text-end">Q1 ({{ currency() }})</th>
                            <th class="text-end">Q2 ({{ currency() }})</th>
                            <th class="text-end">Q3 ({{ currency() }})</th>
                            <th class="text-end">Q4 ({{ currency() }})</th>
                        @endif

                        @if($calcMode === 'none')
                        <th class="text-end">Original Total</th>
                        @endif
                        <th class="text-end">Supplementary</th>
                        <th class="text-end">Effective Total</th>
                        @if($budgetVersion->isEditable())
                        <th>Notes</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
    @forelse($btab['summary'] as $categoryName => $categoryData)
    @php
        $catIdx  = $loop->index;
        $catSupp = $categoryData['items']->sum(fn($i) => $i->approvedSupplementaryTotal());
    @endphp
    {{-- Category separator row --}}
    <tr style="background:#EFF3F9">
        <td colspan="99" style="padding:7px 14px;font-size:11px;font-weight:700;color:#1B2A4A;text-transform:uppercase;letter-spacing:.5px">
            {{ $categoryName }}
            <span class="fw-normal text-muted ms-2" style="text-transform:none;letter-spacing:0;font-size:12px">
                {{ currency() }} <span class="cat-header-orig-{{ $catIdx }}">{{ number_format($categoryData['total'], 2) }}</span>
                @if($catSupp > 0)&nbsp;<span style="color:#10B981">+{{ number_format($catSupp, 2) }} supp</span>@endif
            </span>
        </td>
    </tr>
                    @foreach($categoryData['items'] as $item)
                    @php
                        $itemSupp      = $item->approvedSupplementaryTotal();
                        $itemEffective = $item->effectiveBudget();
                        $typeBadge     = match($item->line_type ?? '') {
                            'revenue'   => ['bg'=>'#D1FAE5','color'=>'#065F46'],
                            'expense'   => ['bg'=>'#FEE2E2','color'=>'#991B1B'],
                            'capex'     => ['bg'=>'#FEF3C7','color'=>'#92400E'],
                            'asset'     => ['bg'=>'#EDE9FE','color'=>'#5B21B6'],
                            'liability' => ['bg'=>'#F3E8FF','color'=>'#7C3AED'],
                            default     => ['bg'=>'#F1F5F9','color'=>'#475569'],
                        };
                        // For qty mode: derive quarterly read-only display from monthly storage
                        $displayQ1 = $item->q1_amount;
                        $displayQ2 = $item->q2_amount;
                        $displayQ3 = $item->q3_amount;
                        $displayQ4 = $item->q4_amount;
                    @endphp
                    <tr data-item-id="{{ $item->id }}" data-supp="{{ $itemSupp }}" data-cat="{{ $catIdx }}">
                        <td class="small">
                            <code>{{ $item->accountCode->code }}</code>
                            {{ $item->accountCode->name }}
                            @if($item->line_type)
                            <span style="padding:1px 6px;border-radius:4px;font-size:9px;font-weight:600;
                                         background:{{ $typeBadge['bg'] }};color:{{ $typeBadge['color'] }}">
                                {{ ucfirst($item->line_type) }}
                            </span>
                            @endif
                        </td>

                        {{-- ════════════════════════════════════════════
                             EDITABLE — Qty × Rate [× Freq] mode
                             ════════════════════════════════════════════ --}}
                        @if($budgetVersion->isEditable() && $calcMode !== 'none')
                            {{-- Quantity (always user-entered) --}}
                            <td>
                                <input type="number"
                                    class="form-control form-control-sm qty-input text-end"
                                    value="{{ $item->quantity ?? '' }}"
                                    min="0" step="any"
                                    placeholder="0"
                                    oninput="liveUpdate(this)">
                            </td>

                            {{-- Rate (locked or user-entered) --}}
                            <td>
                                @if($adminSetsRate)
                                @php
                                    // Fallback: line item → account code → category → 1
                                    $displayRate = $item->rate
                                        ?? $item->accountCode->default_rate
                                        ?? $item->accountCode->category->default_rate
                                        ?? 1;
                                @endphp
                                <input type="number"
                                    class="form-control form-control-sm rate-input text-end"
                                    value="{{ $displayRate }}"
                                    min="0" step="any"
                                    readonly
                                    style="background:#F8FAFC;color:#475569;cursor:not-allowed;">
                                @else
                                <input type="number"
                                    class="form-control form-control-sm rate-input text-end"
                                    value="{{ $item->rate ?? '' }}"
                                    min="0" step="any"
                                    placeholder="0.00"
                                    oninput="liveUpdate(this)">
                                @endif
                            </td>

                            {{-- Frequency (if qty_rate_freq mode) --}}
                            @if($calcMode === 'qty_rate_freq')
                            <td>
                                @if($adminSetsFreq)
                                @php
                                    // Fallback: line item → account code → category → 1
                                    $displayFreq = $item->frequency
                                        ?? $item->accountCode->default_frequency
                                        ?? $item->accountCode->category->default_frequency
                                        ?? 1;
                                @endphp
                                <input type="number"
                                    class="form-control form-control-sm freq-input text-end"
                                    value="{{ $displayFreq }}"
                                    min="0" step="any"
                                    readonly
                                    style="background:#F8FAFC;color:#475569;cursor:not-allowed;">
                                @else
                                <input type="number"
                                    class="form-control form-control-sm freq-input text-end"
                                    value="{{ $item->frequency ?? '' }}"
                                    min="0" step="any"
                                    placeholder="1"
                                    oninput="liveUpdate(this)">
                                @endif
                            </td>
                            @endif

                            {{-- Computed year total + live split-balance indicator --}}
                            <td class="text-end fw-semibold row-original"
                                style="color:var(--navy);min-width:130px">
                                <span class="year-total-num">{{ number_format($item->total_amount, 2) }}</span>
                                @if($manualSplit)
                                <div class="split-remain"
                                     data-computed="{{ $item->total_amount }}"
                                     style="font-size:10px;font-weight:600;
                                            margin-top:3px;line-height:1.2">
                                    &nbsp;
                                </div>
                                <button type="button"
                                        onclick="autoSplitEqual(this)"
                                        title="Split total equally across {{ $entryMode === 'monthly' ? '12 months' : '4 quarters' }}"
                                        style="margin-top:4px;font-size:9px;padding:1px 6px;border-radius:4px;
                                               background:#EEF2FF;color:#4338CA;border:1px solid #C7D2FE;
                                               cursor:pointer;white-space:nowrap;display:inline-block;
                                               line-height:1.5;font-weight:600;">
                                    ÷ equal
                                </button>
                                @endif
                            </td>

                            @if($entryMode === 'monthly')
                                @if($manualSplit)
                                    {{-- Manual monthly split inputs --}}
                                    @foreach(range(1,12) as $mn)
                                    <td>
                                        <input type="number"
                                            class="form-control form-control-sm split-input m{{ $mn }}-split text-end"
                                            value="{{ $item->{'m'.$mn.'_amount'} }}"
                                            min="0" step="0.01"
                                            placeholder="0.00"
                                            oninput="updateSplitBalance(this)">
                                    </td>
                                    @endforeach
                                @else
                                    {{-- Auto-distributed monthly display (read-only) --}}
                                    @foreach(range(1,12) as $mn)
                                    <td class="text-end small text-muted m{{ $mn }}-display">{{ number_format($item->{'m'.$mn.'_amount'}, 2) }}</td>
                                    @endforeach
                                @endif
                            @else
                                @if($manualSplit)
                                    {{-- Manual quarterly split inputs --}}
                                    <td>
                                        <input type="number"
                                            class="form-control form-control-sm split-input q1-split text-end"
                                            value="{{ number_format($displayQ1, 2, '.', '') }}"
                                            min="0" step="0.01"
                                            oninput="updateSplitBalance(this)">
                                    </td>
                                    <td>
                                        <input type="number"
                                            class="form-control form-control-sm split-input q2-split text-end"
                                            value="{{ number_format($displayQ2, 2, '.', '') }}"
                                            min="0" step="0.01"
                                            oninput="updateSplitBalance(this)">
                                    </td>
                                    <td>
                                        <input type="number"
                                            class="form-control form-control-sm split-input q3-split text-end"
                                            value="{{ number_format($displayQ3, 2, '.', '') }}"
                                            min="0" step="0.01"
                                            oninput="updateSplitBalance(this)">
                                    </td>
                                    <td>
                                        <input type="number"
                                            class="form-control form-control-sm split-input q4-split text-end"
                                            value="{{ number_format($displayQ4, 2, '.', '') }}"
                                            min="0" step="0.01"
                                            oninput="updateSplitBalance(this)">
                                    </td>
                                @else
                                    {{-- Auto-distributed quarterly display (read-only) --}}
                                    <td class="text-end small text-muted q1-display">{{ number_format($displayQ1, 2) }}</td>
                                    <td class="text-end small text-muted q2-display">{{ number_format($displayQ2, 2) }}</td>
                                    <td class="text-end small text-muted q3-display">{{ number_format($displayQ3, 2) }}</td>
                                    <td class="text-end small text-muted q4-display">{{ number_format($displayQ4, 2) }}</td>
                                @endif
                            @endif

                            {{-- Supplementary & Effective --}}
                            <td class="text-end" style="color:{{ $itemSupp > 0 ? '#10B981' : 'inherit' }}">
                                {{ $itemSupp > 0 ? '+'.number_format($itemSupp, 2) : '—' }}
                            </td>
                            <td class="text-end fw-bold row-total" style="color:var(--navy)">
                                {{ number_format($itemEffective, 2) }}
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm notes-input"
                                    value="{{ $item->justification }}"
                                    placeholder="Optional note"
                                    onkeyup="scheduleAutoSave()">
                            </td>

                        {{-- ════════════════════════════════════════════
                             EDITABLE — Direct amount (monthly) mode
                             ════════════════════════════════════════════ --}}
                        @elseif($budgetVersion->isEditable() && $entryMode === 'monthly')
                            @foreach(range(1,12) as $mn)
                            <td><input type="number"
                                class="form-control form-control-sm q-input m{{ $mn }} text-end"
                                value="{{ $item->{'m'.$mn.'_amount'} }}"
                                min="0" step="0.01" oninput="liveUpdate(this)"></td>
                            @endforeach
                            <td class="text-end text-muted small row-original">{{ number_format($item->total_amount, 2) }}</td>
                            <td class="text-end" style="color:{{ $itemSupp > 0 ? '#10B981' : 'inherit' }}">
                                {{ $itemSupp > 0 ? '+'.number_format($itemSupp, 2) : '—' }}
                            </td>
                            <td class="text-end fw-bold row-total" style="color:var(--navy)">
                                {{ number_format($itemEffective, 2) }}
                            </td>
                            <td><input type="text" class="form-control form-control-sm notes-input"
                                value="{{ $item->justification }}"
                                placeholder="Optional note"
                                onkeyup="scheduleAutoSave()"></td>

                        {{-- ════════════════════════════════════════════
                             EDITABLE — Direct amount (quarterly) mode
                             ════════════════════════════════════════════ --}}
                        @elseif($budgetVersion->isEditable())
                            <td><input type="number" class="form-control form-control-sm q-input q1 text-end"
                                value="{{ $item->q1_amount }}" min="0" step="0.01"
                                oninput="liveUpdate(this)"></td>
                            <td><input type="number" class="form-control form-control-sm q-input q2 text-end"
                                value="{{ $item->q2_amount }}" min="0" step="0.01"
                                oninput="liveUpdate(this)"></td>
                            <td><input type="number" class="form-control form-control-sm q-input q3 text-end"
                                value="{{ $item->q3_amount }}" min="0" step="0.01"
                                oninput="liveUpdate(this)"></td>
                            <td><input type="number" class="form-control form-control-sm q-input q4 text-end"
                                value="{{ $item->q4_amount }}" min="0" step="0.01"
                                oninput="liveUpdate(this)"></td>
                            <td class="text-end text-muted small row-original">{{ number_format($item->total_amount, 2) }}</td>
                            <td class="text-end" style="color:{{ $itemSupp > 0 ? '#10B981' : 'inherit' }}">
                                {{ $itemSupp > 0 ? '+'.number_format($itemSupp, 2) : '—' }}
                            </td>
                            <td class="text-end fw-bold row-total" style="color:var(--navy)">
                                {{ number_format($itemEffective, 2) }}
                            </td>
                            <td><input type="text" class="form-control form-control-sm notes-input"
                                value="{{ $item->justification }}"
                                placeholder="Optional note"
                                onkeyup="scheduleAutoSave()"></td>

                        {{-- ════════════════════════════════════════════
                             READ-ONLY views
                             ════════════════════════════════════════════ --}}
                        @else
                            @if($calcMode !== 'none')
                                <td class="text-end small">{{ $item->quantity !== null ? number_format($item->quantity, 4) : '—' }}</td>
                                <td class="text-end small">{{ $item->rate      !== null ? number_format($item->rate, 4)     : '—' }}</td>
                                @if($calcMode === 'qty_rate_freq')
                                <td class="text-end small">{{ $item->frequency !== null ? number_format($item->frequency, 4) : '—' }}</td>
                                @endif
                                <td class="text-end small fw-semibold">{{ number_format($item->total_amount, 2) }}</td>
                                @if($entryMode === 'monthly')
                                    @foreach(range(1,12) as $mn)
                                    <td class="text-end small text-muted">{{ number_format($item->{'m'.$mn.'_amount'}, 2) }}</td>
                                    @endforeach
                                @else
                                <td class="text-end small text-muted">{{ number_format($displayQ1, 2) }}</td>
                                <td class="text-end small text-muted">{{ number_format($displayQ2, 2) }}</td>
                                <td class="text-end small text-muted">{{ number_format($displayQ3, 2) }}</td>
                                <td class="text-end small text-muted">{{ number_format($displayQ4, 2) }}</td>
                                @endif
                            @elseif($entryMode === 'monthly')
                                @foreach(range(1,12) as $mn)
                                <td class="text-end small">{{ number_format($item->{'m'.$mn.'_amount'}, 2) }}</td>
                                @endforeach
                                <td class="text-end small text-muted">{{ number_format($item->total_amount, 2) }}</td>
                            @else
                                <td class="text-end small">{{ number_format($item->q1_amount, 2) }}</td>
                                <td class="text-end small">{{ number_format($item->q2_amount, 2) }}</td>
                                <td class="text-end small">{{ number_format($item->q3_amount, 2) }}</td>
                                <td class="text-end small">{{ number_format($item->q4_amount, 2) }}</td>
                                <td class="text-end small text-muted">{{ number_format($item->total_amount, 2) }}</td>
                            @endif
                            <td class="text-end" style="color:{{ $itemSupp > 0 ? '#10B981' : 'inherit' }}">
                                {{ $itemSupp > 0 ? '+'.number_format($itemSupp, 2) : '—' }}
                            </td>
                            <td class="text-end small fw-semibold">{{ number_format($itemEffective, 2) }}</td>
                        @endif
                    </tr>
                    @endforeach
    {{-- Category total row --}}
    <tr style="background:#F8FAFC;font-weight:700;" data-cat-foot="{{ $catIdx }}" data-cat-supp="{{ $catSupp }}">
        <td style="padding-left:14px">Category Total</td>
        @if($calcMode !== 'none')
            <td></td><td></td>
            @if($calcMode === 'qty_rate_freq')<td></td>@endif
            <td class="text-end" data-foot="yearTotal">{{ number_format($categoryData['total'], 2) }}</td>
            @if($entryMode === 'monthly')
                @foreach(range(1,12) as $mn)
                <td class="text-end" data-foot="m{{ $mn }}">{{ number_format($categoryData["m{$mn}"] ?? 0, 2) }}</td>
                @endforeach
            @else
                <td class="text-end" data-foot="q1">{{ number_format($categoryData['q1'], 2) }}</td>
                <td class="text-end" data-foot="q2">{{ number_format($categoryData['q2'], 2) }}</td>
                <td class="text-end" data-foot="q3">{{ number_format($categoryData['q3'], 2) }}</td>
                <td class="text-end" data-foot="q4">{{ number_format($categoryData['q4'], 2) }}</td>
            @endif
        @elseif($entryMode === 'monthly')
            @foreach(range(1,12) as $mn)
            <td class="text-end">{{ number_format($categoryData["m{$mn}"], 2) }}</td>
            @endforeach
            <td class="text-end">{{ number_format($categoryData['total'], 2) }}</td>
        @else
            <td class="text-end">{{ number_format($categoryData['q1'], 2) }}</td>
            <td class="text-end">{{ number_format($categoryData['q2'], 2) }}</td>
            <td class="text-end">{{ number_format($categoryData['q3'], 2) }}</td>
            <td class="text-end">{{ number_format($categoryData['q4'], 2) }}</td>
            <td class="text-end">{{ number_format($categoryData['total'], 2) }}</td>
        @endif
        <td class="text-end" style="color:{{ $catSupp > 0 ? '#10B981' : 'inherit' }}">
            {{ $catSupp > 0 ? '+'.number_format($catSupp, 2) : '—' }}
        </td>
        <td class="text-end" data-foot="eff" style="color:var(--navy)">
            {{ number_format($categoryData['total'] + $catSupp, 2) }}
        </td>
        @if($budgetVersion->isEditable())
        <td></td>
        @endif
    </tr>
    @empty
    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="text-center text-muted py-5">
        <i class="bi bi-inbox d-block mb-2" style="font-size:2rem;opacity:.3"></i>
        No items in this section yet.
    </div>
    @endif
</div>
@endforeach
</div>

@if($budgetVersion->isEditable())
<script>
    const SAVE_URL  = "{{ route('budget.save', $budgetVersion) }}";
    const CSRF      = document.querySelector('meta[name="csrf-token"]')?.content || "{{ csrf_token() }}";
    const CUR       = "{{ currency() }}";
    const ENTRY_MODE   = '{{ $entryMode }}';
    const CALC_MODE    = '{{ $calcMode }}';
    const MANUAL_SPLIT = {{ $manualSplit ? 'true' : 'false' }};

    let autoSaveTimer = null;
    let isSaving      = false;

    function numFmt(v) {
        return parseFloat(v || 0).toLocaleString('en-GH', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });
    }

    // ── Called on every input — updates row totals, category footer, grand total bar ──
    function liveUpdate(input) {
        const row  = input.closest('tr');
        const supp = parseFloat(row.dataset.supp) || 0;
        let orig = 0;

        if (CALC_MODE !== 'none') {
            // Qty × Rate [× Freq]
            const qty  = parseFloat(row.querySelector('.qty-input')?.value)  || 0;
            const rate = parseFloat(row.querySelector('.rate-input')?.value) || 0;
            // Empty freq field defaults to 1 (not 0, which would zero-out the total)
            const freq = CALC_MODE === 'qty_rate_freq'
                ? parseFloat(row.querySelector('.freq-input')?.value || '1')
                : 1;
            orig = qty * rate * freq;

            // Year total cell — update only the number span so split-remain/button survive
            const ytEl = row.querySelector('.row-original');
            const ytNum = ytEl?.querySelector('.year-total-num') ?? ytEl;
            if (ytNum) ytNum.textContent = numFmt(orig);

            if (!MANUAL_SPLIT) {
                if (ENTRY_MODE === 'monthly') {
                    // Monthly display (equal 12 months, auto-distributed)
                    const mShare = Math.round(orig / 12 * 100) / 100;
                    const mLast  = Math.round((orig - mShare * 11) * 100) / 100;
                    for (let i = 1; i <= 12; i++) {
                        const el = row.querySelector(`.m${i}-display`);
                        if (el) el.textContent = numFmt(i === 12 ? mLast : mShare);
                    }
                } else {
                    // Quarterly display (equal quarters, auto-distributed)
                    const qShare = orig / 4;
                    const qVals  = [qShare, qShare, qShare, orig - qShare * 3];
                    ['q1-display','q2-display','q3-display','q4-display'].forEach((cls, i) => {
                        const el = row.querySelector('.' + cls);
                        if (el) el.textContent = numFmt(qVals[i]);
                    });
                }
            } else {
                // Manual split: highlight balance status
                updateSplitBalance(row.querySelector('.split-input') || row);
            }
        } else if (ENTRY_MODE === 'monthly') {
            for (let m = 1; m <= 12; m++) {
                orig += parseFloat(row.querySelector(`.m${m}`)?.value) || 0;
            }
        } else {
            orig = (parseFloat(row.querySelector('.q1')?.value) || 0)
                 + (parseFloat(row.querySelector('.q2')?.value) || 0)
                 + (parseFloat(row.querySelector('.q3')?.value) || 0)
                 + (parseFloat(row.querySelector('.q4')?.value) || 0);
        }

        const origEl = row.querySelector('.row-original');
        const effEl  = row.querySelector('.row-total');
        if (origEl && CALC_MODE === 'none') {
            const origNum = origEl.querySelector('.year-total-num') ?? origEl;
            origNum.textContent = numFmt(orig);
        }
        if (effEl)  effEl.textContent  = numFmt(orig + supp);

        updateCategoryFooter(row);
        updateGrandTotals();
        scheduleAutoSave();
    }

    // ── Balance indicator for manual period split ──────────────────────────────
    function updateSplitBalance(inputOrRow) {
        const row = inputOrRow?.closest
            ? (inputOrRow.closest('tr[data-item-id]') || inputOrRow)
            : inputOrRow;
        if (!row || !row.dataset?.itemId) return;

        const qty  = parseFloat(row.querySelector('.qty-input')?.value)  || 0;
        const rate = parseFloat(row.querySelector('.rate-input')?.value) || 0;
        const freq = CALC_MODE === 'qty_rate_freq'
            ? parseFloat(row.querySelector('.freq-input')?.value || '1')
            : 1;
        const liveComputed = Math.round(qty * rate * freq * 100) / 100;
        // In direct-entry mode (no qty/rate inputs) liveComputed is 0;
        // fall back to the server-rendered total stored in data-computed.
        const indicator = row.querySelector('.split-remain');
        const computed = liveComputed > 0
            ? liveComputed
            : parseFloat(indicator?.dataset?.computed || '0');

        let splitSum = 0;
        if (ENTRY_MODE === 'monthly') {
            [1,2,3,4,5,6,7,8,9,10,11,12].forEach(n => {
                splitSum += parseFloat(row.querySelector(`.m${n}-split`)?.value) || 0;
            });
        } else {
            splitSum += parseFloat(row.querySelector('.q1-split')?.value) || 0;
            splitSum += parseFloat(row.querySelector('.q2-split')?.value) || 0;
            splitSum += parseFloat(row.querySelector('.q3-split')?.value) || 0;
            splitSum += parseFloat(row.querySelector('.q4-split')?.value) || 0;
        }
        splitSum = Math.round(splitSum * 100) / 100;

        const remaining = Math.round((computed - splitSum) * 100) / 100;
        const balanced  = Math.abs(remaining) <= 0.02;

        // ── Border highlight on split inputs ──
        row.querySelectorAll('.split-input').forEach(el => {
            el.style.outline      = balanced
                ? '2px solid #10B981'
                : '2px solid ' + (remaining < 0 ? '#F43F5E' : '#F59E0B');
            el.style.borderRadius = '4px';
            el.title = balanced ? 'Balanced ✓' :
                (remaining > 0
                    ? `Still need to allocate: ${numFmt(remaining)}`
                    : `Over by: ${numFmt(Math.abs(remaining))}`);
        });

        // ── Inline remaining indicator in the year-total cell ──
        if (indicator) {
            if (computed === 0) {
                indicator.textContent = '';
                return;
            }
            if (balanced) {
                indicator.innerHTML =
                    '<span style="color:#10B981">✓ Balanced</span>';
            } else if (remaining > 0) {
                indicator.innerHTML =
                    '<span style="color:#F59E0B">' +
                    '↓ ' + numFmt(remaining) + ' left</span>';
            } else {
                indicator.innerHTML =
                    '<span style="color:#F43F5E">' +
                    '↑ ' + numFmt(Math.abs(remaining)) + ' over</span>';
            }
        }

        scheduleAutoSave();
    }

    // ── Auto-split equally across all periods ─────────────────────────────────
    function autoSplitEqual(btn) {
        const row = btn.closest('tr[data-item-id]');
        if (!row) return;

        // Resolve the total using the same logic as updateSplitBalance
        const qty  = parseFloat(row.querySelector('.qty-input')?.value)  || 0;
        const rate = parseFloat(row.querySelector('.rate-input')?.value) || 0;
        const freq = CALC_MODE === 'qty_rate_freq'
            ? parseFloat(row.querySelector('.freq-input')?.value || '1')
            : 1;
        const liveComputed = Math.round(qty * rate * freq * 100) / 100;
        const indicator = row.querySelector('.split-remain');
        const total = liveComputed > 0
            ? liveComputed
            : parseFloat(indicator?.dataset?.computed || '0');

        if (total === 0) return;

        if (ENTRY_MODE === 'monthly') {
            const share = Math.floor(total / 12 * 100) / 100;
            const last  = Math.round((total - share * 11) * 100) / 100;
            for (let m = 1; m <= 12; m++) {
                const inp = row.querySelector(`.m${m}-split`);
                if (inp) inp.value = (m === 12 ? last : share).toFixed(2);
            }
        } else {
            const share = Math.floor(total / 4 * 100) / 100;
            const last  = Math.round((total - share * 3) * 100) / 100;
            ['q1-split','q2-split','q3-split','q4-split'].forEach((cls, i) => {
                const inp = row.querySelector(`.${cls}`);
                if (inp) inp.value = (i === 3 ? last : share).toFixed(2);
            });
        }

        updateSplitBalance(row.querySelector('.split-input') || row);
    }

    function updateCategoryFooter(itemRow) {
        const catIdx  = itemRow.dataset.cat;
        const tbody   = itemRow.closest('tbody');
        if (!tbody || catIdx === undefined) return;
        const rows    = tbody.querySelectorAll(`tr[data-item-id][data-cat="${catIdx}"]`);
        const footRow = tbody.querySelector(`tr[data-cat-foot="${catIdx}"]`);
        if (!footRow) return;
        const catSupp = parseFloat(footRow.dataset.catSupp) || 0;
        const fq      = name => footRow.querySelector(`[data-foot="${name}"]`);

        if (CALC_MODE !== 'none') {
            let orig = 0;
            const ps = {};
            if (ENTRY_MODE === 'monthly') {
                for (let m = 1; m <= 12; m++) ps[`m${m}`] = 0;
            } else {
                ['q1','q2','q3','q4'].forEach(k => ps[k] = 0);
            }
            rows.forEach(row => {
                const qty  = parseFloat(row.querySelector('.qty-input')?.value)  || 0;
                const rate = parseFloat(row.querySelector('.rate-input')?.value) || 0;
                // Empty freq defaults to 1 so totals stay correct when field is blank
                const freq = CALC_MODE === 'qty_rate_freq'
                    ? parseFloat(row.querySelector('.freq-input')?.value || '1')
                    : 1;
                const rowTotal = qty * rate * freq;
                orig += rowTotal;
                if (ENTRY_MODE === 'monthly') {
                    const share = Math.round(rowTotal / 12 * 100) / 100;
                    for (let m = 1; m <= 12; m++) {
                        ps[`m${m}`] += (m === 12 ? Math.round((rowTotal - share * 11) * 100) / 100 : share);
                    }
                } else {
                    const qShare = rowTotal / 4;
                    ps.q1 += qShare; ps.q2 += qShare; ps.q3 += qShare;
                    ps.q4 += rowTotal - qShare * 3;
                }
            });

            const ytEl = fq('yearTotal'); if (ytEl) ytEl.textContent = numFmt(orig);
            if (ENTRY_MODE === 'monthly') {
                for (let m = 1; m <= 12; m++) {
                    const c = fq(`m${m}`); if (c) c.textContent = numFmt(ps[`m${m}`]);
                }
            } else {
                ['q1','q2','q3','q4'].forEach(lbl => { const c = fq(lbl); if (c) c.textContent = numFmt(ps[lbl]); });
            }
            const effCell = fq('eff');
            if (effCell) effCell.textContent = numFmt(orig + catSupp);

            const hOrig = tbody.querySelector(`.cat-header-orig-${catIdx}`);
            if (hOrig) hOrig.textContent = numFmt(orig);

        } else if (ENTRY_MODE === 'monthly') {
            const cells = Array.from(footRow.querySelectorAll('td'));
            const ms = new Array(12).fill(0);
            let orig = 0;
            rows.forEach(row => {
                for (let m = 1; m <= 12; m++) {
                    const v = parseFloat(row.querySelector(`.m${m}`)?.value) || 0;
                    ms[m - 1] += v;
                    orig += v;
                }
            });
            for (let m = 0; m < 12; m++) {
                if (cells[m + 1]) cells[m + 1].textContent = numFmt(ms[m]);
            }
            if (cells[13]) cells[13].textContent = numFmt(orig);
            if (cells[15]) cells[15].textContent = numFmt(orig + catSupp);
            const hOrig = tbody.querySelector(`.cat-header-orig-${catIdx}`);
            if (hOrig) hOrig.textContent = numFmt(orig);
        } else {
            const cells = Array.from(footRow.querySelectorAll('td'));
            let q1=0, q2=0, q3=0, q4=0, orig=0;
            rows.forEach(row => {
                const rq1 = parseFloat(row.querySelector('.q1')?.value) || 0;
                const rq2 = parseFloat(row.querySelector('.q2')?.value) || 0;
                const rq3 = parseFloat(row.querySelector('.q3')?.value) || 0;
                const rq4 = parseFloat(row.querySelector('.q4')?.value) || 0;
                q1 += rq1; q2 += rq2; q3 += rq3; q4 += rq4;
                orig += rq1 + rq2 + rq3 + rq4;
            });
            if (cells[1]) cells[1].textContent = numFmt(q1);
            if (cells[2]) cells[2].textContent = numFmt(q2);
            if (cells[3]) cells[3].textContent = numFmt(q3);
            if (cells[4]) cells[4].textContent = numFmt(q4);
            if (cells[5]) cells[5].textContent = numFmt(orig);
            if (cells[7]) cells[7].textContent = numFmt(orig + catSupp);
            const hOrig = tbody.querySelector(`.cat-header-orig-${catIdx}`);
            if (hOrig) hOrig.textContent = numFmt(orig);
        }
    }

    function updateGrandTotals() {
        const fmt = v => CUR + ' ' + numFmt(v);
        const el  = id => document.getElementById(id);
        let orig = 0, totalSupp = 0;

        if (ENTRY_MODE === 'monthly') {
            const mTotals = new Array(12).fill(0);
            document.querySelectorAll('tr[data-item-id]').forEach(row => {
                totalSupp += parseFloat(row.dataset.supp) || 0;
                if (CALC_MODE !== 'none') {
                    const qty      = parseFloat(row.querySelector('.qty-input')?.value)  || 0;
                    const rate     = parseFloat(row.querySelector('.rate-input')?.value) || 0;
                    const freq     = CALC_MODE === 'qty_rate_freq'
                        ? parseFloat(row.querySelector('.freq-input')?.value || '1') : 1;
                    const rowTotal = qty * rate * freq;
                    orig += rowTotal;
                    if (MANUAL_SPLIT) {
                        [1,2,3,4,5,6,7,8,9,10,11,12].forEach((n,i) => {
                            mTotals[i] += parseFloat(row.querySelector(`.m${n}-split`)?.value) || 0;
                        });
                    } else {
                        const share = rowTotal / 12;
                        mTotals.forEach((_,i) => mTotals[i] += share);
                    }
                } else {
                    [1,2,3,4,5,6,7,8,9,10,11,12].forEach((n,i) => {
                        const v = parseFloat(row.querySelector(`.m${n}`)?.value) || 0;
                        mTotals[i] += v;
                        orig += v;
                    });
                }
            });
            [1,2,3,4,5,6,7,8,9,10,11,12].forEach((n,i) => {
                if (el(`gt-m${n}`)) el(`gt-m${n}`).textContent = fmt(mTotals[i]);
            });
        } else {
            let q1=0, q2=0, q3=0, q4=0;
            document.querySelectorAll('tr[data-item-id]').forEach(row => {
                totalSupp += parseFloat(row.dataset.supp) || 0;
                if (CALC_MODE !== 'none') {
                    const qty      = parseFloat(row.querySelector('.qty-input')?.value)  || 0;
                    const rate     = parseFloat(row.querySelector('.rate-input')?.value) || 0;
                    const freq     = CALC_MODE === 'qty_rate_freq'
                        ? parseFloat(row.querySelector('.freq-input')?.value || '1') : 1;
                    const rowTotal = qty * rate * freq;
                    orig += rowTotal;
                    if (MANUAL_SPLIT) {
                        q1 += parseFloat(row.querySelector('.q1-split')?.value) || 0;
                        q2 += parseFloat(row.querySelector('.q2-split')?.value) || 0;
                        q3 += parseFloat(row.querySelector('.q3-split')?.value) || 0;
                        q4 += parseFloat(row.querySelector('.q4-split')?.value) || 0;
                    } else {
                        const qShare = rowTotal / 4;
                        q1 += qShare; q2 += qShare; q3 += qShare; q4 += rowTotal - qShare * 3;
                    }
                } else {
                    const rq1 = parseFloat(row.querySelector('.q1')?.value) || 0;
                    const rq2 = parseFloat(row.querySelector('.q2')?.value) || 0;
                    const rq3 = parseFloat(row.querySelector('.q3')?.value) || 0;
                    const rq4 = parseFloat(row.querySelector('.q4')?.value) || 0;
                    q1 += rq1; q2 += rq2; q3 += rq3; q4 += rq4;
                    orig += rq1 + rq2 + rq3 + rq4;
                }
            });
            if (el('gt-q1')) el('gt-q1').textContent = fmt(q1);
            if (el('gt-q2')) el('gt-q2').textContent = fmt(q2);
            if (el('gt-q3')) el('gt-q3').textContent = fmt(q3);
            if (el('gt-q4')) el('gt-q4').textContent = fmt(q4);
        }

        if (el('gt-total'))     el('gt-total').textContent     = fmt(orig);
        if (el('gt-effective')) el('gt-effective').textContent = fmt(orig + totalSupp);
    }

    function collectItems() {
        return Array.from(document.querySelectorAll('tr[data-item-id]')).map(row => {
            const notes = row.querySelector('.notes-input')?.value || '';

            if (CALC_MODE !== 'none') {
                const base = {
                    id:   row.dataset.itemId,
                    qty:  parseFloat(row.querySelector('.qty-input')?.value)  || 0,
                    rate: parseFloat(row.querySelector('.rate-input')?.value) || 0,
                    freq: CALC_MODE === 'qty_rate_freq'
                        ? parseFloat(row.querySelector('.freq-input')?.value || '1')
                        : 1,
                    notes,
                };
                if (MANUAL_SPLIT) {
                    if (ENTRY_MODE === 'monthly') {
                        [1,2,3,4,5,6,7,8,9,10,11,12].forEach(n => {
                            base[`m${n}`] = parseFloat(row.querySelector(`.m${n}-split`)?.value) || 0;
                        });
                    } else {
                        base.q1 = parseFloat(row.querySelector('.q1-split')?.value) || 0;
                        base.q2 = parseFloat(row.querySelector('.q2-split')?.value) || 0;
                        base.q3 = parseFloat(row.querySelector('.q3-split')?.value) || 0;
                        base.q4 = parseFloat(row.querySelector('.q4-split')?.value) || 0;
                    }
                }
                return base;
            }

            if (ENTRY_MODE === 'monthly') {
                const item = { id: row.dataset.itemId, notes };
                [1,2,3,4,5,6,7,8,9,10,11,12].forEach(n => {
                    item[`m${n}`] = parseFloat(row.querySelector(`.m${n}`)?.value) || 0;
                });
                return item;
            }

            return {
                id:    row.dataset.itemId,
                q1:    parseFloat(row.querySelector('.q1')?.value) || 0,
                q2:    parseFloat(row.querySelector('.q2')?.value) || 0,
                q3:    parseFloat(row.querySelector('.q3')?.value) || 0,
                q4:    parseFloat(row.querySelector('.q4')?.value) || 0,
                notes,
            };
        });
    }

    async function saveBudget() {
        if (isSaving) return false;
        isSaving = true;

        const btn    = document.getElementById('save-btn');
        const status = document.getElementById('save-status');
        if (btn)    btn.disabled = true;
        if (status) status.textContent = 'Saving…';

        try {
            const res = await fetch(SAVE_URL, {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept':       'application/json',
                },
                body: JSON.stringify({ items: collectItems() }),
            });

            const data = await res.json();

            if (!res.ok) {
                const msg = data?.error || 'Save failed (' + res.status + ')';
                if (status) {
                    status.style.color = '#F43F5E';
                    status.textContent = msg;
                }
                return false;
            }

            if (status) {
                status.style.color = '';
                status.textContent = data.success ? 'Saved at ' + data.saved_at : 'Save failed.';
            }
            return data.success === true;
        } catch (e) {
            if (status) {
                status.style.color = '#F43F5E';
                status.textContent = 'Network error — not saved.';
            }
            return false;
        } finally {
            isSaving = false;
            if (btn) btn.disabled = false;
        }
    }

    function scheduleAutoSave() {
        clearTimeout(autoSaveTimer);
        const status = document.getElementById('save-status');
        if (status) status.textContent = 'Unsaved changes…';
        autoSaveTimer = setTimeout(saveBudget, 3000);
    }

    // ── On load: compute year-total / Q1-Q4 display and split balance indicators ──
    if (CALC_MODE !== 'none') {
        document.querySelectorAll('tr[data-item-id]').forEach(row => {
            const firstInput = row.querySelector('.qty-input, .rate-input, .freq-input');
            if (firstInput) liveUpdate(firstInput);
            // In manual split mode, also refresh the balance indicator for existing values
            if (MANUAL_SPLIT) {
                const firstSplit = row.querySelector('.split-input');
                if (firstSplit) updateSplitBalance(firstSplit);
            }
        });
    }

    // ── Save before navigating to submit confirmation ──
    // The submit link is a plain <a>, so clicking it navigates away before the
    // 3-second auto-save timer fires.  Intercept it: save first, then redirect.
    document.getElementById('submit-btn')?.addEventListener('click', async function (e) {
        e.preventDefault();
        const href   = this.href;
        const status = document.getElementById('save-status');
        const btn    = this;

        // ── Client-side pre-check: block submission if any split is unbalanced ──
        if (MANUAL_SPLIT) {
            const unbalancedRows = [...document.querySelectorAll('tr[data-item-id]')].filter(row => {
                const ind = row.querySelector('.split-remain');
                if (!ind) return false;
                const text = ind.textContent.trim();
                // Indicator shows something and it's not the "balanced" tick
                return text.length > 0 && !text.includes('Balanced');
            });
            if (unbalancedRows.length > 0) {
                const n = unbalancedRows.length;
                Swal.fire({
                    icon: 'warning',
                    title: 'Unbalanced Splits',
                    html: `<b>${n} line item${n === 1 ? '' : 's'}</b> ${n === 1 ? 'has' : 'have'} period splits that don't add up to the line total.<br><br>Look for <span style="color:#F59E0B">amber (↓ left)</span> or <span style="color:#F43F5E">red (↑ over)</span> indicators in the Year Total column.`,
                    confirmButtonText: 'OK, I\'ll fix it',
                    confirmButtonColor: '#E65C00',
                });
                return;
            }
        }

        btn.textContent = 'Saving…';
        btn.style.opacity = '.6';
        btn.style.pointerEvents = 'none';

        clearTimeout(autoSaveTimer); // cancel any pending auto-save — we're doing it now
        const saved = await saveBudget();

        if (!saved) {
            const msg = (status && status.textContent) || 'Save failed.';
            Swal.fire({
                icon: 'error',
                title: 'Save Failed',
                text: msg,
                confirmButtonColor: '#E65C00',
            });
            btn.textContent = 'Submit for Approval →';
            btn.style.opacity = '';
            btn.style.pointerEvents = '';
            return;
        }

        window.location.href = href;
    });
</script>
@endif

@push('styles')
<style>
@media print {
    /* Hide everything except the budget table */
    .sidebar, .navbar, .topbar,
    .btn, .dropdown, .dropdown-menu,
    #save-status, #save-btn, #submit-btn,
    .chart-card:has(.btn),   /* import/export panel */
    .nav-tabs, .tab-content > :not(.active),
    .pagination, form[method="GET"],
    .alert:not(.alert-danger) {
        display: none !important;
    }

    /* Layout reset for print */
    body, .main-content, .content-wrapper, .px-3, .px-lg-4 {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }

    /* Print header */
    .d-flex.justify-content-between.align-items-center.mb-3::before {
        content: 'GOIL COMPANY LIMITED — BUDGET SUBMISSION';
        display: block;
        font-size: 16px;
        font-weight: 700;
        text-align: center;
        color: #1B2A4A;
        margin-bottom: 8px;
        border-bottom: 2px solid #E65C00;
        padding-bottom: 6px;
    }

    /* Page settings */
    @page {
        size: A4 landscape;
        margin: 1.5cm 1cm;
    }

    /* Grand total bar — keep visible but simplified */
    .bg-goil-orange {
        background: #1B2A4A !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* Make tables page-break friendly */
    table { page-break-inside: auto; }
    tr    { page-break-inside: avoid; page-break-after: auto; }
    thead { display: table-header-group; }
    tfoot { display: table-footer-group; }

    /* Category headers */
    .fw-bold.py-2 { background: #F8FAFC !important; }

    /* Remove shadows */
    .card, .shadow-sm { box-shadow: none !important; border: 1px solid #E2E8F0 !important; }

    /* Shrink font slightly for fit */
    body, td, th { font-size: 11px !important; }
    h5 { font-size: 14px !important; }
}
</style>
@endpush

@endsection
