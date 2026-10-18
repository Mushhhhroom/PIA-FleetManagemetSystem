<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    // Mirrors the sidebar role aesthetics for consistency
    $roleBadges = [
        'admin'          => ['label' => 'Super Admin / Director',  'color' => '#3b82f6', 'bg' => 'rgba(59, 130, 246, 0.15)'],
        'requestor'      => ['label' => 'Staff / Requestor',       'color' => '#8b5cf6', 'bg' => 'rgba(139, 92, 246, 0.15)'],
        'approver_admin' => ['label' => 'Tier 2 Approver (Admin)', 'color' => '#06b6d4', 'bg' => 'rgba(6, 182, 212, 0.15)'],
        'dispatcher'     => ['label' => 'Motorpool Dispatcher (Tier 1 Approver & Asset Allocation)', 'color' => '#10b981', 'bg' => 'rgba(16, 185, 129, 0.15)'],
        'guard'          => ['label' => 'Gate Security Officer',   'color' => '#64748b', 'bg' => 'rgba(100, 116, 139, 0.15)'],
        'auditor'        => ['label' => 'COA Resident Auditor',    'color' => '#d97706', 'bg' => 'rgba(217, 119, 6, 0.15)'],
        'driver'         => ['label' => 'Official Driver',         'color' => '#0284c7', 'bg' => 'rgba(2, 132, 199, 0.15)'],
        'maintenance'    => ['label' => 'Safety & PMS Tech',       'color' => '#ea580c', 'bg' => 'rgba(234, 88, 12, 0.15)'],
    ];
?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            Administration &bull; Super Admin Exclusive
        </div>
        <h1 class="page-title mb-1">User Account Administration</h1>
        <p class="text-muted small mb-0">Provision and govern sign-in accounts for every role in the fleet system. All actions are recorded in the immutable audit trail (NFR-3).</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= base_url('admin/flowchart') ?>" class="btn btn-secondary">
            <i class="fa-solid fa-diagram-project me-1 text-primary"></i> System Flowchart &amp; Nav
        </a>
        <a href="<?= base_url('users/new') ?>" class="btn btn-primary">
            <i class="fa-solid fa-user-plus me-1"></i> Provision New Account
        </a>
    </div>
</div>

<!-- Access Restriction Notice -->
<div class="d-flex align-items-center gap-3 p-3 mb-4 rounded-2 border" style="background: #f8fafc; border-color: var(--border-subtle) !important;">
    <div style="width: 38px; height: 38px; border-radius: 9px; background: rgba(59, 130, 246, 0.12); color: #2563eb; display: grid; place-items: center; font-size: 1rem; flex-shrink: 0;">
        <i class="fa-solid fa-user-shield"></i>
    </div>
    <div class="small" style="color: var(--text-body);">
        <strong class="text-dark d-block">Restricted Module — Super Admin Only</strong>
        <span class="text-muted">Passwords are hashed (Argon2id / strong default), sign-in identifiers are unique, and the last active Super Admin can never be deactivated or demoted.</span>
    </div>
</div>

<!-- Summary Tiles -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="kpi-tile">
            <div class="kpi-label">Total Accounts</div>
            <div class="kpi-value"><?= (int) $summary['total'] ?></div>
            <div class="kpi-sub">provisioned identities</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-tile">
            <div class="kpi-label">Active</div>
            <div class="kpi-value" style="color: #166534;"><?= (int) $summary['active'] ?></div>
            <div class="kpi-sub">can sign in</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-tile">
            <div class="kpi-label">Deactivated</div>
            <div class="kpi-value" style="color: #991b1b;"><?= (int) $summary['inactive'] ?></div>
            <div class="kpi-sub">sign-in blocked</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-tile">
            <div class="kpi-label">Assignable Roles</div>
            <div class="kpi-value"><?= (int) $summary['roles'] ?></div>
            <div class="kpi-sub">RBAC profiles</div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card-panel">
    <div class="card-panel-body py-3">
        <form method="GET" action="<?= base_url('users') ?>" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-semibold text-dark mb-1">Search</label>
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Name or e-mail address..." value="<?= esc($q) ?>" style="border-radius: 7px; border-color: var(--border-subtle);">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-dark mb-1">Role</label>
                <select name="role" class="form-select form-select-sm" style="border-radius: 7px; border-color: var(--border-subtle);">
                    <option value="">All roles</option>
                    <?php foreach ($roles as $value => $label): ?>
                        <option value="<?= esc($value) ?>" <?= $roleFilter === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-dark mb-1">Status</label>
                <select name="status" class="form-select form-select-sm" style="border-radius: 7px; border-color: var(--border-subtle);">
                    <option value="">All</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Deactivated</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-secondary flex-fill"><i class="fa-solid fa-filter me-1"></i> Apply</button>
                <a href="<?= base_url('users') ?>" class="btn btn-sm btn-outline-secondary flex-fill text-center">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Accounts Ledger -->
