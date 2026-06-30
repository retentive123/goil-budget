@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')

{{-- ── Period selector ── --}}
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
    <div>
        <h5 class="fw-bold mb-0">Dashboard</h5>
        <p class="text-muted small mb-0">
            Welcome back, <strong>{{ Auth::user()->name }}</strong>
        </p>
    </div>

    <form method="GET" action="{{ route('dashboard') }}"
          class="d-flex gap-2 align-items-center flex-wrap">

        {{-- Period picker --}}
        <div>
            <select name="period_id" class="form-select form-select-sm"
                    style="min-width:160px"
                    onchange="this.form.querySelector('[name=year]').value='';this.form.submit()">
                <option value="">All Periods</option>
                @foreach($periods as $p)
                <option value="{{ $p->id }}"
                    {{ ($selectedPeriodId == $p->id) ? 'selected' : '' }}>
                    {{ $p->name }}
                    @if($p->status === 'open')
                        (Active)
                    @endif
                </option>
                @endforeach
            </select>
        </div>

        <span class="text-muted small">or</span>

        {{-- Year picker --}}
        <div>
            <select name="year" class="form-select form-select-sm"
                    style="min-width:100px"
                    onchange="this.form.querySelector('[name=period_id]').value='';this.form.submit()">
                <option value="">Year</option>
                @foreach($years as $yr)
                <option value="{{ $yr }}"
                    {{ ($selectedYear == $yr) ? 'selected' : '' }}>
                    {{ $yr }}
                </option>
                @endforeach
            </select>
        </div>

        @if($selectedPeriodId || $selectedYear)
        <a href="{{ route('dashboard') }}"
           class="btn btn-sm btn-outline-secondary"
           style="border-radius:8px">
            Clear
        </a>
        @endif

    </form>
</div>

{{-- ── Active period badge ── --}}
@if($currentPeriod)
<div style="background:var(--navy);border-radius:10px;padding:10px 16px;
            margin-bottom:20px;display:flex;align-items:center;
            justify-content:space-between">
    <div style="color:#fff;font-size:13px">
        <span style="color:var(--gold);font-weight:700">
            {{ $currentPeriod->name }}
        </span>
        &nbsp;·&nbsp;
        {{ $currentPeriod->start_date->format('d M Y') }} —
        {{ $currentPeriod->end_date->format('d M Y') }}
    </div>
    <span style="padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;
                 background:{{
                    match($currentPeriod->status) {
                        'open'     => '#10B981',
                        'closed'   => '#F59E0B',
                        'approved' => '#6366F1',
                        default    => '#64748B'
                    }
                 }};color:#fff">
        {{ ucfirst($currentPeriod->status) }}
    </span>
</div>
@endif


{{-- ════════════════════════════════════════
     FINANCE / ADMIN VIEW
     ════════════════════════════════════════ --}}
@if(isset($periodStats))

{{-- Health bar --}}
@php
    $total = max(1,$periodStats['total']);
    $ap  = round(($periodStats['approved']/$total)*100);
    $su  = round(($periodStats['submitted']/$total)*100);
    $re  = round(($periodStats['rejected']/$total)*100);
    $dr  = round(($periodStats['draft']/$total)*100);
    $ns  = round(($periodStats['not_started']/$total)*100);
    $utilPct = $totalApprovedValue > 0
        ? round(($totalActualValue/$totalApprovedValue)*100,1) : 0;
@endphp

