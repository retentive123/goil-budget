<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Process Guide — GOIL Budget</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap">
  <style>
/* ── Tokens ──────────────────────────────────────────────────────────────── */
:root {
  --navy:      #1B2A4A;
  --gold:      #C9A84C;
  --app-brand: #E65C00;

  --bg:        #F4F7FC;
  --surface:   #FFFFFF;
  --surface-2: #EEF3FA;

  --text:      #1E293B;
  --text-2:    #475569;
  --text-3:    #94A3B8;

  --border:    #CBD5E1;
  --border-2:  #E2E8F0;

  --admin-c: #1B2A4A; --admin-bg: #E8EDF5;
  --fin-c:   #0369A1; --fin-bg:   #DBEAFE;
  --head-c:  #6D28D9; --head-bg:  #EDE9FE;
  --user-c:  #A16207; --user-bg:  #FEF9C3;
  --all-c:   #374151; --all-bg:   #F1F5F9;

  --tip-bg:  #F0FDF4; --tip-b:    #22C55E;
  --warn-bg: #FFFBEB; --warn-b:   #F59E0B;
  --note-bg: #EFF6FF; --note-b:   #3B82F6;

  --step-hl:  rgba(201,168,76,.12);
  --step-dim: 0.28;
  --radius:   8px;
}

@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) {
    --bg:        #0C1628;
    --surface:   #132038;
    --surface-2: #182843;
    --text:      #DCE8F8;
    --text-2:    #8AAAC8;
    --text-3:    #4A6380;
    --border:    #1E3355;
    --border-2:  #172C4A;
    --admin-c: #7B9CC8; --admin-bg: #0A1525;
    --fin-c:   #38BDF8; --fin-bg:   #071E34;
    --head-c:  #A78BFA; --head-bg:  #130A2A;
    --user-c:  #FBBF24; --user-bg:  #1A1100;
    --all-c:   #94A3B8; --all-bg:   #182236;
    --tip-bg:  #022C16; --tip-b:    #22C55E;
    --warn-bg: #1A1200; --warn-b:   #F59E0B;
    --note-bg: #071830; --note-b:   #3B82F6;
    --step-hl: rgba(201,168,76,.15);
  }
}
:root[data-theme="dark"] {
  --bg:        #0C1628; --surface: #132038; --surface-2: #182843;
  --text:      #DCE8F8; --text-2:  #8AAAC8; --text-3:    #4A6380;
  --border:    #1E3355; --border-2:#172C4A;
  --admin-c: #7B9CC8; --admin-bg: #0A1525;
  --fin-c:   #38BDF8; --fin-bg:   #071E34;
  --head-c:  #A78BFA; --head-bg:  #130A2A;
  --user-c:  #FBBF24; --user-bg:  #1A1100;
  --all-c:   #94A3B8; --all-bg:   #182236;
  --tip-bg:  #022C16; --tip-b:    #22C55E;
  --warn-bg: #1A1200; --warn-b:   #F59E0B;
  --note-bg: #071830; --note-b:   #3B82F6;
  --step-hl: rgba(201,168,76,.15);
}

