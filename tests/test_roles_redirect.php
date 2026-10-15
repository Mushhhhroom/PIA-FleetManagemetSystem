<?php

$roles = [
    'admin@pia.gov.ph'       => ['pass' => 'Admin_PIA2026!',    'expected' => '/'],
    'requestor@pia.gov.ph'   => ['pass' => 'Requestor_2026!',   'expected' => '/requests'],
    'admin.head@pia.gov.ph'  => ['pass' => 'AdminHead_2026!',   'expected' => '/approvals'],
    'dispatcher@pia.gov.ph'  => ['pass' => 'Dispatch_2026!',    'expected' => '/dispatch'],
    'guard@pia.gov.ph'       => ['pass' => 'Guard_2026!',       'expected' => '/gate'],
    'auditor@pia.gov.ph'     => ['pass' => 'Auditor_2026!',     'expected' => '/audit'],
    'driver.santos@pia.gov.ph'=> ['pass' => 'Driver_2026!',     'expected' => '/driver/trips'],
    'maintenance@fleet.com'  => ['pass' => 'Maintenance_2026!', 'expected' => '/maintenance'],
];

echo "=== TESTING ROLE-BASED LOGIN REDIRECTION ===" . PHP_EOL;

foreach ($roles as $email => $info) {
    $cookie = __DIR__ . '/cookie_' . md5($email) . '.txt';
    if (file_exists($cookie)) unlink($cookie);

    // Get CSRF
    $ch = curl_init('http://localhost:8090/login');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
    $html = curl_exec($ch);

    preg_match('/name=["\']csrf_test_name["\']\s+value=["\']([^"\']+)["\']/', $html, $m);
    $csrf = $m[1] ?? '';

    // POST Login
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8090/login');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'csrf_test_name' => $csrf,
        'email'          => $email,
        'password'       => $info['pass'],
    ]));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't auto follow to inspect Location header
    $response = curl_exec($ch);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);

    if (file_exists($cookie)) unlink($cookie);

    $matched = str_ends_with($redirectUrl, $info['expected']) || ($info['expected'] === '/' && (str_ends_with($redirectUrl, ':8090/') || str_ends_with($redirectUrl, 'dashboard')));
    echo sprintf("%-28s -> %-20s [%s]" . PHP_EOL, $email, $redirectUrl ?: 'NO REDIRECT', $matched ? 'PASS' : 'FAIL');
}

// Test CSV Export
$ch = curl_init('http://localhost:8090/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookie_admin.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookie_admin.txt');
// Fetch login page for CSRF token
$loginHtml = curl_exec($ch);
preg_match('/name=["\']csrf_test_name["\']\s+value=["\']([^"\']+)["\']/', $loginHtml, $m);
$csrf = $m[1] ?? '';

curl_setopt($ch, CURLOPT_URL, 'http://localhost:8090/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'csrf_test_name' => $csrf,
    'email'          => 'admin@pia.gov.ph',
    'password'       => 'Admin_PIA2026!',
]));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);

// Now request CSV
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8090/audit/export');
curl_setopt($ch, CURLOPT_HTTPGET, true);
$csv = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (file_exists(__DIR__ . '/cookie_admin.txt')) unlink(__DIR__ . '/cookie_admin.txt');

echo PHP_EOL . "=== TESTING COA CSV EXPORT ===" . PHP_EOL;
echo "HTTP Code: " . $httpCode . PHP_EOL;
echo "CSV First Line: " . strtok($csv, "\r\n") . PHP_EOL;
echo (str_contains($csv, 'Ticket Serial No') ? "[PASS] Valid COA CSV Header" : "[FAIL] Invalid CSV") . PHP_EOL;
