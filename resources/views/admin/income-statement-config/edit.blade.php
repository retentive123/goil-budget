@extends('layouts.app')
@section('title', 'Edit P&L Layout')
@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.income-statement-configs.index') }}" class="text-muted text-decoration-none">
        P&L Layouts
    </a>
    <span class="text-muted">/</span>
    <span class="fw-semibold">{{ $config->name }}</span>
    @if($config->is_active)
    <span class="badge ms-1" style="background:#DCFCE7;color:#065F46;font-size:10px;border-radius:20px">
        <i class="fas fa-circle me-1" style="font-size:7px"></i>Active
    </span>
    @endif
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@php
$subsByType = $subCategories->groupBy('budget_type');
$OPERATORS  = ['add' => 'Add', 'subtract' => 'Less'];
$typeColors = ['revenue' => '#10B981', 'expense' => '#F43F5E'];
$typeLabels = ['revenue' => 'Revenue', 'expense' => 'Expense'];
@endphp

<div class="row g-4">

    {{-- ── Left column: config name + builder ── --}}
    <div class="col-lg-8">
        <form method="POST"
              action="{{ route('admin.income-statement-configs.update', $config) }}"
              id="configForm">
            @csrf @method('PUT')

            {{-- Name + CS% base --}}
            <div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <label class="small fw-semibold mb-0" style="white-space:nowrap;color:#1B2A4A">
                            Layout Name
                        </label>
                        <input type="text" name="name" value="{{ old('name', $config->name) }}"
                               class="form-control form-control-sm @error('name') is-invalid @enderror"
                               style="max-width:300px">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <button type="submit" onclick="prepareSubmit()"
                                class="btn btn-sm fw-semibold ms-auto"
                                style="background:#E65C00;color:#fff;border-radius:8px;border:none;white-space:nowrap">
                            <i class="fas fa-save me-1"></i> Save Layout
                        </button>
                    </div>
                </div>
            </div>

            {{-- Hidden ordered lines injected by JS before submit --}}
            <div id="hiddenLines"></div>

            {{-- Sortable line list --}}
            <div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden">
                <div class="card-header border-0 px-4 py-3 d-flex align-items-center justify-content-between"
                     style="background:#1B2A4A;color:#fff">
                    <div>
                        <i class="fas fa-list-ol me-2"></i>
                        <span class="fw-semibold">Statement Lines</span>
                        <small class="ms-2 opacity-75">Drag rows to reorder</small>
                    </div>
                    <small class="opacity-60" id="lineCount">{{ $config->lines->count() }} lines</small>
                </div>

                <div class="card-body p-0">
                    @if($config->lines->isEmpty())
                    <div id="emptyMsg" class="text-center py-5 text-muted" style="font-size:13px">
                        <i class="fas fa-stream fa-2x mb-2 opacity-25"></i>
                        <p class="mb-0">No lines yet. Add rows using the panel below.</p>
                    </div>
                    @else
                    <div id="emptyMsg" class="text-center py-5 text-muted" style="font-size:13px;display:none">
                        <i class="fas fa-stream fa-2x mb-2 opacity-25"></i>
                        <p class="mb-0">No lines yet. Add rows using the panel below.</p>
                    </div>
                    @endif

                    <div id="sortableLines">
                        @foreach($config->lines as $line)
                        @include('admin.income-statement-config._line_row', ['line' => $line, 'typeColors' => $typeColors, 'typeLabels' => $typeLabels])
                        @endforeach
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- ── Right column: add line panel ── --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm" style="border-radius:12px;position:sticky;top:80px">
            <div class="card-header border-0 px-4 py-3"
                 style="background:#F8FAFC;border-bottom:1px solid #E2E8F0;border-radius:12px 12px 0 0">
                <span class="fw-semibold" style="color:#1B2A4A;font-size:14px">
                    <i class="fas fa-plus-circle me-2" style="color:#E65C00"></i>Add Line
                </span>
            </div>
            <div class="card-body p-4">

                {{-- Line type selector --}}
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Line Type</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="newLineType" id="ltSubCat" value="sub_category" checked>
                        <label class="btn btn-sm btn-outline-secondary" for="ltSubCat" style="font-size:12px">
                            Sub-Category
                        </label>

                        <input type="radio" class="btn-check" name="newLineType" id="ltSubtotal" value="subtotal">
                        <label class="btn btn-sm btn-outline-secondary" for="ltSubtotal" style="font-size:12px">
                            Subtotal Row
                        </label>

                        <input type="radio" class="btn-check" name="newLineType" id="ltSpacer" value="spacer">
                        <label class="btn btn-sm btn-outline-secondary" for="ltSpacer" style="font-size:12px">
                            Spacer
                        </label>
                    </div>
                </div>

                {{-- Sub-category fields (shown when type = sub_category) --}}
                <div id="subCatFields">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Sub-Category</label>
                        <select id="newSubCatId" class="form-select form-select-sm">
                            <option value="">— select —</option>
                            @foreach($subsByType as $type => $subs)
                            <optgroup label="{{ ucfirst($type) }}">
                                @foreach($subs as $s)
                                <option value="{{ $s->id }}"
                                        data-type="{{ $type }}"
                                        data-name="{{ $s->name }}">
                                    {{ $s->name }}
                                </option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">
                            Operator
                            <span class="text-muted fw-normal">(how it contributes)</span>
                        </label>
                        <select id="newOperator" class="form-select form-select-sm">
                            <option value="add">Add (positive contribution)</option>
                            <option value="subtract">Less / Subtract</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">
                            Label Override
                            <span class="text-muted fw-normal">(optional)</span>
                        </label>
                        <input type="text" id="newLabel"
                               class="form-control form-control-sm"
                               placeholder="Leave blank to use sub-category name">
                    </div>

                </div>

                {{-- Subtotal label (shown when type = subtotal) --}}
                <div id="subtotalFields" style="display:none">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Subtotal Label</label>
                        <input type="text" id="newSubtotalLabel"
                               class="form-control form-control-sm"
                               placeholder="e.g. Net Revenue, Gross Profit">
                    </div>
                    <p class="small text-muted">
                        A subtotal row shows the running total accumulated from all lines above.
                    </p>
                </div>

                <div id="spacerFields" style="display:none">
                    <p class="small text-muted">A spacer inserts a blank row for visual separation.</p>
                </div>

                <button type="button" onclick="addLine()"
                        class="btn btn-sm w-100 fw-semibold"
                        style="background:#1B2A4A;color:#fff;border-radius:8px;border:none">
                    <i class="fas fa-plus me-1"></i> Add to Layout
                </button>
            </div>
        </div>

        {{-- Legend --}}
        <div class="mt-3 p-3 rounded-3 small text-muted" style="background:#F8FAFC;border:1px solid #E2E8F0">
            <div class="fw-semibold mb-1" style="color:#1B2A4A">How it works</div>
            Lines are computed top-to-bottom. Each <strong>sub-category</strong> row adds or subtracts
            its amounts to a running total. A <strong>subtotal</strong> row shows the current running
            total and names it (e.g. "Gross Profit"). The running total continues after each subtotal.
        </div>
    </div>
