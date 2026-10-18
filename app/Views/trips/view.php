<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            Freight Operations &bull; Waybill Management
        </div>
        <div class="d-flex align-items-center gap-3">
            <h1 class="page-title mb-0 mono" style="font-size: 1.45rem; color: #0f172a;"><?= esc($trip['trip_number']) ?></h1>
            <span class="status-badge status-<?= esc($trip['status']) ?>">
                <?= ucfirst(str_replace('_', ' ', esc($trip['status']))) ?>
            </span>
            <?php if ($trip['priority'] === 'urgent' || $trip['priority'] === 'critical'): ?>
                <span class="status-badge status-out_of_service" style="font-size: 0.68rem;">
                    <?= strtoupper($trip['priority']) ?> PRIORITY
                </span>
            <?php endif; ?>
        </div>
        <p class="text-muted small mb-0 mt-1">
            Departure: <span class="mono fw-medium text-dark"><?= esc(date('M d, Y H:i', strtotime($trip['scheduled_departure']))) ?></span> &bull; 
            Cargo: <span class="fw-medium text-dark"><?= esc($trip['cargo_type']) ?></span> (<?= number_format($trip['cargo_weight_kg'], 0) ?> kg)
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('trips') ?>" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> All Dispatches
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Map & Route Info (8 cols) -->
    <div class="col-lg-8">
        <!-- Live Route Map -->
        <div class="card-panel mb-4">
            <div class="card-panel-header d-flex justify-content-between align-items-center">
                <span class="card-panel-title">
                    <i class="fa-solid fa-route text-primary me-2"></i> Active Transit Corridor & Live Telemetry
                </span>
                <span class="mono" style="font-size: 0.75rem; color: var(--text-muted);">
                    Corridor Distance: <strong class="text-dark"><?= $trip['distance_km'] ?> km</strong>
                </span>
            </div>
            <div class="card-panel-body p-0">
                <div id="tripMap" style="height: 400px; width: 100%;"></div>
            </div>
        </div>

        <!-- Route Breakdown Card -->
        <div class="card-panel">
            <div class="card-panel-header">
                <span class="card-panel-title">Transit Corridor Milestones</span>
            </div>
            <div class="card-panel-body">
                <div class="row g-4">
                    <div class="col-md-6 border-end">
                        <div class="mono" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.06em; color: #166534; font-weight: 600;">
                            <i class="fa-solid fa-circle-dot me-1"></i> Origin Hub
                        </div>
                        <h6 class="fw-semibold mt-1 mb-1" style="font-size: 0.9rem; color: var(--text-heading);"><?= esc($trip['origin_address']) ?></h6>
                        <div class="mono text-muted" style="font-size: 0.72rem;">Lat: <?= $trip['origin_lat'] ?>, Lng: <?= $trip['origin_lng'] ?></div>
                        <div class="text-muted mt-2" style="font-size: 0.78rem;">
                            Scheduled: <span class="mono text-dark"><?= esc($trip['scheduled_departure']) ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mono" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.06em; color: #991b1b; font-weight: 600;">
                            <i class="fa-solid fa-location-dot me-1"></i> Destination Terminal
                        </div>
                        <h6 class="fw-semibold mt-1 mb-1" style="font-size: 0.9rem; color: var(--text-heading);"><?= esc($trip['destination_address']) ?></h6>
                        <div class="mono text-muted" style="font-size: 0.72rem;">Lat: <?= $trip['destination_lat'] ?>, Lng: <?= $trip['destination_lng'] ?></div>
                        <div class="text-muted mt-2" style="font-size: 0.78rem;">
                            Target Arrival: <span class="mono text-dark"><?= esc($trip['scheduled_arrival'] ?: 'Open Window') ?></span>
                        </div>
                    </div>
                </div>

                <?php if (!empty($trip['notes'])): ?>
                    <div class="mt-3 pt-3 border-top">
                        <div class="mono" style="font-size: 0.68rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Dispatcher Special Instructions:</div>
                        <p class="text-secondary small mb-0 mt-1"><?= nl2br(esc($trip['notes'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Status & Details (4 cols) -->
    <div class="col-lg-4">
        <!-- Status Control Box -->
        <div class="card-panel mb-4">
            <div class="card-panel-header">
                <span class="card-panel-title">Update Manifest Status</span>
            </div>
            <div class="card-panel-body">
                <form action="<?= base_url('trips/' . $trip['id'] . '/status') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" style="font-size: 0.78rem; font-weight: 600; color: var(--text-heading);">Lifecycle Stage</label>
                        <select name="status" class="form-select" id="tripStatusSelect" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                            <option value="scheduled" <?= $trip['status'] === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                            <option value="dispatched" <?= $trip['status'] === 'dispatched' ? 'selected' : '' ?>>Dispatched</option>
                            <option value="in_transit" <?= $trip['status'] === 'in_transit' ? 'selected' : '' ?>>In Transit (Active on Road)</option>
                            <option value="completed" <?= $trip['status'] === 'completed' ? 'selected' : '' ?>>Completed (Delivered)</option>
                            <option value="cancelled" <?= $trip['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>

                    <div class="mb-3" id="endOdoField" style="<?= $trip['status'] === 'completed' ? '' : 'display: none;' ?>">
                        <label class="form-label" style="font-size: 0.78rem; font-weight: 600; color: var(--text-heading);">Arrival Final Odometer (km)</label>
                        <input type="number" step="0.1" name="end_odometer" class="form-control mono" placeholder="Current km" value="<?= old('end_odometer', $trip['end_odometer'] ?? ($trip['start_odometer'] + $trip['distance_km'])) ?>" style="font-size: 0.85rem; border-color: var(--border-subtle); border-radius: 7px;">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-check me-1"></i> Apply Status Update
                    </button>
                </form>
            </div>
        </div>

        <!-- Transport Asset Details -->
        <div class="card-panel mb-4">
            <div class="card-panel-header">
                <span class="card-panel-title">Transport Vehicle</span>
            </div>
            <div class="card-panel-body">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="mono fw-bold" style="font-size: 0.95rem; color: var(--text-heading);"><?= esc($trip['vehicle_code']) ?></span>
                    <span class="mono text-muted" style="font-size: 0.75rem;"><?= esc($trip['plate_number']) ?></span>
                </div>
                <div class="text-muted small mb-3"><?= esc($trip['make'] . ' ' . $trip['model']) ?></div>
                
                <div class="d-flex justify-content-between py-1 border-bottom" style="font-size: 0.78rem;">
                    <span class="text-muted">Live Speed:</span>
                    <strong class="mono"><?= $trip['current_speed'] ?> km/h</strong>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom" style="font-size: 0.78rem;">
                    <span class="text-muted">Fuel Reservoir:</span>
                    <strong class="mono"><?= $trip['current_fuel_level'] ?>%</strong>
                </div>
                <div class="d-flex justify-content-between py-1" style="font-size: 0.78rem;">
                    <span class="text-muted">Departure Odometer:</span>
                    <strong class="mono"><?= number_format($trip['start_odometer'], 1) ?> km</strong>
                </div>

                <div class="mt-3">
                    <a href="<?= base_url('vehicles/' . $trip['vehicle_id']) ?>" class="btn btn-secondary w-100 text-center d-block">
                        Inspect Asset Profile &rarr;
                    </a>
                </div>
            </div>
        </div>

        <!-- Driver Details -->
        <div class="card-panel">
            <div class="card-panel-header">
                <span class="card-panel-title">Assigned Operator</span>
            </div>
            <div class="card-panel-body">
                <div class="fw-semibold" style="font-size: 0.95rem; color: var(--text-heading);"><?= esc($trip['driver_name']) ?></div>
                <div class="mono text-muted mb-2" style="font-size: 0.75rem;"><i class="fa-solid fa-phone me-1"></i> <?= esc($trip['driver_phone']) ?></div>
                <div class="small text-muted mb-3">License: <span class="mono text-dark fw-medium"><?= esc($trip['license_number']) ?></span></div>
                <div>
                    <a href="<?= base_url('drivers/' . $trip['driver_id']) ?>" class="btn btn-secondary w-100 text-center d-block">
                        View Operator Scorecard &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    const origin = [<?= (float)$trip['origin_lat'] ?>, <?= (float)$trip['origin_lng'] ?>];
    const destination = [<?= (float)$trip['destination_lat'] ?>, <?= (float)$trip['destination_lng'] ?>];
    const vehiclePos = [<?= (float)$trip['current_latitude'] ?>, <?= (float)$trip['current_longitude'] ?>];

    const map = L.map('tripMap', {
        zoomControl: true,
        attributionControl: false
    }).setView(vehiclePos, 10);

    // Google Maps Roadmap & Corridor
    L.tileLayer('https://mt{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        subdomains: ['0', '1', '2', '3'],
        attribution: '&copy; Google Maps'
    }).addTo(map);

    // Origin Marker (Green)
    L.circleMarker(origin, {
        radius: 8,
        fillColor: '#10b981',
        color: '#ffffff',
        weight: 2,
        fillOpacity: 1
    }).addTo(map).bindPopup("<b style='font-family: var(--font-sans);'>Pickup:</b> <?= esc($trip['origin_address']) ?>");

    // Destination Marker (Red)
    L.circleMarker(destination, {
        radius: 8,
        fillColor: '#ef4444',
        color: '#ffffff',
        weight: 2,
        fillOpacity: 1
    }).addTo(map).bindPopup("<b style='font-family: var(--font-sans);'>Delivery:</b> <?= esc($trip['destination_address']) ?>");

    // Draw route line
    const routeLine = L.polyline([origin, vehiclePos, destination], {
        color: '#2563eb',
        weight: 3.5,
        opacity: 0.8,
        dashArray: '6, 6'
    }).addTo(map);

    // Vehicle live pin
    const truckIcon = L.divIcon({
        className: 'custom-fleet-pin',
        html: `<div style="background-color: #2563eb; color: #ffffff; border-radius: 8px; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.25); border: 2px solid #ffffff;">
                <i class="fa-solid fa-truck" style="font-size: 14px;"></i>
               </div>`,
        iconSize: [34, 34],
        iconAnchor: [17, 17]
    });

    L.marker(vehiclePos, { icon: truckIcon }).addTo(map)
        .bindPopup("<div style='font-family: var(--font-sans);'><strong class='mono'><?= esc($trip['vehicle_code']) ?></strong><br>Speed: <?= $trip['current_speed'] ?> km/h</div>")
        .openPopup();

    map.fitBounds(L.featureGroup([
        L.marker(origin),
        L.marker(destination),
        L.marker(vehiclePos)
    ]).getBounds().pad(0.2));

    // Toggle end odometer field on status change
    document.getElementById('tripStatusSelect').addEventListener('change', function() {
        const odoField = document.getElementById('endOdoField');
        if (this.value === 'completed') {
            odoField.style.display = 'block';
        } else {
            odoField.style.display = 'none';
        }
    });
</script>
<?= $this->endSection() ?>
