<?php
/**
 * Driver Mobile Web Portal View - Senior UI/UX Redesign
 *
 * Tailored for field chauffeurs and operators on handheld smartphones.
 * Features:
 * - High-contrast outdoor legibility (WCAG 2.1 AA/AAA compliant)
 * - Ergonomic touch targets (min 48px-54px) & thumb-zone navigation
 * - Streamlined 4-phase trip lifecycle (Hero Action prioritizes the immediate next step)
 * - Mobile bottom-sheet modal ergonomics with swipe handles
 * - Clean Bento-grid telemetry for vehicle diagnostics & RFID toll balances
 * - Leaflet road navigation with Google Maps tiles & 1-tap external GPS links (Google Maps, Waze)
 *
 * @var array<string, mixed>|null  $driver
 * @var array<string, mixed>|null  $vehicle
 * @var list<array<string, mixed>> $tripTickets
 * @var array<string, mixed>|null  $activeTicket
 * @var array<string, mixed>|null  $lastCompletedTicket
 * @var array<string, mixed>|null  $activeAdvisory
 * @var list<array<string, mixed>> $rfidCards
 * @var bool                       $blowbagetsPassed
 * @var array<string, string>      $checklistItems
 * @var list<array<string, mixed>> $recentDelays
 * @var list<array<string, mixed>> $recentFuelLogs
 * @var list<array<string, mixed>> $recentIncidents
 * @var list<array<string, mixed>> $allDrivers
 */
$driver              = $driver ?? null;
$vehicle             = $vehicle ?? null;
$tripTickets         = $tripTickets ?? [];
$activeTicket        = $activeTicket ?? null;
$lastCompletedTicket = $lastCompletedTicket ?? null;
$activeAdvisory      = $activeAdvisory ?? null;
$rfidCards           = $rfidCards ?? [];
$blowbagetsPassed    = $blowbagetsPassed ?? false;
$checklistItems      = $checklistItems ?? [];
$recentDelays        = $recentDelays ?? [];
$recentFuelLogs      = $recentFuelLogs ?? [];
$recentIncidents     = $recentIncidents ?? [];
$allDrivers          = $allDrivers ?? [];

