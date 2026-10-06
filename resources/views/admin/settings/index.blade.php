@extends('layouts.app')
@section('title', 'System Settings')
@section('content')

@php
$tabMeta = [
    'general'       => ['icon' => 'bi-building-fill',       'label' => 'General',       'desc' => 'App name, company, currency & branding'],
    'budget'        => ['icon' => 'bi-clipboard2-fill',     'label' => 'Budget',        'desc' => 'Entry mode, calc mode, versioning & rules'],
    'notifications' => ['icon' => 'bi-bell-fill',           'label' => 'Notifications', 'desc' => 'Email triggers, reminders & deadlines'],
    'mail'          => ['icon' => 'bi-envelope-fill',       'label' => 'Mail / SMTP',   'desc' => 'Outgoing mail server credentials'],
    'security'      => ['icon' => 'bi-shield-lock-fill',    'label' => 'Security',      'desc' => 'Sessions, 2FA, lockouts & SSO / AD'],
    'backup'        => ['icon' => 'bi-archive-fill',        'label' => 'Backup',        'desc' => 'Scheduled database backups'],
];

// Settings to suppress from the UI (duplicate / internal-only)
$hiddenKeys = ['budget_entry_calc_mode'];
@endphp

<div class="d-flex justify-content-between align-items-center mb-1">
    <div>
        <h5 class="fw-bold mb-0">System Settings</h5>
        <p class="text-muted small mb-0">All changes are applied immediately and audit-logged.</p>
    </div>
</div>

<form method="POST" action="{{ route('admin.settings.update') }}"
      id="settingsForm" enctype="multipart/form-data">
@csrf

