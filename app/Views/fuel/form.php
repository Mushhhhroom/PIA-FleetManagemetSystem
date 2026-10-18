<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            Logistics &bull; Energy Intake
        </div>
        <h1 class="page-title mb-1"><?= esc($title) ?></h1>
        <p class="text-muted small mb-0">Record fuel receipts, liters dispensed, pump odometer readings, and vendor locations.</p>
    </div>
    <a href="<?= base_url('fuel') ?>" class="btn btn-secondary text-decoration-none">
        <i class="fa-solid fa-arrow-left me-1"></i> Return to Ledger
    </a>
</div>

<div class="card-panel">
    <div class="card-panel-body" style="padding: 28px 32px;">
        <form action="<?= base_url('fuel') ?>" method="POST">
            <?= csrf_field() ?>

            <!-- Section 1: Transaction Allocation -->
            <div class="mb-4">
                <div class="mono" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 12px; font-weight: 600;">
                    01 &bull; Asset & Driver Allocation
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Fleet Vehicle Asset</label>
                        <select name="vehicle_id" class="form-select" required style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                            <option value="">-- Choose Commercial Unit --</option>
                            <?php foreach ($vehicles as $v): ?>
                                <option value="<?= $v['id'] ?>" <?= old('vehicle_id') == $v['id'] ? 'selected' : '' ?>>
                                    <?= esc($v['vehicle_code']) ?> &bull; <?= esc($v['make'] . ' ' . $v['model']) ?> (<?= esc($v['plate_number']) ?>) [<?= number_format($v['odometer_km'], 1) ?> km]
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Operator at Pump</label>
                        <select name="driver_id" class="form-select" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                            <option value="">-- Choose Operator (Optional) --</option>
                            <?php foreach ($drivers as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= old('driver_id') == $d['id'] ? 'selected' : '' ?>>
                                    <?= esc($d['first_name'] . ' ' . $d['last_name']) ?> (<?= esc($d['driver_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Receipt / Invoice Ref #</label>
                        <input type="text" name="receipt_no" class="form-control mono" placeholder="e.g. INV-90412" value="<?= old('receipt_no') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                </div>
            </div>

            <hr style="border-color: var(--border-subtle); margin: 24px 0;">

            <!-- Section 2: Metrics & Accounting -->
            <div class="mb-4">
                <div class="mono" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 12px; font-weight: 600;">
                    02 &bull; Fuel Volume & Cost Details
                </div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Transaction Date</label>
                        <input type="date" name="fuel_date" class="form-control mono" required value="<?= old('fuel_date', date('Y-m-d')) ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Volume Dispensed (Liters)</label>
                        <input type="number" step="0.01" name="liters" id="liters" class="form-control mono" required placeholder="0.00" value="<?= old('liters') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Total Invoiced Amount (₱)</label>
                        <input type="number" step="0.01" name="total_cost" id="total_cost" class="form-control mono" required placeholder="0.00" value="<?= old('total_cost') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Odometer at Pump (km)</label>
                        <input type="number" step="0.1" name="odometer_km" class="form-control mono" placeholder="Current km" value="<?= old('odometer_km') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Fueling Station / Vendor Hub</label>
                        <input type="text" name="fuel_station" class="form-control" placeholder="e.g. Petron NLEX Marilao, Shell SLEX Mamplasan" value="<?= old('fuel_station') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Fleet Card / Billing Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Fleet card #, receipt remarks..." value="<?= old('notes') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="<?= base_url('fuel') ?>" class="btn btn-secondary text-decoration-none">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-receipt me-1"></i> Post Fuel Transaction
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
