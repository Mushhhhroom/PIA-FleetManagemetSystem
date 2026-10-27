<?php

namespace App\Controllers;

use App\Models\TripRequestModel;
use App\Models\TripTicketModel;
use App\Models\VehicleModel;
use App\Models\DriverModel;
use App\Models\PmsRecordModel;
use App\Models\NotificationModel;
use App\Services\DispatchEngineService;
use App\Services\SecurityQrService;
use App\Services\AuditLogger;

class DispatchController extends BaseController
{
    protected TripRequestModel $requestModel;
    protected TripTicketModel $ticketModel;
    protected VehicleModel $vehicleModel;
    protected DriverModel $driverModel;
    protected PmsRecordModel $pmsModel;
    protected NotificationModel $notificationModel;
    protected DispatchEngineService $dispatchEngine;
    protected SecurityQrService $qrService;

    public function __construct()
    {
        $this->request           ??= service('request');
        $this->response          ??= service('response');
        $this->requestModel      = new TripRequestModel();
        $this->ticketModel       = new TripTicketModel();
        $this->vehicleModel      = new VehicleModel();
        $this->driverModel       = new DriverModel();
        $this->pmsModel          = new PmsRecordModel();
        $this->notificationModel = new NotificationModel();
        $this->dispatchEngine    = new DispatchEngineService();
        $this->qrService         = new SecurityQrService();
    }

    public function index()
    {
        // 0. Pending Tier 1 VRS requests awaiting Dispatcher document check & endorsement
        $pendingTier1 = $this->requestModel->getRequestsWithDetails(['status' => 'pending_dispatcher']);
        $totalDriverConflicts = 0;

        foreach ($pendingTier1 as &$t1) {
            $t1['sla_info'] = \App\Services\SlaService::formatSlaCountdown($t1['sla_deadline']);
            $t1['driver_conflict'] = false;
            $t1['driver_conflict_reason'] = null;

            if (!empty($t1['requested_driver_id'])) {
                $check = $this->dispatchEngine->checkDriverAvailability(
                    (int)$t1['requested_driver_id'],
                    $t1['departure_time'],
                    $t1['return_time'],
                    (int)$t1['id']
                );
                if (!$check['available']) {
                    $t1['driver_conflict'] = true;
                    $t1['driver_conflict_reason'] = $check['reason'];
                    $totalDriverConflicts++;
                }
            }
        }
        unset($t1);

        // 1. Approved requests waiting for dispatch
        $pendingDispatch = $this->requestModel->getRequestsWithDetails(['status' => 'approved']);

        foreach ($pendingDispatch as &$r) {
            $r['driver_conflict'] = false;
            $r['driver_conflict_reason'] = null;

            if (!empty($r['requested_driver_id'])) {
                $check = $this->dispatchEngine->checkDriverAvailability(
                    (int)$r['requested_driver_id'],
                    $r['departure_time'],
                    $r['return_time'],
                    (int)$r['id']
                );
                if (!$check['available']) {
                    $r['driver_conflict'] = true;
                    $r['driver_conflict_reason'] = $check['reason'];
                    $totalDriverConflicts++;
                }
            }

            // Find mergeable requests count for this approved request
            $mergeCandidates = $this->dispatchEngine->findMergeableRequests($r);
            $r['merge_candidates_count'] = count($mergeCandidates);
        }
        unset($r);

        // 2. Active dispatched trips with tickets
        $activeDispatches = $this->ticketModel->getTicketsWithDetails();

        // 3. Vehicles with PMS Status
        $vehicles = $this->pmsModel->getPmsWithVehicles();

        // 4. Drivers
        $drivers = $this->driverModel->findAll();

        $data = [
            'title'                => 'Motorpool Dispatch Command & Heuristic Allocation',
            'pendingTier1'         => $pendingTier1,
            'pendingDispatch'      => $pendingDispatch,
            'activeDispatches'     => $activeDispatches,
            'vehicles'             => $vehicles,
            'drivers'              => $drivers,
            'totalDriverConflicts' => $totalDriverConflicts,
        ];

        return view('dispatch/index', $data);
    }

    public function assign(int|string $requestId)
    {
        $request = $this->requestModel->getRequestByIdWithDetails($requestId);
        if (!$request) {
            return redirect()->to('/dispatch')->with('error', 'Vehicle request not found.');
        }

        if ($request['status'] !== 'approved') {
            return redirect()->to('/dispatch')->with('error', 'Only authorized requests can be dispatched.');
        }

        // Calculate Smart Recommendations using Heuristic Formula
        $recommendations = $this->dispatchEngine->getRecommendations($request);

        // Find mergeable candidates (same date/window, matching corridor)
        $mergeCandidates = $this->dispatchEngine->findMergeableRequests($request);

        // Parse preselected merge IDs from query parameter (?merge=id1,id2)
        $req = $this->request ?? service('request');
        $mergeParam = $req ? $req->getGet('merge') : null;
        $preselectedMergeIds = [];
        if (!empty($mergeParam)) {
            $preselectedMergeIds = array_filter(array_map('intval', explode(',', $mergeParam)));
        }

        $data = [
            'title'               => "Dispatch Assignment: {$request['request_number']}",
            'request'             => $request,
            'recommendations'     => $recommendations,
            'mergeCandidates'     => $mergeCandidates,
            'preselectedMergeIds' => $preselectedMergeIds,
        ];

        return view('dispatch/assign', $data);
    }