<div class="row g-4 mt-0">

    {{-- ── Main panel ─────────────────────────────────────────────────────── --}}
    <div class="col-xl-9 col-lg-8">

        {{-- Tab navigation --}}
        <div class="settings-tab-nav mb-3">
            @foreach($settings as $group => $groupSettings)
            @php $meta = $tabMeta[$group] ?? ['icon' => 'bi-wrench-adjustable', 'label' => ucfirst($group), 'desc' => '']; @endphp
            <button type="button"
                    class="settings-tab-btn {{ $loop->first ? 'active' : '' }}"
                    data-tab="tab-{{ $group }}"
                    onclick="switchTab('tab-{{ $group }}', this)">
                <i class="bi {{ $meta['icon'] }}"></i>
                <span>{{ $meta['label'] }}</span>
            </button>
            @endforeach
        </div>

        {{-- Tab panes --}}
        @foreach($settings as $group => $groupSettings)
        @php $meta = $tabMeta[$group] ?? ['icon' => 'bi-wrench-adjustable', 'label' => ucfirst($group), 'desc' => '']; @endphp

        <div class="settings-tab-pane {{ $loop->first ? 'active' : '' }}" id="tab-{{ $group }}">

            {{-- Pane header --}}
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="settings-pane-icon">
                    <i class="bi {{ $meta['icon'] }}"></i>
                </div>
                <div>
                    <div class="fw-bold" style="font-size:16px;color:var(--navy)">{{ $meta['label'] }}</div>
                    <div class="text-muted small">{{ $meta['desc'] }}</div>
                </div>
                @if($group === 'security')
                <div class="ms-auto">
                    @php $ssoEnabled = \App\Models\SystemSetting::get('sso_enabled', false); @endphp
                    <span class="badge" style="background:{{ $ssoEnabled ? '#10B981' : '#F59E0B' }};color:#fff;font-size:11px">
                        <i class="bi bi-shield-lock-fill me-1"></i>SSO {{ $ssoEnabled ? 'ACTIVE' : 'INACTIVE' }}
                    </span>
                </div>
                @endif
            </div>

            {{-- Settings rows --}}
            @php $prevKey = null; @endphp
            @foreach($groupSettings as $setting)
            @if(in_array($setting->key, $hiddenKeys)) @php $prevKey = $setting->key; @endphp @continue @endif

            {{-- Sub-group label for calc-mode dependents --}}
            @if($group === 'budget' && in_array($setting->key, ['admin_sets_rate', 'admin_sets_freq']) && $prevKey === 'line_item_calc_mode')
            <div class="settings-subgroup-label">
                <i class="bi bi-arrow-return-right me-1"></i>
                APPLIES WHEN CALC MODE IS QTY × RATE OR QTY × RATE × FREQUENCY
            </div>
            @elseif($group === 'security' && $setting->key === 'session_timeout_minutes' && $prevKey !== null)
            <div class="settings-subgroup-label mt-2">
                <i class="bi bi-person-lock me-1"></i>
                LOGIN & SESSION CONTROLS
            </div>
            @elseif($group === 'security' && $setting->key === 'sso_ad_server')
            <div class="settings-subgroup-label mt-2">
                <i class="bi bi-diagram-3-fill me-1"></i>
                ACTIVE DIRECTORY CONNECTION
            </div>
            @endif

            <div class="settings-row" data-setting-key="{{ $setting->key }}">
                @php $prevKey = $setting->key; @endphp

                {{-- Label + description --}}
                <div class="settings-row-label">
                    <label class="settings-label" for="setting_{{ $setting->key }}">
                        {{ $setting->label }}
                    </label>
                    @if($setting->description)
                    <div class="settings-desc">{{ $setting->description }}</div>
                    @endif
                </div>

                {{-- Control --}}
                <div class="settings-row-control">

                @if($setting->key === 'company_logo')
                    {{-- ── File upload with preview ── --}}
                    @php
                        $currentLogo = $setting->value;
                        $logoUrl = $currentLogo ? (str_starts_with($currentLogo, 'http') ? $currentLogo : asset($currentLogo)) : null;
                    @endphp
                    <div class="logo-upload-wrap">
                        @if($logoUrl)
                        <div class="logo-preview mb-2" id="logoPreview">
                            <img src="{{ $logoUrl }}" alt="Current logo"
                                 style="max-height:56px;max-width:180px;object-fit:contain;
                                        border:1px solid var(--border);border-radius:6px;padding:4px 8px;
                                        background:#fff">
                            <div class="text-muted" style="font-size:11px;margin-top:4px">Current logo</div>
                        </div>
                        @else
                        <div id="logoPreview" class="mb-2" style="display:none">
                            <img id="logoPreviewImg" src="" alt="Logo preview"
                                 style="max-height:56px;max-width:180px;object-fit:contain;
                                        border:1px solid var(--border);border-radius:6px;padding:4px 8px;
                                        background:#fff">
                        </div>
                        @endif
                        <label class="btn btn-outline-secondary btn-sm" style="cursor:pointer;margin-bottom:4px">
                            <i class="bi bi-upload me-1"></i>Upload Logo
                            <input type="file" name="logo_upload" accept="image/*"
                                   style="display:none" onchange="previewLogo(this)">
                        </label>
                        <div class="text-muted" style="font-size:11px">
                            PNG or SVG recommended. Max 2 MB. Used in PDF report headers.
                        </div>
                        @if($currentLogo)
                        <div class="text-muted" style="font-size:11px;margin-top:4px">
                            <code>{{ $currentLogo }}</code>
                        </div>
                        @endif
                    </div>

                @elseif($setting->key === 'budget_entry_mode')
                    {{-- ── Entry mode: quarterly vs monthly ── --}}
                    <div class="d-flex gap-2 flex-wrap">
                        @foreach(['quarterly' => 'Quarterly (Q1–Q4)', 'monthly' => '12-Month'] as $val => $lbl)
                        <label class="radio-pill {{ old("settings.{$setting->key}", $setting->value) === $val ? 'active' : '' }}">
                            <input type="radio" name="settings[{{ $setting->key }}]"
                                   value="{{ $val }}"
                                   {{ old("settings.{$setting->key}", $setting->value) === $val ? 'checked' : '' }}
                                   onchange="markDirty(this);this.closest('.d-flex').querySelectorAll('.radio-pill').forEach(p=>p.classList.remove('active'));this.closest('.radio-pill').classList.add('active')">
                            {{ $lbl }}
                        </label>
                        @endforeach
                    </div>
                    <div class="settings-hint">Default for new periods. Individual periods can override this.</div>

                @elseif($setting->key === 'currency_position')
                    {{-- ── Currency position: before / after ── --}}
                    <div class="d-flex gap-2">
                        @foreach(['before' => 'GHS 1,000  (Before)', 'after' => '1,000 GHS  (After)'] as $val => $lbl)
                        <label class="radio-pill {{ old("settings.{$setting->key}", $setting->value) === $val ? 'active' : '' }}">
                            <input type="radio" name="settings[{{ $setting->key }}]"
                                   value="{{ $val }}"
                                   {{ old("settings.{$setting->key}", $setting->value) === $val ? 'checked' : '' }}
                                   onchange="markDirty(this);this.closest('.d-flex').querySelectorAll('.radio-pill').forEach(p=>p.classList.remove('active'));this.closest('.radio-pill').classList.add('active')">
                            {{ $lbl }}
                        </label>
                        @endforeach
                    </div>

                @elseif($setting->type === 'boolean')
                    <div class="d-flex align-items-center gap-3">
                        <div class="form-check form-switch mb-0">
                            <input type="hidden" name="settings[{{ $setting->key }}_hidden]" value="0">
                            <input type="checkbox"
                                   class="form-check-input" role="switch"
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
                           style="max-width:140px"
                           onchange="markDirty(this)">

                @elseif($setting->key === 'line_item_calc_mode')
                    <select id="setting_{{ $setting->key }}"
                            name="settings[{{ $setting->key }}]"
                            class="form-select form-select-sm"
                            style="max-width:280px"
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
                    <div class="settings-hint">
                        "Admin Controls Rate / Freq" settings below apply only when this is not "Direct entry".
                    </div>

                @elseif($setting->key === 'actuals_approval_flow')
                    <select id="setting_{{ $setting->key }}"
                            name="settings[{{ $setting->key }}]"
                            class="form-select form-select-sm"
                            style="max-width:320px"
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

                @elseif($setting->key === 'actuals_budget_check_mode')
                    <select id="setting_{{ $setting->key }}"
                            name="settings[{{ $setting->key }}]"
                            class="form-select form-select-sm"
                            style="max-width:320px"
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

                @elseif($setting->key === 'supplementary_approval_mode')
                    <select id="setting_{{ $setting->key }}"
                            name="settings[{{ $setting->key }}]"
                            class="form-select form-select-sm"
                            style="max-width:320px"
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

                @elseif($setting->key === 'approver_reminder_mode')
                    <select id="setting_{{ $setting->key }}"
                            name="settings[{{ $setting->key }}]"
                            class="form-select form-select-sm"
                            style="max-width:240px"
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

                @elseif($setting->type === 'password')
                    <input type="password"
                           id="setting_{{ $setting->key }}"
                           name="settings[{{ $setting->key }}]"
                           value=""
                           autocomplete="new-password"
                           placeholder="Leave blank to keep current value"
                           class="form-control form-control-sm"
                           onchange="markDirty(this)">
                    <div class="settings-hint"><i class="bi bi-lock-fill me-1"></i>Stored encrypted.</div>

                @elseif($setting->key === 'mail_mailer')
                    <select id="setting_{{ $setting->key }}"
                            name="settings[{{ $setting->key }}]"
                            class="form-select form-select-sm"
                            style="max-width:260px"
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
                            style="max-width:260px"
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
                            style="max-width:180px"
                            onchange="markDirty(this)">
                        @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $val => $lbl)
                        <option value="{{ $val }}"
                            {{ old("settings.{$setting->key}", $setting->value) === $val ? 'selected' : '' }}>
                            {{ $lbl }}
                        </option>
                        @endforeach
                    </select>

                @elseif(in_array($setting->key, ['email_signature', 'login_page_message']))
                    <textarea id="setting_{{ $setting->key }}"
                              name="settings[{{ $setting->key }}]"
                              class="form-control form-control-sm"
                              rows="{{ $setting->key === 'email_signature' ? 3 : 2 }}"
                              onchange="markDirty(this)">{{ old("settings.{$setting->key}", $setting->value) }}</textarea>

                @else
                    <input type="text"
                           id="setting_{{ $setting->key }}"
                           name="settings[{{ $setting->key }}]"
                           value="{{ old("settings.{$setting->key}", $setting->value) }}"
                           class="form-control form-control-sm"
                           onchange="markDirty(this)">
                @endif

                </div>{{-- /settings-row-control --}}
            </div>{{-- /settings-row --}}
            @endforeach

        </div>{{-- /tab-pane --}}
        @endforeach

    </div>{{-- /col main --}}

    {{-- ── Right column — sticky sidebar ────────────────────────────────── --}}
    <div class="col-xl-3 col-lg-4">
    <div style="position:sticky;top:80px">

        {{-- Unsaved indicator + Save --}}
        <div class="chart-card mb-3" style="border:2px solid var(--navy)">
            <div style="font-size:13px;font-weight:700;color:var(--navy);margin-bottom:8px">
                Save Settings
            </div>
            <p class="small text-muted mb-3">
                Changes apply system-wide and are recorded in the audit log.
            </p>
            <div id="changeIndicator" class="mb-3"
                 style="background:#FEF3C7;color:#92400E;border-radius:8px;
                        padding:8px 12px;font-size:12px;display:none">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>Unsaved changes
            </div>
            <button type="submit" class="btn w-100 mb-2"
                    style="background:var(--navy);color:#fff;border-radius:8px;padding:10px;font-size:13px">
                <i class="bi bi-floppy-fill me-1"></i>Save All Settings
            </button>
            <a href="{{ route('dashboard') }}"
               class="btn btn-outline-secondary w-100"
               style="border-radius:8px;font-size:13px">Cancel</a>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible mb-3 py-2 px-3" style="font-size:13px">
            {{ session('success') }}
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
        @endif

        {{-- Test Mail --}}
        <div class="chart-card mb-3" style="border-left:4px solid #0EA5E9">
            <div style="font-size:12px;font-weight:700;color:var(--navy);margin-bottom:6px">
                <i class="bi bi-envelope-check-fill me-1" style="color:#0EA5E9"></i>Test Mail
            </div>
            <div class="mb-2">
                <input type="email" id="test_email_input" class="form-control form-control-sm"
                       value="{{ auth()->user()->email }}" placeholder="Send test to…">
            </div>
            <div id="testMailResult" class="mb-2" style="font-size:12px;display:none"></div>
            <button type="button" onclick="sendTestMail(this)" class="btn w-100"
                    style="background:#0EA5E9;color:#fff;border-radius:8px;font-size:12px;padding:7px">
                <i class="bi bi-send-fill me-1"></i>Send Test Email
            </button>
        </div>

        {{-- Manual reminders --}}
        @php $reminderMode = \App\Models\SystemSetting::get('approver_reminder_mode', 'off'); @endphp
        @if($reminderMode === 'manual')
        <div class="chart-card mb-3" style="border-left:4px solid #6366F1">
            <div style="font-size:12px;font-weight:700;color:var(--navy);margin-bottom:6px">
                <i class="bi bi-bell-fill me-1" style="color:#6366F1"></i>Approver Reminders
            </div>
            <div id="remindersResult" class="mb-2" style="font-size:12px;display:none"></div>
            <button type="button" onclick="sendReminders(this)" class="btn w-100"
                    style="background:#6366F1;color:#fff;border-radius:8px;font-size:12px;padding:7px">
                <i class="bi bi-send-fill me-1"></i>Send Reminders Now
            </button>
        </div>
        @endif

        {{-- Recent changes --}}
        <div class="chart-card">
            <div class="chart-title" style="font-size:12px">Recent Changes</div>
            @php
                $recentChanges = \App\Models\SystemAuditLog::where('module','settings')
                    ->with('user')->orderByDesc('created_at')->limit(6)->get();
            @endphp
            @forelse($recentChanges as $change)
            <div style="padding:7px 0;border-bottom:1px solid var(--border);font-size:11px">
                <div class="fw-semibold" style="color:var(--navy)">{{ $change->user?->name ?? 'System' }}</div>
                <div class="text-muted">{{ $change->created_at->diffForHumans() }}</div>
                @if($change->new_values)
                <div style="margin-top:3px">
                    @foreach(array_slice(array_keys($change->new_values), 0, 3) as $k)
                    <span style="background:#F1F5F9;border-radius:3px;padding:1px 5px;
                                 font-size:10px;font-family:monospace;color:var(--navy)">{{ $k }}</span>
                    @endforeach
                    @if(count($change->new_values) > 3)
                    <span class="text-muted" style="font-size:10px">+{{ count($change->new_values)-3 }} more</span>
                    @endif
                </div>
                @endif
                <a href="{{ route('admin.audit-log.show', $change->id) }}"
                   style="font-size:10px;color:var(--navy)">View →</a>
            </div>
            @empty
            <div class="text-muted small">No changes recorded yet.</div>
            @endforelse
        </div>

    </div>
    </div>

