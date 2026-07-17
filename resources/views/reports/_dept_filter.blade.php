{{--
    Reusable Dept / Station filter for reports.
    The parent is responsible for the <div col-md-X> wrapper and <label>.

    Props (all optional):
      $filterName  — input name (default 'department_id')
      $selectedId  — current value: string/int for single, array for multi
      $allowEmpty  — show "All …" option (default true, ignored in multi mode)
      $emptyLabel  — label for the empty option (default 'All Depts & Stations')
      $multiple    — bool, true = multi-select (default false)
      $maxItems    — Tom Select maxItems (default 1 single / 5 multi)
      $autoSubmit  — bool, submit the parent form on change (default false)
      $selectId    — HTML id for the <select> (default 'rptDeptSel')
--}}
@php
    $filterName = $filterName ?? 'department_id';
    $selectedId = $selectedId ?? null;
    $allowEmpty = $allowEmpty ?? true;
    $emptyLabel = $emptyLabel ?? 'All Depts & Stations';
    $multiple   = $multiple   ?? false;
    $maxItems   = $maxItems   ?? ($multiple ? 5 : 1);
    $autoSubmit = $autoSubmit ?? false;
    $selectId   = $selectId   ?? 'rptDeptSel';

    // Split into departments and stations grouped by zone
    $_depts  = $departments->filter(fn($d) => !$d->isServiceStation())->sortBy('name');
    $_byZone = $departments->filter(fn($d) =>  $d->isServiceStation())
                           ->groupBy(fn($s) => $s->zone?->name ?? 'No Zone')
                           ->sortKeys();

    // Normalise selectedId to array for comparison
    $_selected = $multiple
        ? (is_array($selectedId) ? array_map('strval', $selectedId) : [])
        : [(string) $selectedId];
@endphp

<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">

<select id="{{ $selectId }}"
        name="{{ $filterName }}{{ $multiple ? '[]' : '' }}"
        {{ $multiple ? 'multiple' : '' }}
        style="width:100%">

    @if($allowEmpty && !$multiple)
    <option value="">{{ $emptyLabel }}</option>
    @endif

    @if($_depts->isNotEmpty())
    <optgroup label="── Departments ──">
        @foreach($_depts as $d)
        <option value="{{ $d->id }}"
            {{ in_array((string)$d->id, $_selected) ? 'selected' : '' }}>
            {{ $d->name }}
        </option>
        @endforeach
    </optgroup>
    @endif

    @foreach($_byZone as $_zoneName => $_zoneStations)
    <optgroup label="{{ $_zoneName }}">
        @foreach($_zoneStations->sortBy('name') as $s)
        <option value="{{ $s->id }}"
            {{ in_array((string)$s->id, $_selected) ? 'selected' : '' }}>
            {{ $s->name }}
        </option>
        @endforeach
    </optgroup>
    @endforeach
</select>

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
(function () {
    var el = document.getElementById('{{ $selectId }}');
    if (!el || el._tomSelect) return;
    new TomSelect(el, {
        plugins:         {!! $multiple ? "['remove_button','clear_button']" : "['clear_button']" !!},
        placeholder:     '{{ addslashes($emptyLabel) }}',
        allowEmptyOption: {{ $allowEmpty && !$multiple ? 'true' : 'false' }},
        maxItems:        {{ $maxItems }},
        searchField:     ['text'],
        maxOptions:      null,
        @if($autoSubmit && !$multiple)
        onChange: function () {
            this.input.closest('form').submit();
        },
        @endif
        onInitialize: function () {
            this.control.style.minHeight = '34px';
        },
    });
})();
</script>

<style>
.ts-wrapper.single .ts-control,
.ts-wrapper.multi  .ts-control {
    border-color: #E2E8F0 !important;
    border-radius: 6px !important;
    box-shadow: none !important;
    min-height: 34px;
    font-size: 13px;
}
.ts-wrapper.focus .ts-control {
    border-color: #E65C00 !important;
    box-shadow: 0 0 0 0.2rem rgba(230,92,0,.15) !important;
}
.ts-dropdown .optgroup-header {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: #94A3B8;
    padding: 6px 10px 2px;
    pointer-events: none;
}
.ts-dropdown .option {
    font-size: 13px;
    padding: 5px 12px;
    color: #1B2A4A;
}
.ts-dropdown .option:hover,
.ts-dropdown .option.active {
    background: rgba(230,92,0,.08);
    color: #E65C00;
}
.ts-dropdown .selected {
    background: rgba(230,92,0,.12) !important;
    color: #E65C00 !important;
}
</style>
