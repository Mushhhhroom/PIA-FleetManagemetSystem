<?php

namespace App\Models;

use CodeIgniter\Model;

class MaintenanceModel extends Model
{
    protected $table            = 'maintenance_records';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'reference_no', 'vehicle_id', 'service_type', 'priority', 'scheduled_date',
        'completion_date', 'odometer_at_service', 'service_center', 'technician_name',
        'cost', 'status', 'description', 'notes', 'created_at', 'updated_at'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getRecordsWithVehicle(?int $limit = null)
    {
        $builder = $this->select('maintenance_records.*, vehicles.vehicle_code, vehicles.plate_number, vehicles.make, vehicles.model')
                        ->join('vehicles', 'vehicles.id = maintenance_records.vehicle_id', 'left')
                        ->orderBy('maintenance_records.id', 'DESC');

        if ($limit) {
            $builder->limit($limit);
        }

        return $builder->findAll();
    }
}
