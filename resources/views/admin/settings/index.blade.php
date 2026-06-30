@extends('layouts.app')
@section('title', 'System Settings')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">System Settings</h5>
        <p class="text-muted small mb-0">
            Configure system-wide behaviour. All changes are audit-logged.
        </p>
    </div>
</div>

<form method="POST" action="{{ route('admin.settings.update') }}" id="settingsForm">
    @csrf

    <div class="row g-4">

        {{-- Left column --}}
        <div class="col-md-8">

            @foreach($settings as $group => $groupSettings)
            <div class="chart-card mb-4">

                {{-- Group header --}}
                <div class="d-flex align-items-center gap-2 mb-4 pb-3"
                     style="border-bottom:1px solid var(--border)">
                    <div style="width:36px;height:36px;border-radius:8px;
                                background:var(--navy);display:flex;
                                align-items:center;justify-content:center;
                                font-size:16px">
                        {{ match($group) {
                            'general'       => '⚙️',
                            'budget'        => '📋',
                            'notifications' => '🔔',
                            'security'      => '🔒',
                            default         => '🔧'
                        } }}
                    </div>
                    <div>
                        <div style="font-size:14px;font-weight:700;color:var(--navy)">
                            {{ ucfirst($group) }}
                        </div>
                        <div style="font-size:12px;color:var(--slate)">
                            {{ match($group) {
                                'general'       => 'Application name, company, currency',
                                'budget'        => 'Version limits, virement rules',
                                'notifications' => 'Email notification triggers',
                                'security'      => 'Session, login, and SSO security settings',
                                default         => ''
                            } }}
                        </div>
                    </div>
                    @if($group === 'security')
                    <div class="ms-auto">
                        <span class="badge" style="background:#E65C00;color:#fff;font-size:10px">
                            🔐 SSO & Security
                        </span>
                    </div>
                    @endif
                </div>

                @foreach($groupSettings as $setting)
                <div class="row align-items-start mb-4 pb-3"
                     style="border-bottom:1px solid var(--border)">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold mb-0"
                               for="setting_{{ $setting->key }}">
                            {{ $setting->label }}
                        </label>
                        @if($setting->description)
                        <div style="font-size:12px;color:var(--slate);margin-top:3px">
                            {{ $setting->description }}
                        </div>
                        @endif
                        {{-- Show key for debugging (optional) --}}
                        {{--
                        <div style="font-size:10px;font-family:monospace;
                                    color:#94A3B8;margin-top:4px">
                            key: {{ $setting->key }}
                        </div>
                        --}}
                    </div>
                    <div class="col-md-4">
                    @if($setting->type === 'boolean')
                        <div class="d-flex align-items-center gap-3">
                            <div class="form-check form-switch mb-0">
                                <input type="hidden"
                                    name="settings[{{ $setting->key }}_hidden]"
                                    value="0">
                                <input type="checkbox"
                                    class="form-check-input"
                                    role="switch"
                                    id="setting_{{ $setting->key }}"
                                    name="settings[{{ $setting->key }}]"
                                    value="1"
                                    style="width:44px;height:22px"
                                    {{ $setting->value ? 'checked' : '' }}
                                    onchange="markDirty(this)">
                            </div>
                            <label for="setting_{{ $setting->key }}"
                                class="form-check-label small"
                                style="color:{{ $setting->value ? '#10B981' : '#94A3B8' }}">
                                {{ $setting->value ? 'Enabled' : 'Disabled' }}
                            </label>
                        </div>

                        @elseif($setting->type === 'integer')
                        <input type="number"
                               id="setting_{{ $setting->key }}"
                               name="settings[{{ $setting->key }}]"
                               value="{{ old("settings.{$setting->key}", $setting->value) }}"
                               class="form-control form-control-sm"
                               style="max-width:120px"
                               onchange="markDirty(this)">

                        @else
                        <input type="text"
                               id="setting_{{ $setting->key }}"
                               name="settings[{{ $setting->key }}]"
                               value="{{ old("settings.{$setting->key}", $setting->value) }}"
                               class="form-control form-control-sm"
                               onchange="markDirty(this)">
                        @endif
                    </div>
                    <div class="col-md-2">
                        <span class="badge"
                              style="background:#F1F5F9;color:var(--slate);font-size:10px">
                            {{ strtoupper($setting->type) }}
                        </span>
                    </div>
                </div>
                @endforeach

            </div>
            @endforeach

        </div>

        {{-- Right column — info + recent changes --}}
        <div class="col-md-4">

            {{-- Save card --}}
            <div class="chart-card mb-4"
                 style="border:2px solid var(--navy);position:sticky;top:80px">
                <div style="font-size:14px;font-weight:700;color:var(--navy);margin-bottom:8px">
                    Save Settings
                </div>
                <p class="small text-muted mb-3">
                    All changes are immediately applied system-wide and recorded
                    in the audit log.
                </p>

                <div id="changeIndicator" class="alert mb-3"
                     style="background:#FEF3C7;color:#92400E;border:none;
                            border-radius:8px;font-size:12px;display:none">
                    ⚠ You have unsaved changes.
                </div>

                <button type="submit" class="btn w-100 mb-2"
                        style="background:var(--navy);color:#fff;
                               border-radius:8px;padding:10px">
                    Save All Settings
                </button>
                <a href="{{ route('dashboard') }}"
                   class="btn btn-outline-secondary w-100"
                   style="border-radius:8px;padding:10px;font-size:13px">
                    Cancel
                </a>
            </div>

            {{-- Quick SSO Status --}}
            @php
                $ssoEnabled = \App\Models\SystemSetting::get('sso_enabled', false);
            @endphp
            <div class="chart-card mb-4" style="border-left: 4px solid {{ $ssoEnabled ? '#10B981' : '#F59E0B' }};">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div style="font-size:12px;font-weight:600;color:var(--navy)">
                            <i class="bi bi-shield-lock"></i> SSO Status
                        </div>
                        <div style="font-size:11px;color:var(--slate)">
                            {{ $ssoEnabled ? '✅ Active Directory SSO is ENABLED' : '⚠️ SSO is DISABLED' }}
                        </div>
                    </div>
                    <span class="badge" style="background:{{ $ssoEnabled ? '#10B981' : '#F59E0B' }};color:#fff;">
                        {{ $ssoEnabled ? 'ACTIVE' : 'INACTIVE' }}
                    </span>
                </div>
            </div>

            {{-- Recent setting changes from audit log --}}
            <div class="chart-card">
                <div class="chart-title">Recent Changes</div>
                @php
                    $recentChanges = \App\Models\SystemAuditLog::where('module','settings')
                        ->with('user')
                        ->orderByDesc('created_at')
                        ->limit(8)
                        ->get();
                @endphp
                @forelse($recentChanges as $change)
                <div style="padding:8px 0;border-bottom:1px solid var(--border);font-size:12px">
                    <div class="fw-semibold" style="color:var(--navy)">
                        {{ $change->user?->name ?? 'System' }}
                    </div>
                    <div style="color:var(--slate);font-size:11px">
                        {{ $change->created_at->format('d M Y H:i') }}
                        &middot; {{ $change->created_at->diffForHumans() }}
                    </div>
                    @if($change->new_values)
                    <div style="margin-top:4px">
                        @foreach(array_keys($change->new_values) as $key)
                        <span style="background:#F1F5F9;border-radius:4px;
                                     padding:1px 6px;font-size:10px;
                                     font-family:monospace;color:var(--navy)">
                            {{ $key }}
                        </span>
                        @endforeach
                    </div>
                    @endif
                    <a href="{{ route('admin.audit-log.show', $change->id) }}"
                       style="font-size:10px;color:var(--navy)">View detail →</a>
                </div>
                @empty
                <div class="text-muted small">No changes recorded yet.</div>
                @endforelse
            </div>

        </div>
    </div>

</form>

<script>
function markDirty(el) {
    document.getElementById('changeIndicator').style.display = 'block';

    // Update boolean label
    if (el.type === 'checkbox') {
        const label = el.closest('.d-flex')?.querySelector('label');
        if (label) {
            label.textContent = el.checked ? 'Enabled' : 'Disabled';
            label.style.color = el.checked ? '#10B981' : '#94A3B8';
        }
    }
}

// Warn before leaving with unsaved changes
let isDirty = false;
document.querySelectorAll('input, select, textarea').forEach(el => {
    el.addEventListener('change', () => isDirty = true);
});
window.addEventListener('beforeunload', e => {
    if (isDirty) {
        e.preventDefault();
        e.returnValue = '';
    }
});
document.getElementById('settingsForm').addEventListener('submit', () => {
    isDirty = false;
});
</script>

@endsection
