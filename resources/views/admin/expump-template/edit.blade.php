@extends('layouts.app')
@section('title', 'Edit Ex-pump Template')

@push('styles')
<style>
/* ── grid table ───────────────────────────────────── */
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

/* ── value cells ───────────────────────────────────── */
.expump-grid td.val-cell { padding: 4px 6px; background: #fff; }
.expump-grid td.val-cell input[type="number"] {
    width: 100px;
    border: 1px solid #E2E8F0;
    border-radius: 4px;
    padding: 4px 6px;
    font-size: 12px;
    text-align: right;
    background: transparent;
    transition: border-color .15s;
}
.expump-grid td.val-cell input[type="number"]:focus {
    outline: none;
    border-color: #E65C00;
    box-shadow: 0 0 0 2px rgba(230,92,0,.12);
}
.calc-display {
    display: block;
    width: 100px;
    text-align: right;
    font-size: 12px;
    color: #0369A1;
    font-weight: 600;
    padding: 4px 6px;
    background: #F0F9FF;
    border-radius: 4px;
    font-variant-numeric: tabular-nums;
}
</style>
@endpush

@section('content')
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('admin.expump-templates.index') }}" class="text-muted text-decoration-none">Ex-pump Templates</a>
    <span class="text-muted">/</span>
    <span>{{ $expumpTemplate->name }}</span>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card shadow-sm mb-4">
    <div class="card-body p-3">
        <div class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small fw-semibold mb-1">Template Name</label>
                <div class="fw-bold">{{ $expumpTemplate->name }}</div>
            </div>
            @if($expumpTemplate->description)
            <div class="col">
                <div class="text-muted small">{{ $expumpTemplate->description }}</div>
            </div>
            @endif
            <div class="col-auto ms-auto d-flex gap-2">
                @if($expumpTemplate->is_active)
                    <span class="badge align-self-center" style="background:#FEF9EC;color:#92400E;">
                        <i class="fas fa-check-circle me-1"></i>ACTIVE
                    </span>
                @else
                    <form method="POST" action="{{ route('admin.expump-templates.activate', $expumpTemplate) }}" class="d-inline" id="editActivateForm">
                        @csrf
                        <button type="button" class="btn btn-sm btn-warning text-white"
                            onclick="confirmActivateEdit()">Activate</button>
                    </form>
                @endif
                <button type="button" class="btn btn-sm btn-outline-secondary"
                    data-bs-toggle="modal" data-bs-target="#renameModal">
                    <i class="fas fa-pencil-alt me-1"></i>Rename
                </button>
            </div>
        </div>
    </div>
</div>

@if($expumpCodes->isEmpty() || $revenueCodes->isEmpty())
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i>
    @if($expumpCodes->isEmpty())
        No active ex-pump item codes found. <a href="{{ route('admin.account-codes.create') }}">Add some</a> with budget type <strong>Ex-pump Item</strong>.
    @else
        No active revenue codes found.
    @endif
</div>
@else

<form method="POST" action="{{ route('admin.expump-templates.update', $expumpTemplate) }}"
      id="gridForm" onsubmit="return prepareGridSubmit()">
    @csrf @method('PUT')

    {{-- Hidden name/description (saved via rename modal) --}}
    <input type="hidden" name="name" id="hiddenName" value="{{ $expumpTemplate->name }}">
    <input type="hidden" name="description" id="hiddenDesc" value="{{ $expumpTemplate->description }}">

    <div class="d-flex justify-content-between align-items-center mb-2">
        <small class="text-muted">
            <i class="fas fa-info-circle me-1"></i>
            Rows = ex-pump codes &nbsp;·&nbsp; Columns = revenue codes &nbsp;·&nbsp;
            <span style="color:#0369A1;">Blue rows</span> are formula-computed (read-only in template)
        </small>
        <div class="d-flex align-items-center gap-3">
            <span id="autoSaveStatus" class="small" style="min-width:150px;text-align:right;"></span>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-save me-1"></i>Save
            </button>
        </div>
    </div>

    <div class="expump-grid-wrap">
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
                    <td class="val-cell">
                        @if($isCalc)
                            <span class="calc-display"
                                  data-row="{{ $ec->id }}"
                                  data-col="{{ $rc->id }}">—</span>
                            <input type="hidden" class="calc-hidden"
                                   name="values[{{ $ec->id }}][{{ $rc->id }}]"
                                   data-row="{{ $ec->id }}"
                                   data-col="{{ $rc->id }}"
                                   value="">
                        @else
                            <input type="number"
                                   name="values[{{ $ec->id }}][{{ $rc->id }}]"
                                   data-row="{{ $ec->id }}"
                                   data-col="{{ $rc->id }}"
                                   class="manual-input"
                                   step="0.000001"
                                   value="{{ $savedValues[$ec->id][$rc->id] ?? '' }}"
                                   placeholder="0">
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-3 text-end">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i>Save Values
        </button>
    </div>
