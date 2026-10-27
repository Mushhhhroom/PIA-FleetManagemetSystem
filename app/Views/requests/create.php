<?php
/**
 * Vehicle Request Slip (VRS) Creation Form (ADMIN-F-018)
 *
 * @var string                     $title
 * @var list<array<string, mixed>> $offices
 * @var list<array<string, mixed>> $departments
 * @var list<array<string, mixed>> $drivers
 */
$offices     = $offices ?? [];
$departments = $departments ?? [];
$drivers     = $drivers ?? [];
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Breadcrumbs & Back -->
<div class="mb-3">
    <a href="<?= base_url('requests') ?>" class="text-decoration-none text-muted small">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Vehicle Request Slips
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <!-- Form Container Card -->
        <div class="card-panel">
            <!-- Government Header Banner -->
            <div class="card-panel-header bg-white d-flex align-items-center justify-content-between py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 1.1rem;">
                        <i class="fa-solid fa-landmark"></i>
                    </div>
                    <div>
                        <div class="mono" style="font-size: 0.68rem; color: #1e3a8a; text-transform: uppercase; letter-spacing: 0.08em;">
                            Republic of the Philippines &bull; Philippine Information Agency
                        </div>
                        <h4 class="mb-0 fw-bold text-dark d-flex align-items-center flex-wrap gap-2" style="font-size: 1.1rem;">
                            VEHICLE REQUEST SLIP (VRS)
                            <span class="policy-badge policy-badge-info" data-bs-toggle="tooltip" data-bs-placement="top" title="<strong>Vehicle Request Slip (ADMIN-F-018 rev2)</strong><br>The official requisition voucher required by PIA Fleet Management and COA Circular No. 75-6 before any government vehicle may be scheduled or dispatched." tabindex="0" role="button">
                                ADMIN-F-018 rev2 <i class="fa-solid fa-circle-question policy-info-icon"></i>
                            </span>
                        </h4>
                    </div>
                </div>
                <div class="text-end d-none d-md-block">
                    <span class="badge bg-light text-secondary border px-2 py-1 small">
                        <i class="fa-solid fa-shield-halved text-success me-1"></i> Official Business Only
                    </span>
                </div>
            </div>

            <!-- Form Body -->
            <form action="<?= base_url('requests') ?>" method="POST" enctype="multipart/form-data" id="vrsForm" class="p-4">
                <?= csrf_field() ?>

                <!-- Official Business Notice (BR-04) -->
                <div class="alert alert-info border-info-subtle p-3 mb-4 rounded-3 d-flex align-items-start gap-3">
                    <i class="fa-solid fa-circle-info text-info mt-1"></i>
                    <div class="small">
                        <strong>Official Business Restriction 
                            <span class="policy-badge policy-badge-info" data-bs-toggle="tooltip" data-bs-placement="top" title="<strong>Business Rule 04 (COA / CSC Rule)</strong><br>Strict prohibition on non-official vehicle usage, unauthorized civilian/family passengers, and personal deviations under Civil Service Commission and COA regulations." tabindex="0" role="button">
                                BR-04 <i class="fa-solid fa-circle-question policy-info-icon"></i>
                            </span>:
                        </strong> Under CSC and COA guidelines, government vehicles are dedicated exclusively to official state business. Non-official trips, unauthorized detours, or unauthorized passengers are strictly prohibited.
                    </div>
                </div>

                <!-- 1. Office, Department & Geographical Scope -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Office / Division Scope <span class="text-danger">*</span></label>
                        <select name="office_scope" id="office_scope" class="form-select form-select-sm" required>
                            <option value="central" selected>Central Office (Quezon City)</option>
                            <option value="regional">Regional Office (Field / Satellite)</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold text-dark">Originating Division / Office <span class="text-danger">*</span></label>
                        <select name="office_id" id="office_id" class="form-select form-select-sm" required>
                            <?php foreach ($offices as $off): ?>
                                <option value="<?= $off['id'] ?>" data-type="<?= $off['office_type'] ?>">
                                    [<?= esc($off['office_code']) ?>] <?= esc($off['office_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold text-dark">Department Information <span class="text-danger">*</span></label>
                        <select name="department_id" id="department_id" class="form-select form-select-sm" required>
                            <option value="" disabled selected>-- Select Department / Unit --</option>
                            <?php foreach ($departments as $dep): ?>
                                <option value="<?= $dep['id'] ?>">
                                    [<?= esc($dep['department_code']) ?>] <?= esc($dep['department_name']) ?>
                                    <?php if (!empty($dep['head_name'])): ?> &mdash; Head: <?= esc($dep['head_name']) ?><?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text" style="font-size: 0.72rem;">Department / unit requesting the vehicle. Used for trip-cost allocation and departmental reporting.</div>
                    </div>
                </div>

                <!-- 2. Destination & Purpose -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-semibold text-dark mb-0">Official Destination / Location <span class="text-danger">*</span></label>
                            <div class="d-flex gap-1 align-items-center" id="vrsRouteMetrics" style="display: none !important;">
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle mono" style="font-size: 0.65rem;"><i class="fa-solid fa-map-location-dot me-1"></i>Google Maps</span>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle mono" id="vrsDistBadge" style="font-size: 0.68rem;">0.0 km</span>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle mono" id="vrsEtaBadge" style="font-size: 0.68rem;">0 min ETA</span>
                            </div>
                        </div>
                        <input type="text" name="destination" id="destinationInput" class="form-control form-control-sm" placeholder="e.g. Malacañang Palace Press Briefing Room, Manila" required>
                        <div class="d-flex flex-wrap align-items-center gap-1 mt-2">
                            <span class="text-muted" style="font-size: 0.7rem;">Quick Landmarks:</span>
                            <button type="button" class="btn btn-xs btn-light border py-0 px-2 vrs-landmark-chip" style="font-size: 0.68rem;" data-dest="Malacañang Palace Press Briefing Room, Manila">Malacañang / PCO</button>
                            <button type="button" class="btn btn-xs btn-light border py-0 px-2 vrs-landmark-chip" style="font-size: 0.68rem;" data-dest="Senate of the Philippines, GSIS Bldg, Pasay City">Senate Pasay</button>
                            <button type="button" class="btn btn-xs btn-light border py-0 px-2 vrs-landmark-chip" style="font-size: 0.68rem;" data-dest="Batasang Pambansa Complex, Quezon City">Batasang Pambansa</button>
                            <button type="button" class="btn btn-xs btn-light border py-0 px-2 vrs-landmark-chip" style="font-size: 0.68rem;" data-dest="Commission on Audit (COA), Commonwealth Ave, Quezon City">COA QC</button>
                            <button type="button" class="btn btn-xs btn-light border py-0 px-2 vrs-landmark-chip" style="font-size: 0.68rem;" data-dest="Camp Aguinaldo (NDRRMC / OCD), Quezon City">Camp Aguinaldo</button>
                            <button type="button" class="btn btn-xs btn-light border py-0 px-2 vrs-landmark-chip" style="font-size: 0.68rem;" data-dest="NAIA Terminal 3, Pasay City">NAIA Terminal 3</button>
                        </div>

                        <!-- Live Google Maps Road Route Preview Map -->
                        <div class="rounded-3 border overflow-hidden mt-2" id="vrsMapWrapper" style="background: #f8fafc;">
                            <div id="vrsRoutePreviewMap" style="height: 200px; width: 100%; z-index: 1;"></div>
                            <div class="px-3 py-1 bg-white border-top d-flex justify-content-between align-items-center small" style="font-size: 0.72rem;">
                                <span id="vrsRouteSummary" class="text-muted">
                                    <i class="fa-solid fa-route text-primary me-1"></i> Select or type an event location above to preview the route from PIA Central Office.
                                </span>
                                <div class="d-flex align-items-center gap-2">
                                    <a id="vrsGoogleMapsLink" href="#" target="_blank" rel="noopener" class="btn btn-xs btn-outline-danger py-0 px-2 fw-semibold" style="font-size: 0.68rem; display: none;">
                                        <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Google Maps
                                    </a>
                                    <span class="mono text-muted" style="font-size: 0.65rem;">
                                        <i class="fa-solid fa-map-location-dot text-danger me-1"></i>Google Maps Engine
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold text-dark">Purpose of Travel & Coverage Details <span class="text-danger">*</span></label>
                        <textarea name="purpose" class="form-control form-control-sm" rows="3" placeholder="Provide detailed justification of the official travel, coverage agenda, or public information activity." required></textarea>
                    </div>
                </div>

                <!-- 3. Schedule & 24h Advance Notice Detector (BR-01 vs BR-03) -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Departure Date & Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="departure_time" id="departure_time" class="form-control form-control-sm" required>
                        <div class="form-text" style="font-size: 0.72rem;">Standard policy requires filing at least 24 hours in advance.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Expected Return Date & Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="return_time" id="return_time" class="form-control form-control-sm" required>
                    </div>
                </div>

                <!-- Dynamic Notice Alert Container -->
                <div id="advanceNoticeAlert" class="mb-4">
                    <!-- Populated dynamically via JS -->
                </div>

                <!-- Emergency Justification Gate (BR-03) - Shown when <24 hours notice -->
                <div id="rushGateSection" class="p-3 mb-4 rounded-3 border border-danger bg-danger bg-opacity-10 d-none">
                    <div class="d-flex align-items-center flex-wrap gap-2 text-danger fw-bold mb-2">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>EMERGENCY JUSTIFICATION REQUIRED</span>
                        <span class="policy-badge policy-badge-danger" data-bs-toggle="tooltip" data-bs-placement="top" title="<strong>Business Rule 03 (Same-Day / Rush Policy)</strong><br>Deployments within 24 hours strictly require an approved emergency memo or advisory to prevent motorpool backlog." tabindex="0" role="button">
                            BR-03 <i class="fa-solid fa-circle-question policy-info-icon"></i>
                        </span>
                    </div>
                    <p class="small text-danger mb-3" style="font-size: 0.8rem;">
                        This request is scheduled for departure within 24 hours of submission. Under Section 3 (BR-03) of the PIA Fleet Policy, same-day deployments strictly require an official emergency memo / justification document to prevent administrative backlog.
                    </p>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold text-dark">Urgent Deployment Explanation <span class="text-danger">*</span></label>
                            <input type="text" name="justification_notes" id="justification_notes" class="form-control form-control-sm bg-white" placeholder="e.g. Breaking news advisory from NDRRMC / Urgent state event called on 2h notice">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold text-dark">Attach Justification Document <span class="text-danger">*</span></label>
                            <input type="file" name="justification_file" id="justification_file" class="form-control form-control-sm bg-white" accept=".pdf,.jpg,.jpeg,.png">
                            <div class="form-text text-muted" style="font-size: 0.7rem;">Signed memo, advisory, or coverage notice (PDF, PNG, JPG).</div>
                        </div>
                    </div>
                </div>

                <!-- 4. Passengers & Capacity -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Total Passenger Count <span class="text-danger">*</span></label>
                        <input type="number" name="passenger_count" id="passenger_count" class="form-control form-control-sm" min="1" max="15" value="1" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Preferred Vehicle Type</label>
                        <select name="requested_vehicle_type" class="form-select form-select-sm">
                            <option value="">No preference (Auto-match)</option>
                            <option value="van">Multi-Passenger Van (HiAce / Urvan)</option>
                            <option value="suv">Executive SUV (Fortuner)</option>
                            <option value="pickup">Field 4x4 Pickup (D-Max / Strada)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Preferred Driver (Optional)</label>
                        <select name="requested_driver_id" class="form-select form-select-sm">
                            <option value="">Any available certified driver</option>
                            <?php foreach ($drivers as $d): ?>
                                <option value="<?= $d['id'] ?>">
                                    <?= esc($d['first_name'] . ' ' . $d['last_name']) ?> (<?= esc($d['driver_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold text-dark">Authorized Passenger Manifest <span class="text-danger">*</span></label>
                        <textarea name="passenger_names" class="form-control form-control-sm" rows="3" placeholder="List all official passengers, designations, and division (e.g. 1. Juan Dela Cruz - Info Officer; 2. Mark Santos - Cameraman)" required></textarea>
                    </div>
                </div>

                <!-- Approval SLA Reminder & Submit Bar -->
                <div class="pt-4 mt-3 border-top d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                    <div class="small text-muted d-flex align-items-center gap-2">
                        <i class="fa-solid fa-clock text-primary flex-shrink-0"></i>
                        <span>Submission starts the mandatory 
                            <span class="policy-badge policy-badge-warning" data-bs-toggle="tooltip" data-bs-placement="top" title="<strong>Service Level Agreement (4 Hours)</strong><br>Under PIA Administrative Guidelines, vehicle requests not actioned by the approving authority within 4 hours automatically escalate to the Division Chief for expedited clearance." tabindex="0" role="button">
                                4-Hour SLA Escalation Clock <i class="fa-solid fa-circle-question policy-info-icon"></i>
                            </span> 
                            (24-hour final expiry).
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <a href="<?= base_url('requests') ?>" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4" id="submitBtn">
                            <i class="fa-solid fa-paper-plane me-1"></i> Submit Vehicle Request Slip
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const depInput = document.getElementById('departure_time');
    const retInput = document.getElementById('return_time');
    const alertBox = document.getElementById('advanceNoticeAlert');
    const rushSection = document.getElementById('rushGateSection');
    const justNotes = document.getElementById('justification_notes');
    const justFile = document.getElementById('justification_file');
    const vrsForm = document.getElementById('vrsForm');

    // Default departure to tomorrow 8:00 AM
    const now = new Date();
    const tomorrow = new Date(now.getTime() + (26 * 60 * 60 * 1000));
    tomorrow.setMinutes(0);
    tomorrow.setSeconds(0);
    const tomorrowReturn = new Date(tomorrow.getTime() + (8 * 60 * 60 * 1000));

    function formatDateTimeLocal(d) {
        const pad = (n) => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    depInput.value = formatDateTimeLocal(tomorrow);
    retInput.value = formatDateTimeLocal(tomorrowReturn);

    function evaluateAdvanceNotice() {
        if (!depInput.value) return;

        const chosenTime = new Date(depInput.value).getTime();
        const currentTime = new Date().getTime();
        const diffHours = (chosenTime - currentTime) / (1000 * 60 * 60);

        if (diffHours < 24) {
            // Rush Request Triggered (BR-03)
            alertBox.innerHTML = `
                <div class="alert alert-danger border-danger-subtle p-2 px-3 rounded-2 d-flex align-items-center gap-2 small">
                    <i class="fa-solid fa-bolt text-danger"></i>
                    <div>
                        <strong>Notice:</strong> Departure is within <strong>${Math.max(0, diffHours.toFixed(1))} hours</strong>. Emergency justification memo and file attachment required below 
                        <span class="policy-badge policy-badge-danger" data-bs-toggle="tooltip" data-bs-placement="top" title="<strong>Business Rule 03 (Same-Day / Rush Policy)</strong><br>Deployments within 24 hours strictly require an approved emergency memo or advisory to prevent motorpool backlog." tabindex="0" role="button">
                            BR-03 <i class="fa-solid fa-circle-question policy-info-icon"></i>
                        </span>.
                    </div>
                </div>
            `;
            rushSection.classList.remove('d-none');
            justNotes.setAttribute('required', 'required');
            justFile.setAttribute('required', 'required');
        } else {
            // Standard Notice (BR-01)
            alertBox.innerHTML = `
                <div class="alert alert-success border-success-subtle p-2 px-3 rounded-2 d-flex align-items-center gap-2 small">
                    <i class="fa-solid fa-circle-check text-success"></i>
                    <div>
                        <strong>Standard Notice:</strong> Departure is in <strong>${diffHours.toFixed(0)} hours</strong> 
                        <span class="policy-badge policy-badge-info" data-bs-toggle="tooltip" data-bs-placement="top" title="<strong>Business Rule 01 (24-Hour Notice Policy)</strong><br>Standard processing window. Requests filed at least 24 hours prior to travel receive routine motorpool routing." tabindex="0" role="button">
                            BR-01 <i class="fa-solid fa-circle-question policy-info-icon"></i>
                        </span> 
                        (Complies with 24-hour advance submission policy).
                    </div>
                </div>
            `;
            rushSection.classList.add('d-none');
            justNotes.removeAttribute('required');
            justFile.removeAttribute('required');
        }

        // Re-initialize tooltips for newly rendered elements
        alertBox.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
            new bootstrap.Tooltip(el, { container: 'body', html: true });
        });
    }

    depInput.addEventListener('change', evaluateAdvanceNotice);
    evaluateAdvanceNotice(); // run on load

    // Interactive Destination Geocoding & Road Route Preview Map
    const destInput  = document.getElementById('destinationInput');
    const vrsMapEl   = document.getElementById('vrsRoutePreviewMap');
    let vrsDebounce  = null;

    if (vrsMapEl && typeof L !== 'undefined') {
        const originLat = 14.657200;
        const originLng = 121.045000;
        const vrsMap = L.map('vrsRoutePreviewMap', {
            zoomControl: true,
            attributionControl: false
        }).setView([originLat, originLng], 12);

        // Google Maps Primary Tile Layer
        const googleVrsLayer = L.tileLayer('https://mt{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            subdomains: ['0', '1', '2', '3'],
            attribution: '&copy; Google Maps'
        }).addTo(vrsMap);

        const vrsLayerGroup = L.featureGroup().addTo(vrsMap);

        function drawOriginPin() {
            const hqIcon = L.divIcon({
                className: 'vrs-hq-pin',
                html: `<div style="background:#0f172a;color:#fff;border-radius:50%;width:26px;height:26px;display:flex;align-items:center;justify-content:center;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,0.3);font-size:11px;"><i class="fa-solid fa-building-shield"></i></div>`,
                iconSize: [26, 26],
                iconAnchor: [13, 13]
            });
            L.marker([originLat, originLng], { icon: hqIcon })
                .bindPopup('<strong>Origin: PIA Central Office Motorpool</strong><br><span class="small text-muted">Visayas Ave, Quezon City</span>')
                .addTo(vrsLayerGroup);
        }

        drawOriginPin();

        async function updateRoutePreview(queryText) {
            const q = (queryText || '').trim();
            if (q.length < 3) return;

            try {
                const res  = await fetch('<?= base_url('api/v1/route') ?>?destination=' + encodeURIComponent(q));
                const data = await res.json();
                if (data.status !== 'success') return;

                vrsLayerGroup.clearLayers();
                drawOriginPin();

                if (data.destination && data.destination.lat && data.destination.lng) {
                    const destIcon = L.divIcon({
                        className: 'vrs-dest-pin',
                        html: `<div style="background:#dc2626;color:#fff;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,0.3);font-size:12px;"><i class="fa-solid fa-flag-checkered"></i></div>`,
                        iconSize: [28, 28],
                        iconAnchor: [14, 14]
                    });
                    L.marker([data.destination.lat, data.destination.lng], { icon: destIcon })
                        .bindPopup(`<strong>${q}</strong>`)
                        .addTo(vrsLayerGroup);
                }

                if (data.leaflet_coords && data.leaflet_coords.length > 1) {
                    L.polyline(data.leaflet_coords, {
                        color: '#2563eb',
                        weight: 4,
                        opacity: 0.9,
                        lineJoin: 'round'
                    }).addTo(vrsLayerGroup);
                }

                vrsMap.fitBounds(vrsLayerGroup.getBounds().pad(0.18));
                setTimeout(() => { if (typeof vrsMap !== 'undefined') vrsMap.invalidateSize(); }, 350);

                const metricsEl = document.getElementById('vrsRouteMetrics');
                if (metricsEl) metricsEl.style.setProperty('display', 'flex', 'important');
                document.getElementById('vrsDistBadge').innerText = `${data.distance_km} km one-way (${(data.distance_km * 2).toFixed(1)} km RT)`;
                document.getElementById('vrsEtaBadge').innerText  = `~${data.duration_min} min ETA`;
                document.getElementById('vrsRouteSummary').innerHTML = `<i class="fa-solid fa-circle-check text-success me-1"></i> Road route verified: <strong>${data.distance_km} km</strong> from PIA Central Office (~${data.duration_min} mins driving).`;
                const gLink = document.getElementById('vrsGoogleMapsLink');
                if (gLink) {
                    gLink.href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(q + ', Philippines');
                    gLink.style.display = 'inline-block';
                }
            } catch (err) {
                console.warn('Route preview lookup failed:', err);
            }
        }

        if (destInput) {
            destInput.addEventListener('input', function() {
                clearTimeout(vrsDebounce);
                vrsDebounce = setTimeout(() => updateRoutePreview(this.value), 450);
            });
        }

        document.querySelectorAll('.vrs-landmark-chip').forEach(btn => {
            btn.addEventListener('click', function() {
                const dest = this.getAttribute('data-dest');
                if (destInput && dest) {
                    destInput.value = dest;
                    updateRoutePreview(dest);
                }
            });
        });

        // Trigger preview if input already has text (e.g. browser autofill or validation reload)
        if (destInput && destInput.value.trim().length >= 3) {
            updateRoutePreview(destInput.value.trim());
        }
    }

    // Form submission validation
    vrsForm.addEventListener('submit', function(e) {
        const chosenTime = new Date(depInput.value).getTime();
        const currentTime = new Date().getTime();
        const diffHours = (chosenTime - currentTime) / (1000 * 60 * 60);

        if (diffHours < 24) {
            if (!justNotes.value.trim() || !justFile.files.length) {
                e.preventDefault();
                alert('Under PIA Fleet Policy BR-03, rush/same-day vehicle requests require an emergency justification memo and document attachment.');
                return false;
            }
        }
    });
});
</script>
<?= $this->endSection() ?>
