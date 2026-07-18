@extends('layouts.app')
@section('title', 'Edit Balance Sheet Layout')
@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.balance-sheet-configs.index') }}" class="text-muted text-decoration-none">
        Balance Sheet Layouts
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
$typeColors = ['assets' => '#1D4ED8', 'liabilities' => '#991B1B'];
$typeLabels = ['assets' => 'Assets', 'liabilities' => 'Liabilities'];
@endphp

<div class="row g-4">

    {{-- ── Left: layout builder ── --}}
    <div class="col-lg-8">
        <form method="POST"
              action="{{ route('admin.balance-sheet-configs.update', $config) }}"
              id="configForm">
            @csrf @method('PUT')

            <div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <label class="small fw-semibold mb-0" style="white-space:nowrap;color:#1B2A4A">Layout Name</label>
                        <input type="text" name="name" value="{{ old('name', $config->name) }}"
                               class="form-control form-control-sm @error('name') is-invalid @enderror"
                               style="max-width:300px">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <button type="submit" onclick="prepareSubmit()"
                                class="btn btn-sm fw-semibold ms-auto"
                                style="background:#1B2A4A;color:#fff;border-radius:8px;border:none;white-space:nowrap">
                            <i class="fas fa-save me-1"></i> Save Layout
                        </button>
                    </div>
                </div>
            </div>

            <div id="hiddenLines"></div>

            <div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden">
                <div class="card-header border-0 px-4 py-3 d-flex align-items-center justify-content-between"
                     style="background:#1B2A4A;color:#fff">
                    <div>
                        <i class="fas fa-list-ol me-2"></i>
                        <span class="fw-semibold">Balance Sheet Lines</span>
                        <small class="ms-2 opacity-75">Drag to reorder</small>
                    </div>
                    <small class="opacity-60" id="lineCount">{{ $config->lines->count() }} lines</small>
                </div>
                <div class="card-body p-0">
                    @if($config->lines->isEmpty())
                    <div id="emptyMsg" class="text-center py-5 text-muted" style="font-size:13px">
                        <i class="fas fa-stream fa-2x mb-2 opacity-25"></i>
                        <p class="mb-0">No lines yet. Add rows using the panel on the right.</p>
                    </div>
                    @else
                    <div id="emptyMsg" class="text-center py-5 text-muted" style="font-size:13px;display:none">
                        <i class="fas fa-stream fa-2x mb-2 opacity-25"></i>
                        <p class="mb-0">No lines yet. Add rows using the panel on the right.</p>
                    </div>
                    @endif

                    <div id="sortableLines">
                        @foreach($config->lines as $line)
                        @include('admin.balance-sheet-config._line_row', ['line' => $line, 'typeColors' => $typeColors, 'typeLabels' => $typeLabels])
                        @endforeach
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- ── Right: add line panel ── --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden;position:sticky;top:80px">
            <div class="card-header border-0 px-4 py-3" style="background:#1B2A4A;color:#fff">
                <i class="fas fa-plus me-2"></i><span class="fw-semibold">Add Line</span>
            </div>
            <div class="card-body p-4">

                {{-- Line type picker --}}
                <div class="mb-3">
                    <label class="form-label small fw-semibold mb-2">Line Type</label>
                    <div class="d-flex gap-2">
                        @foreach(['sub_category' => 'Sub-Category', 'subtotal' => 'Subtotal', 'spacer' => 'Spacer'] as $val => $lbl)
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="newLineType"
                                   id="type_{{ $val }}" value="{{ $val }}"
                                   {{ $val === 'sub_category' ? 'checked' : '' }}>
                            <label class="form-check-label small" for="type_{{ $val }}">{{ $lbl }}</label>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Sub-category fields --}}
                <div id="subCatFields">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Sub-Category</label>
                        <select id="newSubCatId" class="form-select form-select-sm">
                            <option value="">— Select —</option>
                            @foreach($subCategories as $type => $subs)
                            <optgroup label="{{ $typeLabels[$type] ?? ucfirst($type) }}">
                                @foreach($subs as $sub)
                                <option value="{{ $sub->id }}"
                                        data-name="{{ $sub->name }}"
                                        data-type="{{ $sub->budget_type }}">
                                    {{ $sub->name }}
                                </option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Label <span class="text-muted fw-normal">(optional override)</span></label>
                        <input type="text" id="newLabel" class="form-control form-control-sm"
                               placeholder="Leave blank to use sub-category name">
                    </div>
                </div>

                {{-- Subtotal fields --}}
                <div id="subtotalFields" style="display:none">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Label</label>
                        <input type="text" id="newSubtotalLabel" class="form-control form-control-sm"
                               placeholder="e.g. Total Current Assets">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Section</label>
                        <select id="newSubtotalSection" class="form-select form-select-sm">
                            <option value="assets">Assets</option>
                            <option value="liabilities">Liabilities</option>
                        </select>
                        <div class="form-text">Which running total this subtotal displays.</div>
                    </div>
                </div>

                {{-- Spacer fields --}}
                <div id="spacerFields" style="display:none">
                    <p class="small text-muted">Inserts a blank separator row.</p>
                </div>

                <button type="button" class="btn btn-sm w-100 fw-semibold"
                        style="background:#1B2A4A;color:#fff;border-radius:8px"
                        onclick="addLine()">
                    <i class="fas fa-plus me-1"></i>Add Line
                </button>
            </div>
        </div>

        <div class="mt-3 p-3 rounded-3 small text-muted" style="background:#F8FAFC;border:1px solid #E2E8F0">
            <div class="fw-semibold mb-1" style="color:#1B2A4A">How it works</div>
            Lines are rendered top-to-bottom. Each <strong>sub-category</strong> row adds its amounts to its section's running total.
            A <strong>subtotal</strong> row shows the current running total for the chosen section (Assets or Liabilities).
        </div>
    </div>
