<?php

namespace App\Controllers;

use App\Models\TripTicketModel;
use App\Models\TripRequestModel;
use App\Models\GateLogModel;
use App\Models\VehicleModel;
use App\Models\DriverModel;
use App\Models\NotificationModel;

class TripTicketController extends BaseController
{
    protected TripTicketModel $ticketModel;
    protected TripRequestModel $requestModel;
    protected GateLogModel $gateLogModel;
    protected VehicleModel $vehicleModel;
    protected DriverModel $driverModel;
    protected NotificationModel $notificationModel;

    public function __construct()
    {
        $this->ticketModel       = new TripTicketModel();
        $this->requestModel      = new TripRequestModel();
        $this->gateLogModel      = new GateLogModel();
        $this->vehicleModel      = new VehicleModel();
        $this->driverModel       = new DriverModel();
        $this->notificationModel = new NotificationModel();
    }

    public function index()
    {
        $session = session();
        $userRole = $session->get('user_role');


        // Drivers exclusively use the native mobile website (/driver/trips)
        if ($userRole === 'driver') {
            return redirect()->to('/driver/trips?tab=history');
        }

        $statusFilter = $this->request->getGet('status');
        $filters = [];
        if ($statusFilter) {
            $filters['status'] = $statusFilter;
        }

        $tickets = $this->ticketModel->getTicketsWithDetails($filters);

        $data = [
            'title'        => "Driver's Trip Tickets (ADMIN-F-001 rev1)",
            'tickets'      => $tickets,
            'activeFilter' => $statusFilter ?? 'all',
            'userRole'     => $userRole,
        ];

        return view('tickets/index', $data);
    }

    /**
     * @param int|string $id
     */
    public function show(int|string $id)
    {
        // Drivers exclusively use the native mobile website (/driver/trips)
        if (session()->get('user_role') === 'driver') {
            return redirect()->to('/driver/trips?ticket_id=' . (int) $id);
        }

        $ticket = $this->ticketModel->getTicketByIdWithDetails($id);
        if (!$ticket) {
            return redirect()->to('/tickets')->with('error', "Trip ticket not found.");
        }

        // Fetch gate logs for this ticket
        $gateLogs = $this->gateLogModel->where('trip_ticket_id', $id)->orderBy('id', 'ASC')->findAll();
        $safetyModel = new \App\Models\SafetyCheckModel();
        $hasPassedSafety = $safetyModel->hasPassedCheck((int) $id);

        // Fetch any secondary merged requests for this consolidated ticket
        $mergedRequests = [];
        if (!empty($ticket['merged_request_ids'])) {
            $mergedIds = json_decode($ticket['merged_request_ids'], true);
            if (!empty($mergedIds) && \is_array($mergedIds)) {
                $mergedRequests = $this->requestModel
                    ->select('trip_requests.*, offices.office_name, departments.department_name')
                    ->join('offices', 'offices.id = trip_requests.office_id', 'left')
                    ->join('departments', 'departments.id = trip_requests.department_id', 'left')
                    ->whereIn('trip_requests.id', $mergedIds)
                    ->findAll();
            }
        }

        $data = [
            'title'           => "Driver's Trip Ticket: {$ticket['ticket_serial_no']}",
            'ticket'          => $ticket,
            'gateLogs'        => $gateLogs,
            'hasPassedSafety' => $hasPassedSafety,
            'mergedRequests'  => $mergedRequests,
            'userRole'        => session()->get('user_role'),
        ];

        return view('tickets/show', $data);
    }

    /**
     * @param int|string $id
     */
    public function printDtt(int|string $id)
    {
        $ticket = $this->ticketModel->getTicketByIdWithDetails($id);
        if (!$ticket) {
            return redirect()->to('/tickets')->with('error', "Trip ticket not found.");
        }

        $data = [
            'title'  => "Official Print - {$ticket['ticket_serial_no']}",
            'ticket' => $ticket,
        ];

        return view('tickets/print_dtt', $data);
    }

