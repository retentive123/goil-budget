@php
$subType  = $line->subCategory?->budget_type ?? 'expense';
$color    = $typeColors[$subType] ?? '#64748B';
$typeLabel = $typeLabels[$subType] ?? strtoupper($subType);

if ($line->line_type === 'sub_category') {
    $displayLabel = $line->label
        ? $line->label
        : (($line->operator === 'subtract' ? 'Less: ' : '') . ($line->subCategory?->name ?? '—'));
} elseif ($line->line_type === 'subtotal') {
    $displayLabel = $line->label ?? 'Subtotal';
} else {
    $displayLabel = '— blank row —';
}
@endphp

<div class="line-row border-bottom d-flex align-items-center px-3 py-2 gap-2"
     style="font-size:13px;cursor:default"
     data-line_type="{{ $line->line_type }}"
     data-sub_category_id="{{ $line->sub_category_id ?? '' }}"
     data-label="{{ $line->label ?? '' }}"
     data-operator="{{ $line->operator ?? '' }}"
     data-cs_base_sub_category_id="{{ $line->cs_base_sub_category_id ?? '' }}"
     data-cs_base_subtotal_label="{{ $line->cs_base_subtotal_label ?? '' }}">

    <span class="drag-handle text-muted me-1" style="cursor:grab;font-size:16px">⠿</span>

    @if($line->line_type === 'sub_category')
    <span class="row-badge badge me-1"
          style="font-size:10px;border-radius:4px;background:{{ $color }}22;color:{{ $color }}">
        {{ strtoupper($subType) }}
    </span>
    <span class="row-main fw-semibold"
          style="color:#1B2A4A;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
        {{ $displayLabel }}
    </span>
    <select class="cs-base-sel"
            data-selected="{{ $line->cs_base_sub_category_id ?? '' }}"
            onchange="onCsBaseChange(this)"
            style="font-size:10px;border:1px solid #FFFBEB;background:#FFFBEB;color:#92400E;
                   border-radius:4px;padding:2px 4px;max-width:130px;cursor:pointer"
            title="CS% base line">
        <option value="">CS%: none</option>
    </select>
    <span class="row-op badge"
          style="font-size:10px;border-radius:4px;
                 background:{{ $line->operator === 'add' ? '#D1FAE5' : '#FEE2E2' }};
                 color:{{ $line->operator === 'add' ? '#065F46' : '#991B1B' }}">
        {{ $line->operator === 'add' ? '+ Add' : '− Less' }}
    </span>

    @elseif($line->line_type === 'subtotal')
    <span class="row-badge badge me-1"
          style="font-size:10px;border-radius:4px;background:#EFF6FF;color:#1D4ED8">SUBTOTAL</span>
    <span class="row-main fw-semibold"
          style="color:#1D4ED8;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
        {{ $displayLabel }}
    </span>
    @php
        $stCsKey = $line->cs_base_sub_category_id
            ? 'sc:' . $line->cs_base_sub_category_id
            : ($line->cs_base_subtotal_label ? 'st:' . $line->cs_base_subtotal_label : '');
    @endphp
    <select class="cs-base-sel"
            data-selected="{{ $stCsKey }}"
            onchange="onCsBaseChange(this)"
            style="font-size:10px;border:1px solid #FFFBEB;background:#FFFBEB;color:#92400E;
                   border-radius:4px;padding:2px 4px;max-width:130px;cursor:pointer"
            title="CS% base line">
        <option value="">CS%: none</option>
    </select>
    <span class="row-op"></span>

    @else {{-- spacer --}}
    <span class="row-badge badge me-1"
          style="font-size:10px;border-radius:4px;background:#F1F5F9;color:#94A3B8">SPACER</span>
    <span class="row-main" style="color:#94A3B8;flex:1">— blank row —</span>
    <span class="row-op"></span>
    @endif

    <button type="button"
            class="btn btn-sm ms-2 p-0 px-2"
            style="background:#FEE2E2;color:#991B1B;border:none;border-radius:5px;font-size:11px"
            onclick="removeLine(this)">
        <i class="fas fa-times"></i>
    </button>
</div>
