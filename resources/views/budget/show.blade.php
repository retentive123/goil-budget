@extends('layouts.app')
@section('title', 'Budget Entry')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="fw-bold mb-0">
            {{ $budgetVersion->department->name }} —
            {{ $budgetVersion->period->name }}
            <span class="badge bg-goil-orange ms-1">v{{ $budgetVersion->version_number }}</span>
        </h5>
        <p class="text-muted small mb-0">
            Status:
            <span class="badge bg-{{
                match($budgetVersion->status) {
                    'draft'        => 'secondary',
                    'submitted'    => 'primary',
                    'under_review' => 'warning',
                    'approved'     => 'success',
                    'rejected'     => 'danger',
                    default        => 'secondary'
                }
            }}">{{ ucfirst(str_replace('_',' ',$budgetVersion->status)) }}</span>
        </p>
    </div>

    @if($budgetVersion->isEditable())
    <div class="d-flex gap-2 align-items-center">
        <span id="save-status" class="text-muted small"></span>
        <button id="save-btn" class="btn btn-outline-primary btn-sm" onclick="saveBudget()">
            Save
        </button>
        <a href="{{ route('budget.confirm', $budgetVersion) }}" class="btn bg-goil-orange btn-sm">
            Submit for Approval →
        </a>
    </div>
    @endif
</div>

{{-- Grand total bar --}}
@php
    $lineItems = $budgetVersion->lineItems ?? collect();
    $totalSupplementary = $lineItems->sum(fn($i) => $i->approvedSupplementaryTotal());
    $effectiveTotal = $grandTotals['total'] ;
@endphp

<div class="card mb-3 border-0 bg-goil-orange">
    <div class="card-body py-2">
        <div class="row text-center">
            <div class="col">
                <div class="small text-white-50">Q1</div>
                <div class="fw-bold" id="gt-q1">
                    {{ currency() }} {{ number_format($grandTotals['q1'], 2) }}
                </div>
            </div>
            <div class="col">
                <div class="small text-white-50">Q2</div>
                <div class="fw-bold" id="gt-q2">
                    {{ currency() }} {{ number_format($grandTotals['q2'], 2) }}
                </div>
            </div>
            <div class="col">
                <div class="small text-white-50">Q3</div>
                <div class="fw-bold" id="gt-q3">
                    {{ currency() }} {{ number_format($grandTotals['q3'], 2) }}
                </div>
            </div>
            <div class="col">
                <div class="small text-white-50">Q4</div>
                <div class="fw-bold" id="gt-q4">
                    {{ currency() }} {{ number_format($grandTotals['q4'], 2) }}
                </div>
            </div>
            <div class="col border-start border-secondary">
                <div class="small text-white-50">Original Total</div>
                <div class="fw-bold" id="gt-total">
                    {{ currency() }} {{ number_format($grandTotals['total'], 2) }}
                </div>
                @if($totalSupplementary > 0)
                <div class="small" style="color: #6EE7B7;">
                    +{{ currency() }} {{ number_format($totalSupplementary, 2) }} supp.
                </div>
                @endif
            </div>
            <div class="col border-start border-secondary">
                <div class="small text-white-50">Effective Total</div>
                <div class="fw-bold fs-5" style="color: var(--gold);" id="gt-effective">
                    {{ currency() }} {{ number_format($effectiveTotal, 2) }}
                </div>
                @if($totalSupplementary > 0)
                <div class="small" style="color: rgba(255,255,255,0.5);">
                    incl. {{ number_format($totalSupplementary, 2) }} supplementary
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($budgetVersion->isEditable())
{{-- Import/Export panel --}}
<div class="chart-card mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <div style="font-size:13px;font-weight:600;color:var(--navy)">
                Excel Import / Export
            </div>
            <div style="font-size:12px;color:var(--slate)">
                Download the template, fill it in Excel, then upload to save time.
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('ie.budget.download', $budgetVersion) }}"
               class="btn btn-sm btn-outline-success">
                ↓ Download Template
            </a>

            <button type="button"
                    onclick="document.getElementById('uploadPanel').classList.toggle('d-none')"
                    class="btn btn-sm btn-outline-primary">
                ↑ Upload Excel
            </button>
        </div>
    </div>

    <div id="uploadPanel" class="d-none mt-3 pt-3 border-top">
        <form method="POST"
              action="{{ route('ie.budget.upload', $budgetVersion) }}"
              enctype="multipart/form-data">
            @csrf
            <div class="d-flex gap-2 align-items-end">
                <div class="flex-grow-1">
                    <label class="form-label small fw-semibold mb-1">
                        Select filled Excel file
                    </label>
                    <input type="file" name="file"
                           accept=".xlsx,.xls"
                           class="form-control form-control-sm">
                </div>
                <button type="submit" class="btn btn-sm btn-primary">
                    Upload & Save
                </button>
            </div>
            <div class="form-text">
                Only .xlsx and .xls files accepted. Max 5MB.
                Use the downloaded template — do not change the file structure.
            </div>
        </form>
    </div>

    {{-- Show import errors if any --}}
    @if(session('import_errors'))
    <div class="mt-3 pt-3 border-top">
        <div style="font-size:12px;font-weight:600;color:#991B1B;margin-bottom:6px">
            Import Errors:
        </div>
        @foreach(session('import_errors') as $err)
        <div style="font-size:11px;color:#991B1B;padding:2px 0">⚠ {{ $err }}</div>
        @endforeach
    </div>
    @endif
