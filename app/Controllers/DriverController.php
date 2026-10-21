<?php

namespace App\Controllers;

use App\Models\DriverModel;
use App\Models\VehicleModel;
use App\Models\IncidentModel;
use App\Models\TripTicketModel;
use App\Models\FuelLogModel;
use App\Models\RfidCardModel;
use App\Models\SafetyCheckModel;
use App\Models\PassengerDelayModel;

class DriverController extends BaseController
{
    public function index()
    {
        $driverModel = new DriverModel();
        $drivers = $driverModel->orderBy('id', 'DESC')->findAll();

        return view('drivers/index', [
            'title'   => 'Fleet Drivers & Operators',
            'drivers' => $drivers,
        ]);
    }

    public function mobileApp()
    {
        $driverModel   = new DriverModel();
        $ticketModel   = new TripTicketModel();
        $vehicleModel  = new VehicleModel();
        $incidentModel = new IncidentModel();
        $fuelLogModel  = new FuelLogModel();
        $rfidCardModel = new RfidCardModel();
        $safetyModel   = new SafetyCheckModel();
        $delayModel    = new PassengerDelayModel();

        $driverId = $this->request->getGet('driver_id');

        $driver = null;
        if ($driverId && (session()->get('user_role') === 'admin' || is_cli())) {
            $driver = $driverModel->find($driverId);
        }
        if (!$driver && session()->get('user_id')) {
            $driver = $driverModel->where('user_id', session()->get('user_id'))->first();
            if (!$driver && session()->get('user_email')) {
                $driver = $driverModel->where('email', session()->get('user_email'))->first();
            }
            if (!$driver && session()->get('user_name')) {
                $driver = $driverModel->where('CONCAT(first_name, \' \', last_name)', session()->get('user_name'))->first();
            }
        }
        if (!$driver) {
            $driver = $driverModel->first();
        }

        // Assigned vehicle
        $vehicle = null;
        if ($driver) {
            $vehicle = $vehicleModel->where('current_driver_id', $driver['id'])->first();
        }

        // 1. Official Driver's Trip Tickets (ADMIN-F-001 rev1)
        $tripTickets = [];
        $activeTicket = null;
        if ($driver) {
            $tripTickets = $ticketModel->getTicketsWithDetails(['driver_id' => $driver['id']]);
            foreach ($tripTickets as &$tkItem) {
                $tkItem['has_passed_safety'] = $safetyModel->hasPassedCheck((int) $tkItem['id']);
            }
            unset($tkItem);

            // Prefer in-transit (departed, arrived_dest, departed_dest), then issued (pending gate departure), then returned (pending Section B)
            foreach ($tripTickets as $tk) {
                if (in_array($tk['status'], ['departed', 'arrived_dest', 'departed_dest'], true)) {
                    $activeTicket = $tk;
                    break;
                }
            }
            if (!$activeTicket) {
                foreach ($tripTickets as $tk) {
                    if ($tk['status'] === 'issued') {
                        $activeTicket = $tk;
                        break;
                    }
                }
            }
            if (!$activeTicket) {
                foreach ($tripTickets as $tk) {
                    if ($tk['status'] === 'returned') {
                        $activeTicket = $tk;
                        break;
                    }
                }
            }
            if (!$activeTicket && !empty($tripTickets)) {
                $activeTicket = $tripTickets[0];
            }
        }

        // Fallback: If logged in as admin/dispatcher or driver has no active ticket, load the latest active system ticket for demo
        if (!$activeTicket) {
            $anyActive = $ticketModel->whereIn('status', ['departed', 'arrived_dest', 'departed_dest', 'issued', 'returned'])->orderBy('id', 'DESC')->first();
            if ($anyActive) {
                $ticketList = $ticketModel->getTicketsWithDetails(['id' => $anyActive['id']]);
                if (!empty($ticketList)) {
                    $activeTicket = $ticketList[0];
                }
            }
        }

        // If no vehicle directly assigned, find vehicle from active or recent trip ticket
        if (!$vehicle) {
            if ($activeTicket && !empty($activeTicket['vehicle_id'])) {
                $vehicle = $vehicleModel->find($activeTicket['vehicle_id']);
            }
            if (!$vehicle && !empty($tripTickets)) {
                $vehicle = $vehicleModel->find($tripTickets[0]['vehicle_id']);
            }
            if (!$vehicle) {
                $vehicle = $vehicleModel->first();
            }
        }

        // 3. Tollway RFID Cards for vehicle
        $rfidCards = [];
        if ($vehicle) {
            $rfidCards = $rfidCardModel->where('vehicle_id', $vehicle['id'])->findAll();
        }

        // 4. BLOWBAGETS Pre-Trip Safety Check status for active ticket
        $blowbagetsPassed = false;
        $recentDelays = [];
        if ($activeTicket) {
            $blowbagetsPassed = $safetyModel->hasPassedCheck((int) $activeTicket['id']);
            $recentDelays = $delayModel->getForTicket((int) $activeTicket['id']);
        }

        // 5. Fuel Logs for this driver or vehicle
        $recentFuelLogs = [];
        if ($driver) {
            $recentFuelLogs = $fuelLogModel->where('driver_id', $driver['id'])->orderBy('id', 'DESC')->findAll(10);
            if (empty($recentFuelLogs) && $vehicle) {
                $recentFuelLogs = $fuelLogModel->where('vehicle_id', $vehicle['id'])->orderBy('id', 'DESC')->findAll(10);
            }
        }

        // 6. Incidents reported
        $recentIncidents = [];
        if ($driver) {
            $recentIncidents = $incidentModel->where('driver_id', $driver['id'])->orderBy('id', 'DESC')->findAll(10);
        }

        // List of drivers for quick mobile switcher
        $allDrivers = $driverModel->findAll();

        return view('driver/trips', [
            'driver'           => $driver,
            'vehicle'          => $vehicle,
            'tripTickets'      => $tripTickets,
            'activeTicket'     => $activeTicket,
            'rfidCards'        => $rfidCards,
            'blowbagetsPassed' => $blowbagetsPassed,
            'checklistItems'   => SafetyCheckModel::checklistItems(),
            'recentDelays'     => $recentDelays,
            'recentFuelLogs'   => $recentFuelLogs,
            'recentIncidents'  => $recentIncidents,
            'allDrivers'       => $allDrivers,
        ]);
    }

