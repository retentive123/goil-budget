<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">{{ $title }}</h5>
        <p class="text-muted small mb-0">
            <a href="{{ route('reports.index') }}" class="text-muted">Reports</a>
            / {{ $title }}
        </p>
    </div>
    @can('export reports')
    <div class="d-flex gap-2">
        <a href="{{ route('reports.export.' . $exportType, request()->query()) }}"
           class="btn btn-sm btn-outline-success">Export Excel</a>
        <a href="{{ route('reports.export.pdf', array_merge(['type'=>$exportType], request()->query())) }}"
           class="btn btn-sm btn-outline-danger">Export PDF</a>
    </div>
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
                    {{ request('period_id', $period?->id) == $p->id ? 'selected' : '' }}>
                    {{ $p->name }}
                </option>
                @endforeach
            </select>
        </div>
        @if(!empty($showDept) || $showDept !== false)
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Dept / Station</label>
            @include('reports._dept_filter', [
                'selectedId' => request('department_id'),
                'autoSubmit' => true,
                'selectId'   => 'rptToolbarDeptSel',
            ])
        </div>
        @endif
        {{-- Basis toggle lives in its own pill — it's a standalone navigation
             control that applies immediately without the Apply button --}}
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
