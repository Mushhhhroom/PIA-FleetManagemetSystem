<?php

/**
 * End-to-End Functional Test: Full Process from VRS to COA Audit
 *
 * Exercises the complete 10-stage lifecycle:
 * 1. Requestor files VRS (ADMIN-F-018)
 * 2. Dispatcher reviews & endorses (Tier 1)
 * 3. Admin Division Head authorizes (Tier 2)
 * 4. Dispatcher assigns vehicle & driver, generates e-DTT (ADMIN-F-001)
 * 5. Driver performs 10-point BLOWBAGETS pre-trip safety checklist
 * 6. Gate Security Guard verifies QR and records Egress departure
 * 7. En-route telematics / fuel logging
 * 8. Gate Security Guard verifies QR and records Ingress return
 * 9. Post-trip reconciliation & Section B fuel accounting
 * 10. COA Auditor reviews audit trail and exports COA Form B
 */

$pdo = new PDO('mysql:host=127.0.0.1;dbname=fleet_db;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$baseUrl = 'http://localhost:8090';

function httpReq($url, $method = 'GET', $fields = [], $cookie = '', $files = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($cookie) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
    }
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (!empty($files)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        }
    }
    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirect = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    return ['code' => $httpCode, 'redirect' => $redirect, 'body' => $body];
}

function login($baseUrl, $email, $password, $cookie) {
    if (file_exists($cookie)) @unlink($cookie);
    $res = httpReq($baseUrl . '/login', 'GET', [], $cookie);
    preg_match('/name=["\']csrf_test_name["\']\s+value=["\']([^"\']+)["\']/', $res['body'], $m);
    $csrf = $m[1] ?? '';
    $loginRes = httpReq($baseUrl . '/login', 'POST', [
        'csrf_test_name' => $csrf,
        'email'          => $email,
        'password'       => $password,
    ], $cookie);
    return $loginRes;
}

function getCsrf($baseUrl, $path, $cookie) {
    $res = httpReq($baseUrl . $path, 'GET', [], $cookie);
    if (!preg_match('/name=["\']csrf_test_name["\']\s+value=["\']([^"\']+)["\']/', $res['body'], $m)) {
        if (!empty($res['redirect'])) {
            $res = httpReq($res['redirect'], 'GET', [], $cookie);
            preg_match('/name=["\']csrf_test_name["\']\s+value=["\']([^"\']+)["\']/', $res['body'], $m);
        }
    }
    return ['csrf' => $m[1] ?? '', 'body' => $res['body'], 'code' => $res['code']];
}

$passCount = 0;
$failCount = 0;

function assertTest($condition, $message) {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "  [PASS] {$message}\n";
    } else {
        $failCount++;
        echo "  [FAIL] {$message}\n";
    }
}

echo "=================================================================\n";
echo "    FULL LIFECYCLE VERIFICATION: FROM VRS TO COA AUDIT (10 STAGES) \n";
echo "=================================================================\n\n";

$cookieReq  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_req.txt';
$cookieDisp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_disp.txt';
$cookieAdm  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_adm.txt';
$cookieDrv  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_drv.txt';
$cookieGrd  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_grd.txt';
$cookieAud  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_aud.txt';

$createdRequestId = null;
$createdTicketId  = null;

