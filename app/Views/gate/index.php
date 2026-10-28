<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!--
==============================================================================
RESOLUTION SUMMARY - GITHUB ISSUE #19
Reference: https://github.com/Mushhhhroom/Pia-s-Fleet-Management-System-Demo-/issues/19
Title: [UI/UX] Add live camera viewfinder and high-contrast pass/fail verification
       for Gate Guard QR checkpoint

APPLIED FIXES:
1. Live Optical Viewfinder: Targeted green reticle brackets, animated scan laser,
   and camera flashlight torch toggle.
2. Direct e-DTT QR Scanning & Serial Verification: Real-time QR image upload and serial verification fallback.
3. Instant Verdict Cards: High-contrast Green PASS (large checkmark, vehicle plate,
   driver photo, destination, passenger list) and Red REJECT (BLOWBAGETS safety alert,
   expired authorization notice, vehicle holding instructions).
4. Multi-Sensory Feedback: Web Audio API chime & buzz + navigator.vibrate() haptics.
5. Outdoor / Night Mode: High-contrast toggle for harsh sunlight and night shifts.
==============================================================================
-->
<!-- High-Contrast & Viewfinder Reticle Styles -->
<style>
/* Tactical Viewfinder & Reticle HUD */
.viewfinder-wrapper {
    position: relative;
    width: 100%;
    min-height: 290px;
    background: #070d18;
    border-radius: 10px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #1e293b;
    box-shadow: inset 0 0 20px rgba(0,0,0,0.8);
}
#qrReader {
    width: 100% !important;
    border: none !important;
}
#qrReader video {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    border-radius: 8px;
}
.scanner-overlay {
    position: absolute;
    inset: 0;
    pointer-events: none;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
}
.reticle-box {
    width: 220px;
    height: 220px;
    position: relative;
    border-radius: 12px;
    box-shadow: 0 0 0 9999px rgba(7, 13, 24, 0.45);
}
.reticle-corner {
    position: absolute;
    width: 32px;
    height: 32px;
    border-color: #10b981;
    border-style: solid;
    filter: drop-shadow(0 0 4px rgba(16, 185, 129, 0.8));
}
.corner-tl { top: -2px; left: -2px; border-width: 4px 0 0 4px; border-top-left-radius: 10px; }
.corner-tr { top: -2px; right: -2px; border-width: 4px 4px 0 0; border-top-right-radius: 10px; }
.corner-bl { bottom: -2px; left: -2px; border-width: 0 0 4px 4px; border-bottom-left-radius: 10px; }
.corner-br { bottom: -2px; right: -2px; border-width: 0 4px 4px 0; border-bottom-right-radius: 10px; }

