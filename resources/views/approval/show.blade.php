@extends('layouts.app')
@section('title', 'Review Budget')
@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('approvals.index') }}" class="text-muted text-decoration-none">Approvals</a>
    <span class="text-muted">/</span>
    <span>{{ $budgetVersion->department->name }} — v{{ $budgetVersion->version_number }}</span>
</div>

<div class="row g-4">

    {{-- Left: budget details --}}
    <div class="col-lg-8">

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
                        <div class="small text-muted">Submitted by</div>
                        <div class="fw-semibold small">{{ $budgetVersion->submittedBy?->name ?? '—' }}</div>
                    </div>
                    <div class="col">
                        <div class="small text-muted">Total Budget</div>
                        {{-- ✅ Use effective total --}}
                        <div class="fw-bold text-success">{{ currency() }} {{ number_format($grandTotals['total'], 2) }}</div>
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

        {{-- Line items --}}
        @foreach($summary as $categoryName => $categoryData)
        @php
            // ✅ Calculate effective category total including supplementary
            $catEffectiveTotal = $categoryData['items']->sum(fn($item) => $item->effectiveBudget());
            $catSuppTotal = $categoryData['items']->sum(fn($item) => $item->approvedSupplementaryTotal());
        @endphp
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light py-2 d-flex justify-content-between">
                <span class="fw-semibold small text-uppercase">{{ $categoryName }}</span>
                <span class="small text-muted">
                    Total: <strong>{{ currency() }} {{ number_format($catEffectiveTotal, 2) }}</strong>
                    @if($catSuppTotal > 0)
                    <span style="color:#10B981;font-size:11px;">
                        (+{{ number_format($catSuppTotal, 2) }} supp.)
                    </span>
                    @endif
                </span>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:30%">Account</th>
                            <th class="text-end">Q1</th>
                            <th class="text-end">Q2</th>
                            <th class="text-end">Q3</th>
                            <th class="text-end">Q4</th>
                            <th class="text-end">Supplementary</th>
                            <th class="text-end">Total</th>
                            @if($canDecide)<th>Decision</th><th>Adjusted</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categoryData['items'] as $item)
                        @php
                            $itemSupp = $item->approvedSupplementaryTotal();
                            $itemEffective = $item->effectiveBudget();
                        @endphp
                        <tr>
                            <td class="small">
                                <code>{{ $item->accountCode->code }}</code>
                                {{ $item->accountCode->name }}
                                @if($item->justification)
                                    <div class="text-muted" style="font-size:11px">
                                        {{ $item->justification }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-end small">{{ number_format($item->q1_amount, 2) }}</td>
                            <td class="text-end small">{{ number_format($item->q2_amount, 2) }}</td>
                            <td class="text-end small">{{ number_format($item->q3_amount, 2) }}</td>
                            <td class="text-end small">{{ number_format($item->q4_amount, 2) }}</td>
                            <td class="text-end small" style="color:{{ $itemSupp > 0 ? '#10B981' : 'inherit' }}">
                                {{ $itemSupp > 0 ? '+'.number_format($itemSupp, 2) : '—' }}
                            </td>
                            <td class="text-end small fw-semibold" style="color:var(--navy)">
                                {{ number_format($itemEffective, 2) }}
                            </td>
                            @if($canDecide)
                            <td>
                                <select name="line_items[{{ $item->id }}][status]"
                                    class="form-select form-select-sm li-decision"
                                    form="decision-form"
                                    data-item="{{ $item->id }}">
                                    <option value="">—</option>
                                    <option value="approved">Approve</option>
                                    <option value="reduced">Reduce</option>
                                    <option value="rejected">Reject</option>
                                </select>
                            </td>
                            <td>
                                <input type="number"
                                    name="line_items[{{ $item->id }}][approved_amount]"
                                    class="form-control form-control-sm li-amount"
                                    form="decision-form"
                                    id="amount-{{ $item->id }}"
                                    placeholder="{{ currency() }}"
                                    min="0" step="0.01"
                                    style="display:none">
                            </td>
                            @endif
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
                            <td class="text-end" style="color:var(--navy)">
                                {{ currency() }} {{ number_format($catEffectiveTotal, 2) }}
                            </td>
                            @if($canDecide)<td colspan="2"></td>@endif
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endforeach

        {{-- Grand total footer --}}
        <div class="card shadow-sm" style="background:var(--navy);color:#fff;border:none;">
            <div class="card-body py-3">
                <div class="row align-items-center">
                    <div class="col">
                        <div style="font-size:13px;color:rgba(255,255,255,.6)">
                            Grand Total — All Categories
                            @php
                                $totalSupp = $budgetVersion->lineItems->sum(fn($i) => $i->approvedSupplementaryTotal());
                            @endphp
                            @if($totalSupp > 0)
                            <span style="color:#10B981;font-size:12px;">
                                (incl. {{ number_format($totalSupp, 2) }} supplementary)
                            </span>
                            @endif
                        </div>
                    </div>
                    <div class="col-auto">
                        <div style="font-size:22px;font-weight:700;color:var(--gold)">
                            {{ currency() }} {{ number_format($grandTotals['total'], 2) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Right: approval progress + decision form --}}
    <div class="col-lg-4">

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

        {{-- ── Decision Form ── --}}
        @if($canDecide)
            @php $roleConfig = $approvalService->currentRoleConfig($budgetVersion) ?? null; @endphp
            <div class="card border-0 shadow-lg mb-4" style="border-radius: 16px; overflow: hidden; border-top: 4px solid #E65C00;">
                {{-- Card Header --}}
                <div class="card-header border-0 px-4 py-3" style="background: #E65C00; color: #fff;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center justify-content-center rounded-circle"
                                style="width: 40px; height: 40px; background: rgba(255,255,255,0.12); font-size: 18px;">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div>
                                <div style="font-size: 14px; font-weight: 700;">
                                    Your Decision — {{ $currentStage->name }}
                                </div>
                                @if($roleConfig)
                                <div style="font-size: 11px; color: rgba(255,255,255,0.7); margin-top: 2px;">
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
                        <span class="badge px-3 py-2" style="background: rgba(255,255,255,0.15); color: #fff; font-size: 11px; border-radius: 20px;">
                            <i class="bi bi-clock"></i> Pending Review
                        </span>
                    </div>
                </div>

                {{-- Card Body --}}
                <div class="card-body p-4">
                    <form id="decision-form" method="POST" action="{{ route('approvals.decide', $budgetVersion) }}">
                        @csrf

                        {{-- Decision Radio Buttons --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold" style="color: #1B2A4A; font-size: 13px;">
                                <i class="bi bi-check2-circle" style="color: #E65C00;"></i> Decision
                            </label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input type="radio" name="decision" value="approved"
                                        id="dec-approve" class="form-check-input" required
                                        style="border-color: #10B981;">
                                    <label for="dec-approve" class="form-check-label fw-semibold" style="color: #10B981;">
                                        <i class="bi bi-check-circle"></i> Approve
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" name="decision" value="rejected"
                                        id="dec-reject" class="form-check-input"
                                        style="border-color: #F43F5E;">
                                    <label for="dec-reject" class="form-check-label fw-semibold" style="color: #F43F5E;">
                                        <i class="bi bi-x-circle"></i> Reject
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Comments --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold" style="color: #1B2A4A; font-size: 13px;">
                                <i class="bi bi-chat-dots" style="color: #E65C00;"></i> Comments
                                <span class="text-danger" id="comments-required" style="display:none;">
                                    (required for rejection)
                                </span>
                            </label>
                            <textarea name="comments" rows="4"
                                    class="form-control @error('comments') is-invalid @enderror"
                                    style="border-radius: 10px; border-color: #E2E8F0; padding: 12px; resize: vertical;"
                                    placeholder="Add comments…">{{ old('comments') }}</textarea>
                            @error('comments')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Line Item Controls (Partial Approval) --}}
                        @if($roleConfig?->can_partial_approve)
                        <div class="mb-4">
                            <div style="font-size: 12px; font-weight: 600; color: #1B2A4A; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">
                                <i class="bi bi-list-ul" style="color: #E65C00;"></i> Line Item Decisions
                                <span style="font-weight: 400; text-transform: none; color: #94A3B8; font-size: 11px;">
                                    (optional — leave blank to apply overall decision)
                                </span>
                            </div>

                            <div style="max-height: 400px; overflow-y: auto; border-radius: 10px; border: 1px solid #E2E8F0;">
                                @foreach($summary as $catName => $catData)
                                <div style="background: #F8FAFC; padding: 8px 16px; font-size: 11px; font-weight: 700; color: #1B2A4A; text-transform: uppercase; border-bottom: 1px solid #E2E8F0;">
                                    {{ $catName }}
                                </div>
                                @foreach($catData['items'] as $item)
                                @php
                                    $itemEffective = $item->effectiveBudget();
                                @endphp
                                <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom" style="font-size: 12px; background: #fff;">
                                    <div style="flex: 1; font-family: monospace; font-weight: 600; color: #1B2A4A;">
                                        {{ $item->accountCode->code }}
                                    </div>
                                    <div style="flex: 2; color: #475569; font-size: 11px;">
                                        {{ $item->accountCode->name }}
                                    </div>
                                    <div style="font-weight: 600; min-width: 90px; text-align: right; color: #1B2A4A;">
                                        GHS {{ number_format($itemEffective, 2) }}
                                    </div>
                                    <select name="line_items[{{ $item->id }}][status]"
                                            class="form-select form-select-sm li-decision"
                                            form="decision-form"
                                            data-item="{{ $item->id }}"
                                            style="max-width: 110px; border-radius: 6px; font-size: 11px;">
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
                                        style="max-width: 110px; border-radius: 6px; display: none;">
                                    @endif
                                </div>
                                @endforeach
                                @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- Submit Button --}}
                        <button type="submit" class="btn w-100 py-2 fw-semibold"
                                style="background: #E65C00; color: #fff; border-radius: 10px; border: none; transition: all 0.3s ease; font-size: 14px;">
                            <i class="bi bi-send"></i> Submit Decision
                        </button>
                    </form>
                </div>
            </div>

            {{-- Line Item Script --}}
            @if($roleConfig?->can_partial_approve && $roleConfig?->can_reduce_amounts)
            <script>
                document.querySelectorAll('.li-decision').forEach(select => {
                    select.addEventListener('change', function() {
                        const itemId = this.dataset.item;
                        const amountInput = document.getElementById('amount-' + itemId);
                        if (amountInput) {
                            amountInput.style.display = this.value === 'reduced' ? 'inline-block' : 'none';
                        }
                    });
                });
            </script>
            @endif

        {{-- ── Cannot Action / Access Denied ── --}}
        @else
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px; border: 2px solid #E2E8F0; background: #F8FAFC;">
                <div class="card-body p-4 text-center">
                    <div style="font-size: 42px; margin-bottom: 12px;">🔒</div>
                    <div style="font-size: 15px; font-weight: 700; color: #1B2A4A; margin-bottom: 4px;">
                        You cannot action this budget
                    </div>
                    <div style="font-size: 13px; color: #64748B; margin-bottom: 16px;">
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
                        <div class="d-flex align-items-start gap-2 p-3 rounded-3 mb-2" style="background: #FEE2E2; border-left: 4px solid #F43F5E;">
                            <i class="bi bi-shield-exclamation" style="color: #991B1B; font-size: 18px;"></i>
                            <div>
                                <div style="font-weight: 600; color: #991B1B; font-size: 13px;">Segregation of Duties</div>
                                <div style="font-size: 12px; color: #7F1D1D;">You submitted this budget. A different user must approve it.</div>
                            </div>
                        </div>
                        @endif

                        @if($wrongRole)
                        <div class="d-flex align-items-start gap-2 p-3 rounded-3 mb-2" style="background: #FEF3C7; border-left: 4px solid #F59E0B;">
                            <i class="bi bi-exclamation-triangle" style="color: #92400E; font-size: 18px;"></i>
                            <div>
                                <div style="font-weight: 600; color: #92400E; font-size: 13px;">Role Mismatch</div>
                                <div style="font-size: 12px; color: #78350F;">Your role does not match the current approval stage ({{ $currentStage?->name }}).</div>
                            </div>
                        </div>
                        @endif

                        @if($wrongScope)
                        <div class="d-flex align-items-start gap-2 p-3 rounded-3" style="background: #FEF3C7; border-left: 4px solid #F59E0B;">
                            <i class="bi bi-building" style="color: #92400E; font-size: 18px;"></i>
                            <div>
                                <div style="font-weight: 600; color: #92400E; font-size: 13px;">Department Scope</div>
                                <div style="font-size: 12px; color: #78350F;">This budget belongs to a different department and your role only covers your own department.</div>
                            </div>
                        </div>
                        @endif

                        @if(!$isSelf && !$wrongRole && !$wrongScope)
                        <div class="d-flex align-items-start gap-2 p-3 rounded-3" style="background: #F1F5F9; border-left: 4px solid #94A3B8;">
                            <i class="bi bi-info-circle" style="color: #475569; font-size: 18px;"></i>
                            <div>
                                <div style="font-weight: 600; color: #475569; font-size: 13px;">Not Actionable</div>
                                <div style="font-size: 12px; color: #64748B;">This budget is not at a stage you can action, or has already been processed.</div>
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- View Only Button --}}
                    <div class="mt-3">
                        <a href="{{ route('budget.show', $budgetVersion) }}" class="btn btn-outline-secondary btn-sm px-4" style="border-radius: 8px;">
                            <i class="bi bi-eye"></i> View Budget
                        </a>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>

<script>
// Show/hide reduced amount field based on line item decision
document.querySelectorAll('.li-decision').forEach(sel => {
    sel.addEventListener('change', function () {
        const itemId = this.dataset.item;
        const amountField = document.getElementById('amount-' + itemId);
        amountField.style.display = this.value === 'reduced' ? 'block' : 'none';
    });
});

// Show comments required hint when rejection is selected
document.querySelectorAll('input[name="decision"]').forEach(radio => {
    radio.addEventListener('change', function () {
        const hint = document.getElementById('comments-required');
        hint.style.display = this.value === 'rejected' ? 'inline' : 'none';
    });
});
</script>

@endsection
