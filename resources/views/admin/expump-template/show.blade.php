@extends('layouts.app')
@section('title', $expumpTemplate->name . ' — Ex-pump Template')

@push('styles')
<style>
.expump-grid-wrap { overflow-x: auto; }
.expump-grid {
    border-collapse: separate;
    border-spacing: 0;
    min-width: 100%;
    font-size: 13px;
}
.expump-grid th, .expump-grid td {
    white-space: nowrap;
    border: 1px solid #E2E8F0;
    padding: 6px 10px;
    vertical-align: middle;
}
.expump-grid thead th {
    background: #1B2A4A;
    color: #fff;
    font-weight: 600;
    font-size: 12px;
    position: sticky;
    top: 0;
    z-index: 2;
}
.expump-grid thead th:first-child {
    position: sticky;
    left: 0;
    z-index: 3;
    min-width: 220px;
}
.expump-grid tbody td:first-child {
    position: sticky;
    left: 0;
    background: #FAFBFC;
    font-weight: 600;
    font-size: 12px;
    color: #1B2A4A;
    z-index: 1;
    min-width: 220px;
    border-right: 2px solid #CBD5E1;
}
.row-calc td:first-child { background: #F0F9FF; color: #0369A1; }
.row-unit { font-size: 11px; color: #94A3B8; font-weight: 400; }

.val-display {
    display: block;
    width: 100px;
    text-align: right;
    font-size: 12px;
    padding: 4px 6px;
    font-variant-numeric: tabular-nums;
    color: #334155;
}
.val-display.is-calc {
    color: #0369A1;
    font-weight: 600;
    background: #F0F9FF;
    border-radius: 4px;
}
.val-display.is-empty { color: #CBD5E1; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('admin.expump-templates.index') }}" class="text-muted text-decoration-none">Ex-pump Templates</a>
    <span class="text-muted">/</span>
    <span>{{ $expumpTemplate->name }}</span>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body p-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h6 class="fw-bold mb-0">{{ $expumpTemplate->name }}</h6>
                @if($expumpTemplate->description)
                    <div class="text-muted small mt-1">{{ $expumpTemplate->description }}</div>
                @endif
            </div>
            <div class="col-auto d-flex align-items-center gap-2">
                @if($expumpTemplate->is_active)
                    <span class="badge align-self-center" style="background:#FEF9EC;color:#92400E;font-size:11px;">
                        <i class="fas fa-check-circle me-1"></i>ACTIVE
                    </span>
                    <form method="POST" action="{{ route('admin.expump-templates.deactivate', $expumpTemplate) }}" id="deactivateForm">
                        @csrf
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="confirmDeactivate()">
                            Deactivate
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.expump-templates.activate', $expumpTemplate) }}" id="activateForm">
                        @csrf
                        <button type="button" class="btn btn-sm btn-warning text-white" onclick="confirmActivate()">
                            Activate
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.expump-templates.edit', $expumpTemplate) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-edit me-1"></i>Edit Values
                </a>
            </div>
        </div>
    </div>
</div>

@if($expumpCodes->isEmpty() || $revenueCodes->isEmpty())
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i>
    @if($expumpCodes->isEmpty())
        No active ex-pump item codes found.
    @else
        No active revenue codes found.
    @endif
</div>
@else

<div class="d-flex align-items-center justify-content-between mb-2">
    <small class="text-muted">
        <i class="fas fa-info-circle me-1"></i>
        Rows = ex-pump codes &nbsp;·&nbsp; Columns = revenue codes &nbsp;·&nbsp;
        <span style="color:#0369A1;">Blue cells</span> are formula-computed
    </small>
    <small class="text-muted">{{ $expumpCodes->count() }} items &times; {{ $revenueCodes->count() }} products</small>
</div>

<div class="expump-grid-wrap card shadow-sm">
    <table class="expump-grid">
        <thead>
            <tr>
                <th>Ex-pump Item</th>
                <th style="min-width:80px;">Unit</th>
                @foreach($revenueCodes as $rc)
                    <th title="{{ $rc->name }}">{{ $rc->code }}</th>
                @endforeach
            </tr>
            <tr>
                <th style="font-size:10px;font-weight:400;color:#94A3B8;">
                    {{ $expumpCodes->count() }} rows · {{ $revenueCodes->count() }} columns
                </th>
                <th style="font-size:10px;font-weight:400;color:#94A3B8;"></th>
                @foreach($revenueCodes as $rc)
                    <th style="font-size:10px;font-weight:400;color:#94A3B8;max-width:120px;overflow:hidden;text-overflow:ellipsis;">
                        {{ Str::limit($rc->name, 18) }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($expumpCodes as $ec)
            @php
                $isCalc = $ec->calc_type === 'calculation';
                $formulaText = '';
                if ($isCalc && $ec->calc_config) {
                    $cfg = is_array($ec->calc_config) ? $ec->calc_config : [];
                    $getCode = fn($id) => $expumpCodes->firstWhere('id', $id)?->code ?? "#{$id}";
                    if (($cfg['method'] ?? '') === 'pct_of') {
                        $formulaText = ($cfg['pct'] ?? 0) . '% of ' . $getCode($cfg['code_id'] ?? 0);
                    } elseif (($cfg['method'] ?? '') === 'sum') {
                        $formulaText = implode(' + ', array_map($getCode, $cfg['code_ids'] ?? []));
                    } elseif (($cfg['method'] ?? '') === 'mixed') {
                        $parts = [];
                        foreach ($cfg['items'] ?? [] as $it) {
                            $c = $getCode($it['code_id'] ?? 0);
                            $parts[] = ($it['type'] === 'pct_of') ? ($it['pct'] ?? 0) . '% of ' . $c : $c;
                        }
                        $formulaText = implode(' + ', $parts);
                    }
                }
            @endphp
            <tr class="{{ $isCalc ? 'row-calc' : '' }}">
                <td>
                    <div>{{ $ec->code }} — {{ $ec->name }}</div>
                    @if($isCalc)
                        <div style="font-size:10px;color:#0369A1;margin-top:2px;">
                            <i class="fas fa-calculator me-1"></i>computed{{ $formulaText ? ' = ' . $formulaText : '' }}
                        </div>
                    @endif
                </td>
                <td style="text-align:center;font-size:12px;color:#64748B;white-space:nowrap;">
                    {{ $ec->unit ?: '—' }}
                </td>
                @foreach($revenueCodes as $rc)
                @php
                    $val = $savedValues[$ec->id][$rc->id] ?? null;
                @endphp
                <td style="padding:4px 6px;background:#fff;">
                    <span class="val-display {{ $isCalc ? 'is-calc' : ($val === null ? 'is-empty' : '') }}"
                          data-row="{{ $ec->id }}"
                          data-col="{{ $rc->id }}">
                        @if($isCalc)
                            —
                        @elseif($val !== null)
                            {{ number_format((float)$val, 4) }}
                        @else
                            —
                        @endif
                    </span>
                </td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endif

@push('scripts')
<script>
const CALC_ROWS = @json(
    $expumpCodes->where('calc_type', 'calculation')
        ->mapWithKeys(fn($c) => [$c->id => $c->calc_config])
        ->filter()
);
const COL_IDS = @json($revenueCodes->pluck('id')->values());
const SAVED   = @json($savedValues);

function evalFormula(cfg, resolved) {
    if (!cfg || !cfg.method) return 0;
    if (cfg.method === 'pct_of') {
        return ((resolved[cfg.code_id] ?? 0) * (cfg.pct ?? 0)) / 100;
    }
    if (cfg.method === 'sum') {
        return (cfg.code_ids || []).reduce((s, id) => s + (resolved[id] ?? 0), 0);
    }
    if (cfg.method === 'mixed') {
        return (cfg.items || []).reduce((s, item) => {
            const base = resolved[item.code_id] ?? 0;
            return item.type === 'pct_of' ? s + base * (item.pct ?? 0) / 100 : s + base;
        }, 0);
    }
    return 0;
}

function computeAndDisplay() {
    COL_IDS.forEach(colId => {
        const vals = {};
        // seed manual values from saved data
        for (const [rowId, cols] of Object.entries(SAVED)) {
            if (cols[colId] !== undefined) vals[rowId] = parseFloat(cols[colId]) || 0;
        }

        // multi-pass resolve calc rows
        const maxPasses = Object.keys(CALC_ROWS).length + 2;
        for (let pass = 0; pass < maxPasses; pass++) {
            let changed = false;
            for (const [rowId, cfg] of Object.entries(CALC_ROWS)) {
                const v = evalFormula(cfg, vals);
                if (vals[rowId] !== v) { vals[rowId] = v; changed = true; }
            }
            if (!changed) break;
        }

        // update display spans for calc rows
        for (const rowId of Object.keys(CALC_ROWS)) {
            const v    = vals[rowId] ?? 0;
            const span = document.querySelector(`.val-display[data-row="${rowId}"][data-col="${colId}"]`);
            if (span) {
                span.textContent = v.toLocaleString(undefined, { minimumFractionDigits: 4, maximumFractionDigits: 6 });
                span.classList.remove('is-empty');
            }
        }
    });
}

computeAndDisplay();

function confirmActivate() {
    Swal.fire({
        title: 'Activate template?',
        html: 'This will become the active ex-pump template.<br>Any currently active template will be deactivated.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#F59E0B',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, activate',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then(result => {
        if (result.isConfirmed) document.getElementById('activateForm').submit();
    });
}

function confirmDeactivate() {
    Swal.fire({
        title: 'Deactivate template?',
        text: 'The template will be saved but no longer active.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#6B7280',
        cancelButtonColor: '#1B2A4A',
        confirmButtonText: 'Yes, deactivate',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then(result => {
        if (result.isConfirmed) document.getElementById('deactivateForm').submit();
    });
}
</script>
@endpush

@endsection