    public function updateDutyStatus()
    {
        $driverModel = new DriverModel();
        $driverId = (int)$this->request->getPost('driver_id');
        $status = $this->request->getPost('status');

        if ($driverId && in_array($status, ['available', 'off_duty', 'on_trip'])) {
            $driverModel->update($driverId, ['status' => $status]);
            return redirect()->to('/driver/trips')->with('success', 'Driver duty status updated to ' . ucfirst(str_replace('_', ' ', $status)) . '.');
        }

        return redirect()->to('/driver/trips')->with('error', 'Unable to update duty status.');
    }

    public function create()
    {
        return view('drivers/form', [
            'title'  => 'Register New Driver',
            'driver' => null,
        ]);
    }

    public function store()
    {
        $driverModel = new DriverModel();

        $rules = [
            'first_name'     => 'required|min_length[2]',
            'last_name'      => 'required|min_length[2]',
            'phone'          => 'required|min_length[7]',
            'license_number' => 'required|is_unique[drivers.license_number]',
            'license_type'   => 'required',
            'license_expiry' => 'required|valid_date',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $code = 'DRV-' . rand(100, 999);

        $data = [
            'driver_code'       => $code,
            'first_name'        => trim($this->request->getPost('first_name')),
            'last_name'         => trim($this->request->getPost('last_name')),
            'email'             => trim($this->request->getPost('email')),
            'phone'             => trim($this->request->getPost('phone')),
            'license_number'    => strtoupper(trim($this->request->getPost('license_number'))),
            'license_type'      => trim($this->request->getPost('license_type')),
            'license_expiry'    => $this->request->getPost('license_expiry'),
            'status'            => $this->request->getPost('status') ?: 'available',
            'safety_score'      => (float)($this->request->getPost('safety_score') ?: 100.00),
            'address'           => trim($this->request->getPost('address')),
            'emergency_contact' => trim($this->request->getPost('emergency_contact')),
        ];

        $driverModel->insert($data);

        return redirect()->to('/drivers')->with('success', 'Driver ' . $data['first_name'] . ' ' . $data['last_name'] . ' registered successfully.');
    }

    public function show($id)
    {
        $driverModel = new DriverModel();
        $driver = $driverModel->find($id);

        if (!$driver) {
            return redirect()->to('/drivers')->with('error', 'Driver not found.');
        }

        $vehicleModel = new VehicleModel();
        $assignedVehicle = $vehicleModel->where('current_driver_id', $id)->first();

        $ticketModel = new TripTicketModel();
        $trips = $ticketModel->getTicketsWithDetails(['driver_id' => $id]);

        $incidentModel = new IncidentModel();
        $incidents = $incidentModel->where('driver_id', $id)->orderBy('id', 'DESC')->findAll();

        return view('drivers/view', [
            'title'           => 'Driver Profile: ' . $driver['first_name'] . ' ' . $driver['last_name'],
            'driver'          => $driver,
            'assignedVehicle' => $assignedVehicle,
            'trips'           => $trips,
            'incidents'       => $incidents,
        ]);
    }

    public function edit($id)
    {
        $driverModel = new DriverModel();
        $driver = $driverModel->find($id);

        if (!$driver) {
            return redirect()->to('/drivers')->with('error', 'Driver not found.');
        }

        return view('drivers/form', [
            'title'  => 'Edit Driver: ' . $driver['first_name'] . ' ' . $driver['last_name'],
            'driver' => $driver,
        ]);
    }

    public function update($id)
    {
        $driverModel = new DriverModel();
        $driver = $driverModel->find($id);

        if (!$driver) {
            return redirect()->to('/drivers')->with('error', 'Driver not found.');
        }

        $rules = [
            'first_name'     => 'required|min_length[2]',
            'last_name'      => 'required|min_length[2]',
            'phone'          => 'required|min_length[7]',
            'license_number' => "required|is_unique[drivers.license_number,id,{$id}]",
            'license_type'   => 'required',
            'license_expiry' => 'required|valid_date',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'first_name'        => trim($this->request->getPost('first_name')),
            'last_name'         => trim($this->request->getPost('last_name')),
            'email'             => trim($this->request->getPost('email')),
            'phone'             => trim($this->request->getPost('phone')),
            'license_number'    => strtoupper(trim($this->request->getPost('license_number'))),
            'license_type'      => trim($this->request->getPost('license_type')),
            'license_expiry'    => $this->request->getPost('license_expiry'),
            'status'            => $this->request->getPost('status'),
            'safety_score'      => (float)$this->request->getPost('safety_score'),
            'address'           => trim($this->request->getPost('address')),
            'emergency_contact' => trim($this->request->getPost('emergency_contact')),
        ];

        $driverModel->update($id, $data);

        return redirect()->to('/drivers/' . $id)->with('success', 'Driver profile updated.');
    }

    public function delete($id)
    {
        $driverModel = new DriverModel();
        $driver = $driverModel->find($id);

        if ($driver) {
            $driverModel->delete($id);
            return redirect()->to('/drivers')->with('success', 'Driver removed successfully.');
        }

        return redirect()->to('/drivers')->with('error', 'Driver not found.');
    }

    public function reportIncident()
    {
        $incidentModel = new IncidentModel();
        $vehicleModel = new VehicleModel();

        $vehicleId = (int)$this->request->getPost('vehicle_id');
        $driverId = (int)$this->request->getPost('driver_id');
        $type = $this->request->getPost('type') ?: 'Breakdown / Mechanical';
        $severity = $this->request->getPost('severity') ?: 'minor';
        $location = trim($this->request->getPost('location') ?: 'Highway Route');
        $description = trim($this->request->getPost('description'));

        $incNo = 'INC-' . date('Ymd-His') . '-' . rand(10, 99);
        $now = date('Y-m-d H:i:s');

        $incidentId = $incidentModel->insert([
            'incident_no'     => $incNo,
            'vehicle_id'      => $vehicleId,
            'driver_id'       => $driverId ?: null,
            'trip_id'         => (int)$this->request->getPost('trip_id') ?: null,
            'incident_date'   => $now,
            'severity'        => $severity,
            'type'            => $type,
            'location'        => $location,
            'description'     => $description ?: "Driver reported {$type} at {$location}.",
            'damage_estimate' => 0.00,
            'status'          => 'reported',
        ]);

        // ------------------------------------------------------------------
        // FR-4.3 (Report Breakdown): the vehicle state updates to
        // Disabled / Breakdown and a Pre-Repair Inspection Report is
        // automatically generated and routed to the mechanic queue.
        // ------------------------------------------------------------------
        $isBreakdown = stripos($type, 'breakdown') !== false
            || stripos($type, 'mechanical') !== false
            || in_array($severity, ['severe', 'critical'], true);

        $pirNumber = null;
        if ($isBreakdown && $vehicleId > 0) {
            $vehicleModel->update($vehicleId, ['status' => 'disabled_breakdown']);

            $pirModel = new \App\Models\PirReportModel();
            $pirNumber = $pirModel->generatePirNumber();
            $pirModel->insert([
                'pir_number'         => $pirNumber,
                'vehicle_id'         => $vehicleId,
                'incident_id'        => (int) $incidentId,
                'source'             => 'breakdown',
                'defect_description' => "Breakdown reported via incident {$incNo} at {$location} (severity: {$severity}). "
                    . ($description ?: "Driver reported {$type}."),
                'status'             => 'pending',
                'reported_by'        => (int) session()->get('user_id') ?: null,
                'created_at'         => $now,
            ]);

            // Route to the mechanic queue + notify dispatch (FR-4.3)
            $userModel = new \App\Models\UserModel();
            $recipients = array_merge(
                $userModel->where('role', 'maintenance')->where('status', 'active')->findAll(),
                $userModel->where('role', 'dispatcher')->where('status', 'active')->findAll(),
                $userModel->where('role', 'admin')->where('status', 'active')->findAll()
            );
            $notificationModel = new \App\Models\NotificationModel();
            foreach ($recipients as $recip) {
                $notificationModel->insert([
                    'user_id'    => $recip['id'],
                    'title'      => "BREAKDOWN — Vehicle Disabled ({$incNo})",
                    'message'    => "Vehicle #{$vehicleId} reported {$type} at {$location} (severity {$severity}). State set to Disabled/Breakdown and PIR {$pirNumber} auto-generated for the mechanic queue.",
                    'type'       => 'danger',
                    'link'       => '/pir',
                    'is_read'    => 0,
                    'created_at' => $now,
                ]);
            }

            \App\Services\AuditLogger::log(
                \App\Services\AuditLogger::STATUS_CHANGE,
                "Breakdown reported ({$incNo}): vehicle #{$vehicleId} set to Disabled/Breakdown; PIR {$pirNumber} auto-generated (FR-4.3).",
                'vehicle',
                $vehicleId,
                ['incident' => $incNo, 'severity' => $severity]
            );
        }

        $referer = $this->request->getServer('HTTP_REFERER') ?? '';
        $isAjax = method_exists($this->request, 'isAJAX') && $this->request->isAJAX();
        if (str_contains($referer, 'driver/trips') || !$isAjax) {
            return redirect()->to('/driver/trips')->with(
                'success',
                "Incident {$incNo} reported to Dispatch."
                . ($pirNumber ? " Vehicle set to Disabled/Breakdown — PIR {$pirNumber} routed to the mechanic queue." : '')
            );
        }

        return $this->response->setJSON([
            'status'      => 'success',
            'incident_no' => $incNo,
            'pir_number'  => $pirNumber,
            'message'     => 'Incident reported to Dispatch.',
        ]);
    }

    /**
     * Phase 2: Driver confirms arrival at event / location.
     * Stamps arrival_dest_time and transitions status to 'arrived_dest'.
     */
    public function arriveDestination()
    {
        $ticketModel  = new TripTicketModel();
        $vehicleModel = new VehicleModel();
        $isAjax = method_exists($this->request, 'isAJAX') && $this->request->isAJAX();

        $ticketId = (int) ($this->request->getPost('ticket_id') ?: ($_POST['ticket_id'] ?? 0));
        $ticket   = $ticketModel->find($ticketId);

        if (!$ticket) {
            if ($isAjax) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Trip ticket not found.']);
            }
            return redirect()->to('/driver/trips')->with('error', 'Trip ticket not found.');
        }

        $now = date('Y-m-d H:i:s');
        $postedDestOdo = $this->request->getPost('dest_odometer') ?? ($_POST['dest_odometer'] ?? null);
        $destOdo = ($postedDestOdo !== null && $postedDestOdo !== '') ? (float) $postedDestOdo : (float) ($ticket['dest_odometer'] ?? 0);

        if ($destOdo <= 0) {
            $destOdo = (float) ($ticket['start_odometer'] ?? 0);
        }

        $updateData = [
            'arrival_dest_time' => $now,
            'status'            => 'arrived_dest',
        ];
        if ($destOdo > 0) {
            $updateData['dest_odometer'] = $destOdo;
        }

        $ticketModel->update($ticketId, $updateData);

        if (!empty($ticket['vehicle_id']) && $destOdo > 0) {
            $veh = $vehicleModel->find($ticket['vehicle_id']);
            if ($veh && $destOdo > (float) $veh['odometer_km']) {
                $vehicleModel->update($ticket['vehicle_id'], ['odometer_km' => $destOdo]);
            }
        }

        $userModel = new \App\Models\UserModel();
        $dispatchers = $userModel->where('role', 'dispatcher')->where('status', 'active')->findAll();
        $notifModel = new \App\Models\NotificationModel();
        foreach ($dispatchers as $disp) {
            $notifModel->insert([
                'user_id'    => $disp['id'],
                'title'      => "DESTINATION ARRIVAL: {$ticket['ticket_serial_no']}",
                'message'    => "Vehicle has arrived safely at destination: {$ticket['authorized_destination']} at " . date('M d, Y h:i A', strtotime($now)) . ".",
                'type'       => 'info',
                'link'       => "/tickets/{$ticketId}",
                'is_read'    => 0,
                'created_at' => $now,
            ]);
        }

        \App\Services\AuditLogger::log(
            \App\Services\AuditLogger::STATUS_CHANGE,
            "Driver confirmed Arrival at Destination ({$ticket['authorized_destination']}) on ticket {$ticket['ticket_serial_no']} at {$now}.",
            'trip_ticket',
            $ticketId,
            ['arrival_dest_time' => $now, 'dest_odometer' => $destOdo]
        );

        $msg = "Arrival at {$ticket['authorized_destination']} confirmed! Stamped on DTT {$ticket['ticket_serial_no']}.";

        if ($isAjax) {
            return $this->response->setJSON([
                'status'            => 'success',
                'message'           => $msg,
                'arrival_dest_time' => $now,
                'ticket_status'     => 'arrived_dest',
            ]);
        }

        return redirect()->to('/driver/trips')->with('success', $msg);
    }