</div>
@endif

{{-- Line items grouped by category --}}
@foreach($summary as $categoryName => $categoryData)
<div class="card shadow-sm mb-3">
    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold small text-uppercase">{{ $categoryName }}</span>
        <span class="small text-muted">
            Original: <strong>{{ currency() }} {{ number_format($categoryData['total'], 2) }}</strong>
            @php
                $catSupp = $categoryData['items']->sum(fn($i) => $i->approvedSupplementaryTotal());
            @endphp
            @if($catSupp > 0)
            <span style="color:#10B981;">
                +{{ currency() }} {{ number_format($catSupp, 2) }} supp.
            </span>
            <span style="color:var(--navy);font-weight:700;">
                | Effective: {{ currency() }} {{ number_format($categoryData['total'] + $catSupp, 2) }}
            </span>
            @endif
        </span>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:22%">Account</th>
                    <th class="text-end">Q1 ({{ currency() }})</th>
                    <th class="text-end">Q2 ({{ currency() }})</th>
                    <th class="text-end">Q3 ({{ currency() }})</th>
                    <th class="text-end">Q4 ({{ currency() }})</th>
                    <th class="text-end">Original Total</th>
                    <th class="text-end">Supplementary</th>
                    <th class="text-end">Effective Total</th>
                    @if($budgetVersion->isEditable())
                    <th>Notes</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($categoryData['items'] as $item)
                @php
                    $itemSupp = $item->approvedSupplementaryTotal();
                    $itemEffective = $item->effectiveBudget();
                @endphp
                <tr data-item-id="{{ $item->id }}">
                    <td class="small">
                        <code>{{ $item->accountCode->code }}</code>
                        {{ $item->accountCode->name }}
                        @if($item->line_type)
                        <span style="padding:1px 6px;border-radius:4px;font-size:9px;
                                     font-weight:600;
                                     background:{{ $item->line_type==='revenue'?'#D1FAE5':'#FEE2E2' }};
                                     color:{{ $item->line_type==='revenue'?'#065F46':'#991B1B' }}">
                            {{ ucfirst($item->line_type) }}
                        </span>
                        @endif
                    </td>
                    @if($budgetVersion->isEditable())
                        <td><input type="number" class="form-control form-control-sm q-input q1 text-end"
                            value="{{ $item->q1_amount }}" min="0" step="0.01"
                            onchange="updateRowTotal(this)" onkeyup="scheduleAutoSave()"></td>
                        <td><input type="number" class="form-control form-control-sm q-input q2 text-end"
                            value="{{ $item->q2_amount }}" min="0" step="0.01"
                            onchange="updateRowTotal(this)" onkeyup="scheduleAutoSave()"></td>
                        <td><input type="number" class="form-control form-control-sm q-input q3 text-end"
                            value="{{ $item->q3_amount }}" min="0" step="0.01"
                            onchange="updateRowTotal(this)" onkeyup="scheduleAutoSave()"></td>
                        <td><input type="number" class="form-control form-control-sm q-input q4 text-end"
                            value="{{ $item->q4_amount }}" min="0" step="0.01"
                            onchange="updateRowTotal(this)" onkeyup="scheduleAutoSave()"></td>
                        <td class="text-end text-muted small">
                            {{ number_format($item->total_amount, 2) }}
                        </td>
                        <td class="text-end" style="color:{{ $itemSupp > 0 ? '#10B981' : 'inherit' }}">
                            {{ $itemSupp > 0 ? '+'.number_format($itemSupp, 2) : '—' }}
                        </td>
                        <td class="text-end fw-bold row-total" style="color:var(--navy)">
                            {{ number_format($itemEffective, 2) }}
                        </td>
                        <td><input type="text" class="form-control form-control-sm notes-input"
                            value="{{ $item->justification }}"
                            placeholder="Optional note"
                            onkeyup="scheduleAutoSave()"></td>
                    @else
                        <td class="text-end small">{{ number_format($item->q1_amount, 2) }}</td>
                        <td class="text-end small">{{ number_format($item->q2_amount, 2) }}</td>
                        <td class="text-end small">{{ number_format($item->q3_amount, 2) }}</td>
                        <td class="text-end small">{{ number_format($item->q4_amount, 2) }}</td>
                        <td class="text-end small text-muted">{{ number_format($item->total_amount, 2) }}</td>
                        <td class="text-end" style="color:{{ $itemSupp > 0 ? '#10B981' : 'inherit' }}">
                            {{ $itemSupp > 0 ? '+'.number_format($itemSupp, 2) : '—' }}
                        </td>
                        <td class="text-end small fw-semibold">{{ number_format($itemEffective, 2) }}</td>
                    @endif
                </tr>
                @endforeach
            </tbody>
            <tfoot style="background:#F8FAFC;font-weight:700;">
                <tr>
                    <td>Category Total</td>
                    <td class="text-end">{{ number_format($categoryData['q1'], 2) }}</td>
                    <td class="text-end">{{ number_format($categoryData['q2'], 2) }}</td>
                    <td class="text-end">{{ number_format($categoryData['q3'], 2) }}</td>
                    <td class="text-end">{{ number_format($categoryData['q4'], 2) }}</td>
                    <td class="text-end">{{ number_format($categoryData['total'], 2) }}</td>
                    <td class="text-end" style="color:{{ $catSupp > 0 ? '#10B981' : 'inherit' }}">
                        {{ $catSupp > 0 ? '+'.number_format($catSupp, 2) : '—' }}
                    </td>
                    <td class="text-end" style="color:var(--navy)">
                        {{ number_format($categoryData['total'] + $catSupp, 2) }}
                    </td>
                    @if($budgetVersion->isEditable())
                    <td></td>
                    @endif
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endforeach

