@extends('layouts.app')
@section('title', 'Account Code — ' . $accountCode->code)
@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.account-codes.index') }}"
       class="text-muted text-decoration-none small">← Account Codes</a>
    <span class="text-muted">/</span>
    <span class="small">{{ $accountCode->code }}</span>
</div>

<div class="row g-4">

    {{-- Left --}}
    <div class="col-md-8">

        {{-- Header card --}}
        <div class="chart-card mb-4">
            <div class="d-flex justify-content-between align-items-start">
                <div class="d-flex gap-3 align-items-start">
                    <div style="background:var(--navy);color:var(--gold);border-radius:10px;
                                padding:10px 16px;font-family:monospace;font-size:20px;
                                font-weight:700">
                        {{ $accountCode->code }}
                    </div>
                    <div>
                        <div style="font-size:18px;font-weight:700;color:var(--navy)">
                            {{ $accountCode->name }}
                        </div>
                        <div style="font-size:13px;color:var(--slate);margin-top:4px">
                            Category:
                            <a href="{{ route('admin.account-categories.show', $accountCode->account_category_id) }}"
                               style="color:var(--navy);font-weight:600">
                                {{ $accountCode->category->name }}
                            </a>
                        </div>
                        @if($accountCode->description)
                        <div style="font-size:13px;color:var(--slate);margin-top:6px">
                            {{ $accountCode->description }}
                        </div>
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <span style="padding:3px 12px;border-radius:20px;font-size:12px;
                                 font-weight:600;
                                 background:{{ $accountCode->is_active?'#D1FAE5':'#F1F5F9' }};
                                 color:{{ $accountCode->is_active?'#065F46':'#64748B' }}">
                        {{ $accountCode->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <a href="{{ route('admin.account-codes.edit', $accountCode) }}"
                       class="btn btn-sm btn-outline-primary"
                       style="border-radius:8px">
                        Edit
                    </a>
                </div>
            </div>
        </div>

        {{-- Departments using this code --}}
        <div class="chart-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="chart-title mb-0">
                    Departments Using This Code
                    <span style="background:#F1F5F9;border-radius:20px;padding:1px 8px;
                                 font-size:12px;font-weight:400;color:var(--slate)">
                        {{ $accountCode->departments->count() }}
                    </span>
                </div>
            </div>

            @if($accountCode->departments->count())
            <div class="row g-2">
                @foreach($accountCode->departments as $dept)
                <div class="col-md-4">
                    <div style="background:#F8FAFC;border:1px solid var(--border);
                                border-radius:8px;padding:10px 12px">
                        <div style="font-size:12px;font-weight:600;color:var(--navy)">
                            {{ $dept->name }}
                        </div>
                        <div style="font-size:10px;color:var(--slate)">
                            {{ $dept->code }}
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-muted small text-center py-3">
                Not assigned to any departments yet.
            </div>
            @endif
        </div>

        {{-- Default Sub-entries --}}
        <div class="chart-card mb-4" id="sub-entry-templates-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="chart-title mb-0">
                    Default Sub-entries
                    <span id="tpl-count-badge"
                          style="background:#EEF2FF;border-radius:20px;padding:1px 8px;
                                 font-size:12px;font-weight:400;color:#4338CA">
                        {{ $accountCode->subEntryTemplates->count() }}
                    </span>
                </div>
            </div>
            <p class="text-muted small mb-3" style="line-height:1.5">
                When a budget line item using this account code has no sub-entries yet,
                these rows will auto-appear at entry time. Users can edit amounts, remove rows, or add more.
            </p>

            {{-- Template list --}}
            <div id="tpl-list">
                @forelse($accountCode->subEntryTemplates as $tpl)
                <div class="d-flex align-items-center gap-2 py-2 px-3 mb-1 tpl-row"
                     id="tpl-row-{{ $tpl->id }}"
                     style="background:#F8FAFC;border:1px solid var(--border);border-radius:8px">
                    <span class="tpl-row-num" style="min-width:20px;font-size:11px;font-weight:700;color:#818CF8;text-align:right">{{ $loop->iteration }}</span>
                    <span class="flex-grow-1 small fw-semibold" style="color:var(--navy)">{{ $tpl->name }}</span>
                    <button type="button"
                            onclick="deleteTpl({{ $tpl->id }}, this)"
                            style="width:24px;height:24px;border-radius:50%;background:#FEE2E2;
                                   color:#991B1B;border:1px solid #FECACA;cursor:pointer;
                                   font-size:13px;font-weight:700;display:flex;align-items:center;
                                   justify-content:center;padding:0;line-height:1;flex-shrink:0"
                            title="Remove">×</button>
                </div>
                @empty
                <div id="tpl-empty" class="text-muted small text-center py-2">
                    No default sub-entries defined yet.
                </div>
                @endforelse
            </div>

            {{-- Add form --}}
            <div class="mt-3 d-flex gap-2">
                <input type="text" id="tpl-name-input"
                       class="form-control form-control-sm"
                       placeholder="Sub-entry name (e.g. Basic Salary)"
                       maxlength="255"
                       style="border-radius:8px">
                <button type="button" onclick="addTpl()"
                        class="btn btn-sm"
                        style="white-space:nowrap;background:var(--navy);color:#fff;
                               border-radius:8px;padding:5px 14px;font-size:13px">
                    + Add
                </button>
            </div>
        </div>

        {{-- Budget history across all periods --}}
        @php
            $lineItems = \App\Models\BudgetLineItem::where('account_code_id', $accountCode->id)
                ->whereHas('budgetVersion', fn($q) => $q->where('status','approved'))
                ->with('budgetVersion.period','budgetVersion.department')
                ->get();

            $byPeriod = $lineItems->groupBy('budgetVersion.period.name');
        @endphp

        @if($lineItems->count())
        <div class="chart-card mb-4">
            <div class="chart-title">Budget History (Approved Only)</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead style="font-size:11px;text-transform:uppercase;
                                  letter-spacing:.5px;color:var(--slate)">
                        <tr>
                            <th>Period</th>
                            <th>Department</th>
                            <th class="text-end">Q1</th>
                            <th class="text-end">Q2</th>
                            <th class="text-end">Q3</th>
                            <th class="text-end">Q4</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lineItems->sortByDesc('budgetVersion.period.year') as $item)
                        <tr>
                            <td class="small fw-semibold">
                                {{ $item->budgetVersion->period->name }}
                            </td>
                            <td class="small">
                                {{ $item->budgetVersion->subsidiary?->name ?? $item->budgetVersion->department?->name }}
                            </td>
                            <td class="text-end small">{{ number_format($item->q1_amount,0) }}</td>
                            <td class="text-end small">{{ number_format($item->q2_amount,0) }}</td>
                            <td class="text-end small">{{ number_format($item->q3_amount,0) }}</td>
                            <td class="text-end small">{{ number_format($item->q4_amount,0) }}</td>
                            <td class="text-end small fw-semibold" style="color:var(--navy)">
                                GHS {{ number_format($item->total_amount,0) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot style="background:#F8FAFC;font-weight:700;font-size:12px">
                        <tr>
                            <td colspan="6">Grand Total</td>
                            <td class="text-end" style="color:var(--navy)">
                                GHS {{ number_format($lineItems->sum('total_amount'),0) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endif

    </div>

    {{-- Right --}}
    <div class="col-md-4">

        {{-- Quick stats --}}
        <div class="chart-card mb-4">
            <div class="chart-title">Quick Stats</div>
            <div class="row g-3">
                <div class="col-6">
                    <div style="font-size:11px;color:var(--slate)">Total Budget (All Time)</div>
                    <div style="font-size:15px;font-weight:700;color:var(--navy);margin-top:4px">
                        GHS {{ number_format($lineItems->sum('total_amount'),0) }}
                    </div>
                </div>
                <div class="col-6">
                    <div style="font-size:11px;color:var(--slate)">Dept Count</div>
                    <div style="font-size:15px;font-weight:700;color:var(--navy);margin-top:4px">
                        {{ $accountCode->departments->count() }}
                    </div>
                </div>
                <div class="col-6">
                    <div style="font-size:11px;color:var(--slate)">Total Actuals</div>
                    @php
                        $totalActual = \App\Models\BudgetActual::where('account_code_id',$accountCode->id)
                            ->where('status','confirmed')->sum('amount');
                    @endphp
                    <div style="font-size:15px;font-weight:700;color:#10B981;margin-top:4px">
                        GHS {{ number_format($totalActual,0) }}
                    </div>
                </div>
                <div class="col-6">
                    <div style="font-size:11px;color:var(--slate)">Category</div>
                    <div style="font-size:13px;font-weight:700;color:var(--navy);margin-top:4px">
                        {{ $accountCode->category->name }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Year trend chart --}}
        @php
            $yearTrend = $lineItems->groupBy('budgetVersion.period.year')
                ->map(fn($g) => [
                    'budget' => $g->sum('total_amount'),
                    'actual' => \App\Models\BudgetActual::where('account_code_id',$accountCode->id)
                        ->whereIn('budget_line_item_id',$g->pluck('id'))
                        ->where('status','confirmed')->sum('amount'),
                ])
                ->sortKeys();
        @endphp

        @if($yearTrend->count() > 0)
        <div class="chart-card mb-4">
            <div class="chart-title">Year Trend</div>
            <canvas id="yearTrend" height="200"></canvas>
        </div>
        @endif

        {{-- Actions --}}
        <div class="chart-card">
            <div class="chart-title">Actions</div>
            <div class="d-grid gap-2">
                <a href="{{ route('admin.account-codes.edit', $accountCode) }}"
                   class="btn btn-sm text-start"
                   style="background:var(--surface);border:1px solid var(--border);
                          border-radius:8px;padding:10px 14px;font-size:13px;color:var(--navy)">
                    <i class="bi bi-pencil"></i>&nbsp; Edit Account Code
                </a>
                <a href="{{ route('reports.code-explorer', ['account_code_id'=>$accountCode->id]) }}"
                   class="btn btn-sm text-start"
                   style="background:var(--surface);border:1px solid var(--border);
                          border-radius:8px;padding:10px 14px;font-size:13px;color:var(--navy)">
                    <i class="bi bi-bar-chart-line me-2"></i>View in Reports
                </a>
                <a href="{{ route('admin.account-categories.show', $accountCode->account_category_id) }}"
                   class="btn btn-sm text-start"
                   style="background:var(--surface);border:1px solid var(--border);
                          border-radius:8px;padding:10px 14px;font-size:13px;color:var(--navy)">
                    🗂 &nbsp; View Category
                </a>
            </div>
        </div>

    </div>
</div>

<script>
const TPL_STORE_URL   = '{{ route('admin.account-codes.templates.store', $accountCode) }}';
const TPL_DESTROY_URL = '{{ url('admin/account-codes/' . $accountCode->id . '/templates/__ID__') }}';
const CSRF            = '{{ csrf_token() }}';

function renumberTpls() {
    document.querySelectorAll('#tpl-list .tpl-row').forEach((row, i) => {
        const num = row.querySelector('.tpl-row-num');
        if (num) num.textContent = i + 1;
    });
    const badge = document.getElementById('tpl-count-badge');
    if (badge) badge.textContent = document.querySelectorAll('#tpl-list .tpl-row').length;
}

function addTpl() {
    const input = document.getElementById('tpl-name-input');
    const name  = input.value.trim();
    if (!name) { input.focus(); return; }

    fetch(TPL_STORE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ name }),
    })
    .then(r => r.ok ? r.json() : Promise.reject(r))
    .then(tpl => {
        const empty = document.getElementById('tpl-empty');
        if (empty) empty.remove();

        const list = document.getElementById('tpl-list');
        const count = list.querySelectorAll('.tpl-row').length + 1;
        const div = document.createElement('div');
        div.className = 'd-flex align-items-center gap-2 py-2 px-3 mb-1 tpl-row';
        div.id = `tpl-row-${tpl.id}`;
        div.style.cssText = 'background:#F8FAFC;border:1px solid var(--border);border-radius:8px';
        div.innerHTML = `
            <span class="tpl-row-num" style="min-width:20px;font-size:11px;font-weight:700;color:#818CF8;text-align:right">${count}</span>
            <span class="flex-grow-1 small fw-semibold" style="color:var(--navy)">${tpl.name}</span>
            <button type="button" onclick="deleteTpl(${tpl.id}, this)"
                    style="width:24px;height:24px;border-radius:50%;background:#FEE2E2;color:#991B1B;
                           border:1px solid #FECACA;cursor:pointer;font-size:13px;font-weight:700;
                           display:flex;align-items:center;justify-content:center;padding:0;line-height:1;flex-shrink:0"
                    title="Remove">×</button>`;
        list.appendChild(div);
        input.value = '';
        renumberTpls();
    })
    .catch(() => alert('Failed to add. Please try again.'));
}

function deleteTpl(id, btn) {
    if (!confirm('Remove this default sub-entry?')) return;
    const url = TPL_DESTROY_URL.replace('__ID__', id);
    fetch(url, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    })
    .then(r => r.ok ? r.json() : Promise.reject(r))
    .then(() => {
        const row = btn.closest('.tpl-row');
        row.remove();
        if (!document.querySelectorAll('#tpl-list .tpl-row').length) {
            const empty = document.createElement('div');
            empty.id = 'tpl-empty';
            empty.className = 'text-muted small text-center py-2';
            empty.textContent = 'No default sub-entries defined yet.';
            document.getElementById('tpl-list').appendChild(empty);
        }
        renumberTpls();
    })
    .catch(() => alert('Failed to remove. Please try again.'));
}

