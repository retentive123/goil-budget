{{-- Formula builder for ex-pump item codes with calc_type = calculation --}}
{{-- Requires: $expumpCodes (Collection of id/code/name/calc_type) --}}
{{-- Optional: $accountCode (existing model, for prepopulating on edit) --}}

<div id="calcTypeSection" class="mb-3 p-3 rounded" style="display:none;background:#FEF9EC;border:1px solid #FDE68A;">
    <label class="form-label small fw-semibold mb-2" style="color:#92400E;">
        <i class="fas fa-calculator me-1"></i>Calculation Type
    </label>
    <div class="d-flex gap-4">
        <div class="form-check">
            <input type="radio" name="calc_type" value="values" id="calc_type_values"
                class="form-check-input"
                {{ old('calc_type', $accountCode->calc_type ?? 'values') !== 'calculation' ? 'checked' : '' }}>
            <label for="calc_type_values" class="form-check-label small fw-semibold">Manual entry</label>
            <div class="text-muted" style="font-size:11px;">Users enter values directly in the template</div>
        </div>
        <div class="form-check">
            <input type="radio" name="calc_type" value="calculation" id="calc_type_calc"
                class="form-check-input"
                {{ old('calc_type', $accountCode->calc_type ?? 'values') === 'calculation' ? 'checked' : '' }}>
            <label for="calc_type_calc" class="form-check-label small fw-semibold">Formula-based</label>
            <div class="text-muted" style="font-size:11px;">Computed from other codes — read-only in template</div>
        </div>
    </div>
</div>

<div id="formulaBuilder" class="mb-3 p-3 rounded" style="display:none;background:#F0F9FF;border:1px solid #BAE6FD;">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <span class="fw-semibold small" style="color:#0369A1;">
            <i class="fas fa-function me-1"></i>Formula Definition
        </span>
    </div>

    {{-- Method selector --}}
    <div class="mb-3">
        <label class="form-label small fw-semibold">Method</label>
        <div class="d-flex gap-4">
            <div class="form-check">
                <input type="radio" name="formula_method" value="pct_of" id="method_pct"
                    class="form-check-input" onchange="fbShowPanel('pct_of')">
                <label for="method_pct" class="form-check-label small">% of a code</label>
            </div>
            <div class="form-check">
                <input type="radio" name="formula_method" value="sum" id="method_sum"
                    class="form-check-input" onchange="fbShowPanel('sum')">
                <label for="method_sum" class="form-check-label small">Sum of codes</label>
            </div>
            <div class="form-check">
                <input type="radio" name="formula_method" value="mixed" id="method_mixed"
                    class="form-check-input" onchange="fbShowPanel('mixed')">
                <label for="method_mixed" class="form-check-label small">Mixed formula</label>
            </div>
        </div>
    </div>

    {{-- pct_of panel --}}
    <div id="panel_pct_of" style="display:none;">
        <div class="row g-2 align-items-end">
            <div class="col-8">
                <label class="form-label small">Reference code</label>
                <select id="pct_code_id" class="form-select form-select-sm">
                    <option value="">— select ex-pump code —</option>
                    @foreach($expumpCodes as $c)
                        <option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-4">
                <label class="form-label small">Percentage</label>
                <div class="input-group input-group-sm">
                    <input type="number" id="pct_value" class="form-control"
                        step="0.01" min="0" max="100000" placeholder="0.00">
                    <span class="input-group-text">%</span>
                </div>
            </div>
        </div>
    </div>

    {{-- sum panel --}}
    <div id="panel_sum" style="display:none;">
        <label class="form-label small">Codes to sum</label>
        @if($expumpCodes->isEmpty())
            <p class="text-muted small fst-italic mb-0">No other ex-pump codes found.</p>
        @else
        <div class="border rounded p-2 bg-white" style="max-height:220px;overflow-y:auto;">
            @foreach($expumpCodes as $c)
            <div class="form-check">
                <input type="checkbox" class="form-check-input sum-code-check"
                    value="{{ $c->id }}" id="sum_code_{{ $c->id }}">
                <label class="form-check-label small" for="sum_code_{{ $c->id }}">
                    <strong>{{ $c->code }}</strong> — {{ $c->name }}
                    @if($c->calc_type === 'calculation')
                        <span class="badge ms-1" style="font-size:9px;background:#E0F2FE;color:#0369A1;">calc</span>
                    @endif
                </label>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- mixed panel --}}
    <div id="panel_mixed" style="display:none;">
        <label class="form-label small">Formula terms</label>
        <div id="mixedRows" class="d-flex flex-column gap-2 mb-2"></div>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addMixedRow()">
            <i class="fas fa-plus me-1"></i>Add Term
        </button>
    </div>

    <input type="hidden" name="calc_config" id="calcConfigInput">
</div>

