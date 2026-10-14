<?php

namespace App\Controllers;

use App\Models\SafetyCheckModel;
use App\Models\TripTicketModel;
use App\Models\VehicleModel;
use App\Models\PirReportModel;
use App\Models\NotificationModel;
use App\Models\UserModel;
use App\Services\AuditLogger;

/**
 * Module 5 — Pre-Trip Safety (BLOWBAGETS) & Automated Maintenance (PIR)
 *
 * FR-5.1 (Mandatory Web BLOWBAGETS Checklist): drivers must complete and
 *          pass the digital checklist before starting a trip.
 * FR-5.2 (Fail-Safe Safety Lock): any "Failed" item locks the vehicle to
 *          Under Maintenance, prevents trip activation and routes a PIR
 *          ticket to the mechanic queue.
 */
class SafetyChecklistController extends BaseController
{
    protected SafetyCheckModel $safetyModel;
    protected TripTicketModel $ticketModel;
    protected VehicleModel $vehicleModel;
    protected PirReportModel $pirModel;
    protected NotificationModel $notificationModel;

    public function __construct()
    {
        $this->safetyModel       = new SafetyCheckModel();
        $this->ticketModel       = new TripTicketModel();
        $this->vehicleModel      = new VehicleModel();
        $this->pirModel          = new PirReportModel();
        $this->notificationModel = new NotificationModel();
    }

    /**
     * Show the BLOWBAGETS checklist for a trip ticket (FR-5.1).
     */
    public function create($ticketId)
    {
        $ticket = $this->ticketModel->getTicketByIdWithDetails($ticketId);
        if (!$ticket) {
            return redirect()->to('/tickets')->with('error', 'Trip ticket not found.');
        }

        // Drivers may only complete their own checklist
        $session = session();
        if ($session->get('user_role') === 'driver') {
            $driver = (new \App\Models\DriverModel())->where('user_id', $session->get('user_id'))->first();
            if (!$driver || (int) $driver['id'] !== (int) $ticket['driver_id']) {
                return redirect()->to('/driver/trips')->with('error', 'You can only complete safety checks for your own dispatches.');
            }
        }

        $vehicle = $this->vehicleModel->find($ticket['vehicle_id']);

        $data = [
            'title'    => "BLOWBAGETS Pre-Trip Safety Check — {$ticket['ticket_serial_no']}",
            'ticket'   => $ticket,
            'vehicle'  => $vehicle,
            'items'    => SafetyCheckModel::checklistItems(),
            'history'  => $this->safetyModel->getForTicket((int) $ticketId),
        ];

        return view('safety/create', $data);
    }

