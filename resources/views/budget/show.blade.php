@extends('layouts.app')
@section('title', 'Budget Entry')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="fw-bold mb-0">
            {{ $budgetVersion->department->name }}
            @if($budgetVersion->department->isServiceStation())
                <span class="badge ms-1" style="background:#EFF6FF;color:#1D4ED8;font-size:10px;font-weight:600;">Station</span>
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
            &nbsp;
            <span class="badge" style="background:{{ $entryMode === 'monthly' ? '#0369A1' : '#6B7280' }};font-size:10px;">
                {{ $entryMode === 'monthly' ? 'Monthly entry' : 'Quarterly entry' }}
            </span>
        </p>
    </div>

    <div class="d-flex gap-2 align-items-center flex-wrap">
        <div class="btn-group btn-group-sm" role="group" aria-label="View mode">
            <span class="btn btn-secondary" style="pointer-events:none;">Classic View</span>
            <a href="{{ route('budget.show-pnl', $budgetVersion) }}" class="btn btn-outline-secondary">P&amp;L View</a>
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
        @if($budgetVersion->isEditable())
        <span id="save-status" class="text-muted small"></span>
        <button id="save-btn" class="btn btn-outline-primary btn-sm" onclick="saveBudget()">Save</button>
        <a href="{{ route('budget.confirm', $budgetVersion) }}" class="btn bg-goil-orange btn-sm">
            Submit for Approval →
        </a>
        @endif
    </div>
</div>

{{-- Grand total bar --}}
@php
    $lineItems = $budgetVersion->lineItems ?? collect();
    $totalSupplementary = $lineItems->sum(fn($i) => $i->approvedSupplementaryTotal());
    $effectiveTotal = $grandTotals['total'] ;
@endphp

