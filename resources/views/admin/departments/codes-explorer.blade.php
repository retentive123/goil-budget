@extends('layouts.app')
@section('title', 'Department Code Explorer')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <div class="small text-muted mb-1">
            <a href="{{ route('admin.departments.index') }}" class="text-decoration-none" style="color:#E65C00">
                <i class="fas fa-building me-1"></i>Departments
            </a>
            <span class="mx-1">/</span>
            <span>Code Explorer</span>
        </div>
        <h5 class="fw-bold mb-0" style="color:#1B2A4A">
            <i class="fas fa-hashtag me-2" style="color:#E65C00"></i>Department Code Explorer
        </h5>
        <p class="text-muted small mb-0">Switch between departments to view and download their assigned account codes.</p>
    </div>
</div>

<div class="row g-4">

    {{-- ── Left: department switcher ────────────────────────────── --}}
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden;position:sticky;top:80px">
            <div class="card-header bg-white border-bottom py-3 px-3">
                <input type="text" id="deptSearch" placeholder="Search departments…"
                       class="form-control form-control-sm"
                       style="border-radius:8px;font-size:12px">
            </div>
            <div class="card-body p-0" style="max-height:calc(100vh - 260px);overflow-y:auto">
                <ul class="list-group list-group-flush" id="deptList">
                    @foreach($departments as $dept)
                    <li class="list-group-item list-group-item-action dept-item px-3 py-2"
                        data-dept-id="{{ $dept->id }}"
                        data-dept-name="{{ $dept->name }}"
                        style="cursor:pointer;border-radius:0;font-size:13px;
                               {{ $dept->id === $selectedId ? 'background:#FFF7ED;border-left:3px solid #E65C00;font-weight:600;color:#1B2A4A' : 'border-left:3px solid transparent' }}">
                        <div>{{ $dept->name }}</div>
                        <div style="font-size:10px;color:#94A3B8">{{ $dept->code }} · {{ $dept->accountCodes->count() }} codes</div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    {{-- ── Right: codes panel ───────────────────────────────────── --}}
    <div class="col-lg-9">

        {{-- Global type filter --}}
        <div class="mb-3 d-flex align-items-center gap-2">
            <select id="globalTypeFilter"
                    class="form-select form-select-sm"
                    style="border-radius:8px;font-size:12px;max-width:180px">
                <option value="">All Types</option>
                <option value="expense">Expense</option>
                <option value="revenue">Revenue</option>
                <option value="capital_expenditure">CapEx</option>
            </select>
            <span id="typeFilterBadge" class="d-none"
                  style="font-size:11px;color:#64748B"></span>
        </div>

        @foreach($departments as $dept)
        <div class="dept-panel" id="panel-{{ $dept->id }}"
             style="{{ $dept->id !== $selectedId ? 'display:none' : '' }}">

            {{-- Panel header --}}
            <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                <div>
                    <h6 class="fw-bold mb-0" style="color:#1B2A4A">{{ $dept->name }}</h6>
                    <div class="small text-muted">Code {{ $dept->code }} &middot; {{ $dept->accountCodes->count() }} account code(s) assigned</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.departments.export-account-codes', $dept) }}"
                       class="btn btn-sm"
                       style="background:#10B981;color:#fff;border-radius:8px;font-size:12px">
                        <i class="bi bi-download me-1"></i>Download CSV
                    </a>
                    <a href="{{ route('admin.departments.account-codes', $dept) }}"
                       class="btn btn-sm btn-outline-secondary"
                       style="border-radius:8px;font-size:12px">
                        <i class="bi bi-pencil me-1"></i>Edit Codes
                    </a>
                </div>
            </div>

            {{-- Filter bar --}}
            <div class="mb-3">
                <input type="text"
                       class="form-control form-control-sm code-filter"
                       data-panel="{{ $dept->id }}"
                       placeholder="Filter codes in this department…"
                       style="border-radius:8px;font-size:12px;max-width:320px">
            </div>

            @if($dept->accountCodes->isEmpty())
            <div class="text-center py-5" style="color:#94A3B8">
                <i class="bi bi-inbox" style="font-size:36px;display:block;margin-bottom:8px"></i>
                No account codes assigned to {{ $dept->name }} yet.
                <div class="mt-2">
                    <a href="{{ route('admin.departments.account-codes', $dept) }}"
                       style="color:#E65C00;font-size:13px">Assign codes →</a>
                </div>
            </div>
            @else

            {{-- Group by category --}}
            @foreach($dept->accountCodes->groupBy(fn($c) => $c->category?->name ?? 'Uncategorised') as $catName => $codes)
            <div class="mb-4 category-block" data-panel="{{ $dept->id }}">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span style="font-size:11px;font-weight:700;text-transform:uppercase;
                                 letter-spacing:.5px;color:#64748B">{{ $catName }}</span>
                    <span style="font-size:10px;background:#F1F5F9;color:#64748B;
                                 padding:1px 8px;border-radius:10px">{{ $codes->count() }}</span>
                </div>
                <div class="card border-0 shadow-sm" style="border-radius:10px;overflow:hidden">
                    <table class="table table-sm mb-0" style="font-size:12px">
                        <thead style="background:#F8FAFC">
                            <tr>
                                <th class="px-3 py-2" style="color:#1B2A4A;font-weight:600;width:120px">Code</th>
                                <th class="px-3 py-2" style="color:#1B2A4A;font-weight:600">Name</th>
                                <th class="px-3 py-2" style="color:#1B2A4A;font-weight:600;width:140px">Sage Code</th>
                                <th class="px-3 py-2 text-center" style="color:#1B2A4A;font-weight:600;width:80px">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($codes as $code)
                            <tr class="code-row"
                                data-name="{{ strtolower($code->name) }} {{ strtolower($code->code) }}"
                                data-type="{{ $code->category?->budget_type ?? '' }}">
                                <td class="px-3 py-2">
                                    <code style="color:#E65C00;font-size:11px">{{ $code->code }}</code>
                                </td>
                                <td class="px-3 py-2" style="color:#1B2A4A">{{ $code->name }}</td>
                                <td class="px-3 py-2">
                                    <code style="color:#475569;font-size:11px">{{ $code->code }}-{{ $dept->code }}</code>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    @if($code->is_active)
                                    <span style="font-size:10px;background:#D1FAE5;color:#065F46;
                                                 padding:1px 7px;border-radius:10px">Active</span>
                                    @else
                                    <span style="font-size:10px;background:#FEE2E2;color:#991B1B;
                                                 padding:1px 7px;border-radius:10px">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach

            @endif
        </div>
        @endforeach
    </div>
