<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'PaceBoard Admin')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        :root {
            --sidebar-width: 272px;
            --sidebar-collapsed: 76px;
            --header-height: 72px;
            --footer-height: 52px;
            --bg: #f4f6f9;
            --surface: #ffffff;
            --border: #e4e8ef;
            --border-light: #eef1f6;
            --text: #0f172a;
            --muted: #64748b;
            --sidebar-bg: #0b1220;
            --sidebar-hover: rgba(255,255,255,.06);
            --sidebar-active-bg: rgba(37, 99, 235, .18);
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #eff6ff;
            --success: #059669;
            --success-light: #ecfdf5;
            --warning: #d97706;
            --warning-light: #fffbeb;
            --danger: #dc2626;
            --danger-light: #fef2f2;
            --shadow-sm: 0 1px 2px rgba(15,23,42,.05);
            --shadow-md: 0 4px 16px rgba(15,23,42,.08);
            --shadow-lg: 0 12px 40px rgba(15,23,42,.12);
            --radius: 12px;
            --radius-lg: 16px;
        }

        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
        }

        .app-shell { display: flex; min-height: 100vh; }

        /* ── Sidebar ── */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #0b1220 0%, #111827 100%);
            color: #94a3b8;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
            transition: width .25s ease, transform .25s ease;
            overflow: hidden;
            border-right: 1px solid rgba(255,255,255,.06);
        }
        .sidebar.collapsed { width: var(--sidebar-collapsed); }

        .sidebar-brand {
            height: var(--header-height);
            display: flex;
            align-items: center;
            gap: .85rem;
            padding: 0 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,.06);
            flex-shrink: 0;
        }
        .sidebar-brand .logo-img {
            width: 38px; height: 38px;
            border-radius: 10px;
            object-fit: cover;
            flex-shrink: 0;
            border: 2px solid rgba(59,130,246,.4);
        }
        .sidebar-brand .brand-text {
            font-weight: 700;
            color: #fff;
            font-size: 1.05rem;
            white-space: nowrap;
            transition: opacity .2s;
            line-height: 1.2;
        }
        .sidebar-brand .brand-text small {
            display: block;
            font-size: .65rem;
            font-weight: 500;
            color: #64748b;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .sidebar.collapsed .brand-text { opacity: 0; pointer-events: none; }

        .sidebar-nav { flex: 1; overflow-y: auto; padding: 1rem .85rem; }
        .nav-section {
            font-size: .65rem;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: #475569;
            padding: .85rem .75rem .4rem;
            font-weight: 600;
            white-space: nowrap;
            transition: opacity .2s;
        }
        .sidebar.collapsed .nav-section { opacity: 0; height: 0; padding: 0; overflow: hidden; }

        .nav-link {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .65rem .85rem;
            margin-bottom: .15rem;
            border-radius: 10px;
            color: #94a3b8;
            text-decoration: none;
            font-size: .875rem;
            font-weight: 500;
            transition: all .15s;
            white-space: nowrap;
            position: relative;
        }
        .nav-link i { width: 20px; text-align: center; font-size: .95rem; flex-shrink: 0; }
        .nav-link:hover { background: var(--sidebar-hover); color: #e2e8f0; }
        .nav-link.active {
            background: var(--sidebar-active-bg);
            color: #fff;
        }
        .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0; top: 50%;
            transform: translateY(-50%);
            width: 3px; height: 60%;
            background: var(--primary);
            border-radius: 0 3px 3px 0;
        }
        .nav-link .label { transition: opacity .2s; }
        .sidebar.collapsed .nav-link .label { opacity: 0; width: 0; overflow: hidden; }
        .nav-badge {
            margin-left: auto;
            background: var(--danger);
            color: #fff;
            font-size: .65rem;
            font-weight: 700;
            padding: .1rem .45rem;
            border-radius: 999px;
            min-width: 18px;
            text-align: center;
        }
        .sidebar.collapsed .nav-badge { display: none; }

        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid rgba(255,255,255,.06);
        }
        .collapse-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            padding: .65rem;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 10px;
            color: #94a3b8;
            cursor: pointer;
            font-size: .8rem;
            font-family: inherit;
            font-weight: 500;
            transition: all .15s;
        }
        .collapse-btn:hover { background: rgba(255,255,255,.08); color: #fff; }
        .sidebar.collapsed .collapse-label { display: none; }

        /* ── Main column ── */
        .main-column {
            flex: 1;
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            transition: margin-left .25s ease;
        }
        body.sidebar-collapsed .main-column { margin-left: var(--sidebar-collapsed); }

        /* ── Header ── */
        .top-header {
            min-height: var(--header-height);
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: var(--shadow-sm);
        }

        .header-left { display: flex; align-items: center; gap: 1rem; min-width: 0; flex: 1; }
        .mobile-toggle {
            display: none;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: .5rem .65rem;
            cursor: pointer;
            color: var(--text);
            font-size: 1rem;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .75rem;
            color: var(--muted);
            margin-bottom: .2rem;
        }
        .breadcrumb a { color: var(--muted); text-decoration: none; }
        .breadcrumb a:hover { color: var(--primary); }
        .breadcrumb i { font-size: .6rem; opacity: .5; }
        .page-title { margin: 0; font-size: 1.2rem; font-weight: 700; letter-spacing: -.02em; }
        .page-subtitle { margin: .1rem 0 0; font-size: .8rem; color: var(--muted); }

        .header-center {
            flex: 1;
            max-width: 420px;
            display: flex;
            justify-content: center;
        }
        .search-box {
            position: relative;
            width: 100%;
        }
        .search-box i {
            position: absolute;
            left: .9rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: .85rem;
            pointer-events: none;
        }
        .search-box input {
            width: 100%;
            padding: .6rem .9rem .6rem 2.4rem;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: .85rem;
            font-family: inherit;
            background: var(--bg);
            color: var(--text);
            transition: border-color .15s, box-shadow .15s;
        }
        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37,99,235,.12);
            background: #fff;
        }
        .search-box kbd {
            position: absolute;
            right: .65rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: .65rem;
            padding: .15rem .4rem;
            border-radius: 4px;
            border: 1px solid var(--border);
            background: #fff;
            color: var(--muted);
            font-family: inherit;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-shrink: 0;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .35rem .75rem;
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 600;
            border: 1px solid var(--border);
            background: var(--success-light);
            color: var(--success);
        }
        .status-pill.warning { background: var(--warning-light); color: var(--warning); border-color: #fde68a; }
        .status-pill .dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: currentColor;
            animation: pulse 2s infinite;
        }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }

        .header-datetime {
            font-size: .75rem;
            color: var(--muted);
            padding: 0 .5rem;
            border-right: 1px solid var(--border);
            margin-right: .25rem;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            line-height: 1.3;
        }
        .header-datetime strong { color: var(--text); font-size: .8rem; }

        .header-icon-btn {
            position: relative;
            width: 40px; height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--bg);
            color: var(--muted);
            cursor: pointer;
            text-decoration: none;
            font-size: .95rem;
            transition: all .15s;
        }
        .header-icon-btn:hover { border-color: #cbd5e1; color: var(--primary); background: #fff; }
        .header-icon-btn .badge-dot {
            position: absolute;
            top: 6px; right: 6px;
            width: 8px; height: 8px;
            background: var(--danger);
            border-radius: 50%;
            border: 2px solid var(--bg);
        }
        .header-icon-btn .badge-count {
            position: absolute;
            top: -4px; right: -4px;
            min-width: 18px; height: 18px;
            background: var(--danger);
            color: #fff;
            font-size: .6rem;
            font-weight: 700;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #fff;
        }

        .quick-menu { position: relative; }
        .quick-menu .dropdown { min-width: 200px; }

        /* Profile dropdown */
        .profile-menu { position: relative; margin-left: .25rem; }
        .profile-trigger {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .3rem .6rem .3rem .3rem;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 999px;
            cursor: pointer;
            font: inherit;
            color: inherit;
            transition: border-color .15s;
        }
        .profile-trigger:hover { border-color: #cbd5e1; }
        .avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-light);
        }
        .avatar-fallback {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .85rem;
        }
        .profile-meta { text-align: left; line-height: 1.25; }
        .profile-meta .name { font-size: .82rem; font-weight: 600; display: block; }
        .profile-meta .role { font-size: .68rem; color: var(--muted); display: block; }
        .chevron { color: var(--muted); font-size: .65rem; transition: transform .2s; }
        .profile-menu.open .chevron { transform: rotate(180deg); }

        .dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            min-width: 240px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            z-index: 200;
        }
        .profile-menu.open .dropdown,
        .quick-menu.open .dropdown { display: block; }

        .dropdown-header {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            display: flex;
            align-items: center;
            gap: .75rem;
        }
        .dropdown-header img {
            width: 44px; height: 44px;
            border-radius: 50%;
            object-fit: cover;
        }
        .dropdown-header strong { display: block; font-size: .9rem; }
        .dropdown-header span { font-size: .75rem; color: var(--muted); }

        .dropdown a, .dropdown button {
            display: flex;
            align-items: center;
            gap: .65rem;
            width: 100%;
            padding: .75rem 1rem;
            border: none;
            background: none;
            text-align: left;
            font: inherit;
            font-size: .875rem;
            color: var(--text);
            text-decoration: none;
            cursor: pointer;
            transition: background .1s;
        }
        .dropdown a i, .dropdown button i { width: 18px; color: var(--muted); font-size: .85rem; }
        .dropdown a:hover, .dropdown button:hover { background: #f8fafc; }
        .dropdown .danger { color: var(--danger); }
        .dropdown .danger i { color: var(--danger); }
        .dropdown-divider { height: 1px; background: var(--border); margin: .25rem 0; }

        /* ── Content ── */
        .content {
            flex: 1;
            padding: 1.5rem;
            width: 100%;
        }

        /* ── Footer ── */
        .app-footer {
            min-height: var(--footer-height);
            background: var(--surface);
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            font-size: .78rem;
            color: var(--muted);
            flex-wrap: wrap;
            gap: .5rem;
        }
        .app-footer a { color: var(--muted); text-decoration: none; margin-left: 1rem; transition: color .15s; }
        .app-footer a:hover { color: var(--primary); }
        .footer-links { display: flex; align-items: center; flex-wrap: wrap; }

        /* ── Shared components ── */
        .page-hero {
            position: relative;
            border-radius: var(--radius-lg);
            overflow: hidden;
            margin-bottom: 1.5rem;
            min-height: 160px;
            display: flex;
            align-items: flex-end;
        }
        .page-hero img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .page-hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(11,18,32,.88) 0%, rgba(11,18,32,.55) 60%, rgba(11,18,32,.25) 100%);
        }
        .page-hero-content {
            position: relative;
            z-index: 1;
            padding: 1.75rem 2rem;
            color: #fff;
        }
        .page-hero-content h2 { margin: 0 0 .35rem; font-size: 1.5rem; font-weight: 700; }
        .page-hero-content p { margin: 0; opacity: .85; font-size: .9rem; max-width: 520px; }
        .page-hero-actions { margin-top: 1rem; display: flex; gap: .65rem; flex-wrap: wrap; }

        .stat-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-card {
            background: var(--surface);
            padding: 1.25rem;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            transition: box-shadow .15s, transform .15s;
        }
        .stat-card:hover { box-shadow: var(--shadow-md); transform: translateY(-1px); }
        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
        }
        .stat-icon.blue { background: var(--primary-light); color: var(--primary); }
        .stat-icon.green { background: var(--success-light); color: var(--success); }
        .stat-icon.amber { background: var(--warning-light); color: var(--warning); }
        .stat-icon.red { background: var(--danger-light); color: var(--danger); }
        .stat-icon.slate { background: #f1f5f9; color: #475569; }
        .stat-body h3 { margin: 0 0 .25rem; font-size: .75rem; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
        .stat-body .value { margin: 0; font-size: 1.65rem; font-weight: 800; letter-spacing: -.02em; line-height: 1; }
        .stat-body .meta { margin: .35rem 0 0; font-size: .72rem; color: var(--muted); }

        .panel {
            background: var(--surface);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            overflow: hidden;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
        }
        .panel-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border);
            font-weight: 600;
            font-size: .9rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            background: #fafbfc;
        }
        .panel-header-left { display: flex; align-items: center; gap: .6rem; }
        .panel-header-left i { color: var(--primary); font-size: .9rem; }
        .panel-header .panel-action { font-size: .8rem; color: var(--primary); text-decoration: none; font-weight: 500; }
        .panel-header .panel-action:hover { text-decoration: underline; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: .85rem 1.25rem; text-align: left; border-bottom: 1px solid var(--border-light); font-size: .875rem; }
        th { background: #f8fafc; color: var(--muted); font-weight: 600; font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; }
        tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: #fafbfc; }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .55rem 1.1rem;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-size: .875rem;
            font-family: inherit;
            font-weight: 600;
            text-decoration: none;
            transition: all .15s;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-secondary { background: #334155; color: #fff; }
        .btn-outline { background: #fff; color: var(--text); border: 1px solid var(--border); }
        .btn-outline:hover { border-color: #cbd5e1; background: #f8fafc; }
        .btn-ghost { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.3); }
        .btn-ghost:hover { background: rgba(255,255,255,.25); }
        .btn-sm { padding: .4rem .8rem; font-size: .8rem; }

        .alert {
            padding: .85rem 1.1rem;
            border-radius: var(--radius);
            margin-bottom: 1rem;
            font-size: .875rem;
            display: flex;
            align-items: center;
            gap: .6rem;
        }
        .alert-success { background: var(--success-light); color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: var(--danger-light); color: #991b1b; border: 1px solid #fecaca; }

        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: .4rem; font-size: .875rem; font-weight: 600; color: #334155; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; max-width: 480px;
            padding: .6rem .85rem;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: .9rem;
            font-family: inherit;
            background: #fff;
            transition: border-color .15s, box-shadow .15s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37,99,235,.1);
        }

        .badge { display: inline-flex; align-items: center; gap: .3rem; padding: .25rem .6rem; border-radius: 999px; font-size: .7rem; font-weight: 600; }
        .badge-green { background: var(--success-light); color: #065f46; }
        .badge-red { background: var(--danger-light); color: #991b1b; }
        .badge-yellow { background: var(--warning-light); color: #92400e; }

        .page-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
        }
        .page-toolbar h2 { margin: 0; font-size: 1rem; font-weight: 700; display: flex; align-items: center; gap: .5rem; }
        .page-toolbar h2 i { color: var(--primary); }

        .empty-state {
            padding: 3rem 2rem;
            text-align: center;
            color: var(--muted);
        }
        .empty-state i { font-size: 2.5rem; margin-bottom: .75rem; opacity: .4; display: block; }

        .pagination-wrap { padding: .5rem 1rem 1rem; }
        form.inline { display: inline; }

        .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; align-items: start; }

        @media (max-width: 1200px) {
            .header-center { display: none; }
            .header-datetime { display: none; }
        }
        @media (max-width: 1100px) {
            .challenges-grid { grid-template-columns: 1fr !important; }
        }
        @media (max-width: 900px) {
            .sidebar { transform: translateX(-100%); width: var(--sidebar-width) !important; }
            .sidebar.mobile-open { transform: translateX(0); }
            .main-column { margin-left: 0 !important; }
            .mobile-toggle { display: flex; }
            .profile-meta { display: none; }
            .status-pill { display: none; }
            .sidebar-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,.45);
                z-index: 90;
            }
            .sidebar-overlay.show { display: block; }
        }
    </style>
    @stack('styles')
</head>
<body>
@php
    $user = auth()->user();
    $initial = $user ? strtoupper(substr($user->name, 0, 1)) : 'A';
    $nav = fn ($pattern) => request()->is($pattern) ? 'active' : '';
    $segments = collect(request()->segments())->slice(1);
    $pageTitles = [
        'dashboard' => 'Dashboard',
        'users' => 'Users',
        'trips' => 'Trips',
        'reports' => 'Road Alerts',
        'sos' => 'SOS Alerts',
        'challenges' => 'Challenges',
        'routes' => 'Routes',
        'vehicles' => 'Vehicles',
        'leaderboards' => 'Leaderboards',
        'activity' => 'Activity Log',
        'settings' => 'Settings',
    ];
    $currentPage = $pageTitles[$segments->last()] ?? ($segments->last() ? ucfirst(str_replace('-', ' ', $segments->last())) : 'Dashboard');
    $avatarUrl = 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=80&h=80&q=80';
@endphp

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <img src="https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=76&h=76&q=80"
                 alt="PaceBoard" class="logo-img">
            <div class="brand-text">
                PaceBoard
                <small>Admin Console</small>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">Overview</div>
            <a href="/admin/dashboard" class="nav-link {{ $nav('admin/dashboard') }}" title="Dashboard"
               data-turbo-frame="main-content" data-turbo-action="advance">
                <i class="fa-solid fa-gauge-high"></i><span class="label">Dashboard</span>
            </a>

            <div class="nav-section">Operations</div>
            <a href="/admin/users" class="nav-link {{ $nav('admin/users') }}" title="Users"
               data-turbo-frame="main-content" data-turbo-action="advance">
                <i class="fa-solid fa-users"></i><span class="label">Users</span>
            </a>
            <a href="/admin/trips" class="nav-link {{ $nav('admin/trips') }}" title="Trips"
               data-turbo-frame="main-content" data-turbo-action="advance">
                <i class="fa-solid fa-route"></i><span class="label">Trips</span>
            </a>
            <a href="/admin/reports" class="nav-link {{ $nav('admin/reports') }}" title="Road Alerts"
               data-turbo-frame="main-content" data-turbo-action="advance">
                <i class="fa-solid fa-flag"></i>
                <span class="label">Road Alerts</span>
                @if($headerActiveReports > 0)<span class="nav-badge">{{ $headerActiveReports }}</span>@endif
            </a>
            <a href="/admin/sos" class="nav-link {{ $nav('admin/sos') }}" title="SOS Alerts"
               data-turbo-frame="main-content" data-turbo-action="advance">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span class="label">SOS Alerts</span>
                @if($headerActiveSos > 0)<span class="nav-badge">{{ $headerActiveSos }}</span>@endif
            </a>
            <a href="/admin/challenges" class="nav-link {{ $nav('admin/challenges') }}" title="Challenges"
               data-turbo-frame="main-content" data-turbo-action="advance">
                <i class="fa-solid fa-trophy"></i><span class="label">Challenges</span>
            </a>
            <a href="/admin/routes" class="nav-link {{ $nav('admin/routes') }}" title="Routes"
               data-turbo-frame="main-content" data-turbo-action="advance">
                <i class="fa-solid fa-map"></i><span class="label">Routes</span>
            </a>
            <a href="/admin/vehicles" class="nav-link {{ $nav('admin/vehicles') }}" title="Vehicles"
               data-turbo-frame="main-content" data-turbo-action="advance">
                <i class="fa-solid fa-car"></i><span class="label">Vehicles</span>
            </a>

            <div class="nav-section">Platform</div>
            <a href="/admin/leaderboards" class="nav-link {{ $nav('admin/leaderboards') }}" title="Leaderboards"
               data-turbo-frame="main-content" data-turbo-action="advance">
                <i class="fa-solid fa-ranking-star"></i><span class="label">Leaderboards</span>
            </a>
            <a href="/admin/activity" class="nav-link {{ $nav('admin/activity') }}" title="Activity Log"
               data-turbo-frame="main-content" data-turbo-action="advance">
                <i class="fa-solid fa-clipboard-list"></i><span class="label">Activity Log</span>
            </a>
            <a href="/admin/settings" class="nav-link {{ $nav('admin/settings') }}" title="Settings"
               data-turbo-frame="main-content" data-turbo-action="advance">
                <i class="fa-solid fa-gear"></i><span class="label">Settings</span>
            </a>
            <a href="/api/docs" class="nav-link" target="_blank" data-turbo="false" title="API Docs">
                <i class="fa-solid fa-book"></i><span class="label">API Docs</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <button type="button" class="collapse-btn" id="sidebarCollapseBtn" title="Collapse sidebar">
                <i class="fa-solid fa-angles-left" id="collapseIcon"></i>
                <span class="collapse-label">Collapse</span>
            </button>
        </div>
    </aside>

    <div class="main-column">
        <header class="top-header">
            <div class="header-left">
                <button type="button" class="mobile-toggle" id="mobileToggle" aria-label="Open menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <nav class="breadcrumb" aria-label="Breadcrumb">
                        <a href="/admin/dashboard" data-turbo-frame="main-content" data-turbo-action="advance"><i class="fa-solid fa-house"></i></a>
                        <i class="fa-solid fa-chevron-right"></i>
                        <span class="breadcrumb-current">{{ $currentPage }}</span>
                    </nav>
                    <h1 class="page-title">@yield('page_title', 'Dashboard')</h1>
                    @hasSection('page_subtitle')
                        <p class="page-subtitle">@yield('page_subtitle')</p>
                    @endif
                </div>
            </div>

            <div class="header-center">
                <form class="search-box" action="/admin/users" method="GET" role="search" data-turbo-frame="main-content" data-turbo-action="advance">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" name="q" placeholder="Search users, email, phone…" value="{{ request('q') }}" aria-label="Search users">
                    <kbd>Ctrl K</kbd>
                </form>
            </div>

            <div class="header-right">
                @if($maintenanceMode)
                    <span class="status-pill warning"><span class="dot"></span> Maintenance</span>
                @else
                    <span class="status-pill"><span class="dot"></span> Live</span>
                @endif

                <div class="header-datetime">
                    <strong id="headerTime">--:--</strong>
                    <span id="headerDate">{{ now()->format('D, M j Y') }}</span>
                </div>

                <a href="/admin/sos" class="header-icon-btn" title="SOS Alerts" data-turbo-frame="main-content" data-turbo-action="advance">
                    <i class="fa-solid fa-bell"></i>
                    @if($headerActiveSos > 0)<span class="badge-count">{{ $headerActiveSos }}</span>@endif
                </a>

                <div class="quick-menu" id="quickMenu">
                    <button type="button" class="header-icon-btn" id="quickMenuTrigger" title="Quick actions">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                    <div class="dropdown">
                        <a href="/admin/reports" data-turbo-frame="main-content" data-turbo-action="advance"><i class="fa-solid fa-flag"></i> Road alerts</a>
                        <a href="/admin/challenges" data-turbo-frame="main-content" data-turbo-action="advance"><i class="fa-solid fa-trophy"></i> New challenge</a>
                        <a href="/admin/settings" data-turbo-frame="main-content" data-turbo-action="advance"><i class="fa-solid fa-bullhorn"></i> Broadcast message</a>
                        <a href="/admin/users" data-turbo-frame="main-content" data-turbo-action="advance"><i class="fa-solid fa-user-plus"></i> Manage users</a>
                        <div class="dropdown-divider"></div>
                        <a href="/api/docs" target="_blank" data-turbo="false"><i class="fa-solid fa-code"></i> API reference</a>
                    </div>
                </div>

                <a href="/api/docs" class="header-icon-btn" target="_blank" title="API Documentation" data-turbo="false">
                    <i class="fa-solid fa-book-open"></i>
                </a>

                <div class="profile-menu" id="profileMenu">
                    <button type="button" class="profile-trigger" id="profileTrigger" aria-haspopup="true">
                        <img src="{{ $avatarUrl }}" alt="{{ $user?->name }}" class="avatar">
                        <div class="profile-meta">
                            <span class="name">{{ $user?->name ?? 'Admin' }}</span>
                            <span class="role">Super Administrator</span>
                        </div>
                        <i class="fa-solid fa-chevron-down chevron"></i>
                    </button>
                    <div class="dropdown" role="menu">
                        <div class="dropdown-header">
                            <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=88&h=88&q=80" alt="">
                            <div>
                                <strong>{{ $user?->name ?? 'Admin' }}</strong>
                                <span>{{ $user?->email ?? '' }}</span>
                            </div>
                        </div>
                        <a href="/admin/dashboard" data-turbo-frame="main-content" data-turbo-action="advance"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
                        <a href="/admin/settings" data-turbo-frame="main-content" data-turbo-action="advance"><i class="fa-solid fa-gear"></i> Settings</a>
                        <a href="/api/docs" target="_blank" data-turbo="false"><i class="fa-solid fa-book"></i> API Documentation</a>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="/admin/logout" data-turbo="false"
                              data-confirm="You will be signed out of the admin console."
                              data-confirm-title="Sign out?"
                              data-confirm-icon="question"
                              data-confirm-button="Yes, sign out">
                            @csrf
                            <button type="submit" class="danger"><i class="fa-solid fa-right-from-bracket"></i> Sign out</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="content">
            <turbo-frame id="main-content"
                data-page-title="{{ trim($__env->yieldContent('page_title', 'Dashboard')) }}"
                data-page-subtitle="{{ trim($__env->yieldContent('page_subtitle', '')) }}"
                data-breadcrumb="{{ $currentPage }}"
                @if(session('status')) data-flash-success="{{ session('status') }}" @endif
                @if(isset($errors) && $errors->any()) data-flash-error="{{ $errors->first() }}" @endif
            >
                @yield('content')
            </turbo-frame>
        </main>

        <footer class="app-footer">
            <span><i class="fa-regular fa-copyright"></i> {{ date('Y') }} PaceBoard Technologies. All rights reserved.</span>
            <div class="footer-links">
                <a href="/admin/dashboard" data-turbo-frame="main-content" data-turbo-action="advance"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
                <a href="/admin/settings" data-turbo-frame="main-content" data-turbo-action="advance"><i class="fa-solid fa-gear"></i> Settings</a>
                <a href="/api/docs" target="_blank" data-turbo="false"><i class="fa-solid fa-code"></i> API</a>
                <span style="margin-left:1rem;opacity:.6;">v1.0.0</span>
            </div>
        </footer>
    </div>
</div>

<script>
(function () {
    const body = document.body;
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const collapseBtn = document.getElementById('sidebarCollapseBtn');
    const collapseIcon = document.getElementById('collapseIcon');
    const mobileToggle = document.getElementById('mobileToggle');
    const profileMenu = document.getElementById('profileMenu');
    const profileTrigger = document.getElementById('profileTrigger');
    const quickMenu = document.getElementById('quickMenu');
    const quickMenuTrigger = document.getElementById('quickMenuTrigger');
    const searchInput = document.querySelector('.search-box input');

    const collapsed = localStorage.getItem('pb-sidebar-collapsed') === '1';
    if (collapsed && window.innerWidth > 900) {
        sidebar.classList.add('collapsed');
        body.classList.add('sidebar-collapsed');
        collapseIcon.className = 'fa-solid fa-angles-right';
    }

    collapseBtn?.addEventListener('click', () => {
        const isCollapsed = sidebar.classList.toggle('collapsed');
        body.classList.toggle('sidebar-collapsed', isCollapsed);
        collapseIcon.className = isCollapsed ? 'fa-solid fa-angles-right' : 'fa-solid fa-angles-left';
        localStorage.setItem('pb-sidebar-collapsed', isCollapsed ? '1' : '0');
    });

    mobileToggle?.addEventListener('click', () => {
        sidebar.classList.toggle('mobile-open');
        overlay.classList.toggle('show');
    });

    overlay?.addEventListener('click', () => {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('show');
    });

    profileTrigger?.addEventListener('click', (e) => {
        e.stopPropagation();
        quickMenu?.classList.remove('open');
        profileMenu.classList.toggle('open');
    });

    quickMenuTrigger?.addEventListener('click', (e) => {
        e.stopPropagation();
        profileMenu?.classList.remove('open');
        quickMenu.classList.toggle('open');
    });

    document.addEventListener('click', () => {
        profileMenu?.classList.remove('open');
        quickMenu?.classList.remove('open');
    });
    profileMenu?.addEventListener('click', (e) => e.stopPropagation());
    quickMenu?.addEventListener('click', (e) => e.stopPropagation());

    function updateClock() {
        const now = new Date();
        const el = document.getElementById('headerTime');
        if (el) el.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    updateClock();
    setInterval(updateClock, 30000);

    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            searchInput?.focus();
        }
    });
})();
</script>
@include('admin.partials.turbo-swal')
@stack('scripts')
</body>
</html>