</div>

{{-- Row template (hidden) --}}
<template id="rowTemplate">
    <div class="line-row border-bottom d-flex align-items-center px-3 py-2 gap-2"
         style="font-size:13px;cursor:default"
         data-line_type="" data-sub_category_id="" data-label="" data-section="">

        <span class="drag-handle text-muted me-1" style="cursor:grab;font-size:16px">⠿</span>

        <span class="row-badge badge me-1" style="font-size:10px;border-radius:4px"></span>

        <span class="row-main fw-semibold" style="color:#1B2A4A;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></span>

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

<script>
const SUB_CATS = @json($subCategories->flatten()->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'budget_type' => $s->budget_type])->values());

const sortable = Sortable.create(document.getElementById('sortableLines'), {
    animation: 150,
    handle: '.drag-handle',
    ghostClass: 'table-active',
    onEnd() { updateCount(); }
});

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
        const sel     = document.getElementById('newSubCatId');
        const subId   = sel.value;
        const subName = sel.selectedOptions[0]?.dataset.name ?? '';
        const subType = sel.selectedOptions[0]?.dataset.type ?? 'assets';
        const lbl     = document.getElementById('newLabel').value.trim();

        if (!subId) { alert('Please select a sub-category.'); return; }

        const displayLabel = lbl || subName;
        const isAssets     = subType === 'assets';
        const color        = isAssets ? '#1D4ED8' : '#991B1B';
        const bg           = isAssets ? '#DBEAFE' : '#FEE2E2';
        const typeLabel    = isAssets ? 'ASSETS' : 'LIABILITIES';

        row.dataset.line_type       = 'sub_category';
        row.dataset.sub_category_id = subId;
        row.dataset.label           = lbl;
        row.dataset.section         = subType;

        row.querySelector('.row-badge').textContent      = typeLabel;
        row.querySelector('.row-badge').style.background = bg;
        row.querySelector('.row-badge').style.color      = color;
        row.querySelector('.row-main').textContent       = displayLabel;
        row.querySelector('.row-op').style.display       = 'none';

    } else if (type === 'subtotal') {
        const lbl     = document.getElementById('newSubtotalLabel').value.trim();
        const section = document.getElementById('newSubtotalSection').value;
        if (!lbl) { alert('Please enter a label for the subtotal row.'); return; }

        const isAssets  = section === 'assets';
        const secColor  = isAssets ? '#1E40AF' : '#991B1B';
        const secBg     = isAssets ? '#DBEAFE' : '#FEE2E2';

        row.dataset.line_type = 'subtotal';
        row.dataset.label     = lbl;
        row.dataset.section   = section;

        row.querySelector('.row-badge').textContent    = 'SUBTOTAL';
        row.querySelector('.row-badge').style.background = '#EFF6FF';
        row.querySelector('.row-badge').style.color      = '#1D4ED8';
        row.querySelector('.row-main').textContent       = lbl;
        row.querySelector('.row-main').style.color       = '#1D4ED8';
        row.querySelector('.row-op').textContent         = isAssets ? 'Assets' : 'Liabilities';
        row.querySelector('.row-op').style.background    = secBg;
        row.querySelector('.row-op').style.color         = secColor;

    } else { // spacer
        row.dataset.line_type = 'spacer';
        row.dataset.section   = '';

        row.querySelector('.row-badge').textContent      = 'SPACER';
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
    clearAddForm();
}

