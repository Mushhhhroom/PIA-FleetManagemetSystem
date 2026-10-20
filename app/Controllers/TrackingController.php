<?php

namespace App\Controllers;

use App\Models\VehicleModel;
use App\Models\TripTicketModel;
use App\Models\TelemetryModel;

class TrackingController extends BaseController
{
    public function index()
    {
        $vehicleModel = new VehicleModel();
        $vehicles = $vehicleModel->select('vehicles.*,
                                          CONCAT(drivers.first_name, \' \', drivers.last_name) AS driver_name,
                                          drivers.phone AS driver_phone,
                                          trip_tickets.id AS active_ticket_id,
                                          trip_tickets.ticket_serial_no AS trip_number,
                                          trip_tickets.authorized_destination AS destination_address,
                                          trip_tickets.authorized_purpose')
                                ->join('drivers', 'drivers.id = vehicles.current_driver_id', 'left')
                                ->join('trip_tickets', 'trip_tickets.vehicle_id = vehicles.id AND trip_tickets.status IN ("departed", "arrived_dest", "departed_dest", "issued")', 'left')
                                ->groupBy('vehicles.id')
                                ->findAll();

        $geofenceChecker = new \App\Libraries\GeofenceChecker();
        foreach ($vehicles as &$v) {
            $lat = (float)($v['current_latitude'] ?? 0);
            $lng = (float)($v['current_longitude'] ?? 0);
            $fence = ($lat != 0 && $lng != 0) ? $geofenceChecker->check($lat, $lng) : null;
            $v['current_geofence'] = $fence ? $fence['name'] : 'Metro Manila Corridor';
        }

        return view('tracking/index', [
            'title'    => 'Real-Time Fleet Telematics & GPS Tracking',
            'vehicles' => $vehicles,
        ]);
    }

    public function liveData()
    {
        $vehicleModel = new VehicleModel();
        $vehicles = $vehicleModel->select('vehicles.*,
                                          CONCAT(drivers.first_name, \' \', drivers.last_name) AS driver_name,
                                          drivers.phone AS driver_phone,
                                          trip_tickets.id AS active_ticket_id,
                                          trip_tickets.ticket_serial_no AS trip_number,
                                          trip_tickets.authorized_destination AS destination_address,
                                          trip_tickets.authorized_purpose')
                                ->join('drivers', 'drivers.id = vehicles.current_driver_id', 'left')
                                ->join('trip_tickets', 'trip_tickets.vehicle_id = vehicles.id AND trip_tickets.status IN ("departed", "arrived_dest", "departed_dest", "issued")', 'left')
                                ->groupBy('vehicles.id')
                                ->findAll();

        $geofenceChecker = new \App\Libraries\GeofenceChecker();
        foreach ($vehicles as &$v) {
            $lat = (float)($v['current_latitude'] ?? 0);
            $lng = (float)($v['current_longitude'] ?? 0);
            $fence = ($lat != 0 && $lng != 0) ? $geofenceChecker->check($lat, $lng) : null;
            $v['current_geofence'] = $fence ? $fence['name'] : 'Metro Manila Corridor';
        }

        return $this->response->setHeader('Access-Control-Allow-Origin', '*')->setJSON([
            'status'    => 'success',
            'timestamp' => date('Y-m-d H:i:s'),
            'vehicles'  => $vehicles,
        ]);
    }

    /**
     * Simulation action: advances vehicles that are in transit along their route
     */
    public function simulateStep()
    {
        $vehicleModel   = new VehicleModel();
        $telemetryModel = new TelemetryModel();
        $ticketModel    = new TripTicketModel();

        $activeTickets = $ticketModel->whereIn('status', ['departed', 'arrived_dest', 'departed_dest'])->findAll();
        if (empty($activeTickets)) {
            $activeVehicles = $vehicleModel->where('status', 'in_transit')->findAll();
            foreach ($activeVehicles as $v) {
                $activeTickets[] = ['id' => null, 'vehicle_id' => $v['id']];
            }
        }

        $updatedCount = 0;
        foreach ($activeTickets as $tk) {
            $vehicle = $vehicleModel->find($tk['vehicle_id']);
            if (!$vehicle) continue;

            $curLat = (float)$vehicle['current_latitude'];
            $curLng = (float)$vehicle['current_longitude'];

            $dLat = ((rand(-15, 20)) / 10000);
            $dLng = ((rand(-15, 20)) / 10000);

            $newLat   = round($curLat + $dLat, 7);
            $newLng   = round($curLng + $dLng, 7);
            $newSpeed = rand(35, 68) + (rand(0, 9) / 10);
            $newFuel  = max(10, round($vehicle['current_fuel_level'] - 0.2, 2));
            $newOdo   = round($vehicle['odometer_km'] + 1.2, 2);

            $vehicleModel->update($vehicle['id'], [
                'current_latitude'   => $newLat,
                'current_longitude'  => $newLng,
                'current_speed'      => $newSpeed,
                'current_fuel_level' => $newFuel,
                'odometer_km'        => $newOdo,
                'engine_status'      => 'running',
            ]);

            $telemetryModel->insert([
                'vehicle_id'      => $vehicle['id'],
                'trip_id'         => $tk['id'],
                'latitude'        => $newLat,
                'longitude'       => $newLng,
                'speed_kmh'       => $newSpeed,
                'heading'         => rand(0, 360),
                'fuel_level'      => $newFuel,
                'engine_status'   => 'running',
                'battery_voltage' => 14.1,
                'engine_temp_c'   => rand(86, 92),
                'created_at'      => date('Y-m-d H:i:s'),
            ]);

            $updatedCount++;
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Telemetry simulated for {$updatedCount} vehicle(s) in transit.",
        ]);
    }
}
