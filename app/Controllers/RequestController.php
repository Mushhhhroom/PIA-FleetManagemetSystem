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

        }

        // Secure file handling & strict validation against RCE / Arbitrary Upload
        if ($justificationFileObj && $justificationFileObj->isValid() && !$justificationFileObj->hasMoved()) {
            // 1. Max size validation (10 MB)
            if ($justificationFileObj->getSizeByUnit('kb') > 10240) {
                return redirect()->back()->withInput()->with('error', 'File size exceeds maximum permitted limit of 10 MB.');
            }

            // 2. Whitelist file extensions
            $clientExt = strtolower($justificationFileObj->getClientExtension());
            $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
            if (!in_array($clientExt, $allowedExtensions, true)) {
                return redirect()->back()->withInput()->with('error', 'Security Policy Violation: Only authentic PDF, PNG, and JPEG files are permitted.');
            }

            // 3. Inspect real magic bytes via finfo to prevent extension/MIME spoofing
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $realMime = $finfo->file($justificationFileObj->getTempName());
            $mimeToExt = [
                'application/pdf' => 'pdf',
                'image/jpeg'      => 'jpg',
                'image/png'       => 'png',
            ];

            if (!isset($mimeToExt[$realMime])) {
                return redirect()->back()->withInput()->with('error', 'Security Policy Violation: Uploaded file contents do not match an authentic PDF or image format.');
            }

            // 4. Force extension derived strictly from verified MIME type (never trust client-supplied extension)
            $forcedExt = $mimeToExt[$realMime];
            $newFileName = 'justification_' . date('Ymd_His') . '_' . bin2hex(random_bytes(10)) . '.' . $forcedExt;

            // 5. Store outside the public web root in WRITEPATH/uploads/justifications
            $targetDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'justifications';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0750, true);
            }

            $justificationFileObj->move($targetDir, $newFileName);
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

        // Resolve the Tier 1 Approver — Motorpool Dispatcher
        $officeId = (int)$this->request->getPost('office_id');
        $userModel = new UserModel();
        $tier1User = $userModel->where('office_id', $officeId)->where('role', 'dispatcher')->where('status', 'active')->first();
        if (!$tier1User) {
            $tier1User = $userModel->where('role', 'dispatcher')->where('status', 'active')->first();
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
            'status'                 => 'pending_dispatcher',
            // dispatcher_approver_id is recorded when the Dispatcher endorses/rejects
            'dispatcher_approver_id' => null,
            'sla_deadline'           => $slaDeadline,
        ];

        $requestId = $this->requestModel->insert($requestData);

        // Notify the Motorpool Dispatcher of the new filing
        if ($tier1User) {
            $rushTag = $isRush ? '[URGENT RUSH] ' : '';
            $this->notificationModel->insert([
                'user_id'    => $tier1User['id'],
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
            : "Vehicle Request Slip {$vrsNumber} submitted successfully. Pending Motorpool Dispatcher review (Tier 1 — " . SlaService::ESCALATION_HOURS . "-hour action window).";

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

    /**
     * @param int|string $id
     */
    public function show(int|string $id)
    {
        $session = session();
        $userRole = (string) $session->get('user_role');
        $userId   = (int) $session->get('user_id');

        $request = $this->requestModel->getRequestByIdWithDetails($id);
        if (!$request) {
            return redirect()->to('/requests')->with('error', 'Vehicle Request Slip not found.');
        }

        // Ownership authorization: requestors can only view their own requests
        if ($userRole === 'requestor' && (int) $request['requestor_id'] !== $userId) {
            return redirect()->to('/requests')->with('error', 'Access restricted: You cannot view requests filed by other staff members.');
        }

        $request['sla_info'] = SlaService::formatSlaCountdown($request['sla_deadline']);

        $data = [
            'title'   => "VRS Details: {$request['request_number']}",
            'request' => $request,
        ];

        return view('requests/show', $data);
    }

    public function printVrs(int|string $id)
    {
        $session = session();
        $userRole = (string) $session->get('user_role');
        $userId   = (int) $session->get('user_id');

        $request = $this->requestModel->getRequestByIdWithDetails($id);
        if (!$request) {
            return redirect()->to('/requests')->with('error', 'Vehicle Request Slip not found.');
        }

        if ($userRole === 'requestor' && (int) $request['requestor_id'] !== $userId) {
            return redirect()->to('/requests')->with('error', 'Access restricted: You cannot print requests filed by other staff members.');
        }

        $data = [
            'title'   => "ADMIN-F-018 rev2 Official Print - {$request['request_number']}",
            'request' => $request,
        ];

        return view('requests/print_vrs', $data);
    }

    /**
     * Securely stream an emergency justification document.
     * Enforces authentication, authorization, and path traversal prevention.
     */
    public function justification(int|string $id)
    {
        $session = session();
        $userRole = (string) $session->get('user_role');
        $userId   = (int) $session->get('user_id');

        $request = $this->requestModel->find((int) $id);
        if (!$request || empty($request['justification_file'])) {
            return redirect()->to('/requests')->with('error', 'Justification document not found.');
        }

        // Requestors can only download justification documents for their own requests
        if ($userRole === 'requestor' && (int) $request['requestor_id'] !== $userId) {
            return redirect()->to('/requests')->with('error', 'Access restricted: You cannot view documents from other staff members.');
        }

        $baseName = basename((string) $request['justification_file']);

        // Check protected writable directory first, then legacy public directory
        $privatePath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'justifications' . DIRECTORY_SEPARATOR . $baseName;
        $legacyPath  = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'justifications' . DIRECTORY_SEPARATOR . $baseName;

        $targetPath = is_file($privatePath) ? $privatePath : (is_file($legacyPath) ? $legacyPath : null);

        if (!$targetPath || !is_file($targetPath)) {
            return redirect()->to('/requests/' . (int) $id)->with('error', 'The requested file could not be found on storage.');
        }

        $ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
        $contentTypes = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
        ];

        $contentType = $contentTypes[$ext] ?? 'application/octet-stream';

        return $this->response
            ->setHeader('Content-Type', $contentType)
            ->setHeader('Content-Disposition', 'inline; filename="' . esc($baseName) . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Cache-Control', 'private, max-age=3600')
            ->setBody(file_get_contents($targetPath));
    }

    /**
     * Securely route legacy URLs (/uploads/justifications/<filename>)
     */
    public function serveJustificationFile(string $filename)
    {
        $session = session();
        if (!$session->get('logged_in')) {
            return redirect()->to('/login');
        }

        $baseName = basename($filename);
        $request = $this->requestModel->like('justification_file', $baseName)->first();
        if ($request) {
            return $this->justification((int) $request['id']);
        }

        $userRole = (string) $session->get('user_role');
        if ($userRole === 'requestor') {
            return redirect()->to('/requests')->with('error', 'Access restricted.');
        }

        $privatePath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'justifications' . DIRECTORY_SEPARATOR . $baseName;
        $legacyPath  = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'justifications' . DIRECTORY_SEPARATOR . $baseName;

        $targetPath = is_file($privatePath) ? $privatePath : (is_file($legacyPath) ? $legacyPath : null);

        if (!$targetPath || !is_file($targetPath)) {
            return redirect()->to('/requests')->with('error', 'File not found.');
        }

        $ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
        $contentTypes = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
        ];

        $contentType = $contentTypes[$ext] ?? 'application/octet-stream';

        return $this->response
            ->setHeader('Content-Type', $contentType)
            ->setHeader('Content-Disposition', 'inline; filename="' . esc($baseName) . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Cache-Control', 'private, max-age=3600')
            ->setBody(file_get_contents($targetPath));
    }
}
