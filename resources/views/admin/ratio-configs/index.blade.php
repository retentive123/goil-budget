@extends('layouts.app')
@section('title', 'Ratio Configurations')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0" style="color:#1B2A4A">Ratio Configurations</h5>
        <p class="text-muted small mb-0">Define and manage ratio formulas used in the Ratio Analysis report</p>
    </div>
    <a href="{{ route('admin.ratio-configs.create') }}"
       class="btn btn-sm" style="background:#E65C00;color:#fff;border:none;border-radius:8px">
        + New Ratio
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden;border:1px solid #E2E8F0!important">
    <table class="table table-hover mb-0" style="font-size:13px">
        <thead style="background:#F8FAFC;border-bottom:2px solid #E65C00">
            <tr>
                <th class="px-4 py-3" style="color:#1B2A4A;font-weight:600;width:40px">#</th>
                <th class="px-4 py-3" style="color:#1B2A4A;font-weight:600">Name</th>
                <th class="px-4 py-3" style="color:#1B2A4A;font-weight:600">Formula</th>
                <th class="px-4 py-3" style="color:#1B2A4A;font-weight:600">Output</th>
                <th class="px-4 py-3" style="color:#1B2A4A;font-weight:600">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($ratios as $ratio)
            @php
                $typeLabels = \App\Models\RatioConfig::allTypes();
                $numTypes   = collect($ratio->numerator_types)->map(fn($t) => $typeLabels[$t] ?? $t)->join(', ');
                $denTypes   = collect($ratio->denominator_types)->map(fn($t) => $typeLabels[$t] ?? $t)->join(', ');
            @endphp
            <tr>
                <td class="px-4 py-3" style="color:#94A3B8">{{ $ratio->sort_order }}</td>
                <td class="px-4 py-3">
                    <div class="fw-semibold" style="color:#1B2A4A">{{ $ratio->name }}</div>
                    @if($ratio->description)
                    <div style="font-size:11px;color:#64748B">{{ $ratio->description }}</div>
                    @endif
                </td>
                <td class="px-4 py-3" style="font-size:12px">
                    <span style="background:#EFF6FF;color:#1E40AF;padding:2px 7px;border-radius:4px">
                        {{ ucfirst($ratio->numerator_source) }}: {{ $numTypes }}
                    </span>
                    <span class="mx-1 text-muted">÷</span>
                    <span style="background:#F0FDF4;color:#166534;padding:2px 7px;border-radius:4px">
                        {{ ucfirst($ratio->denominator_source) }}: {{ $denTypes }}
                    </span>
                    @if($ratio->multiply_by != 1)
                    <span class="ms-1" style="color:#94A3B8">× {{ number_format($ratio->multiply_by, 0) }}</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <code style="background:#F1F5F9;padding:2px 6px;border-radius:4px;font-size:11px">
                        {{ $ratio->unit }}
                    </code>
                    <span style="font-size:11px;color:#94A3B8;margin-left:4px">
                        {{ $ratio->higher_is_better ? '↑ better' : '↓ better' }}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <span class="badge"
                          style="border-radius:20px;font-size:11px;padding:3px 10px;
                                 background:{{ $ratio->is_active ? '#D1FAE5' : '#FEE2E2' }};
                                 color:{{ $ratio->is_active ? '#065F46' : '#991B1B' }}">
                        {{ $ratio->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('admin.ratio-configs.edit', $ratio) }}"
                           class="btn btn-sm btn-outline-primary"
                           style="border-radius:6px;font-size:11px;padding:3px 10px">Edit</a>
                        <form method="POST" action="{{ route('admin.ratio-configs.destroy', $ratio) }}"
                              onsubmit="return confirm('Delete this ratio?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"
                                    style="border-radius:6px;font-size:11px;padding:3px 10px">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center py-5 text-muted">
                    No ratios configured yet.
                    <a href="{{ route('admin.ratio-configs.create') }}" style="color:#E65C00">Add one</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
