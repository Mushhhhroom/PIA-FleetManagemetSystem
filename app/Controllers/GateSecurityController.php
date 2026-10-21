<?php

namespace App\Controllers;

use App\Models\TripTicketModel;
use App\Models\GateLogModel;
use App\Models\VehicleModel;
use App\Models\DriverModel;
use App\Models\PmsRecordModel;
use App\Models\NotificationModel;
use App\Services\SecurityQrService;

class GateSecurityController extends BaseController
{
    protected TripTicketModel $ticketModel;
    protected GateLogModel $gateLogModel;
    protected VehicleModel $vehicleModel;
    protected DriverModel $driverModel;
    protected PmsRecordModel $pmsModel;
    protected NotificationModel $notificationModel;
    protected SecurityQrService $qrService;

    public function __construct()
    {
        $this->ticketModel       = new TripTicketModel();
        $this->gateLogModel      = new GateLogModel();
        $this->vehicleModel      = new VehicleModel();
        $this->driverModel       = new DriverModel();
        $this->pmsModel          = new PmsRecordModel();
        $this->notificationModel = new NotificationModel();
        $this->qrService         = new SecurityQrService();
    }

    public function index()
    {
        $recentLogs = $this->gateLogModel->getLogsWithDetails(25);

        // Vehicles currently on active duty outside compound
        $vehiclesOut = $this->ticketModel->getTicketsWithDetails(['status' => ['departed', 'arrived_dest', 'departed_dest']]);

        // Vehicles pending departure (enrich with BLOWBAGETS safety status)
        $vehiclesPendingDeparture = $this->ticketModel->getTicketsWithDetails(['status' => 'issued']);
        $safetyModel = new \App\Models\SafetyCheckModel();
        foreach ($vehiclesPendingDeparture as &$vp) {
            $vp['has_passed_safety'] = $safetyModel->hasPassedCheck((int) $vp['id']);
        }
        unset($vp);

        $data = [
            'title'                    => 'Compound Security Gate & QR Checkpoint',
            'recentLogs'               => $recentLogs,
            'vehiclesOut'              => $vehiclesOut,
            'vehiclesPendingDeparture' => $vehiclesPendingDeparture,
        ];

        return view('gate/index', $data);
    }

    /**
     * AJAX endpoint: Verify scanned QR token or manual serial number
     */
    public function verify()
    {
        $token = trim((string)($this->request->getPost('token') ?: $this->request->getPost('qr_token') ?: $this->request->getGet('token')));
        if (empty($token)) {
            return $this->response->setJSON([
                'status'    => 'error',
                'message'   => 'No QR token or serial number provided.',
                'csrf_hash' => csrf_hash(),
            ]);
        }

        // 1. Try finding ticket by token or serial
        $ticket = $this->ticketModel->findByTokenOrSerial($token);

        if (!$ticket) {
            // Also attempt HMAC-SHA256 signature verification directly
            $verifiedPayload = $this->qrService->verifyToken($token);
            if ($verifiedPayload) {
                $ticket = $this->ticketModel->findByTokenOrSerial($verifiedPayload['serial_no']);
            }
        }

        if (!$ticket) {
            return $this->response->setJSON([
                'status'    => 'error',
                'message'   => 'INVALID QR TOKEN: Security signature mismatch or unregistered trip ticket.',
                'csrf_hash' => csrf_hash(),
            ]);
        }

        // Determine recommended action (Egress or Ingress)
        $ticketId = (int)$ticket['id'];
        $safetyModel = new \App\Models\SafetyCheckModel();
        $hasPassedSafety = $safetyModel->hasPassedCheck($ticketId);
        $latestSafety = $safetyModel->latestForTicket($ticketId);

        $isEgress  = in_array($ticket['status'], ['issued'], true);
        $isIngress = in_array($ticket['status'], ['departed', 'arrived_dest', 'departed_dest'], true);
        $eventType = $isEgress ? 'egress' : ($isIngress ? 'ingress' : 'review');

        // High-visibility verdict determination
        $verdict = 'pass';
        $flagReason = '';
        $canAction = false;

        if ($ticket['status'] === 'cancelled') {
            $verdict = 'reject';
            $flagReason = 'Ticket Cancelled due to BLOWBAGETS Failure or Admin Revocation.';
            $canAction = false;
        } elseif ($isEgress) {
            if (!$hasPassedSafety) {
                $verdict = 'reject';
                $detail = $latestSafety
                    ? 'Latest check: ' . strtoupper($latestSafety['result'])
                    : 'No checklist on file';
                $flagReason = 'Ticket Denied due to BLOWBAGETS Safety Checklist Failure (' . $detail . ').';
                $canAction = false;
            } else {
                $verdict = 'pass';
                $canAction = true;
            }
        } elseif ($isIngress) {
            $verdict = 'pass';
            $canAction = true;
        } else {
            $verdict = 'review';
            $flagReason = 'Trip status is already ' . strtoupper($ticket['status']) . '. Gate clearance already processed.';
            $canAction = false;
        }

        $now = date('Y-m-d H:i:s');

        return $this->response->setJSON([
            'status'                        => 'success',
            'verdict'                       => $verdict,
            'flag_reason'                   => $flagReason,
            'ticket'                        => $ticket,
            'has_passed_safety'             => $hasPassedSafety,
            'event_type'                    => $eventType,
            'action_label'                  => $isEgress ? 'Validate & Record Compound Departure (Egress)' : ($isIngress ? 'Validate & Record Compound Return (Ingress)' : 'Inspect Completed Ticket'),
            'can_action'                    => $canAction,
            'automated_timestamp'           => $now,
            'automated_timestamp_formatted' => date('M d, Y h:i:s A', strtotime($now)),
            'departure_time_formatted'      => !empty($ticket['departure_time']) ? date('M d, Y h:i:s A', strtotime($ticket['departure_time'])) : null,
            'arrival_back_time_formatted'   => !empty($ticket['arrival_back_time']) ? date('M d, Y h:i:s A', strtotime($ticket['arrival_back_time'])) : null,
            'csrf_hash'                     => csrf_hash(),
        ]);
    }

