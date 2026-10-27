<?php
/**
 * Import database/fleet_db_demo.sql into a remote MySQL-compatible host.
 *
 * TLS: TiDB Cloud (and most cloud MySQL) requires an encrypted connection.
 * MYSQLI_CLIENT_SSL is used without CA pinning — the handshake is encrypted
 * and the client skips server-cert validation (acceptable for a demo tier).
 *
 * Usage:
 *   php import_db.php <host> <port> <user> <password> [dbName]
 */
if ($argc < 5) {
    fwrite(STDERR, "usage: php import_db.php <host> <port> <user> <password> [dbName]\n");
    exit(2);
}

[, $host, $port, $user, $pass] = $argv;
$dbName = $argv[5] ?? 'fleet_db';
$dumpFile = __DIR__ . '/fleet_db_demo.sql';

mysqli_report(MYSQLI_REPORT_OFF);
$dbh = mysqli_init();
$dbh->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
@$dbh->real_connect($host, $user, $pass, '', (int) $port, null, MYSQLI_CLIENT_SSL);

if ($dbh->connect_errno) {
    fwrite(STDERR, "CONNECT FAILED [{$dbh->connect_errno}]: {$dbh->connect_error}\n");
    fwrite(STDERR, "hint: if this is a TLS error, the host may need a CA file instead of plain SSL\n");
    exit(1);
}
echo "Connected to {$host}:{$port} (TLS=" . ($dbh->server_info ? 'yes' : '?') . "), server {$dbh->server_info}\n";

$sql = file_get_contents($dumpFile);
if ($sql === false) {
    fwrite(STDERR, "cannot read $dumpFile\n");
    exit(1);
}

// Create and select the target database (TiDB/MySQL both allow this)
if (! $dbh->query("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
    fwrite(STDERR, "CREATE DATABASE failed: {$dbh->error}\n");
    exit(1);
}
if (! $dbh->select_db($dbName)) {
    fwrite(STDERR, "SELECT DB failed: {$dbh->error}\n");
    exit(1);
}
echo "Target database: {$dbName}\n";

$t0 = microtime(true);
if (! $dbh->multi_query($sql)) {
    fwrite(STDERR, "multi_query start failed: {$dbh->error}\n");
    exit(1);
}

$errors = 0;
$statements = 0;
do {
    $statements++;
    if ($result = $dbh->store_result()) {
        $result->free();
    }
    if ($dbh->errno) {
        $errors++;
        echo "  SQL ERROR after statement #{$statements}: {$dbh->error}\n";
        if (! $dbh->next_result()) { break; }
        continue;
    }
} while ($dbh->more_results() && $dbh->next_result());

$elapsed = number_format(microtime(true) - $t0, 1);

// Verify: table count + a few key rows
$tableCount = 0;
if ($res = $dbh->query('SHOW TABLES')) { $tableCount = $res->num_rows; }
$counts = [];
foreach (['users', 'vehicles', 'trip_requests', 'departments', 'sessions'] as $t) {
    if ($res = $dbh->query("SELECT COUNT(*) c FROM `{$t}`")) {
        $row = $res->fetch_assoc();
        $counts[$t] = $row['c'];
    } else {
        $counts[$t] = 'MISSING';
    }
}

echo "Statements: {$statements} | errors: {$errors} | {$elapsed}s\n";
echo "Tables: {$tableCount}\n";
echo 'Row counts: ' . json_encode($counts) . "\n";

exit($errors === 0 && $tableCount >= 26 ? 0 : 1);
