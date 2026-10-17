<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Breadcrumb & Back -->
<div class="mb-3">
    <a href="<?= base_url('dispatch') ?>" class="text-decoration-none text-muted small">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Dispatch Queue
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <!-- Header Panel -->
        <div class="card-panel border shadow-sm mb-4">
            <div class="p-3 border-bottom bg-light d-flex align-items-center justify-content-between">
                <div>
                    <div class="mono" style="font-size: 0.68rem; color: #1e3a8a; text-transform: uppercase; letter-spacing: 0.08em;">
                        Heuristic Allocation Engine &bull; SDD §4.2
                    </div>
                    <h4 class="mb-0 fw-bold text-dark fs-5">Dispatch Assignment: <?= esc($request['request_number']) ?></h4>
                    <div class="text-muted small">Destination: <strong><?= esc($request['destination']) ?></strong></div>
                </div>
                <span class="badge bg-success text-white small px-3 py-2">
                    <i class="fa-solid fa-stamp me-1"></i> Authorized by Admin Division Head
                </span>
            </div>

            <!-- Request Quick Overview -->
            <div class="p-3 bg-white border-bottom">
                <div class="row g-2 small">
                    <div class="col-md-3">
                        <span class="text-muted d-block">Originating Division:</span>
                        <strong><?= esc($request['office_name'] ?? 'PIA Central Office') ?></strong>
                    </div>
                    <div class="col-md-3">
                        <span class="text-muted d-block">Departure Schedule:</span>
                        <strong class="mono"><?= date('M d, Y h:i A', strtotime($request['departure_time'])) ?></strong>
                    </div>
                    <div class="col-md-3">
                        <span class="text-muted d-block">Passenger Manifest:</span>
                        <strong><?= esc($request['passenger_count']) ?> official passengers</strong>
                    </div>
                    <div class="col-md-3">
                        <span class="text-muted d-block">Preferred Type:</span>
                        <strong class="text-uppercase"><?= esc($request['requested_vehicle_type'] ?: 'Any Unit') ?></strong>
                    </div>
                </div>
            </div>

            <!-- Dispatch Allocation Form -->
            <form action="<?= base_url('dispatch/assign/' . $request['id']) ?>" method="POST" class="p-4" id="dispatchForm">
                <?= !is_cli() ? csrf_field() : '' ?>

                <!-- Merged / Carpool Consolidation Panel -->
                <div class="mb-4 p-3 bg-light rounded border">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 28px; height: 28px; font-size: 0.8rem;">
                                <i class="fa-solid fa-code-merge"></i>
                            </span>
                            <div>
                                <h6 class="mb-0 fw-bold text-dark small text-uppercase">Consolidate with Other Approved Requests (Shared Corridor / Carpool)</h6>
                                <div class="text-muted" style="font-size: 0.72rem;">Merge multiple official requests heading in the same direction into a single vehicle and certified driver to optimize government resources.</div>
                            </div>
                        </div>
                        <span class="badge bg-secondary rounded-pill small"><?= count($mergeCandidates) ?> Matching Request(s)</span>
                    </div>

                    <?php if (empty($mergeCandidates)): ?>
                        <div class="p-3 text-muted small bg-white rounded border">
                            <i class="fa-solid fa-circle-info me-1 text-secondary"></i> No other approved requests currently overlap with this travel corridor or schedule window. This trip will be dispatched independently.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive bg-white rounded border mb-2">
                            <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.8rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 38px;" class="text-center">Merge</th>
                                        <th>VRS Control No</th>
                                        <th>Division / Office</th>
                                        <th>Destination</th>
                                        <th>Schedule</th>
                                        <th>Passengers</th>
                                        <th>Corridor Match</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mergeCandidates as $mc): ?>
                                        <?php $isChecked = in_array((int)$mc['id'], $preselectedMergeIds ?? [], true); ?>
                                        <tr class="<?= $isChecked ? 'table-primary bg-opacity-10' : '' ?>">
                                            <td class="text-center">
                                                <input class="form-check-input secondary-request-cb" type="checkbox" name="secondary_requests[]" value="<?= $mc['id'] ?>" data-pax="<?= (int)$mc['passenger_count'] ?>" <?= $isChecked ? 'checked' : '' ?> onchange="calculatePassengerCapacity()">
                                            </td>
                                            <td class="mono fw-bold text-dark"><?= esc($mc['request_number']) ?></td>
                                            <td><?= esc($mc['office_name'] ?? 'Central Office') ?></td>
                                            <td class="text-truncate" style="max-width: 180px;" title="<?= esc($mc['destination']) ?>"><?= esc($mc['destination']) ?></td>
                                            <td class="mono" style="font-size: 0.74rem;"><?= date('M d, H:i', strtotime($mc['departure_time'])) ?></td>
                                            <td><span class="badge bg-light text-dark border"><?= esc($mc['passenger_count']) ?> pax</span></td>
                                            <td>
                                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle" style="font-size: 0.65rem;">
                                                    <?= esc($mc['destination_match'] ?? 'Compatible Window') ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <!-- Capacity & Passenger Counter Bar -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between p-2 px-3 bg-white rounded border small">
                        <div class="d-flex align-items-center gap-3">
                            <div>
                                <span class="text-muted">Primary Pax:</span>
                                <strong><?= esc($request['passenger_count']) ?></strong>
                            </div>
                            <div>
                                <span class="text-muted">+ Merged Pax:</span>
                                <strong id="mergedPaxText">0</strong>
                            </div>
                            <div class="border-start ps-3">
                                <span class="text-muted">Total Passengers:</span>
                                <strong id="totalPaxText" class="fs-6 text-primary"><?= esc($request['passenger_count']) ?></strong>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted">Assigned Vehicle Seating Capacity:</span>
                            <strong id="vehicleCapacityBadge" class="badge bg-dark fs-6">--</strong>
                            <div id="capacityWarning" class="badge bg-danger small d-none">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i> Exceeds Seating Capacity!
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- 1. Vehicle Selection (Ranked by Heuristic Score) -->
                    <div class="col-md-6">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="fw-bold text-dark small text-uppercase">
                                <i class="fa-solid fa-truck text-primary me-1"></i> Select Vehicle Asset <span class="text-danger">*</span>
                            </label>
                            <span class="badge bg-light text-muted border small" style="font-size: 0.65rem;">
                                Ranked by PMS &amp; Capacity
                            </span>
                        </div>

                        <?php if (empty($recommendations['recommended_vehicles'])): ?>
                            <div class="alert alert-danger p-3 small">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i> No vehicles currently available or all units are locked for PMS.
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($recommendations['recommended_vehicles'] as $idx => $veh): ?>
                                    <?php $vehCap = (int)($veh['seating_capacity'] ?? 7); ?>
                                    <label class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-3 border rounded-2 mb-2">
                                        <div class="d-flex align-items-start gap-3">
                                            <input class="form-check-input vehicle-radio mt-1" type="radio" name="vehicle_id" value="<?= $veh['id'] ?>" data-capacity="<?= $vehCap ?>" data-plate="<?= esc($veh['plate_number']) ?>" <?= $idx === 0 ? 'checked' : '' ?> required onchange="calculatePassengerCapacity()">
                                            <div>
                                                <div class="mono fw-bold text-dark fs-6"><?= esc($veh['plate_number']) ?></div>
                                                <div class="text-muted small"><?= esc($veh['make'] . ' ' . $veh['model']) ?> (<?= esc($veh['year']) ?>)</div>
                                                <div class="d-flex flex-wrap gap-2 mt-1">
                                                    <span class="badge bg-light text-dark border" style="font-size: 0.65rem;">
                                                        <i class="fa-solid fa-chair me-1 text-muted"></i><?= $vehCap ?> Seats
                                                    </span>
                                                    <span class="badge bg-light text-dark border" style="font-size: 0.65rem;">
                                                        <?= number_format((float)$veh['odometer_km'], 1) ?> km
                                                    </span>
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle" style="font-size: 0.65rem;">
                                                        PMS Safe: <?= number_format($veh['km_to_pms'], 0) ?> km
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-primary rounded-pill small" title="Heuristic Match Index">
                                                Score: <?= $veh['calculated_score'] ?>%
                                            </span>
                                            <div class="small text-muted" style="font-size: 0.68rem; margin-top: 4px;">
                                                Fuel: <?= $veh['current_fuel_level'] ?>%
                                            </div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- 2. Driver Selection (Ranked by Heuristic Score & Duty Status) -->
                    <div class="col-md-6">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="fw-bold text-dark small text-uppercase">
                                <i class="fa-solid fa-user-shield text-primary me-1"></i> Select Certified Driver <span class="text-danger">*</span>
                            </label>
                            <span class="badge bg-light text-muted border small" style="font-size: 0.65rem;">
                                Ranked by Rest &amp; Duty Status
                            </span>
                        </div>

                        <?php if (empty($recommendations['recommended_drivers'])): ?>
                            <div class="alert alert-danger p-3 small">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i> No certified drivers currently available or all drivers are on active assignment.
                            </div>
                        <?php else: ?>
                            <?php $hasSelectedAvailableDriver = false; ?>
                            <div class="list-group">
                                <?php foreach ($recommendations['recommended_drivers'] as $drv): ?>
                                    <?php
                                        $isAvailable = (bool)($drv['is_available'] ?? true);
                                        $shouldCheck = false;
                                        if ($isAvailable && !$hasSelectedAvailableDriver) {
                                            $shouldCheck = true;
                                            $hasSelectedAvailableDriver = true;
                                        }
                                    ?>
                                    <label class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-3 border rounded-2 mb-2 <?= !$isAvailable ? 'opacity-50 bg-light' : '' ?>">
                                        <div class="d-flex align-items-start gap-3">
                                            <input class="form-check-input mt-1" type="radio" name="driver_id" value="<?= $drv['id'] ?>" <?= $shouldCheck ? 'checked' : '' ?> <?= !$isAvailable ? 'disabled' : '' ?> required>
                                            <div>
                                                <div class="fw-bold text-dark small">
                                                    <?= esc($drv['first_name'] . ' ' . $drv['last_name']) ?>
                                                    <?php if (!$isAvailable): ?>
                                                        <span class="badge bg-danger text-white ms-1" style="font-size: 0.65rem;">
                                                            <i class="fa-solid fa-ban me-1"></i> Unavailable
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle ms-1" style="font-size: 0.65rem;">
                                                            <i class="fa-solid fa-circle-check me-1"></i> Available
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="mono text-muted" style="font-size: 0.72rem;"><?= esc($drv['driver_code']) ?> &bull; <?= esc($drv['license_number']) ?></div>
                                                <?php if (!$isAvailable && !empty($drv['conflict_reason'])): ?>
                                                    <div class="text-danger small mt-1" style="font-size: 0.7rem;">
                                                        <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= esc($drv['conflict_reason']) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="d-flex gap-2 mt-1">
                                                    <span class="badge bg-light text-muted border" style="font-size: 0.65rem;">
                                                        <?= esc($drv['license_type']) ?>
                                                    </span>
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle" style="font-size: 0.65rem;">
                                                        Safety: <?= $drv['safety_score'] ?>%
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-primary rounded-pill small" title="Heuristic Match Index">
                                                Score: <?= $drv['calculated_score'] ?>%
                                            </span>
                                            <div class="small text-muted" style="font-size: 0.68rem; margin-top: 4px;">
                                                Trips: <?= $drv['total_trips'] ?>
                                            </div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Action Bar & Security Cryptographic Token Confirmation -->
                <div class="pt-4 mt-3 border-top d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="small text-muted d-flex align-items-center gap-2">
                        <i class="fa-solid fa-qrcode text-primary fa-lg"></i>
                        <span>Confirming dispatch generates the official e-DTT (ADMIN-F-001 rev1) and HMAC-SHA256 QR security code.</span>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?= base_url('dispatch') ?>" class="btn-corp btn-corp-secondary text-decoration-none">
                            Cancel
                        </a>
                        <button type="submit" id="finalizeDispatchBtn" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm" <?= !$recommendations['has_available_match'] ? 'disabled' : '' ?>>
                            <i class="fa-solid fa-paper-plane me-1"></i> Finalize Dispatch &amp; Generate e-DTT
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const primaryPax = <?= (int)$request['passenger_count'] ?>;

