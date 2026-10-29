<?php

namespace App\Models;

use CodeIgniter\Model;

class DriverModel extends Model
{
    protected $table            = 'drivers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'user_id', 'driver_code', 'first_name', 'last_name', 'email', 'phone',
        'license_number', 'license_type', 'license_expiry', 'status', 'standby_checkin_at',
        'safety_score', 'total_trips', 'address', 'emergency_contact',
        'created_at', 'updated_at'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getAvailableDrivers()
    {
        return $this->where('status', 'available')->findAll();
    }
}
