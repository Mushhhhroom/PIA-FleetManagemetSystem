<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="mono" style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">
                Super Admin Architecture &bull; System Blueprint
            </div>
            <h1 class="page-title mb-1">System Navigation &amp; Flowchart Directory</h1>
            <p class="text-muted small mb-0">High-level architectural visualization, end-to-end official travel workflows, and quick-navigation directory of all modules.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= base_url('assets/images/system_entire_flowchart.png') ?>" download="PIA-AFMS_System_Entire_Flowchart.png" class="btn btn-primary text-decoration-none">
                <i class="fa-solid fa-download me-1 text-success"></i> Download Flowchart Image (PNG)
            </a>
            <a href="<?= base_url('dashboard') ?>" class="btn btn-secondary text-decoration-none">
                <i class="fa-solid fa-chart-line me-1"></i> Command Center
            </a>
            <a href="<?= base_url('users') ?>" class="btn btn-secondary text-decoration-none">
                <i class="fa-solid fa-users-gear me-1"></i> Manage Accounts
            </a>
        </div>
    </div>

    <!-- Quick Navigation Hub -->
    <div class="card-panel mb-4">
        <div class="card-panel-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-compass text-primary"></i>
                <h6 class="mb-0 fw-bold">Live Navigation Directory</h6>
            </div>
            <span class="badge bg-light text-muted border small">All 14 Core Workspaces</span>
        </div>
        <div class="card-panel-body">
            <div class="row g-3">
                <!-- Group 1 -->
                <div class="col-md-6 col-lg-3">
                    <div class="p-3 rounded border h-100 bg-white">
                        <div class="fw-bold small text-uppercase text-primary mb-2 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-file-contract"></i> 1. Travel Workflow
                        </div>
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-2 small">
                            <li>
                                <a href="<?= base_url('requests') ?>" class="text-decoration-none d-flex align-items-center justify-content-between text-dark hover-link">
                                    <span><i class="fa-solid fa-file-signature text-secondary me-2"></i>Requests (VRS)</span>
                                    <i class="fa-solid fa-arrow-right text-muted small"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?= base_url('approvals') ?>" class="text-decoration-none d-flex align-items-center justify-content-between text-dark hover-link">
                                    <span><i class="fa-solid fa-stamp text-secondary me-2"></i>Approvals &amp; SLA</span>
                                    <i class="fa-solid fa-arrow-right text-muted small"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?= base_url('dispatch') ?>" class="text-decoration-none d-flex align-items-center justify-content-between text-dark hover-link">
                                    <span><i class="fa-solid fa-truck-fast text-secondary me-2"></i>Dispatch Console</span>
                                    <i class="fa-solid fa-arrow-right text-muted small"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?= base_url('dispatch/calendar') ?>" class="text-decoration-none d-flex align-items-center justify-content-between text-dark hover-link">
                                    <span><i class="fa-solid fa-calendar-days text-secondary me-2"></i>Allocation Calendar</span>
                                    <i class="fa-solid fa-arrow-right text-muted small"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?= base_url('tickets') ?>" class="text-decoration-none d-flex align-items-center justify-content-between text-dark hover-link">
                                    <span><i class="fa-solid fa-ticket text-secondary me-2"></i>Trip Tickets (e-DTT)</span>
                                    <i class="fa-solid fa-arrow-right text-muted small"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?= base_url('gate') ?>" class="text-decoration-none d-flex align-items-center justify-content-between text-dark hover-link">
                                    <span><i class="fa-solid fa-shield-halved text-secondary me-2"></i>Gate QR Checkpoint</span>
                                    <i class="fa-solid fa-arrow-right text-muted small"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Group 2 -->
                <div class="col-md-6 col-lg-3">
                    <div class="p-3 rounded border h-100 bg-white">
                        <div class="fw-bold small text-uppercase text-warning text-dark mb-2 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-shield-virus text-warning"></i> 2. Safety &amp; Inspection
                        </div>
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-2 small">
                            <li>
                                <a href="<?= base_url('safety') ?>" class="text-decoration-none d-flex align-items-center justify-content-between text-dark hover-link">
                                    <span><i class="fa-solid fa-clipboard-check text-secondary me-2"></i>BLOWBAGETS Safety</span>
                                    <i class="fa-solid fa-arrow-right text-muted small"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?= base_url('pir') ?>" class="text-decoration-none d-flex align-items-center justify-content-between text-dark hover-link">
                                    <span><i class="fa-solid fa-screwdriver-wrench text-secondary me-2"></i>Mechanic PIR Queue</span>
                                    <i class="fa-solid fa-arrow-right text-muted small"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?= base_url('rfid') ?>" class="text-decoration-none d-flex align-items-center justify-content-between text-dark hover-link">
                                    <span><i class="fa-solid fa-credit-card text-secondary me-2"></i>Tollway RFID Cards</span>
                                    <i class="fa-solid fa-arrow-right text-muted small"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Group 3 -->
                <div class="col-md-6 col-lg-3">
                    <div class="p-3 rounded border h-100 bg-white">
                        <div class="fw-bold small text-uppercase text-success mb-2 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-truck-ramp-box"></i> 3. Fleet Assets &amp; Ops
                        </div>
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-2 small">
                            <li>
                                <a href="<?= base_url('vehicles') ?>" class="text-decoration-none d-flex align-items-center justify-content-between text-dark hover-link">
                                    <span><i class="fa-solid fa-truck text-secondary me-2"></i>Vehicle Registry</span>
                                    <i class="fa-solid fa-arrow-right text-muted small"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?= base_url('drivers') ?>" class="text-decoration-none d-flex align-items-center justify-content-between text-dark hover-link">
                                    <span><i class="fa-solid fa-id-card text-secondary me-2"></i>Driver Personnel</span>
                                    <i class="fa-solid fa-arrow-right text-muted small"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?= base_url('tracking') ?>" class="text-decoration-none d-flex align-items-center justify-content-between text-dark hover-link">
                                    <span><i class="fa-solid fa-satellite-dish text-secondary me-2"></i>Live GPS Radar</span>
                                    <i class="fa-solid fa-arrow-right text-muted small"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?= base_url('fuel') ?>" class="text-decoration-none d-flex align-items-center justify-content-between text-dark hover-link">
                                    <span><i class="fa-solid fa-gas-pump text-secondary me-2"></i>Fuel Records</span>
                                    <i class="fa-solid fa-arrow-right text-muted small"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?= base_url('maintenance') ?>" class="text-decoration-none d-flex align-items-center justify-content-between text-dark hover-link">
                                    <span><i class="fa-solid fa-wrench text-secondary me-2"></i>Service Orders &amp; PMS</span>
                                    <i class="fa-solid fa-arrow-right text-muted small"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Group 4 -->
                <div class="col-md-6 col-lg-3">
                    <div class="p-3 rounded border h-100 bg-white">
                        <div class="fw-bold small text-uppercase text-info text-dark mb-2 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-building-shield text-info"></i> 4. Compliance &amp; Admin
                        </div>
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-2 small">
                            <li>
                                <a href="<?= base_url('audit') ?>" class="text-decoration-none d-flex align-items-center justify-content-between text-dark hover-link">
                                    <span><i class="fa-solid fa-scale-balanced text-secondary me-2"></i>COA Compliance &amp; Audits</span>
                                    <i class="fa-solid fa-arrow-right text-muted small"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?= base_url('compliance') ?>" class="text-decoration-none d-flex align-items-center justify-content-between text-dark hover-link">
                                    <span><i class="fa-solid fa-file-shield text-secondary me-2"></i>Form B Portal</span>
                                    <i class="fa-solid fa-arrow-right text-muted small"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?= base_url('audit/logs') ?>" class="text-decoration-none d-flex align-items-center justify-content-between text-dark hover-link">
                                    <span><i class="fa-solid fa-clock-rotate-left text-secondary me-2"></i>System Audit Trail</span>
                                    <i class="fa-solid fa-arrow-right text-muted small"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?= base_url('users') ?>" class="text-decoration-none d-flex align-items-center justify-content-between text-dark hover-link">
                                    <span><i class="fa-solid fa-users-gear text-secondary me-2"></i>User Governance</span>
                                    <i class="fa-solid fa-arrow-right text-muted small"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?= base_url('driver/trips') ?>" target="_blank" class="text-decoration-none d-flex align-items-center justify-content-between text-dark hover-link">
                                    <span><i class="fa-solid fa-mobile-screen text-secondary me-2"></i>Driver Mobile PWA</span>
                                    <i class="fa-solid fa-arrow-up-right-from-square text-muted small"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Interactive Visual Flowcharts -->
    <div class="card-panel mb-4">
        <div class="card-panel-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-diagram-project text-primary fs-5"></i>
                <div>
                    <h6 class="mb-0 fw-bold">Interactive System Flowchart</h6>
                    <small class="text-muted">Click any flowchart stage node to directly open the matching module</small>
                </div>
            </div>
            <ul class="nav nav-pills" id="flowchartNav" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active py-1 px-3 small fw-semibold" id="tab-travel-flow-btn" data-bs-toggle="pill" data-bs-target="#flow-travel" type="button" role="tab">
                        <i class="fa-solid fa-route me-1"></i> 1. Official Travel Lifecycle (10 Stages)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-1 px-3 small fw-semibold" id="tab-maintenance-flow-btn" data-bs-toggle="pill" data-bs-target="#flow-maintenance" type="button" role="tab">
                        <i class="fa-solid fa-screwdriver-wrench me-1"></i> 2. Defect Grounding &amp; PIR Queue
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-1 px-3 small fw-semibold" id="tab-audit-flow-btn" data-bs-toggle="pill" data-bs-target="#flow-audit" type="button" role="tab">
                        <i class="fa-solid fa-scale-balanced me-1"></i> 3. Odometer Variance &amp; COA Anomaly
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-panel-body">
            <div class="tab-content" id="flowchartTabContent">
                <!-- Flowchart 1: Travel Request Lifecycle -->
                <div class="tab-pane fade show active" id="flow-travel" role="tabpanel">
                    <div class="flowchart-track">
                        <!-- Step 1 -->
                        <div class="flow-card">
                            <div class="flow-step-num">Step 1</div>
                            <div class="flow-icon bg-blue-subtle text-primary">
                                <i class="fa-solid fa-file-pen"></i>
                            </div>
                            <h6 class="fw-bold mb-1">INPUT: VRS Filing</h6>
                            <span class="badge bg-secondary mb-2" style="font-size: 0.65rem;">Employee Request</span>
                            <p class="text-muted small mb-3">Employee requests transport, submitting destination, purpose, dates, passengers, and emergency memos.</p>
                            <a href="<?= base_url('requests') ?>" class="btn btn-sm btn-outline-primary w-100">
                                Open VRS <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>

                        <div class="flow-connector"><i class="fa-solid fa-arrow-right"></i></div>

                        <!-- Step 2 -->
                        <div class="flow-card">
                            <div class="flow-step-num">Step 2</div>
                            <div class="flow-icon bg-amber-subtle text-danger">
                                <i class="fa-solid fa-file-circle-check"></i>
                            </div>
                            <h6 class="fw-bold mb-1">Tier 1 Dispatcher Approval</h6>
                            <span class="badge bg-danger text-white mb-2" style="font-size: 0.65rem;">Motorpool Dispatcher</span>
                            <p class="text-muted small mb-3">Motorpool Dispatcher checks documents, passenger specs, and justification; endorses or issues specification disapproval.</p>
                            <a href="<?= base_url('approvals') ?>" class="btn btn-sm btn-outline-primary w-100">
                                Check Documents <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>

                        <div class="flow-connector"><i class="fa-solid fa-arrow-right"></i></div>

                        <!-- Step 3 -->
                        <div class="flow-card">
                            <div class="flow-step-num">Step 3</div>
                            <div class="flow-icon bg-purple-subtle text-purple">
                                <i class="fa-solid fa-stamp"></i>
                            </div>
                            <h6 class="fw-bold mb-1">Tier 2 Admin Approval</h6>
                            <span class="badge bg-purple text-white mb-2" style="font-size: 0.65rem;">Head, Admin Division</span>
                            <p class="text-muted small mb-3">Administrative Division Head reviews endorsed VRS and grants final official travel authorization.</p>
                            <a href="<?= base_url('approvals') ?>" class="btn btn-sm btn-outline-primary w-100">
                                Authorize <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>

                        <div class="flow-connector"><i class="fa-solid fa-arrow-right"></i></div>

                        <!-- Step 4 -->
                        <div class="flow-card">
                            <div class="flow-step-num">Step 4</div>
                            <div class="flow-icon bg-rose-subtle text-danger">
                                <i class="fa-solid fa-truck-fast"></i>
                            </div>
                            <h6 class="fw-bold mb-1">Dispatch &amp; Allocation</h6>
                            <span class="badge bg-danger text-white mb-2" style="font-size: 0.65rem;">Motorpool Dispatcher</span>
                            <p class="text-muted small mb-3">Assigns verified vehicle, certified driver, toll RFID card, and issues official e-DTT.</p>
                            <a href="<?= base_url('dispatch') ?>" class="btn btn-sm btn-outline-primary w-100">
                                Dispatch Console <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>

                        <div class="flow-connector"><i class="fa-solid fa-arrow-right"></i></div>

                        <!-- Step 5 -->
                        <div class="flow-card">
                            <div class="flow-step-num">Step 5</div>
                            <div class="flow-icon bg-emerald-subtle text-success">
                                <i class="fa-solid fa-clipboard-check"></i>
                            </div>
                            <h6 class="fw-bold mb-1">Pre-Trip BLOWBAGETS</h6>
                            <span class="badge bg-success text-white mb-2" style="font-size: 0.65rem;">Assigned Driver</span>
                            <p class="text-muted small mb-3">10-point inspection (Battery, Light, Oil, Water, Brake, Air, Gas, Engine, Tire, Self) before road departure.</p>
                            <a href="<?= base_url('safety') ?>" class="btn btn-sm btn-outline-primary w-100">
                                Safety Check <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>

                        <div class="flow-connector"><i class="fa-solid fa-arrow-right"></i></div>

                        <!-- Step 6 -->
                        <div class="flow-card">
                            <div class="flow-step-num">Step 6</div>
                            <div class="flow-icon bg-slate-subtle text-secondary">
                                <i class="fa-solid fa-qrcode"></i>
                            </div>
                            <h6 class="fw-bold mb-1">Gate Out Security Scan</h6>
                            <span class="badge bg-secondary mb-2" style="font-size: 0.65rem;">Security Guard</span>
                            <p class="text-muted small mb-3">Guard verifies e-DTT, logs departure timestamp, starting odometer, and clears vehicle exit.</p>
                            <a href="<?= base_url('gate') ?>" class="btn btn-sm btn-outline-primary w-100">
                                Gate Scanner <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>

                        <div class="flow-connector"><i class="fa-solid fa-arrow-right"></i></div>

                        <!-- Step 7 -->
                        <div class="flow-card">
                            <div class="flow-step-num">Step 7</div>
                            <div class="flow-icon bg-blue-subtle text-info">
                                <i class="fa-solid fa-satellite-dish"></i>
                            </div>
                            <h6 class="fw-bold mb-1">En Route Telematics &amp; Fuel</h6>
                            <span class="badge bg-info text-dark mb-2" style="font-size: 0.65rem;">Telematics &amp; Driver PWA</span>
                            <p class="text-muted small mb-3">Live ping stream, speed watcher alert (>80 km/h), real-time radar, and driver fuel receipt upload.</p>
                            <a href="<?= base_url('tracking') ?>" class="btn btn-sm btn-outline-primary w-100">
                                Live Radar <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>

                        <div class="flow-connector"><i class="fa-solid fa-arrow-right"></i></div>

                        <!-- Step 8 -->
                        <div class="flow-card">
                            <div class="flow-step-num">Step 8</div>
                            <div class="flow-icon bg-slate-subtle text-secondary">
                                <i class="fa-solid fa-barcode"></i>
                            </div>
                            <h6 class="fw-bold mb-1">Gate In Verification</h6>
                            <span class="badge bg-secondary mb-2" style="font-size: 0.65rem;">Security Guard</span>
                            <p class="text-muted small mb-3">Guard logs arrival timestamp, return odometer reading, and verifies passenger headcount.</p>
                            <a href="<?= base_url('gate') ?>" class="btn btn-sm btn-outline-primary w-100">
                                Return Scan <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>

                        <div class="flow-connector"><i class="fa-solid fa-arrow-right"></i></div>

                        <!-- Step 9 -->
                        <div class="flow-card">
                            <div class="flow-step-num">Step 9</div>
                            <div class="flow-icon bg-amber-subtle text-warning">
                                <i class="fa-solid fa-scale-balanced"></i>
                            </div>
                            <h6 class="fw-bold mb-1">Reconciliation &amp; Form B</h6>
                            <span class="badge bg-warning text-dark mb-2" style="font-size: 0.65rem;">COA Auditor &amp; System</span>
                            <p class="text-muted small mb-3">Matches OSRM distance and fuel, computes CPK, writes immutable audit log, and compiles Form B.</p>
                            <a href="<?= base_url('compliance') ?>" class="btn btn-sm btn-outline-primary w-100">
                                Form B Portal <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>

                        <div class="flow-connector"><i class="fa-solid fa-arrow-right"></i></div>

                        <!-- Step 10 -->
                        <div class="flow-card">
                            <div class="flow-step-num">Step 10</div>
                            <div class="flow-icon bg-emerald-subtle text-success">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                            <h6 class="fw-bold mb-1">Trip Closed &amp; Archived</h6>
                            <span class="badge bg-success mb-2" style="font-size: 0.65rem;">COA Compliant</span>
                            <p class="text-muted small mb-3">Audit verified and archived; vehicle returned to available pool for subsequent dispatch.</p>
                            <a href="<?= base_url('audit') ?>" class="btn btn-sm btn-outline-primary w-100">
                                Audit Logs <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Flowchart 2: Safety & Maintenance (PMS) -->
                <div class="tab-pane fade" id="flow-maintenance" role="tabpanel">
                    <div class="flowchart-track">
                        <!-- Step 1 -->
                        <div class="flow-card">
                            <div class="flow-step-num">Stage 1</div>
                            <div class="flow-icon bg-purple-subtle text-purple">
                                <i class="fa-solid fa-car-on"></i>
                            </div>
                            <h6 class="fw-bold mb-1">Defect Detected</h6>
                            <span class="badge bg-secondary mb-2" style="font-size: 0.65rem;">Driver / Check</span>
                            <p class="text-muted small mb-3">BLOWBAGETS checklist fails or driver flags safety issue via Mobile PWA report.</p>
                            <a href="<?= base_url('safety') ?>" class="btn btn-sm btn-outline-primary w-100">
                                Safety Checks <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>

                        <div class="flow-connector"><i class="fa-solid fa-arrow-right"></i></div>

                        <!-- Step 2 -->
                        <div class="flow-card">
                            <div class="flow-step-num">Stage 2</div>
                            <div class="flow-icon bg-amber-subtle text-warning">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>
                            <h6 class="fw-bold mb-1">Vehicle Grounded</h6>
                            <span class="badge bg-danger mb-2" style="font-size: 0.65rem;">FMS Logic</span>
                            <p class="text-muted small mb-3">Vehicle is automatically locked from dispatch allocation until cleared by a certified mechanic.</p>
                            <a href="<?= base_url('vehicles') ?>" class="btn btn-sm btn-outline-primary w-100">
                                Fleet Registry <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>

                        <div class="flow-connector"><i class="fa-solid fa-arrow-right"></i></div>

                        <!-- Step 3 -->
                        <div class="flow-card">
                            <div class="flow-step-num">Stage 3</div>
                            <div class="flow-icon bg-blue-subtle text-primary">
                                <i class="fa-solid fa-screwdriver-wrench"></i>
                            </div>
                            <h6 class="fw-bold mb-1">Mechanic PIR Inspection</h6>
                            <span class="badge bg-primary mb-2" style="font-size: 0.65rem;">Mechanic</span>
                            <p class="text-muted small mb-3">Pre-Repair Inspection (PIR) executed to diagnose mechanical faults, parts, and estimates.</p>
                            <a href="<?= base_url('pir') ?>" class="btn btn-sm btn-outline-primary w-100">
                                PIR Queue <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>

                        <div class="flow-connector"><i class="fa-solid fa-arrow-right"></i></div>

                        <!-- Step 4 -->
                        <div class="flow-card">
                            <div class="flow-step-num">Stage 4</div>
                            <div class="flow-icon bg-emerald-subtle text-success">
                                <i class="fa-solid fa-wrench"></i>
                            </div>
                            <h6 class="fw-bold mb-1">Service &amp; Recalibration</h6>
                            <span class="badge bg-success mb-2" style="font-size: 0.65rem;">Maintenance Tech</span>
                            <p class="text-muted small mb-3">Repairs completed, 5K/10K PMS threshold advanced, vehicle restored to Active status.</p>
                            <a href="<?= base_url('maintenance') ?>" class="btn btn-sm btn-outline-primary w-100">
                                Service Orders <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Flowchart 3: Odometer Variance & COA Anomaly -->
                <div class="tab-pane fade" id="flow-audit" role="tabpanel">
                    <div class="flowchart-track">
                        <!-- Stage 1 -->
                        <div class="flow-card">
                            <div class="flow-step-num">Stage 1</div>
                            <div class="flow-icon bg-amber-subtle text-warning">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>
                            <h6 class="fw-bold mb-1">Variance Detected</h6>
                            <span class="badge bg-warning text-dark mb-2" style="font-size: 0.65rem;">System Rule</span>
                            <p class="text-muted small mb-3">Actual gate-to-gate odometer reading deviates from computed OSRM route distance by over 15%.</p>
                            <a href="<?= base_url('gate') ?>" class="btn btn-sm btn-outline-primary w-100">
                                Gate Logs <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>

                        <div class="flow-connector"><i class="fa-solid fa-arrow-right"></i></div>

                        <!-- Stage 2 -->
                        <div class="flow-card">
                            <div class="flow-step-num">Stage 2</div>
                            <div class="flow-icon bg-rose-subtle text-danger">
                                <i class="fa-solid fa-flag"></i>
                            </div>
                            <h6 class="fw-bold mb-1">Flag Audit Anomaly</h6>
                            <span class="badge bg-danger mb-2" style="font-size: 0.65rem;">COA Auditor</span>
                            <p class="text-muted small mb-3">Reimbursement locked; anomaly flagged for route inspection and driver delay correlation.</p>
                            <a href="<?= base_url('audit') ?>" class="btn btn-sm btn-outline-primary w-100">
                                COA Audits <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>

                        <div class="flow-connector"><i class="fa-solid fa-arrow-right"></i></div>

                        <!-- Stage 3 -->
                        <div class="flow-card">
                            <div class="flow-step-num">Stage 3</div>
                            <div class="flow-icon bg-blue-subtle text-primary">
                                <i class="fa-solid fa-magnifying-glass-chart"></i>
                            </div>
                            <h6 class="fw-bold mb-1">Investigation Review</h6>
                            <span class="badge bg-primary mb-2" style="font-size: 0.65rem;">COA Resident Auditor</span>
                            <p class="text-muted small mb-3">Cross-examines fuel receipts, telematics route breadcrumbs, and official passenger logs.</p>
                            <a href="<?= base_url('audit/logs') ?>" class="btn btn-sm btn-outline-primary w-100">
                                Audit Trail <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>

                        <div class="flow-connector"><i class="fa-solid fa-arrow-right"></i></div>

                        <!-- Stage 4 -->
                        <div class="flow-card">
                            <div class="flow-step-num">Stage 4</div>
                            <div class="flow-icon bg-emerald-subtle text-success">
                                <i class="fa-solid fa-file-shield"></i>
                            </div>
                            <h6 class="fw-bold mb-1">COA Form B Integration</h6>
                            <span class="badge bg-success mb-2" style="font-size: 0.65rem;">System &amp; Auditor</span>
                            <p class="text-muted small mb-3">Resolved audit findings recorded into monthly Form B report and vehicle released to available pool.</p>
                            <a href="<?= base_url('compliance') ?>" class="btn btn-sm btn-outline-primary w-100">
                                Form B Portal <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Official Standard Architecture Flowchart Diagram (Full Image & Download) -->
    <div class="card-panel mb-4">
        <div class="card-panel-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-file-image text-success fs-5"></i>
                <div>
                    <h6 class="mb-0 fw-bold">Official Standard System Flowchart Diagram (ANSI / ISO Specification)</h6>
                    <small class="text-muted">High-Resolution 2500 &times; 1350 standard flowchart matching official PIA workflow blueprint</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= base_url('assets/images/system_entire_flowchart.png') ?>" target="_blank" class="btn btn-secondary text-decoration-none">
                    <i class="fa-solid fa-up-right-and-down-left-from-center me-1"></i> View Full Resolution
                </a>
                <a href="<?= base_url('assets/images/system_entire_flowchart.png') ?>" download="PIA-AFMS_System_Entire_Flowchart.png" class="btn btn-primary text-decoration-none">
                    <i class="fa-solid fa-download me-1 text-success"></i> Download Flowchart Image
                </a>
            </div>
        </div>
        <div class="card-panel-body">
            <!-- Flowchart Symbol Quick Reference Guide -->
            <div class="row g-2 mb-3 p-3 rounded-2 border bg-light align-items-center">
                <div class="col-12 col-md-auto fw-bold small text-dark d-flex align-items-center gap-2">
                    <i class="fa-solid fa-shapes text-primary"></i> Standard Symbols:
                </div>
                <div class="col-6 col-sm-3 col-md-auto">
                    <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-2 py-1 small">
                        <i class="fa-solid fa-capsules me-1"></i> Oval / Pill: Terminator (Start / End)
                    </span>
                </div>
                <div class="col-6 col-sm-3 col-md-auto">
                    <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 small">
                        <i class="fa-solid fa-diamond me-1"></i> Diamond: Decision Branch (Yes / No)
                    </span>
                </div>
                <div class="col-6 col-sm-3 col-md-auto">
                    <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 small">
                        <i class="fa-regular fa-square me-1"></i> Rectangle: Process / Module Action
                    </span>
                </div>
                <div class="col-6 col-sm-3 col-md-auto">
                    <span class="badge rounded-pill bg-purple-subtle text-purple border border-purple-subtle px-2 py-1 small">
                        <i class="fa-solid fa-italic me-1"></i> Parallelogram: Input / Output Scan
                    </span>
                </div>
            </div>

            <!-- Crisp Diagram View Container -->
            <div class="border rounded-2 p-3 text-center position-relative overflow-hidden" style="background: #121214; max-height: 850px; overflow-y: auto;">
                <a href="<?= base_url('assets/images/system_entire_flowchart.png') ?>" target="_blank" title="Click to view full original size">
                    <img src="<?= base_url('assets/images/system_entire_flowchart.png') ?>" alt="PIA-AFMS Complete Standard Architecture Flowchart" class="img-fluid rounded shadow-sm" style="max-width: 100%; height: auto; display: inline-block;">
                </a>
            </div>
            <div class="d-flex align-items-center justify-content-between mt-2 pt-2 px-1 text-muted small">
                <span><i class="fa-solid fa-circle-info me-1 text-primary"></i> Click image above or button to open full-scale 2500&times;1350px dark-theme official standard flowchart rendering.</span>
                <span class="mono">PNG Format &bull; <?= round(filesize(FCPATH . 'assets/images/system_entire_flowchart.png') / 1024) ?> KB</span>
            </div>
        </div>
    </div>
