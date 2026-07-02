@extends('layouts.app')
@section('title', 'Approval History')
@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('approvals.index') }}"
       class="text-muted text-decoration-none small">← Approvals</a>
    <span class="text-muted">/</span>
    <span class="small">
        {{ $budgetVersion->department->name }} —
        v{{ $budgetVersion->version_number }} History
    </span>
</div>

{{-- ── Header card ── --}}
<div class="chart-card mb-4"
     style="border-left:4px solid {{
        match($budgetVersion->status) {
            'approved'     => '#10B981',
            'rejected'     => '#F43F5E',
            'submitted'    => '#3B82F6',
            'under_review' => '#F59E0B',
            default        => '#64748B'
        }
     }}">
    <div class="row align-items-center">
        <div class="col">
            <div style="font-size:18px;font-weight:700;color:var(--navy)">
                {{ $budgetVersion->department->name }}
            </div>
            <div style="font-size:13px;color:var(--slate);margin-top:2px">
                {{ $budgetVersion->period->name }}
                &middot; Version {{ $budgetVersion->version_number }}
                &middot; Submitted by {{ $budgetVersion->submittedBy?->name ?? '—' }}
                @if($budgetVersion->submitted_at)
                    on {{ $budgetVersion->submitted_at->format('d M Y \a\t H:i') }}
                @endif
            </div>
            @if($budgetVersion->submission_notes)
            <div style="font-size:12px;color:var(--slate);margin-top:6px;
                        background:#F8FAFC;border-radius:6px;padding:8px 12px;
                        border-left:3px solid var(--border)">
                "{{ $budgetVersion->submission_notes }}"
            </div>
            @endif
        </div>
        <div class="col-auto">
            <span style="padding:6px 16px;border-radius:20px;font-size:13px;font-weight:600;
                         background:{{
                            match($budgetVersion->status) {
                                'approved'     => '#D1FAE5',
                                'rejected'     => '#FEE2E2',
                                'submitted'    => '#DBEAFE',
                                'under_review' => '#FEF3C7',
                                default        => '#F1F5F9'
                            }
                         }};
                         color:{{
                            match($budgetVersion->status) {
                                'approved'     => '#065F46',
                                'rejected'     => '#991B1B',
                                'submitted'    => '#1E40AF',
                                'under_review' => '#92400E',
                                default        => '#475569'
                            }
                         }}">
                {{ ucfirst(str_replace('_',' ', $budgetVersion->status)) }}
            </span>
        </div>
    </div>
</div>

@php
    $lineItems = $budgetVersion->lineItems ?? collect();

    $grandTotal = $lineItems->sum('total_amount')
                + $lineItems->sum(fn($i) => $i->approvedSupplementaryTotal());
    $grandQ1 = $lineItems->sum('q1_amount');
    $grandQ2 = $lineItems->sum('q2_amount');
    $grandQ3 = $lineItems->sum('q3_amount');
    $grandQ4 = $lineItems->sum('q4_amount');

    // Split by section type
    $revItems  = $lineItems->filter(fn($i) => in_array($i->accountCode->category->budget_type, ['revenue','both']));
    $expItems  = $lineItems->filter(fn($i) => $i->accountCode->category->budget_type === 'expense');
    $cxItems   = $lineItems->filter(fn($i) => $i->accountCode->category->budget_type === 'capital_expenditure');
    $blItems   = $lineItems->filter(fn($i) => in_array($i->accountCode->category->budget_type, ['assets','liabilities']));

    $hasCapex   = $cxItems->isNotEmpty();
    $hasBalance = $blItems->isNotEmpty();

    // Chart data: only IS items
    $isItems     = $revItems->merge($expItems);
    $byCategory  = $isItems->groupBy('accountCode.category.name');
    $catNames    = $byCategory->keys()->toArray();
    $catTotals   = $byCategory->map(fn($i) => $i->sum('total_amount'))->values()->toArray();
@endphp

<div class="row g-4">

{{-- ══════════════════════════════════════
     LEFT — Budget Detail
     ══════════════════════════════════════ --}}
