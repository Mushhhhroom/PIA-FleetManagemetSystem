<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            Overview &bull; Real-Time Fleet Intelligence
        </div>
        <h1 class="page-title mb-1">Operations Command Center</h1>
        <p class="text-muted small mb-0">High-level telemetry overview, active dispatches, and fleet health.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= base_url('tracking') ?>" class="btn btn-corp btn-corp-secondary">
            <i class="fa-solid fa-satellite me-1 text-primary"></i> Live Radar
        </a>
        <?php if (session()->get('user_role') !== 'maintenance'): ?>
            <a href="<?= base_url('trips/new') ?>" class="btn btn-corp btn-corp-primary">
                <i class="fa-solid fa-plus me-1"></i> New Dispatch
            </a>
        <?php else: ?>
            <a href="<?= base_url('maintenance/new') ?>" class="btn btn-corp btn-corp-primary">
                <i class="fa-solid fa-plus me-1"></i> New Work Order
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Tier 1 VRS & Dispatch Action Alerts for Dispatcher & Admin -->
<?php if (!empty($pendingTier1Count) && $pendingTier1Count > 0 && in_array(session()->get('user_role'), ['admin', 'dispatcher'])): ?>
    <div class="alert alert-warning border-warning-subtle shadow-sm p-3 mb-4 rounded-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <span class="rounded-circle bg-warning text-dark d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; font-size: 1.1rem;">
                <i class="fa-solid fa-file-circle-exclamation"></i>
            </span>
            <div>
                <strong class="text-dark d-block">Tier 1 Approval Queue: <?= $pendingTier1Count ?> Vehicle Request Slip(s) Awaiting Dispatcher Review</strong>
                <span class="small text-muted">Verify passenger specifications, destination feasibility, and justification documents under the 4-hour SLA benchmark.</span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <a href="<?= base_url('approvals') ?>" class="btn btn-sm btn-primary px-3 fw-semibold">
                <i class="fa-solid fa-gavel me-1"></i> Review &amp; Act (Approvals Portal)
            </a>
            <a href="<?= base_url('dispatch') ?>" class="btn btn-sm btn-outline-secondary px-3">
                <i class="fa-solid fa-truck-fast me-1"></i> Dispatch Console
            </a>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($pendingDispatchCount) && $pendingDispatchCount > 0 && in_array(session()->get('user_role'), ['admin', 'dispatcher'])): ?>
    <div class="alert alert-info border-info-subtle shadow-sm p-3 mb-4 rounded-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <span class="rounded-circle bg-info text-white d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; font-size: 1.1rem;">
                <i class="fa-solid fa-truck-fast"></i>
            </span>
            <div>
                <strong class="text-dark d-block">Asset Allocation: <?= $pendingDispatchCount ?> Authorized Request(s) Ready for Dispatch</strong>
                <span class="small text-muted">Cleared by Administrative Division Head. Assign vehicles, qualified drivers, and generate electronic Trip Tickets (e-DTT).</span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <a href="<?= base_url('dispatch') ?>" class="btn btn-sm btn-success px-3 fw-semibold">
                <i class="fa-solid fa-van-shuttle me-1"></i> Open Dispatch Console
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- KPI Metric Tiles -->
<div class="row g-3 mb-4">
    <div class="col-xl-2 col-md-4 col-6">
        <div class="kpi-tile">
            <div class="kpi-label">Total Fleet</div>
            <div class="kpi-value"><?= $totalVehicles ?></div>
            <div class="kpi-sub"><span class="text-success fw-semibold"><?= $activeVehicles ?></span> ready for assignment</div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="kpi-tile">
            <div class="kpi-label">In Transit</div>
            <div class="kpi-value text-primary"><?= $inTransitVehicles ?></div>
            <div class="kpi-sub">Commercial delivery routes</div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="kpi-tile">
            <div class="kpi-label">In Workshop</div>
            <div class="kpi-value text-warning"><?= $maintenanceVehicles ?></div>
            <div class="kpi-sub">Preventive & repair PMS</div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="kpi-tile">
            <div class="kpi-label">Active Drivers</div>
            <div class="kpi-value"><?= $availableDrivers ?> <span class="fs-6 text-muted fw-normal">/ <?= $totalDrivers ?></span></div>
            <div class="kpi-sub"><span class="text-primary fw-semibold"><?= $onTripDrivers ?></span> currently on road</div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="kpi-tile">
            <div class="kpi-label">Active Trips</div>
            <div class="kpi-value text-primary"><?= $activeTrips ?></div>
            <div class="kpi-sub"><?= $scheduledTrips ?> upcoming queued</div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="kpi-tile">
            <div class="kpi-label">Fuel Spend</div>
            <div class="kpi-value mono" style="font-size: 1.35rem;">₱<?= number_format($totalFuelCost, 0) ?></div>
            <div class="kpi-sub">Total fleet fuel ledger</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Left Map & Dispatches Column -->
    <div class="col-lg-8">
        <!-- Live GPS Radar Map Panel -->
        <div class="card-panel mb-4">
            <div class="card-panel-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-satellite text-primary"></i>
                    <h6>Live Telematics Map View</h6>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="small text-muted font-monospace"><span id="activeMarkerCount"><?= count($liveVehicles) ?></span> Units Active</span>
                    <a href="<?= base_url('tracking') ?>" class="text-primary small text-decoration-none fw-semibold">Console &rarr;</a>
                </div>
            </div>
            <div class="p-0">
                <div id="dashMap" style="height: 380px; width: 100%; background: #e2e8f0;"></div>
            </div>
        </div>

        <!-- Active Trips Ledger -->
        <div class="card-panel">
            <div class="card-panel-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-route text-primary"></i>
                    <h6>Active Dispatches & Waybills</h6>
                </div>
                <?php if (session()->get('user_role') !== 'maintenance'): ?>
                    <a href="<?= base_url('trips') ?>" class="btn btn-corp btn-corp-secondary py-1 px-2" style="font-size: 0.75rem;">View All Trips</a>
                <?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table-minimal">
                    <thead>
                        <tr>
                            <th>Waybill #</th>
                            <th>Vehicle</th>
                            <th>Driver</th>
                            <th>Route Corridor</th>
                            <th>Cargo</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentTrips)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted small">No active trips dispatched at the moment.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentTrips as $trip): ?>
                                <tr>
                                    <td>
                                        <a href="<?= base_url('trips/' . $trip['id']) ?>" class="fw-bold text-decoration-none text-dark mono">
                                            <?= esc($trip['trip_number']) ?>
                                        </a>
                                        <?php if ($trip['priority'] === 'urgent' || $trip['priority'] === 'critical'): ?>
                                            <span class="badge bg-danger-subtle text-danger ms-1" style="font-size: 0.65rem;"><?= ucfirst($trip['priority']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="text-dark d-block mono"><?= esc($trip['vehicle_code']) ?></strong>
                                        <span class="small text-muted mono"><?= esc($trip['plate_number']) ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-medium text-dark"><?= esc($trip['driver_name']) ?></span>
                                        <span class="small text-muted d-block mono" style="font-size: 0.72rem;"><?= esc($trip['driver_phone']) ?></span>
                                    </td>
                                    <td style="max-width: 240px;">
                                        <div class="text-truncate small fw-medium text-dark"><?= esc($trip['destination_address']) ?></div>
                                        <div class="text-truncate text-muted" style="font-size: 0.72rem;">From: <?= esc($trip['origin_address']) ?></div>
                                    </td>
                                    <td class="small">
                                        <span class="fw-medium"><?= esc($trip['cargo_type']) ?></span>
                                        <span class="text-muted d-block mono" style="font-size: 0.72rem;"><?= number_format($trip['cargo_weight_kg'], 0) ?> kg</span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= esc($trip['status']) ?>">
                                            <?= ucfirst(str_replace('_', ' ', $trip['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= base_url('trips/' . $trip['id']) ?>" class="btn btn-corp btn-corp-secondary py-1 px-2" style="font-size: 0.75rem;">
                                            Inspect &rarr;
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Status Doughnut & Maintenance Alerts -->
    <div class="col-lg-4">
        <!-- Fleet Status Distribution -->
        <div class="card-panel mb-4">
            <div class="card-panel-header">
                <h6>Fleet Asset Distribution</h6>
            </div>
            <div class="card-panel-body">
                <div style="height: 200px; position: relative;">
                    <canvas id="fleetStatusChart"></canvas>
                </div>
                <div class="d-flex justify-content-around text-center mt-3 pt-3 border-top">
                    <div>
                        <div class="small text-muted text-uppercase" style="font-size: 0.65rem;">Active</div>
                        <strong class="text-success"><?= $activeVehicles ?></strong>
                    </div>
                    <div>
                        <div class="small text-muted text-uppercase" style="font-size: 0.65rem;">In Transit</div>
                        <strong class="text-primary"><?= $inTransitVehicles ?></strong>
                    </div>
                    <div>
                        <div class="small text-muted text-uppercase" style="font-size: 0.65rem;">In Shop</div>
                        <strong class="text-warning"><?= $maintenanceVehicles ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Work Orders & Technical Alerts -->
        <div class="card-panel">
            <div class="card-panel-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-wrench text-warning"></i>
                    <h6>Workshop Maintenance Orders</h6>
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
                                    <span class="status-badge status-<?= esc($wo['status']) ?>"><?= ucfirst(str_replace('_', ' ', $wo['status'])) ?></span>
                                </div>
                                <div class="fw-medium text-dark small mb-1"><?= esc($wo['service_type']) ?></div>
                                <div class="d-flex justify-content-between align-items-center text-muted" style="font-size: 0.72rem;">
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
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize Minimalist Leaflet Map
        const map = L.map('dashMap', {
            zoomControl: false,
            attributionControl: false
        }).setView([14.5995, 120.9842], 9);

        // Google Maps Roadmap Layer
        L.tileLayer('https://mt{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            subdomains: ['0', '1', '2', '3'],
            attribution: '&copy; Google Maps'
        }).addTo(map);

        L.control.zoom({ position: 'bottomright' }).addTo(map);

        // Custom Vehicle Icon Marker
        function createVehicleIcon(status) {
            let color = '#10b981';
            if (status === 'in_transit') color = '#2563eb';
            if (status === 'maintenance') color = '#f59e0b';
            if (status === 'out_of_service') color = '#ef4444';

            return L.divIcon({
                className: 'custom-vehicle-marker',
                html: `<div style="background:${color}; width:28px; height:28px; border-radius:50%; display:grid; place-items:center; color:#fff; font-size:12px; box-shadow:0 2px 6px rgba(0,0,0,0.25); border:2px solid #fff;"><i class="fa-solid fa-truck"></i></div>`,
                iconSize: [28, 28],
                iconAnchor: [14, 14]
            });
        }

        const vehicles = <?= json_encode($liveVehicles) ?>;
        const bounds = [];

        vehicles.forEach(v => {
            if (v.current_latitude && v.current_longitude) {
                const marker = L.marker([v.current_latitude, v.current_longitude], {
                    icon: createVehicleIcon(v.status)
                }).addTo(map);

                marker.bindPopup(`
                    <div style="font-family:inherit; min-width:180px; padding:4px;">
                        <strong style="font-size:13px; display:block; color:#0f172a;">${v.vehicle_code} &bull; ${v.plate_number}</strong>
                        <div style="font-size:11px; color:#64748b; margin-top:2px;">${v.make} ${v.model}</div>
                        <hr style="margin:6px 0; border-color:#e2e8f0;">
                        <div style="font-size:11px; display:flex; justify-content:space-between;">
                            <span>Speed:</span><strong>${v.current_speed || 0} km/h</strong>
                        </div>
                        <div style="font-size:11px; display:flex; justify-content:space-between; margin-top:2px;">
                            <span>Driver:</span><strong>${v.driver_name || 'Unassigned'}</strong>
                        </div>
                    </div>
                `);

                bounds.push([v.current_latitude, v.current_longitude]);
            }
        });

        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [30, 30] });
        }

        // Initialize Fleet Distribution Chart
        const ctx = document.getElementById('fleetStatusChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'In Transit', 'In Workshop', 'Out of Service'],
                datasets: [{
                    data: [
                        <?= $activeVehicles ?>,
                        <?= $inTransitVehicles ?>,
                        <?= $maintenanceVehicles ?>,
                        <?= $totalVehicles - ($activeVehicles + $inTransitVehicles + $maintenanceVehicles) ?>
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
    });
</script>
<?= $this->endSection() ?>
