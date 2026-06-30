@extends('layouts.app')
@section('title', 'Record Actuals — ' . \App\Models\BudgetActual::MONTHS[$month] . ' ' . $year)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">
            Record Actuals — {{ \App\Models\BudgetActual::MONTHS[$month] }} {{ $year }}
        </h5>
        <p class="text-muted small mb-0">
            <a href="{{ route('actuals.index', ['period_id'=>$period->id,'department_id'=>$department->id]) }}"
               class="text-muted">Actuals</a>
            / {{ $department->name }} / {{ \App\Models\BudgetActual::MONTHS[$month] }}
        </p>
    </div>

    {{-- Month navigator --}}
    <div class="d-flex gap-2 align-items-center">
        @if($month > 1)
        <a href="{{ route('actuals.entry', ['period_id'=>$period->id,'department_id'=>$department->id,'month'=>$month-1,'year'=>$year]) }}"
           class="btn btn-sm btn-outline-secondary">← Prev</a>
        @endif

        <span style="font-size:13px;font-weight:600;color:var(--navy)">
            {{ \App\Models\BudgetActual::MONTHS[$month] }} {{ $year }}
        </span>

        @if($month < 12)
        <a href="{{ route('actuals.entry', ['period_id'=>$period->id,'department_id'=>$department->id,'month'=>$month+1,'year'=>$year]) }}"
           class="btn btn-sm btn-outline-secondary">Next →</a>
        @endif
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Validation Errors</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Over-budget warning --}}
@if(session('over_budget_items'))
<div style="background:#FEE2E2;border:1px solid #FECACA;border-radius:12px;
            padding:20px;margin-bottom:20px">
    <div style="font-size:14px;font-weight:700;color:#991B1B;margin-bottom:12px">
        <i class="fas fa-exclamation-circle me-2"></i> Submission Blocked — Budget Overrun Detected
    </div>
    <p style="font-size:13px;color:#991B1B;margin-bottom:12px">
        The following expense lines would exceed the approved budget
        (original budget + any approved supplementary).
    </p>

    <table class="table table-sm mb-3" style="font-size:12px">
        <thead style="background:#FCA5A5;color:#7F1D1D">
            <tr>
                <th>Code</th>
                <th>Account</th>
                <th class="text-end">Original Budget</th>
                <th class="text-end">Supplementary</th>
                <th class="text-end">Effective Budget</th>
                <th class="text-end">YTD Before</th>
                <th class="text-end">This Entry</th>
                <th class="text-end">Overrun</th>
            </tr>
        </thead>
        <tbody>
            @foreach(session('over_budget_items') as $item)
            <tr>
                <td style="font-family:monospace;font-weight:700">{{ $item['code'] }}</td>
                <td>{{ $item['name'] }}</td>
                <td class="text-end">{{ number_format($item['original_budget'],2) }}</td>
                <td class="text-end" style="color:#92400E">
                    {{ $item['supplementary'] > 0 ? '+'.number_format($item['supplementary'],2) : '—' }}
                </td>
                <td class="text-end fw-bold">{{ number_format($item['budget'],2) }}</td>
                <td class="text-end">{{ number_format($item['ytd_before'],2) }}</td>
                <td class="text-end text-danger fw-bold">{{ number_format($item['this_entry'],2) }}</td>
                <td class="text-end fw-bold" style="color:#991B1B">
                    +{{ number_format($item['overrun'],2) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="d-flex gap-2">
        <a href="{{ route('supplementary.create', [
                'period_id'     => $period->id,
                'department_id' => $department->id,
            ]) }}"
           class="btn btn-sm"
           style="background:#991B1B;color:#fff;border-radius:8px;padding:8px 18px">
            <i class="fas fa-plus-circle"></i> Request Supplementary Budget
        </a>
        <a href="{{ route('actuals.index', [
                'period_id'     => $period->id,
                'department_id' => $department->id,
            ]) }}"
           class="btn btn-sm btn-outline-secondary"
           style="border-radius:8px">
            Back to Actuals
        </a>
    </div>
</div>
@endif

{{-- Period/dept switcher --}}
<form method="GET" action="{{ route('actuals.entry') }}"
      class="chart-card mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Period</label>
            <select name="period_id" class="form-select form-select-sm"
                    onchange="this.form.submit()">
                @foreach($periods as $p)
                <option value="{{ $p->id }}"
                    {{ $period->id == $p->id ? 'selected' : '' }}>
                    {{ $p->name }}
                </option>
                @endforeach
            </select>
        </div>
        @can('view all budgets')
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Department</label>
            <select name="department_id" class="form-select form-select-sm"
                    onchange="this.form.submit()">
                @foreach($departments as $d)
                <option value="{{ $d->id }}"
                    {{ $department->id == $d->id ? 'selected' : '' }}>
                    {{ $d->name }}
                </option>
                @endforeach
            </select>
        </div>
        @endcan
        <input type="hidden" name="month" value="{{ $month }}">
        <input type="hidden" name="year"  value="{{ $year }}">
    </div>