<div class="health-bar-wrap mb-4">
    <div class="d-flex justify-content-between mb-2">
        <div class="health-bar-title">
            Budget Health — {{ $currentPeriod?->name ?? 'All Periods' }}
        </div>
        <div style="font-size:12px;color:var(--gold);font-weight:600">
            {{ $ap }}% approved
            &nbsp;·&nbsp;
            Utilisation: {{ $utilPct }}%
        </div>
    </div>
    <div class="health-segments">
        @if($ap) <div class="health-segment" style="width:{{ $ap }}%;background:#10B981"></div> @endif
        @if($su) <div class="health-segment" style="width:{{ $su }}%;background:var(--gold)"></div> @endif
        @if($re) <div class="health-segment" style="width:{{ $re }}%;background:#F43F5E"></div> @endif
        @if($dr) <div class="health-segment" style="width:{{ $dr }}%;background:#64748B"></div> @endif
        @if($ns) <div class="health-segment" style="width:{{ $ns }}%;background:rgba(255,255,255,.1)"></div> @endif
    </div>
    <div class="health-legend mt-2">
        <div class="health-legend-item"><div class="health-legend-dot" style="background:#10B981"></div>Approved ({{ $periodStats['approved'] }})</div>
        <div class="health-legend-item"><div class="health-legend-dot" style="background:var(--gold)"></div>In Review ({{ $periodStats['submitted'] }})</div>
        <div class="health-legend-item"><div class="health-legend-dot" style="background:#F43F5E"></div>Rejected ({{ $periodStats['rejected'] }})</div>
        <div class="health-legend-item"><div class="health-legend-dot" style="background:#64748B"></div>Draft ({{ $periodStats['draft'] }})</div>
        <div class="health-legend-item"><div class="health-legend-dot" style="background:rgba(255,255,255,.25)"></div>Not started ({{ $periodStats['not_started'] }})</div>
    </div>
</div>

{{-- KPI cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#10B981"></div>
            <div class="stat-label">Approved Budget</div>
            <div class="stat-value" style="font-size:18px">
                GHS {{ number_format($totalApprovedValue,0) }}
            </div>
            <div class="stat-sub">{{ $periodStats['approved'] }} dept(s) approved</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:var(--gold)"></div>
            <div class="stat-label">Actual Spend (YTD)</div>
            <div class="stat-value" style="font-size:18px">
                GHS {{ number_format($totalActualValue,0) }}
            </div>
            <div class="stat-sub">{{ $utilPct }}% of approved budget</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#F43F5E"></div>
            <div class="stat-label">Awaiting My Action</div>
            <div class="stat-value">{{ $pendingApprovals->count() }}</div>
            <div class="stat-sub">budget(s) pending your decision</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#6366F1"></div>
            <div class="stat-label">Pending Virements</div>
            <div class="stat-value">{{ $pendingVirements }}</div>
            <div class="stat-sub">
                <a href="{{ route('virements.pending') }}"
                   style="color:var(--slate)">Review →</a>
            </div>
        </div>
    </div>
</div>

{{-- Charts row 1 --}}
<div class="row g-3 mb-4">

    {{-- Status donut --}}
    <div class="col-md-3">
        <div class="chart-card h-100">
            <div class="chart-title">Submission Status</div>
            <canvas id="statusDonut" height="200"></canvas>
        </div>
    </div>

    {{-- Monthly actuals trend --}}
    <div class="col-md-5">
        <div class="chart-card h-100">
            <div class="chart-title">Monthly Actual Spend (GHS)</div>
            <canvas id="monthlyTrend" height="200"></canvas>
        </div>
    </div>

    {{-- Budget vs Actual donut --}}
    <div class="col-md-4">
        <div class="chart-card h-100">
            <div class="chart-title">Budget Utilisation</div>
            <canvas id="utilisationDonut" height="200"></canvas>
            <div class="text-center mt-2" style="font-size:12px;color:var(--slate)">
                GHS {{ number_format($totalActualValue,0) }} of
                GHS {{ number_format($totalApprovedValue,0) }} used
            </div>
        </div>
    </div>

</div>

{{-- Charts row 2 --}}
<div class="row g-3 mb-4">

    {{-- Dept stacked bar --}}
    <div class="col-md-8">
        <div class="chart-card h-100">
            <div class="chart-title">Budget vs Actual by Department</div>
            <canvas id="deptBudgetBar" height="200"></canvas>
        </div>
    </div>

    {{-- Category breakdown --}}
    <div class="col-md-4">
        <div class="chart-card h-100">
            <div class="chart-title">By Category</div>
            <canvas id="catPie" height="200"></canvas>
        </div>
    </div>

</div>

{{-- YoY trend (if multiple periods) --}}
@if(count($yoySummary) > 1)
<div class="chart-card mb-4">
    <div class="chart-title">Year-over-Year: Budget vs Actual</div>
    <canvas id="yoyLine" height="100"></canvas>
</div>
@endif

{{-- Dept status grid --}}
<div class="row g-3 mb-4">
    <div class="col-md-7">
        <div class="chart-card h-100">
            <div class="chart-title">Department Status</div>
            <div style="max-height:320px;overflow-y:auto">
                @foreach($deptStatuses as $dept)
                <div class="dept-row">
                    <div class="dept-code">{{ $dept['code'] }}</div>
                    <div class="flex-grow-1">
                        <div class="dept-name">{{ $dept['name'] }}</div>
                        <div class="dept-meta">
                            @if($dept['total'] > 0)
                                GHS {{ number_format($dept['total'],0) }}
                                &middot; v{{ $dept['version'] }}
                            @else
                                No budget submitted
                            @endif
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="status-pill pill-{{ $dept['status'] }}">
                            {{ ucfirst(str_replace('_',' ',$dept['status'])) }}
                        </span>
                        @if($dept['version_id'])
                        <a href="{{ route('approvals.show', $dept['version_id']) }}"
                           style="font-size:11px;color:var(--navy)">
                            View →
                        </a>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Pending approvals table --}}
    <div class="col-md-5">
        <div class="chart-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="chart-title mb-0">Awaiting Your Action</div>
                <a href="{{ route('approvals.index') }}"
                   style="font-size:12px;color:var(--navy)">View all →</a>
            </div>

            @forelse($pendingApprovals as $v)
            <div style="padding:10px 0;border-bottom:1px solid var(--border)">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div style="font-size:13px;font-weight:600;color:var(--navy)">
                            {{ $v->department->name }}
                        </div>
                        <div style="font-size:11px;color:var(--slate)">
                            v{{ $v->version_number }}
                            &middot; {{ $v->submitted_at?->diffForHumans() ?? '—' }}
                        </div>
                    </div>
                    <a href="{{ route('approvals.show', $v) }}"
                       class="btn btn-sm"
                       style="background:var(--navy);color:#fff;font-size:11px;
                              border-radius:6px;padding:3px 12px">
                        Review
                    </a>
                </div>
            </div>
            @empty
            <div class="text-muted small text-center py-4">
                No budgets awaiting your approval.
            </div>
            @endforelse
        </div>
    </div>

