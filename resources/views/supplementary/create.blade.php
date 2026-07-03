@extends('layouts.app')
@section('title', 'Request Supplementary Budget')
@section('content')

@php
    $tabDefs = [
        'pnl'     => ['label' => 'Revenue &amp; Expenses',  'types' => ['revenue','expense','both'],        'accent' => '#1B2A4A', 'light' => '#EFF6FF'],
        'capex'   => ['label' => 'Capital Expenditure',     'types' => ['capital_expenditure'],             'accent' => '#78350F', 'light' => '#FEF3C7'],
        'balance' => ['label' => 'Assets &amp; Liabilities','types' => ['assets','liabilities'],            'accent' => '#4C1D95', 'light' => '#F5F3FF'],
    ];

    $tabGroups = [];
    foreach ($tabDefs as $tk => $td) {
        $filtered = $version->lineItems->filter(
            fn($i) => in_array($i->accountCode->category->budget_type ?? 'expense', $td['types'])
        );
        $tabGroups[$tk] = $filtered->groupBy('accountCode.category.name');
    }

    // Restore previous selections if validation failed
    $oldItems    = old('items', []);
    $hasOldInput = !empty($oldItems);

    // Determine active tab: first non-empty tab normally;
    // if restoring old input, open the tab that contains the first selected item.
    $firstTab = 'pnl';
    foreach ($tabDefs as $tk => $td) {
        if ($tabGroups[$tk]->isNotEmpty()) { $firstTab = $tk; break; }
    }
    if ($hasOldInput) {
        $selectedIds = array_keys($oldItems);
        foreach ($tabDefs as $tk => $td) {
            $tabItemIds = $tabGroups[$tk]->flatten()->pluck('id')->map(fn($id) => (string)$id)->toArray();
            if (array_intersect($selectedIds, $tabItemIds)) { $firstTab = $tk; break; }
        }
    }

    $globalIdx = 0; // unique counter across all tabs for cat IDs
@endphp

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('supplementary.index') }}"
       class="text-muted text-decoration-none small">← Supplementary</a>
    <span class="text-muted">/</span>
    <span class="small">New Request</span>
</div>

{{-- Info banner --}}
<div class="chart-card mb-4" style="border-left:4px solid #F43F5E;background:#FFF5F5">
    <div style="font-size:13px;font-weight:700;color:#991B1B;margin-bottom:6px">
        Supplementary Budget Request — {{ $department->name }} &mdash; {{ $currentPeriod->name }}
    </div>
    <p style="font-size:12px;color:#991B1B;margin-bottom:0">
        Tick the line items that need additional budget and enter the extra amount for each.
        All selected items will be submitted as one batch for Finance approval.
        Finance may approve a different amount per line item.
    </p>
</div>

@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0 small">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ route('supplementary.store') }}" id="suppForm">
@csrf
<input type="hidden" name="budget_period_id" value="{{ $currentPeriod->id }}">
<input type="hidden" name="department_id"    value="{{ $department->id }}">