try {
    // -------------------------------------------------------------
    // STAGE 1: Travel Demand (VRS Submission - ADMIN-F-018)
    // -------------------------------------------------------------
    echo "--- STAGE 1: Requisitioner submits digital VRS (ADMIN-F-018) ---\n";
    login($baseUrl, 'requestor@pia.gov.ph', 'Requestor_2026!', $cookieReq);

    $formInfo = getCsrf($baseUrl, '/requests/new', $cookieReq);
    assertTest($formInfo['code'] === 200, "VRS form /requests/new loaded successfully");

    $depTime = date('Y-m-d H:i:s', strtotime('+2 days 08:00:00'));
    $retTime = date('Y-m-d H:i:s', strtotime('+2 days 18:00:00'));

    $vrsPost = httpReq($baseUrl . '/requests', 'POST', [
        'csrf_test_name'         => $formInfo['csrf'],
        'office_id'              => 1,
        'department_id'          => 1,
        'office_scope'           => 'central',
        'destination'            => 'Malacañang Palace Press Briefing Room, Manila',
        'purpose'                => 'Official Media Coverage - Presidential Press Conference',
        'passenger_names'        => 'Juan Dela Cruz, Pedro Santos, Maria Reyes',
        'passenger_count'        => 3,
        'departure_time'         => $depTime,
        'return_time'            => $retTime,
        'requested_vehicle_type' => 'van',
    ], $cookieReq);

    assertTest(in_array($vrsPost['code'], [200, 302, 303]), "VRS submission HTTP status {$vrsPost['code']}");

    // Retrieve latest VRS created by Juan Dela Cruz
    $latestReq = $pdo->query("SELECT * FROM trip_requests WHERE requestor_name = 'Juan Dela Cruz' ORDER BY id DESC LIMIT 1")->fetch();
    assertTest(!empty($latestReq), "VRS record created in database: {$latestReq['request_number']}");
    assertTest($latestReq['status'] === 'pending_dispatcher', "VRS initial status is 'pending_dispatcher'");
    assertTest(!empty($latestReq['sla_deadline']), "4-Hour SLA deadline initialized: {$latestReq['sla_deadline']}");

    $createdRequestId = (int)$latestReq['id'];

    // Verify VRS Printable Slip
    $printRes = httpReq($baseUrl . "/requests/{$createdRequestId}/print", 'GET', [], $cookieReq);
    assertTest($printRes['code'] === 200 && str_contains($printRes['body'], 'ADMIN-F-018'), "VRS Printable Slip renders Form ADMIN-F-018");

    // -------------------------------------------------------------
    // STAGE 2: Tier 1 Operational Review (Motorpool Dispatcher)
    // -------------------------------------------------------------
    echo "\n--- STAGE 2: Motorpool Dispatcher Tier 1 Endorsement ---\n";
    login($baseUrl, 'dispatcher@pia.gov.ph', 'Dispatch_2026!', $cookieDisp);

    $apprInfo = getCsrf($baseUrl, '/approvals', $cookieDisp);
    assertTest($apprInfo['code'] === 200, "Approvals portal loaded by Dispatcher");

    $endorseRes = httpReq($baseUrl . "/approvals/{$createdRequestId}/action", 'POST', [
        'csrf_test_name' => $apprInfo['csrf'],
        'action'         => 'approve',
        'remarks'        => 'Documents and route verified. Endorsed by Motorpool Dispatcher.',
    ], $cookieDisp);

    $reqTier1 = $pdo->query("SELECT status, dispatcher_action, dispatcher_remarks FROM trip_requests WHERE id = {$createdRequestId}")->fetch();
    assertTest($reqTier1['status'] === 'pending_admin', "VRS status transitioned to 'pending_admin'");
    assertTest($reqTier1['dispatcher_action'] === 'approved', "Tier 1 dispatcher action recorded as 'approved'");

    // -------------------------------------------------------------
    // STAGE 3: Tier 2 Final Authorization (Admin Division Head)
    // -------------------------------------------------------------
    echo "\n--- STAGE 3: Administrative Division Head Tier 2 Authorization ---\n";
    login($baseUrl, 'admin.head@pia.gov.ph', 'AdminHead_2026!', $cookieAdm);

    $apprAdmInfo = getCsrf($baseUrl, '/approvals', $cookieAdm);
    $authRes = httpReq($baseUrl . "/approvals/{$createdRequestId}/action", 'POST', [
        'csrf_test_name' => $apprAdmInfo['csrf'],
        'action'         => 'approve',
        'remarks'        => 'Official travel approved by Administrative Division Head.',
    ], $cookieAdm);

    $reqTier2 = $pdo->query("SELECT status, admin_action, admin_remarks FROM trip_requests WHERE id = {$createdRequestId}")->fetch();
    assertTest($reqTier2['status'] === 'approved', "VRS status transitioned to 'approved'");
    assertTest($reqTier2['admin_action'] === 'approved', "Tier 2 admin action recorded as 'approved'");

    // -------------------------------------------------------------
    // STAGE 4: Motorpool Dispatch & Asset Allocation (e-DTT generation)
    // -------------------------------------------------------------
    echo "\n--- STAGE 4: Motorpool Dispatch & Asset Allocation ---\n";
    login($baseUrl, 'dispatcher@pia.gov.ph', 'Dispatch_2026!', $cookieDisp);

    $assignInfo = getCsrf($baseUrl, "/dispatch/assign/{$createdRequestId}", $cookieDisp);
    assertTest($assignInfo['code'] === 200, "Dispatch assignment form loaded for {$latestReq['request_number']}");

    // Select first active, unlocked pool vehicle (e.g. NCB-4519 or NFE-3392) and driver Rodrigo Santos (id 1)
    $vehicle = $pdo->query("SELECT v.id, v.plate_number, v.odometer_km FROM vehicles v LEFT JOIN pms_records pm ON pm.vehicle_id = v.id WHERE v.status = 'active' AND v.fleet_category = 'pool' AND (pm.is_locked IS NULL OR pm.is_locked = 0) LIMIT 1")->fetch();
    $driver  = $pdo->query("SELECT id, driver_code, first_name, last_name FROM drivers WHERE id = 1 LIMIT 1")->fetch();

    $assignPost = httpReq($baseUrl . "/dispatch/assign/{$createdRequestId}", 'POST', [
        'csrf_test_name' => $assignInfo['csrf'],
        'vehicle_id'     => $vehicle['id'],
        'driver_id'      => $driver['id'],
    ], $cookieDisp);

    echo "DEBUG ASSIGN POST: code={$assignPost['code']} redirect={$assignPost['redirect']}\n";
    $freshReq = $pdo->query("SELECT status, ticket_id FROM trip_requests WHERE id = {$createdRequestId}")->fetch();
    if ($freshReq['status'] !== 'dispatched') {
        $follow = httpReq($assignPost['redirect'] ?: $baseUrl . '/dispatch', 'GET', [], $cookieDisp);
        if (preg_match('/<div[^>]*class="[^"]*alert-danger[^"]*"[^>]*>(.*?)<\/div>/is', $follow['body'], $m)) {
            echo "ALERT DANGER: " . trim(strip_tags($m[1])) . "\n";
        } elseif (preg_match('/<div[^>]*class="[^"]*alert[^"]*"[^>]*>(.*?)<\/div>/is', $follow['body'], $m)) {
            echo "ALERT: " . trim(strip_tags($m[1])) . "\n";
        } else {
            echo "FOLLOW BODY (500 chars): " . substr(strip_tags($follow['body']), 0, 500) . "\n";
        }
    }
    assertTest($freshReq['status'] === 'dispatched', "VRS status transitioned to 'dispatched'");
    assertTest(!empty($freshReq['ticket_id']), "e-DTT linked to request (Ticket #{$freshReq['ticket_id']})");

    $createdTicketId = (int)$freshReq['ticket_id'];
    $ticket = $pdo->query("SELECT * FROM trip_tickets WHERE id = {$createdTicketId}")->fetch();

    assertTest(!empty($ticket['ticket_serial_no']), "e-DTT Serial Number generated: {$ticket['ticket_serial_no']}");
    assertTest(!empty($ticket['qr_crypt_token']), "HMAC-SHA256 Cryptographic QR Token generated");
    assertTest($ticket['status'] === 'issued', "e-DTT initial status is 'issued'");

    // Verify e-DTT Printable Document (ADMIN-F-001)
    $printDtt = httpReq($baseUrl . "/tickets/{$createdTicketId}/print", 'GET', [], $cookieDisp);
    assertTest($printDtt['code'] === 200 && str_contains($printDtt['body'], 'ADMIN-F-001'), "Printable e-DTT renders Official Form ADMIN-F-001");

    // -------------------------------------------------------------
    // STAGE 5: Pre-Trip Safety Inspection (BLOWBAGETS)
    // -------------------------------------------------------------
    echo "\n--- STAGE 5: Driver Pre-Trip BLOWBAGETS Checklist (FR-5.1) ---\n";
    login($baseUrl, 'driver.santos@pia.gov.ph', 'Driver_2026!', $cookieDrv);

    $driverTrips = httpReq($baseUrl . '/driver/trips', 'GET', [], $cookieDrv);
    assertTest($driverTrips['code'] === 200, "Driver Mobile Web Portal /driver/trips accessible");

    // Submit complete 10-point BLOWBAGETS checklist (all passed)
    $safetyInfo = getCsrf($baseUrl, "/safety/{$createdTicketId}", $cookieDrv);
    $safetyPost = httpReq($baseUrl . "/safety/{$createdTicketId}", 'POST', [
        'csrf_test_name' => $safetyInfo['csrf'],
        'check'          => [
            'battery'  => 'pass',
            'lights'   => 'pass',
            'oil'      => 'pass',
            'water'    => 'pass',
            'brake'    => 'pass',
            'air'      => 'pass',
            'gas'      => 'pass',
            'engine'   => 'pass',
            'tire'     => 'pass',
            'steering' => 'pass',
        ],
        'remarks'        => 'All systems checked. Vehicle roadworthy for travel.',
    ], $cookieDrv);

    $safetyRow = $pdo->query("SELECT * FROM safety_checks WHERE trip_ticket_id = {$createdTicketId} ORDER BY id DESC LIMIT 1")->fetch();
    assertTest(!empty($safetyRow) && $safetyRow['result'] === 'pass', "BLOWBAGETS safety checklist recorded with result 'pass'");

    // -------------------------------------------------------------
    // STAGE 6: Gate-Out Checkpoint Clearance (Security Guard)
    // -------------------------------------------------------------
    echo "\n--- STAGE 6: Compound Gate-Out Checkpoint Clearance ---\n";
    login($baseUrl, 'guard@pia.gov.ph', 'Guard_2026!', $cookieGrd);

    $gateInfo = getCsrf($baseUrl, '/gate', $cookieGrd);
    assertTest($gateInfo['code'] === 200, "Gate Checkpoint HUD loaded by Compound Security Officer");

    // Verify token at gate
    $verifyRes = httpReq($baseUrl . '/gate/verify', 'POST', [
        'csrf_test_name' => $gateInfo['csrf'],
        'token'          => $ticket['qr_crypt_token'],
    ], $cookieGrd);
    $verifyJson = json_decode($verifyRes['body'], true);

    assertTest(($verifyJson['status'] ?? '') === 'success', "Gate scanner successfully resolved ticket by QR Token");
    assertTest(($verifyJson['verdict'] ?? '') === 'pass', "Gate clearance verdict is 'pass'");
    assertTest(($verifyJson['event_type'] ?? '') === 'egress', "Recommended gate action is 'egress' (Departure)");

    // Record Egress departure
    $startOdo = (float)$vehicle['odometer_km'];
    $egressPost = httpReq($baseUrl . '/gate/record', 'POST', [
        'csrf_test_name'       => $verifyJson['csrf_hash'] ?? $gateInfo['csrf'],
        'ticket_id'            => $createdTicketId,
        'event_type'           => 'egress',
        'odometer_reading'     => $startOdo,
        'driver_validated'     => 1,
        'passengers_validated' => 1,
        'remarks'              => 'Compound egress authorized. Driver and passengers verified.',
    ], $cookieGrd);

    $ticketDeparted = $pdo->query("SELECT status, departure_time, start_odometer FROM trip_tickets WHERE id = {$createdTicketId}")->fetch();
    assertTest($ticketDeparted['status'] === 'departed', "Ticket status transitioned to 'departed'");
    assertTest(!empty($ticketDeparted['departure_time']), "Departure timestamp stamped by Gate Checkpoint");

    // -------------------------------------------------------------
    // STAGE 7: En-Route Execution & Driver Action Logging
    // -------------------------------------------------------------
    echo "\n--- STAGE 7: En-Route Trip Actions & Driver Portal ---\n";
    login($baseUrl, 'driver.santos@pia.gov.ph', 'Driver_2026!', $cookieDrv);

    // 15-Minute Passenger Delay Logging
    $delayInfo = getCsrf($baseUrl, '/driver/trips', $cookieDrv);
    $delayPost = httpReq($baseUrl . "/tickets/{$createdTicketId}/delay", 'POST', [
        'csrf_test_name' => $delayInfo['csrf'],
        'delay_minutes'  => 15,
        'delay_reason'   => 'Waiting for press equipment loading and media badges.',
    ], $cookieDrv);

    $delayLog = $pdo->query("SELECT * FROM passenger_delay_logs WHERE trip_ticket_id = {$createdTicketId}")->fetch();
    assertTest(!empty($delayLog) && (int)$delayLog['delay_minutes'] === 15, "15-Minute passenger delay recorded (FR-4.2)");

    // -------------------------------------------------------------
    // STAGE 8: Gate-In Checkpoint Clearance (Compound Return)
    // -------------------------------------------------------------
    echo "\n--- STAGE 8: Compound Gate-In Return Clearance ---\n";
    login($baseUrl, 'guard@pia.gov.ph', 'Guard_2026!', $cookieGrd);

    $gateInInfo = getCsrf($baseUrl, '/gate', $cookieGrd);
    $verifyInRes = httpReq($baseUrl . '/gate/verify', 'POST', [
        'csrf_test_name' => $gateInInfo['csrf'],
        'token'          => $ticket['ticket_serial_no'],
    ], $cookieGrd);
    $verifyInJson = json_decode($verifyInRes['body'], true);

    assertTest(($verifyInJson['verdict'] ?? '') === 'pass', "Return verification verdict is 'pass'");
    assertTest(($verifyInJson['event_type'] ?? '') === 'ingress', "Recommended gate action is 'ingress' (Return)");

    // Return with 120 km traveled
    $returnOdo = $startOdo + 120.0;
    $ingressPost = httpReq($baseUrl . '/gate/record', 'POST', [
        'csrf_test_name'       => $verifyInJson['csrf_hash'] ?? $gateInInfo['csrf'],
        'ticket_id'            => $createdTicketId,
        'event_type'           => 'ingress',
        'odometer_reading'     => $returnOdo,
        'driver_validated'     => 1,
        'passengers_validated' => 1,
        'remarks'              => 'Compound ingress verified. Official travel completed safely.',
    ], $cookieGrd);

    $ticketReturned = $pdo->query("SELECT status, arrival_back_time, return_odometer FROM trip_tickets WHERE id = {$createdTicketId}")->fetch();
    assertTest($ticketReturned['status'] === 'returned', "Ticket status transitioned to 'returned'");
    assertTest((float)$ticketReturned['return_odometer'] === $returnOdo, "Return odometer logged: {$returnOdo} KM");

    // -------------------------------------------------------------
    // STAGE 9: Post-Trip Reconciliation & Section B Accounting
    // -------------------------------------------------------------
    echo "\n--- STAGE 9: Post-Trip Reconciliation & Section B Performance Accounting ---\n";
    login($baseUrl, 'dispatcher@pia.gov.ph', 'Dispatch_2026!', $cookieDisp);

    $ticketViewInfo = getCsrf($baseUrl, "/tickets/{$createdTicketId}", $cookieDisp);
    assertTest($ticketViewInfo['code'] === 200, "Trip ticket view /tickets/{$createdTicketId} loaded");

    // Perform Section B reconciliation with dual certifications
    $fuelStart     = 60.0;
    $fuelPurchased = 20.0;
    $fuelCost      = 1300.0;
    $fuelUsed      = 12.0; // 120 km / 12 L = 10.0 km/L efficiency
    $fuelEnd       = ($fuelStart + 0.0 + $fuelPurchased) - $fuelUsed; // 68.0 L

    $reconcilePost = httpReq($baseUrl . "/tickets/{$createdTicketId}/update", 'POST', [
        'csrf_test_name'            => $ticketViewInfo['csrf'],
        'fuel_balance_start_liters' => $fuelStart,
        'fuel_issued_stock_liters'  => 0.0,
        'fuel_purchased_liters'     => $fuelPurchased,
        'fuel_purchased_cost'       => $fuelCost,
        'fuel_used_liters'          => $fuelUsed,
        'toll_expense'              => 250.0,
        'toll_provider'             => 'autosweep',
        'driver_certified'          => 1,
        'passenger_certified'       => 1,
        'passenger_certifier_name'  => 'Juan Dela Cruz',
        'notes'                     => 'Trip reconciled successfully. Distance: 120 km. Efficiency: 10.0 km/L.',
    ], $cookieDisp);

    $ticketFinal = $pdo->query("SELECT * FROM trip_tickets WHERE id = {$createdTicketId}")->fetch();
    $reqFinal    = $pdo->query("SELECT status FROM trip_requests WHERE id = {$createdRequestId}")->fetch();

    assertTest($ticketFinal['status'] === 'completed', "Trip ticket status is 'completed'");
    assertTest($reqFinal['status'] === 'completed', "VRS request status transitioned to 'completed'");
    assertTest((float)$ticketFinal['total_distance_km'] === 120.0, "Total distance calculated: 120 KM");
    assertTest((float)$ticketFinal['fuel_balance_end_liters'] === 68.0, "Section B Fuel Ending formula: (60 + 20) - 12 = 68.0 L");
    assertTest((float)$ticketFinal['fuel_efficiency_kml'] === 10.0, "Fuel efficiency calculated: 10.0 KM/L");
    assertTest((int)$ticketFinal['fuel_anomaly_flag'] === 0, "Fuel anomaly flag is 0 (normal benchmark)");
    assertTest((int)$ticketFinal['driver_certified'] === 1 && (int)$ticketFinal['passenger_certified'] === 1, "Dual certifications verified (Driver & Passenger)");

    // -------------------------------------------------------------
    // STAGE 10: Regulatory Compliance & COA Audit Reporting
    // -------------------------------------------------------------
    echo "\n--- STAGE 10: Regulatory Compliance & COA Audit Reporting ---\n";
    login($baseUrl, 'auditor@pia.gov.ph', 'Auditor_2026!', $cookieAud);

    // 1. Audit Dashboard
    $auditRes = httpReq($baseUrl . '/audit', 'GET', [], $cookieAud);
    assertTest($auditRes['code'] === 200, "COA Audit Portal /audit loaded successfully");
    assertTest(str_contains($auditRes['body'], $ticketFinal['ticket_serial_no']), "Completed ticket {$ticketFinal['ticket_serial_no']} visible in COA audit table");

    // 2. COA Form B Export
    $formBRes = httpReq($baseUrl . '/compliance/form-b', 'GET', [], $cookieAud);
    assertTest($formBRes['code'] === 200, "COA Form B /compliance/form-b rendered successfully");
    assertTest(str_contains($formBRes['body'], $ticketFinal['ticket_serial_no']), "COA Form B includes verified ticket {$ticketFinal['ticket_serial_no']}");

    // 3. COA CSV Report Export
    $csvRes = httpReq($baseUrl . '/audit/export', 'GET', [], $cookieAud);
    assertTest($csvRes['code'] === 200 && str_contains($csvRes['body'], $ticketFinal['ticket_serial_no']), "COA CSV Audit Export includes completed ticket");

    // 4. Immutable Audit Trail Inspection
    $logsRes = httpReq($baseUrl . '/audit/logs', 'GET', [], $cookieAud);
    assertTest($logsRes['code'] === 200, "Immutable Audit Trail /audit/logs loaded successfully");

} finally {
    // Clean up created test records
    echo "\n--- Housekeeping: Cleaning up temporary verification records ---\n";
    if ($createdTicketId) {
        $pdo->prepare("DELETE FROM gate_logs WHERE trip_ticket_id = ?")->execute([$createdTicketId]);
        $pdo->prepare("DELETE FROM safety_checks WHERE trip_ticket_id = ?")->execute([$createdTicketId]);
        $pdo->prepare("DELETE FROM passenger_delay_logs WHERE trip_ticket_id = ?")->execute([$createdTicketId]);
        $pdo->prepare("DELETE FROM trip_tickets WHERE id = ?")->execute([$createdTicketId]);
    }
    if ($createdRequestId) {
        $pdo->prepare("DELETE FROM trip_requests WHERE id = ?")->execute([$createdRequestId]);
    }
    foreach ([$cookieReq, $cookieDisp, $cookieAdm, $cookieDrv, $cookieGrd, $cookieAud] as $c) {
        if (file_exists($c)) @unlink($c);
    }
}

echo "\n=================================================================\n";
echo "   LIFECYCLE TEST SUMMARY: {$passCount} PASSED, {$failCount} FAILED\n";
echo "=================================================================\n";

if ($failCount === 0) {
    echo "\nSUCCESS: The entire 10-stage process from VRS to COA Audit executed flawlessly!\n";
    exit(0);
} else {
    echo "\nFAILURE: Some stages encountered errors.\n";
    exit(1);
}