    /**
     * Record gate entry/exit clearance and automatically stamp Compound Departure / Arrival date & time
     */
    public function recordScan()
    {
        $session = session();
        $guardId = $session->get('user_id') ?? 1;
        $guardName = $session->get('user_name') ?? 'Gate Security Officer';

        $ticketId = (int)$this->request->getPost('ticket_id');
        $eventType = $this->request->getPost('event_type'); // 'egress' or 'ingress'
        $odometer = (float)$this->request->getPost('odometer_reading');
        $remarks = trim((string)$this->request->getPost('remarks'));
        $driverValidated = (int)($this->request->getPost('driver_validated') ?? 1);
        $passengersValidated = (int)($this->request->getPost('passengers_validated') ?? 1);
        $now = date('Y-m-d H:i:s');
        $nowFormatted = date('M d, Y h:i:s A', strtotime($now));

        $ticket = $this->ticketModel->getTicketByIdWithDetails($ticketId);
        if (!$ticket) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Trip ticket not found.']);
        }

        if (!$driverValidated || !$passengersValidated) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'COMPOUND AUDIT HOLD: Both Driver Identity and Passenger Manifest must be validated at the gate before recording compound departure/arrival.',
            ]);
        }

        $vehicleId = (int)$ticket['vehicle_id'];
        $driverId  = (int)$ticket['driver_id'];
        $currentVehicleOdo = (float)$ticket['vehicle_current_odometer'];

        // Default odometer safely if omitted
        if ($odometer <= 0) {
            $odometer = $eventType === 'egress'
                ? $currentVehicleOdo
                : max((float)($ticket['start_odometer'] ?? $currentVehicleOdo), $currentVehicleOdo);
        }

        // Strict Odometer Validation
        if ($eventType === 'egress') {
            // ------------------------------------------------------------------
            // FR-5.1 (Mandatory BLOWBAGETS Checklist): trip activation is
            // blocked at the gate until the driver's checklist has PASSED.
            // ------------------------------------------------------------------
            $safetyModel = new \App\Models\SafetyCheckModel();
            if (!$safetyModel->hasPassedCheck($ticketId)) {
                $latest = $safetyModel->latestForTicket($ticketId);
                $detail = $latest
                    ? ' The most recent checklist result was "' . strtoupper($latest['result']) . '".'
                    : ' No BLOWBAGETS checklist has been submitted for this trip.';
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => "SAFETY HOLD (FR-5.1): Trip ticket {$ticket['ticket_serial_no']} has no PASSED BLOWBAGETS pre-trip safety check. Egress denied until the driver completes and passes the checklist.{$detail}",
                ]);
            }

            if ($odometer < $currentVehicleOdo) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => "ODOMETER VALIDATION ERROR: Entered departure odometer ({$odometer} km) cannot be less than vehicle's current recorded odometer ({$currentVehicleOdo} km).",
                ]);
            }
        } elseif ($eventType === 'ingress') {
            $startOdo = (float)($ticket['start_odometer'] ?? $currentVehicleOdo);
            if ($odometer < $startOdo) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => "ODOMETER VALIDATION ERROR: Return odometer ({$odometer} km) cannot be less than trip departure odometer ({$startOdo} km).",
                ]);
            }
        }

        $auditRemarks = $remarks ?: ($eventType === 'egress'
            ? "Compound Egress: Driver & passengers validated by {$guardName}. Departure auto-stamped at {$nowFormatted}."
            : "Compound Ingress: Driver & vehicle return validated by {$guardName}. Arrival back auto-stamped at {$nowFormatted}.");

        // Record Gate Log
        $this->gateLogModel->insert([
            'trip_ticket_id'    => $ticketId,
            'event_type'        => $eventType,
            'guard_user_id'     => $guardId,
            'guard_name'        => $guardName,
            'scanned_at'        => $now,
            'odometer_reading'  => $odometer,
            'security_status'   => 'cleared',
            'odometer_verified' => 1,
            'remarks'           => $auditRemarks,
            'created_at'        => $now,
        ]);

        // Update Ticket & Vehicle States (Automatically stamping Compound Departure & Compound Return Date/Time)
        if ($eventType === 'egress') {
            $this->ticketModel->update($ticketId, [
                'status'         => 'departed',
                'departure_time' => $now,
                'start_odometer' => $odometer,
            ]);

            $this->vehicleModel->update($vehicleId, [
                'status'            => 'in_transit',
                'odometer_km'       => $odometer,
                'current_driver_id' => $driverId,
            ]);

            \App\Services\AuditLogger::log(
                \App\Services\AuditLogger::STATUS_CHANGE,
                "Compound Gate EGRESS QR Scan: {$ticket['ticket_serial_no']} ({$ticket['plate_number']}) validated by {$guardName}. Automated Departure Date/Time stamped: {$now}.",
                'trip_ticket',
                $ticketId,
                ['event' => 'egress', 'departure_time' => $now, 'start_odometer' => $odometer]
            );

            $msg = "Cleared for EGRESS. Compound Departure Date & Time ({$nowFormatted}) and Start Odometer ({$odometer} km) automatically stamped on {$ticket['ticket_serial_no']}.";
        } else {
            // Ingress
            $startOdo = (float)($ticket['start_odometer'] ?? $odometer);
            $distKm = max(0, $odometer - $startOdo);

            $this->ticketModel->update($ticketId, [
                'status'            => 'returned',
                'arrival_back_time' => $now,
                'return_odometer'   => $odometer,
                'total_distance_km' => $distKm,
            ]);

            $this->vehicleModel->update($vehicleId, [
                'status'            => 'active',
                'odometer_km'       => $odometer,
                'current_driver_id' => null,
            ]);

            $this->driverModel->update($driverId, [
                'status' => 'available',
            ]);

            // Update PMS tracking
            $pms = $this->pmsModel->where('vehicle_id', $vehicleId)->first();
            if ($pms) {
                $nextPms = (float)$pms['next_pms_odometer'];
                $diff = $nextPms - $odometer;
                $pmsStatus = 'ok';
                $isLocked = 0;
                if ($diff <= 0) {
                    $pmsStatus = 'overdue';
                    $isLocked = 1;
                } elseif ($diff <= 500) {
                    $pmsStatus = 'due_soon';
                }
                $this->pmsModel->update($pms['id'], [
                    'status'    => $pmsStatus,
                    'is_locked' => $isLocked,
                ]);
            }

            \App\Services\AuditLogger::log(
                \App\Services\AuditLogger::STATUS_CHANGE,
                "Compound Gate INGRESS QR Scan: {$ticket['ticket_serial_no']} ({$ticket['plate_number']}) validated by {$guardName}. Automated Arrival Back Date/Time stamped: {$now}.",
                'trip_ticket',
                $ticketId,
                ['event' => 'ingress', 'arrival_back_time' => $now, 'return_odometer' => $odometer, 'distance_km' => $distKm]
            );

            $msg = "Cleared for INGRESS. Compound Arrival Date & Time ({$nowFormatted}) and Return Odometer ({$odometer} km) automatically stamped on {$ticket['ticket_serial_no']}. Total distance: {$distKm} km.";
        }

        return $this->response->setJSON([
            'status'               => 'success',
            'message'              => $msg,
            'stamped_at'           => $now,
            'stamped_at_formatted' => $nowFormatted,
            'event_type'           => $eventType,
            'ticket_serial_no'     => $ticket['ticket_serial_no'],
        ]);
    }
}
