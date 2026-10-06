@extends('layouts.app')
@section('title', 'Email Templates')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Email Templates</h5>
        <p class="text-muted small mb-0">Customise the emails sent on each budget event.</p>
    </div>
    <form method="POST" action="{{ route('admin.email-templates.reset') }}">
        @csrf
        <button type="submit" class="btn btn-outline-secondary btn-sm"
                onclick="return confirm('Reset all templates to system defaults?')">
            <i class="bi bi-arrow-counterclockwise"></i> Reset to Defaults
        </button>
    </form>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Event</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $template)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $template->label }}</div>
                        <code class="text-muted" style="font-size:11px">{{ $template->event_key }}</code>
                    </td>
                    <td class="text-muted" style="max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        {{ $template->subject }}
                    </td>
                    <td>
                        @if($template->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.email-templates.edit', $template) }}"
                           class="btn btn-sm btn-outline-primary">Edit</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">No templates found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-4 border-0" style="background:#F8FAFC">
    <div class="card-body">
        <h6 class="fw-semibold mb-2"><i class="bi bi-braces"></i> Available Variables</h6>
        <p class="text-muted small mb-2">Use these placeholders in your subject and body — they are replaced at send time.</p>
        <div class="d-flex flex-wrap gap-2">
            @foreach(['{approver_name}','  {member_name}','{dept_name}','{period_name}','{version}','{comments}','{deadline}'] as $v)
                <code style="background:#E2E8F0;padding:3px 8px;border-radius:4px;font-size:12px">{{ trim($v) }}</code>
            @endforeach
        </div>
    </div>
</div>

@endsection
