{{--
    Reusable Dept / Station Tom Select
    Props:
      $selectedId  — currently selected department_id (integer or null)
      $inputName   — form field name (default: 'department_id')
--}}
@php
    $inputName  = $inputName  ?? 'department_id';
    $selectedId = $selectedId ?? null;

    $depts    = $departments->filter(fn($d) => !$d->isServiceStation())->sortBy('name');
    $stations = $departments->filter(fn($d) =>  $d->isServiceStation());

    // Group stations by zone: ['Zone Name' => Collection, ...]
    $byZone = $stations->groupBy(fn($s) => $s->zone?->name ?? 'No Zone')->sortKeys();
@endphp

<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">

<select id="deptStationSelect"
        name="{{ $inputName }}"
        class="@error($inputName) is-invalid @enderror"
        placeholder="— None —"
        autocomplete="off">
    <option value="">— None —</option>

    @if($depts->isNotEmpty())
    <optgroup label="── Departments ──">
        @foreach($depts as $d)
        <option value="{{ $d->id }}"
            {{ (string)$selectedId === (string)$d->id ? 'selected' : '' }}>
            {{ $d->name }}
        </option>
        @endforeach
    </optgroup>
    @endif

    @foreach($byZone as $zoneName => $zoneStations)
    <optgroup label="{{ $zoneName }}">
        @foreach($zoneStations->sortBy('name') as $s)
        <option value="{{ $s->id }}"
            {{ (string)$selectedId === (string)$s->id ? 'selected' : '' }}>
            {{ $s->name }}
        </option>
        @endforeach
    </optgroup>
    @endforeach
</select>

@error($inputName)
<div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
@enderror

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
(function () {
    new TomSelect('#deptStationSelect', {
        placeholder:       '— None —',
        allowEmptyOption:  true,
        searchField:       ['text'],
        maxOptions:        null,
        plugins:           ['clear_button'],
        render: {
            option: function (data, escape) {
                return '<div>' + escape(data.text) + '</div>';
            },
            item: function (data, escape) {
                return '<div>' + escape(data.text) + '</div>';
            },
            option_create: false,
        },
        onInitialize: function () {
            // Style the control to match the rest of the form
            this.control.style.borderColor = '#E2E8F0';
            this.control.style.borderRadius = '10px';
            this.control.style.minHeight = '44px';
        },
    });
})();
</script>

<style>
.ts-wrapper.single .ts-control {
    padding: 8px 12px;
    border-color: #E2E8F0 !important;
    border-radius: 10px !important;
    box-shadow: none !important;
}
.ts-wrapper.single.focus .ts-control {
    border-color: #E65C00 !important;
    box-shadow: 0 0 0 0.2rem rgba(230,92,0,.15) !important;
}
.ts-dropdown .ts-dropdown-content .optgroup-header {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .6px;
    color: #94A3B8;
    padding: 6px 12px 2px;
    pointer-events: none;
}
.ts-dropdown .option {
    padding: 6px 14px;
    font-size: 13px;
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
.ts-control input {
    font-size: 13px !important;
}
.clear-button {
    color: #94A3B8 !important;
}
</style>