/* ── Reset ───────────────────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
  font-family: 'DM Sans', system-ui, sans-serif;
  font-size: 15px;
  line-height: 1.65;
  color: var(--text);
  background: var(--bg);
}
a { color: var(--fin-c); }
strong { font-weight: 600; }

/* ── Back bar ─────────────────────────────────────────────────────────────── */
.back-bar {
  background: var(--app-brand);
  padding: 8px 0;
  position: sticky;
  top: 0;
  z-index: 200;
}
.back-bar .wrap {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
.back-link {
  color: #fff;
  text-decoration: none;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: .02em;
  opacity: .9;
  display: flex;
  align-items: center;
  gap: 6px;
}
.back-link:hover { opacity: 1; color: #fff; }
.back-link svg { flex-shrink: 0; }
.back-bar-title {
  font-size: 11px;
  color: rgba(255,255,255,.65);
  font-family: 'DM Mono', monospace;
  letter-spacing: .05em;
}

/* ── Layout ──────────────────────────────────────────────────────────────── */
.wrap { max-width: 860px; margin: 0 auto; padding: 0 24px; }

/* ── Doc header ──────────────────────────────────────────────────────────── */
.doc-header {
  background: var(--navy);
  color: #fff;
  padding: 44px 0 34px;
}
.doc-header .wrap { display: flex; flex-direction: column; gap: 8px; }
.doc-eyebrow {
  font-family: 'DM Mono', monospace;
  font-size: 11px;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: var(--gold);
  opacity: .9;
}
.doc-title {
  font-family: 'Playfair Display', Georgia, serif;
  font-size: clamp(26px, 5vw, 38px);
  font-weight: 700;
  color: #fff;
  text-wrap: balance;
  line-height: 1.2;
}
.doc-sub {
  font-size: 14px;
  color: rgba(255,255,255,.65);
  max-width: 560px;
  margin-top: 4px;
}
.gold-rule { width: 48px; height: 3px; background: var(--gold); border-radius: 2px; margin-top: 16px; }

/* ── Process timeline ────────────────────────────────────────────────────── */
.timeline-wrap {
  background: var(--surface);
  border-bottom: 1px solid var(--border-2);
  padding: 22px 0;
  overflow-x: auto;
}
.timeline {
  display: flex;
  align-items: center;
  gap: 0;
  min-width: 680px;
  padding: 0 24px;
  max-width: 860px;
  margin: 0 auto;
}
.tl-phase { display: flex; flex-direction: column; align-items: center; gap: 5px; flex: 1; }
.tl-bubble {
  width: 38px; height: 38px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-family: 'Playfair Display', serif;
  font-size: 15px; font-weight: 700; color: #fff;
  flex-shrink: 0;
}
.tl-label { font-size: 11px; font-weight: 600; color: var(--text-2); text-align: center; letter-spacing: .02em; }
.tl-who   { font-size: 10px; color: var(--text-3); font-family: 'DM Mono', monospace; text-align: center; }
.tl-arrow { font-size: 18px; color: var(--border); padding: 0 4px; flex-shrink: 0; margin-bottom: 22px; }

.ph1 { background: var(--navy); }
.ph2 { background: var(--user-c); }
.ph3 { background: var(--head-c); }
.ph4 { background: var(--user-c); }
.ph5 { background: var(--fin-c); }
.ph6 { background: #059669; }

/* ── Role filter bar ─────────────────────────────────────────────────────── */
.filter-bar {
  position: sticky;
  top: 37px; /* height of back-bar */
  z-index: 100;
  background: var(--surface);
  border-bottom: 1px solid var(--border-2);
  padding: 10px 0;
}
.filter-inner {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  padding: 0 24px;
  max-width: 860px;
  margin: 0 auto;
}
.filter-label {
  font-size: 11px; font-weight: 600;
  color: var(--text-3);
  letter-spacing: .06em; text-transform: uppercase;
  margin-right: 2px; white-space: nowrap;
}
.role-btn {
  font-family: 'DM Sans', sans-serif;
  font-size: 12px; font-weight: 600;
  border: 1.5px solid transparent;
  border-radius: 20px;
  padding: 4px 12px;
  cursor: pointer;
  transition: opacity .15s;
  background: var(--all-bg); color: var(--all-c);
}
.role-btn:hover { opacity: .85; }
.role-btn:focus-visible { outline: 2px solid var(--gold); outline-offset: 2px; }
.role-btn.active { border-color: currentColor; }
.role-btn[data-role="admin"]   { background: var(--admin-bg); color: var(--admin-c); }
.role-btn[data-role="finance"] { background: var(--fin-bg);   color: var(--fin-c); }
.role-btn[data-role="head"]    { background: var(--head-bg);  color: var(--head-c); }
.role-btn[data-role="user"]    { background: var(--user-bg);  color: var(--user-c); }

/* ── Content ─────────────────────────────────────────────────────────────── */
.content { padding: 48px 0 80px; }

/* ── Phase section ───────────────────────────────────────────────────────── */
.phase { margin-bottom: 56px; }
.phase-header {
  display: flex; align-items: flex-start; gap: 16px;
  margin-bottom: 28px;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--border-2);
}
.phase-num {
  font-family: 'Playfair Display', serif;
  font-size: 40px; font-weight: 700; line-height: 1;
  flex-shrink: 0; width: 56px; text-align: right;
  color: var(--border);
}
.phase-meta { flex: 1; }
.phase-eyebrow {
  font-family: 'DM Mono', monospace;
  font-size: 10px; letter-spacing: .1em; text-transform: uppercase;
  margin-bottom: 2px;
}
.phase-name {
  font-family: 'Playfair Display', serif;
  font-size: 22px; font-weight: 700;
  color: var(--text); text-wrap: balance;
}
.phase-desc { font-size: 13px; color: var(--text-2); margin-top: 4px; max-width: 520px; }
.phase-roles { display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap; }

/* ── Steps ───────────────────────────────────────────────────────────────── */
.steps { display: flex; flex-direction: column; gap: 4px; }
.step {
  display: grid;
  grid-template-columns: auto 28px 1fr;
  gap: 0 12px;
  align-items: start;
  padding: 12px 14px 12px 0;
  border-radius: var(--radius);
  border-left: 3px solid transparent;
  transition: opacity .2s, border-color .2s, background .2s;
}
.step:hover { background: var(--surface-2); }

body[data-filter] .step { opacity: var(--step-dim); }
body[data-filter] .step.matched {
  opacity: 1;
  background: var(--step-hl);
  border-left-color: var(--gold);
}

.step-badge {
  display: inline-flex; align-items: center; justify-content: center;
  font-family: 'DM Mono', monospace;
  font-size: 9px; font-weight: 500; letter-spacing: .05em;
  text-transform: uppercase;
  border-radius: 4px; padding: 3px 7px;
  white-space: nowrap; align-self: start; margin-top: 1px;
}
.badge-admin   { background: var(--admin-bg); color: var(--admin-c); }
.badge-finance { background: var(--fin-bg);   color: var(--fin-c); }
.badge-head    { background: var(--head-bg);  color: var(--head-c); }
.badge-user    { background: var(--user-bg);  color: var(--user-c); }
.badge-all     { background: var(--all-bg);   color: var(--all-c); }

.step-num { font-family: 'DM Mono', monospace; font-size: 12px; color: var(--text-3); font-weight: 500; text-align: right; padding-top: 2px; }
.step-action { font-weight: 600; color: var(--text); font-size: 14.5px; line-height: 1.4; }
.step-detail { font-size: 13px; color: var(--text-2); margin-top: 3px; line-height: 1.55; }

.ui {
  font-family: 'DM Mono', monospace;
  font-size: 12px;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 4px;
  padding: 1px 6px;
  color: var(--text);
  white-space: nowrap;
}

.outcome {
  display: flex; align-items: flex-start; gap: 10px;
  background: var(--tip-bg); border: 1px solid var(--tip-b);
  border-radius: var(--radius); padding: 12px 14px;
  margin-top: 8px; font-size: 13px; color: var(--text);
}
.outcome-icon { color: var(--tip-b); font-size: 16px; flex-shrink: 0; margin-top: 1px; }

.callout {
  border-radius: var(--radius); padding: 13px 16px;
  margin: 12px 0 4px; font-size: 13px; color: var(--text);
  display: flex; gap: 10px; align-items: flex-start;
}
.callout-tip  { background: var(--tip-bg);  border-left: 3px solid var(--tip-b); }
.callout-warn { background: var(--warn-bg); border-left: 3px solid var(--warn-b); }
.callout-note { background: var(--note-bg); border-left: 3px solid var(--note-b); }
.callout-icon { font-size: 15px; flex-shrink: 0; margin-top: 1px; }
.callout strong { display: block; margin-bottom: 2px; font-size: 12px; text-transform: uppercase; letter-spacing: .05em; }

/* ── Quick Reference table ───────────────────────────────────────────────── */
.qr-wrap { overflow-x: auto; }
.qr-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.qr-table th {
  background: var(--surface-2); color: var(--text-2);
  font-size: 11px; font-weight: 600; text-transform: uppercase;
  letter-spacing: .06em; padding: 8px 12px;
  text-align: left; border-bottom: 2px solid var(--border);
}
.qr-table td { padding: 10px 12px; border-bottom: 1px solid var(--border-2); vertical-align: top; }
.qr-table tr:last-child td { border-bottom: none; }
.qr-table .ui { font-size: 11.5px; }

.section-divider { height: 1px; background: var(--border-2); margin: 48px 0; }

.doc-footer {
  background: var(--surface); border-top: 1px solid var(--border-2);
  padding: 24px 0; font-size: 12px; color: var(--text-3); text-align: center;
}

@media (max-width: 600px) {
  .step { grid-template-columns: auto 20px 1fr; gap: 0 8px; }
  .timeline { min-width: 520px; }
  .tl-bubble { width: 32px; height: 32px; font-size: 13px; }
  .tl-arrow  { font-size: 14px; }
}
  </style>
</head>
<body>

{{-- ── Back navigation bar ──────────────────────────────────────────────── --}}
<div class="back-bar">
  <div class="wrap">
    <a href="{{ route('dashboard') }}" class="back-link">
      <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M9 11L5 7L9 3" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      Back to Dashboard
    </a>
    <span class="back-bar-title">GOIL Budget System</span>
  </div>
</div>

{{-- ── Doc header ───────────────────────────────────────────────────────── --}}
<header class="doc-header">
  <div class="wrap">
    <div class="doc-eyebrow">GOIL Budget System</div>
    <h1 class="doc-title">Process Guide</h1>
    <p class="doc-sub">The complete workflow — from first-time setup through monthly actuals and management reports. Everyone's steps, in the order they happen.</p>
    <div class="gold-rule"></div>
  </div>
</header>

{{-- ── Process timeline ─────────────────────────────────────────────────── --}}
<div class="timeline-wrap">
  <div class="timeline">
    <div class="tl-phase">
      <div class="tl-bubble ph1">1</div>
      <div class="tl-label">Setup</div>
      <div class="tl-who">Admin</div>
    </div>
    <div class="tl-arrow">›</div>
    <div class="tl-phase">
      <div class="tl-bubble ph2">2</div>
      <div class="tl-label">Budget Entry</div>
      <div class="tl-who">Dept User</div>
    </div>
    <div class="tl-arrow">›</div>
    <div class="tl-phase">
      <div class="tl-bubble ph3">3</div>
      <div class="tl-label">Budget Approval</div>
      <div class="tl-who">Head → Finance</div>
    </div>
    <div class="tl-arrow">›</div>
    <div class="tl-phase">
      <div class="tl-bubble ph4">4</div>
      <div class="tl-label">Actuals Entry</div>
      <div class="tl-who">Dept User (monthly)</div>
    </div>
    <div class="tl-arrow">›</div>
    <div class="tl-phase">
      <div class="tl-bubble ph5">5</div>
      <div class="tl-label">Actuals Approval</div>
      <div class="tl-who">Head → Finance</div>
    </div>
    <div class="tl-arrow">›</div>
    <div class="tl-phase">
      <div class="tl-bubble ph6">6</div>
      <div class="tl-label">Reports</div>
      <div class="tl-who">Finance · Management</div>
    </div>
  </div>
</div>

{{-- ── Role filter bar ──────────────────────────────────────────────────── --}}
<div class="filter-bar">
  <div class="filter-inner">
    <span class="filter-label">Show my steps:</span>
    <button class="role-btn" data-role="all"     onclick="setFilter('all')">All Roles</button>
    <button class="role-btn" data-role="admin"   onclick="setFilter('admin')">BDU Admin</button>
    <button class="role-btn" data-role="user"    onclick="setFilter('user')">Dept User</button>
    <button class="role-btn" data-role="head"    onclick="setFilter('head')">Dept Head</button>
    <button class="role-btn" data-role="finance" onclick="setFilter('finance')">Finance</button>
  </div>
</div>

{{-- ── Content ───────────────────────────────────────────────────────────── --}}
<main class="content">
<div class="wrap">

{{-- ─── PHASE 1 ─────────────────────────────────────────────────────────── --}}
<section class="phase" id="phase1">
  <div class="phase-header">
    <div class="phase-num">1</div>
    <div class="phase-meta">
      <div class="phase-eyebrow" style="color:var(--admin-c)">One-time setup</div>
      <div class="phase-name">System Configuration</div>
      <div class="phase-desc">Done by the system administrator before any department can enter a budget. Complete this phase first — it rarely needs to be repeated.</div>
      <div class="phase-roles"><span class="step-badge badge-admin">BDU Admin</span></div>
    </div>
  </div>
  <div class="steps">

    <div class="step" data-roles="admin">
      <span class="step-badge badge-admin">Admin</span><span class="step-num">1</span>
      <div class="step-body">
        <div class="step-action">Log in with your administrator credentials</div>
        <div class="step-detail">Use the email and password set during system installation. If this is the first login, use the Super Admin account. You'll land on the Dashboard.</div>
      </div>
    </div>

    <div class="step" data-roles="admin">
      <span class="step-badge badge-admin">Admin</span><span class="step-num">2</span>
      <div class="step-body">
        <div class="step-action">Create Account Code Categories</div>
        <div class="step-detail">Go to <span class="ui">Settings → Categories</span>. Create the major groupings for your GL codes — e.g. <em>Operating Revenue</em>, <em>Staff Costs</em>, <em>Administrative Expenses</em>, <em>Capital Expenditure</em>. Assign each a <strong>budget type</strong> (Revenue or Expense) — this controls how the system calculates profit and flags over-budget entries.</div>
      </div>
    </div>

    <div class="step" data-roles="admin">
      <span class="step-badge badge-admin">Admin</span><span class="step-num">3</span>
      <div class="step-body">
        <div class="step-action">Create Account Codes (GL Codes)</div>
        <div class="step-detail">Go to <span class="ui">Settings → Account Codes</span>. Add each line item your organisation budgets for. Each code needs a number (e.g. <em>4001</em>), a name (e.g. <em>Fuel Sales Revenue</em>), and its category from Step 2. These become the rows in every budget form.</div>
      </div>
    </div>

    <div class="step" data-roles="admin">
      <span class="step-badge badge-admin">Admin</span><span class="step-num">4</span>
      <div class="step-body">
        <div class="step-action">Create Departments and Service Stations</div>
        <div class="step-detail">Go to <span class="ui">Settings → Departments</span>. Add every department and service station that will enter a budget, each with a short code (e.g. <em>HQ</em>, <em>ABA</em>). Subsidiaries are created under <span class="ui">Settings → Subsidiaries</span>.</div>
      </div>
    </div>

    <div class="step" data-roles="admin">
      <span class="step-badge badge-admin">Admin</span><span class="step-num">5</span>
      <div class="step-body">
        <div class="step-action">Map account codes to each department</div>
        <div class="step-detail">Go to <span class="ui">Settings → Department Mappings</span>. For each department, select which account codes apply. <strong>Only mapped codes appear in that department's budget form.</strong> A fuel station sees fuel-related codes; HR sees staff cost codes.</div>
      </div>
    </div>

    <div class="step" data-roles="admin">
      <span class="step-badge badge-admin">Admin</span><span class="step-num">6</span>
      <div class="step-body">
        <div class="step-action">Create users and assign roles</div>
        <div class="step-detail">Go to <span class="ui">Settings → Users → Add User</span>. Enter each person's name and email, then assign their role. Available roles: <strong>Department User</strong> (enters budget and actuals), <strong>Department Head</strong> (reviews and confirms), <strong>Finance Reviewer</strong> (gives final approval), <strong>BDU Admin</strong> (manages the system). Assign each department user to their department.</div>
      </div>
    </div>

    <div class="step" data-roles="admin">
      <span class="step-badge badge-admin">Admin</span><span class="step-num">7</span>
      <div class="step-body">
        <div class="step-action">Open a Budget Period</div>
        <div class="step-detail">Go to <span class="ui">Budget Periods → New Period</span>. Set the fiscal year name (e.g. <em>FY 2026</em>), start/end dates, and an entry deadline, then click <span class="ui">Open for Entry</span>. Departments cannot enter budgets until a period is open.</div>
      </div>
    </div>

    <div class="step" data-roles="admin">
      <span class="step-badge badge-admin">Admin</span><span class="step-num">8</span>
      <div class="step-body">
        <div class="step-action">Review System Settings</div>
        <div class="step-detail">Go to <span class="ui">System Settings</span>. Key settings:<br>
          <strong>Calculation Mode</strong> — Direct entry (type totals) or Qty × Rate (unit count × rate, system calculates).<br>
          <strong>Actuals Check Mode</strong> — Annual (YTD flexible) or Monthly (strict per-month cap).<br>
          <strong>Actuals Approval Flow</strong> — Multi-Stage recommended (Dept User → Head → Finance).
        </div>
      </div>
    </div>

    <div class="outcome">
      <span class="outcome-icon">✓</span>
      <div><strong>Phase complete:</strong> Users can log in, departments exist, account codes are mapped, and a budget period is open. Proceed to Phase 2.</div>
    </div>
  </div>
</section>

{{-- ─── PHASE 2 ─────────────────────────────────────────────────────────── --}}
<section class="phase" id="phase2">
  <div class="phase-header">
    <div class="phase-num">2</div>
    <div class="phase-meta">
      <div class="phase-eyebrow" style="color:var(--user-c)">Annual — once per fiscal year</div>
      <div class="phase-name">Budget Entry</div>
      <div class="phase-desc">Each department enters their planned figures for the year. Think of this as filling in your Excel template — but directly in the system, with totals calculated automatically.</div>
      <div class="phase-roles"><span class="step-badge badge-user">Dept User</span></div>
    </div>
  </div>
  <div class="steps">

    <div class="step" data-roles="user">
      <span class="step-badge badge-user">Dept User</span><span class="step-num">1</span>
      <div class="step-body">
        <div class="step-action">Log in and go to My Budget</div>
        <div class="step-detail">Click <span class="ui">My Budget</span> in the sidebar. Your department's budget form for the current period opens automatically. If you see "No budget found", the period may not be open yet — contact your admin.</div>
      </div>
    </div>

    <div class="step" data-roles="user">
      <span class="step-badge badge-user">Dept User</span><span class="step-num">2</span>
      <div class="step-body">
        <div class="step-action">Enter your planned figures for each line item</div>
        <div class="step-detail">Budget lines are grouped by category — Revenue first, then Expenses. Enter the amount per month (Jan–Dec) or a single annual total. In Qty × Rate mode, enter the unit count and rate; the total is computed for you. Tab between cells just like Excel.</div>
      </div>
    </div>

    <div class="step" data-roles="user">
      <span class="step-badge badge-user">Dept User</span><span class="step-num">3</span>
      <div class="step-body">
        <div class="step-action">Save as draft — the system autosaves too</div>
        <div class="step-detail">Click <span class="ui">Save Draft</span> at any time. A status indicator shows <em>"Saved"</em>. You can close the browser and return without losing your entries.</div>
      </div>
    </div>

    <div class="step" data-roles="user">
      <span class="step-badge badge-user">Dept User</span><span class="step-num">4</span>
      <div class="step-body">
        <div class="step-action">Review your totals before submitting</div>
        <div class="step-detail">Category subtotals and a grand total appear at the bottom. Revenue and Expense lines are shown separately. Check that all required lines have figures — empty lines show as zero in reports.</div>
      </div>
    </div>

    <div class="step" data-roles="user">
      <span class="step-badge badge-user">Dept User</span><span class="step-num">5</span>
      <div class="step-body">
        <div class="step-action">Submit for approval when ready</div>
        <div class="step-detail">Click <span class="ui">Submit for Approval</span>. Add an optional note explaining your assumptions. A dialog shows your total — click <span class="ui">Yes, Submit</span>. The budget is now locked for editing until reviewed.</div>
      </div>
    </div>

    <div class="callout callout-tip">
      <span class="callout-icon">💡</span>
      <div><strong>Tip</strong> If rejected, you receive a notification with the reason. Return to <span class="ui">My Budget</span>, make the changes, and resubmit. The cycle can repeat until approved.</div>
    </div>
    <div class="callout callout-warn">
      <span class="callout-icon">⚠</span>
      <div><strong>Deadline</strong> You cannot submit after the period deadline. If you miss it, ask Finance for a deadline extension. The deadline is shown at the top of your budget form.</div>
    </div>

    <div class="outcome">
      <span class="outcome-icon">✓</span>
      <div><strong>Phase complete:</strong> Budget status is <strong>Submitted</strong>. Your department head is notified. Proceed to Phase 3.</div>
    </div>
  </div>
</section>

{{-- ─── PHASE 3 ─────────────────────────────────────────────────────────── --}}
<section class="phase" id="phase3">
  <div class="phase-header">
    <div class="phase-num">3</div>
    <div class="phase-meta">
      <div class="phase-eyebrow" style="color:var(--head-c)">Annual — follows Phase 2</div>
      <div class="phase-name">Budget Approval</div>
      <div class="phase-desc">Submitted budgets pass through the approval chain — typically Department Head then Finance — before becoming the official baseline. Each stage can approve or reject with comments.</div>
      <div class="phase-roles">
        <span class="step-badge badge-head">Dept Head</span>
        <span class="step-badge badge-finance">Finance</span>
      </div>
    </div>
  </div>
  <div class="steps">

    <div class="step" data-roles="head">
      <span class="step-badge badge-head">Dept Head</span><span class="step-num">1</span>
      <div class="step-body">
        <div class="step-action">Open the Approvals queue</div>
        <div class="step-detail">Click <span class="ui">Approvals</span> in the sidebar. All budgets awaiting your review are listed by department and period. A notification email was also sent when the budget was submitted.</div>
      </div>
    </div>

    <div class="step" data-roles="head">
      <span class="step-badge badge-head">Dept Head</span><span class="step-num">2</span>
      <div class="step-body">
        <div class="step-action">Review the budget in detail</div>
        <div class="step-detail">Click a department name to open its budget. All line items are shown with monthly breakdowns and annual totals. Switch to the P&amp;L view for an income-statement format. Compare against prior year if needed.</div>
      </div>
    </div>

    <div class="step" data-roles="head">
      <span class="step-badge badge-head">Dept Head</span><span class="step-num">3</span>
      <div class="step-body">
        <div class="step-action">Approve or Reject</div>
        <div class="step-detail">Scroll to the bottom. Click <span class="ui">Approve</span> to forward to Finance, or <span class="ui">Reject</span> (requires a reason). On rejection, the department user is notified and can revise and resubmit.</div>
      </div>
    </div>

    <div class="step" data-roles="finance">
      <span class="step-badge badge-finance">Finance</span><span class="step-num">4</span>
      <div class="step-body">
        <div class="step-action">Review all head-approved budgets in the Approvals queue</div>
        <div class="step-detail">Click <span class="ui">Approvals</span>. Budgets already approved by the department head appear here. Open each budget, review the figures and notes, and check for consistency across departments.</div>
      </div>
    </div>

    <div class="step" data-roles="finance">
      <span class="step-badge badge-finance">Finance</span><span class="step-num">5</span>
      <div class="step-body">
        <div class="step-action">Give final approval</div>
        <div class="step-detail">Click <span class="ui">Approve</span> at the bottom. If further stages are configured (GCEO, Board), the budget passes up the chain automatically. Final approval locks the budget as the <strong>Official Approved Baseline</strong>.</div>
      </div>
    </div>

    <div class="callout callout-note">
      <span class="callout-icon">ℹ</span>
      <div><strong>Segregation of duties</strong> The person who submitted the budget cannot approve it. This is enforced automatically — if you submitted a budget, it will not appear in your own approval queue.</div>
    </div>

    <div class="outcome">
      <span class="outcome-icon">✓</span>
      <div><strong>Phase complete:</strong> Budget status is <strong>Approved</strong>. This is the fixed baseline for all variance and actuals reports. The department is notified. Actuals entry (Phase 4) can now begin each month.</div>
    </div>
  </div>
</section>

{{-- ─── PHASE 4 ─────────────────────────────────────────────────────────── --}}
<section class="phase" id="phase4">
  <div class="phase-header">
    <div class="phase-num">4</div>
    <div class="phase-meta">
      <div class="phase-eyebrow" style="color:var(--user-c)">Monthly — repeat each month</div>
      <div class="phase-name">Monthly Actuals Entry</div>
      <div class="phase-desc">Each month, departments record what was actually spent against each budget line. This replaces the manual process of emailing Excel files to Finance.</div>
      <div class="phase-roles"><span class="step-badge badge-user">Dept User</span></div>
    </div>
  </div>
  <div class="steps">

    <div class="step" data-roles="user">
      <span class="step-badge badge-user">Dept User</span><span class="step-num">1</span>
      <div class="step-body">
        <div class="step-action">Go to Actuals and select the month</div>
        <div class="step-detail">Click <span class="ui">Actuals</span> in the sidebar. Your department is selected automatically. The monthly grid shows all 12 months — click the card for the month you're recording. Months with data already recorded show a status badge.</div>
      </div>
    </div>

    <div class="step" data-roles="user">
      <span class="step-badge badge-user">Dept User</span><span class="step-num">2</span>
      <div class="step-body">
        <div class="step-action">Enter the actual amount for each line item</div>
        <div class="step-detail">The entry form shows each budgeted line with its approved budget and YTD total. Enter the <strong>actual amount spent this month</strong> in the Amount column. Add a <strong>reference number</strong> (invoice or voucher) — required for the Finance audit trail. An optional Description column is available for notes.</div>
      </div>
    </div>

    <div class="step" data-roles="user">
      <span class="step-badge badge-user">Dept User</span><span class="step-num">3</span>
      <div class="step-body">
        <div class="step-action">Watch for over-budget warnings</div>
        <div class="step-detail">If an expense line exceeds its budget allocation, a red badge appears. This is advisory — it does not block saving. However, you cannot submit if severely over-budget. In that case, request a supplementary budget from Finance first.</div>
      </div>
    </div>

    <div class="step" data-roles="user">
      <span class="step-badge badge-user">Dept User</span><span class="step-num">4</span>
      <div class="step-body">
        <div class="step-action">Save as Draft at any time</div>
        <div class="step-detail">Click <span class="ui">Save as Draft</span> to preserve your work. The system also autosaves as you type. Close the browser and return later — entries are retained. The month card shows <em>Draft</em> status.</div>
      </div>
    </div>

    <div class="step" data-roles="user">
      <span class="step-badge badge-user">Dept User</span><span class="step-num">5</span>
      <div class="step-body">
        <div class="step-action">Submit for approval when all entries are complete</div>
        <div class="step-detail">Click <span class="ui">Save &amp; Submit for Approval</span>. All current figures are saved and the month is locked for editing. A dialog shows the total — click <span class="ui">Yes, Save &amp; Submit</span>. Your department head is notified.</div>
      </div>
    </div>

    <div class="callout callout-tip">
      <span class="callout-icon">💡</span>
      <div><strong>Revenue lines</strong> are never blocked for over-budget — revenue exceeding its forecast is positive. Only expense lines trigger warnings.</div>
    </div>
    <div class="callout callout-warn">
      <span class="callout-icon">⚠</span>
      <div><strong>Once submitted</strong> you cannot edit until your head or Finance acts. If you need to correct an entry, ask Finance to reopen the month.</div>
    </div>

    <div class="outcome">
      <span class="outcome-icon">✓</span>
      <div><strong>Phase complete:</strong> Month status is <strong>Submitted</strong>. Your department head sees it in the pending approvals queue. Proceed to Phase 5.</div>
    </div>
  </div>
</section>

{{-- ─── PHASE 5 ─────────────────────────────────────────────────────────── --}}
<section class="phase" id="phase5">
  <div class="phase-header">
    <div class="phase-num">5</div>
    <div class="phase-meta">
      <div class="phase-eyebrow" style="color:var(--fin-c)">Monthly — follows Phase 4</div>
      <div class="phase-name">Actuals Approval</div>
      <div class="phase-desc">Submitted actuals pass from the department head to Finance for final sign-off. Once Finance approves, the month is locked and the figures appear in all reports.</div>
      <div class="phase-roles">
        <span class="step-badge badge-head">Dept Head</span>
        <span class="step-badge badge-finance">Finance</span>
      </div>
    </div>
  </div>
  <div class="steps">

    <div class="step" data-roles="head">
      <span class="step-badge badge-head">Dept Head</span><span class="step-num">1</span>
      <div class="step-body">
        <div class="step-action">Check the pending approvals card on the Actuals page</div>
        <div class="step-detail">Click <span class="ui">Actuals</span>. A purple <strong>"Awaiting Your Confirmation"</strong> card appears at the top, listing all submitted months from your department with their totals and entry counts.</div>
      </div>
    </div>

    <div class="step" data-roles="head">
      <span class="step-badge badge-head">Dept Head</span><span class="step-num">2</span>
      <div class="step-body">
        <div class="step-action">Click "Review &amp; Confirm" to open the month</div>
        <div class="step-detail">The entry form opens in read-only mode showing all amounts, reference numbers, and descriptions. The budget column lets you compare actual vs approved budget for each line.</div>
      </div>
    </div>

    <div class="step" data-roles="head">
      <span class="step-badge badge-head">Dept Head</span><span class="step-num">3</span>
      <div class="step-body">
        <div class="step-action">Confirm or request corrections</div>
        <div class="step-detail">If entries are correct, click <span class="ui">Confirm (Head Approval)</span> and confirm in the dialog. If something is wrong, ask Finance to reopen the month so the team can correct and resubmit. <em>Note: you cannot confirm a month if you entered any of the figures yourself (segregation of duties).</em></div>
      </div>
    </div>

    <div class="step" data-roles="finance">
      <span class="step-badge badge-finance">Finance</span><span class="step-num">4</span>
      <div class="step-body">
        <div class="step-action">Check the pending approvals card on the Actuals page</div>
        <div class="step-detail">Click <span class="ui">Actuals</span>. A green <strong>"Awaiting Your Final Approval"</strong> card lists every head-confirmed month across all departments — your consolidated queue for the whole organisation.</div>
      </div>
    </div>

    <div class="step" data-roles="finance">
      <span class="step-badge badge-finance">Finance</span><span class="step-num">5</span>
      <div class="step-body">
        <div class="step-action">Review and give final approval</div>
        <div class="step-detail">Click <span class="ui">Review &amp; Final Approve →</span>. Review all line items and references. When satisfied, click <span class="ui">Final Approve</span> and confirm. The month is now <strong>Confirmed</strong> and permanently locked.</div>
      </div>
    </div>

    <div class="step" data-roles="finance">
      <span class="step-badge badge-finance">Finance</span><span class="step-num">6</span>
      <div class="step-body">
        <div class="step-action">Reopen a month if corrections are needed</div>
        <div class="step-detail">On any locked month, click <span class="ui">Reopen Month</span> and confirm. All entries return to Draft status. The department user can then edit and resubmit from the beginning of Phase 4.</div>
      </div>
    </div>

    <div class="callout callout-note">
      <span class="callout-icon">ℹ</span>
      <div><strong>Reports update immediately.</strong> Once Finance gives final approval, the confirmed figures appear in the Variance Report, YTD totals, and all other reports in real time — no manual export or upload needed.</div>
    </div>

    <div class="outcome">
      <span class="outcome-icon">✓</span>
      <div><strong>Phase complete:</strong> Month status is <strong>Confirmed</strong>. These are the official actuals. Repeat Phases 4–5 each month. Proceed to Phase 6 for analysis and reporting.</div>
    </div>
  </div>
</section>

{{-- ─── PHASE 6 ─────────────────────────────────────────────────────────── --}}
<section class="phase" id="phase6">
  <div class="phase-header">
    <div class="phase-num">6</div>
    <div class="phase-meta">
      <div class="phase-eyebrow" style="color:#059669">Ongoing — any time</div>
      <div class="phase-name">Reports &amp; Analysis</div>
      <div class="phase-desc">All reports update automatically as budgets are approved and actuals are confirmed. No manual data preparation needed.</div>
      <div class="phase-roles">
        <span class="step-badge badge-finance">Finance</span>
        <span class="step-badge" style="background:#D1FAE5;color:#065F46">Management</span>
      </div>
    </div>
  </div>
  <div class="steps">

    <div class="step" data-roles="finance admin head user">
      <span class="step-badge badge-all">All</span><span class="step-num">1</span>
      <div class="step-body">
        <div class="step-action">Go to Reports in the sidebar</div>
        <div class="step-detail">Click <span class="ui">Reports</span>. A submenu lists all available reports. Finance sees all departments; department users see their own department only.</div>
      </div>
    </div>

    <div class="step" data-roles="finance admin">
      <span class="step-badge badge-finance">Finance</span><span class="step-num">2</span>
      <div class="step-body">
        <div class="step-action">Variance Report — the primary management tool</div>
        <div class="step-detail">Go to <span class="ui">Reports → Variance</span>. Shows every department's approved budget vs confirmed actuals, with variance and percentage. This replaces the manual consolidated Excel Finance used to compile.</div>
      </div>
    </div>

    <div class="step" data-roles="finance admin">
      <span class="step-badge badge-finance">Finance</span><span class="step-num">3</span>
      <div class="step-body">
        <div class="step-action">Department Report — drill into one department</div>
        <div class="step-detail">Go to <span class="ui">Reports → Department</span>. Select any department from the dropdown. See the full budget by line item with monthly actuals, YTD total, and remaining balance. Useful for monthly review meetings.</div>
      </div>
    </div>

    <div class="step" data-roles="finance admin">
      <span class="step-badge badge-finance">Finance</span><span class="step-num">4</span>
      <div class="step-body">
        <div class="step-action">Year-on-Year Report — trend analysis</div>
        <div class="step-detail">Go to <span class="ui">Reports → Year-on-Year</span>. Compares the current year's budget and actuals against the prior year. Shows growth and decline by line item. Useful for board presentations and budget justification.</div>
      </div>
    </div>

    <div class="step" data-roles="finance admin">
      <span class="step-badge badge-finance">Finance</span><span class="step-num">5</span>
      <div class="step-body">
        <div class="step-action">Financial Statement Report</div>
        <div class="step-detail">Go to <span class="ui">Reports → Financial</span>. A full income statement (P&amp;L) format showing revenue, gross profit, operating expenses, and net profit — both budget and actual. Can be filtered by entity or subsidiary.</div>
      </div>
    </div>

    <div class="step" data-roles="finance admin">
      <span class="step-badge badge-finance">Finance</span><span class="step-num">6</span>
      <div class="step-body">
        <div class="step-action">Export to CSV or print to PDF</div>
        <div class="step-detail">Every report has a <span class="ui">Download CSV</span> button — click it to export for further analysis in Excel. For PDF, use your browser's Print function (Ctrl+P) and select <em>Save as PDF</em>.</div>
      </div>
    </div>

    <div class="callout callout-tip">
      <span class="callout-icon">💡</span>
      <div><strong>Filter by entity</strong> All reports support filtering by Department, Service Station, or Subsidiary using the dropdown at the top.</div>
    </div>
  </div>
</section>

{{-- ─── QUICK REFERENCE ─────────────────────────────────────────────────── --}}
<div class="section-divider"></div>

<section class="phase" id="qr">
  <div class="phase-header">
    <div class="phase-num" style="color:var(--gold);opacity:.7">✦</div>
    <div class="phase-meta">
      <div class="phase-name">Quick Reference — Actions by Role</div>
      <div class="phase-desc">Every button, in the order you'll click it.</div>
    </div>
  </div>
  <div class="qr-wrap">
  <table class="qr-table">
    <thead>
      <tr>
        <th>Role</th>
        <th>Where to go</th>
        <th>Action</th>
        <th>What happens</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><span class="step-badge badge-user">Dept User</span></td>
        <td><span class="ui">My Budget</span></td>
        <td>Enter figures, <span class="ui">Save Draft</span></td>
        <td>Work saved, budget stays Draft</td>
      </tr>
      <tr>
        <td><span class="step-badge badge-user">Dept User</span></td>
        <td><span class="ui">My Budget</span></td>
        <td><span class="ui">Submit for Approval</span></td>
        <td>Budget locked, head notified</td>
      </tr>
      <tr>
        <td><span class="step-badge badge-head">Dept Head</span></td>
        <td><span class="ui">Approvals</span></td>
        <td><span class="ui">Approve</span> or <span class="ui">Reject</span></td>
        <td>Forwards to Finance, or returns to dept</td>
      </tr>
      <tr>
        <td><span class="step-badge badge-finance">Finance</span></td>
        <td><span class="ui">Approvals</span></td>
        <td><span class="ui">Approve</span></td>
        <td>Budget becomes Official Baseline</td>
      </tr>
      <tr>
        <td style="border-top:2px solid var(--border)"><span class="step-badge badge-user">Dept User</span></td>
        <td><span class="ui">Actuals → Month</span></td>
        <td>Enter amounts + refs, <span class="ui">Save as Draft</span></td>
        <td>Entries saved, editable</td>
      </tr>
      <tr>
        <td><span class="step-badge badge-user">Dept User</span></td>
        <td><span class="ui">Actuals → Month</span></td>
        <td><span class="ui">Save &amp; Submit for Approval</span></td>
        <td>Month locked Submitted, head notified</td>
      </tr>
      <tr>
        <td><span class="step-badge badge-head">Dept Head</span></td>
        <td><span class="ui">Actuals</span> → pending card</td>
        <td><span class="ui">Review &amp; Confirm →</span></td>
        <td>Opens month for review</td>
      </tr>
      <tr>
        <td><span class="step-badge badge-head">Dept Head</span></td>
        <td>Month entry page</td>
        <td><span class="ui">Confirm (Head Approval)</span></td>
        <td>Month → Head Confirmed, Finance notified</td>
      </tr>
      <tr>
        <td><span class="step-badge badge-finance">Finance</span></td>
        <td><span class="ui">Actuals</span> → pending card</td>
        <td><span class="ui">Review &amp; Final Approve →</span></td>
        <td>Opens month for final review</td>
      </tr>
      <tr>
        <td><span class="step-badge badge-finance">Finance</span></td>
        <td>Month entry page</td>
        <td><span class="ui">Final Approve</span></td>
        <td>Month locked Confirmed, appears in reports</td>
      </tr>
      <tr>
        <td><span class="step-badge badge-finance">Finance</span></td>
        <td>Month entry page</td>
        <td><span class="ui">Reopen Month</span></td>
        <td>Month → Draft, dept can correct &amp; resubmit</td>
      </tr>
      <tr>
        <td style="border-top:2px solid var(--border)"><span class="step-badge badge-finance">Finance</span></td>
        <td><span class="ui">Reports</span></td>
        <td>Select report, choose filters</td>
        <td>Live data, no preparation needed</td>
      </tr>
      <tr>
        <td><span class="step-badge badge-finance">Finance</span></td>
        <td>Any report</td>
        <td><span class="ui">Download CSV</span></td>
        <td>Exports to Excel-compatible file</td>
      </tr>
    </tbody>
  </table>
  </div>
</section>

</div>{{-- .wrap --}}
</main>

<footer class="doc-footer">
  GOIL Budget System &nbsp;·&nbsp; Process Guide &nbsp;·&nbsp; For internal use
</footer>

<script>
(function () {
  let current = '';
  window.setFilter = function (role) {
    const body = document.body;
    const btns = document.querySelectorAll('.role-btn');
    if (role === 'all' || role === current) {
      current = '';
      delete body.dataset.filter;
      btns.forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.step').forEach(s => s.classList.remove('matched'));
      return;
    }
    current = role;
    body.dataset.filter = role;
    btns.forEach(b => b.classList.toggle('active', b.dataset.role === role));
    document.querySelectorAll('.step').forEach(step => {
      const roles = (step.dataset.roles || '').split(' ');
      step.classList.toggle('matched', roles.includes(role));
    });
  };
})();
</script>

</body>
</html>