</form>

{{-- Excel Import/Export --}}
<div class="chart-card mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <div style="font-size:13px;font-weight:600;color:var(--navy)">
                Excel Import / Export
            </div>
            <div style="font-size:12px;color:var(--slate)">
                Download pre-filled template for {{ \App\Models\BudgetActual::MONTHS[$month] }} {{ $year }},
                fill actual amounts in Excel, then upload.
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('ie.actuals.download', [
                    'budget_version_id' => $version->id,
                    'month'             => $month,
                    'year'              => $year,
                ]) }}"
               class="btn btn-sm btn-outline-success">
                <i class="fas fa-download"></i> Download Template
            </a>
            <button type="button"
                    onclick="document.getElementById('actualsUploadPanel').classList.toggle('d-none')"
                    class="btn btn-sm btn-outline-primary">
                <i class="fas fa-upload"></i> Upload Excel
            </button>
        </div>
    </div>

    <div id="actualsUploadPanel" class="d-none mt-3 pt-3 border-top">
        <form method="POST"
              action="{{ route('ie.actuals.upload') }}"
              enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="budget_version_id" value="{{ $version->id }}">
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year"  value="{{ $year }}">

            <div class="d-flex gap-2 align-items-end">
                <div class="flex-grow-1">
                    <label class="form-label small fw-semibold mb-1">
                        Select filled Excel file
                    </label>
                    <input type="file" name="file" accept=".xlsx,.xls"
                           class="form-control form-control-sm">
                </div>
                <button type="submit" class="btn btn-sm btn-primary">
                    Upload & Save
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Check if month is already confirmed --}}
@php
    $isConfirmed = \App\Models\BudgetActual::where('department_id', $department->id)
        ->where('budget_period_id', $period->id)
        ->where('month', $month)
        ->where('year',  $year)
        ->where('status','confirmed')
        ->exists();
@endphp

@if($isConfirmed)
<div class="alert mb-4"
     style="background:#D1FAE5;color:#065F46;border:none;border-radius:10px">
    <i class="fas fa-check-circle"></i> This month's actuals have been confirmed and locked.
    Contact Finance to make amendments.
</div>
@endif

