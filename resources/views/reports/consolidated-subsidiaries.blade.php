@extends('layouts.app')
@section('title', 'Consolidated Subsidiaries')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Consolidated Subsidiaries Report</h5>
        <p class="text-muted small mb-0">
            <a href="{{ route('reports.index') }}" class="text-muted">Reports</a>
            / Consolidated Subsidiaries
        </p>
    </div>
    @if($period && isset($tree) && count($tree))
    <a href="{{ route('reports.consolidated-subsidiaries', array_merge(request()->only(['period_id','budget_basis']), ['export'=>'csv'])) }}"
       class="btn btn-sm" style="background:#F1F5F9;color:#475569;border:1px solid #E2E8F0;border-radius:8px;font-size:12px">
        <i class="fas fa-download me-1"></i> Export CSV
    </a>
    @endif
</div>

{{-- Filters --}}
<form method="GET" class="chart-card mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Period</label>
            <select name="period_id" class="form-select form-select-sm">
                @foreach($periods as $p)
                <option value="{{ $p->id }}" {{ request('period_id', $period?->id) == $p->id ? 'selected' : '' }}>
                    {{ $p->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Budget Basis</label>
            <select name="budget_basis" class="form-select form-select-sm">
                <option value="original" {{ ($basis ?? 'original') === 'original' ? 'selected' : '' }}>Original Approved</option>
                <option value="revised"  {{ ($basis ?? '') === 'revised'  ? 'selected' : '' }}>Latest Revision</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm w-100"
                    style="background:var(--navy);color:#fff;border-radius:8px">Apply</button>
        </div>
    </div>
</form>

@if($period && isset($tree) && count($tree))

{{-- Summary cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="chart-card text-center p-3">
            <div class="text-muted small mb-1">Subsidiaries</div>
            <div class="fs-4 fw-bold" style="color:var(--navy)">{{ $subsidiaries->count() }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="chart-card text-center p-3">
            <div class="text-muted small mb-1">Categories</div>
            <div class="fs-4 fw-bold text-primary">{{ count($tree) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="chart-card text-center p-3">
            <div class="text-muted small mb-1">Line Items</div>
            <div class="fs-4 fw-bold text-info">
                {{ collect($tree)->sum(fn($c) => count($c['codes'])) }}
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="chart-card text-center p-3">
            <div class="text-muted small mb-1">Grand Total ({{ currency() }})</div>
            <div class="fs-5 fw-bold" style="color:var(--orange)">
                {{ number_format($grandTotal, 2) }}
            </div>
        </div>
    </div>
</div>

<div class="chart-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-semibold mb-0">All Subsidiaries — {{ $period->name }}</h6>
        <span class="badge" style="background:var(--navy);color:#fff">{{ ucfirst($basis) }} basis</span>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle" style="font-size:.82rem;min-width:700px">
            <thead class="table-dark">
                <tr>
                    <th style="min-width:160px">Category / Account Code</th>
                    <th style="min-width:220px">Description</th>
                    @foreach($subsidiaries as $sub)
                    <th class="text-end" style="min-width:120px">
                        {{ $sub->name }}<br>
                        <span class="fw-normal opacity-75" style="font-size:.75rem">{{ $sub->code }}</span>
                    </th>
                    @endforeach
                    <th class="text-end" style="min-width:110px">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tree as $catRow)
                @php
                    $catTotal = 0;
                    $catSubTotals = [];
                    foreach ($catRow['codes'] as $cr) {
                        foreach ($subsidiaries as $s) {
                            $catSubTotals[$s->id] = ($catSubTotals[$s->id] ?? 0) + ($cr['depts'][$s->id] ?? 0);
                            $catTotal += ($cr['depts'][$s->id] ?? 0);
                        }
                    }
                @endphp
                {{-- Category header row --}}
                <tr style="background:#F0F4FF;border-top:2px solid #CBD5E1">
                    <td class="fw-bold text-uppercase" style="font-size:.75rem;color:var(--navy);letter-spacing:.04em" colspan="2">
                        <span class="badge me-1" style="background:#E2E8F0;color:#1B2A4A;font-size:.7rem">
                            {{ $catRow['cat']->code ?? '' }}
                        </span>
                        {{ $catRow['cat']->name }}
                    </td>
                    @foreach($subsidiaries as $sub)
                    <td class="text-end fw-semibold" style="color:var(--navy);font-size:.8rem">
                        {{ ($catSubTotals[$sub->id] ?? 0) > 0 ? number_format($catSubTotals[$sub->id], 0) : '—' }}
                    </td>
                    @endforeach
                    <td class="text-end fw-bold" style="color:var(--navy)">
                        {{ number_format($catTotal, 0) }}
                    </td>
                </tr>
                {{-- Code rows --}}
                @foreach($catRow['codes'] as $codeRow)
                @php $rowTotal = array_sum($codeRow['depts']); @endphp
                <tr>
                    <td class="ps-3 text-muted" style="font-size:.8rem">
                        {{ $codeRow['code']->code }}
                    </td>
                    <td style="font-size:.82rem">{{ $codeRow['code']->name }}</td>
                    @foreach($subsidiaries as $sub)
                    @php $amt = $codeRow['depts'][$sub->id] ?? 0; @endphp
                    <td class="text-end {{ $amt > 0 ? '' : 'text-muted' }}">
                        {{ $amt > 0 ? number_format($amt, 0) : '—' }}
                    </td>
                    @endforeach
                    <td class="text-end fw-semibold" style="color:var(--navy)">
                        {{ number_format($rowTotal, 0) }}
                    </td>
                </tr>
                @endforeach
                @endforeach
            </tbody>
            <tfoot class="table-dark fw-bold">
                <tr>
                    <td colspan="2">Grand Total</td>
                    @foreach($subsidiaries as $sub)
                    @php
                        $subGrand = 0;
                        foreach ($tree as $catRow) {
                            foreach ($catRow['codes'] as $codeRow) {
                                $subGrand += $codeRow['depts'][$sub->id] ?? 0;
                            }
                        }
                    @endphp
                    <td class="text-end">{{ number_format($subGrand, 0) }}</td>
                    @endforeach
                    <td class="text-end" style="color:#FFB347">{{ number_format($grandTotal, 0) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@elseif($period)
<div class="alert alert-info">No approved subsidiary budgets found for this period.</div>
@else
<div class="alert alert-warning">Select a period to view the consolidated subsidiaries report.</div>
@endif

@endsection
