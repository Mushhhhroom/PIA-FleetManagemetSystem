<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--primary-accent); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            Module 2 &bull; Fleet Segregation &amp; Multi-Day Allocation Board &bull; FR-2.1 / FR-2.2
        </div>
        <h1 class="page-title mb-1">Interactive Dispatch Calendar &amp; Allocation Board</h1>
        <p class="text-muted small mb-0">
            Authoritative lifecycle tracking for every Vehicle Request Slip (VRS). Accurately monitors review stages, multi-day trip schedules, asset assignments, and prevents double-booking.
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <a href="<?= base_url('dispatch') ?>" class="btn btn-secondary">
            <i class="fa-solid fa-table-list me-1"></i> Dispatch Board
        </a>
        <a href="<?= base_url('dispatch/calendar?month=' . esc($prevMonth)) ?>" class="btn-icon-sm btn-outline-secondary" title="Previous Month">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
        <a href="<?= base_url('dispatch/calendar?month=' . date('Y-m')) ?>" class="btn btn-sm btn-outline-secondary" title="Jump to Current Month">
            <i class="fa-solid fa-calendar-day me-1"></i> Today
        </a>
        <a href="<?= base_url('dispatch/calendar?month=' . esc($nextMonth)) ?>" class="btn-icon-sm btn-outline-secondary" title="Next Month">
            <i class="fa-solid fa-chevron-right"></i>
        </a>
        <form action="<?= base_url('dispatch/calendar') ?>" method="GET" class="d-inline-flex m-0">
            <input type="month" name="month" class="form-control form-control-sm" value="<?= esc($month) ?>" onchange="this.form.submit()">
        </form>
    </div>
</div>

