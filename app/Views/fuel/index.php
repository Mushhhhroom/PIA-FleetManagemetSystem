<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            Logistics &bull; Energy Accounting
        </div>
        <h1 class="page-title mb-1">Fuel & Energy Records</h1>
        <p class="text-muted small mb-0">Monitor commercial fueling transactions, fuel economy rates, and station vendor billing.</p>
    </div>
    <a href="<?= base_url('fuel/new') ?>" class="btn btn-primary">
        <i class="fa-solid fa-plus me-1"></i> Record Fuel Purchase
    </a>
</div>

<!-- KPI Metric Tiles -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="kpi-tile">
            <div class="kpi-label">Cumulative Fuel Expenditure</div>
            <div class="kpi-value mono" style="color: #166534;">₱<?= number_format($totalSpend, 2) ?></div>
            <div class="kpi-sub">Total validated fleet disbursements</div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="kpi-tile">
            <div class="kpi-label">Cumulative Volume Dispensed</div>
            <div class="kpi-value mono" style="color: #2563eb;"><?= number_format($totalLiters, 1) ?> <span style="font-size: 0.95rem; font-weight: 500; color: var(--text-muted);">Liters</span></div>
            <div class="kpi-sub">Diesel and commercial fuel volume</div>
        </div>
    </div>
</div>

<!-- Fuel Transactions Table Panel -->
<div class="card-panel">
    <div class="table-responsive">
        <table class="table-minimal">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Invoice / Receipt #</th>
                    <th>Transport Unit</th>
                    <th>Operator</th>
                    <th>Service Station</th>
                    <th>Volume (L)</th>
                    <th>Unit Rate</th>
                    <th>Total Spend</th>
                    <th>Pump Odometer</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-gas-pump fa-2x mb-2 d-block text-secondary opacity-50"></i>
                            No fuel transaction records on file.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $f): ?>
                        <tr>
                            <td>
                                <span class="mono text-muted" style="font-size: 0.78rem;"><?= esc($f['fuel_date']) ?></span>
                            </td>
                            <td>
                                <span class="mono fw-semibold" style="font-size: 0.82rem; color: #0f172a;"><?= esc($f['receipt_no']) ?></span>
                            </td>
                            <td>
                                <div class="mono fw-bold" style="font-size: 0.82rem; color: var(--text-heading);"><?= esc($f['vehicle_code']) ?></div>
                                <div class="mono text-muted" style="font-size: 0.7rem;"><?= esc($f['plate_number']) ?></div>
                            </td>
                            <td>
                                <div class="fw-medium" style="font-size: 0.82rem; color: var(--text-heading);">
                                    <?= esc($f['driver_name'] ?: 'Unassigned') ?>
                                </div>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 220px; font-size: 0.8rem; color: var(--text-heading);">
                                    <?= esc($f['fuel_station'] ?: 'Commercial Station') ?>
                                </div>
                            </td>
                            <td>
                                <span class="mono fw-semibold" style="font-size: 0.84rem;"><?= number_format($f['liters'], 2) ?></span>
                                <span class="mono text-muted" style="font-size: 0.7rem;">L</span>
                            </td>
                            <td>
                                <span class="mono text-muted" style="font-size: 0.78rem;">₱<?= number_format($f['cost_per_liter'], 2) ?></span>
                            </td>
                            <td>
                                <span class="mono fw-bold" style="font-size: 0.85rem; color: #166534;">₱<?= number_format($f['total_cost'], 2) ?></span>
                            </td>
                            <td>
                                <span class="mono text-muted" style="font-size: 0.78rem;"><?= number_format($f['odometer_km'], 1) ?> km</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
