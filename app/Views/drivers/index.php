<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            Personnel &bull; Operator Roster
        </div>
        <h1 class="page-title mb-1">Driver Personnel</h1>
        <p class="text-muted small mb-0">Certified commercial operators, compliance licensing, safety metrics, and trip history.</p>
    </div>
    <a href="<?= base_url('drivers/new') ?>" class="btn btn-primary text-decoration-none">
        <i class="fa-solid fa-user-plus me-1"></i> Register Operator
    </a>
</div>

<!-- Drivers Ledger Table Panel -->
<div class="card-panel">
    <div class="table-responsive">
        <table class="table-minimal">
            <thead>
                <tr>
                    <th>Operator Code</th>
                    <th>Full Name & Contact</th>
                    <th>Professional License</th>
                    <th>License Expiry</th>
                    <th>Dispatches</th>
                    <th>Safety Score</th>
                    <th>Duty State</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($drivers)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-id-card fa-2x mb-2 d-block text-secondary opacity-50"></i>
                            No commercial drivers registered.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($drivers as $d): ?>
                        <tr>
                            <td>
                                <a href="<?= base_url('drivers/' . $d['id']) ?>" class="mono fw-bold text-decoration-none" style="color: #0f172a; font-size: 0.85rem;">
                                    <?= esc($d['driver_code']) ?>
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold" style="font-size: 0.84rem; color: var(--text-heading);">
                                    <?= esc($d['first_name'] . ' ' . $d['last_name']) ?>
                                </div>
                                <div class="mono text-muted" style="font-size: 0.72rem;">
                                    <i class="fa-solid fa-phone me-1"></i> <?= esc($d['phone']) ?>
                                </div>
                            </td>
                            <td>
                                <div class="mono fw-medium" style="font-size: 0.82rem; color: var(--text-heading);"><?= esc($d['license_number']) ?></div>
                                <div class="text-muted text-truncate" style="max-width: 200px; font-size: 0.72rem;"><?= esc($d['license_type']) ?></div>
                            </td>
                            <td>
                                <div class="mono text-muted" style="font-size: 0.78rem;"><?= esc($d['license_expiry']) ?></div>
                            </td>
                            <td>
                                <span class="mono fw-semibold" style="font-size: 0.84rem; color: var(--text-heading);"><?= $d['total_trips'] ?></span>
                                <span class="mono text-muted" style="font-size: 0.72rem;">trips</span>
                            </td>
                            <td>
                                <span class="mono fw-bold" style="font-size: 0.85rem; color: #166534; background: #f0fdf4; padding: 2px 8px; border-radius: 6px; border: 1px solid #bbf7d0;">
                                    <?= number_format($d['safety_score'], 1) ?>%
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?= esc($d['status']) ?>">
                                    <?= ucfirst(str_replace('_', ' ', esc($d['status']))) ?>
                                </span>
                            </td>
                            <td class="text-end table-action-cell">
                                <div class="table-actions">
                                    <a href="<?= base_url('drivers/' . $d['id']) ?>" class="btn btn-icon-sm btn-outline-secondary" title="View Scorecard">
                                        <i class="fa-solid fa-id-badge"></i>
                                    </a>
                                    <a href="<?= base_url('drivers/' . $d['id'] . '/edit') ?>" class="btn btn-icon-sm btn-outline-secondary" title="Edit Profile">
                                        <i class="fa-solid fa-pen"></i>
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