</div>
</form>

<style>
/* ── Tab navigation ──────────────────────────────────────────── */
.settings-tab-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    background: var(--card-bg, #fff);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 8px;
}
.settings-tab-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border: none;
    background: transparent;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    color: var(--slate);
    cursor: pointer;
    transition: all .15s;
    white-space: nowrap;
}
.settings-tab-btn:hover {
    background: #F1F5F9;
    color: var(--navy);
}
.settings-tab-btn.active {
    background: var(--navy);
    color: #fff;
    font-weight: 600;
}
.settings-tab-btn i { font-size: 14px; }

/* ── Pane ────────────────────────────────────────────────────── */
.settings-tab-pane { display: none; }
.settings-tab-pane.active { display: block; }
.settings-pane-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: var(--navy);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 18px; flex-shrink: 0;
}

/* ── Row ─────────────────────────────────────────────────────── */
.settings-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px 24px;
    align-items: start;
    padding: 14px 0;
    border-bottom: 1px solid var(--border);
}
@media (max-width: 768px) {
    .settings-row { grid-template-columns: 1fr; }
}
.settings-label {
    font-size: 13px;
    font-weight: 600;
    color: var(--navy);
    margin-bottom: 2px;
    display: block;
}
.settings-desc {
    font-size: 11px;
    color: var(--slate);
    line-height: 1.5;
}
.settings-hint {
    font-size: 11px;
    color: var(--slate);
    margin-top: 4px;
}

