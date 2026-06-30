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
            <strong>10% Virement Rule:</strong> The amount transferred from any
            single account code cannot exceed 10% of that account's total approved budget.
            The system will validate this automatically.
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
                                {{ old('from_line_item_id') == $item->id ? 'selected' : '' }}>
                                {{ $item->accountCode->code }} —
                                {{ $item->accountCode->name }}
                                ({{ currency() }} {{ number_format($item->total_amount, 2) }})
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
                                {{ old('to_line_item_id') == $item->id ? 'selected' : '' }}>
                                {{ $item->accountCode->code }} —
                                {{ $item->accountCode->name }}
                                ({{ currency() }} {{ number_format($item->total_amount, 2) }})
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
function updateMaxAmount() {
    const sel     = document.getElementById('from_item');
    const option  = sel.options[sel.selectedIndex];
    const max     = option.dataset.max;
    const hint    = document.getElementById('max-hint');
    const maxSpan = document.getElementById('max-amount');

    if (max) {
        maxSpan.textContent = '{{ currency() }} ' + parseFloat(max).toLocaleString('en-GH', {
            minimumFractionDigits: 2
        });
        hint.style.display = 'block';
        document.getElementById('amount').max = max;
    } else {
        hint.style.display = 'none';
    }
}
</script>

@endsection
