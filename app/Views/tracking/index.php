<?php
/**
 * Real-Time Fleet Telematics & GPS Tracking View
 *
 * @var list<array<string, mixed>> $vehicles
 * @var array<string, mixed>       $apiStatus
 * @var string                     $title
 */
$vehicles  = $vehicles ?? [];
$apiStatus = $apiStatus ?? [];
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<style>
@keyframes fleet-pulse {
    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.75); }
    70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
    100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}
.in-transit-pulse > div {
    animation: fleet-pulse 2s infinite ease-in-out !important;
}
</style>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            PIA Motorpool &bull; OpenStreetMap Telematics
        </div>
        <h1 class="page-title mb-1">Live GPS Fleet Radar</h1>
        <p class="text-muted small mb-0">Real-time government vehicle locations, active e-DTT assignments, and GPS breadcrumb trails.</p>
    </div>
    
    <!-- Top Telematics Control Toolbar -->
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <!-- Map View Selector -->
        <div class="btn-group btn-group-sm" role="group" aria-label="Map Layer View">
            <button type="button" class="btn btn-sm btn-outline-secondary active" id="btnMapRoad" onclick="setMapLayer('street')">Street Map</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnMapSat" onclick="setMapLayer('satellite')">Satellite</button>
        </div>

        <!-- Auto-Stream Telemetry Toggle -->
        <button id="btnAutoStream" type="button" class="btn btn-sm btn-secondary">
            <i class="fa-solid fa-tower-broadcast text-success" id="streamIcon"></i>
            <span id="streamLabel">Stream: ON (5s)</span>
        </button>

        <!-- Fit Fleet Bounds -->
        <button id="btnFitBounds" type="button" class="btn btn-sm btn-secondary" title="Fit entire fleet into viewport">
            <i class="fa-solid fa-crosshairs text-muted"></i>
            <span>Fit Fleet</span>
        </button>

        <!-- Simulate Step -->
        <button id="btnSimulate" type="button" class="btn btn-sm btn-secondary">
            <i class="fa-solid fa-satellite text-primary"></i>
            <span>Simulate Step</span>
        </button>

        <!-- Sync Button -->
        <button id="btnRefresh" type="button" class="btn btn-sm btn-primary">
            <i class="fa-solid fa-arrows-rotate"></i>
            <span>Sync</span>
        </button>
    </div>
</div>

