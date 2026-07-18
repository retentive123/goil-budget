@extends('layouts.app')
@section('title', 'Approval Stages')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Approval Stages</h5>
        <p class="text-muted small mb-0">
            Define and order the chain of approvals a budget must pass through.
            Drag rows to reorder.
        </p>
    </div>
    <a href="{{ route('admin.approval-stages.create') }}"
       class="btn btn-sm"
       style="background:var(--navy);color:#fff;border-radius:8px;padding:8px 18px">
        + Add Stage
    </a>
</div>

{{-- Warning if budgets are in flight --}}
@php $anyInProgress = collect($stageBudgetCounts)->sum() > 0; @endphp
@if($anyInProgress)
<div class="alert mb-4"
     style="background:#FEF3C7;color:#92400E;border:none;border-radius:10px;font-size:13px">
    ⚠ Some budgets are currently in the approval pipeline.
    Stages with active budgets cannot be deleted until those budgets are processed.
</div>
@endif

{{-- Visual flow --}}
<div class="chart-card mb-4">
    <div class="chart-title">Current Approval Flow</div>
    <div class="d-flex align-items-center gap-0 flex-wrap">
        @php $activeStages = $stages->where('is_active', true)->sortBy('order'); @endphp
        @foreach($activeStages as $idx => $stage)
        <div style="display:flex;align-items:center">
            <div style="background:var(--navy);color:#fff;border-radius:10px;
                        padding:10px 16px;text-align:center;min-width:120px">
                <div style="font-size:10px;color:rgba(255,255,255,.5);
                            text-transform:uppercase;letter-spacing:.5px">
                    Stage {{ $stage->order }}
                </div>
                <div style="font-size:13px;font-weight:700;margin-top:2px">
                    {{ $stage->name }}
                </div>
                <div style="font-size:10px;color:var(--gold);margin-top:2px">
                    {{ ucfirst(str_replace('_',' ',$stage->role_name)) }}
                </div>
            </div>
            @if(!$loop->last)
            <div style="padding:0 6px;color:var(--slate);font-size:18px">→</div>
            @endif
        </div>
        @endforeach

        @if($activeStages->isEmpty())
        <div class="text-muted small">No active stages defined.</div>
        @endif
    </div>
</div>

