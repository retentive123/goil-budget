@extends('layouts.app')
@section('title', $zone->name)

@section('content')
<div class="px-3 px-lg-4">

    {{-- Breadcrumb + Header --}}
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <div class="small text-muted mb-1">
                <a href="{{ route('admin.zones.index') }}" class="text-decoration-none" style="color:#E65C00;">
                    <i class="fas fa-map-marked-alt me-1"></i>Zones
                </a>
                <span class="mx-1">/</span>
                <span>{{ $zone->name }}</span>
            </div>
            <h5 class="fw-bold mb-0" style="color:#1B2A4A;">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle me-2"
                     style="width:36px;height:36px;background:{{ $zone->is_active ? '#E65C00' : '#94A3B8' }};color:#fff;font-size:14px;font-weight:700;vertical-align:middle;">
                    {{ strtoupper(substr($zone->name, 0, 2)) }}
                </div>
                {{ $zone->name }}
                <span class="badge ms-2 align-middle px-2 py-1"
                      style="font-size:11px;border-radius:20px;background:{{ $zone->is_active ? '#D1FAE5' : '#FEE2E2' }};color:{{ $zone->is_active ? '#065F46' : '#991B1B' }};">
                    {{ $zone->is_active ? 'Active' : 'Inactive' }}
                </span>
            </h5>
            @if($zone->description)
                <p class="text-muted small mb-0 mt-1">{{ $zone->description }}</p>
            @endif
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.zones.edit', $zone) }}"
               class="btn btn-sm" style="background:#E65C00;color:#fff;border-radius:8px;border:none;">
                <i class="fas fa-pencil-alt me-1"></i>Edit
            </a>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-card text-center">
                <div class="stat-accent" style="background:#1B2A4A;"></div>
                <div class="stat-label">Code</div>
                <div class="stat-value" style="font-size:20px;">
                    <code style="color:#1B2A4A;">{{ $zone->code }}</code>
                </div>
                <div class="stat-sub">Zone code</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card text-center">
                <div class="stat-accent" style="background:#6366F1;"></div>
                <div class="stat-label">Departments</div>
                <div class="stat-value" style="color:#6366F1;">{{ $zone->departments->count() }}</div>
                <div class="stat-sub">In this zone</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card text-center">
                <div class="stat-accent" style="background:#E65C00;"></div>
                <div class="stat-label">Service Stations</div>
                <div class="stat-value" style="color:#E65C00;">{{ $zone->serviceStations->count() }}</div>
                <div class="stat-sub">In this zone</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card text-center">
                <div class="stat-accent" style="background:#10B981;"></div>
                <div class="stat-label">Status</div>
                <div class="stat-value" style="font-size:16px;color:#10B981;text-transform:capitalize;">
                    {{ $zone->is_active ? 'Active' : 'Inactive' }}
                </div>
                <div class="stat-sub">Zone status</div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- Departments Panel --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;overflow:hidden;">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                    <span class="fw-semibold" style="color:#1B2A4A;font-size:14px;">
                        <i class="fas fa-building me-2" style="color:#6366F1;"></i>Departments
                    </span>
                    <span class="badge" style="background:#EEF2FF;color:#4338CA;font-size:11px;">
                        {{ $zone->departments->count() }}
                    </span>
                </div>
                <div class="card-body p-0" style="max-height:420px;overflow-y:auto;">
                    @if($zone->departments->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-building fa-2x mb-2 d-block" style="color:#CBD5E1;"></i>
                            No departments in this zone
                        </div>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($zone->departments as $dept)
                            <li class="list-group-item px-4 py-3 d-flex align-items-center gap-3"
                                style="border-color:#F1F5F9;">
                                <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                                     style="width:32px;height:32px;background:#EEF2FF;color:#6366F1;font-size:12px;font-weight:700;">
                                    {{ strtoupper(substr($dept->name, 0, 2)) }}
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold small text-truncate" style="color:#1B2A4A;">{{ $dept->name }}</div>
                                    <code class="px-1 rounded" style="background:#F1F5F9;color:#475569;font-size:10px;">{{ $dept->code }}</code>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge" style="background:#DBEAFE;color:#1E40AF;font-size:10px;">
                                        <i class="fas fa-users" style="font-size:9px;"></i> {{ $dept->users_count ?? $dept->users->count() }}
                                    </span>
                                    <a href="{{ route('admin.departments.show', $dept) }}"
                                       class="btn btn-xs btn-outline-secondary"
                                       style="font-size:10px;padding:2px 7px;border-radius:5px;">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        {{-- Service Stations Panel --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;overflow:hidden;">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                    <span class="fw-semibold" style="color:#1B2A4A;font-size:14px;">
                        <i class="fas fa-gas-pump me-2" style="color:#E65C00;"></i>Service Stations
                    </span>
                    <span class="badge" style="background:#FEF3C7;color:#92400E;font-size:11px;">
                        {{ $zone->serviceStations->count() }}
                    </span>
                </div>
                <div class="card-body p-0" style="max-height:420px;overflow-y:auto;">
                    @if($zone->serviceStations->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-gas-pump fa-2x mb-2 d-block" style="color:#CBD5E1;"></i>
                            No service stations in this zone
                        </div>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($zone->serviceStations as $station)
                            <li class="list-group-item px-4 py-3 d-flex align-items-center gap-3"
                                style="border-color:#F1F5F9;">
                                <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                                     style="width:32px;height:32px;background:#FEF3C7;color:#92400E;font-size:12px;font-weight:700;">
                                    {{ strtoupper(substr($station->name, 0, 2)) }}
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold small text-truncate" style="color:#1B2A4A;">{{ $station->name }}</div>
                                    <code class="px-1 rounded" style="background:#F1F5F9;color:#475569;font-size:10px;">{{ $station->code }}</code>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge" style="background:#DBEAFE;color:#1E40AF;font-size:10px;">
                                        <i class="fas fa-users" style="font-size:9px;"></i> {{ $station->users_count ?? $station->users->count() }}
                                    </span>
                                    <a href="{{ route('admin.service-stations.show', $station) }}"
                                       class="btn btn-xs btn-outline-secondary"
                                       style="font-size:10px;padding:2px 7px;border-radius:5px;">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

    </div>

</div>

<style>
.stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 16px 12px;
    border: 1px solid #E2E8F0;
    position: relative;
    overflow: hidden;
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
    letter-spacing: .5px;
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
</style>
@endsection
