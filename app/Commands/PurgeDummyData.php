<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class PurgeDummyData extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:purge-dummy';
    protected $description = 'Safely purge all operational and test dummy data while preserving core master records';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        CLI::write("==================================================================", 'cyan');
        CLI::write("       PURGING DUMMY & TEST DATA FROM FLEET MANAGEMENT SYSTEM      ", 'yellow');
        CLI::write("==================================================================", 'cyan');

        // List of transactional/dummy tables to truncate
        $tablesToPurge = [
            'trip_requests',
            'trip_tickets',
            'gate_logs',
            'trips',
            'fuel_logs',
            'incidents',
            'maintenance_records',
            'passenger_delay_logs',
            'pir_reports',
            'safety_checks',
            'non_usage_certificates',
            'rfid_transactions',
            'telemetry_logs',
            'gps_pings',
            'system_notifications',
            'system_audit_logs',
            'email_outbox',
            'sessions',
        ];

        CLI::write("1. Disabling foreign key checks...", 'yellow');
        $db->query('SET FOREIGN_KEY_CHECKS = 0;');

        CLI::write("2. Truncating transactional & test tables...", 'yellow');
        foreach ($tablesToPurge as $table) {
            if ($db->tableExists($table)) {
                $db->query("TRUNCATE TABLE `{$table}`");
                CLI::write(sprintf("   - Truncated %-25s [CLEARED]", $table), 'green');
            } else {
                CLI::write(sprintf("   - Table %-29s [SKIPPED - NOT FOUND]", $table), 'dark_gray');
            }
        }

        CLI::write("3. Re-enabling foreign key checks...", 'yellow');
        $db->query('SET FOREIGN_KEY_CHECKS = 1;');

        // 4. Reset RFID Cards to baseline
        CLI::write("4. Resetting RFID cards to baseline balance...", 'yellow');
        if ($db->tableExists('rfid_cards')) {
            $db->table('rfid_cards')->update([
                'balance'    => 500.00,
                'status'     => 'active',
                'updated_at' => $now,
            ]);
            CLI::write("   - RFID cards balance reset to 500.00 PHP", 'green');
        }

        // 5. Reset Vehicles to clean baseline status and odometers
        CLI::write("5. Resetting vehicles to clean baseline states...", 'yellow');
        $vehicleBaselines = [
            'PIA-V01' => ['odometer' => 34250.00, 'category' => 'pool', 'official' => null],
            'PIA-V02' => ['odometer' => 18920.00, 'category' => 'dedicated', 'official' => 'Regional Director Maria L. Santos'],
            'PIA-V03' => ['odometer' => 48800.00, 'category' => 'pool', 'official' => null],
            'PIA-V04' => ['odometer' => 29400.00, 'category' => 'pool', 'official' => null],
            'FLT-01'  => ['odometer' => 64220.00, 'category' => 'pool', 'official' => null],
            'FLT-02'  => ['odometer' => 32110.00, 'category' => 'pool', 'official' => null],
            'FLT-03'  => ['odometer' => 41500.00, 'category' => 'pool', 'official' => null],
            'FLT-04'  => ['odometer' => 89230.10, 'category' => 'pool', 'official' => null],
            'FLT-05'  => ['odometer' => 18426.60, 'category' => 'pool', 'official' => null],
        ];

        foreach ($vehicleBaselines as $code => $cfg) {
            $db->table('vehicles')
                ->where('vehicle_code', $code)
                ->update([
                    'odometer_km'        => $cfg['odometer'],
                    'current_latitude'   => 14.650800,
                    'current_longitude'  => 121.050400,
                    'current_speed'      => 0.00,
                    'current_fuel_level' => 100.00,
                    'engine_status'      => 'off',
                    'status'             => 'active',
                    'current_driver_id'  => null,
                    'fleet_category'     => $cfg['category'],
                    'assigned_official'  => $cfg['official'],
                    'updated_at'         => $now,
                ]);
            CLI::write("   - Reset vehicle {$code} (Odo: {$cfg['odometer']} km, Status: active)", 'green');
        }

        // 6. Reset Drivers to available status and 0 trips
        CLI::write("6. Resetting drivers to available status...", 'yellow');
        $db->table('drivers')->update([
            'status'       => 'available',
            'total_trips'  => 0,
            'safety_score' => 100.00,
            'updated_at'   => $now,
        ]);
        CLI::write("   - All drivers reset to 'available' with 0 trips and 100.00 safety score", 'green');

        // 7. Re-sync PMS Records to clean 5,000 km intervals
        CLI::write("7. Re-syncing PMS records for all vehicles...", 'yellow');
        $allVehicles = $db->table('vehicles')->get()->getResult();
        foreach ($allVehicles as $veh) {
            $currentOdo = (float)$veh->odometer_km;
            $interval = 5000.00;
            $nextPms = ceil(($currentOdo + 1) / $interval) * $interval;
            $lastPms = max(0, $nextPms - $interval);

            $pmsExists = $db->table('pms_records')->where('vehicle_id', $veh->id)->get()->getRow();
            $pmsData = [
                'vehicle_id'        => $veh->id,
                'last_pms_odometer' => $lastPms,
                'next_pms_odometer' => $nextPms,
                'pms_interval_km'   => $interval,
                'is_locked'         => 0,
                'status'            => 'ok',
                'last_pms_date'     => date('Y-m-d', strtotime('-15 days')),
                'notes'             => "Routine 5,000 KM PMS Cycle. Next inspection at {$nextPms} km.",
                'updated_at'        => $now,
            ];

            if ($pmsExists) {
                $db->table('pms_records')->where('vehicle_id', $veh->id)->update($pmsData);
            } else {
                $pmsData['created_at'] = $now;
                $db->table('pms_records')->insert($pmsData);
            }
        }
        CLI::write("   - Synchronized PMS records for " . count($allVehicles) . " vehicles [LOCKED: 0, STATUS: ok]", 'green');

        // 8. Seed clean initial baseline audit trail log
        CLI::write("8. Creating baseline audit trail entry...", 'yellow');
        \App\Services\AuditLogger::log(
            \App\Services\AuditLogger::STATUS_CHANGE,
            'System operational database purged of test data and reset to clean production baseline.',
            'system',
            null,
            ['purged_tables' => $tablesToPurge]
        );
        CLI::write("   - Added STATUS_CHANGE audit trail record", 'green');

        CLI::write("\nSUCCESS: All operational dummy data has been removed. The system is now completely clean and ready!", 'cyan');
    }
}