</div>

{{-- Recent notifications --}}
@if($recentNotifs->count())
<div class="chart-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="chart-title mb-0">Recent Notifications</div>
        <a href="{{ route('notifications.index') }}"
           style="font-size:12px;color:var(--navy)">View all →</a>
    </div>
    @foreach($recentNotifs as $notif)
    <div class="d-flex gap-3 p-2 rounded mb-1
         {{ $notif->isRead() ? '' : 'bg-light' }}"
         style="font-size:12px">
        <span>
            {{ match(true) {
                str_contains($notif->type,'approved') => '✅',
                str_contains($notif->type,'rejected') => '❌',
                str_contains($notif->type,'virement') => '🔄',
                default => '📋'
            } }}
        </span>
        <div class="flex-grow-1">
            <div class="fw-semibold {{ $notif->isRead() ? 'text-muted' : '' }}">
                {{ $notif->subject }}
            </div>
            <div class="text-muted" style="font-size:11px">
                {{ $notif->created_at->diffForHumans() }}
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Dashboard Charts JS --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ✅ FIXED: Correct JavaScript variable declaration
const NAVY = '#1B2A4A';
const GOLD = '#C9A84C';
const EMERALD = '#10B981';
const ROSE = '#F43F5E';
const SLATE = '#64748B';
const PALETTE = ['#1B2A4A','#C9A84C','#10B981','#6366F1','#F59E0B',
                 '#EC4899','#14B8A6','#8B5CF6','#F97316','#06B6D4'];

const fmt = v => v>=1000000?(v/1000000).toFixed(1)+'M':v>=1000?(v/1000).toFixed(0)+'K':v;
const yScale = { beginAtZero: true, grid:{color:'#F1F5F9'},
                 ticks:{font:{size:10},callback:fmt} };