    /**
     * @param int|string $id
     */
    public function updateSectionB(int|string $id)
    {
        $ticket = $this->ticketModel->find($id);
        if (!$ticket) {
            return redirect()->to('/tickets')->with('error', "Trip ticket not found.");
        }

        // Section B Timeline & Odometers
        // NOTE: departure_time (Departure from Compound) and arrival_back_time (Arrival back at Compound)
        // are strictly controlled and auto-stamped by the Compound Gate QR Scanner (GateSecurityController).
        $arrDestStr    = $this->request->getPost('arrival_dest_time') ?? ($_POST['arrival_dest_time'] ?? null);
        $depDestStr    = $this->request->getPost('departure_dest_time') ?? ($_POST['departure_dest_time'] ?? null);

        $postedStart   = $this->request->getPost('start_odometer') ?? ($_POST['start_odometer'] ?? null);
        $postedDest    = $this->request->getPost('dest_odometer') ?? ($_POST['dest_odometer'] ?? null);
        $postedReturn  = $this->request->getPost('return_odometer') ?? ($_POST['return_odometer'] ?? null);

        $startOdo      = ($postedStart !== null && $postedStart !== '') ? (float)$postedStart : (float)($ticket['start_odometer'] ?? 0);
        $destOdo       = ($postedDest !== null && $postedDest !== '') ? (float)$postedDest : (float)($ticket['dest_odometer'] ?? 0);
        $returnOdo     = ($postedReturn !== null && $postedReturn !== '') ? (float)$postedReturn : (float)($ticket['return_odometer'] ?? 0);
        $totalDistKm   = max(0, $returnOdo - $startOdo);

        // Section B Fuel Balance Formula:
        // Balance End = Balance Start + Issued from Stock + Purchased on Trip - Used During Trip
        $fuelStart     = (float)($this->request->getPost('fuel_balance_start_liters') ?? ($_POST['fuel_balance_start_liters'] ?? 0));
        $fuelIssued    = (float)($this->request->getPost('fuel_issued_stock_liters') ?? ($_POST['fuel_issued_stock_liters'] ?? 0));
        $fuelPurchased = (float)($this->request->getPost('fuel_purchased_liters') ?? ($_POST['fuel_purchased_liters'] ?? 0));
        $fuelCost      = (float)($this->request->getPost('fuel_purchased_cost') ?? ($_POST['fuel_purchased_cost'] ?? 0));
        $fuelUsed      = (float)($this->request->getPost('fuel_used_liters') ?? ($_POST['fuel_used_liters'] ?? 0));

        $fuelEnd = round($fuelStart + $fuelIssued + $fuelPurchased - $fuelUsed, 2);

        // Fuel Efficiency Calculation & Anomaly Detection (>20% deviation)
        $fuelEfficiency = ($fuelUsed > 0 && $totalDistKm > 0) ? round($totalDistKm / $fuelUsed, 2) : 0.00;

        // Expected benchmark ~ 9-11 km/L for diesel vans/pickups (FR-7.3 & FR-8.4)
        $expectedKml = 10.00;
        $isAnomaly = 0;
        if ($fuelUsed > 0 && $totalDistKm <= 0) {
            $isAnomaly = 1; // Severe consumption anomaly: Fuel consumed with 0 km vehicle movement
        } elseif ($fuelEfficiency > 0) {
            $deviation = abs($fuelEfficiency - $expectedKml) / $expectedKml;
            if ($deviation > 0.20) {
                $isAnomaly = 1;
            }
        }

        // Consumables
        $gearOil  = (float)($this->request->getPost('gear_oil_liters') ?? ($_POST['gear_oil_liters'] ?? 0));
        $lubeOil  = (float)($this->request->getPost('lube_oil_liters') ?? ($_POST['lube_oil_liters'] ?? 0));
        $grease   = (float)($this->request->getPost('grease_units') ?? ($_POST['grease_units'] ?? 0));

        // FR-4.1 / FR-6.2: toll expense capture on the trip ticket
        $tollExpense = max(0, (float)($this->request->getPost('toll_expense') ?? ($_POST['toll_expense'] ?? 0)));
        $tollProvider = $this->request->getPost('toll_provider') ?? ($_POST['toll_provider'] ?? null);
        $tollProvider = \in_array($tollProvider, ['autosweep', 'easytrip', 'cash'], true) ? $tollProvider : null;

        // Digital Certifications
        $driverCert = (int)($this->request->getPost('driver_certified') ?? ($_POST['driver_certified'] ?? 0));
        $passCert   = (int)($this->request->getPost('passenger_certified') ?? ($_POST['passenger_certified'] ?? 0));
        $passName   = trim((string)($this->request->getPost('passenger_certifier_name') ?? ($_POST['passenger_certifier_name'] ?? '')));

        $now = date('Y-m-d H:i:s');

        $updateData = [
            'departure_time'            => $ticket['departure_time'],
            'arrival_dest_time'         => $arrDestStr ?: $ticket['arrival_dest_time'],
            'departure_dest_time'       => $depDestStr ?: $ticket['departure_dest_time'],
            'arrival_back_time'         => $ticket['arrival_back_time'],
            'start_odometer'            => $startOdo,
            'dest_odometer'             => $destOdo ?: $ticket['dest_odometer'],
            'return_odometer'           => $returnOdo,
            'total_distance_km'         => $totalDistKm,
            'fuel_balance_start_liters' => $fuelStart,
            'fuel_issued_stock_liters'  => $fuelIssued,
            'fuel_purchased_liters'     => $fuelPurchased,
            'fuel_purchased_cost'       => $fuelCost,
            'fuel_used_liters'          => $fuelUsed,
            'fuel_balance_end_liters'   => $fuelEnd,
            'fuel_efficiency_kml'       => $fuelEfficiency,
            'fuel_anomaly_flag'         => $isAnomaly,
            'gear_oil_liters'           => $gearOil,
            'lube_oil_liters'           => $lubeOil,
            'grease_units'              => $grease,
            'toll_expense'              => $tollExpense,
            'toll_provider'             => $tollExpense > 0 ? $tollProvider : null,
            'driver_certified'          => $driverCert ? 1 : $ticket['driver_certified'],
            'driver_certified_at'       => $driverCert ? $now : $ticket['driver_certified_at'],
            'passenger_certified'       => $passCert ? 1 : $ticket['passenger_certified'],
            'passenger_certifier_name'  => $passName ?: $ticket['passenger_certifier_name'],
            'passenger_certified_at'    => $passCert ? $now : $ticket['passenger_certified_at'],
            'notes'                     => trim((string)$this->request->getPost('notes')) ?: $ticket['notes'],
        ];

        // If trip is returned and certifications completed, mark ticket and VRS completed
        if ($ticket['status'] === 'returned' && $driverCert && ($passCert || !empty($passName))) {
            $updateData['status'] = 'completed';
            $updateData['passenger_certified'] = 1;
            $updateData['passenger_certified_at'] = $updateData['passenger_certified_at'] ?: $now;
            $this->requestModel->update($ticket['request_id'], ['status' => 'completed']);

            // FR-1.3: completing the trip satisfies the post-trip
            // documentation requirement of an emergency override
            $request = $this->requestModel->find($ticket['request_id']);
            if ($request && !empty($request['is_emergency_override'])
                && \in_array($request['post_trip_doc_status'], ['pending', 'overdue'], true)) {
                $this->requestModel->update($ticket['request_id'], ['post_trip_doc_status' => 'submitted']);
            }

            \App\Services\AuditLogger::log(
                \App\Services\AuditLogger::STATUS_CHANGE,
                "Trip ticket {$ticket['ticket_serial_no']} completed (driver + passenger certifications recorded).",
                'trip_ticket',
                (int) $id,
                ['distance_km' => $totalDistKm, 'fuel_kml' => $fuelEfficiency]
            );
        }

        $this->ticketModel->update($id, $updateData);

        // Ensure vehicle odometer and PMS tracking stay synchronized with verified return odometer
        if ($returnOdo > 0) {
            $vehicle = $this->vehicleModel->find($ticket['vehicle_id']);
            if ($vehicle && $returnOdo > (float)$vehicle['odometer_km']) {
                $this->vehicleModel->update($ticket['vehicle_id'], [
                    'odometer_km' => $returnOdo,
                ]);

                $pmsModel = new \App\Models\PmsRecordModel();
                $pms = $pmsModel->where('vehicle_id', $ticket['vehicle_id'])->first();
                if ($pms) {
                    $nextPms = (float)$pms['next_pms_odometer'];
                    $diff = $nextPms - $returnOdo;
                    $pmsStatus = 'ok';
                    $isLocked = 0;
                    if ($diff <= 0) {
                        $pmsStatus = 'overdue';
                        $isLocked = 1;
                    } elseif ($diff <= 500) {
                        $pmsStatus = 'due_soon';
                    }
                    $pmsModel->update($pms['id'], [
                        'status'    => $pmsStatus,
                        'is_locked' => $isLocked,
                    ]);
                }
            }
        }

        // FR-6.2: record the toll deduction against the vehicle's RFID card
        if ($tollExpense > 0 && \in_array($tollProvider, ['autosweep', 'easytrip'], true)) {
            $rfid = new \App\Controllers\RfidController();
            $newBalance = $rfid->recordToll(
                (int) $ticket['vehicle_id'],
                $tollExpense,
                $tollProvider,
                (int) $id,
                "Toll expense logged on trip ticket {$ticket['ticket_serial_no']}"
            );
            if ($newBalance !== null) {
                \App\Services\AuditLogger::log(
                    \App\Services\AuditLogger::RFID_RELOAD,
                    "Toll of ₱" . number_format($tollExpense, 2) . " deducted from {$tollProvider} card; new balance ₱" . number_format($newBalance, 2) . " (FR-6.2).",
                    'trip_ticket',
                    (int) $id
                );
            }
        }

        $referer = $this->request->getServer('HTTP_REFERER') ?? '';
        if (session()->get('user_role') === 'driver' || str_contains($referer, 'driver/trips')) {
            return redirect()->to('/driver/trips')->with('success', "Driver Trip Ticket {$ticket['ticket_serial_no']} Section B updated successfully.");
        }

        return redirect()->to("/tickets/{$id}")->with('success', 'Driver Trip Ticket Section B (Trip Log & Fuel Accounting) updated successfully.');
    }

