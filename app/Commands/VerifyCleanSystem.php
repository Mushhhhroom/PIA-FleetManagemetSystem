<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class VerifyCleanSystem extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:verify-clean';
    protected $description = 'Verify that all system views and controllers render properly in a clean database state';

    public function run(array $params)
    {
        CLI::write("==================================================================", 'cyan');
        CLI::write("           VERIFYING SYSTEM IN CLEAN BASELINE STATE               ", 'yellow');
        CLI::write("==================================================================", 'cyan');

        // Setup mock admin session
        $session = session();
        $session->set([
            'isLoggedIn' => true,
            'user_id'    => 5,
            'user_name'  => 'Director-General Jose A. Torres Jr.',
            'user_role'  => 'admin',
            'user_email' => 'admin@pia.gov.ph',
            'office_id'  => 2,
        ]);

        $tests = [
            ['name' => 'Dashboard Command Center',     'class' => \App\Controllers\DashboardController::class,       'method' => 'index'],
            ['name' => 'Vehicle Requests (VRS)',       'class' => \App\Controllers\RequestController::class,         'method' => 'index'],
            ['name' => 'Driver Trip Tickets (e-DTT)',  'class' => \App\Controllers\TripTicketController::class,      'method' => 'index'],
            ['name' => 'Dispatch Console',             'class' => \App\Controllers\DispatchController::class,        'method' => 'index'],
            ['name' => 'Dispatch Calendar View',       'class' => \App\Controllers\DispatchController::class,        'method' => 'calendar'],
            ['name' => 'Gate QR Checkpoint',           'class' => \App\Controllers\GateSecurityController::class,    'method' => 'index'],
            ['name' => 'Vehicles Fleet (5K PMS)',      'class' => \App\Controllers\VehicleController::class,         'method' => 'index'],
            ['name' => 'Drivers Personnel',            'class' => \App\Controllers\DriverController::class,          'method' => 'index'],
            ['name' => 'COA Compliance Portal',        'class' => \App\Controllers\ComplianceController::class,      'method' => 'index'],
            ['name' => 'Audit Trail & Compliance',     'class' => \App\Controllers\AuditController::class,           'method' => 'index'],
            ['name' => 'Safety BLOWBAGETS',            'class' => \App\Controllers\SafetyChecklistController::class, 'method' => 'index'],
            ['name' => 'Mechanic PIR Queue',           'class' => \App\Controllers\PirController::class,             'method' => 'index'],
            ['name' => 'RFID Tollway Cards',           'class' => \App\Controllers\RfidController::class,            'method' => 'index'],
            ['name' => 'User Management',              'class' => \App\Controllers\UserController::class,            'method' => 'index'],
        ];

        $passed = 0;
        $failed = 0;

        $request = \Config\Services::incomingrequest(new \Config\App(), false);
        \Config\Services::injectMock('request', $request);

        foreach ($tests as $t) {
            try {
                ob_start();
                $controller = new $t['class']();
                $controller->initController($request, \Config\Services::response(), \Config\Services::logger());
                $response = $controller->{$t['method']}();
                $output = ob_get_clean();

                $len = is_string($response) ? strlen($response) : (method_exists($response, 'getBody') ? strlen($response->getBody()) : strlen($output));

                CLI::write(sprintf("  PASS: %-32s (Output: %d bytes)", $t['name'], $len), 'green');
                $passed++;
            } catch (\Throwable $e) {
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }
                CLI::write(sprintf("  FAIL: %-32s [%s: %s]", $t['name'], get_class($e), $e->getMessage()), 'red');
                CLI::write("        File: " . $e->getFile() . ":" . $e->getLine(), 'light_red');
                $failed++;
            }
        }

        CLI::write("------------------------------------------------------------------", 'cyan');
        CLI::write("SUMMARY: {$passed} PASSED, {$failed} FAILED", $failed === 0 ? 'green' : 'red');
        CLI::write("==================================================================", 'cyan');
    }
}
