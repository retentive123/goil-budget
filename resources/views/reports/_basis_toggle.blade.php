{{--
    Budget Basis Toggle — Original / Revised
    Requires: $basis (string), $revisionCount (int), $period (nullable BudgetPeriod)
    Usage: @include('reports._basis_toggle')
    Add  <input type="hidden" name="budget_basis" value="{{ $basis ?? 'original' }}">
    to the enclosing <form> so form submissions preserve the chosen basis.
--}}
@php
    $currentBasis = $basis ?? 'original';
    $count        = $revisionCount ?? 0;
    $hasRev       = $count > 0;
@endphp

{{-- Always render both buttons; disable Revised when no revisions exist --}}
<div style="display:flex;border-radius:7px;overflow:hidden;border:1px solid #CBD5E1">

    {{-- Original --}}
    <a href="{{ request()->fullUrlWithQuery(['budget_basis' => 'original']) }}"
       style="display:flex;align-items:center;gap:5px;padding:5px 14px;font-size:12px;font-weight:600;
              text-decoration:none;white-space:nowrap;
              {{ $currentBasis === 'original'
                  ? 'background:var(--navy);color:#fff'
                  : 'background:#F8FAFC;color:var(--navy)' }}">
        <i class="bi bi-file-earmark"></i>
        Original
    </a>

    {{-- Divider --}}
    <div style="width:1px;background:#CBD5E1"></div>

    {{-- Revised — disabled (greyed) when no approved revisions exist --}}
    @if($hasRev)
    <a href="{{ request()->fullUrlWithQuery(['budget_basis' => 'revised']) }}"
       style="display:flex;align-items:center;gap:5px;padding:5px 14px;font-size:12px;font-weight:600;
              text-decoration:none;white-space:nowrap;
              {{ $currentBasis === 'revised'
                  ? 'background:#7C3AED;color:#fff'
                  : 'background:#F8FAFC;color:#7C3AED' }}">
        <i class="bi bi-pencil-square"></i>
        Revised
        <span style="font-size:10px;padding:1px 5px;border-radius:10px;
                     {{ $currentBasis === 'revised'
                         ? 'background:rgba(255,255,255,.22);color:#fff'
                         : 'background:#EDE9FE;color:#5B21B6' }}">
            {{ $count }}
        </span>
    </a>
    @else
    <span title="No approved revisions for this period"
          style="display:flex;align-items:center;gap:5px;padding:5px 14px;font-size:12px;font-weight:600;
                 white-space:nowrap;background:#F8FAFC;color:#CBD5E1;cursor:not-allowed">
        <i class="bi bi-pencil-square"></i>
        Revised
    </span>
    @endif

</div>

@if($currentBasis === 'revised' && $hasRev)
<div class="mt-1" style="font-size:10px;color:#7C3AED">
    <i class="bi bi-info-circle me-1"></i>
    Showing revised budget · {{ $count }} dept{{ $count !== 1 ? 's' : '' }} revised
</div>
@endif