    /**
     * FR-5.1 + FR-5.2: evaluate the submitted checklist.
     *  - All pass  → checklist recorded, trip activation unlocked.
     *  - Any fail  → vehicle locked to Under Maintenance, PIR auto-routed
     *                 to the mechanic queue, trip activation blocked.
     */
    public function store($ticketId)
    {
        $ticket = $this->ticketModel->find($ticketId);
        if (!$ticket) {
            return redirect()->to('/tickets')->with('error', 'Trip ticket not found.');
        }

        $session   = session();
        $userId    = $session->get('user_id');
        $userName  = $session->get('user_name');
        $userRole  = $session->get('user_role');

        $items     = SafetyCheckModel::checklistItems();
        $submitted = $this->request->getPost('check') ?? [];
        $remarks   = trim((string) $this->request->getPost('remarks'));

        if (count($submitted) < count($items)) {
            return redirect()->back()->withInput()->with('error', 'All BLOWBAGETS items must be inspected — every item requires a Pass/Fail answer (FR-5.1).');
        }

        $failed = [];
        foreach ($items as $code => $label) {
            if (($submitted[$code] ?? '') !== 'pass') {
                $failed[$code] = $label;
            }
        }

        $result = empty($failed) ? 'pass' : 'fail';
        $now    = date('Y-m-d H:i:s');

        $checkId = $this->safetyModel->insert([
            'trip_ticket_id' => (int) $ticketId,
            'vehicle_id'     => (int) $ticket['vehicle_id'],
            'driver_id'      => (int) $ticket['driver_id'],
            'items'          => json_encode($submitted, JSON_UNESCAPED_UNICODE),
            'failed_items'   => empty($failed) ? null : json_encode($failed, JSON_UNESCAPED_UNICODE),
            'result'         => $result,
            'remarks'        => $remarks ?: null,
            'checked_by'     => $userId ? (int) $userId : null,
            'created_at'     => $now,
        ]);

        if ($result === 'pass') {
            AuditLogger::log(
                AuditLogger::STATUS_CHANGE,
                "BLOWBAGETS PASS for ticket #{$ticketId} (vehicle {$ticket['vehicle_id']}) by {$userName}. Trip activation unlocked.",
                'safety_check',
                (int) $checkId
            );

            return redirect()->to("/tickets/{$ticketId}")->with('success', 'BLOWBAGETS pre-trip safety check PASSED. Trip activation unlocked — you may depart.');
        }

        // ------------------------------------------------------------------
        // FR-5.2 Fail-Safe Safety Lock
        // ------------------------------------------------------------------
        $vehicle   = $this->vehicleModel->find($ticket['vehicle_id']);
        $failedTxt = implode('; ', $failed);

        // 1. Lock the vehicle state to Under Maintenance
        $this->vehicleModel->update($ticket['vehicle_id'], ['status' => 'under_maintenance']);

        // 2. Prevent trip activation: cancel the issued ticket
        $this->ticketModel->update($ticketId, ['status' => 'cancelled']);

        // 3. Route a PIR ticket to the mechanic queue
        $pirNumber = $this->pirModel->generatePirNumber();
        $pirId = $this->pirModel->insert([
            'pir_number'          => $pirNumber,
            'vehicle_id'          => (int) $ticket['vehicle_id'],
            'trip_ticket_id'      => (int) $ticketId,
            'source'              => 'blowbagets',
            'defect_description'  => "Pre-trip BLOWBAGETS failure. Failed items: {$failedTxt}." . ($remarks ? " Driver remarks: {$remarks}" : ''),
            'status'              => 'pending',
            'reported_by'         => $userId ? (int) $userId : null,
            'created_at'          => $now,
        ]);

        // 4. Notify mechanics + dispatchers
        $userModel = new UserModel();
        $recipients = array_merge(
            $userModel->where('role', 'maintenance')->where('status', 'active')->findAll(),
            $userModel->where('role', 'dispatcher')->where('status', 'active')->findAll(),
            $userModel->where('role', 'admin')->where('status', 'active')->findAll()
        );
        foreach ($recipients as $recip) {
            $this->notificationModel->insert([
                'user_id'    => $recip['id'],
                'title'      => "VEHICLE LOCKED — Safety Failure {$pirNumber}",
                'message'    => "Vehicle {$vehicle['plate_number']} failed the BLOWBAGETS pre-trip check ({$failedTxt}). State locked to Under Maintenance; trip {$ticket['ticket_serial_no']} cancelled; PIR {$pirNumber} queued for mechanics.",
                'type'       => 'danger',
                'link'       => '/maintenance',
                'is_read'    => 0,
                'created_at' => $now,
            ]);
        }

        AuditLogger::log(
            AuditLogger::SAFETY_CHECK_FAIL,
            "BLOWBAGETS FAIL for ticket #{$ticketId} by {$userName}. Failed: {$failedTxt}. Vehicle {$vehicle['plate_number']} locked to Under Maintenance; PIR {$pirNumber} auto-created (FR-5.2).",
            'safety_check',
            (int) $checkId,
            ['failed' => $failed, 'pir' => $pirNumber]
        );

        return redirect()->to("/tickets/{$ticketId}")->with('error', "SAFETY LOCK ENGAGED (FR-5.2): failed items — {$failedTxt}. Vehicle locked to Under Maintenance, trip cancelled, and PIR {$pirNumber} routed to the mechanic queue.");
    }

    /**
     * Read-only history of safety checks (dispatcher/auditor view).
     */
    public function index()
    {
        $checks = $this->safetyModel
            ->select('safety_checks.*, vehicles.plate_number, vehicles.make, vehicles.model,
                      trip_tickets.ticket_serial_no,
                      CONCAT(drivers.first_name, " ", drivers.last_name) AS driver_name')
            ->join('vehicles', 'vehicles.id = safety_checks.vehicle_id', 'left')
            ->join('trip_tickets', 'trip_tickets.id = safety_checks.trip_ticket_id', 'left')
            ->join('drivers', 'drivers.id = safety_checks.driver_id', 'left')
            ->orderBy('safety_checks.id', 'DESC')
            ->findAll(200);

        $data = [
            'title'  => 'Pre-Trip Safety (BLOWBAGETS) Registry',
            'checks' => $checks,
        ];

        return view('safety/index', $data);
    }
}
