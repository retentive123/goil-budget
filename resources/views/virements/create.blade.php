@extends('layouts.app')
@section('title', 'New Virement Request')
@section('content')

<div class="row justify-content-center">
<div class="col-md-8">

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('virements.index') }}" class="text-muted text-decoration-none">Virements</a>
    <span class="text-muted">/</span>
    <span>New Request</span>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="alert alert-info small mb-0">
            <strong>Virement Rules:</strong> The amount transferred from any
            single account code cannot exceed the configured virement limit (same-type: {{ \App\Models\SystemSetting::get('virement_limit_pct', 10) }}%
            @if(\App\Models\SystemSetting::get('allow_cross_type_virement', false))
            , cross-type: {{ \App\Models\SystemSetting::get('cross_type_virement_limit_pct', 5) }}%
            @else
            ; cross-type virements are <strong>disabled</strong>
            @endif
            ) of that account's total approved budget.
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-4">New Virement Request</h5>
        <p class="small text-muted mb-4">
            Period: <strong>{{ $currentPeriod->name }}</strong> &nbsp;|&nbsp;
            Budget: v{{ $version->version_number }}
        </p>

        <form method="POST" action="{{ route('virements.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label small fw-semibold">
                    Transfer FROM (source account)
                </label>
                <select name="from_line_item_id"
                    id="from_item"
                    class="form-select @error('from_line_item_id') is-invalid @enderror"
                    onchange="updateMaxAmount()">
                    <option value="">— Select account to transfer from —</option>
                    @foreach($lineItems->groupBy('accountCode.category.name') as $cat => $items)
                        <optgroup label="{{ $cat }}">
                            @foreach($items as $item)
                            <option value="{{ $item->id }}"
                                data-total="{{ $item->total_amount }}"
                                data-max="{{ $item->maxVirementAmount() }}"
                                data-type="{{ $item->line_type }}"
                                {{ old('from_line_item_id') == $item->id ? 'selected' : '' }}>
                                {{ $item->accountCode->code }} —
                                {{ $item->accountCode->name }}
                                ({{ currency() }} {{ number_format($item->total_amount, 2) }})
                                [{{ $item->line_type }}]
                            </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @error('from_line_item_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div id="max-hint" class="form-text text-muted" style="display:none">
                    Maximum virement amount: <strong id="max-amount"></strong>
                </div>
                <div id="cross-type-hint" class="alert alert-warning small mt-2" style="display:none">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    <strong>Cross-type virement:</strong> The source and destination are in different budget type groups.
                    A lower limit applies (<strong id="cross-type-pct">{{ \App\Models\SystemSetting::get('cross_type_virement_limit_pct', 5) }}%</strong>).
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">
                    Transfer TO (destination account)
                </label>
                <select name="to_line_item_id"
                    class="form-select @error('to_line_item_id') is-invalid @enderror">
                    <option value="">— Select account to transfer to —</option>
                    @foreach($lineItems->groupBy('accountCode.category.name') as $cat => $items)
                        <optgroup label="{{ $cat }}">
                            @foreach($items as $item)
                            <option value="{{ $item->id }}"
                                data-type="{{ $item->line_type }}"
                                {{ old('to_line_item_id') == $item->id ? 'selected' : '' }}>
                                {{ $item->accountCode->code }} —
                                {{ $item->accountCode->name }}
                                ({{ currency() }} {{ number_format($item->total_amount, 2) }})
                                [{{ $item->line_type }}]
                            </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @error('to_line_item_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Amount ({{ currency() }})</label>
                <input type="number" name="amount"
                    id="amount"
                    value="{{ old('amount') }}"
                    class="form-control @error('amount') is-invalid @enderror"
                    min="1" step="0.01"
                    placeholder="0.00">
                @error('amount')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold">Justification</label>
                <textarea name="justification" rows="4"
                    class="form-control @error('justification') is-invalid @enderror"
                    placeholder="Explain why this virement is needed…">{{ old('justification') }}</textarea>
                @error('justification')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Submit Request</button>
                <a href="{{ route('virements.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

</div>
</div>

<script>
const SAME_TYPE_PCT   = {{ \App\Models\SystemSetting::get('virement_limit_pct', 10) }};
const CROSS_TYPE_PCT  = {{ \App\Models\SystemSetting::get('cross_type_virement_limit_pct', 5) }};
const CROSS_ALLOWED   = {{ \App\Models\SystemSetting::get('allow_cross_type_virement', false) ? 'true' : 'false' }};

function lineTypeGroup(t) {
    if (!t) return 'other';
    if (t === 'revenue' || t === 'both')         return 'revenue';
    if (t === 'expense' || t === 'ex_pump_item') return 'expense';
    if (t === 'capital_expenditure')             return 'capital';
    if (t === 'assets' || t === 'liabilities')   return 'balance';
    return 'other';
}

function updateMaxAmount() {
    const fromSel    = document.getElementById('from_item');
    const toSel      = document.querySelector('[name="to_line_item_id"]');
    const fromOption = fromSel.options[fromSel.selectedIndex];
    const toOption   = toSel  ? toSel.options[toSel.selectedIndex] : null;

    const total      = parseFloat(fromOption.dataset.total || 0);
    const fromType   = fromOption.dataset.type || '';
    const toType     = toOption  ? (toOption.dataset.type || '') : '';

    const isCross    = fromType && toType && lineTypeGroup(fromType) !== lineTypeGroup(toType);
    const pct        = isCross ? CROSS_TYPE_PCT : SAME_TYPE_PCT;
    const max        = total * pct / 100;

    const hint       = document.getElementById('max-hint');
    const maxSpan    = document.getElementById('max-amount');
    const crossHint  = document.getElementById('cross-type-hint');

    if (total) {
        maxSpan.textContent = '{{ currency() }} ' + max.toLocaleString(undefined, { minimumFractionDigits: 2 });
        hint.style.display = 'block';
        document.getElementById('amount').max = max.toFixed(2);
    } else {
        hint.style.display = 'none';
    }

    if (crossHint) crossHint.style.display = (isCross && CROSS_ALLOWED) ? 'block' : 'none';
}

// Re-run when "to" changes too
document.addEventListener('DOMContentLoaded', function () {
    const toSel = document.querySelector('[name="to_line_item_id"]');
    if (toSel) toSel.addEventListener('change', updateMaxAmount);
});
</script>

@endsection