{{-- ── Budget-type tabs ── --}}
<div class="chart-card p-0 mb-3" style="overflow:hidden">

    {{-- Tab nav --}}
    <ul class="nav nav-tabs px-3 pt-2" style="border-bottom:1px solid #E2E8F0;gap:4px">
        @foreach($tabDefs as $tk => $td)
        @if($tabGroups[$tk]->isNotEmpty())
        <li class="nav-item">
            <a class="nav-link {{ $tk === $firstTab ? 'active' : '' }}"
               data-bs-toggle="tab"
               href="#stab-{{ $tk }}"
               style="font-size:13px;font-weight:600;padding:8px 16px">
                {!! $td['label'] !!}
                <span class="badge rounded-pill ms-1"
                      id="tab-badge-{{ $tk }}"
                      style="font-size:10px;background:#E2E8F0;color:var(--slate)">0</span>
            </a>
        </li>
        @endif
        @endforeach
    </ul>

    {{-- Tab panes --}}
    <div class="tab-content">
    @foreach($tabDefs as $tk => $td)
    @if($tabGroups[$tk]->isNotEmpty())
    <div class="tab-pane {{ $tk === $firstTab ? 'show active' : '' }}"
         id="stab-{{ $tk }}">

        {{-- Toolbar --}}
        <div class="d-flex align-items-center gap-2 px-3 py-2"
             style="border-bottom:1px solid #E2E8F0;background:#F8FAFC;flex-wrap:wrap">
            <input type="text"
                   class="form-control form-control-sm"
                   style="max-width:260px;border-radius:20px;font-size:12px"
                   placeholder="Search code or name…"
                   oninput="searchTab('stab-{{ $tk }}', this.value)">
            <div class="d-flex gap-1 ms-auto">
                <button type="button"
                        class="btn btn-sm btn-outline-secondary"
                        style="font-size:11px;border-radius:6px;padding:3px 10px"
                        onclick="expandAll('stab-{{ $tk }}')">
                    ↕ Expand All
                </button>
                <button type="button"
                        class="btn btn-sm btn-outline-secondary"
                        style="font-size:11px;border-radius:6px;padding:3px 10px"
                        onclick="collapseAll('stab-{{ $tk }}')">
                    ↕ Collapse All
                </button>
            </div>
        </div>

        {{-- Categories --}}
        <div class="px-3 py-2">
        @foreach($tabGroups[$tk] as $catName => $catItems)
        @php $ci = $globalIdx++; @endphp
        <div class="cat-block mb-2" id="catblock-{{ $ci }}"
             style="border:1px solid #E2E8F0;border-radius:8px;overflow:hidden">

            {{-- Category header --}}
            <div class="d-flex justify-content-between align-items-center px-3 py-2"
                 style="background:#F1F5F9;cursor:default">
                <div class="d-flex align-items-center gap-2">
                    <input type="checkbox"
                           class="form-check-input cat-chk"
                           id="cat-chk-{{ $ci }}"
                           data-ci="{{ $ci }}"
                           onchange="selectCat({{ $ci }}, this.checked)"
                           style="cursor:pointer;width:15px;height:15px">
                    <label for="cat-chk-{{ $ci }}"
                           class="fw-bold mb-0"
                           style="font-size:12.5px;cursor:pointer;color:var(--navy)">
                        {{ $catName }}
                    </label>
                    <span class="badge rounded-pill"
                          id="cat-badge-{{ $ci }}"
                          style="font-size:10px;background:#E2E8F0;color:var(--slate)">
                        0 / {{ $catItems->count() }}
                    </span>
                </div>
                <button type="button"
                        class="btn btn-link btn-sm p-0 text-muted cat-toggle-btn"
                        data-ci="{{ $ci }}"
                        onclick="toggleCat({{ $ci }}, this)">
                    <span class="ct-arr"
                          style="display:inline-block;transition:transform .2s;font-size:11px">▼</span>
                </button>
            </div>

            {{-- Items table --}}
            <div class="cat-body" id="cat-body-{{ $ci }}">
                <table class="table table-sm mb-0" style="font-size:12px">
                    <thead>
                        <tr style="background:#F8FAFC;color:var(--slate);font-size:11px;
                                   text-transform:uppercase;letter-spacing:.4px">
                            <th style="width:36px"></th>
                            <th>Account Code / Name</th>
                            <th class="text-end" style="white-space:nowrap">Approved Budget</th>
                            <th class="text-end" style="white-space:nowrap">YTD Actual</th>
                            <th class="text-end" style="white-space:nowrap">Remaining</th>
                            <th style="width:170px;white-space:nowrap">Additional Request (GHS)</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($catItems as $item)
                    @php
                        $ytd = $actualsPerItem->get($item->id, 0);
                        $eff = $item->effectiveBudget();
                        $rem = $eff - $ytd;
                        $pct = $eff > 0 ? round(($ytd / $eff) * 100, 1) : 0;
                        // On re-render after validation: restore from old input.
                        // On fresh load: use preselectedItemId if provided.
                        $oldEntry = $oldItems[$item->id] ?? null;
                        $sel      = $hasOldInput ? ($oldEntry !== null) : ($preselectedItemId == $item->id);
                        $oldAmt   = $oldEntry['requested_amount'] ?? '';
                        $amtStyle = !$sel
                            ? 'background:#F8FAFC;color:var(--slate)'
                            : ($oldAmt !== '' ? 'background:#D1FAE5;color:#065F46' : 'background:#FEF3C7;color:#92400E');
                        $searchStr = strtolower($item->accountCode->code . ' ' . $item->accountCode->name . ' ' . $catName);
                    @endphp
                    <tr class="item-row"
                        id="row-{{ $item->id }}"
                        data-item="{{ $item->id }}"
                        data-ci="{{ $ci }}"
                        data-tab="stab-{{ $tk }}"
                        data-search="{{ $searchStr }}"
                        style="{{ $sel ? 'background:#EFF6FF' : '' }}">
                        <td class="align-middle text-center" style="padding-left:12px">
                            <input type="checkbox"
                                   class="form-check-input item-chk"
                                   id="chk-{{ $item->id }}"
                                   data-item="{{ $item->id }}"
                                   data-ci="{{ $ci }}"
                                   data-tab="stab-{{ $tk }}"
                                   {{ $sel ? 'checked' : '' }}
                                   onchange="toggleItem({{ $item->id }}, this.checked)"
                                   style="cursor:pointer;width:15px;height:15px">
                        </td>
                        <td class="align-middle">
                            <label for="chk-{{ $item->id }}" style="cursor:pointer;margin:0">
                                <span class="fw-semibold" style="color:var(--navy)">
                                    {{ $item->accountCode->code }}
                                </span>
                                <span class="text-muted"> — {{ $item->accountCode->name }}</span>
                            </label>
                        </td>
                        <td class="text-end align-middle">
                            {{ number_format($eff, 2) }}
                        </td>
                        <td class="text-end align-middle"
                            style="color:{{ $pct > 90 ? '#F43F5E' : '#10B981' }}">
                            {{ number_format($ytd, 2) }}
                            <div style="font-size:10px;color:var(--slate)">{{ $pct }}%</div>
                        </td>
                        <td class="text-end align-middle"
                            style="color:{{ $rem < 0 ? '#F43F5E' : '#374151' }};
                                   font-weight:{{ $rem < 0 ? '700' : '400' }}">
                            {{ number_format($rem, 2) }}
                        </td>
                        <td class="align-middle" style="padding-right:12px">
                            <input type="number"
                                   id="amt-{{ $item->id }}"
                                   name="items[{{ $item->id }}][requested_amount]"
                                   min="1" step="0.01" placeholder="0.00"
                                   value="{{ $oldAmt }}"
                                   class="form-control form-control-sm"
                                   {{ $sel ? '' : 'disabled' }}
                                   oninput="colorAmt(this);updateSummary()"
                                   style="{{ $amtStyle }}">
                            <input type="hidden"
                                   id="lid-{{ $item->id }}"
                                   name="items[{{ $item->id }}][budget_line_item_id]"
                                   value="{{ $item->id }}"
                                   {{ $sel ? '' : 'disabled' }}>
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
        </div>
    </div>
    @endif
    @endforeach
    </div>{{-- /tab-content --}}
