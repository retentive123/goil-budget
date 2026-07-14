@extends('layouts.app')
@section('title', 'Zones')
@section('content')

<div class="px-3 px-lg-4">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0" style="color: #1B2A4A;">
                <i class="fas fa-map-marked-alt" style="color: #E65C00;"></i> Zones
            </h5>
            <p class="text-muted small mb-0">
                Manage operational zones and their service station groupings
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.zones.create') }}" class="btn btn-sm" style="background: #E65C00; color: #fff; border-radius: 8px; border: none;">
                <i class="fas fa-plus-circle"></i> New Zone
            </a>
        </div>
    </div>

    {{-- Stats Row --}}
    @php
        $totalZones    = $zones->count();
        $activeZones   = $zones->where('is_active', true)->count();
        $inactiveZones = $zones->where('is_active', false)->count();
        $totalStations = $zones->sum('service_stations_count');
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-card text-center">
                <div class="stat-accent" style="background: #1B2A4A;"></div>
                <div class="stat-label">Total Zones</div>
                <div class="stat-value">{{ $totalZones }}</div>
                <div class="stat-sub">All zones</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card text-center">
                <div class="stat-accent" style="background: #10B981;"></div>
                <div class="stat-label">Active</div>
                <div class="stat-value" style="color: #10B981;">{{ $activeZones }}</div>
                <div class="stat-sub">Active zones</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card text-center">
                <div class="stat-accent" style="background: #F43F5E;"></div>
                <div class="stat-label">Inactive</div>
                <div class="stat-value" style="color: #F43F5E;">{{ $inactiveZones }}</div>
                <div class="stat-sub">Inactive zones</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card text-center">
                <div class="stat-accent" style="background: #E65C00;"></div>
                <div class="stat-label">Service Stations</div>
                <div class="stat-value" style="color: #E65C00;">{{ $totalStations }}</div>
                <div class="stat-sub">Across all zones</div>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show small" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Zone Cards --}}
    @if($zones->isEmpty())
        <div class="text-center py-5 text-muted">
            <div style="font-size: 56px; margin-bottom: 16px; color: #CBD5E1;">
                <i class="fas fa-map-marked-alt"></i>
            </div>
            <div style="font-weight: 600; color: #1B2A4A; font-size: 16px;">No zones found</div>
            <div style="font-size: 13px; color: #64748B; margin-top: 6px;">
                Click <strong>"New Zone"</strong> to create your first zone.
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach($zones as $zone)
            <div class="col-md-4 col-lg-3">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; overflow: hidden; transition: all 0.2s ease;">
                    {{-- Colour top bar --}}
                    <div style="height: 4px; background: {{ $zone->is_active ? '#E65C00' : '#94A3B8' }};"></div>
                    <div class="card-body p-3">
                        {{-- Name + active badge --}}
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                                     style="width:38px;height:38px;background:{{ $zone->is_active ? '#E65C00' : '#94A3B8' }};color:#fff;font-size:14px;font-weight:700;">
                                    {{ strtoupper(substr($zone->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="fw-bold" style="color:#1B2A4A;font-size:14px;line-height:1.2;">{{ $zone->name }}</div>
                                    <code class="px-1 rounded" style="background:#F1F5F9;color:#1B2A4A;font-size:11px;">{{ $zone->code }}</code>
                                </div>
                            </div>
                            <span class="badge flex-shrink-0" style="border-radius:20px;font-size:10px;font-weight:600;background:{{ $zone->is_active ? '#D1FAE5' : '#FEE2E2' }};color:{{ $zone->is_active ? '#065F46' : '#991B1B' }};">
                                {{ $zone->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        {{-- Description --}}
                        @if($zone->description)
                            <p class="text-muted small mb-3" style="font-size:12px;line-height:1.4;">
                                {{ Str::limit($zone->description, 80) }}
                            </p>
                        @else
                            <p class="text-muted small mb-3" style="font-size:12px;font-style:italic;">No description</p>
                        @endif

                        {{-- Counts --}}
                        <div class="d-flex gap-2 mb-3">
                            <div class="flex-fill text-center py-2 rounded" style="background:#F8FAFC;border:1px solid #E2E8F0;">
                                <div class="fw-bold" style="color:#1B2A4A;font-size:16px;">{{ $zone->departments_count ?? 0 }}</div>
                                <div style="font-size:10px;color:#94A3B8;text-transform:uppercase;letter-spacing:.4px;">Depts</div>
                            </div>
                            <div class="flex-fill text-center py-2 rounded" style="background:#F8FAFC;border:1px solid #E2E8F0;">
                                <div class="fw-bold" style="color:#E65C00;font-size:16px;">{{ $zone->service_stations_count ?? 0 }}</div>
                                <div style="font-size:10px;color:#94A3B8;text-transform:uppercase;letter-spacing:.4px;">Stations</div>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.zones.show', $zone) }}"
                               class="btn btn-sm flex-fill"
                               style="background:#1B2A4A;color:#fff;border-radius:6px;font-size:12px;border:none;"
                               title="View Zone">
                                <i class="fas fa-eye me-1"></i>View
                            </a>
                            <a href="{{ route('admin.zones.edit', $zone) }}"
                               class="btn btn-sm btn-outline-secondary"
                               style="border-radius:6px;font-size:12px;"
                               title="Edit Zone">
                                <i class="fas fa-pencil-alt"></i>
                            </a>
                            <form method="POST"
                                  action="{{ route('admin.zones.destroy', $zone) }}"
                                  id="deleteZoneForm-{{ $zone->id }}"
                                  class="d-inline">
                                @csrf @method('DELETE')
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        style="border-radius:6px;font-size:12px;"
                                        title="Delete Zone"
                                        onclick="confirmDeleteZone({{ $zone->id }}, '{{ addslashes($zone->name) }}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif

</div>

@push('scripts')
<script>
function confirmDeleteZone(id, name) {
    Swal.fire({
        title: 'Delete zone?',
        html: `<strong>${name}</strong> will be permanently deleted. This cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then(result => {
        if (result.isConfirmed) document.getElementById(`deleteZoneForm-${id}`).submit();
    });
}
</script>
@endpush

<style>
    .stat-card {
        background: #fff;
        border-radius: 12px;
        padding: 16px 12px;
        border: 1px solid #E2E8F0;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }
    .stat-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        transform: translateY(-1px);
    }
    .stat-card .stat-accent {
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
    }
    .stat-card .stat-label {
        font-size: 11px;
        font-weight: 600;
        color: #94A3B8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    .stat-card .stat-value {
        font-size: 24px;
        font-weight: 700;
        color: #1B2A4A;
    }
    .stat-card .stat-sub {
        font-size: 11px;
        color: #94A3B8;
        margin-top: 2px;
    }
    .card:hover {
        box-shadow: 0 6px 20px rgba(0,0,0,0.08) !important;
        transform: translateY(-2px);
    }
    .btn-outline-secondary:hover { background: #1B2A4A; border-color: #1B2A4A; color: #fff; }
    .btn-outline-danger:hover    { background: #F43F5E; border-color: #F43F5E; color: #fff; }
</style>

@endsection
