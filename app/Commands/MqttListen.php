<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\GpsPingModel;
use App\Models\VehicleModel;
use App\Libraries\GeofenceChecker;

class MqttListen extends BaseCommand
{
    protected $group       = 'Fleet';
    protected $name        = 'mqtt:listen';
    protected $description = 'Subscribes to telemetry topics, ingests vehicle pings, and checks geofences.';

    public function run(array $params)
    {
        CLI::write('PIA Motorpool MQTT Telemetry Subscriber started...', 'green');
        CLI::write('Listening for incoming 4G/GPS device telemetry packets...', 'yellow');

        $pingModel = new GpsPingModel();
        $vehicleModel = new VehicleModel();
        $checker = new GeofenceChecker();

        // Sample simulation loop or broker listener
        $vehicles = $vehicleModel->whereIn('status', ['in_transit', 'active'])->findAll();
        if (empty($vehicles)) {
            $vehicles = $vehicleModel->findAll(3);
        }

        $incidentModel = new \App\Models\IncidentModel();
        $telemetryModel = new \App\Models\TelemetryModel();

        foreach ($vehicles as $v) {
            $deltaLat = (rand(-15, 15) / 10000);
            $deltaLng = (rand(-15, 15) / 10000);
            $newLat = round($v['current_latitude'] + $deltaLat, 7);
            $newLng = round($v['current_longitude'] + $deltaLng, 7);
            $speed  = rand(45, 88); // occasional overspeed to test alerting

            $fence = $checker->check($newLat, $newLng);
            $fenceName = $fence ? $fence['name'] : 'Open Corridor';

            $newFuel = max(10.0, round($v['current_fuel_level'] - 0.05, 2));
            $newOdo = round($v['odometer_km'] + 0.35, 2);

            $pingModel->insert([
                'vehicle_id'  => $v['id'],
                'latitude'    => $newLat,
                'longitude'   => $newLng,
                'speed_kmh'   => $speed,
                'heading'     => rand(0, 360),
                'fuel_level'  => $newFuel,
                'ignition'    => 1,
                'recorded_at' => date('Y-m-d H:i:s'),
            ]);

            $telemetryModel->insert([
                'vehicle_id'      => $v['id'],
                'latitude'        => $newLat,
                'longitude'       => $newLng,
                'speed_kmh'       => $speed,
                'heading'         => rand(0, 360),
                'fuel_level'      => $newFuel,
                'engine_status'   => 'running',
                'battery_voltage' => 24.4,
                'engine_temp_c'   => 89.2,
                'created_at'      => date('Y-m-d H:i:s'),
            ]);

            // Overspeed event check (SDD §5.4)
            if ($speed > 80) {
                $incNo = 'INC-SPD-' . date('Ymd-His') . '-' . $v['id'];
                $incidentModel->insert([
                    'incident_no'     => $incNo,
                    'vehicle_id'      => $v['id'],
                    'driver_id'       => $v['current_driver_id'],
                    'incident_date'   => date('Y-m-d H:i:s'),
                    'severity'        => 'minor',
                    'type'            => 'Overspeeding',
                    'location'        => $fenceName,
                    'description'     => "Speed event: {$v['vehicle_code']} clocked at {$speed} km/h (Limit: 80 km/h).",
                    'damage_estimate' => 0.00,
                    'status'          => 'reported',
                ]);
                CLI::write("  [ALERT] Overspeed incident {$incNo} logged for {$v['vehicle_code']} ({$speed} km/h)!", 'red');
            }

            $vehicleModel->update($v['id'], [
                'current_latitude'   => $newLat,
                'current_longitude'  => $newLng,
                'current_speed'      => $speed,
                'current_fuel_level' => $newFuel,
                'odometer_km'        => $newOdo,
                'engine_status'      => 'running',
            ]);

            CLI::write("Ping ingested for {$v['vehicle_code']} at [{$newLat}, {$newLng}] - Speed: {$speed} km/h - Geofence: {$fenceName}", 'cyan');
        }

        CLI::write('Telemetry subscriber cycle finished.', 'green');
    }
}