document.getElementById('tpl-name-input')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); addTpl(); }
});
</script>

@if($yearTrend->count() > 0)
<script>
new Chart(document.getElementById('yearTrend'), {
    type: 'line',
    data: {
        labels: {!! json_encode($yearTrend->keys()->toArray()) !!},
        datasets: [
            {
                label: 'Budget',
                data:  {!! json_encode($yearTrend->map(fn($r)=>$r['budget'])->values()->toArray()) !!},
                borderColor: '#1B2A4A', backgroundColor: 'rgba(27,42,74,.08)',
                borderWidth: 2.5, pointBackgroundColor: '#C9A84C',
                pointRadius: 5, fill: true, tension: .4,
            },
            {
                label: 'Actual',
                data:  {!! json_encode($yearTrend->map(fn($r)=>$r['actual'])->values()->toArray()) !!},
                borderColor: '#10B981', backgroundColor: 'rgba(16,185,129,.08)',
                borderWidth: 2.5, pointBackgroundColor: '#10B981',
                pointRadius: 5, fill: true, tension: .4,
            },
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position:'top', labels:{ font:{size:11}, boxWidth:12 } } },
        scales: {
            y: { beginAtZero:true, grid:{color:'#F1F5F9'},
                 ticks:{ font:{size:10}, callback:v=>v>=1000000?(v/1000000).toFixed(1)+'M':v>=1000?(v/1000).toFixed(0)+'K':v } },
            x: { grid:{ display:false }, ticks:{ font:{size:11} } }
        }
    }
});
</script>
@endif

@endsection
