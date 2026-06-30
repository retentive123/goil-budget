@extends('layouts.app')
@section('title', 'Maintenance Mode')
@section('content')

<div class="row justify-content-center">
<div class="col-md-7">

<div class="chart-card mb-4" style="border-left:4px solid {{ $isDown ? '#F43F5E' : '#10B981' }}">
    <div style="font-size:18px;font-weight:700;color:var(--navy)">
        System Status: {{ $isDown ? '🔴 Under Maintenance' : '🟢 Live' }}
    </div>
    <p class="text-muted small mt-2 mb-0">
        Maintenance mode shows a friendly "we'll be back soon" page to all regular users
        while you (Super Admin) can continue working using a bypass link.
        No data is lost — users simply cannot interact with the system until you disable it.
    </p>
</div>

@if(session('bypass_url'))
<div class="chart-card mb-4" style="background:#FFFBEB;border:1px solid #FDE68A">
    <div style="font-size:13px;font-weight:700;color:#92400E;margin-bottom:6px">
        🔑 Your Bypass URL — save this now, it won't be shown again
    </div>
    <code style="font-size:12px;word-break:break-all">{{ session('bypass_url') }}</code>
    <p style="font-size:11px;color:#92400E;margin-top:8px;margin-bottom:0">
        Visit this link in your browser to access the system while maintenance mode is active.
        Share only with other admins who need to work during this window.
    </p>
</div>
@endif

@if(!$isDown)
<div class="chart-card">
    <h6 class="fw-bold mb-3">Enable Maintenance Mode</h6>
    <form method="POST" action="{{ route('admin.maintenance.enable') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label small fw-semibold">Message to show users (optional)</label>
            <textarea name="message" rows="2" class="form-control form-control-sm"
                placeholder="e.g. We're applying scheduled updates. Back in 30 minutes."></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label small fw-semibold">Retry-After (seconds)</label>
            <input type="number" name="retry" value="60" min="10" max="600"
                   class="form-control form-control-sm" style="max-width:120px">
        </div>
        <button type="submit" class="btn btn-sm btn-danger"
                onclick="return confirm('This will block all regular users from accessing the system. Continue?')">
            Enable Maintenance Mode
        </button>
    </form>
</div>
@else
<div class="chart-card">
    <h6 class="fw-bold mb-3" style="color:#991B1B">Maintenance Mode is Active</h6>
    <p class="small text-muted">
        Regular users currently see a maintenance page. Use your bypass URL to keep working.
        When updates are complete, disable maintenance mode below.
    </p>
    <form method="POST" action="{{ route('admin.maintenance.disable') }}">
        @csrf
        <button type="submit" class="btn btn-sm btn-success">
            Disable Maintenance Mode — Go Live
        </button>
    </form>
</div>
@endif

</div>
</div>
@endsection
