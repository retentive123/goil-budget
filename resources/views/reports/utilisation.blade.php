@extends('layouts.app')
@section('title', 'Budget Utilisation')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Budget Utilisation</h5>
        <p class="text-muted small mb-0">
            <a href="{{ route('reports.index') }}" class="text-muted">Reports</a>
            / Utilisation
        </p>
    </div>
    @can('export reports')
    <a href="{{ route('reports.export.utilisation', request()->query()) }}"
       class="btn btn-sm btn-outline-success">
        <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
    </a>
    @endcan
</div>

<form method="GET" class="chart-card mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Period</label>
            <select name="period_id" class="form-select form-select-sm"
                    onchange="this.form.submit()">
                @foreach($periods as $p)
                <option value="{{ $p->id }}"
                    {{ request('period_id',$period?->id)==$p->id?'selected':'' }}>
                    {{ $p->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-auto ms-auto">
            <div style="display:flex;align-items:center;gap:10px;
                        background:#F1F5F9;border:1px solid #CBD5E1;
                        border-radius:10px;padding:6px 12px">
                <span style="font-size:11px;font-weight:600;color:#64748B;
                              white-space:nowrap;letter-spacing:.4px;text-transform:uppercase">
                    View&nbsp;as
                </span>
                <div style="width:1px;height:20px;background:#CBD5E1"></div>
                @include('reports._basis_toggle')
            </div>
        </div>
    </div>
    <input type="hidden" name="budget_basis" value="{{ $basis ?? 'original' }}">
</form>

{{-- Status summary --}}
<div class="row g-3 mb-4">
    @php
        $critical = $utilisation->where('status','critical')->count();
        $warning  = $utilisation->where('status','warning')->count();
        $healthy  = $utilisation->where('status','healthy')->count();
    @endphp
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-accent" style="background:#F43F5E"></div>
            <div class="stat-label">Critical (>90%)</div>
            <div class="stat-value" style="color:#F43F5E">{{ $critical }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-accent" style="background:#F59E0B"></div>
            <div class="stat-label">Warning (70–90%)</div>
            <div class="stat-value" style="color:#F59E0B">{{ $warning }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-accent" style="background:#10B981"></div>
            <div class="stat-label">Healthy (<70%)</div>
            <div class="stat-value" style="color:#10B981">{{ $healthy }}</div>
        </div>
    </div>
</div>

{{-- Utilisation chart --}}
@if($utilisation->count())
<div class="chart-card mb-4">
    <div class="chart-title">Utilisation by Department</div>
    <canvas id="utilisationBar" height="100"></canvas>
</div>

{{-- Department rows --}}
<div class="d-flex flex-column gap-2 mb-4">
@foreach($utilisation as $rowIdx => $row)
@php
    $liCount  = !empty($row['line_items']) ? $row['line_items']->count() : 0;
    $barColor = match($row['status']) { 'critical' => '#F43F5E', 'warning' => '#F59E0B', default => '#10B981' };
    $pillBg   = match($row['status']) { 'critical' => '#FEE2E2', 'warning' => '#FEF3C7', default => '#D1FAE5' };
    $pillFg   = match($row['status']) { 'critical' => '#991B1B', 'warning' => '#92400E', default => '#065F46' };
@endphp

{{-- Department summary card --}}
<div class="chart-card mb-0" style="border-left:4px solid {{ $barColor }}">
    <div class="d-flex justify-content-between align-items-start mb-2">
        <div>
            <div class="fw-semibold" style="font-size:14px">{{ $row['department'] }}</div>
            @if($row['code'])
            <div style="font-size:11px;color:var(--slate)">{{ $row['code'] }}</div>
            @endif
        </div>
        <span style="padding:4px 12px;border-radius:20px;font-size:12px;
                     font-weight:600;background:{{ $pillBg }};color:{{ $pillFg }};
                     white-space:nowrap">
            {{ $row['utilisation_pct'] }}% used
        </span>
    </div>

    <div class="progress mb-3" style="height:10px;border-radius:5px;background:#F1F5F9">
        <div class="progress-bar" role="progressbar"
             style="width:{{ min($row['utilisation_pct'],100) }}%;
                    background:{{ $barColor }};border-radius:5px">
        </div>
    </div>

    <div class="row g-0 text-center">
        <div class="col border-end" style="padding:6px 0">
            <div style="font-size:10px;color:var(--slate);text-transform:uppercase;letter-spacing:.4px">Budget</div>
            <div style="font-size:13px;font-weight:600;color:var(--navy)">
                {{ currency() }} {{ number_format($row['approved'],0) }}
            </div>
        </div>
        <div class="col border-end" style="padding:6px 0">
            <div style="font-size:10px;color:var(--slate);text-transform:uppercase;letter-spacing:.4px">Actual Spent</div>
            <div style="font-size:13px;font-weight:600;color:{{ $barColor }}">
                {{ currency() }} {{ number_format($row['actual'],0) }}
            </div>
        </div>
        <div class="col" style="padding:6px 0">
            <div style="font-size:10px;color:var(--slate);text-transform:uppercase;letter-spacing:.4px">Remaining</div>
            <div style="font-size:13px;font-weight:600;color:var(--navy)">
                {{ currency() }} {{ number_format($row['remaining'],0) }}
            </div>
        </div>
    </div>
</div>

{{-- Collapsible line items button & panel --}}
@if($liCount > 0)
<button class="btn w-100 d-flex justify-content-between align-items-center collapsed"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#li-rows-{{ $rowIdx }}"
        aria-expanded="false"
        style="background:#F8FAFC;border:1px solid var(--border);border-radius:8px;
               padding:8px 16px;font-size:12px;font-weight:600;color:var(--navy);
               transition:background .15s">
    <span>
        <i class="bi bi-list-ul me-2" style="color:{{ $barColor }}"></i>
        {{ $row['department'] }} — {{ $liCount }} line item{{ $liCount !== 1 ? 's' : '' }}
    </span>
    <i class="bi bi-chevron-down" style="font-size:11px;transition:transform .2s"
       id="chevron-{{ $rowIdx }}"></i>
</button>

<div class="collapse" id="li-rows-{{ $rowIdx }}">
    <div class="chart-card mb-0" style="border-top:3px solid {{ $barColor }};overflow-x:auto">
        <table class="table table-sm table-hover mb-0" style="font-size:12px;min-width:600px">
            <thead>
                <tr style="background:#F8FAFC">
                    <th style="width:90px">Code</th>
                    <th>Account Name</th>
                    <th style="width:110px">Category</th>
                    <th class="text-end" style="width:120px">Actual Spent</th>
                    <th class="text-end" style="width:120px">Budget</th>
                    <th class="text-end" style="width:90px">Utilisation</th>
                </tr>
            </thead>
            <tbody>
                @foreach($row['line_items'] as $li)
                @php
                    $liBg  = $li['status']==='critical'?'#FEE2E2':($li['status']==='warning'?'#FEF3C7':'transparent');
                    $liFg  = $li['status']==='critical'?'#991B1B':($li['status']==='warning'?'#92400E':'#065F46');
                    $liBar = $li['status']==='critical'?'#F43F5E':($li['status']==='warning'?'#F59E0B':'#10B981');
                @endphp
                <tr>
                    <td class="text-muted" style="font-family:monospace;font-size:11px">{{ $li['code'] }}</td>
                    <td>
                        <span title="{{ $li['name'] }}">{{ $li['name'] }}</span>
                    </td>
                    <td class="text-muted" style="font-size:11px">{{ $li['category'] }}</td>
                    <td class="text-end fw-semibold" style="color:{{ $liBar }}">
                        {{ currency() }} {{ number_format($li['actual'],0) }}
                    </td>
                    <td class="text-end" style="color:var(--slate)">
                        {{ currency() }} {{ number_format($li['budget'],0) }}
                    </td>
                    <td class="text-end">
                        <span style="padding:2px 8px;border-radius:10px;font-size:11px;
                                     font-weight:700;background:{{ $liBg }};color:{{ $liFg }}">
                            {{ $li['pct'] }}%
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#F1F5F9;font-weight:600">
                    <td colspan="3" class="text-muted" style="font-size:11px">Department Total</td>
                    <td class="text-end" style="color:{{ $barColor }}">
                        {{ currency() }} {{ number_format($row['actual'],0) }}
                    </td>
                    <td class="text-end" style="color:var(--navy)">
                        {{ currency() }} {{ number_format($row['approved'],0) }}
                    </td>
                    <td class="text-end">
                        <span style="padding:2px 8px;border-radius:10px;font-size:11px;
                                     font-weight:700;background:{{ $pillBg }};color:{{ $pillFg }}">
                            {{ $row['utilisation_pct'] }}%
                        </span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

@endforeach
</div>

<script>
new Chart(document.getElementById('utilisationBar'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($utilisation->pluck('department')->toArray()) !!},
        datasets: [{
            label: 'Utilised %',
            data:  {!! json_encode($utilisation->pluck('utilisation_pct')->toArray()) !!},
            backgroundColor: {!! json_encode($utilisation->map(fn($r) =>
                match($r['status']) {
                    'critical' => '#F43F5E',
                    'warning'  => '#F59E0B',
                    default    => '#10B981'
                }
            )->toArray()) !!},
            borderRadius: 6, borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true, max: 100,
                grid: { color: '#F1F5F9' },
                ticks: { callback: v => v + '%', font: { size: 11 } }
            },
            x: { grid: { display: false }, ticks: { font: { size: 11 } } }
        }
    }
});

// Rotate chevron icon when collapse opens/closes
document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn => {
    const target = btn.getAttribute('data-bs-target');
    const chevron = btn.querySelector('.bi-chevron-down');
    if (!chevron) return;
    document.querySelector(target)?.addEventListener('show.bs.collapse',  () => chevron.style.transform = 'rotate(180deg)');
    document.querySelector(target)?.addEventListener('hide.bs.collapse',  () => chevron.style.transform = 'rotate(0deg)');
});
</script>
@else
<div class="chart-card text-center py-5 text-muted">
    No approved budgets found for this period.
</div>
@endif
@endsection
