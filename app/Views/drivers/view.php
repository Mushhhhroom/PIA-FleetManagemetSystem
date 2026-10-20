<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            Personnel &bull; Operator Scorecard
        </div>
        <div class="d-flex align-items-center gap-3">
            <h1 class="page-title mb-0" style="font-size: 1.45rem; color: #0f172a;">
                <?= esc($driver['first_name'] . ' ' . $driver['last_name']) ?>
            </h1>
            <span class="status-badge status-<?= esc($driver['status']) ?>">
                <?= ucfirst(str_replace('_', ' ', esc($driver['status']))) ?>
            </span>
        </div>
        <p class="text-muted small mb-0 mt-1">
            Operator Code: <span class="mono fw-semibold text-dark"><?= esc($driver['driver_code']) ?></span> &bull; 
            License: <span class="mono text-dark"><?= esc($driver['license_number']) ?></span> (<?= esc($driver['license_type']) ?>)
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= base_url('drivers/' . $driver['id'] . '/edit') ?>" class="btn btn-secondary">
            <i class="fa-solid fa-pen me-1"></i> Edit Profile
        </a>
        <form action="<?= base_url('drivers/' . $driver['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Are you sure you want to deactivate this driver?');" class="m-0">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-danger">
                <i class="fa-solid fa-user-xmark me-1"></i> Deactivate
            </button>
        </form>
    </div>
</div>

<!-- KPI Metric Tiles -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="kpi-tile">
            <div class="kpi-label">Safety Rating Score</div>
            <div class="kpi-value mono" style="color: #166534;"><?= number_format($driver['safety_score'], 1) ?>%</div>
            <div class="kpi-sub">Telematics road compliance</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-tile">
            <div class="kpi-label">Lifetime Dispatches</div>
            <div class="kpi-value mono"><?= $driver['total_trips'] ?></div>
            <div class="kpi-sub">Completed transport runs</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-tile">
            <div class="kpi-label">License Expiry</div>
            <div class="kpi-value mono" style="font-size: 1.25rem;"><?= esc($driver['license_expiry']) ?></div>
            <div class="kpi-sub text-success"><i class="fa-solid fa-circle-check me-1"></i> Official Government Driver</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-tile">
            <div class="kpi-label">Assigned Vehicle</div>
            <?php if ($assignedVehicle): ?>
                <div class="kpi-value mono" style="font-size: 1.25rem; color: #2563eb;"><?= esc($assignedVehicle['vehicle_code']) ?></div>
                <div class="kpi-sub mono"><?= esc($assignedVehicle['plate_number']) ?> &bull; <?= esc($assignedVehicle['make']) ?></div>
            <?php else: ?>
                <div class="kpi-value text-muted" style="font-size: 1.1rem;">Unassigned</div>
                <div class="kpi-sub">Ready for motorpool dispatch</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Assigned Trip Tickets Log -->
<div class="card-panel">
    <div class="card-panel-header">
        <span class="card-panel-title">Assigned Official Trip Tickets (<?= count($trips) ?>)</span>
    </div>
    <div class="table-responsive">
        <table class="table-minimal">
            <thead>
                <tr>
                    <th>e-DTT Serial #</th>
                    <th>Authorized Destination &amp; Purpose</th>
                    <th>Vehicle</th>
                    <th>Distance</th>
                    <th>Departure Date</th>
                    <th>Status</th>
                    <th class="text-end" style="width: 1%;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($trips)): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">No official Trip Tickets recorded for this driver.</td></tr>
                <?php else: ?>
                    <?php foreach ($trips as $t): ?>
                        <tr>
                            <td>
                                <strong class="mono" style="color: #0f172a;"><?= esc($t['ticket_serial_no']) ?></strong>
                                <?php if (!empty($t['request_number'])): ?>
                                    <div class="mono text-muted" style="font-size: 0.7rem;">VRS: <?= esc($t['request_number']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="text-truncate fw-medium" style="font-size: 0.78rem; max-width: 280px;"><?= esc($t['authorized_destination'] ?? $t['destination'] ?? 'Official Travel') ?></div>
                                <div class="text-truncate text-muted" style="font-size: 0.74rem; max-width: 280px;"><?= esc($t['authorized_purpose'] ?? '') ?></div>
                            </td>
                            <td class="mono">
                                <span class="fw-semibold"><?= esc($t['vehicle_code'] ?? '') ?></span>
                                <span class="text-muted small d-block"><?= esc($t['plate_number'] ?? '') ?></span>
                            </td>
                            <td class="mono"><?= number_format((float)($t['total_distance_km'] ?? 0), 1) ?> km</td>
                            <td class="mono text-muted"><?= esc($t['authorized_departure'] ?? $t['departure_time'] ?? '') ?></td>
                            <td><span class="status-badge status-<?= esc($t['status']) ?>"><?= strtoupper(str_replace('_', ' ', $t['status'])) ?></span></td>
                            <td class="table-action-cell">
                                <div class="table-actions">
                                    <a href="<?= base_url('tickets/' . $t['id']) ?>" class="btn btn-xs btn-outline-secondary">Open e-DTT &rarr;</a>
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
