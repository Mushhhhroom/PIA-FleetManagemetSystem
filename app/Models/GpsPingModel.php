<?php

namespace App\Models;

use CodeIgniter\Model;

class GpsPingModel extends Model
{
    protected $table            = 'gps_pings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'vehicle_id', 'trip_id', 'latitude', 'longitude', 'speed_kmh',
        'heading', 'fuel_level', 'ignition', 'engine_code', 'recorded_at'
    ];
    protected $useTimestamps = false;

    public function getRecentTrail(int|string $vehicleId, int $limit = 50)
    {
        return $this->where('vehicle_id', $vehicleId)
                    ->orderBy('recorded_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }
}
