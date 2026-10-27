<?php

namespace App\Models;

use CodeIgniter\Model;

class IncidentModel extends Model
{
    protected $table            = 'incidents';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'incident_no', 'vehicle_id', 'driver_id', 'trip_id', 'incident_date',
        'severity', 'type', 'location', 'description', 'damage_estimate',
        'status', 'created_at', 'updated_at'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getIncidentsWithDetails(?int $limit = null)
    {
        $builder = $this->select('incidents.*, vehicles.vehicle_code, vehicles.plate_number, CONCAT(drivers.first_name, \' \', drivers.last_name) AS driver_name')
                        ->join('vehicles', 'vehicles.id = incidents.vehicle_id', 'left')
                        ->join('drivers', 'drivers.id = incidents.driver_id', 'left')
                        ->orderBy('incidents.incident_date', 'DESC');

        if ($limit) {
            $builder->limit($limit);
        }

        return $builder->findAll();
    }
}
