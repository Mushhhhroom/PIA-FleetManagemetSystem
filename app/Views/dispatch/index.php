<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--primary-accent); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            <i class="fa-solid fa-van-shuttle me-1"></i> Motorpool Division &bull; Heuristic Dispatch Engine
        </div>
        <h1 class="page-title mb-1">Dispatch Command & Asset Allocation</h1>
        <p class="text-muted small mb-0">
            Vehicle-driver matching, 
            <span class="policy-badge policy-badge-warning" data-bs-toggle="tooltip" data-bs-placement="top" title="<strong>Preventive Maintenance Schedule (PMS)</strong><br>Automated engineering lockout triggered every 5,000 km. Vehicles are locked from dispatch until PMS service is completed and certified by mechanics." tabindex="0" role="button">
                5,000 km PMS Lock <i class="fa-solid fa-circle-question policy-info-icon"></i>
            </span>, and 
            <span class="policy-badge policy-badge-info" data-bs-toggle="tooltip" data-bs-placement="top" title="<strong>Electronic Driver's Trip Ticket (e-DTT)</strong><br>Document Code: ADMIN-F-001 rev1. Mandated by COA Circular No. 75-6. Features cryptographic HMAC-SHA256 QR security tokens for compound gate clearance." tabindex="0" role="button">
                e-DTT <i class="fa-solid fa-circle-question policy-info-icon"></i>
            </span> 
            issuance.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <?php if (!empty($pendingTier1)): ?>
            <a href="#tier1-section" class="badge bg-warning bg-opacity-10 text-dark border border-warning-subtle p-2 px-3 small text-decoration-none" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<strong>Tier 1 Motorpool Review</strong><br>First-line document check by the Motorpool Dispatcher to verify purpose, requested vehicle suitability, and passenger rosters before Division Chief endorsement.">
                <i class="fa-solid fa-file-circle-check text-warning me-1"></i> Tier 1 Document Check: <strong><?= count($pendingTier1) ?></strong>
            </a>
        <?php endif; ?>
        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle p-2 px-3 small">
            <i class="fa-solid fa-check-circle me-1"></i> Ready for Dispatch: <strong><?= count($pendingDispatch) ?></strong>
        </span>
    </div>
</div>

<?php if (!empty($totalDriverConflicts) && $totalDriverConflicts > 0): ?>
<!-- Driver Availability Conflict Alert Banner -->
<div class="alert alert-danger border-danger-subtle d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 shadow-sm mb-4">
    <div class="d-flex align-items-center gap-3">
        <span class="rounded-circle bg-danger text-white d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; font-size: 1.1rem;">
            <i class="fa-solid fa-user-xmark"></i>
        </span>
        <div>
            <h6 class="mb-0 fw-bold text-danger">Driver Availability Conflicts Detected (<?= $totalDriverConflicts ?> Request(s))</h6>
            <div class="small text-muted">One or more pending requests have designated drivers who are currently off-duty, on leave, or already committed to active trip tickets.</div>
        </div>
    </div>
    <form action="<?= base_url('dispatch/auto-deny-conflicts') ?>" method="POST" onsubmit="return confirm('Are you sure you want to automatically deny all requests whose requested drivers are unavailable?');" class="m-0">
        <?= !is_cli() ? csrf_field() : '' ?>
        <button type="submit" class="btn btn-sm btn-danger px-3 py-2 fw-semibold text-nowrap shadow-sm">
            <i class="fa-solid fa-ban me-1"></i> Auto-Deny Conflicted Requests (<?= $totalDriverConflicts ?>)
        </button>
    </form>
</div>
<?php endif; ?>

