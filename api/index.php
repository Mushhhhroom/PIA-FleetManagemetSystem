<?php

use App\Libraries\ServerlessSessionHandler;
use App\Libraries\VercelSqliteBootstrap;
use CodeIgniter\Boot;
use Config\Paths;

/*
 * Vercel front controller (▲ serverless runtime).
 *
 * Every request is routed here by vercel.json. The CodeIgniter application
 * lives one directory up (the project root); this file adjusts runtime paths,
 * proxy headers, dynamic baseURL detection, and serverless session/database
 * fallbacks so the application runs seamlessly on Vercel.
 */

$minPhpVersion = '8.5.12';
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo 'Your PHP version must be ' . $minPhpVersion . ' or higher to run CodeIgniter. Current version: ' . PHP_VERSION;
    exit(1);
}

$projectRoot = dirname(__DIR__);

// Normalize HTTPS and Host headers behind Vercel Edge proxy
if (
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || getenv('VERCEL') === '1'
) {
    $_SERVER['HTTPS']       = 'on';
    $_SERVER['SERVER_PORT'] = 443;
}

$detectedHost = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'pia-fms.vercel.app';
$detectedHost = trim(explode(',', (string) $detectedHost)[0]);
$_SERVER['HTTP_HOST']   = $detectedHost;
$_SERVER['SERVER_NAME'] = explode(':', $detectedHost)[0];

// Normalize SCRIPT_NAME so CodeIgniter URI detection strips /api/index.php cleanly
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF']    = '/index.php';

// Serverless writable directory inside OS temp
$runtimeWritable = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'pia-afms-writable';
foreach (['cache', 'logs', 'session', 'debugbar', 'uploads'] as $dir) {
    $path = $runtimeWritable . DIRECTORY_SEPARATOR . $dir;
    if (! is_dir($path)) {
        @mkdir($path, 0777, true);
    }
}

/*
 * Environment-variable aliases.
 */
$envAliases = [
    'APP_BASEURL'                   => 'app.baseURL',
    'APP_FORCEGLOBALSECUREREQUESTS' => 'app.forceGlobalSecureRequests',
    'APP_CSPENABLED'                => 'app.CSPEnabled',
    'APP_TIMEZONE'                  => 'app.timezone',
    'SESSION_DRIVER'                => 'session.driver',
    'SESSION_SAVEPATH'              => 'session.savePath',
    'SESSION_LIFETIME'              => 'session.sessionLifetime',
    'ENCRYPTION_KEY'                => 'encryption.key',
    'DB_HOST'                       => 'database.default.hostname',
    'DB_PORT'                       => 'database.default.port',
    'DB_DATABASE'                   => 'database.default.database',
    'DB_USERNAME'                   => 'database.default.username',
    'DB_PASSWORD'                   => 'database.default.password',
    'DB_DRIVER'                     => 'database.default.DBDriver',
    'DB_ENCRYPT'                    => 'database.default.encrypt',
];
foreach ($envAliases as $alias => $ciKey) {
    $value = getenv($alias);
    if ($value === false || $value === '') {
        continue;
    }
    if (isset($_ENV[$ciKey]) || isset($_SERVER[$ciKey])) {
        continue;
    }
    $_ENV[$ciKey]    = $value;
    $_SERVER[$ciKey] = $value;
    putenv($ciKey . '=' . $value);
}

// Always bind app.baseURL to the active request host on Vercel so redirects & CSRF match the exact domain
$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$dynamicBaseUrl = $scheme . '://' . $detectedHost . '/';
$_ENV['app.baseURL']    = $dynamicBaseUrl;
$_SERVER['app.baseURL'] = $dynamicBaseUrl;
putenv('app.baseURL=' . $dynamicBaseUrl);

$_ENV['app.indexPage']    = '';
$_SERVER['app.indexPage'] = '';
putenv('app.indexPage=');

// Default CI_ENVIRONMENT to production on Vercel unless explicitly overridden
if (empty(getenv('CI_ENVIRONMENT')) && empty($_ENV['CI_ENVIRONMENT'])) {
    $_ENV['CI_ENVIRONMENT']    = 'production';
    $_SERVER['CI_ENVIRONMENT'] = 'production';
    putenv('CI_ENVIRONMENT=production');
}

// Default encryption key if not set in Vercel environment
if (empty($_ENV['encryption.key'])) {
    $defaultKey = 'hex2bin:66d6ea49e272c05df9ffc91b0595ee8944087ee6f54c9f5801272afbef7e53fb';
    $_ENV['encryption.key']    = $defaultKey;
    $_SERVER['encryption.key'] = $defaultKey;
    putenv('encryption.key=' . $defaultKey);
}

// When no external DB_HOST is configured on Vercel, automatically initialize
// the embedded SQLite3 database and stateless-resilient session handler.
$dbHost = getenv('DB_HOST');
if ($dbHost === false || trim($dbHost) === '') {
    require_once $projectRoot . '/app/Libraries/VercelSqliteBootstrap.php';

    $sqlitePath   = $runtimeWritable . DIRECTORY_SEPARATOR . 'pia_fms.sqlite';
    $snapshotPath = $projectRoot . '/app/Database/vercel_demo_snapshot.json';
    VercelSqliteBootstrap::ensureDatabase($sqlitePath, $snapshotPath);

    $sqliteOverrides = [
        'database.default.DBDriver' => 'SQLite3',
        'database.default.database' => $sqlitePath,
        'database.default.hostname' => '',
        'database.default.username' => '',
        'database.default.password' => '',
        'session.driver'            => ServerlessSessionHandler::class,
        'session.savePath'          => $runtimeWritable . DIRECTORY_SEPARATOR . 'session',
    ];
    foreach ($sqliteOverrides as $k => $v) {
        $_ENV[$k]    = $v;
        $_SERVER[$k] = $v;
        putenv($k . '=' . $v);
    }
}

// Path to the front controller (this file) + the public directory
define('FCPATH', $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);

// Ensure the current directory is pointing to the front controller's directory
if (getcwd() . DIRECTORY_SEPARATOR !== FCPATH) {
    chdir(FCPATH);
}

// LOAD OUR PATHS CONFIG FILE
require $projectRoot . '/app/Config/Paths.php';

$paths = new Paths();
$paths->writableDirectory = $runtimeWritable;

// LOAD THE FRAMEWORK BOOTSTRAP FILE
require $paths->systemDirectory . '/Boot.php';

exit(Boot::bootWeb($paths));
