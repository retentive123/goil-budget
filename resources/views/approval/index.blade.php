@extends('layouts.app')
@section('title', 'Budget Approvals')
@section('content')

{{-- ── Page Header ────────────────────────────────────────────────────────── --}}
<div class="ap-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <div class="ap-eyebrow">Approval Queue</div>
            <h4 class="ap-title">Budget Approvals</h4>
            <p class="ap-subtitle">
                @if($currentPeriod)
                    Reviewing submissions for <strong>{{ $currentPeriod->name }}</strong>
                @else
                    No active budget period
                @endif
            </p>
        </div>
        <div class="d-flex gap-3">
            <div class="ap-stat-pill ap-stat-pending">
                <span class="ap-stat-num">{{ $pending->count() }}</span>
                <span class="ap-stat-lbl">Awaiting</span>
            </div>
            <div class="ap-stat-pill ap-stat-approved">
                <span class="ap-stat-num">{{ $decided->where('status','approved')->count() }}</span>
                <span class="ap-stat-lbl">Approved</span>
            </div>
            <div class="ap-stat-pill ap-stat-rejected">
                <span class="ap-stat-num">{{ $decided->where('status','rejected')->count() }}</span>
                <span class="ap-stat-lbl">Rejected</span>
            </div>
        </div>
    </div>
</div>