{{-- Stages table with drag-and-drop --}}
<div class="chart-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="chart-title mb-0">All Stages</div>
        <div style="font-size:12px;color:var(--slate)">
            💡 Drag the ⠿ handle to reorder
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0" id="stagesTable">
            <thead style="font-size:11px;text-transform:uppercase;
                          letter-spacing:.5px;color:var(--slate)">
                <tr>
                    <th style="width:36px"></th>
                    <th style="width:60px">Order</th>
                    <th>Stage Name</th>
                    <th>Role</th>
                    <th>Active Budgets</th>
                    <th>Status</th>
                    <th>Move</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="sortableBody">
                @forelse($stages as $stage)
                <tr data-id="{{ $stage->id }}"
                    style="cursor:grab">
                    {{-- Drag handle --}}
                    <td style="color:#CBD5E1;font-size:18px;
                                cursor:grab;user-select:none"
                        class="drag-handle">
                        ⠿
                    </td>

                    {{-- Order badge --}}
                    <td>
                        <span style="background:var(--navy);color:#fff;
                                     border-radius:50%;width:28px;height:28px;
                                     display:inline-flex;align-items:center;
                                     justify-content:center;font-size:13px;
                                     font-weight:700"
                              class="order-badge">
                            {{ $stage->order }}
                        </span>
                    </td>

                    <td>
                        <div style="font-size:13px;font-weight:600;color:var(--navy)">
                            {{ $stage->name }}
                        </div>
                    </td>

                    <td>
                        <span style="background:#EFF6FF;color:#1E40AF;
                                     border-radius:6px;padding:3px 10px;
                                     font-size:12px;font-weight:600">
                            {{ ucfirst(str_replace('_',' ',$stage->role_name)) }}
                        </span>
                    </td>

                    <td>
                        @php $count = $stageBudgetCounts[$stage->id] ?? 0; @endphp
                        @if($count > 0)
                        <span style="background:#FEF3C7;color:#92400E;
                                     border-radius:20px;padding:2px 10px;
                                     font-size:12px;font-weight:600">
                            {{ $count }} in progress
                        </span>
                        @else
                        <span style="color:#94A3B8;font-size:12px">None</span>
                        @endif
                    </td>

                    <td>
                        <span style="padding:3px 10px;border-radius:20px;
                                     font-size:11px;font-weight:600;
                                     background:{{ $stage->is_active ? '#D1FAE5' : '#F1F5F9' }};
                                     color:{{ $stage->is_active ? '#065F46' : '#64748B' }}">
                            {{ $stage->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>

                    {{-- Up/down buttons --}}
                    <td>
                        <div class="d-flex gap-1">
                            <form method="POST"
                                  action="{{ route('admin.approval-stages.move-up', $stage) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-secondary"
                                        style="padding:2px 8px;font-size:12px"
                                        {{ $loop->first ? 'disabled' : '' }}
                                        title="Move up">
                                    ↑
                                </button>
                            </form>
                            <form method="POST"
                                  action="{{ route('admin.approval-stages.move-down', $stage) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-secondary"
                                        style="padding:2px 8px;font-size:12px"
                                        {{ $loop->last ? 'disabled' : '' }}
                                        title="Move down">
                                    ↓
                                </button>
                            </form>
                        </div>
                    </td>

                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.approval-stages.edit', $stage) }}"
                               class="btn btn-sm btn-outline-primary"
                               style="font-size:12px;padding:3px 10px">
                                Edit
                            </a>

                            @if(($stageBudgetCounts[$stage->id] ?? 0) === 0)
                            <form method="POST"
                                  action="{{ route('admin.approval-stages.destroy', $stage) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"
                                        style="font-size:12px;padding:3px 10px"
                                        onclick="return confirm('Delete stage \'{{ $stage->name }}\'?\n\nThis cannot be undone.')">
                                    Delete
                                </button>
                            </form>
                            @else
                            <button class="btn btn-sm btn-outline-secondary"
                                    style="font-size:12px;padding:3px 10px"
                                    disabled title="Has active budgets">
                                Delete
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-5">
                        No approval stages defined.
                        <a href="{{ route('admin.approval-stages.create') }}">Add one</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Impact notice --}}
    <div style="margin-top:16px;padding:12px 16px;background:#F8FAFC;
                border-radius:8px;font-size:12px;color:var(--slate)">
        <strong style="color:var(--navy)">Important:</strong>
        Changes to approval stages take effect immediately for all
        <strong>newly submitted</strong> budgets. Budgets already in the approval
        pipeline will continue through the stages that were active when they were submitted.
    </div>
</div>

{{-- Drag-and-drop via SortableJS --}}
<script>
const tbody = document.getElementById('sortableBody');

const sortable = Sortable.create(tbody, {
    handle:    '.drag-handle',
    animation: 150,
    ghostClass: 'bg-light',

    onEnd: function () {
        // Collect new order
        const ids = [...tbody.querySelectorAll('tr[data-id]')]
                        .map(tr => tr.dataset.id);

        // Update order badges visually
        tbody.querySelectorAll('tr[data-id]').forEach((tr, idx) => {
            const badge = tr.querySelector('.order-badge');
            if (badge) badge.textContent = idx + 1;
        });

        // Save to server
        fetch('{{ route("admin.approval-stages.reorder") }}', {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ order: ids }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('Order saved');
            }
        })
        .catch(() => showToast('Failed to save order', true));
    }
});

function showToast(msg, isError = false) {
    const toast = document.createElement('div');
    toast.textContent = msg;
    toast.style.cssText = `
        position:fixed;bottom:24px;right:24px;z-index:9999;
        background:${isError ? '#F43F5E' : '#10B981'};
        color:#fff;padding:10px 20px;border-radius:8px;
        font-size:13px;font-weight:600;
        box-shadow:0 4px 16px rgba(0,0,0,.15);
        animation:slideIn .3s ease;
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2500);
}
</script>

<style>
@keyframes slideIn {
    from { transform:translateY(20px); opacity:0; }
    to   { transform:translateY(0);    opacity:1; }
}
</style>

@endsection
