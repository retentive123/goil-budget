@extends('layouts.app')
@section('title', 'Mass Assign Account Codes — Service Stations')
@section('content')

<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item">
                <a href="{{ route('admin.service-stations.index') }}" class="text-decoration-none">
                    <i class="fas fa-gas-pump"></i> Service Stations
                </a>
            </li>
            <li class="breadcrumb-item active">Mass Assign Account Codes</li>
        </ol>
    </nav>
    <h5 class="fw-bold mb-0" style="color:#1B2A4A">Mass Assign Account Codes</h5>
    <p class="text-muted small mb-0">Select multiple service stations and the account codes to assign to all of them at once.</p>
</div>

<form method="POST" action="{{ route('admin.service-stations.mass-assign.store') }}" id="massForm">
@csrf

<div class="row g-4" style="padding-bottom: 90px;">

    {{-- LEFT: Station picker --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm" style="border-radius:12px; position:sticky; top:80px; max-height:calc(100vh - 160px); display:flex; flex-direction:column;">
            <div class="card-header border-0 d-flex justify-content-between align-items-center py-3 px-4"
                 style="background:#1B2A4A; border-radius:12px 12px 0 0; flex-shrink:0">
                <div>
                    <span class="fw-semibold" style="color:#fff; font-size:13px;">
                        <i class="fas fa-gas-pump" style="color:#E65C00"></i> Service Stations
                    </span>
                </div>
                <span class="badge" id="stationBadge"
                      style="background:#E65C00; font-size:11px; border-radius:20px;">0 selected</span>
            </div>

            {{-- Zone filter pills --}}
            @if($zones->count() > 1)
            <div class="px-3 pt-3 pb-2" style="border-bottom:1px solid #E2E8F0; flex-shrink:0;">
                <div class="d-flex flex-wrap gap-1">
                    <button type="button" class="btn btn-xs zone-pill active-pill" data-zone="all"
                            onclick="filterZone('all', this)"
                            style="font-size:11px; padding:2px 10px; border-radius:20px; background:#1B2A4A; color:#fff; border:none;">
                        All Zones
                    </button>
                    @foreach($zones as $zone)
                    <button type="button" class="btn btn-xs zone-pill" data-zone="{{ $zone->id }}"
                            onclick="filterZone({{ $zone->id }}, this)"
                            style="font-size:11px; padding:2px 10px; border-radius:20px; background:#F1F5F9; color:#475569; border:1px solid #E2E8F0;">
                        {{ $zone->name }}
                    </button>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Select / clear all --}}
            <div class="px-4 py-2 d-flex justify-content-between align-items-center"
                 style="border-bottom:1px solid #E2E8F0; flex-shrink:0; background:#F8FAFC;">
                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" style="font-size:12px; color:#E65C00;" onclick="selectAllStations()">Select all</button>
                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" style="font-size:12px; color:#64748B;" onclick="clearAllStations()">Clear</button>
            </div>

            {{-- Station list --}}
            <div style="overflow-y:auto; flex:1;">
                @foreach($stations->groupBy(fn($s) => $s->zone?->name ?? 'No Zone') as $zoneName => $zoneStations)
                <div class="zone-section" data-zone-name="{{ $zoneName }}">
                    <div class="px-4 py-2" style="background:#F8FAFC; font-size:10px; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid #E2E8F0;">
                        <i class="fas fa-map-marker-alt" style="color:#E65C00; font-size:9px;"></i>
                        {{ $zoneName }}
                        <span style="color:#CBD5E1;">({{ $zoneStations->count() }})</span>
                    </div>
                    @foreach($zoneStations as $station)
                    <label class="station-row d-flex align-items-center gap-3 px-4 py-2"
                           data-zone="{{ $station->zone_id ?? 0 }}"
                           style="cursor:pointer; border-bottom:1px solid #F1F5F9; transition:background .15s;">
                        <input type="checkbox" name="station_ids[]" value="{{ $station->id }}"
                               class="form-check-input station-cb flex-shrink-0"
                               onchange="updateStationCount()"
                               style="width:16px;height:16px;cursor:pointer;accent-color:#E65C00;">
                        <div style="min-width:0;">
                            <div style="font-size:13px; font-weight:600; color:#1B2A4A; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                {{ $station->name }}
                            </div>
                            <div style="font-size:11px; color:#94A3B8;">{{ $station->code }}</div>
                        </div>
                    </label>
                    @endforeach
                </div>
                @endforeach
                @if($stations->isEmpty())
                <div class="text-center py-5 text-muted" style="font-size:13px;">
                    No active service stations found.
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- RIGHT: Code picker --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-header border-0 d-flex justify-content-between align-items-center py-3 px-4"
                 style="background:#1B2A4A; border-radius:12px 12px 0 0;">
                <span class="fw-semibold" style="color:#fff; font-size:13px;">
                    <i class="fas fa-hashtag" style="color:#E65C00"></i> Account Codes
                </span>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge" id="codeBadge"
                          style="background:#E65C00; font-size:11px; border-radius:20px;">0 selected</span>
                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" style="font-size:12px; color:rgba(255,255,255,.7);" onclick="selectAllCodes()">All</button>
                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" style="font-size:12px; color:rgba(255,255,255,.5);" onclick="clearAllCodes()">Clear</button>
                </div>
            </div>
            <div class="card-body p-4">
                @if($codes->isEmpty())
                <div class="text-center py-5 text-muted">No active account codes found.</div>
                @else
                @foreach($codes->groupBy('account_category_id') as $catId => $catCodes)
                @php $cat = $catCodes->first()->category @endphp
                <div class="mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-2 pb-1"
                         style="border-bottom:2px solid #E2E8F0;">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-semibold" style="font-size:12px; color:#64748B; text-transform:uppercase; letter-spacing:.5px;">
                                {{ $cat->name }}
                            </span>
                            <span class="badge bg-light text-muted" id="catCount_{{ $catId }}" style="font-size:10px;">
                                0/{{ $catCodes->count() }}
                            </span>
                        </div>
                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none toggle-cat"
                                data-cat="{{ $catId }}"
                                onclick="toggleCat({{ $catId }}, this)"
                                style="font-size:11px; color:#E65C00;">
                            Select all
                        </button>
                    </div>
                    <div class="row g-2">
                        @foreach($catCodes as $code)
                        <div class="col-md-4 col-lg-3">
                            <label class="code-chip d-flex align-items-center gap-2 px-2 py-2"
                                   data-cat="{{ $catId }}"
                                   style="cursor:pointer; border:1px solid #E2E8F0; border-radius:8px; background:#fff; transition:all .15s; font-size:12px;">
                                <input type="checkbox" name="account_codes[]" value="{{ $code->id }}"
                                       class="form-check-input code-cb flex-shrink-0"
                                       data-cat="{{ $catId }}"
                                       onchange="updateCodeCount()"
                                       style="width:14px;height:14px;cursor:pointer;accent-color:#1B2A4A;">
                                <span>
                                    <code style="font-size:10px; background:#F1F5F9; padding:1px 5px; border-radius:4px; color:#475569;">{{ $code->code }}</code>
                                    <span style="color:#334155; display:block; font-size:11px; margin-top:2px; line-height:1.3; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:110px;">{{ $code->name }}</span>
                                </span>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Sticky action bar --}}
