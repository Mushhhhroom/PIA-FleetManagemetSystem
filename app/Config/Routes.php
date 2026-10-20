<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Authentication Routes
$routes->get('login', 'AuthController::login');
$routes->post('login', 'AuthController::authenticate');
$routes->get('logout', 'AuthController::logout');

// NFR-2: TOTP MFA challenge (pre-session, so outside the auth filter)
$routes->get('login/mfa', 'AuthController::mfaChallenge');
$routes->post('login/mfa', 'AuthController::mfaVerify');

// Public RESTful APIs for IoT Telematics, Mapping System & Fleet Integration
$routes->group('api/v1', static function ($routes) {
    // CORS Preflight
    $routes->options('(:any)', 'ApiController::optionsHandler');

    // Fleet Asset Registry
    $routes->get('vehicles', 'ApiController::getVehicles');
    $routes->get('vehicles/(:num)', 'ApiController::getVehicle/$1');

    // Core Real-Time Mapping & Telematics Endpoints
    $routes->get('tracking', 'ApiController::getLiveTracking');
    $routes->get('map', 'ApiController::getLiveTracking');
    $routes->get('map/vehicles', 'ApiController::getLiveTracking');
    $routes->get('tracking/(:num)', 'ApiController::getVehicleTracking/$1');
    $routes->get('tracking/(:num)/trail', 'ApiController::getVehicleTrail/$1');
    $routes->post('tracking/simulate-step', 'ApiController::simulateStep');

    // Route Geometry & Road Distance Calculation API
    $routes->match(['get', 'post'], 'route', 'ApiController::calculateRoute');

    // Telemetry Ingestion & Log History
    $routes->get('telemetry', 'ApiController::getTelemetry');
    $routes->post('telemetry', 'ApiController::ingestTelemetry');
    $routes->get('trips/active', 'ApiController::getActiveTrips');
});

