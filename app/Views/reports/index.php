<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            Executive Intelligence &bull; Financial Audit
        </div>
        <h1 class="page-title mb-1">Fleet Cost & Analytics Reports</h1>
        <p class="text-muted small mb-0">Aggregate operational expenditure, asset utilization rates, and regulatory data exports.</p>
    </div>
    <div class="dropdown">
        <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="display: inline-flex; align-items: center; gap: 6px;">
            <i class="fa-solid fa-file-csv"></i>
            <span>Export Ledgers (CSV)</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="border: 1px solid var(--border-subtle); border-radius: 8px; font-size: 0.82rem;">
            <li>
                <a class="dropdown-item py-2" href="<?= base_url('reports/export?type=trips') ?>">
                    <i class="fa-solid fa-route me-2 text-primary"></i> Export Trip Dispatches
                </a>
            </li>
            <li>
                <a class="dropdown-item py-2" href="<?= base_url('reports/export?type=fuel') ?>">
                    <i class="fa-solid fa-gas-pump me-2 text-success"></i> Export Fuel Disbursements
                </a>
            </li>
            <li>
                <a class="dropdown-item py-2" href="<?= base_url('reports/export?type=maintenance') ?>">
                    <i class="fa-solid fa-wrench me-2 text-warning"></i> Export Maintenance Work Orders
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- KPI Metric Tiles -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="kpi-tile">
            <div class="kpi-label">Cumulative Dispatched Distance</div>
            <div class="kpi-value mono"><?= number_format($totalDistance, 1) ?> <span style="font-size: 0.95rem; font-weight: 500; color: var(--text-muted);">km</span></div>
            <div class="kpi-sub"><?= $completedTrips ?> of <?= $totalTrips ?> Dispatches Completed</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-tile">
            <div class="kpi-label">Total Fuel Expenditure</div>
            <div class="kpi-value mono" style="color: #166534;">₱<?= number_format($totalFuelCost, 0) ?></div>
            <div class="kpi-sub mono"><?= number_format($totalLiters, 1) ?> Liters Dispensed</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-tile">
            <div class="kpi-label">Workshop Maintenance Spend</div>
            <div class="kpi-value mono" style="color: #92400e;">₱<?= number_format($totalMaintCost, 0) ?></div>
            <div class="kpi-sub">Parts, Routine PMS & Overhauls</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-tile">
            <div class="kpi-label">Operating Cost Index</div>
            <?php 
                $totalOpCost = $totalFuelCost + $totalMaintCost;
                $costPerKm = $totalDistance > 0 ? round($totalOpCost / $totalDistance, 2) : 0;
            ?>
            <div class="kpi-value mono" style="color: #2563eb;">₱<?= number_format($costPerKm, 2) ?> <span style="font-size: 0.95rem; font-weight: 500; color: var(--text-muted);">/ km</span></div>
            <div class="kpi-sub">Blended operating efficiency</div>
        </div>
    </div>
</div>

<!-- Asset Efficiency Breakdown Table -->
<div class="card-panel">
    <div class="card-panel-header">
        <span class="card-panel-title">Asset Utilization & Efficiency Matrix</span>
    </div>
    <div class="table-responsive">
        <table class="table-minimal">
            <thead>
                <tr>
                    <th>Unit Code</th>
                    <th>Make & Model</th>
                    <th>Plate Number</th>
                    <th>Chassis Category</th>
                    <th>Cumulative Distance</th>
                    <th>Fuel Level</th>
                    <th>Assigned Operator</th>
                    <th>Operational Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vehicles as $v): ?>
                    <tr>
                        <td>
                            <a href="<?= base_url('vehicles/' . $v['id']) ?>" class="mono fw-bold text-decoration-none" style="color: #0f172a; font-size: 0.85rem;">
                                <?= esc($v['vehicle_code']) ?>
                            </a>
                        </td>
                        <td>
                            <div class="fw-semibold" style="font-size: 0.82rem; color: var(--text-heading);"><?= esc($v['make'] . ' ' . $v['model']) ?></div>
                            <span class="mono text-muted" style="font-size: 0.72rem;"><?= esc($v['year']) ?></span>
                        </td>
                        <td>
                            <span class="mono fw-medium" style="font-size: 0.82rem; color: var(--text-heading);"><?= esc($v['plate_number']) ?></span>
                        </td>
                        <td>
                            <div style="font-size: 0.8rem;"><?= ucfirst(str_replace('_', ' ', esc($v['type']))) ?></div>
                            <span class="mono text-muted" style="font-size: 0.7rem;"><?= number_format($v['max_payload_kg'], 0) ?> kg capacity</span>
                        </td>
                        <td>
                            <span class="mono fw-semibold" style="font-size: 0.84rem;"><?= number_format($v['odometer_km'], 1) ?></span>
                            <span class="mono text-muted" style="font-size: 0.7rem;">km</span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2" style="width: 120px;">
                                <div class="progress flex-grow-1" style="height: 5px; background: #e2e8f0; border-radius: 999px;">
                                    <div class="progress-bar bg-<?= $v['current_fuel_level'] > 25 ? 'primary' : 'danger' ?>" style="width: <?= $v['current_fuel_level'] ?>%; border-radius: 999px;"></div>
                                </div>
                                <span class="mono text-muted" style="font-size: 0.72rem;"><?= $v['current_fuel_level'] ?>%</span>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($v['driver_name'])): ?>
                                <span class="fw-medium" style="font-size: 0.82rem; color: var(--text-heading);"><i class="fa-solid fa-user me-1 text-muted"></i> <?= esc($v['driver_name']) ?></span>
                            <?php else: ?>
                                <span class="mono text-muted" style="font-size: 0.72rem; font-style: italic;">Unassigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?= esc($v['status']) ?>">
                                <?= ucfirst(str_replace('_', ' ', esc($v['status']))) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
