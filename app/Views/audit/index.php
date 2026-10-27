<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: #b45309; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            <i class="fa-solid fa-scale-balanced me-1"></i> Commission on Audit (COA) &bull; Executive Oversight
        </div>
        <h1 class="page-title mb-1">COA Compliance & Fleet Governance</h1>
        <p class="text-muted small mb-0">Statutory audit trails, approval SLA metrics (4h escalation / 24h expiry), fuel consumption variances, and PMS adherence.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="<?= base_url('audit/logs') ?>" class="btn btn-secondary">
            <i class="fa-solid fa-clock-rotate-left me-1"></i> System Audit Trail (NFR-3)
        </a>
        <a href="<?= base_url('audit/export') ?>" class="btn btn-primary">
            <i class="fa-solid fa-file-csv me-1"></i> Export Official COA Audit CSV
        </a>
    </div>
</div>

<!-- KPI Metric Cards -->
<div class="row g-3 mb-4">
    <!-- 24h SLA Compliance -->
    <div class="col-md-3">
        <div class="card-panel p-3 h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase mono" style="font-size: 0.7rem;">SLA Turnaround Rate</span>
                <span class="rounded-circle bg-success bg-opacity-10 text-success p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="fa-solid fa-clock-check"></i>
                </span>
            </div>
            <div class="fs-3 fw-bold text-dark mono"><?= $slaComplianceRate ?>%</div>
            <div class="small text-muted" style="font-size: 0.72rem;">
                Target: $\ge$ 95.0% &bull; <strong class="text-danger"><?= $slaBreached ?></strong> breached
            </div>
        </div>
    </div>

    <!-- Rush vs Advance Notice -->
    <div class="col-md-3">
        <div class="card-panel p-3 h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase mono" style="font-size: 0.7rem;">Emergency Rush Ratio</span>
                <span class="rounded-circle bg-danger bg-opacity-10 text-danger p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="fa-solid fa-fire"></i>
                </span>
            </div>
            <div class="fs-3 fw-bold text-dark mono">
                <?= $totalRequests > 0 ? round(($rushRequests / $totalRequests) * 100, 1) : 0 ?>%
            </div>
            <div class="small text-muted" style="font-size: 0.72rem;">
                <strong><?= $rushRequests ?></strong> Rush (BR-03) / <strong><?= $standardRequests ?></strong> Standard (BR-01)
            </div>
        </div>
    </div>

    <!-- Fuel Variance Anomalies -->
    <div class="col-md-3">
        <div class="card-panel p-3 h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase mono" style="font-size: 0.7rem;">Fuel Anomalies (>20%)</span>
                <span class="rounded-circle bg-warning bg-opacity-10 text-warning p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="fa-solid fa-gas-pump"></i>
                </span>
            </div>
            <div class="fs-3 fw-bold text-dark mono"><?= count($flaggedAnomalies) ?></div>
            <div class="small text-muted" style="font-size: 0.72rem;">
                Flagged for COA mileage audit
            </div>
        </div>
    </div>

    <!-- PMS Compliance -->
    <div class="col-md-3">
        <div class="card-panel p-3 h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase mono" style="font-size: 0.7rem;">PMS Safety Status</span>
                <span class="rounded-circle bg-primary bg-opacity-10 text-primary p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="fa-solid fa-wrench"></i>
                </span>
            </div>
            <div class="fs-3 fw-bold text-dark mono">
                <?php if ($lockedCount > 0): ?>
                    <span class="text-danger"><?= $lockedCount ?> Locked</span>
                <?php else: ?>
                    <span class="text-success">All Safe</span>
                <?php endif; ?>
            </div>
            <div class="small text-muted" style="font-size: 0.72rem;">
                <?= $dueSoonCount ?> due soon &bull; <?= $overdueCount ?> overdue
            </div>
        </div>
    </div>
</div>

