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

    public function getTicketsWithDetails(array $filters = [], ?int $limit = null)
    {
        $builder = $this->select('trip_tickets.*,
                                  vehicles.plate_number, vehicles.vehicle_code, vehicles.make, vehicles.model, vehicles.fuel_type,
                                  drivers.driver_code, CONCAT(drivers.first_name, \' \', drivers.last_name) AS driver_name, drivers.phone AS driver_phone,
                                  trip_requests.request_number, trip_requests.requestor_name, trip_requests.destination, trip_requests.purpose, trip_requests.passenger_names,
                                  offices.office_code, offices.office_name')
                        ->join('vehicles', 'vehicles.id = trip_tickets.vehicle_id', 'left')
                        ->join('drivers', 'drivers.id = trip_tickets.driver_id', 'left')
                        ->join('trip_requests', 'trip_requests.id = trip_tickets.request_id', 'left')
                        ->join('offices', 'offices.id = trip_requests.office_id', 'left')
                        ->orderBy('trip_tickets.id', 'DESC');

        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $builder->whereIn('trip_tickets.status', $filters['status']);
            } else {
                $builder->where('trip_tickets.status', $filters['status']);
            }
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

    public function getTicketByIdWithDetails(int|string $id)
    {
        return $this->select('trip_tickets.*,
                              vehicles.plate_number, vehicles.vehicle_code, vehicles.make, vehicles.model, vehicles.year, vehicles.fuel_type, vehicles.odometer_km AS vehicle_current_odometer,
                              drivers.driver_code, CONCAT(drivers.first_name, \' \', drivers.last_name) AS driver_name, drivers.phone AS driver_phone, drivers.license_number,
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

    public function findByTokenOrSerial(string $identifier)
    {
        $cleanId = trim((string)$identifier);
        if ($cleanId === '') {
            return null;
        }

        // If a full URL or query string was scanned, extract the token/serial parameter or last path segment
        if (str_contains($cleanId, 'http://') || str_contains($cleanId, 'https://')) {
            $parsedUrl = parse_url($cleanId);
            if (!empty($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $qParams);
                if (!empty($qParams['token'])) {
                    $cleanId = trim((string)$qParams['token']);
                } elseif (!empty($qParams['verify'])) {
                    $cleanId = trim((string)$qParams['verify']);
                }
            } elseif (!empty($parsedUrl['path'])) {
                $segments = array_values(array_filter(explode('/', $parsedUrl['path'])));
                $lastSeg  = end($segments);
                if ($lastSeg) {
                    $cleanId = trim((string)$lastSeg);
                }
            }
        }

        $baseSelect = 'trip_tickets.*,
                       vehicles.plate_number, vehicles.vehicle_code, vehicles.make, vehicles.model, vehicles.odometer_km AS vehicle_current_odometer,
                       drivers.driver_code, CONCAT(drivers.first_name, \' \', drivers.last_name) AS driver_name, drivers.phone AS driver_phone,
                       drivers.license_number, drivers.license_type,
                       trip_requests.request_number, trip_requests.purpose, trip_requests.passenger_names,
                       offices.office_name';

        $builder = $this->select($baseSelect)
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

        // Prioritize active/actionable tickets first if multiple match a partial query (portable across MySQL & SQLite3)
        $statusPrioritySql = "CASE trip_tickets.status WHEN 'issued' THEN 1 WHEN 'departed' THEN 2 WHEN 'arrived_dest' THEN 3 WHEN 'departed_dest' THEN 4 WHEN 'returned' THEN 5 WHEN 'completed' THEN 6 ELSE 7 END";
        $builder->orderBy($statusPrioritySql, 'ASC', false)
                ->orderBy('trip_tickets.id', 'DESC');

        $ticket = $builder->first();
        if ($ticket) {
            return $ticket;
        }

        // Fallback: If a driver's QR pass or manual input passed a Vehicle Plate Number, Vehicle Code, or Driver Code,
        // resolve the most recent active ('issued' or 'departed') Trip Ticket for that vehicle/driver.
        $fallbackBuilder = $this->select($baseSelect)
                    ->join('vehicles', 'vehicles.id = trip_tickets.vehicle_id', 'left')
                    ->join('drivers', 'drivers.id = trip_tickets.driver_id', 'left')
                    ->join('trip_requests', 'trip_requests.id = trip_tickets.request_id', 'left')
                    ->join('offices', 'offices.id = trip_requests.office_id', 'left')
                    ->groupStart()
                        ->where('vehicles.plate_number', $cleanId)
                        ->orWhere('vehicles.vehicle_code', $cleanId)
                        ->orWhere('drivers.driver_code', $cleanId)
                    ->groupEnd()
                    ->orderBy($statusPrioritySql, 'ASC', false)
                    ->orderBy('trip_tickets.id', 'DESC');

        return $fallbackBuilder->first();
    }

    /**
     * Generate the next e-DTT serial number (e.g. DTT-2026-0008).
     *
     * MAX-based and portable across MySQL 8 and SQLite3 so deleted tickets
     * can never cause a duplicate serial.
     */
    public function generateSerialNo(): string
    {
        $year   = date('Y');
        $prefix = "DTT-{$year}-";
        $rows   = $this->db->table('trip_tickets')
            ->select('ticket_serial_no')
            ->like('ticket_serial_no', $prefix, 'after')
            ->get()
            ->getResultArray();

        $maxNo = 0;
        foreach ($rows as $r) {
            $serialStr = (string) ($r['ticket_serial_no'] ?? '');
            $parts     = explode('-', $serialStr);
            $seq       = (int) end($parts);
            if ($seq > $maxNo) {
                $maxNo = $seq;
            }
        }

        $next = $maxNo + 1;
        do {
            $serial = sprintf('%s%04d', $prefix, $next++);
        } while ($this->db->table('trip_tickets')->where('ticket_serial_no', $serial)->countAllResults() > 0);

        return $serial;
    }
}
