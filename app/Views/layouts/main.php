<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'PIA Motorpool FMS') ?> &mdash; PIA-AFMS</title>

    <!-- Google Fonts: Inter & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 & FontAwesome 6 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        :root {
            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;

            --sidebar-w: 268px;
            --bg-canvas: #f8fafc;
            --bg-surface: #ffffff;
            --border-subtle: #e2e8f0;
            --border-strong: #cbd5e1;

            --text-heading: #0f172a;
            --text-body: #334155;
            --text-muted: #64748b;

            --primary: #1e3a8a;
            --primary-accent: #2563eb;
            --sidebar-bg: #0b1324;
            --sidebar-item-hover: rgba(255, 255, 255, 0.05);
            --sidebar-item-active: rgba(37, 99, 235, 0.15);
        }

        html {
            scroll-behavior: smooth;
            scrollbar-gutter: stable; /* no layout shift between pages */
        }

        ::selection {
            background: rgba(37, 99, 235, 0.18);
            color: var(--text-heading);
        }

        /* Consistent keyboard accessibility ring across all interactive elements */
        a:focus-visible,
        button:focus-visible,
        input:focus-visible,
        select:focus-visible,
        textarea:focus-visible,
        [data-bs-toggle]:focus-visible {
            outline: 2px solid #2563eb;
            outline-offset: 2px;
            border-radius: 4px;
        }

        body {
            font-family: var(--font-sans);
            background-color: var(--bg-canvas);
            color: var(--text-body);
            -webkit-font-smoothing: antialiased;
            letter-spacing: -0.011em;
            margin: 0;
            padding: 0;
        }

        /* Monospace accents for IDs, plates, numbers */
        .mono {
            font-family: var(--font-mono);
            letter-spacing: -0.03em;
        }

        /* -------------------------------------------------------------
           Corporate Minimalist Sidebar
        ------------------------------------------------------------- */
        #sidebar {
            width: var(--sidebar-w);
            background: var(--sidebar-bg);
            height: 100vh;            /* fixed viewport panel — nav scrolls independently */
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1040;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255, 255, 255, 0.06);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .sidebar-brand {
            padding: 16px 18px 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            flex-shrink: 0;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #ffffff;
        }

        .brand-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border-radius: 8px;
            display: grid;
            place-items: center;
            color: #ffffff;
            font-size: 0.95rem;
        }

        .brand-title {
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #ffffff;
            line-height: 1.2;
        }

        .brand-badge {
            font-size: 0.65rem;
            font-family: var(--font-mono);
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            display: block;
        }

        /* User Role Pill inside Sidebar */
        .sidebar-user-role {
            margin: 10px 12px 4px;
            padding: 6px 10px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .sidebar-user-role .role-label {
            font-size: 0.68rem;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.08em;
            color: #94a3b8;
        }

        .sidebar-user-role .role-pill {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* Nav links */
        /* Nav links — the scrollable menu region (all items always reachable) */
        .sidebar-nav {
            padding: 8px 10px 18px;
            flex: 1;
            min-height: 0;            /* required so flex child actually scrolls */
            overflow-y: auto;
            overscroll-behavior: contain;
            scrollbar-width: thin;    /* Firefox: slim scrollbar */
            scrollbar-color: rgba(148, 163, 184, 0.35) transparent;
        }

        /* Chrome/Edge/Safari: slim scrollbar that fits the dark sidebar */
        .sidebar-nav::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar-nav::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.3);
            border-radius: 999px;
        }

        .sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: rgba(148, 163, 184, 0.5);
        }

        /* Static section labels (single-link sections) */
        .nav-section-title {
            font-size: 0.68rem;
            font-family: var(--font-mono);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8; /* WCAG 2.2 AA compliant (5.2:1 against #0b1324) */
            padding: 14px 12px 6px;
            font-weight: 600;
        }

        /* Collapsible section headers */
        .nav-section-toggle {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 6px;
            background: none;
            border: 0;
            padding: 12px 12px 6px;
            font-size: 0.68rem;
            font-family: var(--font-mono);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8; /* WCAG 2.2 AA compliant (5.2:1 against #0b1324) */
            font-weight: 600;
            cursor: pointer;
            border-radius: 6px;
            transition: color 0.15s ease;
        }

        .nav-section-toggle:hover {
            color: #cbd5e1;
        }

        .nav-section-toggle .chevron {
            font-size: 0.55rem;
            transition: transform 0.2s ease;
            margin-left: auto;
        }

        .nav-section-toggle[aria-expanded="false"] .chevron {
            transform: rotate(-90deg);
        }

        .nav-group {
            overflow: hidden;
            transition: opacity 0.2s ease;
        }

        .nav-group.collapsed {
            display: none;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 9px 12px;
            min-height: 42px; /* Touch target accessibility */
            color: #94a3b8;
            font-size: 0.82rem;
            font-weight: 500;
            text-decoration: none;
            border-radius: 7px;
            transition: all 0.15s ease;
            margin-bottom: 2px;
        }

        .sidebar-link i {
            font-size: 0.95rem;
            width: 18px;
            text-align: center;
            color: #64748b;
            transition: color 0.15s ease;
        }

        .sidebar-link:hover {
            color: #f8fafc;
            background: var(--sidebar-item-hover);
        }

        .sidebar-link:hover i {
            color: #38bdf8;
        }

        .sidebar-link.active {
            color: #ffffff;
            background: var(--sidebar-item-active);
            font-weight: 600;
        }

        .sidebar-link.active i {
            color: #38bdf8;
        }

        .sidebar-footer {
            padding: 12px 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.72rem;
            color: #64748b;
            flex-shrink: 0;
        }

        /* -------------------------------------------------------------
           Main Content Canvas & Top Header
        ------------------------------------------------------------- */
        #main-wrapper {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.25s ease;
        }

        #sidebarBackdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(11, 19, 36, 0.65);
            backdrop-filter: blur(4px);
            z-index: 1035;
        }

        @media (max-width: 991.98px) {
            #sidebar {
                transform: translateX(-100%);
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
            }
            body.sidebar-open #sidebar {
                transform: translateX(0);
            }
            body.sidebar-open #sidebarBackdrop {
                display: block;
            }
            #main-wrapper {
                margin-left: 0 !important;
                width: 100% !important;
            }
            .top-header {
                padding: 0 16px !important;
            }
            .content-canvas {
                padding: 16px 14px 40px !important;
            }
        }

        .top-header {
            height: 60px;
            min-height: 60px;
            background: #ffffff;
            border-bottom: 1px solid var(--border-subtle);
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 900;
        }

        .top-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            flex: 1;
            overflow: hidden;
        }

        .top-header-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .telemetry-pulse {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.78rem;
            font-weight: 500;
            color: #059669;
            background: #ecfdf5;
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid #a7f3d0;
        }

        .pulse-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
            animation: pulse-ring 2s infinite;
        }

        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            70% { box-shadow: 0 0 0 5px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .content-canvas {
            padding: 32px 36px 56px;
            flex: 1;
            max-width: 1680px;   /* comfortable line lengths on wide monitors */
            width: 100%;
        }

        /* Consistent page heading (used across all module views) */
        .page-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-heading);
            letter-spacing: -0.025em;
            line-height: 1.2;
        }

        /* -------------------------------------------------------------
           Smooth Page Transitions — subtle, non-blocking entrance that
           runs on every full-page navigation without JS.
        ------------------------------------------------------------- */
        .content-canvas {
            animation: pageEnter 0.18s ease-out;
        }

        @keyframes pageEnter {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        /* Ensure modals and backdrops always maintain correct stacking & interactivity */
        .modal {
            z-index: 1065 !important;
        }
        .modal-backdrop {
            z-index: 1050 !important;
        }
        .modal-dialog {
            z-index: 1070 !important;
            position: relative;
        }

        /* Section headers settle in just after the page canvas */
        .nav-section-toggle,
        .nav-section-title {
            animation: sectionEnter 0.3s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes sectionEnter {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        @media (prefers-reduced-motion: reduce) {
            .content-canvas,
            .nav-section-title,
            .nav-section-toggle {
                animation: none;
            }

            html {
                scroll-behavior: auto;
            }
        }

        /* -------------------------------------------------------------
           Minimalist Components — hairline borders, no drop shadows,
           generous whitespace, restrained color.
        ------------------------------------------------------------- */
        /* Clean Flat Cards */
        .card-panel {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: 12px;
            box-shadow: none;
            overflow: hidden;
            margin-bottom: 28px;
        }

        .card-panel-header {
            padding: 18px 22px;
            border-bottom: 1px solid var(--border-subtle);
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .card-panel-header h5,
        .card-panel-header h6 {
            margin: 0;
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--text-heading);
            letter-spacing: -0.01em;
        }

        .card-panel-body {
            padding: 22px;
        }

        /* KPI Metric Blocks */
        .kpi-tile {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: 12px;
            padding: 18px 20px;
            box-shadow: none;
            transition: border-color 0.2s ease;
            position: relative;
            height: 100%;
        }

        .kpi-tile:hover {
            border-color: var(--border-strong);
        }

        .kpi-tile .kpi-label {
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .kpi-tile .kpi-value {
            font-size: 1.65rem;
            font-weight: 700;
            color: var(--text-heading);
            letter-spacing: -0.03em;
            line-height: 1.1;
        }

        .kpi-tile .kpi-sub {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 6px;
        }

        /* Minimalist Status Badges — soft pills */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .status-active, .status-available, .status-completed {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .status-in_transit, .status-on_trip, .status-dispatched {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .status-maintenance {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .status-scheduled {
            background: #f8fafc;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .status-out_of_service, .status-cancelled {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Contextual Policy & Acronym Tooltip Badges (Issue #21) */
        .policy-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 7px;
            border-radius: 6px;
            font-size: 0.72rem;
            font-family: var(--font-mono);
            font-weight: 600;
            letter-spacing: 0.02em;
            cursor: help;
            text-decoration: none;
            border: 1px dashed;
            vertical-align: middle;
            transition: all 0.15s ease;
        }

        .policy-badge-info {
            background: #f0f9ff;
            color: #0369a1;
            border-color: #7dd3fc;
        }
        .policy-badge-info:hover, .policy-badge-info:focus-visible {
            background: #e0f2fe;
            color: #0284c7;
            border-color: #38bdf8;
            outline: none;
        }

        .policy-badge-warning {
            background: #fffbeb;
            color: #b45309;
            border-color: #fde68a;
        }
        .policy-badge-warning:hover, .policy-badge-warning:focus-visible {
            background: #fef3c7;
            color: #92400e;
            border-color: #f59e0b;
            outline: none;
        }

        .policy-badge-danger {
            background: #fef2f2;
            color: #b91c1c;
            border-color: #fca5a5;
        }
        .policy-badge-danger:hover, .policy-badge-danger:focus-visible {
            background: #fee2e2;
            color: #991b1b;
            border-color: #ef4444;
            outline: none;
        }

        .policy-info-icon {
            font-size: 0.68rem;
            opacity: 0.85;
        }

        /* Custom High-Contrast Tooltip Styling */
        .tooltip .tooltip-inner {
            max-width: 300px;
            padding: 9px 13px;
            font-size: 0.78rem;
            line-height: 1.45;
            text-align: left;
            border-radius: 8px;
            background-color: #0f172a;
            color: #f8fafc;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .tooltip .tooltip-inner strong {
            color: #38bdf8;
            display: inline-block;
            margin-bottom: 2px;
        }

        .tooltip .tooltip-arrow::before {
            border-top-color: #0f172a !important;
            border-bottom-color: #0f172a !important;
        }

        /* Minimalist Table */
        .table-minimal {
            width: 100%;
            margin: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-minimal thead th {
            background: #f8fafc;
            font-size: 0.72rem;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            padding: 12px 18px;
            border-bottom: 1px solid var(--border-subtle);
            border-top: none;
        }

        .table-minimal tbody td {
            padding: 14px 18px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.86rem;
            color: var(--text-body);
        }

        .table-minimal tbody tr:last-child td {
            border-bottom: none;
        }

        .table-minimal tbody tr:hover td {
            background-color: #f8fafc;
        }

        /* Subtle zebra striping for long ledgers */
        .table-minimal tbody tr:nth-child(even) td {
            background-color: #fbfdff;
        }

        .table-minimal tbody tr:nth-child(even):hover td {
            background-color: #f8fafc;
        }

        /* -------------------------------------------------------------
           Unified Minimalist Button System & Interaction Tokens
        ------------------------------------------------------------- */
        .btn, .btn-corp {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-sans);
            font-weight: 500;
            font-size: 0.82rem;
            line-height: 1;
            height: 36px;
            padding: 0 14px;
            border-radius: 7px;
            white-space: nowrap;
            vertical-align: middle;
            gap: 6px;
            border: 1px solid transparent;
            text-decoration: none !important;
            box-shadow: none !important;
            transition: all 0.15s ease-in-out;
            cursor: pointer;
            user-select: none;
        }

        .btn-sm, .btn-corp-sm {
            height: 30px;
            padding: 0 10px;
            font-size: 0.76rem;
            border-radius: 6px;
            gap: 5px;
        }

        .btn-xs {
            height: 24px;
            padding: 0 7px;
            font-size: 0.7rem;
            border-radius: 5px;
            gap: 4px;
        }

        .btn-icon {
            width: 36px;
            height: 36px;
            padding: 0 !important;
        }

        .btn-icon-sm {
            width: 30px;
            height: 30px;
            padding: 0 !important;
            font-size: 0.78rem;
        }

        .btn-icon-xs {
            width: 24px;
            height: 24px;
            padding: 0 !important;
            font-size: 0.7rem;
        }

        /* Color variants */
        .btn-primary, .btn-corp-primary {
            background: #0f172a;
            color: #ffffff !important;
            border-color: #0f172a;
        }
        .btn-primary:hover, .btn-corp-primary:hover {
            background: #1e293b;
            border-color: #1e293b;
            color: #ffffff !important;
        }
        .btn-primary:active, .btn-corp-primary:active {
            background: #334155;
            border-color: #334155;
        }

        .btn-secondary, .btn-corp-secondary {
            background: #ffffff;
            color: #334155 !important;
            border-color: var(--border-strong);
        }
        .btn-secondary:hover, .btn-corp-secondary:hover {
            background: #f8fafc;
            border-color: #94a3b8;
            color: #0f172a !important;
        }

        .btn-outline-secondary {
            background: #ffffff;
            color: #475569 !important;
            border-color: var(--border-strong);
        }
        .btn-outline-secondary:hover {
            background: #f1f5f9;
            color: #0f172a !important;
            border-color: #94a3b8;
        }

        .btn-outline-primary {
            background: #ffffff;
            color: #2563eb !important;
            border-color: #bfdbfe;
        }
        .btn-outline-primary:hover {
            background: #eff6ff;
            color: #1d4ed8 !important;
            border-color: #93c5fa;
        }

        .btn-outline-danger {
            background: #ffffff;
            color: #dc2626 !important;
            border-color: #fecaca;
        }
        .btn-outline-danger:hover {
            background: #fef2f2;
            color: #b91c1c !important;
            border-color: #f87171;
        }

        .btn-outline-success {
            background: #ffffff;
            color: #059669 !important;
            border-color: #bbf7d0;
        }
        .btn-outline-success:hover {
            background: #ecfdf5;
            color: #047857 !important;
            border-color: #86efac;
        }

        .btn-outline-dark {
            background: #ffffff;
            color: #0f172a !important;
            border-color: var(--border-strong);
        }
        .btn-outline-dark:hover {
            background: #f1f5f9;
            color: #0f172a !important;
            border-color: #0f172a;
        }

        .btn-danger {
            background: #dc2626;
            color: #ffffff !important;
            border-color: #dc2626;
        }
        .btn-danger:hover {
            background: #b91c1c;
            border-color: #b91c1c;
            color: #ffffff !important;
        }

        .btn-success {
            background: #059669;
            color: #ffffff !important;
            border-color: #059669;
        }
        .btn-success:hover {
            background: #047857;
            border-color: #047857;
            color: #ffffff !important;
        }

        .btn-warning {
            background: #f59e0b;
            color: #0f172a !important;
            border-color: #f59e0b;
        }
        .btn-warning:hover {
            background: #d97706;
            border-color: #d97706;
            color: #ffffff !important;
        }

        /* -------------------------------------------------------------
           Table Action Columns & Actions Alignment
        ------------------------------------------------------------- */
        .table-minimal th:last-child,
        .table-minimal td:last-child,
        .table-action-cell {
            white-space: nowrap !important;
            width: 1%;
            text-align: right;
        }

        .table-actions {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
            flex-wrap: nowrap;
            vertical-align: middle;
        }

        /* -------------------------------------------------------------
           Minimalist Form Controls
        ------------------------------------------------------------- */
        .form-control, .form-select {
            font-family: var(--font-sans);
            font-size: 0.84rem;
            color: var(--text-heading);
            background-color: #ffffff;
            border: 1px solid var(--border-strong);
            border-radius: 7px;
            padding: 7px 12px;
            box-shadow: none !important;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #2563eb;
            outline: 2px solid rgba(37, 99, 235, 0.2);
            outline-offset: 1px;
            background-color: #ffffff;
        }

        .form-control-sm, .form-select-sm {
            font-size: 0.78rem;
            padding: 5px 10px;
            border-radius: 6px;
            height: 32px;
        }

        /* -------------------------------------------------------------
           Print — content only, no application chrome
        ------------------------------------------------------------- */
        @media print {
            #sidebar,
            #sidebarBackdrop,
            .top-header,
            .btn,
            button,
            #systemGuideModal {
                display: none !important;
            }

            #main-wrapper {
                margin-left: 0 !important;
            }

            .content-canvas {
                padding: 0 !important;
                animation: none;
            }

            .card-panel,
            .kpi-tile {
                box-shadow: none;
                break-inside: avoid;
            }

            body {
                background: #ffffff;
            }
        }
    </style>
    <!-- Leaflet JS & Chart.js -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <?php
        $userRole = session()->get('user_role') ?? 'dispatcher';
        $userName = session()->get('user_name') ?? 'System User';
        $userEmail = session()->get('user_email') ?? 'user@fleet.com';

        // In-app notification badge (BRD flows always write a notification)
        $notifUnread = 0;
        try {
            $notifUnread = (int) (new \App\Models\NotificationModel())
                ->getUnreadCount((int) session()->get('user_id'));
        } catch (\Throwable $e) {
            $notifUnread = 0;
        }

        // Role aesthetic configurations for all 7 Government RBAC roles
        $roleBadges = [
            'admin'          => ['label' => 'Super Admin / Director', 'color' => '#3b82f6', 'bg' => 'rgba(59, 130, 246, 0.15)'],
            'requestor'      => ['label' => 'Staff / Requestor', 'color' => '#8b5cf6', 'bg' => 'rgba(139, 92, 246, 0.15)'],
            'approver_admin' => ['label' => 'Tier 2 Approver (Admin Head)', 'color' => '#06b6d4', 'bg' => 'rgba(6, 182, 212, 0.15)'],
            'dispatcher'     => ['label' => 'Motorpool Dispatcher (Tier 1 Approver & Asset Allocation)', 'color' => '#10b981', 'bg' => 'rgba(16, 185, 129, 0.15)'],
            'guard'          => ['label' => 'Gate Security Officer', 'color' => '#64748b', 'bg' => 'rgba(100, 116, 139, 0.15)'],
            'auditor'        => ['label' => 'COA Resident Auditor', 'color' => '#d97706', 'bg' => 'rgba(217, 119, 6, 0.15)'],
            'driver'         => ['label' => 'Official Driver', 'color' => '#0284c7', 'bg' => 'rgba(2, 132, 199, 0.15)'],
            'maintenance'    => ['label' => 'Safety & PMS Tech', 'color' => '#ea580c', 'bg' => 'rgba(234, 88, 12, 0.15)'],
        ];
        $currentBadge = $roleBadges[$userRole] ?? ['label' => ucfirst($userRole), 'color' => '#3b82f6', 'bg' => 'rgba(59, 130, 246, 0.15)'];

        // Breadcrumb: which sidebar section the current page belongs to
        $sectionMap = [
            'requests' => 'Official Travel Workflow', 'approvals' => 'Official Travel Workflow',
            'dispatch' => 'Official Travel Workflow', 'tickets' => 'Official Travel Workflow',
            'gate'     => 'Official Travel Workflow',
            'safety'   => 'Safety & Preventive Ops',  'pir'      => 'Safety & Preventive Ops',
            'rfid'     => 'Safety & Preventive Ops',
            'vehicles' => 'Fleet Assets', 'drivers' => 'Fleet Assets',
            'tracking' => 'Fleet Assets', 'fuel'    => 'Fleet Assets', 'maintenance' => 'Fleet Assets',
            'audit'    => 'COA & Compliance', 'compliance' => 'COA & Compliance',
            'users'    => 'Administration', 'admin' => 'Administration', 'dashboard' => 'Overview',
        ];
        $currentUri   = uri_string();
        $crumbSection = 'Overview';
        foreach ($sectionMap as $prefix => $section) {
            if ($currentUri === $prefix || str_starts_with($currentUri, $prefix . '/')) {
                $crumbSection = $section;
                break;
            }
        }

        // Brand logo points at this role's actual landing page so it never
        // bounces non-ops roles with an "Access Restricted" flash.
        $homeRoute = match ($userRole) {
            'requestor'                     => 'requests',
            'approver_admin'                => 'approvals',
            'dispatcher'                    => 'dispatch',
            'auditor'                       => 'audit',
            'guard'                         => 'gate',
            'driver'                        => 'driver/trips',
            default                         => '',
        };

        // Calculate pending approvals count for badge in sidebar
        $pendingApprovalsBadge = 0;
        if (in_array($userRole, ['admin', 'dispatcher', 'approver_admin'], true)) {
            try {
                $db = \Config\Database::connect();
                if ($userRole === 'dispatcher') {
                    $pendingApprovalsBadge = $db->table('trip_requests')->where('status', 'pending_dispatcher')->countAllResults();
                } elseif ($userRole === 'approver_admin') {
                    $pendingApprovalsBadge = $db->table('trip_requests')->where('status', 'pending_admin')->countAllResults();
                } else {
                    $pendingApprovalsBadge = $db->table('trip_requests')->whereIn('status', ['pending_dispatcher', 'pending_admin'])->countAllResults();
                }
            } catch (\Throwable $e) {
                $pendingApprovalsBadge = 0;
            }
        }
    ?>

    <!-- Sidebar Navigation -->
    <aside id="sidebar">
        <!-- Brand Header -->
        <div class="sidebar-brand">
            <a href="<?= base_url($homeRoute) ?>" class="brand-logo">
                <div class="brand-icon" style="background: linear-gradient(135deg, #0A2540, #1e3a8a);">
                    <i class="fa-solid fa-landmark"></i>
                </div>
                <div>
                    <div class="brand-title">PIA <span class="text-primary-accent" style="color: #38bdf8;">Motorpool</span></div>
                    <span class="brand-badge">Republic of the Philippines</span>
                </div>
            </a>
        </div>

        <!-- Role Pill -->
        <div class="sidebar-user-role">
            <span class="role-label"><i class="fa-solid fa-shield-halved me-1 text-muted"></i> Role</span>
            <span class="role-pill" style="color: <?= $currentBadge['color'] ?>; background: <?= $currentBadge['bg'] ?>;">
                <?= esc($currentBadge['label']) ?>
            </span>
        </div>

        <!-- Dynamic Role-Tailored Navigation (Eliminates Redundancy) -->
        <nav class="sidebar-nav">

            <!-- ============================================================
                 1 · OVERVIEW
            ============================================================ -->
            <?php if (in_array($userRole, ['admin', 'dispatcher', 'maintenance', 'approver_admin'])): ?>
                <div class="nav-section-title">Overview</div>
                <a href="<?= base_url('dashboard') ?>" class="sidebar-link <?= uri_string() === '' || uri_string() === 'dashboard' ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-line"></i> Command Center
                </a>
            <?php endif; ?>

            <!-- ============================================================
                 2 · OFFICIAL TRAVEL WORKFLOW (BRD Modules 1–3, 5)
            ============================================================ -->
            <?php if (in_array($userRole, ['admin', 'dispatcher', 'requestor', 'approver_admin', 'auditor', 'guard', 'driver'])): ?>
                <button type="button" class="nav-section-toggle" data-nav-group="workflow" aria-expanded="true">
                    Official Travel Workflow <i class="fa-solid fa-chevron-down chevron"></i>
                </button>
                <div class="nav-group" data-nav-group-body="workflow">

                <?php if (in_array($userRole, ['admin', 'dispatcher', 'requestor', 'approver_admin', 'auditor'])): ?>
                    <a href="<?= base_url('requests') ?>" class="sidebar-link <?= strpos(uri_string(), 'requests') === 0 ? 'active' : '' ?>">
                        <i class="fa-solid fa-file-signature"></i> Vehicle Requests (VRS)
                    </a>
                <?php endif; ?>

                <?php if (in_array($userRole, ['admin', 'approver_admin', 'dispatcher'])): ?>
                    <a href="<?= base_url('approvals') ?>" class="sidebar-link d-flex align-items-center justify-content-between <?= strpos(uri_string(), 'approvals') === 0 ? 'active' : '' ?>">
                        <span><i class="fa-solid fa-stamp"></i> Approvals &amp; 4h Escalation</span>
                        <?php if ($pendingApprovalsBadge > 0): ?>
                            <span class="badge rounded-pill bg-danger" style="font-size: 0.65rem; padding: 2px 7px;" title="<?= $pendingApprovalsBadge ?> pending requests awaiting action"><?= $pendingApprovalsBadge ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>

                <?php if (in_array($userRole, ['admin', 'dispatcher', 'approver_admin'])): ?>
                    <a href="<?= base_url('dispatch') ?>" class="sidebar-link <?= strpos(uri_string(), 'dispatch') === 0 && strpos(uri_string(), 'calendar') === false ? 'active' : '' ?>">
                        <i class="fa-solid fa-truck-fast"></i> Dispatch Console
                    </a>
                    <a href="<?= base_url('dispatch/calendar') ?>" class="sidebar-link <?= strpos(uri_string(), 'dispatch/calendar') === 0 ? 'active' : '' ?>">
                        <i class="fa-solid fa-calendar-days"></i> Allocation Calendar
                    </a>
                <?php endif; ?>

                <?php if (in_array($userRole, ['admin', 'dispatcher', 'driver', 'guard', 'auditor', 'approver_admin'])): ?>
                    <a href="<?= base_url('tickets') ?>" class="sidebar-link <?= strpos(uri_string(), 'tickets') === 0 ? 'active' : '' ?>">
                        <i class="fa-solid fa-ticket"></i> Driver's Trip Tickets (e-DTT)
                    </a>
                <?php endif; ?>

                <?php if (in_array($userRole, ['admin', 'guard', 'dispatcher', 'approver_admin'])): ?>
                    <a href="<?= base_url('gate') ?>" class="sidebar-link <?= strpos(uri_string(), 'gate') === 0 ? 'active' : '' ?>">
                        <i class="fa-solid fa-shield-halved"></i> Gate QR Checkpoint
                    </a>
                <?php endif; ?>
                </div><!-- /nav-group workflow -->
            <?php endif; ?>

            <!-- ============================================================
                 3 · SAFETY & PREVENTIVE OPERATIONS (BRD Modules 5–6)
            ============================================================ -->
            <?php if (in_array($userRole, ['admin', 'dispatcher', 'driver', 'auditor', 'maintenance', 'approver_admin'])): ?>
                <button type="button" class="nav-section-toggle" data-nav-group="safety" aria-expanded="true">
                    Safety &amp; Preventive Ops <i class="fa-solid fa-chevron-down chevron"></i>
                </button>
                <div class="nav-group" data-nav-group-body="safety">

                <?php if (in_array($userRole, ['admin', 'dispatcher', 'driver', 'auditor', 'approver_admin'])): ?>
                    <a href="<?= base_url('safety') ?>" class="sidebar-link <?= strpos(uri_string(), 'safety') === 0 ? 'active' : '' ?>">
                        <i class="fa-solid fa-clipboard-check"></i> BLOWBAGETS Safety Checks
                    </a>
                <?php endif; ?>

                <?php if (in_array($userRole, ['admin', 'dispatcher', 'maintenance', 'approver_admin'])): ?>
                    <a href="<?= base_url('pir') ?>" class="sidebar-link <?= strpos(uri_string(), 'pir') === 0 ? 'active' : '' ?>">
                        <i class="fa-solid fa-screwdriver-wrench"></i> Mechanic PIR Queue
                    </a>
                <?php endif; ?>

                <?php if (in_array($userRole, ['admin', 'dispatcher', 'auditor', 'approver_admin'])): ?>
                    <a href="<?= base_url('rfid') ?>" class="sidebar-link <?= strpos(uri_string(), 'rfid') === 0 ? 'active' : '' ?>">
                        <i class="fa-solid fa-credit-card"></i> Tollway RFID Cards
                    </a>
                <?php endif; ?>
                </div><!-- /nav-group safety -->
            <?php endif; ?>

            <!-- ============================================================
                 4 · FLEET ASSETS & INFRASTRUCTURE
            ============================================================ -->
            <?php if (in_array($userRole, ['admin', 'dispatcher', 'maintenance', 'approver_admin'])): ?>
                <button type="button" class="nav-section-toggle" data-nav-group="assets" aria-expanded="true">
                    Fleet Assets &amp; Infrastructure <i class="fa-solid fa-chevron-down chevron"></i>
                </button>
                <div class="nav-group" data-nav-group-body="assets">
                <a href="<?= base_url('vehicles') ?>" class="sidebar-link <?= strpos(uri_string(), 'vehicles') === 0 ? 'active' : '' ?>">
                    <i class="fa-solid fa-truck"></i> Vehicle Fleet (5K PMS)
                </a>
                <?php if (in_array($userRole, ['admin', 'dispatcher', 'approver_admin'])): ?>
                    <a href="<?= base_url('drivers') ?>" class="sidebar-link <?= strpos(uri_string(), 'drivers') === 0 ? 'active' : '' ?>">
                        <i class="fa-solid fa-id-card"></i> Driver Personnel
                    </a>
                <?php endif; ?>
                <a href="<?= base_url('tracking') ?>" class="sidebar-link <?= uri_string() === 'tracking' ? 'active' : '' ?>">
                    <i class="fa-solid fa-satellite-dish"></i> Live GPS Radar
                </a>
                <?php if (in_array($userRole, ['admin', 'dispatcher', 'approver_admin'])): ?>
                    <a href="<?= base_url('fuel') ?>" class="sidebar-link <?= strpos(uri_string(), 'fuel') === 0 ? 'active' : '' ?>">
                        <i class="fa-solid fa-gas-pump"></i> Fuel Records
                    </a>
                <?php endif; ?>
                <?php if (in_array($userRole, ['admin', 'maintenance', 'approver_admin'])): ?>
                    <a href="<?= base_url('maintenance') ?>" class="sidebar-link <?= strpos(uri_string(), 'maintenance') === 0 ? 'active' : '' ?>">
                        <i class="fa-solid fa-wrench"></i> Service Orders &amp; PMS
                    </a>
                <?php endif; ?>
                </div><!-- /nav-group assets -->
            <?php endif; ?>

            <!-- ============================================================
                 5 · COA & REGULATORY COMPLIANCE (BRD Module 7, NFR-3)
            ============================================================ -->
            <?php if (in_array($userRole, ['admin', 'auditor'])): ?>
                <button type="button" class="nav-section-toggle" data-nav-group="compliance" aria-expanded="true">
                    COA &amp; Regulatory Compliance <i class="fa-solid fa-chevron-down chevron"></i>
                </button>
                <div class="nav-group" data-nav-group-body="compliance">
                <a href="<?= base_url('audit') ?>" class="sidebar-link <?= uri_string() === 'audit' ? 'active' : '' ?>">
                    <i class="fa-solid fa-scale-balanced"></i> COA Compliance &amp; Audits
                </a>
                <a href="<?= base_url('compliance') ?>" class="sidebar-link <?= strpos(uri_string(), 'compliance') === 0 ? 'active' : '' ?>">
                    <i class="fa-solid fa-file-shield"></i> Compliance Portal (Form B)
                </a>
                <a href="<?= base_url('audit/logs') ?>" class="sidebar-link <?= strpos(uri_string(), 'audit/logs') === 0 ? 'active' : '' ?>">
                    <i class="fa-solid fa-clock-rotate-left"></i> System Audit Trail
                </a>
                </div><!-- /nav-group compliance -->
            <?php endif; ?>

            <!-- ============================================================
                 6 · ADMINISTRATION & ARCHITECTURE
            ============================================================ -->
            <?php if (in_array($userRole, ['admin', 'dispatcher', 'approver_admin'])): ?>
                <div class="nav-section-title">Administration &amp; Architecture</div>
                <a href="<?= base_url('admin/flowchart') ?>" class="sidebar-link <?= strpos(uri_string(), 'admin/flowchart') === 0 ? 'active' : '' ?>" title="System Architecture Flowchart &amp; Module Navigations">
                    <i class="fa-solid fa-diagram-project"></i> Flowchart &amp; Navigations
                    <span class="badge ms-auto" style="font-size: 0.58rem; background: rgba(16, 185, 129, 0.2); color: #34d399; letter-spacing: 0.06em;">VISUAL</span>
                </a>
                <?php if ($userRole === 'admin'): ?>
                <a href="<?= base_url('users') ?>" class="sidebar-link <?= strpos(uri_string(), 'users') === 0 ? 'active' : '' ?>" title="Provision and manage user accounts for every role">
                    <i class="fa-solid fa-users-gear"></i> User Accounts
                    <span class="badge ms-auto" style="font-size: 0.58rem; background: rgba(59, 130, 246, 0.2); color: #93c5fd; letter-spacing: 0.06em;">SUPER ADMIN</span>
                </a>
                <?php endif; ?>
            <?php endif; ?>

            <!-- ============================================================
                 7 · MOBILE OPERATIONS
            ============================================================ -->
            <?php if (in_array($userRole, ['admin', 'dispatcher', 'driver'])): ?>
                <div class="nav-section-title">Driver Operations</div>
                <a href="<?= base_url('driver/trips') ?>" target="_blank" class="sidebar-link">
                    <i class="fa-solid fa-compass"></i> Driver Web Portal <i class="fa-solid fa-arrow-up-right-from-square ms-auto text-muted small"></i>
                </a>
            <?php endif; ?>
        </nav>

        <!-- Sidebar Footer -->
        <div class="sidebar-footer">
            <span class="mono"><i class="fa-solid fa-shield-halved text-success me-1"></i> GovTech Secured</span>
            <span class="mono">PIA-AFMS v2.1</span>
        </div>
    </aside>
    <div id="sidebarBackdrop"></div>

    <!-- Main Content Area -->
    <div id="main-wrapper">
        <!-- Top Header Bar -->
        <header class="top-header">
            <div class="top-header-left">
                <button type="button" class="btn btn-icon-sm btn-outline-secondary d-lg-none" id="sidebarToggle" aria-label="Toggle Sidebar Menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="telemetry-pulse d-none d-md-inline-flex flex-shrink-0">
                    <div class="pulse-dot"></div>
                    <span>Telematics Online</span>
                </div>
                <span class="badge border d-none d-xl-inline-flex align-items-center gap-1 flex-shrink-0" style="font-size: 0.72rem; padding: 4px 8px; background: #ffffff; color: var(--text-body);" title="Active Security: CSRF Enabled, Secure Headers (SAMEORIGIN, nosniff), Rate-Limited Auth, RBAC Enforced">
                    <i class="fa-solid fa-shield-halved text-success"></i> Secured (CSRF &bull; RBAC &bull; Rate-Limit)
                </span>

                <!-- Contextual breadcrumb: current sidebar section / page -->
                <nav aria-label="breadcrumb" class="d-none d-lg-flex align-items-center gap-2 ms-1 text-truncate">
                    <span class="text-muted text-truncate" style="font-size: 0.76rem;">
                        <i class="fa-solid fa-house me-1" style="font-size: 0.65rem;"></i><?= esc($crumbSection) ?>
                    </span>
                    <span class="text-muted" style="font-size: 0.65rem;">/</span>
                    <span class="fw-semibold text-dark text-truncate" style="font-size: 0.76rem; max-width: 240px;"><?= esc($title ?? 'Dashboard') ?></span>
                </nav>
            </div>

            <!-- Profile & Actions Dropdown -->
            <div class="top-header-right">
                <!-- In-App Notification Bell -->
                <a href="<?= base_url('notifications') ?>" class="btn btn-sm btn-outline-secondary btn-icon-sm position-relative" title="My Notifications">
                    <i class="fa-solid fa-bell text-secondary"></i>
                    <?php if ($notifUnread > 0): ?>
                        <span class="position-absolute badge rounded-pill bg-danger d-flex align-items-center justify-content-center" style="font-size: 0.55rem; min-width: 15px; height: 15px; top: -3px; right: -3px; padding: 0 3px;">
                            <?= $notifUnread > 99 ? '99+' : $notifUnread ?>
                        </span>
                    <?php endif; ?>
                </a>

                <!-- System Guide & User Manual Trigger -->
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#systemGuideModal" title="Open Complete System Operations & Security Guide">
                    <i class="fa-solid fa-book-open text-primary"></i>
                    <span class="d-none d-md-inline ms-1 fw-semibold">System Manual</span>
                </button>

                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-circle-user text-primary"></i>
                        <span class="fw-semibold text-truncate d-none d-sm-inline" style="max-width: 130px;"><?= esc($userName) ?></span>
                        <i class="fa-solid fa-chevron-down text-muted" style="font-size: 0.6rem;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border mt-1">
                        <li>
                            <div class="px-3 py-2 border-bottom">
                                <strong class="small d-block text-dark"><?= esc($userName) ?></strong>
                                <span class="small text-muted font-monospace"><?= esc($userEmail) ?></span>
                            </div>
                        </li>
                        <li>
                            <div class="px-3 py-1 text-muted small">
                                Role: <strong class="text-dark"><?= ucfirst(esc($userRole)) ?></strong>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item small py-2" href="#" data-bs-toggle="modal" data-bs-target="#systemGuideModal">
                                <i class="fa-solid fa-circle-question me-2 text-primary"></i> System Guide & Cheatsheet
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item small py-2" href="<?= base_url('mfa/setup') ?>">
                                <i class="fa-solid fa-shield-halved me-2 text-success"></i> Two-Factor Authentication (MFA)
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger small py-2" href="<?= base_url('logout') ?>">
                                <i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Sign Out
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Flash Notifications (Global) -->
        <div class="px-4 pt-3 pb-0">
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show p-3 rounded-3 border d-flex align-items-center" role="alert" style="background: #f0fdf4; color: #166534; border-color: #bbf7d0 !important;">
                    <i class="fa-solid fa-circle-check me-2 fs-5"></i>
                    <div><?= session()->getFlashdata('success') ?></div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show p-3 rounded-3 border d-flex align-items-center" role="alert" style="background: #fef2f2; color: #991b1b; border-color: #fecaca !important;">
                    <i class="fa-solid fa-triangle-exclamation me-2 fs-5"></i>
                    <div><?= session()->getFlashdata('error') ?></div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Rendered Page Content -->
        <main class="content-canvas">
            <?= $this->renderSection('content') ?>
        </main>
    </div>

    <!-- System Operations & Security Architecture Manual Modal -->
    <div class="modal fade" id="systemGuideModal" tabindex="-1" aria-labelledby="systemGuideModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border-radius: 12px; border: 1px solid var(--border-subtle); box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
                <!-- Modal Header -->
                <div class="modal-header border-bottom py-3 px-4" style="background: #f8fafc;">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 40px; height: 40px; border-radius: 8px; background: #0f172a; color: #ffffff; display: grid; place-items: center; font-size: 1.1rem;">
                            <i class="fa-solid fa-book-open-reader"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark mb-0" id="systemGuideModalLabel">PIA Motorpool Operations & Security Manual</h5>
                            <span class="small text-muted font-monospace">Architecture, Operational Workflows & Active Security Protocols</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Navigation Tabs -->
                <div class="border-bottom px-4 pt-2" style="background: #f8fafc;">
                    <ul class="nav nav-tabs border-0" id="guideTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-semibold small" id="workflows-tab" data-bs-toggle="tab" data-bs-target="#tab-workflows" type="button" role="tab">
                                <i class="fa-solid fa-diagram-project me-1 text-primary"></i> 1. Core Lifecycle & Modules
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold small" id="roles-tab" data-bs-toggle="tab" data-bs-target="#tab-roles" type="button" role="tab">
                                <i class="fa-solid fa-users-gear me-1 text-success"></i> 2. Roles & Access Matrix
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold small" id="security-tab" data-bs-toggle="tab" data-bs-target="#tab-security" type="button" role="tab">
                                <i class="fa-solid fa-shield-halved me-1 text-danger"></i> 3. Security Safeguards
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold small" id="radar-tab" data-bs-toggle="tab" data-bs-target="#tab-radar" type="button" role="tab">
                                <i class="fa-brands fa-google me-1 text-info"></i> 4. Google Maps & Radar
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold small" id="api-tab" data-bs-toggle="tab" data-bs-target="#tab-api" type="button" role="tab">
                                <i class="fa-solid fa-terminal me-1 text-warning"></i> 5. REST & IoT APIs
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Modal Body -->
                <div class="modal-body p-4" style="font-size: 0.86rem; line-height: 1.6;">
                    <div class="tab-content" id="guideTabsContent">

                        <!-- TAB 1: Core Lifecycle & Modules -->
                        <div class="tab-pane fade show active" id="tab-workflows" role="tabpanel">
                            <div class="alert alert-primary border-0 rounded-2 py-2 px-3 mb-4 d-flex align-items-center gap-2">
                                <i class="fa-solid fa-circle-info fs-5"></i>
                                <span>The PIA Motorpool Automated Fleet Management System orchestrates official government travel through a closed-loop verified workflow — from vehicle request to trip completion and COA compliance.</span>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="p-3 border rounded-2 bg-white h-100">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge bg-primary">Module 01</span>
                                            <strong class="text-dark">Vehicle Fleet Registry</strong>
                                        </div>
                                        <p class="text-muted small mb-2">Maintains complete records of all transport assets, including VIN, plate number, fuel tank capacity, current odometer, and PMS thresholds.</p>
                                        <ul class="small text-muted ps-3 mb-0">
                                            <li><strong>Statuses:</strong> Active, In Transit, Maintenance, Out of Service.</li>
                                            <li>Tracks engine status, battery voltage, and coolant temperature.</li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="p-3 border rounded-2 bg-white h-100">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge bg-primary">Module 02</span>
                                            <strong class="text-dark">Driver Personnel & Compliance</strong>
                                        </div>
                                        <p class="text-muted small mb-2">Manages certified operators, heavy-vehicle licenses, emergency contacts, and algorithmic safety scoring.</p>
                                        <ul class="small text-muted ps-3 mb-0">
                                            <li><strong>Safety Scoring:</strong> Penalizes overspeeding incidents (>80 km/h) automatically.</li>
                                            <li>Prevents dispatching drivers with expired licenses or unverified status.</li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="p-3 border rounded-2 bg-white h-100">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge bg-primary">Module 03</span>
                                            <strong class="text-dark">Trip Dispatch & Waybills</strong>
                                        </div>
                                        <p class="text-muted small mb-2">Assigns truck and driver to a corridor with auto-calculated OSRM road distance, scheduled departure, and cargo tracking.</p>
                                        <ul class="small text-muted ps-3 mb-0">
                                            <li><strong>Anti-Fraud Odometer Lock:</strong> At trip completion, arrival odometer is logged and cross-verified against route distance.</li>
                                            <li>Dispatches alert drivers via simulated SMS transmission.</li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="p-3 border rounded-2 bg-white h-100">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge bg-primary">Module 04</span>
                                            <strong class="text-dark">Technical Shop & Maintenance (PMS)</strong>
                                        </div>
                                        <p class="text-muted small mb-2">Automates preventive maintenance intervals (every 10,000 km) and captures real-time OBD-II vehicle diagnostic error codes.</p>
                                        <ul class="small text-muted ps-3 mb-0">
                                            <li><strong>OBD-II Alerts:</strong> Engine fault codes (e.g. P0420) instantly trigger high-priority work orders.</li>
                                            <li>Completing service recalibrates the next maintenance threshold.</li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="p-3 border rounded-2 bg-white">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge bg-primary">Module 05</span>
                                            <strong class="text-dark">Fuel Logging & Financial Audits</strong>
                                        </div>
                                        <p class="text-muted small mb-0">Captures fuel volume (liters), cost (₱), odometer readings, and receipt image uploads. Computes the executive Fleet Cost per Kilometer metric (Total Operating Expenses &divide; Total Fleet Distance) to identify operational wastage.</p>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="p-3 border rounded-2" style="background: #f8fafc; border-color: #cbd5e1 !important;">
                                        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fa-solid fa-diagram-project text-primary fs-5"></i>
                                                <strong class="text-dark">Official Travel &amp; Maintenance Flowchart</strong>
                                            </div>
                                            <a href="<?= base_url('admin/flowchart') ?>" class="btn btn-sm btn-primary text-decoration-none">
                                                <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Open Interactive Flowchart &amp; Nav
                                            </a>
                                        </div>
                                        <p class="text-muted small mb-3">View sequential flow nodes from VRS Request, 2-Tier SLA Approvals, Dispatch Assignment, BLOWBAGETS Checklist, Gate QR Scan, Live GPS Telematics, to Post-Trip COA Audit.</p>
                                        <div class="d-flex align-items-center gap-2 overflow-auto pb-1" style="font-size: 0.72rem;">
                                            <span class="badge bg-light text-dark border"><i class="fa-solid fa-file-pen text-primary me-1"></i>1. VRS</span>
                                            <i class="fa-solid fa-chevron-right text-muted" style="font-size: 0.6rem;"></i>
                                            <span class="badge bg-light text-dark border"><i class="fa-solid fa-stamp text-warning me-1"></i>2. Approval</span>
                                            <i class="fa-solid fa-chevron-right text-muted" style="font-size: 0.6rem;"></i>
                                            <span class="badge bg-light text-dark border"><i class="fa-solid fa-truck text-success me-1"></i>3. Dispatch</span>
                                            <i class="fa-solid fa-chevron-right text-muted" style="font-size: 0.6rem;"></i>
                                            <span class="badge bg-light text-dark border"><i class="fa-solid fa-clipboard-check text-purple me-1"></i>4. Safety</span>
                                            <i class="fa-solid fa-chevron-right text-muted" style="font-size: 0.6rem;"></i>
                                            <span class="badge bg-light text-dark border"><i class="fa-solid fa-qrcode text-secondary me-1"></i>5. Gate</span>
                                            <i class="fa-solid fa-chevron-right text-muted" style="font-size: 0.6rem;"></i>
                                            <span class="badge bg-light text-dark border"><i class="fa-solid fa-satellite-dish text-info me-1"></i>6. Radar</span>
                                            <i class="fa-solid fa-chevron-right text-muted" style="font-size: 0.6rem;"></i>
                                            <span class="badge bg-light text-dark border"><i class="fa-solid fa-scale-balanced text-danger me-1"></i>7. Audit</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: Roles & Permissions (RBAC) -->
                        <div class="tab-pane fade" id="tab-roles" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle">
                                    <thead class="table-light">
                                        <tr class="small text-uppercase">
                                            <th>Role & Designation</th>
                                            <th>VRS Approvals</th>
                                            <th>Dispatch & Assets</th>
                                            <th>Trip Tickets (e-DTT)</th>
                                            <th>Gate Checkpoint</th>
                                            <th>Safety & PMS</th>
                                            <th>COA & Compliance</th>
                                        </tr>
                                    </thead>
                                    <tbody class="small">
                                        <tr>
                                            <td>
                                                <span class="badge" style="background:#3b82f6;">Administrator</span>
                                                <div class="text-muted" style="font-size:0.7rem;">Director-General / Super Admin</div>
                                            </td>
                                            <td class="text-success text-center"><i class="fa-solid fa-check"></i> Super Override</td>
                                            <td class="text-success text-center"><i class="fa-solid fa-check"></i> Full Control</td>
                                            <td class="text-success text-center"><i class="fa-solid fa-check"></i> Full Control</td>
                                            <td class="text-success text-center"><i class="fa-solid fa-check"></i> Full Control</td>
                                            <td class="text-success text-center"><i class="fa-solid fa-check"></i> Full Control</td>
                                            <td class="text-success text-center"><i class="fa-solid fa-check"></i> Full Control</td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <span class="badge" style="background:#06b6d4;">Admin Division Head</span>
                                                <div class="text-muted" style="font-size:0.7rem;">Tier 2 Approver (Julius De Peralta)</div>
                                            </td>
                                            <td class="text-success text-center"><i class="fa-solid fa-check"></i> Tier 2 Authorize & FR-1.3 Override</td>
                                            <td class="text-primary text-center"><i class="fa-solid fa-eye"></i> View Feasibility</td>
                                            <td class="text-success text-center"><i class="fa-solid fa-check"></i> Section A Sign-off</td>
                                            <td class="text-muted text-center"><i class="fa-solid fa-minus"></i> No Access</td>
                                            <td class="text-muted text-center"><i class="fa-solid fa-minus"></i> No Access</td>
                                            <td class="text-primary text-center"><i class="fa-solid fa-eye"></i> View Reports</td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <span class="badge" style="background:#10b981;">Motorpool Dispatcher</span>
                                                <div class="text-muted" style="font-size:0.7rem;">Tier 1 Approver & Fleet Allocator</div>
                                            </td>
                                            <td class="text-success text-center fw-bold text-success"><i class="fa-solid fa-check-double"></i> Tier 1 Document Review &amp; Endorse</td>
                                            <td class="text-success text-center"><i class="fa-solid fa-check"></i> Vehicle &amp; Driver Allocation</td>
                                            <td class="text-success text-center"><i class="fa-solid fa-check"></i> Issue e-DTT</td>
                                            <td class="text-success text-center"><i class="fa-solid fa-check"></i> Monitor Gate</td>
                                            <td class="text-success text-center"><i class="fa-solid fa-check"></i> BLOWBAGETS View</td>
                                            <td class="text-primary text-center"><i class="fa-solid fa-eye"></i> Operations Audit</td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <span class="badge" style="background:#64748b;">Security Guard</span>
                                                <div class="text-muted" style="font-size:0.7rem;">Compound Gate Officer</div>
                                            </td>
                                            <td class="text-muted text-center"><i class="fa-solid fa-minus"></i> No Access</td>
                                            <td class="text-muted text-center"><i class="fa-solid fa-minus"></i> No Access</td>
                                            <td class="text-success text-center"><i class="fa-solid fa-check"></i> Verify Departure &amp; Return</td>
                                            <td class="text-success text-center"><i class="fa-solid fa-check"></i> Gate In/Out QR Scanner</td>
                                            <td class="text-muted text-center"><i class="fa-solid fa-minus"></i> No Access</td>
                                            <td class="text-muted text-center"><i class="fa-solid fa-minus"></i> No Access</td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <span class="badge" style="background:#d97706;">COA Auditor</span>
                                                <div class="text-muted" style="font-size:0.7rem;">State Resident Auditor</div>
                                            </td>
                                            <td class="text-primary text-center"><i class="fa-solid fa-eye"></i> View All VRS</td>
                                            <td class="text-primary text-center"><i class="fa-solid fa-eye"></i> View Logs</td>
                                            <td class="text-primary text-center"><i class="fa-solid fa-eye"></i> Reconcile Odometers</td>
                                            <td class="text-primary text-center"><i class="fa-solid fa-eye"></i> Gate Audit</td>
                                            <td class="text-primary text-center"><i class="fa-solid fa-eye"></i> Maintenance Audit</td>
                                            <td class="text-success text-center"><i class="fa-solid fa-check"></i> Form B &amp; CPK Certify</td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <span class="badge" style="background:#0284c7;">Official Driver</span>
                                                <div class="text-muted" style="font-size:0.7rem;">Commercial Transport Operator</div>
                                            </td>
                                            <td class="text-muted text-center"><i class="fa-solid fa-minus"></i> No Access</td>
                                            <td class="text-muted text-center"><i class="fa-solid fa-minus"></i> View Assignment</td>
                                            <td class="text-success text-center"><i class="fa-solid fa-check"></i> Driver PWA Section B</td>
                                            <td class="text-primary text-center"><i class="fa-solid fa-qrcode"></i> Present QR</td>
                                            <td class="text-success text-center"><i class="fa-solid fa-check"></i> Pre-Trip BLOWBAGETS</td>
                                            <td class="text-muted text-center"><i class="fa-solid fa-minus"></i> Fuel Receipts Only</td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <span class="badge" style="background:#8b5cf6;">Requestor / Staff</span>
                                                <div class="text-muted" style="font-size:0.7rem;">PIA Division Personnel</div>
                                            </td>
                                            <td class="text-success text-center"><i class="fa-solid fa-file-pen"></i> File VRS &amp; Track SLA</td>
                                            <td class="text-muted text-center"><i class="fa-solid fa-minus"></i> No Access</td>
                                            <td class="text-primary text-center"><i class="fa-solid fa-eye"></i> View Assigned Trip</td>
                                            <td class="text-muted text-center"><i class="fa-solid fa-minus"></i> No Access</td>
                                            <td class="text-muted text-center"><i class="fa-solid fa-minus"></i> No Access</td>
                                            <td class="text-muted text-center"><i class="fa-solid fa-minus"></i> No Access</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB 3: Security Safeguards -->
                        <div class="tab-pane fade" id="tab-security" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="p-3 border rounded-2 bg-white">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <i class="fa-solid fa-shield-halved text-success fs-5"></i>
                                            <strong class="text-dark">CSRF Defense Filter</strong>
                                        </div>
                                        <p class="text-muted small mb-0">Every web form generates a cryptographic, randomized one-time token. Submissions without a valid token are rejected, preventing Cross-Site Request Forgery attacks.</p>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="p-3 border rounded-2 bg-white">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <i class="fa-solid fa-stopwatch text-danger fs-5"></i>
                                            <strong class="text-dark">Brute-Force Rate Limiting</strong>
                                        </div>
                                        <p class="text-muted small mb-0">Login endpoints are throttled to a maximum of 5 attempts per minute per IP address. Exceeding attempts triggers a timed lockout countdown to prevent dictionary attacks.</p>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="p-3 border rounded-2 bg-white">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <i class="fa-solid fa-cookie-bite text-warning fs-5"></i>
                                            <strong class="text-dark">Session Fixation & Cookie Hardening</strong>
                                        </div>
                                        <p class="text-muted small mb-0">Session IDs are automatically regenerated upon login (`session->regenerate(true)`). Session cookies are marked <code>HttpOnly</code> and <code>SameSite=Lax</code> to prevent XSS session hijacking.</p>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="p-3 border rounded-2 bg-white">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <i class="fa-solid fa-server text-primary fs-5"></i>
                                            <strong class="text-dark">Secure HTTP Headers</strong>
                                        </div>
                                        <p class="text-muted small mb-0">Active responses include <code>X-Frame-Options: SAMEORIGIN</code> to prevent Clickjacking, and <code>X-Content-Type-Options: nosniff</code> to block MIME-type sniffing.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 4: Google Maps & Radar -->
                        <div class="tab-pane fade" id="tab-radar" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="p-3 border rounded-2 bg-white">
                                        <strong class="text-dark d-block mb-1"><i class="fa-solid fa-traffic-light text-warning me-1"></i> Google Live Traffic</strong>
                                        <p class="text-muted small mb-0">Clicking <strong>Traffic: ON</strong> displays Google's real-time color-coded congestion overlay across major corridors, allowing dispatchers to reroute drivers around traffic jams.</p>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="p-3 border rounded-2 bg-white">
                                        <strong class="text-dark d-block mb-1"><i class="fa-solid fa-route text-primary me-1"></i> GPS Breadcrumb Trails</strong>
                                        <p class="text-muted small mb-0">Selecting any vehicle draws its historical movement trajectory line and waypoint nodes, revealing recent speed, heading, and corridor adherence.</p>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="p-3 border rounded-2 bg-white">
                                        <strong class="text-dark d-block mb-1"><i class="fa-solid fa-location-dot text-danger me-1"></i> Geofence Resolution</strong>
                                        <p class="text-muted small mb-0">Geographic circular fences (e.g. Manila Harbor, Clark Distribution Hub) automatically detect vehicle presence and record arrival and departure times.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 5: REST & IoT APIs -->
                        <div class="tab-pane fade" id="tab-api" role="tabpanel">
                            <p class="text-muted small mb-3">External GPS transponders, mobile tracking devices, and third-party enterprise tools can query the PIA Motorpool REST API (relative to the deployed base URL):</p>

                            <div class="bg-dark text-light p-3 rounded font-monospace small mb-2" style="font-size:0.75rem;">
                                <div class="text-info mb-1"># 1. Fetch Real-Time Fleet Map with GeoJSON Collection:</div>
                                <div>GET /api/v1/tracking</div>

                                <div class="text-info mt-3 mb-1"># 2. Ingest GPS Transponder Coordinate:</div>
                                <div>POST /api/v1/gps/ping</div>
                                <div class="text-muted">{ "vehicle_id": 1, "latitude": 14.5995, "longitude": 120.9842, "speed_kmh": 65.0, "fuel_level": 88.0 }</div>

                                <div class="text-info mt-3 mb-1"># 3. Calculate Road Route Corridor:</div>
                                <div>GET /api/v1/route?origin_lat=14.58&origin_lng=120.96&dest_lat=15.18&dest_lng=120.54</div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer border-top py-2 px-4" style="background: #f8fafc;">
                    <span class="small text-muted me-auto"><i class="fa-solid fa-circle-check text-success me-1"></i> System Status: All 5 Security Layers & Telematics Active</span>
                    <button type="button" class="btn btn-sm btn-primary px-3" data-bs-dismiss="modal">Close Manual</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Relocate all modals directly to document.body to prevent stacking context or backdrop-trapping bugs
        document.querySelectorAll('.modal').forEach(function (modal) {
            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }
        });

        // Initialize all Bootstrap tooltips globally with HTML and accessibility support (Issue #21)
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                container: 'body',
                html: true,
                trigger: 'hover focus'
            });
        });

        const sidebarToggle  = document.getElementById('sidebarToggle');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');

        // --- Mobile sidebar open / close -------------------------------
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function () {
                document.body.classList.toggle('sidebar-open');
            });
        }
        if (sidebarBackdrop) {
            sidebarBackdrop.addEventListener('click', function () {
                document.body.classList.remove('sidebar-open');
            });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { document.body.classList.remove('sidebar-open'); }
        });
        // Tapping a menu link on small screens should return to the content
        document.querySelectorAll('#sidebar .sidebar-link').forEach(function (link) {
            link.addEventListener('click', function () {
                document.body.classList.remove('sidebar-open');
            });
        });

        // --- Collapsible menu groups (state remembered per browser) ----
        var storageKey = 'pia-nav-groups';
        var collapsed  = {};
        try { collapsed = JSON.parse(localStorage.getItem(storageKey) || '{}'); } catch (e) { collapsed = {}; }

        document.querySelectorAll('.nav-section-toggle').forEach(function (toggle) {
            var group = document.querySelector('[data-nav-group-body="' + toggle.dataset.navGroup + '"]');
            if (!group) { return; }

            if (collapsed[toggle.dataset.navGroup]) {
                group.classList.add('collapsed');
                toggle.setAttribute('aria-expanded', 'false');
            }

            toggle.addEventListener('click', function () {
                var isCollapsed = group.classList.toggle('collapsed');
                toggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
                collapsed[toggle.dataset.navGroup] = isCollapsed;
                try { localStorage.setItem(storageKey, JSON.stringify(collapsed)); } catch (e) { /* private mode */ }
            });
        });

        // --- Keep the active page visible in the menu ------------------
        var activeLink = document.querySelector('#sidebar .sidebar-link.active');
        if (activeLink) {
            // Expand the group that contains the active link
            var parentGroup = activeLink.closest('.nav-group');
            if (parentGroup && parentGroup.classList.contains('collapsed')) {
                parentGroup.classList.remove('collapsed');
                var ownerToggle = document.querySelector('.nav-section-toggle[data-nav-group="' + parentGroup.dataset.navGroupBody + '"]');
                if (ownerToggle) { ownerToggle.setAttribute('aria-expanded', 'true'); }
            }
            activeLink.scrollIntoView({ block: 'nearest' });
        }
    });
    </script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