</div>

{{-- Row template (hidden) --}}
<template id="rowTemplate">
    <div class="line-row border-bottom d-flex align-items-center px-3 py-2 gap-2"
         style="font-size:13px;cursor:default"
         data-line_type="" data-sub_category_id="" data-label="" data-operator=""
         data-cs_base_sub_category_id="" data-cs_base_subtotal_label="">

        <span class="drag-handle text-muted me-1" style="cursor:grab;font-size:16px">⠿</span>

        <span class="row-badge badge me-1" style="font-size:10px;border-radius:4px"></span>

        <span class="row-main fw-semibold" style="color:#1B2A4A;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></span>

        <select class="cs-base-sel" data-selected="" onchange="onCsBaseChange(this)"
                style="font-size:10px;border:1px solid #FFFBEB;background:#FFFBEB;color:#92400E;
                       border-radius:4px;padding:2px 4px;max-width:130px;cursor:pointer;display:none"
                title="CS% base line">
            <option value="">CS%: none</option>
        </select>

        <span class="row-op badge" style="font-size:10px;border-radius:4px"></span>

        <button type="button" class="btn btn-sm p-0 px-2 edit-btn"
                style="background:#EFF6FF;color:#1D4ED8;border:none;border-radius:5px;font-size:11px"
                onclick="editLine(this)">
            <i class="fas fa-pen" style="font-size:10px"></i>
        </button>
        <button type="button" class="btn btn-sm ms-1 p-0 px-2"
                style="background:#FEE2E2;color:#991B1B;border:none;border-radius:5px;font-size:11px"
                onclick="removeLine(this)">
            <i class="fas fa-times"></i>
        </button>
    </div>
</template>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
const SUB_CATS = @json($subCategories->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'budget_type' => $s->budget_type])->values());