    /**
     * FR-4.2 — 15-Minute Passenger Delay Log.
     *
     * Logs passenger pick-up delays; once a delay reaches the 15-minute
     * standard rule, all dispatchers are automatically notified.
     *
     * @param int|string $id
     */
    public function logDelay(int|string $id)
    {
        $ticket = $this->ticketModel->find($id);
        if (!$ticket) {
            return redirect()->to(session()->get('user_role') === 'driver' ? '/driver/trips' : '/tickets')->with('error', 'Trip ticket not found.');
        }

        $minutes = (int) $this->request->getPost('delay_minutes');
        $reason  = trim((string) $this->request->getPost('reason'));

        if ($minutes <= 0) {
            return redirect()->back()->with('error', 'Delay duration must be greater than zero minutes.');
        }

        // FR-4.2: 15-minute standard rule triggers dispatcher notification
        $notified = $minutes >= 15 ? 1 : 0;
        $now = date('Y-m-d H:i:s');

        $delayModel = new \App\Models\PassengerDelayModel();
        $delayModel->insert([
            'trip_ticket_id'     => (int) $id,
            'delay_minutes'      => $minutes,
            'reason'             => $reason ?: null,
            'dispatcher_notified'=> $notified,
            'logged_by'          => (int) session()->get('user_id') ?: null,
            'created_at'         => $now,
        ]);

        $isDriver = session()->get('user_role') === 'driver' || str_contains($this->request->getServer('HTTP_REFERER') ?? '', 'driver/trips');

        if ($notified) {
            $notificationModel = new NotificationModel();
            $userModel = new \App\Models\UserModel();
            foreach ($userModel->whereIn('role', ['dispatcher', 'admin'])->where('status', 'active')->findAll() as $disp) {
                $notificationModel->insert([
                    'user_id'    => $disp['id'],
                    'title'      => "PASSENGER DELAY (FR-4.2): {$ticket['ticket_serial_no']}",
                    'message'    => "Passenger pick-up delayed by {$minutes} minutes"
                        . ($reason ? " — {$reason}" : '')
                        . ". Exceeds the 15-minute standard rule; adjust the dispatch schedule accordingly.",
                    'type'       => 'warning',
                    'link'       => "/tickets/{$id}",
                    'is_read'    => 0,
                    'created_at' => $now,
                ]);
            }

            \App\Services\AuditLogger::log(
                \App\Services\AuditLogger::STATUS_CHANGE,
                "Passenger delay of {$minutes} minutes logged on {$ticket['ticket_serial_no']} (≥15 min → dispatchers notified, FR-4.2).",
                'trip_ticket',
                (int) $id,
                ['minutes' => $minutes, 'reason' => $reason]
            );

            $msg = "Delay of {$minutes} minutes logged. Dispatchers have been automatically notified (15-minute rule).";
            return ($isDriver ? redirect()->to('/driver/trips') : redirect()->back())->with('success', $msg);
        }

        $msg = "Delay of {$minutes} minutes logged on the trip ticket.";
        return ($isDriver ? redirect()->to('/driver/trips') : redirect()->back())->with('success', $msg);
    }
}
