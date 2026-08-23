@extends('layouts.app')
@section('title', 'Revise Budget — ' . $budgetVersion->ownerName())
@section('content')

<div class="row justify-content-center">
<div class="col-lg-7">

    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
                <a href="{{ route('budget.index') }}" class="text-decoration-none text-muted">Budgets</a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('budgets.show', $budgetVersion) }}" class="text-decoration-none text-muted">
                    {{ $budgetVersion->ownerName() }} v{{ $budgetVersion->version_number }}
                </a>
            </li>
            <li class="breadcrumb-item active">Revise Budget</li>
        </ol>
    </nav>

    @if($pendingRevision)
    <div class="alert alert-warning d-flex gap-2 align-items-start mb-4">
        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
        <div>
            <strong>A revision is already in progress (v{{ $pendingRevision->version_number }}).</strong><br>
            <span class="small">Status: <strong>{{ ucfirst(str_replace('_', ' ', $pendingRevision->status)) }}</strong>.
            Submit or resolve the existing revision before creating another.</span>
            <div class="mt-2">
                <a href="{{ route('budgets.show', $pendingRevision) }}" class="btn btn-sm btn-warning">
                    <i class="bi bi-arrow-right-circle"></i> Go to Pending Revision
                </a>
            </div>
        </div>
    </div>
    @endif

    <div class="card border-0 shadow-lg" style="border-radius:16px;overflow:hidden">

        <div class="card-header border-0 px-4 py-3" style="background:var(--navy);color:#fff">
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center justify-content-center rounded-circle"
                     style="width:44px;height:44px;background:rgba(255,255,255,.12);font-size:22px">
                    <i class="bi bi-pencil-square"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0" style="color:#fff">Revise Approved Budget</h5>
                    <p class="mb-0" style="font-size:13px;color:rgba(255,255,255,.7)">
                        {{ $budgetVersion->ownerName() }} · {{ $budgetVersion->period->name }}
                        · v{{ $budgetVersion->version_number }} (approved)
                    </p>
                </div>
            </div>
        </div>

        <div class="card-body p-4">

            {{-- What happens info box --}}
            <div class="p-3 rounded-3 mb-4" style="background:#EFF6FF;border:1px solid #BFDBFE">
                <div class="fw-semibold mb-2" style="color:#1E40AF;font-size:13px">
                    <i class="bi bi-info-circle me-1"></i> What happens when you create a revision
                </div>
                <ul class="mb-0 small" style="color:#1E3A8A;padding-left:1.2rem">
                    <li>A new budget version is created (v{{ \App\Models\BudgetVersion::nextVersionNumber($budgetVersion->budget_period_id, $budgetVersion->department_id, $budgetVersion->subsidiary_id) }}) with all line items copied from this approved budget.</li>
                    <li>You can adjust any monthly amounts to reflect the revised forecast for the rest of the year.</li>
                    <li>The revision goes through the normal approval workflow before it is published.</li>
                    <li>Reports will show <strong>Original Budget</strong> vs <strong>Revised Budget</strong> vs <strong>Actuals</strong> side by side.</li>
                    <li>The original approved budget is preserved — it is never overwritten.</li>
                </ul>
            </div>

            {{-- Budget summary --}}
            <div class="mb-4">
                <h6 class="fw-semibold mb-3" style="font-size:13px;color:var(--navy)">
                    <i class="bi bi-bar-chart-line text-orange me-1"></i> Current Approved Budget Summary
                </h6>
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded-3 text-center" style="background:#F8FAFC;border:1px solid #E2E8F0">
                            <div class="small text-muted mb-1">Q1</div>
                            <div class="fw-bold" style="font-size:13px">
                                {{ number_format($budgetVersion->lineItems->sum('q1_amount'), 0) }}
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded-3 text-center" style="background:#F8FAFC;border:1px solid #E2E8F0">
                            <div class="small text-muted mb-1">Q2</div>
                            <div class="fw-bold" style="font-size:13px">
                                {{ number_format($budgetVersion->lineItems->sum('q2_amount'), 0) }}
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded-3 text-center" style="background:#F8FAFC;border:1px solid #E2E8F0">
                            <div class="small text-muted mb-1">Q3</div>
                            <div class="fw-bold" style="font-size:13px">
                                {{ number_format($budgetVersion->lineItems->sum('q3_amount'), 0) }}
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded-3 text-center" style="background:#F8FAFC;border:1px solid #E2E8F0">
                            <div class="small text-muted mb-1">Q4</div>
                            <div class="fw-bold" style="font-size:13px">
                                {{ number_format($budgetVersion->lineItems->sum('q4_amount'), 0) }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-3 p-3 rounded-3 d-flex justify-content-between align-items-center"
                     style="background:var(--navy);color:#fff">
                    <span class="small">Total Approved Budget</span>
                    <span class="fw-bold">GHS {{ number_format($budgetVersion->lineItems->sum('total_amount'), 2) }}</span>
                </div>
            </div>

            {{-- Form --}}
            @if(!$pendingRevision)
            <form method="POST" action="{{ route('budgets.revise.store', $budgetVersion) }}">
                @csrf
                <div class="mb-4">
                    <label class="form-label fw-semibold" style="font-size:13px;color:var(--navy)">
                        Revision Notes <span class="fw-normal text-muted">(optional)</span>
                    </label>
                    <textarea name="revision_notes" rows="3"
                              class="form-control @error('revision_notes') is-invalid @enderror"
                              placeholder="e.g. Mid-year revision reflecting updated H2 forecast after Q2 performance review…"
                              style="border-color:#E2E8F0">{{ old('revision_notes') }}</textarea>
                    @error('revision_notes')
                    <div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2 pt-3 border-top">
                    <button type="submit" class="btn px-4 py-2 fw-semibold"
                            style="background:var(--navy);color:#fff;border-radius:10px;border:none">
                        <i class="bi bi-pencil-square me-1"></i> Create Revision
                    </button>
                    <a href="{{ route('budgets.show', $budgetVersion) }}"
                       class="btn px-4 py-2 fw-semibold"
                       style="background:#F1F5F9;color:#475569;border-radius:10px;border:1px solid #E2E8F0">
                        <i class="bi bi-x-lg me-1"></i> Cancel
                    </a>
                </div>
            </form>
            @else
            <div class="d-flex gap-2 pt-3 border-top">
                <a href="{{ route('budgets.show', $budgetVersion) }}"
                   class="btn px-4 py-2 fw-semibold"
                   style="background:#F1F5F9;color:#475569;border-radius:10px;border:1px solid #E2E8F0">
                    <i class="bi bi-arrow-left me-1"></i> Back to Budget
                </a>
            </div>
            @endif
        </div>
    </div>

</div>
</div>

@endsection
