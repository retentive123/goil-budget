<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Approved Budget — {{ $period?->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; margin: 0; padding: 0; }
        h1   { font-size: 14px; margin-bottom: 4px; }
        p    { margin: 0 0 8px; color: #666; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th   { background: #1d3557; color: #fff; padding: 5px 6px; text-align: left; font-size: 9px; }
        td   { padding: 4px 6px; border-bottom: 1px solid #e5e5e5; font-size: 9px; }
        tr:nth-child(even) td { background: #f8f9fa; }
        .category { background: #e9ecef; font-weight: bold; padding: 4px 6px; font-size: 9px; }
        .total-row td { font-weight: bold; background: #f0f4f8; }
        .text-right { text-align: right; }

        /* ── Branded header ── */
        .pdf-header {
            width: 100%;
            border-bottom: 3px solid #E65C00;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .pdf-header table { margin: 0; border: none; }
        .pdf-header td { border: none; padding: 2px 4px; background: transparent; }
        .pdf-header .logo { max-height: 48px; max-width: 140px; }
        .pdf-header .company-name { font-size: 14px; font-weight: bold; color: #1B2A4A; }
        .pdf-header .report-title { font-size: 11px; color: #E65C00; font-weight: bold; }
        .pdf-header .meta { font-size: 8px; color: #666; }

        /* ── Branded footer (fixed at bottom of every page) ── */
        .pdf-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 1px solid #D4CCB8;
            padding: 5px 0 3px;
            font-size: 7.5px;
            color: #999;
            text-align: center;
            background: #fff;
        }
        .pdf-footer .footer-bar {
            height: 2px;
            background: linear-gradient(to right, #1B2A4A, #E65C00);
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
@php
    $isMonthly   = ($entryMode ?? 'quarterly') === 'monthly';
    $periodCols  = $isMonthly
        ? ['m1'=>'Jan','m2'=>'Feb','m3'=>'Mar','m4'=>'Apr','m5'=>'May','m6'=>'Jun',
           'm7'=>'Jul','m8'=>'Aug','m9'=>'Sep','m10'=>'Oct','m11'=>'Nov','m12'=>'Dec']
        : ['q1'=>'Q1','q2'=>'Q2','q3'=>'Q3','q4'=>'Q4'];
    $colspan     = count($periodCols) + 4;
    $companyName = \App\Models\SystemSetting::get('company_name', 'Ghana Oil Company Limited');
    $logoPath    = \App\Models\SystemSetting::get('company_logo', '');
    $footerText  = \App\Models\SystemSetting::get('report_footer_text', 'GOIL Budget Management System — Confidential');
    // Resolve logo to an absolute path for DomPDF (it cannot fetch external URLs in sandboxed mode)
    $logoSrc = '';
    if ($logoPath) {
        $abs = public_path($logoPath);
        if (file_exists($abs)) {
            $logoSrc = 'file://' . str_replace('\\', '/', $abs);
        } else {
            $logoSrc = $logoPath; // use as-is if it looks like a URL
        }
    }
@endphp

{{-- ── Fixed footer (rendered on every page) ── --}}
<div class="pdf-footer">
    <div class="footer-bar"></div>
    {{ $footerText }} &nbsp;·&nbsp; Generated: {{ now()->format('d M Y H:i') }}
    &nbsp;·&nbsp; {{ $companyName }}
</div>

{{-- ── Page header with branding ── --}}
<div class="pdf-header">
    <table style="width:100%">
        <tr>
            @if($logoSrc)
            <td style="width:160px;vertical-align:middle">
                <img src="{{ $logoSrc }}" class="logo" alt="{{ $companyName }}">
            </td>
            @endif
            <td style="vertical-align:middle">
                <div class="company-name">{{ $companyName }}</div>
                <div class="report-title">Approved Budget Report</div>
                <div class="meta">
                    Period: {{ $period?->name }}
                    &nbsp;·&nbsp; Generated: {{ now()->format('d M Y H:i') }}
                    &nbsp;·&nbsp; Basis: Original + Approved Supplementary
                </div>
            </td>
            <td style="text-align:right;vertical-align:top">
                <div style="font-size:8px;color:#999">CONFIDENTIAL</div>
                <div style="font-size:8px;color:#999;margin-top:2px">
                    {{ $period?->start_date?->format('d M Y') }} –
                    {{ $period?->end_date?->format('d M Y') }}
                </div>
            </td>
        </tr>
    </table>
</div>

    <table style="width:100%;border-collapse:collapse;font-size:10px">
        <thead>
            <tr style="background:#1B2A4A;color:#fff">
                <th style="padding:6px;text-align:left">Code</th>
                <th style="padding:6px;text-align:left">Account</th>
                @foreach($periodCols as $pl)
                <th style="padding:6px;text-align:right">{{ $pl }}</th>
                @endforeach
                <th style="padding:6px;text-align:right">Original</th>
                <th style="padding:6px;text-align:right">Supplementary</th>
                <th style="padding:6px;text-align:right">Effective Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($versions as $v)
            <tr style="background:#F1F5F9">
                <td colspan="{{ $colspan }}" style="padding:6px;font-weight:bold">
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
                @foreach($periodCols as $pk => $pl)
                <td style="padding:4px;border-bottom:1px solid #E2E8F0;text-align:right">{{ number_format($item->{$pk.'_amount'},2) }}</td>
                @endforeach
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
