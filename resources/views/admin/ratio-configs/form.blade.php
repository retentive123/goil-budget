@extends('layouts.app')
@section('title', $ratio->exists ? 'Edit Ratio' : 'New Ratio')
@section('content')

<div class="row justify-content-center">
    <div class="col-lg-8">

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <div class="small mb-1">
                    <a href="{{ route('admin.ratio-configs.index') }}"
                       style="color:#64748B;text-decoration:none">Ratio Configs</a>
                    <span class="mx-1" style="color:#CBD5E1">/</span>
                    <span style="color:#1B2A4A;font-weight:600">
                        {{ $ratio->exists ? 'Edit' : 'New Ratio' }}
                    </span>
                </div>
                <h5 class="fw-bold mb-0" style="color:#1B2A4A">
                    {{ $ratio->exists ? $ratio->name : 'New Ratio' }}
                </h5>
            </div>
        </div>

        @if($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0 small">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form method="POST"
              action="{{ $ratio->exists ? route('admin.ratio-configs.update', $ratio) : route('admin.ratio-configs.store') }}">
            @csrf
            @if($ratio->exists) @method('PUT') @endif

            {{-- Identity --}}
            <div class="card border-0 shadow-sm mb-3" style="border-radius:12px;border:1px solid #E2E8F0!important">
                <div class="card-header px-4 py-3 border-0"
                     style="background:#E65C00;color:#fff;border-radius:11px 11px 0 0">
                    <span class="fw-semibold" style="font-size:13px">Identity</span>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold" style="color:#1B2A4A">Name</label>
                        <input type="text" name="name"
                               value="{{ old('name', $ratio->name) }}"
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="e.g. Expense to Revenue Ratio">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold" style="color:#1B2A4A">Description</label>
                        <textarea name="description" rows="2"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="What does this ratio measure?">{{ old('description', $ratio->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold" style="color:#1B2A4A">Unit / Symbol</label>
                            <input type="text" name="unit"
                                   value="{{ old('unit', $ratio->unit ?? '%') }}"
                                   class="form-control @error('unit') is-invalid @enderror"
                                   placeholder="%">
                            @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold" style="color:#1B2A4A">Multiply by</label>
                            <input type="number" name="multiply_by" step="0.0001"
                                   value="{{ old('multiply_by', $ratio->multiply_by ?? 100) }}"
                                   class="form-control @error('multiply_by') is-invalid @enderror">
                            <div class="form-text">100 for %, 1 for raw ratio</div>
                            @error('multiply_by')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold" style="color:#1B2A4A">Sort Order</label>
                            <input type="number" name="sort_order" min="0"
                                   value="{{ old('sort_order', $ratio->sort_order ?? 0) }}"
                                   class="form-control">
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" name="higher_is_better" value="1"
                                       id="hib" class="form-check-input"
                                       {{ old('higher_is_better', $ratio->higher_is_better ?? true) ? 'checked' : '' }}>
                                <label for="hib" class="form-check-label small" style="color:#1B2A4A">
                                    Higher value is better
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" value="1"
                                       id="active" class="form-check-input"
                                       {{ old('is_active', $ratio->is_active ?? true) ? 'checked' : '' }}>
                                <label for="active" class="form-check-label small" style="color:#1B2A4A">
                                    Active (show in report)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Formula --}}
            <div class="card border-0 shadow-sm mb-3" style="border-radius:12px;border:1px solid #E2E8F0!important">
                <div class="card-header px-4 py-3 border-0"
                     style="background:#1B2A4A;color:#fff;border-radius:11px 11px 0 0">
                    <span class="fw-semibold" style="font-size:13px">Formula &nbsp;
                        <span style="font-weight:400;opacity:.7;font-size:12px">Numerator ÷ Denominator × Multiply by</span>
                    </span>
                </div>
                <div class="card-body p-4">

                    @php
                        $selNumTypes  = old('numerator_types',          $ratio->numerator_types          ?? []);
                        $selDenTypes  = old('denominator_types',        $ratio->denominator_types        ?? []);
                        $selNumCatIds = array_map('strval', old('numerator_category_ids',   $ratio->numerator_category_ids   ?? []));
                        $selDenCatIds = array_map('strval', old('denominator_category_ids', $ratio->denominator_category_ids ?? []));
                        $selNumCodes  = array_map('strval', old('numerator_codes',          $ratio->numerator_codes          ?? []));
                        $selDenCodes  = array_map('strval', old('denominator_codes',        $ratio->denominator_codes        ?? []));
                    @endphp

                    <div class="row g-4">

                        @foreach([
                            ['num', '#1E40AF', '#BFDBFE', '#EFF6FF', 'numerator',   $selNumTypes, $selNumCatIds, $selNumCodes, 'Numerator (top)'],
                            ['den', '#166534', '#BBF7D0', '#F0FDF4', 'denominator', $selDenTypes, $selDenCatIds, $selDenCodes, 'Denominator (bottom)'],
                        ] as [$side, $accent, $border, $bg, $typeName, $selT, $selC, $selCodes, $sideLabel])

                        @php
                            $srcDefault = old("{$typeName}_source", $ratio->{"{$typeName}_source"} ?? 'budget');
                        @endphp

                        <div class="col-md-6">
                          <div class="p-3 rounded-2" style="background:{{ $bg }};border:1px solid {{ $border }}">
                            <div class="fw-semibold mb-2" style="color:{{ $accent }};font-size:13px">{{ $sideLabel }}</div>

                            {{-- Source --}}
                            <div class="mb-3">
                              <label class="form-label small fw-semibold" style="color:#1B2A4A">Data Source</label>
                              <select name="{{ $typeName }}_source" class="form-select form-select-sm ratio-src" data-side="{{ $side }}">
                                <option value="budget" {{ $srcDefault === 'budget' ? 'selected' : '' }}>Budget (approved line items)</option>
                                <option value="actual" {{ $srcDefault === 'actual' ? 'selected' : '' }}>Actuals (confirmed spend)</option>
                              </select>
                            </div>

                            {{-- Search --}}
                            <input type="text" class="form-control form-control-sm mb-2 tree-search"
                                   data-side="{{ $side }}" placeholder="Search types, categories or codes…" autocomplete="off">

                            {{-- 3-level tree --}}
                            <div class="sel-tree" data-side="{{ $side }}"
                                 style="border:1px solid {{ $border }};border-radius:8px;background:#fff;font-size:12px;max-height:420px;overflow-y:auto">

                              {{-- All Categories — top-level standalone checkbox --}}
                              <div class="tree-row d-flex align-items-center gap-2"
                                   style="padding:8px 10px;border-bottom:1px solid {{ $border }}"
                                   data-search="all categories">
                                <span style="width:16px;flex-shrink:0"></span>
                                <input type="checkbox" id="{{ $side }}_t_all"
                                       name="{{ $typeName }}_types[]" value="all"
                                       class="form-check-input type-cb flex-shrink-0"
                                       data-side="{{ $side }}" data-type="all"
                                       {{ in_array('all', $selT) ? 'checked' : '' }}>
                                <label for="{{ $side }}_t_all" class="mb-0 fw-semibold"
                                       style="color:{{ $accent }};cursor:pointer">
                                    All Categories
                                </label>
                              </div>

                              {{-- One block per budget type (skip 'all') --}}
                              @foreach($allTypes as $typeKey => $typeLabel)
                                @if($typeKey === 'all') @continue @endif
                                @php
                                    $typeCats  = $categoriesByType->get($typeKey, collect());
                                    $isChecked = in_array($typeKey, $selT);
                                    // auto-expand if type is checked, or any cat/code under it is checked
                                    $typeCatIds = $typeCats->pluck('id')->map(fn($id) => (string)$id)->toArray();
                                    $shouldOpen = $isChecked
                                        || collect($selC)->intersect($typeCatIds)->isNotEmpty()
                                        || $typeCats->flatMap->accountCodes->pluck('id')->map(fn($id) => (string)$id)->intersect($selCodes)->isNotEmpty();
                                @endphp

                                <div class="tree-type-block" data-type="{{ $typeKey }}"
                                     data-search="{{ strtolower($typeLabel) }}"
                                     style="border-bottom:1px solid {{ $border }}">

                                  {{-- Type header --}}
                                  <div class="d-flex align-items-center gap-2"
                                       style="padding:8px 10px;{{ $isChecked ? 'background:rgba(0,0,0,.03)' : '' }}">
                                    @if($typeCats->isNotEmpty())
                                      <button type="button" class="tree-expand flex-shrink-0"
                                              onclick="treeToggle(this)"
                                              style="background:none;border:none;padding:0;width:16px;
                                                     color:#94A3B8;font-size:10px;cursor:pointer;
                                                     transform:{{ $shouldOpen ? 'rotate(90deg)' : '' }};
                                                     transition:transform .2s">▶</button>
                                    @else
                                      <span style="width:16px;flex-shrink:0"></span>
                                    @endif
                                    <input type="checkbox" id="{{ $side }}_t_{{ $typeKey }}"
                                           name="{{ $typeName }}_types[]" value="{{ $typeKey }}"
                                           class="form-check-input type-cb flex-shrink-0"
                                           data-side="{{ $side }}" data-type="{{ $typeKey }}"
                                           {{ $isChecked ? 'checked' : '' }}>
                                    <label for="{{ $side }}_t_{{ $typeKey }}"
                                           class="mb-0 fw-semibold" style="color:{{ $accent }};cursor:pointer;flex:1">
                                        {{ $typeLabel }}
                                        @if($typeCats->isNotEmpty())
                                          <span style="font-weight:400;color:#94A3B8">({{ $typeCats->count() }})</span>
                                        @endif
                                    </label>
                                  </div>

                                  {{-- Category children --}}
                                  @if($typeCats->isNotEmpty())
                                  <div class="tree-type-children"
                                       style="{{ $shouldOpen ? '' : 'display:none;' }}">
                                    @foreach($typeCats as $cat)
                                      @php
                                          $catChecked = in_array((string)$cat->id, $selC);
                                          $codeIds    = $cat->accountCodes->pluck('id')->map(fn($id) => (string)$id)->toArray();
                                          $catHasCodes = !empty($codeIds);
                                          $catOpen    = $catChecked || collect($selCodes)->intersect($codeIds)->isNotEmpty();
                                      @endphp
                                      <div class="tree-cat-block"
                                           data-search="{{ strtolower($cat->name) }}"
                                           style="border-top:1px solid {{ $border }}">

                                        <div class="d-flex align-items-center gap-2"
                                             style="padding:6px 10px 6px 28px;{{ $catChecked ? 'background:rgba(0,0,0,.02)' : '' }}">
                                          @if($catHasCodes)
                                            <button type="button" class="tree-expand flex-shrink-0"
                                                    onclick="treeToggle(this)"
                                                    style="background:none;border:none;padding:0;width:16px;
                                                           color:#94A3B8;font-size:10px;cursor:pointer;
                                                           transform:{{ $catOpen ? 'rotate(90deg)' : '' }};
                                                           transition:transform .2s">▶</button>
                                          @else
                                            <span style="width:16px;flex-shrink:0"></span>
                                          @endif
                                          <input type="checkbox" id="{{ $side }}_c_{{ $cat->id }}"
                                                 name="{{ $typeName }}_category_ids[]" value="{{ $cat->id }}"
                                                 class="form-check-input cat-cb flex-shrink-0"
                                                 data-side="{{ $side }}" data-type="{{ $typeKey }}"
                                                 {{ $catChecked ? 'checked' : '' }}>
                                          <label for="{{ $side }}_c_{{ $cat->id }}"
                                                 class="mb-0" style="color:#1B2A4A;cursor:pointer;flex:1">
                                              {{ $cat->name }}
                                              @if($catHasCodes)
                                                <span style="color:#94A3B8">({{ $cat->accountCodes->count() }})</span>
                                              @endif
                                          </label>
                                        </div>

                                        {{-- Code children --}}
                                        @if($catHasCodes)
                                        <div class="tree-code-list"
                                             style="{{ $catOpen ? '' : 'display:none;' }}background:#FAFAFA">
                                          @foreach($cat->accountCodes as $code)
                                          <div class="tree-code-row d-flex align-items-center gap-2"
                                               data-search="{{ strtolower($code->code.' '.$code->name) }}"
                                               style="padding:4px 10px 4px 52px;border-top:1px solid #F1F5F9">
                                            <input type="checkbox" id="{{ $side }}_code_{{ $code->id }}"
                                                   name="{{ $typeName }}_codes[]" value="{{ $code->id }}"
                                                   class="form-check-input code-cb flex-shrink-0"
                                                   data-side="{{ $side }}" data-cat="{{ $cat->id }}"
                                                   {{ in_array((string)$code->id, $selCodes) ? 'checked' : '' }}>
                                            <label for="{{ $side }}_code_{{ $code->id }}"
                                                   class="mb-0" style="cursor:pointer;flex:1;color:#1B2A4A">
                                                <span style="font-family:monospace;color:#64748B">{{ $code->code }}</span>
                                                {{ $code->name }}
                                            </label>
                                          </div>
                                          @endforeach
                                        </div>
                                        @endif

                                      </div>
                                    @endforeach
                                  </div>
                                  @endif

                                </div>
                              @endforeach

                            </div>{{-- /sel-tree --}}

                            @error("{$typeName}_types")
                              <div class="text-danger mt-1" style="font-size:11px">{{ $message }}</div>
                            @enderror

                          </div>
                        </div>

                        @endforeach

                    </div>{{-- /row --}}

                    {{-- Live formula preview --}}
                    <div class="mt-3 p-3 rounded-2" style="background:#F8FAFC;border:1px solid #E2E8F0;font-size:12px">
                        <span class="fw-semibold" style="color:#1B2A4A">Preview: </span>
                        <span id="formula-preview" style="color:#475569;font-family:monospace"></span>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn fw-semibold px-4"
                        style="background:#E65C00;color:#fff;border:none;border-radius:8px">
                    {{ $ratio->exists ? 'Save Changes' : 'Create Ratio' }}
                </button>
                <a href="{{ route('admin.ratio-configs.index') }}"
                   class="btn fw-semibold px-4"
                   style="background:#F1F5F9;color:#475569;border:1px solid #E2E8F0;border-radius:8px">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</div>

<style>
.form-control,.form-select{border-color:#E2E8F0;border-radius:8px;padding:8px 12px;font-size:13px}
.form-control:focus,.form-select:focus{border-color:#E65C00;box-shadow:0 0 0 3px rgba(230,92,0,.1)}
.form-check-input:checked{background-color:#E65C00;border-color:#E65C00}
.tree-code-row:hover,.tree-cat-block .d-flex:hover{background:rgba(0,0,0,.02)}
</style>

<script>
// ── Expand/collapse a tree node ───────────────────────────────────────────────
function treeToggle(btn) {
    const parent   = btn.closest('.tree-type-block, .tree-cat-block');
    const children = parent.querySelector('.tree-type-children, .tree-code-list');
    if (!children) return;
    const open = children.style.display !== 'none';
    children.style.display = open ? 'none' : '';
    btn.style.transform    = open ? '' : 'rotate(90deg)';
}

// ── Cascade: type → cats + codes; cat → codes ────────────────────────────────
document.querySelectorAll('.type-cb').forEach(cb => {
    cb.addEventListener('change', function() {
        if (this.dataset.type === 'all') { updatePreview(); return; }
        const block = this.closest('.tree-type-block');
        if (!block) return;
        block.querySelectorAll('.cat-cb').forEach(c => c.checked = this.checked);
        block.querySelectorAll('.code-cb').forEach(c => c.checked = this.checked);
        updatePreview();
    });
});

document.querySelectorAll('.cat-cb').forEach(cb => {
    cb.addEventListener('change', function() {
        const block = this.closest('.tree-cat-block');
        if (!block) return;
        block.querySelectorAll('.code-cb').forEach(c => c.checked = this.checked);
        updatePreview();
    });
});

document.querySelectorAll('.code-cb').forEach(cb => {
    cb.addEventListener('change', updatePreview);
});

// ── Search / filter ───────────────────────────────────────────────────────────
document.querySelectorAll('.tree-search').forEach(input => {
    input.addEventListener('input', function() {
        const side = this.dataset.side;
        const term = this.value.toLowerCase().trim();
        const tree = document.querySelector(`.sel-tree[data-side="${side}"]`);

        tree.querySelectorAll('.tree-type-block').forEach(typeBlock => {
            const typeMatch = !term || typeBlock.dataset.search.includes(term);
            let anyVisible = typeMatch;

            typeBlock.querySelectorAll('.tree-cat-block').forEach(catBlock => {
                const catMatch = !term || catBlock.dataset.search.includes(term);
                let codVisible = false;

                catBlock.querySelectorAll('.tree-code-row').forEach(codeRow => {
                    const match = !term || codeRow.dataset.search.includes(term);
                    codeRow.style.display = match ? '' : 'none';
                    if (match) codVisible = true;
                });

                const show = typeMatch || catMatch || codVisible;
                catBlock.style.display = show ? '' : 'none';
                if (show) anyVisible = true;

                // auto-expand category when search finds codes inside
                if (term && codVisible) {
                    const list = catBlock.querySelector('.tree-code-list');
                    const btn  = catBlock.querySelector('.tree-expand');
                    if (list) list.style.display = '';
                    if (btn)  btn.style.transform = 'rotate(90deg)';
                }
            });

            typeBlock.style.display = anyVisible ? '' : 'none';

            // auto-expand type when search finds content inside
            if (term && anyVisible) {
                const children = typeBlock.querySelector('.tree-type-children');
                const btn      = typeBlock.querySelector(':scope > .d-flex > .tree-expand');
                if (children) children.style.display = '';
                if (btn)      btn.style.transform    = 'rotate(90deg)';
            }
        });
    });
});

// ── Formula preview ───────────────────────────────────────────────────────────
function sideLabel(side) {
    const tree = document.querySelector(`.sel-tree[data-side="${side}"]`);
    const parts = [];

    // All categories type
    if (tree.querySelector(`.type-cb[data-type="all"]:checked`)) {
        return 'All Categories';
    }

    // Checked type-level items
    tree.querySelectorAll(`.type-cb:not([data-type="all"]):checked`).forEach(cb => {
        const lbl = cb.closest('.tree-type-block')
                      ?.querySelector('label')
                      ?.textContent.trim().replace(/\(\d+\)/, '').trim();
        if (lbl) parts.push(lbl);
    });

    // Checked category-level items (only if parent type not already selected)
    tree.querySelectorAll('.cat-cb:checked').forEach(cb => {
        const typeBlock = cb.closest('.tree-type-block');
        const typeChecked = typeBlock?.querySelector('.type-cb:not([data-type="all"])');
        if (typeChecked && typeChecked.checked) return; // covered by type
        const lbl = cb.closest('.tree-cat-block')
                      ?.querySelector('label')
                      ?.textContent.trim().replace(/\(\d+\)/, '').trim();
        if (lbl) parts.push(lbl);
    });

    // Checked code-level items (only if parent cat not already selected)
    tree.querySelectorAll('.code-cb:checked').forEach(cb => {
        const catBlock  = cb.closest('.tree-cat-block');
        const catChecked = catBlock?.querySelector('.cat-cb');
        if (catChecked && catChecked.checked) return; // covered by cat
        const lbl = cb.closest('.tree-code-row')
                      ?.querySelector('label')
                      ?.textContent.trim().replace(/\s+/g,' ');
        if (lbl) parts.push(`[${lbl}]`);
    });

    return parts.length ? parts.join(' + ') : '—';
}

function updatePreview() {
    const numSrc = document.querySelector('[name=numerator_source]')?.value   || 'budget';
    const denSrc = document.querySelector('[name=denominator_source]')?.value || 'budget';
    const mult   = document.querySelector('[name=multiply_by]')?.value || '100';
    const unit   = document.querySelector('[name=unit]')?.value || '%';

    document.getElementById('formula-preview').textContent =
        `(${numSrc}: ${sideLabel('num')}) ÷ (${denSrc}: ${sideLabel('den')}) × ${mult}  [${unit}]`;
}

// ── Wire source / meta fields ─────────────────────────────────────────────────
document.querySelectorAll('.ratio-src,[name=multiply_by],[name=unit]').forEach(el => {
    el.addEventListener('change', updatePreview);
    el.addEventListener('input',  updatePreview);
});

// ── Init ──────────────────────────────────────────────────────────────────────
updatePreview();
</script>
@endsection
