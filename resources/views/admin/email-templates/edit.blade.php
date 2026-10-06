@extends('layouts.app')
@section('title', 'Edit Email Template')
@section('content')

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.email-templates.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h5 class="fw-bold mb-0">{{ $template->label }}</h5>
        <code class="text-muted" style="font-size:11px">{{ $template->event_key }}</code>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('admin.email-templates.update', $template) }}">
            @csrf @method('PUT')

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Subject</label>
                        <input type="text" name="subject" class="form-control"
                               value="{{ old('subject', $template->subject) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Body</label>
                        <textarea name="body" class="form-control" rows="12"
                                  style="font-family:monospace;font-size:13px"
                                  required>{{ old('body', $template->body) }}</textarea>
                        <div class="form-text">Plain text. Line breaks are preserved.</div>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active"
                               value="1" id="isActive"
                               {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive">Active (send this template)</label>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Save Template</button>
                        <a href="{{ route('admin.email-templates.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        <div class="card border-0" style="background:#F8FAFC">
            <div class="card-body">
                <h6 class="fw-semibold mb-3"><i class="bi bi-braces"></i> Variables</h6>
                <p class="text-muted small">Click to copy.</p>
                @foreach([
                    ['{approver_name}', 'Name of the approver being notified'],
                    ['{member_name}',   'Name of the department member'],
                    ['{dept_name}',     'Department name'],
                    ['{period_name}',   'Budget period name'],
                    ['{version}',       'Budget version number'],
                    ['{comments}',      'Reviewer comments (rejection)'],
                    ['{deadline}',      'Submission deadline date'],
                ] as [$var, $desc])
                <div class="mb-2">
                    <code class="variable-chip" style="cursor:pointer;background:#E2E8F0;
                          padding:3px 8px;border-radius:4px;font-size:12px"
                          onclick="copyVar('{{ $var }}')">{{ $var }}</code>
                    <div class="text-muted" style="font-size:11px;margin-top:2px">{{ $desc }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
function copyVar(v) {
    navigator.clipboard.writeText(v).then(() => {
        Swal.fire({ toast: true, position: 'bottom-end', icon: 'success',
                    title: 'Copied ' + v, showConfirmButton: false, timer: 1500 });
    });
}
</script>

@endsection
