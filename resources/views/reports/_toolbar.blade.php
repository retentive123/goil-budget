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
            <label class="form-label small fw-semibold mb-1">Department</label>
            <select name="department_id" class="form-select form-select-sm"
                    onchange="this.form.submit()">
                <option value="">All Departments</option>
                @foreach($departments as $d)
                <option value="{{ $d->id }}"
                    {{ request('department_id') == $d->id ? 'selected' : '' }}>
                    {{ $d->name }}
                </option>
                @endforeach
            </select>
        </div>
        @endif
    </div>
</form>
