<?php

namespace App\Models;

use CodeIgniter\Model;

class VehicleModel extends Model
{
    protected $table            = 'vehicles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'vehicle_code', 'plate_number', 'vin', 'make', 'model', 'year', 'type',
        'fuel_type', 'max_payload_kg', 'fuel_capacity_liters', 'odometer_km',
        'current_latitude', 'current_longitude', 'current_speed', 'current_fuel_level',
        'engine_status', 'status', 'current_driver_id', 'last_service_date',
        'next_service_km', 'fleet_category', 'assigned_official',
        'lto_registration_expiry', 'gsis_insurance_expiry', 'created_at', 'updated_at'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getVehiclesWithDriver()
    {
        return $this->select('vehicles.*, CONCAT(drivers.first_name, \' \', drivers.last_name) AS driver_name, drivers.driver_code')
                    ->join('drivers', 'drivers.id = vehicles.current_driver_id', 'left')
                    ->orderBy('vehicles.id', 'DESC')
                    ->findAll();
    }

    public function getAvailableVehicles()
    {
        return $this->where('status', 'active')
                    ->where('current_driver_id IS NULL', null, false)
                    ->findAll();
    }
}
