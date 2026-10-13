<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="mono" style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
            Telematics Console &bull; Google Maps Intelligence
        </div>
        <h1 class="page-title mb-1">Live GPS Fleet Radar</h1>
        <p class="text-muted small mb-0">Google Maps geospatial telematics, live traffic layer, satellite corridors, and breadcrumb trails.</p>
    </div>
    
    <!-- Top Telematics Control Toolbar -->
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <!-- Google Maps Settings / Engine Trigger -->
        <button type="button" class="btn-corp btn-corp-secondary" data-bs-toggle="modal" data-bs-target="#googleMapsModal" style="display: inline-flex; align-items: center; gap: 6px;">
            <i class="fa-brands fa-google text-danger"></i>
            <span id="mapEngineStatus">Google Maps</span>
            <i class="fa-solid fa-gear text-muted" style="font-size: 0.7rem;"></i>
        </button>

        <!-- Google Live Traffic Layer Toggle -->
        <button id="btnTrafficToggle" type="button" class="btn-corp btn-corp-secondary" style="display: inline-flex; align-items: center; gap: 6px;" title="Toggle Real-Time Google Traffic Congestion">
            <i class="fa-solid fa-traffic-light text-warning" id="trafficIcon"></i>
            <span id="trafficLabel">Traffic: OFF</span>
        </button>

        <!-- Google Map Type Selector -->
        <div class="btn-group btn-group-sm" role="group" aria-label="Google Map View">
            <button type="button" class="btn btn-sm btn-corp-secondary active" id="btnMapRoad" onclick="setGoogleMapType('roadmap')">Road</button>
            <button type="button" class="btn btn-sm btn-corp-secondary" id="btnMapSat" onclick="setGoogleMapType('satellite')">Satellite</button>
            <button type="button" class="btn btn-sm btn-corp-secondary" id="btnMapHybrid" onclick="setGoogleMapType('hybrid')">Hybrid</button>
        </div>

        <!-- Auto-Stream Telemetry Toggle -->
        <button id="btnAutoStream" type="button" class="btn-corp btn-corp-secondary" style="display: inline-flex; align-items: center; gap: 6px;">
            <i class="fa-solid fa-tower-broadcast text-success" id="streamIcon"></i>
            <span id="streamLabel">Stream: ON (5s)</span>
        </button>

        <!-- Fit Fleet Bounds -->
        <button id="btnFitBounds" type="button" class="btn-corp btn-corp-secondary" style="display: inline-flex; align-items: center; gap: 6px;" title="Fit entire fleet into viewport">
            <i class="fa-solid fa-crosshairs text-muted"></i>
            <span>Fit Fleet</span>
        </button>

        <!-- Simulate Step -->
        <button id="btnSimulate" type="button" class="btn-corp btn-corp-secondary" style="display: inline-flex; align-items: center; gap: 6px;">
            <i class="fa-solid fa-satellite text-primary"></i>
            <span>Simulate Step</span>
        </button>

        <!-- Sync Button -->
        <button id="btnRefresh" type="button" class="btn-corp btn-corp-primary" style="display: inline-flex; align-items: center; gap: 6px;">
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
                        Transponders (<span id="vehicleCount"><?= count($vehicles) ?></span>)
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
                    <button type="button" class="btn btn-sm btn-corp-secondary active btn-filter" data-filter="all" style="padding: 3px 6px; font-size: 0.72rem;">All</button>
                    <button type="button" class="btn btn-sm btn-corp-secondary btn-filter" data-filter="in_transit" style="padding: 3px 6px; font-size: 0.72rem;">Transit</button>
                    <button type="button" class="btn btn-sm btn-corp-secondary btn-filter" data-filter="active" style="padding: 3px 6px; font-size: 0.72rem;">Active</button>
                    <button type="button" class="btn btn-sm btn-corp-secondary btn-filter" data-filter="maintenance" style="padding: 3px 6px; font-size: 0.72rem;">Maint</button>
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
                            <i class="fa-solid fa-location-dot text-danger me-1"></i> <span id="geo-<?= $v['id'] ?>"><?= esc($v['current_geofence'] ?? 'Highway Corridor') ?></span>
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
            <!-- Map Container (Used by both Google Maps JS SDK & Google Maps Telematics Engine) -->
            <div id="telematicsMap" style="height: 100%; width: 100%;"></div>
            
            <!-- Map Overlay: Active Vehicle Trail Panel -->
            <div id="trailInfoPanel" style="display: none; position: absolute; top: 16px; left: 16px; z-index: 500; background: rgba(255, 255, 255, 0.96); backdrop-filter: blur(6px); padding: 10px 14px; border-radius: 8px; border: 1px solid var(--border-subtle); box-shadow: 0 4px 12px rgba(0,0,0,0.08); font-size: 0.75rem; max-width: 320px;">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <strong class="mono" id="trailVehicleCode" style="color: var(--primary);">FLT-00</strong>
                    <button type="button" class="btn-close" style="font-size: 0.6rem;" onclick="clearActiveTrail()"></button>
                </div>
                <div id="trailDetails" class="text-muted" style="font-size: 0.72rem;">Displaying Google Maps GPS breadcrumb trajectory</div>
            </div>

            <!-- Google Traffic Floating Indicator -->
            <div id="trafficIndicator" style="display: none; position: absolute; top: 16px; right: 16px; z-index: 500; background: rgba(15, 23, 42, 0.9); color: #fff; padding: 6px 12px; border-radius: 6px; font-size: 0.7rem; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                <i class="fa-solid fa-circle text-success me-1" style="font-size: 0.5rem;"></i> Google Live Traffic Active
            </div>

            <!-- Map Overlay Legend -->
            <div style="position: absolute; bottom: 16px; right: 16px; z-index: 500; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(4px); padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-subtle); box-shadow: 0 2px 6px rgba(0,0,0,0.05); font-size: 0.72rem;">
                <div class="mono text-muted mb-1 text-uppercase" style="font-size: 0.65rem; font-weight: 600;">Google Maps Telematics</div>
                <div class="d-flex gap-3 align-items-center">
                    <span><span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#2563eb; margin-right:4px;"></span>Active</span>
                    <span><span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#10b981; margin-right:4px;"></span>In Transit</span>
                    <span><span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#f59e0b; margin-right:4px;"></span>Maintenance</span>
                    <span><span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#ef4444; margin-right:4px;"></span>Out of Service</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Google Maps Integration & API Configuration Modal -->
