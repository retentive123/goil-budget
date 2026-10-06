@extends('layouts.app')
@section('title', 'Reports & Analytics')
@section('content')

<div class="mb-4">
    <h5 class="fw-bold mb-1">Reports & Analytics</h5>
    <p class="text-muted small mb-0">
        @if($currentPeriod)
            Active period: <strong>{{ $currentPeriod->name }}</strong>
            &middot; {{ number_format($kpis['total_value'] ?? 0, 2) }} {{ currency() }} approved
        @endif
    </p>
</div>

{{-- KPI strip --}}
@if($currentPeriod && $kpis)
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#10B981"></div>
            <div class="stat-label">Approved Depts</div>
            <div class="stat-value">{{ $kpis['approved_count'] }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#F59E0B"></div>
            <div class="stat-label">In Review</div>
            <div class="stat-value">{{ $kpis['pending_count'] }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#1B2A4A"></div>
            <div class="stat-label">Total Approved ({{ currency() }})</div>
            <div class="stat-value" style="font-size:18px">
                {{ number_format($kpis['total_value'], 0) }}
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-accent" style="background:#6366F1"></div>
            <div class="stat-label">Virements</div>
            <div class="stat-value">{{ $kpis['virement_count'] }}</div>
        </div>
    </div>
</div>
@endif

{{-- Report cards --}}
<div class="row g-3">

    <div class="col-md-4">
        <div class="chart-card h-100">
            <div class="mb-2" style="font-size: 28px;">
                <i class="bi bi-briefcase"></i>
            </div>
            <div class="chart-title mt-2">Executive Summary</div>
            <p class="small text-muted">
                Full period overview — KPIs, department rankings,
                category breakdown, quarterly distribution.
            </p>
            <a href="{{ route('reports.executive') }}"
               class="btn btn-sm"
               style="background:var(--navy);color:#fff;border-radius:8px">
                Open Report →
            </a>
        </div>
    </div>

    <div class="col-md-4">
        <div class="chart-card h-100">
            <div style="font-size: 28px;">
                <i class="bi bi-building"></i>
            </div>
            <div class="chart-title mt-2">Dept / Station Drill-down</div>
            <p class="small text-muted">
                Select any department or service station. View by category, account code, quarter.
            </p>
            <a href="{{ route('reports.department') }}"
               class="btn btn-sm"
               style="background:var(--navy);color:#fff;border-radius:8px">
                Open Report →
            </a>
        </div>
    </div>

    <div class="col-md-4">
        <div class="chart-card h-100">
            <div style="font-size: 28px;">
                <i class="bi bi-hash"></i>
            </div>
            <div class="chart-title mt-2">Account Code Explorer</div>
            <p class="small text-muted">
                Search any account code. See which departments budgeted
                for it, how much, and its year-on-year trend.
            </p>
            <a href="{{ route('reports.code-explorer') }}"
               class="btn btn-sm"
               style="background:var(--navy);color:#fff;border-radius:8px">
                Open Report →
            </a>
        </div>
    </div>

    <div class="col-md-4">
        <div class="chart-card h-100">
            <div style="font-size: 28px;">
                <i class="bi bi-calendar3"></i>
            </div>
            <div class="chart-title mt-2">Year-over-Year Comparison</div>
            <p class="small text-muted">
                Compare any two budget periods side by side. See growth,
                reduction, and which codes changed the most.
            </p>
            <a href="{{ route('reports.yoy') }}"
               class="btn btn-sm"
               style="background:var(--navy);color:#fff;border-radius:8px">
                Open Report →
            </a>
        </div>
    </div>

    <div class="col-md-4">
        <div class="chart-card h-100">
            <div style="font-size:28px">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div class="chart-title mt-2">Dept / Station Comparison</div>
            <p class="small text-muted">
                Compare up to 5 departments or service stations side by side.
            </p>
            <a href="{{ route('reports.dept-comparison') }}"
               class="btn btn-sm"
               style="background:var(--navy);color:#fff;border-radius:8px">
                Open Report →
            </a>
        </div>
    </div>

    <div class="col-md-4">
        <div class="chart-card h-100">
            <div style="font-size: 28px;">
                <i class="bi bi-graph-down-arrow"></i>
            </div>
            <div class="chart-title mt-2">Variance Analysis</div>
            <p class="small text-muted">
                Budget vs actuals. Filter by overspend/underspend,
                minimum variance %, department or category.
            </p>
            <a href="{{ route('reports.variance') }}"
               class="btn btn-sm"
               style="background:var(--navy);color:#fff;border-radius:8px">
                Open Report →
            </a>
        </div>
    </div>

    <div class="col-md-4">
        <div class="chart-card h-100">
            <div style="font-size: 28px;">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
            <div class="chart-title mt-2">Budget Utilisation</div>
            <p class="small text-muted">
                Track how much of each department's budget has been
                spent. Visual progress bars with status alerts.
            </p>
            <a href="{{ route('reports.utilisation') }}"
               class="btn btn-sm"
               style="background:var(--navy);color:#fff;border-radius:8px">
                Open Report →
            </a>
        </div>
    </div>

    <div class="col-md-4">
        <div class="chart-card h-100">
            <div style="font-size: 28px;">
                <i class="bi bi-arrow-repeat"></i>
            </div>
            <div class="chart-title mt-2">Virement Report</div>
            <p class="small text-muted">
                All budget transfers — approved, pending, rejected.
                Shows from/to accounts and impact on budgets.
            </p>
            <a href="{{ route('reports.virement') }}"
               class="btn btn-sm"
               style="background:var(--navy);color:#fff;border-radius:8px">
                Open Report →
            </a>
        </div>
    </div>

    <div class="col-md-4">
        <div class="chart-card h-100">
            <div style="font-size: 28px;">
                <i class="bi bi-sliders2"></i>
            </div>
            <div class="chart-title mt-2">Flexed Budget</div>
            <p class="small text-muted">
                Adjust the approved budget by an activity level slider
                to produce a flexed budget for comparison.
            </p>
            <a href="{{ route('reports.flexed') }}"
               class="btn btn-sm"
               style="background:var(--navy);color:#fff;border-radius:8px">
                Open Report →
            </a>
        </div>
    </div>

    <div class="col-md-4">
        <div class="chart-card h-100 position-relative" style="border-top:3px solid #7C3AED">
            <div style="font-size:28px;color:#7C3AED">
                <i class="bi bi-pencil-square"></i>
            </div>
            <div class="chart-title mt-2">Revised Budget</div>
            <span class="badge position-absolute top-0 end-0 m-2"
                  style="background:#EDE9FE;color:#5B21B6;font-size:10px">New</span>
            <p class="small text-muted">
                Original Budget vs Mid-Year Revised Budget vs Actuals.
                Shows what changed, how much, and how actuals track against both.
            </p>
            <a href="{{ route('reports.revised') }}"
               class="btn btn-sm"
               style="background:#7C3AED;color:#fff;border-radius:8px">
                Open Report →
            </a>
        </div>
    </div>

    {{-- Subsidiary Budget Report --}}
    <div class="col-md-4">
        <div class="chart-card h-100 position-relative">
            <div style="font-size:14px;font-weight:700;color:#1B2A4A;margin-bottom:8px">
                <i class="bi bi-building me-1" style="color:#C9A84C"></i>
                Subsidiary Report
            </div>
            <span class="badge position-absolute top-0 end-0 m-2"
                  style="background:#FEF3C7;color:#92400E;font-size:10px">New</span>
            <p class="small text-muted">
                Approved budgets submitted by subsidiary entities.
                Filter by category or individual subsidiary.
            </p>
            <a href="{{ route('reports.subsidiary') }}"
               class="btn btn-sm"
               style="background:#C9A84C;color:#1B2A4A;border-radius:8px;font-weight:600">
                Open Report →
            </a>
        </div>
    </div>

    {{-- Ratio Analysis Report --}}
    <div class="col-md-4">
        <div class="chart-card h-100 position-relative" style="border-top:3px solid #E65C00">
            <div style="font-size:28px;color:#E65C00">
                <i class="bi bi-percent"></i>
            </div>
            <div class="chart-title mt-2">Ratio Analysis</div>
            <span class="badge position-absolute top-0 end-0 m-2"
                  style="background:#FFF7ED;color:#9A3412;font-size:10px">New</span>
            <p class="small text-muted">
                Configurable financial ratios — utilisation, efficiency, intensity.
                Compare periods and track trend direction.
            </p>
            <a href="{{ route('reports.ratios') }}"
               class="btn btn-sm"
               style="background:#E65C00;color:#fff;border-radius:8px">
                Open Report →
            </a>
        </div>
    </div>

    {{-- ── New analytical reports ────────────────────────────── --}}
    <div class="col-12 mt-2">
        <h6 class="fw-semibold text-muted mb-3" style="font-size:.8rem;letter-spacing:.05em;text-transform:uppercase">
            Analytical &amp; Performance Reports
        </h6>
    </div>

    <div class="col-md-4">
        <div class="chart-card h-100 d-flex flex-column">
            <h6 class="fw-bold mb-1">Consolidated Group Report</h6>
            <p class="text-muted small flex-grow-1">
                All departments and subsidiaries rolled up into a single group-level
                budget view. Quarterly breakdown with supplementary totals.
            </p>
            <a href="{{ route('reports.consolidated') }}"
               class="btn btn-sm"
               style="background:#E65C00;color:#fff;border-radius:8px">
                Open Report →
            </a>
        </div>
    </div>

    <div class="col-md-4">
        <div class="chart-card h-100 d-flex flex-column">
            <h6 class="fw-bold mb-1">Budget Compliance Report</h6>
            <p class="text-muted small flex-grow-1">
                Submission timeliness, approval duration, and revision count per department.
                Instantly see who submitted on time and who is lagging.
            </p>
            <a href="{{ route('reports.compliance') }}"
               class="btn btn-sm"
               style="background:#E65C00;color:#fff;border-radius:8px">
                Open Report →
            </a>
        </div>
    </div>

    <div class="col-md-4">
        <div class="chart-card h-100 d-flex flex-column">
            <h6 class="fw-bold mb-1">Departmental Ranking</h6>
            <p class="text-muted small flex-grow-1">
                League table ranking departments by budget size, utilisation rate,
                variance percentage, submission speed, and virement activity.
            </p>
            <a href="{{ route('reports.ranking') }}"
               class="btn btn-sm"
               style="background:#E65C00;color:#fff;border-radius:8px">
                Open Report →
            </a>
        </div>
    </div>

    <div class="col-md-4">
        <div class="chart-card h-100 d-flex flex-column">
            <h6 class="fw-bold mb-1">Virement Impact Report</h6>
            <p class="text-muted small flex-grow-1">
                Aggregate effect of all virements per period. Net movement per account code,
                department-level totals, and individual virement details.
            </p>
            <a href="{{ route('reports.virement-impact') }}"
               class="btn btn-sm"
               style="background:#E65C00;color:#fff;border-radius:8px">
                Open Report →
            </a>
        </div>
    </div>

    <div class="col-md-4">
        <div class="chart-card h-100 d-flex flex-column">
            <h6 class="fw-bold mb-1">Service Station Performance</h6>
            <p class="text-muted small flex-grow-1">
                GOIL-specific station comparison. Budget vs actuals per station,
                grouped by zone, with utilisation and virement amounts.
            </p>
            <a href="{{ route('reports.station-performance') }}"
               class="btn btn-sm"
               style="background:#E65C00;color:#fff;border-radius:8px">
                Open Report →
            </a>
        </div>
    </div>

    <div class="col-md-4">
        <div class="chart-card h-100 d-flex flex-column">
            <h6 class="fw-bold mb-1">Approver Activity Report</h6>
            <p class="text-muted small flex-grow-1">
                Review times, approval/rejection counts, and pending workload per approver.
                Identify bottlenecks in the approval pipeline.
            </p>
            <a href="{{ route('reports.approver-activity') }}"
               class="btn btn-sm"
               style="background:#E65C00;color:#fff;border-radius:8px">
                Open Report →
            </a>
        </div>
    </div>

</div>
@endsection
