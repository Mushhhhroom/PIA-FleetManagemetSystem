<?php
/**
 * Dashboard Command Center View
 *
 * @var string                     $title
 * @var int                        $totalVehicles
 * @var int                        $inTransitVehicles
 * @var int                        $activeVehicles
 * @var int                        $maintenanceVehicles
 * @var int                        $totalDrivers
 * @var int                        $availableDrivers
 * @var int                        $onTripDrivers
 * @var int                        $offDutyDrivers
 * @var int                        $onLeaveDrivers
 * @var int                        $activeTrips
 * @var int                        $scheduledTrips
 * @var int                        $completedTrips
 * @var int                        $openMaintenance
 * @var float                      $totalFuelCost
 * @var float                      $totalFuelLiters
 * @var float                      $totalDistanceKm
 * @var list<array<string, mixed>> $liveVehicles
 * @var list<array<string, mixed>> $recentTrips
 * @var list<array<string, mixed>> $maintenanceAlerts
 * @var int                        $pendingTier1Count
 * @var int                        $pendingAdminCount
 * @var int                        $pendingDispatchCount
 * @var list<array<string, mixed>> $pendingTier1List
 * @var list<string>               $analyticsMonths
 * @var list<int>                  $analyticsTicketCounts
 * @var list<array<string, mixed>> $officeRequests
 */