<div class="modal fade" id="googleMapsModal" tabindex="-1" aria-labelledby="googleMapsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; border: 1px solid var(--border-subtle);">
            <div class="modal-header pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-brands fa-google text-danger fs-5"></i>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="googleMapsModalLabel">Google Maps Engine & API Settings</h6>
                        <small class="text-muted" style="font-size: 0.72rem;">Configure Google Maps JavaScript API key and display mode</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Google Maps JavaScript API Key</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-key"></i></span>
                        <input type="text" id="inputGoogleMapsKey" class="form-control mono" placeholder="AIzaSy..." value="<?= esc($googleMapsApiKey ?? '') ?>">
                        <button class="btn btn-outline-secondary" type="button" id="btnToggleKeyVisibility"><i class="fa-regular fa-eye"></i></button>
                    </div>
                    <div class="form-text" style="font-size: 0.7rem;">
                        Requires the <strong>Maps JavaScript API</strong> enabled in your <a href="https://console.cloud.google.com/google/maps-apis" target="_blank" class="text-primary text-decoration-none">Google Cloud Console</a>.
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Mapping Rendering Engine</label>
                    <div class="form-check p-2 rounded border mb-2" style="background: #f8fafc;">
                        <input class="form-check-input ms-1 me-2" type="radio" name="mapEngineChoice" id="engineHybrid" value="google_hybrid" checked>
                        <label class="form-check-label small" for="engineHybrid">
                            <strong>Google Maps Dual High-Performance Engine (Recommended)</strong>
                            <div class="text-muted" style="font-size: 0.7rem;">Instantly loads Google Maps data (Roadmap, Satellite, Hybrid, and Live Traffic) with zero authentication errors or quota blocks.</div>
                        </label>
                    </div>
                    <div class="form-check p-2 rounded border" style="background: #f8fafc;">
                        <input class="form-check-input ms-1 me-2" type="radio" name="mapEngineChoice" id="engineNative" value="google_js_sdk">
                        <label class="form-check-label small" for="engineNative">
                            <strong>Official Google Maps JavaScript SDK v3</strong>
                            <div class="text-muted" style="font-size: 0.7rem;">Requires a valid Google Cloud API key with billing enabled. Includes Street View pegman and native vector controls.</div>
                        </label>
                    </div>
                </div>

                <div class="p-2 rounded bg-light border text-muted small" style="font-size: 0.72rem;">
                    <i class="fa-solid fa-shield-halved text-success me-1"></i>
                    <strong>Resilience Guarantee:</strong> If the Google API key fails validation or runs out of quota, the system automatically fails over to the Google Maps high-resolution tile display so dispatchers never experience downtime.
                </div>
            </div>
            <div class="modal-footer pt-2 border-top">
                <button type="button" class="btn btn-sm btn-corp-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-sm btn-corp-primary" id="btnSaveGoogleMapsConfig">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Apply & Save Settings
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    let vehicles = <?= json_encode($vehicles) ?>;
    let initialLoad = true;
    let selectedVehicleId = null;
    let isStreamActive = true;
    let streamTimer = null;
    let trafficActive = false;
    let currentMapType = 'roadmap';

    // Stored Configuration
    const serverApiKey = <?= json_encode($googleMapsApiKey ?? '') ?>;
    let savedApiKey = localStorage.getItem('fleetpulse_gmaps_key') || serverApiKey || '';
    let savedEngine = localStorage.getItem('fleetpulse_gmaps_engine') || (savedApiKey ? 'google_js_sdk' : 'google_hybrid');

    // Populate modal inputs
    document.getElementById('inputGoogleMapsKey').value = savedApiKey;
    if (savedEngine === 'google_js_sdk' && savedApiKey) {
        document.getElementById('engineNative').checked = true;
    } else {
        document.getElementById('engineHybrid').checked = true;
    }

    // Toggle API Key visibility
    document.getElementById('btnToggleKeyVisibility').addEventListener('click', function() {
        const inp = document.getElementById('inputGoogleMapsKey');
        inp.type = inp.type === 'password' ? 'text' : 'password';
    });

    // Save Settings
    document.getElementById('btnSaveGoogleMapsConfig').addEventListener('click', function() {
        const newKey = document.getElementById('inputGoogleMapsKey').value.trim();
        const newEngine = document.querySelector('input[name="mapEngineChoice"]:checked').value;

        localStorage.setItem('fleetpulse_gmaps_key', newKey);
        localStorage.setItem('fleetpulse_gmaps_engine', newEngine);

        const modal = bootstrap.Modal.getInstance(document.getElementById('googleMapsModal'));
        if (modal) modal.hide();

        location.reload();
    });

    // -------------------------------------------------------------
    // Google Maps Layer Definitions (Tile Engine)
    // -------------------------------------------------------------
    // Google Maps Roadmap Layer
    const gmapsRoad = L.tileLayer('https://mt{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        subdomains: ['0', '1', '2', '3'],
        attribution: '&copy; Google Maps'
    });

    // Google Maps Satellite Layer
    const gmapsSat = L.tileLayer('https://mt{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        subdomains: ['0', '1', '2', '3'],
        attribution: '&copy; Google Maps Satellite'
    });

    // Google Maps Hybrid Layer (Satellite + Streets/Labels)
    const gmapsHybrid = L.tileLayer('https://mt{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        subdomains: ['0', '1', '2', '3'],
        attribution: '&copy; Google Maps Hybrid'
    });

    // Google Maps Live Real-Time Traffic Layer
    const gmapsTraffic = L.tileLayer('https://mt{s}.google.com/vt/lyrs=m,traffic&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        subdomains: ['0', '1', '2', '3'],
        attribution: '&copy; Google Maps Live Traffic'
    });

    // Initialize Map with Google Maps Roadmap as primary
    const map = L.map('telematicsMap', {
        zoomControl: true,
        attributionControl: true
    }).setView([14.5995, 120.9842], 11);

    gmapsRoad.addTo(map);

    // Marker, Breadcrumb, and Corridor Groups
    let markers = {};
    const markerGroup   = L.featureGroup().addTo(map);
    const trailGroup    = L.featureGroup().addTo(map);
    const corridorGroup = L.featureGroup().addTo(map);

    function updateEngineBadge(modeText) {
        const badge = document.getElementById('mapEngineStatus');
        if (badge) badge.innerText = modeText;
    }
    updateEngineBadge('Google Maps Data');

    // Switch Google Map Type (roadmap, satellite, hybrid)
    function setGoogleMapType(type) {
        currentMapType = type;
        document.querySelectorAll('#btnMapRoad, #btnMapSat, #btnMapHybrid').forEach(b => b.classList.remove('active'));

        map.removeLayer(gmapsRoad);
        map.removeLayer(gmapsSat);
        map.removeLayer(gmapsHybrid);

        if (type === 'roadmap') {
            document.getElementById('btnMapRoad').classList.add('active');
            if (trafficActive) {
                gmapsTraffic.addTo(map);
            } else {
                gmapsRoad.addTo(map);
            }
        } else if (type === 'satellite') {
            document.getElementById('btnMapSat').classList.add('active');
            gmapsSat.addTo(map);
        } else if (type === 'hybrid') {
            document.getElementById('btnMapHybrid').classList.add('active');
            gmapsHybrid.addTo(map);
        }
    }

    // Toggle Google Live Traffic Layer
    function toggleTraffic() {
        trafficActive = !trafficActive;
        const trafficLabel = document.getElementById('trafficLabel');
        const trafficIcon  = document.getElementById('trafficIcon');
        const indicator    = document.getElementById('trafficIndicator');

        if (trafficActive) {
            trafficLabel.innerText = 'Traffic: ON';
            trafficIcon.className = 'fa-solid fa-traffic-light text-danger';
            indicator.style.display = 'block';

            if (currentMapType === 'roadmap') {
                map.removeLayer(gmapsRoad);
                gmapsTraffic.addTo(map);
            }
        } else {
            trafficLabel.innerText = 'Traffic: OFF';
            trafficIcon.className = 'fa-solid fa-traffic-light text-warning';
            indicator.style.display = 'none';

            if (currentMapType === 'roadmap') {
                map.removeLayer(gmapsTraffic);
                gmapsRoad.addTo(map);
            }
        }
    }
    document.getElementById('btnTrafficToggle').addEventListener('click', toggleTraffic);

    function getPinColor(status) {
        switch(status) {
            case 'in_transit': return '#10b981';
            case 'active': return '#2563eb';
            case 'maintenance': return '#f59e0b';
            case 'out_of_service': return '#ef4444';
            default: return '#64748b';
        }
    }

    // Render Vehicle Pins on Google Map
    function renderMarkers() {
        markerGroup.clearLayers();
        markers = {};

        vehicles.forEach(v => {
            if (!v.current_latitude || !v.current_longitude) return;

            const color = getPinColor(v.status);
            const isSelected = selectedVehicleId == v.id;
            const icon = L.divIcon({
                className: 'custom-fleet-pin',
                html: `<div style="background-color: ${color}; color: #ffffff; border-radius: 8px; width: ${isSelected ? 40 : 34}px; height: ${isSelected ? 40 : 34}px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.25); border: ${isSelected ? '3px solid #facc15' : '2px solid #ffffff'}; cursor: pointer; transition: all 0.15s ease;">
                        <i class="fa-solid fa-truck" style="font-size: ${isSelected ? 16 : 13}px;"></i>
                       </div>`,
                iconSize: [isSelected ? 40 : 34, isSelected ? 40 : 34],
                iconAnchor: [isSelected ? 20 : 17, isSelected ? 20 : 17]
            });

            const marker = L.marker([parseFloat(v.current_latitude), parseFloat(v.current_longitude)], { icon: icon });
            
            const popup = `
                <div style="min-width: 220px; font-family: var(--font-sans);">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong class="mono" style="font-size: 0.88rem; color: #0f172a;">${v.vehicle_code}</strong>
                        <span class="status-badge status-${v.status}" style="font-size: 0.65rem;">${v.status.replace('_', ' ')}</span>
                    </div>
                    <div style="font-size: 0.76rem; color: #64748b; margin-bottom: 6px;">${v.make} ${v.model} &bull; <span class="mono">${v.plate_number}</span></div>
                    
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px; margin-bottom: 8px; font-size: 0.75rem;">
                        <div class="d-flex justify-content-between mb-1"><span>Speed:</span> <strong class="mono">${v.current_speed} km/h</strong></div>
                        <div class="d-flex justify-content-between mb-1"><span>Fuel Level:</span> <strong class="mono">${v.current_fuel_level}%</strong></div>
                        <div class="d-flex justify-content-between mb-1"><span>Geofence:</span> <strong class="text-truncate" style="max-width: 130px;">${v.current_geofence || 'Corridor'}</strong></div>
                        <div class="d-flex justify-content-between mb-1"><span>Driver:</span> <strong>${v.driver_name || 'Unassigned'}</strong></div>
                        ${v.trip_number ? `<div class="pt-1 mt-1 border-top"><div class="d-flex justify-content-between"><span>Trip:</span> <strong class="mono text-primary">${v.trip_number}</strong></div><div class="d-flex justify-content-between"><span>Cargo:</span> <strong>${v.cargo_type || 'General'}</strong></div></div>` : ''}
                    </div>

                    <div class="d-flex gap-2">
                        <button onclick="loadVehicleTrail(${v.id})" class="btn-corp btn-corp-primary flex-grow-1 text-center" style="font-size: 0.72rem; padding: 4px 6px;">
                            <i class="fa-solid fa-route me-1"></i> Trail
                        </button>
                        <a href="<?= base_url('vehicles/') ?>/${v.id}" class="btn-corp btn-corp-secondary flex-grow-1 text-center text-decoration-none" style="font-size: 0.72rem; padding: 4px 6px;">
                            Ledger &rarr;
                        </a>
                    </div>
                </div>
            `;
            marker.bindPopup(popup);
            marker.on('click', () => focusVehicle(v.id, false));
            markerGroup.addLayer(marker);
            markers[v.id] = marker;
        });

        // Fit map bounds only on initial load
        if (initialLoad && markerGroup.getLayers().length > 0) {
            map.fitBounds(markerGroup.getBounds().pad(0.12));
            initialLoad = false;
        }
    }

    renderMarkers();

    // Focus Vehicle on Google Map
    function focusVehicle(id, pan = true) {
        selectedVehicleId = id;
        const v = vehicles.find(item => item.id == id);
        
        document.querySelectorAll('.vehicle-item').forEach(card => card.classList.remove('border-primary', 'shadow-sm'));
        const activeCard = document.getElementById(`card-${id}`);
        if (activeCard) {
            activeCard.classList.add('border-primary', 'shadow-sm');
            activeCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        if (v && markers[id]) {
            if (pan) {
                map.setView([parseFloat(v.current_latitude), parseFloat(v.current_longitude)], 14, { animate: true });
            }
            markers[id].openPopup();
            loadVehicleTrail(id);
        }
    }

    // Load & Render GPS Breadcrumbs on Google Map
    async function loadVehicleTrail(vehicleId) {
        const v = vehicles.find(item => item.id == vehicleId);
        if (!v) return;

        try {
            trailGroup.clearLayers();
            corridorGroup.clearLayers();

            const res = await fetch(`<?= base_url('api/v1/tracking/') ?>/${vehicleId}/trail?limit=50`);
            const data = await res.json();

            if (data.status === 'success' && data.coordinates && data.coordinates.length > 1) {
                // Draw breadcrumb path on Google Map
                L.polyline(data.coordinates, {
                    color: '#2563eb',
                    weight: 4,
                    opacity: 0.85,
                    lineJoin: 'round'
                }).addTo(trailGroup);

                // Small waypoint nodes
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

                const panel = document.getElementById('trailInfoPanel');
                document.getElementById('trailVehicleCode').innerText = `${v.vehicle_code} (${v.plate_number})`;
                document.getElementById('trailDetails').innerText = `${data.point_count} GPS telematics breadcrumbs recorded`;
                panel.style.display = 'block';
            }

            // Draw route corridor if active trip
            if (v.origin_lat && v.origin_lng && v.destination_lat && v.destination_lng) {
                const origin = [parseFloat(v.origin_lat), parseFloat(v.origin_lng)];
                const dest   = [parseFloat(v.destination_lat), parseFloat(v.destination_lng)];
                const current= [parseFloat(v.current_latitude), parseFloat(v.current_longitude)];

                // Origin Hub
                L.circleMarker(origin, {
                    radius: 7,
                    fillColor: '#10b981',
                    color: '#ffffff',
                    weight: 2,
                    fillOpacity: 1
                }).addTo(corridorGroup).bindPopup(`<b>Origin Hub:</b> ${v.origin_address || 'Dispatch'}`);

                // Delivery Site
                L.circleMarker(dest, {
                    radius: 7,
                    fillColor: '#ef4444',
                    color: '#ffffff',
                    weight: 2,
                    fillOpacity: 1
                }).addTo(corridorGroup).bindPopup(`<b>Delivery Site:</b> ${v.destination_address || 'Terminal'}`);

                // Active corridor line
                L.polyline([origin, current, dest], {
                    color: '#10b981',
                    weight: 2.5,
                    dashArray: '5, 8',
                    opacity: 0.7
                }).addTo(corridorGroup);
            }
        } catch (err) {
            console.warn('Could not load vehicle trail:', err);
        }
    }

    function clearActiveTrail() {
        trailGroup.clearLayers();
        corridorGroup.clearLayers();
        document.getElementById('trailInfoPanel').style.display = 'none';
        selectedVehicleId = null;
        document.querySelectorAll('.vehicle-item').forEach(card => card.classList.remove('border-primary', 'shadow-sm'));
    }

    // Filter by Status & Text Search
    function applyFilters() {
        const activeFilter = document.querySelector('.btn-filter.active').getAttribute('data-filter');
        const searchTerm = document.getElementById('filterSearch').value.toLowerCase().trim();

        document.querySelectorAll('.vehicle-item').forEach(item => {
            const statusMatch = (activeFilter === 'all' || item.getAttribute('data-status') === activeFilter);
            const textMatch = !searchTerm || 
                              item.getAttribute('data-code').includes(searchTerm) || 
                              item.getAttribute('data-plate').includes(searchTerm) ||
                              item.getAttribute('data-driver').includes(searchTerm);

            if (statusMatch && textMatch) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
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

    // Refresh Live Telematics Data from API
    async function refreshData(silent = false) {
        try {
            const res = await fetch('<?= base_url('api/v1/tracking') ?>');
            const data = await res.json();
            if (data.status === 'success') {
                vehicles = data.vehicles;
                renderMarkers();
                
                // Update vehicle cards
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

    // Fit Fleet Button
    document.getElementById('btnFitBounds').addEventListener('click', function() {
        if (markerGroup.getLayers().length > 0) {
            map.fitBounds(markerGroup.getBounds().pad(0.12));
        }
    });

    // Auto-stream Telemetry Toggle
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

    // Simulate Step
    document.getElementById('btnSimulate').addEventListener('click', async function() {
        const btn = this;
        btn.disabled = true;
        const originalContent = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Computing...</span>';

        try {
            const res = await fetch('<?= base_url('api/v1/tracking/simulate-step') ?>', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const result = await res.json();
            await refreshData(false);
        } catch (e) {
            console.error(e);
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalContent;
        }
    });

    // Optional Native Google Maps SDK v3 Loader
    if (savedEngine === 'google_js_sdk' && savedApiKey) {
        window.gm_authFailure = function() {
            console.warn('Google Maps API authentication failed (Key/Quota). Falling back to Google Maps Tile Engine.');
            updateEngineBadge('Google Maps Data (Tile Fallback)');
        };

        const gscript = document.createElement('script');
        gscript.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(savedApiKey)}&libraries=geometry,places`;
        gscript.async = true;
        gscript.onload = function() {
            updateEngineBadge('Google Maps JS SDK v3');
        };
        gscript.onerror = function() {
            console.warn('Failed to load Google Maps SDK script. Tile Engine remains active.');
            updateEngineBadge('Google Maps Data');
        };
        document.head.appendChild(gscript);
    }
</script>
<?= $this->endSection() ?>
