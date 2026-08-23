@extends('layouts.app')
@section('title', 'Subsidiary Budget Report')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Subsidiary Budget Report</h5>
        <p class="text-muted small mb-0">Approved budgets submitted by subsidiary entities</p>
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('reports.subsidiary') }}" class="chart-card mb-4 py-3">
    <div class="row g-2 align-items-end">

        {{-- Period --}}
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Period</label>
            <select name="period_id" class="form-select form-select-sm">
                @foreach($periods as $p)
                    <option value="{{ $p->id }}"
                        {{ request('period_id', $period?->id) == $p->id ? 'selected' : '' }}>
                        {{ $p->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Category --}}
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Category</label>
            <select name="category_id" class="form-select form-select-sm" id="catFilter"
                    onchange="filterSubsidiaries()">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}"
                        {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Individual Subsidiary --}}
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Subsidiary</label>
            <select name="subsidiary_id" class="form-select form-select-sm" id="subFilter">
                <option value="">All Subsidiaries</option>
                @foreach($subsidiaries as $sub)
                    <option value="{{ $sub->id }}"
                            data-cat="{{ $sub->subsidiary_category_id }}"
                        {{ request('subsidiary_id') == $sub->id ? 'selected' : '' }}>
                        {{ $sub->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Budget Basis + Submit --}}
        <div class="col-auto">
            <div style="display:flex;align-items:center;gap:10px;
                        background:#F1F5F9;border:1px solid #CBD5E1;
                        border-radius:10px;padding:6px 12px">
                <span style="font-size:11px;font-weight:600;color:#64748B;
                              white-space:nowrap;letter-spacing:.4px;text-transform:uppercase">
                    View&nbsp;as
                </span>
                <div style="width:1px;height:20px;background:#CBD5E1"></div>
                @include('reports._basis_toggle')
            </div>
        </div>

        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-primary">Apply</button>
            <a href="{{ route('reports.subsidiary') }}" class="btn btn-sm btn-outline-secondary ms-1">Clear</a>
        </div>
    </div>
    <input type="hidden" name="budget_basis" id="basisHidden" value="{{ $basis }}">
</form>

{{-- Context heading --}}
@if($subsidiary || $category || $period)
<div class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    @if($period)
        <span class="badge" style="background:#1B2A4A;color:#fff;font-size:12px;padding:5px 12px">
            <i class="bi bi-calendar me-1"></i>{{ $period->name }}
        </span>
    @endif
    @if($category)
        <span class="badge" style="background:#EFF6FF;color:#1D4ED8;font-size:12px;padding:5px 12px">
            <i class="bi bi-folder me-1"></i>{{ $category->name }}
        </span>
    @endif
    @if($subsidiary)
        <span class="badge" style="background:#F0FDF4;color:#065F46;font-size:12px;padding:5px 12px">
            <i class="bi bi-building me-1"></i>{{ $subsidiary->name }}
        </span>
    @endif
    <span class="text-muted small ms-1">
        {{ $rows->count() }} subsidiar{{ $rows->count() === 1 ? 'y' : 'ies' }} with approved budgets
    </span>
</div>
@endif

@if($rows->isEmpty())
    <div class="chart-card text-center py-5 text-muted">
        <i class="bi bi-building" style="font-size:48px;color:#CBD5E1;display:block;margin-bottom:12px"></i>
        <p class="fw-semibold mb-1">No approved subsidiary budgets found</p>
        <p class="small mb-0">
            @if(!$period)
                No budget period is available.
            @else
                No subsidiaries have an approved budget for
                <strong>{{ $period->name }}</strong>
                @if($category) in category <strong>{{ $category->name }}</strong>@endif
                @if($subsidiary) for <strong>{{ $subsidiary->name }}</strong>@endif.
            @endif
        </p>
    </div>
@else

