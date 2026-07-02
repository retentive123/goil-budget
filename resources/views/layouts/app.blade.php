<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ \App\Models\SystemSetting::get('app_name', 'GOIL Budget') }} — @yield('title')</title>

    <link rel="icon" type="image/png" href="favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="favicon/favicon.svg" />
    <link rel="shortcut icon" href="favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="favicon/apple-touch-icon.png" />
    <link rel="manifest" href="favicon/site.webmanifest" />

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    {{-- Font Awesome CDN --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --navy:   #E65C00;
            --gold:   #fff;
            --gold-light: #F0DFA0;
            --slate:  #4A4A4A;
            --emerald:#10B981;
            --rose:   #F43F5E;
            --surface:#F8FAFC;
            --border: #E2E8F0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: var(--surface);
            font-family: 'Inter', system-ui, sans-serif;
            font-size: 14px;
            color: #1E293B;
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
        }



        /* ── Sidebar ── */
        #sidebar {
            width: 260px;
            height: 100vh;
            background: var(--navy);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease, width 0.3s ease;
            overflow-x: hidden;
            overflow-y: hidden;
        }

        /* Collapsed state */
        #sidebar.collapsed {
            width: 0;
            overflow: hidden;
        }

        .sidebar-brand {
            padding: 20px 20px 16px;
            border-bottom: 1px solid rgba(255,255,255,.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .sidebar-brand .brand-wrapper {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .sidebar-brand .brand-name {
            font-size: 20px;
            font-weight: 700;
            color: #fff;
            letter-spacing: -.3px;
        }

        .sidebar-brand .brand-sub {
            font-size: 10px;
            color: var(--gold);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-top: 2px;
        }

        .sidebar-brand .close-btn {
            background: rgba(255,255,255,.1);
            border: none;
            color: #fff;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
            flex-shrink: 0;
        }

        .sidebar-brand .close-btn:hover {
            background: rgba(255,255,255,.2);
        }

        /* ── Floating toggle button (appears when sidebar is collapsed) ── */
        #sidebarToggle {
            position: fixed;
            top: 12px;
            left: 12px;
            z-index: 1001;
            background: var(--navy);
            color: #fff;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            font-size: 18px;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(27, 42, 74, 0.3);
            transition: all 0.3s ease;
        }

        #sidebarToggle:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 16px rgba(27, 42, 74, 0.4);
        }

        #sidebarToggle.visible {
            display: flex;
        }

        .sidebar-nav {
            padding: 12px 0;
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .nav-section-label {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: rgba(9, 1, 1, 0.873);
            padding: 12px 20px 6px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            color: rgba(255,255,255,.65);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 450;
            border-left: 3px solid transparent;
            transition: all 0.15s;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }

        .sidebar-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.06);
        }

        .sidebar-link.active {
            color: #fff;
            background: rgba(201,168,76,.12);
            border-left-color: var(--gold);
            font-weight: 600;
        }

        .sidebar-link .nav-icon {
            font-size: 16px;
            width: 22px;
            text-align: center;
            opacity: .8;
            flex-shrink: 0;
        }

        .sidebar-link .link-text {
            flex: 1;
        }

        .sidebar-link .chevron-icon {
            font-size: 12px;
            transition: transform 0.3s ease;
            margin-left: auto;
        }

        .sidebar-link .chevron-icon.rotated {
            transform: rotate(180deg);
        }

        .nav-badge {
            margin-left: auto;
            background: var(--rose);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 12px;
            min-width: 20px;
            text-align: center;
        }

        /* Collapsible sub-menu */
        .sidebar-submenu {
            background: rgba(0,0,0,.15);
            padding: 4px 0;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .sidebar-submenu.open {
            max-height: 600px;
        }

        .sidebar-submenu .sidebar-link {
            padding-left: 52px;
            font-size: 13px;
        }

        /* ── Topbar ── */
        #topbar {
            position: fixed;
            top: 0;
            left: 260px;
            right: 0;
            height: 60px;
            background: #fff;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            z-index: 999;
            box-shadow: 0 1px 3px rgba(0,0,0,.04);
            transition: left 0.3s ease;
        }

        #topbar.expanded {
            left: 0;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .hamburger-btn {
            background: none;
            border: none;
            font-size: 22px;
            color: var(--navy);
            cursor: pointer;
            padding: 4px 8px;
            display: none;
        }

        .topbar-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--navy);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .notif-btn {
            position: relative;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 20px;
            color: var(--slate);
            padding: 4px 8px;
        }

        .notif-dot {
            position: absolute;
            top: 2px;
            right: 4px;
            width: 8px;
            height: 8px;
            background: var(--rose);
            border-radius: 50%;
            border: 2px solid #fff;
        }

        .user-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 14px 6px 10px;
            border-radius: 8px;
            cursor: pointer;
            border: 1px solid var(--border);
            background: #fff;
            font-size: 13px;
            font-weight: 500;
            color: var(--navy);
            text-decoration: none;
            transition: all 0.2s;
        }

        .user-pill:hover {
            background: var(--surface);
            border-color: #cbd5e1;
        }

        .user-avatar {
            width: 30px;
            height: 30px;
            background: var(--navy);
            color: var(--gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            flex-shrink: 0;
        }

        /* ── Main content ── */
#main {
    margin-left: 260px;
    margin-top: 60px;
    padding: 28px;
    height: calc(100vh - 60px);
    overflow-y: auto;
    transition: margin-left 0.3s ease;
}

