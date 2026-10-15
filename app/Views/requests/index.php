<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--primary-accent); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            <i class="fa-solid fa-file-signature me-1"></i> Form No. ADMIN-F-018 rev2 &bull; PIA Motorpool
        </div>
        <h1 class="page-title mb-1">Vehicle Request Slips (VRS)</h1>
        <p class="text-muted small mb-0">Official travel authorization requests, multi-tier approvals, 4-hour escalation and 24-hour expiry SLA tracking.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('requests/new') ?>" class="btn-corp btn-corp-primary text-decoration-none shadow-sm">
            <i class="fa-solid fa-plus me-1"></i> File New Vehicle Request
        </a>
    </div>
</div>

<!-- SLA & Policy Info Banner -->
<div class="alert bg-white border border-primary-subtle shadow-sm p-3 mb-4 rounded-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
            <i class="fa-solid fa-clock-rotate-left fa-lg"></i>
        </div>
        <div>
            <div class="fw-semibold text-dark small">4-Hour Escalation SLA + 24-Hour Expiry Active (FR-1.4 / BR-02)</div>
            <div class="text-muted small" style="font-size: 0.78rem;">
                Requests must be submitted $\ge$ 24h prior to departure (BR-01). Un-actioned Tier&nbsp;1 requests escalate automatically to Tier&nbsp;2 after <strong>4 hours</strong>, and expire after 24 hours. Emergency overrides (FR-1.3) require post-trip documentation.
            </div>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2 py-1 small">
            <i class="fa-solid fa-bolt me-1"></i> BR-03: Rush Justification Gate
        </span>
    </div>
</div>

<!-- Filter Tabs -->
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="<?= base_url('requests') ?>" class="btn btn-sm <?= empty($activeFilter) || $activeFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary' ?> py-1 px-3 rounded-pill">
        All Requests
    </a>
    <a href="<?= base_url('requests?status=pending_dispatcher') ?>" class="btn btn-sm <?= $activeFilter === 'pending_dispatcher' ? 'btn-warning text-dark' : 'btn-outline-secondary' ?> py-1 px-3 rounded-pill">
        <i class="fa-solid fa-user-clock me-1"></i> Pending Dispatcher (Tier 1)
    </a>
    <a href="<?= base_url('requests?status=pending_admin') ?>" class="btn btn-sm <?= $activeFilter === 'pending_admin' ? 'btn-info text-white' : 'btn-outline-secondary' ?> py-1 px-3 rounded-pill">
        <i class="fa-solid fa-stamp me-1"></i> Pending Admin Head (Tier 2)
    </a>
    <a href="<?= base_url('requests?status=approved') ?>" class="btn btn-sm <?= $activeFilter === 'approved' ? 'btn-success' : 'btn-outline-secondary' ?> py-1 px-3 rounded-pill">
        <i class="fa-solid fa-check me-1"></i> Authorized (Ready for Dispatch)
    </a>
    <a href="<?= base_url('requests?status=dispatched') ?>" class="btn btn-sm <?= $activeFilter === 'dispatched' ? 'btn-primary' : 'btn-outline-secondary' ?> py-1 px-3 rounded-pill">
        <i class="fa-solid fa-van-shuttle me-1"></i> Dispatched (e-DTT Active)
    </a>
    <a href="<?= base_url('requests?rush=1') ?>" class="btn btn-sm <?= $isRushFilter ? 'btn-danger' : 'btn-outline-danger' ?> py-1 px-3 rounded-pill">
        <i class="fa-solid fa-fire me-1"></i> Same-Day Rush (BR-03)
    </a>
</div>

