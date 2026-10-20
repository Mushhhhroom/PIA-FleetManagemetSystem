<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            PIA Motorpool &bull; Asset Integrity
        </div>
        <h1 class="page-title mb-1">Work Orders &amp; Preventive Service</h1>
        <p class="text-muted small mb-0">Scheduled PMS servicing intervals, defect remediation, and motorpool workshop ledger.</p>
    </div>
    <a href="<?= base_url('maintenance/new') ?>" class="btn btn-primary">
        <i class="fa-solid fa-plus me-1"></i> Issue Work Order
    </a>
</div>

<!-- Maintenance Work Order Ledger -->
<div class="card-panel">
    <div class="table-responsive">
        <table class="table-minimal">
            <thead>
                <tr>
                    <th>Work Order Ref</th>
                    <th>Transport Unit</th>
                    <th>Service & Scope</th>
                    <th>Priority</th>
                    <th>Scheduled Date</th>
                    <th>Workshop Facility</th>
                    <th>Invoiced Cost</th>
                    <th>State</th>
                    <th class="text-end" style="width: 1%;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-wrench fa-2x mb-2 d-block text-secondary opacity-50"></i>
                            No maintenance work orders currently logged.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $m): ?>
                        <tr>
                            <td>
                                <span class="mono fw-bold" style="color: #0f172a; font-size: 0.85rem;"><?= esc($m['reference_no']) ?></span>
                            </td>
                            <td>
                                <div class="mono fw-semibold" style="font-size: 0.82rem; color: var(--text-heading);"><?= esc($m['vehicle_code']) ?></div>
                                <div class="mono text-muted" style="font-size: 0.72rem;"><?= esc($m['plate_number']) ?></div>
                            </td>
                            <td>
                                <div class="fw-medium" style="font-size: 0.84rem; color: var(--text-heading);"><?= esc($m['service_type']) ?></div>
                                <?php if (!empty($m['description'])): ?>
                                    <div class="text-muted text-truncate" style="max-width: 240px; font-size: 0.74rem;"><?= esc($m['description']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    $pClass = match($m['priority']) {
                                        'critical', 'high' => 'status-out_of_service',
                                        'medium'           => 'status-maintenance',
                                        default            => 'status-scheduled',
                                    };
                                ?>
                                <span class="status-badge <?= $pClass ?>" style="font-size: 0.65rem; padding: 2px 6px;">
                                    <?= strtoupper($m['priority']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="mono text-muted" style="font-size: 0.78rem;"><?= esc($m['scheduled_date']) ?></div>
                            </td>
                            <td>
                                <div style="font-size: 0.82rem; color: var(--text-heading);"><?= esc($m['service_center'] ?: 'In-House Workshop') ?></div>
                                <?php if (!empty($m['technician_name'])): ?>
                                    <div class="text-muted" style="font-size: 0.72rem;"><i class="fa-solid fa-wrench me-1"></i> <?= esc($m['technician_name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="mono fw-bold" style="font-size: 0.85rem; color: var(--text-heading);">₱<?= number_format($m['cost'], 2) ?></span>
                            </td>
                            <td>
                                <span class="status-badge status-<?= esc($m['status']) ?>">
                                    <?= ucfirst(str_replace('_', ' ', esc($m['status']))) ?>
                                </span>
                            </td>
                            <td class="table-action-cell">
                                <div class="table-actions">
                                    <?php if ($m['status'] !== 'completed'): ?>
                                        <button class="btn btn-xs btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#completeModal-<?= $m['id'] ?>">
                                            <i class="fa-solid fa-pen-to-square me-1"></i> Update
                                        </button>
                                        
                                        <!-- Modal for updating work order status -->
                                        <div class="modal fade text-start" id="completeModal-<?= $m['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content" style="border: 1px solid var(--border-subtle); border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
                                                    <form action="<?= base_url('maintenance/' . $m['id'] . '/status') ?>" method="POST">
                                                        <?= csrf_field() ?>
                                                        <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid var(--border-subtle);">
                                                            <h6 class="modal-title fw-bold mono mb-0" style="color: #0f172a;">Order #<?= esc($m['reference_no']) ?></h6>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body" style="padding: 24px;">
                                                            <div class="mb-3">
                                                                <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Lifecycle Stage</label>
                                                                <select name="status" class="form-select" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                                                                    <option value="scheduled" <?= $m['status'] === 'scheduled' ? 'selected' : '' ?>>Scheduled (Pending Intake)</option>
                                                                    <option value="in_progress" <?= $m['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress (Vehicle In Bay)</option>
                                                                    <option value="completed" <?= $m['status'] === 'completed' ? 'selected' : '' ?>>Completed (Certified Road-Ready)</option>
                                                                    <option value="cancelled" <?= $m['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-2">
                                                                <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Final Repair Cost (₱)</label>
                                                                <input type="number" step="0.01" name="cost" class="form-control mono" value="<?= $m['cost'] ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer" style="border-top: 1px solid var(--border-subtle); background: #f8fafc;">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="status-badge status-completed" style="font-size: 0.65rem;">
                                            <i class="fa-solid fa-check"></i> Certified
                                        </span>
                                    <?php endif; ?>
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
