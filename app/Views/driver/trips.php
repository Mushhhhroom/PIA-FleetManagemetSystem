<?php
/**
 * Driver Mobile Web Portal View
 *
 * Designed for field drivers with high-contrast touch targets,
 * 4-phase trip lifecycle automation (Compound Egress -> Destination Arrival ->
 * Destination Departure -> Compound Ingress -> Section B Completion),
 * interactive Leaflet road navigation, and zero duplicate navigation tabs.
 *
 * @var array<string, mixed>|null  $driver
 * @var array<string, mixed>|null  $vehicle
 * @var list<array<string, mixed>> $tripTickets
 * @var array<string, mixed>|null  $activeTicket
 * @var list<array<string, mixed>> $rfidCards
 * @var bool                       $blowbagetsPassed
 * @var array<string, string>      $checklistItems
 * @var list<array<string, mixed>> $recentDelays
 * @var list<array<string, mixed>> $recentFuelLogs
 * @var list<array<string, mixed>> $recentIncidents
 * @var list<array<string, mixed>> $allDrivers
 */
$driver           = $driver ?? null;
$vehicle          = $vehicle ?? null;
$tripTickets      = $tripTickets ?? [];
$activeTicket     = $activeTicket ?? null;
$rfidCards        = $rfidCards ?? [];
$blowbagetsPassed = $blowbagetsPassed ?? false;
$checklistItems   = $checklistItems ?? [];
$recentDelays     = $recentDelays ?? [];
$recentFuelLogs   = $recentFuelLogs ?? [];
$recentIncidents  = $recentIncidents ?? [];
$allDrivers       = $allDrivers ?? [];

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="theme-color" content="#ffffff">
    <title><?= esc(($driver['first_name'] ?? 'Driver') . ' ' . ($driver['last_name'] ?? '')) ?> | PIA-AFMS Driver Web Portal</title>

    <!-- Google Fonts: Inter & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 & FontAwesome 6 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <!-- Leaflet 1.9.4 Mapping Engine -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

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

            --brand-primary: #0f172a;
            --brand-accent: #2563eb;
            --brand-light: #eff6ff;

            --success: #059669;
            --success-light: #ecfdf5;
            --warning: #d97706;
            --warning-light: #fffbeb;
            --danger: #dc2626;
            --danger-light: #fef2f2;

            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 10px;
            --radius-pill: 9999px;

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
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100%;
            height: auto;
            overflow-x: hidden;
            overflow-y: visible;
            padding-bottom: calc(85px + var(--safe-bottom));
            margin: 0;
            -webkit-font-smoothing: antialiased;
            letter-spacing: -0.01em;
        }

        .mono {
            font-family: 'JetBrains Mono', monospace;
            letter-spacing: -0.02em;
        }

        /* Header App Bar */
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

        /* Cards & Surfaces */
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

        /* 4-Phase Stepper Component */
        .phase-stepper-box {
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 12px 8px;
            margin-bottom: 14px;
        }
        .phase-stepper {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            position: relative;
            margin: 0;
            padding: 0 4px;
        }
        .phase-stepper::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 24px;
            right: 24px;
            height: 2px;
            background: #e2e8f0;
            z-index: 0;
        }
        .phase-step {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            flex: 1;
            padding: 0 2px;
        }
        .phase-dot {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #ffffff;
            border: 2px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.72rem;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 5px;
            transition: all 0.2s ease;
        }
        .phase-step.completed .phase-dot {
            background: #059669;
            border-color: #059669;
            color: #ffffff;
        }
        .phase-step.active .phase-dot {
            background: #2563eb;
            border-color: #2563eb;
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25);
            animation: pulse-ring 2s infinite;
        }
        .phase-label {
            font-size: 0.62rem;
            font-weight: 600;
            color: #64748b;
            line-height: 1.15;
        }
        .phase-step.active .phase-label {
            color: #2563eb;
            font-weight: 700;
        }
        .phase-step.completed .phase-label {
            color: #059669;
        }
        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.5); }
            70% { box-shadow: 0 0 0 8px rgba(37, 99, 235, 0); }
            100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
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

        /* QR Gate Pass Container */
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

        /* Quick Action Row */
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

        /* Action Buttons */
        .btn-driver-primary {
            background: var(--brand-accent);
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

        /* Tab Views */
        .tab-panel-view {
            display: none;
            height: auto !important;
            overflow: visible !important;
        }
        .tab-panel-view.active {
            display: block !important;
        }

        /* Ergonomic Thumb Navigation Dock */
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
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.04);
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
            font-size: 0.72rem;
            font-weight: 600;
            padding: 0;
            position: relative;
            cursor: pointer;
            transition: color 0.15s ease;
        }
        .thumb-nav-btn.active {
            color: var(--brand-accent);
        }
        .thumb-nav-btn i {
            font-size: 1.2rem;
        }
        .nav-alert-indicator {
            position: absolute;
            top: 8px;
            right: calc(50% - 16px);
            width: 8px;
            height: 8px;
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

        /* Modal Scrollability Fixes */
        .modal {
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch;
        }
        .modal-body {
            max-height: calc(82vh - 120px);
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch;
        }
    </style>
</head>
<body>

    <!-- -------------------------------------------------------------
         APP HEADER (Sticky)
    -------------------------------------------------------------- -->
    <header class="driver-header">
        <div class="d-flex align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2" style="min-width: 0; flex: 1;">
                <div class="driver-avatar flex-shrink-0">
                    <?= esc(strtoupper(substr($driver['first_name'] ?? 'D', 0, 1) . substr($driver['last_name'] ?? 'R', 0, 1))) ?>
                </div>
                <div style="min-width: 0;">
                    <h6 class="fw-bold mb-0 text-truncate" style="font-size: 0.92rem;">
                        <?= esc(($driver['first_name'] ?? 'Driver') . ' ' . ($driver['last_name'] ?? '')) ?>
                    </h6>
                    <div class="small text-muted mono text-truncate" style="font-size: 0.7rem;">
                        <?= esc($driver['driver_code'] ?? 'DRV-101') ?> &bull; PIA Motorpool
                    </div>
                </div>
            </div>

            <!-- Duty Status & Profile Switcher -->
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <?php $dutyState = $driver['status'] ?? 'available'; ?>
                <button type="button" class="duty-badge duty-<?= esc($dutyState) ?>" data-bs-toggle="modal" data-bs-target="#dutyModal" title="Change Duty Status" style="white-space: nowrap;">
                    <span class="status-dot"></span>
                    <span><?= ucfirst(str_replace('_', ' ', $dutyState)) ?></span>
                </button>

                <!-- More Options Menu -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border p-1 rounded-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 32px; height: 32px;">
                        <i class="fa-solid fa-ellipsis-vertical text-secondary"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border mt-1" style="min-width: 220px; font-size: 0.85rem;">
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
                            <li class="px-3 py-1 text-muted small text-uppercase mono" style="font-size: 0.65rem;">Switch Driver Profile (Admin)</li>
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
         MAIN CONTENT AREA (Scrollable)
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

        <!-- Quick Action Tap Row -->
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

            <!-- 3. Passenger Delay Logger -->
            <?php if ($activeTicket): ?>
                <button type="button" class="quick-btn" data-bs-toggle="modal" data-bs-target="#delayModal">
                    <i class="fa-solid fa-user-clock quick-btn-icon" style="color: #d97706;"></i>
                    <span>Log Delay</span>
                </button>
            <?php else: ?>
                <button type="button" class="quick-btn" data-view="view-history">
                    <i class="fa-solid fa-ticket quick-btn-icon"></i>
                    <span>Tickets</span>
                </button>
            <?php endif; ?>

            <!-- 4. Emergency SOS Breakdown -->
            <button type="button" class="quick-btn danger-btn" data-bs-toggle="modal" data-bs-target="#incidentModal">
                <i class="fa-solid fa-triangle-exclamation quick-btn-icon"></i>
                <span class="text-danger">SOS Alert</span>
            </button>
        </div>

        <!-- =========================================================
             VIEW 1: ACTIVE TRIP (Hero 4-Phase Operational Console)
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
                                'issued'        => ['label' => 'Gate Departure (Phase 1)', 'bg' => '#fef3c7', 'color' => '#92400e', 'icon' => 'fa-id-badge'],
                                'departed'      => ['label' => 'En Route / On Trip', 'bg' => '#dbeafe', 'color' => '#1e40af', 'icon' => 'fa-car-side'],
                                'arrived_dest'  => ['label' => 'Arrived at Destination (Phase 2)', 'bg' => '#ccfbf1', 'color' => '#0f766e', 'icon' => 'fa-location-dot'],
                                'departed_dest' => ['label' => 'Returning to Compound (Phase 3)', 'bg' => '#e0e7ff', 'color' => '#3730a3', 'icon' => 'fa-flag-checkered'],
                                'returned'      => ['label' => 'Returned &bull; Complete Section B', 'bg' => '#fed7aa', 'color' => '#9a3412', 'icon' => 'fa-clipboard-check'],
                                'completed'     => ['label' => 'Completed & Audited', 'bg' => '#d1fae5', 'color' => '#065f46', 'icon' => 'fa-circle-check'],
                                default         => ['label' => ucfirst($activeTicket['status']), 'bg' => '#f1f5f9', 'color' => '#475569', 'icon' => 'fa-circle-dot'],
                            };
                        ?>
                        <span class="badge px-2 py-1" style="background: <?= $statusInfo['bg'] ?>; color: <?= $statusInfo['color'] ?>; font-size: 0.72rem; font-weight: 600;">
                            <i class="fa-solid <?= $statusInfo['icon'] ?> me-1"></i><?= $statusInfo['label'] ?>
                        </span>
                    </div>

                    <!-- -------------------------------------------------------------
                         4-PHASE INTERACTIVE WORKFLOW STEPPER (DTT AUTOMATION)
                    -------------------------------------------------------------- -->
                    <div class="phase-stepper-box">
                        <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                            <span class="small fw-bold text-uppercase mono text-secondary" style="font-size: 0.65rem;">
                                <i class="fa-solid fa-route text-primary me-1"></i> 4-Phase Trip Lifecycle
                            </span>
                            <span class="badge bg-light text-muted border mono" style="font-size: 0.62rem;">DTT ADMIN-F-001</span>
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
                                <span class="phase-label">Destination Arrival</span>
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
                                <span class="phase-label">Depart Destination</span>
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
                                <span class="phase-label">Compound Ingress</span>
                            </div>
                        </div>
                    </div>

                    <!-- -------------------------------------------------------------
                         AUTOMATED NOTIFICATIONS & POP-UP ALERTS (PHASES 1-4 & SECTION B)
                    -------------------------------------------------------------- -->

                    <!-- PHASE 2 NOTIFICATION: Arrival at Event / Location (Agree / Disagree) -->
                    <?php if ($status === 'departed'): ?>
                        <div class="alert p-3 rounded-3 shadow-sm border-0 mb-3" id="arrivalPromptCard" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 5px solid #2563eb !important;">
                            <div class="d-flex align-items-start gap-2">
                                <i class="fa-solid fa-location-dot fs-3 text-primary mt-1"></i>
                                <div class="flex-grow-1">
                                    <div class="badge bg-primary text-white mono mb-1" style="font-size: 0.65rem;">PHASE 2 &bull; DESTINATION ARRIVAL</div>
                                    <h6 class="fw-bold text-dark mb-1" style="font-size: 0.95rem;">Have you arrived at the official event or location?</h6>
                                    <p class="text-secondary small mb-2" style="font-size: 0.78rem;">
                                        Authorized Destination: <strong><?= esc($activeTicket['authorized_destination']) ?></strong>
                                    </p>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary btn-sm fw-bold px-3 py-2 flex-grow-1" data-bs-toggle="modal" data-bs-target="#arriveModal" id="btnArriveAgree">
                                            <i class="fa-solid fa-circle-check me-1"></i> Agree (Arrived)
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm fw-semibold px-3 py-2 flex-grow-1 bg-white" id="btnArriveDisagree">
                                            <i class="fa-solid fa-circle-xmark me-1"></i> Disagree (En Route)
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- PHASE 3 POP-UP PROMPT: Departure from Destination -->
                    <?php if ($status === 'arrived_dest'): ?>
                        <div class="alert p-3 rounded-3 shadow-sm border-0 mb-3" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-left: 5px solid #d97706 !important;">
                            <div class="d-flex align-items-start gap-2">
                                <i class="fa-solid fa-flag-checkered fs-3 text-warning mt-1"></i>
                                <div class="flex-grow-1">
                                    <div class="badge bg-warning text-dark mono mb-1" style="font-size: 0.65rem;">PHASE 3 &bull; DEPARTURE FROM DESTINATION</div>
                                    <h6 class="fw-bold text-dark mb-1" style="font-size: 0.95rem;">Depart from Destination back to PIA Compound?</h6>
                                    <p class="text-secondary small mb-2" style="font-size: 0.78rem;">
                                        Arrived at: <strong><?= !empty($activeTicket['arrival_dest_time']) ? date('M d, h:i A', strtotime($activeTicket['arrival_dest_time'])) : 'Destination' ?></strong> &bull; Ready to return?
                                    </p>
                                    <button type="button" class="btn btn-warning fw-bold text-dark btn-sm px-3 py-2 w-100 shadow-sm" data-bs-toggle="modal" data-bs-target="#departModal">
                                        <i class="fa-solid fa-arrow-left me-1"></i> Confirm Departure from Destination
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- PHASE 4 GATE PROMPT: Return Ingress at Compound -->
                    <?php if ($status === 'departed_dest'): ?>
                        <div class="alert p-3 rounded-3 shadow-sm border-0 mb-3" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 5px solid #059669 !important;">
                            <div class="d-flex align-items-start gap-2">
                                <i class="fa-solid fa-shield-check fs-3 text-success mt-1"></i>
                                <div class="flex-grow-1">
                                    <div class="badge bg-success text-white mono mb-1" style="font-size: 0.65rem;">PHASE 4 &bull; COMPOUND GATE RETURN</div>
                                    <h6 class="fw-bold text-dark mb-1" style="font-size: 0.95rem;">Arrival Back at Compound Checkpoint</h6>
                                    <p class="text-secondary small mb-2" style="font-size: 0.78rem;">
                                        You are en route back. Please present or scan your generated QR Code at Gate #1 to record official Ingress return and odometer.
                                    </p>
                                    <button type="button" class="btn btn-success fw-bold text-white btn-sm px-3 py-2 w-100 shadow-sm" data-bs-toggle="modal" data-bs-target="#qrPassModal">
                                        <i class="fa-solid fa-qrcode me-1"></i> Show Ingress QR Code for Guard
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- SECTION B COMPLETION PROMPT (When Returned) -->
                    <?php if ($status === 'returned'): ?>
                        <div class="alert p-3 rounded-3 shadow-sm border-0 mb-3" style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); border-left: 5px solid #ea580c !important;">
                            <div class="d-flex align-items-start gap-2">
                                <i class="fa-solid fa-clipboard-check fs-3 text-warning mt-1"></i>
                                <div class="flex-grow-1">
                                    <div class="badge bg-dark text-white mono mb-1" style="font-size: 0.65rem;">SECTION B POST-TRIP AUDIT</div>
                                    <h6 class="fw-bold text-dark mb-1" style="font-size: 0.95rem;">Compound Return Verified! Complete Section B</h6>
                                    <p class="text-secondary small mb-2" style="font-size: 0.78rem;">
                                        Ingress stamped at <strong><?= !empty($activeTicket['arrival_back_time']) ? date('M d, h:i A', strtotime($activeTicket['arrival_back_time'])) : 'Gate #1' ?></strong> (Return Odo: <?= number_format((float)($activeTicket['return_odometer'] ?? 0), 1) ?> KM). Complete Section B to reconcile fuel and finalize this ticket.
                                    </p>
                                    <button type="button" class="btn btn-warning fw-bold text-dark btn-sm px-3 py-2 w-100 shadow-sm" data-bs-toggle="modal" data-bs-target="#sectionBModal">
                                        <i class="fa-solid fa-file-signature me-1"></i> Complete Section B Post-Trip Report
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Destination & Live Road Navigation Map -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small text-muted text-uppercase fw-semibold" style="font-size: 0.68rem;">Authorized Destination &amp; Navigation</span>
                            <div class="d-flex gap-1 align-items-center">
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 mono" style="font-size: 0.65rem;"><i class="fa-solid fa-map-location-dot me-1"></i>Google Maps</span>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 mono" id="driverNavDistance" style="font-size: 0.68rem;">-- km</span>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 mono" id="driverNavEta" style="font-size: 0.68rem;">-- min</span>
                            </div>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">
                            <?= esc($activeTicket['authorized_destination']) ?>
                        </h5>
                        <?php if (!empty($activeTicket['authorized_purpose'])): ?>
                            <p class="small text-secondary mb-2"><?= esc($activeTicket['authorized_purpose']) ?></p>
                        <?php endif; ?>

                        <!-- Embedded Interactive OSRM Road Navigation Map -->
                        <div class="rounded-3 border overflow-hidden mb-2" style="background: #f1f5f9;">
                            <div id="driverRouteMap" style="height: 250px; min-height: 240px; width: 100%; z-index: 1;"></div>
                            <div class="p-2 bg-white border-top d-flex justify-content-between align-items-center flex-wrap gap-1">
                                <div class="small text-truncate" id="driverNextManeuver" style="font-size: 0.72rem; max-width: 65%; color: #0f172a;">
                                    <i class="fa-solid fa-diamond-turn-right text-primary me-1"></i> Computing road-snapped route...
                                </div>
                                <div class="d-flex gap-1">
                                    <button type="button" id="btnDriverLiveGps" class="btn btn-xs btn-outline-success py-1 px-2" style="font-size: 0.68rem; font-weight: 600;">
                                        <i class="fa-solid fa-location-crosshairs me-1"></i>Live GPS
                                    </button>
                                    <button class="btn btn-xs btn-outline-secondary py-1 px-2" type="button" data-bs-toggle="collapse" data-bs-target="#driverStepsCollapse" aria-expanded="false" style="font-size: 0.68rem;">
                                        <i class="fa-solid fa-list-ol me-1"></i>Steps
                                    </button>
                                </div>
                            </div>
                            <div class="collapse border-top bg-light" id="driverStepsCollapse">
                                <div id="driverTurnSteps" class="p-2 small" style="max-height: 160px; overflow-y: auto; font-size: 0.72rem;">
                                    <div class="text-muted">Loading turn-by-turn directions...</div>
                                </div>
                            </div>
                        </div>

                        <!-- External Turn-by-Turn Navigation Deep Links -->
                        <div class="d-flex gap-2">
                            <a id="btnGoogleMapsDir"
                               href="https://www.google.com/maps/dir/?api=1&amp;origin=14.657200,121.045000&amp;destination=<?= urlencode($activeTicket['authorized_destination'] . ', Philippines') ?>&amp;travelmode=driving"
                               target="_blank" rel="noopener"
                               class="btn btn-sm btn-light border flex-grow-1 fw-semibold text-primary d-flex align-items-center justify-content-center gap-1"
                               style="font-size: 0.74rem;">
                                <i class="fa-solid fa-diamond-turn-right"></i> Google Maps Directions
                            </a>
                            <a id="btnWazeNav"
                               href="https://waze.com/ul?q=<?= urlencode($activeTicket['authorized_destination']) ?>&amp;navigate=yes"
                               target="_blank" rel="noopener"
                               class="btn btn-sm btn-light border fw-semibold text-dark d-flex align-items-center justify-content-center gap-1 px-3"
                               style="font-size: 0.74rem;">
                                <i class="fa-solid fa-location-arrow text-info"></i> Waze
                            </a>
                        </div>
                    </div>

                    <!-- BLOWBAGETS Pre-Trip Safety Notice -->
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
                            <button type="button" class="btn btn-sm btn-warning text-dark fw-bold px-3 py-1" data-bs-toggle="modal" data-bs-target="#blowbagetsModal" style="font-size: 0.75rem;">
                                Inspect
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-sm btn-outline-success fw-semibold px-2 py-1" data-bs-toggle="modal" data-bs-target="#blowbagetsModal" style="font-size: 0.7rem;">
                                Re-Inspect
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Scannable Gate Pass E-QR -->
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

                    <!-- Passenger Delays Logged -->
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
                        <?php if ($status === 'issued'): ?>
                            <div class="p-3 bg-light rounded-3 text-center mb-2 border">
                                <i class="fa-solid fa-shield-halved text-primary fs-4 mb-1"></i>
                                <div class="small fw-bold text-dark">Ready for Gate Departure (Phase 1)</div>
                                <p class="text-muted small mb-0" style="font-size: 0.72rem;">
                                    Present the Gate Pass QR code above to the Guard at Gate #1 to automatically stamp your official Compound Departure date &amp; time.
                                </p>
                            </div>
                            <button type="button" class="btn-driver-outline" onclick="openMobileTicketModal(<?= (int)$activeTicket['id'] ?>)">
                                <i class="fa-solid fa-file-lines me-1"></i> View Full Ticket Document
                            </button>
                        <?php elseif ($status === 'departed'): ?>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn-driver-primary" data-bs-toggle="modal" data-bs-target="#arriveModal">
                                    <i class="fa-solid fa-location-dot me-1"></i> Confirm Arrival at Event / Destination
                                </button>
                                <button type="button" class="btn-driver-outline" data-bs-toggle="modal" data-bs-target="#delayModal">
                                    <i class="fa-solid fa-user-clock me-1"></i> Log 15-Minute Passenger Delay
                                </button>
                                <button type="button" class="btn-driver-outline" onclick="openMobileTicketModal(<?= (int)$activeTicket['id'] ?>)">
                                    <i class="fa-solid fa-file-lines me-1"></i> View Full Ticket Document
                                </button>
                            </div>
                        <?php elseif ($status === 'arrived_dest'): ?>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn-driver-primary" data-bs-toggle="modal" data-bs-target="#departModal" style="background: var(--warning); color: #000;">
                                    <i class="fa-solid fa-arrow-left me-1"></i> Confirm Departure from Destination (Phase 3)
                                </button>
                                <button type="button" class="btn-driver-outline" onclick="openMobileTicketModal(<?= (int)$activeTicket['id'] ?>)">
                                    <i class="fa-solid fa-file-lines me-1"></i> View Full Ticket Document
                                </button>
                            </div>
                        <?php elseif ($status === 'departed_dest'): ?>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn-driver-success" data-bs-toggle="modal" data-bs-target="#qrPassModal">
                                    <i class="fa-solid fa-qrcode me-1"></i> Show Ingress QR Pass for Gate Guard
                                </button>
                                <button type="button" class="btn-driver-outline" onclick="openMobileTicketModal(<?= (int)$activeTicket['id'] ?>)">
                                    <i class="fa-solid fa-file-lines me-1"></i> View Full Ticket Document
                                </button>
                            </div>
                        <?php elseif ($status === 'returned'): ?>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn-driver-success" data-bs-toggle="modal" data-bs-target="#sectionBModal">
                                    <i class="fa-solid fa-clipboard-check me-1"></i> Complete Section B Post-Trip Report
                                </button>
                                <button type="button" class="btn-driver-outline" onclick="openMobileTicketModal(<?= (int)$activeTicket['id'] ?>)">
                                    <i class="fa-solid fa-file-lines me-1"></i> View Full Ticket Document
                                </button>
                            </div>
                        <?php else: ?>
                            <button type="button" class="btn-driver-outline" onclick="openMobileTicketModal(<?= (int)$activeTicket['id'] ?>)">
                                <i class="fa-solid fa-file-check me-1"></i> View Reconciled Ticket
                            </button>
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
                        <button type="button" class="btn btn-sm btn-outline-primary px-3" onclick="switchView('view-history')">
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
                        <button type="button" class="btn-driver-outline py-2 flex-grow-1" onclick="switchView('view-vehicle')" style="min-height: 40px; font-size: 0.8rem;">
                            <i class="fa-solid fa-sliders me-1"></i> Vehicle Diagnostics &amp; Toll RFID
                        </button>
                    </div>
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
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 small">
                            <?= ucfirst(str_replace('_', ' ', $vehicle['status'] ?? 'Active')) ?>
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
                        <button type="button" class="btn-driver-outline py-2 w-100" data-bs-toggle="modal" data-bs-target="#blowbagetsModal" style="min-height: 42px;">
                            <i class="fa-solid fa-list-check text-primary me-1"></i> Perform BLOWBAGETS Pre-Trip Inspection
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Tollway RFID Cards (AutoSweep & EasyTrip) -->
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

            <!-- Fuel Logging & Recent Receipts Ledger -->
            <div class="driver-card">
                <div class="driver-card-header">
                    <span class="card-title-sm"><i class="fa-solid fa-gas-pump text-success"></i> Petroleum &amp; Fuel Purchases</span>
                    <button type="button" class="btn btn-sm btn-success fw-semibold px-2 py-1" data-bs-toggle="modal" data-bs-target="#fuelModal" style="font-size: 0.75rem;">
                        <i class="fa-solid fa-plus me-1"></i> Log Fuel
                    </button>
                </div>

                <?php if (empty($recentFuelLogs)): ?>
                    <div class="text-center py-3 text-muted small">
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
             VIEW 3: HISTORY & SUPPORT (Archives & Hotlines)
        ========================================================== -->
        <section id="view-history" class="tab-panel-view">

            <!-- Driver License & Qualifications -->
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

            <!-- Assigned Trip Tickets Archive -->
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
                                <div role="button" onclick="openMobileTicketModal(<?= (int)$tk['id'] ?>)" class="flex-grow-1" style="cursor: pointer;">
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
                                    <button type="button" onclick="openQrForTicket(<?= (int)$tk['id'] ?>)" class="btn btn-sm btn-light border py-1 px-2" title="Show QR Gate Pass">
                                        <i class="fa-solid fa-qrcode text-primary"></i>
                                    </button>
                                    <button type="button" onclick="openMobileTicketModal(<?= (int)$tk['id'] ?>)" class="btn btn-sm btn-light border py-1 px-2" title="View Mobile Ticket">
                                        <i class="fa-solid fa-eye text-secondary"></i>
                                    </button>
                                    <a href="<?= base_url('tickets/' . $tk['id'] . '/print') ?>" target="_blank" class="btn btn-sm btn-light border py-1 px-2" title="Print PDF">
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
            <span>Vehicle &amp; Safety</span>
        </button>

        <button type="button" class="thumb-nav-btn" data-view="view-history" id="dockBtnHistory">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Tickets &amp; Help</span>
        </button>
    </nav>

    <!-- =============================================================
         MODALS (Clean Minimalist Touch-Ergonomic Design)
    ============================================================== -->

    <!-- 1. FULLSCREEN GATE PASS QR MODAL -->
    <div class="modal fade" id="qrPassModal" tabindex="-1" aria-labelledby="qrPassModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
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

    <!-- 2. PHASE 2: CONFIRM DESTINATION ARRIVAL MODAL -->
    <?php if ($activeTicket): ?>
    <div class="modal fade" id="arriveModal" tabindex="-1" aria-labelledby="arriveModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <form action="<?= base_url('driver/arrive-dest') ?>" method="POST" id="arriveForm">
                    <?= !is_cli() ? csrf_field() : '' ?>
                    <input type="hidden" name="ticket_id" value="<?= (int)$activeTicket['id'] ?>">
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 mono">PHASE 2 &bull; DESTINATION ARRIVAL</span>
                            <h6 class="modal-title fw-bold text-dark mt-1" id="arriveModalLabel">Confirm Arrival at Event / Location</h6>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3">
                        <p class="text-muted small mb-3">
                            Confirming arrival automatically stamps your official destination arrival date &amp; time on Driver's Trip Ticket <strong><?= esc($activeTicket['ticket_serial_no']) ?></strong> and notifies Motorpool Dispatch.
                        </p>
                        <div class="p-2 rounded bg-light border mb-3">
                            <span class="text-muted d-block" style="font-size: 0.68rem;">Authorized Destination</span>
                            <strong class="text-dark fs-6"><?= esc($activeTicket['authorized_destination']) ?></strong>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-secondary">Current Odometer at Destination (KM)</label>
                            <input type="number" step="0.1" name="dest_odometer" class="form-control mono" value="<?= (float)($activeTicket['dest_odometer'] ?? ($vehicle['odometer_km'] ?? ($activeTicket['start_odometer'] ?? 0))) ?>" required>
                            <span class="text-muted" style="font-size: 0.68rem;">Start Odometer was <?= number_format((float)($activeTicket['start_odometer'] ?? 0), 1) ?> KM</span>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold">
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
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <span class="badge bg-warning bg-opacity-25 text-dark border border-warning mono">PHASE 3 &bull; DEPARTURE FROM DESTINATION</span>
                            <h6 class="modal-title fw-bold text-dark mt-1" id="departModalLabel">Confirm Departure from Destination</h6>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3">
                        <p class="text-muted small mb-3">
                            Stamps your departure date &amp; time from <strong><?= esc($activeTicket['authorized_destination']) ?></strong> and transitions your trip status to En Route back to PIA Compound.
                        </p>
                        <div class="p-3 rounded bg-light border mb-2 small">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="fa-solid fa-shield-halved text-success"></i>
                                <strong class="text-dark">Compound Gate Ingress (Phase 4):</strong>
                            </div>
                            <span class="text-muted">Upon returning to the compound, present your QR Code at Gate #1 to record official return arrival and return odometer.</span>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning fw-bold text-dark">
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

    <!-- 5. REFUEL & RECEIPT LOGGING MODAL -->
    <div class="modal fade" id="fuelModal" tabindex="-1" aria-labelledby="fuelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
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

    <!-- 6. REPORT INCIDENT / EMERGENCY SOS MODAL (FR-4.3) -->
    <div class="modal fade" id="incidentModal" tabindex="-1" aria-labelledby="incidentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
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
                            <label class="form-label small fw-semibold text-secondary">Description &amp; Assistance Needed</label>
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

    <!-- 7. DUTY STATUS MODAL -->
    <div class="modal fade" id="dutyModal" tabindex="-1" aria-labelledby="dutyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
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

    <!-- 8. SECTION B POST-TRIP EXECUTION & DUAL CERTIFICATION MODAL (ADMIN-F-001 rev1) -->
    <?php if ($activeTicket): ?>
    <div class="modal fade" id="sectionBModal" tabindex="-1" aria-labelledby="sectionBModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <form action="<?= base_url('tickets/' . $activeTicket['id'] . '/update') ?>" method="POST" id="sectionBForm">
                    <?= !is_cli() ? csrf_field() : '' ?>
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 mono">ADMIN-F-001 rev1</span>
                            <h6 class="modal-title fw-bold text-dark mt-1" id="sectionBModalLabel">Section B: Post-Trip &amp; Fuel Accounting Report</h6>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3">
                        <p class="text-muted small mb-3">
                            Official post-trip audit formula and dual certifications for <strong><?= esc($activeTicket['ticket_serial_no']) ?></strong>.
                        </p>

                        <!-- Gate Auto-Stamped Timestamps -->
                        <div class="fw-bold text-dark small text-uppercase mb-2 pb-1 border-bottom d-flex justify-content-between align-items-center">
                            <span><i class="fa-solid fa-clock text-primary me-1"></i> 1. Compound Gate &amp; Destination Timestamps</span>
                            <span class="badge bg-dark text-white mono" style="font-size: 0.6rem;">GATE AUTO-STAMPED</span>
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
                        <div class="p-3 rounded bg-light border mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="driver_certified" value="1" id="m_driverCert" <?= !empty($activeTicket['driver_certified']) || $status === 'returned' ? 'checked' : '' ?> required>
                                <label class="form-check-label small text-dark" for="m_driverCert">
                                    <strong>Driver's Certification:</strong> I hereby certify on official honor that the above trip route, distance, and fuel consumption are true and correct as accomplished.
                                </label>
                            </div>
                        </div>

                        <!-- Passenger Certification -->
                        <div class="p-3 rounded bg-light border mb-2">
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
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold">
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
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <span class="badge bg-warning bg-opacity-25 text-dark border border-warning mono" style="font-size: 0.68rem;">FR-5.1 &bull; BLOWBAGETS PROTOCOL</span>
                            <h6 class="modal-title fw-bold text-dark mt-1" id="blowbagetsModalLabel">10-Point Pre-Trip Safety Inspection</h6>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-3 p-2 rounded-3 bg-light border">
                            <div class="small">
                                <strong class="text-dark d-block mono"><?= esc($activeTicket['ticket_serial_no']) ?> &bull; <?= esc($vehicle['plate_number'] ?? '') ?></strong>
                                <span class="text-muted" style="font-size: 0.72rem;">All 10 points must pass for compound gate clearance</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-success fw-bold" id="btnMarkAllPassMobile" style="font-size: 0.72rem;">
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
                                <div class="p-2 rounded-3 border bg-white d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2 pe-2">
                                        <span class="badge bg-primary text-white mono d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.8rem;">
                                            <?= esc($letter) ?>
                                        </span>
                                        <div>
                                            <strong class="text-dark d-block small" style="font-size: 0.8rem;"><?= esc($title) ?></strong>
                                            <span class="text-muted d-block" style="font-size: 0.68rem; line-height: 1.2;">Inspect before compound departure</span>
                                        </div>
                                    </div>
                                    <div class="btn-group btn-group-sm flex-shrink-0" role="group">
                                        <input type="radio" class="btn-check safety-pass-radio" name="check[<?= esc($code) ?>]" id="mob_<?= esc($code) ?>_pass" value="pass" checked required>
                                        <label class="btn btn-outline-success fw-bold px-2 py-1" for="mob_<?= esc($code) ?>_pass" style="font-size: 0.72rem;">PASS</label>

                                        <input type="radio" class="btn-check safety-fail-radio" name="check[<?= esc($code) ?>]" id="mob_<?= esc($code) ?>_fail" value="fail">
                                        <label class="btn btn-outline-danger fw-bold px-2 py-1" for="mob_<?= esc($code) ?>_fail" style="font-size: 0.72rem;">FAIL</label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-secondary mb-1">Inspection Remarks / Defect Notes (Optional)</label>
                            <textarea name="remarks" rows="2" class="form-control form-control-sm" placeholder="Note any observations or leave blank if all 10 points pass..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success fw-bold">
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
                <div class="modal-header border-0 pb-0">
                    <div>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 mono" id="td_serial_badge">ADMIN-F-001 rev1</span>
                        <h6 class="modal-title fw-bold text-dark mt-1" id="ticketDetailModalLabel">Electronic Driver's Trip Ticket</h6>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="p-3 rounded-3 bg-light border mb-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-muted" style="font-size: 0.68rem;">Official Serial Number</div>
                            <strong class="mono text-dark fs-6" id="td_serial_no">DTT-0000</strong>
                        </div>
                        <span class="badge bg-primary" id="td_status_badge">Issued</span>
                    </div>

                    <div class="fw-bold text-dark small text-uppercase mb-2 pb-1 border-bottom">
                        <i class="fa-solid fa-file-signature text-primary me-1"></i> Section A: Dispatch Authorization
                    </div>
                    <div class="row g-2 small mb-3">
                        <div class="col-6">
                            <span class="text-muted d-block" style="font-size: 0.68rem;">Assigned Vehicle</span>
                            <strong class="mono text-dark" id="td_vehicle">-</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block" style="font-size: 0.68rem;">Assigned Driver</span>
                            <strong class="text-dark" id="td_driver"><?= esc(($driver['first_name'] ?? '') . ' ' . ($driver['last_name'] ?? '')) ?></strong>
                        </div>
                        <div class="col-12 mt-2">
                            <span class="text-muted d-block" style="font-size: 0.68rem;">Authorized Destination</span>
                            <strong class="text-dark" id="td_destination">-</strong>
                        </div>
                        <div class="col-12 mt-1">
                            <span class="text-muted d-block" style="font-size: 0.68rem;">Official Purpose</span>
                            <span class="text-secondary" id="td_purpose">-</span>
                        </div>
                        <div class="col-12 mt-1">
                            <span class="text-muted d-block" style="font-size: 0.68rem;">Authorized Passengers</span>
                            <span class="text-dark" id="td_passengers">-</span>
                        </div>
                    </div>

                    <div class="fw-bold text-dark small text-uppercase mb-2 pb-1 border-bottom d-flex justify-content-between align-items-center">
                        <span><i class="fa-solid fa-shield-halved text-primary me-1"></i> Section B: Compound Gate &amp; Trip Audit</span>
                        <span class="badge bg-dark text-white mono" style="font-size: 0.6rem;">GATE AUTO-STAMPED</span>
                    </div>
                    <div class="row g-2 small mb-3">
                        <div class="col-6">
                            <div class="p-2 rounded bg-light border">
                                <span class="text-muted d-block" style="font-size: 0.65rem;">Compound Departure (Gate Egress)</span>
                                <strong class="mono text-dark" id="td_dep_time">Pending Gate Scan</strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light border">
                                <span class="text-muted d-block" style="font-size: 0.65rem;">Compound Arrival (Gate Ingress)</span>
                                <strong class="mono text-dark" id="td_arr_time">Pending Gate Scan</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded bg-light border text-center">
                                <span class="text-muted d-block" style="font-size: 0.65rem;">Start Odo</span>
                                <strong class="mono text-dark" id="td_start_odo">0.0 KM</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded bg-light border text-center">
                                <span class="text-muted d-block" style="font-size: 0.65rem;">Return Odo</span>
                                <strong class="mono text-dark" id="td_return_odo">0.0 KM</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded bg-light border text-center">
                                <span class="text-muted d-block" style="font-size: 0.65rem;">Distance</span>
                                <strong class="mono text-primary" id="td_total_dist">0.0 KM</strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light border">
                                <span class="text-muted d-block" style="font-size: 0.65rem;">Fuel Used / Ending Balance</span>
                                <strong class="mono text-success" id="td_fuel_summary">0.00 L</strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light border">
                                <span class="text-muted d-block" style="font-size: 0.65rem;">Tollways &amp; RFID</span>
                                <strong class="mono text-dark" id="td_toll_summary">₱0.00</strong>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-between">
                    <a href="#" target="_blank" id="td_print_btn" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-print me-1"></i> Print Official DTT
                    </a>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm fw-bold" id="td_qr_btn">
                            <i class="fa-solid fa-qrcode me-1"></i> Gate Pass QR
                        </button>
                        <button type="button" class="btn btn-light btn-sm border" data-bs-dismiss="modal">Close</button>
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
                width: 140,
                height: 140,
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
                            <span class="small text-secondary"><i class="fa-solid fa-car-side text-primary me-1"></i> Trip in transit. Have you reached destination?</span>
                            <button type="button" class="btn btn-sm btn-primary py-1 px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#arriveModal">
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
                        html: `<div style="background:#2563eb;color:#fff;border-radius:50%;width:30px;height:30px;display:flex;align-items:center;justify-content:center;border:2.5px solid #fff;box-shadow:0 3px 8px rgba(37,99,235,0.45);font-size:13px;">
                                <i class="fa-solid fa-car-side"></i>
                               </div>`,
                        iconSize: [30, 30],
                        iconAnchor: [15, 15]
                    });
                    driverMarker = L.marker([curLat, curLng], { icon: carIcon })
                        .bindPopup('<strong>Current Vehicle Position</strong>')
                        .addTo(navLayerGroup);

                    if (data.destination && data.destination.lat && data.destination.lng) {
                        const destIcon = L.divIcon({
                            className: 'driver-nav-dest',
                            html: `<div style="background:#dc2626;color:#fff;border-radius:50%;width:30px;height:30px;display:flex;align-items:center;justify-content:center;border:2.5px solid #fff;box-shadow:0 3px 8px rgba(220,38,38,0.45);font-size:13px;">
                                    <i class="fa-solid fa-flag-checkered"></i>
                                   </div>`,
                            iconSize: [30, 30],
                            iconAnchor: [15, 15]
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
                                <div class="d-flex justify-content-between align-items-center py-1 ${i < data.steps.length - 1 ? 'border-bottom' : ''}">
                                    <span>${getManeuverIcon(st.instruction)}<strong class="mono text-dark me-1">${i + 1}.</strong> ${st.instruction}</span>
                                    <span class="mono text-muted ms-2" style="font-size:0.65rem;white-space:nowrap;">${st.distance_m >= 1000 ? (st.distance_m / 1000).toFixed(1) + ' km' : st.distance_m + ' m'}</span>
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
                        btnLiveGps.className = 'btn btn-xs btn-outline-success py-1 px-2';
                        btnLiveGps.innerHTML = '<i class="fa-solid fa-location-crosshairs me-1"></i>Live GPS';
                        return;
                    }

                    btnLiveGps.className = 'btn btn-xs btn-success text-white py-1 px-2';
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
                            btnLiveGps.className = 'btn btn-xs btn-outline-danger py-1 px-2';
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
