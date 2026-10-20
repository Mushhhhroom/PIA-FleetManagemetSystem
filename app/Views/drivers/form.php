<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            Personnel &bull; Operator Profile
        </div>
        <h1 class="page-title mb-1"><?= esc($title) ?></h1>
        <p class="text-muted small mb-0">Record operator credentials, government licensing classification, and emergency contact details.</p>
    </div>
    <a href="<?= base_url('drivers') ?>" class="btn btn-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i> Return to Roster
    </a>
</div>

<div class="card-panel">
    <div class="card-panel-body" style="padding: 28px 32px;">
        <form action="<?= $driver ? base_url('drivers/' . $driver['id']) : base_url('drivers') ?>" method="POST">
            <?= csrf_field() ?>

            <!-- Section 1: Personal Details -->
            <div class="mb-4">
                <div class="mono" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 12px; font-weight: 600;">
                    01 &bull; Personal Information
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">First Name</label>
                        <input type="text" name="first_name" class="form-control" required placeholder="John" value="<?= old('first_name', $driver['first_name'] ?? '') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Last Name</label>
                        <input type="text" name="last_name" class="form-control" required placeholder="Doe" value="<?= old('last_name', $driver['last_name'] ?? '') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Government Email</label>
                        <input type="email" name="email" class="form-control" placeholder="driver@pia.gov.ph" value="<?= old('email', $driver['email'] ?? '') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Mobile Phone</label>
                        <input type="text" name="phone" class="form-control mono" required placeholder="+63 917 000 0000" value="<?= old('phone', $driver['phone'] ?? '') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Residential Address</label>
                        <input type="text" name="address" class="form-control" placeholder="Complete Street Address, City, Province" value="<?= old('address', $driver['address'] ?? '') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                </div>
            </div>

            <hr style="border-color: var(--border-subtle); margin: 24px 0;">

            <!-- Section 2: Licensing & Certification -->
            <div class="mb-4">
                <div class="mono" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 12px; font-weight: 600;">
                    02 &bull; Licensing & Legal Authorization
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Driver License Number</label>
                        <input type="text" name="license_number" class="form-control mono" required placeholder="N01-XX-XXXXXX" value="<?= old('license_number', $driver['license_number'] ?? '') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">License Classification</label>
                        <input type="text" name="license_type" class="form-control" required placeholder="e.g. Professional Driver (Restriction 1, 2, 3)" value="<?= old('license_type', $driver['license_type'] ?? 'Professional Driver (Restriction 1, 2, 3)') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">License Expiration Date</label>
                        <input type="date" name="license_expiry" class="form-control mono" required value="<?= old('license_expiry', $driver['license_expiry'] ?? date('Y-m-d', strtotime('+1 year'))) ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                </div>
            </div>

            <hr style="border-color: var(--border-subtle); margin: 24px 0;">

            <!-- Section 3: Operations & Safety Score -->
            <div class="mb-4">
                <div class="mono" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 12px; font-weight: 600;">
                    03 &bull; Duty Status & Performance Score
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Duty Status</label>
                        <select name="status" class="form-select" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                            <?php
                            $statuses = ['available' => 'Available (On Standby)', 'on_trip' => 'On Active Trip', 'off_duty' => 'Off Duty / Rest', 'suspended' => 'Suspended'];
                            $curStat = old('status', $driver['status'] ?? 'available');
                            foreach ($statuses as $k => $lbl): ?>
                                <option value="<?= $k ?>" <?= $curStat === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Safety Rating Score (%)</label>
                        <input type="number" step="0.1" min="0" max="100" name="safety_score" class="form-control mono" value="<?= old('safety_score', $driver['safety_score'] ?? 100) ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Emergency Contact (Name & Phone)</label>
                        <input type="text" name="emergency_contact" class="form-control" placeholder="Contact Name & Number" value="<?= old('emergency_contact', $driver['emergency_contact'] ?? '') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="<?= base_url('drivers') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Save Operator Profile
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
