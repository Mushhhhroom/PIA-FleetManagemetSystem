<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\GpsPingModel;
use App\Models\VehicleModel;
use App\Models\DriverModel;
use App\Models\IncidentModel;
use App\Models\MaintenanceModel;
use App\Models\TelemetryModel;
use App\Libraries\GeofenceChecker;
use App\Libraries\SmsSender;

class GpsApi extends BaseController
{
    public function ping()
    {
        // Safe input retrieval supporting JSON, form-data, and query params
        $json = null;
        $raw = $this->request->getBody();
        if (!empty($raw)) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $json = $decoded;
            }
        }
        if (empty($json)) {
            $json = $this->request->getPost();
        }
        if (empty($json)) {
            $json = $this->request->getGet();
        }

        if (empty($json['vehicle_id']) || !isset($json['latitude']) || !isset($json['longitude'])) {
            return $this->response->setHeader('Access-Control-Allow-Origin', '*')
                                  ->setStatusCode(400)
                                  ->setJSON([
                'status'  => 'error',
                'message' => 'Missing required telemetry fields: vehicle_id, latitude, longitude.',
                'example' => [
                    'vehicle_id' => 1,
                    'latitude'   => 14.5995,
                    'longitude'  => 120.9842,
                    'speed_kmh'  => 65.5,
                    'fuel_level' => 88.0,
                    'heading'    => 180,
                ],
            ]);
        }

        $vehicleId = (int)$json['vehicle_id'];
        $lat = (float)$json['latitude'];
        $lng = (float)$json['longitude'];
        $speed = (float)($json['speed_kmh'] ?? 0.00);
        $fuel = (float)($json['fuel_level'] ?? 100.00);
        $ignition = (int)($json['ignition'] ?? 1);
        $engineCode = !empty($json['engine_code']) ? trim($json['engine_code']) : null;
        $tripId = !empty($json['trip_id']) ? (int)$json['trip_id'] : null;

        $vehicleModel = new VehicleModel();
        $vehicle = $vehicleModel->find($vehicleId);
        if (!$vehicle) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Vehicle not found.',
            ]);
        }

        // 1. Insert into high-volume GPS pings table (SDD §4.2)
        $pingModel = new GpsPingModel();
        $pingModel->insert([
            'vehicle_id'  => $vehicleId,
            'trip_id'     => $tripId,
            'latitude'    => $lat,
            'longitude'   => $lng,
            'speed_kmh'   => $speed,
            'heading'     => (float)($json['heading'] ?? 0.00),
            'fuel_level'  => $fuel,
            'ignition'    => $ignition,
            'engine_code' => $engineCode,
            'recorded_at' => date('Y-m-d H:i:s'),
        ]);

        // 2. Also log to telemetry breadcrumbs for trip trail visualization
        $telemetryModel = new TelemetryModel();
        $telemetryModel->insert([
            'vehicle_id'      => $vehicleId,
            'trip_id'         => $tripId,
            'latitude'        => $lat,
            'longitude'       => $lng,
            'speed_kmh'       => $speed,
            'heading'         => (float)($json['heading'] ?? 0.00),
            'fuel_level'      => $fuel,
            'engine_status'   => $speed > 0 ? 'running' : ($ignition ? 'idling' : 'off'),
            'battery_voltage' => isset($json['battery_voltage']) ? (float)$json['battery_voltage'] : 24.2,
            'engine_temp_c'   => isset($json['engine_temp_c']) ? (float)$json['engine_temp_c'] : 88.0,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        // 3. Geofence evaluation (SDD §5.4)
        $geofenceChecker = new GeofenceChecker();
        $geofence = $geofenceChecker->check($lat, $lng);
        $geofenceName = $geofence ? $geofence['name'] : 'Open Corridor';

        // 4. Overspeed Detection (Speed > 80 km/h) -> Log Incident & Alert
        $overspeedAlert = false;
        if ($speed > 80.0) {
            $incidentModel = new IncidentModel();
            // Debounce: don't create multiple overspeed incidents for same vehicle within last 2 minutes
            $recentIncident = $incidentModel->where('vehicle_id', $vehicleId)
                                            ->where('type', 'Overspeeding')
                                            ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-2 minutes')))
                                            ->first();

            if (!$recentIncident) {
                $incNo = 'INC-SPD-' . date('Ymd-His');
                $severity = $speed > 100 ? 'severe' : ($speed > 90 ? 'moderate' : 'minor');
                $incidentModel->insert([
                    'incident_no'     => $incNo,
                    'vehicle_id'      => $vehicleId,
                    'driver_id'       => $vehicle['current_driver_id'],
                    'trip_id'         => $tripId,
                    'incident_date'   => date('Y-m-d H:i:s'),
                    'severity'        => $severity,
                    'type'            => 'Overspeeding',
                    'location'        => $geofenceName . " [{$lat}, {$lng}]",
                    'description'     => "Vehicle {$vehicle['vehicle_code']} exceeded speed limit: {$speed} km/h detected (Threshold: 80 km/h).",
                    'damage_estimate' => 0.00,
                    'status'          => 'reported',
                ]);
                $overspeedAlert = true;

                // Penalize safety score
                if (!empty($vehicle['current_driver_id'])) {
                    $driverModel = new DriverModel();
                    $driver = $driverModel->find($vehicle['current_driver_id']);
                    if ($driver) {
                        $newScore = max(60.00, (float)$driver['safety_score'] - 1.50);
                        $driverModel->update($driver['id'], ['safety_score' => $newScore]);
                    }
                }
            }
        }

        // 5. OBD-II Fault Code -> Flow 3 Maintenance Alert trigger (SDD §5.5)
        $maintAlert = false;
        if (!empty($engineCode) && $engineCode !== 'OK') {
            $maintModel = new MaintenanceModel();
            $existingMaint = $maintModel->where('vehicle_id', $vehicleId)
                                        ->where('status', 'scheduled')
                                        ->like('service_type', $engineCode)
                                        ->first();

            if (!$existingMaint) {
                $refNo = 'WO-OBD-' . date('Ymd-His');
                $maintModel->insert([
                    'reference_no'        => $refNo,
                    'vehicle_id'          => $vehicleId,
                    'service_type'        => "OBD-II Fault: {$engineCode}",
                    'priority'            => 'high',
                    'scheduled_date'      => date('Y-m-d'),
                    'odometer_at_service' => $vehicle['odometer_km'],
                    'service_center'      => 'Authorized Diagnostic Workshop',
                    'cost'                => 8500.00,
                    'status'              => 'scheduled',
                    'description'         => "Telemetry OBD-II engine fault code {$engineCode} reported at location: {$geofenceName}.",
                ]);
                $maintAlert = true;

                $smsSender = new SmsSender();
                $smsSender->send(
                    '+63 919 555 0300',
                    "PIA Motorpool Telematics Alert: Engine fault {$engineCode} reported on {$vehicle['vehicle_code']} at {$geofenceName}. WO {$refNo} scheduled."
                );
            }
        }

        // 6. Calculate distance delta and update vehicle location & odometer
        $currentOdo = (float)$vehicle['odometer_km'];
        $prevLat = (float)$vehicle['current_latitude'];
        $prevLng = (float)$vehicle['current_longitude'];
        if ($prevLat > 0 && $prevLng > 0) {
            $theta = $prevLng - $lng;
            $dist = sin(deg2rad($prevLat)) * sin(deg2rad($lat)) + cos(deg2rad($prevLat)) * cos(deg2rad($lat)) * cos(deg2rad($theta));
            $dist = acos(min(1, max(-1, $dist)));
            $distKm = rad2deg($dist) * 60 * 1.1515 * 1.609344;
            if ($distKm > 0.01 && $distKm < 50.0) { // Valid incremental movement
                $currentOdo += round($distKm, 2);
            }
        }

        $vehicleModel->update($vehicleId, [
            'current_latitude'   => $lat,
            'current_longitude'  => $lng,
            'current_speed'      => $speed,
            'current_fuel_level' => $fuel,
            'odometer_km'        => $currentOdo,
            'engine_status'      => $speed > 0 ? 'running' : ($ignition ? 'idling' : 'off'),
        ]);

        return $this->response->setHeader('Access-Control-Allow-Origin', '*')
                              ->setHeader('Access-Control-Allow-Headers', '*')
                              ->setJSON([
            'status'            => 'success',
            'message'           => 'GPS telemetry ping processed.',
            'geofence'          => $geofenceName,
            'overspeed_alert'   => $overspeedAlert,
            'maintenance_alert' => $maintAlert,
            'odometer_km'       => $currentOdo,
            'timestamp'         => date('Y-m-d H:i:s'),
        ]);
    }
}
