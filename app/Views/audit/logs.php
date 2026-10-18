<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--primary-accent); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            NFR-3 &bull; System Auditability &bull; Immutable Event Log
        </div>
        <h1 class="page-title mb-1">System Audit Trail</h1>
        <p class="text-muted small mb-0">
            Critical events — approvals, emergency overrides, status changes, login failures — with timestamps and IP addresses.
            Entries are append-only and cannot be edited or deleted through the application.
        </p>
    </div>
    <a href="<?= base_url('audit') ?>" class="btn btn-secondary">
        <i class="fa-solid fa-chart-pie me-1"></i> Audit Dashboard
    </a>
</div>

<!-- Filters -->
<div class="card-panel mb-4">
    <form action="<?= base_url('audit/logs') ?>" method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small fw-semibold text-dark">Event Type</label>
            <select name="event" class="form-select form-select-sm">
                <option value="">— All events —</option>
                <?php foreach ($eventTypes as $key => $label): ?>
                    <option value="<?= esc($key) ?>" <?= $event === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label small fw-semibold text-dark">Search description, user or IP</label>
            <input type="text" name="q" class="form-control form-control-sm" value="<?= esc($search) ?>" placeholder="e.g. EMERGENCY OVERRIDE, 192.168.">
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="fa-solid fa-filter me-1"></i> Filter</button>
            <a href="<?= base_url('audit/logs') ?>" class="btn btn-sm btn-outline-secondary flex-fill text-center">Reset</a>
        </div>
    </form>
</div>

<!-- Log Table -->
<div class="card-panel">
    <div class="table-responsive">
        <table class="table-minimal">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Event</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Entity</th>
                    <th>Description</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-shield-halved fa-2x mb-2 d-block text-secondary opacity-50"></i>
                            No audit entries match the current filter.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <?php
                            $danger = in_array($log['event_type'], ['login_failure', 'emergency_override', 'safety_check_failed', 'vrs_rejected'], true);
                        ?>
                        <tr style="<?= $danger ? 'background:#fef2f2;' : '' ?>">
                            <td class="mono text-muted" style="font-size: 0.74rem; white-space: nowrap;"><?= esc($log['created_at']) ?></td>
                            <td>
                                <span class="badge <?= $danger ? 'bg-danger' : (str_contains($log['event_type'], 'success') || str_contains($log['event_type'], 'approved') ? 'bg-success' : 'bg-secondary') ?>">
                                    <?= esc($eventTypes[$log['event_type']] ?? $log['event_type']) ?>
                                </span>
                            </td>
                            <td style="font-size: 0.8rem;"><?= esc($log['user_name'] ?? 'System / CLI') ?></td>
                            <td style="font-size: 0.75rem;"><?= esc(ucfirst($log['user_role'] ?? '—')) ?></td>
                            <td class="mono" style="font-size: 0.72rem;"><?= esc(trim(($log['entity_type'] ?? '—') . ' #' . ($log['entity_id'] ?? ''))) ?></td>
                            <td style="font-size: 0.78rem; min-width: 320px;"><?= esc($log['description']) ?></td>
                            <td class="mono" style="font-size: 0.74rem;"><?= esc($log['ip_address'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="text-muted text-end mt-2" style="font-size: 0.72rem;">
        Showing the latest <?= count($logs) ?> entr(ies) &bull; append-only storage
    </div>
</div>
<?= $this->endSection() ?>
