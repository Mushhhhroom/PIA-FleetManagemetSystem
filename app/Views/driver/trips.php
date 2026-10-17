<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="theme-color" content="#ffffff">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="PIA Driver">
    <title><?= esc(($driver['first_name'] ?? 'Driver') . ' ' . ($driver['last_name'] ?? '')) ?> | Driver Mobile</title>

    <!-- Google Fonts: Inter & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 & FontAwesome 6 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <!-- QRCode.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        :root {
            --bg-page: #f8fafc;
            --bg-card: #ffffff;
            --border-subtle: #e2e8f0;
            --border-focus: #cbd5e1;
            
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #94a3b8;

            --brand-primary: #1e3a8a;
            --brand-accent: #2563eb;
            --brand-light: #eff6ff;

            --success: #059669;
            --success-light: #ecfdf5;
            --warning: #d97706;
            --warning-light: #fffbeb;
            --danger: #dc2626;
            --danger-light: #fef2f2;

            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-pill: 9999px;

            --touch-target: 48px;
            --safe-bottom: env(safe-area-inset-bottom, 16px);
            --safe-top: env(safe-area-inset-top, 0px);
        }

        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: var(--bg-page);
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
            padding-bottom: calc(76px + var(--safe-bottom));
            margin: 0;
            -webkit-font-smoothing: antialiased;
            letter-spacing: -0.01em;
        }

        .mono {
            font-family: 'JetBrains Mono', monospace;
            letter-spacing: -0.02em;
        }

        /* -------------------------------------------------------------
           Header & App Bar (Minimalist White/Slate)
        ------------------------------------------------------------- */
        .driver-header {
            background: #ffffff;
            border-bottom: 1px solid var(--border-subtle);
            padding: calc(10px + var(--safe-top)) 16px 10px;
            position: sticky;
            top: 0;
            z-index: 1030;
        }

        .driver-avatar {
            width: 38px;
            height: 38px;
            border-radius: var(--radius-md);
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--brand-primary);
        }

        .duty-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: var(--radius-pill);
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s ease;
        }
        .duty-badge.duty-available {
            background: var(--success-light);
            color: var(--success);
            border-color: #a7f3d0;
        }
        .duty-badge.duty-on_trip {
            background: var(--warning-light);
            color: var(--warning);
            border-color: #fde68a;
        }
        .duty-badge.duty-off_duty {
            background: #f1f5f9;
            color: var(--text-secondary);
            border-color: #e2e8f0;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
        }

        /* -------------------------------------------------------------
           Minimalist Cards & Surfaces
        ------------------------------------------------------------- */
        .driver-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03);
            position: relative;
        }

        .card-active-trip {
            border-color: #93c5fd;
            background: linear-gradient(180deg, #ffffff 0%, #f8faff 100%);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.05);
        }

        .driver-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f1f5f9;
        }

        .card-title-sm {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Metrics Strip */
        .metric-strip {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-bottom: 12px;
        }
        .metric-cell {
            background: #f8fafc;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 10px 8px;
            text-align: center;
        }
        .metric-label {
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 2px;
        }
        .metric-val {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.2;
        }

        /* -------------------------------------------------------------
           Route Origin-Destination Minimal Flow
        ------------------------------------------------------------- */
        .route-flow {
            position: relative;
            padding-left: 24px;
            margin: 8px 0 12px;
        }
        .route-flow::before {
            content: '';
            position: absolute;
            left: 7px;
            top: 6px;
            bottom: 6px;
            width: 2px;
            background: #e2e8f0;
        }
        .route-point {
            position: relative;
            margin-bottom: 12px;
        }
        .route-point:last-child {
            margin-bottom: 0;
        }
        .route-point::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 4px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #ffffff;
            border: 3px solid #94a3b8;
        }
        .route-point.active::before {
            border-color: var(--brand-accent);
            background: var(--brand-accent);
        }
        .route-point.destination::before {
            border-color: var(--danger);
            background: var(--danger);
        }

        /* -------------------------------------------------------------
           QR Gate Pass (Ultra-Readable Scanner Optimized)
        ------------------------------------------------------------- */
        .qr-pass-container {
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 14px;
            text-align: center;
            margin: 12px 0;
        }
        .qr-code-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            padding: 10px;
            border-radius: var(--radius-sm);
            border: 1px solid #f1f5f9;
        }
        .qr-code-box img,
        .qr-code-box canvas {
            display: block;
            margin: 0 auto;
        }

        /* -------------------------------------------------------------
           Minimalist Quick Action Row
        ------------------------------------------------------------- */
        .quick-actions-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 14px;
        }
        .quick-btn {
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 10px 4px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 0.68rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-decoration: none;
            cursor: pointer;
            min-height: var(--touch-target);
            transition: all 0.15s ease;
        }
        .quick-btn:active {
            background: #f1f5f9;
            transform: scale(0.98);
        }
        .quick-btn-icon {
            font-size: 1.1rem;
            color: var(--brand-primary);
        }
        .quick-btn.danger-btn .quick-btn-icon {
            color: var(--danger);
        }

        /* -------------------------------------------------------------
           Action Buttons (Ergonomic Touch Targets >= 48px)
        ------------------------------------------------------------- */
        .btn-driver-primary {
            background: var(--brand-primary);
            color: #ffffff;
            border: none;
            padding: 13px 16px;
            font-weight: 600;
            font-size: 0.9rem;
            border-radius: var(--radius-md);
            min-height: var(--touch-target);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            text-decoration: none;
            transition: opacity 0.15s ease;
        }
        .btn-driver-primary:active {
            opacity: 0.9;
        }
        .btn-driver-success {
            background: var(--success);
            color: #ffffff;
            border: none;
            padding: 13px 16px;
            font-weight: 600;
            font-size: 0.9rem;
            border-radius: var(--radius-md);
            min-height: var(--touch-target);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            text-decoration: none;
        }
        .btn-driver-outline {
            background: transparent;
            color: var(--text-primary);
            border: 1px solid var(--border-subtle);
            padding: 11px 16px;
            font-weight: 600;
            font-size: 0.85rem;
            border-radius: var(--radius-md);
            min-height: var(--touch-target);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            text-decoration: none;
        }
        .btn-driver-outline:active {
            background: #f1f5f9;
        }

        /* -------------------------------------------------------------
           Segmented View Controller
        ------------------------------------------------------------- */
        .nav-segmented {
            background: #f1f5f9;
            padding: 4px;
            border-radius: var(--radius-md);
            display: flex;
            gap: 4px;
            margin-bottom: 14px;
            border: 1px solid var(--border-subtle);
        }
        .segmented-item {
            flex: 1;
            padding: 8px 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-align: center;
            color: var(--text-secondary);
            background: none;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .segmented-item.active {
            background: #ffffff;
            color: var(--brand-primary);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .tab-panel-view {
            display: none;
        }
        .tab-panel-view.active {
            display: block;
        }

        /* -------------------------------------------------------------
           Bottom Ergonomic Thumb Navigation Dock
        ------------------------------------------------------------- */
        .bottom-thumb-dock {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: calc(62px + var(--safe-bottom));
            background: #ffffff;
            border-top: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            justify-content: space-around;
            padding: 0 8px var(--safe-bottom);
            z-index: 1040;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.03);
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
            font-size: 0.68rem;
            font-weight: 600;
            padding: 0;
            position: relative;
            cursor: pointer;
            transition: color 0.15s ease;
        }
        .thumb-nav-btn.active {
            color: var(--brand-primary);
        }
        .thumb-nav-btn i {
            font-size: 1.15rem;
        }
        .nav-alert-indicator {
            position: absolute;
            top: 8px;
            right: calc(50% - 16px);
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--warning);
            border: 2px solid #ffffff;
        }

        /* Delay & Reason Select Chips */
        .chip-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }
        .chip-option {
            padding: 8px 14px;
            border-radius: var(--radius-pill);
            border: 1px solid var(--border-subtle);
            background: #f8fafc;
            color: var(--text-secondary);
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .chip-option.selected {
            background: var(--brand-light);
            border-color: var(--brand-accent);
            color: var(--brand-primary);
            font-weight: 600;
        }
    </style>
</head>
<body>

    <!-- -------------------------------------------------------------
         MINIMALIST APP HEADER (Sticky)
    -------------------------------------------------------------- -->
    <header class="driver-header">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <div class="driver-avatar">
                    <?= esc(strtoupper(substr($driver['first_name'] ?? 'D', 0, 1) . substr($driver['last_name'] ?? 'R', 0, 1))) ?>
                </div>
                <div>
                    <h6 class="fw-bold mb-0 text-truncate" style="font-size: 0.92rem; max-width: 170px;">
                        <?= esc(($driver['first_name'] ?? 'Driver') . ' ' . ($driver['last_name'] ?? '')) ?>
                    </h6>
                    <div class="small text-muted mono" style="font-size: 0.7rem;">
                        <?= esc($driver['driver_code'] ?? 'DRV-101') ?> &bull; PIA Motorpool
                    </div>
                </div>
            </div>

            <!-- Duty Status & Profile Switcher -->
            <div class="d-flex align-items-center gap-2">
                <?php $dutyState = $driver['status'] ?? 'available'; ?>
                <button type="button" class="duty-badge duty-<?= esc($dutyState) ?>" data-bs-toggle="modal" data-bs-target="#dutyModal" title="Change Duty Status">
                    <span class="status-dot"></span>
                    <span><?= ucfirst(str_replace('_', ' ', $dutyState)) ?></span>
                </button>

                <!-- More Options Menu -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border p-1 rounded-circle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 32px; height: 32px;">
                        <i class="fa-solid fa-ellipsis-vertical text-secondary"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border mt-1" style="min-width: 220px; font-size: 0.85rem;">
                        <li class="px-3 py-2 border-bottom">
                            <span class="d-block text-muted small">Logged in as</span>
                            <strong class="text-dark"><?= esc($driver['first_name'] . ' ' . $driver['last_name']) ?></strong>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#dutyModal">
                                <i class="fa-solid fa-user-clock me-2 text-primary"></i> Change Duty Status
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="<?= base_url('tickets') ?>">
                                <i class="fa-solid fa-file-invoice me-2 text-secondary"></i> All Trip Tickets
                            </a>
                        </li>
                        <?php if (!empty($allDrivers) && count($allDrivers) > 1): ?>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li class="px-3 py-1 text-muted small text-uppercase mono" style="font-size: 0.65rem;">Switch Driver Profile (Demo)</li>
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
         MAIN CONTENT AREA
    -------------------------------------------------------------- -->
    <main class="container px-3 pt-3" role="main">

        <!-- Flash Feedback Banners -->
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show p-3 rounded-3 mb-3 border-0 d-flex align-items-center" role="alert" style="background: #ecfdf5; color: #065f46; border-left: 4px solid #059669 !important;">
                <i class="fa-solid fa-circle-check fs-5 me-2"></i>
                <div class="small fw-medium flex-grow-1"><?= session()->getFlashdata('success') ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show p-3 rounded-3 mb-3 border-0 d-flex align-items-center" role="alert" style="background: #fef2f2; color: #991b1b; border-left: 4px solid #dc2626 !important;">
                <i class="fa-solid fa-triangle-exclamation fs-5 me-2"></i>
                <div class="small fw-medium flex-grow-1"><?= session()->getFlashdata('error') ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Quick Action Tap Row (Thumb Ergonomics) -->
        <div class="quick-actions-row">
            <!-- 1. Enlarge QR Gate Pass -->
            <button type="button" class="quick-btn" data-bs-toggle="modal" data-bs-target="#qrPassModal">
                <i class="fa-solid fa-qrcode quick-btn-icon"></i>
                <span>Gate Pass</span>
            </button>

            <!-- 2. Refuel Modal -->
            <button type="button" class="quick-btn" data-bs-toggle="modal" data-bs-target="#fuelModal">
                <i class="fa-solid fa-gas-pump quick-btn-icon" style="color: #059669;"></i>
                <span>Log Fuel</span>
            </button>

            <!-- 3. Passenger Delay Logger (FR-4.2) -->
            <?php if ($activeTicket): ?>
                <button type="button" class="quick-btn" data-bs-toggle="modal" data-bs-target="#delayModal">
                    <i class="fa-solid fa-user-clock quick-btn-icon" style="color: #d97706;"></i>
                    <span>Log Delay</span>
                </button>
            <?php else: ?>
                <a href="<?= base_url('tickets') ?>" class="quick-btn">
                    <i class="fa-solid fa-ticket quick-btn-icon"></i>
                    <span>Tickets</span>
                </a>
            <?php endif; ?>

            <!-- 4. Emergency SOS Breakdown (FR-4.3) -->
            <button type="button" class="quick-btn danger-btn" data-bs-toggle="modal" data-bs-target="#incidentModal">
                <i class="fa-solid fa-triangle-exclamation quick-btn-icon"></i>
                <span class="text-danger">SOS Alert</span>
            </button>
        </div>

        <!-- Segmented Tab Navigation -->
        <div class="nav-segmented" role="tablist">
            <button type="button" class="segmented-item active" data-view="view-active-trip" role="tab" id="tabBtnActive">
                Active Trip <?= $activeTicket ? '•' : '' ?>
            </button>
            <button type="button" class="segmented-item" data-view="view-vehicle" role="tab" id="tabBtnVehicle">
                Vehicle & Safety
            </button>
            <button type="button" class="segmented-item" data-view="view-expenses" role="tab" id="tabBtnExpenses">
                Fuel & Tolls
            </button>
            <button type="button" class="segmented-item" data-view="view-history" role="tab" id="tabBtnHistory">
                History & Help
            </button>
        </div>

        <!-- =========================================================
             VIEW 1: ACTIVE TRIP (Hero Operational Focus)
        ========================================================== -->
        <section id="view-active-trip" class="tab-panel-view active">

            <?php if ($activeTicket): ?>
                <!-- Hero Assignment Card -->
                <div class="driver-card card-active-trip">
                    <div class="driver-card-header">
                        <div>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 mono px-2 py-1" style="font-size: 0.68rem;">
                                <?= esc($activeTicket['ticket_serial_no']) ?>
                            </span>
                        </div>
                        <?php
                            $statusInfo = match($activeTicket['status']) {
                                'issued'    => ['label' => 'Gate Egress Clearance', 'bg' => '#fef3c7', 'color' => '#92400e', 'icon' => 'fa-id-badge'],
                                'departed'  => ['label' => 'En Route / On Trip', 'bg' => '#dbeafe', 'color' => '#1e40af', 'icon' => 'fa-car-side'],
                                'returned'  => ['label' => 'Returned &bull; Pending Report', 'bg' => '#e0e7ff', 'color' => '#3730a3', 'icon' => 'fa-clipboard-check'],
                                'completed' => ['label' => 'Completed', 'bg' => '#d1fae5', 'color' => '#065f46', 'icon' => 'fa-circle-check'],
                                default     => ['label' => ucfirst($activeTicket['status']), 'bg' => '#f1f5f9', 'color' => '#475569', 'icon' => 'fa-circle-dot'],
                            };
                        ?>
                        <span class="badge px-2 py-1" style="background: <?= $statusInfo['bg'] ?>; color: <?= $statusInfo['color'] ?>; font-size: 0.72rem; font-weight: 600;">
                            <i class="fa-solid <?= $statusInfo['icon'] ?> me-1"></i><?= $statusInfo['label'] ?>
                        </span>
                    </div>

                    <!-- Destination & Route Flow -->
                    <div class="mb-3">
                        <div class="small text-muted mb-1 text-uppercase fw-semibold" style="font-size: 0.68rem;">Authorized Destination</div>
                        <h5 class="fw-bold text-dark mb-1 d-flex align-items-start justify-content-between">
                            <span><?= esc($activeTicket['authorized_destination']) ?></span>
                            <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($activeTicket['authorized_destination']) ?>" target="_blank" class="btn btn-sm btn-light border py-1 px-2 text-primary" title="Open in Google Maps">
                                <i class="fa-solid fa-location-arrow"></i>
                            </a>
                        </h5>
                        <?php if (!empty($activeTicket['authorized_purpose'])): ?>
                            <p class="small text-secondary mb-0"><?= esc($activeTicket['authorized_purpose']) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- BLOWBAGETS Pre-Trip Safety Notice (FR-5.1) -->
                    <div class="mb-3 p-2 rounded-3 border d-flex align-items-center justify-content-between" style="background: <?= $blowbagetsPassed ? '#ecfdf5' : '#fffbeb' ?>; border-color: <?= $blowbagetsPassed ? '#a7f3d0' : '#fde68a' ?> !important;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid <?= $blowbagetsPassed ? 'fa-circle-check text-success' : 'fa-shield-halved text-warning' ?> fs-5"></i>
                            <div>
                                <strong class="small d-block" style="color: <?= $blowbagetsPassed ? '#065f46' : '#92400e' ?>;">
                                    <?= $blowbagetsPassed ? 'BLOWBAGETS Safety Cleared' : 'BLOWBAGETS Checklist Required' ?>
                                </strong>
                                <span class="text-muted" style="font-size: 0.7rem;">
                                    <?= $blowbagetsPassed ? 'Inspected & certified for safe travel' : 'Must pass 10-point check before gate exit' ?>
                                </span>
                            </div>
                        </div>
                        <?php if (!$blowbagetsPassed): ?>
                            <a href="<?= base_url('safety/' . $activeTicket['id']) ?>" class="btn btn-sm btn-warning text-dark fw-bold px-3 py-1" style="font-size: 0.75rem;">
                                Inspect
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- High-Contrast Gate Pass E-QR (Scannable at Compound Gate) -->
                    <div class="qr-pass-container">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="small fw-semibold text-secondary" style="font-size: 0.72rem;">
                                <i class="fa-solid fa-shield-check text-primary me-1"></i> Compound Gate E-Pass
                            </span>
                            <span class="mono small text-muted" style="font-size: 0.7rem;">ADMIN-F-001 rev1</span>
                        </div>

                        <div class="qr-code-box my-1">
                            <div id="activeTicketQr"></div>
                        </div>

                        <div class="mt-2 text-center">
                            <div class="mono fw-bold text-dark" style="font-size: 0.85rem;">
                                <?= esc($activeTicket['plate_number']) ?> &bull; <?= esc($activeTicket['ticket_serial_no']) ?>
                            </div>
                            <button type="button" class="btn btn-link btn-sm text-primary text-decoration-none fw-semibold p-0 mt-1" data-bs-toggle="modal" data-bs-target="#qrPassModal">
                                <i class="fa-solid fa-expand me-1"></i> Tap to Fullscreen for Gate Guard
                            </button>
                        </div>
                    </div>

                    <!-- Passengers & Schedule Details -->
                    <div class="bg-light p-3 rounded-3 border mb-3">
                        <div class="row g-2 small">
                            <div class="col-6">
                                <span class="text-muted d-block" style="font-size: 0.68rem;">Departure Schedule</span>
                                <strong class="mono text-dark" style="font-size: 0.78rem;">
                                    <?= date('M d, h:i A', strtotime($activeTicket['authorized_departure'])) ?>
                                </strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block" style="font-size: 0.68rem;">Approver</span>
                                <strong class="text-dark text-truncate d-block" style="font-size: 0.78rem;">
                                    <?= esc($activeTicket['admin_approver_name'] ?? 'Admin Head') ?>
                                </strong>
                            </div>
                            <div class="col-12 mt-2 pt-2 border-top">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="text-muted" style="font-size: 0.68rem;">Authorized Passengers</span>
                                    <button type="button" class="btn btn-link p-0 text-warning text-decoration-none small fw-semibold" data-bs-toggle="modal" data-bs-target="#delayModal" style="font-size: 0.72rem;">
                                        <i class="fa-solid fa-clock-rotate-left me-1"></i> Report Delay
                                    </button>
                                </div>
                                <div class="text-dark fw-medium" style="font-size: 0.78rem;">
                                    <?= nl2br(esc($activeTicket['authorized_passengers'] ?? 'Official Coverage Team')) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Passenger Delays Logged (FR-4.2) -->
                    <?php if (!empty($recentDelays)): ?>
                        <div class="mb-3 p-2 bg-light rounded-3 border border-warning border-opacity-50">
                            <span class="small fw-bold text-warning d-block mb-1" style="font-size: 0.72rem;">
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

                    <!-- Primary Context Action Progression -->
                    <div>
                        <?php if ($activeTicket['status'] === 'issued'): ?>
                            <div class="p-3 bg-light rounded-3 text-center mb-2 border">
                                <i class="fa-solid fa-shield-halved text-primary fs-4 mb-1"></i>
                                <div class="small fw-bold text-dark">Ready for Gate Departure</div>
                                <p class="text-muted small mb-0" style="font-size: 0.72rem;">
                                    Present the Gate Pass QR code above to the Guard at Gate #1 to record your official departure egress.
                                </p>
                            </div>
                            <a href="<?= base_url('tickets/' . $activeTicket['id']) ?>" class="btn-driver-outline">
                                <i class="fa-solid fa-file-lines me-1"></i> View Full Ticket Document
                            </a>
                        <?php elseif ($activeTicket['status'] === 'departed'): ?>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn-driver-primary" data-bs-toggle="modal" data-bs-target="#sectionBModal">
                                    <i class="fa-solid fa-location-dot me-1"></i> Log Arrival or Toll Expense
                                </button>
                                <button type="button" class="btn-driver-outline" data-bs-toggle="modal" data-bs-target="#delayModal">
                                    <i class="fa-solid fa-user-clock me-1"></i> Log 15-Minute Passenger Delay
                                </button>
                            </div>
                        <?php elseif ($activeTicket['status'] === 'returned'): ?>
                            <button type="button" class="btn-driver-success" data-bs-toggle="modal" data-bs-target="#sectionBModal">
                                <i class="fa-solid fa-clipboard-check me-1"></i> Complete Section B Post-Trip Report
                            </button>
                        <?php else: ?>
                            <a href="<?= base_url('tickets/' . $activeTicket['id']) ?>" class="btn-driver-outline">
                                <i class="fa-solid fa-file-check me-1"></i> View Reconciled Ticket
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Standby Card (No Active Ticket) -->
                <div class="driver-card text-center py-5">
                    <div style="width: 52px; height: 52px; border-radius: 50%; background: #eff6ff; color: var(--brand-accent); display: grid; place-items: center; margin: 0 auto 12px; font-size: 1.4rem;">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">Standby &bull; Available for Dispatch</h6>
                    <p class="text-muted small mb-3 px-4">
                        You have no pending gate clearances or active official travel tickets assigned at this moment.
                    </p>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary px-3" onclick="document.getElementById('tabBtnHistory').click();">
                            <i class="fa-solid fa-history me-1"></i> Ticket Archives
                        </button>
                        <button type="button" class="btn btn-sm btn-primary px-3" data-bs-toggle="modal" data-bs-target="#dutyModal">
                            <i class="fa-solid fa-user-clock me-1"></i> Duty Status
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Assigned Unit Mini Summary -->
            <?php if ($vehicle): ?>
                <div class="driver-card">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <span class="small text-muted d-block" style="font-size: 0.68rem;">Assigned Motorpool Vehicle</span>
                            <h6 class="fw-bold text-dark mb-0 mono"><?= esc($vehicle['plate_number']) ?></h6>
                            <span class="small text-secondary"><?= esc(($vehicle['make'] ?? '') . ' ' . ($vehicle['model'] ?? '')) ?></span>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-light text-dark border mono"><?= esc($vehicle['fuel_type'] ?? 'Diesel') ?></span>
                        </div>
                    </div>

                    <div class="metric-strip">
                        <div class="metric-cell">
                            <div class="metric-label">Odometer</div>
                            <div class="metric-val mono"><?= number_format((float)($vehicle['odometer_km'] ?? 0), 0) ?> <span class="small text-muted" style="font-size: 0.65rem;">KM</span></div>
                        </div>
                        <div class="metric-cell">
                            <div class="metric-label">Fuel Level</div>
                            <?php $fuelPct = (float)($vehicle['current_fuel_level'] ?? 80); ?>
                            <div class="metric-val mono <?= $fuelPct < 25 ? 'text-danger' : ($fuelPct < 50 ? 'text-warning' : 'text-success') ?>">
                                <?= number_format($fuelPct, 0) ?>%
                            </div>
                        </div>
                        <div class="metric-cell">
                            <div class="metric-label">Status</div>
                            <div class="metric-val text-capitalize" style="font-size: 0.85rem; color: #059669;">
                                <?= esc(str_replace('_', ' ', $vehicle['status'] ?? 'Active')) ?>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn-driver-outline py-2 flex-grow-1" onclick="document.getElementById('tabBtnVehicle').click();" style="min-height: 40px; font-size: 0.8rem;">
                            <i class="fa-solid fa-sliders me-1"></i> Vehicle Diagnostics & Toll RFID
                        </button>
                    </div>
                </div>
            <?php endif; ?>

        </section>

        <!-- =========================================================
             VIEW 2: VEHICLE & SAFETY (Diagnostics & RFID Balances)
        ========================================================== -->
        <section id="view-vehicle" class="tab-panel-view">

            <!-- Vehicle Identification -->
            <?php if ($vehicle): ?>
                <div class="driver-card">
                    <div class="driver-card-header">
                        <span class="card-title-sm"><i class="fa-solid fa-truck-pickup text-primary"></i> Vehicle Specifications</span>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 small">
                            <?= ucfirst($vehicle['status'] ?? 'Active') ?>
                        </span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="fw-bold text-dark mb-0 mono"><?= esc($vehicle['plate_number']) ?></h5>
                            <span class="text-secondary small"><?= esc($vehicle['make'] . ' ' . $vehicle['model'] . ' (' . ($vehicle['year'] ?? date('Y')) . ')') ?></span>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-light text-secondary border"><?= esc($vehicle['vehicle_code'] ?? 'UNIT') ?></span>
                        </div>
                    </div>

                    <div class="row g-2 mb-3 small">
                        <div class="col-6 p-2 rounded bg-light border">
                            <span class="text-muted d-block" style="font-size: 0.65rem;">Engine State</span>
                            <strong class="text-success"><?= esc(ucfirst($vehicle['engine_status'] ?? 'Good')) ?></strong>
                        </div>
                        <div class="col-6 p-2 rounded bg-light border">
                            <span class="text-muted d-block" style="font-size: 0.65rem;">Battery Voltage</span>
                            <strong class="text-dark mono"><?= esc($vehicle['battery_voltage'] ?? '12.6') ?> V</strong>
                        </div>
                        <div class="col-6 p-2 rounded bg-light border">
                            <span class="text-muted d-block" style="font-size: 0.65rem;">Coolant Temp</span>
                            <strong class="text-dark mono"><?= esc($vehicle['coolant_temp_c'] ?? '85') ?> °C</strong>
                        </div>
                        <div class="col-6 p-2 rounded bg-light border">
                            <span class="text-muted d-block" style="font-size: 0.65rem;">Fuel Capacity</span>
                            <strong class="text-dark mono"><?= esc($vehicle['tank_capacity_l'] ?? '60') ?> L</strong>
                        </div>
                    </div>

                    <!-- BLOWBAGETS Pre-Trip Safety Action Button -->
                    <?php if ($activeTicket): ?>
                        <a href="<?= base_url('safety/' . $activeTicket['id']) ?>" class="btn-driver-outline py-2 w-100" style="min-height: 42px;">
                            <i class="fa-solid fa-list-check text-primary me-1"></i> Perform BLOWBAGETS Pre-Trip Inspection
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Tollway RFID Cards (FR-6.1 & FR-6.3) -->
                <div class="driver-card">
                    <div class="driver-card-header">
                        <span class="card-title-sm"><i class="fa-solid fa-id-card-clip text-primary"></i> Tollway RFID Balances</span>
                        <span class="badge bg-light text-muted border small mono">FR-6.1</span>
                    </div>

                    <?php if (empty($rfidCards)): ?>
                        <div class="text-center py-3 text-muted small">
                            No RFID cards currently linked to vehicle <?= esc($vehicle['plate_number']) ?>.
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2 mb-2">
                            <?php foreach ($rfidCards as $card): ?>
                                <?php $isLow = (float)$card['balance'] < (float)($card['low_balance_threshold'] ?? 500); ?>
                                <div class="p-3 rounded-3 border d-flex align-items-center justify-content-between" style="background: <?= $isLow ? '#fffbeb' : '#f8fafc' ?>; border-color: <?= $isLow ? '#fde68a' : '#e2e8f0' ?> !important;">
                                    <div>
                                        <div class="d-flex align-items-center gap-2">
                                            <strong class="text-uppercase text-dark" style="font-size: 0.85rem;">
                                                <?= esc($card['provider']) ?>
                                            </strong>
                                            <?php if ($isLow): ?>
                                                <span class="badge bg-warning text-dark" style="font-size: 0.65rem;">Low Balance</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mono small text-muted" style="font-size: 0.72rem;">
                                            <?= esc($card['card_number']) ?>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="mono fw-bold fs-6 <?= $isLow ? 'text-warning' : 'text-dark' ?>">
                                            ₱<?= number_format((float)$card['balance'], 2) ?>
                                        </div>
                                        <span class="text-muted" style="font-size: 0.65rem;">Live Balance</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </section>

        <!-- =========================================================
             VIEW 3: FUEL & EXPENSES (Logging & Ledger)
        ========================================================== -->
        <section id="view-expenses" class="tab-panel-view">

            <!-- Quick Log Refuel Action Button -->
            <div class="driver-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Fuel & Trip Expenses</h6>
                        <span class="small text-muted">Official receipts and petroleum logs</span>
                    </div>
                    <button type="button" class="btn btn-success btn-sm px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#fuelModal">
                        <i class="fa-solid fa-plus me-1"></i> Log Fuel
                    </button>
                </div>
            </div>

            <!-- Recent Fuel Purchases -->
            <div class="driver-card">
                <div class="driver-card-header">
                    <span class="card-title-sm"><i class="fa-solid fa-receipt text-primary"></i> Petroleum Logs (<?= count($recentFuelLogs) ?>)</span>
                    <span class="badge bg-light text-muted border small">Section B</span>
                </div>

                <?php if (empty($recentFuelLogs)): ?>
                    <div class="text-center py-4 text-muted small">
                        <i class="fa-solid fa-gas-pump fs-2 text-secondary opacity-50 mb-2"></i>
                        <p class="mb-0">No fuel purchase receipts logged recently.</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($recentFuelLogs as $fl): ?>
                            <div class="p-2 rounded bg-light border d-flex justify-content-between align-items-center small">
                                <div>
                                    <strong class="text-dark d-block"><?= esc($fl['fuel_station']) ?></strong>
                                    <span class="text-muted mono" style="font-size: 0.7rem;">
                                        <?= date('M d, Y', strtotime($fl['fuel_date'])) ?> &bull; <?= number_format($fl['liters'], 1) ?> Liters
                                    </span>
                                </div>
                                <div class="text-end mono">
                                    <strong class="text-dark d-block">₱<?= number_format($fl['total_cost'], 2) ?></strong>
                                    <span class="text-muted" style="font-size: 0.65rem;">Receipt #<?= esc(substr($fl['receipt_no'] ?? 'REC', -7)) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </section>

        <!-- =========================================================
             VIEW 4: HISTORY & HELP (Archives & Emergency Contacts)
        ========================================================== -->
        <section id="view-history" class="tab-panel-view">

            <!-- Driver License Card -->
            <div class="driver-card">
                <div class="driver-card-header">
                    <span class="card-title-sm"><i class="fa-solid fa-address-card text-primary"></i> Driver Credentials</span>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 small">Verified</span>
                </div>
                <div class="row g-2 small">
                    <div class="col-6">
                        <span class="text-muted d-block" style="font-size: 0.65rem;">License Number</span>
                        <strong class="mono text-dark"><?= esc($driver['license_number'] ?? 'N01-19-082914') ?></strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted d-block" style="font-size: 0.65rem;">License Expiry</span>
                        <strong class="mono text-dark"><?= esc($driver['license_expiry'] ?? 'Valid') ?></strong>
                    </div>
                    <div class="col-12 mt-1">
                        <span class="text-muted d-block" style="font-size: 0.65rem;">License Type</span>
                        <span class="text-dark"><?= esc($driver['license_type'] ?? 'Professional Driver') ?></span>
                    </div>
                </div>
            </div>

            <!-- Trip Ticket Archives -->
            <div class="driver-card">
                <div class="driver-card-header">
                    <span class="card-title-sm"><i class="fa-solid fa-ticket text-primary"></i> Assigned Trip Tickets (<?= count($tripTickets) ?>)</span>
                    <span class="badge bg-light text-muted border small mono">ADMIN-F-001</span>
                </div>

                <?php if (empty($tripTickets)): ?>
                    <div class="text-center py-4 text-muted small">
                        No trip tickets found in your driver profile.
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($tripTickets as $tk): ?>
                            <div class="p-2 rounded bg-light border d-flex justify-content-between align-items-center small">
                                <div>
                                    <div class="d-flex align-items-center gap-1">
                                        <strong class="mono text-primary"><?= esc($tk['ticket_serial_no']) ?></strong>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size: 0.65rem;">
                                            <?= ucfirst($tk['status']) ?>
                                        </span>
                                    </div>
                                    <span class="text-muted text-truncate d-block" style="max-width: 190px; font-size: 0.7rem;">
                                        <?= esc($tk['authorized_destination']) ?>
                                    </span>
                                </div>
                                <div class="d-flex gap-1">
                                    <a href="<?= base_url('tickets/' . $tk['id']) ?>" class="btn btn-sm btn-light border py-1 px-2" title="View">
                                        <i class="fa-solid fa-eye text-secondary"></i>
                                    </a>
                                    <a href="<?= base_url('tickets/' . $tk['id'] . '/print') ?>" target="_blank" class="btn btn-sm btn-light border py-1 px-2" title="Print">
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
                    <a href="tel:+639185550200" class="p-2 rounded bg-light border text-decoration-none text-dark d-flex justify-content-between align-items-center">
                        <div>
                            <strong class="d-block">Motorpool Dispatch Console</strong>
                            <span class="text-muted" style="font-size: 0.7rem;">Engr. Roberto D. Tan</span>
                        </div>
                        <span class="btn btn-sm btn-outline-primary py-1 px-2"><i class="fa-solid fa-phone me-1"></i> Call</span>
                    </a>
                    <a href="tel:+639185550400" class="p-2 rounded bg-light border text-decoration-none text-dark d-flex justify-content-between align-items-center">
                        <div>
                            <strong class="d-block">Compound Security Gate #1</strong>
                            <span class="text-muted" style="font-size: 0.7rem;">Sgt. Danilo Ramos</span>
                        </div>
                        <span class="btn btn-sm btn-outline-secondary py-1 px-2"><i class="fa-solid fa-phone me-1"></i> Call</span>
                    </a>
                </div>
            </div>

        </section>

    </main>

    <!-- -------------------------------------------------------------
         BOTTOM ERGONOMIC THUMB NAVIGATION DOCK
    -------------------------------------------------------------- -->
    <nav class="bottom-thumb-dock" role="navigation" aria-label="Bottom Navigation">
        <button type="button" class="thumb-nav-btn active" data-view="view-active-trip" id="dockBtnTrip">
            <i class="fa-solid fa-car"></i>
            <span>Active Trip</span>
            <?php if ($activeTicket && in_array($activeTicket['status'], ['issued', 'departed', 'returned'])): ?>
                <span class="nav-alert-indicator"></span>
            <?php endif; ?>
        </button>

        <button type="button" class="thumb-nav-btn" data-view="view-vehicle" id="dockBtnVehicle">
            <i class="fa-solid fa-truck-front"></i>
            <span>Vehicle</span>
        </button>

        <button type="button" class="thumb-nav-btn" data-view="view-expenses" id="dockBtnExpenses">
            <i class="fa-solid fa-gas-pump"></i>
            <span>Fuel & Tolls</span>
        </button>

        <button type="button" class="thumb-nav-btn" data-view="view-history" id="dockBtnHistory">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>History</span>
        </button>
    </nav>

    <!-- =============================================================
         MODALS (Clean Minimalist Ergonomic Design)
    ============================================================== -->

    <!-- 1. FULLSCREEN GATE PASS QR MODAL (Scanner Ready) -->
    <div class="modal fade" id="qrPassModal" tabindex="-1" aria-labelledby="qrPassModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 mono">ADMIN-F-001 rev1</span>
                        <h6 class="modal-title fw-bold text-dark mt-1" id="qrPassModalLabel">Gate Security Clearance Pass</h6>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <p class="text-muted small mb-3">Present this code directly to Compound Gate Guard Checkpoint Scanner.</p>
                    
                    <div class="p-3 bg-white border rounded-3 d-inline-block shadow-sm my-1" style="width: 240px; height: 240px;">
                        <div id="modalQrCanvas" class="d-flex align-items-center justify-content-center h-100"></div>
                    </div>

                    <div class="mono fw-bold fs-5 text-dark mt-2">
                        <?= esc($activeTicket['ticket_serial_no'] ?? ($vehicle['plate_number'] ?? 'GATE-PASS')) ?>
                    </div>
                    <div class="small text-muted mono">
                        Unit: <strong class="text-dark"><?= esc($vehicle['plate_number'] ?? 'N/A') ?></strong> &bull; Driver: <strong class="text-dark"><?= esc($driver['driver_code'] ?? 'DRV-101') ?></strong>
                    </div>

                    <div class="p-2 mt-3 bg-light rounded text-muted small" style="font-size: 0.72rem;">
                        <i class="fa-solid fa-shield-check text-success me-1"></i> HMAC-SHA256 Cryptographic Gate Clearance Token
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Close Pass</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. PASSENGER DELAY LOGGER MODAL (FR-4.2) -->
    <?php if ($activeTicket): ?>
    <div class="modal fade" id="delayModal" tabindex="-1" aria-labelledby="delayModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form action="<?= base_url('tickets/' . $activeTicket['id'] . '/delay') ?>" method="POST">
                    <?= !is_cli() ? csrf_field() : '' ?>
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">FR-4.2 Protocol</span>
                            <h6 class="modal-title fw-bold text-dark mt-1" id="delayModalLabel">Log Passenger Pick-up Delay</h6>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-3">
                        <p class="text-muted small mb-3">
                            Delays of 15 minutes or longer automatically notify Motorpool Dispatch to adjust fleet schedules.
                        </p>

                        <label class="form-label small fw-semibold text-secondary">Delay Duration</label>
                        <div class="chip-group" id="delayMinutesChips">
                            <button type="button" class="chip-option" data-val="10">+10 mins</button>
                            <button type="button" class="chip-option selected" data-val="15">+15 mins (Standard)</button>
                            <button type="button" class="chip-option" data-val="30">+30 mins</button>
                            <button type="button" class="chip-option" data-val="45">+45 mins</button>
                        </div>
                        <input type="hidden" name="delay_minutes" id="inputDelayMinutes" value="15">

                        <label class="form-label small fw-semibold text-secondary">Reason / Delay Category</label>
                        <div class="chip-group" id="delayReasonChips">
                            <button type="button" class="chip-option selected" data-reason="Passengers still in conference/meeting">In Meeting</button>
                            <button type="button" class="chip-option" data-reason="Awaiting security clearance / briefing">Security Hold</button>
                            <button type="button" class="chip-option" data-reason="Lobby / Building departure hold">Lobby Wait</button>
                            <button type="button" class="chip-option" data-reason="Heavy road congestion en route to pick-up">Traffic Jam</button>
                        </div>
                        <input type="text" name="reason" id="inputDelayReason" class="form-control form-control-sm" value="Passengers still in conference/meeting" placeholder="Or type custom reason..." required>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning fw-bold text-dark">Submit Delay Log</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 3. REFUEL & RECEIPT LOGGING MODAL -->
    <div class="modal fade" id="fuelModal" tabindex="-1" aria-labelledby="fuelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form action="<?= base_url('api/v1/driver/fuel') ?>" method="POST" enctype="multipart/form-data">
                    <?= !is_cli() ? csrf_field() : '' ?>
                    <input type="hidden" name="vehicle_id" value="<?= $vehicle['id'] ?? 1 ?>">
                    <input type="hidden" name="driver_id" value="<?= $driver['id'] ?? 1 ?>">
                    <div class="modal-header border-0 pb-0">
                        <h6 class="modal-title fw-bold text-dark" id="fuelModalLabel">
                            <i class="fa-solid fa-gas-pump text-success me-2"></i> Log Official Gas Station Refuel
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Gas Station / Vendor</label>
                            <input type="text" name="fuel_station" class="form-control" placeholder="e.g. Petron Philam, Shell Visayas Ave" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary">Volume (Liters)</label>
                                <input type="number" step="0.01" name="liters" class="form-control mono" required placeholder="0.00">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary">Total Cost (₱)</label>
                                <input type="number" step="0.01" name="total_cost" class="form-control mono" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Current Odometer (KM)</label>
                            <input type="number" step="0.1" name="odometer_km" class="form-control mono" value="<?= $vehicle['odometer_km'] ?? 0 ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-secondary">Receipt Photo (Optional)</label>
                            <input type="file" name="receipt_image" accept="image/*" class="form-control form-control-sm" capture="environment">
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success fw-bold">Submit Fuel Log</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 4. REPORT INCIDENT / EMERGENCY SOS MODAL (FR-4.3) -->
    <div class="modal fade" id="incidentModal" tabindex="-1" aria-labelledby="incidentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form action="<?= base_url('driver/incident') ?>" method="POST">
                    <?= !is_cli() ? csrf_field() : '' ?>
                    <input type="hidden" name="vehicle_id" value="<?= $vehicle['id'] ?? 1 ?>">
                    <input type="hidden" name="driver_id" value="<?= $driver['id'] ?? 1 ?>">
                    <input type="hidden" name="trip_id" value="<?= $activeTicket['id'] ?? '' ?>">
                    <div class="modal-header border-0 pb-0">
                        <h6 class="modal-title fw-bold text-danger" id="incidentModalLabel">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i> Report Breakdown / Emergency SOS
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div class="alert alert-danger border-0 p-2 small mb-3" style="font-size: 0.72rem;">
                            <i class="fa-solid fa-circle-info me-1"></i> Reporting a breakdown automatically sets the vehicle to <strong>Disabled/Breakdown</strong> and routes a Pre-Repair Inspection Report (PIR) to the mechanic queue.
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Incident Category</label>
                            <select name="type" class="form-select form-select-sm" required>
                                <option value="Mechanical Breakdown">Mechanical Breakdown (Engine / Transmission)</option>
                                <option value="Tire Puncture / Flat">Tire Puncture / Flat</option>
                                <option value="Brake Malfunction">Brake Malfunction</option>
                                <option value="Electrical Failure">Electrical / Battery Failure</option>
                                <option value="Accident / Collision">Accident / Collision</option>
                                <option value="Medical Emergency">Driver Medical Issue</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Severity Level</label>
                            <select name="severity" class="form-select form-select-sm" required>
                                <option value="minor">Minor (Vehicle operable, slight delay)</option>
                                <option value="moderate" selected>Moderate (Roadside assistance required)</option>
                                <option value="severe">Severe (Vehicle immobilized / Towing required)</option>
                                <option value="critical">Critical (Immediate Emergency SOS)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Location / Landmark</label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="location" id="incidentLocationInput" class="form-control" placeholder="e.g. EDSA Northbound near Quezon Ave" required>
                                <button class="btn btn-outline-primary" type="button" id="btnGetGps" title="Detect GPS Location">
                                    <i class="fa-solid fa-location-crosshairs"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-secondary">Description & Assistance Needed</label>
                            <textarea name="description" rows="3" class="form-control form-control-sm" placeholder="Provide details of the vehicle condition and assistance needed..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger fw-bold">Transmit Alert to Dispatch</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 5. DUTY STATUS MODAL -->
    <div class="modal fade" id="dutyModal" tabindex="-1" aria-labelledby="dutyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form action="<?= base_url('driver/status') ?>" method="POST">
                    <?= !is_cli() ? csrf_field() : '' ?>
                    <input type="hidden" name="driver_id" value="<?= $driver['id'] ?? 1 ?>">
                    <div class="modal-header border-0 pb-0">
                        <h6 class="modal-title fw-bold text-dark" id="dutyModalLabel">Update Duty Status</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3">
                        <p class="text-muted small mb-3">Select your operational status for dispatch availability:</p>

                        <div class="d-flex flex-column gap-2">
                            <label class="p-3 rounded-3 border d-flex align-items-center justify-content-between cursor-pointer" style="background: #f8fafc;">
                                <div class="d-flex align-items-center gap-2">
                                    <input type="radio" name="status" value="available" <?= ($driver['status'] ?? '') === 'available' ? 'checked' : '' ?> class="form-check-input mt-0">
                                    <div>
                                        <strong class="text-success d-block small">Available</strong>
                                        <span class="text-muted" style="font-size: 0.72rem;">On standby and ready for trip assignment</span>
                                    </div>
                                </div>
                                <span class="status-dot" style="background: #059669; width: 10px; height: 10px;"></span>
                            </label>

                            <label class="p-3 rounded-3 border d-flex align-items-center justify-content-between cursor-pointer" style="background: #f8fafc;">
                                <div class="d-flex align-items-center gap-2">
                                    <input type="radio" name="status" value="on_trip" <?= ($driver['status'] ?? '') === 'on_trip' ? 'checked' : '' ?> class="form-check-input mt-0">
                                    <div>
                                        <strong class="text-warning d-block small">On Trip / In Transit</strong>
                                        <span class="text-muted" style="font-size: 0.72rem;">Currently deployed on active mission</span>
                                    </div>
                                </div>
                                <span class="status-dot" style="background: #d97706; width: 10px; height: 10px;"></span>
                            </label>

                            <label class="p-3 rounded-3 border d-flex align-items-center justify-content-between cursor-pointer" style="background: #f8fafc;">
                                <div class="d-flex align-items-center gap-2">
                                    <input type="radio" name="status" value="off_duty" <?= ($driver['status'] ?? '') === 'off_duty' ? 'checked' : '' ?> class="form-check-input mt-0">
                                    <div>
                                        <strong class="text-secondary d-block small">Off Duty</strong>
                                        <span class="text-muted" style="font-size: 0.72rem;">Shift ended or mandatory rest period</span>
                                    </div>
                                </div>
                                <span class="status-dot" style="background: #94a3b8; width: 10px; height: 10px;"></span>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 6. SECTION B POST-TRIP EXECUTION MODAL (ADMIN-F-001 rev1) -->
    <?php if ($activeTicket): ?>
    <div class="modal fade" id="sectionBModal" tabindex="-1" aria-labelledby="sectionBModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <form action="<?= base_url('tickets/' . $activeTicket['id'] . '/update') ?>" method="POST" id="sectionBForm">
                    <?= !is_cli() ? csrf_field() : '' ?>
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 mono">ADMIN-F-001 rev1</span>
                            <h6 class="modal-title fw-bold text-dark mt-1" id="sectionBModalLabel">Section B: Post-Trip & Fuel Accounting Report</h6>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3">
                        <p class="text-muted small mb-3">
                            Official post-trip audit formula and digital driver certification for <?= esc($activeTicket['ticket_serial_no']) ?>.
                        </p>

                        <!-- Trip Odometers -->
                        <div class="fw-bold text-dark small text-uppercase mb-2 pb-1 border-bottom">
                            <i class="fa-solid fa-gauge text-primary me-1"></i> 1. Odometer Readings
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small text-secondary">Start Odometer (KM)</label>
                                <input type="number" step="0.1" name="start_odometer" id="m_start_odo" class="form-control mono" value="<?= (float)($activeTicket['start_odometer'] ?? ($vehicle['odometer_km'] ?? 0)) ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-secondary">Return Odometer (KM)</label>
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
                            <i class="fa-solid fa-gas-pump text-primary me-1"></i> 2. Fuel Balance Formula
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small text-secondary">Start Balance (L)</label>
                                <input type="number" step="0.01" name="fuel_balance_start_liters" id="m_fuel_start" class="form-control mono" value="<?= (float)($activeTicket['fuel_balance_start_liters'] ?? 40) ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-secondary">Stock Issued (L)</label>
                                <input type="number" step="0.01" name="fuel_issued_stock_liters" id="m_fuel_issued" class="form-control mono" value="<?= (float)($activeTicket['fuel_issued_stock_liters'] ?? 0) ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-secondary">Purchased on Trip (L)</label>
                                <input type="number" step="0.01" name="fuel_purchased_liters" id="m_fuel_purchased" class="form-control mono" value="<?= (float)($activeTicket['fuel_purchased_liters'] ?? 0) ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-secondary">Total Fuel Used (L)</label>
                                <input type="number" step="0.01" name="fuel_used_liters" id="m_fuel_used" class="form-control mono" value="<?= (float)($activeTicket['fuel_used_liters'] ?? 0) ?>" required>
                            </div>
                            <div class="col-12">
                                <div class="p-2 rounded bg-light border d-flex justify-content-between align-items-center small">
                                    <span class="text-muted">Calculated Ending Balance:</span>
                                    <strong class="mono text-success" id="m_calcEndFuel"><?= number_format((float)($activeTicket['fuel_balance_end_liters'] ?? 40), 2) ?> Liters</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Toll Expense Capture (FR-4.1 & FR-6.2) -->
                        <div class="fw-bold text-dark small text-uppercase mb-2 pb-1 border-bottom">
                            <i class="fa-solid fa-road text-primary me-1"></i> 3. Tollways & Expenses
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

                        <!-- Driver Certification Checkbox -->
                        <div class="p-3 rounded bg-light border mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="driver_certified" value="1" id="m_driverCert" <?= !empty($activeTicket['driver_certified']) ? 'checked' : '' ?> required>
                                <label class="form-check-label small text-dark" for="m_driverCert">
                                    <strong>I hereby certify on official honor</strong> that the above trip route, distance, and fuel consumption are true and correct as accomplished.
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold">Submit Section B</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Client Scripts for Tabs, QR Code & Forms -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Tab & View Switcher Engine
        const segButtons = document.querySelectorAll('.segmented-item[data-view]');
        const dockButtons = document.querySelectorAll('.thumb-nav-btn[data-view]');
        const tabViews = document.querySelectorAll('.tab-panel-view');

        function switchView(targetViewId) {
            tabViews.forEach(v => {
                if (v.id === targetViewId) {
                    v.classList.add('active');
                } else {
                    v.classList.remove('active');
                }
            });

            segButtons.forEach(btn => {
                if (btn.getAttribute('data-view') === targetViewId) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });

            dockButtons.forEach(btn => {
                if (btn.getAttribute('data-view') === targetViewId) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        [...segButtons, ...dockButtons].forEach(btn => {
            btn.addEventListener('click', function (e) {
                const target = this.getAttribute('data-view');
                if (target) {
                    e.preventDefault();
                    switchView(target);
                }
            });
        });

        // 2. High-Contrast QR Code Rendering for Checkpoint
        const qrToken = <?= json_encode($activeTicket['qr_crypt_token'] ?? ($activeTicket['ticket_serial_no'] ?? ($vehicle['plate_number'] ?? 'PIA-TOKEN-DEMO'))) ?>;

        const cardQr = document.getElementById('activeTicketQr');
        if (cardQr && qrToken) {
            new QRCode(cardQr, {
                text: qrToken,
                width: 130,
                height: 130,
                colorDark: "#0f172a",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.M
            });
        }

        const modalQr = document.getElementById('modalQrCanvas');
        if (modalQr && qrToken) {
            new QRCode(modalQr, {
                text: qrToken,
                width: 210,
                height: 210,
                colorDark: "#0f172a",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        }

        // 3. Passenger Delay Chips Interactive Selector
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

        // 4. Section B Live Calculator
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

        // 5. GPS Locator for Incident Reporting
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
    });
    </script>
</body>
</html>
