<?php

namespace App\Controllers;

use App\Models\TripRequestModel;
use App\Models\OfficeModel;
use App\Models\DepartmentModel;
use App\Models\DriverModel;
use App\Models\NotificationModel;
use App\Models\UserModel;
use App\Services\SlaService;
use App\Services\AuditLogger;

class RequestController extends BaseController
{
    protected TripRequestModel $requestModel;
    protected OfficeModel $officeModel;
    protected DepartmentModel $departmentModel;
    protected DriverModel $driverModel;
    protected NotificationModel $notificationModel;

    public function __construct()
    {
        $this->requestModel      = new TripRequestModel();
        $this->officeModel       = new OfficeModel();
        $this->departmentModel   = new DepartmentModel();
        $this->driverModel       = new DriverModel();
        $this->notificationModel = new NotificationModel();
    }

    public function index()
    {
        $session = session();
        $userRole = $session->get('user_role');
        $userId   = $session->get('user_id');

        $filters = [];
        // If requestor, default to only their requests
        if ($userRole === 'requestor') {
            $filters['requestor_id'] = $userId;
        }

        $statusFilter = $this->request->getGet('status');
        if ($statusFilter) {
            $filters['status'] = $statusFilter;
        }
        $rushFilter = $this->request->getGet('rush');
        if ($rushFilter) {
            $filters['is_rush_request'] = 1;
        }

        $requests = $this->requestModel->getRequestsWithDetails($filters);

        // Attach formatted SLA countdown to each request
        foreach ($requests as &$req) {
            $req['sla_info'] = SlaService::formatSlaCountdown($req['sla_deadline']);
        }

        $data = [
            'title'        => 'Vehicle Request Slips (ADMIN-F-018 rev2)',
            'requests'     => $requests,
            'activeFilter' => $statusFilter ?? 'all',
            'isRushFilter' => (bool)$rushFilter,
            'userRole'     => $userRole,
        ];

        return view('requests/index', $data);
    }

    public function create()
    {
        $offices     = $this->officeModel->orderBy('office_type', 'ASC')->orderBy('office_name', 'ASC')->findAll();
        $departments = $this->departmentModel->where('status', 'active')->orderBy('department_name', 'ASC')->findAll();
        $drivers     = $this->driverModel->where('status !=', 'suspended')->findAll();

        $data = [
            'title'       => 'New Vehicle Request Slip (ADMIN-F-018 rev2)',
            'offices'     => $offices,
            'departments' => $departments,
            'drivers'     => $drivers,
        ];

        return view('requests/create', $data);
    }