// Status donut
new Chart(document.getElementById('statusDonut'),{
    type:'doughnut',
    data:{
        labels:['Approved','In Review','Rejected','Draft','Not Started'],
        datasets:[{
            data:[{{ $periodStats['approved'] }},{{ $periodStats['submitted'] }},
                  {{ $periodStats['rejected'] }},{{ $periodStats['draft'] }},
                  {{ $periodStats['not_started'] }}],
            backgroundColor:['#10B981','#C9A84C','#F43F5E','#64748B','#E2E8F0'],
            borderWidth:0,hoverOffset:6,
        }]
    },
    options:{cutout:'65%',plugins:{legend:{position:'bottom',labels:{font:{size:10},padding:8,boxWidth:10}}}}
});

// Monthly actuals trend
@php
    $mLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    $mData   = array_map(fn($m) => $monthlyActualsTrend[$m] ?? 0, range(1,12));
@endphp
new Chart(document.getElementById('monthlyTrend'),{
    type:'bar',
    data:{
        labels:{!! json_encode($mLabels) !!},
        datasets:[{
            label:'Actual Spend',
            data:{!! json_encode($mData) !!},
            backgroundColor:{!! json_encode(array_map(fn($v)=>$v>0?'#1B2A4A':'#E2E8F0',$mData)) !!},
            borderRadius:5,borderSkipped:false,
        }]
    },
    options:{
        responsive:true,
        plugins:{legend:{display:false}},
        scales:{y:yScale,x:{grid:{display:false},ticks:{font:{size:10}}}}
    }
});

// Utilisation donut
new Chart(document.getElementById('utilisationDonut'),{
    type:'doughnut',
    data:{
        labels:['Actual Spend','Remaining'],
        datasets:[{
            data:[{{ $totalActualValue }},{{ max(0,$totalApprovedValue-$totalActualValue) }}],
            backgroundColor:['#10B981','#E2E8F0'],
            borderWidth:0,
        }]
    },
    options:{
        cutout:'72%',
        plugins:{
            legend:{position:'bottom',labels:{font:{size:10},padding:8,boxWidth:10}},
            tooltip:{callbacks:{label:ctx=>'GHS '+ctx.parsed.toLocaleString('en-GH',{minimumFractionDigits:0})}}
        }
    }
});

// Dept budget vs actual bar
@if(count($deptBudgets))
new Chart(document.getElementById('deptBudgetBar'),{
    type:'bar',
    data:{
        labels:{!! json_encode(array_column($deptBudgets,'name')) !!},
        datasets:[
            {label:'Budget',data:{!! json_encode(array_column($deptBudgets,'total')) !!},
             backgroundColor:'#1B2A4A',borderRadius:4,borderSkipped:false},
            {label:'Actual',data:{!! json_encode(array_column($deptBudgets,'actual')) !!},
             backgroundColor:'#10B981',borderRadius:4,borderSkipped:false},
        ]
    },
    options:{
        responsive:true,
        plugins:{legend:{position:'top',labels:{font:{size:11},boxWidth:12}}},
        scales:{y:yScale,x:{grid:{display:false},ticks:{font:{size:10}}}}
    }
});
@endif

// Category pie
@if(count($categoryBreakdown))
new Chart(document.getElementById('catPie'),{
    type:'pie',
    data:{
        labels:{!! json_encode(array_column($categoryBreakdown,'name')) !!},
        datasets:[{
            data:{!! json_encode(array_column($categoryBreakdown,'total')) !!},
            backgroundColor:PALETTE,borderWidth:2,borderColor:'#fff',
        }]
    },
    options:{plugins:{legend:{position:'bottom',labels:{font:{size:10},padding:6,boxWidth:10}}}}
});
@endif

