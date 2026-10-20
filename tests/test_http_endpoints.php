<?php

$cookieFile = __DIR__ . '/cookie.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

$ch = curl_init('http://localhost:8090/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$html = curl_exec($ch);

// Extract CSRF token
preg_match('/name=["\']csrf_test_name["\']\s+value=["\']([^"\']+)["\']/', $html, $m);
$token = $m[1] ?? '';

// Post login as Super Admin
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8090/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'csrf_test_name' => $token,
    'email'          => 'admin@pia.gov.ph',
    'password'       => 'Admin_PIA2026!'
]));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$res = curl_exec($ch);

$pages = [
    '/dashboard',
    '/tracking',
    '/vehicles',
    '/vehicles/new',
    '/vehicles/1',
    '/drivers',
    '/drivers/new',
    '/drivers/1',
    '/fuel',
    '/fuel/new',
    '/maintenance',
    '/maintenance/new',
    '/requests',
    '/requests/new',
    '/requests/1',
    '/requests/1/print',
    '/approvals',
    '/dispatch',
    '/dispatch/calendar',
    '/dispatch/assign/1',
    '/tickets',
    '/tickets/1',
    '/tickets/1/print',
    '/safety',
    '/pir',
    '/rfid',
    '/gate',
    '/audit',
    '/audit/logs',
    '/compliance',
    '/compliance/form-b',
    '/users',
    '/admin/flowchart',
    '/driver/trips',
];

echo "=== TESTING ALL WEB INTERFACES (HTTP AUTHENTICATED) ===" . PHP_EOL;
$allPass = true;
foreach ($pages as $p) {
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8090' . $p);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    $pageHtml = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hasPhpError = str_contains($pageHtml, 'Fatal error') || str_contains($pageHtml, 'Whoops!');
    $statusText = ($code === 200 && !$hasPhpError) ? "[OK]" : "[FAIL]";
    if ($statusText === "[FAIL]") $allPass = false;
    echo sprintf("%-25s : HTTP %d %s" . PHP_EOL, $p, $code, $statusText);
}
curl_close($ch);

if (file_exists($cookieFile)) unlink($cookieFile);

echo PHP_EOL . ($allPass ? "ALL HTTP ENDPOINTS RETURNED 200 OK!" : "SOME ENDPOINTS FAILED") . PHP_EOL;
