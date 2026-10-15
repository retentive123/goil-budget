@extends('layouts.app')
@section('title', 'Allocate Actuals to Departments')
@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('actuals.index') }}" class="text-muted text-decoration-none">Actuals</a>
    <span class="text-muted">/</span>
    <span>Allocate to Departments</span>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- Step 1: Pick period / account code / month ─────────────────────────────── --}}
<div class="chart-card mb-4">
    <div class="chart-title">Select Account Code & Period</div>
    <form method="GET" action="{{ route('actuals.allocate') }}" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Period</label>
            <select name="period_id" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach($periods as $p)
                <option value="{{ $p->id }}" {{ $period?->id == $p->id ? 'selected' : '' }}>
                    {{ $p->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Month</label>
            <select name="month" class="form-select form-select-sm">
                @foreach(\App\Models\BudgetActual::MONTHS as $m => $label)
                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Year</label>
            <input type="number" name="year" value="{{ $year }}"
                   class="form-control form-control-sm" min="2000" max="2100">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold mb-1">Account Code</label>
            <select name="account_code_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">— Select account code —</option>
                @foreach($accountCodes->groupBy('category.name') as $catName => $codes)
                <optgroup label="{{ $catName }}">
                    @foreach($codes as $code)
                    <option value="{{ $code->id }}"
                        {{ $selectedCode?->id == $code->id ? 'selected' : '' }}>
                        {{ $code->code }} — {{ $code->name }}
                    </option>
                    @endforeach
                </optgroup>
                @endforeach
            </select>
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-sm btn-outline-primary w-100">Go</button>
        </div>
        @if($period)
        <input type="hidden" name="period_id" value="{{ $period->id }}">
        @endif
    </form>
</div>

{{-- Step 2: Enter amounts per department ────────────────────────────────────── --}}
@if($selectedCode && $deptLines->isNotEmpty())

<div class="chart-card">
    <div class="chart-title">
        Allocate: {{ $selectedCode->code }} — {{ $selectedCode->name }}
        &nbsp;<span style="font-size:12px;color:var(--slate)">
            {{ \App\Models\BudgetActual::MONTHS[$month] }} {{ $year }}
        </span>
    </div>

    <form method="POST" action="{{ route('actuals.storeAllocation') }}">
        @csrf
        <input type="hidden" name="period_id"       value="{{ $period->id }}">
        <input type="hidden" name="month"           value="{{ $month }}">
        <input type="hidden" name="year"            value="{{ $year }}">
        <input type="hidden" name="account_code_id" value="{{ $selectedCode->id }}">

        <div class="row g-2 mb-3">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Entry Date (applied to all)</label>
                <input type="date" name="entry_date"
                       class="form-control form-control-sm"
                       value="{{ now()->format('Y-m-d') }}">
            </div>
            <div class="col-md-5">
                <label class="form-label small fw-semibold mb-1">
                    Total Amount (optional — for reference)
                </label>
                <input type="number" id="totalRef" step="0.01" min="0"
                       class="form-control form-control-sm"
                       placeholder="Enter total to auto-split by %">
                <div class="form-text small">Enter a total and click Auto-Split to distribute.</div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" onclick="autoSplit()" class="btn btn-sm btn-outline-secondary w-100">
                    Auto-Split
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-hover mb-3" id="allocTable">
                <thead style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--slate)">
                    <tr>
                        <th>Department</th>
                        <th class="text-end" style="width:140px">Budget (Full Year)</th>
                        <th class="text-end" style="width:100px">% of Total</th>
                        <th class="text-end" style="width:140px">Amount *</th>
                        <th style="width:180px">Narration</th>
                        <th style="width:140px">Reference</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalBudget = $deptLines->sum('total_amount'); @endphp
                    @foreach($deptLines as $dIdx => $lineItem)
                    @php
                        $dept = $lineItem->budgetVersion->department;
                        $pct  = $totalBudget > 0 ? round(($lineItem->total_amount / $totalBudget) * 100, 1) : 0;
                    @endphp
                    <tr>
                        <input type="hidden" name="allocations[{{ $dIdx }}][line_item_id]"
                               value="{{ $lineItem->id }}">
                        <td>
                            <div style="font-weight:600;font-size:13px">{{ $dept->name }}</div>
                            @if($dept->code)
                            <div style="font-size:11px;color:var(--slate)">{{ $dept->code }}</div>
                            @endif
                        </td>
                        <td class="text-end small">{{ number_format($lineItem->total_amount, 2) }}</td>
                        <td class="text-end small" data-pct="{{ $pct }}">{{ $pct }}%</td>
                        <td>
                            <input type="number"
                                   name="allocations[{{ $dIdx }}][amount]"
                                   class="form-control form-control-sm alloc-amount text-end"
                                   step="0.01" min="0" placeholder="0.00">
                        </td>
                        <td>
                            <input type="text"
                                   name="allocations[{{ $dIdx }}][description]"
                                   class="form-control form-control-sm"
                                   placeholder="Narration / purpose">
                        </td>
                        <td>
                            <input type="text"
                                   name="allocations[{{ $dIdx }}][reference]"
                                   class="form-control form-control-sm"
                                   placeholder="Invoice / PO #">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:#F8FAFC;font-weight:700;font-size:13px">
                        <td>Total</td>
                        <td class="text-end">{{ number_format($totalBudget, 2) }}</td>
                        <td class="text-end">100%</td>
                        <td class="text-end" id="allocTotal">0.00</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Save Allocation
            </button>
            <a href="{{ route('actuals.index', ['period_id'=>$period->id]) }}"
               class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>

@elseif($selectedCode && $deptLines->isEmpty())
<div class="alert alert-warning">
    No departments have an approved budget for <strong>{{ $selectedCode->code }} — {{ $selectedCode->name }}</strong>
    in period <strong>{{ $period?->name }}</strong>. Only account codes that appear in at least one approved
    department budget are available for allocation.
</div>
@elseif($period && !$selectedCode)
<div class="chart-card text-center text-muted py-5">
    <i class="fas fa-arrow-up d-block mb-2" style="font-size:2rem;opacity:.3"></i>
    Select an account code above to see departments to allocate to.
</div>
@endif

<script>
function updateAllocTotal() {
    let total = 0;
    document.querySelectorAll('.alloc-amount').forEach(inp => {
        total += parseFloat(inp.value) || 0;
    });
    const el = document.getElementById('allocTotal');
    if (el) el.textContent = total.toLocaleString(undefined, { minimumFractionDigits: 2 });
}

function autoSplit() {
    const totalRef = parseFloat(document.getElementById('totalRef').value) || 0;
    if (!totalRef) { alert('Enter a total amount first.'); return; }

    const rows     = document.querySelectorAll('#allocTable tbody tr');
    rows.forEach(row => {
        const pctCell = row.querySelector('[data-pct]');
        const input   = row.querySelector('.alloc-amount');
        if (!pctCell || !input) return;
        const pct = parseFloat(pctCell.dataset.pct) || 0;
        input.value = (totalRef * pct / 100).toFixed(2);
    });
    updateAllocTotal();
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.alloc-amount').forEach(inp =>
        inp.addEventListener('input', updateAllocTotal)
    );
});
</script>

@endsection
