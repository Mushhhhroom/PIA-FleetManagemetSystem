<?php
/**
 * Driver's Trip Ticket Detail View (ADMIN-F-001)
 *
 * @var string                     $title
 * @var array<string, mixed>       $ticket
 * @var list<array<string, mixed>> $gateLogs
 * @var bool                       $hasPassedSafety
 * @var list<array<string, mixed>> $mergedRequests
 * @var string|null                $userRole
 */
$ticket          = $ticket ?? [];
$gateLogs        = $gateLogs ?? [];
$hasPassedSafety = $hasPassedSafety ?? false;
$mergedRequests  = $mergedRequests ?? [];
$userRole        = $userRole ?? (session()->get('user_role') ?? '');
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Breadcrumbs & Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <a href="<?= base_url('tickets') ?>" class="text-decoration-none text-muted small d-inline-block mb-1">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Driver's Trip Tickets
        </a>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h1 class="page-title mb-0"><?= esc($ticket['ticket_serial_no']) ?></h1>
            <span class="badge bg-light text-muted border mono" style="font-size: 0.75rem;">
                ADMIN-F-001 rev1
            </span>
            <?php
                $ticketPills = [
                    'issued'    => ['label' => 'Issued & Authorized', 'class' => 'bg-warning text-dark'],
                    'departed'  => ['label' => 'In Transit (Outside Compound)', 'class' => 'bg-primary text-white'],
                    'returned'  => ['label' => 'Returned to Compound', 'class' => 'bg-info text-white'],
                    'completed' => ['label' => 'Reconciled & Audited', 'class' => 'bg-success text-white'],
                ];
                $st = $ticketPills[$ticket['status']] ?? ['label' => ucfirst($ticket['status']), 'class' => 'bg-secondary text-white'];
            ?>
            <span class="badge <?= $st['class'] ?> px-2 py-1 small">
                <?= $st['label'] ?>
            </span>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php if (in_array($userRole ?? '', ['guard', 'admin', 'dispatcher', 'approver_admin'], true) && in_array($ticket['status'], ['issued', 'departed'], true)): ?>
            <a href="<?= base_url('gate?verify=' . urlencode($ticket['ticket_serial_no'])) ?>" class="btn btn-success fw-semibold">
                <i class="fa-solid fa-qrcode me-1"></i> Verify &amp; Auto-Stamp at Compound Gate
            </a>
        <?php endif; ?>
        <a href="<?= base_url('tickets/' . $ticket['id'] . '/print') ?>" target="_blank" class="btn btn-secondary">
            <i class="fa-solid fa-print me-1"></i> Print Official DTT (ADMIN-F-001 rev1)
        </a>
        <?php if (($userRole ?? '') !== 'guard'): ?>
            <a href="<?= base_url('requests/' . $ticket['request_id']) ?>" class="btn btn-outline-secondary">
                <i class="fa-solid fa-file-lines me-1"></i> View VRS (ADMIN-F-018)
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Section A & QR Code -->
    <div class="col-lg-4">
        <!-- Cryptographic QR Code Card -->
        <div class="card-panel mb-4 text-center p-4">
            <div class="small fw-bold text-dark text-uppercase mono mb-2" style="font-size: 0.75rem;">
                <i class="fa-solid fa-qrcode text-primary me-1"></i> Cryptographic Gate QR Token
            </div>

            <!-- Dynamic QR Code Container (Rendered via secure SVG / JS generator) -->
            <div class="p-3 bg-white border rounded-3 d-inline-block my-2" style="width: 220px; height: 220px;">
                <div id="qrcodeCanvas" class="d-flex align-items-center justify-content-center h-100"></div>
            </div>

            <div class="mono text-muted small mt-2" style="font-size: 0.72rem; word-break: break-all;">
                Token: <span class="text-dark fw-semibold"><?= esc(substr($ticket['qr_crypt_token'], 0, 20)) ?>...</span>
            </div>
            <div class="small text-muted mt-1" style="font-size: 0.7rem;">
                <i class="fa-solid fa-shield-halved text-success me-1"></i> HMAC-SHA256 Signed Security Token
            </div>
            <div class="alert alert-info border-info-subtle p-2 mt-3 mb-0 small text-start" style="font-size: 0.75rem;">
                <i class="fa-solid fa-circle-info me-1"></i> Present this digital QR code to the Gate Security Officer upon compound departure (egress) and return (ingress).
            </div>
        </div>

        <!-- Section A: Official Travel Authorization -->
        <div class="card-panel border shadow-sm mb-4">
            <div class="p-3 border-bottom bg-light">
                <span class="small fw-bold text-dark text-uppercase">
                    <i class="fa-solid fa-passport text-primary me-1"></i> Section A: Travel Authorization
                </span>
            </div>
            <div class="p-3">
                <div class="mb-3">
                    <span class="text-muted small d-block">Authorized Vehicle Unit:</span>
                    <strong class="mono fs-6 text-dark"><?= esc($ticket['plate_number']) ?></strong>
                    <div class="small text-muted"><?= esc($ticket['make'] . ' ' . $ticket['model'] . ' (' . $ticket['year'] . ')') ?></div>
                </div>
                <div class="mb-3">
                    <span class="text-muted small d-block">Official Assigned Driver:</span>
                    <strong class="text-dark"><?= esc($ticket['driver_name']) ?></strong>
                    <div class="mono text-muted small"><?= esc($ticket['driver_code']) ?> &bull; Lic: <?= esc($ticket['license_number']) ?></div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">Authorized Destination:</span>
                        <div class="d-flex gap-1">
                            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle mono" id="tktNavDistance" style="font-size: 0.65rem;">-- km</span>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle mono" id="tktNavEta" style="font-size: 0.65rem;">-- min</span>
                        </div>
                    </div>
                    <strong class="text-dark small d-block mb-2"><i class="fa-solid fa-location-dot text-danger me-1"></i><?= esc($ticket['authorized_destination']) ?></strong>

                    <!-- Interactive Road-Snapped Route & Driver Navigation Preview -->
                    <div class="rounded-3 border overflow-hidden mb-2" style="background: #f8fafc;">
                        <div id="ticketRouteMap" style="height: 195px; width: 100%; z-index: 1;"></div>
                        <div class="p-2 bg-white border-top d-flex justify-content-between align-items-center">
                            <span class="small text-truncate" id="tktNextStep" style="font-size: 0.7rem; max-width: 72%; color: #0f172a;">
                                <i class="fa-solid fa-route text-primary me-1"></i> Computing road-snapped route...
                            </span>
                            <button class="btn btn-xs btn-outline-secondary py-0 px-2" type="button" data-bs-toggle="collapse" data-bs-target="#tktStepsCollapse" style="font-size: 0.68rem;">
                                <i class="fa-solid fa-list-ol me-1"></i>Steps
                            </button>
                        </div>
                        <div class="collapse border-top bg-light" id="tktStepsCollapse">
                            <div id="tktTurnSteps" class="p-2 small" style="max-height: 150px; overflow-y: auto; font-size: 0.7rem;"></div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <a id="tktGoogleMapsBtn"
                           href="https://www.google.com/maps/dir/?api=1&amp;origin=14.657200,121.045000&amp;destination=<?= urlencode($ticket['authorized_destination'] . ', Philippines') ?>&amp;travelmode=driving"
                           target="_blank" rel="noopener"
                           class="btn btn-xs btn-outline-primary flex-grow-1 text-center text-decoration-none">
                            <i class="fa-solid fa-diamond-turn-right me-1"></i> Google Maps
                        </a>
                        <a id="tktWazeBtn"
                           href="https://waze.com/ul?q=<?= urlencode($ticket['authorized_destination']) ?>&amp;navigate=yes"
                           target="_blank" rel="noopener"
                           class="btn btn-xs btn-outline-secondary text-center text-decoration-none px-3">
                            <i class="fa-solid fa-location-arrow text-info me-1"></i> Waze
                        </a>
                    </div>
                </div>
                <div class="mb-3">
                    <span class="text-muted small d-block">Authorized Purpose:</span>
                    <div class="small text-dark p-2 rounded bg-light border"><?= nl2br(esc($ticket['authorized_purpose'])) ?></div>
                </div>
                <div class="mb-3">
                    <span class="text-muted small d-block">Authorized Passengers:</span>
                    <div class="mono small text-dark p-2 rounded bg-light border"><?= nl2br(esc($ticket['authorized_passengers'])) ?></div>
                </div>
                <?php if (!empty($mergedRequests)): ?>
                    <div class="mb-3">
                        <span class="text-muted small d-block mb-1">
                            <i class="fa-solid fa-code-merge text-primary me-1"></i> Consolidated Carpool Requests (<?= count($mergedRequests) ?>):
                        </span>
                        <div class="list-group list-group-flush border rounded">
                            <?php foreach ($mergedRequests as $mr): ?>
                                <div class="list-group-item p-2 small bg-light bg-opacity-50">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <a href="<?= base_url('requests/' . $mr['id']) ?>" class="mono fw-bold text-decoration-none">
                                            <?= esc($mr['request_number']) ?>
                                        </a>
                                        <span class="badge bg-secondary" style="font-size: 0.65rem;"><?= esc($mr['passenger_count']) ?> pax</span>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.72rem;"><?= esc($mr['office_name'] ?? 'PIA') ?> &bull; <?= esc($mr['requestor_name']) ?></div>
                                    <div class="text-dark" style="font-size: 0.72rem;"><i class="fa-solid fa-location-dot text-danger me-1"></i><?= esc($mr['destination']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="pt-2 border-top">
                    <span class="text-muted small d-block">Approving Authority:</span>
                    <strong class="text-dark"><?= esc($ticket['admin_approver_name'] ?? 'Atty. Julius S. De Peralta') ?></strong>
                    <div class="text-muted" style="font-size: 0.72rem;">Head, Administrative Division</div>
                </div>
            </div>
        </div>

        <!-- Compound Checkpoint History -->
        <div class="card-panel border shadow-sm mb-4">
            <div class="p-3 border-bottom bg-light">
                <span class="small fw-bold text-dark text-uppercase">
                    <i class="fa-solid fa-shield-halved text-success me-1"></i> Gate Checkpoint Log
                </span>
            </div>
            <div class="p-3">
                <?php if (empty($gateLogs)): ?>
                    <div class="text-muted small text-center py-3">
                        <i class="fa-solid fa-door-open fa-lg mb-2 d-block opacity-50"></i>
                        No gate scans recorded yet. Vehicle is currently in compound.
                    </div>
                <?php else: ?>
                    <?php foreach ($gateLogs as $g): ?>
                        <div class="p-2 mb-2 rounded border bg-light small">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="fw-bold <?= $g['event_type'] === 'egress' ? 'text-primary' : 'text-success' ?>">
                                    <?= strtoupper($g['event_type']) ?> (<?= $g['event_type'] === 'egress' ? 'DEPARTURE' : 'RETURN' ?>)
                                </span>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle" style="font-size: 0.65rem;">
                                    VERIFIED
                                </span>
                            </div>
                            <div class="mono text-muted" style="font-size: 0.72rem;">
                                <?= date('M d, Y h:i:s A', strtotime($g['scanned_at'])) ?>
                            </div>
                            <div class="mono text-dark fw-bold" style="font-size: 0.75rem;">
                                Verified Odometer: <?= number_format((float)$g['odometer_reading'], 1) ?> km
                            </div>
                            <div class="text-muted" style="font-size: 0.7rem;">
                                Guard: <?= esc($g['guard_name']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Section B Execution, Fuel Accounting, & Dual Certifications -->
    <div class="col-lg-8">
        <div class="card-panel border shadow-sm">
            <div class="p-3 border-bottom bg-light d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="mb-0 fw-bold text-dark small text-uppercase">Section B: Driver's Trip Execution & Post-Trip Report</h6>
                    <div class="text-muted" style="font-size: 0.72rem;">To be accomplished by driver upon trip completion. Enforces standard government fuel accounting formula.</div>
                </div>
                <span class="badge bg-white text-dark border small mono">ADMIN-F-001 rev1</span>
            </div>

            <form action="<?= base_url('tickets/' . $ticket['id'] . '/update') ?>" method="POST" id="sectionBForm" class="p-4">
                <?= csrf_field() ?>

                <!-- B.1 Trip Log & Odometers -->
                <div class="fw-bold text-dark small text-uppercase mb-3 pb-1 border-bottom">
                    <i class="fa-solid fa-gauge text-primary me-1"></i> B.1 Trip Schedule & Odometer Log
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-dark d-flex align-items-center justify-content-between">
                            <span>Departure from Compound</span>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle mono" style="font-size: 0.6rem;" title="Automatically stamped by the Compound Gate QR Scanner upon Egress">
                                <i class="fa-solid fa-qrcode me-1"></i>GATE AUTO
                            </span>
                        </label>
                        <input type="text" class="form-control form-control-sm mono bg-light fw-semibold" value="<?= $ticket['departure_time'] ? date('M d, Y h:i A', strtotime($ticket['departure_time'])) : 'Pending Gate QR Scan' ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-dark">Arrival at Destination</label>
                        <input type="datetime-local" name="arrival_dest_time" class="form-control form-control-sm" value="<?= $ticket['arrival_dest_time'] ? date('Y-m-d\TH:i', strtotime($ticket['arrival_dest_time'])) : '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-dark">Departure from Destination</label>
                        <input type="datetime-local" name="departure_dest_time" class="form-control form-control-sm" value="<?= $ticket['departure_dest_time'] ? date('Y-m-d\TH:i', strtotime($ticket['departure_dest_time'])) : '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-dark d-flex align-items-center justify-content-between">
                            <span>Arrival back at Compound</span>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle mono" style="font-size: 0.6rem;" title="Automatically stamped by the Compound Gate QR Scanner upon Ingress">
                                <i class="fa-solid fa-qrcode me-1"></i>GATE AUTO
                            </span>
                        </label>
                        <input type="text" class="form-control form-control-sm mono bg-light fw-semibold" value="<?= $ticket['arrival_back_time'] ? date('M d, Y h:i A', strtotime($ticket['arrival_back_time'])) : 'Pending Gate QR Scan' ?>" readonly>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Start Odometer (KM)</label>
                        <input type="number" step="0.1" name="start_odometer" id="start_odometer" class="form-control form-control-sm mono" value="<?= (float)($ticket['start_odometer'] ?? 0) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Destination Odometer (KM)</label>
                        <input type="number" step="0.1" name="dest_odometer" id="dest_odometer" class="form-control form-control-sm mono" value="<?= (float)($ticket['dest_odometer'] ?? 0) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Return Odometer (KM)</label>
                        <input type="number" step="0.1" name="return_odometer" id="return_odometer" class="form-control form-control-sm mono" value="<?= (float)($ticket['return_odometer'] ?? 0) ?>" required>
                    </div>
                    <div class="col-12">
                        <div class="p-2 rounded bg-light border d-flex align-items-center justify-content-between flex-wrap gap-2 small">
                            <div>
                                <span class="text-muted">Calculated Total Distance Travelled:</span>
                                <span class="mono fw-bold fs-6 text-dark ms-2" id="calcDistance"><?= number_format((float)$ticket['total_distance_km'], 1) ?> KM</span>
                            </div>
                            <button type="button" id="btnApplyRoadDist" class="btn btn-xs btn-outline-primary d-none" title="Auto-compute Destination & Return Odometer using verified OSRM road route distance">
                                <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Apply Verified Road Distance (<span id="btnRoadDistLabel">0.0</span> km Round-Trip)
                            </button>
                        </div>
                    </div>
                </div>

                <!-- B.2 Fuel Balance Accounting Formula -->
                <div class="fw-bold text-dark small text-uppercase mb-2 pb-1 border-bottom">
                    <i class="fa-solid fa-gas-pump text-primary me-1"></i> B.2 Official Fuel Accounting Formula
                </div>
                <div class="text-muted small mb-3" style="font-size: 0.75rem;">
                    Formula: <code>Balance End = Balance Start + Issued from Stock + Purchased on Trip - Total Used</code>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-dark">1. Start Balance (Liters)</label>
                        <input type="number" step="0.01" name="fuel_balance_start_liters" id="fuel_start" class="form-control form-control-sm mono" value="<?= (float)($ticket['fuel_balance_start_liters'] ?? 0) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-dark">2. Stock Issued (Liters)</label>
                        <input type="number" step="0.01" name="fuel_issued_stock_liters" id="fuel_issued" class="form-control form-control-sm mono" value="<?= (float)($ticket['fuel_issued_stock_liters'] ?? 0) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-dark">3. Purchased (Liters)</label>
                        <input type="number" step="0.01" name="fuel_purchased_liters" id="fuel_purchased" class="form-control form-control-sm mono" value="<?= (float)($ticket['fuel_purchased_liters'] ?? 0) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-dark">Fuel Purchase Cost (₱)</label>
                        <input type="number" step="0.01" name="fuel_purchased_cost" class="form-control form-control-sm mono" value="<?= (float)($ticket['fuel_purchased_cost'] ?? 0) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">4. Total Fuel Used on Trip (Liters)</label>
                        <input type="number" step="0.01" name="fuel_used_liters" id="fuel_used" class="form-control form-control-sm mono" value="<?= (float)($ticket['fuel_used_liters'] ?? 0) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">5. Remaining Balance in Tank (Liters)</label>
                        <input type="number" step="0.01" name="fuel_balance_end_liters" id="fuel_end" class="form-control form-control-sm mono bg-light fw-bold" value="<?= (float)($ticket['fuel_balance_end_liters'] ?? 0) ?>" readonly>
                    </div>

                    <!-- Calculated Efficiency & Anomaly Detector -->
                    <div class="col-12">
                        <div class="p-2 rounded bg-light border d-flex align-items-center justify-content-between small">
                            <span class="text-muted">Calculated Fuel Efficiency:</span>
                            <span class="mono fw-bold text-dark" id="calcEfficiency">
                                <?= number_format((float)$ticket['fuel_efficiency_kml'], 2) ?> KM/L
                            </span>
                        </div>
                        <?php if ((int)$ticket['fuel_anomaly_flag'] === 1): ?>
                            <div class="alert alert-danger p-2 small mt-2 mb-0">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                <strong>COA FUEL ANOMALY FLAGGED:</strong> Fuel consumption deviates by more than 20% from official fleet benchmark (10.0 KM/L). Subject to auditor scrutiny.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- B.3 Consumables -->
                <div class="fw-bold text-dark small text-uppercase mb-3 pb-1 border-bottom">
                    <i class="fa-solid fa-oil-can text-primary me-1"></i> B.3 Consumables & Lubricants
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Gear Oil (Liters)</label>
                        <input type="number" step="0.1" name="gear_oil_liters" class="form-control form-control-sm mono" value="<?= (float)($ticket['gear_oil_liters'] ?? 0) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Lubricating Oil (Liters)</label>
                        <input type="number" step="0.1" name="lube_oil_liters" class="form-control form-control-sm mono" value="<?= (float)($ticket['lube_oil_liters'] ?? 0) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Grease (Cans / Units)</label>
                        <input type="number" step="1" name="grease_units" class="form-control form-control-sm mono" value="<?= (float)($ticket['grease_units'] ?? 0) ?>">
                    </div>
                </div>

                <!-- B.3b Toll Expense (FR-4.1 / FR-6.2) -->
                <div class="fw-bold text-dark small text-uppercase mb-3 pb-1 border-bottom">
                    <i class="fa-solid fa-road-barrier text-primary me-1"></i> B.3b Toll Expense (FR-4.1 / FR-6.2)
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Toll Amount (₱)</label>
                        <input type="number" step="0.01" min="0" name="toll_expense" class="form-control form-control-sm mono" value="<?= number_format((float)($ticket['toll_expense'] ?? 0), 2, '.', '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Tollway Provider</label>
                        <select name="toll_provider" class="form-select form-select-sm">
                            <option value="">— None / Cash —</option>
                            <?php foreach (['autosweep' => 'AutoSweep', 'easytrip' => 'EasyTrip', 'cash' => 'Cash Lane'] as $pKey => $pLabel): ?>
                                <option value="<?= $pKey ?>" <?= ($ticket['toll_provider'] ?? '') === $pKey ? 'selected' : '' ?>><?= $pLabel ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="text-muted" style="font-size: 0.72rem;">
                            <i class="fa-solid fa-circle-info me-1"></i>
                            AutoSweep / EasyTrip amounts are deducted from the vehicle's registered RFID card
                            and re-evaluated against the ₱500 low-balance threshold (FR-6.3).
                        </div>
                    </div>
                </div>

                <!-- B.4 Dual Digital Certifications -->
                <div class="fw-bold text-dark small text-uppercase mb-3 pb-1 border-bottom">
                    <i class="fa-solid fa-file-contract text-primary me-1"></i> B.4 Dual Official Certifications (CSC / COA Standard)
                </div>

                <div class="row g-3 mb-4">
                    <!-- Driver Certification -->
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 bg-light h-100">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="driver_certified" value="1" id="driver_certified" <?= (int)$ticket['driver_certified'] === 1 ? 'checked' : '' ?>>
                                <label class="form-check-label small fw-bold text-dark" for="driver_certified">
                                    Official Driver's Certification
                                </label>
                            </div>
                            <p class="text-muted small fst-italic mb-2" style="font-size: 0.72rem; line-height: 1.4;">
                                "I hereby certify to the correctness of the above statement of record of travel and fuel consumption."
                            </p>
                            <div class="mono text-dark fw-semibold small"><?= esc($ticket['driver_name']) ?></div>
                            <div class="text-muted" style="font-size: 0.68rem;">
                                <?= $ticket['driver_certified_at'] ? 'Certified on ' . date('M d, Y h:i A', strtotime($ticket['driver_certified_at'])) : 'Pending Driver Signature' ?>
                            </div>
                        </div>
                    </div>

                    <!-- Passenger / Official Certification -->
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 bg-light h-100">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="passenger_certified" value="1" id="passenger_certified" <?= (int)$ticket['passenger_certified'] === 1 ? 'checked' : '' ?>>
                                <label class="form-check-label small fw-bold text-dark" for="passenger_certified">
                                    Passenger / Official Certification
                                </label>
                            </div>
                            <p class="text-muted small fst-italic mb-2" style="font-size: 0.72rem; line-height: 1.4;">
                                "I hereby certify that I used this vehicle on official business as stated above."
                            </p>
                            <input type="text" name="passenger_certifier_name" class="form-control form-control-sm mb-1" placeholder="Official Passenger Name" value="<?= esc($ticket['passenger_certifier_name'] ?? $ticket['requestor_name']) ?>">
                            <div class="text-muted" style="font-size: 0.68rem;">
                                <?= $ticket['passenger_certified_at'] ? 'Certified on ' . date('M d, Y h:i A', strtotime($ticket['passenger_certified_at'])) : 'Pending Passenger Signature' ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold text-dark">Driver's Notes / Route Remarks</label>
                    <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Unusual traffic, road conditions, official stops..."><?= esc($ticket['notes'] ?? '') ?></textarea>
                </div>

                <!-- Submit Bar -->
                <div class="pt-3 border-top d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary px-4 shadow-sm fw-semibold">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Save Section B Report & Certifications
                    </button>
                </div>
            </form>
        </div>

        <!-- FR-5.1 Pre-Trip Safety & FR-4.2 Passenger Delay Log -->
        <div class="card-panel mt-4">
            <div class="p-3 border-bottom bg-light">
                <h6 class="mb-0 fw-bold text-dark small text-uppercase">Safety &amp; Compliance Actions (FR-5.1 / FR-4.2)</h6>
            </div>

            <div class="p-4">
                <!-- FR-5.1: BLOWBAGETS checklist -->
                <div class="p-3 border rounded-3 mb-4" style="background: #f0f9ff;">
                    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                        <div>
                            <div class="fw-bold text-dark small">
                                <i class="fa-solid fa-clipboard-check text-primary me-1"></i>
                                Mandatory Pre-Trip BLOWBAGETS Safety Checklist (FR-5.1)
                            </div>
                            <div class="text-muted" style="font-size: 0.74rem;">
                                The gate denies egress until this trip ticket carries a <strong>PASSED</strong> safety check.
                                A single failed item locks the vehicle to Under Maintenance and auto-generates a PIR (FR-5.2).
                            </div>
                        </div>
                        <?php if (($userRole ?? '') === 'guard'): ?>
                            <span class="badge <?= !empty($hasPassedSafety) ? 'bg-success text-white' : 'bg-warning text-dark' ?> px-3 py-2 rounded-pill">
                                <i class="fa-solid <?= !empty($hasPassedSafety) ? 'fa-circle-check' : 'fa-clock' ?> me-1"></i>
                                <?= !empty($hasPassedSafety) ? 'BLOWBAGETS Passed' : 'BLOWBAGETS Pending' ?>
                            </span>
                        <?php else: ?>
                            <a href="<?= base_url('safety/' . $ticket['id']) ?>" class="btn btn-sm btn-primary rounded-pill fw-semibold text-decoration-none">
                                <i class="fa-solid fa-clipboard-check me-1"></i> Open Checklist
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- FR-4.2: 15-minute passenger delay log -->
                <div class="p-3 border rounded-3" style="background: #fffbeb;">
                    <div class="fw-bold text-dark small mb-2">
                        <i class="fa-solid fa-clock text-warning me-1"></i>
                        Passenger Delay Log — 15-Minute Rule (FR-4.2)
                    </div>
                    <form action="<?= base_url('tickets/' . $ticket['id'] . '/delay') ?>" method="POST" class="row g-2 align-items-end">
                        <?= csrf_field() ?>
                        <div class="col-sm-3">
                            <label class="form-label small fw-semibold text-dark">Delay (minutes)</label>
                            <input type="number" name="delay_minutes" min="1" class="form-control form-control-sm mono" required placeholder="e.g. 20">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-dark">Reason</label>
                            <input type="text" name="reason" class="form-control form-control-sm" placeholder="e.g. Passenger not yet at pick-up point, road closure...">
                        </div>
                        <div class="col-sm-3">
                            <button type="submit" class="btn btn-sm btn-warning fw-semibold w-100">
                                <i class="fa-solid fa-plus me-1"></i> Log Delay
                            </button>
                        </div>
                        <div class="col-12 text-muted" style="font-size: 0.7rem;">
                            Once a delay reaches <strong>15 minutes</strong>, all dispatchers are notified automatically.
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- QR Code Library Script (Offline pure client JS) -->
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Generate QR Code dynamically (CorrectLevel.L ensures large, crisp scannable modules for webcams)
    const qrContainer = document.getElementById('qrcodeCanvas');
    const qrToken = <?= json_encode($ticket['qr_crypt_token']) ?>;

    if (typeof QRCode !== 'undefined' && qrContainer) {
        new QRCode(qrContainer, {
            text: qrToken,
            width: 190,
            height: 190,
            colorDark: "#0A2540",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.L
        });
    }

    // Dynamic Section B Fuel & Odometer Calculator
    const startOdo = document.getElementById('start_odometer');
    const returnOdo = document.getElementById('return_odometer');
    const calcDistance = document.getElementById('calcDistance');

    const fStart = document.getElementById('fuel_start');
    const fIssued = document.getElementById('fuel_issued');
    const fPurchased = document.getElementById('fuel_purchased');
    const fUsed = document.getElementById('fuel_used');
    const fEnd = document.getElementById('fuel_end');
    const calcEff = document.getElementById('calcEfficiency');

    function recalculate() {
        const sOdo = parseFloat(startOdo.value) || 0;
        const rOdo = parseFloat(returnOdo.value) || 0;
        const dist = Math.max(0, rOdo - sOdo);
        calcDistance.innerText = dist.toFixed(1) + ' KM';

        const sFuel = parseFloat(fStart.value) || 0;
        const iFuel = parseFloat(fIssued.value) || 0;
        const pFuel = parseFloat(fPurchased.value) || 0;
        const uFuel = parseFloat(fUsed.value) || 0;

        const endFuel = (sFuel + iFuel + pFuel) - uFuel;
        fEnd.value = endFuel.toFixed(2);

        if (dist > 0 && uFuel > 0) {
            const eff = dist / uFuel;
            calcEff.innerText = eff.toFixed(2) + ' KM/L';
        } else {
            calcEff.innerText = '0.00 KM/L';
        }
    }

    [startOdo, returnOdo, fStart, fIssued, fPurchased, fUsed].forEach(input => {
        if (input) input.addEventListener('input', recalculate);
    });

    // Interactive Ticket Route & Turn-by-Turn Navigation Map
    const tktMapEl = document.getElementById('ticketRouteMap');
    if (tktMapEl && typeof L !== 'undefined') {
        const originLat   = 14.657200;
        const originLng   = 121.045000;
        const destAddress = <?= json_encode($ticket['authorized_destination'] ?? '') ?>;

        const tktMap = L.map('ticketRouteMap', {
            zoomControl: true,
            attributionControl: false
        }).setView([originLat, originLng], 12);

        L.tileLayer('https://mt{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            subdomains: ['0', '1', '2', '3'],
            attribution: '&copy; Google Maps'
        }).addTo(tktMap);

        const routeGroup = L.featureGroup().addTo(tktMap);

        if (destAddress && destAddress.trim() !== '') {
            fetch('<?= base_url('api/v1/route') ?>?destination=' + encodeURIComponent(destAddress))
            .then(r => r.json())
            .then(data => {
                if (data.status !== 'success') return;

                const hqIcon = L.divIcon({
                    className: 'tkt-hq-pin',
                    html: `<div style="background:#0f172a;color:#fff;border-radius:50%;width:26px;height:26px;display:flex;align-items:center;justify-content:center;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,0.3);font-size:11px;"><i class="fa-solid fa-building-shield"></i></div>`,
                    iconSize: [26, 26],
                    iconAnchor: [13, 13]
                });
                L.marker([originLat, originLng], { icon: hqIcon })
                    .bindPopup('<strong>PIA Central Office Motorpool</strong>')
                    .addTo(routeGroup);

                if (data.destination && data.destination.lat && data.destination.lng) {
                    const destIcon = L.divIcon({
                        className: 'tkt-dest-pin',
                        html: `<div style="background:#dc2626;color:#fff;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,0.3);font-size:12px;"><i class="fa-solid fa-flag-checkered"></i></div>`,
                        iconSize: [28, 28],
                        iconAnchor: [14, 14]
                    });
                    L.marker([data.destination.lat, data.destination.lng], { icon: destIcon })
                        .bindPopup(`<strong>${destAddress}</strong>`)
                        .addTo(routeGroup);

                    const gBtn = document.getElementById('tktGoogleMapsBtn');
                    if (gBtn) {
                        gBtn.href = `https://www.google.com/maps/dir/?api=1&origin=${originLat},${originLng}&destination=${data.destination.lat},${data.destination.lng}&travelmode=driving`;
                    }
                    const wBtn = document.getElementById('tktWazeBtn');
                    if (wBtn) {
                        wBtn.href = `https://waze.com/ul?ll=${data.destination.lat},${data.destination.lng}&navigate=yes`;
                    }
                }

                if (data.leaflet_coords && data.leaflet_coords.length > 1) {
                    L.polyline(data.leaflet_coords, {
                        color: '#2563eb',
                        weight: 4,
                        opacity: 0.9,
                        lineJoin: 'round'
                    }).addTo(routeGroup);
                }

                tktMap.fitBounds(routeGroup.getBounds().pad(0.18));
                setTimeout(() => { if (typeof tktMap !== 'undefined') tktMap.invalidateSize(); }, 350);

                document.getElementById('tktNavDistance').innerText = `${data.distance_km} km`;
                document.getElementById('tktNavEta').innerText      = `~${data.duration_min} min`;

                function getTktManeuverIcon(instruction) {
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
                    return '<i class="fa-solid fa-arrow-up text-primary me-1"></i>';
                }

                if (data.steps && data.steps.length > 0) {
                    document.getElementById('tktNextStep').innerHTML = `${getTktManeuverIcon(data.steps[0].instruction)} ${data.steps[0].instruction}`;
                    document.getElementById('tktTurnSteps').innerHTML = data.steps.map((st, idx) => `
                        <div class="d-flex justify-content-between py-1 ${idx < data.steps.length - 1 ? 'border-bottom' : ''}">
                            <span>${getTktManeuverIcon(st.instruction)}<strong class="mono text-dark me-1">${idx + 1}.</strong> ${st.instruction}</span>
                            <span class="mono text-muted ms-2">${st.distance_m >= 1000 ? (st.distance_m / 1000).toFixed(1) + ' km' : st.distance_m + ' m'}</span>
                        </div>
                    `).join('');
                }

                // Enable 1-click Road Distance Odometer Helper in Section B
                const btnApply = document.getElementById('btnApplyRoadDist');
                const destOdo  = document.getElementById('dest_odometer');
                if (btnApply && data.distance_km > 0) {
                    const oneWay    = parseFloat(data.distance_km);
                    const roundTrip = (oneWay * 2).toFixed(1);
                    document.getElementById('btnRoadDistLabel').innerText = roundTrip;
                    btnApply.classList.remove('d-none');
                    btnApply.addEventListener('click', function() {
                        const s = parseFloat(startOdo.value) || 0;
                        if (destOdo) destOdo.value = (s + oneWay).toFixed(1);
                        if (returnOdo) returnOdo.value = (s + oneWay * 2).toFixed(1);
                        if (fUsed && parseFloat(fUsed.value || 0) === 0) {
                            fUsed.value = ((oneWay * 2) / 10.0).toFixed(2);
                        }
                        recalculate();
                    });
                }
            })
            .catch(() => {});
        }
    }
});
</script>
<?= $this->endSection() ?>