// Calculate 4-phase state for active ticket
$status = $activeTicket['status'] ?? 'none';
$phase1Done = !empty($activeTicket['departure_time']) || in_array($status, ['departed', 'arrived_dest', 'departed_dest', 'returned', 'completed'], true);
$phase2Done = !empty($activeTicket['arrival_dest_time']) || in_array($status, ['arrived_dest', 'departed_dest', 'returned', 'completed'], true);
$phase3Done = !empty($activeTicket['departure_dest_time']) || in_array($status, ['departed_dest', 'returned', 'completed'], true);
$phase4Done = !empty($activeTicket['arrival_back_time']) || in_array($status, ['returned', 'completed'], true);
$isSectionBCompleted = ($status === 'completed') || (!empty($activeTicket['driver_certified']) && !empty($activeTicket['passenger_certified']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?= esc(($driver['first_name'] ?? 'Driver') . ' ' . ($driver['last_name'] ?? '')) ?> | PIA Driver Portal</title>

    <!-- Typography: Plus Jakarta Sans & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 & FontAwesome 6.5.1 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <!-- Leaflet 1.9.4 Mapping Engine -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- QRCode.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        :root {
            /* Surface Foundations */
            --bg-page: #f8fafc;
            --bg-card: #ffffff;
            --bg-card-subtle: #f1f5f9;
            --bg-sunken: #eef2f6;

            /* Borders */
            --border-subtle: #e2e8f0;
            --border-medium: #cbd5e1;
            --border-focus: #3b82f6;

            /* Typography Hierarchy (High Contrast WCAG AAA/AA) */
            --text-primary: #0f172a;
            --text-secondary: #334155;
            --text-muted: #64748b;
            --text-light: #94a3b8;

            /* Semantic Brand Tokens */
            --brand-primary: #0f172a;
            --brand-accent: #2563eb;
            --brand-accent-hover: #1d4ed8;
            --brand-light: #eff6ff;
            --brand-light-border: #bfdbfe;

            /* Status Tokens */
            --success: #059669;
            --success-dark: #065f46;
            --success-light: #ecfdf5;
            --success-border: #a7f3d0;

            --warning: #d97706;
            --warning-dark: #92400e;
            --warning-light: #fffbeb;
            --warning-border: #fde68a;

            --danger: #dc2626;
            --danger-dark: #991b1b;
            --danger-light: #fef2f2;
            --danger-border: #fecaca;

            /* Radii */
            --radius-xs: 6px;
            --radius-sm: 10px;
            --radius-md: 14px;
            --radius-lg: 18px;
            --radius-xl: 24px;
            --radius-pill: 9999px;

            /* Elevation & Shadows */
            --shadow-subtle: 0 1px 3px 0 rgba(15, 23, 42, 0.04), 0 1px 2px -1px rgba(15, 23, 42, 0.03);
            --shadow-card: 0 4px 16px -2px rgba(15, 23, 42, 0.05), 0 2px 6px -1px rgba(15, 23, 42, 0.03);
            --shadow-elevated: 0 12px 30px -4px rgba(15, 23, 42, 0.09), 0 4px 12px -2px rgba(15, 23, 42, 0.04);
            --shadow-glow-blue: 0 8px 24px -4px rgba(37, 99, 235, 0.28);
            --shadow-glow-emerald: 0 8px 24px -4px rgba(5, 150, 105, 0.28);

            /* Touch Ergonomics & Safe Areas */
            --touch-target: 48px;
            --safe-bottom: env(safe-area-inset-bottom, 16px);
            --safe-top: env(safe-area-inset-top, 0px);
        }

        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        html {
            height: 100%;
            overflow-x: hidden;
            overflow-y: scroll;
            -webkit-overflow-scrolling: touch;
        }

        body {
            background-color: var(--bg-page);
            color: var(--text-primary);
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100%;
            height: auto;
            overflow-x: hidden;
            overflow-y: visible;
            padding-bottom: calc(76px + var(--safe-bottom));
            margin: 0;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            letter-spacing: -0.015em;
        }

        .mono {
            font-family: 'JetBrains Mono', monospace;
            letter-spacing: -0.02em;
        }

        /* -------------------------------------------------------------
           Sticky App Bar (Glassmorphism + Safe Area Support)
        -------------------------------------------------------------- */
        .driver-header {
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-subtle);
            padding: calc(12px + var(--safe-top)) 16px 12px;
            position: sticky;
            top: 0;
            z-index: 1030;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
        }

        .driver-avatar {
            width: 44px;
            height: 44px;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 0.95rem;
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.15);
            position: relative;
        }

        .driver-avatar-status {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--success);
            border: 2px solid #ffffff;
        }

        .duty-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: var(--radius-pill);
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            min-height: 36px;
        }
        .duty-badge:active {
            transform: scale(0.96);
        }
        .duty-badge.duty-available {
            background: var(--success-light);
            color: var(--success-dark);
            border-color: var(--success-border);
        }
        .duty-badge.duty-on_trip {
            background: var(--warning-light);
            color: var(--warning-dark);
            border-color: var(--warning-border);
        }
        .duty-badge.duty-off_duty {
            background: #f1f5f9;
            color: var(--text-secondary);
            border-color: var(--border-medium);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            display: inline-block;
        }
        .status-dot.pulse {
            box-shadow: 0 0 0 0 rgba(5, 150, 105, 0.7);
            animation: pulse-green 1.8s infinite;
        }
        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(5, 150, 105, 0.7); }
            70% { box-shadow: 0 0 0 6px rgba(5, 150, 105, 0); }
            100% { box-shadow: 0 0 0 0 rgba(5, 150, 105, 0); }
        }

        /* -------------------------------------------------------------
           Card System & Visual Hierarchy
        -------------------------------------------------------------- */
        .driver-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            padding: 18px;
            margin-bottom: 14px;
            box-shadow: var(--shadow-card);
            position: relative;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .card-active-trip {
            border-color: #bfdbfe;
            background: #ffffff;
            box-shadow: var(--shadow-elevated);
        }

        .driver-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f1f5f9;
        }

        .card-title-sm {
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-secondary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        /* -------------------------------------------------------------
           Hero Call-to-Action Banner (Elevates the single next step)
        -------------------------------------------------------------- */
        .hero-action-card {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            border-radius: var(--radius-lg);
            padding: 18px;
            color: #ffffff;
            margin-bottom: 14px;
            box-shadow: var(--shadow-glow-blue);
            position: relative;
            overflow: hidden;
        }
        .hero-action-card::after {
            content: '';
            position: absolute;
            top: -30%;
            right: -10%;
            width: 180px;
            height: 180px;
            background: radial-gradient(circle, rgba(255,255,255,0.18) 0%, rgba(255,255,255,0) 70%);
            pointer-events: none;
        }
        .hero-action-card.hero-warning {
            background: linear-gradient(135deg, #92400e 0%, #d97706 100%);
            box-shadow: 0 8px 24px -4px rgba(217, 119, 6, 0.35);
        }
        .hero-action-card.hero-success {
            background: linear-gradient(135deg, #065f46 0%, #059669 100%);
            box-shadow: var(--shadow-glow-emerald);
        }

        .hero-pill-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(8px);
            padding: 4px 10px;
            border-radius: var(--radius-pill);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .hero-action-title {
            font-size: 1.05rem;
            font-weight: 800;
            margin-bottom: 6px;
            line-height: 1.35;
        }

        .hero-action-sub {
            font-size: 0.82rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 14px;
            line-height: 1.45;
        }

        .btn-hero-action {
            background: #ffffff;
            color: var(--text-primary);
            font-weight: 700;
            font-size: 0.95rem;
            padding: 13px 20px;
            border-radius: var(--radius-md);
            border: none;
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            min-height: 52px;
            text-decoration: none;
        }
        .btn-hero-action:active {
            transform: scale(0.98);
            background: #f8fafc;
            color: var(--text-primary);
        }

        /* -------------------------------------------------------------
           4-Phase Stepper Component (Clean, Connected Progress Track)
        -------------------------------------------------------------- */
        .phase-stepper-box {
            background: #f8fafc;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 14px 10px;
            margin-bottom: 16px;
        }
        .phase-stepper {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            position: relative;
            margin: 0;
            padding: 0 6px;
        }
        .phase-stepper::before {
            content: '';
            position: absolute;
            top: 17px;
            left: 28px;
            right: 28px;
            height: 3px;
            background: #e2e8f0;
            z-index: 0;
            border-radius: 99px;
        }
        .phase-step {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            flex: 1;
            padding: 0 3px;
        }
        .phase-dot {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #ffffff;
            border: 2.5px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 800;
            color: #64748b;
            margin-bottom: 6px;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 2px 5px rgba(0,0,0,0.04);
        }
        .phase-step.completed .phase-dot {
            background: var(--success);
            border-color: var(--success);
            color: #ffffff;
            box-shadow: 0 3px 8px rgba(5, 150, 105, 0.35);
        }
        .phase-step.active .phase-dot {
            background: var(--brand-accent);
            border-color: var(--brand-accent);
            color: #ffffff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.25);
            animation: pulse-ring 2s infinite;
        }
        .phase-label {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-muted);
            line-height: 1.25;
            display: block;
        }
        .phase-step.active .phase-label {
            color: var(--brand-accent);
            font-weight: 800;
        }
        .phase-step.completed .phase-label {
            color: var(--success);
            font-weight: 700;
        }
        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.5); }
            70% { box-shadow: 0 0 0 9px rgba(37, 99, 235, 0); }
            100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
        }

        /* -------------------------------------------------------------
           Ergonomic Quick Action Grid (4-Item Finger-Friendly)
        -------------------------------------------------------------- */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 16px;
        }
        .quick-action-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 12px 6px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            text-decoration: none;
            color: var(--text-secondary);
            cursor: pointer;
            min-height: 72px;
            box-shadow: var(--shadow-subtle);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .quick-action-card:active {
            transform: scale(0.95);
            background: #f8fafc;
            border-color: var(--border-medium);
        }
        .quick-icon-circle {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #f1f5f9;
            color: var(--brand-accent);
            display: grid;
            place-items: center;
            font-size: 1.05rem;
            transition: background-color 0.2s ease;
        }
        .quick-action-card.btn-sos .quick-icon-circle {
            background: #fee2e2;
            color: var(--danger);
        }
        .quick-action-card.btn-fuel .quick-icon-circle {
            background: #dcfce7;
            color: var(--success);
        }
        .quick-action-card.btn-delay .quick-icon-circle {
            background: #fef3c7;
            color: var(--warning);
        }
        .quick-action-label {
            font-size: 0.72rem;
            font-weight: 700;
            line-height: 1.15;
            text-align: center;
        }

        /* -------------------------------------------------------------
           Bento Metric Strip & Telemetry Cells
        -------------------------------------------------------------- */
        .bento-metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 14px;
        }
        .bento-metric-card {
            background: #f8fafc;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 12px 10px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .bento-metric-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--text-muted);
            margin-bottom: 4px;
        }
        .bento-metric-val {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1.2;
        }

        /* -------------------------------------------------------------
           QR Gate Pass Section
        -------------------------------------------------------------- */
        .qr-pass-container {
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 16px;
            text-align: center;
            margin: 14px 0;
            box-shadow: var(--shadow-subtle);
        }
        .qr-code-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            padding: 12px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-subtle);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        }
        .qr-code-box img,
        .qr-code-box canvas {
            display: block;
            margin: 0 auto;
        }

        /* -------------------------------------------------------------
           Primary Buttons (Full Width, Thumb Friendly)
        -------------------------------------------------------------- */
        .btn-driver-primary {
            background: var(--brand-accent);
            color: #ffffff;
            border: none;
            padding: 14px 18px;
            font-weight: 700;
            font-size: 0.95rem;
            border-radius: var(--radius-md);
            min-height: var(--touch-target);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            text-decoration: none;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .btn-driver-primary:active {
            transform: scale(0.98);
            background: var(--brand-accent-hover);
            color: #ffffff;
        }
        .btn-driver-success {
            background: var(--success);
            color: #ffffff;
            border: none;
            padding: 14px 18px;
            font-weight: 700;
            font-size: 0.95rem;
            border-radius: var(--radius-md);
            min-height: var(--touch-target);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            text-decoration: none;
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .btn-driver-success:active {
            transform: scale(0.98);
            background: var(--success-dark);
            color: #ffffff;
        }
        .btn-driver-outline {
            background: #ffffff;
            color: var(--text-primary);
            border: 1px solid var(--border-medium);
            padding: 12px 18px;
            font-weight: 700;
            font-size: 0.88rem;
            border-radius: var(--radius-md);
            min-height: var(--touch-target);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .btn-driver-outline:active {
            background: #f1f5f9;
            transform: scale(0.98);
            color: var(--text-primary);
        }

        /* -------------------------------------------------------------
           Ergonomic Bottom Thumb Dock (Floating, Glassmorphism)
        -------------------------------------------------------------- */
        .bottom-thumb-dock {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: calc(64px + var(--safe-bottom));
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            justify-content: space-around;
            padding: 0 12px var(--safe-bottom);
            z-index: 1040;
            box-shadow: 0 -4px 20px -2px rgba(15, 23, 42, 0.08);
        }
        .thumb-nav-btn {
            flex: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0;
            position: relative;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .thumb-nav-btn:active {
            transform: scale(0.92);
        }
        .thumb-nav-btn.active {
            color: var(--brand-accent);
        }
        .thumb-nav-btn i {
            font-size: 1.25rem;
            transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .thumb-nav-btn.active i {
            transform: translateY(-2px);
        }
        .nav-alert-indicator {
            position: absolute;
            top: 10px;
            right: calc(50% - 18px);
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--warning);
            border: 2px solid #ffffff;
            box-shadow: 0 0 0 2px rgba(217, 119, 6, 0.2);
        }

        /* -------------------------------------------------------------
           Tab Views
        -------------------------------------------------------------- */
        .tab-panel-view {
            display: none;
            height: auto !important;
            overflow: visible !important;
            animation: fadeInView 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .tab-panel-view.active {
            display: block !important;
        }
        @keyframes fadeInView {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* -------------------------------------------------------------
           Chip Selector Groups
        -------------------------------------------------------------- */
        .chip-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 14px;
        }
        .chip-option {
            padding: 10px 16px;
            border-radius: var(--radius-pill);
            border: 1px solid var(--border-medium);
            background: #ffffff;
            color: var(--text-secondary);
            font-size: 0.84rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .chip-option:active {
            transform: scale(0.96);
        }
        .chip-option.selected {
            background: var(--brand-light);
            border-color: var(--brand-accent);
            color: var(--brand-accent);
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.15);
        }

        /* -------------------------------------------------------------
           Mobile Bottom-Sheet Modal Paradigm (Max-Width: 768px)
        -------------------------------------------------------------- */
        @media (max-width: 768px) {
            .modal.fade .modal-dialog {
                margin: 0;
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                max-width: 100%;
                transform: translateY(100%);
                transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
            }
            .modal.show .modal-dialog {
                transform: translateY(0);
            }
            .modal-content {
                border-radius: 24px 24px 0 0 !important;
                border: none !important;
                max-height: 90vh;
                box-shadow: 0 -12px 40px rgba(15, 23, 42, 0.25) !important;
            }
            .modal-drag-handle {
                width: 44px;
                height: 5px;
                background: #cbd5e1;
                border-radius: var(--radius-pill);
                margin: 10px auto 4px;
            }
            .modal-body {
                max-height: calc(85vh - 140px);
                overflow-y: auto !important;
                -webkit-overflow-scrolling: touch;
                padding: 16px 20px;
            }
            .modal-footer {
                padding: 14px 20px calc(14px + var(--safe-bottom));
                border-top: 1px solid var(--border-subtle);
                background: #ffffff;
            }
        }
    </style>
</head>
<body>

    <!-- -------------------------------------------------------------
         APP HEADER (Sticky with Glassmorphism & Status Ring)
    -------------------------------------------------------------- -->
    <header class="driver-header">
        <div class="d-flex align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-3" style="min-width: 0; flex: 1;">
                <div class="driver-avatar flex-shrink-0">
                    <?= esc(strtoupper(substr($driver['first_name'] ?? 'D', 0, 1) . substr($driver['last_name'] ?? 'R', 0, 1))) ?>
                    <span class="driver-avatar-status"></span>
                </div>
                <div style="min-width: 0;">
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="fw-bold mb-0 text-truncate text-dark" style="font-size: 0.96rem;">
                            <?= esc(($driver['first_name'] ?? 'Driver') . ' ' . ($driver['last_name'] ?? '')) ?>
                        </h6>
                    </div>
                    <div class="text-muted mono text-truncate" style="font-size: 0.74rem;">
                        <span class="fw-semibold text-primary"><?= esc($driver['driver_code'] ?? 'DRV-101') ?></span> &bull; PIA Motorpool
                    </div>
                </div>
            </div>

            <!-- Duty Status & Profile Switcher -->
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <?php $dutyState = $driver['status'] ?? 'available'; ?>
                <button type="button" class="duty-badge duty-<?= esc($dutyState) ?>" data-bs-toggle="modal" data-bs-target="#dutyModal" title="Change Duty Status" style="white-space: nowrap;">
                    <span class="status-dot <?= $dutyState === 'available' ? 'pulse' : '' ?>"></span>
                    <span><?= ucfirst(str_replace('_', ' ', $dutyState)) ?></span>
                </button>

                <!-- More Options Menu -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border p-1 rounded-3" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 36px; height: 36px; display: grid; place-items: center;">
                        <i class="fa-solid fa-ellipsis-vertical text-secondary"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border mt-1" style="min-width: 220px; font-size: 0.88rem; border-radius: var(--radius-md);">
                        <li class="px-3 py-2 border-bottom">
                            <span class="d-block text-muted small">Logged in as</span>
                            <strong class="text-dark"><?= esc(($driver['first_name'] ?? 'Driver') . ' ' . ($driver['last_name'] ?? '')) ?></strong>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#dutyModal">
                                <i class="fa-solid fa-user-clock me-2 text-primary"></i> Change Duty Status
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="#view-history" data-view="view-history">
                                <i class="fa-solid fa-ticket me-2 text-secondary"></i> All Trip Tickets
                            </a>
                        </li>
                        <?php if (session()->get('user_role') === 'admin' && !empty($allDrivers) && count($allDrivers) > 1): ?>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li class="px-3 py-1 text-muted small text-uppercase mono" style="font-size: 0.68rem;">Switch Driver Profile (Admin)</li>
                            <?php foreach ($allDrivers as $ad): ?>
                                <li>
                                    <a class="dropdown-item py-1 <?= $ad['id'] == ($driver['id'] ?? 0) ? 'fw-bold text-primary' : 'text-secondary' ?>" href="<?= base_url('driver/trips?driver_id=' . $ad['id']) ?>">
                                        <?= esc($ad['first_name'] . ' ' . $ad['last_name']) ?> <span class="mono small text-muted">(<?= esc($ad['driver_code']) ?>)</span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <a class="dropdown-item text-danger py-2" href="<?= base_url('logout') ?>">
                                <i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Sign Out
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <!-- -------------------------------------------------------------
         MAIN CONTENT CONTAINER (Scrollable)
    -------------------------------------------------------------- -->
    <main class="container px-3 pt-3" role="main" style="max-width: 680px;">

        <!-- Flash Feedback Banners -->
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show p-3 rounded-4 mb-3 border-0 d-flex align-items-center shadow-sm" role="alert" style="background: var(--success-light); color: var(--success-dark); border-left: 5px solid var(--success) !important;">
                <i class="fa-solid fa-circle-check fs-5 me-2 text-success"></i>
                <div class="small fw-semibold flex-grow-1"><?= session()->getFlashdata('success') ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show p-3 rounded-4 mb-3 border-0 d-flex align-items-center shadow-sm" role="alert" style="background: var(--danger-light); color: var(--danger-dark); border-left: 5px solid var(--danger) !important;">
                <i class="fa-solid fa-triangle-exclamation fs-5 me-2 text-danger"></i>
                <div class="small fw-semibold flex-grow-1"><?= session()->getFlashdata('error') ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Live Motorpool Advisory / Road & Weather Notice Banner -->
        <?php if (!empty($activeAdvisory)): ?>
            <div class="alert p-3 rounded-4 shadow-sm border-0 mb-3" style="background: <?= $activeAdvisory['severity'] === 'danger' ? 'var(--danger-light); border-left: 5px solid var(--danger) !important;' : ($activeAdvisory['severity'] === 'warning' ? 'var(--warning-light); border-left: 5px solid var(--warning) !important;' : 'var(--brand-light); border-left: 5px solid var(--brand-accent) !important;') ?>">
                <div class="d-flex align-items-start gap-2">
                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px; background: <?= $activeAdvisory['severity'] === 'danger' ? '#fee2e2; color: #dc2626;' : ($activeAdvisory['severity'] === 'warning' ? '#fef3c7; color: #d97706;' : '#dbeafe; color: #2563eb;') ?> font-size: 1rem;">
                        <i class="fa-solid <?= $activeAdvisory['severity'] === 'danger' ? 'fa-triangle-exclamation' : ($activeAdvisory['severity'] === 'warning' ? 'fa-triangle-exclamation' : 'fa-bullhorn') ?>"></i>
                    </span>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-1">
                            <span class="badge <?= $activeAdvisory['severity'] === 'danger' ? 'bg-danger text-white' : ($activeAdvisory['severity'] === 'warning' ? 'bg-warning text-dark' : 'bg-primary text-white') ?> mono" style="font-size: 0.68rem;">
                                <?= strtoupper(esc($activeAdvisory['category'])) ?>
                            </span>
                            <span class="mono text-muted" style="font-size: 0.72rem;">
                                <?= date('M d, h:i A', strtotime($activeAdvisory['created_at'])) ?>
                            </span>
                        </div>
                        <h6 class="fw-bold text-dark mb-1" style="font-size: 0.92rem;">
                            <?= esc($activeAdvisory['title']) ?>
                        </h6>
                        <p class="text-secondary small mb-1" style="font-size: 0.82rem; line-height: 1.45;">
                            <?= nl2br(esc($activeAdvisory['message'])) ?>
                        </p>
                        <div class="mono text-muted" style="font-size: 0.72rem;">
                            <i class="fa-solid fa-user-shield me-1"></i> Issued by: <?= esc($activeAdvisory['dispatcher_name']) ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- -------------------------------------------------------------
             Ergonomic Quick Action Row (4-Item Finger-Friendly)
        -------------------------------------------------------------- -->
        <div class="quick-actions-grid">
            <!-- 1. Enlarge QR Gate Pass -->
            <div class="quick-action-card" data-bs-toggle="modal" data-bs-target="#qrPassModal" role="button">
                <div class="quick-icon-circle">
                    <i class="fa-solid fa-qrcode"></i>
                </div>
                <span class="quick-action-label">Gate Pass</span>
            </div>

            <!-- 2. Refuel Modal -->
            <div class="quick-action-card btn-fuel" data-bs-toggle="modal" data-bs-target="#fuelModal" role="button">
                <div class="quick-icon-circle">
                    <i class="fa-solid fa-gas-pump"></i>
                </div>
                <span class="quick-action-label">Log Fuel</span>
            </div>

            <!-- 3. Passenger Delay Logger -->
            <?php if ($activeTicket): ?>
                <div class="quick-action-card btn-delay" data-bs-toggle="modal" data-bs-target="#delayModal" role="button">
                    <div class="quick-icon-circle">
                        <i class="fa-solid fa-user-clock"></i>
                    </div>
                    <span class="quick-action-label">Log Delay</span>
                </div>
            <?php else: ?>
                <div class="quick-action-card" data-view="view-history" role="button">
                    <div class="quick-icon-circle">
                        <i class="fa-solid fa-ticket"></i>
                    </div>
                    <span class="quick-action-label">Tickets</span>
                </div>
            <?php endif; ?>

            <!-- 4. Emergency SOS Breakdown -->
            <div class="quick-action-card btn-sos" data-bs-toggle="modal" data-bs-target="#incidentModal" role="button">
                <div class="quick-icon-circle">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <span class="quick-action-label text-danger">SOS Alert</span>
            </div>
        </div>

        <!-- =========================================================
             VIEW 1: ACTIVE TRIP (Hero 4-Phase Operational Console)
        ========================================================== -->
        <section id="view-active-trip" class="tab-panel-view active">

            <?php if ($activeTicket): ?>

                <!-- -------------------------------------------------------------
                     DYNAMIC HERO ACTION CARD (Surfaces the Immediate Next Step)
                -------------------------------------------------------------- -->
                <?php if ($status === 'issued'): ?>
                    <div class="hero-action-card">
                        <div class="hero-pill-tag">
                            <i class="fa-solid fa-id-badge"></i> Phase 1 &bull; Ready for Compound Exit
                        </div>
                        <h5 class="hero-action-title">Proceed to Compound Gate #1</h5>
                        <p class="hero-action-sub">
                            Present your Gate Pass QR code to the guard at Gate #1 to stamp official departure time. Ensure your 10-point BLOWBAGETS check is complete.
                        </p>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn-hero-action" data-bs-toggle="modal" data-bs-target="#qrPassModal">
                                <i class="fa-solid fa-qrcode text-primary"></i> Show Gate Pass QR Code
                            </button>
                            <?php if (!$blowbagetsPassed): ?>
                                <button type="button" class="btn btn-warning text-dark fw-bold w-100 py-2 rounded-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#blowbagetsModal">
                                    <i class="fa-solid fa-shield-halved me-1"></i> Complete BLOWBAGETS Safety Inspection
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($status === 'departed'): ?>
                    <div class="hero-action-card" id="arrivalPromptCard">
                        <div class="hero-pill-tag">
                            <i class="fa-solid fa-car-side"></i> Phase 2 &bull; En Route to Destination
                        </div>
                        <h5 class="hero-action-title">Have you arrived at the authorized event?</h5>
                        <p class="hero-action-sub">
                            Destination: <strong><?= esc($activeTicket['authorized_destination']) ?></strong>. Confirming arrival notifies Motorpool Dispatch and stamps your trip log.
                        </p>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn-hero-action flex-grow-1" data-bs-toggle="modal" data-bs-target="#arriveModal" id="btnArriveAgree">
                                <i class="fa-solid fa-circle-check text-success"></i> Agree (I Have Arrived)
                            </button>
                            <button type="button" class="btn btn-outline-light text-white fw-semibold px-3 py-2 rounded-3" id="btnArriveDisagree" style="border-color: rgba(255,255,255,0.4);">
                                En Route
                            </button>
                        </div>
                    </div>
                <?php elseif ($status === 'arrived_dest'): ?>
                    <div class="hero-action-card hero-warning">
                        <div class="hero-pill-tag" style="background: rgba(0,0,0,0.15);">
                            <i class="fa-solid fa-location-dot"></i> Phase 3 &bull; Stationed at Destination
                        </div>
                        <h5 class="hero-action-title">Ready to depart back to PIA Compound?</h5>
                        <p class="hero-action-sub">
                            Arrived at: <strong><?= !empty($activeTicket['arrival_dest_time']) ? date('M d, h:i A', strtotime($activeTicket['arrival_dest_time'])) : 'Destination' ?></strong>. When official coverage wraps up, confirm your return departure.
                        </p>
                        <button type="button" class="btn-hero-action" data-bs-toggle="modal" data-bs-target="#departModal">
                            <i class="fa-solid fa-arrow-left text-warning"></i> Confirm Return Trip Departure
                        </button>
                    </div>
                <?php elseif ($status === 'departed_dest'): ?>
                    <div class="hero-action-card hero-success">
                        <div class="hero-pill-tag">
                            <i class="fa-solid fa-shield-check"></i> Phase 4 &bull; Compound Gate Ingress
                        </div>
                        <h5 class="hero-action-title">Return Arrival at Compound Gate #1</h5>
                        <p class="hero-action-sub">
                            You are en route back. Present your QR code to the gate security guard to record official return arrival and final return odometer.
                        </p>
                        <button type="button" class="btn-hero-action" data-bs-toggle="modal" data-bs-target="#qrPassModal">
                            <i class="fa-solid fa-qrcode text-success"></i> Show Ingress QR Code for Guard
                        </button>
                    </div>
                <?php elseif ($status === 'returned'): ?>
                    <div class="hero-action-card hero-warning">
                        <div class="hero-pill-tag" style="background: rgba(0,0,0,0.2);">
                            <i class="fa-solid fa-clipboard-check"></i> Section B Post-Trip Audit Required
                        </div>
                        <h5 class="hero-action-title">Ingress Verified! Complete Section B Report</h5>
                        <p class="hero-action-sub">
                            Stamped at Gate #1 (Odo: <?= number_format((float)($activeTicket['return_odometer'] ?? 0), 1) ?> KM). Complete fuel reconciliation &amp; passenger sign-off to finalize this ticket.
                        </p>
                        <button type="button" class="btn-hero-action" data-bs-toggle="modal" data-bs-target="#sectionBModal">
                            <i class="fa-solid fa-file-signature text-warning"></i> Complete Section B Post-Trip Report
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Active Trip Main Card -->
                <div class="driver-card card-active-trip">
                    <div class="driver-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 mono px-2 py-1" style="font-size: 0.74rem;">
                                <?= esc($activeTicket['ticket_serial_no']) ?>
                            </span>
                        </div>
                        <?php
                            $statusInfo = match($activeTicket['status']) {
                                'issued'        => ['label' => 'Gate Departure (Phase 1)', 'bg' => '#fef3c7', 'color' => '#92400e', 'icon' => 'fa-id-badge'],
                                'departed'      => ['label' => 'En Route / On Trip', 'bg' => '#dbeafe', 'color' => '#1e40af', 'icon' => 'fa-car-side'],
                                'arrived_dest'  => ['label' => 'At Destination (Phase 2)', 'bg' => '#ccfbf1', 'color' => '#0f766e', 'icon' => 'fa-location-dot'],
                                'departed_dest' => ['label' => 'Returning (Phase 3)', 'bg' => '#e0e7ff', 'color' => '#3730a3', 'icon' => 'fa-flag-checkered'],
                                'returned'      => ['label' => 'Returned &bull; Pending Section B', 'bg' => '#fed7aa', 'color' => '#9a3412', 'icon' => 'fa-clipboard-check'],
                                'completed'     => ['label' => 'Completed & Audited', 'bg' => '#d1fae5', 'color' => '#065f46', 'icon' => 'fa-circle-check'],
                                default         => ['label' => ucfirst($activeTicket['status']), 'bg' => '#f1f5f9', 'color' => '#475569', 'icon' => 'fa-circle-dot'],
                            };
                        ?>
                        <span class="badge px-2 py-1 rounded-pill" style="background: <?= $statusInfo['bg'] ?>; color: <?= $statusInfo['color'] ?>; font-size: 0.76rem; font-weight: 700;">
                            <i class="fa-solid <?= $statusInfo['icon'] ?> me-1"></i><?= $statusInfo['label'] ?>
                        </span>
                    </div>

                    <!-- 4-Phase Stepper Component -->
                    <div class="phase-stepper-box">
                        <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                            <span class="small fw-bold text-uppercase mono text-secondary" style="font-size: 0.72rem;">
                                <i class="fa-solid fa-route text-primary me-1"></i> 4-Phase Trip Lifecycle
                            </span>
                            <span class="badge bg-light text-muted border mono" style="font-size: 0.68rem;">DTT ADMIN-F-001</span>
                        </div>

                        <div class="phase-stepper">
                            <!-- Phase 1: Gate Departure Egress -->
                            <div class="phase-step <?= $phase1Done ? ($status === 'issued' ? 'active' : 'completed') : 'active' ?>">
                                <div class="phase-dot">
                                    <?php if ($phase1Done && $status !== 'issued'): ?>
                                        <i class="fa-solid fa-check"></i>
                                    <?php else: ?>
                                        1
                                    <?php endif; ?>
                                </div>
                                <span class="phase-label">Compound Egress</span>
                            </div>

                            <!-- Phase 2: Destination Arrival -->
                            <div class="phase-step <?= $status === 'departed' ? 'active' : ($phase2Done ? 'completed' : '') ?>">
                                <div class="phase-dot">
                                    <?php if ($phase2Done && !in_array($status, ['issued', 'departed'], true)): ?>
                                        <i class="fa-solid fa-check"></i>
                                    <?php else: ?>
                                        2
                                    <?php endif; ?>
                                </div>
                                <span class="phase-label">Dest Arrival</span>
                            </div>

                            <!-- Phase 3: Departure from Destination -->
                            <div class="phase-step <?= $status === 'arrived_dest' ? 'active' : ($phase3Done ? 'completed' : '') ?>">
                                <div class="phase-dot">
                                    <?php if ($phase3Done && !in_array($status, ['issued', 'departed', 'arrived_dest'], true)): ?>
                                        <i class="fa-solid fa-check"></i>
                                    <?php else: ?>
                                        3
                                    <?php endif; ?>
                                </div>
                                <span class="phase-label">Depart Dest</span>
                            </div>

                            <!-- Phase 4: Gate Ingress & Return -->
                            <div class="phase-step <?= in_array($status, ['departed_dest', 'returned'], true) ? 'active' : ($phase4Done ? 'completed' : '') ?>">
                                <div class="phase-dot">
                                    <?php if ($phase4Done && $status === 'completed'): ?>
                                        <i class="fa-solid fa-check"></i>
                                    <?php else: ?>
                                        4
                                    <?php endif; ?>
                                </div>
                                <span class="phase-label">Gate Ingress</span>
                            </div>
                        </div>
                    </div>

                    <!-- Destination & Live Road Navigation Hub -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small text-muted text-uppercase fw-bold" style="font-size: 0.74rem;">Authorized Destination</span>
                            <div class="d-flex gap-1 align-items-center">
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 mono px-2 py-1" id="driverNavDistance" style="font-size: 0.74rem;">-- km</span>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 mono px-2 py-1" id="driverNavEta" style="font-size: 0.74rem;">-- min</span>
                            </div>
                        </div>
                        <h4 class="fw-bold text-dark mb-1" style="font-size: 1.15rem; line-height: 1.3;">
                            <?= esc($activeTicket['authorized_destination']) ?>
                        </h4>
                        <?php if (!empty($activeTicket['authorized_purpose'])): ?>
                            <p class="small text-secondary mb-2" style="font-size: 0.84rem;"><?= esc($activeTicket['authorized_purpose']) ?></p>
                        <?php endif; ?>

                        <!-- Interactive Map Card -->
                        <div class="rounded-4 border overflow-hidden mb-2 shadow-sm" style="background: #f1f5f9;">
                            <div id="driverRouteMap" style="height: 240px; min-height: 220px; width: 100%; z-index: 1;"></div>
                            <div class="p-2 bg-white border-top d-flex justify-content-between align-items-center flex-wrap gap-1">
                                <div class="small text-truncate" id="driverNextManeuver" style="font-size: 0.78rem; max-width: 65%; color: #0f172a; font-weight: 600;">
                                    <i class="fa-solid fa-diamond-turn-right text-primary me-1"></i> Computing road route...
                                </div>
                                <div class="d-flex gap-1">
                                    <button type="button" id="btnDriverLiveGps" class="btn btn-xs btn-outline-success py-1 px-2 rounded-2" style="font-size: 0.72rem; font-weight: 700;">
                                        <i class="fa-solid fa-location-crosshairs me-1"></i>Live GPS
                                    </button>
                                    <button class="btn btn-xs btn-outline-secondary py-1 px-2 rounded-2" type="button" data-bs-toggle="collapse" data-bs-target="#driverStepsCollapse" aria-expanded="false" style="font-size: 0.72rem; font-weight: 600;">
                                        <i class="fa-solid fa-list-ol me-1"></i>Steps
                                    </button>
                                </div>
                            </div>
                            <div class="collapse border-top bg-light" id="driverStepsCollapse">
                                <div id="driverTurnSteps" class="p-2 small" style="max-height: 160px; overflow-y: auto; font-size: 0.78rem;">
                                    <div class="text-muted">Loading turn-by-turn directions...</div>
                                </div>
                            </div>
                        </div>

                        <!-- External Navigation Deep Links -->
                        <div class="d-flex gap-2">
                            <a id="btnGoogleMapsDir"
                               href="https://www.google.com/maps/dir/?api=1&amp;origin=14.657200,121.045000&amp;destination=<?= urlencode($activeTicket['authorized_destination'] . ', Philippines') ?>&amp;travelmode=driving"
                               target="_blank" rel="noopener"
                               class="btn btn-light border flex-grow-1 fw-bold text-primary d-flex align-items-center justify-content-center gap-2 py-2 rounded-3 shadow-sm"
                               style="font-size: 0.82rem; min-height: 44px;">
                                <i class="fa-brands fa-google text-danger"></i> Google Maps
                            </a>
                            <a id="btnWazeNav"
                               href="https://waze.com/ul?q=<?= urlencode($activeTicket['authorized_destination']) ?>&amp;navigate=yes"
                               target="_blank" rel="noopener"
                               class="btn btn-light border fw-bold text-dark d-flex align-items-center justify-content-center gap-2 px-3 py-2 rounded-3 shadow-sm"
                               style="font-size: 0.82rem; min-height: 44px;">
                                <i class="fa-brands fa-waze text-info"></i> Waze
                            </a>
                        </div>
                    </div>

                    <!-- BLOWBAGETS Pre-Trip Safety Notice -->
                    <div class="mb-3 p-3 rounded-4 border d-flex align-items-center justify-content-between shadow-sm" style="background: <?= $blowbagetsPassed ? 'var(--success-light)' : 'var(--warning-light)' ?>; border-color: <?= $blowbagetsPassed ? 'var(--success-border)' : 'var(--warning-border)' ?> !important;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid <?= $blowbagetsPassed ? 'fa-circle-check text-success' : 'fa-shield-halved text-warning' ?> fs-4"></i>
                            <div>
                                <strong class="small d-block" style="color: <?= $blowbagetsPassed ? 'var(--success-dark)' : 'var(--warning-dark)' ?>; font-size: 0.84rem;">
                                    <?= $blowbagetsPassed ? 'BLOWBAGETS Safety Cleared' : 'BLOWBAGETS Checklist Required' ?>
                                </strong>
                                <span class="text-muted" style="font-size: 0.75rem;">
                                    <?= $blowbagetsPassed ? 'Inspected & certified for safe travel' : 'Must pass 10-point check before gate exit' ?>
                                </span>
                            </div>
                        </div>
                        <?php if (!$blowbagetsPassed): ?>
                            <button type="button" class="btn btn-sm btn-warning text-dark fw-bold px-3 py-2 rounded-3" data-bs-toggle="modal" data-bs-target="#blowbagetsModal" style="font-size: 0.8rem;">
                                Inspect
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-sm btn-outline-success fw-semibold px-2 py-1 rounded-3" data-bs-toggle="modal" data-bs-target="#blowbagetsModal" style="font-size: 0.74rem;">
                                Re-Inspect
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Scannable Gate Pass E-QR -->
                    <div class="qr-pass-container">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="small fw-bold text-secondary" style="font-size: 0.76rem;">
                                <i class="fa-solid fa-shield-check text-primary me-1"></i> Compound Gate E-Pass
                            </span>
                            <span class="mono small text-muted" style="font-size: 0.72rem;">ADMIN-F-001 rev1</span>
                        </div>

                        <div class="qr-code-box my-1">
                            <div id="activeTicketQr"></div>
                        </div>

                        <div class="mt-2 text-center">
                            <div class="mono fw-bold text-dark" style="font-size: 0.92rem;">
                                <?= esc($activeTicket['plate_number']) ?> &bull; <?= esc($activeTicket['ticket_serial_no']) ?>
                            </div>
                            <button type="button" class="btn btn-link btn-sm text-primary text-decoration-none fw-semibold p-0 mt-1" data-bs-toggle="modal" data-bs-target="#qrPassModal" style="font-size: 0.8rem;">
                                <i class="fa-solid fa-expand me-1"></i> Tap to Fullscreen for Gate Guard
                            </button>
                        </div>
                    </div>

                    <!-- Passengers & Schedule Details -->
                    <div class="p-3 rounded-4 bg-light border mb-3">
                        <div class="row g-2 small">
                            <div class="col-6">
                                <span class="text-muted d-block" style="font-size: 0.72rem;">Departure Schedule</span>
                                <strong class="mono text-dark" style="font-size: 0.84rem;">
                                    <?= date('M d, h:i A', strtotime($activeTicket['authorized_departure'])) ?>
                                </strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block" style="font-size: 0.72rem;">Admin Approver</span>
                                <strong class="text-dark text-truncate d-block" style="font-size: 0.84rem;">
                                    <?= esc($activeTicket['admin_approver_name'] ?? 'Admin Head') ?>
                                </strong>
                            </div>
                            <div class="col-12 mt-2 pt-2 border-top">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="text-muted" style="font-size: 0.72rem;">Authorized Passengers</span>
                                    <button type="button" class="btn btn-link p-0 text-warning text-decoration-none small fw-bold" data-bs-toggle="modal" data-bs-target="#delayModal" style="font-size: 0.76rem;">
                                        <i class="fa-solid fa-clock-rotate-left me-1"></i> Report Delay
                                    </button>
                                </div>
                                <div class="text-dark fw-semibold" style="font-size: 0.84rem;">
                                    <?= nl2br(esc($activeTicket['authorized_passengers'] ?? 'Official Coverage Team')) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Passenger Delays Logged -->
                    <?php if (!empty($recentDelays)): ?>
                        <div class="mb-3 p-3 bg-light rounded-4 border border-warning border-opacity-50">
                            <span class="small fw-bold text-warning d-block mb-1" style="font-size: 0.76rem;">
                                <i class="fa-solid fa-circle-exclamation me-1"></i> Recorded Pick-up Delays:
                            </span>
                            <?php foreach ($recentDelays as $dl): ?>
                                <div class="small text-secondary d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                                    <span><?= esc($dl['reason'] ?: 'Passenger boarding delay') ?></span>
                                    <span class="badge bg-warning bg-opacity-25 text-dark mono">+<?= esc($dl['delay_minutes']) ?> mins</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- View Document Action -->
                    <button type="button" class="btn-driver-outline mt-2" onclick="openMobileTicketModal(<?= (int)$activeTicket['id'] ?>)">
                        <i class="fa-solid fa-file-lines me-1"></i> View Full Electronic Trip Ticket Document
                    </button>
                </div>

            <?php else: ?>

                <!-- -------------------------------------------------------------
                     STANDBY STATE (When Trip Completed or No Active Dispatch)
                -------------------------------------------------------------- -->
                <?php if ($lastCompletedTicket): ?>
                    <!-- Standby Card (Trip Completed & Returned to Compound) -->
                    <div class="driver-card" style="border-top: 4px solid var(--success);">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="badge px-3 py-1 rounded-pill" style="background: var(--success-light); color: var(--success-dark); border: 1px solid var(--success-border); font-size: 0.76rem; font-weight: 700;">
                                <span class="status-dot pulse d-inline-block me-1" style="background: var(--success);"></span>
                                STANDBY &bull; WAITING FOR DISPATCH
                            </span>
                            <span class="badge bg-light text-muted border mono" style="font-size: 0.72rem;">PIA MOTORPOOL</span>
                        </div>

                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div style="width: 52px; height: 52px; border-radius: var(--radius-md); background: var(--success-light); color: var(--success); display: grid; place-items: center; font-size: 1.4rem; flex-shrink: 0; border: 1px solid var(--success-border);">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-1" style="font-size: 1.05rem;">Trip Completed &bull; Standby at Compound</h6>
                                <p class="text-secondary small mb-0" style="line-height: 1.45; font-size: 0.84rem;">
                                    Travel on <strong><?= esc($lastCompletedTicket['ticket_serial_no']) ?></strong> has been completed and audited. You are on standby at the PIA Motorpool compound, waiting for another dispatch trip.
                                </p>
                            </div>
                        </div>

                        <!-- Live Operational Status Strip -->
                        <div class="bento-metrics-grid mb-3">
                            <div class="bento-metric-card">
                                <div class="bento-metric-label">Duty State</div>
                                <div class="bento-metric-val text-success" style="font-size: 0.88rem;">
                                    <span class="status-dot d-inline-block me-1" style="background: var(--success);"></span>
                                    Available
                                </div>
                            </div>
                            <div class="bento-metric-card">
                                <div class="bento-metric-label">Location</div>
                                <div class="bento-metric-val text-dark" style="font-size: 0.88rem;">
                                    <i class="fa-solid fa-warehouse text-primary me-1" style="font-size: 0.8rem;"></i> Compound
                                </div>
                            </div>
                            <div class="bento-metric-card">
                                <div class="bento-metric-label">Dispatch</div>
                                <div class="bento-metric-val text-secondary" style="font-size: 0.84rem;">
                                    On Standby
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mb-2">
                            <button type="button" class="btn btn-primary flex-grow-1 py-2 rounded-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#dutyModal">
                                <i class="fa-solid fa-user-clock me-1"></i> Duty Status
                            </button>
                            <button type="button" class="btn btn-light border flex-grow-1 py-2 rounded-3 fw-bold text-dark" onclick="switchView('view-history')">
                                <i class="fa-solid fa-clock-rotate-left me-1"></i> Trip History
                            </button>
                        </div>

                        <form action="<?= base_url('driver/standby-checkin') ?>" method="POST" class="m-0">
                            <?= !is_cli() ? csrf_field() : '' ?>
                            <input type="hidden" name="driver_id" value="<?= esc($driver['id'] ?? '') ?>">
                            <button type="submit" class="btn btn-outline-success w-100 py-2 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-1 shadow-sm" style="min-height: 44px;">
                                <i class="fa-solid fa-hotel me-1"></i> Confirm Presence at Drivers' Lounge
                            </button>
                        </form>
                        <?php if (!empty($driver['standby_checkin_at'])): ?>
                            <div class="mt-2 text-center text-success fw-bold" style="font-size: 0.74rem;">
                                <i class="fa-solid fa-circle-check me-1"></i> Stationed at Lounge &bull; Checked in: <?= date('h:i A (M d)', strtotime($driver['standby_checkin_at'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Completed Trip Reconciliation & Audit Summary -->
                    <div class="driver-card">
                        <div class="driver-card-header mb-2">
                            <div>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 mono px-2 py-1" style="font-size: 0.74rem;">
                                    <?= esc($lastCompletedTicket['ticket_serial_no']) ?>
                                </span>
                                <span class="small text-muted ms-1" style="font-size: 0.76rem;">Latest Completed Travel</span>
                            </div>
                            <span class="badge px-2 py-1 rounded-pill" style="background: var(--success-light); color: var(--success-dark); font-size: 0.74rem; font-weight: 700;">
                                <i class="fa-solid fa-shield-check me-1"></i> Audited
                            </span>
                        </div>

                        <!-- Route & Travel Details Recap -->
                        <div class="p-3 rounded-4 bg-light border mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="small text-muted text-uppercase mono" style="font-size: 0.7rem;">Authorized Destination</span>
                                <span class="badge bg-white text-dark border mono" style="font-size: 0.68rem;">ADMIN-F-001 rev1</span>
                            </div>
                            <h6 class="fw-bold text-dark mb-1" style="font-size: 1rem;">
                                <i class="fa-solid fa-location-dot text-danger me-1"></i> <?= esc($lastCompletedTicket['authorized_destination']) ?>
                            </h6>
                            <?php if (!empty($lastCompletedTicket['authorized_purpose'])): ?>
                                <p class="small text-secondary mb-2" style="font-size: 0.82rem;"><?= esc($lastCompletedTicket['authorized_purpose']) ?></p>
                            <?php endif; ?>

                            <div class="row g-2 pt-2 border-top small" style="font-size: 0.78rem;">
                                <div class="col-6">
                                    <span class="text-muted d-block" style="font-size: 0.7rem;">Gate Ingress</span>
                                    <strong class="mono text-dark">
                                        <?= !empty($lastCompletedTicket['arrival_back_time']) ? date('M d, Y h:i A', strtotime($lastCompletedTicket['arrival_back_time'])) : (!empty($lastCompletedTicket['authorized_return']) ? date('M d, Y h:i A', strtotime($lastCompletedTicket['authorized_return'])) : 'Verified Ingress') ?>
                                    </strong>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block" style="font-size: 0.7rem;">Distance Traveled</span>
                                    <strong class="mono text-primary fs-6"><?= number_format((float)($lastCompletedTicket['total_distance_km'] ?? 0), 1) ?> KM</strong>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block" style="font-size: 0.7rem;">Odometer (Start &rarr; Return)</span>
                                    <span class="mono text-secondary"><?= number_format((float)($lastCompletedTicket['start_odometer'] ?? 0), 0) ?> &rarr; <?= number_format((float)($lastCompletedTicket['return_odometer'] ?? 0), 0) ?> KM</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block" style="font-size: 0.7rem;">Fuel Accounting</span>
                                    <span class="mono text-success fw-semibold"><?= number_format((float)($lastCompletedTicket['fuel_used_liters'] ?? 0), 1) ?> L Used</span>
                                </div>
                            </div>
                        </div>

                        <!-- Dual Certifications Badges -->
                        <div class="p-2 rounded-3 border mb-3 d-flex align-items-center justify-content-between" style="background: #f8fafc;">
                            <div class="d-flex align-items-center gap-1 small text-success fw-semibold" style="font-size: 0.76rem;">
                                <i class="fa-solid fa-circle-check"></i> Driver Certified
                            </div>
                            <div class="d-flex align-items-center gap-1 small text-success fw-semibold" style="font-size: 0.76rem;">
                                <i class="fa-solid fa-circle-check"></i> Passenger Certified
                            </div>
                            <div class="d-flex align-items-center gap-1 small text-primary fw-semibold" style="font-size: 0.76rem;">
                                <i class="fa-solid fa-stamp"></i> COA Audited
                            </div>
                        </div>

                        <!-- Document Actions -->
                        <div class="d-flex gap-2">
                            <button type="button" class="btn-driver-outline flex-grow-1" onclick="openMobileTicketModal(<?= (int)$lastCompletedTicket['id'] ?>)">
                                <i class="fa-solid fa-file-lines me-1"></i> View Reconciled DTT
                            </button>
                            <a href="<?= base_url('tickets/' . $lastCompletedTicket['id'] . '/print') ?>" target="_blank" class="btn btn-light border d-inline-flex align-items-center justify-content-center px-3 rounded-3" title="Print Official DTT Form">
                                <i class="fa-solid fa-print"></i>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Standby Card (No Scheduled Dispatch Today) -->
                    <div class="driver-card" style="border-top: 4px solid var(--brand-accent);">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="badge px-3 py-1 rounded-pill" style="background: var(--brand-light); color: var(--brand-accent); border: 1px solid var(--brand-light-border); font-size: 0.76rem; font-weight: 700;">
                                <span class="status-dot pulse d-inline-block me-1" style="background: var(--brand-accent);"></span>
                                STANDBY &bull; NO DISPATCH TODAY
                            </span>
                            <span class="badge bg-light text-muted border mono" style="font-size: 0.72rem;">PIA MOTORPOOL</span>
                        </div>

                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div style="width: 52px; height: 52px; border-radius: var(--radius-md); background: var(--brand-light); color: var(--brand-accent); display: grid; place-items: center; font-size: 1.4rem; flex-shrink: 0; border: 1px solid var(--brand-light-border);">
                                <i class="fa-solid fa-calendar-check"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-1" style="font-size: 1.05rem;">No Scheduled Dispatch for Today</h6>
                                <p class="text-secondary small mb-0" style="line-height: 1.45; font-size: 0.84rem;">
                                    You have no active trip ticket assigned for today. You are stationed on standby at the PIA Motorpool compound, ready for upcoming missions.
                                </p>
                            </div>
                        </div>

                        <!-- Live Operational Status Strip -->
                        <div class="bento-metrics-grid mb-3">
                            <div class="bento-metric-card">
                                <div class="bento-metric-label">Duty State</div>
                                <div class="bento-metric-val text-success" style="font-size: 0.88rem;">
                                    <span class="status-dot d-inline-block me-1" style="background: var(--success);"></span>
                                    Available
                                </div>
                            </div>
                            <div class="bento-metric-card">
                                <div class="bento-metric-label">Location</div>
                                <div class="bento-metric-val text-dark" style="font-size: 0.88rem;">
                                    <i class="fa-solid fa-warehouse text-primary me-1" style="font-size: 0.8rem;"></i> Compound
                                </div>
                            </div>
                            <div class="bento-metric-card">
                                <div class="bento-metric-label">Dispatch</div>
                                <div class="bento-metric-val text-secondary" style="font-size: 0.84rem;">
                                    Ready for Trip
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mb-2">
                            <button type="button" class="btn btn-primary flex-grow-1 py-2 rounded-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#dutyModal">
                                <i class="fa-solid fa-user-clock me-1"></i> Duty Status
                            </button>
                            <button type="button" class="btn btn-light border flex-grow-1 py-2 rounded-3 fw-bold text-dark" onclick="switchView('view-vehicle')">
                                <i class="fa-solid fa-truck-front me-1"></i> View Vehicle
                            </button>
                        </div>
                        <form action="<?= base_url('driver/standby-checkin') ?>" method="POST" class="m-0">
                            <?= !is_cli() ? csrf_field() : '' ?>
                            <input type="hidden" name="driver_id" value="<?= esc($driver['id'] ?? '') ?>">
                            <button type="submit" class="btn btn-outline-success w-100 py-2 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-1 shadow-sm" style="min-height: 44px;">
                                <i class="fa-solid fa-hotel me-1"></i> Confirm Presence at Drivers' Lounge
                            </button>
                        </form>
                        <?php if (!empty($driver['standby_checkin_at'])): ?>
                            <div class="mt-2 text-center text-success fw-bold" style="font-size: 0.74rem;">
                                <i class="fa-solid fa-circle-check me-1"></i> Stationed at Lounge &bull; Checked in: <?= date('h:i A (M d)', strtotime($driver['standby_checkin_at'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Assigned Unit Mini Summary -->
            <?php if ($vehicle): ?>
                <div class="driver-card">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <span class="small text-muted d-block fw-bold text-uppercase" style="font-size: 0.72rem;">Assigned Motorpool Unit</span>
                            <h5 class="fw-bold text-dark mb-0 mono" style="font-size: 1.15rem;"><?= esc($vehicle['plate_number']) ?></h5>
                            <span class="small text-secondary fw-medium"><?= esc(($vehicle['make'] ?? '') . ' ' . ($vehicle['model'] ?? '')) ?></span>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-light text-dark border mono px-2 py-1 rounded-pill"><?= esc($vehicle['fuel_type'] ?? 'Diesel') ?></span>
                        </div>
                    </div>

                    <div class="bento-metrics-grid mb-3">
                        <div class="bento-metric-card">
                            <div class="bento-metric-label">Odometer</div>
                            <div class="bento-metric-val mono"><?= number_format((float)($vehicle['odometer_km'] ?? 0), 0) ?> <span class="small text-muted" style="font-size: 0.7rem;">KM</span></div>
                        </div>
                        <div class="bento-metric-card">
                            <div class="bento-metric-label">Fuel Level</div>
                            <?php $fuelPct = (float)($vehicle['current_fuel_level'] ?? 80); ?>
                            <div class="bento-metric-val mono <?= $fuelPct < 25 ? 'text-danger' : ($fuelPct < 50 ? 'text-warning' : 'text-success') ?>">
                                <?= number_format($fuelPct, 0) ?>%
                            </div>
                        </div>
                        <div class="bento-metric-card">
                            <div class="bento-metric-label">Condition</div>
                            <div class="bento-metric-val text-capitalize" style="font-size: 0.88rem; color: var(--success);">
                                <?= esc(str_replace('_', ' ', $vehicle['status'] ?? 'Active')) ?>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn-driver-outline py-2 w-100" onclick="switchView('view-vehicle')" style="min-height: 44px; font-size: 0.84rem;">
                        <i class="fa-solid fa-sliders me-1 text-primary"></i> Vehicle Diagnostics &amp; Toll RFID
                    </button>
                </div>
            <?php endif; ?>

        </section>

        <!-- =========================================================
             VIEW 2: VEHICLE & SAFETY (Diagnostics, RFID & Fuel Logs)
        ========================================================== -->
        <section id="view-vehicle" class="tab-panel-view">

            <!-- Vehicle Identification & Specs -->
            <?php if ($vehicle): ?>
                <div class="driver-card">
                    <div class="driver-card-header">
                        <span class="card-title-sm"><i class="fa-solid fa-truck-pickup text-primary"></i> Vehicle Specifications</span>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 rounded-pill" style="font-size: 0.74rem;">
                            <?= ucfirst(str_replace('_', ' ', $vehicle['status'] ?? 'Active')) ?>
                        </span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="fw-bold text-dark mb-0 mono"><?= esc($vehicle['plate_number']) ?></h4>
                            <span class="text-secondary small fw-medium"><?= esc($vehicle['make'] . ' ' . $vehicle['model'] . ' (' . ($vehicle['year'] ?? date('Y')) . ')') ?></span>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-light text-secondary border px-2 py-1"><?= esc($vehicle['vehicle_code'] ?? 'UNIT') ?></span>
                        </div>
                    </div>

                    <div class="row g-2 mb-3 small">
                        <div class="col-6 p-2 rounded-3 bg-light border">
                            <span class="text-muted d-block" style="font-size: 0.7rem;">Engine Status</span>
                            <strong class="text-success" style="font-size: 0.84rem;"><?= esc(ucfirst($vehicle['engine_status'] ?? 'Good')) ?></strong>
                        </div>
                        <div class="col-6 p-2 rounded-3 bg-light border">
                            <span class="text-muted d-block" style="font-size: 0.7rem;">Battery Voltage</span>
                            <strong class="text-dark mono" style="font-size: 0.84rem;"><?= esc($vehicle['battery_voltage'] ?? '12.6') ?> V</strong>
                        </div>
                        <div class="col-6 p-2 rounded-3 bg-light border">
                            <span class="text-muted d-block" style="font-size: 0.7rem;">Coolant Temperature</span>
                            <strong class="text-dark mono" style="font-size: 0.84rem;"><?= esc($vehicle['coolant_temp_c'] ?? '85') ?> °C</strong>
                        </div>
                        <div class="col-6 p-2 rounded-3 bg-light border">
                            <span class="text-muted d-block" style="font-size: 0.7rem;">Tank Capacity</span>
                            <strong class="text-dark mono" style="font-size: 0.84rem;"><?= esc($vehicle['tank_capacity_l'] ?? '60') ?> L</strong>
                        </div>
                    </div>

                    <!-- BLOWBAGETS Pre-Trip Safety Action Button -->
                    <?php if ($activeTicket): ?>
                        <button type="button" class="btn-driver-outline py-2 w-100" data-bs-toggle="modal" data-bs-target="#blowbagetsModal" style="min-height: 46px;">
                            <i class="fa-solid fa-list-check text-primary me-1"></i> Perform BLOWBAGETS Pre-Trip Inspection
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Tollway RFID Cards (AutoSweep & EasyTrip) -->
                <div class="driver-card">
                    <div class="driver-card-header">
                        <span class="card-title-sm"><i class="fa-solid fa-id-card-clip text-primary"></i> Tollway RFID Balances</span>
                        <span class="badge bg-light text-muted border mono" style="font-size: 0.72rem;">FR-6.1</span>
                    </div>

                    <?php if (empty($rfidCards)): ?>
                        <div class="text-center py-4 text-muted small">
                            No RFID cards currently linked to vehicle <?= esc($vehicle['plate_number']) ?>.
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2 mb-2">
                            <?php foreach ($rfidCards as $card): ?>
                                <?php $isLow = (float)$card['balance'] < (float)($card['low_balance_threshold'] ?? 500); ?>
                                <div class="p-3 rounded-4 border d-flex align-items-center justify-content-between shadow-sm" style="background: <?= $isLow ? 'var(--warning-light)' : '#f8fafc' ?>; border-color: <?= $isLow ? 'var(--warning-border)' : 'var(--border-subtle)' ?> !important;">
                                    <div>
                                        <div class="d-flex align-items-center gap-2">
                                            <strong class="text-uppercase text-dark" style="font-size: 0.9rem;">
                                                <?= esc($card['provider']) ?>
                                            </strong>
                                            <?php if ($isLow): ?>
                                                <span class="badge bg-warning text-dark rounded-pill" style="font-size: 0.7rem;">Low Balance</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mono small text-muted" style="font-size: 0.78rem;">
                                            <?= esc($card['card_number']) ?>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="mono fw-bold fs-5 <?= $isLow ? 'text-warning' : 'text-dark' ?>">
                                            ₱<?= number_format((float)$card['balance'], 2) ?>
                                        </div>
                                        <span class="text-muted" style="font-size: 0.72rem;">Live Balance</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Fuel Logging & Recent Receipts Ledger -->
            <div class="driver-card">
                <div class="driver-card-header">
                    <span class="card-title-sm"><i class="fa-solid fa-gas-pump text-success"></i> Petroleum &amp; Fuel Purchases</span>
                    <button type="button" class="btn btn-sm btn-success fw-bold px-3 py-1 rounded-pill" data-bs-toggle="modal" data-bs-target="#fuelModal" style="font-size: 0.8rem;">
                        <i class="fa-solid fa-plus me-1"></i> Log Fuel
                    </button>
                </div>

                <?php if (empty($recentFuelLogs)): ?>
                    <div class="text-center py-4 text-muted small">
                        <i class="fa-solid fa-gas-pump fs-2 text-secondary opacity-50 mb-2"></i>
                        <p class="mb-0">No fuel purchase receipts logged recently.</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($recentFuelLogs as $fl): ?>
                            <div class="p-3 rounded-4 bg-light border d-flex justify-content-between align-items-center small shadow-sm">
                                <div>
                                    <strong class="text-dark d-block" style="font-size: 0.88rem;"><?= esc($fl['fuel_station']) ?></strong>
                                    <span class="text-muted mono" style="font-size: 0.75rem;">
                                        <?= date('M d, Y', strtotime($fl['fuel_date'])) ?> &bull; <?= number_format($fl['liters'], 1) ?> Liters
                                    </span>
                                </div>
                                <div class="text-end mono">
                                    <strong class="text-dark d-block fs-6">₱<?= number_format($fl['total_cost'], 2) ?></strong>
                                    <span class="text-muted" style="font-size: 0.72rem;">Receipt #<?= esc(substr($fl['receipt_no'] ?? 'REC', -7)) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </section>

        <!-- =========================================================
             VIEW 3: HISTORY & SUPPORT (Archives & Hotlines)
        ========================================================== -->
        <section id="view-history" class="tab-panel-view">

            <!-- Driver License & Qualifications -->
            <div class="driver-card">
                <div class="driver-card-header">
                    <span class="card-title-sm"><i class="fa-solid fa-address-card text-primary"></i> Driver Credentials</span>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 rounded-pill" style="font-size: 0.74rem;">Verified</span>
                </div>
                <div class="row g-2 small">
                    <div class="col-6">
                        <span class="text-muted d-block" style="font-size: 0.72rem;">License Number</span>
                        <strong class="mono text-dark" style="font-size: 0.88rem;"><?= esc($driver['license_number'] ?? 'N01-19-082914') ?></strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted d-block" style="font-size: 0.72rem;">License Expiry</span>
                        <strong class="mono text-dark" style="font-size: 0.88rem;"><?= esc($driver['license_expiry'] ?? 'Valid') ?></strong>
                    </div>
                    <div class="col-12 mt-1">
                        <span class="text-muted d-block" style="font-size: 0.72rem;">License Type</span>
                        <span class="text-dark fw-semibold"><?= esc($driver['license_type'] ?? 'Professional Driver') ?></span>
                    </div>
                </div>
            </div>

            <!-- Assigned Trip Tickets Archive -->
            <div class="driver-card">
                <div class="driver-card-header">
                    <span class="card-title-sm"><i class="fa-solid fa-ticket text-primary"></i> Assigned Trip Tickets (<?= count($tripTickets) ?>)</span>
                    <span class="badge bg-light text-muted border mono" style="font-size: 0.72rem;">ADMIN-F-001</span>
                </div>

                <?php if (empty($tripTickets)): ?>
                    <div class="text-center py-4 text-muted small">
                        No trip tickets found in your driver profile.
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($tripTickets as $tk): ?>
                            <div class="p-3 rounded-4 bg-light border d-flex justify-content-between align-items-center small shadow-sm">
                                <div role="button" onclick="openMobileTicketModal(<?= (int)$tk['id'] ?>)" class="flex-grow-1" style="cursor: pointer;">
                                    <div class="d-flex align-items-center gap-1 mb-1">
                                        <strong class="mono text-primary" style="font-size: 0.88rem;"><?= esc($tk['ticket_serial_no']) ?></strong>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size: 0.7rem;">
                                            <?= ucfirst($tk['status']) ?>
                                        </span>
                                    </div>
                                    <span class="text-muted text-truncate d-block fw-medium" style="max-width: 200px; font-size: 0.78rem;">
                                        <?= esc($tk['authorized_destination']) ?>
                                    </span>
                                </div>
                                <div class="d-flex gap-1">
                                    <button type="button" onclick="openQrForTicket(<?= (int)$tk['id'] ?>)" class="btn btn-sm btn-light border p-2 rounded-3" title="Show QR Gate Pass" style="width: 38px; height: 38px;">
                                        <i class="fa-solid fa-qrcode text-primary"></i>
                                    </button>
                                    <button type="button" onclick="openMobileTicketModal(<?= (int)$tk['id'] ?>)" class="btn btn-sm btn-light border p-2 rounded-3" title="View Mobile Ticket" style="width: 38px; height: 38px;">
                                        <i class="fa-solid fa-eye text-secondary"></i>
                                    </button>
                                    <a href="<?= base_url('tickets/' . $tk['id'] . '/print') ?>" target="_blank" class="btn btn-sm btn-light border p-2 rounded-3 d-inline-flex align-items-center justify-content-center" title="Print PDF" style="width: 38px; height: 38px;">
                                        <i class="fa-solid fa-print text-secondary"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Emergency Motorpool Contacts -->
            <div class="driver-card">
                <div class="driver-card-header">
                    <span class="card-title-sm"><i class="fa-solid fa-headset text-primary"></i> Motorpool Emergency Contacts</span>
                </div>
                <div class="d-flex flex-column gap-2 small">
                    <a href="tel:+639185550200" class="p-3 rounded-4 bg-light border text-decoration-none text-dark d-flex justify-content-between align-items-center shadow-sm">
                        <div>
                            <strong class="d-block" style="font-size: 0.9rem;">Motorpool Dispatch Console</strong>
                            <span class="text-muted" style="font-size: 0.76rem;">Engr. Roberto D. Tan</span>
                        </div>
                        <span class="btn btn-sm btn-outline-primary py-2 px-3 rounded-pill fw-bold"><i class="fa-solid fa-phone me-1"></i> Call</span>
                    </a>
                    <a href="tel:+639185550400" class="p-3 rounded-4 bg-light border text-decoration-none text-dark d-flex justify-content-between align-items-center shadow-sm">
                        <div>
                            <strong class="d-block" style="font-size: 0.9rem;">Compound Security Gate #1</strong>
                            <span class="text-muted" style="font-size: 0.76rem;">Sgt. Danilo Ramos</span>
                        </div>
                        <span class="btn btn-sm btn-outline-secondary py-2 px-3 rounded-pill fw-bold"><i class="fa-solid fa-phone me-1"></i> Call</span>
                    </a>
                </div>
            </div>

        </section>

    </main>

    <!-- -------------------------------------------------------------
         BOTTOM THUMB NAVIGATION DOCK (Consolidated - No Duplications)
    -------------------------------------------------------------- -->
    <nav class="bottom-thumb-dock" role="navigation" aria-label="Bottom Navigation">
        <button type="button" class="thumb-nav-btn active" data-view="view-active-trip" id="dockBtnTrip">
            <i class="fa-solid fa-car"></i>
            <span>Active Trip</span>
            <?php if ($activeTicket && in_array($activeTicket['status'], ['issued', 'departed', 'arrived_dest', 'departed_dest', 'returned'], true)): ?>
                <span class="nav-alert-indicator"></span>
            <?php endif; ?>
        </button>

        <button type="button" class="thumb-nav-btn" data-view="view-vehicle" id="dockBtnVehicle">
            <i class="fa-solid fa-truck-front"></i>
            <span>Vehicle &amp; Health</span>
        </button>

        <button type="button" class="thumb-nav-btn" data-view="view-history" id="dockBtnHistory">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Tickets &amp; Help</span>
        </button>
    </nav>

    <!-- =============================================================
         MODALS (Mobile Bottom-Sheet Ergonomic Design)
    ============================================================== -->

    <!-- 1. FULLSCREEN GATE PASS QR MODAL -->
    <div class="modal fade" id="qrPassModal" tabindex="-1" aria-labelledby="qrPassModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-drag-handle d-md-none"></div>
                <div class="modal-header border-0 pb-0">
                    <div>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 mono">ADMIN-F-001 rev1</span>
                        <h6 class="modal-title fw-bold text-dark mt-1" id="qrPassModalLabel">Gate Security Clearance Pass</h6>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <p class="text-muted small mb-3" style="font-size: 0.84rem;">Present this code directly to Compound Gate Guard Checkpoint Scanner.</p>
                    
                    <div class="p-3 bg-white border rounded-4 d-inline-block shadow-sm my-1" style="width: 240px; height: 240px;">
                        <div id="modalQrCanvas" class="d-flex align-items-center justify-content-center h-100"></div>
                    </div>

                    <div class="mono fw-bold fs-4 text-dark mt-3">
                        <?= esc($activeTicket['ticket_serial_no'] ?? ($vehicle['plate_number'] ?? 'GATE-PASS')) ?>
                    </div>
                    <div class="small text-muted mono" style="font-size: 0.8rem;">
                        Unit: <strong class="text-dark"><?= esc($vehicle['plate_number'] ?? 'N/A') ?></strong> &bull; Driver: <strong class="text-dark"><?= esc($driver['driver_code'] ?? 'DRV-101') ?></strong>
                    </div>

                    <div class="p-2 mt-3 bg-light rounded-3 text-muted small" style="font-size: 0.76rem;">
                        <i class="fa-solid fa-shield-check text-success me-1"></i> HMAC-SHA256 Cryptographic Gate Clearance Token
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary w-100 py-3 rounded-3 fw-bold" data-bs-dismiss="modal" style="min-height: 48px;">Close Pass</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. PHASE 2: CONFIRM DESTINATION ARRIVAL MODAL -->
    <?php if ($activeTicket): ?>
    <div class="modal fade" id="arriveModal" tabindex="-1" aria-labelledby="arriveModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <form action="<?= base_url('driver/arrive-dest') ?>" method="POST" id="arriveForm">
                    <?= !is_cli() ? csrf_field() : '' ?>
                    <input type="hidden" name="ticket_id" value="<?= (int)$activeTicket['id'] ?>">
                    <div class="modal-drag-handle d-md-none"></div>
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 mono">PHASE 2 &bull; DESTINATION ARRIVAL</span>
                            <h6 class="modal-title fw-bold text-dark mt-1" id="arriveModalLabel">Confirm Arrival at Event / Location</h6>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3">
                        <p class="text-muted small mb-3" style="font-size: 0.84rem;">
                            Confirming arrival automatically stamps your official destination arrival date &amp; time on Driver's Trip Ticket <strong><?= esc($activeTicket['ticket_serial_no']) ?></strong> and notifies Motorpool Dispatch.
                        </p>
                        <div class="p-3 rounded-3 bg-light border mb-3">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Authorized Destination</span>
                            <strong class="text-dark fs-6"><?= esc($activeTicket['authorized_destination']) ?></strong>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-secondary">Current Odometer at Destination (KM)</label>
                            <input type="number" step="0.1" name="dest_odometer" class="form-control form-control-lg mono" value="<?= (float)($activeTicket['dest_odometer'] ?? ($vehicle['odometer_km'] ?? ($activeTicket['start_odometer'] ?? 0))) ?>" required>
                            <span class="text-muted" style="font-size: 0.74rem;">Start Odometer was <?= number_format((float)($activeTicket['start_odometer'] ?? 0), 1) ?> KM</span>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 d-flex gap-2">
                        <button type="button" class="btn btn-light py-2 px-3 rounded-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold flex-grow-1 py-3 rounded-3 shadow-sm" style="min-height: 48px;">
                            <i class="fa-solid fa-circle-check me-1"></i> Confirm &amp; Stamp Arrival
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 3. PHASE 3: CONFIRM DESTINATION DEPARTURE MODAL -->
    <?php if ($activeTicket): ?>
    <div class="modal fade" id="departModal" tabindex="-1" aria-labelledby="departModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <form action="<?= base_url('driver/depart-dest') ?>" method="POST" id="departForm">
                    <?= !is_cli() ? csrf_field() : '' ?>
                    <input type="hidden" name="ticket_id" value="<?= (int)$activeTicket['id'] ?>">
                    <div class="modal-drag-handle d-md-none"></div>
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <span class="badge bg-warning bg-opacity-25 text-dark border border-warning mono">PHASE 3 &bull; DEPARTURE FROM DESTINATION</span>
                            <h6 class="modal-title fw-bold text-dark mt-1" id="departModalLabel">Confirm Departure from Destination</h6>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3">
                        <p class="text-muted small mb-3" style="font-size: 0.84rem;">
                            Stamps your departure date &amp; time from <strong><?= esc($activeTicket['authorized_destination']) ?></strong> and transitions your trip status to En Route back to PIA Compound.
                        </p>
                        <div class="p-3 rounded-3 bg-light border mb-2 small">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="fa-solid fa-shield-halved text-success"></i>
                                <strong class="text-dark">Compound Gate Ingress (Phase 4):</strong>
                            </div>
                            <span class="text-muted" style="font-size: 0.8rem;">Upon returning to the compound, present your QR Code at Gate #1 to record official return arrival and return odometer.</span>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 d-flex gap-2">
                        <button type="button" class="btn btn-light py-2 px-3 rounded-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning fw-bold text-dark flex-grow-1 py-3 rounded-3 shadow-sm" style="min-height: 48px;">
                            <i class="fa-solid fa-car-side me-1"></i> Confirm Departure
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 4. PASSENGER DELAY LOGGER MODAL (FR-4.2) -->
    <?php if ($activeTicket): ?>
    <div class="modal fade" id="delayModal" tabindex="-1" aria-labelledby="delayModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <form action="<?= base_url('tickets/' . $activeTicket['id'] . '/delay') ?>" method="POST">
                    <?= !is_cli() ? csrf_field() : '' ?>
                    <div class="modal-drag-handle d-md-none"></div>
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">FR-4.2 Protocol</span>
                            <h6 class="modal-title fw-bold text-dark mt-1" id="delayModalLabel">Log Passenger Pick-up Delay</h6>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-3">
                        <p class="text-muted small mb-3" style="font-size: 0.84rem;">
                            Delays of 15 minutes or longer automatically notify Motorpool Dispatch to adjust fleet schedules.
                        </p>

                        <label class="form-label small fw-bold text-secondary">Delay Duration</label>
                        <div class="chip-group" id="delayMinutesChips">
                            <button type="button" class="chip-option" data-val="10">+10 mins</button>
                            <button type="button" class="chip-option selected" data-val="15">+15 mins (Standard)</button>
                            <button type="button" class="chip-option" data-val="30">+30 mins</button>
                            <button type="button" class="chip-option" data-val="45">+45 mins</button>
                        </div>
                        <input type="hidden" name="delay_minutes" id="inputDelayMinutes" value="15">

                        <label class="form-label small fw-bold text-secondary">Reason / Delay Category</label>
                        <div class="chip-group" id="delayReasonChips">
                            <button type="button" class="chip-option selected" data-reason="Passengers still in conference/meeting">In Meeting</button>
                            <button type="button" class="chip-option" data-reason="Awaiting security clearance / briefing">Security Hold</button>
                            <button type="button" class="chip-option" data-reason="Lobby / Building departure hold">Lobby Wait</button>
                            <button type="button" class="chip-option" data-reason="Heavy road congestion en route to pick-up">Traffic Jam</button>
                        </div>
                        <input type="text" name="reason" id="inputDelayReason" class="form-control" value="Passengers still in conference/meeting" placeholder="Or type custom reason..." required>
                    </div>
                    <div class="modal-footer border-0 pt-0 d-flex gap-2">
                        <button type="button" class="btn btn-light py-2 px-3 rounded-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning fw-bold text-dark flex-grow-1 py-3 rounded-3 shadow-sm" style="min-height: 48px;">Submit Delay Log</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 5. REFUEL & RECEIPT LOGGING MODAL -->
    <div class="modal fade" id="fuelModal" tabindex="-1" aria-labelledby="fuelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <form action="<?= base_url('api/v1/driver/fuel') ?>" method="POST" enctype="multipart/form-data">
                    <?= !is_cli() ? csrf_field() : '' ?>
                    <input type="hidden" name="vehicle_id" value="<?= $vehicle['id'] ?? 1 ?>">
                    <input type="hidden" name="driver_id" value="<?= $driver['id'] ?? 1 ?>">
                    <div class="modal-drag-handle d-md-none"></div>
                    <div class="modal-header border-0 pb-0">
                        <h6 class="modal-title fw-bold text-dark" id="fuelModalLabel">
                            <i class="fa-solid fa-gas-pump text-success me-2"></i> Log Official Gas Station Refuel
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Gas Station / Vendor</label>
                            <input type="text" name="fuel_station" class="form-control" placeholder="e.g. Petron Philam, Shell Visayas Ave" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-secondary">Volume (Liters)</label>
                                <input type="number" step="0.01" name="liters" class="form-control mono" required placeholder="0.00">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-secondary">Total Cost (₱)</label>
                                <input type="number" step="0.01" name="total_cost" class="form-control mono" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Current Odometer (KM)</label>
                            <input type="number" step="0.1" name="odometer_km" class="form-control mono" value="<?= $vehicle['odometer_km'] ?? 0 ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-secondary">Receipt Photo (Optional)</label>
                            <input type="file" name="receipt_image" accept="image/*" class="form-control" capture="environment">
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 d-flex gap-2">
                        <button type="button" class="btn btn-light py-2 px-3 rounded-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success fw-bold flex-grow-1 py-3 rounded-3 shadow-sm" style="min-height: 48px;">Submit Fuel Log</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 6. REPORT INCIDENT / EMERGENCY SOS MODAL (FR-4.3) -->
    <div class="modal fade" id="incidentModal" tabindex="-1" aria-labelledby="incidentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <form action="<?= base_url('driver/incident') ?>" method="POST">
                    <?= !is_cli() ? csrf_field() : '' ?>
                    <input type="hidden" name="vehicle_id" value="<?= $vehicle['id'] ?? 1 ?>">
                    <input type="hidden" name="driver_id" value="<?= $driver['id'] ?? 1 ?>">
                    <input type="hidden" name="trip_id" value="<?= $activeTicket['id'] ?? '' ?>">
                    <div class="modal-drag-handle d-md-none"></div>
                    <div class="modal-header border-0 pb-0">
                        <h6 class="modal-title fw-bold text-danger" id="incidentModalLabel">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i> Report Breakdown / Emergency SOS
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div class="alert alert-danger border-0 p-3 rounded-3 small mb-3" style="font-size: 0.8rem; background: var(--danger-light); color: var(--danger-dark);">
                            <i class="fa-solid fa-circle-info me-1"></i> Reporting a breakdown sets the vehicle to <strong>Disabled/Breakdown</strong> and routes a Pre-Repair Inspection Report (PIR) to the mechanic queue.
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Incident Category</label>
                            <select name="type" class="form-select" required>
                                <option value="Mechanical Breakdown">Mechanical Breakdown (Engine / Transmission)</option>
                                <option value="Tire Puncture / Flat">Tire Puncture / Flat</option>
                                <option value="Brake Malfunction">Brake Malfunction</option>
                                <option value="Electrical Failure">Electrical / Battery Failure</option>
                                <option value="Accident / Collision">Accident / Collision</option>
                                <option value="Medical Emergency">Driver Medical Issue</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Severity Level</label>
                            <select name="severity" class="form-select" required>
                                <option value="minor">Minor (Vehicle operable, slight delay)</option>
                                <option value="moderate" selected>Moderate (Roadside assistance required)</option>
                                <option value="severe">Severe (Vehicle immobilized / Towing required)</option>
                                <option value="critical">Critical (Immediate Emergency SOS)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Location / Landmark</label>
                            <div class="input-group">
                                <input type="text" name="location" id="incidentLocationInput" class="form-control" placeholder="e.g. EDSA Northbound near Quezon Ave" required>
                                <button class="btn btn-outline-primary fw-bold" type="button" id="btnGetGps" title="Detect GPS Location">
                                    <i class="fa-solid fa-location-crosshairs"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-bold text-secondary">Description &amp; Assistance Needed</label>
                            <textarea name="description" rows="3" class="form-control" placeholder="Provide details of the vehicle condition and assistance needed..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 d-flex gap-2">
                        <button type="button" class="btn btn-light py-2 px-3 rounded-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger fw-bold flex-grow-1 py-3 rounded-3 shadow-sm" style="min-height: 48px;">Transmit Alert to Dispatch</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 7. DUTY STATUS MODAL -->
    <div class="modal fade" id="dutyModal" tabindex="-1" aria-labelledby="dutyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <form action="<?= base_url('driver/status') ?>" method="POST">
                    <?= !is_cli() ? csrf_field() : '' ?>
                    <input type="hidden" name="driver_id" value="<?= $driver['id'] ?? 1 ?>">
                    <div class="modal-drag-handle d-md-none"></div>
                    <div class="modal-header border-0 pb-0">
                        <h6 class="modal-title fw-bold text-dark" id="dutyModalLabel">Update Duty Status</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3">
                        <p class="text-muted small mb-3" style="font-size: 0.84rem;">Select your operational status for dispatch availability:</p>

                        <div class="d-flex flex-column gap-2">
                            <label class="p-3 rounded-4 border d-flex align-items-center justify-content-between cursor-pointer" style="background: #f8fafc; cursor: pointer;">
                                <div class="d-flex align-items-center gap-3">
                                    <input type="radio" name="status" value="available" <?= ($driver['status'] ?? '') === 'available' ? 'checked' : '' ?> class="form-check-input mt-0">
                                    <div>
                                        <strong class="text-success d-block" style="font-size: 0.9rem;">Available</strong>
                                        <span class="text-muted" style="font-size: 0.76rem;">On standby and ready for trip assignment</span>
                                    </div>
                                </div>
                                <span class="status-dot" style="background: var(--success); width: 12px; height: 12px;"></span>
                            </label>

                            <label class="p-3 rounded-4 border d-flex align-items-center justify-content-between cursor-pointer" style="background: #f8fafc; cursor: pointer;">
                                <div class="d-flex align-items-center gap-3">
                                    <input type="radio" name="status" value="on_trip" <?= ($driver['status'] ?? '') === 'on_trip' ? 'checked' : '' ?> class="form-check-input mt-0">
                                    <div>
                                        <strong class="text-warning d-block" style="font-size: 0.9rem;">On Trip / In Transit</strong>
                                        <span class="text-muted" style="font-size: 0.76rem;">Currently deployed on active mission</span>
                                    </div>
                                </div>
                                <span class="status-dot" style="background: var(--warning); width: 12px; height: 12px;"></span>
                            </label>

                            <label class="p-3 rounded-4 border d-flex align-items-center justify-content-between cursor-pointer" style="background: #f8fafc; cursor: pointer;">
                                <div class="d-flex align-items-center gap-3">
                                    <input type="radio" name="status" value="off_duty" <?= ($driver['status'] ?? '') === 'off_duty' ? 'checked' : '' ?> class="form-check-input mt-0">
                                    <div>
                                        <strong class="text-secondary d-block" style="font-size: 0.9rem;">Off Duty</strong>
                                        <span class="text-muted" style="font-size: 0.76rem;">Shift ended or mandatory rest period</span>
                                    </div>
                                </div>
                                <span class="status-dot" style="background: #94a3b8; width: 12px; height: 12px;"></span>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 d-flex gap-2">
                        <button type="button" class="btn btn-light py-2 px-3 rounded-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold flex-grow-1 py-3 rounded-3 shadow-sm" style="min-height: 48px;">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 8. SECTION B POST-TRIP EXECUTION & DUAL CERTIFICATION MODAL (ADMIN-F-001 rev1) -->
    <?php if ($activeTicket): ?>
    <div class="modal fade" id="sectionBModal" tabindex="-1" aria-labelledby="sectionBModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <form action="<?= base_url('tickets/' . $activeTicket['id'] . '/update') ?>" method="POST" id="sectionBForm">
                    <?= !is_cli() ? csrf_field() : '' ?>
                    <div class="modal-drag-handle d-md-none"></div>
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 mono">ADMIN-F-001 rev1</span>
                            <h6 class="modal-title fw-bold text-dark mt-1" id="sectionBModalLabel">Section B: Post-Trip &amp; Fuel Accounting Report</h6>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3">
                        <p class="text-muted small mb-3" style="font-size: 0.84rem;">
                            Official post-trip audit formula and dual certifications for <strong><?= esc($activeTicket['ticket_serial_no']) ?></strong>.
                        </p>

                        <!-- Gate Auto-Stamped Timestamps -->
                        <div class="fw-bold text-dark small text-uppercase mb-2 pb-1 border-bottom d-flex justify-content-between align-items-center">
                            <span><i class="fa-solid fa-clock text-primary me-1"></i> 1. Compound Gate &amp; Destination Timestamps</span>
                            <span class="badge bg-dark text-white mono" style="font-size: 0.65rem;">GATE AUTO-STAMPED</span>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small text-secondary mb-1">Compound Departure (Egress)</label>
                                <input type="text" class="form-control form-control-sm mono bg-light fw-bold text-dark" value="<?= !empty($activeTicket['departure_time']) ? date('M d, Y h:i A', strtotime($activeTicket['departure_time'])) : 'Pending Gate Scan' ?>" readonly tabindex="-1">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-secondary mb-1">Compound Arrival (Ingress)</label>
                                <input type="text" class="form-control form-control-sm mono bg-light fw-bold text-dark" value="<?= !empty($activeTicket['arrival_back_time']) ? date('M d, Y h:i A', strtotime($activeTicket['arrival_back_time'])) : 'Pending Gate Scan' ?>" readonly tabindex="-1">
                            </div>
                            <div class="col-6 mt-2">
                                <label class="form-label small text-secondary mb-1">Arrival at Destination</label>
                                <input type="datetime-local" name="arrival_dest_time" class="form-control form-control-sm mono" value="<?= !empty($activeTicket['arrival_dest_time']) ? date('Y-m-d\TH:i', strtotime($activeTicket['arrival_dest_time'])) : '' ?>">
                            </div>
                            <div class="col-6 mt-2">
                                <label class="form-label small text-secondary mb-1">Departure from Destination</label>
                                <input type="datetime-local" name="departure_dest_time" class="form-control form-control-sm mono" value="<?= !empty($activeTicket['departure_dest_time']) ? date('Y-m-d\TH:i', strtotime($activeTicket['departure_dest_time'])) : '' ?>">
                            </div>
                        </div>

                        <!-- Trip Odometers -->
                        <div class="fw-bold text-dark small text-uppercase mb-2 pb-1 border-bottom">
                            <i class="fa-solid fa-gauge text-primary me-1"></i> 2. Odometer Readings
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <label class="form-label small text-secondary">Start Odo (KM)</label>
                                <input type="number" step="0.1" name="start_odometer" id="m_start_odo" class="form-control mono" value="<?= (float)($activeTicket['start_odometer'] ?? ($vehicle['odometer_km'] ?? 0)) ?>" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label small text-secondary">Dest. Odo (KM)</label>
                                <input type="number" step="0.1" name="dest_odometer" id="m_dest_odo" class="form-control mono" value="<?= (float)($activeTicket['dest_odometer'] ?? 0) ?>">
                            </div>
                            <div class="col-4">
                                <label class="form-label small text-secondary">Return Odo (KM)</label>
                                <input type="number" step="0.1" name="return_odometer" id="m_return_odo" class="form-control mono" value="<?= (float)($activeTicket['return_odometer'] ?? ($vehicle['odometer_km'] ?? 0)) ?>" required>
                            </div>
                            <div class="col-12">
                                <div class="p-2 rounded bg-light border d-flex justify-content-between align-items-center small">
                                    <span class="text-muted">Total Distance Travelled:</span>
                                    <strong class="mono text-primary fs-6" id="m_calcDistance"><?= number_format((float)($activeTicket['total_distance_km'] ?? 0), 1) ?> KM</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Fuel Accounting Formula -->
                        <div class="fw-bold text-dark small text-uppercase mb-2 pb-1 border-bottom">
                            <i class="fa-solid fa-gas-pump text-primary me-1"></i> 3. Fuel Balance Formula &amp; Efficiency
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small text-secondary">Balance in Tank at Start (L)</label>
                                <input type="number" step="0.01" name="fuel_balance_start_liters" id="m_fuel_start" class="form-control mono" value="<?= (float)($activeTicket['fuel_balance_start_liters'] ?? 40) ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-secondary">Issued from Stock (L)</label>
                                <input type="number" step="0.01" name="fuel_issued_stock_liters" id="m_fuel_issued" class="form-control mono" value="<?= (float)($activeTicket['fuel_issued_stock_liters'] ?? 0) ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-secondary">Purchased on Trip (L)</label>
                                <input type="number" step="0.01" name="fuel_purchased_liters" id="m_fuel_purchased" class="form-control mono" value="<?= (float)($activeTicket['fuel_purchased_liters'] ?? 0) ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-secondary">Fuel Purchased Cost (₱)</label>
                                <input type="number" step="0.01" name="fuel_purchased_cost" class="form-control mono" value="<?= (float)($activeTicket['fuel_purchased_cost'] ?? 0) ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-secondary">Total Fuel Used on Trip (L)</label>
                                <input type="number" step="0.01" name="fuel_used_liters" id="m_fuel_used" class="form-control mono" value="<?= (float)($activeTicket['fuel_used_liters'] ?? 0) ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-secondary">Calculated Ending Balance (L)</label>
                                <div class="p-2 rounded bg-light border text-end">
                                    <strong class="mono text-success" id="m_calcEndFuel"><?= number_format((float)($activeTicket['fuel_balance_end_liters'] ?? 40), 2) ?> L</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Toll Expenses -->
                        <div class="fw-bold text-dark small text-uppercase mb-2 pb-1 border-bottom">
                            <i class="fa-solid fa-road text-primary me-1"></i> 4. Tollways &amp; Expenses
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small text-secondary">Toll Amount (₱)</label>
                                <input type="number" step="0.01" name="toll_expense" class="form-control mono" value="<?= (float)($activeTicket['toll_expense'] ?? 0) ?>" placeholder="0.00">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-secondary">Toll Provider / Payment</label>
                                <select name="toll_provider" class="form-select form-select-sm">
                                    <option value="">None / No Toll</option>
                                    <option value="autosweep" <?= ($activeTicket['toll_provider'] ?? '') === 'autosweep' ? 'selected' : '' ?>>AutoSweep (SLEX/Skyway)</option>
                                    <option value="easytrip" <?= ($activeTicket['toll_provider'] ?? '') === 'easytrip' ? 'selected' : '' ?>>EasyTrip (NLEX/SCTEX)</option>
                                    <option value="cash" <?= ($activeTicket['toll_provider'] ?? '') === 'cash' ? 'selected' : '' ?>>Cash Reimbursable</option>
                                </select>
                            </div>
                        </div>

                        <!-- Dual Certifications (Driver + Passenger) -->
                        <div class="fw-bold text-dark small text-uppercase mb-2 pb-1 border-bottom">
                            <i class="fa-solid fa-stamp text-primary me-1"></i> 5. Dual Certifications (COA Mandated)
                        </div>

                        <!-- Driver Certification -->
                        <div class="p-3 rounded-4 bg-light border mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="driver_certified" value="1" id="m_driverCert" <?= !empty($activeTicket['driver_certified']) || $status === 'returned' ? 'checked' : '' ?> required>
                                <label class="form-check-label small text-dark" for="m_driverCert">
                                    <strong>Driver's Certification:</strong> I hereby certify on official honor that the above trip route, distance, and fuel consumption are true and correct as accomplished.
                                </label>
                            </div>
                        </div>

                        <!-- Passenger Certification -->
                        <div class="p-3 rounded-4 bg-light border mb-2">
                            <div class="mb-2">
                                <label class="form-label small text-secondary mb-1">Passenger / Requisitioner Certifier Name</label>
                                <input type="text" name="passenger_certifier_name" class="form-control form-control-sm" value="<?= esc($activeTicket['passenger_certifier_name'] ?? ($activeTicket['requestor_name'] ?? ($activeTicket['authorized_passengers'] ?? 'Authorized Passenger'))) ?>" required>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="passenger_certified" value="1" id="m_passengerCert" <?= !empty($activeTicket['passenger_certified']) || $status === 'returned' ? 'checked' : '' ?> required>
                                <label class="form-check-label small text-dark" for="m_passengerCert">
                                    <strong>Passenger's Certification:</strong> I hereby certify that the vehicle was used exclusively for official business as authorized on this Driver's Trip Ticket.
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 d-flex gap-2">
                        <button type="button" class="btn btn-light py-2 px-3 rounded-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold flex-grow-1 py-3 rounded-3 shadow-sm" style="min-height: 48px;">
                            <i class="fa-solid fa-check-double me-1"></i> Certify &amp; Complete Section B
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 9. BLOWBAGETS PRE-TRIP SAFETY CHECKLIST MODAL (FR-5.1) -->
    <?php
        $safetyItems = $checklistItems ?: \App\Models\SafetyCheckModel::checklistItems();
    ?>
    <?php if ($activeTicket): ?>
    <div class="modal fade" id="blowbagetsModal" tabindex="-1" aria-labelledby="blowbagetsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <form action="<?= base_url('safety/' . $activeTicket['id']) ?>" method="POST" id="blowbagetsMobileForm">
                    <?= !is_cli() ? csrf_field() : '' ?>
                    <div class="modal-drag-handle d-md-none"></div>
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <span class="badge bg-warning bg-opacity-25 text-dark border border-warning mono" style="font-size: 0.72rem;">FR-5.1 &bull; BLOWBAGETS PROTOCOL</span>
                            <h6 class="modal-title fw-bold text-dark mt-1" id="blowbagetsModalLabel">10-Point Pre-Trip Safety Inspection</h6>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-3 p-3 rounded-4 bg-light border">
                            <div class="small">
                                <strong class="text-dark d-block mono" style="font-size: 0.88rem;"><?= esc($activeTicket['ticket_serial_no']) ?> &bull; <?= esc($vehicle['plate_number'] ?? '') ?></strong>
                                <span class="text-muted" style="font-size: 0.76rem;">All 10 points must pass for compound gate clearance</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-success fw-bold px-3 py-1 rounded-pill" id="btnMarkAllPassMobile" style="font-size: 0.76rem;">
                                <i class="fa-solid fa-check-double me-1"></i>All Pass
                            </button>
                        </div>

                        <div class="d-flex flex-column gap-2 mb-3">
                            <?php foreach ($safetyItems as $code => $label): ?>
                                <?php
                                    $parts = explode('—', (string)$label, 2);
                                    $letter = trim($parts[0] ?? strtoupper(substr($code, 0, 1)));
                                    $title = trim($parts[1] ?? (string)$label);
                                ?>
                                <div class="p-2 rounded-3 border bg-white d-flex align-items-center justify-content-between shadow-sm">
                                    <div class="d-flex align-items-center gap-2 pe-2">
                                        <span class="badge bg-primary text-white mono d-inline-flex align-items-center justify-content-center rounded-2" style="width: 32px; height: 32px; font-size: 0.84rem;">
                                            <?= esc($letter) ?>
                                        </span>
                                        <div>
                                            <strong class="text-dark d-block small" style="font-size: 0.84rem;"><?= esc($title) ?></strong>
                                            <span class="text-muted d-block" style="font-size: 0.72rem; line-height: 1.2;">Inspect before compound departure</span>
                                        </div>
                                    </div>
                                    <div class="btn-group btn-group-sm flex-shrink-0" role="group">
                                        <input type="radio" class="btn-check safety-pass-radio" name="check[<?= esc($code) ?>]" id="mob_<?= esc($code) ?>_pass" value="pass" checked required>
                                        <label class="btn btn-outline-success fw-bold px-2 py-1" for="mob_<?= esc($code) ?>_pass" style="font-size: 0.76rem;">PASS</label>

                                        <input type="radio" class="btn-check safety-fail-radio" name="check[<?= esc($code) ?>]" id="mob_<?= esc($code) ?>_fail" value="fail">
                                        <label class="btn btn-outline-danger fw-bold px-2 py-1" for="mob_<?= esc($code) ?>_fail" style="font-size: 0.76rem;">FAIL</label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-bold text-secondary mb-1">Inspection Remarks / Defect Notes (Optional)</label>
                            <textarea name="remarks" rows="2" class="form-control" placeholder="Note any observations or leave blank if all 10 points pass..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 d-flex gap-2">
                        <button type="button" class="btn btn-light py-2 px-3 rounded-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success fw-bold flex-grow-1 py-3 rounded-3 shadow-sm" style="min-height: 48px;">
                            <i class="fa-solid fa-shield-check me-1"></i> Certify &amp; Submit Inspection
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 10. MOBILE TRIP TICKET DOCUMENT VIEWER MODAL (ADMIN-F-001 rev1) -->
    <div class="modal fade" id="ticketDetailModal" tabindex="-1" aria-labelledby="ticketDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-drag-handle d-md-none"></div>
                <div class="modal-header border-0 pb-0">
                    <div>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 mono" id="td_serial_badge">ADMIN-F-001 rev1</span>
                        <h6 class="modal-title fw-bold text-dark mt-1" id="ticketDetailModalLabel">Electronic Driver's Trip Ticket</h6>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="p-3 rounded-4 bg-light border mb-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-muted" style="font-size: 0.72rem;">Official Serial Number</div>
                            <strong class="mono text-dark fs-5" id="td_serial_no">DTT-0000</strong>
                        </div>
                        <span class="badge bg-primary px-3 py-1 rounded-pill" id="td_status_badge">Issued</span>
                    </div>

                    <div class="fw-bold text-dark small text-uppercase mb-2 pb-1 border-bottom">
                        <i class="fa-solid fa-file-signature text-primary me-1"></i> Section A: Dispatch Authorization
                    </div>
                    <div class="row g-2 small mb-3">
                        <div class="col-6">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Assigned Vehicle</span>
                            <strong class="mono text-dark" id="td_vehicle">-</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Assigned Driver</span>
                            <strong class="text-dark" id="td_driver"><?= esc(($driver['first_name'] ?? '') . ' ' . ($driver['last_name'] ?? '')) ?></strong>
                        </div>
                        <div class="col-12 mt-2">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Authorized Destination</span>
                            <strong class="text-dark" id="td_destination">-</strong>
                        </div>
                        <div class="col-12 mt-1">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Official Purpose</span>
                            <span class="text-secondary" id="td_purpose">-</span>
                        </div>
                        <div class="col-12 mt-1">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Authorized Passengers</span>
                            <span class="text-dark" id="td_passengers">-</span>
                        </div>
                    </div>

                    <div class="fw-bold text-dark small text-uppercase mb-2 pb-1 border-bottom d-flex justify-content-between align-items-center">
                        <span><i class="fa-solid fa-shield-halved text-primary me-1"></i> Section B: Compound Gate &amp; Trip Audit</span>
                        <span class="badge bg-dark text-white mono" style="font-size: 0.65rem;">GATE AUTO-STAMPED</span>
                    </div>
                    <div class="row g-2 small mb-3">
                        <div class="col-6">
                            <div class="p-2 rounded bg-light border">
                                <span class="text-muted d-block" style="font-size: 0.7rem;">Compound Departure (Gate Egress)</span>
                                <strong class="mono text-dark" id="td_dep_time">Pending Gate Scan</strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light border">
                                <span class="text-muted d-block" style="font-size: 0.7rem;">Compound Arrival (Gate Ingress)</span>
                                <strong class="mono text-dark" id="td_arr_time">Pending Gate Scan</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded bg-light border text-center">
                                <span class="text-muted d-block" style="font-size: 0.7rem;">Start Odo</span>
                                <strong class="mono text-dark" id="td_start_odo">0.0 KM</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded bg-light border text-center">
                                <span class="text-muted d-block" style="font-size: 0.7rem;">Return Odo</span>
                                <strong class="mono text-dark" id="td_return_odo">0.0 KM</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded bg-light border text-center">
                                <span class="text-muted d-block" style="font-size: 0.7rem;">Distance</span>
                                <strong class="mono text-primary" id="td_total_dist">0.0 KM</strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light border">
                                <span class="text-muted d-block" style="font-size: 0.7rem;">Fuel Summary</span>
                                <strong class="mono text-success" id="td_fuel_summary">0.00 L</strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light border">
                                <span class="text-muted d-block" style="font-size: 0.7rem;">Tollways &amp; RFID</span>
                                <strong class="mono text-dark" id="td_toll_summary">₱0.00</strong>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-between gap-2">
                    <a href="#" target="_blank" id="td_print_btn" class="btn btn-outline-secondary btn-sm py-2 px-3 rounded-3">
                        <i class="fa-solid fa-print me-1"></i> Print DTT
                    </a>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm fw-bold py-2 px-3 rounded-3" id="td_qr_btn">
                            <i class="fa-solid fa-qrcode me-1"></i> Gate QR
                        </button>
                        <button type="button" class="btn btn-light btn-sm border py-2 px-3 rounded-3" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
        $ticketsJsMap = [];
        foreach ($tripTickets as $tk) {
            $ticketsJsMap[(int)$tk['id']] = [
                'id'                     => (int)$tk['id'],
                'ticket_serial_no'       => $tk['ticket_serial_no'] ?? '',
                'qr_crypt_token'         => $tk['qr_crypt_token'] ?? ($tk['ticket_serial_no'] ?? ''),
                'status'                 => $tk['status'] ?? 'issued',
                'plate_number'           => $tk['plate_number'] ?? ($vehicle['plate_number'] ?? 'N/A'),
                'make_model'             => trim(($tk['make'] ?? ($vehicle['make'] ?? '')) . ' ' . ($tk['model'] ?? ($vehicle['model'] ?? ''))),
                'authorized_destination' => $tk['authorized_destination'] ?? 'Official Coverage',
                'authorized_purpose'     => $tk['authorized_purpose'] ?? 'Official Travel',
                'authorized_passengers'  => $tk['authorized_passengers'] ?? 'Official Coverage Team',
                'departure_time'         => !empty($tk['departure_time']) ? date('M d, Y h:i A', strtotime($tk['departure_time'])) : 'Pending Gate Egress Scan',
                'arrival_back_time'      => !empty($tk['arrival_back_time']) ? date('M d, Y h:i A', strtotime($tk['arrival_back_time'])) : 'Pending Gate Ingress Scan',
                'start_odometer'         => number_format((float)($tk['start_odometer'] ?? 0), 1),
                'return_odometer'        => number_format((float)($tk['return_odometer'] ?? 0), 1),
                'total_distance_km'      => number_format((float)($tk['total_distance_km'] ?? 0), 1),
                'fuel_used_liters'       => number_format((float)($tk['fuel_used_liters'] ?? 0), 2),
                'fuel_balance_end_liters'=> number_format((float)($tk['fuel_balance_end_liters'] ?? 0), 2),
                'toll_expense'           => number_format((float)($tk['toll_expense'] ?? 0), 2),
                'toll_provider'          => strtoupper($tk['toll_provider'] ?? 'NONE'),
                'print_url'              => base_url('tickets/' . $tk['id'] . '/print'),
            ];
        }
    ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Client Scripts for 4-Phase Automation, Navigation & Forms -->
    <script>
    const driverTicketsMap = <?= json_encode($ticketsJsMap) ?>;

    function renderModalQrToken(tokenText, serialLabel) {
        const modalQr = document.getElementById('modalQrCanvas');
        if (!modalQr || !tokenText) return;
        modalQr.innerHTML = '';
        new QRCode(modalQr, {
            text: tokenText,
            width: 210,
            height: 210,
            colorDark: "#0f172a",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.L
        });
    }

    window.openQrForTicket = function(ticketId) {
        const tk = driverTicketsMap[ticketId];
        if (tk) {
            renderModalQrToken(tk.qr_crypt_token || tk.ticket_serial_no, tk.ticket_serial_no);
        }
        const detailEl = document.getElementById('ticketDetailModal');
        if (detailEl) {
            const detailInst = bootstrap.Modal.getInstance(detailEl);
            if (detailInst) detailInst.hide();
        }
        const qrModal = new bootstrap.Modal(document.getElementById('qrPassModal'));
        qrModal.show();
    };

    window.openMobileTicketModal = function(ticketId) {
        const tk = driverTicketsMap[ticketId];
        if (!tk) return;

        document.getElementById('td_serial_no').innerText = tk.ticket_serial_no;
        document.getElementById('td_status_badge').innerText = tk.status.toUpperCase();
        document.getElementById('td_vehicle').innerText = tk.plate_number + (tk.make_model ? ' • ' + tk.make_model : '');
        document.getElementById('td_destination').innerText = tk.authorized_destination;
        document.getElementById('td_purpose').innerText = tk.authorized_purpose;
        document.getElementById('td_passengers').innerText = tk.authorized_passengers;
        document.getElementById('td_dep_time').innerText = tk.departure_time;
        document.getElementById('td_arr_time').innerText = tk.arrival_back_time;
        document.getElementById('td_start_odo').innerText = tk.start_odometer + ' KM';
        document.getElementById('td_return_odo').innerText = tk.return_odometer + ' KM';
        document.getElementById('td_total_dist').innerText = tk.total_distance_km + ' KM';
        document.getElementById('td_fuel_summary').innerText = 'Used: ' + tk.fuel_used_liters + ' L • End: ' + tk.fuel_balance_end_liters + ' L';
        document.getElementById('td_toll_summary').innerText = '₱' + tk.toll_expense + ' (' + tk.toll_provider + ')';
        document.getElementById('td_print_btn').href = tk.print_url;

        const qrBtn = document.getElementById('td_qr_btn');
        if (qrBtn) {
            qrBtn.onclick = function() {
                window.openQrForTicket(ticketId);
            };
        }

        const detailModal = new bootstrap.Modal(document.getElementById('ticketDetailModal'));
        detailModal.show();
    };

    // View Switcher Engine (Thumb Dock & Direct Triggers)
    window.switchView = function(targetViewId) {
        const tabViews = document.querySelectorAll('.tab-panel-view');
        tabViews.forEach(v => {
            if (v.id === targetViewId) {
                v.classList.add('active');
            } else {
                v.classList.remove('active');
            }
        });

        const dockButtons = document.querySelectorAll('.thumb-nav-btn[data-view]');
        dockButtons.forEach(btn => {
            if (btn.getAttribute('data-view') === targetViewId) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    document.addEventListener('DOMContentLoaded', function () {
        // 1. Navigation dock triggers
        const allViewTriggers = document.querySelectorAll('[data-view]');
        allViewTriggers.forEach(btn => {
            btn.addEventListener('click', function (e) {
                const target = this.getAttribute('data-view');
                if (target) {
                    e.preventDefault();
                    window.switchView(target);
                }
            });
        });

        // 2. High-Contrast Low-Density QR Code Rendering
        const qrToken = <?= json_encode($activeTicket['qr_crypt_token'] ?? ($activeTicket['ticket_serial_no'] ?? ($vehicle['plate_number'] ?? 'PIA-TOKEN-DEMO'))) ?>;

        const cardQr = document.getElementById('activeTicketQr');
        if (cardQr && qrToken) {
            new QRCode(cardQr, {
                text: qrToken,
                width: 150,
                height: 150,
                colorDark: "#0f172a",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.L
            });
        }

        renderModalQrToken(qrToken);

        // 3. Phase 2 Disagree handler (Snoozes the destination arrival card)
        const btnArriveDisagree = document.getElementById('btnArriveDisagree');
        const arrivalPromptCard = document.getElementById('arrivalPromptCard');
        if (btnArriveDisagree && arrivalPromptCard) {
            btnArriveDisagree.addEventListener('click', function () {
                arrivalPromptCard.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                arrivalPromptCard.style.opacity = '0';
                arrivalPromptCard.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    arrivalPromptCard.innerHTML = `
                        <div class="d-flex align-items-center justify-content-between p-1">
                            <span class="small text-white"><i class="fa-solid fa-car-side me-1"></i> Trip in transit. Have you reached destination?</span>
                            <button type="button" class="btn btn-sm btn-light text-primary py-1 px-3 fw-bold rounded-pill" data-bs-toggle="modal" data-bs-target="#arriveModal">
                                Arrived
                            </button>
                        </div>
                    `;
                    arrivalPromptCard.style.opacity = '1';
                    arrivalPromptCard.style.transform = 'translateY(0)';
                }, 300);
            });
        }

        // 4. BLOWBAGETS Quick "Mark All Pass" Helper
        const btnMarkAllPassMobile = document.getElementById('btnMarkAllPassMobile');
        if (btnMarkAllPassMobile) {
            btnMarkAllPassMobile.addEventListener('click', function () {
                document.querySelectorAll('.safety-pass-radio').forEach(r => { r.checked = true; });
            });
        }

        // 5. Passenger Delay Chips Interactive Selector
        const delayChips = document.querySelectorAll('#delayMinutesChips .chip-option');
        const delayInput = document.getElementById('inputDelayMinutes');
        delayChips.forEach(chip => {
            chip.addEventListener('click', function () {
                delayChips.forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                if (delayInput) delayInput.value = this.getAttribute('data-val');
            });
        });

        const reasonChips = document.querySelectorAll('#delayReasonChips .chip-option');
        const reasonInput = document.getElementById('inputDelayReason');
        reasonChips.forEach(chip => {
            chip.addEventListener('click', function () {
                reasonChips.forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                if (reasonInput) reasonInput.value = this.getAttribute('data-reason');
            });
        });

        // 6. Section B Live Calculator
        const sOdo = document.getElementById('m_start_odo');
        const rOdo = document.getElementById('m_return_odo');
        const calcDist = document.getElementById('m_calcDistance');

        const fStart = document.getElementById('m_fuel_start');
        const fIssued = document.getElementById('m_fuel_issued');
        const fPurch = document.getElementById('m_fuel_purchased');
        const fUsed = document.getElementById('m_fuel_used');
        const calcEnd = document.getElementById('m_calcEndFuel');

        function recalcSectionB() {
            if (sOdo && rOdo && calcDist) {
                const s = parseFloat(sOdo.value) || 0;
                const r = parseFloat(rOdo.value) || 0;
                const d = Math.max(0, r - s);
                calcDist.innerText = d.toFixed(1) + ' KM';
            }

            if (fStart && calcEnd) {
                const sF = parseFloat(fStart.value) || 0;
                const iF = parseFloat(fIssued ? fIssued.value : 0) || 0;
                const pF = parseFloat(fPurch ? fPurch.value : 0) || 0;
                const uF = parseFloat(fUsed ? fUsed.value : 0) || 0;
                const end = (sF + iF + pF) - uF;
                calcEnd.innerText = end.toFixed(2) + ' Liters';
            }
        }

        [sOdo, rOdo, fStart, fIssued, fPurch, fUsed].forEach(inp => {
            if (inp) inp.addEventListener('input', recalcSectionB);
        });

        // 7. GPS Locator for Incident Reporting
        const btnGps = document.getElementById('btnGetGps');
        const locInput = document.getElementById('incidentLocationInput');
        if (btnGps && locInput) {
            btnGps.addEventListener('click', function () {
                if (navigator.geolocation) {
                    btnGps.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
                    navigator.geolocation.getCurrentPosition(
                        function (position) {
                            const lat = position.coords.latitude.toFixed(6);
                            const lng = position.coords.longitude.toFixed(6);
                            locInput.value = 'GPS: ' + lat + ', ' + lng;
                            btnGps.innerHTML = '<i class="fa-solid fa-check text-success"></i>';
                        },
                        function (err) {
                            alert('Could not detect GPS location automatically. Please type landmark manually.');
                            btnGps.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i>';
                        },
                        { timeout: 8000, enableHighAccuracy: true }
                    );
                }
            });
        }

        // 8. Deep-Link Query Parameter Routing
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        if (tabParam === 'history') {
            window.switchView('view-history');
        } else if (tabParam === 'vehicle') {
            window.switchView('view-vehicle');
        }

        const openSafetyId = urlParams.get('open_safety');
        if (openSafetyId) {
            const blowModalEl = document.getElementById('blowbagetsModal');
            if (blowModalEl) {
                new bootstrap.Modal(blowModalEl).show();
            }
        }

        const openSectionB = urlParams.get('open_section_b');
        if (openSectionB) {
            const secBModalEl = document.getElementById('sectionBModal');
            if (secBModalEl) {
                new bootstrap.Modal(secBModalEl).show();
            }
        }

        const ticketIdParam = urlParams.get('ticket_id');
        if (ticketIdParam && driverTicketsMap[parseInt(ticketIdParam, 10)]) {
            window.openMobileTicketModal(parseInt(ticketIdParam, 10));
        }

        // 9. Interactive Driver Road Navigation Map
        const navMapEl = document.getElementById('driverRouteMap');
        if (navMapEl && typeof L !== 'undefined') {
            const originLat   = <?= (float) ($vehicle['current_latitude'] ?? 14.657200) ?: 14.657200 ?>;
            const originLng   = <?= (float) ($vehicle['current_longitude'] ?? 121.045000) ?: 121.045000 ?>;
            const destAddress = <?= json_encode($activeTicket['authorized_destination'] ?? '') ?>;
            const vehicleId   = <?= (int) ($vehicle['id'] ?? 0) ?>;
            const activeTripId= <?= (int) ($activeTicket['id'] ?? 0) ?>;

            const driverMap = L.map('driverRouteMap', {
                zoomControl: true,
                attributionControl: false,
                scrollWheelZoom: false, // Ensures page scrolling isn't hijacked
                dragging: true,
                touchZoom: true
            }).setView([originLat, originLng], 13);

            // Google Maps Primary Driving Layer
            const googleDriverLayer = L.tileLayer('https://mt{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
                maxZoom: 20,
                subdomains: ['0', '1', '2', '3'],
                attribution: '&copy; Google Maps'
            }).addTo(driverMap);

            const navLayerGroup = L.featureGroup().addTo(driverMap);
            let driverMarker    = null;
            let gpsWatchId      = null;

            async function loadDriverRoute(curLat, curLng) {
                try {
                    const params = new URLSearchParams({
                        origin_lat: curLat,
                        origin_lng: curLng,
                        destination: destAddress
                    });
                    const res  = await fetch('<?= base_url('api/v1/route') ?>?' + params.toString());
                    const data = await res.json();

                    if (data.status !== 'success') return;

                    navLayerGroup.clearLayers();

                    const carIcon = L.divIcon({
                        className: 'driver-nav-car',
                        html: `<div style="background:#2563eb;color:#fff;border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:2.5px solid #fff;box-shadow:0 3px 8px rgba(37,99,235,0.45);font-size:14px;">
                                <i class="fa-solid fa-car-side"></i>
                               </div>`,
                        iconSize: [32, 32],
                        iconAnchor: [16, 16]
                    });
                    driverMarker = L.marker([curLat, curLng], { icon: carIcon })
                        .bindPopup('<strong>Current Vehicle Position</strong>')
                        .addTo(navLayerGroup);

                    if (data.destination && data.destination.lat && data.destination.lng) {
                        const destIcon = L.divIcon({
                            className: 'driver-nav-dest',
                            html: `<div style="background:#dc2626;color:#fff;border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:2.5px solid #fff;box-shadow:0 3px 8px rgba(220,38,38,0.45);font-size:14px;">
                                    <i class="fa-solid fa-flag-checkered"></i>
                                   </div>`,
                            iconSize: [32, 32],
                            iconAnchor: [16, 16]
                        });
                        L.marker([data.destination.lat, data.destination.lng], { icon: destIcon })
                            .bindPopup(`<strong>${destAddress}</strong>`)
                            .addTo(navLayerGroup);

                        const gBtn = document.getElementById('btnGoogleMapsDir');
                        if (gBtn) {
                            gBtn.href = `https://www.google.com/maps/dir/?api=1&origin=${curLat},${curLng}&destination=${data.destination.lat},${data.destination.lng}&travelmode=driving`;
                        }
                        const wBtn = document.getElementById('btnWazeNav');
                        if (wBtn) {
                            wBtn.href = `https://waze.com/ul?ll=${data.destination.lat},${data.destination.lng}&navigate=yes`;
                        }
                    }

                    const polyCoords = (data.leaflet_coords && data.leaflet_coords.length > 1)
                        ? data.leaflet_coords
                        : [[curLat, curLng], [data.destination.lat, data.destination.lng]];

                    L.polyline(polyCoords, {
                        color: '#2563eb',
                        weight: 5,
                        opacity: 0.9,
                        lineJoin: 'round'
                    }).addTo(navLayerGroup);

                    driverMap.fitBounds(navLayerGroup.getBounds().pad(0.18));

                    const distEl = document.getElementById('driverNavDistance');
                    const etaEl  = document.getElementById('driverNavEta');
                    if (distEl) distEl.innerText = `${data.distance_km} km`;
                    if (etaEl)  etaEl.innerText  = `~${data.duration_min} min`;

                    function getManeuverIcon(instruction) {
                        const text = (instruction || '').toLowerCase();
                        if (text.includes('arrive') || text.includes('destination') || text.includes('reach')) {
                            return '<i class="fa-solid fa-flag-checkered text-danger me-1"></i>';
                        }
                        if (text.includes('left')) {
                            return '<i class="fa-solid fa-arrow-turn-left text-primary me-1"></i>';
                        }
                        if (text.includes('right')) {
                            return '<i class="fa-solid fa-arrow-turn-right text-primary me-1"></i>';
                        }
                        if (text.includes('u-turn') || text.includes('uturn')) {
                            return '<i class="fa-solid fa-arrow-rotate-left text-warning me-1"></i>';
                        }
                        if (text.includes('roundabout') || text.includes('rotary')) {
                            return '<i class="fa-solid fa-rotate text-info me-1"></i>';
                        }
                        return '<i class="fa-solid fa-arrow-up text-primary me-1"></i>';
                    }

                    const nextEl  = document.getElementById('driverNextManeuver');
                    const stepsEl = document.getElementById('driverTurnSteps');
                    if (data.steps && data.steps.length > 0) {
                        if (nextEl) {
                            nextEl.innerHTML = `${getManeuverIcon(data.steps[0].instruction)} <strong>Next:</strong> ${data.steps[0].instruction}`;
                        }
                        if (stepsEl) {
                            stepsEl.innerHTML = data.steps.map((st, i) => `
                                <div class="d-flex justify-content-between align-items-center py-2 ${i < data.steps.length - 1 ? 'border-bottom' : ''}">
                                    <span>${getManeuverIcon(st.instruction)}<strong class="mono text-dark me-1">${i + 1}.</strong> ${st.instruction}</span>
                                    <span class="mono text-muted ms-2" style="font-size:0.72rem;white-space:nowrap;">${st.distance_m >= 1000 ? (st.distance_m / 1000).toFixed(1) + ' km' : st.distance_m + ' m'}</span>
                                </div>
                            `).join('');
                        }
                    }
                } catch (err) {
                    console.warn('Driver navigation route load error:', err);
                }
            }

            loadDriverRoute(originLat, originLng);
            setTimeout(() => { if (typeof driverMap !== 'undefined') driverMap.invalidateSize(); }, 350);

            const btnLiveGps = document.getElementById('btnDriverLiveGps');
            if (btnLiveGps) {
                btnLiveGps.addEventListener('click', function () {
                    if (!navigator.geolocation) {
                        alert('Geolocation is not supported by this browser.');
                        return;
                    }

                    if (gpsWatchId !== null) {
                        navigator.geolocation.clearWatch(gpsWatchId);
                        gpsWatchId = null;
                        btnLiveGps.className = 'btn btn-xs btn-outline-success py-1 px-2 rounded-2';
                        btnLiveGps.innerHTML = '<i class="fa-solid fa-location-crosshairs me-1"></i>Live GPS';
                        return;
                    }

                    btnLiveGps.className = 'btn btn-xs btn-success text-white py-1 px-2 rounded-2';
                    btnLiveGps.innerHTML = '<i class="fa-solid fa-satellite-dish fa-fade me-1"></i>GPS Active';

                    gpsWatchId = navigator.geolocation.watchPosition(
                        function (pos) {
                            const lat = parseFloat(pos.coords.latitude.toFixed(6));
                            const lng = parseFloat(pos.coords.longitude.toFixed(6));
                            const spd = pos.coords.speed ? Math.round(pos.coords.speed * 3.6) : 0;

                            if (driverMarker) {
                                driverMarker.setLatLng([lat, lng]);
                            }
                            loadDriverRoute(lat, lng);

                            if (vehicleId > 0) {
                                fetch('<?= base_url('api/v1/gps/ping') ?>', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({
                                        vehicle_id: vehicleId,
                                        trip_id: activeTripId || null,
                                        latitude: lat,
                                        longitude: lng,
                                        speed_kmh: spd
                                    })
                                }).catch(() => {});
                            }
                        },
                        function () {
                            btnLiveGps.className = 'btn btn-xs btn-outline-danger py-1 px-2 rounded-2';
                            btnLiveGps.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i>GPS Blocked';
                            gpsWatchId = null;
                        },
                        { enableHighAccuracy: true, maximumAge: 5000, timeout: 10000 }
                    );
                });
            }
        }
    });
    </script>
</body>
</html>
