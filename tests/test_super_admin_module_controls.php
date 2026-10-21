<?php
/**
 * Test Suite: Super Admin Module & Menu Controls + Single Updatable Calendar Allocation
 */

$baseUrl = 'http://localhost:8090/index.php';
$cookieAdmin = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_test_admin.txt';
$cookieDisp  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_test_disp.txt';
$cookieReq   = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_test_req.txt';

function httpReq(string $url, string $method = 'GET', array $data = [], string $cookieFile = ''): array
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);

    $redirectUrl = null;
    if (preg_match('/^Location:\s*(.+)$/im', $headers, $matches)) {
        $redirectUrl = trim($matches[1]);
    }

    curl_close($ch);

    return [
        'code'     => $httpCode,
        'headers'  => $headers,
        'body'     => $body,
        'redirect' => $redirectUrl,
    ];
}

function getCsrfToken(string $url, string $cookieFile): array
{
    $res = httpReq($url, 'GET', [], $cookieFile);
    $csrf = '';
    if (preg_match('/<input[^>]*name="csrf_test_name"[^>]*value="([^"]+)"/i', $res['body'], $m)) {
        $csrf = $m[1];
    } elseif (preg_match('/csrfHash\s*=\s*[\'"]([a-f0-9]+)[\'"]/i', $res['body'], $m)) {
        $csrf = $m[1];
    }
    return ['csrf' => $csrf, 'code' => $res['code'], 'body' => $res['body']];
}

function loginUser(string $baseUrl, string $email, string $password, string $cookieFile): bool
{
    @unlink($cookieFile);
    $page = getCsrfToken($baseUrl . '/login', $cookieFile);
    $res = httpReq($baseUrl . '/login', 'POST', [
        'csrf_test_name' => $page['csrf'],
        'email'          => $email,
        'password'       => $password,
    ], $cookieFile);
    return in_array($res['code'], [302, 303]);
}

$passCount = 0;
$failCount = 0;

function assertCheck(bool $condition, string $label) {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] {$label}\n";
        $passCount++;
    } else {
        echo "  [FAIL] {$label}\n";
        $failCount++;
    }
}

echo "=================================================================\n";
echo "    TEST: SUPER ADMIN MODULE CONTROLS & CALENDAR ALLOCATION     \n";
echo "=================================================================\n\n";

// 1. Super Admin Login & Module Control View
echo "--- STEP 1: Super Admin Access to Module Controls ---\n";
assertCheck(loginUser($baseUrl, 'admin@pia.gov.ph', 'Admin_PIA2026!', $cookieAdmin), "Super Admin successfully authenticated");

$modulesPage = httpReq($baseUrl . '/admin/modules', 'GET', [], $cookieAdmin);
assertCheck($modulesPage['code'] === 200, "Super Admin can access /admin/modules");
assertCheck(str_contains($modulesPage['body'], 'System Module &amp; Menu Controls'), "Module Controls Dashboard rendered");
assertCheck(stripos($modulesPage['body'], 'tracking') !== false || stripos($modulesPage['body'], 'telematics') !== false, "Tracking module present in governance matrix");

// 2. Non-Admin (Dispatcher) attempting to access /admin/modules is blocked
echo "\n--- STEP 2: RBAC Defense-in-Depth Protection ---\n";
assertCheck(loginUser($baseUrl, 'dispatcher@pia.gov.ph', 'Dispatch_2026!', $cookieDisp), "Dispatcher authenticated");
$dispModAccess = httpReq($baseUrl . '/admin/modules', 'GET', [], $cookieDisp);
assertCheck(in_array($dispModAccess['code'], [302, 303]), "Dispatcher blocked from accessing /admin/modules (HTTP {$dispModAccess['code']})");

// 3. Super Admin Deactivates Tracking Module Globally
echo "\n--- STEP 3: Super Admin Deactivates Tracking Module ---\n";
$adminCsrf = getCsrfToken($baseUrl . '/admin/modules', $cookieAdmin);
$togglePost = httpReq($baseUrl . '/admin/modules/toggle', 'POST', [
    'csrf_test_name'     => $adminCsrf['csrf'],
    'module_key'         => 'tracking',
    'is_enabled'         => 0,
    'maintenance_reason' => 'Offline for GPS sensor upgrade.',
], $cookieAdmin);

$toggleJson = json_decode($togglePost['body'], true);
assertCheck(($toggleJson['status'] ?? '') === 'success' && $toggleJson['is_enabled'] === 0, "Tracking module deactivated via AJAX toggle");

