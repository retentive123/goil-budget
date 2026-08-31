@extends('layouts.app')
@section('title', 'Record Actuals — ' . \App\Models\BudgetActual::MONTHS[$month] . ' ' . $year)
@section('content')
@php
    // $monthStatus ('draft'|'submitted'|'head_confirmed'|'confirmed'|null) and
    // $approvalFlow ('simple'|'multi_stage') are passed by the controller.
    $isConfirmed = $monthStatus === 'confirmed';

    // Form inputs are locked whenever the month is no longer in a pure draft state.
    // Simple flow: locked only once fully confirmed.
    // Multi-stage: locked the moment it leaves draft (submitted/head_confirmed/confirmed).
    $formLocked = $approvalFlow === 'multi_stage'
        ? ($monthStatus && $monthStatus !== 'draft')
        : $isConfirmed;

    // Entity display name for breadcrumbs / headings
    $entityName = ($subsidiary ?? null)?->name ?? ($department ?? null)?->name ?? '—';
    $entityId   = ($subsidiary ?? null) ? null : ($department ?? null)?->id;
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">
            Record Actuals — {{ \App\Models\BudgetActual::MONTHS[$month] }} {{ $year }}
        </h5>
        <p class="text-muted small mb-0">
            <a href="{{ route('actuals.index', ['period_id'=>$period->id,'department_id'=>$department?->id]) }}"
               class="text-muted">Actuals</a>
            / {{ $entityName }} / {{ \App\Models\BudgetActual::MONTHS[$month] }}
        </p>
    </div>

    {{-- Month navigator + save status --}}
    <div class="d-flex gap-2 align-items-center">
        @if($month > 1)
        <a href="{{ route('actuals.entry', ['period_id'=>$period->id,'department_id'=>$department?->id,'subsidiary_id'=>$subsidiary?->id,'month'=>$month-1,'year'=>$year]) }}"
           class="btn btn-sm btn-outline-secondary">← Prev</a>
        @endif

        <span style="font-size:13px;font-weight:600;color:var(--navy)">
            {{ \App\Models\BudgetActual::MONTHS[$month] }} {{ $year }}
        </span>

        @if($month < 12)
        <a href="{{ route('actuals.entry', ['period_id'=>$period->id,'department_id'=>$department?->id,'subsidiary_id'=>$subsidiary?->id,'month'=>$month+1,'year'=>$year]) }}"
           class="btn btn-sm btn-outline-secondary">Next →</a>
        @endif

        @if(!$formLocked)
        <span id="save-status"
              style="font-size:11px;font-weight:600;padding:4px 14px;border-radius:20px;
                     border:1px solid transparent;transition:all .25s;min-width:130px;
                     text-align:center;display:inline-block;visibility:hidden">
        </span>
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
        @if($department)
        <a href="{{ route('supplementary.create', [
                'period_id'     => $period->id,
                'department_id' => $department->id,
            ]) }}"
           class="btn btn-sm"
           style="background:#991B1B;color:#fff;border-radius:8px;padding:8px 18px">
            <i class="fas fa-plus-circle"></i> Request Supplementary Budget
        </a>
        @endif
        <a href="{{ route('actuals.index', [
                'period_id'     => $period->id,
                'department_id' => $department?->id,
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
        @if($department)
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
        @endif
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

@if($monthStatus && $monthStatus !== 'draft')
@php
    $statusMeta = match($monthStatus) {
        'submitted'      => ['bg'=>'#FEF3C7','color'=>'#92400E','icon'=>'fa-paper-plane',
                             'msg'=>'Submitted for department head review. Entries are locked pending confirmation.'],
        'head_confirmed' => ['bg'=>'#DBEAFE','color'=>'#1E40AF','icon'=>'fa-user-check',
                             'msg'=>'Confirmed by department head. Awaiting Finance final approval.'],
        default          => ['bg'=>'#D1FAE5','color'=>'#065F46','icon'=>'fa-check-circle',
                             'msg'=>'Confirmed and locked. Contact Finance to reopen for amendments.'],
    };
