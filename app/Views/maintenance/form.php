<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            Technical Workshop &bull; New Maintenance Intake
        </div>
        <h1 class="page-title mb-1"><?= esc($title) ?></h1>
        <p class="text-muted small mb-0">Record preventative PMS cycles, mechanical repairs, or pre-trip safety defect work orders.</p>
    </div>
    <a href="<?= base_url('maintenance') ?>" class="btn btn-secondary text-decoration-none">
        <i class="fa-solid fa-arrow-left me-1"></i> Return to Ledger
    </a>
</div>

<div class="card-panel">
    <div class="card-panel-body" style="padding: 28px 32px;">
        <form action="<?= base_url('maintenance') ?>" method="POST">
            <?= csrf_field() ?>

            <!-- Section 1: Asset & Service Scope -->
            <div class="mb-4">
                <div class="mono" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 12px; font-weight: 600;">
                    01 &bull; Asset & Service Scope
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Fleet Vehicle Asset</label>
                        <select name="vehicle_id" class="form-select" required style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                            <option value="">-- Choose Service Vehicle --</option>
                            <?php foreach ($vehicles as $v): ?>
                                <option value="<?= $v['id'] ?>" <?= old('vehicle_id') == $v['id'] ? 'selected' : '' ?>>
                                    <?= esc($v['vehicle_code']) ?> &bull; <?= esc($v['make'] . ' ' . $v['model']) ?> (Plate: <?= esc($v['plate_number']) ?>) [<?= number_format($v['odometer_km'], 1) ?> km]
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Service Type / Scope Item</label>
                        <input type="text" name="service_type" class="form-control" required placeholder="e.g. Engine Oil Flush & Filters, Brake Caliper Overhaul, 50k PMS" value="<?= old('service_type') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                </div>
            </div>

            <hr style="border-color: var(--border-subtle); margin: 24px 0;">

            <!-- Section 2: Scheduling & Cost Allocation -->
            <div class="mb-4">
                <div class="mono" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 12px; font-weight: 600;">
                    02 &bull; Scheduling & Financial Estimates
                </div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Service Priority</label>
                        <select name="priority" class="form-select" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                            <option value="low">Low (Routine Check)</option>
                            <option value="medium" selected>Medium (Standard PMS)</option>
                            <option value="high">High (Urgent Repair)</option>
                            <option value="critical">Critical (Grounded / Safety Hazard)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Scheduled Date</label>
                        <input type="date" name="scheduled_date" class="form-control mono" required value="<?= old('scheduled_date', date('Y-m-d')) ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Odometer at Service (km)</label>
                        <input type="number" step="0.1" name="odometer_at_service" class="form-control mono" placeholder="Current km" value="<?= old('odometer_at_service', 0) ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Estimated Cost (₱)</label>
                        <input type="number" step="0.01" name="cost" class="form-control mono" placeholder="0.00" value="<?= old('cost', 0) ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Repair Facility / Service Center</label>
                        <input type="text" name="service_center" class="form-control" placeholder="e.g. In-House Fleet Shop, Isuzu QC Service" value="<?= old('service_center', 'In-House Fleet Shop') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Lead Technician</label>
                        <input type="text" name="technician_name" class="form-control" placeholder="Technician Name" value="<?= old('technician_name') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Initial Work Order State</label>
                        <select name="status" class="form-select" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                            <option value="scheduled">Scheduled (Pending Intake)</option>
                            <option value="in_progress">In Progress (Place Vehicle in Maintenance)</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Defect Diagnostics & Replacement Parts</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Driver reported symptoms, component breakdown, OEM replacement parts required..." style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;"><?= old('description') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="<?= base_url('maintenance') ?>" class="btn btn-secondary text-decoration-none">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-wrench me-1"></i> Issue Work Order
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
