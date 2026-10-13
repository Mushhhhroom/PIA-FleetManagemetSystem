<?php

namespace App\Controllers;

use App\Models\TripModel;
use App\Models\VehicleModel;
use App\Models\DriverModel;
use App\Models\TelemetryModel;
use App\Models\MaintenanceModel;
use App\Libraries\RouteService;
use App\Libraries\SmsSender;

class TripController extends BaseController
{
    public function index()
    {
        $tripModel = new TripModel();
        $trips = $tripModel->getTripsWithDetails();

        return view('trips/index', [
            'title' => 'Dispatch & Trip Operations',
            'trips' => $trips,
        ]);
    }

    public function create()
    {
        $vehicleModel = new VehicleModel();
        $driverModel = new DriverModel();

        $vehicles = $vehicleModel->findAll();
        $drivers = $driverModel->findAll();

        return view('trips/form', [
            'title'    => 'Dispatch New Delivery Trip',
            'trip'     => null,
            'vehicles' => $vehicles,
            'drivers'  => $drivers,
        ]);
    }

    public function store()
    {
        $tripModel = new TripModel();
        $vehicleModel = new VehicleModel();
        $driverModel = new DriverModel();

        $rules = [
            'vehicle_id'          => 'required|numeric',
            'driver_id'           => 'required|numeric',
            'origin_address'      => 'required',
            'destination_address' => 'required',
            'scheduled_departure' => 'required',
            'cargo_type'          => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $tripNumber = 'TRP-' . date('Ymd') . '-' . rand(100, 999);
        $vehicleId = (int)$this->request->getPost('vehicle_id');
        $driverId = (int)$this->request->getPost('driver_id');

        $vehicle = $vehicleModel->find($vehicleId);
        $startOdo = $vehicle ? (float)$vehicle['odometer_km'] : 0.00;

        $originLat = (float)($this->request->getPost('origin_lat') ?: 14.583333);
        $originLng = (float)($this->request->getPost('origin_lng') ?: 120.966667);
        $destLat = (float)($this->request->getPost('destination_lat') ?: 14.676000);
        $destLng = (float)($this->request->getPost('destination_lng') ?: 121.043700);

        // SDD §5.3 Step 5: RouteService queries OSRM routing engine with fallback
        $routeService = new RouteService();
        $route = $routeService->getRoute($originLat, $originLng, $destLat, $destLng);
        $distanceKm = (float)($this->request->getPost('distance_km') ?: $route['distance_km']);

        $scheduledDeparture = $this->request->getPost('scheduled_departure');
        $scheduledArrival = $this->request->getPost('scheduled_arrival');
        if (empty($scheduledArrival) && !empty($scheduledDeparture)) {
            $durationMin = $route['duration_min'] ?? round(($distanceKm / 50) * 60);
            $scheduledArrival = date('Y-m-d H:i:s', strtotime($scheduledDeparture . " +{$durationMin} minutes"));
        }

        $status = $this->request->getPost('status') ?: 'scheduled';

        $data = [
            'trip_number'         => $tripNumber,
            'vehicle_id'          => $vehicleId,
            'driver_id'           => $driverId,
            'origin_address'      => trim($this->request->getPost('origin_address')),
            'origin_lat'          => $originLat,
            'origin_lng'          => $originLng,
            'destination_address' => trim($this->request->getPost('destination_address')),
            'destination_lat'     => $destLat,
            'destination_lng'     => $destLng,
            'cargo_type'          => trim($this->request->getPost('cargo_type')),
            'cargo_weight_kg'     => (float)$this->request->getPost('cargo_weight_kg'),
            'distance_km'         => $distanceKm,
            'scheduled_departure' => $scheduledDeparture,
            'scheduled_arrival'   => $scheduledArrival,
            'actual_departure'    => ($status === 'in_transit' || $status === 'dispatched') ? date('Y-m-d H:i:s') : null,
            'start_odometer'      => $startOdo,
            'status'              => $status,
            'priority'            => $this->request->getPost('priority') ?: 'normal',
            'notes'               => trim($this->request->getPost('notes')),
            'created_by'          => session()->get('user_id'),
        ];

        $tripId = $tripModel->insert($data);

        // Update vehicle and driver status if dispatched or in transit
        if (in_array($status, ['dispatched', 'in_transit'])) {
            $vehicleModel->update($vehicleId, [
                'status'            => 'in_transit',
                'current_driver_id' => $driverId,
            ]);
            $driverModel->update($driverId, [
                'status' => 'on_trip',
            ]);
        }

        // SDD §5.3 Step 7: SmsSender notifies driver of new trip assignment
        $driver = $driverModel->find($driverId);
        if ($driver && !empty($driver['phone'])) {
            $smsSender = new SmsSender();
            $smsSender->send(
                $driver['phone'],
                "PIA Motorpool Dispatch: Trip {$tripNumber} assigned. Route: {$data['origin_address']} to {$data['destination_address']} ({$distanceKm} km). Review details in your PWA."
            );
        }

        return redirect()->to('/trips/' . $tripId)->with('success', "Trip {$tripNumber} created successfully via OSRM routing.");
    }

    public function show($id)
    {
        $tripModel = new TripModel();
        $trip = $tripModel->select('trips.*, 
                                   vehicles.vehicle_code, vehicles.plate_number, vehicles.make, vehicles.model, vehicles.current_latitude, vehicles.current_longitude, vehicles.current_speed, vehicles.current_fuel_level,
                                   CONCAT(drivers.first_name, " ", drivers.last_name) AS driver_name, drivers.phone AS driver_phone, drivers.license_number')
                          ->join('vehicles', 'vehicles.id = trips.vehicle_id', 'left')
                          ->join('drivers', 'drivers.id = trips.driver_id', 'left')
                          ->where('trips.id', $id)
                          ->first();

        if (!$trip) {
            return redirect()->to('/trips')->with('error', 'Trip not found.');
        }

        $telemetryModel = new TelemetryModel();
        $breadcrumbs = $telemetryModel->where('trip_id', $id)->orderBy('id', 'ASC')->findAll();

        return view('trips/view', [
            'title'       => 'Trip ' . $trip['trip_number'],
            'trip'        => $trip,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function updateStatus($id)
    {
        $tripModel = new TripModel();
        $trip = $tripModel->find($id);

        if (!$trip) {
            return redirect()->to('/trips')->with('error', 'Trip not found.');
        }

        $newStatus = $this->request->getPost('status');
        $vehicleModel = new VehicleModel();
        $driverModel = new DriverModel();

        $updateData = ['status' => $newStatus];

        $referer = $this->request->getServer('HTTP_REFERER') ?? '';
        $isDriverView = str_contains($referer, 'driver/trips');

        if ($newStatus === 'dispatched' || $newStatus === 'in_transit') {
            if (empty($trip['actual_departure'])) {
                $updateData['actual_departure'] = date('Y-m-d H:i:s');
            }
            $vehicleModel->update($trip['vehicle_id'], [
                'status'            => 'in_transit',
                'current_driver_id' => $trip['driver_id'],
            ]);
            $driverModel->update($trip['driver_id'], ['status' => 'on_trip']);
        } elseif ($newStatus === 'completed') {
            $updateData['actual_arrival'] = date('Y-m-d H:i:s');
            
            $endOdo = (float)$this->request->getPost('end_odometer');
            if ($endOdo <= 0) {
                $vehicle = $vehicleModel->find($trip['vehicle_id']);
                $endOdo = ($vehicle ? (float)$vehicle['odometer_km'] : 0) + (float)$trip['distance_km'];
            }

            $updateData['end_odometer'] = $endOdo;
            $vehicleModel->update($trip['vehicle_id'], [
                'odometer_km' => $endOdo,
                'status'      => 'active',
            ]);

            $driverModel->update($trip['driver_id'], ['status' => 'available']);
            $driver = $driverModel->find($trip['driver_id']);
            if ($driver) {
                $driverModel->update($trip['driver_id'], ['total_trips' => $driver['total_trips'] + 1]);
            }

            // SDD Flow 2 -> Flow 3 link: Check if odometer exceeds next_service_km
            $vehicle = $vehicleModel->find($trip['vehicle_id']);
            if ($vehicle && $vehicle['next_service_km'] && $endOdo >= (float)$vehicle['next_service_km']) {
                $maintModel = new MaintenanceModel();
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
                        'description'         => "Trip {$trip['trip_number']} completed at {$endOdo} km. Vehicle passed service threshold {$vehicle['next_service_km']} km.",
                    ]);

                    // Send alert via SMS to fleet manager
                    $smsSender = new SmsSender();
                    $smsSender->send(
                        '+63 919 555 0300',
                        "PIA Motorpool Maintenance Alert: Vehicle {$vehicle['vehicle_code']} reached {$endOdo} km (Threshold: {$vehicle['next_service_km']} km). Work order {$maintRef} generated."
                    );
                }
            }
        } elseif ($newStatus === 'cancelled') {
            $vehicleModel->update($trip['vehicle_id'], ['status' => 'active']);
            $driverModel->update($trip['driver_id'], ['status' => 'available']);
        }

        $tripModel->update($id, $updateData);

        if ($isDriverView) {
            return redirect()->to('/driver/trips')->with('success', 'Trip ' . $trip['trip_number'] . ' successfully updated to ' . ucfirst(str_replace('_', ' ', $newStatus)) . '.');
        }

        return redirect()->to('/trips/' . $id)->with('success', 'Trip status updated to ' . ucfirst(str_replace('_', ' ', $newStatus)) . '.');
    }
}
