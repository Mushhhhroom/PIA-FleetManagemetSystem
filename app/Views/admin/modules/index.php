<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<style>
.module-row {
    transition: background-color 0.15s ease;
}
.module-row:hover {
    background-color: #fbfdff;
}
.form-check-input:checked {
    background-color: #10b981;
    border-color: #10b981;
}
.role-pill-btn {
    font-size: 0.7rem;
    padding: 3px 8px;
    border-radius: 999px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.role-pill-btn.role-allowed {
    background: #f0fdf4;
    border-color: #86efac;
    color: #166534;
}
.role-pill-btn.role-blocked {
    background: #fef2f2;
    border-color: #fca5a5;
    color: #991b1b;
    text-decoration: line-through;
}
.role-pill-btn:hover {
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
.switch-status-label {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
</style>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            <i class="fa-solid fa-shield-halved text-primary me-1"></i> Super Admin Enterprise Governance
        </div>
        <h1 class="page-title mb-1">System Module &amp; Menu Controls</h1>
        <p class="text-muted small mb-0">
            Activate or deactivate system functions and menu items globally or per-role to contain bugs and resolve issues with zero downtime.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <form action="<?= base_url('admin/modules/reset') ?>" method="POST" onsubmit="return confirm('Restore all modules and roles to default Active status?');" class="m-0">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-secondary btn-sm" title="Enable all modules and reset role blocks">
                <i class="fa-solid fa-rotate-left me-1"></i> Reset All to Active
            </button>
        </form>
        <a href="<?= base_url('users') ?>" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-users-gear me-1"></i> User Accounts
        </a>
    </div>
</div>

<!-- Flash Alerts -->
<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success border-success-subtle p-3 mb-4 rounded-3 d-flex align-items-center gap-2">
        <i class="fa-solid fa-circle-check text-success fs-5"></i>
        <div class="small fw-semibold"><?= esc(session()->getFlashdata('success')) ?></div>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger border-danger-subtle p-3 mb-4 rounded-3 d-flex align-items-center gap-2">
        <i class="fa-solid fa-circle-exclamation text-danger fs-5"></i>
        <div class="small fw-semibold"><?= esc(session()->getFlashdata('error')) ?></div>
    </div>
<?php endif; ?>

<!-- Summary Metrics Bar -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="kpi-tile">
            <div class="kpi-label">Total System Modules</div>
            <div class="kpi-value text-dark mono"><?= $totalModules ?></div>
            <div class="kpi-sub">Across 5 process domains</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="kpi-tile">
            <div class="kpi-label">Online &amp; Active</div>
            <div class="kpi-value text-success mono" id="kpiActiveCount"><?= $activeCount ?></div>
            <div class="kpi-sub">Available to assigned roles</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="kpi-tile">
            <div class="kpi-label">In Maintenance / Offline</div>
            <div class="kpi-value <?= $offlineCount > 0 ? 'text-danger' : 'text-muted' ?> mono" id="kpiOfflineCount"><?= $offlineCount ?></div>
            <div class="kpi-sub">Blocked at filter &amp; hidden</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="kpi-tile">
            <div class="kpi-label">Role-Specific Restrictions</div>
            <div class="kpi-value text-primary mono"><?= $roleRestrictedCount ?></div>
            <div class="kpi-sub">Targeted role access filters</div>
        </div>
    </div>
</div>

<!-- Search & Fast Filter Filter -->
<div class="card-panel mb-4 p-3">
    <div class="row g-2 align-items-center">
        <div class="col-md-6">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" id="moduleSearchInput" class="form-control border-start-0" placeholder="Filter modules by name, route, or category..." onkeyup="filterModules()">
            </div>
        </div>
        <div class="col-md-6 text-md-end">
            <span class="text-muted small me-2"><i class="fa-solid fa-circle-info text-info me-1"></i> Click any role pill to quickly toggle role access:</span>
            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;">Active</span>
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle text-decoration-line-through" style="font-size: 0.65rem;">Blocked</span>
        </div>
    </div>
</div>

<!-- Modules by Category -->
<?php foreach ($categories as $categoryName => $modules): ?>
    <div class="card-panel mb-4 module-category-block">
        <div class="card-panel-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-folder-tree text-primary"></i>
                <h6 class="mb-0 fw-bold"><?= esc($categoryName) ?></h6>
                <span class="badge bg-light text-secondary border small ms-1"><?= count($modules) ?> Module(s)</span>
            </div>
            <span class="text-muted small" style="font-size: 0.75rem;">Super Admin Real-Time Governance</span>
        </div>
        <div class="table-responsive">
            <table class="table-minimal">
                <thead>
                    <tr>
                        <th style="width: 25%;">Module &amp; Route</th>
                        <th style="width: 20%;" class="text-center">Global State</th>
                        <th style="width: 40%;">Account / Role Access Matrix</th>
                        <th style="width: 15%; text-align: right;">Maintenance Notice</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($modules as $mod): ?>
                        <?php
                            $isEnabled = (int)$mod['is_enabled'] === 1;
                            $disabledRoles = $mod['disabled_roles_array'] ?? [];
                        ?>
                        <tr class="module-row" data-search="<?= strtolower(esc($mod['module_name'] . ' ' . $mod['module_key'] . ' ' . $mod['route_prefix'] . ' ' . $categoryName)) ?>">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded p-2 text-primary" style="background: rgba(37, 99, 235, 0.08); width: 34px; height: 34px; display: grid; place-items: center;">
                                        <i class="fa-solid <?= esc($mod['icon'] ?? 'fa-cube') ?>"></i>
                                    </div>
                                    <div>
                                        <strong class="text-dark d-block"><?= esc($mod['module_name']) ?></strong>
                                        <span class="mono text-muted small" style="font-size: 0.72rem;">/<?= esc($mod['route_prefix']) ?></span>
                                        <?php if (!empty($mod['description'])): ?>
                                            <div class="text-muted small text-truncate" style="max-width: 280px; font-size: 0.7rem;"><?= esc($mod['description']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="form-check form-switch d-inline-flex align-items-center gap-2">
                                    <input class="form-check-input module-toggle-switch" type="checkbox" role="switch"
                                           id="switch_<?= esc($mod['module_key']) ?>"
                                           data-key="<?= esc($mod['module_key']) ?>"
                                           <?= $isEnabled ? 'checked' : '' ?>
                                           onchange="toggleModuleState('<?= esc($mod['module_key']) ?>', this.checked)">
                                    <label class="form-check-label switch-status-label" for="switch_<?= esc($mod['module_key']) ?>" id="status_label_<?= esc($mod['module_key']) ?>" style="color: <?= $isEnabled ? '#10b981' : '#dc2626' ?>;">
                                        <?= $isEnabled ? 'ACTIVE' : 'OFFLINE' ?>
                                    </label>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1 align-items-center">
                                    <?php foreach ($systemRoles as $roleKey => $roleLabel): ?>
                                        <?php $isRoleAllowed = !in_array($roleKey, $disabledRoles, true); ?>
                                        <button type="button"
                                                class="role-pill-btn <?= $isRoleAllowed ? 'role-allowed' : 'role-blocked' ?>"
                                                id="role_btn_<?= esc($mod['module_key']) ?>_<?= esc($roleKey) ?>"
                                                title="<?= $isRoleAllowed ? 'Click to block ' . $roleLabel : 'Click to permit ' . $roleLabel ?>"
                                                onclick="toggleRoleAccess('<?= esc($mod['module_key']) ?>', '<?= esc($roleKey) ?>', <?= $isRoleAllowed ? 0 : 1 ?>)">
                                            <i class="fa-solid <?= $isRoleAllowed ? 'fa-check' : 'fa-ban' ?>" style="font-size: 0.65rem;"></i>
                                            <?= esc(explode(' ', $roleLabel)[0]) ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td style="text-align: right;">
                                <button type="button" class="btn btn-xs btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#reasonModal_<?= esc($mod['module_key']) ?>" title="Edit Maintenance Message">
                                    <i class="fa-solid fa-pen-to-square me-1"></i> Notice
                                </button>
                            </td>
                        </tr>

                        <!-- Reason Modal -->
                        <div class="modal fade" id="reasonModal_<?= esc($mod['module_key']) ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow">
                                    <form action="<?= base_url('admin/modules/update-reason') ?>" method="POST">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="module_key" value="<?= esc($mod['module_key']) ?>">
                                        <div class="modal-header border-bottom">
                                            <h6 class="modal-title fw-bold">Maintenance Notice &bull; <?= esc($mod['module_name']) ?></h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="text-muted small mb-3">
                                                This explanation will be displayed to users whenever they attempt to visit this feature while deactivated.
                                            </p>
                                            <label class="form-label fw-semibold small">User-Facing Explanation</label>
                                            <textarea name="maintenance_reason" class="form-control" rows="3" required><?= esc($mod['maintenance_reason'] ?? 'Temporarily offline for scheduled maintenance and bug resolution.') ?></textarea>
                                        </div>
                                        <div class="modal-footer border-top bg-light">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary btn-sm">Save Notice</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>

<!-- Toast Feedback Container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
    <div id="moduleToast" class="toast align-items-center text-white bg-dark border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2" id="moduleToastBody">
                <i class="fa-solid fa-circle-check text-success"></i>
                <span>Action executed.</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
    let csrfTokenName = '<?= csrf_token() ?>';
    let csrfHash = '<?= csrf_hash() ?>';

    function showToast(message, isSuccess = true) {
        const toastEl = document.getElementById('moduleToast');
        const bodyEl = document.getElementById('moduleToastBody');
        bodyEl.innerHTML = `<i class="fa-solid ${isSuccess ? 'fa-circle-check text-success' : 'fa-circle-exclamation text-danger'}"></i> <span>${message}</span>`;
        const toast = new bootstrap.Toast(toastEl, { delay: 2500 });
        toast.show();
    }

    function toggleModuleState(moduleKey, isChecked) {
        const statusLabel = document.getElementById('status_label_' + moduleKey);
        statusLabel.innerText = isChecked ? 'ACTIVE' : 'OFFLINE';
        statusLabel.style.color = isChecked ? '#10b981' : '#dc2626';

        const formData = new FormData();
        formData.append(csrfTokenName, csrfHash);
        formData.append('module_key', moduleKey);
        formData.append('is_enabled', isChecked ? 1 : 0);

        fetch('<?= base_url('admin/modules/toggle') ?>', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.csrf_hash) csrfHash = data.csrf_hash;
            if (data.status === 'success') {
                showToast(data.message, true);
                updateKpiCounters(isChecked ? 1 : -1);
            } else {
                showToast(data.message || 'Error toggling module', false);
            }
        })
        .catch(err => {
            showToast('Network error while updating module state.', false);
        });
    }

    const roleShortLabels = {
        'requestor': 'Staff',
        'dispatcher': 'Motorpool',
        'approver_admin': 'Admin',
        'driver': 'Driver',
        'guard': 'Gate',
        'auditor': 'COA',
        'maintenance': 'Mechanic'
    };

    function toggleRoleAccess(moduleKey, role, allowFlag) {
        const btn = document.getElementById(`role_btn_${moduleKey}_${role}`);
        const formData = new FormData();
        formData.append(csrfTokenName, csrfHash);
        formData.append('module_key', moduleKey);
        formData.append('role', role);
        formData.append('allowed', allowFlag);

        fetch('<?= base_url('admin/modules/toggle-role') ?>', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.csrf_hash) csrfHash = data.csrf_hash;
            if (data.status === 'success') {
                const nowAllowed = data.allowed;
                btn.className = `role-pill-btn ${nowAllowed ? 'role-allowed' : 'role-blocked'}`;
                const labelText = roleShortLabels[role] || (role.charAt(0).toUpperCase() + role.slice(1));
                btn.innerHTML = `<i class="fa-solid ${nowAllowed ? 'fa-check' : 'fa-ban'}" style="font-size: 0.65rem;"></i> ${labelText}`;
                btn.setAttribute('onclick', `toggleRoleAccess('${moduleKey}', '${role}', ${nowAllowed ? 0 : 1})`);
                btn.setAttribute('title', nowAllowed ? `Click to block ${role}` : `Click to permit ${role}`);
                showToast(data.message, true);
            } else {
                showToast(data.message || 'Error updating role access', false);
            }
        })
        .catch(err => {
            showToast('Network error updating role access.', false);
        });
    }

    function updateKpiCounters(diff) {
        const actEl = document.getElementById('kpiActiveCount');
        const offEl = document.getElementById('kpiOfflineCount');
        if (actEl && offEl) {
            let act = parseInt(actEl.innerText) || 0;
            let off = parseInt(offEl.innerText) || 0;
            actEl.innerText = Math.max(0, act + diff);
            offEl.innerText = Math.max(0, off - diff);
        }
    }

    function filterModules() {
        const query = document.getElementById('moduleSearchInput').value.toLowerCase();
        const rows = document.querySelectorAll('.module-row');
        rows.forEach(r => {
            const text = r.getAttribute('data-search') || '';
            r.style.display = text.includes(query) ? '' : 'none';
        });
    }
</script>
<?= $this->endSection() ?>
