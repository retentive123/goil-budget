@php
if ($line->line_type === 'sub_category') {
    $displayLabel = $line->label ?: ($line->subCategory?->name ?? '—');
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
     data-label="{{ $line->label ?? '' }}">

    <span class="drag-handle text-muted me-1" style="cursor:grab;font-size:16px">⠿</span>

    @if($line->line_type === 'sub_category')
    <span class="row-badge badge me-1"
          style="font-size:10px;border-radius:4px;background:#FFF7ED;color:#C2410C">
        CAPEX
    </span>
    <span class="row-main fw-semibold"
          style="color:#1B2A4A;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
        {{ $displayLabel }}
    </span>
    <span class="row-op"></span>

    @elseif($line->line_type === 'subtotal')
    <span class="row-badge badge me-1"
          style="font-size:10px;border-radius:4px;background:#EFF6FF;color:#1D4ED8">SUBTOTAL</span>
    <span class="row-main fw-semibold"
          style="color:#1D4ED8;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
        {{ $displayLabel }}
    </span>
    <span class="row-op"></span>

    @else {{-- spacer --}}
    <span class="row-badge badge me-1"
          style="font-size:10px;border-radius:4px;background:#F1F5F9;color:#94A3B8">SPACER</span>
    <span class="row-main" style="color:#94A3B8;flex:1">— blank row —</span>
    <span class="row-op"></span>
    @endif

    @if($line->line_type !== 'spacer')
    <button type="button"
            class="btn btn-sm p-0 px-2 edit-btn"
            style="background:#EFF6FF;color:#1D4ED8;border:none;border-radius:5px;font-size:11px"
            onclick="editLine(this)">
        <i class="fas fa-pen" style="font-size:10px"></i>
    </button>
    @endif
    <button type="button"
            class="btn btn-sm ms-1 p-0 px-2"
            style="background:#FEE2E2;color:#991B1B;border:none;border-radius:5px;font-size:11px"
            onclick="removeLine(this)">
        <i class="fas fa-times"></i>
    </button>
</div>