<div class="col-lg-8">

    {{-- Grand total bar --}}
    <div style="background:var(--navy);border-radius:12px;padding:16px 20px;color:#fff;margin-bottom:16px">
        <div style="font-size:11px;color:rgba(255,255,255,.5);text-transform:uppercase;
                    letter-spacing:.8px;margin-bottom:10px">Total Budget</div>
        <div class="row text-center g-0">
            @foreach(['Q1'=>$grandQ1,'Q2'=>$grandQ2,'Q3'=>$grandQ3,'Q4'=>$grandQ4] as $ql => $qv)
            <div class="col border-end" style="border-color:rgba(255,255,255,.1)!important">
                <div style="font-size:10px;color:rgba(255,255,255,.5)">{{ $ql }}</div>
                <div style="font-size:14px;font-weight:700">GHS {{ number_format($qv, 0) }}</div>
            </div>
            @endforeach
            <div class="col">
                <div style="font-size:10px;color:var(--gold)">Full Year</div>
                <div style="font-size:18px;font-weight:700;color:var(--gold)">
                    GHS {{ number_format($grandTotal, 0) }}
                </div>
            </div>
        </div>
    </div>

    {{-- IS Category chart --}}
    @if(count($catNames) > 0)
    <div class="chart-card mb-3">
        <div class="chart-title">Income Statement — Budget by Category</div>
        <div class="row g-0 align-items-center">
            <div class="col-md-5">
                <canvas id="catDonut" height="180"></canvas>
            </div>
            <div class="col-md-7">
                <canvas id="catBar" height="180"></canvas>
            </div>
        </div>
    </div>
    @endif

    {{-- ── 3-Tab Budget Detail ── --}}
    <ul class="nav nav-tabs mb-0" id="histTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#htab-is" type="button">
                <i class="bi bi-bar-chart-line me-1"></i>Income Statement
            </button>
        </li>
        @if($hasCapex)
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#htab-capex" type="button">
                <i class="bi bi-tools me-1"></i>Capital Expenditure
                <span class="badge ms-1" style="background:#FEF3C7;color:#78350F;font-size:10px">
                    {{ $cxItems->groupBy('accountCode.category.name')->count() }}
                </span>
            </button>
        </li>
        @endif
        @if($hasBalance)
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#htab-balance" type="button">
                <i class="bi bi-bank me-1"></i>Assets &amp; Liabilities
                <span class="badge ms-1" style="background:#EDE9FE;color:#4C1D95;font-size:10px">
                    {{ $blItems->groupBy('accountCode.category.name')->count() }}
                </span>
            </button>
        </li>
        @endif
    </ul>

    <div class="tab-content border border-top-0 rounded-bottom mb-4"
         id="histTabsContent" style="max-height:65vh;overflow-y:auto">

        {{-- ── Tab 1: Income Statement ── --}}
        <div class="tab-pane fade show active" id="htab-is" role="tabpanel">
            @php
                $revByCategory = $revItems->groupBy('accountCode.category.name');
                $expByCategory = $expItems->groupBy('accountCode.category.name');
                $revTotal = $revItems->sum('total_amount') + $revItems->sum(fn($i) => $i->approvedSupplementaryTotal());
                $expTotal = $expItems->sum('total_amount') + $expItems->sum(fn($i) => $i->approvedSupplementaryTotal());
                $netTotal = $revTotal - $expTotal;
            @endphp
            @include('approval._history_section_table', [
                'sections' => [
                    ['label' => 'REVENUE INCOME',     'bg' => '#1B2A4A', 'catBg' => '#EFF3F9', 'totalBg' => '#1E3A5F', 'totalLabel' => 'TOTAL REVENUE',   'byCategory' => $revByCategory, 'sectionTotal' => $revTotal, 'textColor' => '#1B2A4A'],
                    ['label' => 'OPERATING EXPENSES',  'bg' => '#7C2D12', 'catBg' => '#FFF7ED', 'totalBg' => '#431407', 'totalLabel' => 'TOTAL EXPENSES',  'byCategory' => $expByCategory, 'sectionTotal' => $expTotal, 'textColor' => '#7C2D12'],
                ],
                'grandTotal' => $grandTotal,
                'netRow' => ['label' => 'NET INCOME / (LOSS)', 'value' => $netTotal, 'color' => $netTotal >= 0 ? '#10B981' : '#F43F5E'],
            ])
        </div>

        {{-- ── Tab 2: Capital Expenditure ── --}}
        @if($hasCapex)
        <div class="tab-pane fade" id="htab-capex" role="tabpanel">
            @php
                $cxByCategory = $cxItems->groupBy('accountCode.category.name');
                $cxTotal = $cxItems->sum('total_amount') + $cxItems->sum(fn($i) => $i->approvedSupplementaryTotal());
            @endphp
            @include('approval._history_section_table', [
                'sections' => [
                    ['label' => 'CAPITAL EXPENDITURE', 'bg' => '#78350F', 'catBg' => '#FEF3C7', 'totalBg' => '#92400E', 'totalLabel' => 'TOTAL CAPITAL EXPENDITURE', 'byCategory' => $cxByCategory, 'sectionTotal' => $cxTotal, 'textColor' => '#78350F'],
                ],
                'grandTotal' => $grandTotal,
                'netRow' => null,
            ])
        </div>
        @endif

        {{-- ── Tab 3: Assets & Liabilities ── --}}
        @if($hasBalance)
        <div class="tab-pane fade" id="htab-balance" role="tabpanel">
            @php
                $blByCategory = $blItems->groupBy('accountCode.category.name');
                $blTotal = $blItems->sum('total_amount') + $blItems->sum(fn($i) => $i->approvedSupplementaryTotal());
            @endphp
            @include('approval._history_section_table', [
                'sections' => [
                    ['label' => 'ASSETS & LIABILITIES', 'bg' => '#4C1D95', 'catBg' => '#EDE9FE', 'totalBg' => '#5B21B6', 'totalLabel' => 'TOTAL ASSETS & LIABILITIES', 'byCategory' => $blByCategory, 'sectionTotal' => $blTotal, 'textColor' => '#4C1D95'],
                ],
                'grandTotal' => $grandTotal,
                'netRow' => null,
            ])
        </div>
        @endif

    </div>

    {{-- Grand total footer --}}
    <div class="chart-card" style="background:var(--navy);color:#fff">
        <div class="row align-items-center">
            <div class="col">
                <div style="font-size:12px;color:rgba(255,255,255,.6)">Grand Total — All Sections</div>
            </div>
            <div class="col-auto">
                <div style="font-size:20px;font-weight:700;color:var(--gold)">
                    GHS {{ number_format($grandTotal, 2) }}
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ══════════════════════════════════════
     RIGHT — Approval Flow
     ══════════════════════════════════════ --}}