$liveVehicles          = $liveVehicles ?? [];
$recentTrips           = $recentTrips ?? [];
$maintenanceAlerts     = $maintenanceAlerts ?? [];
$pendingTier1List      = $pendingTier1List ?? [];
$analyticsMonths       = $analyticsMonths ?? [];
$analyticsTicketCounts = $analyticsTicketCounts ?? [];
$officeRequests        = $officeRequests ?? [];
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<style>
@keyframes fleet-pulse {
    0% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.75); }
    70% { box-shadow: 0 0 0 10px rgba(37, 99, 235, 0); }
    100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
}
.in-transit-pulse > div {
    animation: fleet-pulse 2s infinite ease-in-out !important;
}
.kpi-tile-wrap {
    min-width: 0;
    overflow: hidden;
}
.kpi-tile-value {
    font-size: clamp(1.25rem, 1.8vw, 1.65rem);
    font-weight: 700;
    line-height: 1.15;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.chart-container-panel {
    position: relative;
    width: 100%;
    min-height: 220px;
}
.map-ctrl-btn {
    font-size: 0.72rem;
    padding: 3px 8px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    color: #334155;
    cursor: pointer;
    transition: all 0.15s ease;
}
.map-ctrl-btn:hover {
    background: #f1f5f9;
    color: #0f172a;
}
</style>

<!-- Page Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            PIA Motorpool &bull; Real-Time Telematics &amp; Analytics Command
        </div>
        <h1 class="page-title mb-1">Operations Command Center</h1>
        <p class="text-muted small mb-0">Official vehicle travel requests, active driver telematics, and motorpool fleet readiness.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= base_url('tracking') ?>" class="btn btn-secondary">
            <i class="fa-solid fa-satellite me-1 text-primary"></i> Live Telematics Radar
        </a>
        <?php if (session()->get('user_role') !== 'maintenance'): ?>
            <a href="<?= base_url('requests/new') ?>" class="btn btn-outline-secondary">
                <i class="fa-solid fa-file-signature me-1"></i> File VRS
            </a>
            <a href="<?= base_url('dispatch') ?>" class="btn btn-primary">
                <i class="fa-solid fa-van-shuttle me-1"></i> Dispatch Console
            </a>
        <?php else: ?>
            <a href="<?= base_url('pir') ?>" class="btn btn-outline-secondary">
                <i class="fa-solid fa-clipboard-check me-1"></i> PIR Queue
            </a>
            <a href="<?= base_url('maintenance/new') ?>" class="btn btn-primary">
                <i class="fa-solid fa-plus me-1"></i> New Work Order
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Tier 1 VRS & Dispatch Action Alerts for Dispatcher & Admin -->
<?php if (!empty($pendingTier1Count) && $pendingTier1Count > 0 && in_array(session()->get('user_role'), ['admin', 'dispatcher'])): ?>
    <div class="alert alert-warning border-warning-subtle p-3 mb-4 rounded-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <span class="rounded-circle bg-warning text-dark d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; font-size: 1.1rem;">
                <i class="fa-solid fa-file-circle-exclamation"></i>
            </span>
            <div>
                <strong class="text-dark d-block">Tier 1 Approval Queue: <?= $pendingTier1Count ?> Vehicle Request Slip(s) Awaiting Review</strong>
                <span class="small text-muted">Verify passenger specifications and travel route feasibility under the 4-hour SLA benchmark.</span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <a href="<?= base_url('approvals') ?>" class="btn btn-sm btn-primary">
                <i class="fa-solid fa-gavel me-1"></i> Review &amp; Endorse
            </a>
            <a href="<?= base_url('dispatch') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fa-solid fa-truck-fast me-1"></i> Dispatch Console
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- Tier 2 VRS Action Alert for Admin Division Head -->
<?php if (!empty($pendingAdminCount) && $pendingAdminCount > 0 && in_array(session()->get('user_role'), ['admin', 'approver_admin'])): ?>
    <div class="alert alert-primary border-primary-subtle p-3 mb-4 rounded-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3" style="background: rgba(37, 99, 235, 0.08);">
        <div class="d-flex align-items-center gap-3">
            <span class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; font-size: 1.1rem;">
                <i class="fa-solid fa-stamp"></i>
            </span>
            <div>
                <strong class="text-dark d-block">Tier 2 Final Authorization Queue: <?= $pendingAdminCount ?> Request(s) Awaiting Approval</strong>
                <span class="small text-muted">Administrative Division Head final authorization required prior to motorpool asset dispatch.</span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <a href="<?= base_url('approvals') ?>" class="btn btn-sm btn-primary">
                <i class="fa-solid fa-signature me-1"></i> Authorize Requests
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- Responsive KPI Metric Tiles (No Overlapping Text) -->
<div class="row g-3 mb-4">
    <div class="col-xl-2 col-md-4 col-6 kpi-tile-wrap">
        <div class="kpi-tile">
            <div class="kpi-label">Total Fleet</div>
            <div class="kpi-tile-value text-dark mono"><?= $totalVehicles ?></div>
            <div class="kpi-sub text-truncate"><span class="text-success fw-semibold"><?= $activeVehicles ?></span> ready for trip</div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6 kpi-tile-wrap">
        <div class="kpi-tile">
            <div class="kpi-label">In Transit</div>
            <div class="kpi-tile-value text-primary mono"><?= $inTransitVehicles ?></div>
            <div class="kpi-sub text-truncate">Official travel missions</div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6 kpi-tile-wrap">
        <div class="kpi-tile">
            <div class="kpi-label">In Workshop</div>
            <div class="kpi-tile-value text-warning mono"><?= $maintenanceVehicles ?></div>
            <div class="kpi-sub text-truncate">5K PMS &amp; repairs</div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6 kpi-tile-wrap">
        <div class="kpi-tile">
            <div class="kpi-label">Active Drivers</div>
            <div class="kpi-tile-value text-dark mono"><?= $availableDrivers ?> <span class="fs-6 text-muted fw-normal">/ <?= $totalDrivers ?></span></div>
            <div class="kpi-sub text-truncate"><span class="text-primary fw-semibold"><?= $onTripDrivers ?></span> currently on road</div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6 kpi-tile-wrap">
        <div class="kpi-tile">
            <div class="kpi-label">Active Trips</div>
            <div class="kpi-tile-value text-primary mono"><?= $activeTrips ?></div>
            <div class="kpi-sub text-truncate"><?= $scheduledTrips ?> upcoming queued</div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6 kpi-tile-wrap">
        <div class="kpi-tile">
            <div class="kpi-label">Total Fuel Spend</div>
            <div class="kpi-tile-value text-dark mono" style="font-size: 1.30rem;">₱<?= number_format($totalFuelCost, 0) ?></div>
            <div class="kpi-sub text-truncate"><?= number_format($totalFuelLiters, 1) ?> L accounted</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Left Map & Live Driver Telematics Column -->
    <div class="col-lg-8">
        <!-- Live GPS Radar Map Panel (Zero API Key Dependency) -->
        <div class="card-panel mb-4">
            <div class="card-panel-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-satellite text-primary"></i>
                    <h6 class="mb-0 fw-bold">Live Fleet Telematics &amp; Driver Location Map</h6>
                    <span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size: 0.65rem;">ZERO-KEY MAP API</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="map-ctrl-btn" onclick="focusHq()" title="Center on PIA Central Office Compound">
                        <i class="fa-solid fa-building-flag me-1"></i> PIA Compound
                    </button>
                    <button type="button" class="map-ctrl-btn" onclick="fitAllUnits()" title="Fit bounds to all active vehicles">
                        <i class="fa-solid fa-expand me-1"></i> Fit All Units
                    </button>
                    <a href="<?= base_url('tracking') ?>" class="text-primary small text-decoration-none fw-semibold ms-2">Full Radar &rarr;</a>
                </div>
            </div>
            <div class="p-0 position-relative">
                <div id="dashMap" style="height: 400px; width: 100%; background: #e2e8f0;"></div>
            </div>
            <div class="p-3 bg-light border-top d-flex flex-wrap justify-content-between align-items-center gap-2 text-muted small" style="font-size: 0.76rem;">
                <div class="d-flex align-items-center gap-3">
                    <span><i class="fa-solid fa-circle text-success me-1"></i> Active (<?= $activeVehicles ?>)</span>
                    <span><i class="fa-solid fa-circle text-primary me-1"></i> In Transit (<?= $inTransitVehicles ?>)</span>
                    <span><i class="fa-solid fa-circle text-warning me-1"></i> Maintenance (<?= $maintenanceVehicles ?>)</span>
                </div>
                <div>
                    <span class="mono"><i class="fa-solid fa-clock me-1"></i> Real-time Telematics Feed Active</span>
                </div>
            </div>
        </div>

        <!-- Official Driver's Trip Tickets (e-DTT) Ledger -->
        <div class="card-panel">
            <div class="card-panel-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-ticket text-primary"></i>
                    <h6 class="mb-0 fw-bold">Recent Official Driver's Trip Tickets (e-DTT)</h6>
                </div>
                <?php if (session()->get('user_role') !== 'maintenance'): ?>
                    <a href="<?= base_url('tickets') ?>" class="btn btn-xs btn-outline-secondary">View All Tickets</a>
                <?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table-minimal">
                    <thead>
                        <tr>
                            <th style="width: 20%;">e-DTT Serial #</th>
                            <th style="width: 18%;">Vehicle Unit</th>
                            <th style="width: 22%;">Official Driver</th>
                            <th style="width: 25%;">Destination &amp; Purpose</th>
                            <th style="width: 15%; text-align: right;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentTrips)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted small">No official Driver's Trip Tickets issued yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentTrips as $trip): ?>
                                <tr>
                                    <td>
                                        <a href="<?= base_url('tickets/' . $trip['id']) ?>" class="fw-bold text-decoration-none text-dark mono">
                                            <?= esc($trip['ticket_serial_no']) ?>
                                        </a>
                                        <?php if (!empty($trip['request_number'])): ?>
                                            <span class="small text-muted d-block mono" style="font-size: 0.68rem;">VRS: <?= esc($trip['request_number']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="text-dark d-block mono"><?= esc($trip['vehicle_code']) ?></strong>
                                        <span class="small text-muted mono" style="font-size: 0.72rem;"><?= esc($trip['plate_number']) ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-medium text-dark d-block"><?= esc($trip['driver_name'] ?: 'Unassigned') ?></span>
                                        <span class="small text-muted mono" style="font-size: 0.70rem;"><?= esc($trip['driver_phone'] ?? 'No phone') ?></span>
                                    </td>
                                    <td>
                                        <div class="text-truncate small fw-medium text-dark" style="max-width: 220px;" title="<?= esc($trip['authorized_destination'] ?? 'Official Travel') ?>">
                                            <i class="fa-solid fa-location-dot text-danger me-1"></i><?= esc($trip['authorized_destination'] ?? 'Official Travel') ?>
                                        </div>
                                        <div class="text-truncate text-muted" style="max-width: 220px; font-size: 0.70rem;">
                                            <?= esc($trip['authorized_purpose'] ?? '') ?>
                                        </div>
                                    </td>
                                    <td style="text-align: right;">
                                        <span class="status-badge status-<?= esc($trip['status']) ?>">
                                            <?= strtoupper(str_replace('_', ' ', $trip['status'])) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Analytics & Reports Visualizations -->
    <div class="col-lg-4">
        <!-- Visualization 1: Monthly Travel Demand & Completion Trend -->
        <div class="card-panel mb-4">
            <div class="card-panel-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-chart-area text-primary"></i>
                    <h6 class="mb-0 fw-bold">Travel Demand Trajectory</h6>
                </div>
                <span class="badge bg-light text-secondary border small">Last 6 Months</span>
            </div>
            <div class="card-panel-body">
                <div class="chart-container-panel" style="height: 180px;">
                    <canvas id="travelDemandChart"></canvas>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top small text-muted">
                    <span><i class="fa-solid fa-flag-checkered text-success me-1"></i> Completed: <strong><?= $completedTrips ?></strong></span>
                    <span><i class="fa-solid fa-road text-primary me-1"></i> Total Km: <strong><?= number_format($totalDistanceKm, 0) ?> KM</strong></span>
                </div>
            </div>
        </div>

        <!-- Visualization 2: Fleet Asset Distribution -->
        <div class="card-panel mb-4">
            <div class="card-panel-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-chart-pie text-success"></i>
                    <h6 class="mb-0 fw-bold">Fleet Readiness Distribution</h6>
                </div>
                <span class="badge bg-light text-secondary border small"><?= $totalVehicles ?> Units</span>
            </div>
            <div class="card-panel-body">
                <div class="chart-container-panel" style="height: 170px;">
                    <canvas id="fleetStatusChart"></canvas>
                </div>
                <div class="row g-2 text-center mt-3 pt-3 border-top">
                    <div class="col-4">
                        <div class="small text-muted text-uppercase" style="font-size: 0.65rem;">Ready</div>
                        <strong class="text-success mono"><?= $activeVehicles ?></strong>
                    </div>
                    <div class="col-4">
                        <div class="small text-muted text-uppercase" style="font-size: 0.65rem;">On Road</div>
                        <strong class="text-primary mono"><?= $inTransitVehicles ?></strong>
                    </div>
                    <div class="col-4">
                        <div class="small text-muted text-uppercase" style="font-size: 0.65rem;">In Shop</div>
                        <strong class="text-warning mono"><?= $maintenanceVehicles ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Visualization 3: Driver Duty Roster Distribution -->
        <div class="card-panel mb-4">
            <div class="card-panel-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-id-badge text-info"></i>
                    <h6 class="mb-0 fw-bold">Driver Duty Distribution</h6>
                </div>
                <span class="badge bg-light text-secondary border small"><?= $totalDrivers ?> Drivers</span>
            </div>
            <div class="card-panel-body">
                <div class="chart-container-panel" style="height: 140px;">
                    <canvas id="driverDutyChart"></canvas>
                </div>
                <div class="d-flex justify-content-between text-muted small mt-2 pt-2 border-top" style="font-size: 0.72rem;">
                    <span>Available: <strong class="text-success"><?= $availableDrivers ?></strong></span>
                    <span>On Trip: <strong class="text-primary"><?= $onTripDrivers ?></strong></span>
                    <span>Off Duty: <strong class="text-secondary"><?= $offDutyDrivers ?></strong></span>
                    <span>On Leave: <strong class="text-warning"><?= $onLeaveDrivers ?></strong></span>
                </div>
            </div>
        </div>

        <!-- Work Orders & Technical Alerts -->
        <div class="card-panel">
            <div class="card-panel-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-wrench text-warning"></i>
                    <h6 class="mb-0 fw-bold">Workshop PMS Orders</h6>
                </div>
                <a href="<?= base_url('maintenance') ?>" class="text-primary small text-decoration-none fw-semibold">View All</a>
            </div>
            <div class="p-0">
                <ul class="list-group list-group-flush mb-0">
                    <?php if (empty($maintenanceAlerts)): ?>
                        <li class="list-group-item text-center text-muted py-4 small">No pending maintenance work orders.</li>
                    <?php else: ?>
                        <?php foreach ($maintenanceAlerts as $wo): ?>
                            <li class="list-group-item p-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <strong class="text-dark small mono"><?= esc($wo['reference_no']) ?></strong>
                                    <span class="status-badge status-<?= esc($wo['status']) ?>" style="font-size: 0.65rem;"><?= ucfirst(str_replace('_', ' ', $wo['status'])) ?></span>
                                </div>
                                <div class="fw-medium text-dark small mb-1"><?= esc($wo['service_type']) ?></div>
                                <div class="d-flex justify-content-between align-items-center text-muted" style="font-size: 0.70rem;">
                                    <span>Unit: <strong class="text-dark mono"><?= esc($wo['vehicle_code']) ?></strong></span>
                                    <span><i class="fa-regular fa-calendar me-1"></i> <?= esc($wo['scheduled_date']) ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    let map = null;
    let mapBounds = [];
    const HQ_LAT = 14.6572;
    const HQ_LNG = 121.0450;

    document.addEventListener('DOMContentLoaded', function () {
        // -------------------------------------------------------------
        // 1. Initialize Leaflet Map with Zero-Key OpenStreetMap Layer
        // -------------------------------------------------------------
        map = L.map('dashMap', {
            zoomControl: true,
            attributionControl: true
        }).setView([HQ_LAT, HQ_LNG], 12);

        // Google Maps Primary Tile Layer
        const googleLayer = L.tileLayer('https://mt{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            subdomains: ['0', '1', '2', '3'],
            attribution: '&copy; Google Maps'
        }).addTo(map);

        // Add PIA Central Office Compound Headquarters Pin
        const hqIcon = L.divIcon({
            className: 'pia-hq-marker',
            html: `<div style="background:#0f172a; width:32px; height:32px; border-radius:50%; display:grid; place-items:center; color:#fff; font-size:13px; box-shadow:0 3px 8px rgba(0,0,0,0.3); border:2.5px solid #fff;"><i class="fa-solid fa-landmark"></i></div>`,
            iconSize: [32, 32],
            iconAnchor: [16, 16]
        });

        L.marker([HQ_LAT, HQ_LNG], { icon: hqIcon })
            .bindPopup(`
                <div style="font-family:var(--font-sans); padding:4px;">
                    <strong style="color:#0f172a; font-size:13px;">PIA Central Office Motorpool</strong><br>
                    <span style="font-size:11px; color:#64748b;">Visayas Avenue, Diliman, Quezon City</span><br>
                    <span class="badge bg-primary text-white mt-1" style="font-size:10px;">HQ Central Compound</span>
                </div>
            `)
            .addTo(map);

        mapBounds.push([HQ_LAT, HQ_LNG]);

        // Custom Vehicle & Driver Location Icon Marker
        function createVehicleMarkerIcon(status, isTransit) {
            let color = '#10b981'; // active/available
            if (status === 'in_transit') color = '#2563eb';
            if (status === 'maintenance' || status === 'under_maintenance') color = '#f59e0b';
            if (status === 'out_of_service' || status === 'disabled_breakdown') color = '#ef4444';

            return L.divIcon({
                className: 'custom-vehicle-marker' + (isTransit ? ' in-transit-pulse' : ''),
                html: `<div style="background:${color}; width:28px; height:28px; border-radius:50%; display:grid; place-items:center; color:#fff; font-size:12px; box-shadow:0 2px 6px rgba(0,0,0,0.25); border:2px solid #fff;"><i class="fa-solid fa-car-side"></i></div>`,
                iconSize: [28, 28],
                iconAnchor: [14, 14]
            });
        }

        const vehicles = <?= json_encode($liveVehicles) ?>;
        
        // Prevent stacking overlaps on compound coordinates
        const coordGroups = {};
        vehicles.forEach(v => {
            let lat = parseFloat(v.current_latitude) || HQ_LAT;
            let lng = parseFloat(v.current_longitude) || HQ_LNG;
            const key = lat.toFixed(4) + ',' + lng.toFixed(4);
            if (!coordGroups[key]) coordGroups[key] = [];
            coordGroups[key].push(v);
        });

        const offsetIndex = {};

        vehicles.forEach(v => {
            let lat = parseFloat(v.current_latitude) || HQ_LAT;
            let lng = parseFloat(v.current_longitude) || HQ_LNG;
            const key = lat.toFixed(4) + ',' + lng.toFixed(4);
            const cluster = coordGroups[key] || [];

            if (cluster.length > 1) {
                const idx = offsetIndex[key] || 0;
                offsetIndex[key] = idx + 1;
                const angle = (2 * Math.PI / cluster.length) * idx;
                const radius = 0.00022; // slight visual spread
                lat += radius * Math.cos(angle);
                lng += radius * Math.sin(angle);
            }

            const isTransit = v.status === 'in_transit';
            const marker = L.marker([lat, lng], {
                icon: createVehicleMarkerIcon(v.status, isTransit)
            }).addTo(map);

            const statusLabel = (v.status || '').replace('_', ' ').toUpperCase();
            const tripBadge = v.trip_number
                ? `<div style="font-size:11px; margin-top:5px; padding-top:5px; border-top:1px dashed #e2e8f0; color:#2563eb;">
                     <i class="fa-solid fa-ticket me-1"></i>e-DTT: <strong>${v.trip_number}</strong>
                   </div>`
                : '';
            const destBadge = v.destination_address
                ? `<div style="font-size:11px; color:#334155; margin-top:3px; max-width:210px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                     <i class="fa-solid fa-location-dot text-danger me-1"></i>${v.destination_address}
                   </div>`
                : '';

            marker.bindPopup(`
                <div style="font-family:var(--font-sans); min-width:210px; padding:4px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                        <strong style="font-size:13px; color:#0f172a;" class="mono">${v.vehicle_code}</strong>
                        <span class="status-badge status-${v.status}" style="font-size:9px; padding:2px 6px;">${statusLabel}</span>
                    </div>
                    <div style="font-size:11px; color:#64748b; margin-bottom:6px;">${v.make} ${v.model} &bull; <span class="mono">${v.plate_number}</span></div>
                    
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:6px 8px; font-size:11px; margin-bottom:8px;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:2px;">
                            <span style="color:#64748b;">Driver:</span>
                            <strong style="color:#0f172a;">${v.driver_name || 'Unassigned'}</strong>
                        </div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:2px;">
                            <span style="color:#64748b;">Speed / Fuel:</span>
                            <strong class="mono">${v.current_speed || 0} km/h &bull; ${v.current_fuel_level || 85}%</strong>
                        </div>
                        ${tripBadge}
                        ${destBadge}
                    </div>

                    <a href="<?= base_url('tracking') ?>?vehicle_id=${v.id}" class="btn btn-xs btn-primary w-100 text-center" style="font-size:11px; padding:4px 8px; text-decoration:none; display:block;">
                        <i class="fa-solid fa-route me-1"></i> Track on Live Radar &rarr;
                    </a>
                </div>
            `);

            mapBounds.push([lat, lng]);
        });

        if (mapBounds.length > 1) {
            map.fitBounds(mapBounds, { padding: [40, 40] });
        }
        setTimeout(() => { if (map) map.invalidateSize(); }, 400);

        // -------------------------------------------------------------
        // 2. Analytics Visualization 1: Travel Demand Trajectory (6 Mos)
        // -------------------------------------------------------------
        const demandCtx = document.getElementById('travelDemandChart').getContext('2d');
        new Chart(demandCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($analyticsMonths) ?>,
                datasets: [{
                    label: 'Trips Issued',
                    data: <?= json_encode($analyticsTicketCounts) ?>,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.08)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#2563eb',
                    pointRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 2, font: { family: 'Inter', size: 10 } },
                        grid: { color: '#f1f5f9' }
                    },
                    x: {
                        ticks: { font: { family: 'Inter', size: 10 } },
                        grid: { display: false }
                    }
                }
            }
        });

        // -------------------------------------------------------------
        // 3. Analytics Visualization 2: Fleet Readiness Distribution
        // -------------------------------------------------------------
        const statusCtx = document.getElementById('fleetStatusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Ready for Trip', 'In Transit', 'In Workshop', 'Out of Service'],
                datasets: [{
                    data: [
                        <?= $activeVehicles ?>,
                        <?= $inTransitVehicles ?>,
                        <?= $maintenanceVehicles ?>,
                        <?= max(0, $totalVehicles - ($activeVehicles + $inTransitVehicles + $maintenanceVehicles)) ?>
                    ],
                    backgroundColor: ['#10b981', '#2563eb', '#f59e0b', '#ef4444'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                cutout: '72%'
            }
        });

        // -------------------------------------------------------------
        // 4. Analytics Visualization 3: Driver Duty Distribution
        // -------------------------------------------------------------
        const driverCtx = document.getElementById('driverDutyChart').getContext('2d');
        new Chart(driverCtx, {
            type: 'bar',
            data: {
                labels: ['Available', 'On Trip', 'Off Duty', 'On Leave'],
                datasets: [{
                    data: [
                        <?= $availableDrivers ?>,
                        <?= $onTripDrivers ?>,
                        <?= $offDutyDrivers ?>,
                        <?= $onLeaveDrivers ?>
                    ],
                    backgroundColor: ['#10b981', '#2563eb', '#94a3b8', '#f59e0b'],
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { family: 'Inter', size: 10 } },
                        grid: { color: '#f1f5f9' }
                    },
                    y: {
                        ticks: { font: { family: 'Inter', size: 10 } },
                        grid: { display: false }
                    }
                }
            }
        });
    });

    function focusHq() {
        if (map) {
            map.setView([HQ_LAT, HQ_LNG], 15);
        }
    }

    function fitAllUnits() {
        if (map && mapBounds.length > 0) {
            map.fitBounds(mapBounds, { padding: [40, 40] });
        }
    }
</script>
<?= $this->endSection() ?>