<!-- Requests Ledger Table -->
<div class="card-panel">
    <div class="table-responsive">
        <table class="table-minimal">
            <thead>
                <tr>
                    <th>VRS Request No</th>
                    <th>Division / Office</th>
                    <th>Destination & Purpose</th>
                    <th>Schedule Window</th>
                    <th>Passengers</th>
                    <th>SLA Countdown (4h)</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-file-circle-question fa-2x mb-2 d-block text-secondary opacity-50"></i>
                            No vehicle request slips matching this filter.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <td>
                                <a href="<?= base_url('requests/' . $r['id']) ?>" class="mono fw-bold text-decoration-none text-dark d-block">
                                    <?= esc($r['request_number']) ?>
                                </a>
                                <span class="badge bg-light text-muted border" style="font-size: 0.65rem;">
                                    <?= strtoupper($r['office_scope']) ?>
                                </span>
                                <?php if ((int)$r['is_rush_request'] === 1): ?>
                                    <span class="badge bg-danger text-white ms-1" style="font-size: 0.65rem;" title="BR-03 Rush Request: Filed <24h before departure">
                                        <i class="fa-solid fa-bolt"></i> RUSH
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark small"><?= esc($r['office_name'] ?? 'Central Office') ?></div>
                                <?php if (!empty($r['department_name'])): ?>
                                    <div class="text-primary" style="font-size: 0.72rem;">
                                        <i class="fa-solid fa-building-user me-1"></i><?= esc($r['department_name']) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="text-muted" style="font-size: 0.72rem;">Filed by: <?= esc($r['requestor_name']) ?></div>
                            </td>
                            <td>
                                <div class="fw-medium text-dark small text-truncate" style="max-width: 240px;" title="<?= esc($r['destination']) ?>">
                                    <i class="fa-solid fa-location-dot text-danger me-1"></i><?= esc($r['destination']) ?>
                                </div>
                                <div class="text-muted small text-truncate" style="max-width: 240px; font-size: 0.72rem;" title="<?= esc($r['purpose']) ?>">
                                    <?= esc($r['purpose']) ?>
                                </div>
                            </td>
                            <td>
                                <div class="mono small" style="font-size: 0.78rem;">
                                    <?= date('M d, Y h:i A', strtotime($r['departure_time'])) ?>
                                </div>
                                <div class="mono text-muted" style="font-size: 0.72rem;">
                                    to <?= date('M d, Y h:i A', strtotime($r['return_time'])) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="fa-solid fa-users me-1 text-muted"></i><?= esc($r['passenger_count']) ?> pax
                                </span>
                            </td>
                            <td>
                                <?php if (in_array($r['status'], ['pending_dispatcher', 'pending_admin'])): ?>
                                    <span class="badge <?= $r['sla_info']['class'] ?> small py-1 px-2">
                                        <i class="fa-solid fa-stopwatch me-1"></i><?= $r['sla_info']['text'] ?>
                                    </span>
                                <?php elseif ($r['status'] === 'expired'): ?>
                                    <span class="badge bg-secondary text-white small">
                                        <i class="fa-solid fa-ban me-1"></i>Expired
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle small">
                                        <i class="fa-solid fa-check me-1"></i>Met SLA
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                    $statusPills = [
                                        'pending_dispatcher' => ['label' => 'Pending Dispatcher', 'class' => 'bg-warning text-dark'],
                                        'pending_admin'      => ['label' => 'Pending Admin Head', 'class' => 'bg-info text-white'],
                                        'approved'           => ['label' => 'Authorized', 'class' => 'bg-success text-white'],
                                        'dispatched'         => ['label' => 'Dispatched', 'class' => 'bg-primary text-white'],
                                        'completed'          => ['label' => 'Completed', 'class' => 'bg-secondary text-white'],
                                        'rejected'           => ['label' => 'Disapproved', 'class' => 'bg-danger text-white'],
                                        'expired'            => ['label' => 'SLA Expired', 'class' => 'bg-dark text-white'],
                                    ];
                                    $st = $statusPills[$r['status']] ?? ['label' => ucfirst($r['status']), 'class' => 'bg-secondary text-white'];
                                ?>
                                <span class="badge <?= $st['class'] ?> py-1 px-2 small">
                                    <?= $st['label'] ?>
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <?php if ($r['status'] === 'pending_dispatcher' && in_array($userRole, ['admin', 'dispatcher'])): ?>
                                    <button type="button" class="btn btn-sm btn-primary py-1 px-2 shadow-sm text-decoration-none me-1" style="font-size: 0.75rem;" title="Review documents and endorse/disapprove" data-bs-toggle="modal" data-bs-target="#reqTier1Modal<?= $r['id'] ?>">
                                        <i class="fa-solid fa-file-circle-check me-1"></i> Review &amp; Endorse
                                    </button>
                                <?php elseif ($r['status'] === 'pending_admin' && in_array($userRole, ['admin', 'approver_admin'])): ?>
                                    <button type="button" class="btn btn-sm btn-success py-1 px-2 shadow-sm text-decoration-none me-1" style="font-size: 0.75rem;" title="Authorize official travel" data-bs-toggle="modal" data-bs-target="#reqAdminModal<?= $r['id'] ?>">
                                        <i class="fa-solid fa-stamp me-1"></i> Authorize
                                    </button>
                                <?php endif; ?>
                                <div class="dropdown d-inline-block">
                                    <button class="btn btn-sm btn-outline-secondary border-0" type="button" data-bs-toggle="dropdown">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border">
                                        <li>
                                            <a class="dropdown-item small" href="<?= base_url('requests/' . $r['id']) ?>">
                                                <i class="fa-solid fa-eye me-2 text-primary"></i> View Slip Details
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item small" href="<?= base_url('requests/' . $r['id'] . '/print') ?>" target="_blank">
                                                <i class="fa-solid fa-print me-2 text-secondary"></i> Print ADMIN-F-018 rev2
                                            </a>
                                        </li>
                                        <?php if ($r['status'] === 'pending_dispatcher' && in_array($userRole, ['admin', 'dispatcher'])): ?>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item small text-primary fw-semibold" href="<?= base_url('approvals') ?>">
                                                    <i class="fa-solid fa-file-circle-check me-2"></i> Review &amp; Endorse (Tier 1)
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        <?php if ($r['status'] === 'pending_admin' && in_array($userRole, ['admin', 'approver_admin'])): ?>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item small text-success fw-semibold" href="<?= base_url('approvals') ?>">
                                                    <i class="fa-solid fa-stamp me-2"></i> Administrative Clearance (Tier 2)
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        <?php if ($r['status'] === 'approved' && in_array($userRole, ['admin', 'dispatcher'])): ?>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item small text-success fw-semibold" href="<?= base_url('dispatch/assign/' . $r['id']) ?>">
                                                    <i class="fa-solid fa-van-shuttle me-2"></i> Assign Vehicle & Driver
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        <?php if (!empty($r['ticket_id'])): ?>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item small text-primary" href="<?= base_url('tickets/' . $r['ticket_id']) ?>">
                                                    <i class="fa-solid fa-ticket me-2"></i> View Driver's Trip Ticket
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($requests)): ?>
    <?php foreach ($requests as $r): ?>
        <?php if ($r['status'] === 'pending_dispatcher' && in_array($userRole, ['admin', 'dispatcher'])): ?>
            <div class="modal fade text-start" id="reqTier1Modal<?= $r['id'] ?>" tabindex="-1" aria-hidden="true">
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
                            <input type="hidden" name="redirect_to" value="<?= base_url('requests') ?>">
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
                                    <div class="mono small text-dark p-2 rounded bg-light border"><?= nl2br(esc($r['passenger_names'] ?? '')) ?></div>
                                </div>
                                <?php if (!empty($r['justification_file'])): ?>
                                    <div class="alert alert-info border-info-subtle p-2 px-3 small mb-3 rounded d-flex align-items-center justify-content-between">
                                        <div>
                                            <strong>Attached Justification Memo:</strong>
                                            <?php if (!empty($r['justification_notes'])): ?>
                                                <div class="text-dark small mt-1"><?= esc($r['justification_notes']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <a href="<?= base_url($r['justification_file']) ?>" target="_blank" class="btn btn-sm btn-primary ms-2 flex-shrink-0">
                                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Inspect File
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Tier 1 Endorsement Remarks</label>
                                    <textarea name="remarks" class="form-control form-control-sm" rows="2" placeholder="e.g. Travel requirements and itinerary verified. Endorsed for Administrative Division Head clearance."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer bg-light p-3">
                                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" name="action" value="reject" class="btn btn-sm btn-outline-danger">
                                    <i class="fa-solid fa-ban me-1"></i> Disapprove (Specification)
                                </button>
                                <button type="submit" name="action" value="approve" class="btn btn-sm btn-success px-3 fw-semibold">
                                    <i class="fa-solid fa-circle-check me-1"></i> Endorse (Clearance to Tier 2)
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($r['status'] === 'pending_admin' && in_array($userRole, ['admin', 'approver_admin'])): ?>
            <div class="modal fade text-start" id="reqAdminModal<?= $r['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-light">
                            <h6 class="modal-title fw-bold text-dark">
                                <i class="fa-solid fa-stamp text-success me-1"></i> Tier 2 Administrative Authorization: <?= esc($r['request_number']) ?>
                            </h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="<?= base_url('approvals/' . $r['id'] . '/action') ?>" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="redirect_to" value="<?= base_url('requests') ?>">
                            <div class="modal-body p-4">
                                <div class="mb-3">
                                    <div class="small text-muted">Destination &amp; Purpose:</div>
                                    <div class="fw-bold text-dark"><?= esc($r['destination']) ?></div>
                                    <div class="small text-muted mt-1"><?= nl2br(esc($r['purpose'])) ?></div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Authorization Remarks</label>
                                    <textarea name="remarks" class="form-control form-control-sm" rows="2" placeholder="e.g. Travel approved per official guidelines."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer bg-light p-3">
                                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" name="action" value="reject" class="btn btn-sm btn-outline-danger">
                                    <i class="fa-solid fa-ban me-1"></i> Disapprove
                                </button>
                                <button type="submit" name="action" value="approve" class="btn btn-sm btn-success px-3 fw-semibold">
                                    <i class="fa-solid fa-circle-check me-1"></i> Authorize Travel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

<?= $this->endSection() ?>