{{-- ── Two-column layout ──────────────────────────────────────────────────── --}}
<div class="row g-4">

    {{-- ── LEFT: Pending queue ──────────────────────────────────────────── --}}
    <div class="col-lg-8">

        <div class="d-flex align-items-center gap-2 mb-3">
            <span class="ap-section-dot" style="background:#F59E0B"></span>
            <h6 class="ap-section-title mb-0">Awaiting Your Decision</h6>
            @if($pending->count())
            <span class="ap-count-badge" style="background:#FEF3C7;color:#92400E">{{ $pending->count() }}</span>
            @endif
        </div>

        @forelse($pending as $version)
        @php
            $deptName   = $version->subsidiary?->name ?? $version->department?->name ?? '—';
            $initials   = collect(explode(' ', $deptName))->map(fn($w) => strtoupper($w[0] ?? ''))->take(2)->join('');
            $total      = (float) $version->lineItems->sum('total_amount');
            $effective  = $version->effectiveTotal();
            $hasReduced = $version->approvalDecisions->flatMap->lineItemApprovals->contains('status','reduced');
            $hasIncreased = $version->approvalDecisions->flatMap->lineItemApprovals->contains('status','increased');
        @endphp
        <div class="ap-card mb-3">
            <div class="ap-card-accent"></div>
            <div class="ap-card-body">

                {{-- Top row: avatar + info + amount --}}
                <div class="d-flex align-items-start gap-3">
                    <div class="ap-avatar">{{ $initials }}</div>

                    <div class="flex-grow-1 min-w-0">
                        <div class="ap-dept-name">{{ $deptName }}</div>
                        <div class="ap-meta">
                            <span>{{ $version->period->name }}</span>
                            <span class="ap-dot">·</span>
                            <span>Version {{ $version->version_number }}</span>
                            @if($version->submitted_at)
                            <span class="ap-dot">·</span>
                            <span>{{ $version->submitted_at->diffForHumans() }}</span>
                            @endif
                            @if($version->submittedBy)
                            <span class="ap-dot">·</span>
                            <span>by {{ $version->submittedBy->name }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="ap-amount-block">
                        @if($hasReduced)
                            <div class="ap-amount-original">GHS {{ number_format($total, 0) }}</div>
                            <div class="ap-amount-effective" style="color:#DC2626">GHS {{ number_format($effective, 0) }}</div>
                        @elseif($hasIncreased)
                            <div class="ap-amount-original">GHS {{ number_format($total, 0) }}</div>
                            <div class="ap-amount-effective" style="color:#059669">GHS {{ number_format($effective, 0) }}</div>
                        @else
                            <div class="ap-amount-effective">GHS {{ number_format($effective, 0) }}</div>
                        @endif
                        <div class="ap-amount-lbl">Total Budget</div>
                    </div>
                </div>

                {{-- Tags + notes row --}}
                <div class="d-flex align-items-center gap-2 flex-wrap mt-2">
                    <span class="ap-tag">
                        <i class="bi bi-list-ul"></i> {{ $version->lineItems->count() }} line items
                    </span>
                    @if($hasReduced)
                    <span class="ap-tag ap-tag-warn">
                        <i class="bi bi-arrow-down-circle"></i> Reduced
                    </span>
                    @endif
                    @if($hasIncreased)
                    <span class="ap-tag ap-tag-success">
                        <i class="bi bi-arrow-up-circle"></i> Increased
                    </span>
                    @endif
                    @if($version->submission_notes)
                    <span class="ap-tag" style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        <i class="bi bi-chat-dots"></i> {{ Str::limit($version->submission_notes, 55) }}
                    </span>
                    @endif
                </div>

                {{-- Footer: approval progress + CTA --}}
                <div class="ap-card-footer">
                    {{-- Stage progress --}}
                    @php
                        $decisions = $version->approvalDecisions->sortBy('created_at');
                    @endphp
                    <div class="ap-progress">
                        @forelse($decisions as $dec)
                        @php
                            $ds = $dec->decision ?? 'pending';
                            $dotClass = match($ds) {
                                'approved' => 'ap-prog-dot--green',
                                'rejected' => 'ap-prog-dot--red',
                                'reduced','increased' => 'ap-prog-dot--amber',
                                default    => 'ap-prog-dot--grey',
                            };
                        @endphp
                        <div class="ap-prog-step">
                            <div class="ap-prog-dot {{ $dotClass }}"></div>
                            <div class="ap-prog-lbl">{{ $dec->stage?->name ?? 'Stage' }}</div>
                        </div>
                        @empty
                        <div class="ap-prog-step">
                            <div class="ap-prog-dot ap-prog-dot--pulse"></div>
                            <div class="ap-prog-lbl">Stage 1</div>
                        </div>
                        @endforelse
                        <div class="ap-prog-step">
                            <div class="ap-prog-dot ap-prog-dot--you"></div>
                            <div class="ap-prog-lbl">Your Stage</div>
                        </div>
                    </div>

                    <a href="{{ route('approvals.show', $version) }}" class="ap-review-btn">
                        <i class="bi bi-eye me-1"></i> Review Budget
                    </a>
                </div>

            </div>
        </div>
        @empty
        <div class="ap-empty">
            <div class="ap-empty-icon"><i class="fas fa-check-circle"></i></div>
            <div class="ap-empty-title">All caught up!</div>
            <div class="ap-empty-sub">No budgets are currently awaiting your approval.</div>
        </div>
        @endforelse
    </div>

    {{-- ── RIGHT: History sidebar ────────────────────────────────────────── --}}
    <div class="col-lg-4">
        <div class="ap-sidebar">
            <div class="ap-sidebar-header">
                <span class="ap-section-dot" style="background:#64748B"></span>
                <h6 class="ap-section-title mb-0">Previous Decisions</h6>
                <span class="ap-count-badge ms-auto" style="background:#F1F5F9;color:#475569">{{ $decided->count() }}</span>
            </div>

            <div class="ap-history-list">
                @forelse($decided->sortByDesc('updated_at') as $version)
                @php
                    $dName2   = $version->subsidiary?->name ?? $version->department?->name ?? '—';
                    $init2    = collect(explode(' ', $dName2))->map(fn($w) => strtoupper($w[0] ?? ''))->take(2)->join('');
                    $statusColor = match($version->status) {
                        'approved'      => '#10B981',
                        'rejected'      => '#F43F5E',
                        'under_review'  => '#F59E0B',
                        default         => '#94A3B8',
                    };
                    $statusIcon = match($version->status) {
                        'approved'      => 'check-circle-fill',
                        'rejected'      => 'x-circle-fill',
                        'under_review'  => 'hourglass-split',
                        default         => 'circle',
                    };
                @endphp
                <div class="ap-history-item">
                    <div class="ap-history-avatar" style="background:{{ $statusColor }}1a;color:{{ $statusColor }}">
                        {{ $init2 }}
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="ap-history-name">{{ $dName2 }}</div>
                        <div class="ap-history-meta">
                            {{ $version->period->name }} &middot; v{{ $version->version_number }}
                        </div>
                    </div>
                    <div class="d-flex flex-column align-items-end gap-1">
                        <i class="bi bi-{{ $statusIcon }}" style="color:{{ $statusColor }};font-size:14px"></i>
                        <a href="{{ route('approvals.history', $version) }}"
                           class="ap-history-link">History</a>
                    </div>
                </div>
                @empty
                <div class="ap-empty" style="padding:24px 0">
                    <div style="font-size:13px;color:#94A3B8;text-align:center">
                        <i class="bi bi-inbox"></i> No previous decisions.
                    </div>
                </div>
                @endforelse
            </div>
        </div>
    </div>

</div>

<style>
/* ── Typography & base ─────────────────────────────────────────────────── */
.ap-eyebrow {
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 1px; color: #E65C00; margin-bottom: 2px;
}
.ap-title {
    font-size: 22px; font-weight: 700; color: #1B2A4A; margin: 0 0 2px;
}
.ap-subtitle {
    font-size: 13px; color: #64748B; margin: 0;
}

/* ── Header stat pills ─────────────────────────────────────────────────── */
.ap-stat-pill {
    display: flex; flex-direction: column; align-items: center;
    padding: 8px 18px; border-radius: 12px; min-width: 72px;
}
.ap-stat-pending  { background: #FEF3C7; }
.ap-stat-approved { background: #D1FAE5; }
.ap-stat-rejected { background: #FEE2E2; }
.ap-stat-num {
    font-size: 22px; font-weight: 700; line-height: 1;
}
.ap-stat-pending  .ap-stat-num { color: #D97706; }
.ap-stat-approved .ap-stat-num { color: #059669; }
.ap-stat-rejected .ap-stat-num { color: #DC2626; }
.ap-stat-lbl {
    font-size: 10px; font-weight: 600; text-transform: uppercase;
    letter-spacing: .5px; color: #94A3B8; margin-top: 1px;
}

/* ── Section headings ──────────────────────────────────────────────────── */
.ap-section-dot {
    display: inline-block; width: 4px; height: 20px;
    border-radius: 4px; flex-shrink: 0;
}
.ap-section-title {
    font-size: 13px; font-weight: 700; color: #1B2A4A; letter-spacing: .1px;
}
.ap-count-badge {
    font-size: 11px; font-weight: 700; padding: 2px 9px;
    border-radius: 20px;
}

/* ── Pending card ──────────────────────────────────────────────────────── */
.ap-card {
    background: #fff; border-radius: 14px; overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    transition: box-shadow .2s, transform .15s;
    position: relative;
}
.ap-card:hover {
    box-shadow: 0 4px 20px rgba(230,92,0,.13), 0 2px 8px rgba(0,0,0,.06);
    transform: translateY(-1px);
}
.ap-card-accent {
    height: 3px; background: linear-gradient(90deg, #E65C00, #F59E0B);
}
.ap-card-body { padding: 16px 18px 0; }

.ap-avatar {
    width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
    background: linear-gradient(135deg, #1B2A4A, #2d4a80);
    color: #fff; font-size: 13px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    letter-spacing: .5px;
}
.ap-dept-name {
    font-size: 15px; font-weight: 700; color: #1B2A4A;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.ap-meta {
    font-size: 12px; color: #64748B; margin-top: 2px;
}
.ap-dot { margin: 0 4px; color: #CBD5E1; }

.ap-amount-block { text-align: right; flex-shrink: 0; }
.ap-amount-original {
    font-size: 12px; color: #9CA3AF; text-decoration: line-through;
}
.ap-amount-effective {
    font-size: 17px; font-weight: 700; color: #1B2A4A; white-space: nowrap;
}
.ap-amount-lbl {
    font-size: 10px; color: #94A3B8; text-transform: uppercase;
    letter-spacing: .5px; margin-top: 1px;
}

/* ── Tags ──────────────────────────────────────────────────────────────── */
.ap-tag {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 11px; padding: 3px 9px; border-radius: 20px;
    background: #F1F5F9; color: #475569;
}
.ap-tag-warn    { background: #FEF3C7; color: #92400E; }
.ap-tag-success { background: #D1FAE5; color: #065F46; }

/* ── Card footer: progress + CTA ───────────────────────────────────────── */
.ap-card-footer {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 0 14px; margin-top: 12px;
    border-top: 1px solid #F1F5F9; gap: 12px; flex-wrap: wrap;
}
.ap-progress {
    display: flex; align-items: flex-start; gap: 0;
}
.ap-prog-step {
    display: flex; flex-direction: column; align-items: center; gap: 4px;
    position: relative;
}
.ap-prog-step + .ap-prog-step::before {
    content: ''; position: absolute; left: -12px; top: 7px;
    width: 24px; height: 1px; background: #E2E8F0;
}
.ap-prog-step { padding: 0 12px; }
.ap-prog-step:first-child { padding-left: 0; }

.ap-prog-dot {
    width: 14px; height: 14px; border-radius: 50%;
}
.ap-prog-dot--green  { background: #10B981; }
.ap-prog-dot--red    { background: #F43F5E; }
.ap-prog-dot--amber  { background: #F59E0B; }
.ap-prog-dot--grey   { background: #CBD5E1; }
.ap-prog-dot--you    {
    background: #fff; border: 2px solid #E65C00;
    box-shadow: 0 0 0 3px rgba(230,92,0,.2);
    animation: ap-pulse 1.6s ease-in-out infinite;
}
.ap-prog-dot--pulse  {
    background: #E65C00;
    animation: ap-pulse 1.6s ease-in-out infinite;
}
@keyframes ap-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(230,92,0,.35); }
    50%       { box-shadow: 0 0 0 5px rgba(230,92,0,0); }
}
.ap-prog-lbl {
    font-size: 10px; color: #94A3B8; white-space: nowrap;
}

.ap-review-btn {
    display: inline-flex; align-items: center;
    background: #E65C00; color: #fff; text-decoration: none;
    font-size: 12px; font-weight: 600; padding: 7px 18px;
    border-radius: 8px; transition: background .15s, transform .1s;
    white-space: nowrap;
}
.ap-review-btn:hover {
    background: #c94e00; color: #fff; transform: scale(1.02);
}

/* ── Empty state ───────────────────────────────────────────────────────── */
.ap-empty {
    text-align: center; padding: 48px 24px;
    background: #F8FAFC; border-radius: 14px;
    border: 2px dashed #E2E8F0;
}
.ap-empty-icon { font-size: 42px; color: #10B981; margin-bottom: 10px; }
.ap-empty-title { font-size: 15px; font-weight: 700; color: #1B2A4A; }
.ap-empty-sub { font-size: 13px; color: #94A3B8; margin-top: 4px; }

/* ── Sidebar ───────────────────────────────────────────────────────────── */
.ap-sidebar {
    background: #fff; border-radius: 14px;
    border: 1px solid #E2E8F0; overflow: hidden;
    position: sticky; top: 80px;
}
.ap-sidebar-header {
    display: flex; align-items: center; gap: 8px;
    padding: 14px 16px; border-bottom: 1px solid #F1F5F9;
    background: #F8FAFC;
}
.ap-history-list {
    max-height: calc(100vh - 260px); overflow-y: auto;
}
.ap-history-item {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 16px;
    border-bottom: 1px solid #F8FAFC;
    transition: background .15s;
}
.ap-history-item:last-child { border-bottom: none; }
.ap-history-item:hover { background: #F8FAFC; }

.ap-history-avatar {
    width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
    font-size: 11px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    letter-spacing: .3px;
}
.ap-history-name {
    font-size: 13px; font-weight: 600; color: #1B2A4A;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.ap-history-meta {
    font-size: 11px; color: #94A3B8; margin-top: 1px;
}
.ap-history-link {
    font-size: 11px; color: #E65C00; text-decoration: none;
    font-weight: 600;
}
.ap-history-link:hover { text-decoration: underline; }
</style>

@endsection
