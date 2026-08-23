@extends('layouts.app')
@section('title', 'Revised Budget Report')
@section('content')

<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0">Revised Budget Report</h5>
        <p class="text-muted small mb-0">
            Original Budget vs Revised Budget vs Actuals
            @if($period) — {{ $period->name }} @endif
        </p>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="chart-card mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Period</label>
            <select name="period_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">— Select period —</option>
                @foreach($periods as $p)
                <option value="{{ $p->id }}"
                    {{ request('period_id', $period?->id) == $p->id ? 'selected' : '' }}>
                    {{ $p->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Department</label>
            @include('reports._dept_filter', [
                'filterName' => 'department_id',
                'selectedId' => request('department_id'),
                'allowEmpty' => true,
                'emptyLabel' => 'All Departments',
                'autoSubmit' => true,
            ])
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm w-100"
                    style="background:var(--navy);color:#fff;border-radius:8px">Filter</button>
        </div>
        @if(request()->hasAny(['department_id','period_id']))
        <div class="col-md-1">
            <a href="{{ route('reports.revised') }}" class="btn btn-sm btn-outline-secondary w-100">Clear</a>
        </div>
        @endif
    </div>
</form>

@if(!$period)
<div class="text-center py-5 text-muted">
    <i class="bi bi-calendar3" style="font-size:40px;color:#CBD5E1;display:block;margin-bottom:12px"></i>
    Select a period to view the revised budget report.
</div>

@elseif($revisionCount === 0)
{{-- No revisions yet --}}
<div class="chart-card text-center py-5">
    <i class="bi bi-pencil-square" style="font-size:48px;color:#CBD5E1;display:block;margin-bottom:12px"></i>
    <p class="fw-semibold mb-1" style="color:var(--navy)">No Revised Budgets</p>
    <p class="text-muted small">
        No approved budget revisions exist for <strong>{{ $period->name }}</strong>
        @if($department) / {{ $department->name }} @endif yet.<br>
        Revisions are created from an approved budget's detail page.
    </p>
</div>

@else

{{-- KPI summary strip --}}
@php
    $totalOrig    = collect($data)->flatMap(fn($c) => $c)->sum('orig_total');
    $totalRevised = collect($data)->flatMap(fn($c) => $c)->sum('rev_total');
    $totalActual  = collect($data)->flatMap(fn($c) => $c)->sum('actual');
    $revChange    = $totalRevised - $totalOrig;
    $revChangePct = $totalOrig > 0 ? round(($revChange / $totalOrig) * 100, 1) : 0;
@endphp

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-label">Original Budget</div>
            <div class="stat-value" style="font-size:15px">GHS {{ number_format($totalOrig, 0) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-accent" style="background:#7C3AED"></div>
            <div class="stat-label">Revised Budget</div>
            <div class="stat-value" style="font-size:15px;color:#7C3AED">
                GHS {{ number_format($totalRevised, 0) }}
            </div>
            <div class="small mt-1" style="font-size:11px;color:{{ $revChange >= 0 ? '#10B981' : '#F43F5E' }}">
                {{ $revChange >= 0 ? '+' : '' }}{{ number_format($revChange, 0) }}
                ({{ $revChangePct >= 0 ? '+' : '' }}{{ $revChangePct }}%)
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-accent" style="background:#10B981"></div>
            <div class="stat-label">Actuals (Confirmed)</div>
            <div class="stat-value" style="font-size:15px;color:#10B981">
                GHS {{ number_format($totalActual, 0) }}
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-accent" style="background:var(--navy)"></div>
            <div class="stat-label">Departments Revised</div>
            <div class="stat-value">{{ $revisionCount }}</div>
        </div>
    </div>
</div>

{{-- Revisions inventory --}}
<div class="chart-card mb-4">
    <div class="fw-semibold mb-3" style="font-size:13px;color:var(--navy)">
        <i class="bi bi-pencil-square me-1"></i> Approved Revisions
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--slate)">
                <tr>
                    <th>Department</th>
                    <th class="text-center">Original Ver.</th>
                    <th class="text-center">Revision Ver.</th>
                    <th class="text-end">Original Budget</th>
                    <th class="text-end">Revised Budget</th>
                    <th class="text-end">Change</th>
                    <th>Revised By</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($revisedVersions as $rv)
                @php
                    $origV    = $rv->originalVersion;
                    $origAmt  = $origV ? $origV->lineItems->sum('total_amount') : 0;
                    $revAmt   = $rv->lineItems->sum('total_amount');
                    $chg      = $revAmt - $origAmt;
                @endphp
                <tr>
                    <td>
                        <div class="fw-semibold" style="font-size:13px;color:var(--navy)">
                            {{ $rv->department?->name ?? $rv->subsidiary?->name ?? '—' }}
                        </div>
                    </td>
                    <td class="text-center">
                        @if($origV)
                        <a href="{{ route('budgets.show', $origV) }}"
                           class="badge text-decoration-none"
                           style="background:#E2E8F0;color:#1B2A4A">
                            v{{ $origV->version_number }}
                        </a>
                        @else
                        <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <a href="{{ route('budgets.show', $rv) }}"
                           class="badge text-decoration-none"
                           style="background:#EDE9FE;color:#5B21B6">
                            v{{ $rv->version_number }}
                        </a>
                    </td>
                    <td class="text-end small">GHS {{ number_format($origAmt, 0) }}</td>
                    <td class="text-end small fw-semibold">GHS {{ number_format($revAmt, 0) }}</td>
                    <td class="text-end small"
                        style="color:{{ $chg >= 0 ? '#10B981' : '#F43F5E' }}">
                        {{ $chg >= 0 ? '+' : '' }}{{ number_format($chg, 0) }}
                    </td>
                    <td class="small text-muted">
                        {{ $rv->revisedBy?->name ?? '—' }}
                        @if($rv->revised_at)
                        <div style="font-size:10px">{{ $rv->revised_at->format('d M Y') }}</div>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('budgets.show', $rv) }}"
                           class="btn btn-sm"
                           style="font-size:11px;background:var(--navy);color:#fff;border-radius:6px;padding:2px 10px">
                            View
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Code-level comparison table --}}
@if(!empty($data))
<div class="chart-card p-0">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <div class="fw-semibold" style="font-size:13px;color:var(--navy)">
            <i class="bi bi-table me-1"></i> Original vs Revised vs Actuals — by Account Code
        </div>
        <div class="d-flex gap-2">
            <span style="font-size:11px;padding:2px 10px;border-radius:20px;background:#E2E8F0;color:#475569">Original</span>
            <span style="font-size:11px;padding:2px 10px;border-radius:20px;background:#EDE9FE;color:#5B21B6">Revised</span>
            <span style="font-size:11px;padding:2px 10px;border-radius:20px;background:#D1FAE5;color:#065F46">Actuals</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--slate);background:#F8FAFC">
                <tr>
                    <th style="padding:10px 16px;min-width:220px">Account Code / Name</th>
                    <th class="text-end" style="min-width:130px">Original Budget</th>
                    <th class="text-end" style="min-width:130px">Revised Budget</th>
                    <th class="text-end" style="min-width:110px">Revision Change</th>
                    <th class="text-end" style="min-width:120px">Actuals (YTD)</th>
                    <th class="text-end" style="min-width:120px">Var vs Original</th>
                    <th class="text-end" style="min-width:120px">Var vs Revised</th>
                    <th style="min-width:110px">Utilisation</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $catName => $codes)
                {{-- Category header --}}
                <tr style="background:#F1F5F9">
                    <td colspan="8" class="fw-semibold" style="font-size:11px;text-transform:uppercase;
                        letter-spacing:.5px;color:#64748B;padding:8px 16px">
                        {{ $catName }}
                    </td>
                </tr>
                @php
                    $catOrigTotal   = collect($codes)->sum('orig_total');
                    $catRevTotal    = collect($codes)->sum('rev_total');
                    $catActualTotal = collect($codes)->sum('actual');
                    $catRevChange   = $catRevTotal - $catOrigTotal;
                @endphp
                @foreach($codes as $code => $row)
                @php
                    $isRevenue      = in_array($row['budget_type'], ['revenue','both']);
                    $varOrig        = $row['var_vs_orig'];
                    $varRev         = $row['var_vs_revised'];
                    $revChg         = $row['rev_change'];
                    $util           = $row['has_revision'] ? $row['util_revised'] : $row['util_orig'];
                @endphp
                <tr>
                    <td style="padding:8px 16px">
                        <div style="font-size:12px;font-weight:600;color:#1B2A4A">
                            {{ $code }}
                        </div>
                        <div style="font-size:10px;color:#64748B">{{ $row['name'] }}</div>
                    </td>
                    <td class="text-end" style="font-size:12px;color:#475569">
                        {{ $row['orig_total'] > 0 ? number_format($row['orig_total'], 0) : '—' }}
                    </td>
                    <td class="text-end" style="font-size:12px;font-weight:600;color:#5B21B6">
                        @if($row['has_revision'])
                        {{ number_format($row['rev_total'], 0) }}
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-end" style="font-size:11px;color:{{ $revChg > 0 ? '#10B981' : ($revChg < 0 ? '#F43F5E' : '#94A3B8') }}">
                        @if($row['has_revision'] && $revChg != 0)
                        {{ $revChg > 0 ? '+' : '' }}{{ number_format($revChg, 0) }}
                        @if($row['rev_change_pct'] !== null)
                        <div style="font-size:10px">({{ $row['rev_change_pct'] >= 0 ? '+' : '' }}{{ $row['rev_change_pct'] }}%)</div>
                        @endif
                        @elseif($row['has_revision'])
                        <span class="text-muted small">no change</span>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-end" style="font-size:12px;color:#10B981;font-weight:600">
                        {{ $row['actual'] > 0 ? number_format($row['actual'], 0) : '—' }}
                    </td>
                    <td class="text-end" style="font-size:11px;color:{{ $varOrig >= 0 ? '#10B981' : '#F43F5E' }}">
                        @if($row['orig_total'] > 0 || $row['actual'] > 0)
                        {{ $varOrig >= 0 ? '+' : '' }}{{ number_format($varOrig, 0) }}
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-end" style="font-size:11px;color:{{ ($varRev ?? 0) >= 0 ? '#10B981' : '#F43F5E' }}">
                        @if($varRev !== null)
                        {{ $varRev >= 0 ? '+' : '' }}{{ number_format($varRev, 0) }}
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($util > 0)
                        <div class="d-flex align-items-center gap-1">
                            <div class="progress flex-grow-1" style="height:5px;border-radius:3px">
                                <div class="progress-bar" style="width:{{ min($util,100) }}%;border-radius:3px;
                                     background:{{ $util>90?'#F43F5E':($util>70?'#F59E0B':'#10B981') }}"></div>
                            </div>
                            <span style="font-size:10px;color:#64748B;white-space:nowrap">{{ $util }}%</span>
                        </div>
                        @else
                        <span class="text-muted" style="font-size:11px">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach

                {{-- Category subtotal --}}
                <tr style="background:#F8FAFC;border-top:1px solid #E2E8F0">
                    <td style="padding:6px 16px;font-size:11px;font-weight:600;color:#475569">
                        {{ $catName }} subtotal
                    </td>
                    <td class="text-end small fw-semibold" style="color:#475569">
                        {{ number_format($catOrigTotal, 0) }}
                    </td>
                    <td class="text-end small fw-semibold" style="color:#5B21B6">
                        {{ $catRevTotal > 0 ? number_format($catRevTotal, 0) : '—' }}
                    </td>
                    <td class="text-end" style="font-size:11px;color:{{ $catRevChange > 0 ? '#10B981' : ($catRevChange < 0 ? '#F43F5E' : '#94A3B8') }}">
                        @if($catRevTotal > 0 && $catRevChange != 0)
                        {{ $catRevChange > 0 ? '+' : '' }}{{ number_format($catRevChange, 0) }}
                        @endif
                    </td>
                    <td class="text-end small fw-semibold" style="color:#10B981">
                        {{ number_format($catActualTotal, 0) }}
                    </td>
                    <td colspan="3"></td>
                </tr>
                @endforeach

                {{-- Grand total --}}
                <tr style="background:var(--navy);color:#fff">
                    <td style="padding:10px 16px;font-weight:700">TOTAL</td>
                    <td class="text-end fw-bold">GHS {{ number_format($totalOrig, 0) }}</td>
                    <td class="text-end fw-bold" style="color:#C4B5FD">
                        GHS {{ number_format($totalRevised, 0) }}
                    </td>
                    <td class="text-end" style="font-size:12px;color:{{ $revChange >= 0 ? '#6EE7B7' : '#FCA5A5' }}">
                        {{ $revChange >= 0 ? '+' : '' }}{{ number_format($revChange, 0) }}
                    </td>
                    <td class="text-end fw-bold" style="color:#6EE7B7">
                        GHS {{ number_format($totalActual, 0) }}
                    </td>
                    <td colspan="3"></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endif

@endif {{-- end if revisionCount > 0 --}}

@endsection
