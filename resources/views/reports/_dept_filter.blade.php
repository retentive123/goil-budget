{{--
    Reusable Dept / Station / Subsidiary filter for reports.
    The parent is responsible for the <div col-md-X> wrapper and <label>.

    Props (all optional):
      $filterName      — input name (default 'department_id')
      $selectedId      — current value: string/int for single, array for multi
      $selectedSubId   — current subsidiary_id (from request), used to pre-select s:{id}
      $allowEmpty      — show "All …" option (default true, ignored in multi mode)
      $emptyLabel      — label for the empty option (default 'All Entities')
      $multiple        — bool, true = multi-select (default false)
      $maxItems        — Tom Select maxItems (default 1 single / 5 multi)
      $autoSubmit      — bool, submit the parent form on change (default false)
      $selectId        — HTML id for the <select> (default 'rptDeptSel')
      $includeSubsidiaries — bool, include subsidiary optgroup (default true)
--}}
@php
    $filterName          = $filterName          ?? 'department_id';
    $selectedId          = $selectedId          ?? null;
    $selectedSubId       = $selectedSubId       ?? request('subsidiary_id');
    $allowEmpty          = $allowEmpty          ?? true;
    $emptyLabel          = $emptyLabel          ?? 'All Entities';
    $multiple            = $multiple            ?? false;
    $maxItems            = $maxItems            ?? ($multiple ? 5 : 1);
    $autoSubmit          = $autoSubmit          ?? false;
    $selectId            = $selectId            ?? 'rptDeptSel';
    $includeSubsidiaries = $includeSubsidiaries ?? true;

    // Split into departments and stations grouped by zone
    $_depts  = $departments->filter(fn($d) => !$d->isServiceStation())->sortBy('name');
    $_byZone = $departments->filter(fn($d) =>  $d->isServiceStation())
                           ->groupBy(fn($s) => $s->zone?->name ?? 'No Zone')
                           ->sortKeys();

    // Load subsidiaries grouped by category (for the optgroup)
    $_subCats = $includeSubsidiaries && !$multiple
        ? \App\Models\SubsidiaryCategory::with(['subsidiaries' => fn($q) => $q->orderBy('name')])
              ->orderBy('name')->get()
        : collect();
    $_hasSubsidiaries = $_subCats->flatMap->subsidiaries->isNotEmpty();

    // If a subsidiary is selected, compute the pre-select value (s:{id})
    $_preSelectSub = $selectedSubId ? 's:' . $selectedSubId : null;

    // Normalise selectedId to array for comparison
    $_selected = $multiple
        ? (is_array($selectedId) ? array_map('strval', $selectedId) : [])
        : [(string) $selectedId];
@endphp

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
            {{ in_array((string)$d->id, $_selected) && !$_preSelectSub ? 'selected' : '' }}>
            {{ $d->name }}
        </option>
        @endforeach
    </optgroup>
    @endif

    @foreach($_byZone as $_zoneName => $_zoneStations)
    <optgroup label="{{ $_zoneName }}">
        @foreach($_zoneStations->sortBy('name') as $s)
        <option value="{{ $s->id }}"
            {{ in_array((string)$s->id, $_selected) && !$_preSelectSub ? 'selected' : '' }}>
            {{ $s->name }}
        </option>
        @endforeach
    </optgroup>
    @endforeach

    @if($includeSubsidiaries && !$multiple && $_hasSubsidiaries)
    @foreach($_subCats as $_sc)
        @if($_sc->subsidiaries->isNotEmpty())
        <optgroup label="◈ {{ $_sc->name }}">
            @foreach($_sc->subsidiaries as $_sub)
            <option value="s:{{ $_sub->id }}"
                {{ $_preSelectSub === 's:'.$_sub->id ? 'selected' : '' }}>
                {{ $_sub->name }}
            </option>
            @endforeach
        </optgroup>
        @endif
    @endforeach
    @endif
</select>

{{-- Hidden subsidiary_id field — populated by JS when a subsidiary is selected --}}
@if($includeSubsidiaries && !$multiple)
<input type="hidden" name="subsidiary_id" id="{{ $selectId }}_subHidden" value="{{ $selectedSubId ?? '' }}">
@endif

<script>
(function () {
    var el   = document.getElementById('{{ $selectId }}');
    var subH = document.getElementById('{{ $selectId }}_subHidden');
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
            // TomSelect has already updated el.value at this point.
            var val = (el.value || '').trim();
            if (subH) {
                if (val.startsWith('s:')) {
                    subH.value = val.replace('s:', '');
                    el.value   = ''; // clear dept field so only subsidiary_id submits
                } else {
                    subH.value = '';
                }
            }
            el.closest('form').submit();
        },
        @endif
        onInitialize: function () {
            this.control.style.minHeight = '34px';
        },
    });

    @if(!$autoSubmit)
    // On submit: split s:{id} values into department_id / subsidiary_id.
    // Always read el.value directly — TomSelect keeps the underlying <select> in sync.
    var form = el.closest('form');
    if (form && subH) {
        form.addEventListener('submit', function () {
            var val = (el.value || '').trim();
            if (val.startsWith('s:')) {
                subH.value = val.replace('s:', '');
                el.value   = ''; // submit department_id as empty string
            } else {
                subH.value = '';
            }
        }, true); // capture phase fires before the browser serialises form data
    }
    @endif
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