// Authenticated Application Routes (Protected by Auth and Role RBAC)
$routes->group('', ['filter' => 'auth'], static function ($routes) {

    // General Operations (Admin, Dispatcher, Maintenance, Approver Admin)
    $routes->group('', ['filter' => 'role:admin,dispatcher,maintenance,approver_admin'], static function ($routes) {
        $routes->get('/', 'DashboardController::index');
        $routes->get('dashboard', 'DashboardController::index');
        $routes->get('tracking', 'TrackingController::index');
        $routes->get('tracking/live-data', 'TrackingController::liveData');
        $routes->post('tracking/simulate-step', 'TrackingController::simulateStep');
        $routes->get('vehicles', 'VehicleController::index');
        $routes->get('vehicles/new', 'VehicleController::create');
        $routes->post('vehicles', 'VehicleController::store');
        $routes->get('vehicles/(:num)', 'VehicleController::show/$1');
        $routes->get('vehicles/(:num)/edit', 'VehicleController::edit/$1');
        $routes->post('vehicles/(:num)', 'VehicleController::update/$1');
        $routes->post('vehicles/(:num)/delete', 'VehicleController::delete/$1');
    });

    // Dispatch & Personnel Operations (Admin, Dispatcher, Approver Admin)
    $routes->group('', ['filter' => 'role:admin,dispatcher,approver_admin'], static function ($routes) {
        // Drivers & Operators
        $routes->get('drivers', 'DriverController::index');
        $routes->get('drivers/new', 'DriverController::create');
        $routes->post('drivers', 'DriverController::store');
        $routes->get('drivers/(:num)', 'DriverController::show/$1');
        $routes->get('drivers/(:num)/edit', 'DriverController::edit/$1');
        $routes->post('drivers/(:num)', 'DriverController::update/$1');
        $routes->post('drivers/(:num)/delete', 'DriverController::delete/$1');

        // Fuel & Energy Logging
        $routes->get('fuel', 'FuelController::index');
        $routes->get('fuel/new', 'FuelController::create');
        $routes->post('fuel', 'FuelController::store');
    });

    // Technical Maintenance & Workshop (Admin, Maintenance, Approver Admin)
    $routes->group('', ['filter' => 'role:admin,maintenance,approver_admin'], static function ($routes) {
        $routes->get('maintenance', 'MaintenanceController::index');
        $routes->get('maintenance/new', 'MaintenanceController::create');
        $routes->post('maintenance', 'MaintenanceController::store');
        $routes->post('maintenance/(:num)/status', 'MaintenanceController::updateStatus/$1');
    });

    // Driver Mobile Web Portal (SDD §6.2)
    $routes->get('driver/trips', 'DriverController::mobileApp');
    $routes->post('driver/status', 'DriverController::updateDutyStatus');
    $routes->post('driver/incident', 'DriverController::reportIncident');

    // =========================================================================
    // PHILIPPINE INFORMATION AGENCY - MOTORPOOL FLEET WORKFLOW (BRD & SDD)
    // =========================================================================

    // 1. Vehicle Request Slips (ADMIN-F-018 rev2)
    $routes->group('', ['filter' => 'role:admin,dispatcher,requestor,approver_admin,auditor'], static function ($routes) {
        $routes->get('requests', 'RequestController::index');
        $routes->get('requests/new', 'RequestController::create');
        $routes->post('requests', 'RequestController::store');
        $routes->get('requests/(:num)', 'RequestController::show/$1');
        $routes->get('requests/(:num)/print', 'RequestController::printVrs/$1');
    });

    // 2. Multi-Tier Approval Portal & SLA Watcher (Motorpool Dispatcher = Tier 1, Admin Division Head = Tier 2)
    $routes->group('', ['filter' => 'role:admin,dispatcher,approver_admin'], static function ($routes) {
        $routes->post('approvals/(:num)/action', 'ApprovalController::action/$1');
    });

    // Approval queue: Dispatcher (Tier 1), Admin Head (Tier 2)
    $routes->get('approvals', 'ApprovalController::index',
        ['filter' => 'role:admin,approver_admin,dispatcher']);

    // 3. Motorpool Dispatch Command & Heuristic Allocation Engine
    $routes->group('', ['filter' => 'role:admin,dispatcher,approver_admin'], static function ($routes) {
        $routes->get('dispatch', 'DispatchController::index');
        $routes->get('dispatch/assign/(:num)', 'DispatchController::assign/$1');
        $routes->post('dispatch/assign/(:num)', 'DispatchController::storeAssignment/$1');
        $routes->post('dispatch/auto-deny-conflicts', 'DispatchController::autoDenyConflicts');
        $routes->post('dispatch/deny-driver-conflict/(:num)', 'DispatchController::denyDriverConflict/$1');
        $routes->post('dispatch/merge-batch', 'DispatchController::mergeBatch');
    });

    // 4. Driver's Trip Tickets (ADMIN-F-001 rev1) & Dual Certifications
    $routes->group('', ['filter' => 'role:admin,dispatcher,driver,guard,auditor,approver_admin'], static function ($routes) {
        $routes->get('tickets', 'TripTicketController::index');
        $routes->get('tickets/(:num)', 'TripTicketController::show/$1');
        $routes->get('tickets/(:num)/print', 'TripTicketController::printDtt/$1');
        $routes->post('tickets/(:num)/update', 'TripTicketController::updateSectionB/$1');
        // FR-4.2 — 15-minute passenger delay log
        $routes->post('tickets/(:num)/delay', 'TripTicketController::logDelay/$1');
    });

    // 5. Compound Gate Security Checkpoint & QR Scanner
    $routes->group('', ['filter' => 'role:admin,guard,dispatcher,approver_admin'], static function ($routes) {
        $routes->get('gate', 'GateSecurityController::index');
        $routes->match(['get', 'post'], 'gate/verify', 'GateSecurityController::verify');
        $routes->post('gate/record', 'GateSecurityController::recordScan');
    });

    // 6. COA (Commission on Audit) & Executive Compliance
    $routes->group('', ['filter' => 'role:admin,auditor'], static function ($routes) {
        $routes->get('audit', 'AuditController::index');
        $routes->get('audit/export', 'AuditController::exportCsv');
        // FR-7.1/7.2/7.3/7.4 — Compliance portal, COA Form B, fuel analytics,
        // Certificate of Non-Usage generation
        $routes->get('compliance', 'ComplianceController::index');
        $routes->post('compliance/expiry-alerts', 'ComplianceController::sendExpiryAlertsAction');
        $routes->get('compliance/form-b', 'ComplianceController::formB');
        $routes->post('compliance/non-usage', 'ComplianceController::generateCertificates');
        $routes->get('compliance/non-usage/(:num)', 'ComplianceController::certificate/$1');
    });

    // FR-1.3 — Emergency Fast-Track Override (Admin Division Head / Super Admin only)
    $routes->post('approvals/(:num)/emergency', 'ApprovalController::emergencyOverride/$1',
        ['filter' => 'role:admin,approver_admin']);

    // FR-2.2 — Interactive Dispatch Allocation Calendar (shared anti-double-booking view)
    $routes->get('dispatch/calendar', 'DispatchController::calendar', ['filter' => 'role:admin,dispatcher,approver_admin']);
    $routes->get('dispatch/calendar/feed', 'DispatchController::calendarFeed', ['filter' => 'role:admin,dispatcher,approver_admin']);

    // FR-5.1 / FR-5.2 — Mandatory BLOWBAGETS pre-trip safety checklist
    $routes->group('', ['filter' => 'role:admin,dispatcher,driver,auditor,approver_admin'], static function ($routes) {
        $routes->get('safety', 'SafetyChecklistController::index');
        $routes->get('safety/(:num)', 'SafetyChecklistController::create/$1');
        $routes->post('safety/(:num)', 'SafetyChecklistController::store/$1');
    });

    // FR-5.3 — Mechanic Pre-Repair Inspection (PIR) queue & workflow
    $routes->group('', ['filter' => 'role:admin,maintenance,dispatcher,approver_admin'], static function ($routes) {
        $routes->get('pir', 'PirController::index');
        $routes->get('pir/(:num)', 'PirController::show/$1');
        $routes->post('pir/(:num)', 'PirController::update/$1');
    });

    // FR-6.1 / FR-6.2 / FR-6.3 — Tollway RFID balances, reloads & low-balance alerts
    $routes->group('', ['filter' => 'role:admin,dispatcher,auditor,approver_admin'], static function ($routes) {
        $routes->get('rfid', 'RfidController::index');
        $routes->post('rfid/cards', 'RfidController::storeCard');
        $routes->post('rfid/reload', 'RfidController::reload');
    });

    // NFR-2 — Optional TOTP MFA enrollment for any authenticated user
    $routes->get('mfa/setup', 'AuthController::mfaSetup');
    $routes->post('mfa/enable', 'AuthController::mfaEnable');
    $routes->post('mfa/disable', 'AuthController::mfaDisable');

    // User Account Administration — SUPER ADMIN ONLY
    $routes->group('users', ['filter' => 'role:admin'], static function ($routes) {
        $routes->get('', 'UserController::index');
        $routes->get('new', 'UserController::create');
        $routes->post('', 'UserController::store');
        $routes->get('(:num)/edit', 'UserController::edit/$1');
        $routes->post('(:num)', 'UserController::update/$1');
        $routes->post('(:num)/toggle', 'UserController::toggle/$1');
    });

    // Flowchart and System Visualizer — Super Admin, Motorpool Dispatcher & Tier 2 Approver
    $routes->get('admin/flowchart', 'UserController::flowchart', ['filter' => 'role:admin,dispatcher,approver_admin']);

    // In-app notification center (every BRD flow mirrors here)
    $routes->group('', ['filter' => 'auth'], static function ($routes) {
        $routes->get('notifications', 'NotificationController::index');
        $routes->get('notifications/unread', 'NotificationController::unreadCount');
        $routes->post('notifications/read-all', 'NotificationController::markAllRead');
        $routes->post('notifications/(:num)/read', 'NotificationController::markRead/$1');
    });

    // NFR-3 — Immutable system audit trail viewer (Admin/Auditor)
    $routes->get('audit/logs', 'AuditController::logs', ['filter' => 'role:admin,auditor']);

    // Uploaded Receipts & Documents
    $routes->get('uploads/receipts/(:segment)', 'FuelController::viewReceipt/$1');
});

// Driver Portal & Telematics API Endpoints (SDD §6.2 & §5.4)
$routes->group('api/v1', static function ($routes) {
    $routes->post('driver/fuel', 'Api\FuelApi::log');
    $routes->post('driver/incident', 'DriverController::reportIncident');
    $routes->match(['get', 'post'], 'gps/ping', 'Api\GpsApi::ping');
});