<div style="position:fixed; bottom:0; left:0; right:0; background:#fff; border-top:2px solid #E2E8F0; z-index:100; padding:14px 24px;">
    <div class="d-flex align-items-center gap-4 flex-wrap">

        {{-- Mode --}}
        <div class="d-flex align-items-center gap-3">
            <span style="font-size:12px; font-weight:600; color:#64748B;">Mode:</span>
            <label class="d-flex align-items-center gap-1" style="cursor:pointer; font-size:13px; color:#1B2A4A;">
                <input type="radio" name="mode" value="add" checked style="accent-color:#E65C00;">
                Add to existing
            </label>
            <label class="d-flex align-items-center gap-1" style="cursor:pointer; font-size:13px; color:#1B2A4A;">
                <input type="radio" name="mode" value="replace" style="accent-color:#E65C00;">
                Replace all
            </label>
        </div>

        <div style="height:28px; width:1px; background:#E2E8F0;"></div>

        {{-- Summary --}}
        <div style="font-size:13px; color:#475569;">
            <span id="barStations" class="fw-bold" style="color:#E65C00;">0</span> stations ×
            <span id="barCodes" class="fw-bold" style="color:#1B2A4A;">0</span> codes
        </div>

        {{-- Submit --}}
        <button type="submit" id="submitBtn" disabled
                class="btn btn-sm ms-auto"
                style="background:#E65C00; color:#fff; border:none; border-radius:8px; padding:8px 24px; font-weight:600; font-size:13px; opacity:.5;">
            <i class="fas fa-check-circle"></i> Apply
        </button>

        <a href="{{ route('admin.service-stations.index') }}"
           class="btn btn-sm btn-outline-secondary" style="border-radius:8px; font-size:13px;">
            Cancel
        </a>
    </div>