function calculatePassengerCapacity() {
    let mergedPax = 0;
    document.querySelectorAll('.secondary-request-cb:checked').forEach(cb => {
        mergedPax += parseInt(cb.getAttribute('data-pax') || 0, 10);
    });

    const totalPax = primaryPax + mergedPax;
    const mergedPaxText = document.getElementById('mergedPaxText');
    const totalPaxText = document.getElementById('totalPaxText');
    if (mergedPaxText) mergedPaxText.innerText = mergedPax;
    if (totalPaxText) totalPaxText.innerText = totalPax;

    const selectedVeh = document.querySelector('.vehicle-radio:checked');
    const submitBtn = document.getElementById('finalizeDispatchBtn');
    const warning = document.getElementById('capacityWarning');
    const capBadge = document.getElementById('vehicleCapacityBadge');

    if (selectedVeh) {
        const cap = parseInt(selectedVeh.getAttribute('data-capacity') || 7, 10);
        if (capBadge) capBadge.innerText = cap + ' Seats';

        if (totalPax > cap) {
            if (warning) warning.classList.remove('d-none');
            if (totalPaxText) {
                totalPaxText.classList.add('text-danger');
                totalPaxText.classList.remove('text-primary');
            }
            if (submitBtn) submitBtn.disabled = true;
        } else {
            if (warning) warning.classList.add('d-none');
            if (totalPaxText) {
                totalPaxText.classList.remove('text-danger');
                totalPaxText.classList.add('text-primary');
            }
            if (submitBtn) submitBtn.disabled = false;
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    calculatePassengerCapacity();
});
</script>
<?= $this->endSection() ?>