// Bootstrap sortable
const sortable = Sortable.create(document.getElementById('sortableLines'), {
    animation: 150,
    handle: '.drag-handle',
    ghostClass: 'table-active',
    onEnd() { updateCount(); rebuildCsSelects(); }
});

// Init CS% selects on page load
document.addEventListener('DOMContentLoaded', rebuildCsSelects);

// Toggle add-line fields based on type selection
document.querySelectorAll('input[name="newLineType"]').forEach(radio => {
    radio.addEventListener('change', () => {
        const t = radio.value;
        document.getElementById('subCatFields').style.display   = t === 'sub_category' ? '' : 'none';
        document.getElementById('subtotalFields').style.display  = t === 'subtotal'     ? '' : 'none';
        document.getElementById('spacerFields').style.display    = t === 'spacer'       ? '' : 'none';
    });
});

function getSelectedType() {
    return document.querySelector('input[name="newLineType"]:checked').value;
}

function addLine() {
    const type = getSelectedType();
    const tmpl = document.getElementById('rowTemplate');
    const row  = tmpl.content.cloneNode(true).firstElementChild;

    if (type === 'sub_category') {
        const sel      = document.getElementById('newSubCatId');
        const subId    = sel.value;
        const subName  = sel.selectedOptions[0]?.dataset.name ?? '';
        const subType  = sel.selectedOptions[0]?.dataset.type ?? '';
        const op  = document.getElementById('newOperator').value;
        const lbl = document.getElementById('newLabel').value.trim();

        if (!subId) { alert('Please select a sub-category.'); return; }

        const displayLabel = lbl || (op === 'subtract' ? 'Less: ' : '') + subName;
        const color = subType === 'revenue' ? '#10B981' : '#F43F5E';

        row.dataset.line_type       = 'sub_category';
        row.dataset.sub_category_id = subId;
        row.dataset.label           = lbl;
        row.dataset.operator        = op;
        row.dataset.cs_base_sub_category_id = '';
        row.dataset.cs_base_subtotal_label  = '';

        row.querySelector('.row-badge').textContent      = subType.toUpperCase();
        row.querySelector('.row-badge').style.background = color + '22';
        row.querySelector('.row-badge').style.color      = color;
        row.querySelector('.row-main').textContent       = displayLabel;

        const opBadge = row.querySelector('.row-op');
        opBadge.textContent       = op === 'add' ? '+ Add' : '− Less';
        opBadge.style.background  = op === 'add' ? '#D1FAE5' : '#FEE2E2';
        opBadge.style.color       = op === 'add' ? '#065F46' : '#991B1B';

    } else if (type === 'subtotal') {
        const lbl = document.getElementById('newSubtotalLabel').value.trim();
        if (!lbl) { alert('Please enter a label for the subtotal row.'); return; }

        row.dataset.line_type               = 'subtotal';
        row.dataset.label                   = lbl;
        row.dataset.cs_base_sub_category_id = '';
        row.dataset.cs_base_subtotal_label  = '';

        row.querySelector('.row-badge').textContent    = 'SUBTOTAL';
        row.querySelector('.row-badge').style.background = '#EFF6FF';
        row.querySelector('.row-badge').style.color      = '#1D4ED8';
        row.querySelector('.row-main').textContent       = lbl;
        row.querySelector('.row-main').style.fontStyle   = 'normal';
        row.querySelector('.row-op').style.display       = 'none';

    } else { // spacer
        row.dataset.line_type = 'spacer';

        row.querySelector('.row-badge').textContent    = 'SPACER';
        row.querySelector('.row-badge').style.background = '#F1F5F9';
        row.querySelector('.row-badge').style.color      = '#94A3B8';
        row.querySelector('.row-main').textContent       = '— blank row —';
        row.querySelector('.row-main').style.color       = '#94A3B8';
        row.querySelector('.row-op').style.display       = 'none';
        const eb = row.querySelector('.edit-btn');
        if (eb) eb.style.display = 'none';
    }

    document.getElementById('sortableLines').appendChild(row);
    document.getElementById('emptyMsg').style.display = 'none';
    updateCount();
    rebuildCsSelects();
    clearAddForm();
}

function removeLine(btn) {
    btn.closest('.line-row').remove();
    const remaining = document.querySelectorAll('#sortableLines .line-row').length;
    document.getElementById('emptyMsg').style.display = remaining === 0 ? '' : 'none';
    updateCount();
    rebuildCsSelects();
}

function updateCount() {
    const n = document.querySelectorAll('#sortableLines .line-row').length;
    document.getElementById('lineCount').textContent = n + ' ' + (n === 1 ? 'line' : 'lines');
}

