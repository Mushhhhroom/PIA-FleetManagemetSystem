<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            Asset Management &bull; Registry Configuration
        </div>
        <h1 class="page-title mb-1"><?= esc($title) ?></h1>
        <p class="text-muted small mb-0">Vehicle identification specifications, powertrain calibration, and default operator assignment.</p>
    </div>
    <a href="<?= base_url('vehicles') ?>" class="btn btn-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i> Return to Registry
    </a>
</div>

<div class="card-panel">
    <div class="card-panel-body" style="padding: 28px 32px;">
        <form action="<?= $vehicle ? base_url('vehicles/' . $vehicle['id']) : base_url('vehicles') ?>" method="POST">
            <?= csrf_field() ?>

            <!-- Section 1: Identification -->
            <div class="mb-4">
                <div class="mono" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 12px; font-weight: 600;">
                    01 &bull; Vehicle Identification
                </div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Unit Code (Internal ID)</label>
                        <input type="text" name="vehicle_code" class="form-control mono" required placeholder="e.g. FLT-06" value="<?= old('vehicle_code', $vehicle['vehicle_code'] ?? '') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">License Plate Number</label>
                        <input type="text" name="plate_number" class="form-control mono" required placeholder="e.g. NHA-9921" value="<?= old('plate_number', $vehicle['plate_number'] ?? '') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">VIN (Chassis Number)</label>
                        <input type="text" name="vin" class="form-control mono" required placeholder="17-character VIN" value="<?= old('vin', $vehicle['vin'] ?? '') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                </div>
            </div>

            <hr style="border-color: var(--border-subtle); margin: 24px 0;">

            <!-- Section 2: Make, Model & Powertrain -->
            <div class="mb-4">
                <div class="mono" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 12px; font-weight: 600;">
                    02 &bull; Specifications & Powertrain
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Manufacturer / Make</label>
                        <input type="text" name="make" class="form-control" required placeholder="e.g. Freightliner, Isuzu, Volvo" value="<?= old('make', $vehicle['make'] ?? '') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Model</label>
                        <input type="text" name="model" class="form-control" required placeholder="e.g. Cascadia 126, Giga EXR" value="<?= old('model', $vehicle['model'] ?? '') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Model Year</label>
                        <input type="number" name="year" class="form-control mono" required min="1990" max="2035" value="<?= old('year', $vehicle['year'] ?? date('Y')) ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Chassis Category</label>
                        <select name="type" class="form-select" required style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                            <?php
                            $types = ['semi_truck' => 'Semi-Truck / Tractor', 'box_truck' => 'Box Truck (Medium)', 'delivery_van' => 'Commercial Van', 'pickup' => 'Light Utility', 'trailer' => 'Chassis Trailer', 'suv' => 'Fleet Utility'];
                            $curType = old('type', $vehicle['type'] ?? 'semi_truck');
                            foreach ($types as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $curType === $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Fuel / Energy Type</label>
                        <select name="fuel_type" class="form-select" required style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                            <?php
                            $fuels = ['diesel' => 'Diesel', 'gasoline' => 'Gasoline', 'electric' => 'Electric (EV)', 'hybrid' => 'Hybrid'];
                            $curFuel = old('fuel_type', $vehicle['fuel_type'] ?? 'diesel');
                            foreach ($fuels as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $curFuel === $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Max Payload Capacity (kg)</label>
                        <input type="number" step="0.01" name="max_payload_kg" class="form-control mono" value="<?= old('max_payload_kg', $vehicle['max_payload_kg'] ?? 5000) ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Tank / Battery Capacity</label>
                        <input type="number" step="0.01" name="fuel_capacity_liters" class="form-control mono" value="<?= old('fuel_capacity_liters', $vehicle['fuel_capacity_liters'] ?? 200) ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                </div>
            </div>

            <hr style="border-color: var(--border-subtle); margin: 24px 0;">

            <!-- Section 3: Status & Assignment -->
            <div class="mb-4">
                <div class="mono" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 12px; font-weight: 600;">
                    03 &bull; State, Odometer & Operator Assignment
                </div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Current Odometer (km)</label>
                        <input type="number" step="0.1" name="odometer_km" class="form-control mono" required value="<?= old('odometer_km', $vehicle['odometer_km'] ?? 0) ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Current Fuel Level (%)</label>
                        <input type="number" step="0.1" max="100" min="0" name="current_fuel_level" class="form-control mono" value="<?= old('current_fuel_level', $vehicle['current_fuel_level'] ?? 100) ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Operational State</label>
                        <select name="status" class="form-select" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                            <?php
                            $statuses = [
                                'active'             => 'Active / Ready',
                                'in_transit'         => 'In Transit',
                                'maintenance'        => 'In Maintenance',
                                'under_maintenance'  => 'Under Maintenance (Safety Lock)',
                                'disabled_breakdown' => 'Disabled / Breakdown',
                                'out_of_service'     => 'Out of Service',
                            ];
                            $curStat = old('status', $vehicle['status'] ?? 'active');
                            foreach ($statuses as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $curStat === $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Designated Driver</label>
                        <select name="current_driver_id" class="form-select" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                            <option value="">-- No Assigned Driver --</option>
                            <?php
                            $curDriver = old('current_driver_id', $vehicle['current_driver_id'] ?? '');
                            foreach ($drivers as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= (string)$curDriver === (string)$d['id'] ? 'selected' : '' ?>>
                                    <?= esc($d['first_name'] . ' ' . $d['last_name']) ?> (<?= esc($d['driver_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <hr style="border-color: var(--border-subtle); margin: 24px 0;">

            <!-- Section 4: FR-2.1 Fleet Segregation & FR-7.1 Regulatory Compliance -->
            <div class="mb-4">
                <div class="mono" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 12px; font-weight: 600;">
                    04 &bull; Fleet Segregation &amp; Regulatory Expiries (FR-2.1 / FR-7.1)
                </div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Fleet Category</label>
                        <select name="fleet_category" class="form-select" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                            <?php
                            $categories = ['pool' => 'Shared Pool Vehicle', 'dedicated' => 'Dedicated Executive Vehicle'];
                            $curCat = old('fleet_category', $vehicle['fleet_category'] ?? 'pool');
                            foreach ($categories as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $curCat === $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text" style="font-size: 0.7rem;">Dedicated units are reserved for a designated senior official (FR-2.1).</div>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">Assigned Senior Official (for Dedicated units)</label>
                        <input type="text" name="assigned_official" class="form-control" value="<?= old('assigned_official', $vehicle['assigned_official'] ?? '') ?>" placeholder="e.g. Regional Director Maria L. Santos" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">LTO Registration Expiry</label>
                        <input type="date" name="lto_registration_expiry" class="form-control mono" value="<?= old('lto_registration_expiry', $vehicle['lto_registration_expiry'] ?? '') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-heading);">GSIS Insurance Expiry</label>
                        <input type="date" name="gsis_insurance_expiry" class="form-control mono" value="<?= old('gsis_insurance_expiry', $vehicle['gsis_insurance_expiry'] ?? '') ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                        <div class="form-text" style="font-size: 0.7rem;">30-day automated alerts (FR-7.1).</div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="<?= base_url('vehicles') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Save Vehicle Record
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
