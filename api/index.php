<?php

use CodeIgniter\Boot;
use Config\Paths;

/*
 * Vercel front controller (▲ serverless runtime).
 *
 * Every request is routed here by vercel.json. The CodeIgniter application
 * lives one directory up (the project root); this file only adjusts the two
 * things that differ on Vercel:
 *
 *   1. FCPATH points at public/ so asset helpers resolve correctly.
 *   2. The writable tree is moved into the function's OS temp directory,
 *      because the deployed filesystem is read-only and ephemeral.
 *
 * Configuration (database, baseURL, session driver) arrives through process
 * environment variables — see .env.example for the full list.
 */

$minPhpVersion = '8.2';
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo 'Your PHP version must be ' . $minPhpVersion . ' or higher to run CodeIgniter. Current version: ' . PHP_VERSION;
    exit(1);
}

/*
 * Environment-variable aliases.
 *
 * Vercel rejects dotted names ("app.baseURL") in `vercel env add` — only
 * letters, digits and underscores are allowed. The cloud configuration
 * therefore uses conventional SCREAMING_SNAKE names, which this block
 * re-publishes under the dotted keys CodeIgniter reads, before any Config
 * class is instantiated.
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
        continue;                               // not configured — leave default
    }
    if (isset($_ENV[$ciKey]) || isset($_SERVER[$ciKey])) {
        continue;                               // an explicit dotted value wins
    }
    $_ENV[$ciKey]    = $value;
    $_SERVER[$ciKey] = $value;
    putenv($ciKey . '=' . $value);
}

$projectRoot = dirname(__DIR__);

// Path to the front controller (this file) + the public directory
define('FCPATH', $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);

// Ensure the current directory is pointing to the front controller's directory
if (getcwd() . DIRECTORY_SEPARATOR !== FCPATH) {
    chdir(FCPATH);
}

// LOAD OUR PATHS CONFIG FILE
require $projectRoot . '/app/Config/Paths.php';

$paths = new Paths();

/*
 * Serverless filesystem: only the OS temp directory is writable and it does
 * not survive cold starts. Point CodeIgniter's writable tree there and make
 * sure the subdirectories exist before the framework boots.
 * (Sessions use the database on Vercel — see session.driver.)
 */
$runtimeWritable = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'pia-afms-writable';
foreach (['cache', 'logs', 'session', 'debugbar', 'uploads'] as $dir) {
    $path = $runtimeWritable . DIRECTORY_SEPARATOR . $dir;
    if (! is_dir($path)) {
        @mkdir($path, 0777, true);
    }
}
$paths->writableDirectory = $runtimeWritable;

// LOAD THE FRAMEWORK BOOTSTRAP FILE
require $paths->systemDirectory . '/Boot.php';

exit(Boot::bootWeb($paths));