@endphp
<div class="alert mb-4 d-flex align-items-center gap-2"
     style="background:{{ $statusMeta['bg'] }};color:{{ $statusMeta['color'] }};border:none;border-radius:10px">
    <i class="fas {{ $statusMeta['icon'] }}"></i>
    <span>{{ $statusMeta['msg'] }}</span>
</div>
@endif

{{-- Entry form --}}
<form method="POST" action="{{ route('actuals.store') }}" id="actualForm">
    @csrf
    <input type="hidden" name="period_id"     value="{{ $period->id }}">
    <input type="hidden" name="department_id" value="{{ $department?->id }}">
    @if($subsidiary ?? null)
    <input type="hidden" name="subsidiary_id" value="{{ $subsidiary->id }}">
    @endif
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

    {{-- Split by budget type --}}
    @php
        $pnlTypes          = ['revenue', 'expense', 'both'];
        $byCategoryPnl     = $byCategory->filter(fn($items) => in_array(
            $items->first()?->accountCode?->category?->budget_type ?? 'expense', $pnlTypes));
        $byCategoryCapex   = $byCategory->filter(fn($items) =>
            ($items->first()?->accountCode?->category?->budget_type ?? '') === 'capital_expenditure');
        $byCategoryBalance = $byCategory->filter(fn($items) => in_array(
            $items->first()?->accountCode?->category?->budget_type ?? '', ['assets', 'liabilities']));
        $actTabGroups = [
            ['id'=>'acttab-pnl',     'label'=>'Revenue &amp; Expenses',   'cats'=>$byCategoryPnl,     'active'=>true],
            ['id'=>'acttab-capex',   'label'=>'Capital Expenditure',      'cats'=>$byCategoryCapex,   'active'=>false],
            ['id'=>'acttab-balance', 'label'=>'Assets &amp; Liabilities', 'cats'=>$byCategoryBalance, 'active'=>false],
        ];
    @endphp

    {{-- Tab navigation --}}
    <ul class="nav nav-tabs mb-0" id="actTabs" role="tablist">
        @foreach($actTabGroups as $acttab)
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $acttab['active'] ? 'active' : '' }}"
                    data-bs-toggle="tab" data-bs-target="#{{ $acttab['id'] }}"
                    type="button" role="tab">
                {!! $acttab['label'] !!}
                @if($acttab['cats']->isNotEmpty())
                <span class="badge bg-secondary ms-1" style="font-size:10px">{{ $acttab['cats']->count() }}</span>
                @endif
            </button>
        </li>
        @endforeach
    </ul>

    <div class="tab-content border border-top-0 rounded-bottom mb-3" id="actTabsContent">
    @foreach($actTabGroups as $acttab)
    <div class="tab-pane fade {{ $acttab['active'] ? 'show active' : '' }} p-3"
         id="{{ $acttab['id'] }}" role="tabpanel">
        @forelse($acttab['cats'] as $catName => $items)
        <div class="chart-card mb-3">
            <div class="chart-title">{{ $catName }}</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--slate)">
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
                            $existing      = $existingActuals->get($item->id);
                            $ytd           = $ytdActuals->get($item->id, 0);
                            $supplementary = $item->approvedSupplementaryTotal();
                            $effective     = $item->effectiveBudget();
                            $remaining     = $effective - $ytd;
                            $idx           = $loop->parent->parent->index * 10000
                                           + $loop->parent->index * 100
                                           + $loop->index;
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
                            <td class="text-end small">{{ number_format($ytd, 2) }}</td>
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
                                       {{ $formLocked ? 'readonly' : '' }}
                                       oninput="updateTotals()">
                            </td>
                            <td>
                                <input type="text"
                                       name="actuals[{{ $idx }}][reference]"
                                       class="form-control form-control-sm"
                                       value="{{ $existing?->reference ?? '' }}"
                                       placeholder="Invoice/Voucher #"
                                       {{ $formLocked ? 'readonly' : '' }}>
                            </td>
                            <td>
                                <input type="text"
                                       name="actuals[{{ $idx }}][description]"
                                       class="form-control form-control-sm"
                                       value="{{ $existing?->description ?? '' }}"
                                       placeholder="Optional note"
                                       {{ $formLocked ? 'readonly' : '' }}>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    @php
                        $catTotal     = $items->sum(fn($i) => $i->effectiveBudget());
                        $catYtd       = $items->sum(fn($i) => $ytdActuals->get($i->id, 0));
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
        @empty
        <div class="text-center text-muted py-5">
            <i class="bi bi-inbox d-block mb-2" style="font-size:2rem;opacity:.3"></i>
            No items in this section yet.
        </div>
        @endforelse
    </div>
    @endforeach
    </div>

    {{-- Action buttons — content depends on flow setting + month status --}}
    <div class="d-flex gap-2 align-items-center flex-wrap mt-4">

        @if(!$formLocked)
        {{-- Save as Draft — always visible when form is editable --}}
        <button type="submit" id="save-btn" class="btn"
                style="background:var(--navy);color:#fff;border-radius:8px;padding:10px 24px">
            <i class="fas fa-save"></i> Save as Draft
        </button>
        @endif

        @if($approvalFlow === 'simple')
            {{-- ── Simple flow: one-step confirm ─────────────────────────────── --}}
            @if(!$isConfirmed)
            @can('confirm actuals')
            <button type="button" onclick="confirmMonth()"
                    class="btn"
                    style="background:#10B981;color:#fff;border-radius:8px;padding:10px 24px">
                <i class="fas fa-check-circle"></i> Save & Confirm Month
            </button>
            @endcan
            @endif

        @else
            {{-- ── Multi-stage approval flow ──────────────────────────────────── --}}

            @if(!$monthStatus || $monthStatus === 'draft')
                {{-- Step 1: dept user saves + submits atomically --}}
                @can('submit actuals')
                <button type="button" onclick="saveAndSubmitForApproval()"
                        class="btn"
                        style="background:#3B82F6;color:#fff;border-radius:8px;padding:10px 24px">
                    <i class="fas fa-paper-plane"></i> Save &amp; Submit for Approval
                </button>
                @endcan

            @elseif($monthStatus === 'submitted')
                {{-- Step 2: dept head confirms --}}
                @can('head confirm actuals')
                <button type="button" onclick="headConfirmMonth()"
                        class="btn"
                        style="background:#8B5CF6;color:#fff;border-radius:8px;padding:10px 24px">
                    <i class="fas fa-user-check"></i> Confirm (Head Approval)
                </button>
                @endcan

            @elseif($monthStatus === 'head_confirmed')
                {{-- Step 3: finance gives final approval --}}
                @can('approve actuals')
                <button type="button" onclick="finalApprove()"
                        class="btn"
                        style="background:#10B981;color:#fff;border-radius:8px;padding:10px 24px">
                    <i class="fas fa-check-double"></i> Final Approve
                </button>
                @endcan

            @elseif($monthStatus === 'confirmed')
                {{-- Reopen — Finance only --}}
                @can('reopen actuals')
                <button type="button" onclick="reopenMonth()"
                        class="btn"
                        style="background:#E65C00;color:#fff;border-radius:8px;padding:10px 24px">
                    <i class="fas fa-lock-open"></i> Reopen Month
                </button>
                @endcan
            @endif

        @endif

        <a href="{{ route('actuals.index', ['period_id'=>$period->id,'department_id'=>$department?->id]) }}"
           class="btn btn-outline-secondary"
           style="border-radius:8px;padding:10px 24px">
            <i class="fas fa-times"></i> Cancel
        </a>
    </div>

