<?php

namespace App\Models;

use CodeIgniter\Model;

class TripTicketModel extends Model
{
    protected $table            = 'trip_tickets';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'ticket_serial_no', 'request_id', 'merged_request_ids', 'vehicle_id', 'driver_id', 'qr_crypt_token',
        'status', 'authorized_departure', 'authorized_return', 'authorized_passengers',
        'authorized_destination', 'authorized_purpose', 'admin_approver_name',
        'departure_time', 'arrival_dest_time', 'departure_dest_time', 'arrival_back_time',
        'start_odometer', 'dest_odometer', 'return_odometer', 'total_distance_km',
        'fuel_balance_start_liters', 'fuel_issued_stock_liters', 'fuel_purchased_liters',
        'fuel_purchased_cost', 'fuel_used_liters', 'fuel_balance_end_liters', 'fuel_efficiency_kml',
        'fuel_anomaly_flag', 'toll_expense', 'toll_provider',
        'gear_oil_liters', 'lube_oil_liters', 'grease_units',
        'driver_certified', 'driver_certified_at', 'passenger_certified',
        'passenger_certifier_name', 'passenger_certified_at', 'notes', 'created_at', 'updated_at'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getTicketsWithDetails($filters = [], $limit = null)
    {
        $builder = $this->select('trip_tickets.*,
                                  vehicles.plate_number, vehicles.vehicle_code, vehicles.make, vehicles.model, vehicles.fuel_type,
                                  drivers.driver_code, CONCAT(drivers.first_name, " ", drivers.last_name) AS driver_name, drivers.phone AS driver_phone,
                                  trip_requests.request_number, trip_requests.requestor_name, trip_requests.destination,
                                  offices.office_code, offices.office_name')
                        ->join('vehicles', 'vehicles.id = trip_tickets.vehicle_id', 'left')
                        ->join('drivers', 'drivers.id = trip_tickets.driver_id', 'left')
                        ->join('trip_requests', 'trip_requests.id = trip_tickets.request_id', 'left')
                        ->join('offices', 'offices.id = trip_requests.office_id', 'left')
                        ->orderBy('trip_tickets.id', 'DESC');

        if (!empty($filters['status'])) {
            $builder->where('trip_tickets.status', $filters['status']);
        }
        if (!empty($filters['driver_id'])) {
            $builder->where('trip_tickets.driver_id', $filters['driver_id']);
        }
        if (!empty($filters['vehicle_id'])) {
            $builder->where('trip_tickets.vehicle_id', $filters['vehicle_id']);
        }

        if ($limit) {
            $builder->limit($limit);
        }

        return $builder->findAll();
    }

    public function getTicketByIdWithDetails($id)
    {
        return $this->select('trip_tickets.*,
                              vehicles.plate_number, vehicles.vehicle_code, vehicles.make, vehicles.model, vehicles.year, vehicles.fuel_type, vehicles.odometer_km AS vehicle_current_odometer,
                              drivers.driver_code, CONCAT(drivers.first_name, " ", drivers.last_name) AS driver_name, drivers.phone AS driver_phone, drivers.license_number,
                              trip_requests.request_number, trip_requests.requestor_name, trip_requests.destination, trip_requests.purpose, trip_requests.passenger_names, trip_requests.office_scope,
                              offices.office_code, offices.office_name, offices.address AS office_address,
                              disp_u.name AS dispatcher_name, adm_u.name AS admin_name')
                    ->join('vehicles', 'vehicles.id = trip_tickets.vehicle_id', 'left')
                    ->join('drivers', 'drivers.id = trip_tickets.driver_id', 'left')
                    ->join('trip_requests', 'trip_requests.id = trip_tickets.request_id', 'left')
                    ->join('offices', 'offices.id = trip_requests.office_id', 'left')
                    ->join('users AS disp_u', 'disp_u.id = trip_requests.dispatcher_approver_id', 'left')
                    ->join('users AS adm_u', 'adm_u.id = trip_requests.admin_approver_id', 'left')
                    ->where('trip_tickets.id', $id)
                    ->first();
    }

    public function findByTokenOrSerial($identifier)
    {
        $cleanId = trim((string)$identifier);
        if ($cleanId === '') {
            return null;
        }

        $builder = $this->select('trip_tickets.*,
                              vehicles.plate_number, vehicles.vehicle_code, vehicles.make, vehicles.model, vehicles.odometer_km AS vehicle_current_odometer,
                              drivers.driver_code, CONCAT(drivers.first_name, " ", drivers.last_name) AS driver_name, drivers.phone AS driver_phone,
                              drivers.license_number, drivers.license_type,
                              trip_requests.request_number, trip_requests.purpose, trip_requests.passenger_names,
                              offices.office_name')
                    ->join('vehicles', 'vehicles.id = trip_tickets.vehicle_id', 'left')
                    ->join('drivers', 'drivers.id = trip_tickets.driver_id', 'left')
                    ->join('trip_requests', 'trip_requests.id = trip_tickets.request_id', 'left')
                    ->join('offices', 'offices.id = trip_requests.office_id', 'left');

        if (ctype_digit($cleanId)) {
            $numId = (int)$cleanId;
            $builder->groupStart()
                        ->where('trip_tickets.id', $numId)
                        ->orWhere('trip_tickets.qr_crypt_token', $cleanId)
                        ->orWhere('trip_tickets.ticket_serial_no', $cleanId)
                        ->orWhere('trip_tickets.ticket_serial_no LIKE', "%-{$cleanId}")
                        ->orWhere('trip_tickets.ticket_serial_no LIKE', '%-' . sprintf('%04d', $numId))
                    ->groupEnd();
        } else {
            $builder->groupStart()
                        ->where('trip_tickets.qr_crypt_token', $cleanId)
                        ->orWhere('trip_tickets.ticket_serial_no', $cleanId)
                        ->orWhere('trip_tickets.ticket_serial_no LIKE', "%{$cleanId}%")
                    ->groupEnd();
        }

        return $builder->first();
    }

    /**
     * Generate the next e-DTT serial number (e.g. DTT-2026-0008).
     *
     * MAX-based so deleted tickets can never cause a duplicate serial.
     */
    public function generateSerialNo(): string
    {
        $year   = date('Y');
        $prefix = "DTT-{$year}-";
        $row    = $this->db->table('trip_tickets')
            ->select("MAX(CAST(SUBSTRING_INDEX(ticket_serial_no, '-', -1) AS UNSIGNED)) AS max_no")
            ->like('ticket_serial_no', $prefix, 'after')
            ->get()
            ->getRow();

        $next = (int) ($row->max_no ?? 0) + 1;
        do {
            $serial = sprintf('%s%04d', $prefix, $next++);
        } while ($this->db->table('trip_tickets')->where('ticket_serial_no', $serial)->countAllResults() > 0);

        return $serial;
    }
}
