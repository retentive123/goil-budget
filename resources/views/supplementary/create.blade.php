@extends('layouts.app')
@section('title', 'Request Supplementary Budget')
@section('content')

<div class="row justify-content-center">
<div class="col-md-8">

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('supplementary.index') }}"
       class="text-muted text-decoration-none small">← Supplementary</a>
    <span class="text-muted">/</span>
    <span class="small">New Request</span>
</div>

{{-- Info banner --}}
<div class="chart-card mb-4"
     style="border-left:4px solid #F43F5E;background:#FFF5F5">
    <div style="font-size:13px;font-weight:700;color:#991B1B;margin-bottom:8px">
        📋 Supplementary Budget Request
    </div>
    <p style="font-size:12px;color:#991B1B;margin-bottom:0">
        A supplementary budget is an additional allocation requested when the
        approved budget for a line item is insufficient. This request will go
        through Finance approval before taking effect. During the review period,
        you cannot record actuals that exceed the original approved budget.
    </p>
</div>

<div class="chart-card">
    <h5 class="fw-bold mb-1">New Supplementary Budget Request</h5>
    <p class="text-muted small mb-4">
        {{ $department->name }} — {{ $currentPeriod->name }}
    </p>

    <form method="POST" action="{{ route('supplementary.store') }}">
        @csrf
        <input type="hidden" name="budget_period_id" value="{{ $currentPeriod->id }}">
        <input type="hidden" name="department_id"    value="{{ $department->id }}">

        {{-- Line item selector --}}
        <div class="mb-3">
            <label class="form-label small fw-semibold">Budget Line Item</label>
            <select name="budget_line_item_id"
                    id="lineItemSelect"
                    class="form-select @error('budget_line_item_id') is-invalid @enderror"
                    onchange="updateContext(this)">
                <option value="">— Select the account code that needs more budget —</option>
                @foreach($version->lineItems->groupBy('accountCode.category.name') as $cat => $items)
                <optgroup label="{{ $cat }}">
                    @foreach($items as $item)
                    @php
                        $ytd    = $actualsPerItem->get($item->id, 0);
                        $pct    = $item->total_amount > 0
                            ? round(($ytd/$item->total_amount)*100,1) : 0;
                    @endphp
                    <option value="{{ $item->id }}"
                            data-budget="{{ $item->total_amount }}"
                            data-ytd="{{ $ytd }}"
                            data-pct="{{ $pct }}"
                            data-type="{{ $item->line_type }}"
                            data-code="{{ $item->accountCode->code }}"
                            {{ ($preselectedItemId == $item->id) ? 'selected' : '' }}>
                        {{ $item->accountCode->code }} — {{ $item->accountCode->name }}
                        (Budget: GHS {{ number_format($item->total_amount,0) }}
                         / YTD: GHS {{ number_format($ytd,0) }}
                         / {{ $pct }}% used)
                    </option>
                    @endforeach
                </optgroup>
                @endforeach
            </select>
            @error('budget_line_item_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Context panel --}}
        <div id="contextPanel" style="display:none;background:#F8FAFC;
                border-radius:10px;padding:16px;margin-bottom:16px">
            <div class="row g-3">
                <div class="col-md-3">
                    <div style="font-size:11px;color:var(--slate)">Approved Budget</div>
                    <div style="font-size:16px;font-weight:700;color:var(--navy)"
                         id="ctx-budget">—</div>
                </div>
                <div class="col-md-3">
                    <div style="font-size:11px;color:var(--slate)">YTD Actual</div>
                    <div style="font-size:16px;font-weight:700;color:#10B981"
                         id="ctx-ytd">—</div>
                </div>
                <div class="col-md-3">
                    <div style="font-size:11px;color:var(--slate)">Remaining</div>
                    <div style="font-size:16px;font-weight:700" id="ctx-remaining">—</div>
                </div>
                <div class="col-md-3">
                    <div style="font-size:11px;color:var(--slate)">Utilisation</div>
                    <div style="font-size:16px;font-weight:700" id="ctx-pct">—</div>
                </div>
            </div>
            <div class="progress mt-2" style="height:6px">
                <div class="progress-bar" id="ctx-bar" style="width:0%"></div>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label small fw-semibold">
                Additional Amount Requested (GHS)
            </label>
            <input type="number" name="requested_amount"
                   value="{{ old('requested_amount') }}"
                   class="form-control @error('requested_amount') is-invalid @enderror"
                   min="1" step="0.01"
                   placeholder="0.00">
            <div class="form-text">
                Enter only the <strong>additional</strong> amount needed,
                not the total. Finance may approve a different amount.
            </div>
            @error('requested_amount')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label small fw-semibold">
                Justification
                <span style="color:var(--slate);font-weight:400">(minimum 20 characters)</span>
            </label>
            <textarea name="justification" rows="5"
                      class="form-control @error('justification') is-invalid @enderror"
                      placeholder="Explain in detail why additional budget is needed. Include what has changed since the original budget was approved, what the funds will be used for, and why it cannot be covered within the existing budget…">{{ old('justification') }}</textarea>
            @error('justification')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label class="form-label small fw-semibold">
                Supporting Evidence
                <span style="color:var(--slate);font-weight:400">(optional)</span>
            </label>
            <textarea name="supporting_evidence" rows="3"
                      class="form-control"
                      placeholder="Quote references, contract numbers, approval emails, or any other supporting context…">{{ old('supporting_evidence') }}</textarea>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-sm"
                    style="background:var(--navy);color:#fff;border-radius:8px;padding:8px 20px">
                Submit Request
            </button>
            <a href="{{ route('supplementary.index') }}"
               class="btn btn-sm btn-outline-secondary"
               style="border-radius:8px;padding:8px 20px">
                Cancel
            </a>
        </div>
    </form>
</div>

</div>
</div>

<script>
function updateContext(select) {
    const opt       = select.options[select.selectedIndex];
    const budget    = parseFloat(opt.dataset.budget || 0);
    const ytd       = parseFloat(opt.dataset.ytd    || 0);
    const pct       = parseFloat(opt.dataset.pct    || 0);
    const remaining = budget - ytd;
    const panel     = document.getElementById('contextPanel');

    if (!opt.value) { panel.style.display = 'none'; return; }

    panel.style.display = 'block';
    document.getElementById('ctx-budget').textContent =
        'GHS ' + budget.toLocaleString('en-GH',{minimumFractionDigits:2});
    document.getElementById('ctx-ytd').textContent =
        'GHS ' + ytd.toLocaleString('en-GH',{minimumFractionDigits:2});

    const remEl = document.getElementById('ctx-remaining');
    remEl.textContent = 'GHS ' + remaining.toLocaleString('en-GH',{minimumFractionDigits:2});
    remEl.style.color = remaining < 0 ? '#F43F5E' : '#10B981';

    document.getElementById('ctx-pct').textContent = pct + '%';

    const bar = document.getElementById('ctx-bar');
    bar.style.width = Math.min(pct,100) + '%';
    bar.style.background = pct > 90 ? '#F43F5E' : pct > 70 ? '#F59E0B' : '#10B981';
}

// Trigger on page load if preselected
document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('lineItemSelect');
    if (sel.value) updateContext(sel);
});
</script>
@endsection