{{-- Entry form --}}
<form method="POST" action="{{ route('actuals.store') }}" id="actualForm">
    @csrf
    <input type="hidden" name="period_id"     value="{{ $period->id }}">
    <input type="hidden" name="department_id" value="{{ $department->id }}">
    <input type="hidden" name="month"         value="{{ $month }}">
    <input type="hidden" name="year"          value="{{ $year }}">

    {{-- Running total bar --}}
    @php
        // Calculate effective total budget including supplementary
        $effectiveTotalBudget = 0;
        $totalSupplementary = 0;
        foreach ($version->lineItems as $item) {
            $supp = $item->approvedSupplementaryTotal();
            $effectiveTotalBudget += $item->effectiveBudget();
            $totalSupplementary += $supp;
        }
        $remainingBudget = $effectiveTotalBudget - $ytdActuals->sum();
    @endphp
    <div style="background:var(--navy);border-radius:12px;padding:16px 20px;
                color:#fff;margin-bottom:20px">
        <div class="row text-center">
            <div class="col">
                <div style="font-size:11px;color:rgba(255,255,255,.5)">
                    Effective Budget (Full Year)
                </div>
                <div style="font-size:16px;font-weight:700">
                    {{ currency() }} {{ number_format($effectiveTotalBudget, 0) }}
                    @if($totalSupplementary > 0)
                    <span style="font-size:10px;color:#10B981;display:block;">
                        +{{ number_format($totalSupplementary, 0) }} supplementary
                    </span>
                    @endif
                </div>
            </div>
            <div class="col">
                <div style="font-size:11px;color:rgba(255,255,255,.5)">YTD Actual</div>
                <div style="font-size:16px;font-weight:700;color:var(--gold)"
                     id="ytdTotal">
                    {{ currency() }} {{ number_format($ytdActuals->sum(), 0) }}
                </div>
            </div>
            <div class="col">
                <div style="font-size:11px;color:rgba(255,255,255,.5)">
                    This Month Total
                </div>
                <div style="font-size:16px;font-weight:700" id="monthTotal">
                    {{ currency() }} 0.00
                </div>
            </div>
            <div class="col">
                <div style="font-size:11px;color:rgba(255,255,255,.5)">
                    Remaining Budget
                </div>
                <div style="font-size:16px;font-weight:700;
                    color:{{ $remainingBudget >= 0 ? '#6EE7B7' : '#FCA5A5' }}"
                     id="remaining">
                    {{ currency() }} {{ number_format($remainingBudget, 0) }}
                </div>
            </div>
        </div>
    </div>

    {{-- Line items by category --}}
    @foreach($byCategory as $catName => $items)
    <div class="chart-card mb-3">
        <div class="chart-title">{{ $catName }}</div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead style="font-size:11px;text-transform:uppercase;
                              letter-spacing:.5px;color:var(--slate)">
                    <tr>
                        <th style="width:25%">Account</th>
                        <th class="text-end" style="min-width:120px">Budget</th>
                        <th class="text-end">YTD Actual</th>
                        <th class="text-end">Remaining</th>
                        <th class="text-end" style="width:150px">
                            {{ \App\Models\BudgetActual::MONTHS[$month] }} Actual
                        </th>
                        <th>Reference</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    @php
                        $existing = $existingActuals->get($item->id);
                        $ytd      = $ytdActuals->get($item->id, 0);
                        $supplementary = $item->approvedSupplementaryTotal();
                        $effective     = $item->effectiveBudget();
                        $remaining = $effective - $ytd;
                        $idx = $loop->parent->index * 100 + $loop->index;
                    @endphp
                    <tr>
                        <input type="hidden"
                               name="actuals[{{ $idx }}][line_item_id]"
                               value="{{ $item->id }}">

                        <td>
                            <div style="font-family:monospace;font-weight:700;font-size:12px;color:var(--navy)">
                                {{ $item->accountCode->code }}
                            </div>
                            <div style="font-size:11px;color:var(--slate)">
                                {{ $item->accountCode->name }}
                            </div>
                            @if($item->justification)
                            <div style="font-size:10px;color:#94A3B8;font-style:italic;margin-top:2px">
                                {{ Str::limit($item->justification, 50) }}
                            </div>
                            @endif
                        </td>
                        <td class="text-end small">
                            <div>{{ number_format($item->total_amount, 2) }}</div>
                            @if($supplementary > 0)
                            <div style="font-size:10px;color:#10B981;">
                                +{{ number_format($supplementary, 2) }} supp.
                            </div>
                            <div style="font-size:11px;font-weight:700;color:var(--navy);border-top:1px dashed var(--border);padding-top:2px;margin-top:2px">
                                = {{ number_format($effective, 2) }}
                            </div>
                            @endif
                        </td>
                        <td class="text-end small">
                            {{ number_format($ytd, 2) }}
                        </td>
                        <td class="text-end small fw-semibold"
                            style="color:{{ $remaining >= 0 ? '#10B981' : '#F43F5E' }}">
                            {{ number_format($remaining, 2) }}
                        </td>
                        <td>
                            <input type="number"
                                   name="actuals[{{ $idx }}][amount]"
                                   class="form-control form-control-sm actual-input"
                                   value="{{ $existing?->amount ?? '' }}"
                                   min="0" step="0.01"
                                   placeholder="0.00"
                                   {{ $isConfirmed ? 'readonly' : '' }}
                                   oninput="updateTotals()">
                        </td>
                        <td>
                            <input type="text"
                                   name="actuals[{{ $idx }}][reference]"
                                   class="form-control form-control-sm"
                                   value="{{ $existing?->reference ?? '' }}"
                                   placeholder="Invoice/Voucher #"
                                   {{ $isConfirmed ? 'readonly' : '' }}>
                        </td>
                        <td>
                            <input type="text"
                                   name="actuals[{{ $idx }}][description]"
                                   class="form-control form-control-sm"
                                   value="{{ $existing?->description ?? '' }}"
                                   placeholder="Optional note"
                                   {{ $isConfirmed ? 'readonly' : '' }}>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                @php
                    $catTotal = $items->sum(fn($i) => $i->effectiveBudget());
                    $catYtd = $items->sum(fn($i) => $ytdActuals->get($i->id, 0));
                    $catRemaining = $catTotal - $catYtd;
                @endphp
                <tfoot style="background:#F8FAFC;font-weight:700;font-size:12px">
                    <tr>
                        <td>Category Total</td>
                        <td class="text-end">{{ number_format($catTotal, 2) }}</td>
                        <td class="text-end">{{ number_format($catYtd, 2) }}</td>
                        <td class="text-end" style="color:{{ $catRemaining >= 0 ? '#10B981' : '#F43F5E' }}">
                            {{ number_format($catRemaining, 2) }}
                        </td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endforeach

    @if(!$isConfirmed)
    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn"
                style="background:var(--navy);color:#fff;border-radius:8px;padding:10px 24px">
            <i class="fas fa-save"></i> Save as Draft
        </button>

        {{-- Confirm button -- only for Finance/Admin --}}
        @can('approve budget')
        <button type="button"
                onclick="confirmMonth()"
                class="btn"
                style="background:#10B981;color:#fff;border-radius:8px;padding:10px 24px">
            <i class="fas fa-check-circle"></i> Save & Confirm Month
        </button>
        @endcan

        <a href="{{ route('actuals.index', ['period_id'=>$period->id,'department_id'=>$department->id]) }}"
           class="btn btn-outline-secondary"
           style="border-radius:8px;padding:10px 24px">
            <i class="fas fa-times"></i> Cancel
        </a>
    </div>
    @endif
