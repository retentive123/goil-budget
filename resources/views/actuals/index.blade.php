@extends('layouts.app')
@section('title', 'Record Actuals')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Actual Expenditure</h5>
        <p class="text-muted small mb-0">
            Record monthly actual spend against approved budgets
        </p>
    </div>
    @can('view all budgets')
    <a href="{{ route('actuals.overview') }}"
       class="btn btn-sm"
       style="background:var(--navy);color:#fff;border-radius:8px">
        Full Overview →
    </a>
    @endcan
</div>

{{-- ── Pending Approvals Queue (multi-stage flow) ── --}}
@if($approvalFlow === 'multi_stage' && isset($pendingApprovals) && $pendingApprovals->isNotEmpty())
@php
    $isFinance   = auth()->user()->hasAnyRole(['finance_reviewer','bdu_admin','super_admin']);
    $queueTitle  = $isFinance ? 'Awaiting Your Final Approval' : 'Awaiting Your Confirmation';
    $queueColor  = $isFinance ? '#10B981' : '#8B5CF6';
    $queueBg     = $isFinance ? '#D1FAE5'  : '#EDE9FE';
    $queueIcon   = $isFinance ? 'bi-check-double' : 'bi-person-check-fill';
    $queueBtnBg  = $isFinance ? '#10B981'  : '#8B5CF6';
    $queueBtnLbl = $isFinance ? 'Final Approve' : 'Confirm';
@endphp
<div class="chart-card mb-4" style="border-left:4px solid {{ $queueColor }}">
    <div class="d-flex align-items-center gap-2 mb-3">
        <span style="background:{{ $queueBg }};color:{{ $queueColor }};border-radius:8px;
                     padding:6px 10px;font-size:15px">
            <i class="bi {{ $queueIcon }}"></i>
        </span>
        <div>
            <div style="font-weight:700;color:var(--navy);font-size:14px">
                {{ $queueTitle }}
                <span style="background:{{ $queueColor }};color:#fff;border-radius:20px;
                             font-size:11px;padding:2px 8px;margin-left:6px">
                    {{ $pendingApprovals->count() }} month{{ $pendingApprovals->count() === 1 ? '' : 's' }}
                </span>
            </div>
            <div style="font-size:12px;color:var(--slate)">
                {{ $isFinance ? 'Head-confirmed entries waiting for Finance sign-off' : 'Submitted entries waiting for your department head confirmation' }}
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm mb-0" style="font-size:13px">
            <thead>
                <tr style="color:var(--slate);font-size:11px;text-transform:uppercase;letter-spacing:.4px">
                    <th>Department / Entity</th>
                    <th>Month</th>
                    <th class="text-end">Total Amount</th>
                    <th class="text-end">Entries</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($pendingApprovals as $item)
            @php
                $dept    = $item->department;
                $deptName = $dept?->name ?? '—';
                $mName   = \App\Models\BudgetActual::MONTHS[$item->month] ?? "Month {$item->month}";
                $entryUrl = route('actuals.entry', [
                    'period_id'     => $item->budget_period_id,
                    'department_id' => $item->department_id,
                    'subsidiary_id' => $item->subsidiary_id,
                    'month'         => $item->month,
                    'year'          => $item->year,
                ]);
            @endphp
            <tr>
                <td style="font-weight:600;color:var(--navy)">{{ $deptName }}</td>
                <td>{{ $mName }} {{ $item->year }}</td>
                <td class="text-end" style="font-variant-numeric:tabular-nums">
                    {{ currency() }} {{ number_format($item->total_amount, 2) }}
                </td>
                <td class="text-end" style="color:var(--slate)">{{ $item->entry_count }}</td>
                <td class="text-end">
                    <a href="{{ $entryUrl }}"
                       class="btn btn-sm"
                       style="background:{{ $queueBtnBg }};color:#fff;border-radius:6px;
                              font-size:11px;padding:3px 10px">
                        Review &amp; {{ $queueBtnLbl }} →
                    </a>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Filters --}}
<form method="GET" class="chart-card mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Period</label>
            <select name="period_id" class="form-select form-select-sm"
                    onchange="this.form.submit()">
                @foreach($periods as $p)
                <option value="{{ $p->id }}"
                    {{ $period?->id == $p->id ? 'selected' : '' }}>
                    {{ $p->name }}
                </option>
                @endforeach
            </select>
        </div>
        @can('view all budgets')
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Department / Station</label>
            @include('reports._dept_filter', [
                'filterName' => 'department_id',
                'selectedId' => $department?->id,
                'allowEmpty' => false,
                'emptyLabel' => 'Select entity…',
                'autoSubmit' => true,
                'selectId'   => 'actualsDeptSel',
            ])
        </div>
        @endcan
    </div>
</form>

@if($department && $period)