</div>

<script>
// ── Department switcher ──────────────────────────────────────────
document.querySelectorAll('.dept-item').forEach(function(item) {
    item.addEventListener('click', function() {
        const id = this.dataset.deptId;

        // Highlight active
        document.querySelectorAll('.dept-item').forEach(function(el) {
            el.style.background    = '';
            el.style.borderLeft   = '3px solid transparent';
            el.style.fontWeight   = '';
            el.style.color        = '';
        });
        this.style.background  = '#FFF7ED';
        this.style.borderLeft  = '3px solid #E65C00';
        this.style.fontWeight  = '600';
        this.style.color       = '#1B2A4A';

        // Show panel
        document.querySelectorAll('.dept-panel').forEach(function(p) {
            p.style.display = 'none';
        });
        const panel = document.getElementById('panel-' + id);
        if (panel) panel.style.display = '';

        // Update URL without reload
        const url = new URL(window.location);
        url.searchParams.set('department_id', id);
        window.history.replaceState({}, '', url);

        // Clear the text filter but preserve the type filter for this panel
        const filter  = panel.querySelector('.code-filter');
        const typeVal = document.getElementById('globalTypeFilter').value;
        if (filter) { filter.value = ''; }
        filterCodes(panel, '', typeVal);
    });
});

// ── Dept search ──────────────────────────────────────────────────
document.getElementById('deptSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.dept-item').forEach(function(item) {
        const match = item.dataset.deptName.toLowerCase().includes(q);
        item.style.display = match ? '' : 'none';
    });
});

// ── Code filter ──────────────────────────────────────────────────
function filterCodes(panel, q, typeVal) {
    if (typeVal === undefined) {
        typeVal = document.getElementById('globalTypeFilter').value;
    }
    panel.querySelectorAll('.code-row').forEach(function(row) {
        const nameMatch = q === '' || row.dataset.name.includes(q);
        const typeMatch = typeVal === '' || row.dataset.type === typeVal;
        row.style.display = (nameMatch && typeMatch) ? '' : 'none';
    });
    // Hide category blocks that have no visible rows
    panel.querySelectorAll('.category-block').forEach(function(block) {
        const visible = Array.from(block.querySelectorAll('.code-row'))
            .some(function(r) { return r.style.display !== 'none'; });
        block.style.display = visible ? '' : 'none';
    });
}

document.querySelectorAll('.code-filter').forEach(function(input) {
    input.addEventListener('input', function() {
        const panel = document.getElementById('panel-' + this.dataset.panel);
        if (panel) filterCodes(panel, this.value.toLowerCase());
    });
});

// ── Global type filter ────────────────────────────────────────────
document.getElementById('globalTypeFilter').addEventListener('change', function() {
    const typeVal = this.value;
    const badge   = document.getElementById('typeFilterBadge');
    const labels  = { expense: 'Expense', revenue: 'Revenue', capital_expenditure: 'CapEx' };

    if (typeVal) {
        badge.textContent = 'Showing: ' + labels[typeVal];
        badge.classList.remove('d-none');
    } else {
        badge.classList.add('d-none');
    }

    // Apply to the currently visible panel
    const visible = document.querySelector('.dept-panel:not([style*="display:none"]):not([style*="display: none"])');
    if (visible) {
        const textInput = visible.querySelector('.code-filter');
        const q = textInput ? textInput.value.toLowerCase() : '';
        filterCodes(visible, q, typeVal);
    }
});
</script>

@endsection
