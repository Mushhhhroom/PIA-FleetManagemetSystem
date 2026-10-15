<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--primary-accent); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            <i class="fa-solid fa-van-shuttle me-1"></i> Motorpool Division &bull; Heuristic Dispatch Engine
        </div>
        <h1 class="page-title mb-1">Dispatch Command & Asset Allocation</h1>
        <p class="text-muted small mb-0">Vehicle-driver matching, 5,000 km PMS safety locks, and e-DTT issuance.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <?php if (!empty($pendingTier1)): ?>
            <a href="#tier1-section" class="badge bg-warning bg-opacity-10 text-dark border border-warning-subtle p-2 px-3 small text-decoration-none">
                <i class="fa-solid fa-file-circle-check text-warning me-1"></i> Tier 1 Document Check: <strong><?= count($pendingTier1) ?></strong>
            </a>
        <?php endif; ?>
        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle p-2 px-3 small">
            <i class="fa-solid fa-check-circle me-1"></i> Ready for Dispatch: <strong><?= count($pendingDispatch) ?></strong>
        </span>
    </div>
</div>

<?php if (!empty($pendingTier1)): ?>
<!-- Tier 1: VRS Review & Document Check Queue (Motorpool Dispatcher Authority) -->
<div class="card-panel border border-warning shadow-sm mb-4" id="tier1-section">
    <div class="p-3 border-bottom bg-warning bg-opacity-10 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <span class="rounded-circle bg-warning text-dark d-inline-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.75rem;">
                <i class="fa-solid fa-file-circle-check"></i>
            </span>
            <div>
                <span class="fw-bold text-dark small text-uppercase">Tier 1: VRS Review &amp; Document Check Queue</span>
                <div class="text-muted" style="font-size: 0.72rem;">Assigned to Motorpool Dispatcher &bull; Check travel documents, passenger specifications, and endorse for Tier 2 administrative clearance.</div>
            </div>
        </div>
        <span class="badge bg-warning text-dark rounded-pill small"><?= count($pendingTier1) ?> Pending Review</span>
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
                                <a href="<?= base_url($r['justification_file']) ?>" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size: 0.72rem;">
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
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-primary py-1 px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#dispatchTier1Modal<?= $r['id'] ?>">
                                <i class="fa-solid fa-gavel me-1"></i> Review &amp; Endorse
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Pending Dispatch Queue (Priority Action) -->
<div class="card-panel border shadow-sm mb-4">
    <div class="p-3 border-bottom bg-light d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <span class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.75rem;">
                <i class="fa-solid fa-truck-fast"></i>
            </span>
            <span class="fw-bold text-dark small text-uppercase">Authorized Requests Awaiting Vehicle & Driver Assignment</span>
        </div>
        <span class="badge bg-primary rounded-pill small"><?= count($pendingDispatch) ?> Requests</span>
    </div>
    <div class="table-responsive">
        <table class="table-minimal">
            <thead>
                <tr>
                    <th>VRS Control No</th>
                    <th>Requesting Division</th>
                    <th>Destination & Purpose</th>
                    <th>Departure Schedule</th>
                    <th>Pax / Type</th>
                    <th class="text-end">Dispatch Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pendingDispatch)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-calendar-check fa-2x mb-2 d-block text-secondary opacity-50"></i>
                            No authorized requests awaiting dispatch. All cleared requests have been paired!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pendingDispatch as $r): ?>
                        <tr>
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
                            <td class="text-end">
                                <a href="<?= base_url('dispatch/assign/' . $r['id']) ?>" class="btn btn-sm btn-primary py-1 px-3 shadow-sm fw-semibold">
                                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Smart Pair & Issue e-DTT
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Fleet Asset Status Board -->
<div class="row g-4">
    <!-- Vehicles with PMS Health -->
    <div class="col-lg-7">
        <div class="card-panel border shadow-sm h-100">
            <div class="p-3 border-bottom bg-light d-flex align-items-center justify-content-between">
                <span class="fw-bold text-dark small text-uppercase">
                    <i class="fa-solid fa-truck me-1 text-primary"></i> Vehicle Fleet & 5,000 KM PMS Radar
                </span>
                <span class="badge bg-white text-muted border small"><?= count($vehicles) ?> Units</span>
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
        <div class="card-panel border shadow-sm h-100">
            <div class="p-3 border-bottom bg-light d-flex align-items-center justify-content-between">
                <span class="fw-bold text-dark small text-uppercase">
                    <i class="fa-solid fa-id-card me-1 text-primary"></i> Certified Driver Pool
                </span>
                <span class="badge bg-white text-muted border small"><?= count($drivers) ?> Drivers</span>
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
                        <?= csrf_field() ?>
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

<?= $this->endSection() ?>