function clearAddForm() {
    document.getElementById('newSubCatId').value      = '';
    document.getElementById('newOperator').value      = 'add';
    document.getElementById('newLabel').value         = '';
    document.getElementById('newSubtotalLabel').value = '';
}

// ── CS% base selects ──────────────────────────────────────────────────────

function getSubCatRows() {
    return [...document.querySelectorAll('#sortableLines .line-row')]
        .filter(r => r.dataset.line_type === 'sub_category');
}

function rebuildCsSelects() {
    // Build options from all sub_category and subtotal rows
    const allRows = [...document.querySelectorAll('#sortableLines .line-row')];
    const baseOpts = [];
    allRows.forEach(r => {
        if (r.dataset.line_type === 'sub_category') {
            const id    = r.dataset.sub_category_id;
            const label = r.querySelector('.row-main')?.firstChild?.textContent?.trim() ?? id;
            baseOpts.push({ value: 'sc:' + id, label, selfKey: 'sc:' + id });
        } else if (r.dataset.line_type === 'subtotal') {
            const lbl = r.dataset.label || r.querySelector('.row-main')?.textContent?.trim() || '';
            if (lbl) baseOpts.push({ value: 'st:' + lbl, label: lbl + ' =', selfKey: 'st:' + lbl });
        }
    });

    allRows.forEach(row => {
        const lt = row.dataset.line_type;
        if (lt !== 'sub_category' && lt !== 'subtotal') return;
        const sel = row.querySelector('.cs-base-sel');
        if (!sel) return;

        const scId   = row.dataset.cs_base_sub_category_id || '';
        const stLbl  = row.dataset.cs_base_subtotal_label  || '';
        const current = scId ? 'sc:' + scId : (stLbl ? 'st:' + stLbl : '');

        const ownKey = lt === 'sub_category'
            ? 'sc:' + row.dataset.sub_category_id
            : 'st:' + (row.dataset.label || '');

        sel.innerHTML = '<option value="">CS%: none</option>';
        baseOpts.forEach(o => {
            if (o.value === ownKey) return; // skip self
            const opt = document.createElement('option');
            opt.value = o.value;
            opt.textContent = 'CS%: ' + o.label;
            if (o.value === current) opt.selected = true;
            sel.appendChild(opt);
        });
        sel.style.display = '';
    });

    refreshCsBaseOptions();
}

function onCsBaseChange(sel) {
    const row = sel.closest('.line-row');
    if (!row) return;
    const val = sel.value;
    if (val.startsWith('sc:')) {
        row.dataset.cs_base_sub_category_id = val.slice(3);
        row.dataset.cs_base_subtotal_label  = '';
    } else if (val.startsWith('st:')) {
        row.dataset.cs_base_sub_category_id = '';
        row.dataset.cs_base_subtotal_label  = val.slice(3);
    } else {
        row.dataset.cs_base_sub_category_id = '';
        row.dataset.cs_base_subtotal_label  = '';
    }
}

function refreshCsBaseOptions() {
    const sel = document.getElementById('newCsBase');
    if (!sel) return;
    const current = sel.value;
    sel.innerHTML = '<option value="">— None —</option>';
    getSubCatRows().forEach(row => {
        const scId   = row.dataset.sub_category_id;
        const label  = row.querySelector('.row-main')?.firstChild?.textContent?.trim() ?? scId;
        const opt    = document.createElement('option');
        opt.value    = scId;
        opt.textContent = label;
        if (scId === current) opt.selected = true;
        sel.appendChild(opt);
    });
}

// ── Inline edit ──────────────────────────────────────────────────────────
function escAttr(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');
}