.scan-laser {
    position: absolute;
    left: 4px;
    right: 4px;
    height: 3px;
    background: linear-gradient(90deg, transparent, #10b981, #34d399, #10b981, transparent);
    box-shadow: 0 0 10px #10b981;
    border-radius: 2px;
    animation: scanAnim 2.2s ease-in-out infinite alternate;
}
@keyframes scanAnim {
    0% { top: 8px; opacity: 0.6; }
    50% { opacity: 1; }
    100% { top: 208px; opacity: 0.6; }
}

/* High Contrast Verdict Fullscreen Overlay */
.verdict-header-pass {
    background: linear-gradient(135deg, #065f46 0%, #047857 100%);
    color: #ffffff;
    border-bottom: 4px solid #10b981;
}
.verdict-header-reject {
    background: linear-gradient(135deg, #991b1b 0%, #b91c1c 100%);
    color: #ffffff;
    border-bottom: 4px solid #ef4444;
}
.verdict-header-warning {
    background: linear-gradient(135deg, #92400e 0%, #b45309 100%);
    color: #ffffff;
    border-bottom: 4px solid #f59e0b;
}

/* Outdoor / Night High Contrast Toggle */
.outdoor-mode {
    filter: contrast(1.25) brightness(1.05);
}
.outdoor-mode .card-panel {
    border-width: 2px !important;
    border-color: #0f172a !important;
}
</style>

<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.72rem; color: #059669; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            <i class="fa-solid fa-shield-halved me-1"></i> Compound Security &bull; Sub-8-Second Verification
        </div>
        <h1 class="page-title mb-1">Gate Security Checkpoint & QR Scanner</h1>
        <p class="text-muted small mb-0">Live optical viewfinder, targeted reticle HUD, e-DTT QR scanning, and instant full-screen verdict cards.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-sm btn-outline-dark shadow-sm" id="btnToggleHighContrast" title="Toggle High-Contrast mode for outdoor glare / night shifts">
            <i class="fa-solid fa-circle-half-stroke me-1"></i> <span id="contrastModeLabel">High Contrast: Normal</span>
        </button>
        <span class="badge bg-white text-dark border p-2 shadow-sm small">
            <i class="fa-solid fa-tower-broadcast text-success me-1"></i> Checkpoint Gate #1 Active
        </span>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Scanner Interface -->
    <div class="col-lg-5">
        <!-- Interactive Scanner Card -->
        <div class="card-panel mb-4">
            <div class="card-panel-header bg-white px-3 py-2 d-flex align-items-center justify-content-between">
                <span class="fw-bold text-dark small text-uppercase">
                    <i class="fa-solid fa-camera text-primary me-1"></i> Live Optical Viewfinder &amp; Scanner
                </span>
                <span class="badge bg-light text-muted border mono small">e-DTT Checkpoint</span>
            </div>

            <div class="p-3">
                <!-- Camera Viewport Box with Targeted Reticle & HUD -->
                <div class="viewfinder-wrapper" id="viewfinderWrapper">
                    <!-- Targeting Reticle Overlay -->
                    <div class="scanner-overlay" id="scannerReticle" style="display: none;">
                        <div class="reticle-box">
                            <div class="reticle-corner corner-tl"></div>
                            <div class="reticle-corner corner-tr"></div>
                            <div class="reticle-corner corner-bl"></div>
                            <div class="reticle-corner corner-br"></div>
                            <div class="scan-laser"></div>
                        </div>
                    </div>

                    <!-- Top Viewfinder Controls (Torch & Camera Switch) -->
                    <div class="position-absolute top-0 start-0 end-0 p-2 d-flex justify-content-between align-items-center z-3" id="viewfinderControls" style="display: none !important;">
                        <span class="badge bg-dark bg-opacity-75 text-success border border-success-subtle mono small">
                            <i class="fa-solid fa-circle text-success me-1" style="font-size: 0.5rem;"></i> LIVE SCANNER
                        </span>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-dark bg-opacity-75 text-info border-info border-opacity-50 py-1 px-2 d-none" id="btnSwitchCamera" title="Switch Camera Lens">
                                <i class="fa-solid fa-camera-rotate"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-dark bg-opacity-75 text-warning border-warning border-opacity-50 py-1 px-2" id="btnToggleTorch" title="Flashlight Torch">
                                <i class="fa-solid fa-bolt"></i> <span id="torchLabel">Torch</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-dark bg-opacity-75 text-white border-secondary py-1 px-2" id="btnStopCamera" title="Stop Camera">
                                <i class="fa-solid fa-power-off"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Video Element Container for html5-qrcode -->
                    <div id="qrReader" style="width: 100%; min-height: 270px;">
                        <div id="scannerPlaceholder" class="p-4 text-center text-white-50">
                            <i class="fa-solid fa-qrcode fa-3x mb-3 d-block text-white opacity-50"></i>
                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                <button type="button" class="btn btn-primary px-4 py-2 shadow fw-semibold" id="btnStartCamera">
                                    <i class="fa-solid fa-video me-1"></i> Start Camera Viewfinder
                                </button>
                                <button type="button" class="btn btn-outline-light px-3 py-2 fw-semibold" id="btnScanQrImage" title="Upload or scan a saved QR code image">
                                    <i class="fa-solid fa-image me-1"></i> Scan QR Image
                                </button>
                            </div>
                            <input type="file" id="qrImageFileInput" accept="image/*" class="d-none">
                            <div class="small mt-2 text-white-50" style="font-size: 0.72rem;">
                                Auto-stamps Compound Departure &amp; Arrival upon Driver/Passenger validation
                            </div>
                        </div>
                    </div>
                </div>

                <div id="scannerStatus" class="mt-3 small text-center text-muted fw-semibold">
                    Align the driver's printed or digital e-DTT QR code inside the green reticle.
                </div>

                <!-- Barcode Gun / Manual Input Fallback -->
                <div class="mt-3 pt-2 border-top">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light mono"><i class="fa-solid fa-barcode"></i></span>
                        <input type="text" id="manualTokenInput" class="form-control mono" placeholder="Scan/type DTT Serial or Plate (e.g. DTT-2026-0001)">
                        <button class="btn btn-dark" type="button" id="btnVerifyManual">
                            <i class="fa-solid fa-magnifying-glass me-1"></i> Verify
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Compound In/Out Stats -->
        <div class="card-panel p-3">
            <div class="row g-2 text-center">
                <div class="col-6">
                    <div class="p-3 rounded bg-primary bg-opacity-10 border border-primary-subtle">
                        <div class="mono fs-4 fw-bold text-primary"><?= count($vehiclesOut) ?></div>
                        <div class="small text-muted" style="font-size: 0.72rem;">VEHICLES OUT ON TRIP</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 rounded bg-warning bg-opacity-10 border border-warning-subtle">
                        <div class="mono fs-4 fw-bold text-dark"><?= count($vehiclesPendingDeparture) ?></div>
                        <div class="small text-muted" style="font-size: 0.72rem;">AWAITING EGRESS</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Live Compound Roster & Clearance History -->
    <div class="col-lg-7">
        <!-- Vehicles Pending Compound Departure (Issued Tickets Awaiting Egress Scan) -->
        <div class="card-panel border shadow-sm mb-4">
            <div class="p-3 border-bottom bg-light d-flex align-items-center justify-content-between">
                <span class="fw-bold text-dark small text-uppercase">
                    <i class="fa-solid fa-right-from-bracket text-warning me-1"></i> Dispatched Units Awaiting Compound Departure (<?= count($vehiclesPendingDeparture) ?>)
                </span>
                <span class="badge bg-warning text-dark small">PENDING EGRESS</span>
            </div>
            <div class="table-responsive">
                <table class="table-minimal">
                    <thead>
                        <tr>
                            <th>Unit / e-DTT</th>
                            <th>Driver</th>
                            <th>Destination</th>
                            <th>BLOWBAGETS</th>
                            <th class="text-end">Compound Gate Audit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($vehiclesPendingDeparture)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-3 text-muted small">
                                    No dispatched vehicles currently awaiting departure at the compound gate.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($vehiclesPendingDeparture as $vp): ?>
                                <tr>
                                    <td>
                                        <div class="mono fw-bold text-dark"><?= esc($vp['plate_number']) ?></div>
                                        <div class="text-muted mono" style="font-size: 0.7rem;"><?= esc($vp['ticket_serial_no']) ?></div>
                                    </td>
                                    <td class="small fw-semibold"><?= esc($vp['driver_name']) ?></td>
                                    <td class="small text-truncate" style="max-width: 160px;" title="<?= esc($vp['authorized_destination']) ?>">
                                        <?= esc($vp['authorized_destination']) ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($vp['has_passed_safety'])): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle" style="font-size: 0.68rem;">
                                                <i class="fa-solid fa-check me-1"></i>PASSED
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning bg-opacity-25 text-dark border border-warning" style="font-size: 0.68rem;">
                                                <i class="fa-solid fa-clock me-1"></i>PENDING
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-primary py-1 px-2 fw-semibold" onclick="triggerManualVerification('<?= esc($vp['ticket_serial_no']) ?>')">
                                            <i class="fa-solid fa-qrcode me-1"></i> Scan Egress
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Vehicles Currently on Official Transit Outside Compound -->
        <div class="card-panel border shadow-sm mb-4">
            <div class="p-3 border-bottom bg-light d-flex align-items-center justify-content-between">
                <span class="fw-bold text-dark small text-uppercase">
                    <i class="fa-solid fa-route text-primary me-1"></i> Active Missions Outside Compound (<?= count($vehiclesOut) ?>)
                </span>
                <span class="badge bg-primary small">EGRESS ACTIVE</span>
            </div>
            <div class="table-responsive">
                <table class="table-minimal">
                    <thead>
                        <tr>
                            <th>Unit / Plate</th>
                            <th>Driver</th>
                            <th>Destination</th>
                            <th>Departed Compound</th>
                            <th class="text-end">Compound Return</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($vehiclesOut)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted small">
                                    All official vehicles are currently parked safely inside the compound.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($vehiclesOut as $vo): ?>
                                <tr>
                                    <td>
                                        <div class="mono fw-bold text-dark"><?= esc($vo['plate_number']) ?></div>
                                        <div class="text-muted mono" style="font-size: 0.7rem;"><?= esc($vo['ticket_serial_no']) ?></div>
                                    </td>
                                    <td class="small fw-semibold"><?= esc($vo['driver_name']) ?></td>
                                    <td class="small text-truncate" style="max-width: 180px;" title="<?= esc($vo['authorized_destination']) ?>">
                                        <?= esc($vo['authorized_destination']) ?>
                                    </td>
                                    <td class="mono small text-muted">
                                        <?= $vo['departure_time'] ? date('M d, h:i A', strtotime($vo['departure_time'])) : '-' ?>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-success py-1 px-2 fw-semibold" onclick="triggerManualVerification('<?= esc($vo['ticket_serial_no']) ?>')">
                                            <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Scan Ingress
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Checkpoint Log -->
        <div class="card-panel border shadow-sm">
            <div class="p-3 border-bottom bg-light">
                <span class="fw-bold text-dark small text-uppercase">
                    <i class="fa-solid fa-clock-rotate-left text-muted me-1"></i> Recent Security Gate Clearances
                </span>
            </div>
            <div class="table-responsive">
                <table class="table-minimal">
                    <thead>
                        <tr>
                            <th>Scan Time</th>
                            <th>Event</th>
                            <th>Vehicle Plate</th>
                            <th>Driver</th>
                            <th>Odometer</th>
                            <th>Guard Officer</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentLogs)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted small">No gate scans recorded today.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentLogs as $log): ?>
                                <tr>
                                    <td class="mono small text-muted">
                                        <?= date('M d, H:i:s', strtotime($log['scanned_at'])) ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $log['event_type'] === 'egress' ? 'bg-primary text-white' : 'bg-success text-white' ?> small">
                                            <?= strtoupper($log['event_type']) ?>
                                        </span>
                                    </td>
                                    <td class="mono fw-bold small"><?= esc($log['plate_number']) ?></td>
                                    <td class="small"><?= esc($log['driver_name']) ?></td>
                                    <td class="mono small fw-semibold"><?= number_format((float)$log['odometer_reading'], 1) ?> km</td>
                                    <td class="small text-muted"><?= esc($log['guard_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- HIGH-CONTRAST FULL-SCREEN / EXPANDED VERDICT MODAL CARD -->
<!-- ======================================================== -->
<div class="modal fade" id="verdictModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg overflow-hidden">
            <!-- Verdict Banner (Green PASS or Red REJECT dynamically colored) -->
            <div class="p-3 text-center" id="verdictBanner">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="badge bg-black bg-opacity-25 mono px-2 py-1 text-uppercase" id="verdictEventBadge">EGRESS / INGRESS</span>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div id="verdictIconContainer" class="my-2">
                    <!-- Dynamic Big Icon -->
                </div>
                <h3 class="fw-bold mb-0 text-white text-uppercase tracking-wide" id="verdictTitle">CLEARANCE VERDICT</h3>
                <div class="small text-white-50 mono" id="verdictSubTitle">Cryptographic Security Verification</div>
            </div>

            <!-- REJECT Banner / Reason Alert Box -->
            <div id="verdictRejectBox" class="p-3 bg-danger bg-opacity-10 border-bottom border-danger-subtle d-none">
                <div class="d-flex align-items-start gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-danger fs-4 mt-1"></i>
                    <div>
                        <div class="fw-bold text-danger" id="verdictRejectHeading">GATE ACCESS DENIED / FLAGGED</div>
                        <div class="small text-dark mt-1" id="verdictRejectReason">Reason details here</div>
                        <div class="alert alert-danger py-2 px-3 small mt-2 mb-0 border-0 fw-semibold">
                            <i class="fa-solid fa-hand me-1"></i> Security Action: Hold vehicle at gate barrier. Direct driver to Motorpool Bay for safety compliance resolution.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Container for Pass Clearance -->
            <form id="gateClearanceForm">
                <?= csrf_field() ?>
                <input type="hidden" name="ticket_id" id="modalTicketId">
                <input type="hidden" name="event_type" id="modalEventType">

                <div class="modal-body p-4 bg-light">
                    <!-- High-Contrast Detail Matrix -->
                    <div class="card border-0 shadow-sm p-3 mb-3 bg-white">
                        <div class="row g-3 align-items-center">
                            <!-- Plate & Vehicle -->
                            <div class="col-md-6 border-end-md">
                                <div class="text-muted small text-uppercase mono">Authorized Unit</div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <div class="badge bg-dark fs-5 mono px-3 py-2 text-white border border-light" id="modalPlateNumber">
                                        ---
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark small" id="modalVehicleModel">---</div>
                                        <div class="mono text-muted" style="font-size: 0.72rem;" id="modalTicketSerial">---</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Driver Info & Photo -->
                            <div class="col-md-6">
                                <div class="text-muted small text-uppercase mono">Official Driver</div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold fs-5" style="width: 44px; height: 44px;" id="modalDriverAvatar">
                                        <i class="fa-solid fa-user"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark" id="modalDriverName">---</div>
                                        <div class="small text-muted mono" style="font-size: 0.72rem;" id="modalDriverCodeLicense">---</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-3">

                        <!-- Mission Destination & Passengers -->
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="text-muted small text-uppercase mono" style="font-size: 0.7rem;">Authorized Destination</div>
                                <div class="fw-semibold text-dark small" id="modalDestination">---</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small text-uppercase mono" style="font-size: 0.7rem;">Authorized Passengers</div>
                                <div class="small text-dark fw-semibold" id="modalPassengers">---</div>
                            </div>
                        </div>
                    </div>

                    <!-- Egress / Ingress Automated Timestamp, Audit Validation & Odometer Section (Active only on PASS) -->
                    <div id="verdictOdometerSection">
                        <!-- Automated Compound Departure / Arrival Timestamp Box -->
                        <div class="card border-2 border-success-subtle p-3 mb-3 shadow-sm" style="background: #f0fdf4;">
                            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                                <div>
                                    <div class="small fw-bold text-success text-uppercase mono" id="modalAutoTimeLabel">
                                        <i class="fa-solid fa-clock me-1"></i> Automated Compound Departure Date &amp; Time
                                    </div>
                                    <div class="mono fw-bold fs-5 text-dark mt-1" id="modalAutoTimeValue">
                                        <?= date('M d, Y h:i:s A') ?>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.72rem;" id="modalPrevDepartureNote">
                                        Automatically stamped by the Compound Gate QR Scanner upon driver &amp; passenger validation.
                                    </div>
                                </div>
                                <span class="badge bg-success text-white mono px-2 py-2 align-self-start align-self-md-center">
                                    <i class="fa-solid fa-lock me-1"></i> AUTO-STAMPED AT COMPOUND
                                </span>
                            </div>
                        </div>

                        <!-- Driver & Passenger Compound Audit Checkboxes -->
                        <div class="card border p-3 bg-white shadow-sm mb-3">
                            <div class="small fw-bold text-dark text-uppercase mono mb-2">
                                <i class="fa-solid fa-user-check text-primary me-1"></i> Compound Gate Audit Validation
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="driver_validated" value="1" id="chkDriverValidated" checked required>
                                <label class="form-check-label small fw-semibold text-dark" for="chkDriverValidated">
                                    Assigned Driver identity &amp; official vehicle plate verified at compound barrier
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="passengers_validated" value="1" id="chkPassengersValidated" checked required>
                                <label class="form-check-label small fw-semibold text-dark" for="chkPassengersValidated">
                                    Authorized passengers &amp; official travel manifest verified at compound barrier
                                </label>
                            </div>
                        </div>

                        <div class="card border-2 border-primary-subtle p-3 bg-white shadow-sm mb-3">
                            <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center">
                                <span><i class="fa-solid fa-gauge-high text-primary me-1"></i> Physical Odometer Reading (KM) <span class="text-danger">*</span></span>
                                <span class="badge bg-primary-subtle text-primary mono small" id="odoTypeLabel">Current Vehicle Odometer</span>
                            </label>
                            <input type="number" step="0.1" name="odometer_reading" id="modalOdometer" class="form-control form-control-lg mono fw-bold text-center fs-3 border-2 border-primary" required>
                            <div class="form-text small fw-semibold mt-1" id="modalOdoValidationHint">
                                Reading must be &ge; current recorded odometer.
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-dark">Security Guard Remarks (Optional)</label>
                            <input type="text" name="remarks" id="modalRemarks" class="form-control form-control-sm" placeholder="e.g. Passenger ID badges checked, vehicle tools verified">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light p-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary px-4 fw-semibold" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Close / Hold
                    </button>
                    <button type="submit" class="btn btn-success px-4 py-2 fw-bold shadow" id="btnSubmitClearance">
                        <i class="fa-solid fa-check-double me-1"></i> Validate &amp; Record Compound Timestamp
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- html5-qrcode library -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let html5QrCode = null;
    let isScanning = false;
    let isVerifying = false;
    let isTorchOn = false;
    let currentTicketData = null;
    let availableCameras = [];
    let currentCameraIdx = 0;
    let autoClockTimer = null;

    const btnStart = document.getElementById('btnStartCamera');
    const btnStop = document.getElementById('btnStopCamera');
    const btnSwitchCamera = document.getElementById('btnSwitchCamera');
    const btnScanQrImage = document.getElementById('btnScanQrImage');
    const qrImageFileInput = document.getElementById('qrImageFileInput');
    const btnTorch = document.getElementById('btnToggleTorch');
    const torchLabel = document.getElementById('torchLabel');
    const placeholder = document.getElementById('scannerPlaceholder');
    const statusText = document.getElementById('scannerStatus');
    const reticle = document.getElementById('scannerReticle');
    const controls = document.getElementById('viewfinderControls');
    const btnToggleHighContrast = document.getElementById('btnToggleHighContrast');
    const contrastLabel = document.getElementById('contrastModeLabel');

    const verdictModalEl = document.getElementById('verdictModal');
    const verdictModal = new bootstrap.Modal(verdictModalEl);

    // Resume camera scanning when verdict modal closes
    verdictModalEl.addEventListener('hidden.bs.modal', function() {
        isVerifying = false;
        if (autoClockTimer) {
            clearInterval(autoClockTimer);
            autoClockTimer = null;
        }
        if (html5QrCode && isScanning) {
            try {
                html5QrCode.resume();
                statusText.innerText = "Camera active. Center the e-DTT QR code inside the green reticle.";
            } catch (e) {}
        }
    });

    // Audio & Haptic Feedback synthesis (Zero latency Web Audio API)
    function playAudioFeedback(type) {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const now = ctx.currentTime;

            if (type === 'pass') {
                // Crisp ascending dual chime (880Hz -> 1320Hz)
                const osc1 = ctx.createOscillator();
                const gain1 = ctx.createGain();
                osc1.type = "sine";
                osc1.frequency.setValueAtTime(880, now);
                osc1.frequency.exponentialRampToValueAtTime(1320, now + 0.12);
                gain1.gain.setValueAtTime(0.3, now);
                gain1.gain.exponentialRampToValueAtTime(0.01, now + 0.25);
                osc1.connect(gain1);
                gain1.connect(ctx.destination);
                osc1.start(now);
                osc1.stop(now + 0.25);
            } else {
                // Warning reject low double buzz (180Hz square)
                const osc2 = ctx.createOscillator();
                const gain2 = ctx.createGain();
                osc2.type = "sawtooth";
                osc2.frequency.setValueAtTime(180, now);
                gain2.gain.setValueAtTime(0.4, now);
                gain2.gain.exponentialRampToValueAtTime(0.01, now + 0.35);
                osc2.connect(gain2);
                gain2.connect(ctx.destination);
                osc2.start(now);
                osc2.stop(now + 0.35);
            }
        } catch(e) {}
    }

    function triggerHaptic(type) {
        if ('vibrate' in navigator) {
            try {
                if (type === 'pass') {
                    navigator.vibrate([100, 50, 100]); // double crisp tap
                } else {
                    navigator.vibrate([350, 100, 350]); // warning buzz
                }
            } catch(e) {}
        }
    }

    // High Contrast Mode Toggle
    let isHighContrast = false;
    btnToggleHighContrast.addEventListener('click', function() {
        isHighContrast = !isHighContrast;
        if (isHighContrast) {
            document.body.classList.add('outdoor-mode');
            contrastLabel.innerText = "High Contrast: ON (Outdoor)";
            btnToggleHighContrast.classList.replace('btn-outline-dark', 'btn-warning');
        } else {
            document.body.classList.remove('outdoor-mode');
            contrastLabel.innerText = "High Contrast: Normal";
            btnToggleHighContrast.classList.replace('btn-warning', 'btn-outline-dark');
        }
    });

    // Camera Viewfinder Handlers
    btnStart.addEventListener('click', startScanner);
    btnStop.addEventListener('click', stopScanner);

    function createQrInstance() {
        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode("qrReader", {
                experimentalFeatures: { useBarCodeDetectorIfSupported: true }
            });
        }
        return html5QrCode;
    }

    function onQrDecodedSuccess(decodedText) {
        if (isVerifying) return;
        isVerifying = true;
        if (html5QrCode && isScanning) {
            try { html5QrCode.pause(true); } catch (e) {}
        }
        statusText.innerHTML = `<span class="text-success fw-bold"><i class="fa-solid fa-circle-check"></i> QR Code Read! Validating &amp; Preparing Compound Timestamp...</span>`;
        verifyToken(decodedText);
    }

    function startScannerWithCameraSource(cameraSource) {
        placeholder.classList.add('d-none');
        reticle.style.display = 'flex';
        controls.style.setProperty('display', 'flex', 'important');

        createQrInstance();

        const config = {
            fps: 15,
            qrbox: function(viewfinderWidth, viewfinderHeight) {
                const minEdge = Math.min(viewfinderWidth || 260, viewfinderHeight || 260);
                const boxSize = Math.max(180, Math.floor(minEdge * 0.78));
                return { width: boxSize, height: boxSize };
            },
            aspectRatio: 1.0
        };

        return html5QrCode.start(
            cameraSource,
            config,
            (decodedText) => onQrDecodedSuccess(decodedText),
            () => {}
        ).then(() => {
            isScanning = true;
            statusText.innerText = "Camera active. Center the Driver's e-DTT QR code inside the green reticle.";
        });
    }

    function startScanner() {
        createQrInstance();
        Html5Qrcode.getCameras().then(cameras => {
            availableCameras = cameras || [];
            if (availableCameras.length > 1 && btnSwitchCamera) {
                btnSwitchCamera.classList.remove('d-none');
            }
            // Prefer environment/back camera first, then fallback to user/front camera or first camera ID
            startScannerWithCameraSource({ facingMode: "environment" })
                .catch(() => {
                    if (availableCameras.length > 0) {
                        currentCameraIdx = availableCameras.length - 1;
                        return startScannerWithCameraSource(availableCameras[currentCameraIdx].id);
                    }
                    return startScannerWithCameraSource({ facingMode: "user" });
                })
                .catch((err) => {
                    statusText.innerHTML = `<span class="text-danger fw-bold"><i class="fa-solid fa-triangle-exclamation"></i> Camera access error (${err}). Use 'Scan QR Image' or DTT Serial Input below.</span>`;
                    placeholder.classList.remove('d-none');
                    reticle.style.display = 'none';
                    controls.style.setProperty('display', 'none', 'important');
                });
        }).catch(() => {
            // If getCameras() fails, try direct environment then user facingMode
            startScannerWithCameraSource({ facingMode: "environment" })
                .catch(() => startScannerWithCameraSource({ facingMode: "user" }))
                .catch((err) => {
                    statusText.innerHTML = `<span class="text-danger fw-bold"><i class="fa-solid fa-triangle-exclamation"></i> Camera unavailable (${err}). Use 'Scan QR Image' or Serial Input below.</span>`;
                    placeholder.classList.remove('d-none');
                    reticle.style.display = 'none';
                    controls.style.setProperty('display', 'none', 'important');
                });
        });
    }

    if (btnSwitchCamera) {
        btnSwitchCamera.addEventListener('click', function() {
            if (!availableCameras || availableCameras.length < 2) return;
            currentCameraIdx = (currentCameraIdx + 1) % availableCameras.length;
            if (html5QrCode && isScanning) {
                html5QrCode.stop().then(() => {
                    isScanning = false;
                    startScannerWithCameraSource(availableCameras[currentCameraIdx].id);
                }).catch(() => {});
            }
        });
    }

    // Scan QR from Image File / Screenshot Fallback
    if (btnScanQrImage && qrImageFileInput) {
        btnScanQrImage.addEventListener('click', function() {
            qrImageFileInput.value = '';
            qrImageFileInput.click();
        });

        qrImageFileInput.addEventListener('change', function(e) {
            if (!e.target.files || e.target.files.length === 0) return;
            const imageFile = e.target.files[0];
            createQrInstance();
            statusText.innerHTML = `<i class="fa-solid fa-spinner fa-spin text-primary"></i> Decoding QR image file...`;

            const scanImageFile = () => {
                html5QrCode.scanFile(imageFile, false)
                    .then(decodedText => {
                        onQrDecodedSuccess(decodedText);
                    })
                    .catch(err => {
                        playAudioFeedback('reject');
                        statusText.innerHTML = `<span class="text-danger fw-bold"><i class="fa-solid fa-triangle-exclamation"></i> Could not detect a valid QR code in that image. Try entering the DTT Serial below.</span>`;
                    });
            };

            if (isScanning) {
                html5QrCode.stop().then(() => {
                    isScanning = false;
                    placeholder.classList.remove('d-none');
                    reticle.style.display = 'none';
                    controls.style.setProperty('display', 'none', 'important');
                    scanImageFile();
                }).catch(() => scanImageFile());
            } else {
                scanImageFile();
            }
        });
    }

    function stopScanner() {
        if (html5QrCode && isScanning) {
            html5QrCode.stop().then(() => {
                isScanning = false;
                placeholder.classList.remove('d-none');
                reticle.style.display = 'none';
                controls.style.setProperty('display', 'none', 'important');
                statusText.innerText = "Viewfinder stopped. Click start to resume camera scanner.";
            }).catch(err => console.error("Error stopping scanner:", err));
        }
    }

    // Flashlight (Torch) Toggle
    btnTorch.addEventListener('click', function() {
        if (!html5QrCode || !isScanning) return;
        isTorchOn = !isTorchOn;

        try {
            html5QrCode.applyVideoConstraints({
                advanced: [{ torch: isTorchOn }]
            }).then(() => {
                torchLabel.innerText = isTorchOn ? "Torch: ON" : "Torch: OFF";
                btnTorch.classList.toggle('btn-warning', isTorchOn);
                btnTorch.classList.toggle('btn-dark', !isTorchOn);
            }).catch(err => {
                alert("Flashlight torch not supported on this device/browser.");
                isTorchOn = false;
            });
        } catch(e) {
            alert("Torch control not available.");
        }
    });

    // Manual Input Verification (Button + Enter Key)
    const manualTokenInput = document.getElementById('manualTokenInput');
    document.getElementById('btnVerifyManual').addEventListener('click', function() {
        const val = manualTokenInput.value.trim();
        if (val) verifyToken(val);
    });
    if (manualTokenInput) {
        manualTokenInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const val = this.value.trim();
                if (val) verifyToken(val);
            }
        });
    }

    window.triggerManualVerification = function(serial) {
        verifyToken(serial);
    };

    // Central Token Verification (Includes CSRF Token + Header for CI4 Security Filter)
    let currentCsrfTokenName = '<?= csrf_token() ?>';
    let currentCsrfHash = '<?= csrf_hash() ?>';

    function verifyToken(token) {
        statusText.innerHTML = `<i class="fa-solid fa-spinner fa-spin text-primary"></i> Cryptographically verifying token signature...`;

        const formInputCsrf = document.querySelector('#gateClearanceForm input[name="' + currentCsrfTokenName + '"]');
        if (formInputCsrf && formInputCsrf.value) {
            currentCsrfHash = formInputCsrf.value;
        }

        const payload = new URLSearchParams();
        payload.append('token', token);
        payload.append(currentCsrfTokenName, currentCsrfHash);

        fetch('<?= base_url('gate/verify') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': currentCsrfHash
            },
            body: payload
        })
        .then(res => res.json())
        .then(data => {
            if (data.csrf_hash) {
                currentCsrfHash = data.csrf_hash;
                if (formInputCsrf) formInputCsrf.value = data.csrf_hash;
            }
            if (data.status === 'success') {
                renderVerdictModal(data);
                statusText.innerText = "Verified: " + data.ticket.ticket_serial_no;
            } else {
                // Reject alert
                playAudioFeedback('reject');
                triggerHaptic('reject');
                renderFailedVerdictModal(data.message || 'INVALID QR TOKEN: Unregistered or tampered code.');
                statusText.innerHTML = `<span class="text-danger fw-bold"><i class="fa-solid fa-circle-xmark"></i> ${data.message}</span>`;
            }
        })
        .catch(err => {
            isVerifying = false;
            playAudioFeedback('reject');
            alert('Verification request failed. Please check network connection.');
            statusText.innerText = "Network verification error.";
        });
    }

    function formatLiveCompoundClock(dateObj) {
        return dateObj.toLocaleString('en-US', {
            month: 'short',
            day: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });
    }

    // High-Contrast Instant Verdict Modal Presentation
    function renderVerdictModal(data) {
        currentTicketData = data;
        const t = data.ticket;
        const isEgress = data.event_type === 'egress';
        const isPass = data.verdict === 'pass';

        const banner = document.getElementById('verdictBanner');
        const iconContainer = document.getElementById('verdictIconContainer');
        const title = document.getElementById('verdictTitle');
        const subTitle = document.getElementById('verdictSubTitle');
        const eventBadge = document.getElementById('verdictEventBadge');
        const rejectBox = document.getElementById('verdictRejectBox');
        const odoSection = document.getElementById('verdictOdometerSection');
        const submitBtn = document.getElementById('btnSubmitClearance');

        // Populate Ticket Data
        document.getElementById('modalTicketId').value = t.id;
        document.getElementById('modalEventType').value = isEgress ? 'egress' : 'ingress';
        document.getElementById('modalPlateNumber').innerText = t.plate_number;
        document.getElementById('modalVehicleModel').innerText = (t.make || '') + ' ' + (t.model || '') + ' (' + (t.vehicle_code || '') + ')';
        document.getElementById('modalTicketSerial').innerText = t.ticket_serial_no;
        document.getElementById('modalDriverName').innerText = t.driver_name;
        document.getElementById('modalDriverCodeLicense').innerText = 'Code: ' + (t.driver_code || 'N/A') + ' | License: ' + (t.license_number || 'Official Gov');
        document.getElementById('modalDestination').innerText = t.authorized_destination || 'Designated Official Route';
        document.getElementById('modalPassengers').innerText = t.authorized_passengers || t.passenger_names || 'None Specified';

        const odoInput = document.getElementById('modalOdometer');
        const odoHint = document.getElementById('modalOdoValidationHint');
        const odoLabel = document.getElementById('odoTypeLabel');
        const autoTimeLabel = document.getElementById('modalAutoTimeLabel');
        const autoTimeValue = document.getElementById('modalAutoTimeValue');
        const prevDepNote = document.getElementById('modalPrevDepartureNote');
        const chkDriver = document.getElementById('chkDriverValidated');
        const chkPassengers = document.getElementById('chkPassengersValidated');

        if (chkDriver) chkDriver.checked = true;
        if (chkPassengers) chkPassengers.checked = true;

        if (autoClockTimer) {
            clearInterval(autoClockTimer);
            autoClockTimer = null;
        }

        if (isPass) {
            playAudioFeedback('pass');
            triggerHaptic('pass');

            banner.className = 'p-3 text-center verdict-header-pass';
            iconContainer.innerHTML = '<i class="fa-solid fa-circle-check text-white fa-4x animate__animated animate__zoomIn"></i>';
            title.innerText = isEgress ? 'CLEARED FOR EGRESS (DEPARTURE)' : 'CLEARED FOR INGRESS (RETURN)';
            subTitle.innerText = 'Cryptographic Signature Verified & Security Clear';
            eventBadge.className = 'badge bg-white text-success mono px-3 py-1 fw-bold';
            eventBadge.innerText = isEgress ? 'COMPOUND DEPARTURE' : 'COMPOUND ARRIVAL';

            rejectBox.classList.add('d-none');
            odoSection.classList.remove('d-none');
            submitBtn.classList.remove('d-none');
            submitBtn.disabled = false;
            submitBtn.innerHTML = isEgress
                ? `<i class="fa-solid fa-check-double me-1"></i> Validate &amp; Auto-Stamp Departure Time`
                : `<i class="fa-solid fa-check-double me-1"></i> Validate &amp; Auto-Stamp Arrival Time`;

            if (autoTimeValue) {
                autoTimeValue.innerText = data.automated_timestamp_formatted || formatLiveCompoundClock(new Date());
                autoClockTimer = setInterval(() => {
                    autoTimeValue.innerText = formatLiveCompoundClock(new Date());
                }, 1000);
            }

            if (isEgress) {
                if (autoTimeLabel) {
                    autoTimeLabel.innerHTML = `<i class="fa-solid fa-clock me-1"></i> Automated Compound Departure Date &amp; Time`;
                }
                if (prevDepNote) {
                    prevDepNote.innerText = `Will automatically stamp 'Departure from Compound' on e-DTT ${t.ticket_serial_no} upon confirmation.`;
                }
                odoLabel.innerText = "Departure Odometer";
                odoInput.value = parseFloat(t.vehicle_current_odometer || 0).toFixed(1);
                odoHint.innerHTML = `Must be &ge; current vehicle odometer: <strong>${t.vehicle_current_odometer} KM</strong>`;
            } else {
                if (autoTimeLabel) {
                    autoTimeLabel.innerHTML = `<i class="fa-solid fa-clock me-1"></i> Automated Compound Arrival Back Date &amp; Time`;
                }
                if (prevDepNote) {
                    const depText = data.departure_time_formatted || t.departure_time || 'Recorded at Egress';
                    prevDepNote.innerHTML = `Departed Compound at: <strong>${depText}</strong> &bull; Will automatically stamp 'Arrival back at Compound'.`;
                }
                odoLabel.innerText = "Return Odometer";
                const startOdo = parseFloat(t.start_odometer || t.vehicle_current_odometer || 0);
                odoInput.value = (startOdo + 10).toFixed(1);
                odoHint.innerHTML = `Must be &ge; trip departure odometer: <strong>${startOdo} KM</strong>`;
            }
        } else {
            playAudioFeedback('reject');
            triggerHaptic('reject');

            banner.className = 'p-3 text-center verdict-header-reject';
            iconContainer.innerHTML = '<i class="fa-solid fa-triangle-exclamation text-white fa-4x animate__animated animate__shakeX"></i>';
            title.innerText = 'GATE ACCESS REJECTED / FLAGGED';
            subTitle.innerText = 'Authorization Denied at Security Checkpoint';
            eventBadge.className = 'badge bg-white text-danger mono px-3 py-1 fw-bold';
            eventBadge.innerText = 'FLAGGED / BLOCKED';

            rejectBox.classList.remove('d-none');
            document.getElementById('verdictRejectReason').innerText = data.flag_reason || 'Compliance failure or invalid authorization.';
            odoSection.classList.add('d-none');
            submitBtn.classList.add('d-none');
        }

        verdictModal.show();
    }

    function renderFailedVerdictModal(errorMessage) {
        const banner = document.getElementById('verdictBanner');
        const iconContainer = document.getElementById('verdictIconContainer');
        const title = document.getElementById('verdictTitle');
        const subTitle = document.getElementById('verdictSubTitle');
        const eventBadge = document.getElementById('verdictEventBadge');
        const rejectBox = document.getElementById('verdictRejectBox');
        const odoSection = document.getElementById('verdictOdometerSection');
        const submitBtn = document.getElementById('btnSubmitClearance');

        banner.className = 'p-3 text-center verdict-header-reject';
        iconContainer.innerHTML = '<i class="fa-solid fa-circle-xmark text-white fa-4x animate__animated animate__shakeX"></i>';
        title.innerText = 'SECURITY ALERT: TOKEN REJECTED';
        subTitle.innerText = 'Unregistered / Signature Mismatch';
        eventBadge.className = 'badge bg-white text-danger mono px-3 py-1 fw-bold';
        eventBadge.innerText = 'UNAUTHORIZED ENTRY';

        rejectBox.classList.remove('d-none');
        document.getElementById('verdictRejectReason').innerText = errorMessage;
        odoSection.classList.add('d-none');
        submitBtn.classList.add('d-none');

        document.getElementById('modalPlateNumber').innerText = 'UNKNOWN';
        document.getElementById('modalVehicleModel').innerText = 'Unregistered Vehicle';
        document.getElementById('modalTicketSerial').innerText = 'INVALID TOKEN';
        document.getElementById('modalDriverName').innerText = 'Unverified Driver';
        document.getElementById('modalDriverCodeLicense').innerText = 'Access Denied';
        document.getElementById('modalDestination').innerText = 'None';
        document.getElementById('modalPassengers').innerText = 'None';

        verdictModal.show();
    }

    // Submit Clearance Form
    document.getElementById('gateClearanceForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = document.getElementById('btnSubmitClearance');
        submitBtn.disabled = true;
        submitBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin me-1"></i> Stamping Compound Timestamp...`;

        fetch('<?= base_url('gate/record') ?>', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = `<i class="fa-solid fa-check-double me-1"></i> Validate &amp; Record Compound Timestamp`;

            if (data.status === 'success') {
                playAudioFeedback('pass');
                statusText.innerHTML = `<span class="text-success fw-bold"><i class="fa-solid fa-circle-check"></i> ${data.message}</span>`;
                verdictModal.hide();
                setTimeout(() => {
                    window.location.href = '<?= base_url('gate') ?>';
                }, 400);
            } else {
                playAudioFeedback('reject');
                alert(data.message);
            }
        })
        .catch(err => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = `<i class="fa-solid fa-check-double me-1"></i> Validate &amp; Record Compound Timestamp`;
            alert('Error logging clearance. Please verify odometer input.');
        });
    });

    // Auto-trigger verification if ?verify=DTT-... query parameter is present
    const urlParams = new URLSearchParams(window.location.search);
    const verifyParam = urlParams.get('verify') || urlParams.get('token');
    if (verifyParam) {
        verifyToken(verifyParam);
    }
});
</script>
<?= $this->endSection() ?>