</form>

{{-- Hidden workflow forms (all flows) --}}
@php $wfBase = ['period_id'=>$period->id,'department_id'=>$department?->id,'subsidiary_id'=>($subsidiary??null)?->id,'month'=>$month,'year'=>$year]; @endphp

@can('confirm actuals')
<form method="POST" action="{{ route('actuals.confirm') }}" id="confirmForm">
    @csrf
    @foreach($wfBase as $k => $v) @if(!is_null($v)) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif @endforeach
</form>
@endcan

@can('submit actuals')
<form method="POST" action="{{ route('actuals.submit') }}" id="submitForm">
    @csrf
    @foreach($wfBase as $k => $v) @if(!is_null($v)) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif @endforeach
</form>
@endcan

@can('head confirm actuals')
<form method="POST" action="{{ route('actuals.head-confirm') }}" id="headConfirmForm">
    @csrf
    @foreach($wfBase as $k => $v) @if(!is_null($v)) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif @endforeach
</form>
@endcan

@can('approve actuals')
<form method="POST" action="{{ route('actuals.approve') }}" id="approveForm">
    @csrf
    @foreach($wfBase as $k => $v) @if(!is_null($v)) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif @endforeach
</form>
@endcan

@can('reopen actuals')
<form method="POST" action="{{ route('actuals.reopen') }}" id="reopenForm">
    @csrf
    @foreach($wfBase as $k => $v) @if(!is_null($v)) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif @endforeach