<!-- Process Lifecycle KPI Bar -->
<div class="row g-2 mb-4">
    <div class="col-6 col-md">
        <div class="card-panel p-3 h-100 border-start border-4 border-primary">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem;">Total Requests</div>
                    <div class="h4 mb-0 fw-bold text-dark mt-1"><?= (int)($kpi['total'] ?? 0) ?></div>
                </div>
                <span class="badge bg-primary-subtle text-primary p-2 rounded-circle">
                    <i class="fa-solid fa-folder-open"></i>
                </span>
            </div>
            <div class="text-muted small mt-1" style="font-size: 0.7rem;">In <?= date('F Y', strtotime($month . '-01')) ?></div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card-panel p-3 h-100 border-start border-4 border-warning">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem;">Under Review</div>
                    <div class="h4 mb-0 fw-bold text-warning-emphasis mt-1"><?= (int)($kpi['pending_review'] ?? 0) ?></div>
                </div>
                <span class="badge bg-warning-subtle text-warning p-2 rounded-circle">
                    <i class="fa-solid fa-user-clock"></i>
                </span>
            </div>
            <div class="text-muted small mt-1" style="font-size: 0.7rem;">Tier 1 &amp; Tier 2 Queues</div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card-panel p-3 h-100 border-start border-4 border-info position-relative">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem;">Ready to Allocate</div>
                    <div class="h4 mb-0 fw-bold text-info-emphasis mt-1"><?= (int)($kpi['ready_to_allocate'] ?? 0) ?></div>
                </div>
                <span class="badge bg-info-subtle text-info p-2 rounded-circle position-relative">
                    <i class="fa-solid fa-key"></i>
                    <?php if (($kpi['ready_to_allocate'] ?? 0) > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="text-muted small mt-1" style="font-size: 0.7rem;">Approved &bull; Needs Vehicle</div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card-panel p-3 h-100 border-start border-4 border-success">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem;">Dispatched / Active</div>
                    <div class="h4 mb-0 fw-bold text-success mt-1"><?= (int)($kpi['dispatched_active'] ?? 0) ?></div>
                </div>
                <span class="badge bg-success-subtle text-success p-2 rounded-circle">
                    <i class="fa-solid fa-route"></i>
                </span>
            </div>
            <div class="text-muted small mt-1" style="font-size: 0.7rem;">e-DTT Issued or En Route</div>
        </div>
    </div>
    <div class="col-12 col-md">
        <div class="card-panel p-3 h-100 border-start border-4 border-secondary">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.68rem;">Completed &amp; Audited</div>
                    <div class="h4 mb-0 fw-bold text-secondary mt-1"><?= (int)($kpi['completed'] ?? 0) ?></div>
                </div>
                <span class="badge bg-secondary-subtle text-secondary p-2 rounded-circle">
                    <i class="fa-solid fa-circle-check"></i>
                </span>
            </div>
            <div class="text-muted small mt-1" style="font-size: 0.7rem;">Reconciled Trips</div>
        </div>
    </div>
</div>

<!-- Action Required Banner: Approved Requests Awaiting Asset Allocation -->
<?php if (!empty($awaitingAllocation)): ?>
    <div class="card border-primary mb-4 shadow-sm" style="background: #f0f7ff;">
        <div class="card-body p-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary p-2"><i class="fa-solid fa-bell"></i></span>
                    <h2 class="h6 mb-0 fw-bold text-primary">
                        Action Required: <?= count($awaitingAllocation) ?> Authorized Request(s) Awaiting Motorpool Asset Allocation
                    </h2>
                </div>
                <span class="badge bg-light text-primary border border-primary-subtle small">
                    Cleared by Administrative Division Head &bull; Ready for e-DTT Generation
                </span>
            </div>
            <div class="row g-2 mt-1">
                <?php foreach ($awaitingAllocation as $req): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="p-2 rounded bg-white border border-primary-subtle shadow-sm h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <span class="mono fw-bold text-primary" style="font-size: 0.78rem;"><?= esc($req['request_number']) ?></span>
                                    <span class="badge bg-primary text-white" style="font-size: 0.62rem;">Ready to Allocate</span>
                                </div>
                                <div class="fw-semibold text-dark text-truncate small mb-1" title="<?= esc($req['destination']) ?>">
                                    <i class="fa-solid fa-location-dot text-danger me-1"></i> <?= esc($req['destination']) ?>
                                </div>
                                <div class="text-muted small mb-1" style="font-size: 0.72rem;">
                                    <i class="fa-solid fa-user me-1"></i> <?= esc($req['requestor']) ?> (<?= esc($req['office']) ?>)
                                </div>
                                <div class="text-muted small mb-2" style="font-size: 0.7rem;">
                                    <i class="fa-solid fa-clock me-1 text-primary"></i> <?= date('M d, H:i', strtotime($req['departure'])) ?> &rarr; <?= date('M d, H:i', strtotime($req['return'])) ?>
                                </div>
                            </div>
                            <div class="d-flex gap-2 mt-2 pt-2 border-top">
                                <a href="<?= base_url('dispatch/assign/' . $req['request_id']) ?>" class="btn btn-sm btn-primary flex-fill" style="font-size: 0.72rem;">
                                    <i class="fa-solid fa-key me-1"></i> Allocate Asset Now
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="viewRequestDetails('<?= esc($req['id']) ?>')" title="View Process Details" style="font-size: 0.72rem;">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Process Lifecycle Filter Pills & Legend -->
<div class="card-panel mb-3 p-3">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
        <!-- Interactive Stage Filters -->
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="fw-semibold text-muted small me-1"><i class="fa-solid fa-filter me-1"></i> Filter Stage:</span>
            <button type="button" class="btn btn-sm btn-outline-dark active filter-btn" data-filter="all">
                All Requests <span class="badge bg-secondary ms-1"><?= (int)($kpi['total'] ?? 0) ?></span>
            </button>
            <button type="button" class="btn btn-sm btn-outline-warning filter-btn" data-filter="pending">
                <i class="fa-solid fa-user-clock me-1"></i> Under Review <span class="badge bg-warning text-dark ms-1"><?= (int)($kpi['pending_review'] ?? 0) ?></span>
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary filter-btn" data-filter="ready">
                <i class="fa-solid fa-key me-1"></i> Ready to Allocate <span class="badge bg-primary text-white ms-1"><?= (int)($kpi['ready_to_allocate'] ?? 0) ?></span>
            </button>
            <button type="button" class="btn btn-sm btn-outline-success filter-btn" data-filter="active">
                <i class="fa-solid fa-route me-1"></i> Dispatched &bull; En Route <span class="badge bg-success text-white ms-1"><?= (int)($kpi['dispatched_active'] ?? 0) ?></span>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary filter-btn" data-filter="completed">
                <i class="fa-solid fa-circle-check me-1"></i> Completed <span class="badge bg-dark text-white ms-1"><?= (int)($kpi['completed'] ?? 0) ?></span>
            </button>
        </div>

        <!-- Multi-Day Phase Legend -->
        <div class="d-flex flex-wrap gap-2 align-items-center" style="font-size: 0.72rem;">
            <span class="text-muted fw-semibold">Phase Tags:</span>
            <span class="badge bg-light text-dark border"><span class="badge bg-primary me-1" style="font-size: 0.55rem;">DEP</span> Departure</span>
            <span class="badge bg-light text-dark border"><span class="badge bg-info text-dark me-1" style="font-size: 0.55rem;">TRANSIT</span> In-Trip</span>
            <span class="badge bg-light text-dark border"><span class="badge bg-success me-1" style="font-size: 0.55rem;">RET</span> Return</span>
            <span class="badge bg-light text-dark border"><span class="badge bg-secondary me-1" style="font-size: 0.55rem;">DEDICATED</span> Executive</span>
        </div>
    </div>
</div>

<?php
    // Group allocations by day
    $byDay = [];
    foreach ($allocations as $a) {
        $byDay[$a['date']][] = $a;
    }

    $firstOfMonth = strtotime($month . '-01');
    $daysInMonth  = (int) date('t', $firstOfMonth);
    $startDow     = (int) date('w', $firstOfMonth); // 0 = Sunday
    $monthName    = date('F Y', $firstOfMonth);
?>

<!-- Calendar Grid Board -->
<div class="card-panel p-0 overflow-hidden shadow-sm mb-4">
    <div class="d-flex align-items-center justify-content-between p-3 bg-light border-bottom">
        <div class="d-flex align-items-center gap-2">
            <h2 class="h6 mb-0 fw-bold">
                <i class="fa-solid fa-calendar-days me-2" style="color: var(--primary-accent);"></i> <?= esc($monthName) ?> Allocation Schedule
            </h2>
            <span class="badge bg-white text-dark border"><?= count($allocations) ?> daily slot(s)</span>
        </div>
        <div class="small text-muted d-none d-md-block">
            <i class="fa-solid fa-shield-halved text-success me-1"></i> Anti-Double Booking Guard Active (FR-2.2) &bull; Click any card for 6-stage lifecycle stepper
        </div>
    </div>

    <!-- Day Header Row -->
    <div class="row g-0 bg-white" style="border-bottom: 1px solid #e2e8f0;">
        <?php foreach (['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $idx => $dow): ?>
            <div class="col text-center py-2 fw-bold text-muted <?= in_array($idx, [0, 6], true) ? 'bg-light-subtle' : '' ?>"
                 style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; border-right: 1px solid #e2e8f0;">
                <span class="d-none d-md-inline"><?= $dow ?></span>
                <span class="d-md-none"><?= substr($dow, 0, 3) ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Weeks Rows -->
    <?php $dayCounter = 1; ?>
    <?php for ($week = 0; $week < ceil(($startDow + $daysInMonth) / 7); $week++): ?>
        <div class="row g-0">
            <?php for ($dow = 0; $dow < 7; $dow++): ?>
                <?php
                    $isWeekend = in_array($dow, [0, 6], true);
                    $isEmpty = ($week === 0 && $dow < $startDow) || ($dayCounter > $daysInMonth);
                    $dateKey = sprintf('%s-%02d', $month, $dayCounter);
                    $today = $dateKey === date('Y-m-d');
                    $dayItems = !$isEmpty ? ($byDay[$dateKey] ?? []) : [];
                ?>
                <div class="col cal-day-cell <?= $isWeekend ? 'bg-light-subtle' : 'bg-white' ?> <?= $today ? 'today-highlight' : '' ?>"
                     style="min-height: 125px; border-bottom: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; padding: 6px; position: relative;"
                     data-date="<?= esc($dateKey) ?>">

                    <?php if (!$isEmpty): ?>
                        <!-- Day Number & Day Counts -->
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge rounded-circle <?= count($dayItems) > 0 ? 'bg-secondary-subtle text-secondary' : 'text-transparent' ?>" style="font-size: 0.6rem; min-width: 18px;">
                                <?= count($dayItems) > 0 ? count($dayItems) : '' ?>
                            </span>
                            <span class="fw-bold <?= $today ? 'bg-primary text-white px-2 py-0 rounded-pill' : 'text-muted' ?>"
                                  style="font-size: 0.75rem;">
                                <?= $dayCounter ?>
                            </span>
                        </div>

                        <!-- Day Allocation Cards -->
                        <div class="d-flex flex-column gap-1">
                            <?php foreach ($dayItems as $a): ?>
                                <?php
                                    // Determine stage filter category
                                    $stageCat = 'pending';
                                    if ($a['allocation_status'] === 'ready_to_allocate') {
                                        $stageCat = 'ready';
                                    } elseif ($a['allocation_status'] === 'allocated' && in_array($a['stage_num'], [4, 5], true)) {
                                        $stageCat = 'active';
                                    } elseif ($a['stage_num'] === 6) {
                                        $stageCat = 'completed';
                                    } elseif (in_array($a['status'], ['rejected', 'expired'], true)) {
                                        $stageCat = 'rejected';
                                    }

                                    // Card border & left accent
                                    $borderAccent = match ((int)$a['stage_num']) {
                                        1       => '#f59e0b', // Amber/gold (Tier 1 review)
                                        2       => '#06b6d4', // Cyan/info (Tier 2 review)
                                        3       => '#3b82f6', // Blue (Ready to allocate)
                                        4       => '#64748b', // Slate (e-DTT Issued)
                                        5       => '#10b981', // Green (En route)
                                        6       => '#1e293b', // Dark (Completed)
                                        default => '#ef4444', // Red (Rejected/expired)
                                    };

                                    $isUnallocated = ($a['allocation_status'] === 'ready_to_allocate' || empty($a['ticket_id']));
                                    $isDedicated = ($a['fleet_category'] ?? 'pool') === 'dedicated';
                                ?>

                                <div class="alloc-card p-1 rounded border shadow-2xs cursor-pointer <?= $isUnallocated ? 'border-dashed' : '' ?>"
                                     style="background: <?= $isUnallocated ? '#eff6ff' : '#ffffff' ?>; border-left: 3px solid <?= $borderAccent ?> !important;"
                                     data-stage-category="<?= esc($stageCat) ?>"
                                     data-alloc-id="<?= esc($a['id']) ?>"
                                     onclick="viewRequestDetails('<?= esc($a['id']) ?>')"
                                     role="button"
                                     tabindex="0"
                                     title="Click to view full workflow process for <?= esc($a['request_number']) ?>">

                                    <!-- Top Row: VRS Number + Phase Chip -->
                                    <div class="d-flex justify-content-between align-items-center gap-1" style="line-height: 1.1;">
                                        <span class="mono fw-bold text-dark text-truncate" style="font-size: 0.62rem;">
                                            <?= esc($a['request_number']) ?>
                                        </span>
                                        <?php if ($a['day_phase'] === 'departure'): ?>
                                            <span class="badge bg-primary text-white px-1 py-0" style="font-size: 0.52rem;" title="Departure Time">DEP</span>
                                        <?php elseif ($a['day_phase'] === 'return'): ?>
                                            <span class="badge bg-success text-white px-1 py-0" style="font-size: 0.52rem;" title="Return Time">RET</span>
                                        <?php elseif ($a['day_phase'] === 'transit'): ?>
                                            <span class="badge bg-info text-dark px-1 py-0" style="font-size: 0.52rem;" title="In Transit Across Travel Days">TRANSIT</span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Middle Row: Destination -->
                                    <div class="text-truncate fw-semibold text-dark mt-0" style="font-size: 0.62rem; line-height: 1.15;" title="<?= esc($a['destination']) ?>">
                                        <?= esc($a['destination'] ?: 'Official Destination') ?>
                                    </div>

                                    <!-- Bottom Row: Asset Plate / Stage Badge -->
                                    <div class="d-flex justify-content-between align-items-center mt-1" style="font-size: 0.56rem; line-height: 1;">
                                        <span class="mono fw-bold <?= $isUnallocated ? 'text-primary' : 'text-secondary' ?> text-truncate" style="max-width: 65%;">
                                            <?php if ($isDedicated): ?>
                                                <i class="fa-solid fa-crown text-warning me-0" title="Dedicated Executive Unit"></i>
                                            <?php endif; ?>
                                            <?= esc($a['plate']) ?>
                                        </span>
                                        <span class="badge <?= esc($a['badge_class']) ?> p-1" style="font-size: 0.50rem;">
                                            <?= esc($a['stage_name']) ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php $dayCounter++; ?>
                    <?php endif; ?>

                </div>
            <?php endfor; ?>
        </div>
    <?php endfor; ?>
</div>

<!-- FR-2.1 Fleet Segregation Summary -->
<div class="row g-3 mt-1 mb-4">
    <?php
        $dedicated = array_values(array_filter($vehicles, fn($v) => ($v['fleet_category'] ?? 'pool') === 'dedicated'));
        $pool      = array_values(array_filter($vehicles, fn($v) => ($v['fleet_category'] ?? 'pool') !== 'dedicated'));
    ?>
    <div class="col-md-6">
        <div class="card-panel h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 fw-bold mb-0">
                    <i class="fa-solid fa-crown me-2 text-warning"></i> Dedicated Executive Vehicles (FR-2.1)
                </h2>
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><?= count($dedicated) ?> Unit(s)</span>
            </div>
            <p class="text-muted small mb-3" style="font-size: 0.75rem;">
                Reserved strictly for assigned senior agency officials. The dispatch engine blocks cross-allocation to general pool trips.
            </p>
            <?php if (empty($dedicated)): ?>
                <p class="text-muted small mb-0">No dedicated executive vehicles classified yet.</p>
            <?php else: ?>
                <ul class="list-unstyled mb-0 small">
                    <?php foreach ($dedicated as $v): ?>
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <span class="mono fw-bold text-dark"><?= esc($v['plate_number']) ?></span>
                                <span class="text-muted ms-2 small"><?= esc($v['make']) ?> <?= esc($v['model']) ?></span>
                            </div>
                            <span class="badge bg-light text-dark border">
                                <i class="fa-solid fa-user-tie me-1 text-warning"></i> <?= esc($v['assigned_official'] ?? 'Designated Official') ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card-panel h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 fw-bold mb-0">
                    <i class="fa-solid fa-car-side me-2 text-primary"></i> Shared Pool Vehicles (FR-2.1)
                </h2>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= count($pool) ?> Unit(s)</span>
            </div>
            <p class="text-muted small mb-3" style="font-size: 0.75rem;">
                Available for general deployment across all PIA divisions, bureaus, and regional missions.
            </p>
            <?php if (empty($pool)): ?>
                <p class="text-muted small mb-0">No shared pool vehicles on file.</p>
            <?php else: ?>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($pool as $v): ?>
                        <span class="badge bg-light text-dark border p-2 mono d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                            <i class="fa-solid fa-car text-primary"></i> <?= esc($v['plate_number']) ?>
                            <span class="text-muted small fw-normal ms-1">(<?= esc($v['vehicle_type'] ?? 'Pool') ?>)</span>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- INTERACTIVE REQUEST LIFECYCLE PROCESS MODAL                               -->
<!-- ========================================================================= -->
<div class="modal fade" id="requestProcessModal" tabindex="-1" aria-labelledby="modalRequestTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <!-- Modal Header -->
            <div class="modal-header border-bottom pb-3" style="background: var(--bg-secondary, #f8fafc);">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="mono fw-bold h5 mb-0 text-primary" id="modalRequestNumber">VRS-2026-0000</span>
                        <span class="badge" id="modalBadgeStatus">Stage 1</span>
                        <span class="badge bg-warning text-dark d-none" id="modalRushBadge"><i class="fa-solid fa-bolt me-1"></i> Rush Request</span>
                        <span class="badge bg-danger text-white d-none" id="modalEmergencyBadge"><i class="fa-solid fa-triangle-exclamation me-1"></i> Emergency Override</span>
                    </div>
                    <div class="text-muted small" id="modalRequestSubtitle">
                        Official Travel Slip &bull; Philippine Information Agency
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4">
                <!-- 6-Stage Visual Stepper -->
                <div class="mb-4 p-3 rounded border" style="background: #ffffff;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 0.68rem; letter-spacing: 0.06em;">
                            <i class="fa-solid fa-bars-progress me-1 text-primary"></i> Main Request Process Lifecycle
                        </span>
                        <span class="badge bg-light text-dark border small" id="modalProcessCode">Process: vrs_pending_tier1</span>
                    </div>

                    <!-- Progress Nodes Bar -->
                    <div class="position-relative m-3">
                        <div class="progress" style="height: 4px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: 0%;" id="modalProgressBar"></div>
                        </div>
                        <div class="d-flex justify-content-between position-absolute top-50 start-0 w-100 translate-middle-y">
                            <!-- Step 1 -->
                            <div class="step-node text-center" id="stepNode1">
                                <span class="step-circle badge rounded-pill bg-secondary"><i class="fa-solid fa-file-pen"></i></span>
                                <div class="step-label text-muted small mt-1" style="font-size: 0.65rem;">1. VRS Filed</div>
                            </div>
                            <!-- Step 2 -->
                            <div class="step-node text-center" id="stepNode2">
                                <span class="step-circle badge rounded-pill bg-secondary"><i class="fa-solid fa-user-check"></i></span>
                                <div class="step-label text-muted small mt-1" style="font-size: 0.65rem;">2. Tier 1 Review</div>
                            </div>
                            <!-- Step 3 -->
                            <div class="step-node text-center" id="stepNode3">
                                <span class="step-circle badge rounded-pill bg-secondary"><i class="fa-solid fa-stamp"></i></span>
                                <div class="step-label text-muted small mt-1" style="font-size: 0.65rem;">3. Tier 2 Clearance</div>
                            </div>
                            <!-- Step 4 -->
                            <div class="step-node text-center" id="stepNode4">
                                <span class="step-circle badge rounded-pill bg-secondary"><i class="fa-solid fa-car"></i></span>
                                <div class="step-label text-muted small mt-1" style="font-size: 0.65rem;">4. Asset Allocation</div>
                            </div>
                            <!-- Step 5 -->
                            <div class="step-node text-center" id="stepNode5">
                                <span class="step-circle badge rounded-pill bg-secondary"><i class="fa-solid fa-door-open"></i></span>
                                <div class="step-label text-muted small mt-1" style="font-size: 0.65rem;">5. Gate &amp; Trip</div>
                            </div>
                            <!-- Step 6 -->
                            <div class="step-node text-center" id="stepNode6">
                                <span class="step-circle badge rounded-pill bg-secondary"><i class="fa-solid fa-certificate"></i></span>
                                <div class="step-label text-muted small mt-1" style="font-size: 0.65rem;">6. Audit &amp; Done</div>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1" id="modalStageSummaryText" style="font-size: 0.72rem;">
                            Stage 1 of 6: Awaiting Tier 1 Motorpool Dispatcher Review
                        </span>
                    </div>
                </div>

                <!-- Rejection Alert (hidden by default) -->
                <div class="alert alert-danger d-none" id="modalRejectionAlert">
                    <h6 class="alert-heading fw-bold mb-1"><i class="fa-solid fa-triangle-exclamation me-1"></i> Request Disapproved / Expired</h6>
                    <p class="mb-0 small" id="modalRejectionText">This travel request was not approved.</p>
                </div>

                <!-- Travel & Request Specifications -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="p-3 rounded border bg-light h-100">
                            <h6 class="fw-bold mb-2 text-dark small text-uppercase" style="letter-spacing: 0.05em;">
                                <i class="fa-solid fa-location-dot text-danger me-1"></i> Travel Mission &amp; Scope
                            </h6>
                            <table class="table table-sm table-borderless mb-0 small">
                                <tr>
                                    <td class="text-muted" style="width: 35%;">Destination:</td>
                                    <td class="fw-semibold text-dark" id="modalDestination">—</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Purpose:</td>
                                    <td class="text-dark" id="modalPurpose">—</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Departure:</td>
                                    <td class="mono fw-semibold text-dark" id="modalDeparture">—</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Expected Return:</td>
                                    <td class="mono fw-semibold text-dark" id="modalReturn">—</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Schedule Span:</td>
                                    <td class="badge bg-light text-dark border" id="modalSpanDays">1 Day</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded border bg-light h-100">
                            <h6 class="fw-bold mb-2 text-dark small text-uppercase" style="letter-spacing: 0.05em;">
                                <i class="fa-solid fa-users text-primary me-1"></i> Requestor &amp; Passengers
                            </h6>
                            <table class="table table-sm table-borderless mb-0 small">
                                <tr>
                                    <td class="text-muted" style="width: 35%;">Requestor:</td>
                                    <td class="fw-semibold text-dark" id="modalRequestor">—</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Office / Division:</td>
                                    <td class="text-dark" id="modalOffice">—</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Passengers (Count):</td>
                                    <td class="text-dark"><span id="modalPassengerCount">0</span> passenger(s)</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Passenger Manifest:</td>
                                    <td class="text-dark small" id="modalPassengerNames" style="max-height: 60px; overflow-y: auto;">—</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Approval Trail & Allocation Grid -->
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 rounded border h-100" style="background: #ffffff;">
                            <h6 class="fw-bold mb-2 text-dark small text-uppercase" style="letter-spacing: 0.05em;">
                                <i class="fa-solid fa-signature text-secondary me-1"></i> Two-Tier Approval Trail
                            </h6>
                            <div class="small mb-2 pb-2 border-bottom">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-dark">Tier 1: Motorpool Dispatcher</span>
                                    <span class="badge" id="modalTier1Badge">Pending</span>
                                </div>
                                <div class="text-muted" style="font-size: 0.72rem;" id="modalTier1Info">Awaiting endorsement</div>
                                <div class="fst-italic text-secondary small mt-1" id="modalTier1Remarks"></div>
                            </div>
                            <div class="small">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-dark">Tier 2: Administrative Division Head</span>
                                    <span class="badge" id="modalTier2Badge">Pending</span>
                                </div>
                                <div class="text-muted" style="font-size: 0.72rem;" id="modalTier2Info">Awaiting final clearance</div>
                                <div class="fst-italic text-secondary small mt-1" id="modalTier2Remarks"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded border h-100" style="background: #ffffff;">
                            <h6 class="fw-bold mb-2 text-dark small text-uppercase" style="letter-spacing: 0.05em;">
                                <i class="fa-solid fa-van-shuttle text-success me-1"></i> Motorpool Allocation &amp; Crew
                            </h6>
                            <div id="modalAllocationDetails">
                                <table class="table table-sm table-borderless mb-0 small">
                                    <tr>
                                        <td class="text-muted" style="width: 35%;">Vehicle:</td>
                                        <td class="mono fw-bold text-dark" id="modalVehiclePlate">Unassigned</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Model / Type:</td>
                                        <td class="text-dark" id="modalVehicleModel">—</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Fleet Category:</td>
                                        <td id="modalFleetCategory"><span class="badge bg-info text-dark">pool</span></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Assigned Driver:</td>
                                        <td class="fw-semibold text-dark" id="modalDriverName">Unassigned</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Driver Contact:</td>
                                        <td class="mono text-muted" id="modalDriverPhone">—</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Driver's Trip Ticket:</td>
                                        <td class="mono text-primary fw-bold" id="modalTicketSerial">—</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FR-2.2 Unified Single Updatable Allocation Form (Admin / Dispatcher / Approver) -->
                <?php if (in_array(session()->get('user_role'), ['admin', 'dispatcher', 'approver_admin'])): ?>
                    <div class="mt-3 p-3 rounded border" style="background: #f8fafc;" id="quickAllocationPanel">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-uppercase fw-bold text-dark" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                                <i class="fa-solid fa-pen-to-square text-success me-1"></i> Quick Allocation &bull; Single Updatable Process
                            </span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;">CALENDAR DISPATCH</span>
                        </div>
                        <p class="text-muted small mb-2" style="font-size: 0.72rem;">
                            Directly allocate or reassign the official vehicle and certified driver for this travel schedule in one unified step.
                        </p>
                        <form action="<?= base_url('dispatch/calendar/update-allocation') ?>" method="POST" id="quickAllocForm">
                            <?= csrf_field() ?>
                            <input type="hidden" name="request_id" id="quickAllocRequestId" value="">
                            <input type="hidden" name="month" value="<?= esc($month) ?>">

                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label small fw-semibold mb-1" for="quickAllocVehicleId" style="font-size: 0.74rem;">Vehicle Asset</label>
                                    <select name="vehicle_id" id="quickAllocVehicleId" class="form-select form-select-sm" required>
                                        <option value="">-- Choose Vehicle --</option>
                                        <?php foreach ($vehicles as $v): ?>
                                            <?php
                                                $isDedicated = ($v['fleet_category'] ?? 'pool') === 'dedicated';
                                                $isInactive = $v['status'] !== 'active';
                                            ?>
                                            <option value="<?= $v['id'] ?>" <?= $isInactive ? 'disabled' : '' ?>>
                                                <?= esc($v['plate_number']) ?> &bull; <?= esc($v['make']) ?> <?= esc($v['model']) ?> (<?= ucfirst($v['type']) ?>) <?= $isDedicated ? '[DEDICATED]' : '' ?> <?= $isInactive ? '[' . strtoupper($v['status']) . ']' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1" for="quickAllocDriverId" style="font-size: 0.74rem;">Official Driver</label>
                                    <select name="driver_id" id="quickAllocDriverId" class="form-select form-select-sm" required>
                                        <option value="">-- Choose Driver --</option>
                                        <?php foreach ($drivers as $d): ?>
                                            <option value="<?= $d['id'] ?>">
                                                <?= esc($d['first_name'] . ' ' . $d['last_name']) ?> (<?= esc($d['driver_code']) ?>) [<?= ucfirst($d['status']) ?>]
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-sm btn-success w-100" id="btnSaveAllocation">
                                        <i class="fa-solid fa-check me-1"></i> Save Allocation
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Modal Footer Direct Action -->
            <div class="modal-footer border-top bg-light d-flex justify-content-between">
                <div>
                    <span class="text-muted small" id="modalActionHint">Click below to proceed with next lifecycle step:</span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <a href="#" class="btn btn-primary btn-sm" id="modalPrimaryActionBtn">
                        <i class="fa-solid fa-arrow-right me-1"></i> <span id="modalPrimaryActionLabel">Take Action</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Calendar Allocations Store & Interactive Modal Controller -->
<script>
    // Authoritative calendar allocation records populated directly from the controller
    window.calendarAllocations = <?= json_encode($allocations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

    // Filter pills controller
    document.addEventListener('DOMContentLoaded', function() {
        const filterBtns = document.querySelectorAll('.filter-btn');
        const allocCards = document.querySelectorAll('.alloc-card');

        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                filterBtns.forEach(b => b.classList.remove('active', 'btn-dark', 'btn-warning', 'btn-primary', 'btn-success', 'btn-secondary'));
                filterBtns.forEach(b => b.classList.add('btn-outline-dark', 'btn-outline-warning', 'btn-outline-primary', 'btn-outline-success', 'btn-outline-secondary'));

                this.classList.add('active');

                const filter = this.getAttribute('data-filter');

                allocCards.forEach(card => {
                    const cardCat = card.getAttribute('data-stage-category');
                    if (filter === 'all' || cardCat === filter) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    });

    // Modal Details Renderer
    function viewRequestDetails(allocId) {
        if (!window.calendarAllocations) return;
        const alloc = window.calendarAllocations.find(a => a.id === allocId);
        if (!alloc) return;

        // Populate Header
        document.getElementById('modalRequestNumber').textContent = alloc.request_number || 'VRS';
        document.getElementById('modalRequestSubtitle').innerHTML = `Official Travel Mission &bull; <strong>${escapeHtml(alloc.destination)}</strong>`;

        const statusBadge = document.getElementById('modalBadgeStatus');
        statusBadge.className = 'badge ' + (alloc.badge_class || 'bg-secondary');
        statusBadge.textContent = alloc.process_title || alloc.status;

        // Rush & Emergency badges
        const rushBadge = document.getElementById('modalRushBadge');
        if (alloc.is_rush) {
            rushBadge.classList.remove('d-none');
        } else {
            rushBadge.classList.add('d-none');
        }

        const emergBadge = document.getElementById('modalEmergencyBadge');
        if (alloc.is_emergency) {
            emergBadge.classList.remove('d-none');
        } else {
            emergBadge.classList.add('d-none');
        }

        // Stepper Progress Calculation (1 to 6)
        const stageNum = parseInt(alloc.stage_num, 10) || 0;
        const progressBar = document.getElementById('modalProgressBar');
        const processCode = document.getElementById('modalProcessCode');
        processCode.textContent = 'Process Code: ' + (alloc.process_code || 'vrs_process');

        // Reset step circles
        for (let i = 1; i <= 6; i++) {
            const node = document.getElementById('stepNode' + i);
            const circle = node.querySelector('.step-circle');
            if (stageNum === 0) {
                // Rejected / Expired
                circle.className = 'step-circle badge rounded-pill bg-danger text-white';
            } else if (i < stageNum) {
                // Completed past step
                circle.className = 'step-circle badge rounded-pill bg-success text-white';
                circle.innerHTML = '<i class="fa-solid fa-check"></i>';
            } else if (i === stageNum) {
                // Active current step
                circle.className = 'step-circle badge rounded-pill bg-primary text-white shadow-sm';
                circle.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            } else {
                // Future pending step
                circle.className = 'step-circle badge rounded-pill bg-secondary text-white';
                circle.innerHTML = '<i class="fa-solid fa-circle text-muted" style="font-size: 0.5rem;"></i>';
            }
        }

        // Progress bar percentage
        let pct = 0;
        if (stageNum >= 1 && stageNum <= 6) {
            pct = Math.round(((stageNum - 1) / 5) * 100);
        } else if (stageNum === 6) {
            pct = 100;
        }
        progressBar.style.width = pct + '%';

        // Stage Summary Text
        const summaryText = document.getElementById('modalStageSummaryText');
        summaryText.textContent = alloc.process_title || 'Request Processing';

        // Rejection / Expiration Alert
        const rejAlert = document.getElementById('modalRejectionAlert');
        if (stageNum === 0 || alloc.status === 'rejected' || alloc.status === 'expired') {
            rejAlert.classList.remove('d-none');
            document.getElementById('modalRejectionText').textContent = alloc.dispatcher_remarks || alloc.admin_remarks || 'This request has been disapproved or has expired due to SLA breach.';
        } else {
            rejAlert.classList.add('d-none');
        }

        // Travel Mission Scope
        document.getElementById('modalDestination').textContent = alloc.destination || '—';
        document.getElementById('modalPurpose').textContent = alloc.purpose || '—';
        document.getElementById('modalDeparture').textContent = alloc.departure_time || alloc.departure || '—';
        document.getElementById('modalReturn').textContent = alloc.return_time || alloc.return || '—';

        // Span Days calculation
        const depD = new Date(alloc.departure_time || alloc.departure);
        const retD = new Date(alloc.return_time || alloc.return);
        const diffDays = Math.max(1, Math.ceil((retD - depD) / (1000 * 60 * 60 * 24)));
        document.getElementById('modalSpanDays').textContent = diffDays > 1 ? `${diffDays} Days Mission (${alloc.day_phase_label || 'Multi-day'})` : 'Same-Day Mission';

        // Requestor & Passengers
        document.getElementById('modalRequestor').textContent = alloc.requestor_name || alloc.requestor || '—';
        document.getElementById('modalOffice').textContent = (alloc.office_name || alloc.office) + (alloc.requestor_designation ? ` (${alloc.requestor_designation})` : '');
        document.getElementById('modalPassengerCount').textContent = alloc.passenger_count || '1';
        document.getElementById('modalPassengerNames').textContent = alloc.passenger_names || 'Self / Manifest on file';

        // Approval Trail
        const t1Badge = document.getElementById('modalTier1Badge');
        const t1Info = document.getElementById('modalTier1Info');
        const t1Remarks = document.getElementById('modalTier1Remarks');
        if (alloc.dispatcher_name) {
            t1Badge.className = 'badge bg-success';
            t1Badge.textContent = 'Endorsed';
            t1Info.textContent = `${alloc.dispatcher_name} &bull; ${alloc.dispatcher_action_at || 'Recorded'}`;
            t1Remarks.textContent = alloc.dispatcher_remarks ? `"${alloc.dispatcher_remarks}"` : '';
        } else {
            t1Badge.className = 'badge bg-warning text-dark';
            t1Badge.textContent = 'Awaiting Review';
            t1Info.textContent = 'Pending Motorpool Dispatcher check';
            t1Remarks.textContent = '';
        }

        const t2Badge = document.getElementById('modalTier2Badge');
        const t2Info = document.getElementById('modalTier2Info');
        const t2Remarks = document.getElementById('modalTier2Remarks');
        if (alloc.admin_name) {
            t2Badge.className = 'badge bg-success';
            t2Badge.textContent = 'Authorized';
            t2Info.textContent = `${alloc.admin_name} &bull; ${alloc.admin_action_at || 'Recorded'}`;
            t2Remarks.textContent = alloc.admin_remarks ? `"${alloc.admin_remarks}"` : '';
        } else {
            t2Badge.className = 'badge bg-secondary';
            t2Badge.textContent = 'Pending Clearance';
            t2Info.textContent = 'Awaiting Administrative Division Head action';
            t2Remarks.textContent = '';
        }

        // Motorpool Asset & Driver Allocation
        document.getElementById('modalVehiclePlate').textContent = alloc.vehicle_plate || alloc.plate || 'Unassigned';
        document.getElementById('modalVehicleModel').textContent = alloc.vehicle_model || (alloc.plate ? 'PIA Fleet Unit' : 'Awaiting Assignment');

        const catBadge = document.getElementById('modalFleetCategory');
        if (alloc.fleet_category === 'dedicated') {
            catBadge.innerHTML = `<span class="badge bg-warning text-dark"><i class="fa-solid fa-crown me-1"></i> dedicated (${escapeHtml(alloc.assigned_official || 'Official')})</span>`;
        } else {
            catBadge.innerHTML = `<span class="badge bg-info text-dark">shared pool unit</span>`;
        }

        document.getElementById('modalDriverName').textContent = alloc.driver_name || alloc.driver || 'Unassigned';
        document.getElementById('modalDriverPhone').textContent = alloc.driver_phone ? `Phone: ${alloc.driver_phone}` : (alloc.driver_code ? `Code: ${alloc.driver_code}` : '—');
        document.getElementById('modalTicketSerial').textContent = alloc.ticket_serial_no || (alloc.allocation_status === 'ready_to_allocate' ? 'Not Issued Yet (Ready)' : '—');

        // Populate Single Updatable Allocation Form
        const reqIdInput = document.getElementById('quickAllocRequestId');
        const vehSelect  = document.getElementById('quickAllocVehicleId');
        const drvSelect  = document.getElementById('quickAllocDriverId');
        const panelEl    = document.getElementById('quickAllocationPanel');

        if (reqIdInput && vehSelect && drvSelect && panelEl) {
            if (alloc.request_id && (stageNum >= 1 && stageNum <= 5)) {
                panelEl.style.display = 'block';
                reqIdInput.value = alloc.request_id;
                if (alloc.vehicle_id) vehSelect.value = alloc.vehicle_id;
                else vehSelect.value = '';
                if (alloc.driver_id) drvSelect.value = alloc.driver_id;
                else drvSelect.value = '';
            } else {
                panelEl.style.display = 'none';
            }
        }

        // Direct Action Button Configuration
        const actionBtn = document.getElementById('modalPrimaryActionBtn');
        const actionLabel = document.getElementById('modalPrimaryActionLabel');
        const actionHint = document.getElementById('modalActionHint');

        actionBtn.href = alloc.action_url || '#';
        actionLabel.textContent = alloc.action_label || 'View Record';

        if (alloc.allocation_status === 'ready_to_allocate') {
            actionBtn.className = 'btn btn-primary btn-sm';
            actionHint.textContent = 'Action Required: Assign an available vehicle & driver to generate the e-DTT:';
        } else if (stageNum === 1 || stageNum === 2) {
            actionBtn.className = 'btn btn-warning btn-sm text-dark';
            actionHint.textContent = 'Action Required: Complete approval endorsement in government portal:';
        } else if (stageNum === 4) {
            actionBtn.className = 'btn btn-secondary btn-sm';
            actionHint.textContent = 'e-DTT issued. Verify BLOWBAGETS safety checklist or gate egress:';
        } else if (stageNum === 5) {
            actionBtn.className = 'btn btn-success btn-sm';
            actionHint.textContent = 'Vehicle is on the road. View live GPS telematics or record trip events:';
        } else {
            actionBtn.className = 'btn btn-dark btn-sm';
            actionHint.textContent = 'View finalized COA compliance record and dual certifications:';
        }

        // Open Modal
        const modalEl = document.getElementById('requestProcessModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
</script>

<style>
    .cursor-pointer { cursor: pointer; }
    .border-dashed { border-style: dashed !important; }
    .shadow-2xs { box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04); }
    .today-highlight { background-color: #f8fafc !important; border: 2px solid var(--primary-accent, #0284c7) !important; }
    .alloc-card {
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .alloc-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    .step-node {
        position: relative;
        z-index: 2;
    }
    .step-circle {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        transition: all 0.2s ease;
    }
</style>
<?= $this->endSection() ?>