function editLine(btn) {
    const row = btn.closest('.line-row');
    if (row.classList.contains('editing')) return;
    row.classList.add('editing');
    btn.style.display = 'none';

    const lt = row.dataset.line_type;
    let html = '<div class="row-edit-panel">';

    if (lt === 'sub_category') {
        const curScId = row.dataset.sub_category_id;
        const curOp   = row.dataset.operator || 'add';
        const curLbl  = row.dataset.label || '';

        const scOpts = SUB_CATS.map(s =>
            `<option value="${s.id}" data-type="${escAttr(s.budget_type)}" ${s.id == curScId ? 'selected' : ''}>${escAttr(s.name)}</option>`
        ).join('');
        html += `<label class="small text-muted mb-0">Sub-cat:</label>
                 <select class="edit-subcat form-select form-select-sm" style="max-width:200px">${scOpts}</select>
                 <label class="small text-muted mb-0 ms-1">Op:</label>
                 <select class="edit-op form-select form-select-sm" style="max-width:120px">
                     <option value="add" ${curOp==='add'?'selected':''}>+ Add</option>
                     <option value="subtract" ${curOp==='subtract'?'selected':''}>− Less</option>
                 </select>
                 <label class="small text-muted mb-0 ms-1">Label:</label>
                 <input type="text" class="edit-lbl form-control form-control-sm" style="max-width:180px"
                        placeholder="Override (optional)" value="${escAttr(curLbl)}">`;

    } else if (lt === 'subtotal') {
        const curLbl = row.dataset.label || '';
        html += `<label class="small text-muted mb-0">Label:</label>
                 <input type="text" class="edit-lbl form-control form-control-sm" style="max-width:280px"
                        placeholder="Subtotal label" value="${escAttr(curLbl)}">`;
    }

    html += `<button type="button" class="btn btn-sm fw-semibold px-3 ms-2"
                     style="background:#1B2A4A;color:#fff;border:none;border-radius:6px;font-size:11px"
                     onclick="saveEdit(this)"><i class="fas fa-check me-1"></i>Save</button>
             <button type="button" class="btn btn-sm px-2"
                     style="background:#F1F5F9;color:#64748B;border:none;border-radius:6px;font-size:11px"
                     onclick="cancelEdit(this)">Cancel</button></div>`;

    row.insertAdjacentHTML('beforeend', html);
}

function saveEdit(btn) {
    const row = btn.closest('.line-row');
    const lt  = row.dataset.line_type;

    if (lt === 'sub_category') {
        const scSel  = row.querySelector('.edit-subcat');
        const scId   = scSel.value;
        const scName = scSel.selectedOptions[0]?.text ?? '';
        const scType = scSel.selectedOptions[0]?.dataset.type ?? '';
        const op     = row.querySelector('.edit-op').value;
        const lbl    = row.querySelector('.edit-lbl').value.trim();

        row.dataset.sub_category_id = scId;
        row.dataset.operator        = op;
        row.dataset.label           = lbl;

        const displayLabel = lbl || (op === 'subtract' ? 'Less: ' : '') + scName;
        const color = scType === 'revenue' ? '#10B981' : '#F43F5E';

        row.querySelector('.row-badge').textContent      = scType.toUpperCase();
        row.querySelector('.row-badge').style.background = color + '22';
        row.querySelector('.row-badge').style.color      = color;
        row.querySelector('.row-main').textContent       = displayLabel;

        const opBadge = row.querySelector('.row-op');
        opBadge.textContent      = op === 'add' ? '+ Add' : '− Less';
        opBadge.style.background = op === 'add' ? '#D1FAE5' : '#FEE2E2';
        opBadge.style.color      = op === 'add' ? '#065F46' : '#991B1B';

    } else if (lt === 'subtotal') {
        const lbl = row.querySelector('.edit-lbl').value.trim();
        if (!lbl) { alert('Subtotal label is required.'); return; }
        row.dataset.label = lbl;
        row.querySelector('.row-main').textContent = lbl;
    }

    exitEditMode(row);
    rebuildCsSelects();
}

function cancelEdit(btn) { exitEditMode(btn.closest('.line-row')); }

function exitEditMode(row) {
    row.querySelector('.row-edit-panel')?.remove();
    row.classList.remove('editing');
    const eb = row.querySelector('.edit-btn');
    if (eb) eb.style.display = '';
}

function prepareSubmit() {
    const container = document.getElementById('hiddenLines');
    container.innerHTML = '';
    document.querySelectorAll('#sortableLines .line-row').forEach((row, i) => {
        ['line_type', 'sub_category_id', 'label', 'operator', 'cs_base_sub_category_id', 'cs_base_subtotal_label'].forEach(field => {
            const inp   = document.createElement('input');
            inp.type    = 'hidden';
            inp.name    = `lines[${i}][${field}]`;
            inp.value   = row.dataset[field] || '';
            container.appendChild(inp);
        });
    });
}
</script>

<style>
.sort-ghost { opacity: .4; background: #EFF6FF !important; }
.line-row:hover { background: #F8FAFC; }
.drag-handle:hover { color: #E65C00 !important; }
.line-row.editing { flex-wrap: wrap; align-items: flex-start; }
.row-edit-panel {
    flex-basis: 100%;
    background: #EFF6FF;
    border-top: 1px solid #BFDBFE;
    padding: 8px 44px 10px;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: center;
}
</style>

@endsection