</form>

{{-- Hidden confirm form --}}
@can('approve budget')
<form method="POST" action="{{ route('actuals.confirm') }}" id="confirmForm">
    @csrf
    <input type="hidden" name="period_id"     value="{{ $period->id }}">
    <input type="hidden" name="department_id" value="{{ $department->id }}">
    <input type="hidden" name="month"         value="{{ $month }}">
    <input type="hidden" name="year"          value="{{ $year }}">
</form>
@endcan

<script>
function updateTotals() {
    let total = 0;
    document.querySelectorAll('.actual-input').forEach(inp => {
        total += parseFloat(inp.value) || 0;
    });

    document.getElementById('monthTotal').textContent =
        '{{ currency() }} ' + total.toLocaleString('en-GH', { minimumFractionDigits: 2 });
}

function confirmMonth() {
    // Calculate total amount
    let totalAmount = 0;
    let itemCount = 0;
    document.querySelectorAll('.actual-input').forEach(inp => {
        const val = parseFloat(inp.value) || 0;
        if (val > 0) {
            totalAmount += val;
            itemCount++;
        }
    });

    if (itemCount === 0) {
        Swal.fire({
            title: 'No Actuals to Confirm',
            html: `
                <p style="color:#64748B;font-size:14px;">
                    You haven't entered any actuals for
                    <strong>{{ \App\Models\BudgetActual::MONTHS[$month] }} {{ $year }}</strong>.
                </p>
                <p style="color:#64748B;font-size:13px;margin-top:8px;">
                    Please enter amounts before confirming.
                </p>
            `,
            icon: 'info',
            confirmButtonColor: '#1B2A4A',
            confirmButtonText: 'OK, Go Back',
        });
        return;
    }

    Swal.fire({
        title: 'Confirm Month Actuals?',
        html: `
            <p style="color:#64748B;margin-bottom:12px">
                You are about to <strong>save and confirm</strong> all actuals for
                <strong>{{ \App\Models\BudgetActual::MONTHS[$month] }} {{ $year }}</strong>.
            </p>
            <div style="background:#D1FAE5;border-radius:8px;padding:12px;
                        text-align:left;font-size:13px;color:#065F46;margin-bottom:12px">
                <div>✅ <strong>${itemCount}</strong> line item(s) with amounts</div>
                <div style="margin-top:4px;">📊 <strong>Total:</strong> {{ currency() }} ${totalAmount.toLocaleString('en-GH', { minimumFractionDigits: 2 })}</div>
            </div>
            <div style="background:#FEF3C7;border-radius:8px;padding:12px;
                        text-align:left;font-size:13px;color:#92400E;margin-bottom:12px">
                <div>⚠️ Confirmed actuals are <strong>locked</strong> and cannot be edited without Finance approval.</div>
                <div style="margin-top:4px;">⚠️ Only proceed if all entries are correct.</div>
            </div>
            <p style="color:#64748B;font-size:13px">
                Are you sure you want to continue?
            </p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#10B981',
        cancelButtonColor: '#64748B',
        confirmButtonText: 'Yes, Save & Confirm',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: 'Processing...',
                html: 'Saving and confirming your actuals...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Submit the form
            document.getElementById('actualForm').submit();

            // Submit confirmation after form submits
            setTimeout(() => {
                document.getElementById('confirmForm').submit();
            }, 1500);
        }
    });
}

// Init totals
document.addEventListener('DOMContentLoaded', function() {
    updateTotals();
});
</script>

<style>
    .actual-input:focus {
        border-color: #E65C00;
        box-shadow: 0 0 0 0.2rem rgba(230, 92, 0, 0.15);
    }

    .actual-input:read-only {
        background: #F1F5F9;
        cursor: not-allowed;
    }
</style>

@endsection
