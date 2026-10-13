<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\TripModel;
use App\Models\VehicleModel;
use App\Models\DriverModel;

class TripApi extends BaseController
{
    public function index()
    {
        $driverId = $this->request->getGet('driver_id');
        $tripModel = new TripModel();

        $builder = $tripModel->select('trips.*, vehicles.vehicle_code, vehicles.plate_number, vehicles.make, vehicles.model')
                            ->join('vehicles', 'vehicles.id = trips.vehicle_id', 'left')
                            ->orderBy('trips.id', 'DESC');

        if ($driverId) {
            $builder->where('trips.driver_id', $driverId);
        }

        $trips = $builder->findAll(20);

        return $this->response->setJSON([
            'status' => 'success',
            'count'  => count($trips),
            'data'   => $trips,
        ]);
    }

    public function start($id)
    {
        $tripModel = new TripModel();
        $trip = $tripModel->find($id);

        if (!$trip) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Trip not found']);
        }

        $tripModel->update($id, [
            'status'           => 'in_transit',
            'actual_departure' => date('Y-m-d H:i:s'),
        ]);

        $vehicleModel = new VehicleModel();
        $vehicleModel->update($trip['vehicle_id'], [
            'status' => 'in_transit',
            'current_driver_id' => $trip['driver_id'],
        ]);

        $driverModel = new DriverModel();
        $driverModel->update($trip['driver_id'], ['status' => 'on_trip']);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Trip started. Status set to in_transit.',
            'trip_id' => $id,
        ]);
    }

    public function complete($id)
    {
        $tripModel = new TripModel();
        $trip = $tripModel->find($id);

        if (!$trip) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Trip not found']);
        }

        $json = null;
        try {
            $json = $this->request->getJSON(true);
        } catch (\Throwable $e) {
            $raw = $this->request->getBody();
            if (!empty($raw)) {
                $json = json_decode($raw, true);
            }
        }
        if (empty($json)) {
            $json = $this->request->getPost();
        }
        $endOdo = (float)($json['end_odometer'] ?? ($trip['start_odometer'] + $trip['distance_km']));

        $tripModel->update($id, [
            'status'         => 'completed',
            'actual_arrival' => date('Y-m-d H:i:s'),
            'end_odometer'   => $endOdo,
        ]);

        $vehicleModel = new VehicleModel();
        $vehicleModel->update($trip['vehicle_id'], [
            'status'      => 'active',
            'odometer_km' => $endOdo,
        ]);

        $driverModel = new DriverModel();
        $driverModel->update($trip['driver_id'], ['status' => 'available']);
        $driver = $driverModel->find($trip['driver_id']);
        if ($driver) {
            $driverModel->update($trip['driver_id'], ['total_trips' => $driver['total_trips'] + 1]);
        }

        // SDD Flow 2 -> Flow 3 link: Check if odometer exceeds next_service_km
        $vehicle = $vehicleModel->find($trip['vehicle_id']);
        $maintenanceScheduled = false;
        if ($vehicle && $vehicle['next_service_km'] && $endOdo >= (float)$vehicle['next_service_km']) {
            $maintModel = new \App\Models\MaintenanceModel();
            $existing = $maintModel->where('vehicle_id', $vehicle['id'])
                                   ->whereIn('status', ['scheduled', 'in_progress'])
                                   ->first();
            if (!$existing) {
                $maintRef = 'WO-AUTO-' . date('Ymd') . '-' . $vehicle['id'];
                $maintModel->insert([
                    'reference_no'        => $maintRef,
                    'vehicle_id'          => $vehicle['id'],
                    'service_type'        => 'Post-Trip Preventive Maintenance (PMS)',
                    'priority'            => 'high',
                    'scheduled_date'      => date('Y-m-d', strtotime('+2 days')),
                    'odometer_at_service' => $endOdo,
                    'service_center'      => 'In-House Fleet Workshop',
                    'cost'                => 15000.00,
                    'status'              => 'scheduled',
                    'description'         => "Trip {$trip['trip_number']} completed at {$endOdo} km. Exceeded service threshold of {$vehicle['next_service_km']} km.",
                ]);
                $maintenanceScheduled = true;

                $smsSender = new \App\Libraries\SmsSender();
                $smsSender->send(
                    '+63 919 555 0300',
                    "PIA Motorpool Maintenance Alert: Vehicle {$vehicle['vehicle_code']} reached {$endOdo} km. Work order {$maintRef} generated."
                );
            }
        }

        return $this->response->setJSON([
            'status'                => 'success',
            'message'               => 'Trip successfully marked as completed.',
            'trip_id'               => $id,
            'odometer_km'           => $endOdo,
            'maintenance_scheduled' => $maintenanceScheduled,
        ]);
    }
}