// YoY line
@if(count($yoySummary) > 1)
new Chart(document.getElementById('yoyLine'),{
    type:'line',
    data:{
        labels:{!! json_encode($yoySummary->pluck('name')->toArray()) !!},
        datasets:[
            {label:'Budget',
             data:{!! json_encode($yoySummary->pluck('budget')->toArray()) !!},
             borderColor:'#1B2A4A',backgroundColor:'rgba(27,42,74,.08)',
             borderWidth:2.5,pointBackgroundColor:'#C9A84C',pointRadius:5,fill:true,tension:.4},
            {label:'Actual',
             data:{!! json_encode($yoySummary->pluck('actual')->toArray()) !!},
             borderColor:'#10B981',backgroundColor:'rgba(16,185,129,.08)',
             borderWidth:2.5,pointBackgroundColor:'#10B981',pointRadius:5,fill:true,tension:.4},
        ]
    },
    options:{
        responsive:true,
        plugins:{legend:{position:'top',labels:{font:{size:11},boxWidth:12}}},
        scales:{y:yScale,x:{grid:{display:false},ticks:{font:{size:11}}}}
    }
});
@endif
</script>
@endif

{{-- ════════════════════════════════════════
     DEPARTMENT USER VIEW
     ════════════════════════════════════════ --}}
@if(isset($myBudget) || isset($versionHistory))

{{-- Header --}}
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h5 class="fw-bold mb-0">{{ Auth::user()->department?->name }}</h5>
        <p class="text-muted small mb-0">
            {{ $currentPeriod?->name ?? 'No active period' }}
        </p>
    </div>
    @if(!$myBudget || $myBudget->status === 'rejected')
        @if($currentPeriod && \App\Models\BudgetVersion::canCreateNew($currentPeriod->id, Auth::user()->department_id))
        <form method="POST" action="{{ route('budget.start') }}">
            @csrf
            <button type="submit" class="btn btn-sm"
                    style="background:var(--navy);color:#fff;border-radius:8px;padding:8px 18px">
                {{ $myBudget ? '+ Start Revision' : '+ Start Budget' }}
            </button>
        </form>
        @endif
    @endif
</div>

{{-- Budget status card --}}
@if($myBudget)
<div class="chart-card mb-4"
     style="border-left:4px solid {{
        match($myBudget->status) {
            'approved'     => '#10B981',
            'rejected'     => '#F43F5E',
            'submitted'    => '#3B82F6',
            'under_review' => '#F59E0B',
            default        => '#64748B'
        }
     }}">
    <div class="row align-items-center">
        <div class="col">
            <div class="d-flex align-items-center gap-2">
                <span style="font-size:22px;font-weight:700;color:var(--navy)">
                    Version {{ $myBudget->version_number }}
                </span>
                <span class="status-pill pill-{{ $myBudget->status }}">
                    {{ ucfirst(str_replace('_',' ',$myBudget->status)) }}
                </span>
            </div>
            @if($myBudget->submission_notes)
            <div style="font-size:12px;color:var(--slate);margin-top:4px">
                "{{ $myBudget->submission_notes }}"
            </div>
            @endif
        </div>
        <div class="col-auto d-flex gap-2">
            @if($myBudget->isEditable())
            <a href="{{ route('budget.show', $myBudget) }}"
               class="btn btn-sm"
               style="background:var(--navy);color:#fff;border-radius:8px">
                Continue Editing
            </a>
            <a href="{{ route('budget.confirm', $myBudget) }}"
               class="btn btn-sm"
               style="background:#10B981;color:#fff;border-radius:8px">
                Submit →
            </a>
            @else
            <a href="{{ route('budget.show', $myBudget) }}"
               class="btn btn-sm btn-outline-secondary"
               style="border-radius:8px">
                View Budget
            </a>
            @endif
        </div>
    </div>
</div>

{{-- Quarterly KPIs --}}
@if(isset($quarterTotals))
<div class="row g-3 mb-4">
    @foreach(['q1'=>'Q1 (Jan-Mar)','q2'=>'Q2 (Apr-Jun)','q3'=>'Q3 (Jul-Sep)','q4'=>'Q4 (Oct-Dec)'] as $k=>$label)
    <div class="col-md-3">
        <div class="quarter-pill">
            <div class="q-label">{{ $label }}</div>
            <div class="q-value">
                GHS {{ number_format($quarterTotals->$k ?? 0, 0) }}
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Charts --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="chart-card">
            <div class="chart-title">Quarterly Budget Distribution</div>
            <canvas id="deptQBar" height="200"></canvas>
        </div>
    </div>
    <div class="col-md-6">
        <div class="chart-card">
            <div class="chart-title">
                Monthly Actuals vs Budget
                @if($currentPeriod) — {{ $currentPeriod->name }} @endif
            </div>
            <canvas id="deptMonthly" height="200"></canvas>
        </div>
    </div>