<div class="card mb-3 border-0 bg-goil-orange">
    <div class="card-body py-2">
        <div class="row text-center">
            <div class="col">
                <div class="small text-white-50">Q1</div>
                <div class="fw-bold" id="gt-q1">
                    {{ currency() }} {{ number_format($grandTotals['q1'], 2) }}
                </div>
            </div>
            <div class="col">
                <div class="small text-white-50">Q2</div>
                <div class="fw-bold" id="gt-q2">
                    {{ currency() }} {{ number_format($grandTotals['q2'], 2) }}
                </div>
            </div>
            <div class="col">
                <div class="small text-white-50">Q3</div>
                <div class="fw-bold" id="gt-q3">
                    {{ currency() }} {{ number_format($grandTotals['q3'], 2) }}
                </div>
            </div>
            <div class="col">
                <div class="small text-white-50">Q4</div>
                <div class="fw-bold" id="gt-q4">
                    {{ currency() }} {{ number_format($grandTotals['q4'], 2) }}
                </div>
            </div>
            <div class="col border-start border-secondary">
                <div class="small text-white-50">Original Total</div>
                <div class="fw-bold" id="gt-total">
                    {{ currency() }} {{ number_format($grandTotals['total'], 2) }}
                </div>
                @if($totalSupplementary > 0)
                <div class="small" style="color: #6EE7B7;">
                    +{{ currency() }} {{ number_format($totalSupplementary, 2) }} supp.
                </div>
                @endif
            </div>
            <div class="col border-start border-secondary">
                <div class="small text-white-50">Effective Total</div>
                <div class="fw-bold fs-5" style="color: var(--gold);" id="gt-effective">
                    {{ currency() }} {{ number_format($effectiveTotal, 2) }}
                </div>
                @if($totalSupplementary > 0)
                <div class="small" style="color: rgba(255,255,255,0.5);">
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
            <div style="font-size:13px;font-weight:600;color:var(--navy)">
                Excel Import / Export
            </div>
            <div style="font-size:12px;color:var(--slate)">
                Download the template, fill it in Excel, then upload to save time.
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('ie.budget.download', $budgetVersion) }}"
               class="btn btn-sm btn-outline-success">
                ↓ Download Template
            </a>

            <button type="button"
                    onclick="document.getElementById('uploadPanel').classList.toggle('d-none')"
                    class="btn btn-sm btn-outline-primary">
                ↑ Upload Excel
            </button>
        </div>
    </div>

    <div id="uploadPanel" class="d-none mt-3 pt-3 border-top">
        <form method="POST"
              action="{{ route('ie.budget.upload', $budgetVersion) }}"
              enctype="multipart/form-data">
            @csrf
            <div class="d-flex gap-2 align-items-end">
                <div class="flex-grow-1">
                    <label class="form-label small fw-semibold mb-1">
                        Select filled Excel file
                    </label>
                    <input type="file" name="file"
                           accept=".xlsx,.xls"
                           class="form-control form-control-sm">
                </div>
                <button type="submit" class="btn btn-sm btn-primary">
                    Upload & Save
                </button>
            </div>
            <div class="form-text">
                Only .xlsx and .xls files accepted. Max 5MB.
                Use the downloaded template — do not change the file structure.
            </div>
        </form>
    </div>

    {{-- Show import errors if any --}}
    @if(session('import_errors'))
    <div class="mt-3 pt-3 border-top">
        <div style="font-size:12px;font-weight:600;color:#991B1B;margin-bottom:6px">
            Import Errors:
        </div>
        @foreach(session('import_errors') as $err)
        <div style="font-size:11px;color:#991B1B;padding:2px 0">⚠ {{ $err }}</div>
        @endforeach
    </div>
    @endif
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
<div class="tab-pane fade {{ $btab['active'] ? 'show active' : '' }} p-3"
     id="{{ $btab['id'] }}" role="tabpanel">
    @forelse($btab['summary'] as $categoryName => $categoryData)
    @php
        $catSupp = $categoryData['items']->sum(fn($i) => $i->approvedSupplementaryTotal());
    @endphp
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
            <span class="fw-semibold small text-uppercase">{{ $categoryName }}</span>
            <span class="small text-muted">
                Original: <strong class="cat-header-orig">{{ currency() }} {{ number_format($categoryData['total'], 2) }}</strong>
                @if($catSupp > 0)
                <span style="color:#10B981;">
                    +{{ currency() }} {{ number_format($catSupp, 2) }} supp.
                </span>
                <span class="cat-header-eff" style="color:var(--navy);font-weight:700;">
                    | Effective: {{ currency() }} {{ number_format($categoryData['total'] + $catSupp, 2) }}
                </span>
                @endif
            </span>
        </div>
        @php
            $monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        @endphp
        <div class="card-body p-0" style="{{ $entryMode === 'monthly' ? 'overflow-x:auto;' : '' }}">
            <table class="table table-sm table-hover mb-0" style="{{ $entryMode === 'monthly' ? 'min-width:1400px;' : '' }}">
                <thead class="table-light">
                    <tr>
                        <th style="min-width:180px;">Account</th>
                        @if($entryMode === 'monthly')
                            @foreach($monthLabels as $ml)
                                <th class="text-end" style="min-width:90px;">{{ $ml }} ({{ currency() }})</th>
                            @endforeach
                        @else
                            <th class="text-end">Q1 ({{ currency() }})</th>
                            <th class="text-end">Q2 ({{ currency() }})</th>
                            <th class="text-end">Q3 ({{ currency() }})</th>
                            <th class="text-end">Q4 ({{ currency() }})</th>
                        @endif
                        <th class="text-end">Original Total</th>
                        <th class="text-end">Supplementary</th>
                        <th class="text-end">Effective Total</th>
                        @if($budgetVersion->isEditable())
                        <th>Notes</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
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
                    @endphp
                    <tr data-item-id="{{ $item->id }}" data-supp="{{ $itemSupp }}">
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
                        @if($budgetVersion->isEditable())
                            @if($entryMode === 'monthly')
                                @foreach(range(1,12) as $mn)
                                <td><input type="number"
                                    class="form-control form-control-sm q-input m{{ $mn }} text-end"
                                    value="{{ $item->{'m'.$mn.'_amount'} }}"
                                    min="0" step="0.01" oninput="liveUpdate(this)"></td>
                                @endforeach
                            @else
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
                            @endif
                            <td class="text-end text-muted small row-original">
                                {{ number_format($item->total_amount, 2) }}
                            </td>
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
                        @else
                            @if($entryMode === 'monthly')
                                @foreach(range(1,12) as $mn)
                                <td class="text-end small">{{ number_format($item->{'m'.$mn.'_amount'}, 2) }}</td>
                                @endforeach
                            @else
                                <td class="text-end small">{{ number_format($item->q1_amount, 2) }}</td>
                                <td class="text-end small">{{ number_format($item->q2_amount, 2) }}</td>
                                <td class="text-end small">{{ number_format($item->q3_amount, 2) }}</td>
                                <td class="text-end small">{{ number_format($item->q4_amount, 2) }}</td>
                            @endif
                            <td class="text-end small text-muted">{{ number_format($item->total_amount, 2) }}</td>
                            <td class="text-end" style="color:{{ $itemSupp > 0 ? '#10B981' : 'inherit' }}">
                                {{ $itemSupp > 0 ? '+'.number_format($itemSupp, 2) : '—' }}
                            </td>
                            <td class="text-end small fw-semibold">{{ number_format($itemEffective, 2) }}</td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
                <tfoot style="background:#F8FAFC;font-weight:700;" data-cat-supp="{{ $catSupp }}">
                    <tr>
                        <td>Category Total</td>
                        @if($entryMode === 'monthly')
                            @foreach(range(1,12) as $mn)
                            <td class="text-end">{{ number_format($categoryData["m{$mn}"], 2) }}</td>
                            @endforeach
                        @else
                            <td class="text-end">{{ number_format($categoryData['q1'], 2) }}</td>
                            <td class="text-end">{{ number_format($categoryData['q2'], 2) }}</td>
                            <td class="text-end">{{ number_format($categoryData['q3'], 2) }}</td>
                            <td class="text-end">{{ number_format($categoryData['q4'], 2) }}</td>
                        @endif
                        <td class="text-end">{{ number_format($categoryData['total'], 2) }}</td>
                        <td class="text-end" style="color:{{ $catSupp > 0 ? '#10B981' : 'inherit' }}">
                            {{ $catSupp > 0 ? '+'.number_format($catSupp, 2) : '—' }}
                        </td>
                        <td class="text-end" style="color:var(--navy)">
                            {{ number_format($categoryData['total'] + $catSupp, 2) }}
                        </td>
                        @if($budgetVersion->isEditable())
                        <td></td>
                        @endif
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
</div>
@endforeach
</div>

