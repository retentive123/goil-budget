@extends('layouts.app')
@section('title', 'Confirm Submission')
@section('content')

<div class="row justify-content-center">
    <div class="col-lg-8">

        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('budget.show', $budgetVersion) }}" class="text-decoration-none">
                        <i class="bi bi-arrow-left"></i> Budget
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Confirm Submission
                </li>
            </ol>
        </nav>

        {{-- Main Card --}}
        <div class="card border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            {{-- Card Header with GOIL Orange --}}
            <div class="card-header border-0 px-4 py-3"
                 style="background: #E65C00; color: #fff;">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center justify-content-center rounded-circle"
                             style="width: 44px; height: 44px; background: rgba(255,255,255,0.2); font-size: 20px;">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0" style="color: #fff;">Confirm Submission</h5>
                            <p class="mb-0" style="font-size: 13px; color: rgba(255,255,255,0.85);">
                                {{ $budgetVersion->department->name }} &middot;
                                {{ $budgetVersion->period->name }} &middot;
                                Version {{ $budgetVersion->version_number }}
                            </p>
                        </div>
                    </div>
                    <span class="badge px-3 py-2"
                          style="background: rgba(255,255,255,0.2); color: #fff; font-size: 12px; border-radius: 20px;">
                        <i class="bi bi-file-earmark-text"></i> Ready for Review
                    </span>
                </div>
            </div>

            {{-- Card Body --}}
            <div class="card-body p-4">

                {{-- Budget Summary --}}
                <div class="mb-4">
                    <h6 class="fw-semibold mb-3" style="color: #1B2A4A;">
                        <i class="bi bi-pie-chart" style="color: #E65C00;"></i>
                        Budget Summary
                    </h6>
                    <div class="row g-2">
                        @foreach(['Q1' => 'Jan-Mar', 'Q2' => 'Apr-Jun', 'Q3' => 'Jul-Sep', 'Q4' => 'Oct-Dec'] as $q => $label)
                        <div class="col-6 col-md-3">
                            <div class="p-3 text-center rounded-3"
                                 style="background: #F8FAFC; border: 1px solid #E2E8F0; transition: all 0.2s;">
                                <div style="font-size: 11px; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px;">
                                    {{ $q }}
                                </div>
                                <div style="font-size: 15px; font-weight: 700; color: #1B2A4A; margin-top: 4px;">
                                    {{ currency() }} {{ number_format($grandTotals[strtolower($q)], 2) }}
                                </div>
                                <div style="font-size: 10px; color: #94A3B8;">
                                    {{ $label }}
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Total Budget --}}
                <div class="rounded-3 p-3 mb-4"
                     style="background: linear-gradient(135deg, #1B2A4A 0%, #2A3F5E 100%);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div style="font-size: 12px; color: rgba(255,255,255,0.6); font-weight: 500;">
                                <i class="bi bi-wallet2"></i> Total Budget
                            </div>
                            <div style="font-size: 24px; font-weight: 700; color: #C9A84C;">
                                {{ currency() }} {{ number_format($grandTotals['total'], 2) }}
                            </div>
                        </div>
                        <div class="text-end">
                            <div style="font-size: 11px; color: rgba(255,255,255,0.5);">
                                <i class="bi bi-building"></i> {{ $budgetVersion->department->name }}
                            </div>
                            <div style="font-size: 11px; color: rgba(255,255,255,0.5);">
                                <i class="bi bi-calendar3"></i> {{ $budgetVersion->period->name }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Line Items Preview --}}
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-semibold mb-0" style="color: #1B2A4A;">
                            <i class="bi bi-list-ul" style="color: #E65C00;"></i>
                            Budget Line Items
                        </h6>
                        <span class="badge" style="background: #F1F5F9; color: #64748B; font-weight: 500;">
                            {{ $budgetVersion->lineItems->count() }} items
                        </span>
                    </div>
                    <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                        <table class="table table-sm table-hover mb-0" style="font-size: 13px;">
                            <thead style="background: #F8FAFC; border-bottom: 2px solid #E65C00;">
                                <tr>
                                    <th style="color: #1B2A4A; font-weight: 600;">Account Code</th>
                                    <th style="color: #1B2A4A; font-weight: 600;">Account Name</th>
                                    <th class="text-end" style="color: #1B2A4A; font-weight: 600;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($budgetVersion->lineItems->take(10) as $item)
                                <tr>
                                    <td>
                                        <code class="px-1 py-0 rounded"
                                              style="background: #F1F5F9; color: #1B2A4A; font-size: 11px;">
                                            {{ $item->accountCode->code }}
                                        </code>
                                    </td>
                                    <td style="color: #475569;">{{ $item->accountCode->name }}</td>
                                    <td class="text-end fw-semibold" style="color: #1B2A4A;">
                                        {{ currency() }} {{ number_format($item->total_amount, 2) }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            @if($budgetVersion->lineItems->count() > 10)
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-center text-muted" style="font-size: 12px; padding: 8px;">
                                        <i class="bi bi-plus-circle"></i>
                                        {{ $budgetVersion->lineItems->count() - 10 }} more items...
                                    </td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>

                {{-- Warning Alert --}}
                <div class="alert d-flex align-items-start gap-2 mb-4"
                     style="background: #FEF3C7; border: 1px solid #F59E0B; border-radius: 10px; padding: 14px 18px;">
                    <i class="bi bi-exclamation-triangle" style="color: #92400E; font-size: 18px;"></i>
                    <div>
                        <div style="font-weight: 600; color: #92400E; font-size: 13px;">
                            <i class="bi bi-lock"></i> Important
                        </div>
                        <div style="font-size: 13px; color: #78350F;">
                            Once submitted, you will not be able to edit this budget unless it is rejected
                            and sent back for revision.
                        </div>
                    </div>
                </div>

                {{-- Form --}}
                <form method="POST" action="{{ route('budget.submit', $budgetVersion) }}">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="color: #1B2A4A; font-size: 13px;">
                            <i class="bi bi-pencil-square" style="color: #E65C00;"></i>
                            Submission Notes <span class="text-muted fw-normal">(optional)</span>
                        </label>
                        <textarea name="submission_notes" rows="3"
                            class="form-control @error('submission_notes') is-invalid @enderror"
                            style="border-radius: 10px; border-color: #E2E8F0; padding: 12px; resize: vertical;"
                            placeholder="Any notes or context for the approver…">{{ old('submission_notes') }}</textarea>
                        @error('submission_notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn px-4 py-2 fw-semibold"
                                style="background: #E65C00; color: #fff; border-radius: 10px; border: none; transition: all 0.3s ease;">
                            <i class="bi bi-send"></i> Confirm & Submit
                        </button>
                        <a href="{{ route('budget.show', $budgetVersion) }}"
                           class="btn px-4 py-2 fw-semibold"
                           style="background: #F1F5F9; color: #475569; border-radius: 10px; border: 1px solid #E2E8F0; transition: all 0.3s ease;">
                            <i class="bi bi-arrow-left"></i> Go Back
                        </a>
                    </div>
                </form>

            </div>
        </div>

        {{-- Helpful Tips Card --}}
        <div class="mt-3 p-3 rounded-3" style="background: #F8FAFC; border: 1px solid #E2E8F0;">
            <div class="d-flex gap-2 align-items-start">
                <i class="bi bi-lightbulb" style="color: #C9A84C; font-size: 18px;"></i>
                <div>
                    <div style="font-size: 12px; font-weight: 600; color: #1B2A4A;">Tips for a Smooth Approval</div>
                    <ul class="list-unstyled mb-0" style="font-size: 12px; color: #64748B;">
                        <li><i class="bi bi-check2-circle" style="color: #10B981;"></i> Double-check all amounts for accuracy</li>
                        <li><i class="bi bi-check2-circle" style="color: #10B981;"></i> Provide clear notes explaining any significant changes</li>
                        <li><i class="bi bi-check2-circle" style="color: #10B981;"></i> Ensure all required justifications are included</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    .btn-goil-orange:hover {
        background: #C44D00 !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(230, 92, 0, 0.3);
    }

    .form-control:focus {
        border-color: #E65C00;
        box-shadow: 0 0 0 0.2rem rgba(230, 92, 0, 0.15);
    }

    .table-responsive::-webkit-scrollbar {
        width: 4px;
        height: 4px;
    }

    .table-responsive::-webkit-scrollbar-thumb {
        background: #CBD5E1;
        border-radius: 4px;
    }

    .table-responsive::-webkit-scrollbar-track {
        background: #F1F5F9;
    }
</style>

@endsection