<div class="row g-3">
    <!-- Fleet List Column -->
    <div class="col-lg-4 col-xl-3">
        <div class="card-panel" style="height: calc(100vh - 180px); display: flex; flex-direction: column;">
            <!-- Header with Search -->
            <div class="p-3 border-bottom">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="mono" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 600; color: var(--text-muted);">
                        Service Units (<span id="vehicleCount"><?= count($vehicles) ?></span>)
                    </span>
                    <span id="telemetryBadge" class="telemetry-pulse" style="padding: 2px 8px; font-size: 0.7rem;">
                        <span class="pulse-dot"></span> Live
                    </span>
                </div>
                <!-- Vehicle Quick Search -->
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass" style="font-size: 0.75rem;"></i></span>
                    <input type="text" id="filterSearch" class="form-control border-start-0" placeholder="Filter code, plate, or driver..." style="font-size: 0.8rem;">
                </div>
            </div>
            
            <!-- Filter Pills -->
            <div class="p-2 border-bottom bg-light">
                <div class="btn-group w-100 btn-group-sm" role="group">
                    <button type="button" class="btn btn-sm btn-outline-secondary active btn-filter" data-filter="all" style="padding: 3px 6px; font-size: 0.72rem;">All</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-filter" data-filter="in_transit" style="padding: 3px 6px; font-size: 0.72rem;">Transit</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-filter" data-filter="active" style="padding: 3px 6px; font-size: 0.72rem;">Active</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-filter" data-filter="maintenance" style="padding: 3px 6px; font-size: 0.72rem;">Maint</button>
                </div>
            </div>

            <!-- Scrollable Vehicle List -->
            <div class="overflow-auto flex-grow-1" id="vehicleList" style="padding: 8px;">
                <?php foreach ($vehicles as $v): ?>
                    <div class="vehicle-item p-2 mb-2 rounded border bg-white" 
                         id="card-<?= $v['id'] ?>"
                         data-id="<?= $v['id'] ?>"
                         data-status="<?= $v['status'] ?>"
                         data-code="<?= esc(strtolower($v['vehicle_code'])) ?>"
                         data-plate="<?= esc(strtolower($v['plate_number'])) ?>"
                         data-driver="<?= esc(strtolower($v['driver_name'] ?? '')) ?>"
                         data-lat="<?= $v['current_latitude'] ?>"
                         data-lng="<?= $v['current_longitude'] ?>"
                         onclick="focusVehicle(<?= $v['id'] ?>)"
                         style="cursor: pointer; transition: all 0.15s ease;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="mono fw-bold" style="font-size: 0.82rem; color: var(--text-heading);"><?= esc($v['vehicle_code']) ?></span>
                            <span class="status-badge status-<?= esc($v['status']) ?>" style="font-size: 0.65rem; padding: 2px 6px;">
                                <?= ucfirst(str_replace('_', ' ', $v['status'])) ?>
                            </span>
                        </div>
                        <div class="text-muted" style="font-size: 0.74rem; margin-bottom: 4px;">
                            <?= esc($v['make'] . ' ' . $v['model']) ?> &bull; <span class="mono"><?= esc($v['plate_number']) ?></span>
                        </div>
                        <div class="small text-truncate mb-2" style="font-size: 0.7rem; color: #475569;">
                            <i class="fa-solid fa-location-dot text-danger me-1"></i> <span id="geo-<?= $v['id'] ?>"><?= esc($v['current_geofence'] ?? 'Metro Manila Corridor') ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-1 border-top" style="font-size: 0.72rem; color: var(--text-muted);">
                            <span><i class="fa-solid fa-gauge-high text-primary me-1"></i> <span id="speed-<?= $v['id'] ?>" class="mono fw-semibold text-dark"><?= $v['current_speed'] ?></span> km/h</span>
                            <span><i class="fa-solid fa-gas-pump text-warning me-1"></i> <span id="fuel-<?= $v['id'] ?>" class="mono fw-semibold text-dark"><?= $v['current_fuel_level'] ?></span>%</span>
                            <?php if (!empty($v['driver_name'])): ?>
                                <span class="text-truncate" style="max-width: 80px;" title="<?= esc($v['driver_name']) ?>"><i class="fa-solid fa-user me-1"></i> <?= esc($v['driver_name']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Map Column -->
    <div class="col-lg-8 col-xl-9">
        <div class="card-panel" style="height: calc(100vh - 180px); overflow: hidden; position: relative;">
            <div id="telematicsMap" style="height: 100%; width: 100%;"></div>
            
            <!-- Map Overlay: Active Vehicle Trail & Navigation Route Panel -->
            <div id="trailInfoPanel" style="display: none; position: absolute; top: 16px; left: 16px; z-index: 500; background: rgba(255, 255, 255, 0.97); backdrop-filter: blur(8px); padding: 12px 15px; border-radius: 10px; border: 1px solid var(--border-subtle); box-shadow: 0 6px 18px rgba(15,23,42,0.12); font-size: 0.75rem; max-width: 340px;">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <strong class="mono" id="trailVehicleCode" style="color: var(--primary); font-size: 0.82rem;">FLT-00</strong>
                    <button type="button" class="btn-close" style="font-size: 0.6rem;" onclick="clearActiveTrail()" aria-label="Close route panel"></button>
                </div>
                <div id="trailDetails" class="text-muted mb-1" style="font-size: 0.72rem;">Displaying GPS breadcrumb trajectory</div>
                <div id="routeGuidanceBox" style="display: none; margin-top: 6px; padding-top: 6px; border-top: 1px solid #e2e8f0;">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 mono" id="routeDistBadge">0.0 km</span>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 mono" id="routeEtaBadge">0 min ETA</span>
                    </div>
                    <div class="text-dark fw-semibold text-truncate" id="routeDestLabel" style="font-size: 0.73rem;"></div>
                    <div class="text-secondary mt-1" id="routeNextStep" style="font-size: 0.69rem;"></div>
                </div>
            </div>

            <!-- Map Overlay Legend -->
            <div style="position: absolute; bottom: 16px; right: 16px; z-index: 500; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(4px); padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-subtle); box-shadow: 0 2px 6px rgba(0,0,0,0.05); font-size: 0.72rem;">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                    <span class="mono text-muted text-uppercase" style="font-size: 0.65rem; font-weight: 600;">PIA Motorpool Radar</span>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 mono" style="font-size: 0.6rem;">OSRM Road-Snapped</span>
                </div>
                <div class="d-flex gap-3 align-items-center flex-wrap">
                    <span><span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#2563eb; margin-right:4px;"></span>Active</span>
                    <span><span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#10b981; margin-right:4px;"></span>In Transit</span>
                    <span><span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#f59e0b; margin-right:4px;"></span>Maintenance</span>
                    <span><span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#ef4444; margin-right:4px;"></span>Disabled</span>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    let vehicles = <?= json_encode($vehicles) ?: '[]' ?>;
    let initialLoad = true;
    let selectedVehicleId = null;
    let isStreamActive = true;
    let streamTimer = null;

    // OpenStreetMap / CartoDB Voyager Street Layer (Keyless, Zero API Key Errors)
    const streetLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        maxZoom: 19,
        subdomains: 'abcd',
        attribution: '&copy; OpenStreetMap &copy; CARTO'
    });

    // Esri World Imagery Satellite Layer
    const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19,
        attribution: '&copy; Esri World Imagery'
    });

    const map = L.map('telematicsMap', {
        zoomControl: true,
        attributionControl: true
    }).setView([14.6572, 121.0450], 12);

    streetLayer.addTo(map);

    let markers = {};
    const markerGroup = L.featureGroup().addTo(map);
    const trailGroup  = L.featureGroup().addTo(map);

    // Mark PIA Central Office Motorpool Compound
    const motorpoolIcon = L.divIcon({
        className: 'pia-hq-pin',
        html: `<div title="PIA Central Office Motorpool (Visayas Ave, QC)" style="background:#0f172a;color:#fff;border-radius:50%;width:26px;height:26px;display:flex;align-items:center;justify-content:center;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,0.3);font-size:11px;">
                <i class="fa-solid fa-building-shield"></i>
               </div>`,
        iconSize: [26, 26],
        iconAnchor: [13, 13]
    });
    L.marker([14.6572, 121.0450], { icon: motorpoolIcon })
        .bindPopup('<strong>PIA Central Office Motorpool</strong><br><span class="text-muted small">Visayas Ave, Diliman, Quezon City</span>')
        .addTo(map);

    function setMapLayer(type) {
        document.getElementById('btnMapRoad').classList.toggle('active', type === 'street');
        document.getElementById('btnMapSat').classList.toggle('active', type === 'satellite');

        if (type === 'satellite') {
            map.removeLayer(streetLayer);
            satelliteLayer.addTo(map);
        } else {
            map.removeLayer(satelliteLayer);
            streetLayer.addTo(map);
        }
    }

    function getPinColor(status) {
        switch(status) {
            case 'in_transit': return '#10b981';
            case 'active': return '#2563eb';
            case 'maintenance':
            case 'under_maintenance': return '#f59e0b';
            case 'out_of_service':
            case 'disabled_breakdown': return '#ef4444';
            default: return '#64748b';
        }
    }

    function buildMarkerIcon(v, isSelected) {
        const color = getPinColor(v.status);
        const size  = isSelected ? 40 : 34;
        const half  = size / 2;
        const isTransit = v.status === 'in_transit';
        const headingBadge = (isTransit && v.heading)
            ? `<span style="position:absolute;top:-5px;right:-5px;width:14px;height:14px;border-radius:50%;background:#0f172a;color:#fff;font-size:8px;display:flex;align-items:center;justify-content:center;border:1.5px solid #fff;transform:rotate(${Math.round(v.heading)}deg);"><i class="fa-solid fa-arrow-up"></i></span>`
            : '';

        return L.divIcon({
            className: 'custom-fleet-pin' + (isTransit ? ' in-transit-pulse' : ''),
            html: `<div style="position:relative;background-color:${color};color:#ffffff;border-radius:8px;width:${size}px;height:${size}px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 10px rgba(0,0,0,0.25);border:${isSelected ? '3px solid #facc15' : '2px solid #ffffff'};cursor:pointer;transition:all 0.15s ease;">
                    <i class="fa-solid fa-car-side" style="font-size:${isSelected ? 16 : 13}px;"></i>
                    ${headingBadge}
                   </div>`,
            iconSize: [size, size],
            iconAnchor: [half, half]
        });
    }

    function buildPopupHtml(v) {
        return `
            <div style="min-width: 235px; font-family: var(--font-sans);">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <strong class="mono" style="font-size: 0.88rem; color: #0f172a;">${v.vehicle_code}</strong>
                    <span class="status-badge status-${v.status}" style="font-size: 0.65rem;">${v.status.replace('_', ' ')}</span>
                </div>
                <div style="font-size: 0.76rem; color: #64748b; margin-bottom: 6px;">${v.make} ${v.model} &bull; <span class="mono">${v.plate_number}</span></div>
                
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px; margin-bottom: 8px; font-size: 0.75rem;">
                    <div class="d-flex justify-content-between mb-1"><span>Speed / Heading:</span> <strong class="mono">${v.current_speed} km/h ${v.heading ? '&bull; ' + Math.round(v.heading) + '&deg;' : ''}</strong></div>
                    <div class="d-flex justify-content-between mb-1"><span>Fuel Level:</span> <strong class="mono">${v.current_fuel_level}%</strong></div>
                    <div class="d-flex justify-content-between mb-1"><span>Zone:</span> <strong class="text-truncate" style="max-width: 130px;">${v.current_geofence || 'Corridor'}</strong></div>
                    <div class="d-flex justify-content-between mb-1"><span>Driver:</span> <strong>${v.driver_name || 'Unassigned'}</strong></div>
                    ${v.trip_number ? `<div class="pt-1 mt-1 border-top"><div class="d-flex justify-content-between"><span>e-DTT:</span> <a href="<?= base_url('tickets/') ?>/${v.active_ticket_id || ''}" class="mono fw-bold text-primary text-decoration-none">${v.trip_number}</a></div><div class="text-truncate text-dark fw-semibold" style="max-width: 205px; font-size: 0.71rem;"><i class="fa-solid fa-flag-checkered text-success me-1"></i>${v.destination_address || 'Official Travel'}</div></div>` : ''}
                </div>

                <div class="d-flex gap-2">
                    <button onclick="loadVehicleTrail(${v.id})" class="btn btn-xs btn-primary flex-grow-1 text-center">
                        <i class="fa-solid fa-route me-1"></i> Route &amp; Trail
                    </button>
                    <a href="<?= base_url('vehicles/') ?>/${v.id}" class="btn btn-xs btn-secondary flex-grow-1 text-center text-decoration-none">
                        Profile &rarr;
                    </a>
                </div>
            </div>
        `;
    }

    function renderMarkers() {
        const seenIds = new Set();

        // 1. Detect overlapping coordinates (e.g. vehicles parked at the PIA Motorpool compound)
        const coordGroups = {};
        vehicles.forEach(v => {
            if (!v.current_latitude || !v.current_longitude) return;
            const key = parseFloat(v.current_latitude).toFixed(4) + ',' + parseFloat(v.current_longitude).toFixed(4);
            if (!coordGroups[key]) coordGroups[key] = [];
            coordGroups[key].push(v);
        });

        const offsetIndex = {};

        vehicles.forEach(v => {
            if (!v.current_latitude || !v.current_longitude) return;
            seenIds.add(String(v.id));

            let lat = parseFloat(v.current_latitude);
            let lng = parseFloat(v.current_longitude);
            const key = lat.toFixed(4) + ',' + lng.toFixed(4);
            const cluster = coordGroups[key] || [];

            // Disperse overlapping vehicles into dedicated parking slots (~18-20m radius)
            if (cluster.length > 1) {
                const idx = offsetIndex[key] || 0;
                offsetIndex[key] = idx + 1;
                const angle = (2 * Math.PI / cluster.length) * idx;
                const radius = 0.00018;
                lat += radius * Math.cos(angle);
                lng += radius * Math.sin(angle);
            }

            const latLng     = [lat, lng];
            const isSelected = selectedVehicleId == v.id;
            const icon       = buildMarkerIcon(v, isSelected);
            const popupHtml  = buildPopupHtml(v);

            if (markers[v.id]) {
                // Smoothly update position and icon in place without closing an open popup
                markers[v.id].setLatLng(latLng);
                markers[v.id].setIcon(icon);
                markers[v.id].setPopupContent(popupHtml);
            } else {
                const marker = L.marker(latLng, { icon: icon });
                marker.bindPopup(popupHtml);
                marker.on('click', () => focusVehicle(v.id, false));
                markerGroup.addLayer(marker);
                markers[v.id] = marker;
            }
        });

        // Remove stale markers if any vehicle was removed
        Object.keys(markers).forEach(id => {
            if (!seenIds.has(String(id))) {
                markerGroup.removeLayer(markers[id]);
                delete markers[id];
            }
        });

        if (initialLoad && markerGroup.getLayers().length > 0) {
            map.fitBounds(markerGroup.getBounds().pad(0.15));
            initialLoad = false;
        }
    }

    renderMarkers();
    setTimeout(() => { if (typeof map !== 'undefined') map.invalidateSize(); }, 300);

    // Deep-linking: auto-focus vehicle from query string (e.g. /tracking?vehicle_id=3)
    const urlParams = new URLSearchParams(window.location.search);
    const targetVehicleId = urlParams.get('vehicle_id');
    if (targetVehicleId) {
        setTimeout(() => focusVehicle(parseInt(targetVehicleId, 10)), 350);
    }

    function focusVehicle(id, pan = true) {
        selectedVehicleId = id;
        const v = vehicles.find(item => item.id == id);
        
        document.querySelectorAll('.vehicle-item').forEach(card => card.classList.remove('border-primary', 'shadow-sm'));
        const activeCard = document.getElementById(`card-${id}`);
        if (activeCard) {
            activeCard.classList.add('border-primary', 'shadow-sm');
            activeCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        renderMarkers();

        if (v && markers[id]) {
            if (pan) {
                map.setView([parseFloat(v.current_latitude), parseFloat(v.current_longitude)], 14, { animate: true });
            }
            markers[id].openPopup();
            loadVehicleTrail(id);
        }
    }

    async function loadVehicleTrail(vehicleId) {
        const v = vehicles.find(item => item.id == vehicleId);
        if (!v) return;

        try {
            trailGroup.clearLayers();

            const res = await fetch(`<?= base_url('api/v1/tracking/') ?>/${vehicleId}/trail?limit=50`);
            const data = await res.json();

            const panel     = document.getElementById('trailInfoPanel');
            const routeBox  = document.getElementById('routeGuidanceBox');
            document.getElementById('trailVehicleCode').innerText = `${v.vehicle_code} (${v.plate_number})`;

            // 1. Draw Historical GPS Breadcrumb Trail (Solid Blue)
            if (data.status === 'success' && data.coordinates && data.coordinates.length > 1) {
                L.polyline(data.coordinates, {
                    color: '#2563eb',
                    weight: 4,
                    opacity: 0.85,
                    lineJoin: 'round'
                }).addTo(trailGroup);

                data.coordinates.forEach((coord, idx) => {
                    if (idx % 2 === 0) {
                        L.circleMarker(coord, {
                            radius: 3,
                            fillColor: '#3b82f6',
                            color: '#ffffff',
                            weight: 1,
                            fillOpacity: 0.9
                        }).addTo(trailGroup);
                    }
                });
                document.getElementById('trailDetails').innerText = `${data.point_count} GPS breadcrumbs recorded`;
            } else {
                document.getElementById('trailDetails').innerText = 'Live GPS position locked';
            }

            // 2. Draw Road-Snapped Planned Navigation Route & Destination Pin (Emerald Green)
            if (data.planned_route && data.planned_route.leaflet_coords && data.planned_route.leaflet_coords.length > 1) {
                const pr = data.planned_route;

                L.polyline(pr.leaflet_coords, {
                    color: '#059669',
                    weight: 5,
                    opacity: 0.9,
                    dashArray: '10, 6',
                    lineJoin: 'round'
                }).addTo(trailGroup);

                const destIcon = L.divIcon({
                    className: 'dest-flag-pin',
                    html: `<div style="background:#dc2626;color:#fff;border-radius:50%;width:30px;height:30px;display:flex;align-items:center;justify-content:center;border:2px solid #fff;box-shadow:0 3px 8px rgba(0,0,0,0.3);font-size:13px;">
                            <i class="fa-solid fa-flag-checkered"></i>
                           </div>`,
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                });

                L.marker([pr.destination_lat, pr.destination_lng], { icon: destIcon })
                    .bindPopup(`<strong>Authorized Destination</strong><br><span class="small">${pr.destination_address}</span><br><span class="mono small text-success">${pr.distance_km} km &bull; ~${pr.duration_min} min ETA</span>`)
                    .addTo(trailGroup);

                document.getElementById('routeDistBadge').innerText = `${pr.distance_km} km road route`;
                document.getElementById('routeEtaBadge').innerText  = `~${pr.duration_min} min ETA`;
                document.getElementById('routeDestLabel').innerHTML = `<i class="fa-solid fa-flag-checkered text-danger me-1"></i>${pr.destination_address}`;

                const nextStep = (pr.steps && pr.steps.length > 0) ? pr.steps[0].instruction : 'Follow arterial road route to destination';
                document.getElementById('routeNextStep').innerHTML  = `<i class="fa-solid fa-diamond-turn-right text-primary me-1"></i>${nextStep}`;
                routeBox.style.display = 'block';
            } else {
                routeBox.style.display = 'none';
            }

            panel.style.display = 'block';
        } catch (err) {
            console.warn('Could not load vehicle trail:', err);
        }
    }

    function clearActiveTrail() {
        trailGroup.clearLayers();
        document.getElementById('trailInfoPanel').style.display = 'none';
        selectedVehicleId = null;
        document.querySelectorAll('.vehicle-item').forEach(card => card.classList.remove('border-primary', 'shadow-sm'));
        renderMarkers();
    }

    function applyFilters() {
        const activeFilter = document.querySelector('.btn-filter.active').getAttribute('data-filter');
        const searchTerm = document.getElementById('filterSearch').value.toLowerCase().trim();

        document.querySelectorAll('.vehicle-item').forEach(item => {
            const statusMatch = (activeFilter === 'all' || item.getAttribute('data-status') === activeFilter);
            const textMatch = !searchTerm || 
                              item.getAttribute('data-code').includes(searchTerm) || 
                              item.getAttribute('data-plate').includes(searchTerm) ||
                              item.getAttribute('data-driver').includes(searchTerm);

            item.style.display = (statusMatch && textMatch) ? 'block' : 'none';
        });
    }

    document.querySelectorAll('.btn-filter').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.btn-filter').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            applyFilters();
        });
    });

    document.getElementById('filterSearch').addEventListener('input', applyFilters);

    async function refreshData(silent = false) {
        try {
            const res = await fetch('<?= base_url('api/v1/tracking') ?>');
            const data = await res.json();
            if (data.status === 'success') {
                vehicles = data.vehicles;
                renderMarkers();
                
                vehicles.forEach(v => {
                    const speedEl = document.getElementById(`speed-${v.id}`);
                    const fuelEl  = document.getElementById(`fuel-${v.id}`);
                    const geoEl   = document.getElementById(`geo-${v.id}`);
                    if (speedEl) speedEl.innerText = v.current_speed;
                    if (fuelEl) fuelEl.innerText = v.current_fuel_level;
                    if (geoEl && v.current_geofence) geoEl.innerText = v.current_geofence;
                });

                if (selectedVehicleId) {
                    loadVehicleTrail(selectedVehicleId);
                }
            }
        } catch(e) {
            if (!silent) console.error('Failed to sync telemetry:', e);
        }
    }

    document.getElementById('btnRefresh').addEventListener('click', () => refreshData(false));

    document.getElementById('btnFitBounds').addEventListener('click', function() {
        if (markerGroup.getLayers().length > 0) {
            map.fitBounds(markerGroup.getBounds().pad(0.12));
        }
    });

    const btnAutoStream = document.getElementById('btnAutoStream');
    const streamIcon    = document.getElementById('streamIcon');
    const streamLabel   = document.getElementById('streamLabel');

    function toggleAutoStream() {
        isStreamActive = !isStreamActive;
        if (isStreamActive) {
            streamIcon.className = 'fa-solid fa-tower-broadcast text-success';
            streamLabel.innerText = 'Stream: ON (5s)';
            startStreamTimer();
        } else {
            streamIcon.className = 'fa-solid fa-tower-broadcast text-muted';
            streamLabel.innerText = 'Stream: PAUSED';
            clearInterval(streamTimer);
        }
    }

    function startStreamTimer() {
        clearInterval(streamTimer);
        streamTimer = setInterval(() => {
            if (isStreamActive) {
                refreshData(true);
            }
        }, 5000);
    }

    btnAutoStream.addEventListener('click', toggleAutoStream);
    startStreamTimer();

    document.getElementById('btnSimulate').addEventListener('click', async function() {
        const btn = this;
        btn.disabled = true;
        const originalContent = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Computing...</span>';

        try {
            await fetch('<?= base_url('api/v1/tracking/simulate-step') ?>', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            await refreshData(false);
        } catch (e) {
            console.error(e);
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalContent;
        }
    });
</script>
<?= $this->endSection() ?>
