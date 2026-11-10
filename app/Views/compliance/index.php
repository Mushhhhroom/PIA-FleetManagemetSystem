<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--primary-accent); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            Module 7 &bull; COA &amp; Government Compliance
        </div>
        <h1 class="page-title mb-1">COA &amp; Government Compliance Portal</h1>
        <p class="text-muted small mb-0">
            FR-7.1 regulatory expiry alerts &bull; 
            <span class="policy-badge policy-badge-info" data-bs-toggle="tooltip" data-bs-placement="top" title="<strong>COA Form B (Monthly Travel Report)</strong><br>Mandated by COA Circular No. 75-6. Official monthly report detailing distance, fuel consumed, trip ticket serials, and travel justification." tabindex="0" role="button">
                COA Form B (FR-7.2) <i class="fa-solid fa-circle-question policy-info-icon"></i>
            </span> &bull; 
            FR-7.3 fuel efficiency analytics &bull; 
            <span class="policy-badge policy-badge-warning" data-bs-toggle="tooltip" data-bs-placement="top" title="<strong>Certificate of Non-Usage (CNU)</strong><br>Official certification required when government vehicles remain parked/idle throughout the reporting month, certifying zero official travel." tabindex="0" role="button">
                CNU (FR-7.4) <i class="fa-solid fa-circle-question policy-info-icon"></i>
            </span>.
        </p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <form action="<?= base_url('compliance') ?>" method="GET" class="d-flex gap-2">
            <input type="month" name="month" class="form-control" value="<?= esc($month) ?>" onchange="this.form.submit()">
        </form>
        <a href="<?= base_url('compliance/form-b?month=' . urlencode($month)) ?>" class="btn btn-primary">
            <i class="fa-solid fa-file-csv me-1"></i> COA Form B
        </a>
    </div>
</div>

<!-- FR-7.1 — Regulatory Expiry Monitoring -->
<div class="card-panel mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="h6 mb-0 fw-bold">
            <i class="fa-solid fa-calendar-xmark me-2 text-danger"></i> FR-7.1 — LTO / GSIS 30-Day Expiry Monitoring
        </h2>
        <form action="<?= base_url('compliance/expiry-alerts') ?>" method="POST" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="fa-solid fa-bullhorn me-1"></i> Send Alerts Now
            </button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table-minimal">
            <thead>
                <tr>
                    <th>Vehicle</th>
                    <th>LTO Registration Expiry</th>
                    <th>GSIS Insurance Expiry</th>
                    <th>Alert Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($expiring)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">
                            <i class="fa-solid fa-circle-check fa-2x mb-2 d-block text-success opacity-75"></i>
                            No regulatory document expires within the next 30 days.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($expiring as $row): ?>
                        <?php $v = $row['vehicle']; ?>
                        <tr>
                            <td>
                                <span class="mono fw-semibold"><?= esc($v['plate_number']) ?></span>
                                <div class="text-muted" style="font-size: 0.72rem;"><?= esc(trim($v['make'] . ' ' . $v['model'])) ?></div>
                            </td>
                            <td>
                                <?php if ($row['lto']): ?>
                                    <span class="mono" style="font-size: 0.8rem;"><?= esc($row['lto']) ?></span>
                                    <div class="<?= $row['lto_expired'] ? 'text-danger fw-bold' : ($row['lto_days'] <= 7 ? 'text-warning fw-semibold' : 'text-muted') ?>" style="font-size: 0.72rem;">
                                        <?= $row['lto_expired'] ? 'EXPIRED ' . abs($row['lto_days']) . ' day(s) ago' : $row['lto_days'] . ' day(s) remaining' ?>
                                    </div>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['gsis']): ?>
                                    <span class="mono" style="font-size: 0.8rem;"><?= esc($row['gsis']) ?></span>
                                    <div class="<?= $row['gsis_expired'] ? 'text-danger fw-bold' : ($row['gsis_days'] <= 7 ? 'text-warning fw-semibold' : 'text-muted') ?>" style="font-size: 0.72rem;">
                                        <?= $row['gsis_expired'] ? 'EXPIRED ' . abs($row['gsis_days']) . ' day(s) ago' : $row['gsis_days'] . ' day(s) remaining' ?>
                                    </div>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= ($row['lto_expired'] || $row['gsis_expired']) ? 'bg-danger' : 'bg-warning text-dark' ?>">
                                    <?= ($row['lto_expired'] || $row['gsis_expired']) ? 'COMPLIANCE BREACH' : 'WITHIN 30-DAY WINDOW' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- FR-7.3 — Fuel Efficiency Analytics -->