</div>

{{-- Top items + year trend --}}
<div class="row g-3 mb-4">

    @if(isset($topItems) && $topItems->count())
    <div class="col-md-6">
        <div class="chart-card">
            <div class="chart-title">Top 5 Budget Lines</div>
            <canvas id="topItemsBar" height="200"></canvas>
        </div>
    </div>
    @endif

    @if(isset($deptPeriodSummary) && $deptPeriodSummary->count() > 1)
    <div class="col-md-6">
        <div class="chart-card">
            <div class="chart-title">My Budget History (All Periods)</div>
            <canvas id="deptYoY" height="200"></canvas>
        </div>
    </div>
    @endif

</div>
@endif

{{-- Bottom row --}}
<div class="row g-3">
    <div class="col-md-6">
        <div class="chart-card">
            <div class="chart-title">Version History</div>
            @forelse($versionHistory as $v)
            <div class="dept-row">
                <div class="dept-code" style="font-size:14px;font-weight:700">
                    v{{ $v->version_number }}
                </div>
                <div>
                    <div class="dept-name">Version {{ $v->version_number }}</div>
                    <div class="dept-meta">
                        {{ $v->submitted_at?->format('d M Y') ?? 'Not submitted' }}
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="status-pill pill-{{ $v->status }}">
                        {{ ucfirst(str_replace('_',' ',$v->status)) }}
                    </span>
                    <a href="{{ route('budget.show',$v) }}"
                       style="font-size:11px;color:var(--navy)">View →</a>
                </div>
            </div>
            @empty
            <div class="text-muted small text-center py-3">No versions yet.</div>
            @endforelse
        </div>
    </div>

    <div class="col-md-6">
        <div class="chart-card">
            <div class="chart-title">Quick Actions</div>
            <div class="d-grid gap-2">
                <a href="{{ route('budget.index') }}"
                   class="btn btn-sm text-start"
                   style="background:var(--surface);border:1px solid var(--border);
                          border-radius:8px;padding:10px 14px;font-size:13px;color:var(--navy)">
                    📋 &nbsp; View / Edit My Budget
                </a>
                <a href="{{ route('actuals.index') }}"
                   class="btn btn-sm text-start"
                   style="background:var(--surface);border:1px solid var(--border);
                          border-radius:8px;padding:10px 14px;font-size:13px;color:var(--navy)">
                    💰 &nbsp; Record Actuals
                </a>
                <a href="{{ route('virements.index') }}"
                   class="btn btn-sm text-start"
                   style="background:var(--surface);border:1px solid var(--border);
                          border-radius:8px;padding:10px 14px;font-size:13px;color:var(--navy)">
                    🔄 &nbsp; Virement Requests
                    @if($myVirements)
                    <span class="badge float-end"
                          style="background:var(--rose);color:#fff;font-size:10px">
                        {{ $myVirements }} pending
                    </span>
                    @endif
                </a>
                <a href="{{ route('reports.department') }}"
                   class="btn btn-sm text-start"
                   style="background:var(--surface);border:1px solid var(--border);
                          border-radius:8px;padding:10px 14px;font-size:13px;color:var(--navy)">
                    📊 &nbsp; My Department Report
                </a>
                <a href="{{ route('notifications.index') }}"
                   class="btn btn-sm text-start"
                   style="background:var(--surface);border:1px solid var(--border);
                          border-radius:8px;padding:10px 14px;font-size:13px;color:var(--navy)">
                    🔔 &nbsp; Notifications
                    @if($unreadCount)
                    <span class="badge float-end"
                          style="background:var(--rose);color:#fff;font-size:10px">
                        {{ $unreadCount }} new
                    </span>
                    @endif
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const fmt = v => v>=1000000?(v/1000000).toFixed(1)+'M':v>=1000?(v/1000).toFixed(0)+'K':v;
const yScale = {beginAtZero:true,grid:{color:'#F1F5F9'},ticks:{font:{size:10},callback:fmt}};

