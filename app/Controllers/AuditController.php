<?php

namespace App\Controllers;

use App\Models\TripTicketModel;
use App\Models\TripRequestModel;
use App\Models\PmsRecordModel;
use App\Models\GateLogModel;
use Config\Database;

class AuditController extends BaseController
{
    protected TripTicketModel $ticketModel;
    protected TripRequestModel $requestModel;
    protected PmsRecordModel $pmsModel;
    protected GateLogModel $gateLogModel;
    protected $db;

    public function __construct()
    {
        $this->ticketModel  = new TripTicketModel();
        $this->requestModel = new TripRequestModel();
        $this->pmsModel     = new PmsRecordModel();
        $this->gateLogModel = new GateLogModel();
        $this->db           = Database::connect();
    }

    public function index()
    {
        // 1. SLA Performance Analytics
        $totalRequests = $this->requestModel->countAllResults();
        $slaBreached = $this->requestModel->where('is_sla_breached', 1)->countAllResults();
        $slaComplianceRate = $totalRequests > 0 ? round((($totalRequests - $slaBreached) / $totalRequests) * 100, 1) : 100.0;

        // 2. Rush vs Standard Request Ratio (BR-01 vs BR-03)
        $rushRequests = $this->requestModel->where('is_rush_request', 1)->countAllResults();
        $standardRequests = max(0, $totalRequests - $rushRequests);

        // 3. Fuel Anomaly Flags (>20% deviation or severe consumption anomaly)
        $anomalies = $this->ticketModel->getTicketsWithDetails();
        $flaggedAnomalies = array_filter($anomalies, fn($t) => (int)($t['fuel_anomaly_flag'] ?? 0) === 1 && ($t['status'] ?? '') !== 'cancelled');

        // 4. PMS Status Summary
        $pmsRecords = $this->pmsModel->getPmsWithVehicles();
        $lockedCount = 0;
        $overdueCount = 0;
        $dueSoonCount = 0;
        foreach ($pmsRecords as $p) {
            if ((int)$p['is_locked'] === 1) $lockedCount++;
            if ($p['status'] === 'overdue') $overdueCount++;
            if ($p['status'] === 'due_soon') $dueSoonCount++;
        }

        // 5. Historical Audit Trail of Completed Trips (FR-8.2 / FR-8.3 COA Verified)
        $completedTrips = $this->ticketModel->getTicketsWithDetails(['status' => 'completed'], 50);

        $data = [
            'title'             => 'Commission on Audit (COA) Compliance & Fleet Governance',
            'totalRequests'     => $totalRequests,
            'slaComplianceRate' => $slaComplianceRate,
            'slaBreached'       => $slaBreached,
            'rushRequests'      => $rushRequests,
            'standardRequests'  => $standardRequests,
            'flaggedAnomalies'  => $flaggedAnomalies,
            'pmsRecords'        => $pmsRecords,
            'lockedCount'       => $lockedCount,
            'overdueCount'      => $overdueCount,
            'dueSoonCount'      => $dueSoonCount,
            'completedTrips'    => $completedTrips,
        ];

        return view('audit/index', $data);
    }

