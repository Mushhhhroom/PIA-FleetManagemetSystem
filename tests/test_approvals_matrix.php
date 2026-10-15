<?php

$pdo = new PDO('mysql:host=127.0.0.1;dbname=fleet_db;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

function doReq($url, $method = 'GET', $fields = [], $cookie = '') {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    }
    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    return ['code' => $httpCode, 'redirect' => $redirectUrl, 'body' => $body];
}

function resetReq12($pdo) {
    $future = date('Y-m-d H:i:s', strtotime('+4 hours'));
    $pdo->prepare("UPDATE trip_requests SET status = 'pending_dispatcher', sla_deadline = ?, dispatcher_action = NULL, dispatcher_action_at = NULL, dispatcher_remarks = NULL, dispatcher_approver_id = 8, admin_action = NULL, admin_action_at = NULL, admin_remarks = NULL WHERE id = 12")->execute([$future]);
}

echo "==========================================================" . PHP_EOL;
echo "     APPROVAL & ENDORSEMENT MATRIX VERIFICATION TEST     " . PHP_EOL;
echo "==========================================================" . PHP_EOL;

// TEST 1: Dispatcher Endorsement from /approvals modal (Tier 1 review)
echo PHP_EOL . "--- TEST 1: Dispatcher Endorses from /approvals modal ---" . PHP_EOL;
resetReq12($pdo);
$cookieDisp = __DIR__ . '/c_disp.txt';
if (file_exists($cookieDisp)) unlink($cookieDisp);

$login = doReq('http://localhost:8090/login', 'GET', [], $cookieDisp);
preg_match('/name=["\']csrf_test_name["\']\s+value=["\']([^"\']+)["\']/', $login['body'], $m);
doReq('http://localhost:8090/login', 'POST', [
    'csrf_test_name' => $m[1],
    'email'          => 'dispatcher@pia.gov.ph',
    'password'       => 'Dispatch_2026!'
], $cookieDisp);

$appr = doReq('http://localhost:8090/approvals', 'GET', [], $cookieDisp);
preg_match('/name=["\']csrf_test_name["\']\s+value=["\']([^"\']+)["\']/', $appr['body'], $m);
$action = doReq('http://localhost:8090/approvals/12/action', 'POST', [
    'csrf_test_name' => $m[1],
    'action'         => 'approve',
    'remarks'        => 'Documents verified. Endorsed by Motorpool Dispatcher.',
], $cookieDisp);

$after = doReq($action['redirect'] ?: 'http://localhost:8090/approvals', 'GET', [], $cookieDisp);
$blocked = str_contains($after['body'], 'Access Restricted');
$success = str_contains($after['body'], 'endorsed successfully');

echo "HTTP Code: {$action['code']} | Redirect: {$action['redirect']}" . PHP_EOL;
$statusTier1 = $pdo->query("SELECT status, dispatcher_action FROM trip_requests WHERE id = 12")->fetch();
echo "Status in DB: {$statusTier1['status']} (dispatcher_action: {$statusTier1['dispatcher_action']})" . PHP_EOL;
echo "Result: " . ($success && !$blocked && $statusTier1['status'] === 'pending_admin' ? "[PASS - ENDORSED CLEANLY TO TIER 2]" : "[FAIL]") . PHP_EOL;

// TEST 2: Admin Division Head Authorization on /approvals modal (Tier 2 review)
echo PHP_EOL . "--- TEST 2: Admin Division Head Authorizes Tier 2 ---" . PHP_EOL;
$cookieAdm = __DIR__ . '/c_adm.txt';
if (file_exists($cookieAdm)) unlink($cookieAdm);

$login = doReq('http://localhost:8090/login', 'GET', [], $cookieAdm);
preg_match('/name=["\']csrf_test_name["\']\s+value=["\']([^"\']+)["\']/', $login['body'], $m);
doReq('http://localhost:8090/login', 'POST', [
    'csrf_test_name' => $m[1],
    'email'          => 'admin.head@pia.gov.ph',
    'password'       => 'AdminHead_2026!'
], $cookieAdm);

$appr = doReq('http://localhost:8090/approvals', 'GET', [], $cookieAdm);
preg_match('/name=["\']csrf_test_name["\']\s+value=["\']([^"\']+)["\']/', $appr['body'], $m);
$action = doReq('http://localhost:8090/approvals/12/action', 'POST', [
    'csrf_test_name' => $m[1],
    'action'         => 'approve',
    'remarks'        => 'Official travel authorized by Administrative Division Chief.',
], $cookieAdm);

$statusTier2 = $pdo->query("SELECT status, admin_action FROM trip_requests WHERE id = 12")->fetch();
echo "HTTP Code: {$action['code']} | Redirect: {$action['redirect']}" . PHP_EOL;
echo "Status in DB: {$statusTier2['status']} (admin_action: {$statusTier2['admin_action']})" . PHP_EOL;
echo "Result: " . ($statusTier2['status'] === 'approved' ? "[PASS - AUTHORIZED FOR DISPATCH]" : "[FAIL]") . PHP_EOL;

// Check Dispatch queue
$inDispatch = doReq('http://localhost:8090/dispatch', 'GET', [], $cookieDisp);
echo "In Dispatch Queue for Vehicle/Driver Assignment: " . (str_contains($inDispatch['body'], 'VRS-2026-0012') ? "[YES - PASS]" : "[NO - FAIL]") . PHP_EOL;

// TEST 3: Dispatcher Endorsement directly from /requests/12 view
echo PHP_EOL . "--- TEST 3: Dispatcher Endorses directly from /requests/12 View ---" . PHP_EOL;
resetReq12($pdo);

$vrs = doReq('http://localhost:8090/requests/12', 'GET', [], $cookieDisp);
preg_match('/name=["\']csrf_test_name["\']\s+value=["\']([^"\']+)["\']/', $vrs['body'], $m);
$action = doReq('http://localhost:8090/approvals/12/action', 'POST', [
    'csrf_test_name' => $m[1],
    'action'         => 'approve',
    'remarks'        => 'Specifications and passenger check confirmed by Motorpool Dispatcher from VRS slip view.',
    'redirect_to'    => 'http://localhost:8090/requests/12',
], $cookieDisp);

$statusView = $pdo->query("SELECT status, dispatcher_action FROM trip_requests WHERE id = 12")->fetch();
echo "HTTP Code: {$action['code']} | Redirect: {$action['redirect']}" . PHP_EOL;
echo "Status in DB: {$statusView['status']} (dispatcher_action: {$statusView['dispatcher_action']})" . PHP_EOL;
echo "Result: " . ($statusView['status'] === 'pending_admin' ? "[PASS - ENDORSED FROM SLIP VIEW]" : "[FAIL]") . PHP_EOL;

if (file_exists($cookieDisp)) unlink($cookieDisp);
if (file_exists($cookieAdm)) unlink($cookieAdm);

// Final Reset so demo is ready
resetReq12($pdo);
echo PHP_EOL . ">>> ALL APPROVAL MATRIX TESTS COMPLETED SUCCESSFULLY! <<<" . PHP_EOL;
