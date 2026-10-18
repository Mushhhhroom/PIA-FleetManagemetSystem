<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            Freight Operations &bull; Dispatch Manifest
        </div>
        <h1 class="page-title mb-1"><?= esc($title) ?></h1>
        <p class="text-muted small mb-0">Assign transport asset, allocate qualified operator, and calibrate transit route coordinates.</p>
    </div>
    <a href="<?= base_url('trips') ?>" class="btn btn-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i> Return to Ledger
    </a>
</div>

<div class="card-panel">
    <div class="card-panel-body" style="padding: 28px 32px;">
        <form action="<?= base_url('trips') ?>" method="POST">
            <?= csrf_field() ?>

            <!-- Section 1: Asset & Operator Allocation -->
            <div class="mb-4">
                <div class="mono" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 12px; font-weight: 600;">
                    01 &bull; Asset & Driver Allocation
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Transport Asset</label>
                        <select name="vehicle_id" class="form-select" required style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                            <option value="">-- Choose Commercial Unit --</option>
                            <?php foreach ($vehicles as $v): ?>
                                <option value="<?= $v['id'] ?>" <?= old('vehicle_id') == $v['id'] ? 'selected' : '' ?>>
                                    <?= esc($v['vehicle_code']) ?> &bull; <?= esc($v['make']) ?> <?= esc($v['model']) ?> (Plate: <?= esc($v['plate_number']) ?>) [<?= ucfirst(str_replace('_', ' ', $v['status'])) ?>]
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Designated Driver Operator</label>
                        <select name="driver_id" class="form-select" required style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                            <option value="">-- Choose Operator --</option>
                            <?php foreach ($drivers as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= old('driver_id') == $d['id'] ? 'selected' : '' ?>>
                                    <?= esc($d['first_name'] . ' ' . $d['last_name']) ?> (<?= esc($d['driver_code']) ?>) &bull; <?= esc($d['license_type']) ?> [Safety: <?= $d['safety_score'] ?>%]
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <hr style="border-color: var(--border-subtle); margin: 24px 0;">

            <!-- Section 2: Route Coordinates & Corridors -->
            <div class="mb-4">
                <div class="mono" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 12px; font-weight: 600;">
                    02 &bull; Transit Corridor Coordinates
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Origin Hub / Dispatch Facility</label>
                        <input type="text" name="origin_address" id="origin_address" class="form-control mb-2" required placeholder="e.g. Manila North Harbor, Port Area, Manila" value="<?= old('origin_address', 'Manila North Harbor, Port Area, Manila') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="number" step="0.000001" name="origin_lat" id="origin_lat" class="form-control form-control-sm mono" placeholder="Origin Lat" value="<?= old('origin_lat', '14.583333') ?>" style="font-size: 0.78rem;">
                            </div>
                            <div class="col-6">
                                <input type="number" step="0.000001" name="origin_lng" id="origin_lng" class="form-control form-control-sm mono" placeholder="Origin Lng" value="<?= old('origin_lng', '120.966667') ?>" style="font-size: 0.78rem;">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Destination Terminal / Delivery Site</label>
                        <input type="text" name="destination_address" id="destination_address" class="form-control mb-2" required placeholder="e.g. Clark Global City Distribution Hub, Pampanga" value="<?= old('destination_address', 'Clark Global City Distribution Hub, Pampanga') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="number" step="0.000001" name="destination_lat" id="destination_lat" class="form-control form-control-sm mono" placeholder="Dest Lat" value="<?= old('destination_lat', '15.185500') ?>" style="font-size: 0.78rem;">
                            </div>
                            <div class="col-6">
                                <input type="number" step="0.000001" name="destination_lng" id="destination_lng" class="form-control form-control-sm mono" placeholder="Dest Lng" value="<?= old('destination_lng', '120.540600') ?>" style="font-size: 0.78rem;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr style="border-color: var(--border-subtle); margin: 24px 0;">

            <!-- Section 3: Freight & Dispatch Schedule -->
            <div class="mb-4">
                <div class="mono" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 12px; font-weight: 600;">
                    03 &bull; Freight Commodity & Timing
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Cargo Description</label>
                        <input type="text" name="cargo_type" class="form-control" required placeholder="e.g. Commercial Electronics, Dry Goods" value="<?= old('cargo_type', 'General Merchandise') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Payload Weight (kg)</label>
                        <input type="number" step="0.01" name="cargo_weight_kg" class="form-control mono" required placeholder="Weight in kg" value="<?= old('cargo_weight_kg', 12500) ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Estimated Distance (km)</label>
                        <input type="number" step="0.1" name="distance_km" class="form-control mono" placeholder="Auto-calculated if blank" value="<?= old('distance_km', 95.0) ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Scheduled Departure</label>
                        <input type="datetime-local" name="scheduled_departure" class="form-control mono" required value="<?= old('scheduled_departure', date('Y-m-d\TH:i')) ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Estimated Arrival</label>
                        <input type="datetime-local" name="scheduled_arrival" class="form-control mono" value="<?= old('scheduled_arrival', date('Y-m-d\TH:i', strtotime('+4 hours'))) ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Dispatch Priority</label>
                        <select name="priority" class="form-select" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                            <option value="normal" <?= old('priority') === 'normal' ? 'selected' : '' ?>>Normal Freight</option>
                            <option value="urgent" <?= old('priority') === 'urgent' ? 'selected' : '' ?>>Urgent Priority</option>
                            <option value="critical" <?= old('priority') === 'critical' ? 'selected' : '' ?>>Critical Express</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Initial State</label>
                        <select name="status" class="form-select" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                            <option value="scheduled">Scheduled</option>
                            <option value="dispatched">Dispatched</option>
                            <option value="in_transit" selected>In Transit (Active Now)</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Handling & Gate Pass Instructions</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Seal numbers, temperature constraints, security clearances..." style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;"><?= old('notes') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="<?= base_url('trips') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-paper-plane me-1"></i> Publish & Dispatch Manifest
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