/* ── Sub-group label ─────────────────────────────────────────── */
.settings-subgroup-label {
    background: #F8FAFC;
    border-left: 3px solid #C9A84C;
    border-radius: 0 6px 6px 0;
    padding: 6px 12px;
    font-size: 11px;
    color: #92400E;
    font-weight: 700;
    letter-spacing: .3px;
    margin: 8px 0 2px;
}

/* ── Radio pill ──────────────────────────────────────────────── */
.radio-pill {
    display: inline-flex;
    align-items: center;
    padding: 5px 14px;
    border: 1px solid var(--border);
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    color: var(--slate);
    cursor: pointer;
    transition: all .15s;
    background: #fff;
    gap: 6px;
}
.radio-pill input[type=radio] { display: none; }
.radio-pill:hover { border-color: var(--navy); color: var(--navy); }
.radio-pill.active {
    background: var(--navy);
    border-color: var(--navy);
    color: #fff;
    font-weight: 600;
}

/* ── Logo upload ─────────────────────────────────────────────── */
.logo-upload-wrap { max-width: 280px; }

/* ── Unsaved dot ─────────────────────────────────────────────── */
.settings-tab-btn.dirty::after {
    content: '';
    display: inline-block;
    width: 6px; height: 6px;
    background: #F59E0B;
    border-radius: 50%;
    margin-left: 4px;
    vertical-align: middle;
}
</style>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

