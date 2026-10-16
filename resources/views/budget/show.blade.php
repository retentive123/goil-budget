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
        @if($manualSplit ?? false)
        <div class="btn-group btn-group-sm" role="group">
            <button class="btn btn-outline-secondary" onclick="splitAllEqual()"
                    title="Split every line item's total equally across all {{ $entryMode === 'monthly' ? 'months' : 'quarters' }}">
                ÷ Split All
            </button>
            <button class="btn btn-outline-danger" onclick="clearAll()"
                    title="Reset every line item's {{ $entryMode === 'monthly' ? 'month' : 'quarter' }} values to 0">
                ✕ Clear All
            </button>
        </div>
        @endif
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
    $effectiveTotal = $lineItems->sum(fn($i) =>
        ($reductions[$i->id] ?? $i->total_amount) + $i->approvedSupplementaryTotal()
    );
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
        <div class="card-body p-0" style="max-height:calc(100vh - 260px);overflow:auto;">
            <table class="table table-sm table-hover mb-0"
                   style="{{ $calcMode !== 'none' ? 'min-width:900px;' : ($entryMode === 'monthly' ? 'min-width:1400px;' : '') }}">
                <thead class="table-light" style="position:sticky;top:0;z-index:3">
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
                        @foreach($customFields ?? [] as $cf)
                        <th style="min-width:110px">{{ $cf->label }}@if($cf->is_required)<span class="text-danger ms-1">*</span>@endif</th>
                        @endforeach
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
                        $itemEffective = ($reductions[$item->id] ?? $item->total_amount) + $itemSupp;
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
                        // Map custom field id → saved value for this line item
                        $cvMap = $item->customValues->keyBy('custom_budget_field_id');
                    @endphp
                    @php
                        $hasSubEntries    = $item->subEntries->isNotEmpty();
                        $hasTemplatedSubs = !$hasSubEntries && $budgetVersion->isEditable() && $item->accountCode->subEntryTemplates->isNotEmpty();
                    @endphp
                    <tr data-item-id="{{ $item->id }}" data-supp="{{ $itemSupp }}" data-cat="{{ $catIdx }}"
                        data-has-subs="{{ ($hasSubEntries || $hasTemplatedSubs) ? '1' : '0' }}">
                        <td class="small">
                            <code>{{ $item->accountCode->code }}</code>
                            {{ $item->accountCode->name }}
                            @if($item->line_type)
                            <span style="padding:1px 6px;border-radius:4px;font-size:9px;font-weight:600;
                                         background:{{ $typeBadge['bg'] }};color:{{ $typeBadge['color'] }}">
                                {{ ucfirst($item->line_type) }}
                            </span>
                            @endif
                            @if($budgetVersion->isEditable())
                            <button type="button"
                                    onclick="addSubRow({{ $item->id }})"
                                    title="Add a sub-item under this line"
                                    style="display:inline-flex;align-items:center;justify-content:center;
                                           width:18px;height:18px;border-radius:50%;font-size:11px;
                                           background:#EEF2FF;color:#4338CA;border:1px solid #C7D2FE;
                                           cursor:pointer;margin-left:5px;flex-shrink:0;font-weight:700;
                                           line-height:1;padding:0">
                                +
                            </button>
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
                                <div style="display:flex;gap:3px;margin-top:4px;flex-wrap:wrap">
                                <button type="button"
                                        onclick="autoSplitEqual(this)"
                                        title="Split total equally across {{ $entryMode === 'monthly' ? '12 months' : '4 quarters' }}"
                                        style="font-size:9px;padding:1px 6px;border-radius:4px;
                                               background:#EEF2FF;color:#4338CA;border:1px solid #C7D2FE;
                                               cursor:pointer;white-space:nowrap;display:inline-block;
                                               line-height:1.5;font-weight:600;">
                                    ÷ equal
                                </button>
                                <button type="button"
                                        onclick="clearSplitInputs(this)"
                                        title="Reset all {{ $entryMode === 'monthly' ? 'month' : 'quarter' }} values to 0"
                                        style="font-size:9px;padding:1px 6px;border-radius:4px;
                                               background:#FEE2E2;color:#991B1B;border:1px solid #FECACA;
                                               cursor:pointer;white-space:nowrap;display:inline-block;
                                               line-height:1.5;font-weight:600;">
                                    ✕ 0
                                </button>
                                </div>
                                @endif
                            </td>

                            @if($entryMode === 'monthly')
                                @if($manualSplit)
                                    {{-- Manual monthly split inputs --}}
                                    @foreach(range(1,12) as $mn)
                                    @php $mnVal = $item->{'m'.$mn.'_amount'}; @endphp
                                    <td>
                                        <input type="number"
                                            class="form-control form-control-sm split-input m{{ $mn }}-split text-end"
                                            value="{{ $mnVal }}"
                                            min="0" step="0.01"
                                            placeholder="0.00"
                                            data-split-tip="{{ number_format($mnVal, 2) }}"
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
                                            data-split-tip="{{ number_format($displayQ1, 2) }}"
                                            oninput="updateSplitBalance(this)">
                                    </td>
                                    <td>
                                        <input type="number"
                                            class="form-control form-control-sm split-input q2-split text-end"
                                            value="{{ number_format($displayQ2, 2, '.', '') }}"
                                            min="0" step="0.01"
                                            data-split-tip="{{ number_format($displayQ2, 2) }}"
                                            oninput="updateSplitBalance(this)">
                                    </td>
                                    <td>
                                        <input type="number"
                                            class="form-control form-control-sm split-input q3-split text-end"
                                            value="{{ number_format($displayQ3, 2, '.', '') }}"
                                            min="0" step="0.01"
                                            data-split-tip="{{ number_format($displayQ3, 2) }}"
                                            oninput="updateSplitBalance(this)">
                                    </td>
                                    <td>
                                        <input type="number"
                                            class="form-control form-control-sm split-input q4-split text-end"
                                            value="{{ number_format($displayQ4, 2, '.', '') }}"
                                            min="0" step="0.01"
                                            data-split-tip="{{ number_format($displayQ4, 2) }}"
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
                            @foreach($customFields ?? [] as $cf)
                            <td>
                                @php $cvVal = $cvMap[$cf->id]->value ?? '' @endphp
                                @if($cf->field_type === 'select')
                                <select class="form-select form-select-sm custom-field-input"
                                    data-field-id="{{ $cf->id }}"
                                    onchange="scheduleAutoSave()">
                                    <option value="">—</option>
                                    @foreach($cf->options ?? [] as $opt)
                                    <option value="{{ $opt }}" {{ $cvVal === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                    @endforeach
                                </select>
                                @elseif($cf->field_type === 'boolean')
                                <input type="checkbox" class="form-check-input custom-field-input"
                                    data-field-id="{{ $cf->id }}"
                                    {{ $cvVal ? 'checked' : '' }}
                                    onchange="scheduleAutoSave()">
                                @elseif($cf->field_type === 'number')
                                <input type="number" class="form-control form-control-sm custom-field-input text-end"
                                    data-field-id="{{ $cf->id }}"
                                    value="{{ $cvVal }}"
                                    placeholder="{{ $cf->placeholder ?? '0' }}"
                                    step="any" min="0"
                                    oninput="scheduleAutoSave()">
                                @else
                                <input type="text" class="form-control form-control-sm custom-field-input"
                                    data-field-id="{{ $cf->id }}"
                                    value="{{ $cvVal }}"
                                    placeholder="{{ $cf->placeholder ?? '' }}"
                                    oninput="scheduleAutoSave()">
                                @endif
                            </td>
                            @endforeach

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
                            @foreach($customFields ?? [] as $cf)
                            <td>
                                @php $cvVal = $cvMap[$cf->id]->value ?? '' @endphp
                                @if($cf->field_type === 'select')
                                <select class="form-select form-select-sm custom-field-input" data-field-id="{{ $cf->id }}" onchange="scheduleAutoSave()">
                                    <option value="">—</option>
                                    @foreach($cf->options ?? [] as $opt)<option value="{{ $opt }}" {{ $cvVal === $opt ? 'selected' : '' }}>{{ $opt }}</option>@endforeach
                                </select>
                                @elseif($cf->field_type === 'boolean')
                                <input type="checkbox" class="form-check-input custom-field-input" data-field-id="{{ $cf->id }}" {{ $cvVal ? 'checked' : '' }} onchange="scheduleAutoSave()">
                                @elseif($cf->field_type === 'number')
                                <input type="number" class="form-control form-control-sm custom-field-input text-end" data-field-id="{{ $cf->id }}" value="{{ $cvVal }}" placeholder="{{ $cf->placeholder ?? '0' }}" step="any" min="0" oninput="scheduleAutoSave()">
                                @else
                                <input type="text" class="form-control form-control-sm custom-field-input" data-field-id="{{ $cf->id }}" value="{{ $cvVal }}" placeholder="{{ $cf->placeholder ?? '' }}" oninput="scheduleAutoSave()">
                                @endif
                            </td>
                            @endforeach

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
                            @foreach($customFields ?? [] as $cf)
                            <td>
                                @php $cvVal = $cvMap[$cf->id]->value ?? '' @endphp
                                @if($cf->field_type === 'select')
                                <select class="form-select form-select-sm custom-field-input" data-field-id="{{ $cf->id }}" onchange="scheduleAutoSave()">
                                    <option value="">—</option>
                                    @foreach($cf->options ?? [] as $opt)<option value="{{ $opt }}" {{ $cvVal === $opt ? 'selected' : '' }}>{{ $opt }}</option>@endforeach
                                </select>
                                @elseif($cf->field_type === 'boolean')
                                <input type="checkbox" class="form-check-input custom-field-input" data-field-id="{{ $cf->id }}" {{ $cvVal ? 'checked' : '' }} onchange="scheduleAutoSave()">
                                @elseif($cf->field_type === 'number')
                                <input type="number" class="form-control form-control-sm custom-field-input text-end" data-field-id="{{ $cf->id }}" value="{{ $cvVal }}" placeholder="{{ $cf->placeholder ?? '0' }}" step="any" min="0" oninput="scheduleAutoSave()">
                                @else
                                <input type="text" class="form-control form-control-sm custom-field-input" data-field-id="{{ $cf->id }}" value="{{ $cvVal }}" placeholder="{{ $cf->placeholder ?? '' }}" oninput="scheduleAutoSave()">
                                @endif
                            </td>
                            @endforeach

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
                            @foreach($customFields ?? [] as $cf)
                            <td class="small text-muted">{{ $cvMap[$cf->id]->value ?? '—' }}</td>
                            @endforeach
                        @endif
                    </tr>
                    {{-- ── Sub-entry rows ── --}}
                    @php
                        $subRows    = $item->subEntries;
                        $isTemplated = false;
                        if ($hasTemplatedSubs) {
                            $isTemplated = true;
                            $subRows = $item->accountCode->subEntryTemplates->map(fn($tpl) => (object)[
                                'id'           => null,
                                'name'         => $tpl->name,
                                'quantity'     => null, 'rate' => null, 'frequency' => null,
                                'm1_amount'    => 0, 'm2_amount'  => 0, 'm3_amount'  => 0,
                                'm4_amount'    => 0, 'm5_amount'  => 0, 'm6_amount'  => 0,
                                'm7_amount'    => 0, 'm8_amount'  => 0, 'm9_amount'  => 0,
                                'm10_amount'   => 0, 'm11_amount' => 0, 'm12_amount' => 0,
                                'total_amount' => 0,
                            ]);
                        }
                    @endphp
                    @foreach($subRows as $sub)
                    @php
                        $subQ1 = $sub->m1_amount + $sub->m2_amount + $sub->m3_amount;
                        $subQ2 = $sub->m4_amount + $sub->m5_amount + $sub->m6_amount;
                        $subQ3 = $sub->m7_amount + $sub->m8_amount + $sub->m9_amount;
                        $subQ4 = $sub->m10_amount + $sub->m11_amount + $sub->m12_amount;
                    @endphp
                    <tr class="{{ $isTemplated ? 'sub-entry-row sub-entry-new' : 'sub-entry-row' }}"
                        data-sub-id="{{ $sub->id ?? '' }}"
                        data-parent-id="{{ $item->id }}"
                        data-cat="{{ $catIdx }}"
                        style="background:#F0F4FF;border-left:3px solid #818CF8">
                        <td style="padding-left:28px" class="small">
                            @if($budgetVersion->isEditable())
                            <div style="display:flex;align-items:center;gap:5px">
                                <span class="sub-row-number" style="flex-shrink:0;min-width:18px;font-size:11px;font-weight:700;color:#818CF8;text-align:right">{{ $loop->iteration }}</span>
                                <input type="text"
                                       class="form-control form-control-sm sub-name-input"
                                       value="{{ $sub->name }}"
                                       placeholder="Sub-item name"
                                       style="min-width:130px;font-size:12px"
                                       oninput="scheduleAutoSave()">
                                <button type="button"
                                        onclick="removeSubRow(this)"
                                        title="Remove this sub-item"
                                        style="flex-shrink:0;width:20px;height:20px;border-radius:50%;
                                               background:#FEE2E2;color:#991B1B;border:1px solid #FECACA;
                                               cursor:pointer;font-size:11px;font-weight:700;
                                               display:flex;align-items:center;justify-content:center;
                                               padding:0;line-height:1">
                                    ×
                                </button>
                            </div>
                            @else
                            <span style="color:#4338CA;font-weight:500">↳ {{ $loop->iteration }}. {{ $sub->name }}</span>
                            @endif
                        </td>

                        @if($budgetVersion->isEditable() && $calcMode !== 'none')
                            <td>
                                <input type="number"
                                    class="form-control form-control-sm sub-qty text-end"
                                    value="{{ $sub->quantity ?? '' }}"
                                    min="0" step="any" placeholder="0"
                                    oninput="subLiveUpdate(this)">
                            </td>
                            <td>
                                @if($adminSetsRate)
                                @php $displayRate = $sub->rate ?? $item->accountCode->default_rate ?? $item->accountCode->category->default_rate ?? 1; @endphp
                                <input type="number" class="form-control form-control-sm sub-rate text-end"
                                    value="{{ $displayRate }}" min="0" step="any" readonly
                                    style="background:#F8FAFC;color:#475569;cursor:not-allowed;">
                                @else
                                <input type="number" class="form-control form-control-sm sub-rate text-end"
                                    value="{{ $sub->rate ?? $item->rate ?? '' }}" min="0" step="any" placeholder="0.00"
                                    oninput="subLiveUpdate(this)">
                                @endif
                            </td>
                            @if($calcMode === 'qty_rate_freq')
                            <td>
                                @if($adminSetsFreq)
                                @php $displayFreq = $sub->frequency ?? $item->accountCode->default_frequency ?? $item->accountCode->category->default_frequency ?? 1; @endphp
                                <input type="number" class="form-control form-control-sm sub-freq text-end"
                                    value="{{ $displayFreq }}" min="0" step="any" readonly
                                    style="background:#F8FAFC;color:#475569;cursor:not-allowed;">
                                @else
                                <input type="number" class="form-control form-control-sm sub-freq text-end"
                                    value="{{ $sub->frequency ?? $item->frequency ?? '' }}" min="0" step="any" placeholder="1"
                                    oninput="subLiveUpdate(this)">
                                @endif
                            </td>
                            @endif
                            {{-- Year-total cell with split balance indicator when manualSplit --}}
                            <td class="text-end fw-semibold" style="color:#4338CA;min-width:130px">
                                <span class="sub-year-total">{{ number_format($sub->total_amount, 2) }}</span>
                                @if($manualSplit)
                                <div class="sub-split-remain"
                                     data-computed="{{ $sub->total_amount }}"
                                     style="font-size:10px;font-weight:600;margin-top:3px;line-height:1.2">&nbsp;</div>
                                <div style="display:flex;gap:3px;margin-top:4px;flex-wrap:wrap">
                                    <button type="button" onclick="autoSplitEqualSub(this)"
                                            title="Split total equally"
                                            style="font-size:9px;padding:1px 6px;border-radius:4px;background:#EEF2FF;color:#4338CA;border:1px solid #C7D2FE;cursor:pointer;white-space:nowrap;display:inline-block;line-height:1.5;font-weight:600;">÷ equal</button>
                                    <button type="button" onclick="clearSplitInputsSub(this)"
                                            title="Reset to 0"
                                            style="font-size:9px;padding:1px 6px;border-radius:4px;background:#FEE2E2;color:#991B1B;border:1px solid #FECACA;cursor:pointer;white-space:nowrap;display:inline-block;line-height:1.5;font-weight:600;">✕ 0</button>
                                </div>
                                @endif
                            </td>
                            @if($entryMode === 'monthly')
                                @if($manualSplit)
                                    @foreach(range(1,12) as $mn)
                                    @php $mnVal = $sub->{'m'.$mn.'_amount'}; @endphp
                                    <td><input type="number"
                                        class="form-control form-control-sm split-input sub-m{{ $mn }}-split text-end"
                                        value="{{ $mnVal }}" min="0" step="0.01"
                                        data-split-tip="{{ number_format($mnVal, 2) }}"
                                        oninput="updateSubSplitBalance(this)"></td>
                                    @endforeach
                                @else
                                    @foreach(range(1,12) as $mn)
                                    <td class="text-end small text-muted sub-m{{ $mn }}-display">
                                        {{ number_format($sub->{'m'.$mn.'_amount'}, 2) }}
                                    </td>
                                    @endforeach
                                @endif
                            @else
                                @if($manualSplit)
                                    @php [$sq1,$sq2,$sq3,$sq4] = [$subQ1,$subQ2,$subQ3,$subQ4]; @endphp
                                    @foreach([['sub-q1-split',$sq1],['sub-q2-split',$sq2],['sub-q3-split',$sq3],['sub-q4-split',$sq4]] as [$cls,$qv])
                                    <td><input type="number"
                                        class="form-control form-control-sm split-input {{ $cls }} text-end"
                                        value="{{ number_format($qv, 2, '.', '') }}" min="0" step="0.01"
                                        data-split-tip="{{ number_format($qv, 2) }}"
                                        oninput="updateSubSplitBalance(this)"></td>
                                    @endforeach
                                @else
                                    <td class="text-end small text-muted sub-q1-display">{{ number_format($subQ1, 2) }}</td>
                                    <td class="text-end small text-muted sub-q2-display">{{ number_format($subQ2, 2) }}</td>
                                    <td class="text-end small text-muted sub-q3-display">{{ number_format($subQ3, 2) }}</td>
                                    <td class="text-end small text-muted sub-q4-display">{{ number_format($subQ4, 2) }}</td>
                                @endif
                            @endif

                        @elseif($budgetVersion->isEditable() && $entryMode === 'monthly')
                            @foreach(range(1,12) as $mn)
                            <td><input type="number"
                                class="form-control form-control-sm sub-m-input sub-m{{ $mn }} text-end"
                                value="{{ $sub->{'m'.$mn.'_amount'} }}"
                                min="0" step="0.01" oninput="subDirectUpdate(this)"></td>
                            @endforeach
                            <td class="text-end small fw-semibold sub-year-total" style="color:#4338CA">
                                {{ number_format($sub->total_amount, 2) }}
                            </td>

                        @elseif($budgetVersion->isEditable())
                            <td><input type="number" class="form-control form-control-sm sub-q-input sub-q1 text-end"
                                value="{{ $subQ1 }}" min="0" step="0.01" oninput="subDirectUpdate(this)"></td>
                            <td><input type="number" class="form-control form-control-sm sub-q-input sub-q2 text-end"
                                value="{{ $subQ2 }}" min="0" step="0.01" oninput="subDirectUpdate(this)"></td>
                            <td><input type="number" class="form-control form-control-sm sub-q-input sub-q3 text-end"
                                value="{{ $subQ3 }}" min="0" step="0.01" oninput="subDirectUpdate(this)"></td>
                            <td><input type="number" class="form-control form-control-sm sub-q-input sub-q4 text-end"
                                value="{{ $subQ4 }}" min="0" step="0.01" oninput="subDirectUpdate(this)"></td>
                            <td class="text-end small fw-semibold sub-year-total" style="color:#4338CA">
                                {{ number_format($sub->total_amount, 2) }}
                            </td>

                        @else
                            {{-- Read-only sub-entry --}}
                            @if($calcMode !== 'none')
                                <td class="text-end small">{{ $sub->quantity !== null ? number_format($sub->quantity, 4) : '—' }}</td>
                                <td class="text-end small">{{ $sub->rate      !== null ? number_format($sub->rate, 4)     : '—' }}</td>
                                @if($calcMode === 'qty_rate_freq')
                                <td class="text-end small">{{ $sub->frequency !== null ? number_format($sub->frequency, 4) : '—' }}</td>
                                @endif
                                <td class="text-end small fw-semibold" style="color:#4338CA">{{ number_format($sub->total_amount, 2) }}</td>
                                @if($entryMode === 'monthly')
                                    @foreach(range(1,12) as $mn)
                                    <td class="text-end small text-muted">{{ number_format($sub->{'m'.$mn.'_amount'}, 2) }}</td>
                                    @endforeach
                                @else
                                <td class="text-end small text-muted">{{ number_format($subQ1, 2) }}</td>
                                <td class="text-end small text-muted">{{ number_format($subQ2, 2) }}</td>
                                <td class="text-end small text-muted">{{ number_format($subQ3, 2) }}</td>
                                <td class="text-end small text-muted">{{ number_format($subQ4, 2) }}</td>
                                @endif
                            @elseif($entryMode === 'monthly')
                                @foreach(range(1,12) as $mn)
                                <td class="text-end small">{{ number_format($sub->{'m'.$mn.'_amount'}, 2) }}</td>
                                @endforeach
                                <td class="text-end small fw-semibold" style="color:#4338CA">{{ number_format($sub->total_amount, 2) }}</td>
                            @else
                                <td class="text-end small">{{ number_format($subQ1, 2) }}</td>
                                <td class="text-end small">{{ number_format($subQ2, 2) }}</td>
                                <td class="text-end small">{{ number_format($subQ3, 2) }}</td>
                                <td class="text-end small">{{ number_format($subQ4, 2) }}</td>
                                <td class="text-end small fw-semibold" style="color:#4338CA">{{ number_format($sub->total_amount, 2) }}</td>
                            @endif
                        @endif

                        {{-- Supplementary / Effective / Notes: blank for sub-entries --}}
                        <td></td><td></td>
                        @if($budgetVersion->isEditable())<td></td>@endif
                        @foreach($customFields ?? [] as $cf)<td></td>@endforeach
                    </tr>
                    @endforeach
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
        @foreach($customFields ?? [] as $cf)
        <td></td>
        @endforeach
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
    const CUSTOM_FIELD_COUNT = {{ count($customFields ?? []) }};

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

        const indicator = row.querySelector('.split-remain');

        let computed;
        if (row.dataset.hasSubs === '1') {
            // Parent is driven by sub-entries — use the live displayed year total
            computed = parseFloat((row.querySelector('.year-total-num')?.textContent || '').replace(/,/g,'')) || 0;
        } else {
            const qty  = parseFloat(row.querySelector('.qty-input')?.value)  || 0;
            const rate = parseFloat(row.querySelector('.rate-input')?.value) || 0;
            const freq = CALC_MODE === 'qty_rate_freq'
                ? parseFloat(row.querySelector('.freq-input')?.value || '1')
                : 1;
            const liveComputed = Math.round(qty * rate * freq * 100) / 100;
            // In direct-entry mode (no qty/rate inputs) liveComputed is 0;
            // fall back to the server-rendered total stored in data-computed.
            computed = liveComputed > 0
                ? liveComputed
                : parseFloat(indicator?.dataset?.computed || '0');
        }

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

        // ── Border highlight + custom tooltip data on split inputs ──
        let statusColor, statusText;
        if (balanced) {
            statusColor = '#6EE7B7'; statusText = '✓ Balanced';
        } else if (remaining > 0) {
            statusColor = '#FCD34D'; statusText = `Under by ${numFmt(remaining)}`;
        } else {
            statusColor = '#FCA5A5'; statusText = `Over by ${numFmt(Math.abs(remaining))}`;
        }

        row.querySelectorAll('.split-input').forEach(el => {
            el.style.outline      = balanced
                ? '2px solid #10B981'
                : '2px solid ' + (remaining < 0 ? '#F43F5E' : '#F59E0B');
            el.style.borderRadius = '4px';

            const fmtVal = numFmt(parseFloat(el.value) || 0);
            el.dataset.splitTip      = fmtVal;
            el.dataset.splitTipSub   = statusText;
            el.dataset.splitTipColor = statusColor;
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

    // ── Clear (reset to 0) all split inputs on a row ─────────────────────────
    function clearSplitInputs(btn) {
        const row = btn.closest('tr[data-item-id]');
        if (!row) return;
        row.querySelectorAll('.split-input').forEach(inp => { inp.value = '0.00'; });
        updateSplitBalance(row.querySelector('.split-input') || row);
        scheduleAutoSave?.();
    }

    // ── Split ALL rows equally at once ────────────────────────────────────────
    function splitAllEqual() {
        document.querySelectorAll('tr[data-item-id]').forEach(row => {
            if (!row.querySelector('.split-input')) return;
            const qty  = parseFloat(row.querySelector('.qty-input')?.value)  || 0;
            const rate = parseFloat(row.querySelector('.rate-input')?.value) || 0;
            const freq = CALC_MODE === 'qty_rate_freq'
                ? parseFloat(row.querySelector('.freq-input')?.value || '1') : 1;
            const liveComputed = Math.round(qty * rate * freq * 100) / 100;
            const indicator    = row.querySelector('.split-remain');
            const total        = liveComputed > 0
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
        });
        scheduleAutoSave?.();
    }

    // ── Clear ALL rows to 0 at once ───────────────────────────────────────────
    function clearAll() {
        document.querySelectorAll('tr[data-item-id]').forEach(row => {
            if (!row.querySelector('.split-input')) return;
            row.querySelectorAll('.split-input').forEach(inp => { inp.value = '0.00'; });
            updateSplitBalance(row.querySelector('.split-input') || row);
        });
        scheduleAutoSave?.();
    }

    // ── Custom floating tooltip for split inputs ────────────────────────────
    (function () {
        const tip = document.createElement('div');
        tip.id = 'split-tip';
        tip.style.cssText = [
            'position:fixed', 'z-index:9999', 'display:none',
            'background:#1E293B', 'color:#fff', 'border-radius:6px',
            'padding:6px 12px', 'font-size:12px', 'line-height:1.6',
            'pointer-events:none', 'white-space:nowrap',
            'box-shadow:0 4px 14px rgba(0,0,0,.35)'
        ].join(';');
        document.body.appendChild(tip);

        function showTip(inp, x, y) {
            if (!inp.dataset.splitTip) return;
            tip.innerHTML =
                `<span style="font-size:13px;font-weight:700">${inp.dataset.splitTip}</span>` +
                `<br><span style="color:${inp.dataset.splitTipColor || '#94A3B8'}">${inp.dataset.splitTipSub || ''}</span>`;
            tip.style.display = 'block';
            const tw = tip.offsetWidth, th = tip.offsetHeight;
            const top = y - th - 10;
            tip.style.left = Math.max(4, Math.min(x - tw / 2, window.innerWidth - tw - 4)) + 'px';
            tip.style.top  = (top < 4 ? y + 22 : top) + 'px';
        }

        document.addEventListener('mouseover', function (e) {
            const inp = e.target.closest('.split-input');
            if (!inp) { tip.style.display = 'none'; return; }
            const r = inp.getBoundingClientRect();
            showTip(inp, r.left + r.width / 2, r.top);
        });
        document.addEventListener('mouseout', function (e) {
            if (e.target.closest('.split-input')) tip.style.display = 'none';
        });
    })();

    // ── Initial setup after DOM ready ────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('tr[data-item-id]').forEach(row => {
            if (row.querySelector('.split-input')) updateSplitBalance(row);
        });
        // Lock parent rows that already have sub-entries
        document.querySelectorAll('tr[data-item-id][data-has-subs="1"]').forEach(row => {
            setParentInputsReadonly(row, true);
        });
        // Initialize balance indicators on existing sub-entry rows with split inputs
        document.querySelectorAll('tr.sub-entry-row').forEach(sub => {
            if (sub.querySelector('input.split-input')) updateSubSplitBalance(sub);
        });
    });

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
                let rowTotal;
                if (row.dataset.hasSubs === '1') {
                    // Parent with sub-entries: read the displayed year total
                    const ytNum = row.querySelector('.year-total-num');
                    rowTotal = parseFloat((ytNum?.textContent || '').replace(/,/g,'')) || 0;
                } else {
                    const qty  = parseFloat(row.querySelector('.qty-input')?.value)  || 0;
                    const rate = parseFloat(row.querySelector('.rate-input')?.value) || 0;
                    // Empty freq defaults to 1 so totals stay correct when field is blank
                    const freq = CALC_MODE === 'qty_rate_freq'
                        ? parseFloat(row.querySelector('.freq-input')?.value || '1')
                        : 1;
                    rowTotal = qty * rate * freq;
                }
                orig += rowTotal;
                if (ENTRY_MODE === 'monthly') {
                    if (row.dataset.hasSubs === '1') {
                        for (let m = 1; m <= 12; m++) {
                            const inp = row.querySelector(`.m${m}-split`) || row.querySelector(`.m${m}-display`) || row.querySelector(`.m${m}`);
                            ps[`m${m}`] += parseFloat((inp?.value || inp?.textContent || '').replace(/,/g,'')) || 0;
                        }
                    } else {
                        const share = Math.round(rowTotal / 12 * 100) / 100;
                        for (let m = 1; m <= 12; m++) {
                            ps[`m${m}`] += (m === 12 ? Math.round((rowTotal - share * 11) * 100) / 100 : share);
                        }
                    }
                } else {
                    if (row.dataset.hasSubs === '1') {
                        ['q1','q2','q3','q4'].forEach(k => {
                            const inp = row.querySelector(`.${k}-split`) || row.querySelector(`.${k}-display`) || row.querySelector(`.${k}`);
                            ps[k] += parseFloat((inp?.value || inp?.textContent || '').replace(/,/g,'')) || 0;
                        });
                    } else {
                        const qShare = rowTotal / 4;
                        ps.q1 += qShare; ps.q2 += qShare; ps.q3 += qShare;
                        ps.q4 += rowTotal - qShare * 3;
                    }
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
                if (row.dataset.hasSubs === '1') {
                    // Use the sub-entry-driven displayed values
                    for (let n = 1; n <= 12; n++) {
                        const inp = row.querySelector(`.m${n}-split`) || row.querySelector(`.m${n}-display`) || row.querySelector(`.m${n}`);
                        const v = parseFloat((inp?.value || inp?.textContent || '').replace(/,/g,'')) || 0;
                        mTotals[n-1] += v; orig += v;
                    }
                } else if (CALC_MODE !== 'none') {
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
                if (row.dataset.hasSubs === '1') {
                    const getQ = (sel) => {
                        const el = row.querySelector(sel);
                        return parseFloat((el?.value || el?.textContent || '').replace(/,/g,'')) || 0;
                    };
                    const rq1 = getQ('.q1-split') || getQ('.q1-display') || getQ('.q1');
                    const rq2 = getQ('.q2-split') || getQ('.q2-display') || getQ('.q2');
                    const rq3 = getQ('.q3-split') || getQ('.q3-display') || getQ('.q3');
                    const rq4 = getQ('.q4-split') || getQ('.q4-display') || getQ('.q4');
                    q1 += rq1; q2 += rq2; q3 += rq3; q4 += rq4;
                    orig += rq1+rq2+rq3+rq4;
                } else if (CALC_MODE !== 'none') {
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

    function collectCustomFields() {
        const custom = {};
        document.querySelectorAll('tr[data-item-id]').forEach(row => {
            const itemId = row.dataset.itemId;
            row.querySelectorAll('.custom-field-input').forEach(input => {
                const fieldId = input.dataset.fieldId;
                if (!fieldId) return;
                if (!custom[itemId]) custom[itemId] = {};
                custom[itemId][fieldId] = input.type === 'checkbox' ? (input.checked ? '1' : '0') : input.value;
            });
        });
        return custom;
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
                body: JSON.stringify({ items: collectItems(), custom_fields: collectCustomFields(), sub_entries: collectAllSubEntries() }),
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
                return text.length > 0 && !text.includes('Balanced');
            });
            const unbalancedSubs = [...document.querySelectorAll('tr.sub-entry-row')].filter(sub => {
                const ind = sub.querySelector('.sub-split-remain');
                if (!ind) return false;
                const text = ind.textContent.trim();
                return text.length > 0 && !text.includes('Balanced');
            });
            const total = unbalancedRows.length + unbalancedSubs.length;
            if (total > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Unbalanced Splits',
                    html: `<b>${total} row${total === 1 ? '' : 's'}</b> ${total === 1 ? 'has' : 'have'} period splits that don't add up to the line total.<br><br>Look for <span style="color:#F59E0B">amber (↓ left)</span> or <span style="color:#F43F5E">red (↑ over)</span> indicators in the Year Total column. Sub-item rows must also balance.`,
                    confirmButtonText: 'OK, I\'ll fix it',
                    confirmButtonColor: '#E65C00',
                });
                return;
            }
        }

        // ── Sub-item total balance check (all calc modes) ─────────────────────
        // SUM(sub-entry year totals) must equal the parent's displayed year total
        const subMismatch = [];
        document.querySelectorAll('tr[data-item-id][data-has-subs="1"]').forEach(parentRow => {
            const subRows = [...document.querySelectorAll(`.sub-entry-row[data-parent-id="${parentRow.dataset.itemId}"]`)];
            if (!subRows.length) return;
            const parentTotal = parseFloat((parentRow.querySelector('.year-total-num')?.textContent || '').replace(/,/g,'')) || 0;
            let subSum = 0;
            subRows.forEach(sub => {
                subSum += parseFloat((sub.querySelector('.sub-year-total')?.textContent || '').replace(/,/g,'')) || 0;
            });
            subSum = Math.round(subSum * 100) / 100;
            const pTotal = Math.round(parentTotal * 100) / 100;
            if (Math.abs(subSum - pTotal) > 0.02) subMismatch.push({ parentRow, subSum, pTotal });
        });
        if (subMismatch.length > 0) {
            const n = subMismatch.length;
            Swal.fire({
                icon: 'warning',
                title: 'Sub-item Total Mismatch',
                html: `<b>${n} line item${n === 1 ? '' : 's'}</b> ${n === 1 ? 'has' : 'have'} sub-item totals that do not equal the parent line total.<br><br>The sum of all sub-items must always equal the parent line total before submitting.`,
                confirmButtonText: 'OK, I\'ll fix it',
                confirmButtonColor: '#E65C00',
            });
            return;
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

    // Keep session alive — ping every 10 minutes
    setInterval(() => {
        fetch('/dashboard', { credentials: 'same-origin', cache: 'no-store' }).catch(() => {});
    }, 10 * 60 * 1000);

    // ══════════════════════════════════════════════════════════════════════════
    // SUB-ENTRIES
    // ══════════════════════════════════════════════════════════════════════════

    // Lock / unlock parent row period inputs when sub-entries exist
    function setParentInputsReadonly(parentRow, readonly) {
        parentRow.dataset.hasSubs = readonly ? '1' : '0';
        // Use individual properties (not cssText) so outline set by updateSplitBalance is preserved
        const applyLock = (inp) => {
            inp.readOnly = readonly;
            inp.style.background  = readonly ? '#EEF2FF' : '';
            inp.style.color       = readonly ? '#4338CA' : '';
            inp.style.cursor      = readonly ? 'not-allowed' : '';
        };
        parentRow.querySelectorAll('.q-input, .split-input').forEach(applyLock);
        if (CALC_MODE !== 'none') {
            parentRow.querySelectorAll('.qty-input, .rate-input, .freq-input').forEach(applyLock);
        }
        parentRow.querySelectorAll('[onclick^="autoSplitEqual"], [onclick^="clearSplitInputs"]')
            .forEach(btn => { btn.style.display = readonly ? 'none' : ''; });
    }

    // Handle qty/rate/freq change on a sub-entry row (calcMode != none)
    function subLiveUpdate(input) {
        const sub = input.closest('.sub-entry-row');
        if (!sub) return;
        const qty  = parseFloat(sub.querySelector('.sub-qty')?.value)  || 0;
        const rate = parseFloat(sub.querySelector('.sub-rate')?.value) || 0;
        const freq = CALC_MODE === 'qty_rate_freq'
            ? parseFloat(sub.querySelector('.sub-freq')?.value || '1') : 1;
        const total = Math.round(qty * rate * freq * 100) / 100;

        const ytEl = sub.querySelector('.sub-year-total');
        if (ytEl) ytEl.textContent = numFmt(total);

        // Keep data-computed on balance indicator in sync
        const indicator = sub.querySelector('.sub-split-remain');
        if (indicator) indicator.dataset.computed = total;

        if (MANUAL_SPLIT) {
            // With manual split: just update balance; user distributes manually
            updateSubSplitBalance(sub);
        } else {
            // Without manual split: auto-distribute to display spans
            if (ENTRY_MODE === 'monthly') {
                const share = Math.floor(total / 12 * 100) / 100;
                const last  = Math.round((total - share * 11) * 100) / 100;
                for (let m = 1; m <= 12; m++) {
                    const el = sub.querySelector(`.sub-m${m}-display`);
                    if (el) el.textContent = numFmt(m === 12 ? last : share);
                }
            } else {
                const share = Math.floor(total / 4 * 100) / 100;
                const last  = Math.round((total - share * 3) * 100) / 100;
                [1,2,3,4].forEach((q,i) => {
                    const el = sub.querySelector(`.sub-q${q}-display`);
                    if (el) el.textContent = numFmt(i === 3 ? last : share);
                });
            }
            updateParentFromSubs(sub.dataset.parentId);
        }
    }

    // Handle period input change on a sub-entry row (direct-entry modes)
    function subDirectUpdate(input) {
        const sub = input.closest('.sub-entry-row');
        if (!sub) return;
        let total = 0;
        if (ENTRY_MODE === 'monthly') {
            for (let m = 1; m <= 12; m++)
                total += parseFloat(sub.querySelector(`.sub-m${m}`)?.value) || 0;
        } else {
            ['sub-q1','sub-q2','sub-q3','sub-q4'].forEach(c => {
                total += parseFloat(sub.querySelector('.' + c)?.value) || 0;
            });
        }
        const ytEl = sub.querySelector('.sub-year-total');
        if (ytEl) ytEl.textContent = numFmt(total);
        updateParentFromSubs(sub.dataset.parentId);
    }

    // ── Sub-entry split balance (mirrors updateSplitBalance for parents) ──────
    function updateSubSplitBalance(inputOrSub) {
        const sub = inputOrSub?.closest
            ? (inputOrSub.closest('.sub-entry-row') || inputOrSub)
            : inputOrSub;
        if (!sub || !sub.classList.contains('sub-entry-row')) return;

        const qty  = parseFloat(sub.querySelector('.sub-qty')?.value)  || 0;
        const rate = parseFloat(sub.querySelector('.sub-rate')?.value) || 0;
        const freq = CALC_MODE === 'qty_rate_freq'
            ? parseFloat(sub.querySelector('.sub-freq')?.value || '1') : 1;
        const liveComputed = Math.round(qty * rate * freq * 100) / 100;
        const indicator = sub.querySelector('.sub-split-remain');
        const computed = liveComputed > 0
            ? liveComputed
            : parseFloat(indicator?.dataset?.computed || '0');

        let splitSum = 0;
        if (ENTRY_MODE === 'monthly') {
            for (let n = 1; n <= 12; n++)
                splitSum += parseFloat(sub.querySelector(`.sub-m${n}-split`)?.value) || 0;
        } else {
            [1,2,3,4].forEach(q => {
                splitSum += parseFloat(sub.querySelector(`.sub-q${q}-split`)?.value) || 0;
            });
        }
        splitSum = Math.round(splitSum * 100) / 100;

        const remaining = Math.round((computed - splitSum) * 100) / 100;
        const balanced  = Math.abs(remaining) <= 0.02;

        let statusColor, statusText;
        if (balanced) {
            statusColor = '#6EE7B7'; statusText = '✓ Balanced';
        } else if (remaining > 0) {
            statusColor = '#FCD34D'; statusText = `Under by ${numFmt(remaining)}`;
        } else {
            statusColor = '#FCA5A5'; statusText = `Over by ${numFmt(Math.abs(remaining))}`;
        }

        sub.querySelectorAll('input.split-input').forEach(el => {
            el.style.outline      = balanced ? '2px solid #10B981' : '2px solid ' + (remaining < 0 ? '#F43F5E' : '#F59E0B');
            el.style.borderRadius = '4px';
            el.dataset.splitTip      = numFmt(parseFloat(el.value) || 0);
            el.dataset.splitTipSub   = statusText;
            el.dataset.splitTipColor = statusColor;
        });

        if (indicator) {
            if (computed === 0) { indicator.textContent = ''; }
            else if (balanced) {
                indicator.innerHTML = '<span style="color:#10B981">✓ Balanced</span>';
            } else if (remaining > 0) {
                indicator.innerHTML = `<span style="color:#F59E0B">↓ ${numFmt(remaining)} left</span>`;
            } else {
                indicator.innerHTML = `<span style="color:#F43F5E">↑ ${numFmt(Math.abs(remaining))} over</span>`;
            }
        }

        updateParentFromSubs(sub.dataset.parentId);
    }

    // ── Auto-split sub-entry equally ──────────────────────────────────────────
    function autoSplitEqualSub(btn) {
        const sub = btn.closest('.sub-entry-row');
        if (!sub) return;
        const qty  = parseFloat(sub.querySelector('.sub-qty')?.value)  || 0;
        const rate = parseFloat(sub.querySelector('.sub-rate')?.value) || 0;
        const freq = CALC_MODE === 'qty_rate_freq'
            ? parseFloat(sub.querySelector('.sub-freq')?.value || '1') : 1;
        const liveComputed = Math.round(qty * rate * freq * 100) / 100;
        const indicator = sub.querySelector('.sub-split-remain');
        const total = liveComputed > 0
            ? liveComputed
            : parseFloat(indicator?.dataset?.computed || '0');
        if (total === 0) return;
        if (ENTRY_MODE === 'monthly') {
            const share = Math.floor(total / 12 * 100) / 100;
            const last  = Math.round((total - share * 11) * 100) / 100;
            for (let m = 1; m <= 12; m++) {
                const inp = sub.querySelector(`.sub-m${m}-split`);
                if (inp) inp.value = (m === 12 ? last : share).toFixed(2);
            }
        } else {
            const share = Math.floor(total / 4 * 100) / 100;
            const last  = Math.round((total - share * 3) * 100) / 100;
            [1,2,3,4].forEach((q,i) => {
                const inp = sub.querySelector(`.sub-q${q}-split`);
                if (inp) inp.value = (i === 3 ? last : share).toFixed(2);
            });
        }
        updateSubSplitBalance(sub.querySelector('input.split-input') || sub);
    }

    // ── Clear sub-entry split inputs to 0 ────────────────────────────────────
    function clearSplitInputsSub(btn) {
        const sub = btn.closest('.sub-entry-row');
        if (!sub) return;
        sub.querySelectorAll('input.split-input').forEach(inp => { inp.value = '0.00'; });
        updateSubSplitBalance(sub.querySelector('input.split-input') || sub);
    }

    // Sum all sub-entries for a parent and reflect the totals in the parent row
    function updateParentFromSubs(parentId) {
        const parentRow = document.querySelector(`tr[data-item-id="${parentId}"]`);
        if (!parentRow) return;
        const subRows = [...document.querySelectorAll(`.sub-entry-row[data-parent-id="${parentId}"]`)];

        if (subRows.length === 0) {
            setParentInputsReadonly(parentRow, false);
            updateCategoryFooter(parentRow);
            updateGrandTotals();
            return;
        }
        setParentInputsReadonly(parentRow, true);

        const readDisp = (el) => parseFloat((el?.textContent || el?.value || '').replace(/,/g,'')) || 0;

        // Sum sub qty values into parent qty (only when calcMode uses qty)
        if (CALC_MODE !== 'none') {
            let totalQty = 0;
            subRows.forEach(sub => { totalQty += parseFloat(sub.querySelector('.sub-qty')?.value) || 0; });
            const parentQtyInp = parentRow.querySelector('.qty-input');
            if (parentQtyInp) parentQtyInp.value = totalQty % 1 === 0 ? totalQty : totalQty.toFixed(4).replace(/\.?0+$/, '');
        }

        // Parent year total = direct sum of sub year-total spans (always up-to-date
        // even before period splits are filled in)
        let subYearTotal = 0;
        subRows.forEach(sub => {
            subYearTotal += parseFloat((sub.querySelector('.sub-year-total')?.textContent || '').replace(/,/g,'')) || 0;
        });
        subYearTotal = Math.round(subYearTotal * 100) / 100;
        const ytNum = parentRow.querySelector('.year-total-num');
        if (ytNum) ytNum.textContent = numFmt(subYearTotal);
        if (CALC_MODE === 'none') {
            const origEl = parentRow.querySelector('.row-original');
            if (origEl) { const n = origEl.querySelector('.year-total-num') ?? origEl; n.textContent = numFmt(subYearTotal); }
        }

        if (ENTRY_MODE === 'monthly') {
            const ms = new Array(12).fill(0);
            subRows.forEach(sub => {
                for (let m = 1; m <= 12; m++) {
                    if (CALC_MODE !== 'none' && MANUAL_SPLIT) {
                        ms[m-1] += parseFloat(sub.querySelector(`.sub-m${m}-split`)?.value) || 0;
                    } else if (CALC_MODE !== 'none') {
                        ms[m-1] += readDisp(sub.querySelector(`.sub-m${m}-display`));
                    } else {
                        ms[m-1] += parseFloat(sub.querySelector(`.sub-m${m}`)?.value) || 0;
                    }
                }
            });
            for (let m = 1; m <= 12; m++) {
                const inp = parentRow.querySelector(`.m${m}`);
                const disp = parentRow.querySelector(`.m${m}-display`);
                if (inp)  inp.value = ms[m-1].toFixed(2);
                if (disp) disp.textContent = numFmt(ms[m-1]);
                const split = parentRow.querySelector(`.m${m}-split`);
                if (split) split.value = ms[m-1].toFixed(2);
            }
        } else {
            let q1=0, q2=0, q3=0, q4=0;
            subRows.forEach(sub => {
                if (CALC_MODE !== 'none' && MANUAL_SPLIT) {
                    q1 += parseFloat(sub.querySelector('.sub-q1-split')?.value) || 0;
                    q2 += parseFloat(sub.querySelector('.sub-q2-split')?.value) || 0;
                    q3 += parseFloat(sub.querySelector('.sub-q3-split')?.value) || 0;
                    q4 += parseFloat(sub.querySelector('.sub-q4-split')?.value) || 0;
                } else if (CALC_MODE !== 'none') {
                    q1 += readDisp(sub.querySelector('.sub-q1-display'));
                    q2 += readDisp(sub.querySelector('.sub-q2-display'));
                    q3 += readDisp(sub.querySelector('.sub-q3-display'));
                    q4 += readDisp(sub.querySelector('.sub-q4-display'));
                } else {
                    q1 += parseFloat(sub.querySelector('.sub-q1')?.value) || 0;
                    q2 += parseFloat(sub.querySelector('.sub-q2')?.value) || 0;
                    q3 += parseFloat(sub.querySelector('.sub-q3')?.value) || 0;
                    q4 += parseFloat(sub.querySelector('.sub-q4')?.value) || 0;
                }
            });
            const setP = (sel, v) => {
                const el = parentRow.querySelector(sel);
                if (!el) return;
                if (el.tagName === 'INPUT') el.value = v.toFixed(2);
                else el.textContent = numFmt(v);
            };
            setP('.q1', q1); setP('.q1-display', q1); setP('.q1-split', q1);
            setP('.q2', q2); setP('.q2-display', q2); setP('.q2-split', q2);
            setP('.q3', q3); setP('.q3-display', q3); setP('.q3-split', q3);
            setP('.q4', q4); setP('.q4-display', q4); setP('.q4-split', q4);
        }

        updateCategoryFooter(parentRow);
        updateGrandTotals();
        if (MANUAL_SPLIT) updateSplitBalance(parentRow);
        scheduleAutoSave?.();
    }

    // Dynamically add a new blank sub-entry row below the last sub of this parent
    function addSubRow(parentId) {
        const parentRow = document.querySelector(`tr[data-item-id="${parentId}"]`);
        if (!parentRow) return;
        const existing = [...document.querySelectorAll(`.sub-entry-row[data-parent-id="${parentId}"]`)];
        const after = existing.length ? existing[existing.length - 1] : parentRow;

        // Inherit rate/freq from parent inputs
        const parentRate = parentRow.querySelector('.rate-input')?.value || '';
        const parentFreq = parentRow.querySelector('.freq-input')?.value || '1';

        const tr = document.createElement('tr');
        tr.className = 'sub-entry-row sub-entry-new';
        tr.dataset.subId    = '';
        tr.dataset.parentId = parentId;
        tr.dataset.cat      = parentRow.dataset.cat;
        tr.style.cssText    = 'background:#F0F4FF;border-left:3px solid #818CF8';

        const newNum = existing.length + 1;
        const nameCell = `<td style="padding-left:28px;vertical-align:middle">
            <div style="display:flex;align-items:center;gap:5px">
                <span class="sub-row-number" style="flex-shrink:0;min-width:18px;font-size:11px;font-weight:700;color:#818CF8;text-align:right">${newNum}</span>
                <input type="text" class="form-control form-control-sm sub-name-input"
                       value="" placeholder="Sub-item name" style="min-width:130px;font-size:12px"
                       oninput="scheduleAutoSave()">
                <button type="button" onclick="removeSubRow(this)" title="Remove sub-item"
                        style="flex-shrink:0;width:20px;height:20px;border-radius:50%;
                               background:#FEE2E2;color:#991B1B;border:1px solid #FECACA;
                               cursor:pointer;font-size:12px;font-weight:700;padding:0;
                               display:flex;align-items:center;justify-content:center;line-height:1">×</button>
            </div></td>`;

        let periodCells = '';
        if (CALC_MODE !== 'none') {
            periodCells += `<td><input type="number" class="form-control form-control-sm sub-qty text-end" value="" min="0" step="any" placeholder="0" oninput="subLiveUpdate(this)"></td>`;
            periodCells += `<td><input type="number" class="form-control form-control-sm sub-rate text-end" value="${parentRate}" min="0" step="any" placeholder="0.00" oninput="subLiveUpdate(this)"></td>`;
            if (CALC_MODE === 'qty_rate_freq')
                periodCells += `<td><input type="number" class="form-control form-control-sm sub-freq text-end" value="${parentFreq}" min="0" step="any" placeholder="1" oninput="subLiveUpdate(this)"></td>`;

            const splitBtns = MANUAL_SPLIT
                ? `<div class="sub-split-remain" data-computed="0"
                        style="font-size:10px;font-weight:600;margin-top:3px;line-height:1.2">&nbsp;</div>
                   <div style="display:flex;gap:3px;margin-top:4px;flex-wrap:wrap">
                       <button type="button" onclick="autoSplitEqualSub(this)"
                               style="font-size:9px;padding:1px 6px;border-radius:4px;background:#EEF2FF;color:#4338CA;border:1px solid #C7D2FE;cursor:pointer;white-space:nowrap;display:inline-block;line-height:1.5;font-weight:600;">÷ equal</button>
                       <button type="button" onclick="clearSplitInputsSub(this)"
                               style="font-size:9px;padding:1px 6px;border-radius:4px;background:#FEE2E2;color:#991B1B;border:1px solid #FECACA;cursor:pointer;white-space:nowrap;display:inline-block;line-height:1.5;font-weight:600;">✕ 0</button>
                   </div>` : '';
            periodCells += `<td class="text-end fw-semibold" style="color:#4338CA;min-width:130px"><span class="sub-year-total">0.00</span>${splitBtns}</td>`;

            if (ENTRY_MODE === 'monthly') {
                if (MANUAL_SPLIT) {
                    for (let m = 1; m <= 12; m++)
                        periodCells += `<td><input type="number" class="form-control form-control-sm split-input sub-m${m}-split text-end" value="0.00" min="0" step="0.01" data-split-tip="0.00" oninput="updateSubSplitBalance(this)"></td>`;
                } else {
                    for (let m = 1; m <= 12; m++)
                        periodCells += `<td class="text-end small text-muted sub-m${m}-display">0.00</td>`;
                }
            } else {
                if (MANUAL_SPLIT) {
                    [1,2,3,4].forEach(q =>
                        periodCells += `<td><input type="number" class="form-control form-control-sm split-input sub-q${q}-split text-end" value="0.00" min="0" step="0.01" data-split-tip="0.00" oninput="updateSubSplitBalance(this)"></td>`);
                } else {
                    periodCells += `<td class="text-end small text-muted sub-q1-display">0.00</td><td class="text-end small text-muted sub-q2-display">0.00</td><td class="text-end small text-muted sub-q3-display">0.00</td><td class="text-end small text-muted sub-q4-display">0.00</td>`;
                }
            }
        } else if (ENTRY_MODE === 'monthly') {
            for (let m = 1; m <= 12; m++)
                periodCells += `<td><input type="number" class="form-control form-control-sm sub-m${m} text-end" value="0" min="0" step="0.01" oninput="subDirectUpdate(this)"></td>`;
            periodCells += `<td class="text-end fw-semibold" style="color:#4338CA"><span class="sub-year-total">0.00</span></td>`;
        } else {
            [1,2,3,4].forEach(q =>
                periodCells += `<td><input type="number" class="form-control form-control-sm sub-q${q} text-end" value="0" min="0" step="0.01" oninput="subDirectUpdate(this)"></td>`);
            periodCells += `<td class="text-end fw-semibold" style="color:#4338CA"><span class="sub-year-total">0.00</span></td>`;
        }

        let tail = '<td></td><td></td><td></td>';
        for (let i = 0; i < CUSTOM_FIELD_COUNT; i++) tail += '<td></td>';

        tr.innerHTML = nameCell + periodCells + tail;
        after.insertAdjacentElement('afterend', tr);
        tr.querySelector('.sub-name-input')?.focus();
        setParentInputsReadonly(parentRow, true);
        scheduleAutoSave?.();
    }

    // Remove a sub-entry row
    function removeSubRow(btn) {
        const sub = btn.closest('.sub-entry-row');
        if (!sub) return;
        const parentId = sub.dataset.parentId;
        sub.remove();
        renumberSubRows(parentId);
        updateParentFromSubs(parentId);
    }

    // Update sequential numbers on all sub-rows for a parent
    function renumberSubRows(parentId) {
        document.querySelectorAll(`.sub-entry-row[data-parent-id="${parentId}"]`).forEach((sub, i) => {
            const el = sub.querySelector('.sub-row-number');
            if (el) el.textContent = i + 1;
        });
    }

    // Collect sub-entries for a single parent item ID
    function collectSubEntries(parentId) {
        const result = [];
        document.querySelectorAll(`.sub-entry-row[data-parent-id="${parentId}"]`).forEach((sub, idx) => {
            const name = sub.querySelector('.sub-name-input')?.value?.trim() || '';
            if (!name) return;
            const entry = {
                id:   sub.dataset.subId || null,
                name,
                qty:  parseFloat(sub.querySelector('.sub-qty')?.value)  || 0,
                rate: parseFloat(sub.querySelector('.sub-rate')?.value) || 0,
                freq: parseFloat(sub.querySelector('.sub-freq')?.value) || 1,
            };
            const spreadQ = (q) => {
                const t = Math.floor(q / 3 * 100) / 100;
                return [t, t, Math.round((q - t * 2) * 100) / 100];
            };
            const readDisp = el => parseFloat((el?.textContent || el?.value || '').replace(/,/g,'')) || 0;
            if (ENTRY_MODE === 'monthly') {
                for (let m = 1; m <= 12; m++) {
                    if (CALC_MODE !== 'none' && MANUAL_SPLIT)
                        entry[`m${m}`] = parseFloat(sub.querySelector(`.sub-m${m}-split`)?.value) || 0;
                    else if (CALC_MODE !== 'none')
                        entry[`m${m}`] = readDisp(sub.querySelector(`.sub-m${m}-display`));
                    else
                        entry[`m${m}`] = parseFloat(sub.querySelector(`.sub-m${m}`)?.value) || 0;
                }
            } else {
                let q1,q2,q3,q4;
                if (CALC_MODE !== 'none' && MANUAL_SPLIT) {
                    [q1,q2,q3,q4] = [1,2,3,4].map(q => parseFloat(sub.querySelector(`.sub-q${q}-split`)?.value) || 0);
                } else if (CALC_MODE !== 'none') {
                    [q1,q2,q3,q4] = [1,2,3,4].map(q => readDisp(sub.querySelector(`.sub-q${q}-display`)));
                } else {
                    [q1,q2,q3,q4] = [1,2,3,4].map(q => parseFloat(sub.querySelector(`.sub-q${q}`)?.value) || 0);
                }
                const [m1,m2,m3]=spreadQ(q1), [m4,m5,m6]=spreadQ(q2), [m7,m8,m9]=spreadQ(q3), [m10,m11,m12]=spreadQ(q4);
                Object.assign(entry,{m1,m2,m3,m4,m5,m6,m7,m8,m9,m10,m11,m12});
            }
            result.push(entry);
        });
        return result;
    }

    // Collect sub-entries for ALL items that have them
    function collectAllSubEntries() {
        const result = {};
        document.querySelectorAll('tr[data-item-id]').forEach(row => {
            const id = row.dataset.itemId;
            const subs = collectSubEntries(id);
            if (subs.length) result[id] = subs;
        });
        return result;
    }
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