</div>

</form>

@push('scripts')
<script>
function updateStationCount() {
    const n = document.querySelectorAll('.station-cb:checked').length;
    document.getElementById('stationBadge').textContent = n + ' selected';
    document.getElementById('barStations').textContent = n;
    updateSubmit();
}

function updateCodeCount() {
    const all   = document.querySelectorAll('.code-cb');
    const n     = document.querySelectorAll('.code-cb:checked').length;
    document.getElementById('codeBadge').textContent = n + ' selected';
    document.getElementById('barCodes').textContent  = n;

    // Per-category counts
    const cats = {};
    all.forEach(cb => {
        const c = cb.dataset.cat;
        if (!cats[c]) cats[c] = {total:0, checked:0};
        cats[c].total++;
        if (cb.checked) cats[c].checked++;
    });
    Object.entries(cats).forEach(([c, v]) => {
        const el = document.getElementById('catCount_' + c);
        if (el) el.textContent = v.checked + '/' + v.total;
        const btn = document.querySelector('.toggle-cat[data-cat="' + c + '"]');
        if (btn) btn.textContent = (v.checked === v.total) ? 'Clear' : 'Select all';
    });
    updateSubmit();
}

function updateSubmit() {
    const stations = document.querySelectorAll('.station-cb:checked').length;
    const codes    = document.querySelectorAll('.code-cb:checked').length;
    const btn = document.getElementById('submitBtn');
    btn.disabled = !(stations > 0 && codes > 0);
    btn.style.opacity = (stations > 0 && codes > 0) ? '1' : '.5';
}

function selectAllStations() {
    document.querySelectorAll('.station-row:not([style*="display: none"]) .station-cb').forEach(cb => cb.checked = true);
    updateStationCount();
}
function clearAllStations() {
    document.querySelectorAll('.station-cb').forEach(cb => cb.checked = false);
    updateStationCount();
}
function selectAllCodes() {
    document.querySelectorAll('.code-cb').forEach(cb => cb.checked = true);
    updateCodeCount();
}
function clearAllCodes() {
    document.querySelectorAll('.code-cb').forEach(cb => cb.checked = false);
    updateCodeCount();
}

function toggleCat(catId, btn) {
    const cbs = document.querySelectorAll('.code-cb[data-cat="' + catId + '"]');
    const allChecked = Array.from(cbs).every(cb => cb.checked);
    cbs.forEach(cb => cb.checked = !allChecked);
    updateCodeCount();
}

function filterZone(zoneId, btn) {
    document.querySelectorAll('.zone-pill').forEach(p => {
        p.style.background = '#F1F5F9';
        p.style.color = '#475569';
        p.style.borderColor = '#E2E8F0';
    });
    btn.style.background = '#1B2A4A';
    btn.style.color = '#fff';
    btn.style.borderColor = '#1B2A4A';

    const rows = document.querySelectorAll('.station-row');
    rows.forEach(row => {
        const show = zoneId === 'all' || row.dataset.zone == zoneId;
        row.style.display = show ? '' : 'none';
    });
    // Hide/show section headers
    document.querySelectorAll('.zone-section').forEach(sec => {
        const visible = Array.from(sec.querySelectorAll('.station-row')).some(r => r.style.display !== 'none');
        sec.style.display = visible ? '' : 'none';
    });
}

// Highlight checked chips
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('code-cb')) {
        const chip = e.target.closest('.code-chip');
        if (chip) {
            chip.style.borderColor  = e.target.checked ? '#1B2A4A' : '#E2E8F0';
            chip.style.background   = e.target.checked ? '#EFF6FF' : '#fff';
        }
    }
    if (e.target.classList.contains('station-cb')) {
        const row = e.target.closest('.station-row');
        if (row) row.style.background = e.target.checked ? '#FFF7ED' : '';
    }
});

document.addEventListener('DOMContentLoaded', updateCodeCount);
</script>

<style>
.station-row:hover { background: #FFF7ED !important; }
.code-chip:hover { border-color: #1B2A4A !important; background: #F8FAFC !important; }
</style>
@endpush

@endsection