function removeLine(btn) {
    btn.closest('.line-row').remove();
    const remaining = document.querySelectorAll('#sortableLines .line-row').length;
    document.getElementById('emptyMsg').style.display = remaining === 0 ? '' : 'none';
    updateCount();
}

function updateCount() {
    const n = document.querySelectorAll('#sortableLines .line-row').length;
    document.getElementById('lineCount').textContent = n + ' ' + (n === 1 ? 'line' : 'lines');
}

function clearAddForm() {
    document.getElementById('newSubCatId').value       = '';
    document.getElementById('newLabel').value          = '';
    document.getElementById('newSubtotalLabel').value  = '';
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
        const curLbl  = row.dataset.label || '';

        const scOpts = SUB_CATS.map(s =>
            `<option value="${s.id}" data-type="${escAttr(s.budget_type)}" ${s.id == curScId ? 'selected' : ''}>${escAttr(s.name)}</option>`
        ).join('');
        html += `<label class="small text-muted mb-0">Sub-cat:</label>
                 <select class="edit-subcat form-select form-select-sm" style="max-width:220px">${scOpts}</select>
                 <label class="small text-muted mb-0 ms-1">Label:</label>
                 <input type="text" class="edit-lbl form-control form-control-sm" style="max-width:180px"
                        placeholder="Override (optional)" value="${escAttr(curLbl)}">`;

    } else if (lt === 'subtotal') {
        const curLbl     = row.dataset.label || '';
        const curSection = row.dataset.section || 'assets';

        html += `<label class="small text-muted mb-0">Label:</label>
                 <input type="text" class="edit-lbl form-control form-control-sm" style="max-width:220px"
                        placeholder="Subtotal label" value="${escAttr(curLbl)}">
                 <label class="small text-muted mb-0 ms-1">Section:</label>
                 <select class="edit-section form-select form-select-sm" style="max-width:140px">
                     <option value="assets"      ${curSection==='assets'?'selected':''}>Assets</option>
                     <option value="liabilities" ${curSection==='liabilities'?'selected':''}>Liabilities</option>
                 </select>`;
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
        const scType = scSel.selectedOptions[0]?.dataset.type ?? 'assets';
        const lbl    = row.querySelector('.edit-lbl').value.trim();

        const isAssets = scType === 'assets';
        const color    = isAssets ? '#1D4ED8' : '#991B1B';
        const bg       = isAssets ? '#DBEAFE' : '#FEE2E2';
        const typeLabel = isAssets ? 'ASSETS' : 'LIABILITIES';

        row.dataset.sub_category_id = scId;
        row.dataset.label           = lbl;
        row.dataset.section         = scType;

        row.querySelector('.row-badge').textContent      = typeLabel;
        row.querySelector('.row-badge').style.background = bg;
        row.querySelector('.row-badge').style.color      = color;
        row.querySelector('.row-main').textContent       = lbl || scName;

    } else if (lt === 'subtotal') {
        const lbl     = row.querySelector('.edit-lbl').value.trim();
        const section = row.querySelector('.edit-section').value;
        if (!lbl) { alert('Label is required.'); return; }

        const isAssets = section === 'assets';
        const secBg    = isAssets ? '#DBEAFE' : '#FEE2E2';
        const secColor = isAssets ? '#1E40AF' : '#991B1B';

        row.dataset.label   = lbl;
        row.dataset.section = section;

        row.querySelector('.row-main').textContent       = lbl;
        row.querySelector('.row-op').textContent         = isAssets ? 'Assets' : 'Liabilities';
        row.querySelector('.row-op').style.background    = secBg;
        row.querySelector('.row-op').style.color         = secColor;
    }

    exitEditMode(row);
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
        ['line_type', 'sub_category_id', 'label', 'section'].forEach(field => {
            const inp = document.createElement('input');
            inp.type  = 'hidden';
            inp.name  = `lines[${i}][${field}]`;
            inp.value = row.dataset[field] || '';
            container.appendChild(inp);
        });
    });
}
</script>

<style>
.sort-ghost { opacity:.4; background:#EFF6FF !important; }
.line-row:hover { background:#F8FAFC; }
.drag-handle:hover { color:#1B2A4A !important; }
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
