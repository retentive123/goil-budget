@extends('layouts.app')
@section('title', 'Review Budget — P&L View')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('approvals.index') }}" class="text-muted text-decoration-none">Approvals</a>
        <span class="text-muted">/</span>
        <span>{{ $budgetVersion->department->name }} — v{{ $budgetVersion->version_number }}</span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <div class="btn-group btn-group-sm" role="group">
            <a href="{{ route('approvals.show', $budgetVersion) }}" class="btn btn-outline-secondary">Classic View</a>
            <span class="btn btn-secondary" style="pointer-events:none;">P&amp;L View</span>
        </div>
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-download"></i> Export
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="{{ route('ie.budget.export', $budgetVersion) }}">
                    <i class="bi bi-file-earmark-excel me-1"></i> Classic Export (.xlsx)
                </a></li>
                <li><a class="dropdown-item" href="{{ route('ie.budget.export-pnl', $budgetVersion) }}">
                    <i class="bi bi-file-earmark-excel me-1"></i> P&amp;L Export (.xlsx)
                </a></li>
            </ul>
        </div>
    </div>
</div>

<div class="row g-4">

    {{-- Left: P&L table --}}
    <div class="col-lg-9">

        {{-- Info bar --}}
        <div class="card shadow-sm mb-3">
            <div class="card-body py-2">
                <div class="row text-center">
                    <div class="col border-end">
                        <div class="small text-muted">Department</div>
                        <div class="fw-semibold small">{{ $budgetVersion->department->name }}</div>
                    </div>
                    <div class="col border-end">
                        <div class="small text-muted">Period</div>
                        <div class="fw-semibold small">{{ $budgetVersion->period->name }}</div>
                    </div>
                    <div class="col border-end">
                        <div class="small text-muted">Version</div>
                        <div class="fw-semibold small">v{{ $budgetVersion->version_number }}</div>
                    </div>
                    <div class="col border-end">
                        <div class="small text-muted">Status</div>
                        <div>
                            <span class="badge bg-{{ match($budgetVersion->status) {
                                'draft'        => 'secondary',
                                'submitted'    => 'primary',
                                'under_review' => 'warning',
                                'approved'     => 'success',
                                'rejected'     => 'danger',
                                default        => 'secondary'
                            } }}">{{ ucfirst(str_replace('_',' ',$budgetVersion->status)) }}</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="small text-muted">Submitted by</div>
                        <div class="fw-semibold small">{{ $budgetVersion->submittedBy?->name ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Submission notes --}}
        @if($budgetVersion->submission_notes)
        <div class="alert alert-light border small mb-3">
            <strong>Submission notes:</strong> {{ $budgetVersion->submission_notes }}
        </div>
        @endif

        {{-- Summary bar --}}
        <div class="card mb-3 border-0" style="background:var(--navy)">
            <div class="card-body py-2">
                <div class="row text-center text-white">
                    <div class="col">
                        <div class="small" style="color:rgba(255,255,255,.5)">Revenue Budget</div>
                        <div class="fw-bold" id="bar-rev">
                            {{ currency() }} {{ number_format($pnlData['revenue']['totals']['effective'], 2) }}
                        </div>
                    </div>
                    <div class="col border-start border-secondary">
                        <div class="small" style="color:rgba(255,255,255,.5)">Expense Budget</div>
                        <div class="fw-bold" id="bar-exp">
                            {{ currency() }} {{ number_format($pnlData['expense']['totals']['effective'], 2) }}
                        </div>
                    </div>
                    <div class="col border-start border-secondary">
                        <div class="small" style="color:rgba(255,255,255,.5)">Net Income</div>
                        @php $net = $pnlData['revenue']['totals']['effective'] - $pnlData['expense']['totals']['effective']; @endphp
                        <div class="fw-bold fs-5" style="color:{{ $net >= 0 ? '#6EE7B7' : '#FCA5A5' }}">
                            {{ currency() }} {{ number_format($net, 2) }}
                        </div>
                    </div>
                    @if($prevPeriod)
                    <div class="col border-start border-secondary">
                        <div class="small" style="color:rgba(255,255,255,.5)">Prev Net ({{ $prevPeriod->year }})</div>
                        @php $prevNet = $pnlData['revenue']['totals']['prev_actual'] - $pnlData['expense']['totals']['prev_actual']; @endphp
                        <div class="fw-bold" style="color:rgba(255,255,255,.7)">
                            {{ currency() }} {{ number_format($prevNet, 2) }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Split non-PnL items --}}
        @php
            $apnlCapex   = array_filter($summary, fn($d) =>
                ($d['items']->first()?->accountCode?->category?->budget_type ?? '') === 'capital_expenditure');
            $apnlBalance = array_filter($summary, fn($d) => in_array(
                $d['items']->first()?->accountCode?->category?->budget_type ?? '', ['assets', 'liabilities']));
        @endphp

        {{-- Tab navigation --}}
        <ul class="nav nav-tabs mb-0" id="aprvPnlTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#aptab-is" type="button">
                    Income Statement
                </button>
            </li>
            @if(!empty($apnlCapex))
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#aptab-capex" type="button">
                    Capital Expenditure
                    <span class="badge bg-secondary ms-1" style="font-size:10px">{{ count($apnlCapex) }}</span>
                </button>
            </li>
            @endif
            @if(!empty($apnlBalance))
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#aptab-balance" type="button">
                    Assets &amp; Liabilities
                    <span class="badge bg-secondary ms-1" style="font-size:10px">{{ count($apnlBalance) }}</span>
                </button>
            </li>
            @endif
        </ul>

        <div class="tab-content border border-top-0 rounded-bottom" id="aprvPnlTabsContent">

        {{-- Tab 1: Income Statement --}}
        <div class="tab-pane fade show active p-0" id="aptab-is" role="tabpanel">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                <span class="fw-semibold small text-uppercase">Income Statement — {{ $budgetVersion->period->name }}</span>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary" onclick="expandAll()">Expand All</button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="collapseAll()">Collapse All</button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0" id="pnl-table">
                        <thead>
                            <tr style="background:#1B2A4A;color:#fff;font-size:12px">
                                <th rowspan="2" style="min-width:220px;vertical-align:middle">Account</th>
                                <th colspan="6" class="text-center border-start border-secondary py-2">
                                    {{ $budgetVersion->period->year }} Budget
                                </th>
                                @if($prevPeriod)
                                <th colspan="3" class="text-center border-start border-secondary py-2">
                                    {{ $prevPeriod->year }} Reference
                                </th>
                                @endif
                            </tr>
                            <tr style="background:#243B55;color:#CBD5E1;font-size:11px">
                                <th class="text-end border-start border-secondary" style="min-width:90px">Q1</th>
                                <th class="text-end" style="min-width:90px">Q2</th>
                                <th class="text-end" style="min-width:90px">Q3</th>
                                <th class="text-end" style="min-width:90px">Q4</th>
                                <th class="text-end" style="min-width:110px">Total</th>
                                <th class="text-end" style="min-width:60px">CS&nbsp;%</th>
                                @if($prevPeriod)
                                <th class="text-end border-start border-secondary" style="min-width:100px">Prev Budget</th>
                                <th class="text-end" style="min-width:100px">Prev Actual</th>
                                <th class="text-end" style="min-width:70px">Growth&nbsp;%</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody id="pnl-tbody">
                            <tr><td colspan="20" class="text-center text-muted py-4">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        </div>

        {{-- Tab 2: Capital Expenditure --}}
        @if(!empty($apnlCapex))
        <div class="tab-pane fade p-3" id="aptab-capex" role="tabpanel">
            @foreach($apnlCapex as $categoryName => $categoryData)
            @php
                $catSuppTotal      = $categoryData['items']->sum(fn($i) => $i->approvedSupplementaryTotal());
                $catEffectiveTotal = $categoryData['items']->sum(fn($i) => $i->effectiveBudget());
            @endphp
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light py-2 d-flex justify-content-between">
                    <span class="fw-semibold small text-uppercase">{{ $categoryName }}</span>
                    <span class="small text-muted">Total: <strong>{{ currency() }} {{ number_format($catEffectiveTotal, 2) }}</strong></span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:30%">Account</th>
                                <th class="text-end">Q1</th><th class="text-end">Q2</th>
                                <th class="text-end">Q3</th><th class="text-end">Q4</th>
                                <th class="text-end">Supplementary</th><th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categoryData['items'] as $item)
                            @php $itemSupp = $item->approvedSupplementaryTotal(); @endphp
                            <tr>
                                <td class="small"><code>{{ $item->accountCode->code }}</code> {{ $item->accountCode->name }}</td>
                                <td class="text-end small">{{ number_format($item->q1_amount, 2) }}</td>
                                <td class="text-end small">{{ number_format($item->q2_amount, 2) }}</td>
                                <td class="text-end small">{{ number_format($item->q3_amount, 2) }}</td>
                                <td class="text-end small">{{ number_format($item->q4_amount, 2) }}</td>
                                <td class="text-end small" style="color:{{ $itemSupp > 0 ? '#10B981' : 'inherit' }}">
                                    {{ $itemSupp > 0 ? '+'.number_format($itemSupp, 2) : '—' }}
                                </td>
                                <td class="text-end small fw-semibold" style="color:var(--navy)">
                                    {{ number_format($item->effectiveBudget(), 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot style="background:#F8FAFC;font-weight:700;font-size:11px">
                            <tr>
                                <td>Category Total</td>
                                <td class="text-end">{{ number_format($categoryData['items']->sum('q1_amount'), 2) }}</td>
                                <td class="text-end">{{ number_format($categoryData['items']->sum('q2_amount'), 2) }}</td>
                                <td class="text-end">{{ number_format($categoryData['items']->sum('q3_amount'), 2) }}</td>
                                <td class="text-end">{{ number_format($categoryData['items']->sum('q4_amount'), 2) }}</td>
                                <td class="text-end" style="color:{{ $catSuppTotal > 0 ? '#10B981' : 'inherit' }}">
                                    {{ $catSuppTotal > 0 ? '+'.number_format($catSuppTotal, 2) : '—' }}
                                </td>
                                <td class="text-end" style="color:var(--navy)">{{ currency() }} {{ number_format($catEffectiveTotal, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Tab 3: Assets & Liabilities --}}
        @if(!empty($apnlBalance))
        <div class="tab-pane fade p-3" id="aptab-balance" role="tabpanel">
            @foreach($apnlBalance as $categoryName => $categoryData)
            @php
                $catSuppTotal      = $categoryData['items']->sum(fn($i) => $i->approvedSupplementaryTotal());
                $catEffectiveTotal = $categoryData['items']->sum(fn($i) => $i->effectiveBudget());
            @endphp
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light py-2 d-flex justify-content-between">
                    <span class="fw-semibold small text-uppercase">{{ $categoryName }}</span>
                    <span class="small text-muted">Total: <strong>{{ currency() }} {{ number_format($catEffectiveTotal, 2) }}</strong></span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:30%">Account</th>
                                <th class="text-end">Q1</th><th class="text-end">Q2</th>
                                <th class="text-end">Q3</th><th class="text-end">Q4</th>
                                <th class="text-end">Supplementary</th><th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categoryData['items'] as $item)
                            @php $itemSupp = $item->approvedSupplementaryTotal(); @endphp
                            <tr>
                                <td class="small"><code>{{ $item->accountCode->code }}</code> {{ $item->accountCode->name }}</td>
                                <td class="text-end small">{{ number_format($item->q1_amount, 2) }}</td>
                                <td class="text-end small">{{ number_format($item->q2_amount, 2) }}</td>
                                <td class="text-end small">{{ number_format($item->q3_amount, 2) }}</td>
                                <td class="text-end small">{{ number_format($item->q4_amount, 2) }}</td>
                                <td class="text-end small" style="color:{{ $itemSupp > 0 ? '#10B981' : 'inherit' }}">
                                    {{ $itemSupp > 0 ? '+'.number_format($itemSupp, 2) : '—' }}
                                </td>
                                <td class="text-end small fw-semibold" style="color:var(--navy)">
                                    {{ number_format($item->effectiveBudget(), 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot style="background:#F8FAFC;font-weight:700;font-size:11px">
                            <tr>
                                <td>Category Total</td>
                                <td class="text-end">{{ number_format($categoryData['items']->sum('q1_amount'), 2) }}</td>
                                <td class="text-end">{{ number_format($categoryData['items']->sum('q2_amount'), 2) }}</td>
                                <td class="text-end">{{ number_format($categoryData['items']->sum('q3_amount'), 2) }}</td>
                                <td class="text-end">{{ number_format($categoryData['items']->sum('q4_amount'), 2) }}</td>
                                <td class="text-end" style="color:{{ $catSuppTotal > 0 ? '#10B981' : 'inherit' }}">
                                    {{ $catSuppTotal > 0 ? '+'.number_format($catSuppTotal, 2) : '—' }}
                                </td>
                                <td class="text-end" style="color:var(--navy)">{{ currency() }} {{ number_format($catEffectiveTotal, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        </div>{{-- end tab-content --}}

    </div>

    {{-- Right: approval progress + decision form --}}
    <div class="col-lg-3">

        {{-- Approval progress --}}
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Approval Progress</h6>
                @foreach($progress as $step)
                <div class="d-flex align-items-start gap-2 mb-3">
                    <div class="mt-1">
                        @if($step['status'] === 'approved')
                            <span class="text-success">✔</span>
                        @elseif($step['status'] === 'rejected')
                            <span class="text-danger">✘</span>
                        @elseif($step['status'] === 'pending')
                            <span class="text-warning">●</span>
                        @else
                            <span class="text-muted">○</span>
                        @endif
                    </div>
                    <div class="flex-grow-1">
                        <div class="small fw-semibold d-flex align-items-center gap-2">
                            {{ $step['stage']->name }}
                            @if(!$step['is_active'])
                            <span style="background:#F1F5F9;color:#94A3B8;font-size:10px;
                                        border-radius:4px;padding:1px 6px;font-weight:400">
                                Inactive
                            </span>
                            @endif
                        </div>
                        @if($step['decision'])
                            <div class="small text-muted">
                                {{ $step['decision']->decidedBy->name }}
                                &middot; {{ $step['decision']->decided_at->format('d M Y H:i') }}
                            </div>
                            @if($step['decision']->comments)
                            <div class="small text-muted fst-italic mt-1">
                                "{{ $step['decision']->comments }}"
                            </div>
                            @endif
                        @elseif($step['status'] === 'pending')
                            <div class="small text-warning">Awaiting decision</div>
                        @elseif(!$step['is_active'])
                            <div class="small text-muted">Skipped (stage inactive)</div>
                        @else
                            <div class="small text-muted">Not yet reached</div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Decision Form --}}
        @if($canDecide)
            @php $roleConfig = $approvalService->currentRoleConfig($budgetVersion) ?? null; @endphp
            <div class="card border-0 shadow-lg mb-4" style="border-radius:16px;overflow:hidden;border-top:4px solid #E65C00">
                <div class="card-header border-0 px-4 py-3" style="background:#E65C00;color:#fff">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center justify-content-center rounded-circle"
                                style="width:40px;height:40px;background:rgba(255,255,255,.12);font-size:18px">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div>
                                <div style="font-size:14px;font-weight:700">
                                    Your Decision — {{ $currentStage->name }}
                                </div>
                                @if($roleConfig)
                                <div style="font-size:11px;color:rgba(255,255,255,.7);margin-top:2px">
                                    <i class="bi bi-{{ $roleConfig->scope === 'all' ? 'globe2' : 'building' }}"></i>
                                    Scope: {{ $roleConfig->scope === 'all' ? 'All departments' : 'Own department only' }}
                                    @if($roleConfig->can_partial_approve)
                                        · <i class="bi bi-check2-square"></i> Partial approval enabled
                                    @endif
                                    @if($roleConfig->can_reduce_amounts)
                                        · <i class="bi bi-dash-circle"></i> Can reduce amounts
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                        <span class="badge px-3 py-2" style="background:rgba(255,255,255,.15);color:#fff;font-size:11px;border-radius:20px">
                            <i class="bi bi-clock"></i> Pending Review
                        </span>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form id="decision-form" method="POST" action="{{ route('approvals.decide', $budgetVersion) }}">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label fw-semibold" style="color:#1B2A4A;font-size:13px">
                                <i class="bi bi-check2-circle" style="color:#E65C00"></i> Decision
                            </label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input type="radio" name="decision" value="approved"
                                        id="dec-approve" class="form-check-input" required
                                        style="border-color:#10B981">
                                    <label for="dec-approve" class="form-check-label fw-semibold" style="color:#10B981">
                                        <i class="bi bi-check-circle"></i> Approve
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" name="decision" value="rejected"
                                        id="dec-reject" class="form-check-input"
                                        style="border-color:#F43F5E">
                                    <label for="dec-reject" class="form-check-label fw-semibold" style="color:#F43F5E">
                                        <i class="bi bi-x-circle"></i> Reject
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold" style="color:#1B2A4A;font-size:13px">
                                <i class="bi bi-chat-dots" style="color:#E65C00"></i> Comments
                                <span class="text-danger" id="comments-required" style="display:none">
                                    (required for rejection)
                                </span>
                            </label>
                            <textarea name="comments" rows="4"
                                    class="form-control @error('comments') is-invalid @enderror"
                                    style="border-radius:10px;border-color:#E2E8F0;padding:12px;resize:vertical"
                                    placeholder="Add comments…">{{ old('comments') }}</textarea>
                            @error('comments')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if($roleConfig?->can_partial_approve)
                        <div class="mb-4">
                            <div style="font-size:12px;font-weight:600;color:#1B2A4A;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">
                                <i class="bi bi-list-ul" style="color:#E65C00"></i> Line Item Decisions
                                <span style="font-weight:400;text-transform:none;color:#94A3B8;font-size:11px">
                                    (optional — leave blank to apply overall decision)
                                </span>
                            </div>
                            <div style="max-height:400px;overflow-y:auto;border-radius:10px;border:1px solid #E2E8F0">
                                @foreach($summary as $catName => $catData)
                                <div style="background:#F8FAFC;padding:8px 16px;font-size:11px;font-weight:700;color:#1B2A4A;text-transform:uppercase;border-bottom:1px solid #E2E8F0">
                                    {{ $catName }}
                                </div>
                                @foreach($catData['items'] as $item)
                                @php $itemEffective = $item->effectiveBudget(); @endphp
                                <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom" style="font-size:12px;background:#fff">
                                    <div style="flex:1;font-family:monospace;font-weight:600;color:#1B2A4A">
                                        {{ $item->accountCode->code }}
                                    </div>
                                    <div style="flex:2;color:#475569;font-size:11px">
                                        {{ $item->accountCode->name }}
                                    </div>
                                    <div style="font-weight:600;min-width:90px;text-align:right;color:#1B2A4A">
                                        GHS {{ number_format($itemEffective, 2) }}
                                    </div>
                                    <select name="line_items[{{ $item->id }}][status]"
                                            class="form-select form-select-sm li-decision"
                                            form="decision-form"
                                            data-item="{{ $item->id }}"
                                            style="max-width:110px;border-radius:6px;font-size:11px">
                                        <option value="">—</option>
                                        <option value="approved">✅ Approve</option>
                                        @if($roleConfig?->can_reduce_amounts)
                                        <option value="reduced">📉 Reduce</option>
                                        @endif
                                        <option value="rejected">❌ Reject</option>
                                    </select>
                                    @if($roleConfig?->can_reduce_amounts)
                                    <input type="number"
                                        name="line_items[{{ $item->id }}][approved_amount]"
                                        class="form-control form-control-sm li-amount"
                                        form="decision-form"
                                        id="amount-{{ $item->id }}"
                                        placeholder="New GHS"
                                        min="0" step="0.01"
                                        style="max-width:110px;border-radius:6px;display:none">
                                    @endif
                                </div>
                                @endforeach
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <button type="submit" class="btn w-100 py-2 fw-semibold"
                                style="background:#E65C00;color:#fff;border-radius:10px;border:none;font-size:14px">
                            <i class="bi bi-send"></i> Submit Decision
                        </button>
                    </form>
                </div>
            </div>

            @if($roleConfig?->can_partial_approve && $roleConfig?->can_reduce_amounts)
            <script>
                document.querySelectorAll('.li-decision').forEach(select => {
                    select.addEventListener('change', function() {
                        const amountInput = document.getElementById('amount-' + this.dataset.item);
                        if (amountInput) amountInput.style.display = this.value === 'reduced' ? 'inline-block' : 'none';
                    });
                });
            </script>
            @endif

        @else
            <div class="card border-0 shadow-sm mb-4" style="border-radius:16px;border:2px solid #E2E8F0;background:#F8FAFC">
                <div class="card-body p-4 text-center">
                    <div style="font-size:42px;margin-bottom:12px">🔒</div>
                    <div style="font-size:15px;font-weight:700;color:#1B2A4A;margin-bottom:4px">
                        You cannot action this budget
                    </div>
                    <div style="font-size:13px;color:#64748B;margin-bottom:16px">
                        {{ $currentStage?->name ?? 'No active stage' }}
                    </div>
                    @php
                        $version    = $budgetVersion;
                        $isSelf     = \App\Services\SegregationService::enabled()
                            && $version->submitted_by === auth()->id();
                        $wrongRole  = !auth()->user()->hasRole($currentStage?->role_name ?? '');
                        $wrongScope = false;
                        if ($currentStage) {
                            $role = \Spatie\Permission\Models\Role::where('name', $currentStage->role_name)->first();
                            if ($role && $role->scope === 'own') {
                                $wrongScope = auth()->user()->department_id !== $version->department_id;
                            }
                        }
                    @endphp
                    <div class="text-start">
                        @if($isSelf)
                        <div class="d-flex align-items-start gap-2 p-3 rounded-3 mb-2" style="background:#FEE2E2;border-left:4px solid #F43F5E">
                            <i class="bi bi-shield-exclamation" style="color:#991B1B;font-size:18px"></i>
                            <div>
                                <div style="font-weight:600;color:#991B1B;font-size:13px">Segregation of Duties</div>
                                <div style="font-size:12px;color:#7F1D1D">You submitted this budget. A different user must approve it.</div>
                            </div>
                        </div>
                        @endif
                        @if($wrongRole)
                        <div class="d-flex align-items-start gap-2 p-3 rounded-3 mb-2" style="background:#FEF3C7;border-left:4px solid #F59E0B">
                            <i class="bi bi-exclamation-triangle" style="color:#92400E;font-size:18px"></i>
                            <div>
                                <div style="font-weight:600;color:#92400E;font-size:13px">Role Mismatch</div>
                                <div style="font-size:12px;color:#78350F">Your role does not match the current approval stage ({{ $currentStage?->name }}).</div>
                            </div>
                        </div>
                        @endif
                        @if($wrongScope)
                        <div class="d-flex align-items-start gap-2 p-3 rounded-3" style="background:#FEF3C7;border-left:4px solid #F59E0B">
                            <i class="bi bi-building" style="color:#92400E;font-size:18px"></i>
                            <div>
                                <div style="font-weight:600;color:#92400E;font-size:13px">Department Scope</div>
                                <div style="font-size:12px;color:#78350F">This budget belongs to a different department and your role only covers your own department.</div>
                            </div>
                        </div>
                        @endif
                        @if(!$isSelf && !$wrongRole && !$wrongScope)
                        <div class="d-flex align-items-start gap-2 p-3 rounded-3" style="background:#F1F5F9;border-left:4px solid #94A3B8">
                            <i class="bi bi-info-circle" style="color:#475569;font-size:18px"></i>
                            <div>
                                <div style="font-weight:600;color:#475569;font-size:13px">Not Actionable</div>
                                <div style="font-size:12px;color:#64748B">This budget is not at a stage you can action, or has already been processed.</div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>

<script>
document.querySelectorAll('input[name="decision"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('comments-required').style.display =
            this.value === 'rejected' ? 'inline' : 'none';
    });
});
</script>

@push('scripts')
<script>
const PNL      = @json($pnlData);
const HAS_PREV = {{ $prevPeriod ? 'true' : 'false' }};
const EDITABLE = false;
const CUR      = "{{ currency() }}";

function numFmt(v) {
    return parseFloat(v || 0).toLocaleString('en-GH', {minimumFractionDigits:2, maximumFractionDigits:2});
}

function escHtml(s) {
    const d = document.createElement('div');
    d.textContent = s || '';
    return d.innerHTML;
}

function growthHtml(type, current, prev) {
    if (!prev) return '<span class="text-muted">—</span>';
    const g = ((current - prev) / Math.abs(prev)) * 100;
    const isGood = type === 'revenue' ? g >= 0 : g <= 0;
    const color  = isGood ? '#10B981' : '#F43F5E';
    const sign   = g >= 0 ? '+' : '';
    return `<span style="color:${color}">${sign}${g.toFixed(1)}%</span>`;
}

function renderPnl() {
    let html = '';
    html += renderSection('revenue', 'REVENUE INCOME');
    html += '<tr style="height:6px;background:#F8FAFC"><td colspan="99"></td></tr>';
    html += renderSection('expense', 'OPERATING EXPENSES');
    html += renderNetRow();
    document.getElementById('pnl-tbody').innerHTML = html;
}

function renderSection(type, label) {
    let html = '';
    const sec   = PNL[type];
    const bg    = type === 'revenue' ? '#1B2A4A' : '#7C2D12';
    const light = type === 'revenue' ? '#EFF3F9' : '#FFF7ED';

    html += `<tr style="background:${bg};color:#fff;">
        <td colspan="99" style="font-size:11px;font-weight:700;letter-spacing:1px;padding:8px 12px">
            ${label}
        </td>
    </tr>`;

    sec.categories.forEach((cat, catIdx) => {
        const catId     = `${type}_${catIdx}`;
        const collapsed = cat.items.length > 8;

        html += `<tr class="pnl-cat-row" id="crow_${catId}" data-type="${type}"
                     style="background:${light};cursor:pointer;font-size:12px;font-weight:600"
                     onclick="toggleCat('${catId}')">
            <td style="padding-left:14px">
                <i class="fas fa-chevron-${collapsed ? 'right' : 'down'} me-2 small" id="icon_${catId}"></i>
                ${escHtml(cat.name)}
                <span class="text-muted fw-normal ms-1" style="font-size:11px">(${cat.items.length})</span>
            </td>
            <td class="text-end border-start" id="cq1_${catId}">${numFmt(cat.totals.q1)}</td>
            <td class="text-end" id="cq2_${catId}">${numFmt(cat.totals.q2)}</td>
            <td class="text-end" id="cq3_${catId}">${numFmt(cat.totals.q3)}</td>
            <td class="text-end" id="cq4_${catId}">${numFmt(cat.totals.q4)}</td>
            <td class="text-end fw-bold" id="ceff_${catId}">${numFmt(cat.totals.effective)}</td>
            <td class="text-end text-muted" id="ccs_${catId}">${numFmt(cat.totals.common_size)}%</td>
            ${HAS_PREV ? `
            <td class="text-end border-start text-muted" id="cpb_${catId}">${numFmt(cat.totals.prev_budget)}</td>
            <td class="text-end text-muted" id="cpa_${catId}">${numFmt(cat.totals.prev_actual)}</td>
            <td class="text-end" id="cgr_${catId}">${growthHtml(type, cat.totals.effective, cat.totals.prev_actual)}</td>
            ` : ''}
        </tr>`;

        cat.items.forEach(item => {
            const suppBadge = item.supp > 0
                ? `<span class="badge ms-1" style="background:#D1FAE5;color:#065F46;font-size:10px">+${numFmt(item.supp)} supp</span>`
                : '';

            html += `<tr class="pnl-item-row ${collapsed ? 'd-none' : ''}" style="font-size:12px"
                        data-item-id="${item.id}" data-cat-id="${catId}" data-type="${type}">
                <td style="padding-left:2.2rem" class="small">
                    <code class="text-muted me-1" style="font-size:11px">${escHtml(item.code)}</code>${escHtml(item.name)}${suppBadge}
                    ${item.justification ? `<div class="text-muted" style="font-size:11px">${escHtml(item.justification)}</div>` : ''}
                </td>
                <td class="text-end border-start">${numFmt(item.q1)}</td>
                <td class="text-end">${numFmt(item.q2)}</td>
                <td class="text-end">${numFmt(item.q3)}</td>
                <td class="text-end">${numFmt(item.q4)}</td>
                <td class="text-end fw-semibold">${numFmt(item.effective)}</td>
                <td class="text-end text-muted small">${numFmt(item.common_size)}%</td>
                ${HAS_PREV ? `
                <td class="text-end text-muted small border-start">${numFmt(item.prev_budget)}</td>
                <td class="text-end text-muted small">${numFmt(item.prev_actual)}</td>
                <td class="text-end small">${growthHtml(type, item.effective, item.prev_actual)}</td>
                ` : ''}
            </tr>`;
        });
    });

    const t   = sec.totals;
    const bg2 = type === 'revenue' ? '#1E3A5F' : '#431407';
    html += `<tr id="st_${type}" style="background:${bg2};color:#fff;font-weight:700;font-size:12px;border-top:2px solid #fff">
        <td style="padding-left:12px;font-size:13px">TOTAL ${label}</td>
        <td class="text-end border-start border-secondary">${numFmt(t.q1)}</td>
        <td class="text-end">${numFmt(t.q2)}</td>
        <td class="text-end">${numFmt(t.q3)}</td>
        <td class="text-end">${numFmt(t.q4)}</td>
        <td class="text-end">${numFmt(t.effective)}</td>
        <td class="text-end">100%</td>
        ${HAS_PREV ? `
        <td class="text-end border-start border-secondary">${numFmt(t.prev_budget)}</td>
        <td class="text-end">${numFmt(t.prev_actual)}</td>
        <td class="text-end">${growthHtml(type, t.effective, t.prev_actual)}</td>
        ` : ''}
    </tr>`;

    return html;
}

function renderNetRow() {
    const revT = PNL.revenue.totals;
    const expT = PNL.expense.totals;
    const net  = revT.effective - expT.effective;
    const prevNetB = revT.prev_budget - expT.prev_budget;
    const prevNetA = revT.prev_actual - expT.prev_actual;
    const netColor = net >= 0 ? '#6EE7B7' : '#FCA5A5';

    return `<tr style="background:#0F172A;color:#fff;font-weight:700;font-size:13px;border-top:3px solid #E2E8F0">
        <td style="padding-left:12px">NET INCOME / (LOSS)</td>
        <td class="text-end border-start border-secondary">${numFmt(revT.q1 - expT.q1)}</td>
        <td class="text-end">${numFmt(revT.q2 - expT.q2)}</td>
        <td class="text-end">${numFmt(revT.q3 - expT.q3)}</td>
        <td class="text-end">${numFmt(revT.q4 - expT.q4)}</td>
        <td class="text-end fs-6 fw-bold" style="color:${netColor}">${numFmt(net)}</td>
        <td class="text-end">—</td>
        ${HAS_PREV ? `
        <td class="text-end border-start border-secondary">${numFmt(prevNetB)}</td>
        <td class="text-end">${numFmt(prevNetA)}</td>
        <td class="text-end">${growthHtml('revenue', net, prevNetA)}</td>
        ` : ''}
    </tr>`;
}

function toggleCat(catId) {
    const icon = document.getElementById(`icon_${catId}`);
    const rows = document.querySelectorAll(`.pnl-item-row[data-cat-id="${catId}"]`);
    const isCollapsed = rows.length > 0 && rows[0].classList.contains('d-none');
    rows.forEach(r => r.classList.toggle('d-none', !isCollapsed));
    if (icon) {
        icon.classList.toggle('fa-chevron-right', !isCollapsed);
        icon.classList.toggle('fa-chevron-down', isCollapsed);
    }
}

function expandAll() {
    document.querySelectorAll('.pnl-item-row').forEach(r => r.classList.remove('d-none'));
    document.querySelectorAll('[id^="icon_"]').forEach(i => {
        i.classList.remove('fa-chevron-right');
        i.classList.add('fa-chevron-down');
    });
}

function collapseAll() {
    document.querySelectorAll('.pnl-item-row').forEach(r => r.classList.add('d-none'));
    document.querySelectorAll('[id^="icon_"]').forEach(i => {
        i.classList.remove('fa-chevron-down');
        i.classList.add('fa-chevron-right');
    });
}

document.addEventListener('DOMContentLoaded', renderPnl);
</script>
@endpush

@endsection