</form>
@endcan

<script>
const AUTOSAVE_URL  = "{{ route('actuals.autosave') }}";
const STORE_URL     = "{{ route('actuals.store') }}";
const CSRF          = "{{ csrf_token() }}";
const PERIOD_ID     = {{ $period->id }};
const DEPT_ID       = {{ $department?->id ?? 'null' }};
const SUBSIDIARY_ID = {{ ($subsidiary ?? null)?->id ?? 'null' }};
const MONTH         = {{ $month }};
const YEAR          = {{ $year }};
const IS_CONFIRMED  = {{ $formLocked ? 'true' : 'false' }};
const MONTH_STATUS  = "{{ $monthStatus ?? '' }}";
const APPROVAL_FLOW = "{{ $approvalFlow }}";

let autoSaveTimer = null;
let isSaving      = false;

/* ── Running totals ── */
function updateTotals() {
    let total = 0;
    document.querySelectorAll('.actual-input').forEach(inp => {
        total += parseFloat(inp.value) || 0;
    });
    document.getElementById('monthTotal').textContent =
        '{{ currency() }} ' + total.toLocaleString('en-GH', { minimumFractionDigits: 2 });
}

/* ── Save-status badge helpers ── */
function setStatus(state, text) {
    const el = document.getElementById('save-status');
    if (!el) return;
    el.innerHTML  = text;
    el.style.visibility = 'visible';
    // unsaved → amber  |  saving → blue  |  saved → green  |  error → red
    const styles = {
        unsaved: { bg: '#FEF3C7', border: '#F59E0B', color: '#92400E' },
        saving:  { bg: '#DBEAFE', border: '#3B82F6', color: '#1E40AF' },
        saved:   { bg: '#D1FAE5', border: '#10B981', color: '#065F46' },
        error:   { bg: '#FEE2E2', border: '#F43F5E', color: '#991B1B' },
    };
    const s = styles[state] || styles.saved;
    el.style.background   = s.bg;
    el.style.borderColor  = s.border;
    el.style.color        = s.color;
}

/* ── Autosave ── */
function scheduleAutoSave() {
    if (IS_CONFIRMED) return;
    clearTimeout(autoSaveTimer);
    setStatus('unsaved', '<i class="bi bi-circle-fill" style="font-size:8px;vertical-align:middle"></i> Unsaved changes');
    autoSaveTimer = setTimeout(saveActuals, 3000);
}

function collectActuals() {
    const rows = [];
    // Each hidden line_item_id input anchors one row's data
    document.querySelectorAll('input[name^="actuals["][name$="[line_item_id]"]').forEach(hiddenInput => {
        const prefix  = hiddenInput.name.replace('[line_item_id]', '');
        const amtInp  = document.querySelector(`input[name="${prefix}[amount]"]`);
        const refInp  = document.querySelector(`input[name="${prefix}[reference]"]`);
        const descInp = document.querySelector(`input[name="${prefix}[description]"]`);
        rows.push({
            line_item_id: hiddenInput.value,
            amount:       amtInp  ? (amtInp.value  === '' ? null : parseFloat(amtInp.value))  : null,
            reference:    refInp  ? refInp.value  : null,
            description:  descInp ? descInp.value : null,
        });
    });
    return rows;
}

