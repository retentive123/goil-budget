@extends('layouts.app')

@section('title', 'Deployment Guide')

@push('styles')
<style>
  /* ── Reuse docs palette & layout ── */
  .docs-wrap { --d-navy: #1B2A4A; --d-gold: #C9A84C; }
  .docs-wrap { display: flex; gap: 0; min-height: 100%; align-items: flex-start; }

  .docs-nav {
    width: 210px; flex-shrink: 0;
    position: sticky; top: 0;
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
    font-size: 10px; font-weight: 700; letter-spacing: 1px;
    text-transform: uppercase; color: #94A3B8;
    padding: 14px 12px 5px; display: block;
  }
  .docs-nav-link {
    display: block; padding: 6px 12px; font-size: 13px;
    color: #64748B; text-decoration: none;
    border-left: 2px solid transparent;
    border-radius: 0 4px 4px 0;
    transition: color .1s, border-color .1s, background .1s;
    line-height: 1.4;
  }
  .docs-nav-link:hover { color: #1B2A4A; background: #F1F5F9; }
  .docs-nav-link.active { color: #1B2A4A; border-left-color: #C9A84C; background: #FEF9EC; font-weight: 600; }

  .docs-content { flex: 1; min-width: 0; padding: 0 0 80px 40px; max-width: 820px; }

  .doc-section { padding-top: 52px; scroll-margin-top: 20px; }
  .doc-eyebrow { font-size: 10.5px; font-weight: 700; letter-spacing: 1.1px; text-transform: uppercase; color: #C9A84C; margin-bottom: 5px; }
  .doc-title { font-size: 24px; font-weight: 700; color: #1B2A4A; line-height: 1.2; margin-bottom: 10px; }
  .doc-lead { font-size: 14.5px; color: #64748B; max-width: 66ch; margin-bottom: 24px; line-height: 1.7; }
  .doc-sub { padding-top: 28px; margin-bottom: 4px; }
  .doc-sub-title { font-size: 16px; font-weight: 700; color: #1B2A4A; margin-bottom: 10px; padding-bottom: 7px; border-bottom: 1px solid #E2E8F0; }
  .docs-divider { border: none; border-top: 1px solid #E2E8F0; margin: 36px 0; }

  .docs-content p { margin-bottom: 12px; max-width: 70ch; }
  .docs-content p:last-child { margin-bottom: 0; }
  .docs-content ul, .docs-content ol { padding-left: 20px; margin-bottom: 12px; }
  .docs-content li { margin-bottom: 4px; max-width: 68ch; }

  .doc-table-wrap { overflow-x: auto; margin: 14px 0 20px; border-radius: 8px; border: 1px solid #E2E8F0; }
  .doc-table { width: 100%; border-collapse: collapse; font-size: 13.5px; font-variant-numeric: tabular-nums; }
  .doc-table thead tr { background: #F8FAFC; }
  .doc-table th { text-align: left; padding: 9px 13px; font-size: 10.5px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase; color: #64748B; border-bottom: 1px solid #E2E8F0; white-space: nowrap; }
  .doc-table td { padding: 8px 13px; border-bottom: 1px solid #F1F5F9; vertical-align: top; line-height: 1.5; }
  .doc-table tr:last-child td { border-bottom: none; }
  .doc-table tbody tr:hover td { background: #FAFBFD; }

  .doc-note    { background: #EFF6FF; border-left: 3px solid #3B82F6; padding: 11px 14px; border-radius: 0 6px 6px 0; font-size: 13.5px; color: #1E40AF; margin: 14px 0; max-width: 70ch; }
  .doc-warning { background: #FFFBEB; border-left: 3px solid #F59E0B; padding: 11px 14px; border-radius: 0 6px 6px 0; font-size: 13.5px; color: #92400E; margin: 14px 0; max-width: 70ch; }
  .doc-tip     { background: #F0FDF4; border-left: 3px solid #10B981; padding: 11px 14px; border-radius: 0 6px 6px 0; font-size: 13.5px; color: #065F46; margin: 14px 0; max-width: 70ch; }
  .doc-danger  { background: #FEE2E2; border-left: 3px solid #EF4444; padding: 11px 14px; border-radius: 0 6px 6px 0; font-size: 13.5px; color: #991B1B; margin: 14px 0; max-width: 70ch; }

  .sk { font-family: 'Courier New', Courier, monospace; font-size: 12px; background: #EEF2F7; color: #1B2A4A; padding: 1px 5px; border-radius: 3px; white-space: nowrap; }
  .db { display: inline-block; font-size: 10.5px; font-weight: 700; padding: 2px 8px; border-radius: 20px; white-space: nowrap; }
  .db-navy   { background: #1B2A4A; color: #fff; }
  .db-gold   { background: #FEF3C7; color: #92400E; }
  .db-green  { background: #D1FAE5; color: #065F46; }
  .db-rose   { background: #FEE2E2; color: #991B1B; }
  .db-slate  { background: #F1F5F9; color: #475569; }
  .db-blue   { background: #DBEAFE; color: #1E40AF; }

  /* ── Code blocks ── */
  .doc-code {
    background: #0F1C35;
    border-radius: 8px;
    overflow: hidden;
    margin: 12px 0 16px;
    border: 1px solid rgba(255,255,255,0.06);
  }
  .doc-code-bar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 7px 14px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    background: rgba(255,255,255,0.03);
  }
  .doc-code-lang {
    font-family: 'Courier New', Courier, monospace;
    font-size: 10px; font-weight: 700; letter-spacing: 0.8px;
    text-transform: uppercase; color: rgba(255,255,255,0.3);
  }
  .doc-copy-btn {
    background: none; border: 1px solid rgba(255,255,255,0.15);
    color: rgba(255,255,255,0.5); font-size: 10px; font-weight: 600;
    padding: 2px 8px; border-radius: 4px; cursor: pointer;
    font-family: inherit; transition: all 0.15s;
  }
  .doc-copy-btn:hover { border-color: rgba(255,255,255,0.4); color: rgba(255,255,255,0.9); }
  .doc-copy-btn.copied { border-color: #10B981; color: #10B981; }
  .doc-code pre {
    padding: 14px 16px; overflow-x: auto;
    font-family: 'Courier New', Courier, monospace;
    font-size: 12.5px; line-height: 1.7;
    color: #CBD5E1; background: transparent; margin: 0;
    white-space: pre;
  }

  /* ── Step list ── */
  .step-list { margin: 16px 0; }
  .step-item { display: flex; gap: 16px; margin-bottom: 20px; position: relative; }
  .step-item::before {
    content: ''; position: absolute;
    left: 15px; top: 34px; bottom: -20px;
    width: 2px; background: #E2E8F0;
  }
  .step-item:last-child::before { display: none; }
  .step-dot {
    width: 32px; height: 32px; border-radius: 50%;
    background: #1B2A4A; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700; flex-shrink: 0;
    position: relative; z-index: 1;
  }
  .step-body { flex: 1; padding-top: 4px; }
  .step-body h4 { font-size: 14px; font-weight: 700; color: #1B2A4A; margin-bottom: 5px; }
  .step-body p  { font-size: 13px; color: #64748B; line-height: 1.6; margin-bottom: 6px; }

  /* ── Req grid ── */
  .req-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 10px; margin: 14px 0; }
  .req-card { background: #fff; border: 1px solid #E2E8F0; border-radius: 9px; padding: 12px 14px; position: relative; }
  .req-card-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #94A3B8; margin-bottom: 3px; }
  .req-card-val { font-family: 'Courier New', Courier, monospace; font-size: 15px; font-weight: 700; color: #1B2A4A; }
  .req-card-sub { font-size: 11px; color: #64748B; margin-top: 2px; }
  .req-card-badge { position: absolute; top: 9px; right: 9px; font-size: 9px; font-weight: 700; padding: 2px 6px; border-radius: 4px; }
  .req-must { background: #FEE2E2; color: #991B1B; }
  .req-rec  { background: #D1FAE5; color: #065F46; }

  /* ── Compare cards ── */
  .compare-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin: 14px 0; }
  .compare-card { border-radius: 10px; border: 2px solid #E2E8F0; overflow: hidden; }
  .compare-card.recommended { border-color: #10B981; }
  .compare-head { padding: 13px 16px; background: #F8FAFC; display: flex; align-items: center; justify-content: space-between; }
  .compare-head h3 { font-size: 14px; font-weight: 700; color: #1B2A4A; }
  .compare-body { padding: 14px 16px; }
  .pro-con li { display: flex; align-items: flex-start; gap: 6px; font-size: 12.5px; color: #64748B; margin-bottom: 5px; }
  .pro-con .ic { font-size: 13px; flex-shrink: 0; margin-top: 1px; }

  /* ── Checklist ── */
  .doc-checklist { list-style: none; padding: 0; }
  .doc-checklist li { display: flex; align-items: flex-start; gap: 10px; padding: 8px 0; border-bottom: 1px solid #F1F5F9; font-size: 13.5px; }
  .doc-checklist li:last-child { border-bottom: none; }
  .doc-chk {
    width: 20px; height: 20px; border-radius: 5px;
    background: #F8FAFC; border: 1.5px solid #E2E8F0;
    flex-shrink: 0; margin-top: 2px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all 0.15s;
    color: transparent; font-size: 11px; font-weight: 700;
    user-select: none;
  }
  .doc-chk.chk-done { background: #10B981; border-color: #10B981; color: #fff; }
  .doc-checklist li.row-done span { color: #94A3B8; text-decoration: line-through; }

  /* ── Hero ── */
  .docs-hero { background: #1B2A4A; border-radius: 10px; padding: 28px 32px; margin-bottom: 8px; color: #fff; }
  .docs-hero h1 { font-size: 22px; font-weight: 700; line-height: 1.2; margin-bottom: 8px; color: #fff; }
  .docs-hero h1 span { color: #E8C56A; }
  .docs-hero p { color: rgba(255,255,255,.65); max-width: 58ch; margin-bottom: 16px; font-size: 14px; line-height: 1.65; }
  .docs-hero-meta { display: flex; gap: 20px; flex-wrap: wrap; }
  .docs-hero-meta-item { font-size: 11.5px; color: rgba(255,255,255,.45); }
  .docs-hero-meta-item strong { color: rgba(255,255,255,.8); font-weight: 600; display: block; font-size: 12.5px; }

  /* ── Timeline (update procedure) ── */
  .doc-timeline { position: relative; padding-left: 24px; margin: 16px 0; }
  .doc-timeline::before {
    content: ''; position: absolute; left: 7px; top: 8px; bottom: 8px;
    width: 2px; background: linear-gradient(to bottom, #E65C00, #1B2A4A); border-radius: 2px;
  }
  .doc-timeline-item { position: relative; margin-bottom: 20px; }
  .doc-timeline-dot { position: absolute; left: -24px; top: 3px; width: 16px; height: 16px; border-radius: 50%; background: #fff; border: 2.5px solid #E65C00; }
  .doc-timeline-title { font-size: 13px; font-weight: 700; color: #1B2A4A; margin-bottom: 3px; }
  .doc-timeline-body { font-size: 13px; color: #64748B; line-height: 1.6; }

  .back-link {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 13px; color: #64748B; text-decoration: none; margin-bottom: 20px;
    transition: color .1s;
  }
  .back-link:hover { color: #1B2A4A; }

  @media (max-width: 900px) {
    .docs-nav { display: none; }
    .docs-content { padding-left: 0; }
    .compare-grid { grid-template-columns: 1fr; }
    .req-grid { grid-template-columns: repeat(2, 1fr); }
  }

  @media print {
    .docs-nav { display: none; }
    .docs-content { padding-left: 0; max-width: 100%; }
    .doc-copy-btn { display: none; }
  }
</style>
@endpush

@section('content')
<div class="docs-wrap">

  {{-- ── Sidebar nav ── --}}
  <aside class="docs-nav" id="docsNav">
    <div class="docs-nav-group">
      <span class="docs-nav-label">On this page</span>
      <a class="docs-nav-link" href="#intro">Overview</a>
      <a class="docs-nav-link" href="#package">Package Contents</a>
      <a class="docs-nav-link" href="#methods">Delivery Methods</a>
    </div>
    <div class="docs-nav-group">
      <span class="docs-nav-label">Installation</span>
      <a class="docs-nav-link" href="#requirements">1 — Server Requirements</a>
      <a class="docs-nav-link" href="#server">2 — Prepare the Server</a>
      <a class="docs-nav-link" href="#deploy">3 — Deploy the Files</a>
      <a class="docs-nav-link" href="#configure">4 — Configure Environment</a>
      <a class="docs-nav-link" href="#install">5 — Run the Installer</a>
      <a class="docs-nav-link" href="#iis">6 — Configure IIS</a>
      <a class="docs-nav-link" href="#scheduler">7 — Background Tasks</a>
    </div>
    <div class="docs-nav-group">
      <span class="docs-nav-label">Operations</span>
      <a class="docs-nav-link" href="#first-login">First Login</a>
      <a class="docs-nav-link" href="#updates">Applying Updates</a>
      <a class="docs-nav-link" href="#trouble">Troubleshooting</a>
    </div>
    <div class="docs-nav-group">
      <span class="docs-nav-label">Other docs</span>
      <a class="docs-nav-link" href="{{ route('docs.index') }}">← System Documentation</a>
      <a class="docs-nav-link" href="{{ route('docs.process-guide') }}">Process Guide</a>
      <a class="docs-nav-link" href="{{ route('docs.sage-integration') }}">Sage Integration</a>
    </div>
  </aside>

  {{-- ── Content ── --}}
  <div class="docs-content">

    <a href="{{ route('docs.index') }}" class="back-link">
      <i class="fas fa-arrow-left" style="font-size:11px"></i> System Documentation
    </a>

    {{-- ── Intro ── --}}
    <div class="doc-section" id="intro">
      <div class="docs-hero">
        <h1>GOIL Budget System<br><span>Deployment Guide</span></h1>
        <p>Everything GOIL's IT team needs to install, configure, and maintain the Budget System on their own infrastructure — from unpacking the zip to first login.</p>
        <div class="docs-hero-meta">
          <div class="docs-hero-meta-item"><strong>Stack</strong>Laravel / PHP 8.2</div>
          <div class="docs-hero-meta-item"><strong>Database</strong>MySQL 8.0</div>
          <div class="docs-hero-meta-item"><strong>Web server</strong>Windows Server + IIS 10</div>
          <div class="docs-hero-meta-item"><strong>Audience</strong>GOIL IT administrators</div>
        </div>
      </div>
    </div>

    <hr class="docs-divider">

    {{-- ── Package contents ── --}}
    <div class="doc-section" id="package">
      <div class="doc-eyebrow">Overview</div>
      <div class="doc-title">Package Contents</div>
      <div class="doc-lead">A single versioned zip file is all that changes hands. Here is exactly what it contains.</div>

      <div class="doc-code">
        <div class="doc-code-bar">
          <span class="doc-code-lang">Package layout</span>
        </div>
        <pre>goil-budget-vYYYY.MM.DD.zip
├── app/            ← Application PHP code
├── bootstrap/      ← Laravel bootstrap files
├── config/         ← Application configuration
├── database/       ← Migrations and seeders
├── public/         ← Web root — point IIS here
│   ├── build/      ← Pre-compiled JS/CSS assets
│   └── web.config  ← IIS URL rewrite rules
├── resources/      ← Blade views, JS/CSS source
├── routes/         ← Route definitions
├── storage/        ← Logs, cache (empty on install)
├── vendor/         ← PHP dependencies (pre-installed)
├── install.ps1     ★ Run this first — automated setup
├── env.production.txt  ★ Rename to .env and fill in credentials
├── INSTALL.md      ← Plain-text version of this guide
└── composer.json   ← Dependency manifest</pre>
      </div>

      <div class="doc-note">No <strong>.env</strong> file is included — <code>env.production.txt</code> is a template. You fill in database credentials, mail server, and domain before installing. The actual <code>.env</code> with real passwords must never be shared.</div>
    </div>

    <hr class="docs-divider">

    {{-- ── Delivery methods ── --}}
    <div class="doc-section" id="methods">
      <div class="doc-eyebrow">Overview</div>
      <div class="doc-title">Delivery Methods</div>
      <div class="doc-lead">Two ways to get the software onto the server. The ZIP approach is simpler and works without any internet access on the server.</div>

      <div class="compare-grid">
        <div class="compare-card recommended">
          <div class="compare-head">
            <h3>📦 ZIP Archive</h3>
            <span class="db db-green">Recommended</span>
          </div>
          <div class="compare-body">
            <p style="font-size:13px;color:#64748B;margin-bottom:10px">Hand over a versioned zip (USB, SharePoint, secure email). GOIL IT unzips and runs the installer.</p>
            <ul class="pro-con" style="list-style:none;padding:0">
              <li><span class="ic">✅</span> No internet needed on server</li>
              <li><span class="ic">✅</span> Simple — one file, one command</li>
              <li><span class="ic">✅</span> Works behind any corporate firewall</li>
              <li><span class="ic">⚠️</span> Updates require a new zip</li>
            </ul>
          </div>
        </div>
        <div class="compare-card">
          <div class="compare-head">
            <h3>🔀 Private Git Repo</h3>
            <span class="db db-slate">Advanced</span>
          </div>
          <div class="compare-body">
            <p style="font-size:13px;color:#64748B;margin-bottom:10px">GOIL clones from a private GitHub/GitLab repo and pulls updates.</p>
            <ul class="pro-con" style="list-style:none;padding:0">
              <li><span class="ic">✅</span> Updates are just <code class="sk">git pull</code></li>
              <li><span class="ic">✅</span> Full change history visible</li>
              <li><span class="ic">⚠️</span> Server needs internet access to GitHub</li>
              <li><span class="ic">❌</span> Never push <code class="sk">.env</code> or secrets</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <hr class="docs-divider">

    {{-- ══ STEP 1 — Requirements ══ --}}
    <div class="doc-section" id="requirements">
      <div class="doc-eyebrow">Step 1</div>
      <div class="doc-title">Server Requirements</div>
      <div class="doc-lead">Confirm all of these before starting the installation.</div>

      <div class="req-grid">
        <div class="req-card">
          <span class="req-card-badge req-must">Required</span>
          <div class="req-card-label">PHP</div>
          <div class="req-card-val">8.2+</div>
          <div class="req-card-sub">Thread-safe (TS) for IIS/FastCGI</div>
        </div>
        <div class="req-card">
          <span class="req-card-badge req-must">Required</span>
          <div class="req-card-label">MySQL</div>
          <div class="req-card-val">8.0+</div>
          <div class="req-card-sub">MariaDB 10.6+ also works</div>
        </div>
        <div class="req-card">
          <span class="req-card-badge req-must">Required</span>
          <div class="req-card-label">IIS</div>
          <div class="req-card-val">10.0</div>
          <div class="req-card-sub">+ URL Rewrite 2.x module</div>
        </div>
        <div class="req-card">
          <span class="req-card-badge req-must">Required</span>
          <div class="req-card-label">Composer</div>
          <div class="req-card-val">2.x</div>
          <div class="req-card-sub">Must be in system PATH</div>
        </div>
        <div class="req-card">
          <span class="req-card-badge req-must">Required</span>
          <div class="req-card-label">RAM</div>
          <div class="req-card-val">4 GB</div>
          <div class="req-card-sub">8 GB recommended</div>
        </div>
        <div class="req-card">
          <span class="req-card-badge req-must">Required</span>
          <div class="req-card-label">HTTPS</div>
          <div class="req-card-val">SSL cert</div>
          <div class="req-card-sub">Must be HTTPS in production</div>
        </div>
        <div class="req-card">
          <span class="req-card-badge req-rec">Recommended</span>
          <div class="req-card-label">OS</div>
          <div class="req-card-val">2022</div>
          <div class="req-card-sub">Windows Server 2019 minimum</div>
        </div>
        <div class="req-card">
          <span class="req-card-badge req-rec">Recommended</span>
          <div class="req-card-label">Disk</div>
          <div class="req-card-val">50 GB</div>
          <div class="req-card-sub">10 GB minimum; 50 GB for backups</div>
        </div>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Required PHP extensions</div>
        <p>All of the following must be enabled in <code class="sk">php.ini</code>. Verify with this command:</p>
        <div class="doc-code">
          <div class="doc-code-bar">
            <span class="doc-code-lang">PowerShell</span>
            <button class="doc-copy-btn" onclick="copyCode(this)">Copy</button>
          </div>
          <pre>php -m | Select-String "pdo_mysql|mbstring|openssl|tokenizer|xml|fileinfo|gd|zip|bcmath|curl|intl"</pre>
        </div>
        <p>You should see all eleven names listed. If any are missing, enable them in <code class="sk">php.ini</code> by removing the semicolon before <code class="sk">extension=name</code> and restarting IIS.</p>
      </div>

      <div class="doc-warning">
        <strong>IIS URL Rewrite</strong> is not included in Windows Server by default. Download and install it from
        <strong>iis.net/downloads/microsoft/url-rewrite</strong> before proceeding. Without it, all Laravel routes return 404.
      </div>
    </div>

    <hr class="docs-divider">

    {{-- ══ STEP 2 — Prepare ══ --}}
    <div class="doc-section" id="server">
      <div class="doc-eyebrow">Step 2</div>
      <div class="doc-title">Prepare the Server</div>
      <div class="doc-lead">One-time setup. Skip steps you have already done.</div>

      <div class="doc-sub">
        <div class="doc-sub-title">Create a dedicated database user</div>
        <p>Never run the application as the MySQL <code class="sk">root</code> account. Create a dedicated user with minimum privileges:</p>
        <div class="doc-code">
          <div class="doc-code-bar">
            <span class="doc-code-lang">SQL</span>
            <button class="doc-copy-btn" onclick="copyCode(this)">Copy</button>
          </div>
          <pre>CREATE DATABASE goil_budget CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'goilbudget'@'localhost' IDENTIFIED BY 'StrongPassword!2026';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER
  ON goil_budget.* TO 'goilbudget'@'localhost';
FLUSH PRIVILEGES;</pre>
        </div>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Install URL Rewrite and PHP FastCGI</div>
        <p>In IIS Manager, ensure the <strong>CGI</strong> role service is enabled. Then install the URL Rewrite 2.x module from Microsoft. PHP FastCGI must also be configured — if PHP is not already registered as a handler, this is done in Step 6 (IIS Configuration).</p>
      </div>
    </div>

    <hr class="docs-divider">

    {{-- ══ STEP 3 — Deploy ══ --}}
    <div class="doc-section" id="deploy">
      <div class="doc-eyebrow">Step 3</div>
      <div class="doc-title">Deploy the Files</div>
      <div class="doc-lead">Copy the package to the server and extract it.</div>

      <div class="step-list">
        <div class="step-item">
          <div class="step-dot">1</div>
          <div class="step-body">
            <h4>Copy the zip to the server</h4>
            <p>Use a USB drive, shared network folder, or SFTP. Place it anywhere — e.g. <code class="sk">C:\Deploy\goil-budget-vYYYY.MM.DD.zip</code></p>
          </div>
        </div>
        <div class="step-item">
          <div class="step-dot">2</div>
          <div class="step-body">
            <h4>Extract to the web root</h4>
            <div class="doc-code">
              <div class="doc-code-bar">
                <span class="doc-code-lang">PowerShell — as Administrator</span>
                <button class="doc-copy-btn" onclick="copyCode(this)">Copy</button>
              </div>
              <pre>Expand-Archive -Path "C:\Deploy\goil-budget-vYYYY.MM.DD.zip" `
              -DestinationPath "C:\inetpub\wwwroot\goil-budget"</pre>
            </div>
          </div>
        </div>
        <div class="step-item">
          <div class="step-dot">3</div>
          <div class="step-body">
            <h4>Verify the folder structure</h4>
            <p>Inside <code class="sk">C:\inetpub\wwwroot\goil-budget\</code> you should see: <code class="sk">app/</code> <code class="sk">public/</code> <code class="sk">vendor/</code> <code class="sk">install.ps1</code> <code class="sk">env.production.txt</code></p>
          </div>
        </div>
      </div>
    </div>

    <hr class="docs-divider">

    {{-- ══ STEP 4 — Configure .env ══ --}}
    <div class="doc-section" id="configure">
      <div class="doc-eyebrow">Step 4</div>
      <div class="doc-title">Configure the Environment</div>
      <div class="doc-lead">Edit the <code class="sk">env.production.txt</code> template before running the installer. Every value marked <code class="sk">&lt;&lt;&lt;</code> must be filled in.</div>

      <div class="doc-danger">
        Rename <strong>env.production.txt</strong> to <strong>.env</strong> and fill in every <code class="sk">&lt;&lt;&lt;</code> value. Do not share this file — it contains database passwords and encryption keys.
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Minimum values to set</div>
        <div class="doc-table-wrap">
          <table class="doc-table">
            <thead><tr><th>Setting</th><th>Example</th><th>Notes</th></tr></thead>
            <tbody>
              <tr>
                <td><span class="sk">APP_URL</span></td>
                <td><code class="sk">https://budget.goil.com</code></td>
                <td>Must be the real HTTPS URL. No trailing slash.</td>
              </tr>
              <tr>
                <td><span class="sk">DB_HOST</span></td>
                <td><code class="sk">127.0.0.1</code></td>
                <td>Change if MySQL is on a separate server.</td>
              </tr>
              <tr>
                <td><span class="sk">DB_USERNAME</span></td>
                <td><code class="sk">goilbudget</code></td>
                <td>The dedicated user created in Step 2.</td>
              </tr>
              <tr>
                <td><span class="sk">DB_PASSWORD</span></td>
                <td><em>your strong password</em></td>
                <td>Must match the MySQL user's password exactly.</td>
              </tr>
              <tr>
                <td><span class="sk">MAIL_HOST</span></td>
                <td><code class="sk">mail.goil.com</code></td>
                <td>GOIL's internal SMTP relay. Without this, no emails are sent.</td>
              </tr>
              <tr>
                <td><span class="sk">MAIL_USERNAME</span></td>
                <td><code class="sk">budget-system@goil.com</code></td>
                <td>A dedicated service mailbox.</td>
              </tr>
              <tr>
                <td><span class="sk">MAIL_PASSWORD</span></td>
                <td><em>mailbox password</em></td>
                <td></td>
              </tr>
              <tr>
                <td><span class="sk">SESSION_DOMAIN</span></td>
                <td><code class="sk">.goil.com</code></td>
                <td>Leading dot required. Must match your actual domain.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <hr class="docs-divider">

    {{-- ══ STEP 5 — Installer ══ --}}
    <div class="doc-section" id="install">
      <div class="doc-eyebrow">Step 5</div>
      <div class="doc-title">Run the Installer</div>
      <div class="doc-lead">The <code class="sk">install.ps1</code> script automates the entire setup in one command. Run it as Administrator.</div>

      <div class="doc-code">
        <div class="doc-code-bar">
          <span class="doc-code-lang">PowerShell — run as Administrator</span>
          <button class="doc-copy-btn" onclick="copyCode(this)">Copy</button>
        </div>
        <pre>cd C:\inetpub\wwwroot\goil-budget
Set-ExecutionPolicy Bypass -Scope Process -Force
.\install.ps1</pre>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">What the script does automatically</div>
        <ul>
          <li>Checks PHP version and all required extensions</li>
          <li>Generates the <code class="sk">APP_KEY</code> encryption key</li>
          <li>Runs <code class="sk">composer install</code> (production mode, no dev tools)</li>
          <li>Creates the <code class="sk">goil_budget</code> database if it does not exist</li>
          <li>Runs all database migrations (creates every table)</li>
          <li>Seeds roles, permissions, and the initial admin account</li>
          <li>Sets folder write permissions for the IIS App Pool on <code class="sk">storage\</code> and <code class="sk">bootstrap\cache\</code></li>
          <li>Caches routes, config, views, and events for production performance</li>
          <li>Creates two Windows Task Scheduler tasks — the Laravel scheduler and queue worker</li>
        </ul>
      </div>

      <div class="doc-tip">
        If the script completes with no red errors, the application is installed. Proceed to IIS configuration.
      </div>
    </div>

    <hr class="docs-divider">

    {{-- ══ STEP 6 — IIS ══ --}}
    <div class="doc-section" id="iis">
      <div class="doc-eyebrow">Step 6</div>
      <div class="doc-title">Configure IIS</div>
      <div class="doc-lead">Point IIS to the <code class="sk">public\</code> subfolder — not the root of the application.</div>

      <div class="doc-sub">
        <div class="doc-sub-title">Create a new IIS site</div>
        <p>Open <strong>IIS Manager → Sites → Add Website</strong> and fill in:</p>
        <div class="doc-table-wrap">
          <table class="doc-table">
            <thead><tr><th>Field</th><th>Value</th></tr></thead>
            <tbody>
              <tr><td>Site name</td><td><code class="sk">GOIL Budget</code></td></tr>
              <tr><td>Physical path</td><td><code class="sk">C:\inetpub\wwwroot\goil-budget\public</code></td></tr>
              <tr><td>Binding type</td><td><code class="sk">https</code></td></tr>
              <tr><td>Port</td><td><code class="sk">443</code></td></tr>
              <tr><td>SSL certificate</td><td>Select GOIL's SSL certificate</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Configure the Application Pool</div>
        <p>In IIS Manager → Application Pools → find the pool for this site and set:</p>
        <div class="doc-table-wrap">
          <table class="doc-table">
            <thead><tr><th>Setting</th><th>Value</th></tr></thead>
            <tbody>
              <tr><td>.NET CLR version</td><td><code class="sk">No Managed Code</code> ← critical</td></tr>
              <tr><td>Managed pipeline mode</td><td><code class="sk">Integrated</code></td></tr>
              <tr><td>Identity</td><td><code class="sk">ApplicationPoolIdentity</code></td></tr>
            </tbody>
          </table>
        </div>
        <div class="doc-warning">
          <strong>No Managed Code</strong> is not the default in IIS. If left as .NET 4.5, PHP will throw 500 errors immediately on every request.
        </div>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Add the PHP FastCGI handler</div>
        <p>If PHP is not already registered in IIS, go to <strong>Handler Mappings → Add Module Mapping</strong>:</p>
        <div class="doc-table-wrap">
          <table class="doc-table">
            <thead><tr><th>Field</th><th>Value</th></tr></thead>
            <tbody>
              <tr><td>Request path</td><td><code class="sk">*.php</code></td></tr>
              <tr><td>Module</td><td><code class="sk">FastCgiModule</code></td></tr>
              <tr><td>Executable</td><td><code class="sk">C:\php\php-cgi.exe</code> (adjust to your PHP path)</td></tr>
              <tr><td>Name</td><td><code class="sk">PHP_via_FastCGI</code></td></tr>
            </tbody>
          </table>
        </div>
        <p>The <code class="sk">web.config</code> file inside <code class="sk">public\</code> handles all URL rewriting automatically — it routes every request through Laravel's front controller and sets security headers. No additional IIS rewrite rules are needed.</p>
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Force HTTPS (recommended)</div>
        <p>Add an HTTP binding on port 80 to redirect all plain-HTTP traffic to HTTPS. The following rule goes inside the <code class="sk">&lt;rules&gt;</code> section of <code class="sk">public\web.config</code>:</p>
        <div class="doc-code">
          <div class="doc-code-bar">
            <span class="doc-code-lang">web.config — add to &lt;rules&gt;</span>
            <button class="doc-copy-btn" onclick="copyCode(this)">Copy</button>
          </div>
          <pre>&lt;rule name="Force HTTPS" stopProcessing="true"&gt;
  &lt;match url="(.*)" /&gt;
  &lt;conditions&gt;
    &lt;add input="{HTTPS}" pattern="^OFF$" /&gt;
  &lt;/conditions&gt;
  &lt;action type="Redirect" url="https://{HTTP_HOST}/{R:1}"
          redirectType="Permanent" /&gt;
&lt;/rule&gt;</pre>
        </div>
      </div>
    </div>

    <hr class="docs-divider">

    {{-- ══ STEP 7 — Background Tasks ══ --}}
    <div class="doc-section" id="scheduler">
      <div class="doc-eyebrow">Step 7</div>
      <div class="doc-title">Background Tasks</div>
      <div class="doc-lead">The application depends on two always-running background processes. The installer registers them automatically in Windows Task Scheduler.</div>

      <div class="doc-table-wrap">
        <table class="doc-table">
          <thead><tr><th>Task name</th><th>Runs every</th><th>Purpose</th></tr></thead>
          <tbody>
            <tr>
              <td><code class="sk">GOIL Budget - Laravel Scheduler</code></td>
              <td>1 minute</td>
              <td>Sends deadline reminder emails, prunes old audit logs, runs scheduled backups, processes other time-based tasks</td>
            </tr>
            <tr>
              <td><code class="sk">GOIL Budget - Queue Worker</code></td>
              <td>5 minutes</td>
              <td>Processes approval notification emails, export jobs, and all queued background work</td>
            </tr>
          </tbody>
        </table>
      </div>

      <p>Open <strong>Task Scheduler</strong> after installation and verify both tasks appear. Right-click each → <strong>Run</strong> and confirm the Last Run Result shows <code class="sk">0x0</code> (success).</p>

      <div class="doc-warning">
        If these tasks are not running, <strong>approval emails will not be sent</strong> and scheduled backups will not run. Always verify them after installation.
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Manual test</div>
        <div class="doc-code">
          <div class="doc-code-bar">
            <span class="doc-code-lang">PowerShell</span>
            <button class="doc-copy-btn" onclick="copyCode(this)">Copy</button>
          </div>
          <pre>cd C:\inetpub\wwwroot\goil-budget

# Test the scheduler
php artisan schedule:run

# Test the queue (processes pending jobs and exits)
php artisan queue:work --once

# Trigger a manual backup to verify storage
php artisan backup:run</pre>
        </div>
      </div>
    </div>

    <hr class="docs-divider">

    {{-- ── First Login ── --}}
    <div class="doc-section" id="first-login">
      <div class="doc-eyebrow">Operations</div>
      <div class="doc-title">First Login</div>
      <div class="doc-lead">The installer seeds a default admin account. Log in and change the password immediately.</div>

      <div class="doc-table-wrap">
        <table class="doc-table">
          <thead><tr><th>Field</th><th>Value</th></tr></thead>
          <tbody>
            <tr><td><strong>URL</strong></td><td><code class="sk">https://budget.goil.com</code></td></tr>
            <tr><td><strong>Email</strong></td><td><code class="sk">admin@goil.com</code></td></tr>
            <tr><td><strong>Password</strong></td><td><code class="sk">Admin@1234</code></td></tr>
          </tbody>
        </table>
      </div>

      <div class="doc-danger">
        Change the admin password immediately after first login: <strong>Account → Change Password</strong>. Any new user account created by the admin also starts with a temporary password and will be forced to change it on first login.
      </div>

      <div class="doc-sub">
        <div class="doc-sub-title">Post-installation checklist</div>
        <ul class="doc-checklist" id="postinstall-chk">
          <li><div class="doc-chk" onclick="toggleChk(this)">✓</div><span>Change admin password</span></li>
          <li><div class="doc-chk" onclick="toggleChk(this)">✓</div><span>Settings → System Settings: set company name, currency, session timeout</span></li>
          <li><div class="doc-chk" onclick="toggleChk(this)">✓</div><span>Setup → Budget Periods: create the current financial year</span></li>
          <li><div class="doc-chk" onclick="toggleChk(this)">✓</div><span>Setup → Approval Stages: configure who approves at each stage</span></li>
          <li><div class="doc-chk" onclick="toggleChk(this)">✓</div><span>Setup → Account Categories &amp; Codes: import the chart of accounts</span></li>
          <li><div class="doc-chk" onclick="toggleChk(this)">✓</div><span>Setup → Departments: create all departments and assign account codes</span></li>
          <li><div class="doc-chk" onclick="toggleChk(this)">✓</div><span>Admin → Users: create user accounts (or use Bulk Import CSV)</span></li>
          <li><div class="doc-chk" onclick="toggleChk(this)">✓</div><span>Send a test email to verify the mail server is working</span></li>
          <li><div class="doc-chk" onclick="toggleChk(this)">✓</div><span>Admin → Backups: run a manual backup to confirm backup storage works</span></li>
        </ul>
      </div>
    </div>

    <hr class="docs-divider">

    {{-- ── Updates ── --}}
    <div class="doc-section" id="updates">
      <div class="doc-eyebrow">Operations</div>
      <div class="doc-title">Applying Updates</div>
      <div class="doc-lead">When a new version is delivered, follow these steps in order. The process takes under 10 minutes.</div>

      <div class="doc-timeline">
        <div class="doc-timeline-item">
          <div class="doc-timeline-dot"></div>
          <div class="doc-timeline-title">1 — Take a backup first</div>
          <div class="doc-timeline-body">Always backup before any update. Run <code class="sk">php artisan backup:run</code> from the app folder.</div>
        </div>
        <div class="doc-timeline-item">
          <div class="doc-timeline-dot"></div>
          <div class="doc-timeline-title">2 — Unzip the new package over the existing folder</div>
          <div class="doc-timeline-body">
            <div class="doc-code" style="margin-top:8px">
              <div class="doc-code-bar">
                <span class="doc-code-lang">PowerShell</span>
                <button class="doc-copy-btn" onclick="copyCode(this)">Copy</button>
              </div>
              <pre>Expand-Archive -Path "goil-budget-vNEW.zip" `
              -DestinationPath "C:\inetpub\wwwroot\goil-budget" -Force</pre>
            </div>
            The <code class="sk">.env</code> file is not included in the zip, so your credentials are not overwritten.
          </div>
        </div>
        <div class="doc-timeline-item">
          <div class="doc-timeline-dot"></div>
          <div class="doc-timeline-title">3 — Run the update commands</div>
          <div class="doc-timeline-body">
            <div class="doc-code" style="margin-top:8px">
              <div class="doc-code-bar">
                <span class="doc-code-lang">PowerShell — as Administrator</span>
                <button class="doc-copy-btn" onclick="copyCode(this)">Copy</button>
              </div>
              <pre>cd C:\inetpub\wwwroot\goil-budget
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache</pre>
            </div>
          </div>
        </div>
        <div class="doc-timeline-item">
          <div class="doc-timeline-dot"></div>
          <div class="doc-timeline-title">4 — Test in browser</div>
          <div class="doc-timeline-body">Open <code class="sk">https://budget.goil.com</code> and confirm login works. Check <code class="sk">storage\logs\laravel.log</code> if anything looks wrong.</div>
        </div>
      </div>
    </div>

    <hr class="docs-divider">

    {{-- ── Troubleshooting ── --}}
    <div class="doc-section" id="trouble">
      <div class="doc-eyebrow">Operations</div>
      <div class="doc-title">Troubleshooting</div>
      <div class="doc-lead">Common problems and how to resolve them. Always check the log file first.</div>

      <div class="doc-sub">
        <div class="doc-sub-title">Log file location</div>
        <div class="doc-code">
          <div class="doc-code-bar">
            <span class="doc-code-lang">PowerShell — view last 50 lines of log</span>
            <button class="doc-copy-btn" onclick="copyCode(this)">Copy</button>
          </div>
          <pre>Get-Content "C:\inetpub\wwwroot\goil-budget\storage\logs\laravel.log" -Tail 50</pre>
        </div>
        <p>Share this log (with passwords redacted) when requesting developer support. Do not share the <code class="sk">.env</code> file.</p>
      </div>

      <div class="doc-table-wrap">
        <table class="doc-table">
          <thead><tr><th>Symptom</th><th>Likely cause</th><th>Fix</th></tr></thead>
          <tbody>
            <tr>
              <td><strong>500 Internal Server Error</strong></td>
              <td>PHP error or missing config</td>
              <td>Check <code class="sk">storage\logs\laravel.log</code>. Temporarily set <code class="sk">APP_DEBUG=true</code> in <code class="sk">.env</code> to see the error in the browser, then reset to <code class="sk">false</code>.</td>
            </tr>
            <tr>
              <td><strong>All routes return 404</strong></td>
              <td>URL Rewrite not installed, or <code class="sk">web.config</code> missing</td>
              <td>Install IIS URL Rewrite 2.x from iis.net. Confirm <code class="sk">public\web.config</code> exists.</td>
            </tr>
            <tr>
              <td><strong>Blank white page</strong></td>
              <td>PHP not running / wrong App Pool setting</td>
              <td>Confirm App Pool .NET CLR = "No Managed Code". Confirm the PHP FastCGI handler is registered.</td>
            </tr>
            <tr>
              <td><strong>500 on upload or cache clear</strong></td>
              <td>IIS App Pool has no write access to <code class="sk">storage\</code></td>
              <td>Run: <code class="sk">icacls storage /grant "IIS AppPool\DefaultAppPool":(OI)(CI)M /T</code></td>
            </tr>
            <tr>
              <td><strong>Login works but session drops</strong></td>
              <td><code class="sk">SESSION_DOMAIN</code> mismatch</td>
              <td>Check <code class="sk">SESSION_DOMAIN</code> in <code class="sk">.env</code>. Must start with a dot, e.g. <code class="sk">.goil.com</code>.</td>
            </tr>
            <tr>
              <td><strong>Password reset emails not arriving</strong></td>
              <td>MAIL_* not configured or queue not running</td>
              <td>Verify <code class="sk">MAIL_HOST</code>, <code class="sk">MAIL_USERNAME</code>, <code class="sk">MAIL_PASSWORD</code> in <code class="sk">.env</code>. Ensure the Queue Worker task is running in Task Scheduler.</td>
            </tr>
            <tr>
              <td><strong>Approval emails not sent</strong></td>
              <td>Queue worker stopped</td>
              <td>Open Task Scheduler → right-click "GOIL Budget - Queue Worker" → Run. Check for errors.</td>
            </tr>
            <tr>
              <td><strong>LDAP / AD login not working</strong></td>
              <td>SSO misconfigured</td>
              <td>In System Settings, disable SSO temporarily. Fix the <code class="sk">LDAP_*</code> settings in <code class="sk">.env</code>, then re-enable.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>{{-- end docs-content --}}
</div>{{-- end docs-wrap --}}
@endsection

@push('scripts')
<script>
  // ── Scroll-spy ──
  const dpNavLinks  = document.querySelectorAll('#docsNav .docs-nav-link[href^="#"]');
  const dpSections  = Array.from(document.querySelectorAll('.doc-section')).filter(s => s.id);
  const dpMainEl    = document.getElementById('main');

  function updateDpActive() {
    const scrollTop = (dpMainEl ? dpMainEl.scrollTop : window.scrollY) + 100;
    let current = dpSections[0];
    for (const s of dpSections) { if (s.offsetTop <= scrollTop) current = s; }
    dpNavLinks.forEach(l => {
      l.classList.toggle('active', l.getAttribute('href') === '#' + (current?.id ?? ''));
    });
  }
  (dpMainEl || window).addEventListener('scroll', updateDpActive, { passive: true });
  updateDpActive();

  // ── Copy buttons ──
  function copyCode(btn) {
    const pre = btn.closest('.doc-code').querySelector('pre');
    navigator.clipboard.writeText(pre.textContent.trim()).then(() => {
      btn.textContent = 'Copied!';
      btn.classList.add('copied');
      setTimeout(() => { btn.textContent = 'Copy'; btn.classList.remove('copied'); }, 2000);
    });
  }

  // ── Checklist with localStorage persistence ──
  function toggleChk(chk) {
    chk.classList.toggle('chk-done');
    const li  = chk.closest('li');
    li.classList.toggle('row-done');
    const ul  = chk.closest('ul');
    const idx = Array.from(li.parentNode.children).indexOf(li);
    try { localStorage.setItem('chk-' + ul.id + '-' + idx, chk.classList.contains('chk-done') ? '1' : '0'); } catch(e) {}
  }

  // Restore checklist state
  document.querySelectorAll('ul[id] .doc-chk').forEach((chk) => {
    const ul  = chk.closest('ul');
    const li  = chk.closest('li');
    const idx = Array.from(li.parentNode.children).indexOf(li);
    try {
      if (localStorage.getItem('chk-' + ul.id + '-' + idx) === '1') {
        chk.classList.add('chk-done');
        li.classList.add('row-done');
      }
    } catch(e) {}
  });
</script>
@endpush
