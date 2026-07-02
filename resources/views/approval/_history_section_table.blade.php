<div class="table-responsive">
<table class="table table-sm mb-0" style="font-size:12px;min-width:700px">
    <thead class="sticky-top" style="top:0;z-index:2">
        <tr style="background:#1B2A4A;color:#fff">
            <th style="min-width:200px">Account</th>
            <th class="text-end border-start border-secondary" style="min-width:70px">Q1</th>
            <th class="text-end" style="min-width:70px">Q2</th>
            <th class="text-end" style="min-width:70px">Q3</th>
            <th class="text-end" style="min-width:70px">Q4</th>
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
            $catTotal = $items->sum('total_amount') + $items->sum(fn($i) => $i->approvedSupplementaryTotal());
            $secBase  = $section['sectionTotal'] > 0 ? $section['sectionTotal'] : 1;
            $catShare = round(($catTotal / $secBase) * 100, 1);
            $catSupp  = $items->sum(fn($i) => $i->approvedSupplementaryTotal());
            $sectionSupp  += $catSupp;
            $sectionTotal += $catTotal;
        @endphp

        {{-- Category header row --}}
        <tr style="background:{{ $section['catBg'] }}">
            <td style="padding-left:12px;font-weight:700;color:{{ $section['textColor'] }}" colspan="7">
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
            $itemSupp     = $item->approvedSupplementaryTotal();
            $itemEffTotal = $item->total_amount + $itemSupp;
            $itemShare    = round(($itemEffTotal / $secBase) * 100, 1);
        @endphp
        <tr>
            <td style="padding-left:28px">
                <div style="font-family:monospace;font-weight:700;font-size:11px;color:var(--navy)">
                    {{ $item->accountCode->code }}
                </div>
                <div style="font-size:11px;color:var(--slate)">{{ $item->accountCode->name }}</div>
            </td>
            <td class="text-end border-start small">{{ number_format($item->q1_amount, 2) }}</td>
            <td class="text-end small">{{ number_format($item->q2_amount, 2) }}</td>
            <td class="text-end small">{{ number_format($item->q3_amount, 2) }}</td>
            <td class="text-end small">{{ number_format($item->q4_amount, 2) }}</td>
            <td class="text-end small" style="color:{{ $itemSupp > 0 ? '#92400E' : 'inherit' }}">
                {{ $itemSupp > 0 ? '+'.number_format($itemSupp, 2) : '—' }}
            </td>
            <td class="text-end fw-semibold small" style="color:var(--navy)">
                {{ number_format($itemEffTotal, 2) }}
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
            <td class="text-end border-start">{{ number_format($items->sum('q1_amount'), 2) }}</td>
            <td class="text-end">{{ number_format($items->sum('q2_amount'), 2) }}</td>
            <td class="text-end">{{ number_format($items->sum('q3_amount'), 2) }}</td>
            <td class="text-end">{{ number_format($items->sum('q4_amount'), 2) }}</td>
            <td class="text-end" style="color:{{ $catSupp > 0 ? '#92400E' : 'inherit' }}">
                {{ $catSupp > 0 ? '+'.number_format($catSupp, 2) : '—' }}
            </td>
            <td class="text-end" style="color:var(--navy)">{{ number_format($catTotal, 2) }}</td>
            <td></td>
        </tr>
        @endforeach

        {{-- Section total --}}
        <tr style="background:{{ $section['totalBg'] }};color:#fff;font-weight:700;border-top:2px solid #fff">
            <td style="padding-left:12px;font-size:13px">{{ $section['totalLabel'] }}</td>
            <td class="text-end border-start border-secondary">—</td>
            <td class="text-end">—</td>
            <td class="text-end">—</td>
            <td class="text-end">—</td>
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
        <td class="text-end border-start border-secondary" colspan="5">—</td>
        <td class="text-end fs-6" style="color:{{ $netRow['color'] }}">
            {{ number_format($netRow['value'], 2) }}
        </td>
        <td></td>
    </tr>
    @endif

    </tbody>
</table>
</div>