</div>{{-- /chart-card tabs --}}

{{-- Summary bar --}}
<div class="chart-card mb-3 py-3 px-4"
     style="background:#EFF6FF;border:1px solid #BFDBFE">
    <div class="d-flex justify-content-between align-items-center">
        <div style="font-size:13px;color:var(--navy)">
            <span id="sel-count" style="font-weight:700">0</span> item(s) selected
        </div>
        <div style="font-size:15px;font-weight:700;color:var(--navy)">
            Total Requested: GHS&nbsp;<span id="sel-total">0.00</span>
        </div>
    </div>
</div>

{{-- Justification + evidence --}}
<div class="chart-card mb-3">
    <h6 class="fw-bold mb-3" style="font-size:13px;color:var(--navy)">
        Justification &amp; Evidence
    </h6>
    <div class="mb-3">
        <label class="form-label small fw-semibold">
            Justification
            <span style="color:var(--slate);font-weight:400">(min 20 characters — applies to all selected items)</span>
        </label>
        <textarea name="justification" rows="4"
                  class="form-control @error('justification') is-invalid @enderror"
                  placeholder="Explain why additional budget is needed. Include what has changed since the original budget was approved and why existing funds are insufficient…">{{ old('justification') }}</textarea>
        @error('justification')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="mb-0">
        <label class="form-label small fw-semibold">
            Supporting Evidence
            <span style="color:var(--slate);font-weight:400">(optional)</span>
        </label>
        <textarea name="supporting_evidence" rows="2"
                  class="form-control"
                  placeholder="Quote references, contract numbers, approval emails, or any other supporting context…">{{ old('supporting_evidence') }}</textarea>
    </div>
</div>

<div class="d-flex gap-2 mb-4">
    <button type="submit" id="submitBtn" class="btn btn-sm" disabled
            style="background:var(--navy);color:#fff;border-radius:8px;
                   padding:8px 24px;opacity:.45">
        Submit Request
    </button>
    <a href="{{ route('supplementary.index') }}"
       class="btn btn-sm btn-outline-secondary"
       style="border-radius:8px;padding:8px 20px">
        Cancel
    </a>
</div>
</form>

<script>
/* ── Amount input colour: yellow = selected/empty, green = has value ── */
function colorAmt(input) {
    const hasVal = input.value !== '' && parseFloat(input.value) > 0;
    input.style.background = hasVal ? '#D1FAE5' : '#FEF3C7';
    input.style.color      = hasVal ? '#065F46'  : '#92400E';
}

/* ── Row toggle ── */
function toggleItem(itemId, enabled) {
    const row = document.getElementById('row-' + itemId);
    const amt = document.getElementById('amt-' + itemId);
    const lid = document.getElementById('lid-' + itemId);

    amt.disabled = lid.disabled = !enabled;
    row.style.background = enabled ? '#EFF6FF' : '';

    if (!enabled) {
        amt.value = '';
        amt.style.background = '#F8FAFC';
        amt.style.color      = 'var(--slate)';
    } else {
        colorAmt(amt);
        amt.focus();
    }

    const chk = document.getElementById('chk-' + itemId);
    syncCatChk(chk.dataset.ci);
    updateSummary();
}

