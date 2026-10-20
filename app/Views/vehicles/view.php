<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            Asset Ledger &bull; Vehicle Profile
        </div>
        <div class="d-flex align-items-center gap-3">
            <h1 class="page-title mb-0 mono" style="font-size: 1.45rem; color: #0f172a;"><?= esc($vehicle['vehicle_code']) ?></h1>
            <span class="status-badge status-<?= esc($vehicle['status']) ?>">
                <?= ucfirst(str_replace('_', ' ', esc($vehicle['status']))) ?>
            </span>
        </div>
        <p class="text-muted small mb-0 mt-1">
            <?= esc($vehicle['make']) ?> <?= esc($vehicle['model']) ?> (<?= esc($vehicle['year']) ?>) &bull; Plate: <span class="mono fw-semibold text-dark"><?= esc($vehicle['plate_number']) ?></span> &bull; VIN: <span class="mono text-muted"><?= esc($vehicle['vin']) ?></span>
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= base_url('vehicles/' . $vehicle['id'] . '/edit') ?>" class="btn btn-secondary">
            <i class="fa-solid fa-pen me-1"></i> Edit Specifications
        </a>
        <form action="<?= base_url('vehicles/' . $vehicle['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Are you sure you want to permanently decommission this vehicle asset?');" class="m-0">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-danger">
                <i class="fa-solid fa-trash me-1"></i> Decommission
            </button>
        </form>
    </div>
</div>

<!-- KPI Metric Tiles -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="kpi-tile">
            <div class="kpi-label">Cumulative Odometer</div>
            <div class="kpi-value mono"><?= number_format($vehicle['odometer_km'], 1) ?> <span style="font-size: 0.95rem; font-weight: 500; color: var(--text-muted);">km</span></div>
            <div class="kpi-sub">Total operational distance</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-tile">
            <div class="kpi-label">Fuel Reservoir</div>
            <div class="kpi-value mono"><?= $vehicle['current_fuel_level'] ?>%</div>
            <div class="kpi-sub">
                Tank: <?= esc($vehicle['fuel_capacity_liters']) ?>L &bull; <?= ucfirst(esc($vehicle['fuel_type'])) ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-tile">
            <div class="kpi-label">Live Telemetry Speed</div>
            <div class="kpi-value mono" style="color: #2563eb;"><?= $vehicle['current_speed'] ?> <span style="font-size: 0.95rem; font-weight: 500; color: var(--text-muted);">km/h</span></div>
            <div class="kpi-sub mono text-capitalize">Engine: <?= esc($vehicle['engine_status'] ?: 'off') ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-tile">
            <div class="kpi-label">Assigned Operator</div>
            <div class="kpi-value" style="font-size: 1.25rem;"><?= esc($vehicle['driver_name'] ?: 'Unassigned') ?></div>
            <div class="kpi-sub mono"><?= esc($vehicle['driver_phone'] ?: 'N/A') ?></div>
        </div>
    </div>
</div>

