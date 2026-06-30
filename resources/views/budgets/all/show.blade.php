@extends('layouts.app')
@section('title', 'Budget — ' . $budgetVersion->department->name)
@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('budgets.index') }}"
       class="text-muted text-decoration-none small">← All Budgets</a>
    <span class="text-muted">/</span>
    <a href="{{ route('budgets.department', $budgetVersion->department) }}"
       class="text-muted text-decoration-none small">
        {{ $budgetVersion->department->name }}
    </a>
    <span class="text-muted">/</span>
    <span class="small">
        v{{ $budgetVersion->version_number }} — {{ $budgetVersion->period->name }}
    </span>
</div>

{{-- Version switcher --}}
@if($allVersions->count() > 1)
<div class="d-flex gap-2 mb-4 flex-wrap">
    @foreach($allVersions as $v)
    <a href="{{ route('budgets.show', $v) }}"
       style="padding:6px 14px;border-radius:8px;font-size:12px;
              font-weight:600;text-decoration:none;
              background:{{ $v->id === $budgetVersion->id ? 'var(--navy)' : '#F1F5F9' }};
              color:{{ $v->id === $budgetVersion->id ? '#fff' : 'var(--slate)' }}">
        v{{ $v->version_number }}
        <span style="opacity:.7;font-size:10px">
            — {{ ucfirst(str_replace('_',' ',$v->status)) }}
        </span>
    </a>
    @endforeach
</div>
@endif

{{-- Header --}}
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
            <div style="font-size:19px;font-weight:700;color:var(--navy)">
                {{ $budgetVersion->department->name }}
            </div>
            <div style="font-size:13px;color:var(--slate);margin-top:4px">
                {{ $budgetVersion->period->name }}
                &middot; Version {{ $budgetVersion->version_number }}
                &middot; Type:
                <strong>{{ ucfirst($budgetVersion->department->budget_type) }}</strong>
                @if($budgetVersion->submittedBy)
                &middot; Submitted by {{ $budgetVersion->submittedBy->name }}
                @if($budgetVersion->submitted_at)
                    on {{ $budgetVersion->submitted_at->format('d M Y H:i') }}
                @endif
                @endif
            </div>
            @if($budgetVersion->submission_notes)
            <div style="font-size:12px;color:var(--slate);margin-top:6px;
                        background:#F8FAFC;border-radius:6px;padding:8px 12px">
                "{{ $budgetVersion->submission_notes }}"
            </div>
            @endif
        </div>
        <div class="col-auto d-flex gap-2 align-items-center flex-wrap">
            <span style="padding:6px 16px;border-radius:20px;font-size:13px;font-weight:700;
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
                {{ ucfirst(str_replace('_',' ',$budgetVersion->status)) }}
            </span>

            @if($canDecide)
            <a href="{{ route('approvals.show', $budgetVersion) }}"
               class="btn btn-sm"
               style="background:#F59E0B;color:#fff;border-radius:8px;padding:6px 14px">
                Review & Decide →
            </a>
            @endif

            <a href="{{ route('approvals.history', $budgetVersion) }}"
               class="btn btn-sm btn-outline-secondary"
               style="border-radius:8px;padding:6px 14px;font-size:12px">
                Full History
            </a>
        </div>
    </div>
</div>