// ── Tab switching ──────────────────────────────────────────────
function switchTab(id, btn) {
    document.querySelectorAll('.settings-tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.settings-tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    btn.classList.add('active');
    try { localStorage.setItem('settings_tab', id); } catch(e) {}
}
document.addEventListener('DOMContentLoaded', function () {
    // Restore last active tab from localStorage or URL hash
    const saved = (window.location.hash.slice(1)) ||
                  (function(){ try { return localStorage.getItem('settings_tab'); } catch(e){} }());
    if (saved && document.getElementById(saved)) {
        const btn = document.querySelector(`[data-tab="${saved}"]`);
        if (btn) switchTab(saved, btn);
    }

    // Restore SMTP visibility
    const driverEl = document.getElementById('setting_mail_mailer');
    if (driverEl) toggleSmtpFields(driverEl.value);

    // Restore reminder freq visibility
    const modeEl = document.getElementById('setting_approver_reminder_mode');
    if (modeEl) toggleReminderFreq(modeEl.value);
});

// ── Logo preview ───────────────────────────────────────────────
function previewLogo(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const wrap = document.getElementById('logoPreview');
        let img = wrap.querySelector('img');
        if (!img) {
            img = document.createElement('img');
            img.id = 'logoPreviewImg';
            img.style = 'max-height:56px;max-width:180px;object-fit:contain;border:1px solid var(--border);border-radius:6px;padding:4px 8px;background:#fff';
            wrap.insertBefore(img, wrap.firstChild);
        }
        img.src = e.target.result;
        wrap.style.display = '';
        markDirty(input);
    };
    reader.readAsDataURL(input.files[0]);
}