    /**
     * Phase 3: Driver confirms departure from destination en route back to compound.
     * Stamps departure_dest_time and transitions status to 'departed_dest'.
     */
    public function departDestination()
    {
        $ticketModel = new TripTicketModel();
        $isAjax = method_exists($this->request, 'isAJAX') && $this->request->isAJAX();

        $ticketId = (int) ($this->request->getPost('ticket_id') ?: ($_POST['ticket_id'] ?? 0));
        $ticket   = $ticketModel->find($ticketId);

        if (!$ticket) {
            if ($isAjax) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Trip ticket not found.']);
            }
            return redirect()->to('/driver/trips')->with('error', 'Trip ticket not found.');
        }

        $now = date('Y-m-d H:i:s');

        $ticketModel->update($ticketId, [
            'departure_dest_time' => $now,
            'status'              => 'departed_dest',
        ]);

        $userModel = new \App\Models\UserModel();
        $dispatchers = $userModel->where('role', 'dispatcher')->where('status', 'active')->findAll();
        $notifModel = new \App\Models\NotificationModel();
        foreach ($dispatchers as $disp) {
            $notifModel->insert([
                'user_id'    => $disp['id'],
                'title'      => "DESTINATION DEPARTURE: {$ticket['ticket_serial_no']}",
                'message'    => "Vehicle has departed from {$ticket['authorized_destination']} en route back to PIA Compound at " . date('M d, Y h:i A', strtotime($now)) . ".",
                'type'       => 'info',
                'link'       => "/tickets/{$ticketId}",
                'is_read'    => 0,
                'created_at' => $now,
            ]);
        }

        \App\Services\AuditLogger::log(
            \App\Services\AuditLogger::STATUS_CHANGE,
            "Driver confirmed Departure from Destination ({$ticket['authorized_destination']}) en route back to compound on ticket {$ticket['ticket_serial_no']} at {$now}.",
            'trip_ticket',
            $ticketId,
            ['departure_dest_time' => $now]
        );

        $msg = "Departure from {$ticket['authorized_destination']} confirmed. You are en route back to PIA Compound. Please scan QR at Gate #1 upon arrival.";

        if ($isAjax) {
            return $this->response->setJSON([
                'status'              => 'success',
                'message'             => $msg,
                'departure_dest_time' => $now,
                'ticket_status'       => 'departed_dest',
            ]);
        }

        return redirect()->to('/driver/trips')->with('success', $msg);
    }
}
