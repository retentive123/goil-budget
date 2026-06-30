@extends('layouts.app')
@section('title', 'Budget Approvals')
@section('content')

<div class="row">
    <div class="col-lg-10 col-xl-9">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-0" style="color: #1B2A4A;">
                    <i class="bi bi-check2-circle" style="color: #E65C00;"></i> Budget Approvals
                </h5>
                <p class="text-muted small mb-0">
                    Review and approve department budget submissions
                </p>
            </div>

        </div>

        {{-- Stats Row --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-accent" style="background: #F59E0B;"></div>
                    <div class="stat-label">Pending</div>
                    <div class="stat-value" style="color: #F59E0B;">{{ $pending->count() }}</div>
                    <div class="stat-sub">Awaiting your decision</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-accent" style="background: #10B981;"></div>
                    <div class="stat-label">Approved</div>
                    <div class="stat-value" style="color: #10B981;">{{ $decided->where('status','approved')->count() }}</div>
                    <div class="stat-sub">Previously approved</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-accent" style="background: #F43F5E;"></div>
                    <div class="stat-label">Rejected</div>
                    <div class="stat-value" style="color: #F43F5E;">{{ $decided->where('status','rejected')->count() }}</div>
                    <div class="stat-sub">Previously rejected</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-accent" style="background: #6366F1;"></div>
                    <div class="stat-label">Total Processed</div>
                    <div class="stat-value" style="color: #6366F1;">{{ $decided->count() }}</div>
                    <div class="stat-sub">All time decisions</div>
                </div>
            </div>
        </div>

        {{-- Pending Action Section --}}
        <div class="mb-5">
            <div class="d-flex align-items-center gap-2 mb-3">
                <div style="width: 4px; height: 24px; background: #F59E0B; border-radius: 4px;"></div>
                <h6 class="fw-semibold mb-0" style="color: #1B2A4A; font-size: 14px;">
                    <i class="bi bi-clock" style="color: #F59E0B;"></i>
                    Awaiting Your Decision
                </h6>
                <span class="badge px-3 py-2" style="background: #F59E0B; color: #fff; font-size: 11px; border-radius: 20px;">
                    {{ $pending->count() }}
                </span>
            </div>

            @forelse($pending as $version)
            <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px; overflow: hidden; border-left: 4px solid #F59E0B; transition: all 0.2s;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center justify-content-center rounded-circle"
                                 style="width: 40px; height: 40px; background: rgba(245, 158, 11, 0.1); color: #F59E0B; font-size: 18px;">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>
                            <div>
                                <div class="fw-semibold" style="color: #1B2A4A; font-size: 14px;">
                                    {{ $version->department->name }}
                                </div>
                                <div class="small" style="color: #64748B;">
                                    <span class="fw-medium">{{ $version->period->name }}</span>
                                    &middot; Version {{ $version->version_number }}
                                    &middot; Submitted {{ $version->submitted_at?->diffForHumans() }}
                                    @if($version->submittedBy)
                                    &middot; by {{ $version->submittedBy->name }}
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <span class="badge px-3 py-2" style="background: #FEF3C7; color: #92400E; font-size: 11px; border-radius: 20px;">
                                <i class="bi bi-clock"></i> Pending
                            </span>
                            <a href="{{ route('approvals.show', $version) }}"
                               class="btn btn-sm px-3 py-2"
                               style="background: #E65C00; color: #fff; border-radius: 8px; border: none; font-size: 12px; transition: all 0.2s;">
                                <i class="bi bi-eye"></i> Review
                            </a>
                        </div>
                    </div>

                    {{-- Quick Summary --}}
                    <div class="mt-2 pt-2 border-top" style="border-color: #F1F5F9 !important;">
                        <div class="d-flex gap-3 flex-wrap small" style="color: #64748B;">
                            <span><i class="bi bi-wallet2" style="color: #E65C00;"></i> GHS {{ number_format($version->lineItems()->sum('total_amount'), 0) }}</span>
                            <span><i class="bi bi-list-ul" style="color: #E65C00;"></i> {{ $version->lineItems()->count() }} line items</span>
                            @if($version->submission_notes)
                            <span><i class="bi bi-chat-dots" style="color: #E65C00;"></i> {{ Str::limit($version->submission_notes, 50) }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-5" style="background: #F8FAFC; border-radius: 12px; border: 2px dashed #E2E8F0;">
                <div style="font-size: 48px; margin-bottom: 12px; color: #10B981;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div style="font-weight: 600; color: #1B2A4A; font-size: 16px;">All caught up!</div>
                <div style="font-size: 13px; color: #64748B;">No budgets awaiting your approval.</div>
            </div>
            @endforelse
        </div>

        {{-- Previously Decided Section --}}
        <div>
            <div class="d-flex align-items-center gap-2 mb-3">
                <div style="width: 4px; height: 24px; background: #64748B; border-radius: 4px;"></div>
                <h6 class="fw-semibold mb-0" style="color: #1B2A4A; font-size: 14px;">
                    <i class="bi bi-check2-all" style="color: #64748B;"></i>
                    Previously Decided
                </h6>
                <span class="badge px-3 py-2" style="background: #64748B; color: #fff; font-size: 11px; border-radius: 20px;">
                    {{ $decided->count() }}
                </span>
            </div>

            @forelse($decided as $version)
            <div class="card border-0 shadow-sm mb-2" style="border-radius: 10px; background: #F8FAFC; border-left: 4px solid {{
                match($version->status) {
                    'approved' => '#10B981',
                    'rejected' => '#F43F5E',
                    default => '#94A3B8'
                }
            }};">
                <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center justify-content-center rounded-circle"
                                 style="width: 32px; height: 32px; background: rgba(100, 116, 139, 0.08); font-size: 14px; color: #64748B;">
                                <i class="bi bi-file-earmark-check"></i>
                            </div>
                            <div>
                                <div class="fw-semibold small" style="color: #1B2A4A;">
                                    {{ $version->department->name }}
                                </div>
                                <div class="small" style="color: #94A3B8; font-size: 11px;">
                                    {{ $version->period->name }} &middot; v{{ $version->version_number }}
                                    @if($version->submitted_at)
                                    &middot; {{ $version->submitted_at->format('d M Y') }}
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <span class="badge px-3 py-1" style="border-radius: 20px; font-size: 11px; background: {{
                                match($version->status) {
                                    'approved' => '#D1FAE5',
                                    'rejected' => '#FEE2E2',
                                    default => '#F1F5F9'
                                }
                            }}; color: {{
                                match($version->status) {
                                    'approved' => '#065F46',
                                    'rejected' => '#991B1B',
                                    default => '#475569'
                                }
                            }};">
                                <i class="bi bi-{{
                                    match($version->status) {
                                        'approved' => 'check-circle',
                                        'rejected' => 'x-circle',
                                        default => 'circle'
                                    }
                                }}"></i>
                                {{ ucfirst(str_replace('_', ' ', $version->status)) }}
                            </span>
                            <a href="{{ route('approvals.history', $version) }}"
                               class="btn btn-sm btn-outline-secondary"
                               style="border-radius: 6px; font-size: 11px; padding: 3px 10px;">
                                <i class="bi bi-clock-history"></i> History
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-4" style="background: #F8FAFC; border-radius: 12px; border: 2px dashed #E2E8F0;">
                <div style="font-size: 13px; color: #64748B;">
                    <i class="bi bi-inbox"></i> No previous decisions.
                </div>
            </div>
            @endforelse
        </div>

    </div>
</div>

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
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        transform: translateY(-1px);
    }

    .stat-card .stat-accent {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        border-radius: 0 0 4px 4px;
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

    .card {
        transition: all 0.2s ease;
    }

    .card:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08) !important;
    }
</style>
@endsection