async function saveActuals() {
    if (isSaving || IS_CONFIRMED) return;
    isSaving = true;

    const btn = document.getElementById('save-btn');
    if (btn) btn.disabled = true;
    setStatus('saving', '↻ Saving…');

    try {
        const res = await fetch(AUTOSAVE_URL, {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept':       'application/json',
            },
            body: JSON.stringify({
                period_id:     PERIOD_ID,
                department_id: DEPT_ID,
                subsidiary_id: SUBSIDIARY_ID,
                month:         MONTH,
                year:          YEAR,
                actuals:       collectActuals(),
            }),
        });

        if (!res.ok) {
            setStatus('error', '✕ Save failed (' + res.status + ')');
            return;
        }

        const data = await res.json();
        if (data.success) {
            setStatus('saved', '<i class="bi bi-check-circle-fill" style="vertical-align:middle"></i> Saved at ' + data.saved_at);

            // Non-blocking over-budget warning badge
            showOverBudgetWarning(data.over_budget_lines || []);
        } else {
            setStatus('error', '<i class="bi bi-x-circle-fill" style="vertical-align:middle"></i> Save failed');
        }
    } catch (e) {
        setStatus('error', '✕ Network error');
    } finally {
        isSaving = false;
        if (btn) btn.disabled = false;
    }
}

/**
 * Show or clear a non-blocking warning badge when autosaved drafts exceed budget.
 * Does not block saving — just informs the user so they can fix before confirming.
 */
function showOverBudgetWarning(lines) {
    let badge = document.getElementById('over-budget-badge');

    if (!lines || lines.length === 0) {
        if (badge) badge.remove();
        return;
    }

    if (!badge) {
        badge = document.createElement('div');
        badge.id = 'over-budget-badge';
        badge.style.cssText = [
            'position:fixed', 'bottom:24px', 'right:24px', 'z-index:1060',
            'background:#FEF3C7', 'border:1px solid #F59E0B', 'border-radius:8px',
            'padding:12px 16px', 'max-width:360px', 'box-shadow:0 4px 12px rgba(0,0,0,.15)',
            'font-size:13px', 'line-height:1.5',
        ].join(';');

        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '&times;';
        closeBtn.style.cssText = 'float:right;background:none;border:none;font-size:16px;cursor:pointer;margin:-4px -4px 0 8px;color:#92400E';
        closeBtn.onclick = () => badge.remove();
        badge.appendChild(closeBtn);

        document.body.appendChild(badge);
    }

    // Rebuild content (close button is preserved as first child)
    const closeBtn = badge.firstChild;
    badge.innerHTML = '';
    badge.appendChild(closeBtn);

    const title = document.createElement('strong');
    title.style.color = '#92400E';
    title.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i>' + lines.length + ' line(s) exceed budget';
    badge.appendChild(title);

    const list = document.createElement('ul');
    list.style.cssText = 'margin:6px 0 0;padding-left:16px';
    lines.forEach(l => {
        const li = document.createElement('li');
        li.textContent = l.code + ' ' + l.name + ': over by ' + Number(l.overrun).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
        list.appendChild(li);
    });
    badge.appendChild(list);

    const note = document.createElement('p');
    note.style.cssText = 'margin:6px 0 0;color:#78350F;font-size:12px';
    note.textContent = 'Draft saved. Confirmation will be blocked until resolved.';
    badge.appendChild(note);
}