    public function store()
    {
        $session = session();
        $userId = $session->get('user_id');
        $userName = $session->get('user_name');

        $depTimeStr = $this->request->getPost('departure_time');
        $retTimeStr = $this->request->getPost('return_time');

        if (!$depTimeStr || !$retTimeStr) {
            return redirect()->back()->withInput()->with('error', 'Please provide valid departure and expected return dates.');
        }

        $depTime = strtotime($depTimeStr);
        $retTime = strtotime($retTimeStr);
        $now = time();

        if ($retTime <= $depTime) {
            return redirect()->back()->withInput()->with('error', 'Expected return date/time must be after departure time.');
        }

        // BR-01 & BR-03: 24-Hour Advance Notice & Rush Request Justification Check
        $hoursNotice = ($depTime - $now) / 3600.0;
        $isRush = $hoursNotice < 24.0;

        $justificationFile = null;
        $justificationFileObj = $this->request->getFile('justification_file');

        if ($isRush) {
            $notes = trim((string)$this->request->getPost('justification_notes'));
            if (empty($notes)) {
                return redirect()->back()->withInput()->with('error', 'Same-day / rush requests require an explanation of the emergency justification.');
            }

            if (!$justificationFileObj || !$justificationFileObj->isValid()) {
                return redirect()->back()->withInput()->with('error', 'Emergency Justification Document (PDF or image) is mandatory for same-day requests under BR-03.');
            }

            // Save uploaded justification file
            $newFileName = $justificationFileObj->getRandomName();
            $justificationFileObj->move(FCPATH . 'uploads/justifications', $newFileName);
            $justificationFile = 'uploads/justifications/' . $newFileName;
        }

        // Generate sequential VRS number
        $vrsNumber = $this->requestModel->generateVrsNumber();

        // FR-1.4: 4-Hour Approval Escalation Deadline (escalates to Admin Division Chief)
        $slaDeadline = date('Y-m-d H:i:s', strtotime('+' . SlaService::ESCALATION_HOURS . ' hours'));

        // Resolve Department Information (required dropdown selection)
        $departmentId = (int)$this->request->getPost('department_id');
        if ($departmentId <= 0) {
            return redirect()->back()->withInput()->with('error', 'Please select a Department Information for this vehicle request.');
        }
        if (!$this->departmentModel->find($departmentId)) {
            return redirect()->back()->withInput()->with('error', 'The selected department does not exist. Please choose a valid department.');
        }

        // Resolve OIC Approver based on office
        $officeId = (int)$this->request->getPost('office_id');
        $userModel = new UserModel();
        $oicUser = $userModel->where('office_id', $officeId)->where('role', 'approver_oic')->first();
        if (!$oicUser) {
            // Fallback to any active OIC approver
            $oicUser = $userModel->where('role', 'approver_oic')->first();
        }

        $requestData = [
            'request_number'         => $vrsNumber,
            'requestor_id'           => $userId,
            'requestor_name'         => $userName,
            'office_id'              => $officeId,
            'department_id'          => $departmentId,
            'office_scope'           => $this->request->getPost('office_scope') ?? 'central',
            'destination'            => trim((string)$this->request->getPost('destination')),
            'purpose'                => trim((string)$this->request->getPost('purpose')),
            'passenger_names'        => trim((string)$this->request->getPost('passenger_names')),
            'passenger_count'        => max(1, (int)$this->request->getPost('passenger_count')),
            'departure_time'         => date('Y-m-d H:i:s', $depTime),
            'return_time'            => date('Y-m-d H:i:s', $retTime),
            'requested_driver_id'    => $this->request->getPost('requested_driver_id') ?: null,
            'requested_vehicle_type' => $this->request->getPost('requested_vehicle_type') ?: null,
            'is_rush_request'        => $isRush ? 1 : 0,
            'justification_file'     => $justificationFile,
            'justification_notes'    => $this->request->getPost('justification_notes') ?: null,
            'status'                 => 'pending_oic',
            'oic_approver_id'        => $oicUser ? $oicUser['id'] : null,
            'sla_deadline'           => $slaDeadline,
        ];

        $requestId = $this->requestModel->insert($requestData);

        // Send Notification to Approver 1 (OIC)
        if ($oicUser) {
            $rushTag = $isRush ? '[URGENT RUSH] ' : '';
            $this->notificationModel->insert([
                'user_id'    => $oicUser['id'],
                'title'      => "{$rushTag}New VRS Filed: {$vrsNumber}",
                'message'    => "{$userName} filed a vehicle request to {$requestData['destination']}. Act within " . SlaService::ESCALATION_HOURS . " hours or it auto-escalates to the Administrative Division Chief (FR-1.4).",
                'type'       => $isRush ? 'danger' : 'info',
                'link'       => "/approvals",
                'is_read'    => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $msg = $isRush
            ? "Rush Vehicle Request {$vrsNumber} submitted with emergency justification. Approver escalation countdown started (" . SlaService::ESCALATION_HOURS . "h)."
            : "Vehicle Request Slip {$vrsNumber} submitted successfully. Pending OIC review (" . SlaService::ESCALATION_HOURS . "-hour action window).";

        // NFR-3: immutable audit trail
        AuditLogger::log(
            AuditLogger::VRS_SUBMITTED,
            "VRS {$vrsNumber} filed by {$userName} for travel to {$requestData['destination']}"
            . ($isRush ? ' (RUSH — justification attached)' : ''),
            'trip_request',
            (int) $requestId,
            ['rush' => $isRush, 'departure' => $requestData['departure_time']]
        );

        return redirect()->to("/requests/{$requestId}")->with('success', $msg);
    }

    public function show($id)
    {
        $request = $this->requestModel->getRequestByIdWithDetails($id);
        if (!$request) {
            return redirect()->to('/requests')->with('error', 'Vehicle Request Slip not found.');
        }

        $request['sla_info'] = SlaService::formatSlaCountdown($request['sla_deadline']);

        $data = [
            'title'   => "VRS Details: {$request['request_number']}",
            'request' => $request,
        ];

        return view('requests/show', $data);
    }

    public function printVrs($id)
    {
        $request = $this->requestModel->getRequestByIdWithDetails($id);
        if (!$request) {
            return redirect()->to('/requests')->with('error', 'Vehicle Request Slip not found.');
        }

        $data = [
            'title'   => "ADMIN-F-018 rev2 Official Print - {$request['request_number']}",
            'request' => $request,
        ];

        return view('requests/print_vrs', $data);
    }
}
