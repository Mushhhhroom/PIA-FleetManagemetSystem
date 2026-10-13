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
