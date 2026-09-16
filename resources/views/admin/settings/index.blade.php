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
                                font-size:17px;color:#fff">
                        <i class="{{ match($group) {
                            'general'       => 'bi bi-building-fill',
                            'budget'        => 'bi bi-clipboard2-fill',
                            'notifications' => 'bi bi-bell-fill',
                            'mail'          => 'bi bi-envelope-fill',
                            'security'      => 'bi bi-shield-lock-fill',
                            'backup'        => 'bi bi-archive-fill',
                            default         => 'bi bi-wrench-adjustable'
                        } }}"></i>
                    </div>
                    <div>
                        <div style="font-size:14px;font-weight:700;color:var(--navy)">
                            {{ ucfirst($group) }}
                        </div>
                        <div style="font-size:12px;color:var(--slate)">
                            {{ match($group) {
                                'general'       => 'Application name, company, currency and fiscal year',
                                'budget'        => 'Calculation mode, version limits, actuals check, virement rules',
                                'notifications' => 'Email notification triggers and approver reminders',
                                'mail'          => 'SMTP server, credentials, and sender identity for all system emails',
                                'security'      => 'Session, login, and SSO security settings',
                                'backup'        => 'Scheduled backups, retention and notifications',
                                default         => ''
                            } }}
                        </div>
                    </div>
                    @if($group === 'security')
                    <div class="ms-auto">
                        <span class="badge" style="background:#E65C00;color:#fff;font-size:10px">
                            <i class="bi bi-shield-lock-fill me-1"></i>SSO & Security
                        </span>
                    </div>
                    @endif
                </div>

                @php $prevKey = null; @endphp
                @foreach($groupSettings as $setting)

                {{-- Visual sub-header: "Admin Controls" settings depend on calc mode --}}
                @if($group === 'budget' && in_array($setting->key, ['admin_sets_rate', 'admin_sets_freq']) && $prevKey === 'line_item_calc_mode')
                <div class="mb-3 mt-1 px-2 py-2"
                     style="background:#F8FAFC;border-left:3px solid #C9A84C;border-radius:0 6px 6px 0;font-size:11px;color:#92400E;font-weight:600;letter-spacing:.3px">
                    <i class="bi bi-arrow-return-right me-1"></i>
                    APPLIES WHEN CALC MODE IS QTY × RATE OR QTY × RATE × FREQUENCY
                </div>
                @elseif($group === 'budget' && $prevKey === 'admin_sets_freq' && !in_array($setting->key, ['admin_sets_rate', 'admin_sets_freq']))
                <div style="margin-bottom:4px"></div>
                @endif

                <div class="row align-items-start mb-4 pb-3"
                     style="border-bottom:1px solid var(--border)"
                     data-setting-key="{{ $setting->key }}">
                @php $prevKey = $setting->key; @endphp
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

                        @elseif($setting->key === 'line_item_calc_mode')
                        <select id="setting_{{ $setting->key }}"
                                name="settings[{{ $setting->key }}]"
                                class="form-select form-select-sm"
                                style="max-width:260px"
                                onchange="markDirty(this)">
                            @foreach([
                                'none'          => 'Direct entry (no Qty / Rate)',
                                'qty_rate'      => 'Qty × Rate',
                                'qty_rate_freq' => 'Qty × Rate × Frequency',
                            ] as $val => $lbl)
                            <option value="{{ $val }}"
                                {{ old("settings.{$setting->key}", $setting->value) === $val ? 'selected' : '' }}>
                                {{ $lbl }}
                            </option>
                            @endforeach
                        </select>
                        <div style="font-size:11px;color:var(--slate);margin-top:4px">
                            Affects all budget entry forms.
                            "Admin Controls Rate/Freq" settings below only apply
                            when this is not "Direct entry".
                        </div>

                        @elseif($setting->key === 'actuals_approval_flow')
                        <select id="setting_{{ $setting->key }}"
                                name="settings[{{ $setting->key }}]"
                                class="form-select form-select-sm"
                                style="max-width:300px"
                                onchange="markDirty(this)">
                            @foreach([
                                'simple'      => 'Simple — one step, confirm directly',
                                'multi_stage' => 'Multi-Stage — Dept User → Head → Finance',
                            ] as $val => $lbl)
                            <option value="{{ $val }}"
                                {{ old("settings.{$setting->key}", $setting->value) === $val ? 'selected' : '' }}>
                                {{ $lbl }}
                            </option>
                            @endforeach
                        </select>
                        <div style="font-size:11px;color:var(--slate);margin-top:4px">
                            <strong>Simple:</strong> anyone with "confirm actuals" locks the month directly.<br>
                            <strong>Multi-Stage:</strong> dept user submits → dept head confirms → finance gives final approval.
                        </div>

                        @elseif($setting->key === 'actuals_budget_check_mode')
                        <select id="setting_{{ $setting->key }}"
                                name="settings[{{ $setting->key }}]"
                                class="form-select form-select-sm"
                                style="max-width:260px"
                                onchange="markDirty(this)">
                            @foreach([
                                'annual'  => 'Annual (flexible) — YTD vs full annual budget',
                                'monthly' => 'Monthly (strict) — each month vs its allocation',
                            ] as $val => $lbl)
                            <option value="{{ $val }}"
                                {{ old("settings.{$setting->key}", $setting->value) === $val ? 'selected' : '' }}>
                                {{ $lbl }}
                            </option>
                            @endforeach
                        </select>
                        <div style="font-size:11px;color:var(--slate);margin-top:4px">
                            <strong>Annual:</strong> a single month can exceed its monthly allocation
                            as long as the total YTD stays under the annual budget.<br>
                            <strong>Monthly:</strong> each month's actuals cannot exceed that
                            month's specific budgeted amount.
                        </div>

                        @elseif($setting->key === 'supplementary_approval_mode')
                        <select id="setting_{{ $setting->key }}"
                                name="settings[{{ $setting->key }}]"
                                class="form-select form-select-sm"
                                style="max-width:300px"
                                onchange="markDirty(this)">
                            @foreach([
                                'finance_final'   => 'Finance approves (default)',
                                'dept_head_final' => 'Department Head approves finally',
                                'full_stages'     => 'Full approval stages (all configured stages)',
                            ] as $val => $lbl)
                            <option value="{{ $val }}"
                                {{ old("settings.{$setting->key}", $setting->value) === $val ? 'selected' : '' }}>
                                {{ $lbl }}
                            </option>
                            @endforeach
                        </select>
                        <div style="font-size:11px;color:var(--slate);margin-top:4px">
                            <strong>Finance approves:</strong> submitted requests go directly to Finance.<br>
                            <strong>Dept Head finally:</strong> department head is the final approver.<br>
                            <strong>Full stages:</strong> follows all configured approval stages in order.
                        </div>

                        @elseif($setting->key === 'approver_reminder_mode')
                        <select id="setting_{{ $setting->key }}"
                                name="settings[{{ $setting->key }}]"
                                class="form-select form-select-sm"
                                style="max-width:220px"
                                onchange="markDirty(this); toggleReminderFreq(this.value)">
                            @foreach([
                                'off'    => 'Off — no reminders',
                                'auto'   => 'Auto — send on schedule',
                                'manual' => 'Manual — triggered by admin',
                            ] as $val => $lbl)
                            <option value="{{ $val }}"
                                {{ old("settings.{$setting->key}", $setting->value) === $val ? 'selected' : '' }}>
                                {{ $lbl }}
                            </option>
                            @endforeach
                        </select>
                        <div style="font-size:11px;color:var(--slate);margin-top:4px">
                            <strong>Auto:</strong> sends in-app + email reminders to approvers on a set schedule.<br>
                            <strong>Manual:</strong> admin triggers reminders from this page.
                        </div>

                        @elseif($setting->type === 'password')
                        {{-- Password: masked input, blank = keep existing --}}
                        <input type="password"
                               id="setting_{{ $setting->key }}"
                               name="settings[{{ $setting->key }}]"
                               value=""
                               autocomplete="new-password"
                               placeholder="Leave blank to keep current password"
                               class="form-control form-control-sm"
                               onchange="markDirty(this)">
                        <div style="font-size:11px;color:var(--slate);margin-top:3px">
                            <i class="bi bi-lock-fill me-1"></i>Stored encrypted. Leave blank to keep the existing value.
                        </div>

                        @elseif($setting->key === 'mail_mailer')
                        <select id="setting_{{ $setting->key }}"
                                name="settings[{{ $setting->key }}]"
                                class="form-select form-select-sm"
                                style="max-width:220px"
                                onchange="markDirty(this); toggleSmtpFields(this.value)">
                            @foreach([
                                'smtp'  => 'SMTP — send via mail server',
                                'log'   => 'Log — write to log file (dev)',
                                'array' => 'Array — discard (testing)',
                            ] as $val => $lbl)
                            <option value="{{ $val }}"
                                {{ old("settings.{$setting->key}", $setting->value) === $val ? 'selected' : '' }}>
                                {{ $lbl }}
                            </option>
                            @endforeach
                        </select>

                        @elseif($setting->key === 'mail_encryption')
                        <select id="setting_{{ $setting->key }}"
                                name="settings[{{ $setting->key }}]"
                                class="form-select form-select-sm"
                                style="max-width:220px"
                                onchange="markDirty(this)">
                            @foreach([
                                'tls'  => 'TLS / STARTTLS (port 587)',
                                'ssl'  => 'SSL / Implicit TLS (port 465)',
                                'none' => 'None (port 25 — not recommended)',
                            ] as $val => $lbl)
                            <option value="{{ $val }}"
                                {{ old("settings.{$setting->key}", $setting->value) === $val ? 'selected' : '' }}>
                                {{ $lbl }}
                            </option>
                            @endforeach
                        </select>

                        @elseif($setting->key === 'backup_frequency')
                        <select id="setting_{{ $setting->key }}"
                                name="settings[{{ $setting->key }}]"
                                class="form-select form-select-sm"
                                style="max-width:160px"
                                onchange="markDirty(this)">
                            @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $val => $label)
                            <option value="{{ $val }}"
                                {{ old("settings.{$setting->key}", $setting->value) === $val ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                            @endforeach
                        </select>

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
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>You have unsaved changes.
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

            {{-- Test Mail --}}
            <div class="chart-card mb-4" style="border-left:4px solid #0EA5E9">
                <div style="font-size:13px;font-weight:700;color:var(--navy);margin-bottom:6px">
                    <i class="bi bi-envelope-check-fill me-2" style="color:#0EA5E9"></i>Test Mail Settings
                </div>
                <p style="font-size:12px;color:var(--slate);margin-bottom:10px">
                    Send a test email to verify your SMTP configuration is working.
                    <strong>Save settings first</strong>, then test.
                </p>
                <form method="POST" action="{{ route('admin.settings.test-mail') }}">
                    @csrf
                    <div class="mb-2">
                        <input type="email"
                               name="test_email"
                               class="form-control form-control-sm"
                               value="{{ auth()->user()->email }}"
                               placeholder="Send test to…">
                    </div>
                    <button type="submit" class="btn w-100"
                            style="background:#0EA5E9;color:#fff;border-radius:8px;font-size:13px">
                        <i class="bi bi-send-fill me-1"></i>Send Test Email
                    </button>
                </form>
            </div>

            {{-- Send Approver Reminders (manual trigger) --}}
            @php $reminderMode = \App\Models\SystemSetting::get('approver_reminder_mode', 'off'); @endphp
            @if($reminderMode === 'manual')
            <div class="chart-card mb-4" style="border-left:4px solid #6366F1">
                <div style="font-size:13px;font-weight:700;color:var(--navy);margin-bottom:6px">
                    <i class="bi bi-bell-fill me-2" style="color:#6366F1"></i>Approver Reminders
                </div>
                <p style="font-size:12px;color:var(--slate);margin-bottom:10px">
                    Manually send pending-approval reminders to all active approvers right now.
                </p>
                <form method="POST" action="{{ route('admin.settings.send-reminders') }}">
                    @csrf
                    <button type="submit" class="btn w-100"
                            style="background:#6366F1;color:#fff;border-radius:8px;font-size:13px">
                        <i class="bi bi-send-fill me-1"></i>Send Reminders Now
                    </button>
                </form>
            </div>
            @endif

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
                            @if($ssoEnabled)
                            <i class="bi bi-check-circle-fill text-success me-1"></i>Active Directory SSO is ENABLED
                        @else
                            <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>SSO is DISABLED
                        @endif
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
// Show/hide SMTP-specific settings based on the selected mail driver
function toggleSmtpFields(driver) {
    const smtpKeys = ['mail_host','mail_port','mail_encryption','mail_username','mail_password'];
    smtpKeys.forEach(key => {
        const row = document.querySelector(`[data-setting-key="${key}"]`);
        if (row) row.style.display = driver === 'smtp' ? '' : 'none';
    });
}
document.addEventListener('DOMContentLoaded', function () {
    const driverEl = document.getElementById('setting_mail_mailer');
    if (driverEl) toggleSmtpFields(driverEl.value);
});

function toggleReminderFreq(mode) {
    // Show/hide the frequency_days row based on mode
    document.querySelectorAll('[data-setting-key="approver_reminder_frequency_days"]').forEach(row => {
        row.style.display = mode === 'auto' ? '' : 'none';
    });
}
// Run on page load to set initial state
document.addEventListener('DOMContentLoaded', function () {
    const modeEl = document.getElementById('setting_approver_reminder_mode');
    if (modeEl) toggleReminderFreq(modeEl.value);
});

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