/* ── Category select-all ── */
function selectCat(ci, checked) {
    document.querySelectorAll('.item-chk[data-ci="' + ci + '"]').forEach(chk => {
        // only affect visible rows (not hidden by search)
        const row = document.getElementById('row-' + chk.dataset.item);
        if (row && row.style.display === 'none') return;
        if (chk.checked !== checked) {
            chk.checked = checked;
            toggleItem(parseInt(chk.dataset.item), checked);
        }
    });
}

function syncCatChk(ci) {
    const chks  = [...document.querySelectorAll('.item-chk[data-ci="' + ci + '"]')]
                    .filter(c => document.getElementById('row-' + c.dataset.item)?.style.display !== 'none');
    const cat   = document.getElementById('cat-chk-' + ci);
    const badge = document.getElementById('cat-badge-' + ci);
    const n     = chks.filter(c => c.checked).length;
    const total = document.querySelectorAll('.item-chk[data-ci="' + ci + '"]').length;
    cat.checked       = chks.length > 0 && n === chks.length;
    cat.indeterminate = n > 0 && n < chks.length;
    badge.textContent = n + ' / ' + total;
    badge.style.background = n > 0 ? '#DBEAFE' : '#E2E8F0';
    badge.style.color      = n > 0 ? '#1E40AF' : 'var(--slate)';
}

/* ── Category collapse ── */
function toggleCat(ci, btn) {
    const body = document.getElementById('cat-body-' + ci);
    const arr  = btn.querySelector('.ct-arr');
    const show = body.style.display === 'none';
    body.style.display  = show ? '' : 'none';
    arr.style.transform = show ? '' : 'rotate(-90deg)';
}

/* ── Expand / Collapse All ── */
function expandAll(tabId) {
    document.querySelectorAll('#' + tabId + ' .cat-body').forEach(b => {
        b.style.display = '';
    });
    document.querySelectorAll('#' + tabId + ' .ct-arr').forEach(a => {
        a.style.transform = '';
    });
}
function collapseAll(tabId) {
    document.querySelectorAll('#' + tabId + ' .cat-body').forEach(b => {
        b.style.display = 'none';
    });
    document.querySelectorAll('#' + tabId + ' .ct-arr').forEach(a => {
        a.style.transform = 'rotate(-90deg)';
    });
}

/* ── Search ── */
function searchTab(tabId, query) {
    const q = query.toLowerCase().trim();
    const tab = document.getElementById(tabId);
    if (!tab) return;

    // Show/hide item rows
    tab.querySelectorAll('.item-row').forEach(row => {
        const match = !q || row.dataset.search.includes(q);
        row.style.display = match ? '' : 'none';
    });

    // Show/hide category blocks; auto-expand if search active
    tab.querySelectorAll('.cat-block').forEach(block => {
        const ci = block.id.replace('catblock-', '');
        const visibleRows = [...block.querySelectorAll('.item-row')]
                            .filter(r => r.style.display !== 'none');
        block.style.display = visibleRows.length ? '' : 'none';
        if (q && visibleRows.length) {
            // expand so results are visible
            const body = block.querySelector('.cat-body');
            const arr  = block.querySelector('.ct-arr');
            if (body) body.style.display = '';
            if (arr)  arr.style.transform = '';
        }
        syncCatChk(ci);
    });
}

/* ── Summary bar + submit state ── */
function updateSummary() {
    let count = 0, total = 0;
    document.querySelectorAll('.item-chk:checked').forEach(chk => {
        const amt = parseFloat(document.getElementById('amt-' + chk.dataset.item)?.value) || 0;
        count++;
        total += amt;
    });
    document.getElementById('sel-count').textContent = count;
    document.getElementById('sel-total').textContent =
        total.toLocaleString('en-GH', {minimumFractionDigits:2, maximumFractionDigits:2});

    // Update per-tab badges
    document.querySelectorAll('.tab-pane').forEach(pane => {
        const n = pane.querySelectorAll('.item-chk:checked').length;
        const badge = document.getElementById('tab-badge-' + pane.id.replace('stab-', ''));
        if (badge) {
            badge.textContent = n;
            badge.style.background = n > 0 ? '#DBEAFE' : '#E2E8F0';
            badge.style.color      = n > 0 ? '#1E40AF' : 'var(--slate)';
        }
    });

    const btn = document.getElementById('submitBtn');
    btn.disabled      = count === 0;
    btn.style.opacity = count === 0 ? '.45' : '1';
}

/* ── Init ── */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.item-chk:checked').forEach(chk => {
        syncCatChk(chk.dataset.ci);
        const amt = document.getElementById('amt-' + chk.dataset.item);
        if (amt) colorAmt(amt);
    });
    updateSummary();
});
</script>
@endsection