{{-- Grand Total Summary --}}
<div class="row g-3 mb-4">
    @php $fmt = fn($n) => 'GHS ' . number_format($n, 2); @endphp
    @foreach(['Q1' => 'q1', 'Q2' => 'q2', 'Q3' => 'q3', 'Q4' => 'q4'] as $label => $key)
    <div class="col-6 col-md-3">
        <div class="chart-card text-center py-3">
            <div class="text-muted small text-uppercase fw-semibold mb-1"
                 style="font-size:10px;letter-spacing:.5px">{{ $label }}</div>
            <div style="font-size:18px;font-weight:700;color:#1B2A4A">
                {{ $fmt($grandTotals[$key]) }}
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="chart-card mb-4 py-2 px-3 d-flex justify-content-between align-items-center">
    <div>
        <span class="text-muted small">Grand Total (all subsidiaries)</span>
        <span class="fw-bold ms-2" style="font-size:18px;color:#1B2A4A">
            {{ $fmt($grandTotals['total']) }}
        </span>
    </div>
    <span class="badge" style="background:#{{ $basis === 'revised' ? 'C9A84C' : '1B2A4A' }};color:#fff;font-size:11px">
        {{ $basis === 'revised' ? 'Revised basis' : 'Original basis' }}
    </span>
</div>

{{-- Per-subsidiary sections --}}
@foreach($rows->groupBy(fn($r) => $r['subsidiary']?->category?->name ?? 'Uncategorised') as $catName => $catRows)

<div class="mb-2 mt-4">
    <div class="text-uppercase fw-semibold"
         style="font-size:10px;letter-spacing:.7px;color:#94A3B8;
                border-bottom:2px solid #F1F5F9;padding-bottom:4px">
        <i class="bi bi-folder-fill me-1" style="color:#C9A84C"></i>{{ $catName }}
        <span class="ms-2" style="font-weight:400;color:#CBD5E1">({{ $catRows->count() }})</span>
    </div>
</div>