    public function storeAssignment(int|string $requestId)
    {
        $request = $this->requestModel->getRequestByIdWithDetails($requestId);
        if (!$request || $request['status'] !== 'approved') {
            return redirect()->to('/dispatch')->with('error', 'Invalid or unapproved request.');
        }

        $vehicleId = (int)$this->request->getPost('vehicle_id');
        $driverId  = (int)$this->request->getPost('driver_id');

        if (!$vehicleId || !$driverId) {
            return redirect()->back()->with('error', 'Please select both an authorized vehicle and driver.');
        }

        $vehicle = $this->vehicleModel->find($vehicleId);
        $driver  = $this->driverModel->find($driverId);

        if (!$vehicle || !$driver) {
            return redirect()->back()->with('error', 'Selected vehicle or driver does not exist.');
        }

        // Strict Driver Availability Validation
        $driverCheck = $this->dispatchEngine->checkDriverAvailability(
            $driverId,
            $request['departure_time'],
            $request['return_time'],
            (int)$requestId
        );
        if (!$driverCheck['available']) {
            return redirect()->back()->with(
                'error',
                "Assignment Denied: {$driverCheck['reason']} Please choose an active and available certified driver."
            );
        }

        // FR-5.1/5.2: a locked (Under Maintenance / Disabled) vehicle can never be dispatched
        if (in_array($vehicle['status'], ['maintenance', 'under_maintenance', 'disabled_breakdown', 'out_of_service'], true)) {
            return redirect()->back()->with('error', "Safety lock: vehicle {$vehicle['plate_number']} is in '{$vehicle['status']}' status and cannot be dispatched (FR-5.2).");
        }

        // FR-2.1 (Fleet Segregation): dedicated executive vehicles are reserved
        // for their designated senior official.
        if (($vehicle['fleet_category'] ?? 'pool') === 'dedicated'
            && !empty($vehicle['assigned_official'])) {
            $official = strtolower(trim($vehicle['assigned_official']));
            $requestor = strtolower(trim($request['requestor_name'] ?? ''));
            if ($requestor === '' || (!str_contains($official, $requestor) && !str_contains($requestor, $official))) {
                return redirect()->back()->with(
                    'error',
                    "Fleet segregation rule (FR-2.1): {$vehicle['plate_number']} is a Dedicated Executive vehicle reserved for {$vehicle['assigned_official']}. Select a Shared Pool vehicle instead."
                );
            }
        }

        // FR-2.2 (Interactive Calendar / no double-booking): reject when the
        // vehicle or driver already holds an overlapping assignment.
        $conflict = $this->findBookingConflict($requestId, $vehicleId, $driverId, $request);
        if ($conflict) {
            return redirect()->back()->with('error', $conflict);
        }

        // Check if vehicle has PMS lock
        $pmsRecord = $this->pmsModel->where('vehicle_id', $vehicleId)->first();
        if ($pmsRecord && (int)$pmsRecord['is_locked'] === 1) {
            return redirect()->back()->with('error', "Security Lockout: Vehicle {$vehicle['plate_number']} is locked for 5,000 KM Preventive Maintenance. Please select an alternate vehicle.");
        }

        // Process Merged / Consolidated Requests
        $secondaryRequestIds = $this->request->getPost('secondary_requests') ?: [];
        $validSecondaryRequests = [];
        $totalPassengers = (int)$request['passenger_count'];
        $passengerManifest = trim($request['passenger_names']);
        $allRequestNumbers = [$request['request_number']];

        if (!empty($secondaryRequestIds) && is_array($secondaryRequestIds)) {
            foreach ($secondaryRequestIds as $secId) {
                $secId = (int)$secId;
                if ($secId === (int)$requestId) {
                    continue;
                }
                $secReq = $this->requestModel->getRequestByIdWithDetails($secId);
                if ($secReq && $secReq['status'] === 'approved' && empty($secReq['ticket_id'])) {
                    $validSecondaryRequests[] = $secReq;
                    $totalPassengers += (int)$secReq['passenger_count'];
                    $passengerManifest .= "\n[VRS {$secReq['request_number']} - " . ($secReq['office_name'] ?? 'Division') . "]: " . $secReq['passenger_names'];
                    $allRequestNumbers[] = $secReq['request_number'];
                }
            }
        }

        // Vehicle Capacity Verification
        $vehicleCapacity = $this->dispatchEngine->getVehicleCapacity($vehicle);
        if ($totalPassengers > $vehicleCapacity) {
            return redirect()->back()->with(
                'error',
                "Passenger Capacity Exceeded: Combined passenger count ({$totalPassengers} passengers) exceeds vehicle {$vehicle['plate_number']} ({$vehicle['make']} {$vehicle['model']}) certified seating capacity of {$vehicleCapacity} seats. Adjust merged requests or choose a vehicle with higher seating capacity."
            );
        }

        // 1. Generate Sequential Serial Number for e-DTT (ADMIN-F-001 rev1)
        $serialNo = $this->ticketModel->generateSerialNo();

        // 2. Generate Cryptographically Signed HMAC-SHA256 QR Token
        $qrToken = $this->qrService->generateToken(
            $serialNo,
            $vehicle['plate_number'],
            $driver['driver_code'],
            $request['departure_time']
        );

        // 3. Create Driver's Trip Ticket (ADMIN-F-001 rev1)
        $currentFuelLiters = ((float)($vehicle['current_fuel_level'] ?? 80) / 100.0) * (float)($vehicle['fuel_capacity_liters'] ?? 70);

        $mergedIdsJson = !empty($validSecondaryRequests) ? json_encode(array_column($validSecondaryRequests, 'id')) : null;
        $ticketPurpose = !empty($validSecondaryRequests)
            ? ($request['purpose'] . " [CONSOLIDATED TRIP: " . implode(', ', $allRequestNumbers) . "]")
            : $request['purpose'];
        $ticketNotes = !empty($validSecondaryRequests)
            ? ("Consolidated Carpool Dispatch for: " . implode(', ', $allRequestNumbers))
            : null;

        $ticketData = [
            'ticket_serial_no'          => $serialNo,
            'request_id'                => $requestId,
            'vehicle_id'                => $vehicleId,
            'driver_id'                 => $driverId,
            'qr_crypt_token'            => $qrToken,
            'status'                    => 'issued',
            'authorized_departure'      => $request['departure_time'],
            'authorized_return'         => $request['return_time'],
            'authorized_passengers'     => $passengerManifest,
            'authorized_destination'    => $request['destination'],
            'authorized_purpose'        => $ticketPurpose,
            'admin_approver_name'       => $request['admin_approver_name'] ?? 'Atty. Julius S. De Peralta',
            'start_odometer'            => (float)$vehicle['odometer_km'],
            'fuel_balance_start_liters' => round($currentFuelLiters, 2),
            'fuel_issued_stock_liters'  => 0.00,
            'fuel_purchased_liters'     => 0.00,
            'fuel_used_liters'          => 0.00,
            'fuel_balance_end_liters'   => round($currentFuelLiters, 2),
            'merged_request_ids'        => $mergedIdsJson,
            'notes'                     => $ticketNotes,
        ];

        $ticketId = $this->ticketModel->insert($ticketData);

        // 4. Update Primary Trip Request Status to Dispatched
        $this->requestModel->update($requestId, [
            'status'                 => 'dispatched',
            'ticket_id'              => $ticketId,
            'is_merged'              => !empty($validSecondaryRequests) ? 1 : 0,
            'merged_with_request_id' => null,
        ]);

        // 5. Update Merged Secondary Requests to Dispatched
        foreach ($validSecondaryRequests as $secReq) {
            $this->requestModel->update($secReq['id'], [
                'status'                 => 'dispatched',
                'ticket_id'              => $ticketId,
                'is_merged'              => 1,
                'merged_with_request_id' => $requestId,
            ]);

            // Notify secondary requestor
            if (!empty($secReq['requestor_id'])) {
                $this->notificationModel->insert([
                    'user_id'    => $secReq['requestor_id'],
                    'title'      => "Trip Dispatched (Consolidated): {$secReq['request_number']}",
                    'message'    => "Your trip has been consolidated and dispatched under e-DTT {$serialNo} with vehicle {$vehicle['plate_number']} and driver {$driver['first_name']} {$driver['last_name']}.",
                    'type'       => 'success',
                    'link'       => "/requests/{$secReq['id']}",
                    'is_read'    => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            AuditLogger::log(
                AuditLogger::VRS_DISPATCHED,
                "VRS {$secReq['request_number']} merged and dispatched under e-DTT {$serialNo} (Primary: {$request['request_number']}) with vehicle {$vehicle['plate_number']} + driver {$driver['first_name']} {$driver['last_name']}.",
                'trip_request',
                (int)$secReq['id'],
                ['ticket_serial_no' => $serialNo, 'primary_request_id' => $requestId]
            );
        }

        // 6. Update Driver Status to 'on_trip'
        $this->driverModel->update($driverId, [
            'status' => 'on_trip',
        ]);

        // 7. Notify Driver
        if (!empty($driver['user_id'])) {
            $this->notificationModel->insert([
                'user_id'    => $driver['user_id'],
                'title'      => "Official Dispatch: {$serialNo}",
                'message'    => "You have been dispatched with vehicle {$vehicle['plate_number']} to {$request['destination']}. Present your e-DTT at Gate security.",
                'type'       => 'info',
                'link'       => '/driver/trips',
                'is_read'    => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // 8. Notify Primary Requestor
        $this->notificationModel->insert([
            'user_id'    => $request['requestor_id'],
            'title'      => "Trip Dispatched: {$request['request_number']}",
            'message'    => "Vehicle {$vehicle['make']} {$vehicle['model']} ({$vehicle['plate_number']}) with Driver {$driver['first_name']} {$driver['last_name']} has been assigned for your trip.",
            'type'       => 'success',
            'link'       => "/requests/{$requestId}",
            'is_read'    => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Audit primary dispatch
        AuditLogger::log(
            AuditLogger::VRS_DISPATCHED,
            "VRS {$request['request_number']} dispatched: vehicle {$vehicle['plate_number']} + driver {$driver['first_name']} {$driver['last_name']} assigned; ticket {$serialNo} issued." . (!empty($validSecondaryRequests) ? " (Consolidated with: " . implode(', ', array_column($validSecondaryRequests, 'request_number')) . ")" : ""),
            'trip_ticket',
            (int) $ticketId,
            ['vehicle' => $vehicle['plate_number'], 'driver' => $driver['driver_code'], 'merged_count' => count($validSecondaryRequests)]
        );

        // 9. AUTOMATIC DENIAL OF CONFLICTING REQUESTS:
        // Automatically deny other pending/approved requests requesting this driver for an overlapping window
        $excludeIds = array_merge([(int)$requestId], array_map('intval', array_column($validSecondaryRequests, 'id')));
        $conflicts = $this->dispatchEngine->findConflictingDriverRequests(
            $driverId,
            $request['departure_time'],
            $request['return_time'],
            $excludeIds
        );

        $autoDeniedCount = 0;
        foreach ($conflicts as $conf) {
            $denyReason = "Auto-denied by Dispatch System: Requested driver {$driver['first_name']} {$driver['last_name']} was officially assigned to dispatched trip ticket {$serialNo} ({$request['departure_time']} - {$request['return_time']}). Please submit a new request with an alternate driver or schedule.";

            $this->requestModel->update($conf['id'], [
                'status'             => 'rejected',
                'dispatcher_action'  => 'rejected',
                'dispatcher_remarks' => $denyReason,
            ]);

            if (!empty($conf['requestor_id'])) {
                $this->notificationModel->insert([
                    'user_id'    => $conf['requestor_id'],
                    'title'      => "Request Auto-Denied: {$conf['request_number']}",
                    'message'    => $denyReason,
                    'type'       => 'danger',
                    'link'       => "/requests/{$conf['id']}",
                    'is_read'    => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            AuditLogger::log(
                AuditLogger::VRS_REJECTED,
                "VRS {$conf['request_number']} auto-denied due to driver assignment collision: Driver {$driver['first_name']} {$driver['last_name']} assigned to {$serialNo}.",
                'trip_request',
                (int)$conf['id'],
                ['conflicting_ticket' => $serialNo, 'driver_id' => $driverId]
            );

            $autoDeniedCount++;
        }

        $flashMsg = "Dispatched successfully! Driver's Trip Ticket {$serialNo} generated with cryptographic QR security token.";
        if (!empty($validSecondaryRequests)) {
            $flashMsg .= " Consolidated " . count($validSecondaryRequests) . " additional passenger request(s).";
        }
        if ($autoDeniedCount > 0) {
            $flashMsg .= " Note: {$autoDeniedCount} overlapping request(s) requesting Driver {$driver['first_name']} {$driver['last_name']} were automatically denied.";
        }

        return redirect()->to("/tickets/{$ticketId}")->with('success', $flashMsg);
    }

    // ------------------------------------------------------------------
    // FR-2.1 / FR-2.2 — Fleet segregation enforcement & double-booking guard
    // ------------------------------------------------------------------

    /**
     * Returns an error message when the vehicle or driver already holds a
     * non-cancelled booking overlapping the requested travel window.
     */
    protected function findBookingConflict(int $requestId, int $vehicleId, int $driverId, array $request): ?string
    {
        $dep = $request['departure_time'] ?? null;
        $ret = $request['return_time'] ?? null;
        if (!$dep) {
            return null;
        }

        $ret = $ret ?: date('Y-m-d H:i:s', strtotime($dep) + 4 * 3600);

        $overlapping = $this->ticketModel
            ->select('trip_tickets.ticket_serial_no, trip_tickets.status, trip_tickets.vehicle_id,
                      trip_tickets.driver_id, vehicles.plate_number,
                      trip_tickets.authorized_departure, trip_tickets.authorized_return,
                      CONCAT(drivers.first_name, \' \', drivers.last_name) AS driver_name')
            ->join('vehicles', 'vehicles.id = trip_tickets.vehicle_id', 'left')
            ->join('drivers', 'drivers.id = trip_tickets.driver_id', 'left')
            ->whereNotIn('trip_tickets.status', ['cancelled', 'completed', 'returned'])
            ->where('trip_tickets.request_id !=', $requestId)
            ->where('trip_tickets.authorized_departure <=', $ret)
            ->where('trip_tickets.authorized_return >=', $dep)
            ->groupStart()
                ->where('trip_tickets.vehicle_id', $vehicleId)
                ->orWhere('trip_tickets.driver_id', $driverId)
            ->groupEnd()
            ->findAll();

        if (empty($overlapping)) {
            return null;
        }

        foreach ($overlapping as $c) {
            if ((int) $c['vehicle_id'] === $vehicleId) {
                $who = "vehicle {$c['plate_number']}";
            } else {
                $who = 'driver ' . ($c['driver_name'] ?: '#' . $c['driver_id']);
            }

            return "Double-booking blocked (FR-2.2): {$who} already holds {$c['ticket_serial_no']} "
                . "({$c['authorized_departure']} → {$c['authorized_return']}). "
                . 'Check the Dispatch Calendar for open slots.';
        }

        return null;
    }

    /**
     * Single-click denial for a request with an unavailable/conflicted driver.
     */
    public function denyDriverConflict(int|string $requestId)
    {
        $request = $this->requestModel->getRequestByIdWithDetails($requestId);
        if (!$request || in_array($request['status'], ['dispatched', 'completed', 'rejected', 'cancelled'], true)) {
            return redirect()->to('/dispatch')->with('error', 'Request not found or cannot be denied.');
        }

        if (empty($request['requested_driver_id'])) {
            return redirect()->to('/dispatch')->with('error', 'This request does not have a designated driver assigned.');
        }

        $driver = $this->driverModel->find($request['requested_driver_id']);
        $driverName = $driver ? "{$driver['first_name']} {$driver['last_name']}" : "Driver #{$request['requested_driver_id']}";

        $driverCheck = $this->dispatchEngine->checkDriverAvailability(
            (int)$request['requested_driver_id'],
            $request['departure_time'],
            $request['return_time'],
            (int)$requestId
        );

        $reason = $driverCheck['reason'] ?? "Driver {$driverName} is unavailable for this travel schedule.";
        $denyNote = "Denied due to driver unavailability: {$reason} Please resubmit with an alternate driver or schedule.";

        $this->requestModel->update($requestId, [
            'status'             => 'rejected',
            'dispatcher_action'  => 'rejected',
            'dispatcher_remarks' => $denyNote,
        ]);

        if (!empty($request['requestor_id'])) {
            $this->notificationModel->insert([
                'user_id'    => $request['requestor_id'],
                'title'      => "Request Denied (Driver Unavailable): {$request['request_number']}",
                'message'    => $denyNote,
                'type'       => 'danger',
                'link'       => "/requests/{$requestId}",
                'is_read'    => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        AuditLogger::log(
            AuditLogger::VRS_REJECTED,
            "VRS {$request['request_number']} denied due to driver unavailability: {$reason}",
            'trip_request',
            (int)$requestId,
            ['driver_id' => $request['requested_driver_id'], 'reason' => $reason]
        );

        return redirect()->to('/dispatch')->with('success', "Request {$request['request_number']} has been denied because {$driverName} is unavailable.");
    }

    /**
     * Batch auto-denial for all requests across pipeline whose requested driver is unavailable.
     */
    public function autoDenyConflicts()
    {
        $requests = $this->requestModel->whereIn('status', ['pending_dispatcher', 'pending_admin', 'approved'])
            ->where('requested_driver_id IS NOT NULL')
            ->where('ticket_id IS NULL')
            ->findAll();

        $deniedCount = 0;
        foreach ($requests as $r) {
            $check = $this->dispatchEngine->checkDriverAvailability(
                (int)$r['requested_driver_id'],
                $r['departure_time'],
                $r['return_time'],
                (int)$r['id']
            );

            if (!$check['available']) {


                $denyNote = "Auto-denied by Dispatch Console: {$check['reason']} Please resubmit your request with an available driver.";

                $this->requestModel->update($r['id'], [
                    'status'             => 'rejected',
                    'dispatcher_action'  => 'rejected',
                    'dispatcher_remarks' => $denyNote,
                ]);

                if (!empty($r['requestor_id'])) {
                    $this->notificationModel->insert([
                        'user_id'    => $r['requestor_id'],
                        'title'      => "Request Auto-Denied: {$r['request_number']}",
                        'message'    => $denyNote,
                        'type'       => 'danger',
                        'link'       => "/requests/{$r['id']}",
                        'is_read'    => 0,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }

                AuditLogger::log(
                    AuditLogger::VRS_REJECTED,
                    "VRS {$r['request_number']} auto-denied by Dispatch Console: {$check['reason']}",
                    'trip_request',
                    (int)$r['id'],
                    ['driver_id' => $r['requested_driver_id'], 'reason' => $check['reason']]
                );

                $deniedCount++;
            }
        }

        if ($deniedCount > 0) {
            return redirect()->to('/dispatch')->with('success', "Batch auto-denial complete: Successfully denied {$deniedCount} request(s) with driver availability conflicts.");
        }

        return redirect()->to('/dispatch')->with('info', "Batch scan complete: No driver availability conflicts found in current queues.");
    }

    /**
     * Batch merge entry point from /dispatch multi-checkbox selection.
     */
    public function mergeBatch()
    {
        $selected = $this->request->getPost('selected_requests');
        if (empty($selected) || !is_array($selected) || count($selected) < 2) {
            return redirect()->to('/dispatch')->with('error', 'Please select at least two approved requests to merge and dispatch.');
        }

        $selected = array_values(array_unique(array_filter(array_map('intval', $selected))));
        if (count($selected) < 2) {
            return redirect()->to('/dispatch')->with('error', 'Please select at least two valid requests to merge.');
        }

        $primaryId = $selected[0];
        $secondaryIds = array_slice($selected, 1);

        return redirect()->to("/dispatch/assign/{$primaryId}?merge=" . implode(',', $secondaryIds));
    }

    // ------------------------------------------------------------------
    // FR-2.2 — Interactive Allocation Calendar (shared dispatcher view)
    // ------------------------------------------------------------------

    /**
     * Month-view calendar of vehicle + driver allocations.
     */
    public function calendar()
    {
        $month = $this->request->getGet('month') ?: date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $allocations = $this->calendarData($month);

        // Group unique requests for summary KPIs and awaiting allocation queue
        $uniqueRequests = [];
        $awaitingAllocation = [];
        $kpi = [
            'total'             => 0,
            'pending_review'    => 0,
            'ready_to_allocate' => 0,
            'dispatched_active' => 0,
            'completed'         => 0,
        ];

        foreach ($allocations as $a) {
            $key = $a['request_id'] ? 'req-' . $a['request_id'] : 'ticket-' . $a['ticket_id'];
            if (!isset($uniqueRequests[$key])) {
                $uniqueRequests[$key] = $a;
                $kpi['total']++;

                if (in_array($a['status'], ['pending_dispatcher', 'pending_admin'], true)) {
                    $kpi['pending_review']++;
                } elseif ($a['allocation_status'] === 'ready_to_allocate') {
                    $kpi['ready_to_allocate']++;
                    $awaitingAllocation[] = $a;
                } elseif ($a['allocation_status'] === 'allocated' && in_array($a['stage_num'], [4, 5], true)) {
                    $kpi['dispatched_active']++;
                } elseif ($a['stage_num'] === 6) {
                    $kpi['completed']++;
                }
            }
        }

        // Previous and Next month navigation strings
        $ts = strtotime($month . '-01');
        $prevMonth = date('Y-m', strtotime('-1 month', $ts));
        $nextMonth = date('Y-m', strtotime('+1 month', $ts));

        $data = [
            'title'              => 'Interactive Dispatch Calendar & Allocation Board',
            'month'              => $month,
            'prevMonth'          => $prevMonth,
            'nextMonth'          => $nextMonth,
            'allocations'        => $allocations,
            'awaitingAllocation' => $awaitingAllocation,
            'kpi'                => $kpi,
            'vehicles'           => $this->vehicleModel->orderBy('plate_number', 'ASC')->findAll(),
            'drivers'            => $this->driverModel->findAll(),
            'userRole'           => session()->get('user_role'),
        ];

        return view('dispatch/calendar', $data);
    }

    /**
     * Resolves the authoritative 6-stage lifecycle process metadata for a request.
     */
    protected function resolveRequestProcessStage(array $r): array
    {
        $status       = $r['status'] ?? 'pending_dispatcher';
        $ticketStatus = $r['ticket_status'] ?? null;
        $ticketId     = $r['ticket_id'] ?? null;
        $requestId    = $r['id'] ?? null;

        // Stage 1: VRS Filed (Awaiting Tier 1 Motorpool Dispatcher Review)
        if ($status === 'pending_dispatcher') {
            return [
                'stage_num'         => 1,
                'stage_total'       => 6,
                'process_code'      => 'vrs_pending_tier1',
                'process_title'     => 'Stage 1: VRS Filed — Tier 1 Motorpool Review',
                'stage_name'        => 'Tier 1 Review',
                'badge_class'       => 'bg-warning text-dark',
                'badge_label'       => 'Stage 1: Tier 1 Review',
                'allocation_status' => 'unassigned',
                'action_url'        => base_url('approvals'),
                'action_label'      => 'Review Tier 1 Endorsement',
                'status'            => 'pending_dispatcher',
                'ticket_status'     => null,
            ];
        }

        // Stage 2: Tier 1 Endorsed (Awaiting Tier 2 Admin Division Head Clearance)
        if ($status === 'pending_admin') {
            return [
                'stage_num'         => 2,
                'stage_total'       => 6,
                'process_code'      => 'vrs_pending_tier2',
                'process_title'     => 'Stage 2: Tier 1 Endorsed — Tier 2 Admin Head Clearance',
                'stage_name'        => 'Tier 2 Clearance',
                'badge_class'       => 'bg-info text-dark',
                'badge_label'       => 'Stage 2: Tier 2 Clearance',
                'allocation_status' => 'unassigned',
                'action_url'        => base_url('approvals'),
                'action_label'      => 'Authorize Tier 2 Clearance',
                'status'            => 'pending_admin',
                'ticket_status'     => null,
            ];
        }

        // Stage 3: Officially Authorized / Approved (Awaiting Motorpool Asset Allocation)
        if ($status === 'approved' && empty($ticketId)) {
            return [
                'stage_num'         => 3,
                'stage_total'       => 6,
                'process_code'      => 'approved_needs_allocation',
                'process_title'     => 'Stage 3: Cleared — Ready for Motorpool Asset Allocation',
                'stage_name'        => 'Ready to Allocate',
                'badge_class'       => 'bg-primary text-white',
                'badge_label'       => 'Stage 3: Allocate Asset',
                'allocation_status' => 'ready_to_allocate',
                'action_url'        => base_url('dispatch/assign/' . $requestId),
                'action_label'      => 'Allocate Vehicle & Driver',
                'status'            => 'approved',
                'ticket_status'     => null,
            ];
        }

        // Stage 4: Dispatched / e-DTT Issued (Pre-Trip Safety & Gate Departure Pending)
        if (!empty($ticketId) && ($ticketStatus === 'issued' || ($status === 'dispatched' && empty($ticketStatus)))) {
            return [
                'stage_num'         => 4,
                'stage_total'       => 6,
                'process_code'      => 'dispatched_issued',
                'process_title'     => 'Stage 4: Assets Allocated & e-DTT Issued — Pre-Trip & Gate',
                'stage_name'        => 'e-DTT Issued',
                'badge_class'       => 'bg-secondary text-white',
                'badge_label'       => 'Stage 4: e-DTT Issued',
                'allocation_status' => 'allocated',
                'action_url'        => base_url('tickets/' . $ticketId),
                'action_label'      => 'View e-DTT / Safety Check',
                'status'            => 'dispatched',
                'ticket_status'     => $ticketStatus ?: 'issued',
            ];
        }

        // Stage 5: En Route / On Official Travel
        if (!empty($ticketId) && in_array($ticketStatus, ['departed', 'arrived_dest', 'departed_dest'], true)) {
            return [
                'stage_num'         => 5,
                'stage_total'       => 6,
                'process_code'      => 'en_route',
                'process_title'     => 'Stage 5: En Route — Official Travel in Progress',
                'stage_name'        => 'En Route',
                'badge_class'       => 'bg-success text-white',
                'badge_label'       => 'Stage 5: En Route (' . $ticketStatus . ')',
                'allocation_status' => 'allocated',
                'action_url'        => base_url('tickets/' . $ticketId),
                'action_label'      => 'Live Telematics & Trip Log',
                'status'            => 'dispatched',
                'ticket_status'     => $ticketStatus,
            ];
        }

        // Stage 6: Reconciled & Completed
        if ($status === 'completed' || (!empty($ticketId) && in_array($ticketStatus, ['returned', 'completed'], true))) {
            return [
                'stage_num'         => 6,
                'stage_total'       => 6,
                'process_code'      => 'completed',
                'process_title'     => 'Stage 6: Reconciled & Completed',
                'stage_name'        => 'Completed',
                'badge_class'       => 'bg-dark text-white',
                'badge_label'       => 'Stage 6: Completed',
                'allocation_status' => 'completed',
                'action_url'        => $ticketId ? base_url('tickets/' . $ticketId) : base_url('requests/' . $requestId),
                'action_label'      => 'View COA Audit Record',
                'status'            => 'completed',
                'ticket_status'     => $ticketStatus ?: 'completed',
            ];
        }

        // Special: Rejected
        if ($status === 'rejected') {
            return [
                'stage_num'         => 0,
                'stage_total'       => 6,
                'process_code'      => 'rejected',
                'process_title'     => 'Disapproved / Rejected',
                'stage_name'        => 'Rejected',
                'badge_class'       => 'bg-danger text-white',
                'badge_label'       => 'Disapproved',
                'allocation_status' => 'rejected',
                'action_url'        => base_url('requests/' . $requestId),
                'action_label'      => 'View Rejection Details',
                'status'            => 'rejected',
                'ticket_status'     => null,
            ];
        }

        // Special: SLA Expired
        if ($status === 'expired') {
            return [
                'stage_num'         => 0,
                'stage_total'       => 6,
                'process_code'      => 'expired',
                'process_title'     => 'SLA Expired (Unactioned)',
                'stage_name'        => 'Expired',
                'badge_class'       => 'bg-danger text-white',
                'badge_label'       => 'Expired',
                'allocation_status' => 'expired',
                'action_url'        => base_url('requests/' . $requestId),
                'action_label'      => 'View Expired Request',
                'status'            => 'expired',
                'ticket_status'     => null,
            ];
        }

        // Fallback
        return [
            'stage_num'         => 1,
            'stage_total'       => 6,
            'process_code'      => 'unknown',
            'process_title'     => 'VRS Request: ' . ucfirst((string)$status),
            'stage_name'        => ucfirst((string)$status),
            'badge_class'       => 'bg-secondary text-white',
            'badge_label'       => ucfirst((string)$status),
            'allocation_status' => 'unassigned',
            'action_url'        => base_url('requests/' . $requestId),
            'action_label'      => 'View Request',
            'status'            => (string)$status,
            'ticket_status'     => $ticketStatus,
        ];
    }

    /**
     * JSON feed powering the FR-2.2 calendar (used by the view and APIs).
     * Accurately inputs and tracks the main process lifecycle of every request across
     * all active days it spans (departure, transit, return).
     */
    public function calendarData(?string $month = null): array
    {
        $month = $month ?: ($this->request->getGet('month') ?: date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $start = $month . '-01 00:00:00';
        $end   = date('Y-m-t 23:59:59', strtotime($start));
        $monthStartDay = $month . '-01';
        $monthEndDay   = date('Y-m-t', strtotime($start));

        $db = \Config\Database::connect();

        // 1. Fetch all trip requests intersecting this month (any status except cancelled)
        $requests = $db->table('trip_requests tr')
            ->select('tr.*,
                      offices.office_name, offices.office_code,
                      departments.department_name, departments.department_code,
                      req_u.name AS requestor_user_name, req_u.designation AS requestor_designation,
                      disp_u.name AS dispatcher_approver_name,
                      adm_u.name AS admin_approver_name,
                      req_drv.first_name AS req_drv_first, req_drv.last_name AS req_drv_last,
                      tt.id AS ticket_id, tt.ticket_serial_no, tt.status AS ticket_status,
                      tt.start_odometer, tt.dest_odometer, tt.return_odometer,
                      vehicles.plate_number, vehicles.fleet_category, vehicles.assigned_official,
                      vehicles.make AS vehicle_make, vehicles.model AS vehicle_model,
                      CONCAT(drivers.first_name, \' \', drivers.last_name) AS driver_name,
                      drivers.driver_code, drivers.phone AS driver_phone')
            ->join('offices', 'offices.id = tr.office_id', 'left')
            ->join('departments', 'departments.id = tr.department_id', 'left')
            ->join('users req_u', 'req_u.id = tr.requestor_id', 'left')
            ->join('users disp_u', 'disp_u.id = tr.dispatcher_approver_id', 'left')
            ->join('users adm_u', 'adm_u.id = tr.admin_approver_id', 'left')
            ->join('drivers req_drv', 'req_drv.id = tr.requested_driver_id', 'left')
            ->join('trip_tickets tt', 'tt.request_id = tr.id', 'left')
            ->join('vehicles', 'vehicles.id = tt.vehicle_id', 'left')
            ->join('drivers', 'drivers.id = tt.driver_id', 'left')
            ->where('tr.departure_time <=', $end)
            ->where('(tr.return_time >= "' . $start . '" OR (tr.return_time IS NULL AND tr.departure_time >= "' . $start . '"))')
            ->whereNotIn('tr.status', ['cancelled'])
            ->orderBy('tr.departure_time', 'ASC')
            ->get()
            ->getResultArray();

        // 2. Standalone tickets fallback (any tickets not tied to a trip_requests row)
        $linkedTicketIds = array_filter(array_column($requests, 'ticket_id'));
        $standaloneBuilder = $db->table('trip_tickets tt')
            ->select('tt.*,
                      vehicles.plate_number, vehicles.fleet_category, vehicles.assigned_official,
                      vehicles.make AS vehicle_make, vehicles.model AS vehicle_model,
                      CONCAT(drivers.first_name, \' \', drivers.last_name) AS driver_name,
                      drivers.driver_code, drivers.phone AS driver_phone')
            ->join('vehicles', 'vehicles.id = tt.vehicle_id', 'left')
            ->join('drivers', 'drivers.id = tt.driver_id', 'left')
            ->where('tt.authorized_departure <=', $end)
            ->where('tt.authorized_return >=', $start)
            ->whereNotIn('tt.status', ['cancelled']);

        if (!empty($linkedTicketIds)) {
            $standaloneBuilder->whereNotIn('tt.id', $linkedTicketIds);
        }
        $standaloneTickets = $standaloneBuilder->get()->getResultArray();

        $allocations = [];

        // 3. Process requests and accurately plot them across their active date span
        foreach ($requests as $r) {
            $depStr = $r['departure_time'];
            $retStr = $r['return_time'] ?: date('Y-m-d H:i:s', strtotime($depStr) + 4 * 3600);

            $depDay = substr($depStr, 0, 10);
            $retDay = substr($retStr, 0, 10);

            // Calculate lifecycle process details
            $processInfo = $this->resolveRequestProcessStage($r);

            // Determine date iteration bounds clipped to the requested calendar month
            $iterStart = max($depDay, $monthStartDay);
            $iterEnd   = min($retDay, $monthEndDay);

            $currentTs = strtotime($iterStart);
            $endTs     = strtotime($iterEnd);

            while ($currentTs <= $endTs) {
                $currentDay = date('Y-m-d', $currentTs);

                if ($depDay === $retDay) {
                    $dayPhase = 'single_day';
                    $phaseLabel = 'Same Day';
                    $timeDisplay = date('H:i', strtotime($depStr)) . ' - ' . date('H:i', strtotime($retStr));
                } elseif ($currentDay === $depDay) {
                    $dayPhase = 'departure';
                    $phaseLabel = 'Departure';
                    $timeDisplay = 'DEP ' . date('H:i', strtotime($depStr));
                } elseif ($currentDay === $retDay) {
                    $dayPhase = 'return';
                    $phaseLabel = 'Return';
                    $timeDisplay = 'RET ' . date('H:i', strtotime($retStr));
                } else {
                    $dayPhase = 'transit';
                    $phaseLabel = 'In Transit';
                    $timeDisplay = 'IN TRANSIT';
                }

                $allocations[] = array_merge($processInfo, [
                    'id'                    => 'req-' . $r['id'] . '-' . $currentDay,
                    'request_id'            => (int) $r['id'],
                    'request_number'        => $r['request_number'],
                    'ticket_id'             => $r['ticket_id'] ? (int) $r['ticket_id'] : null,
                    'ticket_serial_no'      => $r['ticket_serial_no'],
                    'ticket'                => $r['ticket_serial_no'] ?: $r['request_number'], // legacy key
                    'date'                  => $currentDay,
                    'day_phase'             => $dayPhase,
                    'day_phase_label'       => $phaseLabel,
                    'time_display'          => $timeDisplay,
                    'destination'           => $r['destination'],
                    'purpose'               => $r['purpose'],
                    'requestor'             => $r['requestor_name'] ?: ($r['requestor_user_name'] ?? 'Official'),
                    'requestor_name'        => $r['requestor_name'] ?: ($r['requestor_user_name'] ?? 'Official'),
                    'requestor_designation' => $r['requestor_designation'] ?? '',
                    'office'                => $r['office_code'] ?: ($r['office_name'] ?? 'PIA'),
                    'office_name'           => $r['office_name'] ?? 'Philippine Information Agency',
                    'passenger_names'       => $r['passenger_names'],
                    'passenger_count'       => (int) ($r['passenger_count'] ?? 1),
                    'departure'             => $depStr,
                    'return'                => $retStr,
                    'departure_time'        => $depStr,
                    'return_time'           => $retStr,
                    'plate'                 => $r['plate_number'] ?: ($r['requested_vehicle_type'] ? 'Req: ' . strtoupper($r['requested_vehicle_type']) : 'Unassigned'),
                    'vehicle_plate'         => $r['plate_number'] ?? null,
                    'vehicle_model'         => trim(($r['vehicle_make'] ?? '') . ' ' . ($r['vehicle_model'] ?? '')),
                    'fleet_category'        => $r['fleet_category'] ?? 'pool',
                    'assigned_official'     => $r['assigned_official'] ?? null,
                    'driver'                => $r['driver_name'] ?: ($r['req_drv_first'] ? 'Req: ' . $r['req_drv_first'] . ' ' . $r['req_drv_last'] : 'Unassigned'),
                    'driver_name'           => $r['driver_name'] ?? null,
                    'driver_phone'          => $r['driver_phone'] ?? null,
                    'driver_code'           => $r['driver_code'] ?? null,
                    'is_rush'               => (bool) ($r['is_rush_request'] ?? 0),
                    'is_emergency'          => (bool) ($r['is_emergency_override'] ?? 0),
                    'dispatcher_name'       => $r['dispatcher_approver_name'] ?? null,
                    'dispatcher_action_at'  => $r['dispatcher_action_at'] ?? null,
                    'dispatcher_remarks'    => $r['dispatcher_remarks'] ?? null,
                    'admin_name'            => $r['admin_approver_name'] ?? null,
                    'admin_action_at'       => $r['admin_action_at'] ?? null,
                    'admin_remarks'         => $r['admin_remarks'] ?? null,
                    'sla_deadline'          => $r['sla_deadline'] ?? null,
                    'url'                   => $processInfo['action_url'], // legacy key
                ]);

                $currentTs = strtotime('+1 day', $currentTs);
            }
        }

        // 4. Append standalone tickets if any
        foreach ($standaloneTickets as $st) {
            $depStr = $st['authorized_departure'];
            $retStr = $st['authorized_return'];
            $depDay = substr($depStr, 0, 10);
            $retDay = substr($retStr, 0, 10);

            $stageNum = 4;
            $processCode = 'dispatched_issued';
            $processTitle = 'Stage 4: e-DTT Issued';
            $badgeClass = 'bg-secondary text-white';
            $badgeLabel = 'Issued';
            if (in_array($st['status'], ['departed', 'arrived_dest', 'departed_dest'], true)) {
                $stageNum = 5;
                $processCode = 'en_route';
                $processTitle = 'Stage 5: En Route';
                $badgeClass = 'bg-success text-white';
                $badgeLabel = 'En Route';
            } elseif (in_array($st['status'], ['returned', 'completed'], true)) {
                $stageNum = 6;
                $processCode = 'completed';
                $processTitle = 'Stage 6: Completed';
                $badgeClass = 'bg-dark text-white';
                $badgeLabel = 'Completed';
            }

            $iterStart = max($depDay, $monthStartDay);
            $iterEnd   = min($retDay, $monthEndDay);
            $currentTs = strtotime($iterStart);
            $endTs     = strtotime($iterEnd);

            while ($currentTs <= $endTs) {
                $currentDay = date('Y-m-d', $currentTs);
                $allocations[] = [
                    'id'                    => 'ticket-' . $st['id'] . '-' . $currentDay,
                    'request_id'            => null,
                    'request_number'        => 'TICKET-' . $st['ticket_serial_no'],
                    'ticket_id'             => (int) $st['id'],
                    'ticket_serial_no'      => $st['ticket_serial_no'],
                    'ticket'                => $st['ticket_serial_no'],
                    'date'                  => $currentDay,
                    'day_phase'             => $depDay === $retDay ? 'single_day' : ($currentDay === $depDay ? 'departure' : ($currentDay === $retDay ? 'return' : 'transit')),
                    'day_phase_label'       => $depDay === $retDay ? 'Same Day' : ($currentDay === $depDay ? 'Departure' : ($currentDay === $retDay ? 'Return' : 'In Transit')),
                    'time_display'          => $depDay === $retDay ? date('H:i', strtotime($depStr)) . ' - ' . date('H:i', strtotime($retStr)) : ($currentDay === $depDay ? 'DEP ' . date('H:i', strtotime($depStr)) : ($currentDay === $retDay ? 'RET ' . date('H:i', strtotime($retStr)) : 'IN TRANSIT')),
                    'status'                => $st['status'],
                    'ticket_status'         => $st['status'],
                    'stage_num'             => $stageNum,
                    'stage_total'           => 6,
                    'process_code'          => $processCode,
                    'process_title'         => $processTitle,
                    'badge_class'           => $badgeClass,
                    'badge_label'           => $badgeLabel,
                    'allocation_status'     => 'allocated',
                    'plate'                 => $st['plate_number'] ?? 'Unassigned',
                    'vehicle_plate'         => $st['plate_number'] ?? null,
                    'vehicle_model'         => trim(($st['vehicle_make'] ?? '') . ' ' . ($st['vehicle_model'] ?? '')),
                    'fleet_category'        => $st['fleet_category'] ?? 'pool',
                    'assigned_official'     => $st['assigned_official'] ?? null,
                    'driver'                => $st['driver_name'] ?? 'Unassigned',
                    'driver_name'           => $st['driver_name'] ?? null,
                    'driver_phone'          => $st['driver_phone'] ?? null,
                    'driver_code'           => $st['driver_code'] ?? null,
                    'destination'           => $st['authorized_destination'] ?? 'Official Travel',
                    'purpose'               => $st['authorized_purpose'] ?? '',
                    'requestor'             => $st['authorized_passengers'] ?? 'PIA Staff',
                    'requestor_name'        => $st['authorized_passengers'] ?? 'PIA Staff',
                    'requestor_designation' => '',
                    'office'                => 'PIA',
                    'office_name'           => 'Philippine Information Agency',
                    'passenger_names'       => $st['authorized_passengers'] ?? '',
                    'passenger_count'       => 1,
                    'departure'             => $depStr,
                    'return'                => $retStr,
                    'departure_time'        => $depStr,
                    'return_time'           => $retStr,
                    'is_rush'               => false,
                    'is_emergency'          => false,
                    'dispatcher_name'       => null,
                    'dispatcher_action_at'  => null,
                    'dispatcher_remarks'    => null,
                    'admin_name'            => $st['admin_approver_name'] ?? null,
                    'admin_action_at'       => null,
                    'admin_remarks'         => null,
                    'sla_deadline'          => null,
                    'action_url'            => base_url('tickets/' . $st['id']),
                    'action_label'          => 'View e-DTT',
                    'url'                   => base_url('tickets/' . $st['id']),
                ];
                $currentTs = strtotime('+1 day', $currentTs);
            }
        }

        return $allocations;
    }

    /**
     * JSON endpoint for the calendar view.
     */
    public function calendarFeed()
    {
        $month = $this->request->getGet('month') ?: date('Y-m');
        return $this->response->setJSON([
            'status' => 'success',
            'month'  => $month,
            'data'   => $this->calendarData($month),
        ]);
    }

    /**
     * Unified Single Updatable Process for Every Dispatch Vehicle and Driver from Calendar.
     * Allows dispatchers/admins to reassign or directly allocate any trip right from the calendar modal.
     */
    public function updateAllocation()
    {
        $requestId = (int)$this->request->getPost('request_id');
        $vehicleId = (int)$this->request->getPost('vehicle_id');
        $driverId  = (int)$this->request->getPost('driver_id');
        $month     = $this->request->getPost('month') ?: date('Y-m');

        if (!$requestId || !$vehicleId || !$driverId) {
            return redirect()->to("/dispatch/calendar?month={$month}")
                ->with('error', 'Please select both an authorized vehicle and driver to update allocation.');
        }

        $request = $this->requestModel->getRequestByIdWithDetails($requestId);
        if (!$request) {
            return redirect()->to("/dispatch/calendar?month={$month}")
                ->with('error', 'Vehicle request not found.');
        }

        $vehicle = $this->vehicleModel->find($vehicleId);
        $driver  = $this->driverModel->find($driverId);

        if (!$vehicle || !$driver) {
            return redirect()->to("/dispatch/calendar?month={$month}")
                ->with('error', 'Selected vehicle or driver not found.');
        }

        // 1. Strict Driver Availability Check
        $driverCheck = $this->dispatchEngine->checkDriverAvailability(
            $driverId,
            $request['departure_time'],
            $request['return_time'],
            $requestId
        );
        if (!$driverCheck['available']) {
            return redirect()->to("/dispatch/calendar?month={$month}")
                ->with('error', "Driver Unavailable: {$driverCheck['reason']}");
        }

        // 2. PMS Lockout Check
        $pmsRecord = $this->pmsModel->where('vehicle_id', $vehicleId)->first();
        if ($pmsRecord && (int)$pmsRecord['is_locked'] === 1) {
            return redirect()->to("/dispatch/calendar?month={$month}")
                ->with('error', "Security Lockout: Vehicle {$vehicle['plate_number']} is locked for 5,000 KM PMS.");
        }

        // 3. Double-Booking Check
        $conflict = $this->findBookingConflict($requestId, $vehicleId, $driverId, $request);
        if ($conflict) {
            return redirect()->to("/dispatch/calendar?month={$month}")
                ->with('error', $conflict);
        }

        // 4. Vehicle Capacity Verification
        $capacity = $this->dispatchEngine->getVehicleCapacity($vehicle);
        $passCount = (int)$request['passenger_count'];
        if ($passCount > $capacity) {
            return redirect()->to("/dispatch/calendar?month={$month}")
                ->with('error', "Capacity Exceeded: Request requires {$passCount} seats, but {$vehicle['plate_number']} only seats {$capacity}.");
        }

        // Check if ticket already exists or needs creation
        if (!empty($request['ticket_id'])) {
            // Update existing ticket
            $ticketId = (int)$request['ticket_id'];
            $oldTicket = $this->ticketModel->find($ticketId);

            // Re-generate cryptographic token for new asset pairing
            $qrToken = $this->qrService->generateToken(
                $oldTicket['ticket_serial_no'],
                $vehicle['plate_number'],
                $driver['driver_code'],
                $request['departure_time']
            );

            $this->ticketModel->update($ticketId, [
                'vehicle_id'     => $vehicleId,
                'driver_id'      => $driverId,
                'qr_crypt_token' => $qrToken,
                'start_odometer' => (float)$vehicle['odometer_km'],
            ]);

            // If previous driver was different, free them
            if ((int)$oldTicket['driver_id'] !== $driverId) {
                $this->driverModel->update($oldTicket['driver_id'], ['status' => 'available']);
            }
            $this->driverModel->update($driverId, ['status' => 'on_trip']);

            AuditLogger::log(
                AuditLogger::ADMIN_ACTION,
                "Allocation updated on Calendar for VRS {$request['request_number']} (e-DTT {$oldTicket['ticket_serial_no']}) to vehicle {$vehicle['plate_number']} and driver {$driver['first_name']} {$driver['last_name']}.",
                'trip_tickets',
                $ticketId
            );

            return redirect()->to("/dispatch/calendar?month={$month}")
                ->with('success', "Allocation updated: VRS {$request['request_number']} assigned to vehicle {$vehicle['plate_number']} and driver {$driver['first_name']} {$driver['last_name']}.");
        } else {
            // Needs initial dispatch from approved status
            if ($request['status'] !== 'approved') {
                $this->requestModel->update($requestId, [
                    'status'             => 'approved',
                    'admin_action'       => 'approved',
                    'admin_remarks'      => 'Authorized via Calendar Allocation',
                ]);
            }

            $serialNo = $this->ticketModel->generateSerialNo();
            $qrToken = $this->qrService->generateToken(
                $serialNo,
                $vehicle['plate_number'],
                $driver['driver_code'],
                $request['departure_time']
            );

            $currentFuelLiters = ((float)($vehicle['current_fuel_level'] ?? 80) / 100.0) * (float)($vehicle['fuel_capacity_liters'] ?? 70);

            $ticketId = $this->ticketModel->insert([
                'ticket_serial_no'          => $serialNo,
                'request_id'                => $requestId,
                'vehicle_id'                => $vehicleId,
                'driver_id'                 => $driverId,
                'qr_crypt_token'            => $qrToken,
                'status'                    => 'issued',
                'authorized_departure'      => $request['departure_time'],
                'authorized_return'         => $request['return_time'],
                'authorized_passengers'     => $request['passenger_names'],
                'authorized_destination'    => $request['destination'],
                'authorized_purpose'        => $request['purpose'],
                'admin_approver_name'       => $request['admin_approver_name'] ?? 'Atty. Julius S. De Peralta',
                'start_odometer'            => (float)$vehicle['odometer_km'],
                'fuel_balance_start_liters' => round($currentFuelLiters, 2),
                'fuel_issued_stock_liters'  => 0.00,
                'fuel_purchased_liters'     => 0.00,
                'fuel_used_liters'          => 0.00,
                'fuel_balance_end_liters'   => round($currentFuelLiters, 2),
            ]);

            $this->requestModel->update($requestId, [
                'status'    => 'dispatched',
                'ticket_id' => $ticketId,
            ]);

            $this->driverModel->update($driverId, ['status' => 'on_trip']);

            AuditLogger::log(
                AuditLogger::VRS_DISPATCHED,
                "VRS {$request['request_number']} dispatched directly via Calendar Allocation under e-DTT {$serialNo} with vehicle {$vehicle['plate_number']} + driver {$driver['first_name']} {$driver['last_name']}.",
                'trip_requests',
                $requestId
            );

            return redirect()->to("/dispatch/calendar?month={$month}")
                ->with('success', "Dispatched successfully: VRS {$request['request_number']} allocated to {$vehicle['plate_number']} and {$driver['first_name']} {$driver['last_name']} (e-DTT #{$serialNo}).");
        }
    }
}