{{-- Monthly summary grid --}}
<div class="chart-card mb-4">
    <div class="chart-title">
        {{ $department->name }} — {{ $period->name }} Monthly Summary
    </div>

    @php
    $statusDisplay = [
        'draft'          => ['label'=>'Draft',          'color'=>'#92400E','bg'=>'#FEF3C7','icon'=>'bi-pencil-fill'],
        'submitted'      => ['label'=>'Submitted',      'color'=>'#1D4ED8','bg'=>'#DBEAFE','icon'=>'bi-send-fill'],
        'head_confirmed' => ['label'=>'Head Confirmed', 'color'=>'#5B21B6','bg'=>'#EDE9FE','icon'=>'bi-person-check-fill'],
        'confirmed'      => ['label'=>'Confirmed',      'color'=>'#065F46','bg'=>'#D1FAE5','icon'=>'bi-check-circle-fill'],
    ];
    @endphp
    <div class="row g-2 mb-4">
        @foreach($monthlySummary as $m => $summary)
        @php
            $isCurrentMonth = $m == now()->month && $period->year == now()->year;
            $isPast         = ($period->year < now()->year) ||
                              ($period->year == now()->year && $m < now()->month);
            $st             = $summary['status'] ?? null;
            $sd             = $st ? ($statusDisplay[$st] ?? null) : null;
        @endphp
        <div class="col-md-2 col-4">
            <a href="{{ route('actuals.entry', [
                    'period_id'     => $period->id,
                    'department_id' => $department->id,
                    'month'         => $m,
                    'year'          => $period->year,
                ]) }}"
               style="text-decoration:none">
                <div style="border:1px solid {{ $sd ? $sd['color'] : ($isCurrentMonth ? 'var(--navy)' : 'var(--border)') }};
                            border-radius:10px;padding:12px;text-align:center;
                            background:{{ $sd ? $sd['bg'] : ($isCurrentMonth ? '#F0F4FF' : 'var(--surface)') }};
                            transition:.2s">
                    <div style="font-size:11px;font-weight:600;text-transform:uppercase;
                                letter-spacing:.5px;color:var(--slate)">
                        {{ substr($summary['name'],0,3) }}
                    </div>
                    @if($st === 'confirmed')
                    <div style="font-size:13px;font-weight:700;color:#065F46;margin-top:4px">
                        {{ currency() }} {{ number_format($summary['total'],0) }}
                    </div>
                    <div style="font-size:10px;color:#10B981">
                        <i class="bi bi-check-circle-fill me-1"></i>Confirmed
                    </div>
                    @elseif($sd)
                    <div style="font-size:12px;color:{{ $sd['color'] }};margin-top:4px">—</div>
                    <div style="font-size:10px;color:{{ $sd['color'] }}">
                        <i class="bi {{ $sd['icon'] }} me-1"></i>{{ $sd['label'] }}
                    </div>
                    @elseif($isPast || $isCurrentMonth)
                    <div style="font-size:12px;color:var(--slate);margin-top:4px">—</div>
                    <div style="font-size:10px;color:#F59E0B">Pending</div>
                    @else
                    <div style="font-size:12px;color:#CBD5E1;margin-top:4px">—</div>
                    <div style="font-size:10px;color:#CBD5E1">Future</div>
                    @endif
                </div>
            </a>
        </div>
        @endforeach
    </div>

    {{-- YTD total --}}
    @php $ytd = collect($monthlySummary)->sum('total'); @endphp
    <div class="d-flex justify-content-between align-items-center
                pt-3 border-top">
        <span class="small fw-semibold">Year-to-Date Total</span>
        <span style="font-size:18px;font-weight:700;color:var(--navy)">
            {{ currency() }} {{ number_format($ytd, 2) }}
        </span>
    </div>
</div>

{{-- Quick entry buttons --}}
<div class="chart-card">
    <div class="chart-title">Quick Entry</div>
    <div class="row g-2">
        @foreach(\App\Models\BudgetActual::MONTHS as $m => $name)
        <div class="col-md-3">
            <a href="{{ route('actuals.entry', [
                    'period_id'     => $period->id,
                    'department_id' => $department->id,
                    'month'         => $m,
                    'year'          => $period->year,
                ]) }}"
               class="btn btn-sm w-100 text-start"
               style="background:{{ $monthlySummary[$m]['has_data'] ? '#D1FAE5' : 'var(--surface)' }};
                      border:1px solid var(--border);border-radius:8px;
                      padding:8px 12px;font-size:13px;color:var(--navy)">
                {{ $name }}
                @if($monthlySummary[$m]['has_data'])
                    <span style="float:right;color:#10B981;font-size:11px">
                        {{ currency() }} {{ number_format($monthlySummary[$m]['total'],0) }}
                    </span>
                @endif
            </a>
        </div>
        @endforeach
    </div>
</div>

@endif
@endsection
