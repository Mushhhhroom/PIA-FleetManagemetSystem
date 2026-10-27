<?php

namespace App\Controllers;

use App\Models\VehicleModel;
use App\Models\DriverModel;
use App\Models\MaintenanceModel;
use App\Models\FuelLogModel;
use App\Models\TelemetryModel;
use App\Models\TripTicketModel;

class VehicleController extends BaseController
{
    public function index()
    {
        $vehicleModel = new VehicleModel();
        $vehicles = $vehicleModel->getVehiclesWithDriver();

        return view('vehicles/index', [
            'title'    => 'Fleet Vehicles Inventory',
            'vehicles' => $vehicles,
        ]);
    }

    public function create()
    {
        $driverModel = new DriverModel();
        $availableDrivers = $driverModel->getAvailableDrivers();

        return view('vehicles/form', [
            'title'   => 'Register New Vehicle',
            'vehicle' => null,
            'drivers' => $availableDrivers,
        ]);
    }

    public function store()
    {
        $vehicleModel = new VehicleModel();

        $rules = [
            'vehicle_code' => 'required|min_length[2]|is_unique[vehicles.vehicle_code]',
            'plate_number' => 'required|min_length[3]|is_unique[vehicles.plate_number]',
            'vin'          => 'required|min_length[5]|is_unique[vehicles.vin]',
            'make'         => 'required',
            'model'        => 'required',
            'year'         => 'required|numeric',
            'type'         => 'required',
            'fuel_type'    => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $driverId = $this->request->getPost('current_driver_id');
        $driverId = !empty($driverId) ? $driverId : null;

        $data = [
            'vehicle_code'         => strtoupper(trim($this->request->getPost('vehicle_code'))),
            'plate_number'         => strtoupper(trim($this->request->getPost('plate_number'))),
            'vin'                  => strtoupper(trim($this->request->getPost('vin'))),
            'make'                 => trim($this->request->getPost('make')),
            'model'                => trim($this->request->getPost('model')),
            'year'                 => (int)$this->request->getPost('year'),
            'type'                 => $this->request->getPost('type'),
            'fuel_type'            => $this->request->getPost('fuel_type'),
            'max_payload_kg'       => (float)$this->request->getPost('max_payload_kg'),
            'fuel_capacity_liters' => (float)$this->request->getPost('fuel_capacity_liters'),
            'odometer_km'          => (float)$this->request->getPost('odometer_km'),
            'current_latitude'     => (float)($this->request->getPost('current_latitude') ?: 14.599512),
            'current_longitude'    => (float)($this->request->getPost('current_longitude') ?: 120.984222),
            'current_speed'        => 0.00,
            'current_fuel_level'   => (float)($this->request->getPost('current_fuel_level') ?: 100.00),
            'engine_status'        => 'off',
            'status'               => $this->request->getPost('status') ?: 'active',
            'current_driver_id'    => $driverId,
            'next_service_km'      => (float)$this->request->getPost('next_service_km'),
            // FR-2.1 fleet segregation + FR-7.1 regulatory expiries
            'fleet_category'         => $this->request->getPost('fleet_category') === 'dedicated' ? 'dedicated' : 'pool',
            'assigned_official'      => trim((string)$this->request->getPost('assigned_official')) ?: null,
            'lto_registration_expiry'=> $this->request->getPost('lto_registration_expiry') ?: null,
            'gsis_insurance_expiry'  => $this->request->getPost('gsis_insurance_expiry') ?: null,
        ];

        $vehicleModel->insert($data);
        $newVehicleId = (int)($vehicleModel->getInsertID() ?: 0);
        if ($newVehicleId > 0) {
            $pmsModel = new \App\Models\PmsRecordModel();
            $initialOdo = (float)$data['odometer_km'];
            $pmsModel->insert([
                'vehicle_id'        => $newVehicleId,
                'last_pms_odometer' => $initialOdo,
                'next_pms_odometer' => $initialOdo + 5000.00,
                'pms_interval_km'   => 5000.00,
                'is_locked'         => 0,
                'status'            => 'ok',
                'last_pms_date'     => date('Y-m-d'),
                'notes'             => 'Initial baseline PMS schedule on registration.',
            ]);
        }

        \App\Services\AuditLogger::log(
            \App\Services\AuditLogger::STATUS_CHANGE,
            "Vehicle {$data['vehicle_code']} ({$data['plate_number']}) registered — category: {$data['fleet_category']}.",
            'vehicle',
            (int) ($vehicleModel->getInsertID() ?: 0)
        );

        return redirect()->to('/vehicles')->with('success', 'Vehicle ' . $data['vehicle_code'] . ' successfully registered.');
    }

    public function show(int|string $id)
    {
        $vehicleModel = new VehicleModel();
        $vehicle = $vehicleModel->select('vehicles.*, CONCAT(drivers.first_name, \' \', drivers.last_name) AS driver_name, drivers.phone AS driver_phone, drivers.license_number')
                                ->join('drivers', 'drivers.id = vehicles.current_driver_id', 'left')
                                ->where('vehicles.id', $id)
                                ->first();

        if (!$vehicle) {
            return redirect()->to('/vehicles')->with('error', 'Vehicle not found.');
        }

        $ticketModel = new TripTicketModel();
        $trips = $ticketModel->getTicketsWithDetails(['vehicle_id' => $id], 10);

        $maintenanceModel = new MaintenanceModel();
        $maintenance = $maintenanceModel->where('vehicle_id', $id)->orderBy('id', 'DESC')->findAll();

        $fuelModel = new FuelLogModel();
        $fuelLogs = $fuelModel->where('vehicle_id', $id)->orderBy('fuel_date', 'DESC')->findAll();

        $telemetryModel = new TelemetryModel();
        $telemetry = $telemetryModel->getVehicleBreadcrumbs($id, 20);

        return view('vehicles/view', [
            'title'       => 'Vehicle: ' . $vehicle['vehicle_code'] . ' (' . $vehicle['plate_number'] . ')',
            'vehicle'     => $vehicle,
            'trips'       => $trips,
            'maintenance' => $maintenance,
            'fuelLogs'    => $fuelLogs,
            'telemetry'   => $telemetry,
        ]);
    }

    public function edit(int|string $id)
    {
        $vehicleModel = new VehicleModel();
        $vehicle = $vehicleModel->find($id);

        if (!$vehicle) {
            return redirect()->to('/vehicles')->with('error', 'Vehicle not found.');
        }

        $driverModel = new DriverModel();
        $drivers = $driverModel->findAll();

        return view('vehicles/form', [
            'title'   => 'Edit Vehicle: ' . $vehicle['vehicle_code'],
            'vehicle' => $vehicle,
            'drivers' => $drivers,
        ]);
    }

    public function update(int|string $id)
    {
        $vehicleModel = new VehicleModel();
        $vehicle = $vehicleModel->find($id);

        if (!$vehicle) {
            return redirect()->to('/vehicles')->with('error', 'Vehicle not found.');
        }

        $rules = [
            'vehicle_code' => "required|min_length[2]|is_unique[vehicles.vehicle_code,id,{$id}]",
            'plate_number' => "required|min_length[3]|is_unique[vehicles.plate_number,id,{$id}]",
            'vin'          => "required|min_length[5]|is_unique[vehicles.vin,id,{$id}]",
            'make'         => 'required',
            'model'        => 'required',
            'year'         => 'required|numeric',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $driverId = $this->request->getPost('current_driver_id');
        $driverId = !empty($driverId) ? $driverId : null;

        $data = [
            'vehicle_code'         => strtoupper(trim($this->request->getPost('vehicle_code'))),
            'plate_number'         => strtoupper(trim($this->request->getPost('plate_number'))),
            'vin'                  => strtoupper(trim($this->request->getPost('vin'))),
            'make'                 => trim($this->request->getPost('make')),
            'model'                => trim($this->request->getPost('model')),
            'year'                 => (int)$this->request->getPost('year'),
            'type'                 => $this->request->getPost('type'),
            'fuel_type'            => $this->request->getPost('fuel_type'),
            'max_payload_kg'       => (float)$this->request->getPost('max_payload_kg'),
            'fuel_capacity_liters' => (float)$this->request->getPost('fuel_capacity_liters'),
            'odometer_km'          => (float)$this->request->getPost('odometer_km'),
            'current_fuel_level'   => (float)$this->request->getPost('current_fuel_level'),
            'status'               => $this->request->getPost('status'),
            'current_driver_id'    => $driverId,
            'next_service_km'      => (float)$this->request->getPost('next_service_km'),
            // FR-2.1 fleet segregation + FR-7.1 regulatory expiries
            'fleet_category'         => $this->request->getPost('fleet_category') === 'dedicated' ? 'dedicated' : 'pool',
            'assigned_official'      => trim((string)$this->request->getPost('assigned_official')) ?: null,
            'lto_registration_expiry'=> $this->request->getPost('lto_registration_expiry') ?: null,
            'gsis_insurance_expiry'  => $this->request->getPost('gsis_insurance_expiry') ?: null,
        ];

        $previousStatus = $vehicle['status'];
        $vehicleModel->update($id, $data);

        if ($previousStatus !== $data['status']) {
            \App\Services\AuditLogger::log(
                \App\Services\AuditLogger::STATUS_CHANGE,
                "Vehicle {$vehicle['plate_number']} status changed: {$previousStatus} → {$data['status']}.",
                'vehicle',
                (int) $id,
                ['from' => $previousStatus, 'to' => $data['status']]
            );
        }

        return redirect()->to('/vehicles/' . $id)->with('success', 'Vehicle details updated successfully.');
    }

    public function delete(int|string $id)
    {
        $vehicleModel = new VehicleModel();
        $vehicle = $vehicleModel->find($id);

        if ($vehicle) {
            $vehicleModel->delete($id);
            return redirect()->to('/vehicles')->with('success', 'Vehicle removed from registry.');
        }

        return redirect()->to('/vehicles')->with('error', 'Vehicle not found.');
    }
}
