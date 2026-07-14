@extends('layouts.app')
@section('title', $station->name)

@section('content')
<div class="px-3 px-lg-4">

    {{-- Breadcrumb + Header --}}
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <div class="small text-muted mb-1">
                <a href="{{ route('admin.service-stations.index') }}" class="text-decoration-none" style="color:#E65C00;">
                    <i class="fas fa-gas-pump me-1"></i>Service Stations
                </a>
                <span class="mx-1">/</span>
                <span>{{ $station->name }}</span>
            </div>
            <h5 class="fw-bold mb-0" style="color:#1B2A4A;">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle me-2"
                     style="width:36px;height:36px;background:{{ $station->is_active ? '#E65C00' : '#94A3B8' }};color:#fff;font-size:14px;font-weight:700;vertical-align:middle;">
                    {{ strtoupper(substr($station->name, 0, 2)) }}
                </div>
                {{ $station->name }}
                <span class="badge ms-2 align-middle px-2 py-1"
                      style="font-size:11px;border-radius:20px;background:{{ $station->is_active ? '#D1FAE5' : '#FEE2E2' }};color:{{ $station->is_active ? '#065F46' : '#991B1B' }};">
                    {{ $station->is_active ? 'Active' : 'Inactive' }}
                </span>
                @if($station->zone)
                    <span class="badge ms-1 align-middle px-2 py-1"
                          style="font-size:11px;border-radius:20px;background:#FEF3C7;color:#92400E;">
                        <i class="fas fa-map-marked-alt me-1" style="font-size:9px;"></i>{{ $station->zone->name }}
                    </span>
                @endif
            </h5>
            @if($station->description)
                <p class="text-muted small mb-0 mt-1">{{ $station->description }}</p>
            @endif
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.service-stations.account-codes', $station) }}"
               class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
                <i class="fas fa-hashtag me-1"></i>Account Codes
            </a>
            <a href="{{ route('admin.service-stations.edit', $station) }}"
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
                    <code style="color:#1B2A4A;">{{ $station->code }}</code>
                </div>
                <div class="stat-sub">Station code</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card text-center">
                <div class="stat-accent" style="background:#6366F1;"></div>
                <div class="stat-label">Users</div>
                <div class="stat-value" style="color:#6366F1;">{{ $station->users->count() }}</div>
                <div class="stat-sub">Assigned users</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card text-center">
                <div class="stat-accent" style="background:#F59E0B;"></div>
                <div class="stat-label">Account Codes</div>
                <div class="stat-value" style="color:#F59E0B;">{{ $station->accountCodes->count() }}</div>
                <div class="stat-sub">Linked codes</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card text-center">
                <div class="stat-accent" style="background:#10B981;"></div>
                <div class="stat-label">Budget Type</div>
                <div class="stat-value" style="font-size:16px;color:#10B981;text-transform:capitalize;">
                    {{ $station->budget_type ?? '—' }}
                </div>
                <div class="stat-sub">Classification</div>
            </div>
        </div>
    </div>

    {{-- Zone info bar --}}
    @if($station->zone)
    <div class="alert border-0 mb-4 d-flex align-items-center gap-3"
         style="background:#FEF9EC;border-left:4px solid #E65C00 !important;border-radius:8px;">
        <i class="fas fa-map-marked-alt fa-lg" style="color:#E65C00;"></i>
        <div>
            <span class="fw-semibold" style="color:#1B2A4A;">Zone:</span>
            <a href="{{ route('admin.zones.show', $station->zone) }}" class="text-decoration-none ms-1" style="color:#E65C00;">
                {{ $station->zone->name }}
            </a>
            <code class="ms-1 px-1 rounded" style="background:#F1F5F9;color:#475569;font-size:11px;">{{ $station->zone->code }}</code>
        </div>
    </div>
    @endif

    <div class="row g-4">

        {{-- Users --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;overflow:hidden;">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                    <span class="fw-semibold" style="color:#1B2A4A;font-size:14px;">
                        <i class="fas fa-users me-2" style="color:#6366F1;"></i>Users
                    </span>
                    <span class="badge" style="background:#EEF2FF;color:#4338CA;font-size:11px;">
                        {{ $station->users->count() }}
                    </span>
                </div>
                <div class="card-body p-0">
                    @if($station->users->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-user-slash fa-2x mb-2 d-block" style="color:#CBD5E1;"></i>
                            No users assigned to this station
                        </div>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($station->users as $user)
                            <li class="list-group-item px-4 py-3 d-flex align-items-center gap-3"
                                style="border-color:#F1F5F9;">
                                <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                                     style="width:32px;height:32px;background:#EEF2FF;color:#6366F1;font-size:12px;font-weight:700;">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold small text-truncate" style="color:#1B2A4A;">{{ $user->name }}</div>
                                    <div class="text-muted" style="font-size:11px;">{{ $user->email }}</div>
                                </div>
                                @if($user->roles->isNotEmpty())
                                    <span class="badge" style="background:#F0FDF4;color:#166534;font-size:10px;white-space:nowrap;">
                                        {{ $user->roles->first()->name }}
                                    </span>
                                @endif
                            </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        {{-- Account Codes --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;overflow:hidden;">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                    <span class="fw-semibold" style="color:#1B2A4A;font-size:14px;">
                        <i class="fas fa-hashtag me-2" style="color:#F59E0B;"></i>Account Codes
                    </span>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge" style="background:#FEF3C7;color:#92400E;font-size:11px;">
                            {{ $station->accountCodes->count() }}
                        </span>
                        <a href="{{ route('admin.service-stations.account-codes', $station) }}"
                           class="btn btn-xs btn-outline-secondary" style="font-size:11px;padding:2px 8px;border-radius:6px;">
                            <i class="fas fa-edit me-1"></i>Manage
                        </a>
                    </div>
                </div>
                <div class="card-body p-0" style="max-height:420px;overflow-y:auto;">
                    @if($station->accountCodes->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-hashtag fa-2x mb-2 d-block" style="color:#CBD5E1;"></i>
                            No account codes linked yet
                            <div class="mt-2">
                                <a href="{{ route('admin.service-stations.account-codes', $station) }}"
                                   class="btn btn-sm btn-outline-secondary" style="border-radius:8px;font-size:12px;">
                                    Assign account codes
                                </a>
                            </div>
                        </div>
                    @else
                        @php $grouped = $station->accountCodes->groupBy(fn($c) => $c->category?->name ?? 'Uncategorised'); @endphp
                        @foreach($grouped as $catName => $codes)
                        <div class="px-4 pt-3 pb-1">
                            <div class="small fw-semibold text-uppercase mb-2"
                                 style="color:#94A3B8;letter-spacing:.5px;font-size:10px;">
                                {{ $catName }}
                            </div>
                            @foreach($codes as $code)
                            <div class="d-flex align-items-center gap-2 py-1 border-bottom" style="border-color:#F8FAFC!important;">
                                <code class="px-2 py-1 rounded flex-shrink-0"
                                      style="background:#F1F5F9;color:#1B2A4A;font-size:11px;">
                                    {{ $code->code }}
                                </code>
                                <span class="small text-truncate" style="color:#475569;">{{ $code->name }}</span>
                                @if(!$code->pivot->is_active ?? false)
                                    <span class="badge ms-auto flex-shrink-0"
                                          style="background:#FEE2E2;color:#991B1B;font-size:10px;">inactive</span>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @endforeach
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