<div class="row g-4">

    {{-- ── Left: Budget Detail ── --}}
    <div class="col-lg-8">

        {{-- Grand totals bar --}}
        @php
            $grandQ1    = $budgetVersion->lineItems->sum('q1_amount');
            $grandQ2    = $budgetVersion->lineItems->sum('q2_amount');
            $grandQ3    = $budgetVersion->lineItems->sum('q3_amount');
            $grandQ4    = $budgetVersion->lineItems->sum('q4_amount');

            $totalSupplementary = $budgetVersion->lineItems->sum(fn($i) => $i->approvedSupplementaryTotal());
            $grandTotal = $grandTotals['total'] - $totalSupplementary;
            $effectiveTotal = $grandTotal + $totalSupplementary;
            $totalActual = $actualsPerItem->sum();
            $utilPct    = $effectiveTotal > 0 ? round(($totalActual/$effectiveTotal)*100,1) : 0;
        @endphp

        <div style="background:var(--navy);border-radius:12px;padding:16px 20px;
                    color:#fff;margin-bottom:16px">
            <div class="row g-0 text-center">
                @foreach(['Q1'=>$grandQ1,'Q2'=>$grandQ2,'Q3'=>$grandQ3,'Q4'=>$grandQ4] as $q=>$val)
                <div class="col border-end" style="border-color:rgba(255,255,255,.1)!important">
                    <div style="font-size:10px;color:rgba(255,255,255,.5)">{{ $q }}</div>
                    <div style="font-size:13px;font-weight:700">
                        GHS {{ number_format($val,0) }}
                    </div>
                </div>
                @endforeach
                <div class="col border-end" style="border-color:rgba(255,255,255,.1)!important">
                    <div style="font-size:10px;color:var(--gold)">Original Budget</div>
                    <div style="font-size:14px;font-weight:700;color:var(--gold)">
                        GHS {{ number_format($grandTotal,0) }}
                    </div>
                    @if($totalSupplementary > 0)
                    <div style="font-size:10px;color:#6EE7B7">
                        +GHS {{ number_format($totalSupplementary,0) }} supp.
                    </div>
                    @endif
                </div>
                <div class="col border-end" style="border-color:rgba(255,255,255,.1)!important">
                    <div style="font-size:10px;color:#6EE7B7">Effective Budget</div>
                    <div style="font-size:16px;font-weight:700;color:#6EE7B7">
                        GHS {{ number_format($effectiveTotal,0) }}
                    </div>
                </div>
                <div class="col">
                    <div style="font-size:10px;color:#6EE7B7">Actual (YTD)</div>
                    <div style="font-size:16px;font-weight:700;color:#6EE7B7">
                        GHS {{ number_format($totalActual,0) }}
                    </div>
                    <div style="font-size:10px;color:rgba(255,255,255,.5)">
                        {{ $utilPct }}% utilised
                    </div>
                </div>
            </div>

            {{-- Utilisation bar --}}
            <div class="progress mt-3" style="height:6px;background:rgba(255,255,255,.15);border-radius:3px">
                <div class="progress-bar"
                     style="width:{{ min($utilPct,100) }}%;
                            background:{{ $utilPct>90?'#F43F5E':($utilPct>70?'#F59E0B':'#6EE7B7') }};
                            border-radius:3px">
                </div>
            </div>
        </div>

        {{-- Charts --}}
        @php
            $catNames   = array_keys($summary);
            $catBudgets = array_map(fn($c) => $c['total'], $summary);
            $catEffective = array_map(function($cat) {
                return collect($cat['items'])->sum(fn($item) => $item->effectiveBudget());
            }, $summary);
            $catActuals = array_map(function($cat) use ($actualsPerItem) {
                return collect($cat['items'])->sum(fn($item) => $actualsPerItem->get($item->id, 0));
            }, $summary);
        @endphp

        @if(count($catNames) > 0)
        <div class="row g-3 mb-4">
            <div class="col-md-5">
                <div class="chart-card h-100">
                    <div class="chart-title">Budget by Category</div>
                    <canvas id="catDonut" height="200"></canvas>
                </div>
            </div>
            <div class="col-md-7">
                <div class="chart-card h-100">
                    <div class="chart-title">Budget vs Actual by Category</div>
                    <canvas id="catBar" height="200"></canvas>
                </div>
            </div>
        </div>
        @endif

        {{-- Line items by category --}}
        @foreach($summary as $catName => $catData)
        @php
            $catActual = collect($catData['items'])
                ->sum(fn($item) => $actualsPerItem->get($item->id, 0));
            $catBudget = $catData['total'];
            $catEffective = collect($catData['items'])->sum(fn($item) => $item->effectiveBudget());
            $catUtil   = $catEffective > 0
                ? round(($catActual/$catEffective)*100,1) : 0;
            $catSupp = collect($catData['items'])->sum(fn($item) => $item->approvedSupplementaryTotal());
        @endphp
        <div class="chart-card mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div style="font-size:12px;font-weight:700;text-transform:uppercase;
                            letter-spacing:.5px;color:var(--navy)">
                    {{ $catName }}
                </div>
                <div class="text-end">
                    <span style="font-size:13px;font-weight:700;color:var(--navy)">
                        GHS {{ number_format($catBudget,2) }}
                        @if($catSupp > 0)
                        <span style="color:#10B981;font-size:11px;">
                            +{{ number_format($catSupp,2) }} supp.
                        </span>
                        @endif
                    </span>
                    <span style="font-size:11px;color:#10B981;margin-left:8px">
                        Actual: GHS {{ number_format($catActual,2) }} ({{ $catUtil }}%)
                    </span>
                </div>
            </div>

            <div class="progress mb-3" style="height:4px">
                <div class="progress-bar"
                     style="width:{{ min($catUtil,100) }}%;
                            background:{{ $catUtil>90?'#F43F5E':($catUtil>70?'#F59E0B':'#10B981') }}">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" style="font-size:12px">
                    <thead style="font-size:10px;text-transform:uppercase;
                                  letter-spacing:.5px;color:var(--slate)">
                        <tr>
                            <th>Code</th>
                            <th>Account Name</th>
                            <th>Type</th>
                            <th class="text-end">Q1</th>
                            <th class="text-end">Q2</th>
                            <th class="text-end">Q3</th>
                            <th class="text-end">Q4</th>
                            <th class="text-end">Original</th>
                            <th class="text-end">Supplementary</th>
                            <th class="text-end">Effective</th>
                            <th class="text-end">Actual</th>
                            <th class="text-end">Variance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($catData['items'] as $item)
                        @php
                            $itemActual   = $actualsPerItem->get($item->id, 0);
                            $itemSupp     = $item->approvedSupplementaryTotal();
                            $itemEffective = $item->effectiveBudget();
                            $itemVariance = $itemActual - $itemEffective;
                        @endphp
                        <tr>
                            <td style="font-family:monospace;font-weight:700;color:var(--navy)">
                                {{ $item->accountCode->code }}
                            </td>
                            <td>{{ $item->accountCode->name }}</td>
                            <td>
                                <span style="padding:1px 6px;border-radius:4px;font-size:10px;
                                             font-weight:600;
                                             background:{{ $item->line_type==='revenue'?'#D1FAE5':'#FEE2E2' }};
                                             color:{{ $item->line_type==='revenue'?'#065F46':'#991B1B' }}">
                                    {{ ucfirst($item->line_type) }}
                                </span>
                            </td>
                            <td class="text-end">{{ number_format($item->q1_amount,2) }}</td>
                            <td class="text-end">{{ number_format($item->q2_amount,2) }}</td>
                            <td class="text-end">{{ number_format($item->q3_amount,2) }}</td>
                            <td class="text-end">{{ number_format($item->q4_amount,2) }}</td>
                            <td class="text-end">{{ number_format($item->total_amount,2) }}</td>
                            <td class="text-end" style="color:{{ $itemSupp > 0 ? '#10B981' : 'inherit' }}">
                                {{ $itemSupp > 0 ? '+'.number_format($itemSupp,2) : '—' }}
                            </td>
                            <td class="text-end fw-semibold" style="color:var(--navy)">
                                {{ number_format($itemEffective,2) }}
                            </td>
                            <td class="text-end" style="color:#10B981">
                                {{ number_format($itemActual,2) }}
                            </td>
                            <td class="text-end fw-semibold"
                                style="color:{{ $itemVariance>0?'#F43F5E':($itemVariance<0?'#10B981':'inherit') }}">
                                {{ $itemVariance>=0?'+':'' }}{{ number_format($itemVariance,2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot style="background:#F8FAFC;font-weight:700;font-size:11px">
                        <tr>
                            <td colspan="7">Category Total</td>
                            <td class="text-end">GHS {{ number_format($catBudget,2) }}</td>
                            <td class="text-end" style="color:{{ $catSupp > 0 ? '#10B981' : 'inherit' }}">
                                {{ $catSupp > 0 ? '+'.number_format($catSupp,2) : '—' }}
                            </td>
                            <td class="text-end">GHS {{ number_format($catEffective,2) }}</td>
                            <td class="text-end" style="color:#10B981">
                                GHS {{ number_format($catActual,2) }}
                            </td>
                            <td class="text-end"
                                style="color:{{ ($catActual-$catEffective)>0?'#F43F5E':'#10B981' }}">
                                {{ ($catActual-$catEffective)>=0?'+':'' }}
                                {{ number_format($catActual-$catEffective,2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endforeach

        {{-- Grand total footer --}}
        <div class="chart-card" style="background:var(--navy);color:#fff">
            <div class="row align-items-center">
                <div class="col">
                    <div style="font-size:12px;color:rgba(255,255,255,.6)">Grand Total</div>
                    @if($totalSupplementary > 0)
                    <div style="font-size:10px;color:#6EE7B7">
                        incl. {{ number_format($totalSupplementary,2) }} supplementary
                    </div>
                    @endif
                </div>
                <div class="col-auto text-end">
                    <div style="font-size:11px;color:rgba(255,255,255,.5)">Budget</div>
                    <div style="font-size:18px;font-weight:700;color:var(--gold)">
                        GHS {{ number_format($grandTotal,2) }}
                    </div>
                </div>
                <div class="col-auto text-end">
                    <div style="font-size:11px;color:rgba(255,255,255,.5)">Effective</div>
                    <div style="font-size:18px;font-weight:700;color:#6EE7B7">
                        GHS {{ number_format($effectiveTotal,2) }}
                    </div>
                </div>
                <div class="col-auto text-end">
                    <div style="font-size:11px;color:rgba(255,255,255,.5)">Actual</div>
                    <div style="font-size:18px;font-weight:700;color:#6EE7B7">
                        GHS {{ number_format($totalActual,2) }}
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Right: Approval + Supplementary ── --}}
    <div class="col-lg-4">

        {{-- Approval progress --}}
        <div class="chart-card mb-4">
            <div class="chart-title">Approval Progress</div>
            @foreach($progress as $idx => $step)
            @php
                $isLast   = $idx === count($progress)-1;
                $iconBg   = match($step['status']) {
                    'approved' => '#10B981',
                    'rejected' => '#F43F5E',
                    'pending'  => '#F59E0B',
                    default    => '#E2E8F0',
                };
                $icon = match($step['status']) {
                    'approved' => '✔',
                    'rejected' => '✘',
                    'pending'  => '●',
                    default    => '○',
                };
            @endphp
            <div class="d-flex gap-3" style="position:relative">
                @if(!$isLast)
                <div style="position:absolute;left:15px;top:32px;width:2px;
                            height:calc(100% - 16px);
                            background:{{ $step['status']==='approved'?'#10B981':'#E2E8F0' }};
                            z-index:0">
                </div>
                @endif
                <div style="width:32px;height:32px;border-radius:50%;
                            background:{{ $iconBg }};color:#fff;
                            display:flex;align-items:center;justify-content:center;
                            font-size:13px;font-weight:700;flex-shrink:0;
                            position:relative;z-index:1">
                    {{ $icon }}
                </div>
                <div class="flex-grow-1 pb-4">
                    <div style="font-size:13px;font-weight:700;color:var(--navy)">
                        {{ $step['stage']->name }}
                        @if(!$step['is_active'])
                        <span style="font-size:10px;color:#94A3B8;font-weight:400"> (inactive)</span>
                        @endif
                    </div>
                    @if($step['decision'])
                    <div style="font-size:11px;color:var(--slate)">
                        {{ $step['decision']->decidedBy->name }}
                        &middot; {{ $step['decision']->decided_at->format('d M Y H:i') }}
                    </div>
                    @if($step['decision']->comments)
                    <div style="font-size:11px;color:var(--slate);font-style:italic;
                                background:#F8FAFC;border-radius:6px;padding:6px 8px;margin-top:4px">
                        "{{ $step['decision']->comments }}"
                    </div>
                    @endif
                    @elseif($step['status']==='pending')
                    <div style="font-size:11px;color:#F59E0B">Awaiting decision</div>
                    @else
                    <div style="font-size:11px;color:#94A3B8">Not yet reached</div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        {{-- Supplementary budgets --}}
        @if($supplementaries->count())
        <div class="chart-card mb-4">
            <div class="chart-title">
                Supplementary Requests
                <span style="background:#F1F5F9;border-radius:20px;padding:1px 8px;
                             font-size:11px;font-weight:400;color:var(--slate)">
                    {{ $supplementaries->count() }}
                </span>
            </div>
            @foreach($supplementaries as $s)
            <div style="padding:8px 0;border-bottom:1px solid var(--border);font-size:12px">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <code style="font-size:11px;color:var(--navy)">{{ $s->accountCode->code }}</code>
                        <span style="color:var(--slate)"> — {{ $s->accountCode->name }}</span>
                    </div>
                    <span style="padding:1px 8px;border-radius:20px;font-size:10px;
                                 font-weight:600;
                                 background:{{
                                    match($s->status) {
                                        'approved'     => '#D1FAE5',
                                        'rejected'     => '#FEE2E2',
                                        'submitted'    => '#DBEAFE',
                                        default        => '#F1F5F9'
                                    }
                                 }};
                                 color:{{
                                    match($s->status) {
                                        'approved'     => '#065F46',
                                        'rejected'     => '#991B1B',
                                        'submitted'    => '#1E40AF',
                                        default        => '#475569'
                                    }
                                 }}">
                        {{ ucfirst($s->status) }}
                    </span>
                </div>
                <div style="color:var(--slate);margin-top:3px">
                    Requested: GHS {{ number_format($s->requested_amount,2) }}
                    @if($s->approved_amount)
                    &nbsp;→&nbsp;
                    <span style="color:#10B981;font-weight:600">
                        Approved: GHS {{ number_format($s->approved_amount,2) }}
                    </span>
                    @endif
                </div>
                <div style="color:#94A3B8;font-size:10px;margin-top:2px">
                    By {{ $s->requestedBy->name }} · {{ $s->submitted_at?->format('d M Y') }}
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Quick actions --}}
        <div class="chart-card">
            <div class="chart-title">Actions</div>
            <div class="d-grid gap-2">
                @if($canDecide)
                <a href="{{ route('approvals.show', $budgetVersion) }}"
                   class="btn btn-sm text-start"
                   style="background:var(--navy);color:#fff;border-radius:8px;padding:10px 14px">
                    ✅ &nbsp; Review & Decide
                </a>
                @endif

                <a href="{{ route('approvals.history', $budgetVersion) }}"
                   class="btn btn-sm text-start"
                   style="background:var(--surface);border:1px solid var(--border);
                          border-radius:8px;padding:10px 14px;font-size:13px;color:var(--navy)">
                    📋 &nbsp; Full Approval History
                </a>

                <a href="{{ route('budgets.department', $budgetVersion->department) }}"
                   class="btn btn-sm text-start"
                   style="background:var(--surface);border:1px solid var(--border);
                          border-radius:8px;padding:10px 14px;font-size:13px;color:var(--navy)">
                    🏢 &nbsp; All Versions — {{ $budgetVersion->department->name }}
                </a>

                <a href="{{ route('reports.department', ['department_id'=>$budgetVersion->department_id,'period_id'=>$budgetVersion->budget_period_id]) }}"
                   class="btn btn-sm text-start"
                   style="background:var(--surface);border:1px solid var(--border);
                          border-radius:8px;padding:10px 14px;font-size:13px;color:var(--navy)">
                    📊 &nbsp; Department Report
                </a>

                <a href="{{ route('actuals.entry', ['period_id'=>$budgetVersion->budget_period_id,'department_id'=>$budgetVersion->department_id,'month'=>now()->month,'year'=>now()->year]) }}"
                   class="btn btn-sm text-start"
                   style="background:var(--surface);border:1px solid var(--border);
                          border-radius:8px;padding:10px 14px;font-size:13px;color:var(--navy)">
                    💰 &nbsp; Record Actuals
                </a>
            </div>
        </div>

    </div>
</div>

{{-- Charts JS --}}
@if(count($catNames) > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const COLORS = ['#1B2A4A','#C9A84C','#10B981','#6366F1','#F59E0B',
                '#EC4899','#14B8A6','#8B5CF6','#F97316','#06B6D4'];

new Chart(document.getElementById('catDonut'), {
    type: 'doughnut',
    data: {
        labels:   {!! json_encode($catNames) !!},
        datasets: [{
            data:            {!! json_encode($catEffective) !!},
            backgroundColor: COLORS,
            borderWidth:     2,
            borderColor:     '#fff',
            hoverOffset:     6,
        }]
    },
    options: {
        cutout: '65%',
        plugins: {
            legend: { position:'bottom', labels:{ font:{size:10}, padding:8, boxWidth:10 } }
        }
    }
});

new Chart(document.getElementById('catBar'), {
    type: 'bar',
    data: {
        labels:   {!! json_encode($catNames) !!},
        datasets: [
            {
                label: 'Effective Budget',
                data:  {!! json_encode($catEffective) !!},
                backgroundColor: '#1B2A4A',
                borderRadius: 4,
                borderSkipped: false,
            },
            {
                label: 'Actual',
                data:  {!! json_encode($catActuals) !!},
                backgroundColor: '#10B981',
                borderRadius: 4,
                borderSkipped: false,
            },
        ]
    },
    options: {
        responsive: true,
        plugins: { legend:{ position:'top', labels:{ font:{size:11}, boxWidth:12 } } },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color:'#F1F5F9' },
                ticks: {
                    font: { size:10 },
                    callback: v => v>=1000000?(v/1000000).toFixed(1)+'M':v>=1000?(v/1000).toFixed(0)+'K':v
                }
            },
            x: { grid:{ display:false }, ticks:{ font:{ size:10 } } }
        }
    }
});
</script>
@endif

@endsection
