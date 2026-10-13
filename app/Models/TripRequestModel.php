<?php

namespace App\Models;

use CodeIgniter\Model;

class TripRequestModel extends Model
{
    protected $table            = 'trip_requests';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'request_number', 'requestor_id', 'requestor_name', 'office_id', 'department_id', 'office_scope',
        'destination', 'purpose', 'passenger_names', 'passenger_count', 'departure_time',
        'return_time', 'requested_driver_id', 'requested_vehicle_type', 'is_rush_request',
        'justification_file', 'justification_notes', 'status', 'oic_approver_id', 'oic_action',
        'oic_action_at', 'oic_remarks', 'admin_approver_id', 'admin_action', 'admin_action_at',
        'admin_remarks', 'sla_deadline', 'is_sla_breached',
        'is_emergency_override', 'emergency_override_by', 'emergency_override_at',
        'emergency_override_remarks', 'escalated_at',
        'post_trip_doc_due', 'post_trip_doc_status', 'created_at', 'updated_at'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getRequestsWithDetails($filters = [], $limit = null)
    {
        $builder = $this->select('trip_requests.*,
                                  offices.office_code, offices.office_name, offices.office_type,
                                  departments.department_code, departments.department_name, departments.head_name AS department_head,
                                  users.name AS requestor_full_name, users.email AS requestor_email,
                                  oic_u.name AS oic_approver_name,
                                  adm_u.name AS admin_approver_name,
                                  CONCAT(drivers.first_name, " ", drivers.last_name) AS requested_driver_name,
                                  trip_tickets.id AS ticket_id, trip_tickets.ticket_serial_no, trip_tickets.status AS ticket_status')
                        ->join('offices', 'offices.id = trip_requests.office_id', 'left')
                        ->join('departments', 'departments.id = trip_requests.department_id', 'left')
                        ->join('users', 'users.id = trip_requests.requestor_id', 'left')
                        ->join('users AS oic_u', 'oic_u.id = trip_requests.oic_approver_id', 'left')
                        ->join('users AS adm_u', 'adm_u.id = trip_requests.admin_approver_id', 'left')
                        ->join('drivers', 'drivers.id = trip_requests.requested_driver_id', 'left')
                        ->join('trip_tickets', 'trip_tickets.request_id = trip_requests.id', 'left')
                        ->orderBy('trip_requests.id', 'DESC');

        if (!empty($filters['status'])) {
            $builder->where('trip_requests.status', $filters['status']);
        }
        if (!empty($filters['requestor_id'])) {
            $builder->where('trip_requests.requestor_id', $filters['requestor_id']);
        }
        if (!empty($filters['office_id'])) {
            $builder->where('trip_requests.office_id', $filters['office_id']);
        }
        if (!empty($filters['is_rush_request'])) {
            $builder->where('trip_requests.is_rush_request', 1);
        }

        if ($limit) {
            $builder->limit($limit);
        }

        return $builder->findAll();
    }

    public function getRequestByIdWithDetails($id)
    {
        return $this->select('trip_requests.*,
                              offices.office_code, offices.office_name, offices.office_type, offices.address AS office_address,
                              departments.department_code, departments.department_name, departments.head_name AS department_head,
                              users.name AS requestor_full_name, users.email AS requestor_email, users.designation AS requestor_designation,
                              oic_u.name AS oic_approver_name, oic_u.designation AS oic_approver_designation,
                              adm_u.name AS admin_approver_name, adm_u.designation AS admin_approver_designation,
                              CONCAT(drivers.first_name, " ", drivers.last_name) AS requested_driver_name,
                              trip_tickets.id AS ticket_id, trip_tickets.ticket_serial_no, trip_tickets.status AS ticket_status,
                              trip_tickets.qr_crypt_token,
                              vehicles.plate_number, vehicles.make AS vehicle_make, vehicles.model AS vehicle_model')
                    ->join('offices', 'offices.id = trip_requests.office_id', 'left')
                    ->join('departments', 'departments.id = trip_requests.department_id', 'left')
                    ->join('users', 'users.id = trip_requests.requestor_id', 'left')
                    ->join('users AS oic_u', 'oic_u.id = trip_requests.oic_approver_id', 'left')
                    ->join('users AS adm_u', 'adm_u.id = trip_requests.admin_approver_id', 'left')
                    ->join('drivers', 'drivers.id = trip_requests.requested_driver_id', 'left')
                    ->join('trip_tickets', 'trip_tickets.request_id = trip_requests.id', 'left')
                    ->join('vehicles', 'vehicles.id = trip_tickets.vehicle_id', 'left')
                    ->where('trip_requests.id', $id)
                    ->first();
    }

    /**
     * Generate unique VRS sequential tracking number (e.g. VRS-2026-0005)
     */
    public function generateVrsNumber(): string
    {
        $year = date('Y');
        $count = $this->where("request_number LIKE 'VRS-{$year}-%'")->countAllResults();
        $next = $count + 1;
        return sprintf("VRS-%s-%04d", $year, $next);
    }
}