</div>

<style>
/* Flowchart Styles */
.flowchart-track {
    display: flex;
    align-items: stretch;
    gap: 12px;
    overflow-x: auto;
    padding: 16px 8px;
    scrollbar-width: thin;
}

.flow-card {
    min-width: 220px;
    max-width: 240px;
    background: #ffffff;
    border: 1px solid var(--border-subtle);
    border-radius: 10px;
    padding: 18px 14px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    position: relative;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.flow-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.08);
    border-color: #93c5fd;
}

.flow-step-num {
    position: absolute;
    top: 8px;
    left: 10px;
    font-size: 0.65rem;
    font-weight: 700;
    color: var(--text-muted);
    font-family: var(--font-mono);
    text-transform: uppercase;
}

.flow-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    font-size: 1.2rem;
    margin-top: 10px;
    margin-bottom: 12px;
}

.flow-card p {
    font-size: 0.78rem;
    line-height: 1.45;
    flex-grow: 1;
}

.flow-connector {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    font-size: 1.1rem;
    padding: 0 4px;
}

.bg-blue-subtle { background: rgba(59, 130, 246, 0.12); }
.bg-amber-subtle { background: rgba(245, 158, 11, 0.15); }
.bg-emerald-subtle { background: rgba(16, 185, 129, 0.15); }
.bg-purple-subtle { background: rgba(139, 92, 246, 0.15); }
.text-purple { color: #8b5cf6; }
.bg-slate-subtle { background: rgba(100, 116, 139, 0.15); }
.bg-rose-subtle { background: rgba(244, 63, 94, 0.15); }

.hover-link {
    padding: 6px 8px;
    border-radius: 6px;
    transition: background-color 0.15s ease;
}

.hover-link:hover {
    background-color: #f1f5f9;
    color: var(--primary-accent) !important;
}

.hover-link:hover i.fa-arrow-right {
    color: var(--primary-accent) !important;
    transform: translateX(2px);
    transition: transform 0.15s ease;
}
</style>
<?= $this->endSection() ?>