<div class="card-panel">
    <div class="table-responsive">
        <table class="table-minimal">
            <thead>
                <tr>
                    <th>Account Holder</th>
                    <th>Role Profile</th>
                    <th>Office / Designation</th>
                    <th>MFA</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-user-slash fa-2x mb-2 d-block text-secondary opacity-50"></i>
                            No accounts match the current filters.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $selfId = (int) session()->get('user_id'); ?>
                    <?php foreach ($users as $u): ?>
                        <?php $badge = $roleBadges[$u['role']] ?? ['label' => ucfirst($u['role']), 'color' => '#3b82f6', 'bg' => 'rgba(59,130,246,.15)']; ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0; display: grid; place-items: center; font-weight: 700; font-size: 0.8rem; color: <?= $badge['color'] ?>; background: <?= $badge['bg'] ?>;">
                                        <?= esc(strtoupper(mb_substr(preg_replace('/\s+/', '', $u['name']), 0, 1))) ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold" style="font-size: 0.85rem; color: var(--text-heading);">
                                            <?= esc($u['name']) ?>
                                            <?php if ((int) $u['id'] === $selfId): ?>
                                                <span class="badge bg-secondary" style="font-size: 0.6rem;">YOU</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mono text-muted" style="font-size: 0.72rem;"><?= esc($u['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge" style="color: <?= $badge['color'] ?>; background: <?= $badge['bg'] ?>; border: 1px solid transparent;">
                                    <?= esc($badge['label']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="small" style="font-size: 0.8rem; color: var(--text-body);"><?= esc($u['office_name'] ?? '—') ?></div>
                                <div class="text-muted" style="font-size: 0.72rem;"><?= esc($u['designation'] ?? 'No designation on file') ?></div>
                            </td>
                            <td>
                                <?php if (!empty($u['totp_enabled'])): ?>
                                    <span class="status-badge status-active"><i class="fa-solid fa-lock me-1"></i>Enrolled</span>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size: 0.72rem;">Not enrolled</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge <?= $u['status'] === 'active' ? 'status-active' : 'status-out_of_service' ?>">
                                    <?= $u['status'] === 'active' ? 'Active' : 'Deactivated' ?>
                                </span>
                            </td>
                            <td>
                                <span class="mono text-muted" style="font-size: 0.72rem;"><?= $u['created_at'] ? date('M d, Y', strtotime($u['created_at'])) : '—' ?></span>
                            </td>
                            <td class="text-end table-action-cell">
                                <div class="table-actions">
                                    <a href="<?= base_url('users/' . $u['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary" title="Edit account">
                                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                                    </a>
                                    <?php if ((int) $u['id'] !== $selfId): ?>
                                        <?php $confirmMsg = $u['status'] === 'active'
                                            ? 'Deactivate ' . $u['name'] . "\u{2019}" . 's account? Sign-in will be blocked.'
                                            : 'Reactivate ' . $u['name'] . "\u{2019}" . 's account?'; ?>
                                        <form method="POST" action="<?= base_url('users/' . $u['id'] . '/toggle') ?>" class="d-inline m-0"
                                              data-confirm="<?= esc($confirmMsg, 'attr') ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-icon-sm <?= $u['status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success' ?>" title="<?= $u['status'] === 'active' ? 'Deactivate account' : 'Reactivate account' ?>">
                                                <i class="fa-solid <?= $u['status'] === 'active' ? 'fa-ban' : 'fa-circle-check' ?>"></i>
                                            </button>
                                        </form>
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

<?php $this->section('scripts') ?>
<script>
    // CSP-friendly confirmation for account status toggles
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm(form.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });
</script>
<?php $this->endSection() ?>
<?= $this->endSection() ?>
