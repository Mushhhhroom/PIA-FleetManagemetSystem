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
        <a href="<?= base_url('requests/' . $request['id'] . '/print') ?>" target="_blank" class="btn-corp btn-corp-secondary text-decoration-none shadow-sm">
            <i class="fa-solid fa-print me-1"></i> Print Official VRS
        </a>
        <?php if ($request['status'] === 'approved' && in_array(session()->get('user_role'), ['admin', 'dispatcher'])): ?>
            <a href="<?= base_url('dispatch/assign/' . $request['id']) ?>" class="btn-corp btn-corp-primary text-decoration-none shadow-sm">
                <i class="fa-solid fa-van-shuttle me-1"></i> Dispatch Vehicle & Driver
            </a>
        <?php endif; ?>
        <?php if (!empty($request['ticket_id'])): ?>
            <a href="<?= base_url('tickets/' . $request['ticket_id']) ?>" class="btn btn-outline-primary btn-sm px-3 shadow-sm">
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
                $isOicDone = $request['oic_action'] === 'approved';
                $isOicPending = $request['status'] === 'pending_oic';
            ?>
            <div class="p-2 rounded <?= $isOicDone ? 'bg-success bg-opacity-10 border border-success-subtle' : ($isOicPending ? 'bg-warning bg-opacity-10 border border-warning-subtle' : 'bg-light border') ?>">
                <i class="fa-solid <?= $isOicDone ? 'fa-circle-check text-success' : ($isOicPending ? 'fa-spinner fa-spin text-warning' : 'fa-circle text-muted opacity-50') ?> d-block mb-1"></i>
                <div class="fw-semibold text-dark" style="font-size: 0.72rem;">2. Dispatcher Endorsed</div>
                <div class="mono text-muted" style="font-size: 0.65rem;">
                    <?= $isOicDone ? date('M d, H:i', strtotime($request['oic_action_at'])) : ($isOicPending ? 'Pending Action' : 'Awaiting') ?>
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
                        <label class="text-muted small d-block">Official Destination</label>
                        <div class="fw-semibold text-dark fs-6">
                            <i class="fa-solid fa-location-dot text-danger me-2"></i><?= esc($request['destination']) ?>
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
                <?php if (in_array($request['status'], ['pending_oic', 'pending_admin'])): ?>
                    <div class="mb-2">
                        <span class="badge <?= $request['sla_info']['class'] ?> fs-6 py-2 px-3">
                            <i class="fa-solid fa-clock me-1"></i> <?= $request['sla_info']['text'] ?>
                        </span>
                    </div>
                    <div class="small text-muted" style="font-size: 0.76rem;">
                        <?= $request['status'] === 'pending_oic'
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
                        <?php if ($request['oic_action'] === 'approved'): ?>
                            <span class="badge bg-success small"><i class="fa-solid fa-check"></i> Endorsed</span>
                        <?php elseif ($request['oic_action'] === 'rejected'): ?>
                            <span class="badge bg-danger small">Disapproved</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark small">Pending</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-dark small fw-medium"><?= esc($request['oic_approver_name'] ?? 'Motorpool Dispatcher') ?></div>
                    <?php if ($request['oic_action_at']): ?>
                        <div class="mono text-muted" style="font-size: 0.7rem;">
                            Action Date: <?= date('M d, Y h:i A', strtotime($request['oic_action_at'])) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($request['oic_remarks']): ?>
                        <div class="small text-muted fst-italic mt-1" style="font-size: 0.75rem;">
                            "<?= esc($request['oic_remarks']) ?>"
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
            $canTier1Act = ($userRole === 'dispatcher' || $userRole === 'admin') && $request['status'] === 'pending_oic';
            $canAdminAct = ($userRole === 'approver_admin' || $userRole === 'admin') && $request['status'] === 'pending_admin';
        ?>
        <?php if ($canTier1Act || $canAdminAct): ?>
            <div class="card-panel border border-primary shadow-sm p-3 mb-4 bg-primary bg-opacity-10">
                <div class="fw-bold text-primary small mb-2">
                    <i class="fa-solid fa-gavel me-1"></i> Approver Action Required
                </div>
                <p class="small text-dark mb-3" style="font-size: 0.78rem;">
                    You are authorized to review and act on this travel request.
                </p>
                <form action="<?= base_url('approvals/' . $request['id'] . '/action') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <textarea name="remarks" class="form-control form-control-sm" rows="2" placeholder="Approval or justification remarks..."></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" name="action" value="approve" class="btn btn-success btn-sm flex-fill">
                            <i class="fa-solid fa-check me-1"></i> <?= $canTier1Act ? 'Endorse Request' : 'Authorize Travel' ?>
                        </button>
                        <button type="submit" name="action" value="reject" class="btn btn-outline-danger btn-sm">
                            Disapprove
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