#main.expanded {
    margin-left: 0;
}

/* ── Scrollbar for main content ── */
#main::-webkit-scrollbar {
    width: 6px;
}

#main::-webkit-scrollbar-track {
    background: #F1F5F9;
    border-radius: 4px;
}

#main::-webkit-scrollbar-thumb {
    background: #CBD5E1;
    border-radius: 4px;
}

#main::-webkit-scrollbar-thumb:hover {
    background: #94A3B8;
}
        /* ── Cards ── */
        .stat-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        .stat-card .stat-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: var(--slate);
            margin-bottom: 6px;
        }

        .stat-card .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--navy);
            line-height: 1;
        }

        .stat-card .stat-sub {
            font-size: 12px;
            color: var(--slate);
            margin-top: 6px;
        }

        .stat-card .stat-accent {
            position: absolute;
            top: 0;
            right: 0;
            width: 4px;
            height: 100%;
            border-radius: 0 12px 12px 0;
        }

        .chart-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
        }

        .chart-card .chart-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--navy);
            margin-bottom: 16px;
        }

        /* ── Health bar ── */
        .health-bar-wrap {
            background: var(--navy);
            border-radius: 14px;
            padding: 20px 24px;
            color: #fff;
            margin-bottom: 24px;
        }

        .health-bar-title {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--gold);
            margin-bottom: 12px;
        }

        .health-segments {
            display: flex;
            height: 12px;
            border-radius: 6px;
            overflow: hidden;
            gap: 2px;
            margin-bottom: 10px;
        }

        .health-segment {
            height: 100%;
            border-radius: 3px;
            transition: width .6s ease;
        }

        .health-legend {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .health-legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: rgba(255,255,255,.75);
        }

        .health-legend-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        /* ── Dept status table ── */
        .dept-row {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
            gap: 12px;
        }

        .dept-row:last-child { border-bottom: none; }

        .dept-code {
            width: 44px;
            height: 44px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
            color: var(--navy);
            flex-shrink: 0;
        }

        .dept-name { font-weight: 500; font-size: 13px; }
        .dept-meta { font-size: 11px; color: var(--slate); }

        .status-pill {
            margin-left: auto;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            flex-shrink: 0;
        }

        .pill-approved    { background: #D1FAE5; color: #065F46; }
        .pill-submitted   { background: #DBEAFE; color: #1E40AF; }
        .pill-under_review{ background: #FEF3C7; color: #92400E; }
        .pill-rejected    { background: #FEE2E2; color: #991B1B; }
        .pill-draft       { background: #F1F5F9; color: #475569; }
        .pill-not_started { background: #F8FAFC; color: #94A3B8; border: 1px solid var(--border); }

        /* ── My budget dept view ── */
        .quarter-pill {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px;
            text-align: center;
        }

        .quarter-pill .q-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: var(--slate);
        }

        .quarter-pill .q-value {
            font-size: 16px;
            font-weight: 700;
            color: var(--navy);
            margin-top: 4px;
        }


        .bg-goil-orange {
            background: #E65C00;
            color: white;
        }

        .bg-goil-orange .text-muted {
            color: rgba(255, 255, 255, 0.7) !important;
        }

        .bg-goil-orange .btn-outline-light:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .bg-goil-orange .border-bottom {
            border-color: rgba(255, 255, 255, 0.15) !important;
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            #sidebar {
                transform: translateX(-100%);
                width: 280px;
            }

            #sidebar.mobile-open {
                transform: translateX(0);
            }

            #sidebar.collapsed {
                width: 280px;
                transform: translateX(-100%);
            }

            #topbar {
                left: 0;
            }

            #main {
                margin-left: 0;
            }

            .hamburger-btn {
                display: block;
            }

            #sidebar .close-btn {
                display: flex;
            }

            .sidebar-brand .close-btn.desktop-only {
                display: none;
            }

            #sidebarToggle {
                display: none !important;
            }
        }

        @media (min-width: 769px) {
            #sidebar .close-btn.mobile-only {
                display: none;
            }

            .sidebar-brand .close-btn.desktop-only {
                display: flex;
            }
        }

        /* Overlay for mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        .sidebar-overlay.active {
            display: block;
        }
    </style>
</head>
<body>

{{-- Overlay for mobile --}}
<div class="sidebar-overlay" id="sidebarOverlay"></div>

{{-- Floating toggle button (appears when sidebar is collapsed) --}}
<button id="sidebarToggle" title="Toggle Sidebar">
    <i class="fas fa-chevron-right"></i>
</button>

{{-- ── Sidebar ── --}}
<nav id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-wrapper">
            <div class="brand-name">{{ \App\Models\SystemSetting::get('app_name', 'GOIL Budget') }}</div>
            <div class="brand-sub">Budget Management</div>
        </div>
        <button class="close-btn mobile-only" id="closeSidebar">
            <i class="fas fa-times"></i>
        </button>
        <button class="close-btn desktop-only" id="collapseSidebar">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>

    <div class="sidebar-nav">

        <div class="nav-section-label">Main</div>

        <a href="{{ route('dashboard') }}"
           class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="fas fa-th-large nav-icon"></i>
            <span class="link-text">Dashboard</span>
        </a>

        @can('view all budgets')
        <a href="{{ route('budgets.index') }}"
        class="sidebar-link {{ request()->routeIs('budgets.*') ? 'active' : '' }}">
            <i class="fa-solid fa-briefcase nav-icon"></i>
            <span class="link-text">All Budgets</span>
            @php
                $pendingCount = \App\Models\BudgetVersion::whereIn('status',['submitted','under_review'])->count();
            @endphp
            @if($pendingCount)
                <span class="nav-badge">{{ $pendingCount }}</span>
            @endif
        </a>
        @endcan

        @can('create budget')
        <a href="{{ route('budget.index') }}"
           class="sidebar-link {{ request()->routeIs('budget.*') ? 'active' : '' }}">
            <i class="fas fa-file-invoice nav-icon"></i>
            <span class="link-text">My Budget</span>
        </a>
        @endcan

        @can('approve budget')
        <a href="{{ route('approvals.index') }}"
           class="sidebar-link {{ request()->routeIs('approvals.*') ? 'active' : '' }}">
            <i class="fas fa-check-circle nav-icon"></i>
            <span class="link-text">Approvals</span>
            @php $pendingCount = \App\Models\BudgetVersion::whereIn('status',['submitted','under_review'])->count(); @endphp
            @if($pendingCount)
                <span class="nav-badge">{{ $pendingCount }}</span>
            @endif
        </a>
        @endcan

        @can('create budget')
        <a href="{{ route('actuals.index') }}"
        class="sidebar-link {{ request()->routeIs('actuals.*') ? 'active' : '' }}">
        <i class="fa-solid fa-gift nav-icon"></i>
            <span class="link-text">Actuals</span>
        </a>
        @endcan

        @can('request supplementary budget')
        <a href="{{ route('supplementary.index') }}"
        class="sidebar-link {{ request()->routeIs('supplementary.*') ? 'active' : '' }}">
        <i class="bi bi-node-plus nav-icon"></i>
            <span class="link-text">Supplementary</span>
            @php $pendingSup = \App\Models\SupplementaryBudget::where('status','submitted')->count(); @endphp
            @if($pendingSup)
                <span class="nav-badge">{{ $pendingSup }}</span>
            @endif
        </a>
        @endcan

        @can('grant deadline override')
        <a href="{{ route('admin.deadline-overrides.index') }}"
        class="sidebar-link {{ request()->routeIs('admin.deadline-overrides.*') ? 'active' : '' }}">
        <i class="bi bi-calendar-date nav-icon"></i>
            <span class="link-text">Deadlines</span>
        </a>
        @endcan

        @canany(['request virement','approve virement'])
        <a href="{{ route('virements.index') }}"
           class="sidebar-link {{ request()->routeIs('virements.*') ? 'active' : '' }}">
            <i class="fas fa-exchange-alt nav-icon"></i>
            <span class="link-text">Virements</span>
            @php $virCount = \App\Models\Virement::where('status','pending')->count(); @endphp
            @if($virCount)
                <span class="nav-badge">{{ $virCount }}</span>
            @endif
        </a>
        @endcanany


        {{-- ════════════════════════════════════════════════
             REPORTS DROPDOWN (NOW COLLAPSIBLE)
             ════════════════════════════════════════════════ --}}
        @can('view reports')
        <div class="nav-section-label">Analytics</div>

        <button class="sidebar-link" onclick="toggleSubmenu('reportsSubmenu')">
            <i class="fas fa-chart-pie nav-icon"></i>
            <span class="link-text">Reports</span>
            <i class="fas fa-chevron-down chevron-icon" id="reportsChevron"></i>
        </button>
        <div class="sidebar-submenu" id="reportsSubmenu">
            <a href="{{ route('reports.index') }}"
               class="sidebar-link {{ request()->routeIs('reports.index') ? 'active' : '' }}">
                <i class="fas fa-home nav-icon"></i>
                <span class="link-text">Reports Home</span>
            </a>
            <a href="{{ route('reports.executive') }}"
               class="sidebar-link {{ request()->routeIs('reports.executive') ? 'active' : '' }}">
                <i class="fas fa-file-alt nav-icon"></i>
                <span class="link-text">Executive Summary</span>
            </a>
            <a href="{{ route('reports.department') }}"
               class="sidebar-link {{ request()->routeIs('reports.department') ? 'active' : '' }}">
                <i class="fas fa-building nav-icon"></i>
                <span class="link-text">By Department</span>
            </a>
            <a href="{{ route('reports.code-explorer') }}"
               class="sidebar-link {{ request()->routeIs('reports.code-explorer') ? 'active' : '' }}">
                <i class="fas fa-hashtag nav-icon"></i>
                <span class="link-text">Code Explorer</span>
            </a>
            <a href="{{ route('reports.yoy') }}"
               class="sidebar-link {{ request()->routeIs('reports.yoy') ? 'active' : '' }}">
                <i class="fas fa-calendar-alt nav-icon"></i>
                <span class="link-text">Year-on-Year</span>
            </a>
            <a href="{{ route('reports.dept-comparison') }}"
               class="sidebar-link {{ request()->routeIs('reports.dept-comparison') ? 'active' : '' }}">
                <i class="fas fa-balance-scale nav-icon"></i>
                <span class="link-text">Dept Comparison</span>
            </a>
            <a href="{{ route('reports.variance') }}"
               class="sidebar-link {{ request()->routeIs('reports.variance') ? 'active' : '' }}">
                <i class="fas fa-arrow-down nav-icon"></i>
                <span class="link-text">Variance</span>
            </a>
            <a href="{{ route('reports.utilisation') }}"
               class="sidebar-link {{ request()->routeIs('reports.utilisation') ? 'active' : '' }}">
                <i class="fas fa-arrow-up nav-icon"></i>
                <span class="link-text">Utilisation</span>
            </a>
            <a href="{{ route('reports.financial') }}"
               class="sidebar-link {{ request()->routeIs('reports.financial') ? 'active' : '' }}">
                <i class="fas fa-file-invoice-dollar nav-icon"></i>
                <span class="link-text">Financial Statements</span>
            </a>
            <a href="{{ route('reports.capex') }}"
               class="sidebar-link {{ request()->routeIs('reports.capex') ? 'active' : '' }}">
                <i class="fas fa-hard-hat nav-icon"></i>
                <span class="link-text">Capital Expenditure</span>
            </a>
        </div>
        @endcan
        {{-- ════════════════════════════════════════════════ --}}

        @can('manage users')
        <div class="nav-section-label">Administration</div>

        {{-- Users Dropdown --}}
        <button class="sidebar-link" onclick="toggleSubmenu('usersSubmenu')">
            <i class="fas fa-users nav-icon"></i>
            <span class="link-text">User Settings</span>
            <i class="fas fa-chevron-down chevron-icon" id="usersChevron"></i>
        </button>
        <div class="sidebar-submenu" id="usersSubmenu">
            <a href="{{ route('admin.users.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="fas fa-user nav-icon"></i>
                <span class="link-text">All Users</span>
            </a>
            <a href="{{ route('admin.roles.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                <i class="fas fa-user-tag nav-icon"></i>
                <span class="link-text">Roles</span>
            </a>
            <a href="{{ route('admin.departments.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}">
                <i class="fas fa-building nav-icon"></i>
                <span class="link-text">Departments</span>
            </a>
        </div>

        {{-- Budget Dropdown --}}
        <button class="sidebar-link" onclick="toggleSubmenu('budgetSubmenu')">
            <i class="fas fa-coins nav-icon"></i>
            <span class="link-text">Budget Setup</span>
            <i class="fas fa-chevron-down chevron-icon" id="budgetChevron"></i>
        </button>
        <div class="sidebar-submenu" id="budgetSubmenu">
            <a href="{{ route('admin.account-categories.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.account-categories.*') ? 'active' : '' }}">
                <i class="fas fa-folder nav-icon"></i>
                <span class="link-text">Categories</span>
            </a>
            <a href="{{ route('admin.account-codes.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.account-codes.*') ? 'active' : '' }}">
                <i class="fas fa-hashtag nav-icon"></i>
                <span class="link-text">Account Codes</span>
            </a>
            <a href="{{ route('admin.budget-periods.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.budget-periods.*') ? 'active' : '' }}">
                <i class="fas fa-calendar-alt nav-icon"></i>
                <span class="link-text">Budget Periods</span>
            </a>

            <a href="{{ route('admin.approval-stages.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.approval-stages.*') ? 'active' : '' }}">
                <i class="fas fa-unlock nav-icon"></i>
                <span class="link-text">Approval Stages</span>
            </a>

        </div>

        {{-- System Dropdown --}}
        <button class="sidebar-link" onclick="toggleSubmenu('systemSubmenu')">
            <i class="fas fa-cog nav-icon"></i>
            <span class="link-text">System</span>
            <i class="fas fa-chevron-down chevron-icon" id="systemChevron"></i>
        </button>
        <div class="sidebar-submenu" id="systemSubmenu">
            @can('view audit log')
            <a href="{{ route('admin.audit-log.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.audit-log.*') ? 'active' : '' }}">
                <i class="fas fa-clipboard-list nav-icon"></i>
                <span class="link-text">Audit Log</span>
            </a>
            @endcan
            @can('manage system settings')
            <a href="{{ route('admin.settings.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                <i class="fas fa-sliders-h nav-icon"></i>
                <span class="link-text">Settings</span>
            </a>
            @endcan

            @can('manage system settings')
            <a href="{{ route('admin.backups.index') }}"
            class="sidebar-link {{ request()->routeIs('admin.backups.*') ? 'active' : '' }}">
                <i class="bi bi-database-add"></i>
                <span class="link-text">Backups</span>
            </a>
            @endcan

        </div>
        @endcan

    </div>

    {{-- Sidebar footer --}}
    <div style="padding:16px 20px; border-top:1px solid rgba(255,255,255,.08)">
        <div style="font-size:12px; color:rgba(255,255,255,.4)">
            <i class="fas fa-user-circle me-1"></i>{{ Auth::user()->name }}
        </div>
        <div style="font-size:11px; color:var(--gold); margin-top:2px">
            <i class="fas fa-shield-alt me-1"></i>{{ ucfirst(str_replace('_',' ', Auth::user()->roles->first()?->name ?? '')) }}
        </div>
    </div>
</nav>

{{-- ── Topbar ── --}}
<header id="topbar">
    <div class="topbar-left">
        <button class="hamburger-btn" id="hamburgerBtn">
            <i class="fas fa-bars"></i>
        </button>
        <div class="topbar-title">@yield('title')</div>
    </div>

    <div class="topbar-right">

        {{-- Notifications --}}
        <div class="dropdown">
            <button class="notif-btn dropdown-toggle"
                    data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-bell"></i>
                @if(Auth::user()->unreadNotifications()->count())
                    <span class="notif-dot"></span>
                @endif
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow"
                style="min-width:320px;max-height:380px;overflow-y:auto">
                <li class="px-3 py-2 d-flex justify-content-between border-bottom">
                    <span class="fw-semibold small">
                        <i class="fas fa-bell me-1"></i>Notifications
                    </span>
                    @if(Auth::user()->unreadNotifications()->count())
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button class="btn btn-link btn-sm p-0 text-muted"
                                style="font-size:12px">
                            <i class="fas fa-check-double me-1"></i>Mark all read
                        </button>
                    </form>
                    @endif
                </li>
                @forelse(Auth::user()->budgetNotifications()->latest()->limit(5)->get() as $n)
                <li>
                    <div class="dropdown-item py-2 {{ $n->isRead() ? 'text-muted' : 'fw-semibold' }}"
                         style="white-space:normal;font-size:13px">
                        {{ $n->subject }}
                        <div class="text-muted mt-1" style="font-size:11px;font-weight:400">
                            <i class="far fa-clock me-1"></i>{{ $n->created_at->diffForHumans() }}
                        </div>
                    </div>
                </li>
                @empty
                <li>
                    <div class="dropdown-item text-muted small py-3 text-center">
                        <i class="far fa-bell-slash me-1"></i>No notifications yet.
                    </div>
                </li>
                @endforelse
                <li class="border-top">
                    <a href="{{ route('notifications.index') }}"
                       class="dropdown-item text-center small py-2"
                       style="color:var(--navy)">
                        <i class="fas fa-arrow-right me-1"></i>View all notifications
                    </a>
                </li>
            </ul>
        </div>

        {{-- User menu --}}
        <div class="dropdown">
            <a href="#" class="user-pill dropdown-toggle" data-bs-toggle="dropdown">
                <div class="user-avatar">
                    {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                </div>
                {{ explode(' ', Auth::user()->name)[0] }}
                <i class="fas fa-chevron-down ms-1" style="font-size:10px;color:var(--slate)"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li>
                    <span class="dropdown-item-text small text-muted">
                        <i class="far fa-envelope me-1"></i>{{ Auth::user()->email }}
                    </span>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item small" href="{{ route('password.change') }}">
                        <i class="fas fa-key me-2"></i>Change password
                    </a>
                </li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="dropdown-item small text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i>Sign out
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>

{{-- ── Main content ── --}}
<main id="main">

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert"
         style="border-radius:10px;border:none;background:#D1FAE5;color:#065F46">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert"
         style="border-radius:10px;border:none;background:#FEE2E2;color:#991B1B">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @yield('content')
</main>

<script>
// ── Toggle sidebar submenus ──
function toggleSubmenu(id) {
    const submenu = document.getElementById(id);
    const chevron = document.getElementById(id.replace('Submenu', 'Chevron'));

    if (submenu.classList.contains('open')) {
        submenu.classList.remove('open');
        if (chevron) chevron.classList.remove('rotated');
    } else {
        submenu.classList.add('open');
        if (chevron) chevron.classList.add('rotated');
    }
}

// ── Toggle sidebar (desktop) ──
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const topbar = document.getElementById('topbar');
    const main = document.getElementById('main');
    const toggleBtn = document.getElementById('sidebarToggle');
    const collapseBtn = document.getElementById('collapseSidebar');
    const icon = collapseBtn?.querySelector('i');

    sidebar.classList.toggle('collapsed');
    topbar.classList.toggle('expanded');
    main.classList.toggle('expanded');

    // Toggle the collapse button icon
    if (icon) {
        if (sidebar.classList.contains('collapsed')) {
            icon.className = 'fas fa-chevron-right';
        } else {
            icon.className = 'fas fa-chevron-left';
        }
    }

    // Show/hide floating toggle button
    if (sidebar.classList.contains('collapsed')) {
        toggleBtn.classList.add('visible');
    } else {
        toggleBtn.classList.remove('visible');
    }
}

// ── Floating toggle button click ──
document.getElementById('sidebarToggle')?.addEventListener('click', toggleSidebar);

// ── Collapse button click ──
document.getElementById('collapseSidebar')?.addEventListener('click', toggleSidebar);

// ── Hamburger (mobile) ──
document.getElementById('hamburgerBtn')?.addEventListener('click', function() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('mobile-open');
    document.getElementById('sidebarOverlay').classList.toggle('active');
    // Remove collapsed state on mobile
    sidebar.classList.remove('collapsed');
});

document.getElementById('closeSidebar')?.addEventListener('click', function() {
    document.getElementById('sidebar').classList.remove('mobile-open');
    document.getElementById('sidebarOverlay').classList.remove('active');
});

document.getElementById('sidebarOverlay')?.addEventListener('click', function() {
    document.getElementById('sidebar').classList.remove('mobile-open');
    this.classList.remove('active');
});

// ── Auto-close submenus on mobile when clicking a link ──
document.querySelectorAll('.sidebar-link').forEach(link => {
    link.addEventListener('click', function(e) {
        if (window.innerWidth <= 768 && !this.querySelector('.chevron-icon')) {
            document.getElementById('sidebar').classList.remove('mobile-open');
            document.getElementById('sidebarOverlay').classList.remove('active');
        }
    });
});

// ── Keep submenus open based on active route ──
document.addEventListener('DOMContentLoaded', function() {
    const activeLinks = document.querySelectorAll('.sidebar-link.active');
    activeLinks.forEach(link => {
        const submenu = link.closest('.sidebar-submenu');
        if (submenu) {
            submenu.classList.add('open');
            const chevron = document.getElementById(submenu.id.replace('Submenu', 'Chevron'));
            if (chevron) chevron.classList.add('rotated');
        }
    });
});
</script>

@stack('scripts')
</body>
</html>