<!-- Tabbed Ledger Panels -->
<div class="card-panel">
    <div class="p-3 border-bottom bg-light">
        <ul class="nav nav-pills" id="vehicleTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active btn-sm fw-semibold" data-bs-toggle="pill" data-bs-target="#tripsTab" style="font-size: 0.8rem; border-radius: 6px; padding: 6px 14px;">
                    <i class="fa-solid fa-ticket me-1"></i> Official Trip Tickets (<?= count($trips) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link btn-sm fw-semibold" data-bs-toggle="pill" data-bs-target="#maintenanceTab" style="font-size: 0.8rem; border-radius: 6px; padding: 6px 14px;">
                    <i class="fa-solid fa-wrench me-1"></i> Maintenance (<?= count($maintenance) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link btn-sm fw-semibold" data-bs-toggle="pill" data-bs-target="#fuelTab" style="font-size: 0.8rem; border-radius: 6px; padding: 6px 14px;">
                    <i class="fa-solid fa-gas-pump me-1"></i> Fuel Records (<?= count($fuelLogs) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link btn-sm fw-semibold" data-bs-toggle="pill" data-bs-target="#telemetryTab" style="font-size: 0.8rem; border-radius: 6px; padding: 6px 14px;">
                    <i class="fa-solid fa-satellite-dish me-1"></i> Telematics Logs
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content">
        <!-- Trips Tab -->
        <div class="tab-pane fade show active" id="tripsTab">
            <div class="table-responsive">
                <table class="table-minimal">
                    <thead>
                        <tr>
                            <th>e-DTT Serial #</th>
                            <th>Authorized Destination &amp; Purpose</th>
                            <th>Driver</th>
                            <th>Distance</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($trips)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No official Trip Tickets logged for this vehicle.</td></tr>
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
                                    <td><?= esc($t['driver_name'] ?: 'Unassigned') ?></td>
                                    <td class="mono"><?= number_format((float)($t['total_distance_km'] ?? 0), 1) ?> km</td>
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

        <!-- Maintenance Tab -->
        <div class="tab-pane fade" id="maintenanceTab">
            <div class="table-responsive">
                <table class="table-minimal">
                    <thead>
                        <tr>
                            <th>Work Order #</th>
                            <th>Service Description</th>
                            <th>Scheduled Date</th>
                            <th>Workshop Facility</th>
                            <th>Invoiced Cost</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($maintenance)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No maintenance orders on file.</td></tr>
                        <?php else: ?>
                            <?php foreach ($maintenance as $m): ?>
                                <tr>
                                    <td><strong class="mono"><?= esc($m['reference_no']) ?></strong></td>
                                    <td><?= esc($m['service_type']) ?></td>
                                    <td class="mono"><?= esc($m['scheduled_date']) ?></td>
                                    <td><?= esc($m['service_center'] ?: 'In-house Fleet Shop') ?></td>
                                    <td class="mono fw-semibold">₱<?= number_format($m['cost'], 2) ?></td>
                                    <td><span class="status-badge status-<?= esc($m['status']) ?>"><?= ucfirst(str_replace('_', ' ', $m['status'])) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Fuel Tab -->
        <div class="tab-pane fade" id="fuelTab">
            <div class="table-responsive">
                <table class="table-minimal">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Receipt Ref</th>
                            <th>Station Hub</th>
                            <th>Volume</th>
                            <th>Unit Rate</th>
                            <th>Total Cost</th>
                            <th>Odometer</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fuelLogs)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No fuel transactions on file.</td></tr>
                        <?php else: ?>
                            <?php foreach ($fuelLogs as $f): ?>
                                <tr>
                                    <td class="mono"><?= esc($f['fuel_date']) ?></td>
                                    <td class="mono"><?= esc($f['receipt_no']) ?></td>
                                    <td><?= esc($f['fuel_station']) ?></td>
                                    <td class="mono"><?= number_format($f['liters'], 2) ?> L</td>
                                    <td class="mono">₱<?= number_format($f['cost_per_liter'], 2) ?></td>
                                    <td class="mono fw-semibold">₱<?= number_format($f['total_cost'], 2) ?></td>
                                    <td class="mono"><?= number_format($f['odometer_km'], 1) ?> km</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Telemetry Tab -->
        <div class="tab-pane fade" id="telemetryTab">
            <div class="table-responsive">
                <table class="table-minimal mono" style="font-size: 0.78rem;">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Latitude</th>
                            <th>Longitude</th>
                            <th>Velocity (km/h)</th>
                            <th>Fuel Level</th>
                            <th>Engine State</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($telemetry)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted font-sans">No recent telemetry logs received.</td></tr>
                        <?php else: ?>
                            <?php foreach ($telemetry as $tel): ?>
                                <tr>
                                    <td><?= esc($tel['created_at']) ?></td>
                                    <td><?= esc($tel['latitude']) ?></td>
                                    <td><?= esc($tel['longitude']) ?></td>
                                    <td><?= esc($tel['speed_kmh']) ?> km/h</td>
                                    <td><?= esc($tel['fuel_level']) ?>%</td>
                                    <td class="text-capitalize"><?= esc($tel['engine_status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