<?php if (!empty($pendingTier1)): ?>
<!-- Tier 1: VRS Review & Document Check Queue (Motorpool Dispatcher Authority) -->
<div class="card-panel mb-4" id="tier1-section">
    <div class="card-panel-header bg-white d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="rounded-circle bg-warning bg-opacity-10 text-warning d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.8rem;">
                <i class="fa-solid fa-file-circle-check"></i>
            </span>
            <div>
                <span class="fw-bold text-dark small text-uppercase">
                    Tier 1: VRS Review &amp; Document Check Queue
                    <span class="policy-badge policy-badge-warning ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="<strong>Tier 1 Motorpool Authority</strong><br>Ensures completeness of travel orders, driver availability, and vehicle passenger capacity matching before administrative endorsement." tabindex="0" role="button">
                        Tier 1 <i class="fa-solid fa-circle-question policy-info-icon"></i>
                    </span>
                </span>
                <div class="text-muted" style="font-size: 0.72rem;">Assigned to Motorpool Dispatcher &bull; Check travel documents, passenger specifications, and endorse for Tier 2 administrative clearance.</div>
            </div>
        </div>
        <span class="badge bg-warning bg-opacity-10 text-dark border border-warning-subtle rounded-pill small"><?= count($pendingTier1) ?> Pending Review</span>
    </div>
    <div class="table-responsive">
        <table class="table-minimal">
            <thead>
                <tr>
                    <th>VRS Control No</th>
                    <th>Requesting Division &amp; Official</th>
                    <th>Destination &amp; Purpose</th>
                    <th>Departure Schedule</th>
                    <th>Document Check</th>
                    <th>SLA (4h)</th>
                    <th class="text-end">Tier 1 Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingTier1 as $r): ?>
                    <tr>
                        <td>
                            <a href="<?= base_url('requests/' . $r['id']) ?>" class="mono fw-bold text-decoration-none text-dark d-block">
                                <?= esc($r['request_number']) ?>
                            </a>
                            <?php if ((int)$r['is_rush_request'] === 1): ?>
                                <span class="policy-badge policy-badge-danger" data-bs-toggle="tooltip" data-bs-placement="top" title="<strong>Emergency Rush Request (BR-03)</strong><br>Filed within 24 hours of departure. Requires verified justification attachment." tabindex="0" role="button">
                                    <i class="fa-solid fa-bolt"></i> RUSH (BR-03)
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($r['driver_conflict'])): ?>
                                <div class="mt-1">
                                    <span class="badge bg-danger text-white small" title="<?= esc($r['driver_conflict_reason']) ?>" style="font-size: 0.65rem;">
                                        <i class="fa-solid fa-user-xmark me-1"></i> Driver Unavailable
                                    </span>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark small"><?= esc($r['office_name'] ?? 'Central Office') ?></div>
                            <div class="text-muted" style="font-size: 0.72rem;"><?= esc($r['requestor_name']) ?></div>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark small text-truncate" style="max-width: 230px;" title="<?= esc($r['destination']) ?>">
                                <i class="fa-solid fa-location-dot text-danger me-1"></i><?= esc($r['destination']) ?>
                            </div>
                            <div class="text-muted small text-truncate" style="max-width: 230px; font-size: 0.72rem;" title="<?= esc($r['purpose']) ?>">
                                <?= esc($r['purpose']) ?>
                            </div>
                        </td>
                        <td>
                            <div class="mono small fw-semibold" style="font-size: 0.76rem;">
                                <?= date('M d, Y h:i A', strtotime($r['departure_time'])) ?>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($r['justification_file'])): ?>
                                <a href="<?= base_url($r['justification_file']) ?>" target="_blank" class="btn btn-xs btn-outline-primary" style="font-size: 0.72rem;">
                                    <i class="fa-solid fa-paperclip me-1"></i> Inspect File
                                </a>
                            <?php else: ?>
                                <span class="text-muted small" style="font-size: 0.72rem;">Standard Itinerary</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= $r['sla_info']['class'] ?> small py-1 px-2">
                                <i class="fa-solid fa-stopwatch me-1"></i><?= $r['sla_info']['text'] ?>
                            </span>
                        </td>
                        <td class="text-end table-action-cell">
                            <div class="table-actions">
                                <?php if (!empty($r['driver_conflict'])): ?>
                                    <form action="<?= base_url('dispatch/deny-driver-conflict/' . $r['id']) ?>" method="POST" class="d-inline m-0" onsubmit="return confirm('Auto-deny this request due to driver unavailability?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Deny request due to driver conflict">
                                            <i class="fa-solid fa-user-slash me-1"></i> Deny
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#dispatchTier1Modal<?= $r['id'] ?>">
                                    <i class="fa-solid fa-gavel me-1"></i> Review &amp; Endorse
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Pending Dispatch Queue (Priority Action) -->
<form id="batchMergeForm" action="<?= base_url('dispatch/merge-batch') ?>" method="POST" class="m-0">
    <?= !is_cli() ? csrf_field() : '' ?>
    <div class="card-panel mb-4">
        <div class="card-panel-header bg-white d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="rounded-circle bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.8rem;">
                    <i class="fa-solid fa-truck-fast"></i>
                </span>
                <span class="fw-bold text-dark small text-uppercase">Authorized Requests Awaiting Vehicle &amp; Driver Assignment</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="submit" id="mergeBatchBtn" class="btn btn-sm btn-outline-primary" disabled title="Select 2 or more requests with matching destinations or schedules to merge into a single vehicle dispatch">
                    <i class="fa-solid fa-code-merge me-1"></i> Merge &amp; Dispatch Selected (<span id="selectedCount">0</span>)
                </button>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill small"><?= count($pendingDispatch) ?> Requests</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table-minimal">
                <thead>
                    <tr>
                        <th style="width: 36px;" class="text-center">
                            <input type="checkbox" id="selectAllMerge" class="form-check-input" title="Select all requests for merging">
                        </th>
                        <th>VRS Control No</th>
                        <th>Requesting Division</th>
                        <th>Destination &amp; Purpose</th>
                        <th>Departure Schedule</th>
                        <th>Pax / Type</th>
                        <th class="text-end">Dispatch Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pendingDispatch)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-calendar-check fa-2x mb-2 d-block text-secondary opacity-50"></i>
                                No authorized requests awaiting dispatch. All cleared requests have been paired!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pendingDispatch as $r): ?>
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" name="selected_requests[]" value="<?= $r['id'] ?>" class="form-check-input merge-checkbox" onchange="updateMergeState()">
                                </td>
                                <td>
                                    <a href="<?= base_url('requests/' . $r['id']) ?>" class="mono fw-bold text-decoration-none text-dark d-block">
                                        <?= esc($r['request_number']) ?>
                                    </a>
                                    <?php if ((int)$r['is_rush_request'] === 1): ?>
                                        <span class="badge bg-danger text-white" style="font-size: 0.65rem;">
                                            <i class="fa-solid fa-bolt"></i> RUSH (BR-03)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark small"><?= esc($r['office_name'] ?? 'Central Office') ?></div>
                                    <div class="text-muted" style="font-size: 0.72rem;"><?= esc($r['requestor_name']) ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark small text-truncate" style="max-width: 250px;">
                                        <i class="fa-solid fa-location-dot text-danger me-1"></i><?= esc($r['destination']) ?>
                                    </div>
                                    <div class="text-muted small text-truncate" style="max-width: 250px; font-size: 0.72rem;">
                                        <?= esc($r['purpose']) ?>
                                    </div>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        <?php if (!empty($r['driver_conflict'])): ?>
                                            <span class="badge bg-danger text-white small" title="<?= esc($r['driver_conflict_reason']) ?>" style="font-size: 0.65rem;">
                                                <i class="fa-solid fa-user-xmark me-1"></i> Driver Unavailable
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($r['merge_candidates_count']) && $r['merge_candidates_count'] > 0): ?>
                                            <span class="badge bg-info bg-opacity-10 text-primary border border-info-subtle small" style="font-size: 0.65rem;" title="Eligible for carpool consolidation">
                                                <i class="fa-solid fa-code-merge me-1"></i> <?= $r['merge_candidates_count'] ?> Merge Match
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="mono small fw-semibold" style="font-size: 0.78rem;">
                                        <?= date('M d, Y h:i A', strtotime($r['departure_time'])) ?>
                                    </div>
                                    <div class="mono text-muted" style="font-size: 0.72rem;">
                                        Return: <?= date('M d, h:i A', strtotime($r['return_time'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="fa-solid fa-users me-1 text-muted"></i><?= esc($r['passenger_count']) ?> pax
                                    </span>
                                    <?php if ($r['requested_vehicle_type']): ?>
                                        <span class="badge bg-light text-primary border" style="font-size: 0.68rem;">
                                            <?= strtoupper($r['requested_vehicle_type']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end table-action-cell">
                                    <div class="table-actions">
                                        <?php if (!empty($r['driver_conflict'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" title="Auto-deny this request due to driver conflict" onclick="submitSingleDeny(<?= $r['id'] ?>)">
                                                <i class="fa-solid fa-user-slash me-1"></i> Deny
                                            </button>
                                        <?php endif; ?>
                                        <a href="<?= base_url('dispatch/assign/' . $r['id']) ?>" class="btn btn-sm btn-primary text-nowrap">
                                            <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Pair &amp; e-DTT
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>

<!-- Fleet Asset Status Board -->
<div class="row g-4">
    <!-- Vehicles with PMS Health -->
    <div class="col-lg-7">
        <div class="card-panel h-100">
            <div class="card-panel-header bg-white d-flex align-items-center justify-content-between">
                <span class="fw-bold text-dark small text-uppercase">
                    <i class="fa-solid fa-truck me-1 text-primary"></i> Vehicle Fleet &amp; 5,000 KM PMS Radar
                </span>
                <span class="badge bg-light text-muted border small"><?= count($vehicles) ?> Units</span>
            </div>
            <div class="table-responsive">
                <table class="table-minimal">
                    <thead>
                        <tr>
                            <th>Unit / Plate</th>
                            <th>Current Odo</th>
                            <th>5K PMS Status</th>
                            <th>Dispatch Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vehicles as $v): ?>
                            <tr>
                                <td>
                                    <div class="mono fw-bold text-dark" style="font-size: 0.85rem;"><?= esc($v['plate_number']) ?></div>
                                    <div class="text-muted small" style="font-size: 0.72rem;"><?= esc($v['make'] . ' ' . $v['model']) ?></div>
                                </td>
                                <td>
                                    <div class="mono small"><?= number_format((float)$v['odometer_km'], 1) ?> km</div>
                                </td>
                                <td>
                                    <?php
                                        $curr = (float)$v['odometer_km'];
                                        $next = (float)($v['next_pms_odometer'] ?? ($curr + 5000));
                                        $remain = max(0, $next - $curr);
                                        $isLocked = (int)($v['is_locked'] ?? 0) === 1;
                                    ?>
                                    <?php if ($isLocked): ?>
                                        <span class="badge bg-danger text-white small">
                                            <i class="fa-solid fa-lock me-1"></i> LOCKED (PMS Due)
                                        </span>
                                    <?php elseif ($remain <= 500): ?>
                                        <span class="badge bg-warning text-dark small">
                                            <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= number_format($remain, 0) ?> km to PMS
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle small">
                                            <i class="fa-solid fa-shield-check me-1"></i> <?= number_format($remain, 0) ?> km safe
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        $vStatusBadges = [
                                            'active'         => 'bg-success text-white',
                                            'in_transit'     => 'bg-primary text-white',
                                            'maintenance'    => 'bg-danger text-white',
                                            'out_of_service' => 'bg-secondary text-white',
                                        ];
                                        $vClass = $vStatusBadges[$v['vehicle_status'] ?? 'active'] ?? 'bg-secondary text-white';
                                    ?>
                                    <span class="badge <?= $vClass ?> small" style="font-size: 0.68rem;">
                                        <?= strtoupper($v['vehicle_status'] ?? 'active') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Certified Drivers Pool -->
    <div class="col-lg-5">
        <div class="card-panel h-100">
            <div class="card-panel-header bg-white d-flex align-items-center justify-content-between">
                <span class="fw-bold text-dark small text-uppercase">
                    <i class="fa-solid fa-id-card me-1 text-primary"></i> Certified Driver Pool
                </span>
                <span class="badge bg-light text-muted border small"><?= count($drivers) ?> Drivers</span>
            </div>
            <div class="p-3">
                <?php foreach ($drivers as $d): ?>
                    <div class="d-flex align-items-center justify-content-between p-2 mb-2 border rounded-2 bg-white">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-light border p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                <i class="fa-solid fa-user-tie text-secondary small"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark small"><?= esc($d['first_name'] . ' ' . $d['last_name']) ?></div>
                                <div class="mono text-muted" style="font-size: 0.7rem;"><?= esc($d['driver_code']) ?> &bull; <?= esc($d['license_number']) ?></div>
                            </div>
                        </div>
                        <div class="text-end">
                            <?php if ($d['status'] === 'available'): ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle small">Available</span>
                            <?php elseif ($d['status'] === 'on_trip'): ?>
                                <span class="badge bg-primary text-white small">On Trip</span>
                            <?php else: ?>
                                <span class="badge bg-secondary text-white small"><?= ucfirst($d['status']) ?></span>
                            <?php endif; ?>
                            <div class="mono text-muted" style="font-size: 0.68rem; margin-top: 2px;">
                                Safety: <?= $d['safety_score'] ?>%
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($pendingTier1)): ?>
    <?php foreach ($pendingTier1 as $r): ?>
        <div class="modal fade text-start" id="dispatchTier1Modal<?= $r['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <h6 class="modal-title fw-bold text-dark">
                            <i class="fa-solid fa-file-circle-check text-primary me-1"></i> Tier 1 Dispatcher Review &amp; Document Check: <?= esc($r['request_number']) ?>
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="<?= base_url('approvals/' . $r['id'] . '/action') ?>" method="POST">
                        <?= !is_cli() ? csrf_field() : '' ?>
                        <input type="hidden" name="redirect_to" value="<?= base_url('dispatch') ?>">
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <div class="small text-muted">Destination:</div>
                                <div class="fw-bold text-dark"><?= esc($r['destination']) ?></div>
                            </div>
                            <div class="mb-3">
                                <div class="small text-muted">Purpose:</div>
                                <div class="small text-dark p-2 rounded bg-light border"><?= nl2br(esc($r['purpose'])) ?></div>
                            </div>
                            <div class="mb-3">
                                <div class="small text-muted">Passengers:</div>
                                <div class="mono small text-dark p-2 rounded bg-light border"><?= nl2br(esc($r['passenger_names'])) ?></div>
                            </div>
                            <div class="mb-3">
                                <div class="small text-muted fw-semibold mb-1">
                                    <i class="fa-solid fa-file-lines text-primary me-1"></i> Document Check for VRS:
                                </div>
                                <?php if (!empty($r['justification_file'])): ?>
                                    <div class="alert alert-info border-info-subtle p-2 px-3 small mb-0 rounded d-flex align-items-center justify-content-between">
                                        <div>
                                            <strong>Attached Document / Justification:</strong>
                                            <?php if (!empty($r['justification_notes'])): ?>
                                                <div class="text-dark small mt-1"><?= esc($r['justification_notes']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <a href="<?= base_url($r['justification_file']) ?>" target="_blank" class="btn btn-sm btn-primary ms-2 flex-shrink-0">
                                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Inspect File
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="p-2 px-3 rounded bg-light border small text-muted">
                                        <i class="fa-solid fa-circle-info text-secondary me-1"></i> Standard VRS Filing &mdash; verified travel itinerary and passenger specifications.
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-dark">Tier 1 Endorsement / Specification Remarks</label>
                                <textarea name="remarks" class="form-control form-control-sm" rows="2" placeholder="e.g. Documents verified. Specification and travel requirements endorsed for Administrative Division Head clearance."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer bg-light p-3">
                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-outline-danger">
                                <i class="fa-solid fa-ban me-1"></i> Disapprove (Specification Disapproval)
                            </button>
                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success px-3 fw-semibold">
                                <i class="fa-solid fa-circle-check me-1"></i> Endorse (Clearance to Tier 2)
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
function updateMergeState() {
    const checkboxes = document.querySelectorAll('.merge-checkbox:checked');
    const count = checkboxes.length;
    const btn = document.getElementById('mergeBatchBtn');
    const badge = document.getElementById('selectedCount');
    if (badge) badge.innerText = count;
    if (btn) btn.disabled = (count < 2);
}

document.getElementById('selectAllMerge')?.addEventListener('change', function() {
    document.querySelectorAll('.merge-checkbox').forEach(cb => cb.checked = this.checked);
    updateMergeState();
});

function submitSingleDeny(requestId) {
    if (!confirm('Are you sure you want to deny this request due to driver unavailability?')) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= base_url('dispatch/deny-driver-conflict') ?>/' + requestId;
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '<?= !is_cli() ? csrf_token() : 'csrf_test_name' ?>';
    csrfInput.value = '<?= !is_cli() ? csrf_hash() : '' ?>';
    form.appendChild(csrfInput);
    document.body.appendChild(form);
    form.submit();
}
</script>

<?= $this->endSection() ?>