<div class="col-lg-4">

    {{-- Approval progress timeline --}}
    <div class="chart-card mb-4">
        <div class="chart-title">Approval Flow</div>

        @foreach($progress as $idx => $step)
        @php
            $isLast   = $idx === count($progress) - 1;
            $decision = $step['decision'];
            $status   = $step['status'];

            $iconBg = match($status) {
                'approved' => '#10B981',
                'rejected' => '#F43F5E',
                'pending'  => '#F59E0B',
                default    => '#E2E8F0',
            };
            $iconColor = in_array($status, ['approved','rejected','pending']) ? '#fff' : '#94A3B8';
            $icon = match($status) {
                'approved' => '✔',
                'rejected' => '✘',
                'pending'  => '●',
                default    => '○',
            };
        @endphp

        <div class="d-flex gap-3" style="position:relative">
            @if(!$isLast)
            <div style="position:absolute;left:15px;top:32px;width:2px;
                        height:calc(100% - 16px);z-index:0;
                        background:{{ $status === 'approved' ? '#10B981' : '#E2E8F0' }}">
            </div>
            @endif

            <div style="width:32px;height:32px;border-radius:50%;flex-shrink:0;
                        background:{{ $iconBg }};color:{{ $iconColor }};
                        display:flex;align-items:center;justify-content:center;
                        font-size:14px;font-weight:700;position:relative;z-index:1">
                {{ $icon }}
            </div>

            <div class="flex-grow-1 pb-4">
                <div style="font-size:13px;font-weight:700;color:var(--navy)">
                    {{ $step['stage']->name }}
                </div>
                <div style="font-size:11px;color:var(--slate)">
                    Role: {{ ucfirst(str_replace('_',' ', $step['stage']->role_name)) }}
                </div>

                @if($decision)
                <div style="background:#F8FAFC;border-radius:8px;padding:10px 12px;
                            margin-top:8px;border-left:3px solid {{ $iconBg }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div style="font-size:12px;font-weight:600;color:{{ $iconBg }}">
                                {{ ucfirst($decision->decision) }}
                            </div>
                            <div style="font-size:11px;color:var(--slate)">
                                by {{ $decision->decidedBy->name }}
                            </div>
                        </div>
                        <div style="font-size:10px;color:var(--slate);text-align:right">
                            {{ $decision->decided_at->format('d M Y') }}<br>
                            {{ $decision->decided_at->format('H:i') }}
                            <div style="color:#94A3B8">{{ $decision->decided_at->diffForHumans() }}</div>
                        </div>
                    </div>

                    @if($decision->comments)
                    <div style="margin-top:8px;padding-top:8px;border-top:1px solid var(--border);
                                font-size:12px;color:var(--slate);font-style:italic">
                        "{{ $decision->comments }}"
                    </div>
                    @endif

                    @if($decision->lineItemApprovals->count())
                    <div style="margin-top:8px;padding-top:8px;border-top:1px solid var(--border)">
                        <div style="font-size:10px;font-weight:600;text-transform:uppercase;
                                    letter-spacing:.5px;color:var(--slate);margin-bottom:6px">
                            Line Item Decisions
                        </div>
                        @foreach($decision->lineItemApprovals as $lia)
                        <div class="d-flex justify-content-between align-items-center"
                             style="font-size:11px;padding:3px 0;border-bottom:1px dashed #F1F5F9">
                            <span style="font-family:monospace;color:var(--navy)">
                                {{ $lia->lineItem->accountCode->code }}
                            </span>
                            <span>{{ $lia->lineItem->accountCode->name }}</span>
                            <span style="padding:1px 6px;border-radius:10px;font-size:10px;font-weight:600;
                                         background:{{ match($lia->status) {
                                             'approved' => '#D1FAE5', 'reduced' => '#FEF3C7',
                                             'rejected' => '#FEE2E2', default   => '#F1F5F9'
                                         } }};
                                         color:{{ match($lia->status) {
                                             'approved' => '#065F46', 'reduced' => '#92400E',
                                             'rejected' => '#991B1B', default   => '#475569'
                                         } }}">
                                {{ ucfirst($lia->status) }}
                                @if($lia->approved_amount)
                                    — GHS {{ number_format($lia->approved_amount, 2) }}
                                @endif
                            </span>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                @elseif($status === 'pending')
                <div style="background:#FEF3C7;border-radius:8px;padding:8px 12px;
                            margin-top:8px;font-size:12px;color:#92400E">
                    ⏳ Awaiting decision
                </div>
                @else
                <div style="background:#F8FAFC;border-radius:8px;padding:8px 12px;
                            margin-top:8px;font-size:12px;color:#94A3B8">
                    Not yet reached
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- Review Timeline stats --}}
    @php
        $decisions     = $budgetVersion->approvalDecisions ?? collect();
        $lastDecision  = $decisions->sortBy('decided_at')->last();
        $timeString    = '—';
        if ($budgetVersion->submitted_at && $lastDecision) {
            $start  = \Carbon\Carbon::parse($budgetVersion->submitted_at);
            $end    = \Carbon\Carbon::parse($lastDecision->decided_at);
            $diff   = $start->diff($end);
            $parts  = [];
            if ($diff->d > 0) $parts[] = $diff->d . ' day' . ($diff->d > 1 ? 's' : '');
            if ($diff->h > 0) $parts[] = $diff->h . ' hour' . ($diff->h > 1 ? 's' : '');
            if ($diff->i > 0) $parts[] = $diff->i . ' min';
            $timeString = implode(', ', $parts) ?: '< 1 min';
        }
    @endphp

    @if($decisions->count())
    <div class="chart-card mb-4">
        <div class="chart-title">Review Timeline</div>
        <div class="row g-3">
            <div class="col-6">
                <div style="font-size:11px;color:var(--slate)">Submitted</div>
                <div style="font-size:13px;font-weight:600;color:var(--navy)">
                    {{ $budgetVersion->submitted_at?->format('d M Y') ?? '—' }}
                </div>
            </div>
            <div class="col-6">
                <div style="font-size:11px;color:var(--slate)">Last Action</div>
                <div style="font-size:13px;font-weight:600;color:var(--navy)">
                    {{ $lastDecision?->decided_at->format('d M Y') ?? '—' }}
                </div>
            </div>
            <div class="col-6">
                <div style="font-size:11px;color:var(--slate)">Total Approvers</div>
                <div style="font-size:13px;font-weight:600;color:var(--navy)">{{ $decisions->count() }}</div>
            </div>
            <div class="col-6">
                <div style="font-size:11px;color:var(--slate)">Time in Review</div>
                <div style="font-size:13px;font-weight:600;color:var(--navy)">{{ $timeString }}</div>
            </div>
        </div>

        @if($decisions->count() > 1)
        <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--border)">
            <div style="font-size:11px;color:var(--slate);margin-bottom:8px">Decision Timeline</div>
            <div style="position:relative;height:6px;background:#E2E8F0;border-radius:3px;margin-bottom:20px">
                @php
                    $tStart = $budgetVersion->submitted_at ?? $decisions->min('decided_at');
                    $tEnd   = $decisions->max('decided_at');
                    $tSpan  = max(1, $tStart->diffInHours($tEnd));
                @endphp
                @foreach($decisions->sortBy('decided_at') as $dec)
                @php $pos = round(($tStart->diffInHours($dec->decided_at) / $tSpan) * 100); @endphp
                <div style="position:absolute;left:{{ $pos }}%;top:-4px;width:14px;height:14px;
                            border-radius:50%;transform:translateX(-50%);border:2px solid #fff;
                            background:{{ $dec->decision === 'approved' ? '#10B981' : '#F43F5E' }}"
                     title="{{ $dec->decidedBy->name }} — {{ $dec->decided_at->format('d M Y H:i') }}"></div>
                <div style="position:absolute;left:{{ $pos }}%;top:16px;font-size:9px;
                            color:var(--slate);transform:translateX(-50%);white-space:nowrap">
                    {{ $dec->stage->name }}
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- All versions --}}
    @php
        $allVersions = \App\Models\BudgetVersion::where('budget_period_id', $budgetVersion->budget_period_id)
            ->where('department_id', $budgetVersion->department_id)
            ->orderBy('version_number')->get();
    @endphp

    @if($allVersions->count() > 1)
    <div class="chart-card">
        <div class="chart-title">All Versions — {{ $budgetVersion->period->name }}</div>
        @foreach($allVersions as $v)
        <div class="d-flex justify-content-between align-items-center py-2
                    {{ !$loop->last ? 'border-bottom' : '' }}"
             style="{{ $v->id === $budgetVersion->id ? 'background:#F0F4FF;border-radius:6px;padding:6px 8px;margin:-2px' : '' }}">
            <div>
                <div style="font-size:12px;font-weight:700;color:var(--navy)">
                    Version {{ $v->version_number }}
                    @if($v->id === $budgetVersion->id)
                    <span style="font-size:10px;background:#DBEAFE;color:var(--navy);
                                 border-radius:10px;padding:1px 6px;margin-left:4px">Current</span>
                    @endif
                </div>
                <div style="font-size:11px;color:var(--slate)">
                    {{ $v->submitted_at?->format('d M Y') ?? 'Not submitted' }}
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span style="padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600;
                             background:{{ match($v->status) {
                                 'approved' => '#D1FAE5', 'rejected' => '#FEE2E2',
                                 'submitted' => '#DBEAFE', 'under_review' => '#FEF3C7',
                                 default => '#F1F5F9'
                             } }};
                             color:{{ match($v->status) {
                                 'approved' => '#065F46', 'rejected' => '#991B1B',
                                 'submitted' => '#1E40AF', 'under_review' => '#92400E',
                                 default => '#475569'
                             } }}">
                    {{ ucfirst(str_replace('_',' ', $v->status)) }}
                </span>
                @if($v->id !== $budgetVersion->id)
                <a href="{{ route('approvals.history', $v) }}" style="font-size:11px;color:var(--navy)">View →</a>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>

