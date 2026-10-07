@extends('layouts.app')

@section('title', 'Sage Integration')

@push('styles')
<style>
  /* ── Reuse docs palette & layout ── */
  .docs-wrap { --d-navy: #1B2A4A; --d-gold: #C9A84C; }

  .docs-wrap {
    display: flex;
    gap: 0;
    min-height: 100%;
    align-items: flex-start;
  }

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

  .docs-content {
    flex: 1; min-width: 0;
    padding: 0 0 80px 40px;
    max-width: 820px;
  }

  .doc-section { padding-top: 52px; scroll-margin-top: 20px; }
  .doc-eyebrow {
    font-size: 10.5px; font-weight: 700; letter-spacing: 1.1px;
    text-transform: uppercase; color: #C9A84C; margin-bottom: 5px;
  }
  .doc-title {
    font-size: 24px; font-weight: 700; color: #1B2A4A;
    line-height: 1.2; margin-bottom: 10px;
  }
  .doc-lead {
    font-size: 14.5px; color: #64748B; max-width: 66ch;
    margin-bottom: 24px; line-height: 1.7;
  }
  .doc-sub { padding-top: 28px; margin-bottom: 4px; }
  .doc-sub-title {
    font-size: 16px; font-weight: 700; color: #1B2A4A;
    margin-bottom: 10px; padding-bottom: 7px;
    border-bottom: 1px solid #E2E8F0;
  }
  .docs-divider { border: none; border-top: 1px solid #E2E8F0; margin: 36px 0; }

  .docs-content p { margin-bottom: 12px; max-width: 70ch; }
  .docs-content p:last-child { margin-bottom: 0; }
  .docs-content ul, .docs-content ol { padding-left: 20px; margin-bottom: 12px; }
  .docs-content li { margin-bottom: 4px; max-width: 68ch; }

  .doc-table-wrap { overflow-x: auto; margin: 14px 0 20px; border-radius: 8px; border: 1px solid #E2E8F0; }
  .doc-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
  .doc-table thead tr { background: #F8FAFC; }
  .doc-table th {
    text-align: left; padding: 9px 13px;
    font-size: 10.5px; font-weight: 700; letter-spacing: .6px;
    text-transform: uppercase; color: #64748B;
    border-bottom: 1px solid #E2E8F0; white-space: nowrap;
  }
  .doc-table td { padding: 8px 13px; border-bottom: 1px solid #F1F5F9; vertical-align: top; line-height: 1.5; }
  .doc-table tr:last-child td { border-bottom: none; }
  .doc-table tbody tr:hover td { background: #FAFBFD; }

  .doc-note    { background: #EFF6FF; border-left: 3px solid #3B82F6; padding: 11px 14px; border-radius: 0 6px 6px 0; font-size: 13.5px; color: #1E40AF; margin: 14px 0; }
  .doc-warning { background: #FFFBEB; border-left: 3px solid #F59E0B; padding: 11px 14px; border-radius: 0 6px 6px 0; font-size: 13.5px; color: #92400E; margin: 14px 0; }
  .doc-tip     { background: #F0FDF4; border-left: 3px solid #10B981; padding: 11px 14px; border-radius: 0 6px 6px 0; font-size: 13.5px; color: #065F46; margin: 14px 0; }

  .sk { font-family: 'Courier New', Courier, monospace; font-size: 12px; background: #EEF2F7; color: #1B2A4A; padding: 1px 5px; border-radius: 3px; white-space: nowrap; }

  /* ── Priority chips ── */
  .pri {
    display: inline-flex; align-items: center;
    font-size: 10px; font-weight: 700; letter-spacing: .6px;
    text-transform: uppercase; padding: 3px 8px;
    border-radius: 4px; white-space: nowrap; flex-shrink: 0;
  }
  .pri-critical { background: #FEE2E2; color: #991B1B; }
  .pri-required { background: #FEF3C7; color: #92400E; }
  .pri-important{ background: #DBEAFE; color: #1E40AF; }

  /* ── Requirement card rows ── */
  .req-list { display: flex; flex-direction: column; gap: 2px; margin-top: 14px; }
  .req {
    display: grid;
    grid-template-columns: 90px 1fr;
    gap: 10px 14px;
    align-items: start;
    padding: 13px 15px;
    border-radius: 8px;
    background: #fff;
    border: 1px solid #E2E8F0;
    transition: border-color .12s;
  }
  .req:hover { border-color: #CBD5E1; }
  .req-label { font-size: 14px; font-weight: 600; color: #1B2A4A; margin-bottom: 3px; }
  .req-detail { font-size: 13px; color: #475569; line-height: 1.55; }
  .req-detail code { font-family: 'Courier New', Courier, monospace; font-size: 12px; background: #F1F5F9; color: #E65C00; padding: 1px 5px; border-radius: 4px; }
  .req-tags { margin-top: 7px; display: flex; flex-wrap: wrap; gap: 5px; }
  .req-tag { font-family: 'Courier New', Courier, monospace; font-size: 11px; background: #F1F5F9; color: #475569; padding: 2px 7px; border-radius: 4px; }

  /* ── Priority legend ── */
  .pri-legend {
    display: flex; gap: 20px; flex-wrap: wrap;
    padding: 12px 16px; background: #F8FAFC;
    border: 1px solid #E2E8F0; border-radius: 8px;
    margin-bottom: 32px; font-size: 12px; color: #64748B;
  }
  .pri-legend-item { display: flex; align-items: center; gap: 7px; }

  /* ── Docs hero ── */
  .docs-hero { background: #1B2A4A; border-radius: 10px; padding: 28px 32px; margin-bottom: 8px; color: #fff; }
  .docs-hero h1 { font-size: 22px; font-weight: 700; line-height: 1.2; margin-bottom: 8px; color: #fff; }
  .docs-hero h1 span { color: #E8C56A; }
  .docs-hero p { color: rgba(255,255,255,.65); max-width: 58ch; margin-bottom: 16px; font-size: 14px; line-height: 1.65; }
  .docs-hero-meta { display: flex; gap: 20px; flex-wrap: wrap; }
  .docs-hero-meta-item { font-size: 11.5px; color: rgba(255,255,255,.45); }
  .docs-hero-meta-item strong { color: rgba(255,255,255,.8); font-weight: 600; display: block; font-size: 12.5px; }

  /* ── Back link ── */
  .back-link {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 13px; color: #64748B; text-decoration: none;
    margin-bottom: 20px;
    transition: color .1s;
  }
  .back-link:hover { color: #1B2A4A; }

  /* ── DDL code blocks ── */
  .ddl-wrap { overflow-x: auto; border-radius: 8px; margin: 14px 0 6px; border: 1px solid #1E293B; }
  pre.ddl {
    background: #0F172A;
    color: #CBD5E1;
    font-family: 'Courier New', Courier, monospace;
    font-size: 12px;
    line-height: 1.7;
    padding: 18px 22px;
    margin: 0;
    min-width: 520px;
    tab-size: 2;
  }
  .ddl .kw  { color: #67E8F9; font-weight: 600; }
  .ddl .ty  { color: #A5B4FC; }
  .ddl .cmt { color: #475569; font-style: italic; }
  .ddl .str { color: #86EFAC; }
  .ddl .nm  { color: #FDE68A; }

  /* ── Table classification badges ── */
  .tb { display: inline-block; font-size: 10px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; padding: 2px 8px; border-radius: 4px; white-space: nowrap; margin-left: 8px; vertical-align: middle; }
  .tb-master  { background: #D1FAE5; color: #065F46; }
  .tb-finance { background: #FEF3C7; color: #92400E; }
  .tb-lookup  { background: #EDE9FE; color: #5B21B6; }
  .tb-transact{ background: #FEE2E2; color: #991B1B; }

  /* ── Sage mapping callout ── */
  .sage-map {
    background: #EFF6FF; border-left: 3px solid #3B82F6;
    padding: 10px 14px; border-radius: 0 6px 6px 0;
    font-size: 13px; color: #1E40AF; margin: 12px 0 4px;
  }
  .sage-map strong { color: #1E40AF; }
  .sage-map code { font-family: 'Courier New', Courier, monospace; font-size: 11.5px; background: #DBEAFE; color: #1E3A8A; padding: 1px 5px; border-radius: 3px; }

  @media (max-width: 900px) {
    .docs-nav { display: none; }
    .docs-content { padding-left: 0; }
  }
</style>
@endpush

@section('content')
<div class="docs-wrap">

  {{-- ── Inner nav ── --}}
  <aside class="docs-nav" id="docsNav">
    <div class="docs-nav-group">
      <span class="docs-nav-label">On this page</span>
      <a class="docs-nav-link" href="#overview">Overview</a>
      <a class="docs-nav-link" href="#s1">Sage Product &amp; Environment</a>
      <a class="docs-nav-link" href="#s2">API &amp; Connectivity</a>
      <a class="docs-nav-link" href="#s3">Chart of Accounts</a>
      <a class="docs-nav-link" href="#s4">Org Structure Mapping</a>
      <a class="docs-nav-link" href="#s5">Budget Module</a>
      <a class="docs-nav-link" href="#s6">Fiscal Calendar</a>
      <a class="docs-nav-link" href="#s7">Actuals &amp; GL Balances</a>
      <a class="docs-nav-link" href="#s8">Service Account</a>
      <a class="docs-nav-link" href="#s9">Sandbox &amp; Testing</a>
      <a class="docs-nav-link" href="#s10">Support Contacts</a>
      <a class="docs-nav-link" href="#handoff">Handoff Summary</a>
      <a class="docs-nav-link" href="#schema">Database Schema</a>
    </div>
    <div class="docs-nav-group" style="margin-top:8px">
      <span class="docs-nav-label">Other Docs</span>
      <a class="docs-nav-link" href="{{ route('docs.index') }}">← System Documentation</a>
      <a class="docs-nav-link" href="{{ route('docs.process-guide') }}">Process Guide</a>
      <a class="docs-nav-link" href="{{ route('docs.deployment-guide') }}">Deployment Guide</a>
    </div>
  </aside>

  {{-- ── Content ── --}}
  <div class="docs-content">

    <a href="{{ route('docs.index') }}" class="back-link">
      <i class="fas fa-arrow-left" style="font-size:11px"></i> System Documentation
    </a>

    {{-- Hero ── --}}
    <div class="docs-hero" id="overview">
      <h1>Sage ERP Integration — <span>Requirements Checklist</span></h1>
      <p>
        Everything the GOIL Budget team needs from the Sage admins before integration work can begin.
        Share this page with your Sage administrator and ask them to prepare each item listed.
      </p>
      <div class="docs-hero-meta">
        <div class="docs-hero-meta-item">
          <strong>Direction</strong>
          GOIL Budget ↔ Sage ERP
        </div>
        <div class="docs-hero-meta-item">
          <strong>Requirement areas</strong>
          10 categories + DB schema
        </div>
        <div class="docs-hero-meta-item">
          <strong>Updated</strong>
          August 2026
        </div>
      </div>
    </div>

    {{-- Priority legend ── --}}
    <div class="pri-legend">
      <div class="pri-legend-item">
        <span class="pri pri-critical">Critical</span>
        Can't start without this
      </div>
      <div class="pri-legend-item">
        <span class="pri pri-required">Required</span>
        Needed before go-live
      </div>
      <div class="pri-legend-item">
        <span class="pri pri-important">Important</span>
        Needed for full feature set
      </div>
    </div>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- 1. Sage Product & Environment --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="doc-section" id="s1">
      <div class="doc-eyebrow">Requirement Area 01</div>
      <div class="doc-title">Sage Product &amp; Environment</div>
      <div class="doc-lead">
        The integration approach depends entirely on which Sage product is in use.
        Different products expose completely different APIs — getting this wrong means building the wrong connector.
      </div>

      <div class="req-list">
        <div class="req">
          <span class="pri pri-critical">Critical</span>
          <div>
            <div class="req-label">Exact Sage product name and version</div>
            <div class="req-detail">Not just "Sage" — the specific product line and version number determine which API or import method is available.</div>
            <div class="req-tags">
              <span class="req-tag">Sage 50</span>
              <span class="req-tag">Sage 100</span>
              <span class="req-tag">Sage 200</span>
              <span class="req-tag">Sage 300 (Accpac)</span>
              <span class="req-tag">Sage X3</span>
              <span class="req-tag">Sage Intacct</span>
              <span class="req-tag">Sage Business Cloud</span>
            </div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-critical">Critical</span>
          <div>
            <div class="req-label">Deployment type</div>
            <div class="req-detail">Cloud-hosted (Sage-managed) or on-premise server? This determines whether the integration calls an internet URL or a local network address.</div>
            <div class="req-tags">
              <span class="req-tag">Cloud / SaaS</span>
              <span class="req-tag">On-premise server</span>
              <span class="req-tag">Hosted by third-party partner</span>
            </div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-critical">Critical</span>
          <div>
            <div class="req-label">Server URL or API base URL</div>
            <div class="req-detail">The base address the integration will connect to. For cloud: the tenant URL. For on-premise: the server hostname or IP and port. Example: <code>https://goil.sage.com</code> or <code>http://192.168.1.50:8080</code>.</div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-required">Required</span>
          <div>
            <div class="req-label">Company database name / Company ID</div>
            <div class="req-detail">Sage can host multiple companies in one installation. Provide the exact company code or database name for GOIL's live environment — e.g. <code>GOILGH</code> or <code>Company001</code>.</div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-required">Required</span>
          <div>
            <div class="req-label">Are multiple Sage company databases in use?</div>
            <div class="req-detail">If subsidiaries (Lubricants, Aviation, Marine, etc.) are held in separate Sage companies, we need credentials and mappings for each one. List all company databases that hold GOIL budget data.</div>
          </div>
        </div>
      </div>
    </div>
    <hr class="docs-divider">

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- 2. API & Connectivity --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="doc-section" id="s2">
      <div class="doc-eyebrow">Requirement Area 02</div>
      <div class="doc-title">API &amp; Connectivity Access</div>
      <div class="doc-lead">
        How the integration actually communicates with Sage. Without confirmed connectivity details, no code can be tested.
      </div>

      <div class="req-list">
        <div class="req">
          <span class="pri pri-critical">Critical</span>
          <div>
            <div class="req-label">Is the Sage API or web services module licensed and enabled?</div>
            <div class="req-detail">Many Sage editions require an additional API licence. Confirm whether REST API, SOAP web services, or the Sage SDK is activated for your subscription. If not enabled, this must be arranged before any development begins.</div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-critical">Critical</span>
          <div>
            <div class="req-label">Integration method available</div>
            <div class="req-detail">Tell us which method(s) Sage exposes on your installation so we build the right connector.</div>
            <div class="req-tags">
              <span class="req-tag">REST API</span>
              <span class="req-tag">SOAP / Web Services</span>
              <span class="req-tag">Sage SDK / Business Objects</span>
              <span class="req-tag">ODBC direct DB</span>
              <span class="req-tag">CSV / file import only</span>
            </div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-critical">Critical</span>
          <div>
            <div class="req-label">Authentication method and credentials</div>
            <div class="req-detail">The exact auth flow the integration must use. Provide all values needed — share credentials via a password manager or secure handover call, never plain email.</div>
            <div class="req-tags">
              <span class="req-tag">API key</span>
              <span class="req-tag">OAuth 2.0 client ID + secret</span>
              <span class="req-tag">Username + password</span>
              <span class="req-tag">Session token</span>
            </div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-required">Required</span>
          <div>
            <div class="req-label">Firewall / IP allowlist requirements</div>
            <div class="req-detail">If Sage is behind a firewall, the GOIL Budget server's outbound IP address must be whitelisted. Provide the allowlist process and the IT contact who can apply it. Also confirm whether a VPN is required.</div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-required">Required</span>
          <div>
            <div class="req-label">Rate limits and throttling policy</div>
            <div class="req-detail">Does the Sage API cap requests per minute or hour? Provide the limit so the integration stays within it — especially important for bulk budget uploads covering hundreds of line items.</div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-important">Important</span>
          <div>
            <div class="req-label">SSL / TLS certificate details (on-premise only)</div>
            <div class="req-detail">If Sage is on-premise with a self-signed certificate, provide the certificate file so the integration server can trust it. A proper CA-signed certificate is strongly preferred.</div>
          </div>
        </div>
      </div>
    </div>
    <hr class="docs-divider">

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- 3. Chart of Accounts --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="doc-section" id="s3">
      <div class="doc-eyebrow">Requirement Area 03</div>
      <div class="doc-title">Chart of Accounts</div>
      <div class="doc-lead">
        The core mapping layer. GOIL Budget's account codes must be reconciled against Sage's GL codes before any data can flow.
      </div>

      <div class="req-list">
        <div class="req">
          <span class="pri pri-critical">Critical</span>
          <div>
            <div class="req-label">Full chart of accounts export from Sage</div>
            <div class="req-detail">An export of every GL account: code, name, account type (revenue/expense/asset/liability), parent/group, and active status. CSV or Excel is fine. This is the reference we map GOIL's account codes against.</div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-critical">Critical</span>
          <div>
            <div class="req-label">GL account code format and structure</div>
            <div class="req-detail">Describe the format Sage uses for GL codes — this determines how the mapping table is validated.</div>
            <div class="req-tags">
              <span class="req-tag">4-digit numeric: 4100</span>
              <span class="req-tag">Segmented: 4100-01-200</span>
              <span class="req-tag">Alphanumeric: EXP-SALARY-GH</span>
            </div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-critical">Critical</span>
          <div>
            <div class="req-label">Mapping table: GOIL account codes → Sage GL codes</div>
            <div class="req-detail">Finance and the Sage admin must jointly produce a spreadsheet with two columns: GOIL Budget account code and its corresponding Sage GL code. Flag any GOIL codes with no Sage equivalent — those lines cannot sync until a GL code is created in Sage.</div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-required">Required</span>
          <div>
            <div class="req-label">Cost centre / department codes in Sage</div>
            <div class="req-detail">How departments and cost centres are identified in Sage GL — these must match how GOIL Budget identifies departments. Export the full list with their Sage codes.</div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-important">Important</span>
          <div>
            <div class="req-label">Multi-currency setup</div>
            <div class="req-detail">Does Sage hold accounts in multiple currencies? Confirm the functional currency for the GOIL company and whether budget figures are expected in GHS or converted amounts.</div>
          </div>
        </div>
      </div>

      <div class="doc-warning">
        <strong>Action required (Finance + Sage admin jointly):</strong>
        Accounts that exist in GOIL Budget but have no Sage GL equivalent will be excluded from the sync.
        The Finance team must decide: create the missing GL codes in Sage, or retire the GOIL codes. This must be resolved before go-live.
      </div>
    </div>
    <hr class="docs-divider">

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- 4. Org Structure --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="doc-section" id="s4">
      <div class="doc-eyebrow">Requirement Area 04</div>
      <div class="doc-title">Organisational Structure Mapping</div>
      <div class="doc-lead">
        GOIL Budget organises budgets by Department, Service Station (under a Zone), and Subsidiary.
        Sage may represent these differently — as cost centres, analysis codes, or GL dimensions.
      </div>

      <div class="req-list">
        <div class="req">
          <span class="pri pri-critical">Critical</span>
          <div>
            <div class="req-label">How are departments represented in Sage?</div>
            <div class="req-detail">Confirm which Sage concept maps to a GOIL department — and export the full list with codes.</div>
            <div class="req-tags">
              <span class="req-tag">Cost centres</span>
              <span class="req-tag">Analysis codes / dimensions</span>
              <span class="req-tag">GL account segments</span>
              <span class="req-tag">Projects</span>
            </div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-required">Required</span>
          <div>
            <div class="req-label">Are service stations tracked separately in Sage?</div>
            <div class="req-detail">GOIL Budget tracks many service stations under zones. If Sage consolidates these into zone-level cost centres rather than individual station codes, the integration must aggregate accordingly. Confirm the level of granularity and provide the station/zone code list.</div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-required">Required</span>
          <div>
            <div class="req-label">Are subsidiaries tracked in Sage? How?</div>
            <div class="req-detail">GOIL subsidiaries (Lubricants, Aviation, Marine, etc.) each have their own budget versions. Are these separate Sage companies, separate cost centres, or separate analysis dimensions? Export the list with codes.</div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-important">Important</span>
          <div>
            <div class="req-label">Full dimension / segment structure export</div>
            <div class="req-detail">If Sage uses a segmented GL (e.g. <code>Account-CostCentre-Project</code>), export the full dimension structure and all valid combinations relevant to GOIL operations. This shapes every GL posting the integration generates.</div>
          </div>
        </div>
      </div>
    </div>
    <hr class="docs-divider">

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- 5. Budget Module --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="doc-section" id="s5">
      <div class="doc-eyebrow">Requirement Area 05</div>
      <div class="doc-title">Budget Module</div>
      <div class="doc-lead">
        How GOIL budget data will land in Sage — and in exactly what format.
      </div>

      <div class="req-list">
        <div class="req">
          <span class="pri pri-critical">Critical</span>
          <div>
            <div class="req-label">Is the Sage budget module active and licensed?</div>
            <div class="req-detail">Some Sage editions require a separate licence for the budgeting module. Confirm it is active. If not, clarify whether Sage will receive budget data at all, or whether this integration is only for pulling actuals back into GOIL Budget.</div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-critical">Critical</span>
          <div>
            <div class="req-label">Budget import method and required field format</div>
            <div class="req-detail">Provide the exact schema Sage expects for a budget record — field names, data types, mandatory vs optional fields, and the example import template if one exists.</div>
            <div class="req-tags">
              <span class="req-tag">REST API endpoint + JSON body</span>
              <span class="req-tag">SOAP budget import call</span>
              <span class="req-tag">CSV import template</span>
            </div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-required">Required</span>
          <div>
            <div class="req-label">Does Sage support budget versions / scenarios?</div>
            <div class="req-detail">GOIL Budget maintains an Original budget and approved Revisions. Can Sage hold multiple named budget scenarios per period (e.g. "Original 2026", "Revision 1 2026")? If not, clarify which version Sage should receive — typically the latest approved.</div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-required">Required</span>
          <div>
            <div class="req-label">Budget data granularity expected by Sage</div>
            <div class="req-detail">Does Sage want monthly figures (12 periods), quarterly, or annual totals? GOIL Budget stores 12 monthly amounts per line item — if Sage only wants annual totals, the integration will sum them before posting.</div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-important">Important</span>
          <div>
            <div class="req-label">What triggers a budget push to Sage?</div>
            <div class="req-detail">Should Sage receive budget data only when a budget is fully approved? Or at each approval stage? Confirm the business rule so the integration fires at the right point in the GOIL workflow.</div>
            <div class="req-tags">
              <span class="req-tag">On final approval only</span>
              <span class="req-tag">On each approval stage</span>
              <span class="req-tag">Manual / on-demand</span>
              <span class="req-tag">Nightly scheduled batch</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <hr class="docs-divider">

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- 6. Fiscal Calendar --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="doc-section" id="s6">
      <div class="doc-eyebrow">Requirement Area 06</div>
      <div class="doc-title">Fiscal Calendar</div>
      <div class="doc-lead">
        Period numbers must match exactly. A mismatch between GOIL's month 1 and Sage's period 1 corrupts every figure pushed across.
      </div>

      <div class="req-list">
        <div class="req">
          <span class="pri pri-critical">Critical</span>
          <div>
            <div class="req-label">Sage fiscal year start date</div>
            <div class="req-detail">The month and day the Sage financial year begins — e.g. January 1 (calendar year) or July 1. This determines how GOIL's monthly budget amounts map to Sage period numbers.</div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-critical">Critical</span>
          <div>
            <div class="req-label">Sage period numbering convention</div>
            <div class="req-detail">Does Sage period 1 equal January, or does it equal the first month of the fiscal year? Export the period list for the current fiscal year: period number, start date, end date, open/closed status.</div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-required">Required</span>
          <div>
            <div class="req-label">Can data be posted to closed periods?</div>
            <div class="req-detail">If a GOIL budget is approved after a Sage period is already closed (e.g. delayed approvals), can the integration write to that period — or does Sage need to reopen it first? Provide the policy and the contact who can reopen periods.</div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-important">Important</span>
          <div>
            <div class="req-label">Does Sage use a 13th adjustment period?</div>
            <div class="req-detail">Some Sage setups include a 13th period for year-end adjustments. Confirm the total period count so GOIL's 12-period data is not accidentally posted to period 13.</div>
          </div>
        </div>
      </div>
    </div>
    <hr class="docs-divider">

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- 7. Actuals --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="doc-section" id="s7">
      <div class="doc-eyebrow">Requirement Area 07</div>
      <div class="doc-title">Actuals &amp; GL Balances (Pull from Sage)</div>
      <div class="doc-lead">
        If GOIL Budget will pull actual figures from Sage — rather than entering them manually — these details are required.
      </div>

      <div class="req-list">
        <div class="req">
          <span class="pri pri-required">Required</span>
          <div>
            <div class="req-label">Can actual GL balances be read via API?</div>
            <div class="req-detail">Confirm whether the Sage API exposes trial balance or period GL balances per account and cost centre. If not via API, is a scheduled report export (CSV) feasible instead?</div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-required">Required</span>
          <div>
            <div class="req-label">GL balance format returned by Sage</div>
            <div class="req-detail">Are balances returned as period-to-date (this month only) or cumulative YTD? GOIL Budget needs period-level figures. If only YTD is available, confirm whether prior-period figures can also be retrieved so monthly movements can be derived.</div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-required">Required</span>
          <div>
            <div class="req-label">Are actuals posted at account + cost centre level?</div>
            <div class="req-detail">GOIL Budget records actuals per account code per department. Confirm Sage postings carry both the GL code and the department/cost centre dimension — without the cost centre, actuals cannot be broken down by department.</div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-important">Important</span>
          <div>
            <div class="req-label">Actuals pull frequency</div>
            <div class="req-detail">How often should GOIL Budget refresh actual figures from Sage — nightly, on demand, or after each GL period close? This shapes the scheduled job design.</div>
          </div>
        </div>
      </div>
    </div>
    <hr class="docs-divider">

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- 8. Service Account --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="doc-section" id="s8">
      <div class="doc-eyebrow">Requirement Area 08</div>
      <div class="doc-title">Integration Service Account</div>
      <div class="doc-lead">
        The integration must authenticate as a dedicated Sage user — not a named person's account — so access can be revoked independently without disrupting anyone's day-to-day login.
      </div>

      <div class="req-list">
        <div class="req">
          <span class="pri pri-critical">Critical</span>
          <div>
            <div class="req-label">Create a dedicated Sage service account for the integration</div>
            <div class="req-detail">Username should be something like <code>goil-budget-api</code> or <code>integration-svc</code>. This account must never be used for interactive login. Share credentials via a password manager or secure handover call — never email or chat.</div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-critical">Critical</span>
          <div>
            <div class="req-label">Required Sage permissions for the service account</div>
            <div class="req-detail">Grant only what the integration actually uses — no broader access than necessary:</div>
            <div class="req-tags">
              <span class="req-tag">Read GL accounts &amp; balances</span>
              <span class="req-tag">Write / import budget data</span>
              <span class="req-tag">Read cost centre list</span>
              <span class="req-tag">Read fiscal period list</span>
            </div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-required">Required</span>
          <div>
            <div class="req-label">Password rotation policy</div>
            <div class="req-detail">How often must the service account password be rotated? The integration's credentials store must be updated every time — provide advance notice of rotation dates so a password change does not break the integration unexpectedly.</div>
          </div>
        </div>
      </div>

      <div class="doc-note">
        <strong>Security:</strong> Credentials must be shared via a password manager, never plain email or chat.
        Store them in environment variables on the GOIL Budget server — never hard-code them in source files.
      </div>
    </div>
    <hr class="docs-divider">

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- 9. Sandbox --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="doc-section" id="s9">
      <div class="doc-eyebrow">Requirement Area 09</div>
      <div class="doc-title">Sandbox &amp; Testing Environment</div>
      <div class="doc-lead">
        Integration work cannot be tested against the live Sage database. A separate test environment is mandatory before any production deployment.
      </div>

      <div class="req-list">
        <div class="req">
          <span class="pri pri-critical">Critical</span>
          <div>
            <div class="req-label">Is a Sage test / sandbox environment available?</div>
            <div class="req-detail">A copy of the production environment — ideally with recent anonymised data — where the integration can write, update, and delete records freely without risk to live financials. If no sandbox exists, request one be provisioned before development begins.</div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-critical">Critical</span>
          <div>
            <div class="req-label">Sandbox URL and credentials</div>
            <div class="req-detail">The same details as production (sections 01 and 02 above) for the sandbox environment. Must be separate accounts — do not reuse production credentials in the sandbox.</div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-required">Required</span>
          <div>
            <div class="req-label">Can test data be freely created and deleted in the sandbox?</div>
            <div class="req-detail">Integration testing will create dummy budget records, attempt failed postings, and run stress tests. Confirm the Sage admin is comfortable with this and that sandbox data cannot affect production.</div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-important">Important</span>
          <div>
            <div class="req-label">How and when is the sandbox refreshed from production?</div>
            <div class="req-detail">If the sandbox is periodically synced from production, coordinate refresh windows so test data is not unexpectedly wiped mid-development sprint.</div>
          </div>
        </div>
      </div>
    </div>
    <hr class="docs-divider">

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- 10. Support Contacts --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="doc-section" id="s10">
      <div class="doc-eyebrow">Requirement Area 10</div>
      <div class="doc-title">Support &amp; Escalation Contacts</div>
      <div class="doc-lead">
        Integration issues that cannot be resolved in GOIL Budget's codebase need a clear escalation path into the Sage environment.
      </div>

      <div class="req-list">
        <div class="req">
          <span class="pri pri-required">Required</span>
          <div>
            <div class="req-label">Sage administrator contact (internal GOIL staff)</div>
            <div class="req-detail">Name, email, and phone number of the GOIL staff member who administers Sage. First point of contact for access issues, permission changes, and period reopening.</div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-required">Required</span>
          <div>
            <div class="req-label">Sage partner / reseller contact</div>
            <div class="req-detail">If GOIL's Sage is managed by an external partner, provide their contact details and the support contract reference number. API issues often need the partner to raise a case with Sage directly.</div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-important">Important</span>
          <div>
            <div class="req-label">Sage support contract details</div>
            <div class="req-detail">Support contract number, support tier (business hours vs 24/7), and the Sage support portal URL — needed if an API bug must be escalated to Sage's own support team.</div>
          </div>
        </div>

        <div class="req">
          <span class="pri pri-important">Important</span>
          <div>
            <div class="req-label">Change management process for Sage environment changes</div>
            <div class="req-detail">What is the process for requesting changes such as opening a period, adding a GL code, or creating a cost centre? Is there a change request form, an approval chain, and an SLA? The GOIL Budget team needs to know lead times so integration timelines can be planned around them.</div>
          </div>
        </div>
      </div>
    </div>
    <hr class="docs-divider">

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- Handoff Summary --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="doc-section" id="handoff">
      <div class="doc-eyebrow">Reference</div>
      <div class="doc-title">Handoff Summary</div>
      <div class="doc-lead">Quick reference for the Sage admin — what to prepare and how to deliver it.</div>

      <div class="doc-table-wrap">
        <table class="doc-table">
          <thead>
            <tr>
              <th>Item to deliver</th>
              <th>Format</th>
              <th>Priority</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Sage product name, version &amp; deployment type</td>
              <td>Written confirmation</td>
              <td><span class="pri pri-critical">Critical</span></td>
            </tr>
            <tr>
              <td>Server / API base URL and company database name</td>
              <td>Written confirmation</td>
              <td><span class="pri pri-critical">Critical</span></td>
            </tr>
            <tr>
              <td>API module licence confirmation + integration method</td>
              <td>Written confirmation</td>
              <td><span class="pri pri-critical">Critical</span></td>
            </tr>
            <tr>
              <td>Service account username + secure credential share</td>
              <td>Password manager share</td>
              <td><span class="pri pri-critical">Critical</span></td>
            </tr>
            <tr>
              <td>Authentication details (API key / OAuth credentials)</td>
              <td>Password manager share</td>
              <td><span class="pri pri-critical">Critical</span></td>
            </tr>
            <tr>
              <td>Full chart of accounts export</td>
              <td>CSV / Excel</td>
              <td><span class="pri pri-critical">Critical</span></td>
            </tr>
            <tr>
              <td>GOIL → Sage account code mapping table</td>
              <td>CSV / Excel (Finance + Sage admin)</td>
              <td><span class="pri pri-critical">Critical</span></td>
            </tr>
            <tr>
              <td>Cost centre / department / dimension list</td>
              <td>CSV / Excel</td>
              <td><span class="pri pri-critical">Critical</span></td>
            </tr>
            <tr>
              <td>Fiscal year start date and full period list</td>
              <td>CSV / Excel or written</td>
              <td><span class="pri pri-critical">Critical</span></td>
            </tr>
            <tr>
              <td>Sandbox URL and credentials</td>
              <td>Password manager share</td>
              <td><span class="pri pri-critical">Critical</span></td>
            </tr>
            <tr>
              <td>Budget import format / field template</td>
              <td>Sample file + field spec</td>
              <td><span class="pri pri-required">Required</span></td>
            </tr>
            <tr>
              <td>Firewall / IP allowlist process + IT contact</td>
              <td>Written + contact details</td>
              <td><span class="pri pri-required">Required</span></td>
            </tr>
            <tr>
              <td>GL balance API endpoint or export method</td>
              <td>API docs or sample file</td>
              <td><span class="pri pri-required">Required</span></td>
            </tr>
            <tr>
              <td>Sage admin + partner contact details</td>
              <td>Contact sheet</td>
              <td><span class="pri pri-required">Required</span></td>
            </tr>
            <tr>
              <td>Rate limits, password rotation policy, change process SLA</td>
              <td>Written</td>
              <td><span class="pri pri-important">Important</span></td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="doc-tip">
        <strong>Where to start:</strong> Confirm the Sage product, version, and whether the API module is licensed.
        Everything else — connector library, authentication flow, field mapping — depends on that single answer.
        Get it in writing before any development begins.
      </div>
    </div>

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- Database Schema --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    <div class="doc-section" id="schema">
      <div class="doc-eyebrow">Reference</div>
      <div class="doc-title">Database Schema — Integration Tables</div>
      <div class="doc-lead">
        The eight tables the Sage team may read for a database-level integration. All other tables are internal and should not be shared. Fields marked <code class="sk">FK</code> in the comments are foreign keys — Sage only needs the value, not the referenced row.
      </div>

      <div class="doc-note" style="max-width:none">
        <strong>Read-only access only.</strong> These tables should be exposed via a dedicated read-only MySQL user. No INSERT, UPDATE, or DELETE should be granted. The recommended access method is a Sage ODBC connection or scheduled SQL SELECT pull, not direct application-level writes.
      </div>

      {{-- 1. account_categories ───────────────── --}}
      <div class="doc-sub">
        <div class="doc-sub-title">
          account_categories
          <span class="tb tb-master">Master Data</span>
        </div>
        <p>Groups of GL accounts (e.g. "Staff Costs", "Fuel Revenue"). Each account code belongs to one category. <code class="sk">budget_type</code> drives P&amp;L vs Balance Sheet classification.</p>
        <div class="sage-map"><strong>Sage mapping:</strong> Map <code>code</code> to your Sage GL account group / nominal code prefix. <code>budget_type</code> tells you whether to post on the revenue or expense side.</div>
        <div class="ddl-wrap">
<pre class="ddl"><span class="kw">CREATE TABLE</span> <span class="nm">account_categories</span> (
  <span class="nm">id</span>                      <span class="ty">BIGINT UNSIGNED</span>  <span class="kw">NOT NULL AUTO_INCREMENT</span>,
  <span class="nm">name</span>                    <span class="ty">VARCHAR(255)</span>     <span class="kw">NOT NULL</span>,             <span class="cmt">-- Display name e.g. "Staff Costs"</span>
  <span class="nm">code</span>                    <span class="ty">VARCHAR(255)</span>     <span class="kw">NOT NULL UNIQUE</span>,      <span class="cmt">-- Short code used in GL mapping</span>
  <span class="nm">description</span>             <span class="ty">TEXT</span>                     <span class="kw">NULL</span>,
  <span class="nm">default_rate</span>            <span class="ty">DECIMAL(15,4)</span>            <span class="kw">NULL</span>,             <span class="cmt">-- Default unit rate for budgeting</span>
  <span class="nm">default_frequency</span>       <span class="ty">DECIMAL(10,4)</span>            <span class="kw">NULL</span>,             <span class="cmt">-- Default occurrences per year</span>
  <span class="nm">is_active</span>               <span class="ty">TINYINT(1)</span>       <span class="kw">NOT NULL DEFAULT 1</span>,   <span class="cmt">-- 1=active 0=archived</span>
  <span class="nm">budget_type</span>             <span class="ty">ENUM</span>(<span class="str">'revenue'</span>,<span class="str">'expense'</span>,<span class="str">'both'</span>,
                              <span class="str">'capital_expenditure'</span>,<span class="str">'assets'</span>,
                              <span class="str">'liabilities'</span>,<span class="str">'ex_pump_item'</span>) <span class="kw">NOT NULL</span>,
  <span class="nm">account_sub_category_id</span> <span class="ty">BIGINT UNSIGNED</span>          <span class="kw">NULL</span>,             <span class="cmt">-- FK internal grouping</span>
  <span class="nm">created_at</span>              <span class="ty">TIMESTAMP</span>                <span class="kw">NULL</span>,
  <span class="nm">updated_at</span>              <span class="ty">TIMESTAMP</span>                <span class="kw">NULL</span>,
  <span class="kw">PRIMARY KEY</span> (<span class="nm">id</span>)
) <span class="kw">ENGINE</span>=InnoDB;</pre>
        </div>
      </div>

      {{-- 2. account_codes ───────────────────── --}}
      <div class="doc-sub">
        <div class="doc-sub-title">
          account_codes
          <span class="tb tb-master">Master Data</span>
        </div>
        <p>Individual GL line items (e.g. "Basic Salary", "Diesel Purchases"). Each code belongs to a category and carries its own calculation rules.</p>
        <div class="sage-map"><strong>Sage mapping:</strong> <code>code</code> is the <strong>primary join key</strong> to Sage nominal / GL codes. <code>calc_type</code> explains how GOIL derives the budget amount (flat / rate×qty / rate×qty×freq). <code>unit</code> names the quantity unit (litres, kwh, etc.).</div>
        <div class="ddl-wrap">
<pre class="ddl"><span class="kw">CREATE TABLE</span> <span class="nm">account_codes</span> (
  <span class="nm">id</span>                  <span class="ty">BIGINT UNSIGNED</span>  <span class="kw">NOT NULL AUTO_INCREMENT</span>,
  <span class="nm">account_category_id</span> <span class="ty">BIGINT UNSIGNED</span>  <span class="kw">NOT NULL</span>,              <span class="cmt">-- FK → account_categories.id</span>
  <span class="nm">code</span>                <span class="ty">VARCHAR(255)</span>     <span class="kw">NOT NULL UNIQUE</span>,       <span class="cmt">-- GL code — PRIMARY join to Sage</span>
  <span class="nm">name</span>                <span class="ty">VARCHAR(255)</span>     <span class="kw">NOT NULL</span>,              <span class="cmt">-- e.g. "Diesel Purchases"</span>
  <span class="nm">description</span>         <span class="ty">TEXT</span>                     <span class="kw">NULL</span>,
  <span class="nm">unit</span>                <span class="ty">VARCHAR(50)</span>              <span class="kw">NULL</span>,              <span class="cmt">-- Unit of measure: litres, kwh…</span>
  <span class="nm">calc_type</span>           <span class="ty">VARCHAR(20)</span>      <span class="kw">NOT NULL</span>,              <span class="cmt">-- 'flat'|'rate_qty'|'rate_qty_freq'</span>
  <span class="nm">calc_config</span>         <span class="ty">JSON</span>                     <span class="kw">NULL</span>,              <span class="cmt">-- Calculation parameters</span>
  <span class="nm">sort_order</span>          <span class="ty">SMALLINT</span>         <span class="kw">NOT NULL DEFAULT 0</span>,
  <span class="nm">default_rate</span>        <span class="ty">DECIMAL(15,4)</span>            <span class="kw">NULL</span>,
  <span class="nm">default_frequency</span>   <span class="ty">DECIMAL(10,4)</span>            <span class="kw">NULL</span>,
  <span class="nm">is_active</span>           <span class="ty">TINYINT(1)</span>       <span class="kw">NOT NULL DEFAULT 1</span>,
  <span class="nm">deleted_at</span>          <span class="ty">TIMESTAMP</span>                <span class="kw">NULL</span>,              <span class="cmt">-- Soft-delete; NULL=active</span>
  <span class="nm">created_at</span>          <span class="ty">TIMESTAMP</span>                <span class="kw">NULL</span>,
  <span class="nm">updated_at</span>          <span class="ty">TIMESTAMP</span>                <span class="kw">NULL</span>,
  <span class="kw">PRIMARY KEY</span> (<span class="nm">id</span>)
) <span class="kw">ENGINE</span>=InnoDB;</pre>
        </div>
      </div>

      {{-- 3. budget_periods ─────────────────── --}}
      <div class="doc-sub">
        <div class="doc-sub-title">
          budget_periods
          <span class="tb tb-lookup">Reference</span>
        </div>
        <p>One row per financial year (e.g. "FY 2026"). All budget and actuals data ties back to a period.</p>
        <div class="sage-map"><strong>Sage mapping:</strong> Match <code>year</code> + <code>start_date</code> / <code>end_date</code> to a Sage financial year. Only periods with <code>status = 'approved'</code> or <code>'closed'</code> hold finalised data suitable for posting to Sage GL.</div>
        <div class="ddl-wrap">
<pre class="ddl"><span class="kw">CREATE TABLE</span> <span class="nm">budget_periods</span> (
  <span class="nm">id</span>         <span class="ty">BIGINT UNSIGNED</span>                                    <span class="kw">NOT NULL AUTO_INCREMENT</span>,
  <span class="nm">name</span>       <span class="ty">VARCHAR(255)</span>                                       <span class="kw">NOT NULL</span>,  <span class="cmt">-- e.g. "FY 2026"</span>
  <span class="nm">year</span>       <span class="ty">YEAR</span>                                               <span class="kw">NOT NULL</span>,  <span class="cmt">-- 4-digit year</span>
  <span class="nm">start_date</span> <span class="ty">DATE</span>                                               <span class="kw">NOT NULL</span>,
  <span class="nm">end_date</span>   <span class="ty">DATE</span>                                               <span class="kw">NOT NULL</span>,
  <span class="nm">status</span>     <span class="ty">ENUM</span>(<span class="str">'draft'</span>,<span class="str">'open'</span>,<span class="str">'closed'</span>,<span class="str">'approved'</span>)          <span class="kw">NOT NULL</span>,  <span class="cmt">-- Lifecycle state</span>
  <span class="nm">opened_at</span>  <span class="ty">TIMESTAMP</span>                                                  <span class="kw">NULL</span>,
  <span class="nm">closed_at</span>  <span class="ty">TIMESTAMP</span>                                                  <span class="kw">NULL</span>,
  <span class="nm">entry_mode</span> <span class="ty">VARCHAR(10)</span>                                        <span class="kw">NOT NULL</span>,  <span class="cmt">-- 'monthly' or 'annual'</span>
  <span class="nm">created_at</span> <span class="ty">TIMESTAMP</span>                                                  <span class="kw">NULL</span>,
  <span class="nm">updated_at</span> <span class="ty">TIMESTAMP</span>                                                  <span class="kw">NULL</span>,
  <span class="kw">PRIMARY KEY</span> (<span class="nm">id</span>)
) <span class="kw">ENGINE</span>=InnoDB;</pre>
        </div>
      </div>

      {{-- 4. departments ────────────────────── --}}
      <div class="doc-sub">
        <div class="doc-sub-title">
          departments
          <span class="tb tb-master">Master Data</span>
        </div>
        <p>Cost centres and head-office departments. Each department owns its own budget version.</p>
        <div class="sage-map"><strong>Sage mapping:</strong> <code>code</code> maps to Sage cost centre / department code. <code>entity_type</code> differentiates head-office departments (<code>'department'</code>) from field entities (<code>'subsidiary'</code>). Filter <code>is_active = 1</code> and <code>deleted_at IS NULL</code> for the active list.</div>
        <div class="ddl-wrap">
<pre class="ddl"><span class="kw">CREATE TABLE</span> <span class="nm">departments</span> (
  <span class="nm">id</span>          <span class="ty">BIGINT UNSIGNED</span>                              <span class="kw">NOT NULL AUTO_INCREMENT</span>,
  <span class="nm">zone_id</span>     <span class="ty">BIGINT UNSIGNED</span>                                      <span class="kw">NULL</span>,  <span class="cmt">-- FK → zones.id</span>
  <span class="nm">name</span>        <span class="ty">VARCHAR(255)</span>                                 <span class="kw">NOT NULL</span>,
  <span class="nm">code</span>        <span class="ty">VARCHAR(255)</span>                                 <span class="kw">NOT NULL UNIQUE</span>,  <span class="cmt">-- Cost centre code → Sage</span>
  <span class="nm">description</span> <span class="ty">TEXT</span>                                                 <span class="kw">NULL</span>,
  <span class="nm">is_active</span>   <span class="ty">TINYINT(1)</span>                                   <span class="kw">NOT NULL DEFAULT 1</span>,
  <span class="nm">budget_type</span> <span class="ty">ENUM</span>(<span class="str">'revenue'</span>,<span class="str">'expense'</span>,<span class="str">'both'</span>)             <span class="kw">NOT NULL</span>,
  <span class="nm">entity_type</span> <span class="ty">VARCHAR(20)</span>                                  <span class="kw">NOT NULL</span>,  <span class="cmt">-- 'department' or 'subsidiary'</span>
  <span class="nm">deleted_at</span>  <span class="ty">TIMESTAMP</span>                                            <span class="kw">NULL</span>,  <span class="cmt">-- Soft-delete</span>
  <span class="nm">created_at</span>  <span class="ty">TIMESTAMP</span>                                            <span class="kw">NULL</span>,
  <span class="nm">updated_at</span>  <span class="ty">TIMESTAMP</span>                                            <span class="kw">NULL</span>,
  <span class="kw">PRIMARY KEY</span> (<span class="nm">id</span>)
) <span class="kw">ENGINE</span>=InnoDB;</pre>
        </div>
      </div>

      {{-- 5. subsidiaries ───────────────────── --}}
      <div class="doc-sub">
        <div class="doc-sub-title">
          subsidiaries
          <span class="tb tb-master">Master Data</span>
        </div>
        <p>Service stations and subsidiary entities. A budget version can belong to a subsidiary instead of (or in addition to) a department.</p>
        <div class="sage-map"><strong>Sage mapping:</strong> <code>code</code> is the station / subsidiary identifier — match to Sage branch or project codes. <code>subsidiary_category_id</code> groups stations by type (e.g. branded, dealer). Filter <code>is_active = 1</code> for active stations.</div>
        <div class="ddl-wrap">
<pre class="ddl"><span class="kw">CREATE TABLE</span> <span class="nm">subsidiaries</span> (
  <span class="nm">id</span>                     <span class="ty">BIGINT UNSIGNED</span>  <span class="kw">NOT NULL AUTO_INCREMENT</span>,
  <span class="nm">subsidiary_category_id</span> <span class="ty">BIGINT UNSIGNED</span>  <span class="kw">NOT NULL</span>,  <span class="cmt">-- FK → subsidiary_categories.id (station type)</span>
  <span class="nm">name</span>                   <span class="ty">VARCHAR(255)</span>     <span class="kw">NOT NULL</span>,  <span class="cmt">-- Full station / entity name</span>
  <span class="nm">code</span>                   <span class="ty">VARCHAR(20)</span>      <span class="kw">NOT NULL UNIQUE</span>,  <span class="cmt">-- Station code → Sage branch/project</span>
  <span class="nm">description</span>            <span class="ty">TEXT</span>                     <span class="kw">NULL</span>,
  <span class="nm">is_active</span>              <span class="ty">TINYINT(1)</span>       <span class="kw">NOT NULL DEFAULT 1</span>,
  <span class="nm">sort_order</span>             <span class="ty">INT UNSIGNED</span>     <span class="kw">NOT NULL DEFAULT 0</span>,
  <span class="nm">created_at</span>             <span class="ty">TIMESTAMP</span>                <span class="kw">NULL</span>,
  <span class="nm">updated_at</span>             <span class="ty">TIMESTAMP</span>                <span class="kw">NULL</span>,
  <span class="kw">PRIMARY KEY</span> (<span class="nm">id</span>)
) <span class="kw">ENGINE</span>=InnoDB;</pre>
        </div>
      </div>

      {{-- 6. budget_versions ────────────────── --}}
      <div class="doc-sub">
        <div class="doc-sub-title">
          budget_versions
          <span class="tb tb-finance">Finance</span>
        </div>
        <p>One row per submitted budget for a department or station within a period. Tracks the full approval workflow. Revisions create a new row with <code class="sk">is_revision = 1</code>.</p>
        <div class="sage-map"><strong>Sage mapping:</strong> Only versions with <code>status = 'approved'</code> are final. Join to <code>budget_line_items</code> on <code>id</code> to get the approved figures. <code>version_number &gt; 1</code> indicates a supplementary or revised budget.</div>
        <div class="ddl-wrap">
<pre class="ddl"><span class="kw">CREATE TABLE</span> <span class="nm">budget_versions</span> (
  <span class="nm">id</span>               <span class="ty">BIGINT UNSIGNED</span>                                     <span class="kw">NOT NULL AUTO_INCREMENT</span>,
  <span class="nm">budget_period_id</span> <span class="ty">BIGINT UNSIGNED</span>                                     <span class="kw">NOT NULL</span>,  <span class="cmt">-- FK → budget_periods.id</span>
  <span class="nm">department_id</span>    <span class="ty">BIGINT UNSIGNED</span>                                             <span class="kw">NULL</span>,  <span class="cmt">-- FK → departments.id (dept budget)</span>
  <span class="nm">subsidiary_id</span>    <span class="ty">BIGINT UNSIGNED</span>                                             <span class="kw">NULL</span>,  <span class="cmt">-- FK → subsidiaries.id (station budget)</span>
  <span class="nm">version_number</span>   <span class="ty">TINYINT UNSIGNED</span>                                    <span class="kw">NOT NULL</span>,  <span class="cmt">-- 1=original; 2+=supplementary</span>
  <span class="nm">is_revision</span>      <span class="ty">TINYINT(1)</span>                                          <span class="kw">NOT NULL</span>,  <span class="cmt">-- 1 if revision of earlier version</span>
  <span class="nm">revised_from_id</span>  <span class="ty">BIGINT UNSIGNED</span>                                             <span class="kw">NULL</span>,  <span class="cmt">-- FK → budget_versions.id (parent)</span>
  <span class="nm">status</span>           <span class="ty">ENUM</span>(<span class="str">'draft'</span>,<span class="str">'submitted'</span>,<span class="str">'under_review'</span>,
                        <span class="str">'approved'</span>,<span class="str">'rejected'</span>)                     <span class="kw">NOT NULL</span>,  <span class="cmt">-- Workflow state</span>
  <span class="nm">submitted_at</span>     <span class="ty">TIMESTAMP</span>                                                   <span class="kw">NULL</span>,
  <span class="nm">created_at</span>       <span class="ty">TIMESTAMP</span>                                                   <span class="kw">NULL</span>,
  <span class="nm">updated_at</span>       <span class="ty">TIMESTAMP</span>                                                   <span class="kw">NULL</span>,
  <span class="kw">PRIMARY KEY</span> (<span class="nm">id</span>)
) <span class="kw">ENGINE</span>=InnoDB;</pre>
        </div>
      </div>

      {{-- 7. budget_line_items ──────────────── --}}
      <div class="doc-sub">
        <div class="doc-sub-title">
          budget_line_items
          <span class="tb tb-finance">Finance</span>
        </div>
        <p>The approved budget amounts — one row per GL code per budget version. Columns <code class="sk">m1_amount</code> … <code class="sk">m12_amount</code> hold Jan–Dec figures. <code class="sk">total_amount</code> is the sum of all 12 months.</p>
        <div class="sage-map"><strong>Sage mapping:</strong> This is the core budget feed. Join to <code>budget_versions</code> (filter <code>status='approved'</code>), then to <code>account_codes</code> on <code>account_code_id</code> to get the Sage GL code. Monthly columns correspond to calendar months within the period's year.</div>
        <div class="ddl-wrap">
<pre class="ddl"><span class="kw">CREATE TABLE</span> <span class="nm">budget_line_items</span> (
  <span class="nm">id</span>                <span class="ty">BIGINT UNSIGNED</span>   <span class="kw">NOT NULL AUTO_INCREMENT</span>,
  <span class="nm">budget_version_id</span> <span class="ty">BIGINT UNSIGNED</span>   <span class="kw">NOT NULL</span>,    <span class="cmt">-- FK → budget_versions.id</span>
  <span class="nm">account_code_id</span>   <span class="ty">BIGINT UNSIGNED</span>   <span class="kw">NOT NULL</span>,    <span class="cmt">-- FK → account_codes.id (GL code)</span>
  <span class="nm">m1_amount</span>         <span class="ty">DECIMAL(15,2)</span>     <span class="kw">NOT NULL DEFAULT 0</span>,  <span class="cmt">-- January budget (GHS)</span>
  <span class="nm">m2_amount</span>         <span class="ty">DECIMAL(15,2)</span>     <span class="kw">NOT NULL DEFAULT 0</span>,  <span class="cmt">-- February</span>
  <span class="nm">m3_amount</span>         <span class="ty">DECIMAL(15,2)</span>     <span class="kw">NOT NULL DEFAULT 0</span>,  <span class="cmt">-- March</span>
  <span class="nm">m4_amount</span>         <span class="ty">DECIMAL(15,2)</span>     <span class="kw">NOT NULL DEFAULT 0</span>,  <span class="cmt">-- April</span>
  <span class="nm">m5_amount</span>         <span class="ty">DECIMAL(15,2)</span>     <span class="kw">NOT NULL DEFAULT 0</span>,  <span class="cmt">-- May</span>
  <span class="nm">m6_amount</span>         <span class="ty">DECIMAL(15,2)</span>     <span class="kw">NOT NULL DEFAULT 0</span>,  <span class="cmt">-- June</span>
  <span class="nm">m7_amount</span>         <span class="ty">DECIMAL(15,2)</span>     <span class="kw">NOT NULL DEFAULT 0</span>,  <span class="cmt">-- July</span>
  <span class="nm">m8_amount</span>         <span class="ty">DECIMAL(15,2)</span>     <span class="kw">NOT NULL DEFAULT 0</span>,  <span class="cmt">-- August</span>
  <span class="nm">m9_amount</span>         <span class="ty">DECIMAL(15,2)</span>     <span class="kw">NOT NULL DEFAULT 0</span>,  <span class="cmt">-- September</span>
  <span class="nm">m10_amount</span>        <span class="ty">DECIMAL(15,2)</span>     <span class="kw">NOT NULL DEFAULT 0</span>,  <span class="cmt">-- October</span>
  <span class="nm">m11_amount</span>        <span class="ty">DECIMAL(15,2)</span>     <span class="kw">NOT NULL DEFAULT 0</span>,  <span class="cmt">-- November</span>
  <span class="nm">m12_amount</span>        <span class="ty">DECIMAL(15,2)</span>     <span class="kw">NOT NULL DEFAULT 0</span>,  <span class="cmt">-- December</span>
  <span class="nm">quantity</span>          <span class="ty">DECIMAL(15,4)</span>             <span class="kw">NULL</span>,        <span class="cmt">-- Input qty (e.g. litres, trips)</span>
  <span class="nm">rate</span>              <span class="ty">DECIMAL(15,4)</span>             <span class="kw">NULL</span>,        <span class="cmt">-- Unit rate (e.g. price per litre)</span>
  <span class="nm">frequency</span>         <span class="ty">DECIMAL(10,4)</span>             <span class="kw">NULL</span>,        <span class="cmt">-- Occurrences per month</span>
  <span class="nm">justification</span>     <span class="ty">TEXT</span>                      <span class="kw">NULL</span>,        <span class="cmt">-- Budget owner narrative</span>
  <span class="nm">line_type</span>         <span class="ty">ENUM</span>(<span class="str">'revenue'</span>,<span class="str">'expense'</span>,
                         <span class="str">'capex'</span>,<span class="str">'asset'</span>,<span class="str">'liability'</span>) <span class="kw">NOT NULL</span>,
  <span class="nm">total_amount</span>      <span class="ty">DECIMAL(15,2)</span>             <span class="kw">NULL</span>,        <span class="cmt">-- Sum of m1…m12 (denormalised)</span>
  <span class="nm">created_at</span>        <span class="ty">TIMESTAMP</span>                 <span class="kw">NULL</span>,
  <span class="nm">updated_at</span>        <span class="ty">TIMESTAMP</span>                 <span class="kw">NULL</span>,
  <span class="kw">PRIMARY KEY</span> (<span class="nm">id</span>)
) <span class="kw">ENGINE</span>=InnoDB;</pre>
        </div>
      </div>

      {{-- 8. budget_actuals ─────────────────── --}}
      <div class="doc-sub">
        <div class="doc-sub-title">
          budget_actuals
          <span class="tb tb-transact">Transactional</span>
        </div>
        <p>Actual expenditure and revenue recorded month by month against each budget line. The <code class="sk">reference</code> column is intended for the source document number (Sage journal or invoice reference).</p>
        <div class="sage-map"><strong>Sage mapping:</strong> Each row = one GL posting for a given month. Only rows with <code>status = 'confirmed'</code> are finalised. Join to <code>account_codes</code> via <code>account_code_id</code> to get the GL code. Use <code>reference</code> to link back to the originating Sage journal entry.</div>
        <div class="ddl-wrap">
<pre class="ddl"><span class="kw">CREATE TABLE</span> <span class="nm">budget_actuals</span> (
  <span class="nm">id</span>                  <span class="ty">BIGINT UNSIGNED</span>                                     <span class="kw">NOT NULL AUTO_INCREMENT</span>,
  <span class="nm">budget_line_item_id</span> <span class="ty">BIGINT UNSIGNED</span>                                     <span class="kw">NOT NULL</span>,  <span class="cmt">-- FK → budget_line_items.id</span>
  <span class="nm">budget_period_id</span>    <span class="ty">BIGINT UNSIGNED</span>                                     <span class="kw">NOT NULL</span>,  <span class="cmt">-- FK → budget_periods.id</span>
  <span class="nm">department_id</span>       <span class="ty">BIGINT UNSIGNED</span>                                             <span class="kw">NULL</span>,  <span class="cmt">-- FK → departments.id (if dept)</span>
  <span class="nm">subsidiary_id</span>       <span class="ty">BIGINT UNSIGNED</span>                                             <span class="kw">NULL</span>,  <span class="cmt">-- FK → subsidiaries.id (if station)</span>
  <span class="nm">account_code_id</span>     <span class="ty">BIGINT UNSIGNED</span>                                     <span class="kw">NOT NULL</span>,  <span class="cmt">-- FK → account_codes.id (GL code)</span>
  <span class="nm">month</span>               <span class="ty">TINYINT UNSIGNED</span>                                    <span class="kw">NOT NULL</span>,  <span class="cmt">-- 1=Jan … 12=Dec</span>
  <span class="nm">year</span>                <span class="ty">YEAR</span>                                                <span class="kw">NOT NULL</span>,
  <span class="nm">amount</span>              <span class="ty">DECIMAL(15,2)</span>                                       <span class="kw">NOT NULL</span>,  <span class="cmt">-- Actual amount (GHS)</span>
  <span class="nm">description</span>         <span class="ty">TEXT</span>                                                        <span class="kw">NULL</span>,  <span class="cmt">-- Narrative / memo</span>
  <span class="nm">reference</span>           <span class="ty">VARCHAR(255)</span>                                                <span class="kw">NULL</span>,  <span class="cmt">-- Source doc ref (Sage journal #)</span>
  <span class="nm">status</span>              <span class="ty">ENUM</span>(<span class="str">'draft'</span>,<span class="str">'submitted'</span>,
                           <span class="str">'head_confirmed'</span>,<span class="str">'confirmed'</span>)              <span class="kw">NOT NULL</span>,  <span class="cmt">-- 'confirmed'=finalised</span>
  <span class="nm">created_at</span>          <span class="ty">TIMESTAMP</span>                                                   <span class="kw">NULL</span>,
  <span class="nm">updated_at</span>          <span class="ty">TIMESTAMP</span>                                                   <span class="kw">NULL</span>,
  <span class="kw">PRIMARY KEY</span> (<span class="nm">id</span>)
) <span class="kw">ENGINE</span>=InnoDB;</pre>
        </div>
      </div>

      {{-- Sample query ─────────────────────── --}}
      <div class="doc-sub">
        <div class="doc-sub-title">Sample query — approved budget by GL code</div>
        <p>A query Sage can run via ODBC to pull the full approved budget for a given year across all departments and stations.</p>
        <div class="ddl-wrap">
<pre class="ddl"><span class="kw">SELECT</span>
    bp.year,
    d.code        <span class="kw">AS</span> cost_centre_code,
    d.name        <span class="kw">AS</span> cost_centre_name,
    ac.code       <span class="kw">AS</span> gl_code,
    ac.name       <span class="kw">AS</span> gl_description,
    ac.budget_type,
    bli.line_type,
    bli.m1_amount,  bli.m2_amount,  bli.m3_amount,
    bli.m4_amount,  bli.m5_amount,  bli.m6_amount,
    bli.m7_amount,  bli.m8_amount,  bli.m9_amount,
    bli.m10_amount, bli.m11_amount, bli.m12_amount,
    bli.total_amount
<span class="kw">FROM</span>      budget_line_items  bli
<span class="kw">JOIN</span>      budget_versions    bv  <span class="kw">ON</span> bv.id = bli.budget_version_id
<span class="kw">JOIN</span>      budget_periods     bp  <span class="kw">ON</span> bp.id = bv.budget_period_id
<span class="kw">JOIN</span>      account_codes      ac  <span class="kw">ON</span> ac.id = bli.account_code_id
<span class="kw">LEFT JOIN</span> departments        d   <span class="kw">ON</span> d.id  = bv.department_id
<span class="kw">LEFT JOIN</span> subsidiaries       s   <span class="kw">ON</span> s.id  = bv.subsidiary_id
<span class="kw">WHERE</span>
    bp.year    = <span class="str">2026</span>
    <span class="kw">AND</span> bv.status  = <span class="str">'approved'</span>
    <span class="kw">AND</span> ac.deleted_at <span class="kw">IS NULL</span>
<span class="kw">ORDER BY</span>
    cost_centre_code, ac.sort_order, gl_code;</pre>
        </div>
        <div class="doc-tip" style="max-width:none">
          The <code>department_id</code> and <code>subsidiary_id</code> columns on <code>budget_versions</code> are mutually exclusive — a version belongs to either a department or a station, not both. The <code>LEFT JOIN</code> above handles this; use <code>COALESCE(d.code, s.code)</code> if you want a single cost-centre column.
        </div>
      </div>

    </div>{{-- end #schema --}}

  </div>{{-- end .docs-content --}}
</div>{{-- end .docs-wrap --}}
@endsection

@push('scripts')
<script>
// Highlight active inner nav link on scroll
const sections = document.querySelectorAll('.doc-section[id], .docs-hero[id]');
const navLinks  = document.querySelectorAll('#docsNav a');

const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            navLinks.forEach(l => l.classList.remove('active'));
            const hit = document.querySelector('#docsNav a[href="#' + entry.target.id + '"]');
            if (hit) hit.classList.add('active');
        }
    });
}, { threshold: 0.25 });

sections.forEach(s => observer.observe(s));
</script>
@endpush
