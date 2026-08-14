@extends('layouts.app')
@section('title', 'Period Rates — ' . $budgetPeriod->name)
@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.budget-periods.index') }}" class="text-muted text-decoration-none small">Budget Periods</a>
    <span class="text-muted">/</span>
    <a href="{{ route('admin.budget-periods.show', $budgetPeriod) }}" class="text-muted text-decoration-none small">{{ $budgetPeriod->name }}</a>
    <span class="text-muted">/</span>
    <span class="small">Rate Settings</span>
</div>

{{-- Header --}}
<div class="d-flex align-items-start justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:var(--navy)">
            Rate Settings — {{ $budgetPeriod->name }}
        </h4>
        <div class="small text-muted">
            Per-period rate snapshot. Category rates are fallbacks used when a code has no period-specific rate set.
        </div>
    </div>
    <span style="padding:6px 16px;border-radius:20px;font-size:12px;font-weight:700;
                 background:#F5F3FF;color:#4C1D95;">
        Mode: {{ match($calcMode) {
            'qty_rate'      => 'Qty × Rate',
            'qty_rate_freq' => 'Qty × Rate × Freq',
            default         => 'Direct Entry'
        } }}
    </span>
</div>

@if($calcMode === 'none')
<div class="alert alert-info">
    This period uses <strong>Direct Entry</strong> mode — no Qty/Rate/Freq fields.
    To enable rate management, edit the period and change the Calculation Mode.
</div>
@else

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@php
    $showFreq      = $calcMode === 'qty_rate_freq';
    $adminSetsRate = $budgetPeriod->adminSetsRate();
    $adminSetsFreq = $budgetPeriod->adminSetsFreq();
    $colCount      = $showFreq ? 6 : 4;

    $typeConfig = [
        'revenue'             => ['label' => 'Revenue',                    'bg' => '#D1FAE5', 'text' => '#065F46', 'border' => '#6EE7B7'],
        'both'                => ['label' => 'Revenue & Expenditure',       'bg' => '#D1FAE5', 'text' => '#065F46', 'border' => '#6EE7B7'],
        'expense'             => ['label' => 'Expenditure',                 'bg' => '#FEE2E2', 'text' => '#991B1B', 'border' => '#FCA5A5'],
        'assets'              => ['label' => 'Assets',                      'bg' => '#DBEAFE', 'text' => '#1E40AF', 'border' => '#93C5FD'],
        'liabilities'         => ['label' => 'Liabilities',                 'bg' => '#FEF3C7', 'text' => '#92400E', 'border' => '#FDE68A'],
        'capital_expenditure' => ['label' => 'Capital Expenditure (Capex)', 'bg' => '#F3E8FF', 'text' => '#6B21A8', 'border' => '#D8B4FE'],
    ];

    // Auto-expand categories that already have period-specific code rates saved
    $autoExpand = [];
    foreach ($categories as $cat) {
        foreach ($cat->accountCodes as $code) {
            $cpr = $codeRateMap[$code->id] ?? null;
            if ($cpr && ($cpr->default_rate !== null || $cpr->default_frequency !== null)) {
                $autoExpand[] = $cat->id;
                break;
            }
        }
    }
@endphp

{{-- Legend --}}
<div class="mb-3 d-flex align-items-center gap-3 small text-muted flex-wrap">
    <span>
        <span style="display:inline-block;width:10px;height:10px;border-radius:50%;
                     background:#4C1D95;margin-right:4px"></span>Period rate (overrides global)
    </span>
    <span>
        <span style="display:inline-block;width:10px;height:10px;border-radius:50%;
                     background:#94A3B8;margin-right:4px"></span>Global default (fallback)
    </span>
    <span>
        <span style="display:inline-block;width:10px;height:10px;border-radius:50%;
                     background:#D1FAE5;border:1px solid #10B981;margin-right:4px"></span>Effective value (what budget uses)
    </span>
    <span>
        <i class="bi bi-diagram-3" style="color:var(--navy)"></i>&nbsp;
        Click a category row to expand its account codes
    </span>
</div>