// ── Mail toggle ────────────────────────────────────────────────
function toggleSmtpFields(driver) {
    ['mail_host','mail_port','mail_encryption','mail_username','mail_password'].forEach(key => {
        const row = document.querySelector(`[data-setting-key="${key}"]`);
        if (row) row.style.display = driver === 'smtp' ? '' : 'none';
    });
}

function toggleReminderFreq(mode) {
    document.querySelectorAll('[data-setting-key="approver_reminder_frequency_days"]').forEach(row => {
        row.style.display = mode === 'auto' ? '' : 'none';
    });
}

// ── Dirty tracking ────────────────────────────────────────────
function markDirty(el) {
    document.getElementById('changeIndicator').style.display = 'block';
    if (el.type === 'checkbox') {
        const label = el.closest('.d-flex')?.querySelector('label');
        if (label) {
            label.textContent = el.checked ? 'Enabled' : 'Disabled';
            label.style.color = el.checked ? '#10B981' : '#94A3B8';
        }
    }
}

let isDirty = false;
document.querySelectorAll('input, select, textarea').forEach(el => {
    el.addEventListener('change', () => { isDirty = true; });
});
window.addEventListener('beforeunload', e => {
    if (isDirty) { e.preventDefault(); e.returnValue = ''; }
});
document.getElementById('settingsForm').addEventListener('submit', () => { isDirty = false; });

// ── Test mail ──────────────────────────────────────────────────
async function sendTestMail(btn) {
    const email  = document.getElementById('test_email_input').value;
    const result = document.getElementById('testMailResult');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending…';
    result.style.display = 'none';
    try {
        const res = await fetch('{{ route("admin.settings.test-mail") }}', {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify({test_email: email})
        });
        const data = await res.json();
        result.style.display = 'block';
        result.style.color = data.success ? '#16A34A' : '#DC2626';
        result.innerHTML = (data.success ? '✓ ' : '✗ ') + (data.message || '');
    } catch(e) {
        result.style.display = 'block';
        result.style.color = '#DC2626';
        result.innerHTML = '✗ ' + e.message;
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-send-fill me-1"></i>Send Test Email';
}

// ── Reminders ─────────────────────────────────────────────────
async function sendReminders(btn) {
    const result = document.getElementById('remindersResult');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending…';
    result.style.display = 'none';
    try {
        const res = await fetch('{{ route("admin.settings.send-reminders") }}', {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify({})
        });
        const data = await res.json();
        result.style.display = 'block';
        result.style.color = data.success ? '#16A34A' : '#DC2626';
        result.innerHTML = (data.success ? '✓ ' : '✗ ') + (data.message || '');
    } catch(e) {
        result.style.display = 'block';
        result.style.color = '#DC2626';
        result.innerHTML = '✗ ' + e.message;
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-send-fill me-1"></i>Send Reminders Now';
}
</script>

@endsection