</div>

{{-- Charts --}}
@if(count($catNames) > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const COLORS = ['#1B2A4A','#C9A84C','#10B981','#6366F1','#F59E0B',
                '#EC4899','#14B8A6','#8B5CF6','#F97316','#06B6D4'];

new Chart(document.getElementById('catDonut'), {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($catNames) !!},
        datasets: [{ data: {!! json_encode($catTotals) !!}, backgroundColor: COLORS, borderWidth:2, borderColor:'#fff', hoverOffset:6 }]
    },
    options: { cutout:'65%', plugins:{ legend:{ position:'bottom', labels:{ font:{size:10}, padding:8, boxWidth:10 } } } }
});

new Chart(document.getElementById('catBar'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($catNames) !!},
        datasets: [{ label:'Budget (GHS)', data:{!! json_encode($catTotals) !!}, backgroundColor:COLORS, borderRadius:5, borderSkipped:false }]
    },
    options: {
        indexAxis:'y', responsive:true,
        plugins:{ legend:{ display:false } },
        scales: {
            x:{ beginAtZero:true, grid:{color:'#F1F5F9'}, ticks:{font:{size:10}, callback:v=>v>=1000000?(v/1000000).toFixed(1)+'M':v>=1000?(v/1000).toFixed(0)+'K':v} },
            y:{ grid:{display:false}, ticks:{font:{size:10}} }
        }
    }
});
</script>
@endif

@endsection