<!-- COA Official Audit Trail Table -->
<div class="card-panel mb-4">
    <div class="card-panel-header d-flex align-items-center justify-content-between">
        <div>
            <h6 class="mb-0 fw-bold text-dark small text-uppercase">Statutory Trip & Fuel Audit Ledger</h6>
            <div class="text-muted" style="font-size: 0.72rem;">Comprehensive verification of Section A authorization, gate timestamps, odometers, fuel reconciliation, and dual certifications.</div>
        </div>
        <span class="badge bg-white text-muted border small">COA Circular 75-6</span>
    </div>
    <div class="table-responsive">
        <table class="table-minimal">
            <thead>
                <tr>
                    <th>Serial / Request No</th>
                    <th>Office / Division</th>
                    <th>Plate & Driver</th>
                    <th>Destination & Purpose</th>
                    <th>Odometers (Start / End)</th>
                    <th>Fuel Ledger (Start + Buy - Used = End)</th>
                    <th>Efficiency</th>
                    <th>Dual Cert</th>
                    <th class="text-end" style="width: 1%;">Print</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($completedTrips)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted small">No trip audit records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($completedTrips as $t): ?>
                        <tr>
                            <td>
                                <a href="<?= base_url('tickets/' . $t['id']) ?>" class="mono fw-bold text-dark text-decoration-none d-block">
                                    <?= esc($t['ticket_serial_no']) ?>
                                </a>
                                <span class="mono text-muted" style="font-size: 0.68rem;"><?= esc($t['request_number']) ?></span>
                            </td>
                            <td>
                                <div class="small fw-semibold"><?= esc($t['office_name'] ?? 'Central Office') ?></div>
                            </td>
                            <td>
                                <div class="mono fw-bold text-dark small"><?= esc($t['plate_number']) ?></div>
                                <div class="small text-muted" style="font-size: 0.7rem;"><?= esc($t['driver_name']) ?></div>
                            </td>
                            <td>
                                <div class="small fw-medium text-truncate" style="max-width: 180px;" title="<?= esc($t['authorized_destination']) ?>">
                                    <?= esc($t['authorized_destination']) ?>
                                </div>
                            </td>
                            <td>
                                <div class="mono small"><?= number_format((float)$t['start_odometer'], 1) ?> &rarr; <?= number_format((float)$t['return_odometer'], 1) ?> km</div>
                                <div class="mono text-muted" style="font-size: 0.7rem;">Dist: <strong><?= number_format((float)$t['total_distance_km'], 1) ?> KM</strong></div>
                            </td>
                            <td>
                                <div class="mono small" style="font-size: 0.72rem;">
                                    <?= (float)$t['fuel_balance_start_liters'] ?>L<?= ((float)($t['fuel_issued_stock_liters'] ?? 0) > 0) ? ' + ' . (float)$t['fuel_issued_stock_liters'] . 'L (stk)' : '' ?> + <?= (float)$t['fuel_purchased_liters'] ?>L - <?= (float)$t['fuel_used_liters'] ?>L = <strong><?= (float)$t['fuel_balance_end_liters'] ?>L</strong>
                                </div>
                                <div class="mono text-muted" style="font-size: 0.68rem;">₱<?= number_format((float)$t['fuel_purchased_cost'], 2) ?> purchased<?php if ((float)($t['toll_expense'] ?? 0) > 0): ?> &bull; ₱<?= number_format((float)$t['toll_expense'], 2) ?> toll<?php endif; ?></div>
                            </td>
                            <td>
                                <div class="mono small fw-bold">
                                    <?= number_format((float)$t['fuel_efficiency_kml'], 1) ?> km/L
                                </div>
                                <?php if ((int)$t['fuel_anomaly_flag'] === 1): ?>
                                    <span class="badge bg-danger text-white p-1" style="font-size: 0.6rem;">ANOMALY</span>
                                <?php else: ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle p-1" style="font-size: 0.6rem;">NORMAL</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small">
                                    Driver: <?= (int)$t['driver_certified'] === 1 ? '<i class="fa-solid fa-check text-success"></i>' : '<i class="fa-solid fa-xmark text-danger"></i>' ?><br>
                                    Pax: <?= (int)$t['passenger_certified'] === 1 ? '<i class="fa-solid fa-check text-success"></i>' : '<i class="fa-solid fa-xmark text-danger"></i>' ?>
                                </div>
                            </td>
                            <td class="table-action-cell">
                                <div class="table-actions">
                                    <a href="<?= base_url('tickets/' . $t['id'] . '/print') ?>" target="_blank" class="btn-icon-sm btn-outline-secondary" title="Print ADMIN-F-001 rev1">
                                        <i class="fa-solid fa-print"></i>
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
<?= $this->endSection() ?>