// 4. Dispatcher Accessing Deactivated Tracking Route is Intercepted
echo "\n--- STEP 4: Verification that Deactivated Module Intercepts Non-Admin Users ---\n";
$dispTracking = httpReq($baseUrl . '/tracking', 'GET', [], $cookieDisp);
assertCheck(in_array($dispTracking['code'], [302, 303]), "Dispatcher accessing deactivated /tracking is redirected (HTTP {$dispTracking['code']})");

// 5. Super Admin Can Still Access Deactivated Module for Maintenance & Debugging
echo "\n--- STEP 5: Super Admin Universal Access Verification ---\n";
$adminTracking = httpReq($baseUrl . '/tracking', 'GET', [], $cookieAdmin);
assertCheck($adminTracking['code'] === 200, "Super Admin retains access to deactivated /tracking to debug and fix bugs");

// 6. Super Admin Reactivates Tracking Module
echo "\n--- STEP 6: Super Admin Reactivates Tracking Module ---\n";
$adminCsrf2 = getCsrfToken($baseUrl . '/admin/modules', $cookieAdmin);
$reactivatePost = httpReq($baseUrl . '/admin/modules/toggle', 'POST', [
    'csrf_test_name' => $adminCsrf2['csrf'],
    'module_key'     => 'tracking',
    'is_enabled'     => 1,
], $cookieAdmin);
$reactivateJson = json_decode($reactivatePost['body'], true);
assertCheck(($reactivateJson['status'] ?? '') === 'success' && $reactivateJson['is_enabled'] === 1, "Tracking module reactivated successfully");

$dispTrackingActive = httpReq($baseUrl . '/tracking', 'GET', [], $cookieDisp);
assertCheck($dispTrackingActive['code'] === 200, "Dispatcher can now access /tracking normally again");

// 7. Single Updatable Process of Calendar Allocation
echo "\n--- STEP 7: Unified Calendar Allocation Update ---\n";
$calPage = getCsrfToken($baseUrl . '/dispatch/calendar', $cookieAdmin);
assertCheck($calPage['code'] === 200, "Interactive Allocation Calendar loaded");
assertCheck(str_contains($calPage['body'], 'quickAllocationPanel'), "Single Updatable Allocation Form integrated in calendar modal");

// Create or find an approved request to test calendar update
$pdo = new PDO('mysql:host=127.0.0.1;dbname=fleet_db', 'root', '');
$testReq = $pdo->query("SELECT id, request_number, departure_time, return_time FROM trip_requests WHERE status = 'approved' AND ticket_id IS NULL LIMIT 1")->fetch();

if (!$testReq) {
    // If no unassigned approved request exists, pick any active request
    $testReq = $pdo->query("SELECT id, request_number, departure_time, return_time FROM trip_requests ORDER BY id DESC LIMIT 1")->fetch();
}

if ($testReq) {
    $vehicle = $pdo->query("SELECT v.id, v.plate_number FROM vehicles v LEFT JOIN pms_records pm ON pm.vehicle_id = v.id WHERE v.status = 'active' AND v.fleet_category = 'pool' AND (pm.is_locked IS NULL OR pm.is_locked = 0) LIMIT 1")->fetch();
    $driver  = $pdo->query("SELECT id, driver_code, first_name, last_name FROM drivers WHERE id = 1 LIMIT 1")->fetch();

    $allocUpdate = httpReq($baseUrl . '/dispatch/calendar/update-allocation', 'POST', [
        'csrf_test_name' => $calPage['csrf'],
        'request_id'     => $testReq['id'],
        'vehicle_id'     => $vehicle['id'],
        'driver_id'      => $driver['id'],
        'month'          => date('Y-m'),
    ], $cookieAdmin);

    assertCheck(in_array($allocUpdate['code'], [302, 303]), "Calendar allocation updated in single updatable process (HTTP {$allocUpdate['code']})");
}

// 8. Reset All Modules to Active Defaults
echo "\n--- STEP 8: Reset All Modules to Default Active State ---\n";
$adminCsrf3 = getCsrfToken($baseUrl . '/admin/modules', $cookieAdmin);
$resetPost = httpReq($baseUrl . '/admin/modules/reset', 'POST', [
    'csrf_test_name' => $adminCsrf3['csrf'],
], $cookieAdmin);
assertCheck(in_array($resetPost['code'], [302, 303]), "All modules reset to default active state");

echo "\n=================================================================\n";
echo "   MODULE CONTROLS TEST SUMMARY: {$passCount} PASSED, {$failCount} FAILED\n";
echo "=================================================================\n";

if ($failCount > 0) {
    exit(1);
}