</form>

@endif

{{-- Rename modal --}}
<div class="modal fade" id="renameModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Rename Template</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Name</label>
                    <input type="text" id="modalName" class="form-control"
                        value="{{ $expumpTemplate->name }}">
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Description</label>
                    <textarea id="modalDesc" rows="2" class="form-control">{{ $expumpTemplate->description }}</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-sm" onclick="applyRename()">Apply &amp; Save</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/* ── data ─────────────────────────────────────────── */
const CALC_ROWS = @json(
    $expumpCodes->where('calc_type', 'calculation')
        ->mapWithKeys(fn($c) => [$c->id => $c->calc_config])
        ->filter()
);
const COL_IDS = @json($revenueCodes->pluck('id')->values());
const SAVED   = @json($savedValues);

/* ── formula engine ───────────────────────────────── */
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

function recomputeCol(colId) {
    /* collect manual inputs */
    const vals = {};
    document.querySelectorAll(`.manual-input[data-col="${colId}"]`).forEach(inp => {
        vals[inp.dataset.row] = parseFloat(inp.value) || 0;
    });
    /* multi-pass resolve calc rows */
    const maxPasses = Object.keys(CALC_ROWS).length + 2;
    for (let pass = 0; pass < maxPasses; pass++) {
        let changed = false;
        for (const [rowId, cfg] of Object.entries(CALC_ROWS)) {
            const v = evalFormula(cfg, vals);
            if (vals[rowId] !== v) { vals[rowId] = v; changed = true; }
        }
        if (!changed) break;
    }
    /* update display + hidden inputs */
    for (const rowId of Object.keys(CALC_ROWS)) {
        const v   = vals[rowId] ?? 0;
        const fmt = v.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 6 });
        const disp = document.querySelector(`.calc-display[data-row="${rowId}"][data-col="${colId}"]`);
        const hid  = document.querySelector(`.calc-hidden[data-row="${rowId}"][data-col="${colId}"]`);
        if (disp) disp.textContent = fmt;
        if (hid)  hid.value = v;
    }
}

function recomputeAll() {
    COL_IDS.forEach(colId => recomputeCol(colId));
}

/* ── wire up manual inputs ────────────────────────── */
document.querySelectorAll('.manual-input').forEach(inp => {
    inp.addEventListener('input', () => {
        recomputeCol(inp.dataset.col);
        scheduleAutoSave();
    });
});

/* ── initial compute ──────────────────────────────── */
recomputeAll();

/* ── form submit: ensure calc hiddens are populated ── */
function prepareGridSubmit() {
    recomputeAll();
    return true;
}

/* ── auto-save ────────────────────────────────────── */
let _autoSaveTimer  = null;
let _autoSaveBusy   = false;

function setAutoSaveStatus(state) {
    const el = document.getElementById('autoSaveStatus');
    if (state === 'pending') {
        el.innerHTML = '<span class="text-muted"><i class="fas fa-clock me-1"></i>Unsaved changes</span>';
    } else if (state === 'saving') {
        el.innerHTML = '<span class="text-warning"><i class="fas fa-spinner fa-spin me-1"></i>Saving…</span>';
    } else if (state === 'saved') {
        const t = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        el.innerHTML = `<span class="text-success"><i class="fas fa-check-circle me-1"></i>Auto-saved at ${t}</span>`;
    } else if (state === 'error') {
        el.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Auto-save failed</span>';
        setTimeout(() => { el.innerHTML = ''; }, 5000);
    } else {
        el.innerHTML = '';
    }
}

async function runAutoSave() {
    if (_autoSaveBusy) return;
    _autoSaveBusy = true;
    setAutoSaveStatus('saving');

    recomputeAll();

    try {
        const resp = await fetch(document.getElementById('gridForm').action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(document.getElementById('gridForm')),
        });
        setAutoSaveStatus(resp.ok ? 'saved' : 'error');
    } catch {
        setAutoSaveStatus('error');
    } finally {
        _autoSaveBusy = false;
    }
}

function scheduleAutoSave() {
    setAutoSaveStatus('pending');
    clearTimeout(_autoSaveTimer);
    _autoSaveTimer = setTimeout(runAutoSave, 1500);
}

/* ── activate confirm ─────────────────────────────── */
function confirmActivateEdit() {
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
        if (result.isConfirmed) document.getElementById('editActivateForm').submit();
    });
}

/* ── rename modal ─────────────────────────────────── */
function applyRename() {
    const name = document.getElementById('modalName').value.trim();
    if (!name) { alert('Name is required.'); return; }
    document.getElementById('hiddenName').value = name;
    document.getElementById('hiddenDesc').value = document.getElementById('modalDesc').value;
    bootstrap.Modal.getInstance(document.getElementById('renameModal')).hide();
    document.getElementById('gridForm').requestSubmit();
}
</script>
@endpush

@endsection