    public function exportCsv()
    {
        $rawTrips = $this->ticketModel->getTicketsWithDetails();
        // Exclude cancelled tickets from COA Audit Ledger (keep completed and active official trips)
        $trips = array_filter($rawTrips, fn($t) => ($t['status'] ?? '') !== 'cancelled');

        $filename = 'COA_Trip_Audit_Report_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        // CSV Header following COA Fleet Audit & BRD FR-8.2 / FR-8.3 specifications
        fputcsv($out, [
            'Ticket Serial No', 'VRS Request No', 'Office / Division', 'Plate Number',
            'Driver Name', 'Authorized Passengers', 'Authorized Destination', 'Authorized Purpose', 'Departure Date/Time',
            'Return Date/Time', 'Start Odometer (KM)', 'Return Odometer (KM)', 'Total Distance (KM)',
            'Fuel Start (L)', 'Fuel Issued Stock (L)', 'Fuel Purchased (L)', 'Fuel Purchased Cost (PHP)', 'Fuel Used (L)',
            'Fuel End (L)', 'Fuel Efficiency (KM/L)', 'Toll Expense (PHP)', 'Toll Provider',
            'Cost Per KM (PHP)', 'Fuel Anomaly Flag', 'Driver Certified', 'Passenger Certified', 'Passenger Certifier'
        ]);

        foreach ($trips as $t) {
            $dist = (float)($t['total_distance_km'] ?? 0);
            $fuelCost = (float)($t['fuel_purchased_cost'] ?? 0);
            $tollExpense = (float)($t['toll_expense'] ?? 0);
            $cpk = $dist > 0 ? round(($fuelCost + $tollExpense) / $dist, 2) : 0.00;

            $dest = !empty($t['authorized_destination']) ? $t['authorized_destination'] : ($t['destination'] ?? '');
            $purp = !empty($t['authorized_purpose']) ? $t['authorized_purpose'] : ($t['purpose'] ?? '');
            $pax  = !empty($t['authorized_passengers']) ? $t['authorized_passengers'] : ($t['passenger_names'] ?? '');
            $certifier = $t['passenger_certifier_name'] ?? '';
            $office = !empty($t['office_name']) ? $t['office_name'] : 'Central Office';

            fputcsv($out, [
                $t['ticket_serial_no'] ?? '',
                $t['request_number'] ?? '',
                $office,
                $t['plate_number'] ?? '',
                $t['driver_name'] ?? '',
                $pax,
                $dest,
                $purp,
                $t['departure_time'] ?? $t['authorized_departure'] ?? '',
                $t['arrival_back_time'] ?? $t['authorized_return'] ?? '',
                number_format((float)($t['start_odometer'] ?? 0), 2, '.', ''),
                number_format((float)($t['return_odometer'] ?? 0), 2, '.', ''),
                number_format($dist, 2, '.', ''),
                number_format((float)($t['fuel_balance_start_liters'] ?? 0), 2, '.', ''),
                number_format((float)($t['fuel_issued_stock_liters'] ?? 0), 2, '.', ''),
                number_format((float)($t['fuel_purchased_liters'] ?? 0), 2, '.', ''),
                number_format($fuelCost, 2, '.', ''),
                number_format((float)($t['fuel_used_liters'] ?? 0), 2, '.', ''),
                number_format((float)($t['fuel_balance_end_liters'] ?? 0), 2, '.', ''),
                number_format((float)($t['fuel_efficiency_kml'] ?? 0), 2, '.', ''),
                number_format($tollExpense, 2, '.', ''),
                $t['toll_provider'] ? strtoupper((string)$t['toll_provider']) : '—',
                number_format($cpk, 2, '.', ''),
                ((int)($t['fuel_anomaly_flag'] ?? 0) === 1) ? 'YES (>20% DEVIATION)' : 'NO',
                ((int)($t['driver_certified'] ?? 0) === 1) ? 'YES' : 'NO',
                ((int)($t['passenger_certified'] ?? 0) === 1) ? 'YES' : 'NO',
                $certifier,
            ]);
        }

        fclose($out);

        \App\Services\AuditLogger::log(
            \App\Services\AuditLogger::REPORT_EXPORT,
            'COA Trip Audit Report exported to CSV (' . count($trips) . ' rows).',
            'report'
        );

        exit;
    }

    /**
     * NFR-3 — Immutable system audit trail (approvals, emergency overrides,
     * status changes, login failures — with timestamps and IP addresses).
     */
    public function logs()
    {
        $eventType = $this->request->getGet('event');
        $search    = trim((string) $this->request->getGet('q'));

        $auditModel = new \App\Models\AuditLogModel();
        $builder = $auditModel->orderBy('id', 'DESC');

        if ($eventType) {
            $builder->where('event_type', $eventType);
        }
        if ($search !== '') {
            $builder->groupStart()
                ->like('description', $search)
                ->orLike('user_name', $search)
                ->orLike('ip_address', $search)
                ->groupEnd();
        }

        $data = [
            'title'     => 'System Audit Trail (NFR-3)',
            'logs'      => $builder->findAll(300),
            'event'     => $eventType,
            'search'    => $search,
            'eventTypes'=> [
                'login_success'      => 'Login Success',
                'login_failure'      => 'Login Failure',
                'logout'             => 'Logout',
                'vrs_submitted'      => 'VRS Submitted',
                'vrs_approved'       => 'VRS Approved',
                'vrs_rejected'       => 'VRS Rejected',
                'vrs_escalated'      => 'VRS Escalated (4h SLA)',
                'emergency_override' => 'Emergency Override',
                'vrs_dispatched'     => 'Dispatched',
                'status_change'      => 'Status Change',
                'safety_check_failed'=> 'Safety Check Failed',
                'pir_created'        => 'PIR Created',
                'pir_released'       => 'PIR Released',
                'rfid_reload'        => 'RFID Transaction',
                'mfa_setting_changed'=> 'MFA Setting Changed',
                'report_export'      => 'Report Export',
            ],
        ];

        return view('audit/logs', $data);
    }
}
