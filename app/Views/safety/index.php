<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--primary-accent); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            Module 5 &bull; Pre-Trip Safety Registry &bull; FR-5.1 / FR-5.2
        </div>
        <h1 class="page-title mb-1">BLOWBAGETS Safety Check Registry</h1>
        <p class="text-muted small mb-0">Complete audit of all pre-trip safety inspections, failures and vehicle safety locks.</p>
    </div>
    <a href="<?= base_url('pir') ?>" class="btn btn-secondary">
        <i class="fa-solid fa-screwdriver-wrench me-1"></i> Mechanic PIR Queue
    </a>
</div>

<?php $failedCount = count(array_filter($checks, fn($c) => $c['result'] === 'fail')); ?>

<!-- KPI Tiles -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="kpi-tile">
            <div class="kpi-label">Total Checks Recorded</div>
            <div class="kpi-value mono"><?= count($checks) ?></div>
            <div class="kpi-sub">All pre-trip inspections on file</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi-tile">
            <div class="kpi-label">Passed</div>
            <div class="kpi-value mono" style="color: #166534;"><?= count($checks) - $failedCount ?></div>
            <div class="kpi-sub">Cleared for trip activation</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi-tile">
            <div class="kpi-label">Failed (Safety Locks)</div>
            <div class="kpi-value mono" style="color: #991b1b;"><?= $failedCount ?></div>
            <div class="kpi-sub">Vehicles locked &amp; PIRs auto-generated</div>
        </div>
    </div>
</div>

<!-- Registry Table -->
<div class="card-panel">
    <div class="table-responsive">
        <table class="table-minimal">
            <thead>
                <tr>
                    <th>Date Checked</th>
                    <th>Ticket</th>
                    <th>Vehicle</th>
                    <th>Driver</th>
                    <th>Result</th>
                    <th>Failed Items</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($checks)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-clipboard-check fa-2x mb-2 d-block text-secondary opacity-50"></i>
                            No safety checks have been recorded yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($checks as $c): ?>
                        <tr>
                            <td class="mono" style="font-size: 0.78rem;"><?= esc($c['created_at']) ?></td>
                            <td>
                                <?php if (!empty($c['trip_ticket_id'])): ?>
                                    <a href="<?= base_url('tickets/' . $c['trip_ticket_id']) ?>" class="mono fw-semibold text-decoration-none" style="font-size: 0.8rem;">
                                        <?= esc($c['ticket_serial_no'] ?? '#' . $c['trip_ticket_id']) ?>
                                    </a>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td>
                                <span class="mono fw-semibold"><?= esc($c['plate_number'] ?? 'N/A') ?></span>
                                <div class="text-muted" style="font-size: 0.72rem;"><?= esc(trim(($c['make'] ?? '') . ' ' . ($c['model'] ?? ''))) ?></div>
                            </td>
                            <td style="font-size: 0.82rem;"><?= esc($c['driver_name'] ?? '—') ?></td>
                            <td>
                                <span class="badge <?= $c['result'] === 'pass' ? 'bg-success' : 'bg-danger' ?>">
                                    <?= strtoupper($c['result']) ?>
                                </span>
                            </td>
                            <td style="font-size: 0.78rem; color: #991b1b;">
                                <?php
                                    $failed = $c['failed_items'] ? (array) json_decode($c['failed_items'], true) : [];
                                    echo esc(!empty($failed) ? implode(', ', $failed) : '—');
                                ?>
                            </td>
                            <td class="text-muted" style="font-size: 0.78rem;"><?= esc($c['remarks'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
