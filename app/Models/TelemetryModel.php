<?php

namespace App\Models;

use CodeIgniter\Model;

class TelemetryModel extends Model
{
    protected $table            = 'telemetry_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'vehicle_id', 'trip_id', 'latitude', 'longitude', 'speed_kmh',
        'heading', 'fuel_level', 'engine_status', 'battery_voltage', 'engine_temp_c',
        'created_at'
    ];
    protected $useTimestamps = false;

    public function getLatestForVehicle(int|string $vehicleId)
    {
        return $this->where('vehicle_id', (int) $vehicleId)
                    ->orderBy('id', 'DESC')
                    ->first();
    }

    public function getVehicleBreadcrumbs(int|string $vehicleId, int $limit = 30)
    {
        return $this->where('vehicle_id', (int) $vehicleId)
                    ->orderBy('id', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }
}