/* ── Attach listeners ── */
document.addEventListener('DOMContentLoaded', function () {
    updateTotals();

    // Amount inputs — trigger totals + autosave
    document.querySelectorAll('.actual-input').forEach(inp => {
        inp.addEventListener('input', function () {
            updateTotals();
            scheduleAutoSave();
        });
    });

    // Reference and description inputs — autosave only
    document.querySelectorAll(
        'input[name$="[reference]"], input[name$="[description]"]'
    ).forEach(inp => {
        inp.addEventListener('input', scheduleAutoSave);
    });
});

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
                <div><i class="bi bi-check-circle-fill" style="color:#10B981"></i> <strong>${itemCount}</strong> line item(s) with amounts</div>
                <div style="margin-top:4px;"><i class="bi bi-bar-chart-fill" style="color:#10B981"></i> <strong>Total:</strong> {{ currency() }} ${totalAmount.toLocaleString('en-GH', { minimumFractionDigits: 2 })}</div>
            </div>
            <div style="background:#FEF3C7;border-radius:8px;padding:12px;
                        text-align:left;font-size:13px;color:#92400E;margin-bottom:12px">
                <div><i class="bi bi-exclamation-triangle-fill"></i> Confirmed actuals are <strong>locked</strong> and cannot be edited without Finance approval.</div>
                <div style="margin-top:4px;"><i class="bi bi-exclamation-triangle-fill"></i> Only proceed if all entries are correct.</div>
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

            // Step 1: Save as draft via JSON fetch (no page navigation).
            // On success, Step 2 submits confirmForm to lock the month.
            // On over-budget 422, surface the blocked lines in a Swal error.
            fetch(STORE_URL, {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept':       'application/json',
                },
                body: JSON.stringify({
                    period_id:     PERIOD_ID,
                    department_id: DEPT_ID,
                    subsidiary_id: SUBSIDIARY_ID,
                    month:         MONTH,
                    year:          YEAR,
                    actuals:       collectActuals(),
                }),
            })
            .then(async res => {
                const data = await res.json();

                if (res.status === 422 && data.status === 'over_budget') {
                    // Build a readable list of blocked lines
                    const rows = (data.items || []).map(it =>
                        `<li><strong>${it.code}</strong> ${it.name}: ` +
                        `budget {{ currency() }} ${Number(it.budget).toLocaleString('en-GH',{minimumFractionDigits:2})} · ` +
                        `projected {{ currency() }} ${Number(it.projected_total).toLocaleString('en-GH',{minimumFractionDigits:2})} ` +
                        `<span style="color:#F43F5E">(+${Number(it.overrun).toLocaleString('en-GH',{minimumFractionDigits:2})})</span></li>`
                    ).join('');

                    Swal.fire({
                        title: 'Confirmation Blocked',
                        html: `
                            <p style="color:#64748B;margin-bottom:12px">
                                ${data.items.length} expense line(s) would exceed the approved budget.
                                Request a supplementary budget for the affected lines before confirming.
                            </p>
                            <ul style="text-align:left;font-size:13px;padding-left:18px;color:#1E293B">${rows}</ul>
                        `,
                        icon: 'error',
                        confirmButtonColor: '#1B2A4A',
                        confirmButtonText: 'OK, I\'ll Review',
                    });
                    return;
                }

                if (!res.ok) {
                    Swal.fire({
                        title: 'Save Failed',
                        text: data.message || 'An unexpected error occurred. Please try again.',
                        icon: 'error',
                        confirmButtonColor: '#1B2A4A',
                    });
                    return;
                }

                // Step 2: Draft saved successfully — now confirm (lock) the month.
                document.getElementById('confirmForm').submit();
            })
            .catch(() => {
                Swal.fire({
                    title: 'Network Error',
                    text: 'Could not reach the server. Check your connection and try again.',
                    icon: 'error',
                    confirmButtonColor: '#1B2A4A',
                });
            });
        }
    });
}

/* ── Multi-stage workflow actions ─────────────────────────────────────────── */

