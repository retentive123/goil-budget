@extends('layouts.app')

@section('title', 'System Documentation')

@push('styles')
<style>
  /* ── Docs palette (independent of layout CSS vars) ── */
  .docs-wrap { --d-navy: #1B2A4A; --d-gold: #C9A84C; }

  /* ── Docs layout ────────────────────────────────── */
  .docs-wrap {
    display: flex;
    gap: 0;
    min-height: 100%;
    align-items: flex-start;
  }

  /* Inner nav */
  .docs-nav {
    width: 210px;
    flex-shrink: 0;
    position: sticky;
    top: 0;
    max-height: calc(100vh - 88px);
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #CBD5E1 transparent;
    padding: 4px 0 40px;
  }
  .docs-nav::-webkit-scrollbar { width: 3px; }
  .docs-nav::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 2px; }

  .docs-nav-group { margin-bottom: 2px; }
  .docs-nav-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #94A3B8;
    padding: 14px 12px 5px;
    display: block;
  }
  .docs-nav-link {
    display: block;
    padding: 6px 12px;
    font-size: 13px;
    color: #64748B;
    text-decoration: none;
    border-left: 2px solid transparent;
    border-radius: 0 4px 4px 0;
    transition: color .1s, border-color .1s, background .1s;
    line-height: 1.4;
  }
  .docs-nav-link:hover { color: #1B2A4A; background: #F1F5F9; }
  .docs-nav-link.active { color: #1B2A4A; border-left-color: #C9A84C; background: #FEF9EC; font-weight: 600; }

  /* Content column */
  .docs-content {
    flex: 1;
    min-width: 0;
    padding: 0 0 80px 40px;
    max-width: 820px;
  }

  /* Sections */
  .doc-section { padding-top: 52px; }
  .doc-eyebrow {
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: 1.1px;
    text-transform: uppercase;
    color: #C9A84C;
    margin-bottom: 5px;
  }
  .doc-title {
    font-size: 24px;
    font-weight: 700;
    color: #1B2A4A;
    line-height: 1.2;
    margin-bottom: 10px;
  }
  .doc-lead {
    font-size: 14.5px;
    color: #64748B;
    max-width: 66ch;
    margin-bottom: 24px;
    line-height: 1.7;
  }
  .doc-sub { padding-top: 28px; margin-bottom: 4px; }
  .doc-sub-title {
    font-size: 16px;
    font-weight: 700;
    color: #1B2A4A;
    margin-bottom: 10px;
    padding-bottom: 7px;
    border-bottom: 1px solid #E2E8F0;
  }

  .docs-divider { border: none; border-top: 1px solid #E2E8F0; margin: 36px 0; }

  /* Prose */
  .docs-content p { margin-bottom: 12px; max-width: 70ch; }
  .docs-content p:last-child { margin-bottom: 0; }
  .docs-content ul, .docs-content ol { padding-left: 20px; margin-bottom: 12px; }
  .docs-content li { margin-bottom: 4px; max-width: 68ch; }

  /* Tables */
  .doc-table-wrap { overflow-x: auto; margin: 14px 0 20px; border-radius: 8px; border: 1px solid #E2E8F0; }
  .doc-table { width: 100%; border-collapse: collapse; font-size: 13.5px; font-variant-numeric: tabular-nums; }
  .doc-table thead tr { background: #F8FAFC; }
  .doc-table th {
    text-align: left; padding: 9px 13px;
    font-size: 10.5px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase;
    color: #64748B; border-bottom: 1px solid #E2E8F0; white-space: nowrap;
  }
  .doc-table td { padding: 8px 13px; border-bottom: 1px solid #F1F5F9; vertical-align: top; line-height: 1.5; }
  .doc-table tr:last-child td { border-bottom: none; }
  .doc-table tbody tr:hover td { background: #FAFBFD; }

  /* Callout boxes */
  .doc-note    { background: #EFF6FF; border-left: 3px solid #3B82F6; padding: 11px 14px; border-radius: 0 6px 6px 0; font-size: 13.5px; color: #1E40AF; margin: 14px 0; }
  .doc-warning { background: #FFFBEB; border-left: 3px solid #F59E0B; padding: 11px 14px; border-radius: 0 6px 6px 0; font-size: 13.5px; color: #92400E; margin: 14px 0; }
  .doc-tip     { background: #F0FDF4; border-left: 3px solid #10B981; padding: 11px 14px; border-radius: 0 6px 6px 0; font-size: 13.5px; color: #065F46; margin: 14px 0; }

  /* Badges */
  .db { display: inline-block; font-size: 10.5px; font-weight: 700; padding: 2px 8px; border-radius: 20px; white-space: nowrap; }
  .db-navy   { background: #1B2A4A; color: #fff; }
  .db-gold   { background: #FEF3C7; color: #92400E; }
  .db-green  { background: #D1FAE5; color: #065F46; }
  .db-rose   { background: #FEE2E2; color: #991B1B; }
  .db-slate  { background: #F1F5F9; color: #475569; }
  .db-blue   { background: #DBEAFE; color: #1E40AF; }
  .db-purple { background: #EDE9FE; color: #5B21B6; }

  /* Permission check/cross */
  .pc { color: #059669; font-size: 14px; }
  .px { color: #CBD5E1; font-size: 14px; }

  /* Workflow */
  .wf { display: flex; gap: 0; margin: 18px 0 24px; flex-wrap: wrap; }
  .wf-step { flex: 1; min-width: 110px; position: relative; }
  .wf-step + .wf-step::before { content: '→'; position: absolute; left: -10px; top: 14px; color: #94A3B8; font-size: 13px; }
  .wf-box { background: #fff; border: 1.5px solid #E2E8F0; border-radius: 8px; padding: 11px 13px; margin: 0 5px; font-size: 12px; }
  .wf-num { font-size: 10px; font-weight: 700; color: #94A3B8; letter-spacing: .4px; margin-bottom: 2px; }
  .wf-label { font-weight: 700; color: #1B2A4A; font-size: 12.5px; }
  .wf-sub { font-size: 11px; color: #64748B; margin-top: 1px; }
  .wf-done .wf-box { border-color: #10B981; background: #F0FDF4; }

  /* Setting keys */
  .sk { font-family: 'Courier New', Courier, monospace; font-size: 12px; background: #EEF2F7; color: #1B2A4A; padding: 1px 5px; border-radius: 3px; white-space: nowrap; }

  /* Intro hero */
  .docs-hero { background: #1B2A4A; border-radius: 10px; padding: 30px 36px; margin-bottom: 8px; color: #fff; }
  .docs-hero h1 { font-size: 26px; font-weight: 700; line-height: 1.2; margin-bottom: 8px; color: #fff; }
  .docs-hero h1 span { color: #E8C56A; }
  .docs-hero p { color: rgba(255,255,255,.65); max-width: 58ch; margin-bottom: 18px; font-size: 14px; line-height: 1.65; }
  .docs-hero-meta { display: flex; gap: 20px; flex-wrap: wrap; }
  .docs-hero-meta-item { font-size: 11.5px; color: rgba(255,255,255,.45); }
  .docs-hero-meta-item strong { color: rgba(255,255,255,.8); font-weight: 600; display: block; font-size: 12.5px; }

  @media (max-width: 900px) {
    .docs-nav { display: none; }
    .docs-content { padding-left: 0; }
  }
</style>
@endpush

@section('content')
<div class="docs-wrap">

  {{-- ── Inner nav ── --}}
  <aside class="docs-nav">

    <div class="docs-nav-group">
      <span class="docs-nav-label">Overview</span>
      <a class="docs-nav-link" href="#intro">Introduction</a>
      <a class="docs-nav-link" href="#roles">Roles &amp; Permissions</a>
      <a class="docs-nav-link" href="#getting-started">Getting Started</a>
    </div>

    <div class="docs-nav-group">
      <span class="docs-nav-label">Core Modules</span>
      <a class="docs-nav-link" href="#dashboard">Dashboard</a>
      <a class="docs-nav-link" href="#budget-entry">Budget Entry</a>
      <a class="docs-nav-link" href="#approvals">Approvals</a>
      <a class="docs-nav-link" href="#virements">Virements</a>
      <a class="docs-nav-link" href="#supplementary">Supplementary</a>
      <a class="docs-nav-link" href="#actuals">Actuals</a>
      <a class="docs-nav-link" href="#reports">Reports</a>
      <a class="docs-nav-link" href="#notifications">Notifications</a>
    </div>

    <div class="docs-nav-group">
      <span class="docs-nav-label">Administration</span>
      <a class="docs-nav-link" href="#admin-users">Users</a>
      <a class="docs-nav-link" href="#admin-departments">Departments &amp; Codes</a>
      <a class="docs-nav-link" href="#admin-periods">Budget Periods</a>
      <a class="docs-nav-link" href="#admin-approval-stages">Approval Stages</a>
      <a class="docs-nav-link" href="#admin-settings">System Settings</a>
      <a class="docs-nav-link" href="#admin-pnl-layout">P&amp;L Layout</a>
      <a class="docs-nav-link" href="#admin-bs-layout">BS Layout</a>
      <a class="docs-nav-link" href="#admin-audit">Audit Log</a>
      <a class="docs-nav-link" href="#admin-backups">Backups</a>
      <a class="docs-nav-link" href="#admin-overrides">Deadline Overrides</a>
    </div>

    <div class="docs-nav-group">
      <span class="docs-nav-label">Reference</span>
      <a class="docs-nav-link" href="#security">Security</a>
      <a class="docs-nav-link" href="#import-export">Import &amp; Export</a>
      <a class="docs-nav-link" href="#settings-ref">Settings Reference</a>
    </div>

  </aside>

  {{-- ── Content ── --}}
  <div class="docs-content">

    {{-- Introduction ──────────────────────────────────── --}}
    <div class="doc-section" id="intro">

      <div class="docs-hero">
        <h1>GOIL <span>Budget Tool</span><br>System Documentation</h1>
        <p>A centralised budgeting platform covering the full cycle — from departmental entry and multi-stage approval through virements, supplementary requests, actuals recording, and real-time reporting.</p>
        <div class="docs-hero-meta">
          <div class="docs-hero-meta-item"><strong>Organisation</strong>Ghana Oil Company Limited</div>
          <div class="docs-hero-meta-item"><strong>Currency</strong>GHS (Ghanaian Cedi)</div>
          <div class="docs-hero-meta-item"><strong>Authentication</strong>Local + Active Directory SSO</div>
          <div class="docs-hero-meta-item"><strong>Last updated</strong>July 2026</div>
        </div>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Key concepts</div>
        <div class="doc-table-wrap">
          <table class="doc-table">
            <thead><tr><th>Term</th><th>Meaning</th></tr></thead>
            <tbody>
              <tr><td><strong>Budget Period</strong></td><td>A named fiscal year or sub-period (e.g. "FY 2026"). Only one period may be open at a time.</td></tr>
              <tr><td><strong>Budget Version</strong></td><td>A department's submission for a period. Up to 4 versions allowed per department; rejected budgets create a new version.</td></tr>
              <tr><td><strong>Line Item</strong></td><td>A single account code entry with Q1–Q4 amounts.</td></tr>
              <tr><td><strong>Effective Budget</strong></td><td>Original approved budget + approved supplementary amounts. Used across all reports and over-budget checks.</td></tr>
              <tr><td><strong>Virement</strong></td><td>A reallocation of funds between two approved line items within the same department. Capped at 10% of the source line item.</td></tr>
              <tr><td><strong>Supplementary Budget</strong></td><td>An additional allocation requested when actual spend is projected to exceed the approved budget.</td></tr>
              <tr><td><strong>Actuals</strong></td><td>Month-by-month expenditure recorded against each line item; must be confirmed by a different user.</td></tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
    <hr class="docs-divider">

    {{-- Roles ──────────────────────────────────────────── --}}
    <div class="doc-section" id="roles">
      <div class="doc-eyebrow">Access Control</div>
      <div class="doc-title">Roles &amp; Permissions</div>
      <div class="doc-lead">Each user holds exactly one role. Roles determine which modules and actions are available.</div>

      <div class="doc-sub">
        <div class="doc-sub-title">System roles</div>
        <div class="doc-table-wrap">
          <table class="doc-table">
            <thead><tr><th>Role</th><th>Who holds it</th><th>Summary</th></tr></thead>
            <tbody>
              <tr><td><span class="db db-slate">department_user</span></td><td>Budget preparers</td><td>Create, edit, and submit budgets for their own department. Request virements and supplementary allocations.</td></tr>
              <tr><td><span class="db db-blue">department_head</span></td><td>HODs</td><td>All department user actions plus first-stage budget approval and report export.</td></tr>
              <tr><td><span class="db db-gold">finance_reviewer</span></td><td>Finance staff</td><td>View and approve all departments' budgets and virements. Manage account codes. Full report access.</td></tr>
              <tr><td><span class="db db-purple">gceo</span></td><td>Group CEO</td><td>View and approve all budgets and virements. Full read-only report access.</td></tr>
              <tr><td><span class="db db-purple">board</span></td><td>Board members</td><td>View and approve all budgets. Full report and export access.</td></tr>
              <tr><td><span class="db db-navy">bdu_admin</span></td><td>Budget &amp; Data Unit</td><td>Manage departments, account codes, users, and virements. Access audit log. Can disable 2FA for users.</td></tr>
              <tr><td><span class="db db-rose">super_admin</span></td><td>System administrator</td><td>All permissions. Manages system settings, backups, maintenance mode, and every module.</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Permission matrix</div>
        <div class="doc-table-wrap">
          <table class="doc-table">
            <thead>
              <tr>
                <th>Permission</th>
                <th style="text-align:center">dept_user</th>
                <th style="text-align:center">dept_head</th>
                <th style="text-align:center">finance</th>
                <th style="text-align:center">gceo</th>
                <th style="text-align:center">board</th>
                <th style="text-align:center">bdu_admin</th>
                <th style="text-align:center">super_admin</th>
              </tr>
            </thead>
            <tbody>
              <tr><td>Create / submit budget</td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="pc">✓</span></td></tr>
              <tr><td>View all budgets</td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="pc">✓</span></td></tr>
              <tr><td>Approve budget</td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="pc">✓</span></td></tr>
              <tr><td>Request virement</td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="pc">✓</span></td></tr>
              <tr><td>Approve virement</td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="pc">✓</span></td></tr>
              <tr><td>Request supplementary</td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="pc">✓</span></td></tr>
              <tr><td>Approve supplementary</td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="pc">✓</span></td></tr>
              <tr><td>View reports</td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="pc">✓</span></td></tr>
              <tr><td>Export reports</td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="pc">✓</span></td></tr>
              <tr><td>Manage users</td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="pc">✓</span></td></tr>
              <tr><td>Manage system settings</td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="pc">✓</span></td></tr>
              <tr><td>View audit log</td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="pc">✓</span></td></tr>
              <tr><td>Grant deadline override</td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="px">–</span></td><td style="text-align:center"><span class="pc">✓</span></td><td style="text-align:center"><span class="pc">✓</span></td></tr>
            </tbody>
          </table>
        </div>
        <div class="doc-note">Roles and permissions are configurable from <strong>Admin → Roles</strong>. The table above reflects the default seeded configuration.</div>
      </div>
    </div>
    <hr class="docs-divider">

    {{-- Getting Started ─────────────────────────────── --}}
    <div class="doc-section" id="getting-started">
      <div class="doc-eyebrow">Onboarding</div>
      <div class="doc-title">Getting Started</div>
      <div class="doc-lead">How to access the system, complete first-time setup, and configure your account.</div>

      <div class="doc-sub">
        <div class="doc-sub-title">Logging in</div>
        <p>Navigate to the application URL and enter your email address and password. If your organisation uses Active Directory, you may log in with your Windows credentials — the system attempts AD authentication first, then falls back to a local account if AD is unavailable.</p>
        <p>After five failed login attempts the account is locked for 15 minutes. Contact your administrator to unlock it manually.</p>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Two-factor authentication (2FA)</div>
        <p>2FA adds a second verification step using an authenticator app (Google Authenticator, Authy, or Microsoft Authenticator).</p>
        <ol>
          <li>After logging in, you are redirected to the <strong>2FA Setup</strong> page.</li>
          <li>Open your authenticator app and scan the QR code displayed.</li>
          <li>Enter the 6-digit code from your app to confirm setup.</li>
          <li>On future logins, you will be asked for your current code before being granted access.</li>
        </ol>
        <div class="doc-warning"><strong>Important:</strong> Complete the QR code confirmation before navigating away — if you leave without confirming, you will be redirected to the setup page again on your next login.</div>
        <p>To disable 2FA, go to <strong>Account → 2FA Setup</strong> and use the <em>Disable</em> section. Requires your current password. If you lost access to your authenticator app, contact your administrator.</p>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Changing your password</div>
        <p>Go to <strong>Account → Change Password</strong>. Passwords must contain uppercase, lowercase, numbers, and symbols. If password expiry is enabled (default: 90 days), you will be redirected to this page automatically when your password expires.</p>
      </div>
    </div>
    <hr class="docs-divider">

    {{-- Dashboard ──────────────────────────────────── --}}
    <div class="doc-section" id="dashboard">
      <div class="doc-eyebrow">Module</div>
      <div class="doc-title">Dashboard</div>
      <div class="doc-lead">The dashboard surface changes based on your role — departmental users see their own budget status; finance and admin roles see organisation-wide summaries.</div>

      <div class="doc-sub">
        <div class="doc-sub-title">Department users &amp; heads</div>
        <ul>
          <li><strong>Budget status card</strong> — current version status for the open period.</li>
          <li><strong>Quarter totals</strong> — Q1–Q4 breakdown of the current approved budget.</li>
          <li><strong>Top 5 line items</strong> by budget value.</li>
          <li><strong>Section summary</strong> — Revenue, Expense, CapEx, and Balance Sheet totals.</li>
          <li><strong>Monthly actuals chart</strong> — budget vs. actual spend by month.</li>
          <li><strong>Multi-period summary</strong> — how budget totals have changed across recent periods.</li>
        </ul>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Finance, Admin &amp; Senior leadership</div>
        <ul>
          <li><strong>Pending approvals</strong> — budgets awaiting your decision.</li>
          <li><strong>Period statistics</strong> — total / submitted / approved / rejected / draft / not-started counts.</li>
          <li><strong>Department status matrix</strong> — one-glance view of where every department stands.</li>
          <li><strong>Category breakdown</strong> — approved totals by account category.</li>
          <li><strong>Year-over-year trend</strong> — total approved budget across all periods.</li>
          <li><strong>Organisation budget chart</strong> — approved budget by department.</li>
        </ul>
        <div class="doc-tip">Finance dashboard data is cached for 5 minutes. If you have just approved a budget and the figures do not yet reflect it, wait a moment and refresh.</div>
      </div>
    </div>
    <hr class="docs-divider">

    {{-- Budget Entry ──────────────────────────────── --}}
    <div class="doc-section" id="budget-entry">
      <div class="doc-eyebrow">Module</div>
      <div class="doc-title">Budget Entry</div>
      <div class="doc-lead">Prepare and submit your department's annual budget. Amounts are entered per quarter (Q1–Q4) for each account code assigned to the department.</div>

      <div class="doc-sub">
        <div class="doc-sub-title">Starting a budget</div>
        <p>Go to <strong>Budget → My Budget</strong>. If no budget exists for the current period, click <em>Start Budget</em>. The system pre-populates all account codes assigned to your department.</p>
        <div class="doc-note">If no period is currently open, or the submission deadline has passed (and no override has been granted), you will not be able to start a budget. Contact the Budget &amp; Data Unit.</div>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Entry views</div>
        <p><strong>Standard view</strong> — grouped by account category, with Q1–Q4 inputs per line item.</p>
        <p><strong>P&amp;L view</strong> — income statement layout showing Revenue, Expenses, and Net Position. Both views save automatically as you type.</p>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Justifications</div>
        <p>If your administrator has enabled <em>require justification</em>, the system will block submission until every non-zero line item has a justification note entered.</p>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Submitting</div>
        <p>Click <em>Confirm &amp; Submit</em> to review a P&amp;L summary of your figures. Click <em>Submit for Approval</em> to confirm — the budget status changes to <span class="db db-blue">Submitted</span> and the first-stage approvers are notified.</p>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Version limits &amp; Excel import</div>
        <p>Each department may submit up to <strong>4 versions</strong> per period. Amounts can also be uploaded via an Excel template — download the template from the budget entry screen, fill in the Q1–Q4 columns, and upload.</p>
      </div>
    </div>
    <hr class="docs-divider">

    {{-- Approvals ──────────────────────────────────── --}}
    <div class="doc-section" id="approvals">
      <div class="doc-eyebrow">Module</div>
      <div class="doc-title">Budget Approval</div>
      <div class="doc-lead">Budgets pass through a configurable sequence of approval stages, each tied to a role.</div>

      <div class="doc-sub">
        <div class="doc-sub-title">Approval workflow</div>
        <div class="wf">
          <div class="wf-step"><div class="wf-box"><div class="wf-num">Stage 1</div><div class="wf-label">Department Head</div><div class="wf-sub">Initial review</div></div></div>
          <div class="wf-step"><div class="wf-box"><div class="wf-num">Stage 2</div><div class="wf-label">Finance Reviewer</div><div class="wf-sub">Detailed review</div></div></div>
          <div class="wf-step"><div class="wf-box"><div class="wf-num">Stage 3</div><div class="wf-label">GCEO</div><div class="wf-sub">Executive sign-off</div></div></div>
          <div class="wf-step wf-done"><div class="wf-box"><div class="wf-num">Final</div><div class="wf-label">Approved</div><div class="wf-sub">Budget locked</div></div></div>
        </div>
        <div class="doc-note">The stage sequence is configurable from <a href="{{ route('admin.approval-stages.index') }}">Admin → Approval Stages</a>.</div>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Actioning a budget</div>
        <p>Go to <strong>Approvals → Pending</strong>. Click a budget to open the review screen, then choose:</p>
        <ul>
          <li><strong>Approve</strong> — advances to the next stage, or marks fully <span class="db db-green">Approved</span> at the final stage.</li>
          <li><strong>Reject</strong> — returns the budget as <span class="db db-rose">Rejected</span> with a required comment. The department may start a new version.</li>
        </ul>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Segregation of duties</div>
        <p>The user who submitted a budget cannot approve or reject it, even if they hold an approver role. This is enforced when <em>Enforce Segregation of Duties</em> is enabled in settings.</p>
      </div>
    </div>
    <hr class="docs-divider">

    {{-- Virements ───────────────────────────────────── --}}
    <div class="doc-section" id="virements">
      <div class="doc-eyebrow">Module</div>
      <div class="doc-title">Virements</div>
      <div class="doc-lead">A virement reallocates budget between two approved line items within the same department without requiring a new budget version.</div>

      <div class="doc-sub">
        <div class="doc-sub-title">Rules</div>
        <ul>
          <li>Can only be requested against an <strong>approved</strong> budget version.</li>
          <li>Cannot exceed <strong>10%</strong> of the source line item's total budget (configurable).</li>
          <li>Cumulative virements from the same source are checked against the cap — multiple small transfers cannot circumvent the limit.</li>
          <li>The requester cannot approve or reject their own virement.</li>
        </ul>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Requesting &amp; approving</div>
        <p>Go to <strong>Virements → New Request</strong>. Select the source and destination line items, enter the amount and justification, then submit. Finance reviewers and admins see pending virements under <strong>Virements → Pending</strong>. On approval, Q1–Q4 amounts are adjusted proportionally across quarters.</p>
      </div>
    </div>
    <hr class="docs-divider">

    {{-- Supplementary ───────────────────────────────── --}}
    <div class="doc-section" id="supplementary">
      <div class="doc-eyebrow">Module</div>
      <div class="doc-title">Supplementary Budget</div>
      <div class="doc-lead">Request an additional allocation when projected spend is expected to exceed the approved budget.</div>

      <div class="doc-sub">
        <div class="doc-sub-title">When to request</div>
        <p>The system automatically blocks actuals confirmation when cumulative actuals for an expense line exceed its effective budget. A notification is sent to the department and they must submit a supplementary request for that line before recording further actuals.</p>
        <div class="doc-note">Revenue line items are never blocked by over-budget actuals — only expense lines are subject to the cap.</div>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Submitting &amp; approval</div>
        <p>Go to <strong>Supplementary → New Request</strong>. A batch is created — you may add multiple line items before submitting. Finance may approve or reject the whole batch or individual items within it, and may approve a lesser amount than requested. Once approved, the effective budget for affected lines increases immediately.</p>
      </div>
    </div>
    <hr class="docs-divider">

    {{-- Actuals ─────────────────────────────────────── --}}
    <div class="doc-section" id="actuals">
      <div class="doc-eyebrow">Module</div>
      <div class="doc-title">Actuals Recording</div>
      <div class="doc-lead">Monthly actual expenditure is recorded against each line item and confirmed by a second user.</div>

      <div class="doc-sub">
        <div class="doc-sub-title">Recording actuals</div>
        <p>Go to <strong>Actuals → Entry</strong>. Select the department, period, and month. For each line item, enter the actual amount spent. Entries are saved as <span class="db db-gold">Draft</span> until confirmed.</p>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Confirmation</div>
        <p>A <strong>different user</strong> must confirm the draft entries. Go to <strong>Actuals → Entry</strong>, find the drafts, and click <em>Confirm</em>. Once confirmed, entries become <span class="db db-green">Confirmed</span> and are used in all reports.</p>
        <div class="doc-warning">You cannot confirm your own entries. If a second user is unavailable, contact your administrator.</div>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Over-budget handling</div>
        <p>If confirming actuals for an expense line would cause cumulative actuals to exceed the effective budget, the confirmation is blocked and the department receives a notification. A supplementary budget must be requested for the affected line before proceeding.</p>
      </div>
    </div>
    <hr class="docs-divider">

    {{-- Reports ─────────────────────────────────────── --}}
    <div class="doc-section" id="reports">
      <div class="doc-eyebrow">Module</div>
      <div class="doc-title">Reports</div>
      <div class="doc-lead">A suite of analytical reports covering budget vs. actuals, trends, utilisation, virements, and financial statements.</div>

      <div class="doc-table-wrap">
        <table class="doc-table">
          <thead><tr><th>Report</th><th>What it shows</th><th>Export</th></tr></thead>
          <tbody>
            <tr><td><strong>Executive Summary</strong></td><td>Organisation-wide KPIs: total approved budget, actuals, utilisation %, top departments, category breakdown, quarterly trend.</td><td>–</td></tr>
            <tr><td><strong>Department Drill-down</strong></td><td>One department's approved line items grouped by type. Tabs for Revenue / Expense / CapEx analysis.</td><td>Excel</td></tr>
            <tr><td><strong>Code Explorer</strong></td><td>Account-code level analysis: budget vs. actuals by quarter, department breakdown, year-on-year trend per code.</td><td>Excel</td></tr>
            <tr><td><strong>Year-over-Year</strong></td><td>Compare any two periods side by side. Budget view, Actual view, and Combined view. Filter by type.</td><td>CSV / JSON / TSV</td></tr>
            <tr><td><strong>Department Comparison</strong></td><td>Side-by-side comparison of selected departments within a period.</td><td>–</td></tr>
            <tr><td><strong>Variance</strong></td><td>Budget vs. actuals per code, showing favorable/unfavorable variance. Filterable by threshold.</td><td>Excel</td></tr>
            <tr><td><strong>Utilisation</strong></td><td>Budget utilisation % per department with traffic-light status: critical (&gt;90%), warning (&gt;70%), healthy.</td><td>Excel</td></tr>
            <tr><td><strong>Virements</strong></td><td>All virement activity for the selected period.</td><td>Excel</td></tr>
            <tr><td><strong>Approved Budgets</strong></td><td>All approved line items by category and code for the selected period.</td><td>Excel / PDF</td></tr>
            <tr><td><strong>Financial Statement</strong></td><td>Full P&amp;L, cash flow, and balance sheet with monthly/quarterly columns and year-over-year comparison.</td><td>–</td></tr>
            <tr><td><strong>Capital Expenditure</strong></td><td>CapEx-only line items with budget and actual values.</td><td>–</td></tr>
          </tbody>
        </table>
      </div>
    </div>
    <hr class="docs-divider">

    {{-- Notifications ───────────────────────────────── --}}
    <div class="doc-section" id="notifications">
      <div class="doc-eyebrow">Module</div>
      <div class="doc-title">Notifications</div>
      <div class="doc-lead">In-app notifications for significant budget events. A bell icon in the topbar shows the unread count.</div>

      <div class="doc-table-wrap">
        <table class="doc-table">
          <thead><tr><th>Event</th><th>Who is notified</th></tr></thead>
          <tbody>
            <tr><td>Budget submitted</td><td>First-stage approvers</td></tr>
            <tr><td>Budget approved (stage)</td><td>Submitter + next-stage approvers</td></tr>
            <tr><td>Budget fully approved</td><td>Submitter</td></tr>
            <tr><td>Budget rejected</td><td>Submitter</td></tr>
            <tr><td>Virement requested</td><td>Finance reviewers (if enabled)</td></tr>
            <tr><td>Virement approved / rejected</td><td>Requester</td></tr>
            <tr><td>Supplementary submitted</td><td>Finance reviewers</td></tr>
            <tr><td>Supplementary approved / rejected</td><td>Requester</td></tr>
            <tr><td>Actuals over-budget blocked</td><td>Department user who triggered the block</td></tr>
          </tbody>
        </table>
      </div>
      <p>Email notifications are sent in parallel if <em>Email Notifications Enabled</em> is on in system settings.</p>
    </div>
    <hr class="docs-divider">

    {{-- ══════════════════════ ADMIN GUIDE ══════════════════════ --}}

    <div class="doc-section" id="admin-users">
      <div class="doc-eyebrow">Admin Guide</div>
      <div class="doc-title">Managing Users</div>
      <div class="doc-lead">Create and manage user accounts, assign roles, and control 2FA per user.</div>

      <div class="doc-sub">
        <div class="doc-sub-title">Creating a user</div>
        <p>Go to <strong>Admin → Users → Add User</strong>. Required fields: name, email, department, role, and initial password. The 2FA toggle can be pre-enabled — the user will be directed to the setup page on their first login.</p>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Activating / deactivating</div>
        <p>Deactivated users cannot log in but their historical data is preserved. Use the toggle on the user list or the status field on the edit form. Note: you cannot deactivate your own account from the edit form.</p>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Disabling a user's 2FA</div>
        <p>If a user has lost access to their authenticator app, an admin with the <code>disable two factor</code> permission can clear their 2FA from the user edit form. The user will be directed to set up 2FA again on their next login if the global 2FA setting is on.</p>
      </div>
    </div>
    <hr class="docs-divider">

    <div class="doc-section" id="admin-departments">
      <div class="doc-eyebrow">Admin Guide</div>
      <div class="doc-title">Departments, Categories &amp; Account Codes</div>

      <div class="doc-sub">
        <div class="doc-sub-title">Account categories</div>
        <div class="doc-table-wrap">
          <table class="doc-table">
            <thead><tr><th>Type</th><th>P&amp;L</th><th>Notes</th></tr></thead>
            <tbody>
              <tr><td><span class="db db-green">Revenue</span></td><td>Yes</td><td>Income lines</td></tr>
              <tr><td><span class="db db-rose">Expense</span></td><td>Yes</td><td>Subject to over-budget blocks on actuals</td></tr>
              <tr><td><span class="db db-blue">Both</span></td><td>Yes</td><td>Mixed income/expense</td></tr>
              <tr><td><span class="db db-gold">Capital Expenditure</span></td><td>No</td><td>CapEx report and balance sheet only</td></tr>
              <tr><td><span class="db db-purple">Assets / Liabilities</span></td><td>No</td><td>Balance sheet items</td></tr>
            </tbody>
          </table>
        </div>
        <p>Categories can be imported from Excel via <strong>Admin → Account Categories</strong>.</p>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Assigning codes to departments</div>
        <p>Go to <strong>Admin → Departments → [Department] → Account Codes</strong>. Use the sync interface to select which codes the department can budget for. When a department starts a budget, only their assigned codes appear as line items.</p>
        <div class="doc-tip">Adding a new code assignment to a department with an existing draft budget automatically adds the new line item to their draft.</div>
      </div>
    </div>
    <hr class="docs-divider">

    <div class="doc-section" id="admin-periods">
      <div class="doc-eyebrow">Admin Guide</div>
      <div class="doc-title">Budget Periods</div>
      <div class="doc-lead">Only one period may be open at a time. All activity operates within the currently open period.</div>

      <div class="doc-table-wrap">
        <table class="doc-table">
          <thead><tr><th>Status</th><th>Meaning</th></tr></thead>
          <tbody>
            <tr><td><span class="db db-slate">Draft</span></td><td>Created but not open. No budgets can be submitted.</td></tr>
            <tr><td><span class="db db-green">Open</span></td><td>Active. Departments can submit; approvers can action; actuals can be recorded.</td></tr>
            <tr><td><span class="db db-navy">Closed</span></td><td>No new submissions. All data is read-only.</td></tr>
          </tbody>
        </table>
      </div>
      <p>Clicking <em>Open Period</em> automatically closes any previously open period. The submission deadline equals the period open date plus the <span class="sk">budget_entry_deadline_days</span> setting.</p>
    </div>
    <hr class="docs-divider">

    <div class="doc-section" id="admin-approval-stages">
      <div class="doc-eyebrow">Admin Guide</div>
      <div class="doc-title">Approval Stages</div>
      <div class="doc-lead">The approval chain is fully configurable. Stages are processed in order — a budget must pass each active stage before being fully approved.</div>

      <p>Go to <strong>Admin → Approval Stages</strong>. Each stage has a name, a role assignment, an order number, and an active flag. Inactive stages are skipped. Use the drag handles or Move buttons to reorder.</p>
      <div class="doc-warning">Reordering stages affects future decisions only. Budgets already at a given stage continue from that stage.</div>
    </div>
    <hr class="docs-divider">

    <div class="doc-section" id="admin-settings">
      <div class="doc-eyebrow">Admin Guide</div>
      <div class="doc-title">System Settings</div>
      <div class="doc-lead">System-wide behaviour is controlled from Admin → Settings. All changes are immediately applied and recorded in the audit log.</div>
      <p>Settings are grouped into four sections: <strong>General</strong>, <strong>Budget</strong>, <strong>Notifications</strong>, and <strong>Security</strong>. See the <a href="#settings-ref">Settings Reference</a> below for a complete list of keys and their defaults.</p>
    </div>
    <hr class="docs-divider">

    {{-- P&L Configurable Layout ────────────────────────── --}}
    <div class="doc-section" id="admin-pnl-layout">
      <div class="doc-eyebrow">Admin Guide</div>
      <div class="doc-title">P&amp;L Configurable Layout</div>
      <div class="doc-lead">Design a custom income-statement structure that controls how the Financial Statement P&amp;L tab renders budget, actual, and prior-year figures. When an active layout exists, the report renders your layout instead of the default grouped view.</div>

      <div class="doc-sub">
        <div class="doc-sub-title">Data hierarchy</div>
        <p>Understanding the three-tier account hierarchy is essential before building a layout:</p>
        <div class="doc-table-wrap">
          <table class="doc-table">
            <thead><tr><th>Tier</th><th>Example</th><th>Role in layout</th></tr></thead>
            <tbody>
              <tr><td><strong>Account Sub-Category</strong></td><td>Petroleum Sales, Staff Costs</td><td>The unit you add to a layout row. Groups one or more account categories.</td></tr>
              <tr><td><strong>Account Category</strong></td><td>Fuel Sales, Salaries</td><td>Belongs to one sub-category. Shown when a sub-category row is expanded in the report.</td></tr>
              <tr><td><strong>Account Code</strong></td><td>4001 – Petrol, 5010 – Basic Salary</td><td>The leaf level. Shown under its parent category when expanded.</td></tr>
            </tbody>
          </table>
        </div>
        <div class="doc-note">A sub-category row in the layout aggregates the budget/actual totals of all account codes that belong to any category under it.</div>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Setting up a layout</div>
        <div class="wf">
          <div class="wf-step"><div class="wf-box"><div class="wf-num">Step 1</div><div class="wf-label">Open P&amp;L Layout</div><div class="wf-sub">Admin → P&amp;L Layout</div></div></div>
          <div class="wf-step"><div class="wf-box"><div class="wf-num">Step 2</div><div class="wf-label">Create layout</div><div class="wf-sub">Give it a name</div></div></div>
          <div class="wf-step"><div class="wf-box"><div class="wf-num">Step 3</div><div class="wf-label">Add lines</div><div class="wf-sub">Sub-category, subtotal, or spacer</div></div></div>
          <div class="wf-step wf-done"><div class="wf-box"><div class="wf-num">Step 4</div><div class="wf-label">Activate</div><div class="wf-sub">Report uses the layout</div></div></div>
        </div>
        <p>Only one layout may be active at a time. Activating a new layout automatically deactivates the previous one.</p>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Line types</div>
        <div class="doc-table-wrap">
          <table class="doc-table">
            <thead><tr><th>Type</th><th>What it does</th><th>Configuration</th></tr></thead>
            <tbody>
              <tr>
                <td><span class="db db-blue">Sub-Category</span></td>
                <td>Displays the aggregated budget and actual totals for one account sub-category. Clickable in the report to expand into account categories and their code items.</td>
                <td><strong>Sub-category</strong> (required) · <strong>Operator</strong>: Add or Less · <strong>Label</strong> (optional override) · <strong>CS% base</strong> toggle</td>
              </tr>
              <tr>
                <td><span class="db db-gold">Subtotal</span></td>
                <td>Computes and displays the running total of all Add lines minus all Less lines since the previous subtotal (or the start of the layout). Renders in a highlighted row.</td>
                <td><strong>Label</strong> (e.g. "Gross Profit", "Net Income")</td>
              </tr>
              <tr>
                <td><span class="db db-slate">Spacer</span></td>
                <td>A blank row for visual breathing room between sections. No data displayed.</td>
                <td>None</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Operator: Add vs. Less</div>
        <p>Each sub-category row carries an operator that controls its contribution to the next subtotal:</p>
        <ul>
          <li><strong>Add</strong> — the sub-category total is added to the running sum (typical for revenue lines).</li>
          <li><strong>Less</strong> — the sub-category total is subtracted from the running sum (typical for expense lines).</li>
        </ul>
        <p>A subtotal row shows: <em>sum of all Add lines above it − sum of all Less lines above it</em>, since the previous subtotal.</p>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">CS% base</div>
        <p>Marking a sub-category row as the <strong>CS% base</strong> designates it as the denominator for Cost-of-Sales percentage calculations. Subtotal rows will display the CS% figure alongside the monetary total, computed as: <em>subtotal ÷ CS% base × 100</em>.</p>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Editing and reordering lines</div>
        <p>Drag the <strong>⠿</strong> handle to reorder any line. Click the <strong>pencil icon</strong> on a sub-category or subtotal row to open an inline edit panel where you can change the sub-category, operator, or label without deleting and recreating the row.</p>
        <div class="doc-tip">Reordering lines in the editor immediately affects which codes are included in each running subtotal — review the layout on the report after making structural changes.</div>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">In the report</div>
        <p>When the P&amp;L tab of the Financial Statement report loads and an active layout exists:</p>
        <ul>
          <li>Each sub-category row shows budget, actual, previous-year budget, and variance columns.</li>
          <li>Subtotal rows display the computed running total in a highlighted band.</li>
          <li><strong>Click any sub-category row</strong> to expand it — the report inserts the account categories under that sub-category and their individual account code items inline, showing each code's budget and actual figures.</li>
          <li>Click the row again to collapse it.</li>
          <li>Use <strong>Expand All / Collapse All</strong> in the toolbar to toggle every sub-category at once.</li>
          <li>Use <strong>Export CSV</strong> to download the full layout including all expanded code items.</li>
        </ul>
      </div>
    </div>
    <hr class="docs-divider">

    {{-- Balance Sheet Configurable Layout ──────────────── --}}
    <div class="doc-section" id="admin-bs-layout">
      <div class="doc-eyebrow">Admin Guide</div>
      <div class="doc-title">Balance Sheet Configurable Layout</div>
      <div class="doc-lead">Design a custom balance sheet structure that controls how the Financial Statement Balance Sheet tab renders asset and liability figures. The feature mirrors the P&amp;L layout system but operates across two independent sections — Assets and Liabilities.</div>

      <div class="doc-sub">
        <div class="doc-sub-title">Two-section model</div>
        <p>The balance sheet maintains two separate running totals:</p>
        <ul>
          <li><strong>Assets</strong> — sub-categories whose account sub-category <span class="sk">budget_type</span> is <em>assets</em>.</li>
          <li><strong>Liabilities</strong> — sub-categories whose account sub-category <span class="sk">budget_type</span> is <em>liabilities</em>.</li>
        </ul>
        <p>The report footer always shows <strong>Total Assets</strong>, <strong>Total Liabilities</strong>, and <strong>Net Assets/(Liabilities)</strong> (Total Assets minus Total Liabilities) regardless of how many subtotal rows the layout contains.</p>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Setting up a layout</div>
        <p>Go to <strong>Admin → BS Layout</strong> and follow the same four-step process as the P&amp;L layout. The line type palette is identical — sub-category, subtotal, and spacer — with one key difference: subtotal rows require an explicit <strong>section</strong> choice.</p>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Line type differences vs. P&amp;L</div>
        <div class="doc-table-wrap">
          <table class="doc-table">
            <thead><tr><th>Line type</th><th>BS-specific behaviour</th></tr></thead>
            <tbody>
              <tr>
                <td><span class="db db-blue">Sub-Category</span></td>
                <td>The section badge (ASSETS / LIABILITIES) is derived automatically from the chosen sub-category's <span class="sk">budget_type</span>. No operator field — all sub-category lines are additive within their section. The section badge updates automatically when you change the sub-category in the inline editor.</td>
              </tr>
              <tr>
                <td><span class="db db-gold">Subtotal</span></td>
                <td>Requires an explicit <strong>Section</strong> selection (Assets or Liabilities) that determines which running total the row displays. This allows you to insert named sub-totals within either section (e.g. "Total Non-Current Assets", "Total Current Liabilities") before the overall footer totals.</td>
              </tr>
              <tr>
                <td><span class="db db-slate">Spacer</span></td>
                <td>Identical to P&amp;L.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">In the report</div>
        <p>When the Balance Sheet tab loads with an active layout:</p>
        <ul>
          <li>Asset sub-category rows are accented in blue; liability rows in red.</li>
          <li>Subtotal rows are colour-coded by section (blue for assets, red for liabilities).</li>
          <li><strong>Click any sub-category row</strong> to expand it — account categories and their code items appear inline, showing budget and actual figures for each code. Single click expands both categories and codes together.</li>
          <li>Expand All / Collapse All and Export CSV work identically to the P&amp;L layout view.</li>
        </ul>
        <div class="doc-warning">When exporting the balance sheet to CSV, subtotal row labels are written as plain text. Do not prefix labels with <strong>=</strong>, <strong>+</strong>, or <strong>-</strong> as spreadsheet applications may interpret these as formulas.</div>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Data hierarchy (same as P&amp;L)</div>
        <p>Balance sheet account codes are organised in the same three-tier hierarchy: <strong>Account Sub-Category → Account Category → Account Code</strong>. Assign account codes of type <span class="db db-purple">Assets / Liabilities</span> to the relevant categories, then assign those categories to sub-categories, before building the BS layout.</p>
      </div>
    </div>
    <hr class="docs-divider">

    <div class="doc-section" id="admin-audit">
      <div class="doc-eyebrow">Admin Guide</div>
      <div class="doc-title">Audit Log</div>
      <div class="doc-lead">An append-only record of all significant system events. Accessible to bdu_admin and super_admin only.</div>

      <div class="doc-table-wrap">
        <table class="doc-table">
          <thead><tr><th>Field</th><th>Description</th></tr></thead>
          <tbody>
            <tr><td>User</td><td>Who performed the action (or "System" for automated events)</td></tr>
            <tr><td>Module</td><td>auth / budget / approval / virement / actuals / admin / reports / settings / system</td></tr>
            <tr><td>Severity</td><td><span class="db db-green">info</span> <span class="db db-gold">warning</span> <span class="db db-rose">critical</span></td></tr>
            <tr><td>Old / New Values</td><td>JSON snapshot of before and after state</td></tr>
            <tr><td>IP Address</td><td>Client IP at time of action</td></tr>
          </tbody>
        </table>
      </div>
      <p>The log is filterable by module, severity, date range, and user. Exportable to Excel.</p>
    </div>
    <hr class="docs-divider">

    <div class="doc-section" id="admin-backups">
      <div class="doc-eyebrow">Admin Guide</div>
      <div class="doc-title">Backups</div>
      <p>Go to <strong>Admin → Backups</strong>. Click <em>Run Backup Now</em> to trigger an immediate backup. Backups are retained according to the <span class="sk">backup_keep_count</span> setting (default: 10). Older backups are pruned automatically. The <span class="sk">backup_frequency</span> setting (Daily / Weekly / Monthly) controls the automated schedule.</p>
    </div>
    <hr class="docs-divider">

    <div class="doc-section" id="admin-overrides">
      <div class="doc-eyebrow">Admin Guide</div>
      <div class="doc-title">Deadline Overrides</div>
      <p>Go to <strong>Admin → Deadline Overrides</strong>. Select the period, the department, and a new deadline date. Only one active override per department per period is allowed. When active, the department uses their override deadline instead of the global period deadline.</p>
    </div>
    <hr class="docs-divider">

    {{-- ══════════════════════ REFERENCE ══════════════════════ --}}

    <div class="doc-section" id="security">
      <div class="doc-eyebrow">Reference</div>
      <div class="doc-title">Security Features</div>

      <div class="doc-table-wrap">
        <table class="doc-table">
          <thead><tr><th>Feature</th><th>Default</th><th>Configurable</th></tr></thead>
          <tbody>
            <tr><td><strong>Two-factor authentication</strong> (TOTP)</td><td>Optional per user</td><td>Can be made mandatory globally</td></tr>
            <tr><td><strong>Single-session enforcement</strong> — new login terminates any existing session</td><td>On</td><td>Yes</td></tr>
            <tr><td><strong>Session timeout</strong> — auto-logout after inactivity</td><td>10 minutes</td><td>Yes</td></tr>
            <tr><td><strong>Login lockout</strong> — account locked after N failed attempts</td><td>5 attempts / 15 min</td><td>Yes</td></tr>
            <tr><td><strong>Password expiry</strong> — forced change after N days</td><td>90 days</td><td>Yes (0 = disabled)</td></tr>
            <tr><td><strong>Segregation of duties</strong> — submitter cannot approve/confirm their own records</td><td>On</td><td>Yes</td></tr>
            <tr><td><strong>Rate limiting</strong> — API: 120 req/min; login: 5/min per IP</td><td>Always on</td><td>No</td></tr>
            <tr><td><strong>Active Directory SSO</strong> — LDAP with local fallback</td><td>Off</td><td>Yes</td></tr>
            <tr><td><strong>Audit logging</strong> — all significant actions recorded</td><td>Always on</td><td>No</td></tr>
          </tbody>
        </table>
      </div>
    </div>
    <hr class="docs-divider">

    <div class="doc-section" id="import-export">
      <div class="doc-eyebrow">Reference</div>
      <div class="doc-title">Import &amp; Export</div>
      <div class="doc-lead">All major data objects can be imported via Excel templates. Download the template first — the column structure must match exactly.</div>

      <div class="doc-table-wrap">
        <table class="doc-table">
          <thead><tr><th>Template</th><th>Where to download</th><th>Notes</th></tr></thead>
          <tbody>
            <tr><td>Budget (standard)</td><td>Budget entry screen</td><td>Maps by account code; overwrites Q1–Q4 for matched items</td></tr>
            <tr><td>Budget (P&amp;L view)</td><td>Budget entry screen</td><td>Same mapping, P&amp;L layout</td></tr>
            <tr><td>Actuals</td><td>Actuals → Entry</td><td>Maps by code + month + year; creates draft entries</td></tr>
            <tr><td>Account Categories</td><td>Admin → Account Categories</td><td>Existing categories updated by code; new ones created</td></tr>
            <tr><td>Account Codes</td><td>Admin → Account Codes</td><td>Existing codes updated by code; new ones created</td></tr>
          </tbody>
        </table>
      </div>

      <p>Import errors are shown inline after upload. Fix the highlighted rows and re-upload — successfully processed rows are not duplicated.</p>

      <div class="doc-sub">
        <div class="doc-sub-title">Report exports</div>
        <ul>
          <li><strong>Excel (.xlsx)</strong> — Approved Budgets, Variance, Utilisation, Virement, Department Drill-down, Code Explorer</li>
          <li><strong>PDF</strong> — Approved Budgets</li>
          <li><strong>CSV / JSON / TSV</strong> — YoY report, Account Categories index</li>
        </ul>
      </div>
    </div>
    <hr class="docs-divider">

    <div class="doc-section" id="settings-ref">
      <div class="doc-eyebrow">Reference</div>
      <div class="doc-title">Settings Reference</div>
      <div class="doc-lead">Complete list of all system settings, their defaults, and their effect.</div>

      <div class="doc-sub">
        <div class="doc-sub-title">General</div>
        <div class="doc-table-wrap">
          <table class="doc-table">
            <thead><tr><th>Key</th><th>Default</th><th>Description</th></tr></thead>
            <tbody>
              <tr><td><span class="sk">app_name</span></td><td>GOIL Budget Tool</td><td>Application name shown in the header</td></tr>
              <tr><td><span class="sk">company_name</span></td><td>Ghana Oil Company Limited</td><td>Used in reports and exports</td></tr>
              <tr><td><span class="sk">currency_symbol</span></td><td>GHS</td><td>Currency prefix on all monetary displays</td></tr>
              <tr><td><span class="sk">fiscal_year_start</span></td><td>1 (January)</td><td>Month number (1–12) that begins the fiscal year</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Budget</div>
        <div class="doc-table-wrap">
          <table class="doc-table">
            <thead><tr><th>Key</th><th>Default</th><th>Description</th></tr></thead>
            <tbody>
              <tr><td><span class="sk">max_budget_versions</span></td><td>4</td><td>Max versions a department may submit per period</td></tr>
              <tr><td><span class="sk">virement_limit_pct</span></td><td>10</td><td>Max % of a source line item that may be vired away</td></tr>
              <tr><td><span class="sk">allow_virement_after_approval</span></td><td>true</td><td>Allow virements on approved budgets</td></tr>
              <tr><td><span class="sk">require_justification</span></td><td>false</td><td>Block submission unless every non-zero line has a justification</td></tr>
              <tr><td><span class="sk">budget_entry_deadline_days</span></td><td>30</td><td>Days after period opens within which departments must submit</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Security</div>
        <div class="doc-table-wrap">
          <table class="doc-table">
            <thead><tr><th>Key</th><th>Default</th><th>Description</th></tr></thead>
            <tbody>
              <tr><td><span class="sk">two_factor_enabled</span></td><td>false</td><td>Require 2FA for all users system-wide</td></tr>
              <tr><td><span class="sk">single_session_only</span></td><td>true</td><td>New login terminates any existing session for the same user</td></tr>
              <tr><td><span class="sk">session_timeout_minutes</span></td><td>10</td><td>Inactivity minutes before automatic logout (0 = disabled)</td></tr>
              <tr><td><span class="sk">max_login_attempts</span></td><td>5</td><td>Failed attempts before lockout</td></tr>
              <tr><td><span class="sk">login_lockout_minutes</span></td><td>15</td><td>Lockout duration after exceeding failed-attempt limit</td></tr>
              <tr><td><span class="sk">force_password_change_days</span></td><td>90</td><td>Days until password expiry (0 = never)</td></tr>
              <tr><td><span class="sk">enforce_segregation_of_duties</span></td><td>true</td><td>Prevent submitter from approving / confirming their own records</td></tr>
              <tr><td><span class="sk">sso_enabled</span></td><td>false</td><td>Enable Active Directory / LDAP authentication</td></tr>
              <tr><td><span class="sk">sso_fallback_to_local</span></td><td>true</td><td>Allow local password login if AD is unreachable</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Notifications</div>
        <div class="doc-table-wrap">
          <table class="doc-table">
            <thead><tr><th>Key</th><th>Default</th><th>Description</th></tr></thead>
            <tbody>
              <tr><td><span class="sk">email_notifications_enabled</span></td><td>true</td><td>Send email in addition to in-app notifications</td></tr>
              <tr><td><span class="sk">notify_on_submission</span></td><td>true</td><td>Notify approvers when a budget is submitted</td></tr>
              <tr><td><span class="sk">notify_on_approval</span></td><td>true</td><td>Notify submitter when their budget is approved</td></tr>
              <tr><td><span class="sk">notify_on_rejection</span></td><td>true</td><td>Notify submitter when their budget is rejected</td></tr>
              <tr><td><span class="sk">notify_finance_on_virement</span></td><td>true</td><td>Notify finance reviewers when a virement is requested</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Backup</div>
        <div class="doc-table-wrap">
          <table class="doc-table">
            <thead><tr><th>Key</th><th>Default</th><th>Description</th></tr></thead>
            <tbody>
              <tr><td><span class="sk">backup_enabled</span></td><td>true</td><td>Enable scheduled automatic backups</td></tr>
              <tr><td><span class="sk">backup_frequency</span></td><td>daily</td><td>daily / weekly / monthly</td></tr>
              <tr><td><span class="sk">backup_keep_count</span></td><td>10</td><td>Number of backups to retain before pruning oldest</td></tr>
              <tr><td><span class="sk">backup_notify_email</span></td><td>–</td><td>Email to notify on backup completion or failure</td></tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>{{-- end settings-ref --}}

  </div>{{-- end docs-content --}}
</div>{{-- end docs-wrap --}}
@endsection

@push('scripts')
<script>
  // Scroll-spy — listen on #main (the layout's scroll container)
  const docNavLinks = document.querySelectorAll('.docs-nav-link');
  const docSections = Array.from(document.querySelectorAll('.doc-section')).filter(s => s.id);
  const mainEl = document.getElementById('main');

  function updateDocActive() {
    const scrollTop = (mainEl ? mainEl.scrollTop : window.scrollY) + 100;
    let current = docSections[0];
    for (const s of docSections) {
      if (s.offsetTop <= scrollTop) current = s;
    }
    docNavLinks.forEach(l => {
      l.classList.toggle('active', l.getAttribute('href') === '#' + (current?.id ?? ''));
    });
  }

  (mainEl || window).addEventListener('scroll', updateDocActive, { passive: true });
  updateDocActive();
</script>
@endpush