@foreach($catRows as $row)
@php $sub = $row['subsidiary']; $ver = $row['version']; $totals = $row['totals']; @endphp
<div class="chart-card mb-4">
    {{-- Subsidiary header --}}
    <div class="d-flex align-items-center justify-content-between mb-3 pb-2"
         style="border-bottom:1px solid #F1F5F9">
        <div class="d-flex align-items-center gap-3">
            <div style="width:40px;height:40px;border-radius:8px;background:#1B2A4A;
                        color:#C9A84C;display:flex;align-items:center;justify-content:center;
                        font-size:11px;font-weight:700;text-align:center;line-height:1.2">
                {{ strtoupper(substr($sub?->code ?? '--', 0, 4)) }}
            </div>
            <div>
                <div class="fw-bold" style="font-size:15px;color:#1B2A4A">{{ $sub?->name }}</div>
                <div class="text-muted small">
                    <code style="font-size:11px">{{ $sub?->code }}</code>
                    @if($sub?->category)
                    <span class="ms-2" style="font-size:11px">{{ $sub->category->name }}</span>
                    @endif
                    &nbsp;·&nbsp;Version {{ $ver->version_number }}
                    @if($ver->is_revision)
                        <span class="badge ms-1" style="background:#FEF3C7;color:#92400E;font-size:10px">Revised</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="text-end">
            <div class="fw-bold" style="font-size:16px;color:#1B2A4A">
                {{ $fmt($totals['total']) }}
            </div>
            <div class="text-muted" style="font-size:11px">
                Q1 {{ $fmt($totals['q1']) }} &nbsp;
                Q2 {{ $fmt($totals['q2']) }} &nbsp;
                Q3 {{ $fmt($totals['q3']) }} &nbsp;
                Q4 {{ $fmt($totals['q4']) }}
            </div>
        </div>
    </div>

    {{-- Line items table --}}
    @if($row['items']->isNotEmpty())
    <div class="table-responsive">
        <table class="table table-sm mb-0" style="font-size:12px">
            <thead style="font-size:10px;text-transform:uppercase;color:#94A3B8;letter-spacing:.4px">
                <tr>
                    <th style="padding:6px 10px;border:none">Code</th>
                    <th style="padding:6px 10px;border:none">Name</th>
                    <th style="padding:6px 10px;border:none">Category</th>
                    <th style="padding:6px 10px;border:none;text-align:right">Q1</th>
                    <th style="padding:6px 10px;border:none;text-align:right">Q2</th>
                    <th style="padding:6px 10px;border:none;text-align:right">Q3</th>
                    <th style="padding:6px 10px;border:none;text-align:right">Q4</th>
                    <th style="padding:6px 10px;border:none;text-align:right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($row['items']->groupBy('category') as $catLabel => $codeItems)
                <tr>
                    <td colspan="8" style="padding:4px 10px;background:#F8FAFC;font-size:10px;
                                           font-weight:700;text-transform:uppercase;letter-spacing:.5px;
                                           color:#64748B;border:none">
                        {{ $catLabel }}
                    </td>
                </tr>
                @foreach($codeItems as $item)
                <tr style="border-bottom:1px solid #F8FAFC">
                    <td style="padding:5px 10px;border:none">
                        <code style="font-size:11px;color:#E65C00;background:transparent">{{ $item['code'] }}</code>
                    </td>
                    <td style="padding:5px 10px;border:none;color:#374151">{{ $item['name'] }}</td>
                    <td style="padding:5px 10px;border:none;color:#94A3B8"></td>
                    <td style="padding:5px 10px;border:none;text-align:right;
                               font-variant-numeric:tabular-nums">
                        {{ number_format($item['q1'], 2) }}
                    </td>
                    <td style="padding:5px 10px;border:none;text-align:right;
                               font-variant-numeric:tabular-nums">
                        {{ number_format($item['q2'], 2) }}
                    </td>
                    <td style="padding:5px 10px;border:none;text-align:right;
                               font-variant-numeric:tabular-nums">
                        {{ number_format($item['q3'], 2) }}
                    </td>
                    <td style="padding:5px 10px;border:none;text-align:right;
                               font-variant-numeric:tabular-nums">
                        {{ number_format($item['q4'], 2) }}
                    </td>
                    <td style="padding:5px 10px;border:none;text-align:right;font-weight:600;
                               font-variant-numeric:tabular-nums;color:#1B2A4A">
                        {{ number_format($item['total'], 2) }}
                    </td>
                </tr>
                @endforeach
                @endforeach

                {{-- Subsidiary subtotal --}}
                <tr style="background:#F8FAFC;font-weight:700;border-top:2px solid #E2E8F0">
                    <td colspan="3" style="padding:7px 10px;border:none;color:#1B2A4A;font-size:12px">
                        Total — {{ $sub?->name }}
                    </td>
                    <td style="padding:7px 10px;border:none;text-align:right;font-variant-numeric:tabular-nums">
                        {{ number_format($totals['q1'], 2) }}
                    </td>
                    <td style="padding:7px 10px;border:none;text-align:right;font-variant-numeric:tabular-nums">
                        {{ number_format($totals['q2'], 2) }}
                    </td>
                    <td style="padding:7px 10px;border:none;text-align:right;font-variant-numeric:tabular-nums">
                        {{ number_format($totals['q3'], 2) }}
                    </td>
                    <td style="padding:7px 10px;border:none;text-align:right;font-variant-numeric:tabular-nums">
                        {{ number_format($totals['q4'], 2) }}
                    </td>
                    <td style="padding:7px 10px;border:none;text-align:right;font-size:14px;color:#1B2A4A">
                        {{ $fmt($totals['total']) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    @else
        <p class="text-muted small mb-0 py-2">No line items in this budget version.</p>
    @endif
</div>
@endforeach
@endforeach

@endif

@push('scripts')
<script>
function filterSubsidiaries() {
    var catId = document.getElementById('catFilter').value;
    var sel   = document.getElementById('subFilter');
    Array.from(sel.options).forEach(function(opt) {
        if (!opt.value) return; // keep "All" option
        opt.style.display = (!catId || opt.dataset.cat === catId) ? '' : 'none';
    });
    // Clear subsidiary selection if it no longer matches
    if (catId && sel.value && sel.options[sel.selectedIndex]?.dataset.cat !== catId) {
        sel.value = '';
    }
}
// Run on load to sync state with current URL params
filterSubsidiaries();
</script>
@endpush

@endsection