@if($budgetVersion->isEditable())
<script>
    const SAVE_URL = "{{ route('budget.save', $budgetVersion) }}";
    const CSRF     = document.querySelector('meta[name="csrf-token"]')?.content
                     || "{{ csrf_token() }}";

    let autoSaveTimer  = null;
    let isSaving       = false;

    function updateRowTotal(input) {
        const row  = input.closest('tr');
        const q1   = parseFloat(row.querySelector('.q1').value) || 0;
        const q2   = parseFloat(row.querySelector('.q2').value) || 0;
        const q3   = parseFloat(row.querySelector('.q3').value) || 0;
        const q4   = parseFloat(row.querySelector('.q4').value) || 0;
        const total = q1 + q2 + q3 + q4;
        row.querySelector('.row-total').textContent = total.toLocaleString('en-GH', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });
    }

    function collectItems() {
        const rows = document.querySelectorAll('tr[data-item-id]');
        return Array.from(rows).map(row => ({
            id:    row.dataset.itemId,
            q1:    parseFloat(row.querySelector('.q1')?.value)    || 0,
            q2:    parseFloat(row.querySelector('.q2')?.value)    || 0,
            q3:    parseFloat(row.querySelector('.q3')?.value)    || 0,
            q4:    parseFloat(row.querySelector('.q4')?.value)    || 0,
            notes: row.querySelector('.notes-input')?.value       || '',
        }));
    }

    async function saveBudget() {
        if (isSaving) return;
        isSaving = true;

        const btn    = document.getElementById('save-btn');
        const status = document.getElementById('save-status');

        if (btn)    btn.disabled = true;
        if (status) status.textContent = 'Saving…';

        try {
            const res = await fetch(SAVE_URL, {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept':       'application/json',
                },
                body: JSON.stringify({ items: collectItems() }),
            });

            if (!res.ok) {
                const text = await res.text();
                console.error('Save failed:', res.status, text);
                if (status) status.textContent = 'Save failed (' + res.status + ')';
                return;
            }

            const data = await res.json();

            if (data.success) {
                if (status) status.textContent = 'Saved at ' + data.saved_at;

                // Update grand totals
                const fmt = v => 'GHS ' + parseFloat(v).toLocaleString('en-GH', {
                    minimumFractionDigits: 2, maximumFractionDigits: 2
                });

                const el = id => document.getElementById(id);
                if (el('gt-q1'))    el('gt-q1').textContent    = fmt(data.totals.q1);
                if (el('gt-q2'))    el('gt-q2').textContent    = fmt(data.totals.q2);
                if (el('gt-q3'))    el('gt-q3').textContent    = fmt(data.totals.q3);
                if (el('gt-q4'))    el('gt-q4').textContent    = fmt(data.totals.q4);
                if (el('gt-total')) el('gt-total').textContent = 'GHS ' + data.grand_total;
            } else {
                if (status) status.textContent = 'Save failed.';
            }
        } catch (e) {
            console.error('Network error:', e);
            if (status) status.textContent = 'Network error — not saved.';
        } finally {
            isSaving = false;
            if (btn) btn.disabled = false;
        }
    }

    function scheduleAutoSave() {
        clearTimeout(autoSaveTimer);
        const status = document.getElementById('save-status');
        if (status) status.textContent = 'Unsaved changes…';
        autoSaveTimer = setTimeout(saveBudget, 3000);
    }
</script>
@endif

@endsection