<form method="POST" action="{{ route('admin.budget-periods.rates.update', $budgetPeriod) }}">
    @csrf @method('PUT')

    {{-- Expand / Collapse All --}}
    <div class="d-flex justify-content-end gap-2 mb-2">
        <button type="button" class="btn btn-sm btn-outline-secondary"
                onclick="toggleAll(true)" style="font-size:12px">
            <i class="bi bi-arrows-expand"></i> Expand All
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary"
                onclick="toggleAll(false)" style="font-size:12px">
            <i class="bi bi-arrows-collapse"></i> Collapse All
        </button>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body p-0">
            <div style="overflow-x:clip">
                <table class="table table-sm mb-0" style="font-size:12px;min-width:600px">

                    {{-- ── Column headers (sticky — stays visible while scrolling) ── --}}
                    @php
                        // Topbar is position:fixed; height:60px — offset sticky by that amount
                        $thStyle = 'position:sticky;top:60px;z-index:10;'
                                 . 'background:#F0F4FF;'
                                 . 'font-size:10px;text-transform:uppercase;letter-spacing:.5px;'
                                 . 'box-shadow:0 1px 0 #C7D2FE;';
                    @endphp
                    <thead>
                        <tr>
                            <th style="{{ $thStyle }}width:34%;padding-left:14px;color:var(--slate)">
                                Category / Account Code
                            </th>
                            <th class="text-end" style="{{ $thStyle }}color:#94A3B8">Global Rate</th>
                            <th class="text-end" style="{{ $thStyle }}color:#4C1D95">Period Rate</th>
                            @if($showFreq)
                            <th class="text-end" style="{{ $thStyle }}color:#94A3B8">Global Freq</th>
                            <th class="text-end" style="{{ $thStyle }}color:#4C1D95">Period Freq</th>
                            @endif
                            <th class="text-center" style="{{ $thStyle }}color:#10B981;width:140px">Effective</th>
                        </tr>
                    </thead>

                    {{-- ── Rows ── --}}
                    @php $prevType = null; @endphp
                    @foreach($categories as $cat)
                    @php
                        $catPeriodRate = $categoryRateMap[$cat->id] ?? null;
                        $effCatRate    = $catPeriodRate?->default_rate    ?? $cat->default_rate;
                        $effCatFreq    = $catPeriodRate?->default_frequency ?? $cat->default_frequency;
                        $tCfg      = $typeConfig[$cat->budget_type]
                                     ?? ['label'=>ucfirst($cat->budget_type??'Other'),
                                         'bg'=>'#F1F5F9','text'=>'#475569','border'=>'#CBD5E1'];
                        $codeCount = $cat->accountCodes->count();
                        $hasCodes  = $codeCount > 0;
                    @endphp

                    {{-- Budget-type group header --}}
                    @if($cat->budget_type !== $prevType)
                    @php $prevType = $cat->budget_type; @endphp
                    <tbody>
                        <tr>
                            <td colspan="{{ $colCount }}"
                                style="padding:7px 14px;
                                       background:{{ $tCfg['bg'] }};
                                       border-left:3px solid {{ $tCfg['border'] }};
                                       border-top:2px solid {{ $tCfg['border'] }}">
                                <span class="fw-bold"
                                      style="font-size:10px;text-transform:uppercase;
                                             letter-spacing:.8px;color:{{ $tCfg['text'] }}">
                                    {{ $tCfg['label'] }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                    @endif

                    {{-- ── Category row ── --}}
                    <tbody>
                        <tr class="category-toggle-row"
                            @if($hasCodes) onclick="toggleCodes({{ $cat->id }}, event)" @endif
                            style="{{ $hasCodes ? 'cursor:pointer;' : '' }}background:#F8FAFC;
                                   border-left:3px solid {{ $tCfg['border'] }}">
                            <td style="padding:9px 12px 9px 14px">
                                @if($hasCodes)
                                <i id="chevron-{{ $cat->id }}"
                                   class="bi bi-chevron-right"
                                   style="font-size:10px;color:{{ $tCfg['text'] }};
                                          transition:transform .18s ease;margin-right:6px;
                                          vertical-align:middle"></i>
                                @else
                                <i class="bi bi-dash" style="font-size:10px;color:#CBD5E1;
                                                             margin-right:6px;vertical-align:middle"></i>
                                @endif
                                <strong style="color:var(--navy)">{{ $cat->name }}</strong>
                                @if($hasCodes)
                                <span class="ms-2"
                                      style="font-size:10px;background:#E2E8F0;border-radius:10px;
                                             padding:1px 7px;color:#475569">
                                    {{ $codeCount }} code{{ $codeCount !== 1 ? 's' : '' }}
                                </span>
                                @endif
                                <span class="ms-1 text-muted" style="font-size:10px;font-style:italic">
                                    fallback
                                </span>
                            </td>

                            {{-- Global category rate --}}
                            <td class="text-end text-muted" style="vertical-align:middle">
                                {{ $cat->default_rate !== null ? number_format($cat->default_rate, 4) : '—' }}
                            </td>

                            {{-- Period category rate --}}
                            <td class="text-end" style="vertical-align:middle">
                                <input type="number" step="any" min="0"
                                       name="cats[{{ $cat->id }}][rate]"
                                       value="{{ $catPeriodRate?->default_rate }}"
                                       placeholder="{{ $cat->default_rate ?? 'inherit' }}"
                                       class="form-control form-control-sm text-end period-rate-input"
                                       style="max-width:110px;margin-left:auto;
                                              border-color:{{ $catPeriodRate?->default_rate !== null ? '#7C3AED' : '#E2E8F0' }}"
                                       data-global-rate="{{ $cat->default_rate }}"
                                       data-eff-target="cat-eff-rate-{{ $cat->id }}">
                            </td>

                            @if($showFreq)
                            <td class="text-end text-muted" style="vertical-align:middle">
                                {{ $cat->default_frequency !== null ? number_format($cat->default_frequency, 2) : '—' }}
                            </td>
                            <td class="text-end" style="vertical-align:middle">
                                <input type="number" step="any" min="0"
                                       name="cats[{{ $cat->id }}][freq]"
                                       value="{{ $catPeriodRate?->default_frequency }}"
                                       placeholder="{{ $cat->default_frequency ?? 'inherit' }}"
                                       class="form-control form-control-sm text-end period-freq-input"
                                       style="max-width:110px;margin-left:auto;
                                              border-color:{{ $catPeriodRate?->default_frequency !== null ? '#7C3AED' : '#E2E8F0' }}"
                                       data-global-freq="{{ $cat->default_frequency }}"
                                       data-eff-target="cat-eff-freq-{{ $cat->id }}">
                            </td>
                            @else
                            <input type="hidden" name="cats[{{ $cat->id }}][freq]" value="">
                            @endif

                            {{-- Effective badge --}}
                            <td class="text-center" style="vertical-align:middle">
                                <span id="cat-eff-rate-{{ $cat->id }}"
                                      class="badge"
                                      style="background:{{ $effCatRate !== null ? '#D1FAE5' : '#F1F5F9' }};
                                             color:{{ $effCatRate !== null ? '#065F46' : '#94A3B8' }};
                                             font-size:10px">
                                    {{ $effCatRate !== null ? number_format($effCatRate, 4) : '—' }}
                                </span>
                                @if($showFreq)
                                <span id="cat-eff-freq-{{ $cat->id }}"
                                      class="badge ms-1"
                                      style="background:{{ $effCatFreq !== null ? '#DBEAFE' : '#F1F5F9' }};
                                             color:{{ $effCatFreq !== null ? '#1E40AF' : '#94A3B8' }};
                                             font-size:10px">
                                    × {{ $effCatFreq !== null ? number_format($effCatFreq, 2) : '—' }}
                                </span>
                                @endif
                            </td>
                        </tr>
                    </tbody>

                    {{-- ── Code rows (hidden by default; auto-expanded if period rates exist) ── --}}
                    @if($hasCodes)
                    <tbody id="codes-{{ $cat->id }}" class="codes-tbody" style="display:none">
                        @foreach($cat->accountCodes as $code)
                        @php
                            $codePeriodRate = $codeRateMap[$code->id] ?? null;
                            $effCodeRate    = $codePeriodRate?->default_rate
                                ?? $code->default_rate
                                ?? $catPeriodRate?->default_rate
                                ?? $cat->default_rate;
                            $effCodeFreq    = $codePeriodRate?->default_frequency
                                ?? $code->default_frequency
                                ?? $catPeriodRate?->default_frequency
                                ?? $cat->default_frequency;
                        @endphp
                        <tr style="background:#FFFFFF;border-left:3px solid {{ $tCfg['border'] }}">
                            <td style="padding:6px 12px 6px 40px;border-top:1px solid #F1F5F9">
                                <code style="background:#F1F5F9;color:#334155;padding:1px 6px;
                                             border-radius:4px;font-size:10px">{{ $code->code }}</code>
                                <span class="ms-1" style="color:#475569;font-size:11px">{{ $code->name }}</span>
                            </td>
                            <td class="text-end text-muted" style="vertical-align:middle;border-top:1px solid #F1F5F9">
                                {{ $code->default_rate !== null ? number_format($code->default_rate, 4) : '—' }}
                            </td>
                            <td class="text-end" style="vertical-align:middle;border-top:1px solid #F1F5F9">
                                <input type="number" step="any" min="0"
                                       name="codes[{{ $code->id }}][rate]"
                                       value="{{ $codePeriodRate?->default_rate }}"
                                       placeholder="{{ $code->default_rate ?? ($catPeriodRate?->default_rate ?? $cat->default_rate ?? 'inherit') }}"
                                       class="form-control form-control-sm text-end period-rate-input"
                                       style="max-width:110px;margin-left:auto;
                                              border-color:{{ $codePeriodRate?->default_rate !== null ? '#7C3AED' : '#E2E8F0' }}"
                                       data-eff-target="code-eff-rate-{{ $code->id }}"
                                       data-global-rate="{{ $code->default_rate }}">
                            </td>
                            @if($showFreq)
                            <td class="text-end text-muted" style="vertical-align:middle;border-top:1px solid #F1F5F9">
                                {{ $code->default_frequency !== null ? number_format($code->default_frequency, 2) : '—' }}
                            </td>
                            <td class="text-end" style="vertical-align:middle;border-top:1px solid #F1F5F9">
                                <input type="number" step="any" min="0"
                                       name="codes[{{ $code->id }}][freq]"
                                       value="{{ $codePeriodRate?->default_frequency }}"
                                       placeholder="{{ $code->default_frequency ?? ($catPeriodRate?->default_frequency ?? $cat->default_frequency ?? 'inherit') }}"
                                       class="form-control form-control-sm text-end period-freq-input"
                                       style="max-width:110px;margin-left:auto;
                                              border-color:{{ $codePeriodRate?->default_frequency !== null ? '#7C3AED' : '#E2E8F0' }}"
                                       data-eff-target="code-eff-freq-{{ $code->id }}"
                                       data-global-freq="{{ $code->default_frequency }}">
                            </td>
                            @else
                            <input type="hidden" name="codes[{{ $code->id }}][freq]" value="">
                            @endif
                            <td class="text-center" style="vertical-align:middle;border-top:1px solid #F1F5F9">
                                <span id="code-eff-rate-{{ $code->id }}"
                                      class="badge"
                                      style="background:{{ $effCodeRate !== null ? '#D1FAE5' : '#F1F5F9' }};
                                             color:{{ $effCodeRate !== null ? '#065F46' : '#94A3B8' }};
                                             font-size:10px">
                                    {{ $effCodeRate !== null ? number_format($effCodeRate, 4) : '—' }}
                                </span>
                                @if($showFreq)
                                <span id="code-eff-freq-{{ $code->id }}"
                                      class="badge ms-1"
                                      style="background:{{ $effCodeFreq !== null ? '#DBEAFE' : '#F1F5F9' }};
                                             color:{{ $effCodeFreq !== null ? '#1E40AF' : '#94A3B8' }};
                                             font-size:10px">
                                    × {{ $effCodeFreq !== null ? number_format($effCodeFreq, 2) : '—' }}
                                </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    @endif

                    @endforeach

                </table>
            </div>
        </div>
    </div>

    {{-- Sticky save bar --}}
    <div class="d-flex align-items-center gap-3 p-3 rounded shadow-sm mb-4"
         style="position:sticky;bottom:16px;z-index:50;
                background:var(--navy);border:1px solid rgba(255,255,255,.15)">
        <div class="flex-grow-1 small" style="color:rgba(255,255,255,.7)">
            <i class="bi bi-lightbulb-fill"></i>
            Leave a field blank to inherit from the global default.
            <strong style="color:#fff">Purple border</strong> = period-specific value set.
            Category rates act as fallbacks for all codes in that category.
        </div>
        <a href="{{ route('admin.budget-periods.show', $budgetPeriod) }}"
           class="btn btn-sm btn-outline-light">Cancel</a>
        <button type="submit" class="btn btn-sm"
                style="background:var(--gold);color:var(--navy);font-weight:700">
            <i class="bi bi-floppy-fill me-1"></i>Save Period Rates
        </button>
    </div>
</form>

@endif

@push('scripts')
<script>
// ── Toggle a single category's code rows ──────────────────────────────────────
function toggleCodes(catId, event) {
    // Don't collapse when the user is clicking a form control inside the row
    if (event) {
        const tag = event.target.tagName;
        if (['INPUT','BUTTON','SELECT','TEXTAREA','LABEL'].includes(tag)) return;
    }

    const tbody   = document.getElementById('codes-' + catId);
    const chevron = document.getElementById('chevron-' + catId);
    if (!tbody) return;

    const isHidden = (tbody.style.display === 'none' || tbody.style.display === '');
    tbody.style.display   = isHidden ? 'table-row-group' : 'none';
    if (chevron) chevron.style.transform = isHidden ? 'rotate(90deg)' : 'rotate(0deg)';
}

// ── Expand or collapse all ────────────────────────────────────────────────────
function toggleAll(expand) {
    document.querySelectorAll('.codes-tbody').forEach(tbody => {
        const catId   = tbody.id.replace('codes-', '');
        const chevron = document.getElementById('chevron-' + catId);
        tbody.style.display   = expand ? 'table-row-group' : 'none';
        if (chevron) chevron.style.transform = expand ? 'rotate(90deg)' : 'rotate(0deg)';
    });
}

// ── Auto-expand categories that already have period-specific code rates ───────
const autoExpand = {!! json_encode($autoExpand) !!};
autoExpand.forEach(id => {
    const tbody   = document.getElementById('codes-' + id);
    const chevron = document.getElementById('chevron-' + id);
    if (tbody)   tbody.style.display = 'table-row-group';
    if (chevron) chevron.style.transform = 'rotate(90deg)';
});

// ── Live-update Effective badge as the user types ─────────────────────────────
document.querySelectorAll('.period-rate-input, .period-freq-input').forEach(input => {
    input.addEventListener('input', function () {
        const badge = document.getElementById(this.dataset.effTarget);
        if (!badge) return;

        const val = this.value.trim();
        if (val !== '' && !isNaN(parseFloat(val))) {
            badge.textContent      = parseFloat(val).toFixed(4);
            badge.style.background = '#D1FAE5';
            badge.style.color      = '#065F46';
            this.style.borderColor = '#7C3AED';
        } else {
            const fallback = parseFloat(this.dataset.globalRate ?? this.dataset.globalFreq);
            if (!isNaN(fallback)) {
                badge.textContent      = fallback.toFixed(4);
                badge.style.background = '#F1F5F9';
                badge.style.color      = '#64748B';
            } else {
                badge.textContent      = '—';
                badge.style.background = '#F1F5F9';
                badge.style.color      = '#94A3B8';
            }
            this.style.borderColor = '#E2E8F0';
        }
    });
});
</script>
@endpush

@endsection
