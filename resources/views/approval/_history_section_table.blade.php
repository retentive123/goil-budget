@php $isMonthly = ($entryMode ?? 'quarterly') === 'monthly'; @endphp
<div class="table-responsive">
<table class="table table-sm mb-0" style="font-size:12px;{{ $isMonthly ? 'min-width:1300px' : 'min-width:700px' }}">
    <thead class="sticky-top" style="top:0;z-index:2">
        <tr style="background:#1B2A4A;color:#fff">
            <th style="min-width:200px">Account</th>
            @if($isMonthly)
                @foreach(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $ml)
                <th class="text-end{{ $loop->first ? ' border-start border-secondary' : '' }}" style="min-width:65px">{{ $ml }}</th>
                @endforeach
            @else
                <th class="text-end border-start border-secondary" style="min-width:70px">Q1</th>
                <th class="text-end" style="min-width:70px">Q2</th>
                <th class="text-end" style="min-width:70px">Q3</th>
                <th class="text-end" style="min-width:70px">Q4</th>
            @endif
            <th class="text-end" style="min-width:70px">Supp.</th>
            <th class="text-end" style="min-width:100px">Total</th>
            <th style="min-width:80px">Share</th>
        </tr>
    </thead>
    <tbody>
    @foreach($sections as $section)

        {{-- Section header --}}
        <tr style="background:{{ $section['bg'] }};color:#fff">
            <td colspan="99" style="font-size:11px;font-weight:700;letter-spacing:1px;padding:7px 12px">
                {{ $section['label'] }}
            </td>
        </tr>

        @php
            $sectionSupp  = 0;
            $sectionTotal = 0;
        @endphp

        @foreach($section['byCategory'] as $catName => $items)
        @php
            $catTotal = $items->sum(fn($i) => ($reductions ?? collect())->has($i->id)
                ? ($reductions ?? collect())->get($i->id)
                : ((float) $i->total_amount + $i->approvedSupplementaryTotal()));
            $secBase  = $section['sectionTotal'] > 0 ? $section['sectionTotal'] : 1;
            $catShare = round(($catTotal / $secBase) * 100, 1);
            $catSupp  = $items->sum(fn($i) => ($reductions ?? collect())->has($i->id)
                ? 0
                : $i->approvedSupplementaryTotal());
            $sectionSupp  += $catSupp;
            $sectionTotal += $catTotal;
        @endphp

        {{-- Category header row --}}
        <tr style="background:{{ $section['catBg'] }}">
            <td style="padding-left:12px;font-weight:700;color:{{ $section['textColor'] }}" colspan="{{ $isMonthly ? 14 : 7 }}">
                {{ $catName }}
                <span class="text-muted fw-normal ms-1" style="font-size:10px">({{ $items->count() }})</span>
            </td>
            <td>
                <div class="progress mb-0" style="height:4px;border-radius:2px">
                    <div class="progress-bar" style="width:{{ $catShare }}%;background:{{ $section['textColor'] }}"></div>
                </div>
                <div style="font-size:10px;color:var(--slate)">{{ $catShare }}%</div>
            </td>
        </tr>

        {{-- Item rows --}}
        @foreach($items->sortByDesc('total_amount') as $item)
        @php
            $itemSupp      = $item->approvedSupplementaryTotal();
            $itemEffTotal  = $item->total_amount + $itemSupp;
            $reducedAmount = ($reductions ?? collect())->get($item->id);
            $displayTotal  = $reducedAmount !== null ? $reducedAmount : $itemEffTotal;
            $itemShare     = round(($displayTotal / $secBase) * 100, 1);
            $lia           = ($reductionLias ?? collect())->get($item->id);
            $liaHasPeriods = $lia && $lia->m1_amount !== null;
            // Pre-compute reduced quarter totals from lia m-amounts
            $liaQ = $liaHasPeriods ? [
                'q1' => $lia->m1_amount + $lia->m2_amount + $lia->m3_amount,
                'q2' => $lia->m4_amount + $lia->m5_amount + $lia->m6_amount,
                'q3' => $lia->m7_amount + $lia->m8_amount + $lia->m9_amount,
                'q4' => $lia->m10_amount + $lia->m11_amount + $lia->m12_amount,
            ] : null;
        @endphp
        <tr>
            <td style="padding-left:28px">
                <div style="font-family:monospace;font-weight:700;font-size:11px;color:var(--navy)">
                    {{ $item->accountCode->code }}
                </div>
                <div style="font-size:11px;color:var(--slate)">{{ $item->accountCode->name }}</div>
            </td>
            @if($isMonthly)
                @foreach(range(1,12) as $mn)
                @php
                    $cellAmt   = $liaHasPeriods ? (float)($lia->{'m'.$mn.'_amount'} ?? 0) : $item->{'m'.$mn.'_amount'};
                    $cellStyle = $reducedAmount !== null ? ($liaHasPeriods ? 'color:#B45309;font-weight:600' : 'color:#94A3B8') : '';
                    $cellTitle = $reducedAmount !== null ? ($liaHasPeriods ? 'Reduced period amount' : 'Original period figures; total was reduced by approver') : '';
                @endphp
                <td class="text-end small{{ $mn === 1 ? ' border-start' : '' }}"
                    style="{{ $cellStyle }}"
                    title="{{ $cellTitle }}">
                    {{ number_format($cellAmt, 2) }}
                </td>
                @endforeach
            @else
                @foreach(['q1','q2','q3','q4'] as $qi => $qk)
                @php
                    if ($liaHasPeriods && $liaQ) {
                        $cellAmt   = $liaQ[$qk];
                        $cellStyle = 'color:#B45309;font-weight:600';
                        $cellTitle = 'Reduced period amount';
                    } else {
                        $cellAmt   = $item->{$qk.'_amount'};
                        $cellStyle = $reducedAmount !== null ? 'color:#94A3B8' : '';
                        $cellTitle = $reducedAmount !== null ? 'Original period figures; total was reduced by approver' : '';
                    }
                @endphp
                <td class="text-end small{{ $qi === 0 ? ' border-start' : '' }}"
                    style="{{ $cellStyle }}"
                    title="{{ $cellTitle }}">
                    {{ number_format($cellAmt, 2) }}
                </td>
                @endforeach
            @endif
            <td class="text-end small" style="color:{{ $itemSupp > 0 ? '#92400E' : 'inherit' }}">
                {{ $itemSupp > 0 ? '+'.number_format($itemSupp, 2) : '—' }}
            </td>
            <td class="text-end fw-semibold small">
                @if($reducedAmount !== null)
                    <span style="text-decoration:line-through;color:#94A3B8;font-size:10px">
                        {{ number_format($itemEffTotal, 2) }}
                    </span><br>
                    <span style="color:#B45309" title="Reduced by approver">
                        {{ number_format($reducedAmount, 2) }}
                        <i class="bi bi-arrow-down-circle-fill" style="font-size:9px"></i>
                    </span>
                @else
                    <span style="color:var(--navy)">{{ number_format($itemEffTotal, 2) }}</span>
                @endif
            </td>
            <td>
                <div class="progress mb-0" style="height:4px">
                    <div class="progress-bar" style="width:{{ $itemShare }}%;background:var(--navy)"></div>
                </div>
                <div style="font-size:10px;color:var(--slate)">{{ $itemShare }}%</div>
            </td>
        </tr>
        @endforeach

        {{-- Category subtotal --}}
        <tr style="background:#F8FAFC;font-weight:700;font-size:11px">
            <td style="padding-left:12px;color:var(--slate)">{{ $catName }} Subtotal</td>
            @if($isMonthly)
                @foreach(range(1,12) as $mn)
                <td class="text-end{{ $mn === 1 ? ' border-start' : '' }}">{{ number_format($items->sum("m{$mn}_amount"), 2) }}</td>
                @endforeach
            @else
                <td class="text-end border-start">{{ number_format($items->sum('q1_amount'), 2) }}</td>
                <td class="text-end">{{ number_format($items->sum('q2_amount'), 2) }}</td>
                <td class="text-end">{{ number_format($items->sum('q3_amount'), 2) }}</td>
                <td class="text-end">{{ number_format($items->sum('q4_amount'), 2) }}</td>
            @endif
            <td class="text-end" style="color:{{ $catSupp > 0 ? '#92400E' : 'inherit' }}">
                {{ $catSupp > 0 ? '+'.number_format($catSupp, 2) : '—' }}
            </td>
            <td class="text-end fw-semibold" style="color:var(--navy)">{{ number_format($catTotal, 2) }}</td>
            <td></td>
        </tr>
        @endforeach

        {{-- Section total --}}
        <tr style="background:{{ $section['totalBg'] }};color:#fff;font-weight:700;border-top:2px solid #fff">
            <td style="padding-left:12px;font-size:13px">{{ $section['totalLabel'] }}</td>
            @if($isMonthly)
                <td class="text-end border-start border-secondary" colspan="12">—</td>
            @else
                <td class="text-end border-start border-secondary" colspan="4">—</td>
            @endif
            <td class="text-end" style="color:{{ $sectionSupp > 0 ? '#FDE68A' : 'rgba(255,255,255,.5)' }}">
                {{ $sectionSupp > 0 ? '+'.number_format($sectionSupp, 2) : '—' }}
            </td>
            <td class="text-end fs-6">{{ number_format($sectionTotal, 2) }}</td>
            <td></td>
        </tr>

        {{-- Spacer between sections --}}
        @if(!$loop->last)
        <tr style="height:4px;background:#F8FAFC"><td colspan="99"></td></tr>
        @endif

    @endforeach

    {{-- Net row (IS tab only) --}}
    @if(!empty($netRow))
    <tr style="background:#0F172A;color:#fff;font-weight:700;font-size:13px;border-top:3px solid #E2E8F0">
        <td style="padding-left:12px">{{ $netRow['label'] }}</td>
        <td class="text-end border-start border-secondary" colspan="{{ $isMonthly ? 13 : 5 }}">—</td>
        <td class="text-end fs-6" style="color:{{ $netRow['color'] }}">
            {{ number_format($netRow['value'], 2) }}
        </td>
        <td></td>
    </tr>
    @endif

    </tbody>
</table>
</div>
