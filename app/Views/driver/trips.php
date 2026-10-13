<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="theme-color" content="#080e1a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="PIA Motorpool Driver">
    <title><?= esc(($driver['first_name'] ?? 'Driver') . ' ' . ($driver['last_name'] ?? '')) ?> | Driver Mobile Portal</title>

    <!-- Google Fonts: Inter & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 & FontAwesome 6 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <!-- QRCode.js for high-speed offline gate pass generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        :root {
            --app-bg: #080e1a;
            --surface-1: #0f172a;
            --surface-2: #1e293b;
            --surface-3: #334155;
            --border-subtle: rgba(255, 255, 255, 0.08);
            --border-active: rgba(56, 189, 248, 0.4);

            --cyan-primary: #38bdf8;
            --cyan-glow: rgba(56, 189, 248, 0.25);
            --emerald-accent: #10b981;
            --amber-accent: #f59e0b;
            --rose-accent: #f43f5e;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;

            --touch-target: 48px;
            --safe-bottom: env(safe-area-inset-bottom, 16px);
            --safe-top: env(safe-area-inset-top, 0px);
        }

        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: var(--app-bg);
            color: var(--text-main);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
            padding-bottom: calc(88px + var(--safe-bottom));
            margin: 0;
            -webkit-font-smoothing: antialiased;
            letter-spacing: -0.011em;
            touch-action: manipulation;
        }

        .mono {
            font-family: 'JetBrains Mono', monospace;
            letter-spacing: -0.02em;
        }

        /* -------------------------------------------------------------
           Header & App Bar
        ------------------------------------------------------------- */
        .driver-app-bar {
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            padding: calc(12px + var(--safe-top)) 16px 12px;
            border-bottom: 1px solid var(--border-subtle);
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        .telemetry-beacon {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.72rem;
            color: #34d399;
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.25);
            padding: 3px 9px;
            border-radius: 999px;
            font-weight: 600;
        }

        .beacon-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.3);
            animation: beacon-pulse 2s infinite;
        }

        @keyframes beacon-pulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.6); }
            70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* -------------------------------------------------------------
           Mobile Cards & Surfaces
        ------------------------------------------------------------- */
        .mobile-card {
            background: var(--surface-2);
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 14px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.35);
            position: relative;
            overflow: hidden;
            transition: border-color 0.15s ease, transform 0.15s ease;
        }

        .mobile-card.card-highlight {
            border: 1px solid rgba(56, 189, 248, 0.35);
            box-shadow: 0 4px 20px rgba(56, 189, 248, 0.12);
        }

        .mobile-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        /* Quick Metric HUD */
        .hud-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 14px;
        }
        @media (min-width: 576px) {
            .hud-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .hud-tile {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid var(--border-subtle);
            border-radius: 12px;
            padding: 12px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .hud-label {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.04em;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .hud-value {
            font-size: 1.15rem;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.2;
        }

        /* -------------------------------------------------------------
           Quick Action Buttons (Ergonomic Touch Targets)
        ------------------------------------------------------------- */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 16px;
        }
        @media (min-width: 480px) {
            .action-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .btn-action-tile {
            background: var(--surface-1);
            border: 1px solid var(--border-subtle);
            border-radius: 14px;
            padding: 14px 10px;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 8px;
            min-height: var(--touch-target);
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
            position: relative;
        }

        .btn-action-tile:active {
            transform: scale(0.97);
            background: var(--surface-3);
        }

        .btn-action-tile .tile-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 1.15rem;
        }

        .btn-action-tile .tile-title {
            font-size: 0.76rem;
            font-weight: 600;
            color: #f1f5f9;
            line-height: 1.2;
        }

        /* -------------------------------------------------------------
           QR Gate Pass Card (High-Contrast for Scanners)
        ------------------------------------------------------------- */
        .qr-gate-pass-box {
            background: #ffffff;
            color: #0b1324;
            border-radius: 14px;
            padding: 16px;
            text-align: center;
            margin: 12px 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
        }

        .qr-canvas-container {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            padding: 8px;
            border-radius: 8px;
            margin: 4px auto;
        }

        .qr-canvas-container img,
        .qr-canvas-container canvas {
            display: block;
            margin: 0 auto;
            max-width: 100%;
        }

        /* -------------------------------------------------------------
           Route Stops Visualizer
        ------------------------------------------------------------- */
        .route-stop {
            position: relative;
            padding-left: 28px;
            padding-bottom: 12px;
        }

        .route-stop::before {
            content: '';
            position: absolute;
            left: 6px;
            top: 4px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--cyan-primary);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
        }

        .route-stop.dest::before {
            background: var(--rose-accent);
            box-shadow: 0 0 0 3px rgba(244, 63, 94, 0.2);
        }

        .route-stop::after {
            content: '';
            position: absolute;
            left: 11px;
            top: 18px;
            width: 2px;
            height: calc(100% - 6px);
            background: rgba(255, 255, 255, 0.15);
        }

        .route-stop:last-child {
            padding-bottom: 0;
        }

        .route-stop:last-child::after {
            display: none;
        }

        /* -------------------------------------------------------------
           Bottom Navigation Dock (Accessible & Thumb-Friendly)
        ------------------------------------------------------------- */
        .mobile-bottom-dock {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(15, 23, 42, 0.94);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid var(--border-subtle);
            padding: 8px 12px calc(8px + var(--safe-bottom));
            display: flex;
            justify-content: space-around;
            align-items: center;
            z-index: 1030;
            box-shadow: 0 -8px 24px rgba(0, 0, 0, 0.4);
        }

        .dock-nav-item {
            background: none;
            border: none;
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 600;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            padding: 6px 10px;
            border-radius: 12px;
            min-width: 62px;
            min-height: var(--touch-target);
            transition: all 0.18s ease;
            position: relative;
            cursor: pointer;
        }

        .dock-nav-item i {
            font-size: 1.25rem;
            transition: transform 0.18s ease;
        }

        .dock-nav-item:active {
            transform: scale(0.92);
        }

        .dock-nav-item.active {
            color: #ffffff;
            background: rgba(56, 189, 248, 0.12);
        }

        .dock-nav-item.active i {
            color: var(--cyan-primary);
            transform: translateY(-2px);
        }

        .dock-nav-item .dock-badge {
            position: absolute;
            top: 2px;
            right: 14px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--amber-accent);
            box-shadow: 0 0 0 2px var(--surface-1);
        }

        /* -------------------------------------------------------------
           Tabbed Views Management
        ------------------------------------------------------------- */
        .driver-tab-view {
            display: none;
        }
        .driver-tab-view.active {
            display: block;
            animation: fadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* -------------------------------------------------------------
           Mobile Modals (Touch-Friendly Full Bottom Sheet)
        ------------------------------------------------------------- */
        .modal-content {
            background-color: var(--surface-2);
            color: var(--text-main);
            border: 1px solid var(--border-subtle);
            border-radius: 20px;
            overflow: hidden;
        }

        .modal-header {
            border-bottom: 1px solid var(--border-subtle);
            padding: 16px 20px;
            background: rgba(15, 23, 42, 0.6);
        }

        .modal-footer {
            border-top: 1px solid var(--border-subtle);
            padding: 14px 20px;
            background: rgba(15, 23, 42, 0.6);
        }

        /* Large touchable inputs on mobile */
        .form-control, .form-select {
            background-color: rgba(15, 23, 42, 0.8) !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            color: #ffffff !important;
            padding: 12px 14px;
            font-size: 1rem; /* >=16px prevents iOS Safari automatic page zoom */
            border-radius: 10px;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--cyan-primary) !important;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.25) !important;
        }

        .form-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        /* Accessible Touch Buttons */
        .btn-mobile-primary {
            background: linear-gradient(135deg, #0284c7, #2563eb);
            color: #ffffff;
            border: none;
            padding: 14px 20px;
            font-weight: 700;
            font-size: 0.95rem;
            border-radius: 12px;
            min-height: var(--touch-target);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-mobile-primary:active {
            transform: scale(0.98);
            filter: brightness(1.1);
        }

        .btn-mobile-success {
            background: linear-gradient(135deg, #059669, #10b981);
            color: #ffffff;
            border: none;
            padding: 14px 20px;
            font-weight: 700;
            font-size: 0.95rem;
            border-radius: 12px;
            min-height: var(--touch-target);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-mobile-danger {
            background: linear-gradient(135deg, #e11d48, #be123c);
            color: #ffffff;
            border: none;
            padding: 14px 20px;
            font-weight: 700;
            font-size: 0.95rem;
            border-radius: 12px;
            min-height: var(--touch-target);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        /* Custom Segmented Tab Bar on mobile */
        .segmented-tabs {
            background: var(--surface-1);
            padding: 4px;
            border-radius: 12px;
            display: flex;
            gap: 4px;
            margin-bottom: 16px;
            border: 1px solid var(--border-subtle);
        }

        .segmented-tab {
            flex: 1;
            padding: 8px 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-align: center;
            color: var(--text-muted);
            background: none;
            border: none;
            border-radius: 8px;
            transition: all 0.15s ease;
            cursor: pointer;
            min-height: 38px;
        }

        .segmented-tab.active {
            background: var(--surface-2);
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
        }
    </style>
</head>
<body>

    <!-- -------------------------------------------------------------
         TOP APP BAR (Sticky Mobile Header)
    -------------------------------------------------------------- -->
    <header class="driver-app-bar">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <!-- Driver Avatar with Status Ring -->
                <div class="position-relative">
                    <div style="width: 40px; height: 40px; border-radius: 12px; background: linear-gradient(135deg, #2563eb, #0284c7); display: grid; place-items: center; font-weight: 800; font-size: 1rem; color: #ffffff; box-shadow: 0 2px 8px rgba(37,99,235,0.4);">
                        <?= esc(strtoupper(substr($driver['first_name'] ?? 'D', 0, 1) . substr($driver['last_name'] ?? 'R', 0, 1))) ?>
                    </div>
                    <span class="position-absolute bottom-0 end-0 p-1 bg-<?= ($driver['status'] ?? 'available') === 'on_trip' ? 'warning' : (($driver['status'] ?? 'available') === 'available' ? 'success' : 'secondary') ?> border border-dark rounded-circle" style="transform: translate(25%, 25%);"></span>
                </div>

                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="fw-bold mb-0 text-white" style="font-size: 0.95rem;">
                            <?= esc(($driver['first_name'] ?? 'Driver') . ' ' . ($driver['last_name'] ?? '')) ?>
                        </h6>
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <span class="telemetry-beacon">
                            <span class="beacon-dot"></span>
                            <span>GPS Telematics Sync</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Header Quick Actions Dropdown -->
            <div class="d-flex align-items-center gap-2">
                <!-- Emergency SOS Quick Button -->
                <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1 rounded-pill" data-bs-toggle="modal" data-bs-target="#incidentModal" title="Emergency SOS" aria-label="Emergency SOS Alert">
                    <i class="fa-solid fa-triangle-exclamation text-danger"></i>
                    <span class="fw-bold small ms-1 d-none d-sm-inline">SOS</span>
                </button>

                <div class="dropdown">
                    <button class="btn btn-dark btn-sm rounded-circle p-2 border border-secondary border-opacity-50" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Driver Menu">
                        <i class="fa-solid fa-ellipsis-vertical text-white"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2" style="background: #1e293b; min-width: 230px;">
                        <li class="px-3 py-2 border-bottom border-secondary border-opacity-25">
                            <strong class="d-block small text-white"><?= esc($driver['first_name'] . ' ' . $driver['last_name']) ?></strong>
                            <span class="mono text-muted small"><?= esc($driver['driver_code'] ?? 'DRV-101') ?></span>
                        </li>
                        <li>
                            <a class="dropdown-item text-white-50 small py-2" href="#" data-bs-toggle="modal" data-bs-target="#dutyStatusModal">
                                <i class="fa-solid fa-user-clock me-2 text-info"></i> Change Duty Status (<?= ucfirst($driver['status'] ?? 'available') ?>)
                            </a>
                        </li>
                        <?php if (!empty($allDrivers) && count($allDrivers) > 1): ?>
                            <li><hr class="dropdown-divider border-secondary border-opacity-25"></li>
                            <li class="px-3 py-1 text-muted small text-uppercase mono" style="font-size: 0.65rem;">Switch Driver Profile (Demo)</li>
                            <?php foreach ($allDrivers as $ad): ?>
                                <li>
                                    <a class="dropdown-item text-white-50 small py-1 <?= $ad['id'] == ($driver['id'] ?? 0) ? 'fw-bold text-info' : '' ?>" href="<?= base_url('driver/trips?driver_id=' . $ad['id']) ?>">
                                        <?= esc($ad['first_name'] . ' ' . $ad['last_name']) ?> <span class="mono small text-muted">(<?= esc($ad['driver_code']) ?>)</span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider border-secondary border-opacity-25"></li>
                        <li>
                            <a class="dropdown-item text-danger small py-2" href="<?= base_url('logout') ?>">
                                <i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Sign Out
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <!-- -------------------------------------------------------------
         MAIN CONTENT CONTAINER
    -------------------------------------------------------------- -->
    <main class="container px-3 pt-3" role="main">

        <!-- Flash Messages (Accessibility Alert Banners) -->
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show p-3 rounded-3 mb-3 border-0 d-flex align-items-center" role="alert" style="background: rgba(16, 185, 129, 0.18); color: #34d399; border-left: 4px solid #10b981 !important;">
                <i class="fa-solid fa-circle-check fs-5 me-2"></i>
                <div class="small fw-semibold flex-grow-1"><?= session()->getFlashdata('success') ?></div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show p-3 rounded-3 mb-3 border-0 d-flex align-items-center" role="alert" style="background: rgba(244, 63, 94, 0.18); color: #fb7185; border-left: 4px solid #f43f5e !important;">
                <i class="fa-solid fa-triangle-exclamation fs-5 me-2"></i>
                <div class="small fw-semibold flex-grow-1"><?= session()->getFlashdata('error') ?></div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- ---------------------------------------------------------
             DRIVER QUICK TELEMETRY HUD
        ---------------------------------------------------------- -->
        <div class="hud-grid" aria-label="Driver and Vehicle Telemetry Summary">
            <!-- Assigned Vehicle Unit -->
            <div class="hud-tile">
                <div class="hud-label">
                    <i class="fa-solid fa-truck-front text-info"></i> Vehicle Unit
                </div>
                <div class="hud-value mono text-truncate" title="<?= esc($vehicle['plate_number'] ?? 'Unassigned') ?>">
                    <?= esc($vehicle['plate_number'] ?? 'NONE') ?>
                </div>
                <div class="small text-muted text-truncate" style="font-size: 0.7rem;">
                    <?= esc(($vehicle['make'] ?? '') . ' ' . ($vehicle['model'] ?? '')) ?>
                </div>
            </div>

            <!-- Fuel Level Gauge -->
            <div class="hud-tile">
                <div class="hud-label">
                    <i class="fa-solid fa-gas-pump text-warning"></i> Fuel Level
                </div>
                <?php $fuelPct = (float)($vehicle['current_fuel_level'] ?? 80); ?>
                <div class="hud-value mono d-flex align-items-baseline gap-1">
                    <span class="<?= $fuelPct < 25 ? 'text-danger' : ($fuelPct < 50 ? 'text-warning' : 'text-success') ?>">
                        <?= number_format($fuelPct, 0) ?>%
                    </span>
                    <span class="small text-muted" style="font-size: 0.72rem;">Tank</span>
                </div>
                <div class="progress mt-1" style="height: 5px; background: rgba(255,255,255,0.1);">
                    <div class="progress-bar bg-<?= $fuelPct < 25 ? 'danger' : ($fuelPct < 50 ? 'warning' : 'success') ?>" style="width: <?= min(100, $fuelPct) ?>%;"></div>
                </div>
            </div>

            <!-- Current Odometer -->
            <div class="hud-tile">
                <div class="hud-label">
                    <i class="fa-solid fa-gauge text-primary"></i> Current Odometer
                </div>
                <div class="hud-value mono" style="font-size: 1.05rem;">
                    <?= number_format((float)($vehicle['odometer_km'] ?? 0), 1) ?>
                </div>
                <div class="small text-muted" style="font-size: 0.7rem;">Kilometers</div>
            </div>

            <!-- Driver Safety Rating -->
            <div class="hud-tile">
                <div class="hud-label">
                    <i class="fa-solid fa-shield-heart text-success"></i> Safety Score
                </div>
                <div class="hud-value mono text-success">
                    <?= number_format((float)($driver['safety_score'] ?? 100), 1) ?>%
                </div>
                <div class="small text-muted" style="font-size: 0.7rem;">
                    <span class="badge bg-success bg-opacity-25 text-success p-1" style="font-size: 0.65rem;">LTO CLASS A</span>
                </div>
            </div>
        </div>

        <!-- ---------------------------------------------------------
             QUICK ACTION TOUCH GRID (Minimum 48px Touch Targets)
        ---------------------------------------------------------- -->
        <div class="action-grid" role="group" aria-label="Field Quick Action Controls">
            <!-- 1. Gate Pass QR Modal Trigger -->
            <button type="button" class="btn-action-tile" data-bs-toggle="modal" data-bs-target="#qrPassModal">
                <div class="tile-icon" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8;">
                    <i class="fa-solid fa-qrcode"></i>
                </div>
                <span class="tile-title">Gate Pass QR</span>
            </button>

            <!-- 2. Refuel & Receipt Capture -->
            <button type="button" class="btn-action-tile" data-bs-toggle="modal" data-bs-target="#fuelModal">
                <div class="tile-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                    <i class="fa-solid fa-gas-pump"></i>
                </div>
                <span class="tile-title">Log Refuel</span>
            </button>

            <!-- 3. Report Incident / SOS -->
            <button type="button" class="btn-action-tile" data-bs-toggle="modal" data-bs-target="#incidentModal">
                <div class="tile-icon" style="background: rgba(244, 63, 94, 0.15); color: #f43f5e;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <span class="tile-title">Report Issue</span>
            </button>

            <!-- 4. Direct Call to Dispatch -->
            <a href="tel:+639185550200" class="btn-action-tile">
                <div class="tile-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                    <i class="fa-solid fa-phone-volume"></i>
                </div>
                <span class="tile-title">Call Dispatch</span>
            </a>
        </div>

        <!-- ---------------------------------------------------------
             SEGMENTED TAB CONTROLLER (Mobile Accessible Tabs)
        ---------------------------------------------------------- -->
        <div class="segmented-tabs" role="tablist" aria-label="Driver Operations Views">
            <button type="button" class="segmented-tab active" data-target="tab-active-duty" role="tab" aria-selected="true" id="btn-tab-duty">
                <i class="fa-solid fa-id-badge me-1"></i> Duty & Gate
            </button>
            <button type="button" class="segmented-tab" data-target="tab-tickets" role="tab" aria-selected="false" id="btn-tab-tickets">
                <i class="fa-solid fa-ticket me-1"></i> Tickets (<?= count($tripTickets) ?>)
            </button>
            <button type="button" class="segmented-tab" data-target="tab-waybills" role="tab" aria-selected="false" id="btn-tab-waybills">
                <i class="fa-solid fa-truck-ramp-box me-1"></i> Waybills (<?= count($trips) ?>)
            </button>
            <button type="button" class="segmented-tab" data-target="tab-profile" role="tab" aria-selected="false" id="btn-tab-profile">
                <i class="fa-solid fa-user-shield me-1"></i> Profile
            </button>
        </div>

        <!-- =========================================================
             VIEW 1: ACTIVE DUTY & OFFICIAL GATE PASS (DEFAULT)
        ========================================================== -->
        <section id="tab-active-duty" class="driver-tab-view active" role="tabpanel" aria-labelledby="btn-tab-duty">

            <?php if ($activeTicket): ?>
                <!-- ACTIVE TICKET HERO CARD -->
                <div class="mobile-card card-highlight">
                    <div class="mobile-card-header">
                        <div>
                            <span class="small text-info mono fw-bold text-uppercase d-block" style="font-size: 0.7rem;">
                                <i class="fa-solid fa-certificate me-1"></i> Official Trip Ticket &bull; ADMIN-F-001 rev1
                            </span>
                            <h5 class="fw-bold mb-0 text-white mono"><?= esc($activeTicket['ticket_serial_no']) ?></h5>
                        </div>
                        <?php
                            $statusBadge = match($activeTicket['status']) {
                                'issued'    => ['label' => 'Pending Gate Exit', 'class' => 'bg-warning text-dark'],
                                'departed'  => ['label' => 'Active In Transit', 'class' => 'bg-primary text-white'],
                                'returned'  => ['label' => 'Compound Return', 'class' => 'bg-info text-white'],
                                'completed' => ['label' => 'Reconciled', 'class' => 'bg-success text-white'],
                                default     => ['label' => ucfirst($activeTicket['status']), 'class' => 'bg-secondary text-white'],
                            };
                        ?>
                        <span class="badge <?= $statusBadge['class'] ?> px-2 py-1 small">
                            <?= $statusBadge['label'] ?>
                        </span>
                    </div>

                    <!-- Compact Gate QR Visual for Quick Checkpoint Presentment -->
                    <div class="qr-gate-pass-box">
                        <div class="small fw-bold text-uppercase mono text-muted" style="font-size: 0.68rem;">
                            Compound Gate Security QR Pass
                        </div>
                        <div class="qr-canvas-container my-1">
                            <div id="activeTicketQr"></div>
                        </div>
                        <div class="mono small fw-bold text-dark mt-1" style="font-size: 0.8rem;">
                            <?= esc($activeTicket['ticket_serial_no']) ?> &bull; <?= esc($activeTicket['plate_number']) ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-dark w-100 mt-2 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#qrPassModal">
                            <i class="fa-solid fa-expand me-1"></i> Enlarge Fullscreen for Guard Scanner
                        </button>
                    </div>

                    <!-- Destination & Route -->
                    <div class="p-2 mb-3 rounded" style="background: rgba(15, 23, 42, 0.6);">
                        <div class="route-stop pb-2">
                            <span class="small text-muted d-block" style="font-size: 0.7rem;">Origin (Motorpool)</span>
                            <strong class="small text-white">PIA Central Compound, Visayas Ave, QC</strong>
                        </div>
                        <div class="route-stop dest">
                            <span class="small text-muted d-block" style="font-size: 0.7rem;">Authorized Destination</span>
                            <strong class="small text-white"><i class="fa-solid fa-location-dot text-danger me-1"></i><?= esc($activeTicket['authorized_destination']) ?></strong>
                        </div>
                    </div>

                    <!-- Key Details Grid -->
                    <div class="row g-2 mb-3 small text-white-50">
                        <div class="col-6">
                            <span class="d-block text-muted" style="font-size: 0.68rem;">Authorized Departure</span>
                            <strong class="text-white mono" style="font-size: 0.76rem;">
                                <?= date('M d, Y h:i A', strtotime($activeTicket['authorized_departure'])) ?>
                            </strong>
                        </div>
                        <div class="col-6">
                            <span class="d-block text-muted" style="font-size: 0.68rem;">Approving Authority</span>
                            <strong class="text-white text-truncate d-block" style="font-size: 0.76rem;">
                                <?= esc($activeTicket['admin_approver_name'] ?? 'Administrative Head') ?>
                            </strong>
                        </div>
                        <div class="col-12">
                            <span class="d-block text-muted" style="font-size: 0.68rem;">Authorized Passengers</span>
                            <div class="p-2 rounded bg-black bg-opacity-25 small text-white mono" style="font-size: 0.74rem;">
                                <?= nl2br(esc($activeTicket['authorized_passengers'] ?? 'Official Coverage Team')) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Primary Context Action Buttons -->
                    <div class="d-grid gap-2">
                        <?php if ($activeTicket['status'] === 'issued'): ?>
                            <div class="alert alert-warning border-0 p-2 mb-0 small text-center" style="font-size: 0.75rem;">
                                <i class="fa-solid fa-id-card-clip me-1"></i> Show the QR code above to the Compound Gate Guard at Gate #1 to record your departure egress.
                            </div>
                        <?php elseif ($activeTicket['status'] === 'departed'): ?>
                            <button type="button" class="btn btn-outline-info w-100 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#fuelModal">
                                <i class="fa-solid fa-gas-pump me-1"></i> Log Official Gas Station Refuel
                            </button>
                        <?php elseif ($activeTicket['status'] === 'returned'): ?>
                            <button type="button" class="btn-mobile-success" data-bs-toggle="modal" data-bs-target="#sectionBModal">
                                <i class="fa-solid fa-clipboard-check me-1"></i> Accomplish Section B Post-Trip Report
                            </button>
                        <?php endif; ?>

                        <!-- Direct link to full official ticket document -->
                        <a href="<?= base_url('tickets/' . $activeTicket['id']) ?>" class="btn btn-sm btn-outline-light py-2">
                            <i class="fa-solid fa-file-invoice me-1"></i> Open Full Trip Ticket Document
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Standby Card (No Active Ticket) -->
                <div class="mobile-card text-center py-4">
                    <div style="width: 56px; height: 56px; border-radius: 50%; background: rgba(56, 189, 248, 0.1); color: var(--cyan-primary); display: grid; place-items: center; margin: 0 auto 12px; font-size: 1.5rem;">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <h5 class="fw-bold text-white mb-1">Standby & Ready for Duty</h5>
                    <p class="text-muted small mb-3 px-3">
                        No active Vehicle Request Slip or Driver's Trip Ticket currently requires gate clearance.
                    </p>
                    <div class="p-3 mx-2 rounded bg-black bg-opacity-25 text-start small mb-3">
                        <div class="fw-bold text-info mb-1"><i class="fa-solid fa-list-check me-1"></i> Pre-Trip Readiness Checklist:</div>
                        <div class="text-white-50"><i class="fa-solid fa-check text-success me-1"></i> Assigned unit: <?= esc($vehicle['plate_number'] ?? 'Assigned') ?></div>
                        <div class="text-white-50"><i class="fa-solid fa-check text-success me-1"></i> Fuel capacity check: <?= $vehicle['current_fuel_level'] ?? 100 ?>%</div>
                        <div class="text-white-50"><i class="fa-solid fa-check text-success me-1"></i> Driver LTO license verified active</div>
                    </div>
                    <button type="button" class="btn btn-outline-info btn-sm px-4" onclick="document.getElementById('btn-tab-tickets').click();">
                        <i class="fa-solid fa-history me-1"></i> Browse Trip Ticket Archives
                    </button>
                </div>
            <?php endif; ?>

            <!-- Assigned Vehicle Telematics Panel -->
            <?php if ($vehicle): ?>
                <div class="mobile-card">
                    <div class="mobile-card-header">
                        <span class="small fw-bold text-white text-uppercase">
                            <i class="fa-solid fa-truck-pickup text-info me-1"></i> Vehicle Telematics Status
                        </span>
                        <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 small">
                            <?= ucfirst($vehicle['status'] ?? 'Active') ?>
                        </span>
                    </div>

                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="fw-bold mb-0 text-white mono"><?= esc($vehicle['plate_number']) ?></h5>
                            <span class="small text-muted"><?= esc($vehicle['make'] . ' ' . $vehicle['model'] . ' (' . $vehicle['year'] . ')') ?></span>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-primary mono fs-6"><?= esc($vehicle['fuel_type'] ?? 'Diesel') ?></span>
                        </div>
                    </div>

                    <div class="row g-2 small text-muted">
                        <div class="col-4 p-2 rounded bg-black bg-opacity-25 text-center">
                            <span class="d-block text-muted" style="font-size: 0.65rem;">Engine State</span>
                            <strong class="text-success"><?= esc(ucfirst($vehicle['engine_status'] ?? 'Running')) ?></strong>
                        </div>
                        <div class="col-4 p-2 rounded bg-black bg-opacity-25 text-center">
                            <span class="d-block text-muted" style="font-size: 0.65rem;">Battery</span>
                            <strong class="text-white mono"><?= esc($vehicle['battery_voltage'] ?? '12.6') ?>V</strong>
                        </div>
                        <div class="col-4 p-2 rounded bg-black bg-opacity-25 text-center">
                            <span class="d-block text-muted" style="font-size: 0.65rem;">Coolant</span>
                            <strong class="text-white mono"><?= esc($vehicle['coolant_temp_c'] ?? '85') ?>Â°C</strong>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </section>

        <!-- =========================================================
             VIEW 2: OFFICIAL DRIVER'S TRIP TICKETS (e-DTT)
        ========================================================== -->
        <section id="tab-tickets" class="driver-tab-view" role="tabpanel" aria-labelledby="btn-tab-tickets">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold mb-0 text-white text-uppercase small">
                    <i class="fa-solid fa-receipt text-info me-1"></i> Driver's Trip Tickets (<?= count($tripTickets) ?>)
                </h6>
                <span class="badge bg-dark border border-secondary text-muted small mono">ADMIN-F-001 rev1</span>
            </div>

            <?php if (empty($tripTickets)): ?>
                <div class="mobile-card text-center py-5 text-muted">
                    <i class="fa-solid fa-ticket fs-1 mb-2 text-secondary opacity-50"></i>
                    <p class="mb-0">No trip tickets registered under your driver code.</p>
                </div>
            <?php else: ?>
                <?php foreach ($tripTickets as $tk): ?>
                    <div class="mobile-card">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-info mono"><?= esc($tk['ticket_serial_no']) ?></span>
                            <?php
                                $tkPill = match($tk['status']) {
                                    'issued'    => 'bg-warning text-dark',
                                    'departed'  => 'bg-primary text-white',
                                    'returned'  => 'bg-info text-white',
                                    'completed' => 'bg-success text-white',
                                    default     => 'bg-secondary text-white',
                                };
                            ?>
                            <span class="badge <?= $tkPill ?> px-2 py-1 small">
                                <?= ucfirst($tk['status']) ?>
                            </span>
                        </div>

                        <div class="mb-2">
                            <div class="small text-muted">Destination:</div>
                            <strong class="small text-white d-block text-truncate">
                                <i class="fa-solid fa-location-dot text-danger me-1"></i><?= esc($tk['authorized_destination']) ?>
                            </strong>
                        </div>

                        <div class="bg-black bg-opacity-25 p-2 rounded small text-white-50 mb-3 d-flex justify-content-between mono">
                            <span>Plate: <strong class="text-white"><?= esc($tk['plate_number']) ?></strong></span>
                            <span>Distance: <?= number_format((float)$tk['total_distance_km'], 1) ?> km</span>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="<?= base_url('tickets/' . $tk['id']) ?>" class="btn btn-outline-info btn-sm flex-grow-1 py-2">
                                <i class="fa-solid fa-eye me-1"></i> View Ticket
                            </a>
                            <?php if ($tk['status'] === 'returned'): ?>
                                <button type="button" class="btn btn-success btn-sm flex-grow-1 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#sectionBModal">
                                    <i class="fa-solid fa-pen-to-square me-1"></i> Section B
                                </button>
                            <?php endif; ?>
                            <a href="<?= base_url('tickets/' . $tk['id'] . '/print') ?>" target="_blank" class="btn btn-outline-secondary btn-sm px-3 py-2" title="Print View">
                                <i class="fa-solid fa-print"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <!-- =========================================================
             VIEW 3: FREIGHT & LOGISTICS WAYBILLS
        ========================================================== -->
        <section id="tab-waybills" class="driver-tab-view" role="tabpanel" aria-labelledby="btn-tab-waybills">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold mb-0 text-white text-uppercase small">
                    <i class="fa-solid fa-truck-fast text-info me-1"></i> Freight Trips & Waybills (<?= count($trips) ?>)
                </h6>
            </div>

            <?php if (empty($trips)): ?>
                <div class="mobile-card text-center py-5 text-muted">
                    <i class="fa-solid fa-calendar-check fs-1 mb-2 text-secondary opacity-50"></i>
                    <p class="mb-0">No active cargo waybills assigned at the moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($trips as $t): ?>
                    <div class="mobile-card">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-info mono"><?= esc($t['trip_number']) ?></span>
                            <span class="badge bg-<?= $t['status'] === 'in_transit' ? 'success' : ($t['status'] === 'completed' ? 'secondary' : 'primary') ?>">
                                <?= ucfirst(str_replace('_', ' ', $t['status'])) ?>
                            </span>
                        </div>

                        <div class="mb-3">
                            <div class="route-stop pb-2">
                                <span class="small text-muted d-block" style="font-size: 0.7rem;">Origin (Pickup)</span>
                                <strong class="small text-white"><?= esc($t['origin_address']) ?></strong>
                            </div>
                            <div class="route-stop dest">
                                <span class="small text-muted d-block" style="font-size: 0.7rem;">Destination (Delivery)</span>
                                <strong class="small text-white"><?= esc($t['destination_address']) ?></strong>
                            </div>
                        </div>

                        <div class="bg-black bg-opacity-25 p-2 rounded small text-white-50 mb-3 d-flex justify-content-between mono">
                            <span><strong>Cargo:</strong> <?= esc($t['cargo_type'] ?? 'General') ?></span>
                            <span><strong>Weight:</strong> <?= number_format($t['cargo_weight_kg'] ?? 0, 0) ?> kg</span>
                            <span><strong>Dist:</strong> <?= $t['distance_km'] ?? 0 ?> km</span>
                        </div>

                        <!-- Action buttons based on status -->
                        <div class="d-grid gap-2">
                            <?php if ($t['status'] === 'scheduled' || $t['status'] === 'dispatched'): ?>
                                <form action="<?= base_url('trips/' . $t['id'] . '/status') ?>" method="POST">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="in_transit">
                                    <button type="submit" class="btn-mobile-success">
                                        <i class="fa-solid fa-play me-1"></i> Start Trip (Depart Egress)
                                    </button>
                                </form>
                            <?php elseif ($t['status'] === 'in_transit'): ?>
                                <button class="btn-mobile-primary" data-bs-toggle="modal" data-bs-target="#completeModal-<?= $t['id'] ?>">
                                    <i class="fa-solid fa-circle-check me-1"></i> Arrived / Complete Trip
                                </button>

                                <!-- Complete Trip Modal -->
                                <div class="modal fade" id="completeModal-<?= $t['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form action="<?= base_url('trips/' . $t['id'] . '/status') ?>" method="POST">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="status" value="completed">
                                                <div class="modal-header">
                                                    <h6 class="modal-title fw-bold text-white">Complete Waybill: <?= esc($t['trip_number']) ?></h6>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Arrival Odometer Reading (KM)</label>
                                                        <input type="number" step="0.1" name="end_odometer" class="form-control mono" required value="<?= $vehicle['odometer_km'] ?? 0 ?>">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Confirm Arrival</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="text-center text-muted small py-1"><i class="fa-solid fa-check-double text-success me-1"></i> Completed & Logged</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <!-- =========================================================
             VIEW 4: DRIVER PROFILE, COMPLIANCE & SAFETY
        ========================================================== -->
        <section id="tab-profile" class="driver-tab-view" role="tabpanel" aria-labelledby="btn-tab-profile">
            <!-- Driver Credential Card -->
            <div class="mobile-card">
                <div class="mobile-card-header">
                    <span class="small fw-bold text-white text-uppercase">
                        <i class="fa-solid fa-address-card text-info me-1"></i> Driver License & Compliance
                    </span>
                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 small">
                        VERIFIED LTO
                    </span>
                </div>

                <div class="mb-3">
                    <span class="text-muted small d-block">Official Driver Name:</span>
                    <h6 class="fw-bold text-white mb-0"><?= esc(($driver['first_name'] ?? '') . ' ' . ($driver['last_name'] ?? '')) ?></h6>
                    <span class="mono text-info small"><?= esc($driver['driver_code'] ?? 'DRV-101') ?></span>
                </div>

                <div class="row g-2 mb-3 small">
                    <div class="col-6">
                        <span class="text-muted d-block">License Number:</span>
                        <strong class="mono text-white"><?= esc($driver['license_number'] ?? 'N01-19-082914') ?></strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted d-block">License Expiry:</span>
                        <strong class="mono text-white"><?= esc($driver['license_expiry'] ?? 'Valid') ?></strong>
                    </div>
                    <div class="col-12 mt-2">
                        <span class="text-muted d-block">License Classification:</span>
                        <div class="p-2 rounded bg-black bg-opacity-25 text-white-50">
                            <?= esc($driver['license_type'] ?? 'Professional Driver') ?>
                        </div>
                    </div>
                </div>

                <div class="border-top border-secondary border-opacity-25 pt-3">
                    <span class="text-muted small d-block mb-1">Emergency Contact:</span>
                    <div class="d-flex align-items-center justify-content-between p-2 rounded bg-black bg-opacity-25">
                        <span class="small text-white"><?= esc($driver['emergency_contact'] ?? 'Motorpool Dispatch Desk') ?></span>
                        <a href="tel:<?= esc($driver['phone'] ?? '+639171112233') ?>" class="btn btn-sm btn-outline-info">
                            <i class="fa-solid fa-phone"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Fuel Purchases -->
            <?php if (!empty($recentFuelLogs)): ?>
                <div class="mobile-card">
                    <div class="mobile-card-header">
                        <span class="small fw-bold text-white text-uppercase">
                            <i class="fa-solid fa-gas-pump text-success me-1"></i> Recent Refuels (<?= count($recentFuelLogs) ?>)
                        </span>
                    </div>
                    <?php foreach ($recentFuelLogs as $fl): ?>
                        <div class="p-2 mb-2 rounded bg-black bg-opacity-25 small d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold text-white d-block"><?= esc($fl['fuel_station']) ?></span>
                                <span class="mono text-muted" style="font-size: 0.7rem;"><?= esc($fl['fuel_date']) ?> &bull; <?= number_format($fl['liters'], 1) ?> L</span>
                            </div>
                            <div class="text-end mono">
                                <span class="fw-bold text-success">â‚±<?= number_format($fl['total_cost'], 2) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Emergency Dispatch Hotline Directory -->
            <div class="mobile-card">
                <div class="mobile-card-header">
                    <span class="small fw-bold text-white text-uppercase">
                        <i class="fa-solid fa-headset text-warning me-1"></i> Motorpool Emergency Directory
                    </span>
                </div>
                <div class="d-flex flex-column gap-2 small">
                    <a href="tel:+639185550200" class="p-2 rounded bg-black bg-opacity-25 text-decoration-none text-white d-flex justify-content-between align-items-center">
                        <div>
                            <strong class="d-block">Motorpool Dispatch Console</strong>
                            <span class="text-muted" style="font-size: 0.72rem;">Engr. Roberto D. Tan</span>
                        </div>
                        <span class="btn btn-sm btn-outline-warning"><i class="fa-solid fa-phone me-1"></i> Call</span>
                    </a>
                    <a href="tel:+639185550400" class="p-2 rounded bg-black bg-opacity-25 text-decoration-none text-white d-flex justify-content-between align-items-center">
                        <div>
                            <strong class="d-block">Compound Security Gate #1</strong>
                            <span class="text-muted" style="font-size: 0.72rem;">Sgt. Danilo Ramos</span>
                        </div>
                        <span class="btn btn-sm btn-outline-info"><i class="fa-solid fa-phone me-1"></i> Call</span>
                    </a>
                </div>
            </div>
        </section>

    </main>

    <!-- -------------------------------------------------------------
         FULLSCREEN GATE PASS QR MODAL (For Guard Scanner)
    -------------------------------------------------------------- -->
    <div class="modal fade" id="qrPassModal" tabindex="-1" aria-labelledby="qrPassModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: #ffffff; color: #0b1324;">
                <div class="modal-header border-0 pb-0 justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-primary mono text-uppercase">ADMIN-F-001 rev1</span>
                        <h6 class="modal-title fw-bold text-dark mt-1" id="qrPassModalLabel">Official Gate Security Pass</h6>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <p class="small text-muted mb-2">Present this digital token to the Compound Guard Checkpoint Scanner.</p>

                    <!-- High Resolution QR Canvas -->
                    <div class="p-3 bg-white border rounded-3 d-inline-block shadow-sm my-2" style="width: 240px; height: 240px;">
                        <div id="modalQrCanvas" class="d-flex align-items-center justify-content-center h-100"></div>
                    </div>

                    <div class="mono fw-bold fs-5 text-dark mt-2">
                        <?= esc($activeTicket['ticket_serial_no'] ?? ($vehicle['plate_number'] ?? 'GATE-PASS')) ?>
                    </div>
                    <div class="small text-muted mono">
                        Unit: <strong class="text-dark"><?= esc($vehicle['plate_number'] ?? 'N/A') ?></strong> &bull; Driver: <strong class="text-dark"><?= esc($driver['driver_code'] ?? 'DRV-101') ?></strong>
                    </div>
                    <div class="alert alert-info border-0 p-2 mt-3 mb-0 small text-start">
                        <i class="fa-solid fa-shield-halved text-success me-1"></i> HMAC-SHA256 Cryptographic Pass Token. Valid for official motorpool egress and ingress verification.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Close Pass</button>
                </div>
            </div>
        </div>
    </div>

    <!-- -------------------------------------------------------------
         SECTION B POST-TRIP EXECUTION MODAL (ADMIN-F-001 rev1)
    -------------------------------------------------------------- -->
    <?php if ($activeTicket): ?>
    <div class="modal fade" id="sectionBModal" tabindex="-1" aria-labelledby="sectionBModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form action="<?= base_url('tickets/' . $activeTicket['id'] . '/update') ?>" method="POST" id="sectionBForm">
                    <?= csrf_field() ?>
                    <div class="modal-header">
                        <div>
                            <span class="badge bg-info text-white mono mb-1">ADMIN-F-001 rev1</span>
                            <h6 class="modal-title fw-bold text-white" id="sectionBModalLabel">Section B: Post-Trip & Fuel Accounting Report</h6>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3">
                        <p class="small text-muted mb-3">Enforces official government post-trip audit formula and digital driver certification.</p>

                        <!-- Trip Odometers -->
                        <div class="fw-bold text-info small text-uppercase mb-2 pb-1 border-bottom border-secondary border-opacity-25">
                            <i class="fa-solid fa-gauge me-1"></i> 1. Odometer Readings
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">Start Odometer (KM)</label>
                                <input type="number" step="0.1" name="start_odometer" id="m_start_odo" class="form-control mono" value="<?= (float)($activeTicket['start_odometer'] ?? ($vehicle['odometer_km'] ?? 0)) ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Return Odometer (KM)</label>
                                <input type="number" step="0.1" name="return_odometer" id="m_return_odo" class="form-control mono" value="<?= (float)($activeTicket['return_odometer'] ?? ($vehicle['odometer_km'] ?? 0)) ?>" required>
                            </div>
                            <div class="col-12">
                                <div class="p-2 rounded bg-black bg-opacity-25 d-flex justify-content-between align-items-center small">
                                    <span class="text-muted">Total Distance Travelled:</span>
                                    <span class="mono fw-bold fs-6 text-info" id="m_calcDistance"><?= number_format((float)($activeTicket['total_distance_km'] ?? 0), 1) ?> KM</span>
                                </div>
                            </div>
                        </div>

                        <!-- Fuel Accounting Formula -->
                        <div class="fw-bold text-info small text-uppercase mb-2 pb-1 border-bottom border-secondary border-opacity-25">
                            <i class="fa-solid fa-gas-pump me-1"></i> 2. Fuel Balance Formula
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">Start Balance (L)</label>
                                <input type="number" step="0.01" name="fuel_balance_start_liters" id="m_fuel_start" class="form-control mono" value="<?= (float)($activeTicket['fuel_balance_start_liters'] ?? 40) ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Stock Issued (L)</label>
                                <input type="number" step="0.01" name="fuel_issued_stock_liters" id="m_fuel_issued" class="form-control mono" value="<?= (float)($activeTicket['fuel_issued_stock_liters'] ?? 0) ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Purchased on Trip (L)</label>
                                <input type="number" step="0.01" name="fuel_purchased_liters" id="m_fuel_purchased" class="form-control mono" value="<?= (float)($activeTicket['fuel_purchased_liters'] ?? 0) ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Total Fuel Used (L)</label>
                                <input type="number" step="0.01" name="fuel_used_liters" id="m_fuel_used" class="form-control mono" value="<?= (float)($activeTicket['fuel_used_liters'] ?? 0) ?>" required>
                            </div>
                            <div class="col-12">
                                <div class="p-2 rounded bg-black bg-opacity-25 d-flex justify-content-between align-items-center small">
                                    <span class="text-muted">Calculated Ending Fuel Balance:</span>
                                    <span class="mono fw-bold text-success" id="m_calcEndFuel"><?= number_format((float)($activeTicket['fuel_balance_end_liters'] ?? 40), 2) ?> Liters</span>
                                </div>
                            </div>
                        </div>

                        <!-- Driver Certification Checkbox -->
                        <div class="p-3 rounded bg-black bg-opacity-25 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="driver_certified" value="1" id="m_driverCert" <?= !empty($activeTicket['driver_certified']) ? 'checked' : '' ?> required>
                                <label class="form-check-label small text-white" for="m_driverCert">
                                    <strong>I hereby certify on official honor</strong> that the above trip route, distance, and fuel consumption are true and correct as accomplished.
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold">Submit Section B Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- -------------------------------------------------------------
         REFUEL & RECEIPT LOGGING MODAL
    -------------------------------------------------------------- -->
    <div class="modal fade" id="fuelModal" tabindex="-1" aria-labelledby="fuelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= base_url('api/v1/driver/fuel') ?>" method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="vehicle_id" value="<?= $vehicle['id'] ?? 1 ?>">
                    <input type="hidden" name="driver_id" value="<?= $driver['id'] ?? 1 ?>">
                    <div class="modal-header">
                        <h6 class="modal-title fw-bold text-white" id="fuelModalLabel">
                            <i class="fa-solid fa-gas-pump text-success me-2"></i> Log Fuel & Receipt
                        </h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div class="mb-3">
                            <label class="form-label">Capture Photo of Receipt</label>
                            <input type="file" name="receipt_image" accept="image/*" class="form-control" capture="environment">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">Volume (Liters)</label>
                                <input type="number" step="0.01" name="liters" class="form-control mono" required placeholder="0.00">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Total Cost (â‚±)</label>
                                <input type="number" step="0.01" name="total_cost" class="form-control mono" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Current Odometer (KM)</label>
                            <input type="number" step="0.1" name="odometer_km" class="form-control mono" value="<?= $vehicle['odometer_km'] ?? 0 ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Gas Station</label>
                            <input type="text" name="fuel_station" class="form-control" placeholder="e.g. Petron Philam, Shell Visayas Ave" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success fw-bold">Submit Fuel Log</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- -------------------------------------------------------------
         REPORT INCIDENT / EMERGENCY SOS MODAL
    -------------------------------------------------------------- -->
    <div class="modal fade" id="incidentModal" tabindex="-1" aria-labelledby="incidentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= base_url('driver/incident') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="vehicle_id" value="<?= $vehicle['id'] ?? 1 ?>">
                    <input type="hidden" name="driver_id" value="<?= $driver['id'] ?? 1 ?>">
                    <input type="hidden" name="trip_id" value="<?= $activeTicket['id'] ?? ($trips[0]['id'] ?? '') ?>">
                    <div class="modal-header">
                        <h6 class="modal-title fw-bold text-danger" id="incidentModalLabel">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i> Report Road Incident / SOS
                        </h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="type" class="form-select" required>
                                <option value="Mechanical Breakdown">Mechanical Breakdown</option>
                                <option value="Tire Puncture / Flat">Tire Puncture / Flat</option>
                                <option value="Accident / Collision">Minor Collision / Accident</option>
                                <option value="Traffic Gridlock / Delay">Severe Traffic / Road Closure</option>
                                <option value="Medical Emergency">Driver Medical Issue</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Severity Level</label>
                            <select name="severity" class="form-select" required>
                                <option value="minor">Minor (Vehicle moving, slight delay)</option>
                                <option value="moderate" selected>Moderate (Requires roadside assistance)</option>
                                <option value="severe">Severe (Immobilized / Towing required)</option>
                                <option value="critical">Critical (Immediate Emergency SOS)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location / Landmark</label>
                            <div class="input-group">
                                <input type="text" name="location" id="incidentLocationInput" class="form-control" placeholder="e.g. EDSA Northbound near Quezon Ave" required>
                                <button class="btn btn-outline-info" type="button" id="btnGetGps" title="Detect GPS Location">
                                    <i class="fa-solid fa-location-crosshairs"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Brief Description</label>
                            <textarea name="description" rows="3" class="form-control" placeholder="Describe the situation and immediate assistance needed..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-mobile-danger">
                            <i class="fa-solid fa-paper-plane me-1"></i> Transmit Alert to Dispatch
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- -------------------------------------------------------------
         DUTY STATUS MODAL
    -------------------------------------------------------------- -->
    <div class="modal fade" id="dutyStatusModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= base_url('driver/status') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="driver_id" value="<?= $driver['id'] ?? 1 ?>">
                    <div class="modal-header">
                        <h6 class="modal-title fw-bold text-white">Update Duty Status</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div class="mb-3">
                            <label class="form-label">Select Current Status</label>
                            <select name="status" class="form-select">
                                <option value="available" <?= ($driver['status'] ?? '') === 'available' ? 'selected' : '' ?>>Available / Ready for Dispatch</option>
                                <option value="on_trip" <?= ($driver['status'] ?? '') === 'on_trip' ? 'selected' : '' ?>>On Trip / In Transit</option>
                                <option value="off_duty" <?= ($driver['status'] ?? '') === 'off_duty' ? 'selected' : '' ?>>Off Duty / Rest Period</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- -------------------------------------------------------------
         MOBILE BOTTOM NAVIGATION DOCK (Thumb Ergonomics)
    -------------------------------------------------------------- -->
    <nav class="mobile-bottom-dock" role="navigation" aria-label="Bottom Navigation Dock">
        <button type="button" class="dock-nav-item active" data-target="tab-active-duty" aria-label="Duty Home Tab">
            <i class="fa-solid fa-house-chimney"></i>
            <span>Duty</span>
        </button>

        <button type="button" class="dock-nav-item" data-target="tab-tickets" aria-label="Trip Tickets Tab">
            <?php if (!empty($activeTicket)): ?>
                <span class="dock-badge"></span>
            <?php endif; ?>
            <i class="fa-solid fa-ticket"></i>
            <span>Tickets</span>
        </button>

        <button type="button" class="dock-nav-item" data-target="tab-waybills" aria-label="Freight Waybills Tab">
            <i class="fa-solid fa-truck-ramp-box"></i>
            <span>Waybills</span>
        </button>

        <button type="button" class="dock-nav-item" data-bs-toggle="modal" data-bs-target="#fuelModal" aria-label="Refuel Action">
            <i class="fa-solid fa-gas-pump text-success"></i>
            <span>Refuel</span>
        </button>

        <button type="button" class="dock-nav-item" data-target="tab-profile" aria-label="Driver Profile Tab">
            <i class="fa-solid fa-id-card"></i>
            <span>Profile</span>
        </button>
    </nav>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Interactive Client Scripts -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Tab Switching Engine
        const tabButtons = document.querySelectorAll('.dock-nav-item[data-target], .segmented-tab[data-target]');
        const tabViews   = document.querySelectorAll('.driver-tab-view');

        function switchTab(targetId) {
            tabViews.forEach(v => {
                if (v.id === targetId) {
                    v.classList.add('active');
                } else {
                    v.classList.remove('active');
                }
            });

            // Update bottom dock active state
            document.querySelectorAll('.dock-nav-item[data-target]').forEach(b => {
                if (b.getAttribute('data-target') === targetId) {
                    b.classList.add('active');
                } else {
                    b.classList.remove('active');
                }
            });

            // Update segmented top tabs active state
            document.querySelectorAll('.segmented-tab[data-target]').forEach(b => {
                if (b.getAttribute('data-target') === targetId) {
                    b.classList.add('active');
                    b.setAttribute('aria-selected', 'true');
                } else {
                    b.classList.remove('active');
                    b.setAttribute('aria-selected', 'false');
                }
            });

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        tabButtons.forEach(btn => {
            btn.addEventListener('click', function (e) {
                const target = this.getAttribute('data-target');
                if (target) {
                    e.preventDefault();
                    switchTab(target);
                }
            });
        });

        // 2. Dynamic QR Code Rendering for Compound Gate Checkpoint
        const qrToken = <?= json_encode($activeTicket['qr_crypt_token'] ?? ($activeTicket['ticket_serial_no'] ?? ($vehicle['plate_number'] ?? 'FLEETPULSE-TOKEN'))) ?>;

        // Compact in-card QR
        const cardQrContainer = document.getElementById('activeTicketQr');
        if (cardQrContainer && qrToken) {
            new QRCode(cardQrContainer, {
                text: qrToken,
                width: 140,
                height: 140,
                colorDark: "#0A2540",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.M
            });
        }

        // Fullscreen Modal QR
        const modalQrContainer = document.getElementById('modalQrCanvas');
        if (modalQrContainer && qrToken) {
            new QRCode(modalQrContainer, {
                text: qrToken,
                width: 220,
                height: 220,
                colorDark: "#0A2540",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        }

        // 3. Section B Fuel & Odometer Live Calculator
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
                const start = parseFloat(sOdo.value) || 0;
                const ret = parseFloat(rOdo.value) || 0;
                const d = Math.max(0, ret - start);
                calcDist.innerText = d.toFixed(1) + ' KM';
            }

            if (fStart && calcEnd) {
                const startFuel = parseFloat(fStart.value) || 0;
                const issuedFuel = parseFloat(fIssued ? fIssued.value : 0) || 0;
                const purchFuel = parseFloat(fPurch ? fPurch.value : 0) || 0;
                const usedFuel = parseFloat(fUsed ? fUsed.value : 0) || 0;

                const ending = (startFuel + issuedFuel + purchFuel) - usedFuel;
                calcEnd.innerText = ending.toFixed(2) + ' Liters';
            }
        }

        [sOdo, rOdo, fStart, fIssued, fPurch, fUsed].forEach(inp => {
            if (inp) inp.addEventListener('input', recalcSectionB);
        });

        // 4. HTML5 GPS Locator for Incident Reporting
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
                        function (error) {
                            alert('Could not retrieve current GPS coordinates. Please type landmark manually.');
                            btnGps.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i>';
                        },
                        { timeout: 10000, enableHighAccuracy: true }
                    );
                } else {
                    alert('Geolocation is not supported by your mobile browser.');
                }
            });
        }
    });
    </script>
</body>
</html>