@if($budgetVersion->isEditable())
<script>
    const SAVE_URL = "{{ route('budget.save', $budgetVersion) }}";
    const CSRF     = document.querySelector('meta[name="csrf-token"]')?.content
                     || "{{ csrf_token() }}";
    const CUR      = "{{ currency() }}";

    let autoSaveTimer = null;
    let isSaving      = false;

    function numFmt(v) {
        return parseFloat(v || 0).toLocaleString('en-GH', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });
    }

    const ENTRY_MODE = '{{ $entryMode }}';

    // Called on every input keystroke — updates row totals, category footer, grand total bar
    function liveUpdate(input) {
        const row  = input.closest('tr');
        const supp = parseFloat(row.dataset.supp) || 0;
        let orig = 0;

        if (ENTRY_MODE === 'monthly') {
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
        if (origEl) origEl.textContent = numFmt(orig);
        if (effEl)  effEl.textContent  = numFmt(orig + supp);

        updateCategoryFooter(row.closest('table'));
        updateGrandTotals();
        scheduleAutoSave();
    }

    function updateCategoryFooter(table) {
        const rows  = table.querySelectorAll('tbody tr[data-item-id]');
        const tfoot = table.querySelector('tfoot');
        if (!tfoot) return;
        const catSupp = parseFloat(tfoot.dataset.catSupp) || 0;
        const cells   = tfoot.querySelectorAll('td');

        if (ENTRY_MODE === 'monthly') {
            const ms = new Array(12).fill(0);
            let orig = 0;
            rows.forEach(row => {
                for (let m = 1; m <= 12; m++) {
                    const v = parseFloat(row.querySelector(`.m${m}`)?.value) || 0;
                    ms[m - 1] += v;
                    orig += v;
                }
            });
            // cells[0]=label, cells[1..12]=months, cells[13]=orig, cells[14]=supp, cells[15]=eff
            for (let m = 0; m < 12; m++) {
                if (cells[m + 1]) cells[m + 1].textContent = numFmt(ms[m]);
            }
            if (cells[13]) cells[13].textContent = numFmt(orig);
            if (cells[15]) cells[15].textContent = numFmt(orig + catSupp);
            const card = table.closest('.card');
            if (card) {
                const hOrig = card.querySelector('.cat-header-orig');
                const hEff  = card.querySelector('.cat-header-eff');
                if (hOrig) hOrig.textContent = CUR + ' ' + numFmt(orig);
                if (hEff)  hEff.textContent  = '| Effective: ' + CUR + ' ' + numFmt(orig + catSupp);
            }
        } else {
            let q1=0, q2=0, q3=0, q4=0, orig=0;
            rows.forEach(row => {
                const rq1 = parseFloat(row.querySelector('.q1')?.value) || 0;
                const rq2 = parseFloat(row.querySelector('.q2')?.value) || 0;
                const rq3 = parseFloat(row.querySelector('.q3')?.value) || 0;
                const rq4 = parseFloat(row.querySelector('.q4')?.value) || 0;
                q1 += rq1; q2 += rq2; q3 += rq3; q4 += rq4;
                orig += rq1 + rq2 + rq3 + rq4;
            });
            // cells[0]=label, cells[1..4]=Q1-Q4, cells[5]=orig, cells[6]=supp, cells[7]=eff
            if (cells[1]) cells[1].textContent = numFmt(q1);
            if (cells[2]) cells[2].textContent = numFmt(q2);
            if (cells[3]) cells[3].textContent = numFmt(q3);
            if (cells[4]) cells[4].textContent = numFmt(q4);
            if (cells[5]) cells[5].textContent = numFmt(orig);
            if (cells[7]) cells[7].textContent = numFmt(orig + catSupp);
            const card = table.closest('.card');
            if (card) {
                const hOrig = card.querySelector('.cat-header-orig');
                const hEff  = card.querySelector('.cat-header-eff');
                if (hOrig) hOrig.textContent = CUR + ' ' + numFmt(orig);
                if (hEff)  hEff.textContent  = '| Effective: ' + CUR + ' ' + numFmt(orig + catSupp);
            }
        }
    }

    function updateGrandTotals() {
        let q1=0, q2=0, q3=0, q4=0, orig=0, totalSupp=0;
        document.querySelectorAll('tr[data-item-id]').forEach(row => {
            const supp = parseFloat(row.dataset.supp) || 0;
            totalSupp += supp;
            if (ENTRY_MODE === 'monthly') {
                const ms = [1,2,3,4,5,6,7,8,9,10,11,12].map(n =>
                    parseFloat(row.querySelector(`.m${n}`)?.value) || 0);
                q1 += ms[0]+ms[1]+ms[2];
                q2 += ms[3]+ms[4]+ms[5];
                q3 += ms[6]+ms[7]+ms[8];
                q4 += ms[9]+ms[10]+ms[11];
                orig += ms.reduce((a,b) => a+b, 0);
            } else {
                const rq1 = parseFloat(row.querySelector('.q1')?.value) || 0;
                const rq2 = parseFloat(row.querySelector('.q2')?.value) || 0;
                const rq3 = parseFloat(row.querySelector('.q3')?.value) || 0;
                const rq4 = parseFloat(row.querySelector('.q4')?.value) || 0;
                q1 += rq1; q2 += rq2; q3 += rq3; q4 += rq4;
                orig += rq1 + rq2 + rq3 + rq4;
            }
        });
        const fmt = v => CUR + ' ' + numFmt(v);
        const el  = id => document.getElementById(id);
        if (el('gt-q1'))        el('gt-q1').textContent        = fmt(q1);
        if (el('gt-q2'))        el('gt-q2').textContent        = fmt(q2);
        if (el('gt-q3'))        el('gt-q3').textContent        = fmt(q3);
        if (el('gt-q4'))        el('gt-q4').textContent        = fmt(q4);
        if (el('gt-total'))     el('gt-total').textContent     = fmt(orig);
        if (el('gt-effective')) el('gt-effective').textContent = fmt(orig + totalSupp);
    }

    function collectItems() {
        return Array.from(document.querySelectorAll('tr[data-item-id]')).map(row => {
            const notes = row.querySelector('.notes-input')?.value || '';
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
        if (isSaving) return;
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

            if (!res.ok) {
                if (status) status.textContent = 'Save failed (' + res.status + ')';
                return;
            }

            const data = await res.json();
            if (status) status.textContent = data.success ? 'Saved at ' + data.saved_at : 'Save failed.';
        } catch (e) {
            if (status) status.textContent = 'Network error — not saved.';
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
</script>
@endif

@endsection
