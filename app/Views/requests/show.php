<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Breadcrumbs & Actions Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <a href="<?= base_url('requests') ?>" class="text-decoration-none text-muted small d-inline-block mb-1">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Vehicle Request Slips
        </a>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h1 class="page-title mb-0"><?= esc($request['request_number']) ?></h1>
            <span class="badge bg-light text-muted border mono" style="font-size: 0.75rem;">
                ADMIN-F-018 rev2
            </span>
            <?php if ((int)$request['is_rush_request'] === 1): ?>
                <span class="badge bg-danger text-white px-2 py-1 small">
                    <i class="fa-solid fa-bolt me-1"></i> SAME-DAY RUSH REQUEST (BR-03)
                </span>
            <?php endif; ?>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="<?= base_url('requests/' . $request['id'] . '/print') ?>" target="_blank" class="btn btn-secondary">
            <i class="fa-solid fa-print me-1"></i> Print Official VRS
        </a>
        <?php if ($request['status'] === 'pending_dispatcher' && in_array(session()->get('user_role'), ['admin', 'dispatcher'])): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#showTier1Modal">
                <i class="fa-solid fa-file-circle-check me-1"></i> Review &amp; Endorse (Tier 1)
            </button>
        <?php elseif ($request['status'] === 'pending_admin' && in_array(session()->get('user_role'), ['admin', 'approver_admin'])): ?>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#showTier2Modal">
                <i class="fa-solid fa-stamp me-1"></i> Authorize Travel (Tier 2)
            </button>
        <?php endif; ?>
        <?php if ($request['status'] === 'approved' && in_array(session()->get('user_role'), ['admin', 'dispatcher'])): ?>
            <a href="<?= base_url('dispatch/assign/' . $request['id']) ?>" class="btn btn-primary">
                <i class="fa-solid fa-van-shuttle me-1"></i> Dispatch Vehicle & Driver
            </a>
        <?php endif; ?>
        <?php if (!empty($request['ticket_id'])): ?>
            <a href="<?= base_url('tickets/' . $request['ticket_id']) ?>" class="btn btn-outline-primary">
                <i class="fa-solid fa-ticket me-1"></i> View Driver's Trip Ticket
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- 6-Stage Visual Workflow Progress Tracker -->
<div class="card-panel p-3 mb-4 border shadow-sm">
    <div class="small fw-semibold text-muted text-uppercase mono mb-3" style="font-size: 0.68rem; letter-spacing: 0.08em;">
        Official Travel & Fleet Lifecycle
    </div>
    <div class="row g-2 text-center">
        <!-- Stage 1: Submission -->
        <div class="col-4 col-md-2">
            <div class="p-2 rounded bg-success bg-opacity-10 border border-success-subtle">
                <i class="fa-solid fa-file-circle-check text-success d-block mb-1"></i>
                <div class="fw-semibold text-dark" style="font-size: 0.72rem;">1. Filed</div>
                <div class="mono text-muted" style="font-size: 0.65rem;"><?= date('M d, H:i', strtotime($request['created_at'])) ?></div>
            </div>
        </div>

        <!-- Stage 2: Motorpool Dispatcher Endorsement (Tier 1) -->
        <div class="col-4 col-md-2">
            <?php
                $isDispDone = ($request['dispatcher_action'] ?? null) === 'approved';
                $isDispPending = ($request['status'] === 'pending_dispatcher');
                $dispActionAt = $request['dispatcher_action_at'] ?? null;
            ?>
            <div class="p-2 rounded <?= $isDispDone ? 'bg-success bg-opacity-10 border border-success-subtle' : ($isDispPending ? 'bg-warning bg-opacity-10 border border-warning-subtle' : 'bg-light border') ?>">
                <i class="fa-solid <?= $isDispDone ? 'fa-circle-check text-success' : ($isDispPending ? 'fa-spinner fa-spin text-warning' : 'fa-circle text-muted opacity-50') ?> d-block mb-1"></i>
                <div class="fw-semibold text-dark" style="font-size: 0.72rem;">2. Dispatcher Endorsed</div>
                <div class="mono text-muted" style="font-size: 0.65rem;">
                    <?= $isDispDone && $dispActionAt ? date('M d, H:i', strtotime($dispActionAt)) : ($isDispPending ? 'Pending Action' : 'Awaiting') ?>
                </div>
            </div>
        </div>

        <!-- Stage 3: Admin Head Approval -->
        <div class="col-4 col-md-2">
            <?php
                $isAdminDone = $request['admin_action'] === 'approved';
                $isAdminPending = $request['status'] === 'pending_admin';
            ?>
            <div class="p-2 rounded <?= $isAdminDone ? 'bg-success bg-opacity-10 border border-success-subtle' : ($isAdminPending ? 'bg-info bg-opacity-10 border border-info-subtle' : 'bg-light border') ?>">
                <i class="fa-solid <?= $isAdminDone ? 'fa-circle-check text-success' : ($isAdminPending ? 'fa-spinner fa-spin text-info' : 'fa-circle text-muted opacity-50') ?> d-block mb-1"></i>
                <div class="fw-semibold text-dark" style="font-size: 0.72rem;">3. Admin Head</div>
                <div class="mono text-muted" style="font-size: 0.65rem;">
                    <?= $isAdminDone ? date('M d, H:i', strtotime($request['admin_action_at'])) : ($isAdminPending ? 'Pending Action' : 'Awaiting') ?>
                </div>
            </div>
        </div>

        <!-- Stage 4: Dispatch -->
        <div class="col-4 col-md-2">
            <?php
                $isDispatched = in_array($request['status'], ['dispatched', 'completed']);
            ?>
            <div class="p-2 rounded <?= $isDispatched ? 'bg-primary bg-opacity-10 border border-primary-subtle' : 'bg-light border' ?>">
                <i class="fa-solid <?= $isDispatched ? 'fa-van-shuttle text-primary' : 'fa-circle text-muted opacity-50' ?> d-block mb-1"></i>
                <div class="fw-semibold text-dark" style="font-size: 0.72rem;">4. Dispatched</div>
                <div class="mono text-muted" style="font-size: 0.65rem;">
                    <?= $isDispatched ? 'e-DTT Active' : 'Queue' ?>
                </div>
            </div>
        </div>

        <!-- Stage 5: Gate Checkpoint -->
        <div class="col-4 col-md-2">
            <?php
                $isGatePassed = !empty($request['ticket_status']) && in_array($request['ticket_status'], ['departed', 'returned', 'completed']);
            ?>
            <div class="p-2 rounded <?= $isGatePassed ? 'bg-success bg-opacity-10 border border-success-subtle' : 'bg-light border' ?>">
                <i class="fa-solid <?= $isGatePassed ? 'fa-shield-halved text-success' : 'fa-circle text-muted opacity-50' ?> d-block mb-1"></i>
                <div class="fw-semibold text-dark" style="font-size: 0.72rem;">5. Gate Verified</div>
                <div class="mono text-muted" style="font-size: 0.65rem;">
                    <?= $isGatePassed ? 'QR Cleared' : 'Pending Gate' ?>
                </div>
            </div>
        </div>

        <!-- Stage 6: Audit Reconciliation -->
        <div class="col-4 col-md-2">
            <?php
                $isCompleted = $request['status'] === 'completed';
            ?>
            <div class="p-2 rounded <?= $isCompleted ? 'bg-secondary bg-opacity-10 border border-secondary-subtle' : 'bg-light border' ?>">
                <i class="fa-solid <?= $isCompleted ? 'fa-file-invoice text-dark' : 'fa-circle text-muted opacity-50' ?> d-block mb-1"></i>
                <div class="fw-semibold text-dark" style="font-size: 0.72rem;">6. COA Reconciled</div>
                <div class="mono text-muted" style="font-size: 0.65rem;">
                    <?= $isCompleted ? 'Audited' : 'Pending Return' ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Request Details -->
    <div class="col-lg-8">
        <!-- Main VRS Details Card -->
        <div class="card-panel border shadow-sm mb-4">
            <div class="p-3 border-bottom bg-light d-flex align-items-center justify-content-between">
                <span class="small fw-bold text-dark text-uppercase">
                    <i class="fa-solid fa-list-check me-2 text-primary"></i> Travel Request Specifications
                </span>
                <span class="badge bg-white text-dark border small mono">
                    Scope: <?= strtoupper($request['office_scope']) ?>
                </span>
            </div>
            <div class="p-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="text-muted small d-block">Originating Office / Division</label>
                        <div class="fw-bold text-dark"><?= esc($request['office_name'] ?? 'PIA Central Office') ?></div>
                        <div class="text-muted small"><?= esc($request['office_code'] ?? 'CO') ?> &bull; <?= esc($request['office_address'] ?? 'Diliman, Quezon City') ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small d-block">Department Information</label>
                        <?php if (!empty($request['department_name'])): ?>
                            <div class="fw-bold text-dark"><?= esc($request['department_name']) ?></div>
                            <div class="text-muted small"><?= esc($request['department_code'] ?? '') ?><?php if (!empty($request['department_head'])): ?> &bull; Head: <?= esc($request['department_head']) ?><?php endif; ?></div>
                        <?php else: ?>
                            <div class="text-muted small fst-italic">Not specified</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small d-block">Filing Officer / Requestor</label>
                        <div class="fw-bold text-dark"><?= esc($request['requestor_full_name'] ?? $request['requestor_name']) ?></div>
                        <div class="text-muted small"><?= esc($request['requestor_designation'] ?? 'PIA Personnel') ?> (<?= esc($request['requestor_email'] ?? '') ?>)</div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="text-muted small d-block">Official Destination / Event Location</label>
                        <div class="fw-semibold text-dark fs-6 mb-2">
                            <i class="fa-solid fa-location-dot text-danger me-2"></i><?= esc($request['destination']) ?>
                        </div>

                        <!-- Google Maps Event Location & Travel Corridor Card -->
                        <div class="rounded-3 border overflow-hidden mt-2" style="background: #f8fafc;">
                            <div class="p-2 bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle mono" style="font-size: 0.68rem;">
                                        <i class="fa-solid fa-map-location-dot me-1"></i> Google Maps
                                    </span>
                                    <span class="small fw-semibold text-dark" style="font-size: 0.75rem;">Event Location &amp; Travel Corridor</span>
                                </div>
                                <div class="d-flex gap-1 align-items-center flex-wrap">
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle mono" id="vrsShowDistBadge" style="font-size: 0.68rem;">-- km</span>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle mono" id="vrsShowEtaBadge" style="font-size: 0.68rem;">-- min ETA</span>
                                    <a id="vrsShowGoogleLink"
                                       href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($request['destination'] . ', Philippines') ?>"
                                       target="_blank" rel="noopener"
                                       class="btn btn-xs btn-outline-danger py-1 px-2 fw-semibold text-decoration-none"
                                       style="font-size: 0.68rem;">
                                        <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> View on Google Maps
                                    </a>
                                    <a id="vrsShowDirectionsLink"
                                       href="https://www.google.com/maps/dir/?api=1&origin=14.657200,121.045000&destination=<?= urlencode($request['destination'] . ', Philippines') ?>&travelmode=driving"
                                       target="_blank" rel="noopener"
                                       class="btn btn-xs btn-light border py-1 px-2 fw-semibold text-decoration-none"
                                       style="font-size: 0.68rem;">
                                        <i class="fa-solid fa-diamond-turn-right text-primary me-1"></i> Directions
                                    </a>
                                </div>
                            </div>
                            <div id="vrsShowMap" style="height: 220px; width: 100%; z-index: 1;"></div>
                            <div class="px-3 py-1 bg-white border-top d-flex justify-content-between align-items-center small" style="font-size: 0.72rem;">
                                <span class="text-muted" id="vrsShowSummary">
                                    <i class="fa-solid fa-route text-primary me-1"></i> Origin: <strong>PIA Central Office Motorpool</strong> &rarr; Event: <strong><?= esc($request['destination']) ?></strong>
                                </span>
                                <span class="mono text-muted" style="font-size: 0.65rem;">
                                    Google Maps Routing Engine
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="text-muted small d-block">Purpose of Travel & Official Agenda</label>
                        <div class="p-3 rounded bg-light border text-dark small" style="line-height: 1.6;">
                            <?= nl2br(esc($request['purpose'])) ?>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="text-muted small d-block">Scheduled Departure</label>
                        <div class="mono fw-bold text-dark">
                            <i class="fa-regular fa-calendar me-1 text-primary"></i> <?= date('F d, Y &bull; h:i A', strtotime($request['departure_time'])) ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small d-block">Expected Return</label>
                        <div class="mono fw-bold text-dark">
                            <i class="fa-regular fa-calendar-check me-1 text-success"></i> <?= date('F d, Y &bull; h:i A', strtotime($request['return_time'])) ?>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="text-muted small d-block mb-1">
                        Authorized Passenger Manifest (<?= esc($request['passenger_count']) ?> Passengers)
                    </label>
                    <div class="p-3 rounded bg-light border text-dark small mono" style="line-height: 1.6;">
                        <?= nl2br(esc($request['passenger_names'])) ?>
                    </div>
                </div>

                <!-- Emergency Justification Card (BR-03) -->
                <?php if ((int)$request['is_rush_request'] === 1): ?>
                    <div class="p-3 rounded-3 border border-danger-subtle bg-danger bg-opacity-10 mt-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-danger fw-bold small">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i> BR-03 Emergency Justification File
                            </span>
                            <span class="badge bg-danger text-white small">RUSH GATE VALIDATED</span>
                        </div>
                        <div class="text-dark small mb-2">
                            <strong>Explanation:</strong> <?= esc($request['justification_notes'] ?? 'Emergency state news coverage deployment.') ?>
                        </div>
                        <?php if (!empty($request['justification_file'])): ?>
                            <a href="<?= base_url($request['justification_file']) ?>" target="_blank" class="btn btn-sm btn-outline-danger bg-white">
                                <i class="fa-solid fa-file-pdf me-1"></i> View Attached Justification Document
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Approvals, SLA, & Dispatch Status -->
    <div class="col-lg-4">
        <!-- 4h Escalation / 24h Expiry SLA Status Card -->
        <div class="card-panel border shadow-sm mb-4">
            <div class="p-3 border-bottom bg-light">
                <span class="small fw-bold text-dark text-uppercase">
                    <i class="fa-solid fa-stopwatch me-1 text-primary"></i> Approval SLA — 4h Escalation / 24h Expiry (FR-1.4)
                </span>
            </div>
            <div class="p-3 text-center">
                <?php if (in_array($request['status'], ['pending_dispatcher', 'pending_admin'])): ?>
                    <div class="mb-2">
                        <span class="badge <?= $request['sla_info']['class'] ?> fs-6 py-2 px-3">
                            <i class="fa-solid fa-clock me-1"></i> <?= $request['sla_info']['text'] ?>
                        </span>
                    </div>
                    <div class="small text-muted" style="font-size: 0.76rem;">
                        <?= $request['status'] === 'pending_dispatcher'
                            ? 'Escalates to Tier 2 automatically after 4 hours.'
                            : 'Final authorization deadline (auto-expiry after 24 hours).' ?><br>
                        Deadline: <strong class="mono text-dark"><?= date('M d, Y h:i A', strtotime($request['sla_deadline'])) ?></strong>
                    </div>
                <?php elseif ($request['status'] === 'expired'): ?>
                    <div class="badge bg-dark fs-6 py-2 px-3 mb-2">
                        <i class="fa-solid fa-ban me-1"></i> SLA EXPIRED
                    </div>
                    <div class="small text-danger" style="font-size: 0.76rem;">
                        This request auto-expired per Policy BR-02 due to administrative non-action within 24 hours.
                    </div>
                <?php else: ?>
                    <div class="badge bg-success bg-opacity-10 text-success border border-success-subtle fs-6 py-2 px-3 mb-2">
                        <i class="fa-solid fa-circle-check me-1"></i> SLA Compliant
                    </div>
                    <div class="small text-muted" style="font-size: 0.76rem;">
                        Approved within the official turnaround benchmark.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($request['is_emergency_override'])): ?>
            <!-- FR-1.3 Emergency Override / Post-Trip Documentation -->
            <div class="card-panel border shadow-sm mb-4" style="border-left: 4px solid #dc2626 !important;">
                <div class="p-3 border-bottom bg-light">
                    <span class="small fw-bold text-danger text-uppercase">
                        <i class="fa-solid fa-bolt me-1"></i> FR-1.3 Emergency Override
                    </span>
                </div>
                <div class="p-3">
                    <div class="small text-muted mb-2">
                        Authorized by the Administrative Division Chief / Motorpool Head.
                        <?= $request['emergency_override_remarks']
                            ? '<div class="text-dark fst-italic p-2 rounded bg-light border mt-1">' . esc($request['emergency_override_remarks']) . '</div>'
                            : '' ?>
                        <div class="mono mt-1" style="font-size: 0.7rem;">
                            <?= !empty($request['escalated_at']) ? 'Escalated: ' . esc($request['escalated_at']) : '' ?>
                        </div>
                    </div>

                    <div class="kpi-label mb-1">Post-Trip Documentation (FR-1.3)</div>
                    <?php
                        $docStatus = $request['post_trip_doc_status'] ?? 'pending';
                        $docBadge  = match ($docStatus) {
                            'submitted' => 'bg-success',
                            'overdue'   => 'bg-danger',
                            default     => 'bg-warning text-dark',
                        };
                    ?>
                    <span class="badge <?= $docBadge ?>"><?= esc(str_replace('_', ' ', ucfirst($docStatus))) ?></span>
                    <?php if (!empty($request['post_trip_doc_due']) && $docStatus !== 'submitted'): ?>
                        <div class="small text-danger mt-1" style="font-size: 0.72rem;">
                            Due: <span class="mono"><?= esc(date('M d, Y h:i A', strtotime($request['post_trip_doc_due']))) ?></span>
                            (24 hours after trip completion)
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- 2-Tier Approval Signatures Card -->
        <div class="card-panel border shadow-sm mb-4">
            <div class="p-3 border-bottom bg-light">
                <span class="small fw-bold text-dark text-uppercase">
                    <i class="fa-solid fa-signature me-1 text-primary"></i> Administrative Signatures
                </span>
            </div>
            <div class="p-3">
                <!-- Tier 1: Motorpool Dispatcher Endorsement -->
                <div class="border-bottom pb-3 mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="fw-semibold text-dark small">1. Motorpool Dispatcher (Tier 1)</span>
                        <?php if ($request['dispatcher_action'] === 'approved'): ?>
                            <span class="badge bg-success small"><i class="fa-solid fa-check"></i> Endorsed</span>
                        <?php elseif ($request['dispatcher_action'] === 'rejected'): ?>
                            <span class="badge bg-danger small">Disapproved</span>
                        <?php elseif (!empty($request['escalated_at'])): ?>
                            <span class="badge bg-warning text-dark small" title="No Tier 1 action within <?= \App\Services\SlaService::ESCALATION_HOURS ?>h (FR-1.4)">Auto-escalated</span>
                        <?php elseif (!empty($request['is_emergency_override'])): ?>
                            <span class="badge bg-danger small">Bypassed (Emergency)</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark small">Pending</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-dark small fw-medium">
                        <?= !empty($request['dispatcher_action']) ? esc($request['dispatcher_approver_name'] ?? 'Motorpool Dispatcher') : 'Motorpool Dispatcher (no action recorded)' ?>
                    </div>
                    <?php if ($request['dispatcher_action_at']): ?>
                        <div class="mono text-muted" style="font-size: 0.7rem;">
                            Action Date: <?= date('M d, Y h:i A', strtotime($request['dispatcher_action_at'])) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($request['dispatcher_remarks']): ?>
                        <div class="small text-muted fst-italic mt-1" style="font-size: 0.75rem;">
                            "<?= esc($request['dispatcher_remarks']) ?>"
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tier 2: Administrative Division Head (Atty. Julius S. De Peralta) -->
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="fw-semibold text-dark small">2. Admin Division Head</span>
                        <?php if ($request['admin_action'] === 'approved'): ?>
                            <span class="badge bg-success small"><i class="fa-solid fa-check"></i> Authorized</span>
                        <?php elseif ($request['admin_action'] === 'rejected'): ?>
                            <span class="badge bg-danger small">Disapproved</span>
                        <?php elseif ($request['status'] === 'pending_admin'): ?>
                            <span class="badge bg-info text-white small">Pending</span>
                        <?php else: ?>
                            <span class="badge bg-secondary small">Awaiting Tier 1</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-dark small fw-medium"><?= esc($request['admin_approver_name'] ?? 'Atty. Julius S. De Peralta') ?></div>
                    <div class="text-muted" style="font-size: 0.7rem;">Head, Administrative Division</div>
                    <?php if ($request['admin_action_at']): ?>
                        <div class="mono text-muted" style="font-size: 0.7rem;">
                            Action Date: <?= date('M d, Y h:i A', strtotime($request['admin_action_at'])) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($request['admin_remarks']): ?>
                        <div class="small text-muted fst-italic mt-1" style="font-size: 0.75rem;">
                            "<?= esc($request['admin_remarks']) ?>"
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Approver Quick Action (if applicable) -->
        <?php
            $userRole = session()->get('user_role');
            $sessUserId = (int) session()->get('user_id');
            $isOwnRequest = (int) $request['requestor_id'] === $sessUserId;
            $isTier1Endorser = $request['dispatcher_action'] === 'approved' && (int) $request['dispatcher_approver_id'] === $sessUserId;
            $canTier1Act = ($userRole === 'dispatcher' || $userRole === 'admin') && $request['status'] === 'pending_dispatcher';
            $canAdminAct = ($userRole === 'approver_admin' || $userRole === 'admin') && $request['status'] === 'pending_admin' && !$isTier1Endorser;
        ?>
        <?php if ($canTier1Act || $canAdminAct): ?>
            <div class="card-panel border border-primary shadow-sm p-3 mb-4 bg-primary bg-opacity-10">
                <div class="fw-bold text-primary small mb-2">
                    <i class="fa-solid fa-gavel me-1"></i> <?= $canTier1Act ? 'Tier 1 Dispatcher Document Check & Endorsement' : 'Tier 2 Administrative Authorization Required' ?>
                </div>
                <p class="small text-dark mb-3" style="font-size: 0.78rem;">
                    <?= $canTier1Act ? 'You are assigned as Motorpool Dispatcher to check VRS documents and specifications.' : 'You are authorized to review and act on this travel request.' ?>
                </p>
                <form action="<?= base_url('approvals/' . $request['id'] . '/action') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect_to" value="<?= base_url('requests/' . $request['id']) ?>">
                    <div class="mb-2">
                        <textarea name="remarks" class="form-control form-control-sm" rows="2" placeholder="Endorsement or justification remarks..."></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" name="action" value="approve" class="btn btn-success btn-sm flex-fill">
                            <i class="fa-solid fa-check me-1"></i> <?= $canTier1Act ? 'Endorse (Clearance to Tier 2)' : 'Authorize Travel' ?>
                        </button>
                        <button type="submit" name="action" value="reject" class="btn btn-outline-danger btn-sm">
                            <?= $canTier1Act ? 'Disapprove (Specification)' : 'Disapprove' ?>
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal for Tier 1 Dispatcher Review & Endorsement -->
<?php if ($request['status'] === 'pending_dispatcher' && in_array(session()->get('user_role'), ['admin', 'dispatcher'])): ?>
    <div class="modal fade text-start" id="showTier1Modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h6 class="modal-title fw-bold text-dark">
                        <i class="fa-solid fa-file-circle-check text-primary me-1"></i> Tier 1 Dispatcher Review &amp; Document Check
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="<?= base_url('approvals/' . $request['id'] . '/action') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect_to" value="<?= base_url('requests/' . $request['id']) ?>">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <div class="small text-muted">VRS Control:</div>
                            <div class="fw-bold text-dark mono"><?= esc($request['request_number']) ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="small text-muted">Destination &amp; Purpose:</div>
                            <div class="fw-semibold text-dark"><?= esc($request['destination']) ?></div>
                            <div class="small text-muted mt-1"><?= nl2br(esc($request['purpose'])) ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="small text-muted">Passengers (<?= esc($request['passenger_count']) ?> pax):</div>
                            <div class="mono small text-dark p-2 rounded bg-light border"><?= nl2br(esc($request['passenger_names'])) ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Specification &amp; Document Review Remarks</label>
                            <textarea name="remarks" class="form-control form-control-sm" rows="3" placeholder="e.g. Travel itinerary and passenger specifications verified. Endorsed for Administrative Division Head clearance."></textarea>
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

