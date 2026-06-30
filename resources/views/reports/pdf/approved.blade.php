<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Approved Budget — {{ $period?->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; }
        h1   { font-size: 14px; margin-bottom: 4px; }
        p    { margin: 0 0 8px; color: #666; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th   { background: #1d3557; color: #fff; padding: 5px 6px; text-align: left; font-size: 9px; }
        td   { padding: 4px 6px; border-bottom: 1px solid #e5e5e5; font-size: 9px; }
        tr:nth-child(even) td { background: #f8f9fa; }
        .category { background: #e9ecef; font-weight: bold; padding: 4px 6px; font-size: 9px; }
        .total-row td { font-weight: bold; background: #f0f4f8; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>{{ setting('company_name', 'GOIL') }} — Approved Budget Report</h1>
    <p>Period: {{ $period?->name }} &nbsp;|&nbsp; Generated: {{ now()->format('d M Y H:i') }}</p>

    <table style="width:100%;border-collapse:collapse;font-size:10px">
        <thead>
            <tr style="background:#1B2A4A;color:#fff">
                <th style="padding:6px;text-align:left">Code</th>
                <th style="padding:6px;text-align:left">Account</th>
                <th style="padding:6px;text-align:right">Q1</th>
                <th style="padding:6px;text-align:right">Q2</th>
                <th style="padding:6px;text-align:right">Q3</th>
                <th style="padding:6px;text-align:right">Q4</th>
                <th style="padding:6px;text-align:right">Original</th>
                <th style="padding:6px;text-align:right">Supplementary</th>
                <th style="padding:6px;text-align:right">Effective Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($versions as $v)
            <tr style="background:#F1F5F9">
                <td colspan="9" style="padding:6px;font-weight:bold">
                    {{ $v->department->name }} — {{ $v->period->name }}
                </td>
            </tr>
            @foreach($v->lineItems as $item)
            @php
                $orig = $item->total_amount;
                $supp = $item->approvedSupplementaryTotal();
                $eff  = $orig + $supp;
            @endphp
            <tr>
                <td style="padding:4px;border-bottom:1px solid #E2E8F0">{{ $item->accountCode->code }}</td>
                <td style="padding:4px;border-bottom:1px solid #E2E8F0">{{ $item->accountCode->name }}</td>
                <td style="padding:4px;border-bottom:1px solid #E2E8F0;text-align:right">{{ number_format($item->q1_amount,2) }}</td>
                <td style="padding:4px;border-bottom:1px solid #E2E8F0;text-align:right">{{ number_format($item->q2_amount,2) }}</td>
                <td style="padding:4px;border-bottom:1px solid #E2E8F0;text-align:right">{{ number_format($item->q3_amount,2) }}</td>
                <td style="padding:4px;border-bottom:1px solid #E2E8F0;text-align:right">{{ number_format($item->q4_amount,2) }}</td>
                <td style="padding:4px;border-bottom:1px solid #E2E8F0;text-align:right">{{ number_format($orig,2) }}</td>
                <td style="padding:4px;border-bottom:1px solid #E2E8F0;text-align:right;color:#92400E">
                    {{ $supp > 0 ? '+'.number_format($supp,2) : '—' }}
                </td>
                <td style="padding:4px;border-bottom:1px solid #E2E8F0;text-align:right;font-weight:bold">
                    {{ number_format($eff,2) }}
                </td>
            </tr>
            @endforeach
            @endforeach
        </tbody>
    </table>
</body>
</html>
