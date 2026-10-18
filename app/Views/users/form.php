<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $editing    = !empty($user);
    $selfId     = (int) session()->get('user_id');
    $isSelf     = $editing && (int) $user['id'] === $selfId;
    $roleBadges = [
        'admin'          => ['Super Admin / Director', '#3b82f6'],
        'approver_admin' => ['Tier 2 Approver (Admin Head)', '#06b6d4'],
        'dispatcher'     => ['Motorpool Dispatcher (Tier 1 Approver & Asset Allocation)', '#10b981'],
        'maintenance'    => ['Safety & PMS Technician', '#ea580c'],
        'driver'         => ['Official Driver', '#0284c7'],
        'guard'          => ['Gate Security Officer', '#64748b'],
        'auditor'        => ['COA Resident Auditor', '#d97706'],
        'requestor'      => ['Staff / Requestor', '#8b5cf6'],
    ];
?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            Administration &bull; Super Admin Exclusive
        </div>
        <h1 class="page-title mb-1"><?= esc($title) ?></h1>
        <p class="text-muted small mb-0">
            <?= $editing
                ? 'Update the account profile, role assignment, or issue a one-time credential reset.'
                : 'Create a sign-in identity and assign the appropriate RBAC role profile for the new user.' ?>
        </p>
    </div>
    <a href="<?= base_url('users') ?>" class="btn btn-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i> Return to Directory
    </a>
</div>

<div class="card-panel">
    <div class="card-panel-body" style="padding: 28px 32px;">
        <form action="<?= $editing ? base_url('users/' . $user['id']) : base_url('users') ?>" method="POST" autocomplete="off">
            <?= csrf_field() ?>

            <!-- 01 · Identity -->
            <div class="mb-4">
                <div class="mono" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 12px; font-weight: 600;">
                    01 &bull; Identity
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required maxlength="100" placeholder="Juan Dela Cruz" value="<?= old('name', $user['name'] ?? '') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">E-mail (Sign-in Identifier) <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required maxlength="150" placeholder="juan.delacruz@pia.gov.ph" value="<?= old('email', $user['email'] ?? '') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                        <div class="form-text" style="font-size: 0.72rem;">Must be unique across the system.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Designation / Position</label>
                        <input type="text" name="designation" class="form-control" maxlength="100" placeholder="Senior Information Officer" value="<?= old('designation', $user['designation'] ?? '') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Mobile Phone</label>
                        <input type="text" name="phone" class="form-control mono" maxlength="30" placeholder="+63 917 000 0000" value="<?= old('phone', $user['phone'] ?? '') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                </div>
            </div>

            <hr style="border-color: var(--border-subtle); margin: 24px 0;">

            <!-- 02 · Role & Assignment -->
            <div class="mb-4">
                <div class="mono" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 12px; font-weight: 600;">
                    02 &bull; Role &amp; Assignment
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Account Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                            <option value="" disabled <?= empty($user['role']) ? 'selected' : '' ?>>-- Select role profile --</option>
                            <?php foreach ($roles as $value => $label): ?>
                                <option value="<?= esc($value) ?>" <?= old('role', $user['role'] ?? '') === $value ? 'selected' : '' ?>>
                                    <?= esc($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text" style="font-size: 0.72rem;">
                            The role determines which modules the user can access (RBAC). Super Admin is the only role that can manage accounts.
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Assigned Office / Division</label>
                        <select name="office_id" class="form-select" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                            <option value="">— No office assigned —</option>
                            <?php foreach ($offices as $off): ?>
                                <option value="<?= (int) $off['id'] ?>" <?= (int) old('office_id', $user['office_id'] ?? 0) === (int) $off['id'] ? 'selected' : '' ?>>
                                    <?= esc($off['office_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label d-block" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Account Status</label>
                        <div class="d-flex gap-4 mt-1">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" id="statusActive" value="active"
                                       <?= old('status', $user['status'] ?? 'active') === 'active' ? 'checked' : '' ?> <?= $isSelf ? 'disabled' : '' ?>>
                                <label class="form-check-label small" for="statusActive"><i class="fa-solid fa-circle-check text-success me-1"></i> Active — can sign in</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" id="statusInactive" value="inactive"
                                       <?= old('status', $user['status'] ?? 'active') === 'inactive' ? 'checked' : '' ?> <?= $isSelf ? 'disabled' : '' ?>>
                                <label class="form-check-label small" for="statusInactive"><i class="fa-solid fa-ban text-danger me-1"></i> Deactivated — sign-in blocked</label>
                            </div>
                        </div>
                        <?php if ($isSelf): ?>
                            <div class="form-text text-muted" style="font-size: 0.72rem;"><i class="fa-solid fa-circle-info me-1"></i> Your own account must remain active — another Super Admin may change it.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <hr style="border-color: var(--border-subtle); margin: 24px 0;">

            <!-- 03 · Credentials -->
            <div class="mb-2">
                <div class="mono" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 12px; font-weight: 600;">
                    03 &bull; Credentials
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">
                            <?= $editing ? 'Reset Password (optional)' : 'Initial Password' ?> <?= $editing ? '' : '<span class="text-danger">*</span>' ?>
                        </label>
                        <input type="password" name="password" class="form-control" <?= $editing ? '' : 'required' ?> minlength="8" autocomplete="new-password"
                               placeholder="<?= $editing ? 'Leave blank to keep the current password' : 'Minimum 8 characters' ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                        <div class="form-text" style="font-size: 0.72rem;">
                            <?= $editing
                                ? "Setting a new password clears the user's two-factor enrollment — they must re-enroll MFA."
                                : 'Stored as an Argon2id (or strongest available) hash — plaintext is never saved.' ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security & Audit Footer Note -->
            <div class="d-flex align-items-center gap-2 p-3 mt-3 mb-3 rounded-2" style="background: #f8fafc; border: 1px solid var(--border-subtle);">
                <i class="fa-solid fa-shield-halved text-success"></i>
                <span class="small text-muted">Every provisioning, update, and status change made on this screen is recorded in the immutable system audit trail with your administrator identity and IP address (NFR-3).</span>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="<?= base_url('users') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid <?= $editing ? 'fa-floppy-disk' : 'fa-user-plus' ?> me-1"></i>
                    <?= $editing ? 'Save Account Changes' : 'Provision Account' ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
