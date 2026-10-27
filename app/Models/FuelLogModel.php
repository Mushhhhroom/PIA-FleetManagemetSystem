<?php

namespace App\Models;

use CodeIgniter\Model;

class FuelLogModel extends Model
{
    protected $table            = 'fuel_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'receipt_no', 'vehicle_id', 'driver_id', 'fuel_date', 'odometer_km',
        'liters', 'cost_per_liter', 'total_cost', 'fuel_station', 'notes',
        'created_at', 'updated_at'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getLogsWithDetails(?int $limit = null)
    {
        $builder = $this->select('fuel_logs.*, vehicles.vehicle_code, vehicles.plate_number, CONCAT(drivers.first_name, \' \', drivers.last_name) AS driver_name')
                        ->join('vehicles', 'vehicles.id = fuel_logs.vehicle_id', 'left')
                        ->join('drivers', 'drivers.id = fuel_logs.driver_id', 'left')
                        ->orderBy('fuel_logs.fuel_date', 'DESC');

        if ($limit) {
            $builder->limit($limit);
        }

        return $builder->findAll();
    }
}
