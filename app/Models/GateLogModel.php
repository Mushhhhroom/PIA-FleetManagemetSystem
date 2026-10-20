<?php

namespace App\Models;

use CodeIgniter\Model;

class GateLogModel extends Model
{
    protected $table            = 'gate_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'trip_ticket_id', 'event_type', 'guard_user_id', 'guard_name', 'scanned_at',
        'odometer_reading', 'security_status', 'odometer_verified', 'remarks', 'created_at'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    public function getLogsWithDetails($limit = 50)
    {
        return $this->select('gate_logs.*,
                             trip_tickets.ticket_serial_no, trip_tickets.status AS ticket_status,
                             vehicles.plate_number, vehicles.vehicle_code, vehicles.make, vehicles.model,
                             drivers.driver_code, CONCAT(drivers.first_name, \' \', drivers.last_name) AS driver_name,
                             trip_requests.destination, trip_requests.request_number')
                    ->join('trip_tickets', 'trip_tickets.id = gate_logs.trip_ticket_id', 'left')
                    ->join('vehicles', 'vehicles.id = trip_tickets.vehicle_id', 'left')
                    ->join('drivers', 'drivers.id = trip_tickets.driver_id', 'left')
                    ->join('trip_requests', 'trip_requests.id = trip_tickets.request_id', 'left')
                    ->orderBy('gate_logs.id', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }
}