<!-- Modal for Tier 2 Administrative Division Head Authorization -->
<?php if ($request['status'] === 'pending_admin' && in_array(session()->get('user_role'), ['admin', 'approver_admin'])): ?>
    <div class="modal fade text-start" id="showTier2Modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h6 class="modal-title fw-bold text-dark">
                        <i class="fa-solid fa-stamp text-success me-1"></i> Tier 2 Administrative Division Head Authorization
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="<?= base_url('approvals/' . $request['id'] . '/action') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect_to" value="<?= base_url('requests/' . $request['id']) ?>">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <div class="small text-muted">VRS Control:</div>
                            <div class="fw-bold text-dark mono"><?= esc($request['request_number']) ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="small text-muted">Tier 1 Endorsement:</div>
                            <div class="small text-success fw-semibold">
                                <i class="fa-solid fa-check-double me-1"></i> Endorsed by <?= esc($request['dispatcher_approver_name'] ?? 'Motorpool Dispatcher') ?>
                            </div>
                            <?php if (!empty($request['dispatcher_remarks'])): ?>
                                <div class="text-muted small fst-italic">"<?= esc($request['dispatcher_remarks']) ?>"</div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Administrative Authorization Remarks</label>
                            <textarea name="remarks" class="form-control form-control-sm" rows="3" placeholder="e.g. Travel approved per official guidelines. Authorized for vehicle and driver dispatch."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light p-3">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="action" value="reject" class="btn btn-sm btn-outline-danger">
                            <i class="fa-solid fa-ban me-1"></i> Disapprove
                        </button>
                        <button type="submit" name="action" value="approve" class="btn btn-sm btn-success px-3 fw-semibold">
                            <i class="fa-solid fa-circle-check me-1"></i> Authorize Official Travel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const vrsShowMapEl = document.getElementById('vrsShowMap');
    if (!vrsShowMapEl || typeof L === 'undefined') return;

    const originLat   = 14.657200;
    const originLng   = 121.045000;
    const destination = <?= json_encode($request['destination'] ?? '') ?>;

    const vrsShowMap = L.map('vrsShowMap', {
        zoomControl: true,
        attributionControl: false,
        scrollWheelZoom: false,
        dragging: true,
        touchZoom: true
    }).setView([originLat, originLng], 12);

    // Google Maps Primary Tile Layer
    L.tileLayer('https://mt{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        subdomains: ['0', '1', '2', '3'],
        attribution: '&copy; Google Maps'
    }).addTo(vrsShowMap);

    const layerGroup = L.featureGroup().addTo(vrsShowMap);

    const hqIcon = L.divIcon({
        className: 'vrs-hq-pin',
        html: `<div title="PIA Central Office Motorpool" style="background:#0f172a;color:#fff;border-radius:50%;width:26px;height:26px;display:flex;align-items:center;justify-content:center;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,0.3);font-size:11px;"><i class="fa-solid fa-building-shield"></i></div>`,
        iconSize: [26, 26],
        iconAnchor: [13, 13]
    });
    L.marker([originLat, originLng], { icon: hqIcon })
        .bindPopup('<strong>Origin: PIA Central Office Motorpool</strong><br><span class="small text-muted">Visayas Ave, Quezon City</span>')
        .addTo(layerGroup);

    if (destination && destination.trim() !== '') {
        fetch('<?= base_url('api/v1/route') ?>?destination=' + encodeURIComponent(destination))
            .then(res => res.json())
            .then(data => {
                if (data.status !== 'success') return;

                if (data.destination && data.destination.lat && data.destination.lng) {
                    const destIcon = L.divIcon({
                        className: 'vrs-dest-pin',
                        html: `<div title="${destination}" style="background:#dc2626;color:#fff;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,0.3);font-size:12px;"><i class="fa-solid fa-flag-checkered"></i></div>`,
                        iconSize: [28, 28],
                        iconAnchor: [14, 14]
                    });
                    L.marker([data.destination.lat, data.destination.lng], { icon: destIcon })
                        .bindPopup(`<strong>Event Location</strong><br><span class="small text-dark">${destination}</span>`)
                        .addTo(layerGroup);
                }

                if (data.leaflet_coords && data.leaflet_coords.length > 1) {
                    L.polyline(data.leaflet_coords, {
                        color: '#2563eb',
                        weight: 4,
                        opacity: 0.9,
                        lineJoin: 'round'
                    }).addTo(layerGroup);
                }

                vrsShowMap.fitBounds(layerGroup.getBounds().pad(0.2));
                setTimeout(() => { if (typeof vrsShowMap !== 'undefined') vrsShowMap.invalidateSize(); }, 350);

                const distBadge = document.getElementById('vrsShowDistBadge');
                const etaBadge  = document.getElementById('vrsShowEtaBadge');
                if (distBadge) distBadge.innerText = `${data.distance_km} km`;
                if (etaBadge)  etaBadge.innerText  = `~${data.duration_min} min ETA`;
            })
            .catch(err => console.warn('VRS event map route load error:', err));
    }
});
</script>
<?= $this->endSection() ?>