<div class="card-panel mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="h6 mb-0 fw-bold">
            <i class="fa-solid fa-chart-line me-2" style="color: var(--primary-accent);"></i> FR-7.3 — Fuel Efficiency (km/L) &amp; Anomaly Detection
        </h2>
        <span class="badge bg-light text-dark border p-2"><?= esc(date('F Y', strtotime($month . '-01'))) ?></span>
    </div>

    <div class="table-responsive">
        <table class="table-minimal">
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Vehicle</th>
                    <th>Driver</th>
                    <th>Distance (km)</th>
                    <th>Fuel Used (L)</th>
                    <th>km/L</th>
                    <th>Toll (₱)</th>
                    <th>CPK (₱/km)</th>
                    <th>Flag</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($fuelAnalytics)): ?>
                    <tr><td colspan="9" class="text-center py-4 text-muted">No completed trips with fuel data for this month.</td></tr>
                <?php else: ?>
                    <?php foreach ($fuelAnalytics as $t): ?>
                        <tr>
                            <td class="mono" style="font-size: 0.78rem;"><?= esc($t['ticket_serial_no']) ?></td>
                            <td class="mono" style="font-size: 0.78rem;"><?= esc($t['plate_number'] ?? '—') ?></td>
                            <td style="font-size: 0.8rem;"><?= esc(trim(($t['first_name'] ?? '') . ' ' . ($t['last_name'] ?? '')) ?: '—') ?></td>
                            <td class="mono"><?= number_format((float) $t['total_distance_km'], 2) ?></td>
                            <td class="mono"><?= number_format((float) $t['fuel_used_liters'], 2) ?></td>
                            <td class="mono fw-bold"><?= number_format((float) $t['fuel_efficiency_kml'], 2) ?></td>
                            <td class="mono"><?= number_format((float) ($t['toll_expense'] ?? 0), 2) ?></td>
                            <td class="mono fw-semibold"><?= number_format((float) ($t['cpk'] ?? 0), 2) ?></td>
                            <td>
                                <?php if ((int) $t['fuel_anomaly_flag'] === 1): ?>
                                    <span class="badge bg-danger">&gt;20% DEVIATION</span>
                                <?php else: ?>
                                    <span class="badge bg-success">NORMAL</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row g-4">
    <!-- FR-7.2 — COA Form B quick actions -->
    <div class="col-lg-6">
        <div class="card-panel h-100">
            <h2 class="h6 fw-bold mb-3 d-flex align-items-center flex-wrap gap-2">
                <i class="fa-solid fa-file-lines me-1" style="color: var(--primary-accent);"></i> 
                FR-7.2 — COA Form B
                <span class="policy-badge policy-badge-info" data-bs-toggle="tooltip" data-bs-placement="top" title="<strong>COA Form B (Monthly Travel Report)</strong><br>Prescribed by COA Circular No. 75-6. An immutable ledger recording trip serials, odometer start/end, fuel consumed, and driver certifications for audit submission." tabindex="0" role="button">
                    COA Form B <i class="fa-solid fa-circle-question policy-info-icon"></i>
                </span>
            </h2>
            <p class="text-muted small">
                One-click generation of the official monthly report containing driver details, distance travelled,
                fuel consumed, destination addresses and passenger lists.
            </p>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= base_url('compliance/form-b?month=' . urlencode($month)) ?>" class="btn btn-primary" target="_blank">
                    <i class="fa-solid fa-print me-1"></i> Generate Form B — <?= esc(date('M Y', strtotime($month . '-01'))) ?>
                </a>
                <span class="badge bg-light text-dark border p-2 align-self-center"><?= count($formB) ?> trip record(s)</span>
            </div>
        </div>
    </div>

    <!-- FR-7.4 — Certificates of Non-Usage -->
    <div class="col-lg-6">
        <div class="card-panel h-100">
            <h2 class="h6 fw-bold mb-3 d-flex align-items-center flex-wrap gap-2">
                <i class="fa-solid fa-certificate me-1" style="color: var(--primary-accent);"></i> 
                FR-7.4 — Certificates of Non-Usage
                <span class="policy-badge policy-badge-warning" data-bs-toggle="tooltip" data-bs-placement="top" title="<strong>Certificate of Non-Usage (CNU)</strong><br>Required by COA when a government vehicle logs 0 km in a billing cycle. Certifies the vehicle was parked/under repair, justifying zero fuel vouchers." tabindex="0" role="button">
                    CNU <i class="fa-solid fa-circle-question policy-info-icon"></i>
                </span>
            </h2>

            <?php if (empty($idleVehicles)): ?>
                <p class="text-muted small mb-3">
                    Every fleet unit recorded trip activity in <?= esc(date('F Y', strtotime($month . '-01'))) ?> — no idle vehicles to certify.
                </p>
            <?php else: ?>
                <p class="text-muted small mb-2">
                    <strong><?= count($idleVehicles) ?></strong> idle vehicle(s) with zero trips in
                    <?= esc(date('F Y', strtotime($month . '-01'))) ?> are eligible for a Certificate of Non-Usage for COA/HRDD submission:
                </p>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <?php foreach (array_slice($idleVehicles, 0, 12) as $v): ?>
                        <span class="badge bg-light text-dark border p-2 mono"><?= esc($v['plate_number']) ?></span>
                    <?php endforeach; ?>
                    <?php if (count($idleVehicles) > 12): ?>
                        <span class="badge bg-secondary p-2">+<?= count($idleVehicles) - 12 ?> more</span>
                    <?php endif; ?>
                </div>
                <form action="<?= base_url('compliance/non-usage') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="month" value="<?= esc($month) ?>">
                    <button type="submit" class="btn btn-secondary"
                            onclick="return confirm('Generate Certificates of Non-Usage for all idle vehicles this month?');">
                        <i class="fa-solid fa-certificate me-1"></i> Generate Certificates
                    </button>
                </form>
            <?php endif; ?>

            <?php if (!empty($certificates)): ?>
                <hr>
                <div class="kpi-label mb-2">Generated Certificates — <?= esc($month) ?></div>
                <ul class="list-unstyled mb-0 small">
                    <?php foreach ($certificates as $cert): ?>
                        <li class="d-flex justify-content-between align-items-center py-1 border-bottom">
                            <span>
                                <span class="mono fw-semibold"><?= esc($cert['certificate_no']) ?></span>
                                <span class="text-muted">— <?= esc($cert['plate_number'] ?? '?') ?></span>
                            </span>
                            <a href="<?= base_url('compliance/non-usage/' . $cert['id']) ?>" class="btn-icon-sm btn-outline-secondary" target="_blank" title="Print Certificate">
                                <i class="fa-solid fa-print"></i>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
