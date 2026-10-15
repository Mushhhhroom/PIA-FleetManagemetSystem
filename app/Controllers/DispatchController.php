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
        foreach ($pendingTier1 as &$t1) {
            $t1['sla_info'] = \App\Services\SlaService::formatSlaCountdown($t1['sla_deadline']);
        }

        // 1. Approved requests waiting for dispatch
        $pendingDispatch = $this->requestModel->getRequestsWithDetails(['status' => 'approved']);

        // 2. Active dispatched trips with tickets
        $activeDispatches = $this->ticketModel->getTicketsWithDetails();

        // 3. Vehicles with PMS Status
        $vehicles = $this->pmsModel->getPmsWithVehicles();

        // 4. Drivers
        $drivers = $this->driverModel->findAll();

        $data = [
            'title'            => 'Motorpool Dispatch Command & Heuristic Allocation',
            'pendingTier1'     => $pendingTier1,
            'pendingDispatch'  => $pendingDispatch,
            'activeDispatches' => $activeDispatches,
            'vehicles'         => $vehicles,
            'drivers'          => $drivers,
        ];

        return view('dispatch/index', $data);
    }

    public function assign($requestId)
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

        $data = [
            'title'           => "Dispatch Assignment: {$request['request_number']}",
            'request'         => $request,
            'recommendations' => $recommendations,
        ];

        return view('dispatch/assign', $data);
    }

    public function storeAssignment($requestId)
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
        // Automatically populates Section A from the approved VRS!
        $currentFuelLiters = ((float)($vehicle['current_fuel_level'] ?? 80) / 100.0) * (float)($vehicle['fuel_capacity_liters'] ?? 70);

        $ticketData = [
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
        ];

        $ticketId = $this->ticketModel->insert($ticketData);

        // 4. Update Trip Request Status to Dispatched
        $this->requestModel->update($requestId, [
            'status' => 'dispatched',
        ]);

        // 5. Update Driver Status to 'on_trip'
        $this->driverModel->update($driverId, [
            'status' => 'on_trip',
        ]);

        // 6. Notify Driver
        if (!empty($driver['user_id'])) {
            $this->notificationModel->insert([
                'user_id'    => $driver['user_id'],
                'title'      => "Official Dispatch: {$serialNo}",
                'message'    => "You have been dispatched with vehicle {$vehicle['plate_number']} to {$request['destination']}. Present your e-DTT at Gate security.",
                'type'       => 'info',
                'link'       => "/tickets/{$ticketId}",
                'is_read'    => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // 7. Notify Requestor
        $this->notificationModel->insert([
            'user_id'    => $request['requestor_id'],
            'title'      => "Trip Dispatched: {$request['request_number']}",
            'message'    => "Vehicle {$vehicle['make']} {$vehicle['model']} ({$vehicle['plate_number']}) with Driver {$driver['first_name']} {$driver['last_name']} has been assigned for your trip.",
            'type'       => 'success',
            'link'       => "/requests/{$requestId}",
            'is_read'    => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // NFR-3: dispatch allocation is a critical status change
        AuditLogger::log(
            AuditLogger::VRS_DISPATCHED,
            "VRS {$request['request_number']} dispatched: vehicle {$vehicle['plate_number']} + driver {$driver['first_name']} {$driver['last_name']} assigned; ticket {$serialNo} issued.",
            'trip_ticket',
            (int) $ticketId,
            ['vehicle' => $vehicle['plate_number'], 'driver' => $driver['driver_code']]
        );

        return redirect()->to("/tickets/{$ticketId}")->with('success', "Dispatched successfully! Driver's Trip Ticket {$serialNo} generated with cryptographic QR security token.");
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
                      CONCAT(drivers.first_name, " ", drivers.last_name) AS driver_name')
            ->join('vehicles', 'vehicles.id = trip_tickets.vehicle_id', 'left')
            ->join('drivers', 'drivers.id = trip_tickets.driver_id', 'left')
            ->whereNotIn('trip_tickets.status', ['cancelled', 'completed', 'returned'])
            ->where('trip_tickets.request_id !=', $requestId)
            ->where('trip_tickets.authorized_departure <=', $ret)
            ->where('trip_tickets.authorized_return >=', $dep)
            ->where('(trip_tickets.vehicle_id = ' . $vehicleId . ' OR trip_tickets.driver_id = ' . $driverId . ')')
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

    // ------------------------------------------------------------------
    // FR-2.2 — Interactive Allocation Calendar (shared dispatcher view)
    // ------------------------------------------------------------------

    /**
     * Month-view calendar of vehicle + driver allocations.
     */
    public function calendar()
    {
        $month = $this->request->getGet('month') ?: date('Y-m');

        $data = [
            'title'      => 'Interactive Dispatch Calendar & Allocation Board',
            'month'      => $month,
            'allocations'=> $this->calendarData($month),
            'vehicles'   => $this->vehicleModel->orderBy('plate_number', 'ASC')->findAll(),
            'drivers'    => $this->driverModel->findAll(),
        ];

        return view('dispatch/calendar', $data);
    }

    /**
     * JSON feed powering the FR-2.2 calendar (used by the view and APIs).
     */
    public function calendarData(?string $month = null): array
    {
        $month  = $month ?: ($this->request->getGet('month') ?: date('Y-m'));
        $start  = $month . '-01 00:00:00';
        $end    = date('Y-m-t 23:59:59', strtotime($start));

        $tickets = $this->ticketModel
            ->select('trip_tickets.*, vehicles.plate_number, vehicles.fleet_category,
                      vehicles.assigned_official,
                      CONCAT(drivers.first_name, " ", drivers.last_name) AS driver_name,
                      trip_requests.destination, trip_requests.requestor_name')
            ->join('vehicles', 'vehicles.id = trip_tickets.vehicle_id', 'left')
            ->join('drivers', 'drivers.id = trip_tickets.driver_id', 'left')
            ->join('trip_requests', 'trip_requests.id = trip_tickets.request_id', 'left')
            ->whereNotIn('trip_tickets.status', ['cancelled'])
            ->where('trip_tickets.authorized_departure <=', $end)
            ->where('trip_tickets.authorized_return >=', $start)
            ->orderBy('trip_tickets.authorized_departure', 'ASC')
            ->findAll();

        $allocations = [];
        foreach ($tickets as $t) {
            $allocations[] = [
                'date'          => substr((string) $t['authorized_departure'], 0, 10),
                'ticket'        => $t['ticket_serial_no'],
                'status'        => $t['status'],
                'plate'         => $t['plate_number'],
                'fleet_category'=> $t['fleet_category'] ?? 'pool',
                'driver'        => $t['driver_name'],
                'destination'   => $t['destination'],
                'requestor'     => $t['requestor_name'],
                'departure'     => $t['authorized_departure'],
                'return'        => $t['authorized_return'],
                'url'           => '/tickets/' . $t['id'],
            ];
        }

        return $allocations;
    }

    /**
     * JSON endpoint for the calendar view.
     */
    public function calendarFeed()
    {
        return $this->response->setJSON([
            'status' => 'success',
            'month'  => $this->request->getGet('month') ?: date('Y-m'),
            'data'   => $this->calendarData(),
        ]);
    }
}
