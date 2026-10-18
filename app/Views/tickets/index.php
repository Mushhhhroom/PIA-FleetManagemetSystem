<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--primary-accent); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            <i class="fa-solid fa-receipt me-1"></i> Form No. ADMIN-F-001 rev1 &bull; PIA Motorpool
        </div>
        <h1 class="page-title mb-1">Driver's Trip Tickets (e-DTT)</h1>
        <p class="text-muted small mb-0">Official travel authorization, Section B trip execution logs, fuel balance accounting, and dual certifications.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('dispatch') ?>" class="btn btn-primary">
            <i class="fa-solid fa-van-shuttle me-1"></i> Motorpool Dispatch Queue
        </a>
    </div>
</div>

<!-- Filter Tabs -->
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="<?= base_url('tickets') ?>" class="btn btn-sm <?= empty($activeFilter) || $activeFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary' ?>">
        All Tickets
    </a>
    <a href="<?= base_url('tickets?status=issued') ?>" class="btn btn-sm <?= $activeFilter === 'issued' ? 'btn-warning text-dark' : 'btn-outline-secondary' ?>">
        <i class="fa-solid fa-ticket me-1"></i> Issued (Pending Departure)
    </a>
    <a href="<?= base_url('tickets?status=departed') ?>" class="btn btn-sm <?= $activeFilter === 'departed' ? 'btn-primary' : 'btn-outline-secondary' ?>">
        <i class="fa-solid fa-route me-1"></i> Departed (Active Transit)
    </a>
    <a href="<?= base_url('tickets?status=returned') ?>" class="btn btn-sm <?= $activeFilter === 'returned' ? 'btn-info text-white' : 'btn-outline-secondary' ?>">
        <i class="fa-solid fa-warehouse me-1"></i> Returned (Pending Section B Audit)
    </a>
    <a href="<?= base_url('tickets?status=completed') ?>" class="btn btn-sm <?= $activeFilter === 'completed' ? 'btn-success' : 'btn-outline-secondary' ?>">
        <i class="fa-solid fa-circle-check me-1"></i> Reconciled & Audited
    </a>
</div>

<!-- Tickets Ledger Table -->
<div class="card-panel">
    <div class="table-responsive">
        <table class="table-minimal">
            <thead>
                <tr>
                    <th>DTT Serial No</th>
                    <th>Assigned Vehicle</th>
                    <th>Assigned Driver</th>
                    <th>Destination & Purpose</th>
                    <th>Departure Schedule</th>
                    <th>Distance & Fuel</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tickets)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-ticket fa-2x mb-2 d-block text-secondary opacity-50"></i>
                            No Driver's Trip Tickets found matching this status.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tickets as $t): ?>
                        <tr>
                            <td>
                                <a href="<?= base_url('tickets/' . $t['id']) ?>" class="mono fw-bold text-decoration-none text-dark d-block">
                                    <?= esc($t['ticket_serial_no']) ?>
                                </a>
                                <span class="mono text-muted" style="font-size: 0.7rem;">
                                    Ref: <?= esc($t['request_number']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="mono fw-bold text-dark small"><?= esc($t['plate_number']) ?></div>
                                <div class="text-muted" style="font-size: 0.72rem;"><?= esc($t['make'] . ' ' . $t['model']) ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark small"><?= esc($t['driver_name']) ?></div>
                                <div class="mono text-muted" style="font-size: 0.7rem;"><?= esc($t['driver_code']) ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark small text-truncate" style="max-width: 240px;" title="<?= esc($t['authorized_destination']) ?>">
                                    <i class="fa-solid fa-location-dot text-danger me-1"></i><?= esc($t['authorized_destination']) ?>
                                </div>
                                <div class="text-muted small text-truncate" style="max-width: 240px; font-size: 0.72rem;">
                                    <?= esc($t['authorized_purpose']) ?>
                                </div>
                            </td>
                            <td>
                                <div class="mono small" style="font-size: 0.76rem;">
                                    <?= date('M d, Y h:i A', strtotime($t['authorized_departure'])) ?>
                                </div>
                            </td>
                            <td>
                                <div class="mono small"><?= number_format((float)$t['total_distance_km'], 1) ?> km</div>
                                <div class="mono text-muted" style="font-size: 0.7rem;">
                                    Used: <?= number_format((float)$t['fuel_used_liters'], 1) ?> L
                                    <?php if ((int)$t['fuel_anomaly_flag'] === 1): ?>
                                        <span class="badge bg-danger text-white p-1" style="font-size: 0.6rem;">ANOMALY</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php
                                    $ticketPills = [
                                        'issued'    => ['label' => 'Issued', 'class' => 'bg-warning text-dark'],
                                        'departed'  => ['label' => 'In Transit', 'class' => 'bg-primary text-white'],
                                        'returned'  => ['label' => 'Returned', 'class' => 'bg-info text-white'],
                                        'completed' => ['label' => 'Audited', 'class' => 'bg-success text-white'],
                                    ];
                                    $st = $ticketPills[$t['status']] ?? ['label' => ucfirst($t['status']), 'class' => 'bg-secondary text-white'];
                                ?>
                                <span class="badge <?= $st['class'] ?> small py-1 px-2">
                                    <?= $st['label'] ?>
                                </span>
                            </td>
                            <td class="text-end table-action-cell">
                                <div class="table-actions">
                                    <a href="<?= base_url('tickets/' . $t['id']) ?>" class="btn btn-icon-sm btn-outline-secondary" title="View e-DTT">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="<?= base_url('tickets/' . $t['id'] . '/print') ?>" target="_blank" class="btn btn-icon-sm btn-outline-secondary" title="Print ADMIN-F-001 rev1">
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