function saveAndSubmitForApproval() {
    Swal.fire({
        title: 'Save & Submit for Approval?',
        html: `<p style="color:#64748B;font-size:14px">
            Your <strong>{{ \App\Models\BudgetActual::MONTHS[$month] }} {{ $year }}</strong>
            entries will be <strong>saved then sent to your department head</strong> for review.
            You will <strong>not</strong> be able to edit them until the head or Finance acts on them.</p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3B82F6',
        confirmButtonText: 'Yes, Save & Submit',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then(result => {
        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'Saving…',
            html: 'Saving entries then submitting for approval.',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading(),
        });

        fetch(STORE_URL, {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept':       'application/json',
            },
            body: JSON.stringify({
                period_id:     PERIOD_ID,
                department_id: DEPT_ID,
                subsidiary_id: SUBSIDIARY_ID,
                month:         MONTH,
                year:          YEAR,
                actuals:       collectActuals(),
            }),
        })
        .then(async res => {
            const data = await res.json();

            if (res.status === 422 && data.status === 'over_budget') {
                const rows = (data.items || []).map(it =>
                    `<li><strong>${it.code}</strong> ${it.name}: budget {{ currency() }} ${Number(it.budget).toLocaleString('en-GH',{minimumFractionDigits:2})} · projected {{ currency() }} ${Number(it.projected_total).toLocaleString('en-GH',{minimumFractionDigits:2})} <span style="color:#F43F5E">(+${Number(it.overrun).toLocaleString('en-GH',{minimumFractionDigits:2})})</span></li>`
                ).join('');
                Swal.fire({
                    title: 'Submission Blocked — Over Budget',
                    html: `<p style="color:#64748B;margin-bottom:12px">${data.items.length} expense line(s) exceed the approved budget. Fix the amounts or request a supplementary budget before submitting.</p><ul style="text-align:left;font-size:13px;padding-left:18px;color:#1E293B">${rows}</ul>`,
                    icon: 'error',
                    confirmButtonColor: '#1B2A4A',
                    confirmButtonText: 'OK, I\'ll Review',
                });
                return;
            }

            if (!res.ok) {
                Swal.fire({ title: 'Save Failed', text: data.message || 'Unexpected error.', icon: 'error', confirmButtonColor: '#1B2A4A' });
                return;
            }

            // Entries saved — now submit for approval
            document.getElementById('submitForm').submit();
        })
        .catch(() => {
            Swal.fire({ title: 'Network Error', text: 'Could not reach the server. Check your connection and try again.', icon: 'error', confirmButtonColor: '#1B2A4A' });
        });
    });
}

function headConfirmMonth() {
    Swal.fire({
        title: 'Confirm & Forward to Finance?',
        html: `<p style="color:#64748B;font-size:14px">
            You are confirming that the <strong>{{ \App\Models\BudgetActual::MONTHS[$month] }} {{ $year }}</strong>
            entries are correct and forwarding them to Finance for final approval.</p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#8B5CF6',
        confirmButtonText: 'Yes, Confirm',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then(result => {
        if (result.isConfirmed) document.getElementById('headConfirmForm').submit();
    });
}

function finalApprove() {
    Swal.fire({
        title: 'Final Approval?',
        html: `<p style="color:#64748B;font-size:14px">
            You are giving Finance's final approval for
            <strong>{{ \App\Models\BudgetActual::MONTHS[$month] }} {{ $year }}</strong>.
            The month will be <strong>locked</strong> and counted in all reports.</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#10B981',
        confirmButtonText: 'Yes, Approve & Lock',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then(result => {
        if (result.isConfirmed) document.getElementById('approveForm').submit();
    });
}

function reopenMonth() {
    Swal.fire({
        title: 'Reopen Month?',
        html: `<p style="color:#64748B;font-size:14px">
            This will <strong>unlock</strong>
            <strong>{{ \App\Models\BudgetActual::MONTHS[$month] }} {{ $year }}</strong>
            and reset all entries to Draft so the department can edit and resubmit.</p>
            <p style="color:#92400E;font-size:13px;margin-top:8px">
            The month will be removed from confirmed totals until re-approved.</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#E65C00',
        confirmButtonText: 'Yes, Reopen',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    }).then(result => {
        if (result.isConfirmed) document.getElementById('reopenForm').submit();
    });
}

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