// Quarterly bar
new Chart(document.getElementById('deptQBar'),{
    type:'bar',
    data:{
        labels:['Q1','Q2','Q3','Q4'],
        datasets:[{
            data:[{{ $quarterTotals->q1??0 }},{{ $quarterTotals->q2??0 }},
                  {{ $quarterTotals->q3??0 }},{{ $quarterTotals->q4??0 }}],
            backgroundColor:['#1B2A4A','#C9A84C','#10B981','#6366F1'],
            borderRadius:8,borderSkipped:false,
        }]
    },
    options:{responsive:true,plugins:{legend:{display:false}},
             scales:{y:yScale,x:{grid:{display:false}}}}
});

// Monthly actuals
@php
    $deptMonthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    $deptMonthData   = isset($monthlyActuals)
        ? array_map(fn($m) => $monthlyActuals[$m] ?? 0, range(1,12))
        : array_fill(0,12,0);
    $monthlyBudget   = isset($quarterTotals)
        ? ($quarterTotals->q1+$quarterTotals->q2+$quarterTotals->q3+$quarterTotals->q4) / 12
        : 0;
@endphp
new Chart(document.getElementById('deptMonthly'),{
    type:'bar',
    data:{
        labels:{!! json_encode($deptMonthLabels) !!},
        datasets:[
            {label:'Actual',
             data:{!! json_encode($deptMonthData) !!},
             backgroundColor:'#10B981',borderRadius:4,borderSkipped:false},
            {label:'Monthly Budget',
             data:Array(12).fill({{ round($monthlyBudget,2) }}),
             type:'line',borderColor:'#C9A84C',borderWidth:2,
             borderDash:[5,4],pointRadius:0,fill:false},
        ]
    },
    options:{responsive:true,
             plugins:{legend:{position:'top',labels:{font:{size:10},boxWidth:10}}},
             scales:{y:yScale,x:{grid:{display:false},ticks:{font:{size:10}}}}}
});

@if(isset($topItems) && $topItems->count())
new Chart(document.getElementById('topItemsBar'),{
    type:'bar',
    data:{
        labels:{!! json_encode($topItems->map(fn($i)=>$i->accountCode->code)->toArray()) !!},
        datasets:[{
            label:'Budget (GHS)',
            data:{!! json_encode($topItems->map(fn($i)=>$i->total_amount)->toArray()) !!},
            backgroundColor:'#1B2A4A',borderRadius:4,borderSkipped:false,
        }]
    },
    options:{indexAxis:'y',responsive:true,
             plugins:{legend:{display:false}},
             scales:{x:yScale,y:{grid:{display:false},ticks:{font:{size:10}}}}}
});
@endif

@if(isset($deptPeriodSummary) && $deptPeriodSummary->count() > 1)
new Chart(document.getElementById('deptYoY'),{
    type:'line',
    data:{
        labels:{!! json_encode($deptPeriodSummary->pluck('name')->toArray()) !!},
        datasets:[
            {label:'Budget',
             data:{!! json_encode($deptPeriodSummary->pluck('budget')->toArray()) !!},
             borderColor:'#1B2A4A',backgroundColor:'rgba(27,42,74,.08)',
             borderWidth:2.5,pointBackgroundColor:'#C9A84C',
             pointRadius:5,fill:true,tension:.4},
            {label:'Actual',
             data:{!! json_encode($deptPeriodSummary->pluck('actual')->toArray()) !!},
             borderColor:'#10B981',backgroundColor:'rgba(16,185,129,.08)',
             borderWidth:2.5,pointBackgroundColor:'#10B981',
             pointRadius:5,fill:true,tension:.4},
        ]
    },
    options:{responsive:true,
             plugins:{legend:{position:'top',labels:{font:{size:11},boxWidth:12}}},
             scales:{y:yScale,x:{grid:{display:false},ticks:{font:{size:11}}}}}
});
@endif
</script>
@endif
@endif 
@endsection