<script>
(function () {
    const FB_CODES    = @json($expumpCodes->values()->map(fn($c) => ['id' => $c->id, 'label' => $c->code . ' — ' . $c->name]));
    const FB_EXISTING = @json(isset($accountCode) ? $accountCode->calc_config : null);

    /* ── panel switcher ───────────────────────────────── */
    window.fbShowPanel = function(panel) {
        ['pct_of','sum','mixed'].forEach(p => {
            document.getElementById('panel_' + p).style.display = p === panel ? '' : 'none';
        });
    };

    /* ── mixed row builder ────────────────────────────── */
    let mixedCount = 0;
    window.addMixedRow = function(type = 'add', codeId = '', pct = '') {
        mixedCount++;
        const opts = FB_CODES.map(c =>
            `<option value="${c.id}" ${c.id == codeId ? 'selected' : ''}>${c.label}</option>`
        ).join('');
        const row = document.createElement('div');
        row.className = 'd-flex gap-2 align-items-center mixed-row';
        row.innerHTML = `
            <select class="form-select form-select-sm" style="max-width:110px;" onchange="this.closest('.mixed-row').querySelector('.mixed-pct-group').style.display=this.value==='pct_of'?'':'none'">
                <option value="add"    ${type==='add'    ?'selected':''}>Add</option>
                <option value="pct_of" ${type==='pct_of' ?'selected':''}>% of</option>
            </select>
            <select class="form-select form-select-sm mixed-code" style="flex:1;">
                <option value="">— select —</option>${opts}
            </select>
            <div class="input-group input-group-sm mixed-pct-group" style="max-width:120px;${type==='pct_of'?'':'display:none;'}">
                <input type="number" class="form-control mixed-pct" value="${pct}"
                    step="0.01" min="0" max="100000" placeholder="0.00">
                <span class="input-group-text">%</span>
            </div>
            <button type="button" class="btn btn-link text-danger p-0 lh-1"
                onclick="this.closest('.mixed-row').remove()" title="Remove">
                <i class="fas fa-times"></i>
            </button>`;
        document.getElementById('mixedRows').appendChild(row);
    };

    /* ── serializer (called by form submit) ───────────── */
    window.fbSerialize = function() {
        const calcType = document.querySelector('input[name="calc_type"]:checked')?.value;
        if (calcType !== 'calculation') {
            document.getElementById('calcConfigInput').value = '';
            return true;
        }
        const method = document.querySelector('input[name="formula_method"]:checked')?.value;
        if (!method) { alert('Select a formula method.'); return false; }

        let config = { method };

        if (method === 'pct_of') {
            const codeId = parseInt(document.getElementById('pct_code_id').value || '0');
            const pct    = parseFloat(document.getElementById('pct_value').value || '0');
            if (!codeId)       { alert('Select a reference code.'); return false; }
            if (!(pct > 0))    { alert('Enter a positive percentage.'); return false; }
            config.code_id = codeId;
            config.pct     = pct;

        } else if (method === 'sum') {
            const ids = [...document.querySelectorAll('.sum-code-check:checked')].map(el => parseInt(el.value));
            if (!ids.length) { alert('Tick at least one code.'); return false; }
            config.code_ids = ids;

        } else if (method === 'mixed') {
            const items = [];
            let ok = true;
            document.querySelectorAll('.mixed-row').forEach(row => {
                const type   = row.querySelector('select').value;
                const codeId = parseInt(row.querySelector('.mixed-code').value || '0');
                if (!codeId) { alert('Select a code for every term.'); ok = false; return; }
                const item = { type, code_id: codeId };
                if (type === 'pct_of') {
                    const pct = parseFloat(row.querySelector('.mixed-pct').value || '0');
                    if (!(pct > 0)) { alert('Enter a positive % for each "% of" term.'); ok = false; return; }
                    item.pct = pct;
                }
                items.push(item);
            });
            if (!ok) return false;
            if (!items.length) { alert('Add at least one term.'); return false; }
            config.items = items;
        }

        document.getElementById('calcConfigInput').value = JSON.stringify(config);
        return true;
    };

    /* ── show/hide formula builder on calc_type change ── */
    document.querySelectorAll('input[name="calc_type"]').forEach(r => {
        r.addEventListener('change', function () {
            document.getElementById('formulaBuilder').style.display = this.value === 'calculation' ? '' : 'none';
        });
    });

    /* ── prepopulate on edit ──────────────────────────── */
    (function prepopulate() {
        if (!FB_EXISTING) return;
        const m = FB_EXISTING.method;
        const r = document.querySelector(`input[name="formula_method"][value="${m}"]`);
        if (r) { r.checked = true; fbShowPanel(m); }

        if (m === 'pct_of') {
            document.getElementById('pct_code_id').value = FB_EXISTING.code_id || '';
            document.getElementById('pct_value').value   = FB_EXISTING.pct     || '';
        } else if (m === 'sum') {
            (FB_EXISTING.code_ids || []).forEach(id => {
                const cb = document.getElementById('sum_code_' + id);
                if (cb) cb.checked = true;
            });
        } else if (m === 'mixed') {
            (FB_EXISTING.items || []).forEach(item => {
                addMixedRow(item.type, item.code_id, item.pct || '');
            });
        }
    })();
})();
</script>
