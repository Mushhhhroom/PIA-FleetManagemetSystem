<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            Freight Operations &bull; Dispatch Ledger
        </div>
        <h1 class="page-title mb-1">Trip Dispatches</h1>
        <p class="text-muted small mb-0">Active transit manifests, transport allocations, and real-time delivery status.</p>
    </div>
    <a href="<?= base_url('trips/new') ?>" class="btn btn-primary">
        <i class="fa-solid fa-plus me-1"></i> New Dispatch Manifest
    </a>
</div>

<!-- Dispatches Ledger Table Panel -->
<div class="card-panel">
    <div class="table-responsive">
        <table class="table-minimal">
            <thead>
                <tr>
                    <th>Manifest / Waybill</th>
                    <th>Transport Unit</th>
                    <th>Assigned Operator</th>
                    <th>Route Transit Corridor</th>
                    <th>Freight & Payload</th>
                    <th>Departure</th>
                    <th>Status</th>
                    <th class="text-end" style="width: 1%;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($trips)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-route fa-2x mb-2 d-block text-secondary opacity-50"></i>
                            No active or scheduled trip dispatches recorded.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($trips as $t): ?>
                        <tr>
                            <td>
                                <a href="<?= base_url('trips/' . $t['id']) ?>" class="mono fw-bold text-decoration-none" style="color: #0f172a; font-size: 0.85rem;">
                                    <?= esc($t['trip_number']) ?>
                                </a>
                                <?php if ($t['priority'] === 'urgent' || $t['priority'] === 'critical'): ?>
                                    <span class="status-badge status-out_of_service ms-1" style="font-size: 0.6rem; padding: 1px 5px;">
                                        <?= strtoupper($t['priority']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="mono fw-semibold" style="font-size: 0.82rem; color: var(--text-heading);"><?= esc($t['vehicle_code']) ?></div>
                                <div class="mono text-muted" style="font-size: 0.72rem;"><?= esc($t['plate_number']) ?></div>
                            </td>
                            <td>
                                <div class="fw-medium" style="font-size: 0.84rem; color: var(--text-heading);"><?= esc($t['driver_name']) ?></div>
                                <div class="mono text-muted" style="font-size: 0.72rem;"><?= esc($t['driver_phone']) ?></div>
                            </td>
                            <td style="max-width: 260px;">
                                <div class="text-truncate" style="font-size: 0.78rem; color: var(--text-heading);">
                                    <span class="text-muted">From:</span> <?= esc($t['origin_address']) ?>
                                </div>
                                <div class="text-truncate" style="font-size: 0.78rem; color: var(--text-heading);">
                                    <span class="text-muted">To:</span> <?= esc($t['destination_address']) ?>
                                </div>
                                <span class="mono text-secondary" style="font-size: 0.68rem; background: #f1f5f9; padding: 1px 6px; border-radius: 4px; display: inline-block; margin-top: 3px;">
                                    <?= number_format($t['distance_km'], 1) ?> km
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold" style="font-size: 0.82rem; color: var(--text-heading);"><?= esc($t['cargo_type']) ?></div>
                                <div class="mono text-muted" style="font-size: 0.72rem;"><?= number_format($t['cargo_weight_kg'], 0) ?> kg</div>
                            </td>
                            <td>
                                <div class="mono text-muted" style="font-size: 0.78rem;">
                                    <?= esc(date('M d, Y H:i', strtotime($t['scheduled_departure']))) ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?= esc($t['status']) ?>">
                                    <?= ucfirst(str_replace('_', ' ', esc($t['status']))) ?>
                                </span>
                            </td>
                            <td class="table-action-cell">
                                <div class="table-actions">
                                    <a href="<?= base_url('trips/' . $t['id']) ?>" class="btn btn-xs btn-outline-secondary">
                                        Manage &rarr;
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
