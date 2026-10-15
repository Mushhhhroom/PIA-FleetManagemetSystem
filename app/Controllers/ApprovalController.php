<?php

namespace App\Controllers;

use App\Models\TripRequestModel;
use App\Models\TripTicketModel;
use App\Models\NotificationModel;
use App\Models\UserModel;
use App\Services\SlaService;
use App\Services\AuditLogger;
use App\Services\MailerService;

class ApprovalController extends BaseController
{
    protected TripRequestModel $requestModel;
    protected TripTicketModel $ticketModel;
    protected NotificationModel $notificationModel;
    protected MailerService $mailer;

    public function __construct()
    {
        $this->requestModel      = new TripRequestModel();
        $this->ticketModel       = new TripTicketModel();
        $this->notificationModel = new NotificationModel();
        $this->mailer            = new MailerService();
    }

    public function index()
    {
        $session = session();
        $userRole = $session->get('user_role');
        $userId   = $session->get('user_id');

        // Check pending requests by tier
        $builder = $this->requestModel->select('trip_requests.*,
                                                offices.office_name, offices.office_code,
                                                users.name AS requestor_full_name, users.designation AS requestor_designation,
                                                disp_u.name AS dispatcher_approver_name,
                                                adm_u.name AS admin_approver_name')
                                      ->join('offices', 'offices.id = trip_requests.office_id', 'left')
                                      ->join('users', 'users.id = trip_requests.requestor_id', 'left')
                                      ->join('users AS disp_u', 'disp_u.id = trip_requests.dispatcher_approver_id', 'left')
                                      ->join('users AS adm_u', 'adm_u.id = trip_requests.admin_approver_id', 'left')
                                      ->orderBy('trip_requests.is_rush_request', 'DESC')
                                      ->orderBy('trip_requests.sla_deadline', 'ASC');

        if ($userRole === 'approver_admin') {
            $builder->whereIn('trip_requests.status', ['pending_admin', 'approved']);
        } else {
            // Super Admin and Motorpool Dispatcher see both approval tiers plus authorized requests.
            $builder->whereIn('trip_requests.status', ['pending_dispatcher', 'pending_admin', 'approved']);
        }

        $allRequests = $builder->findAll();

        $pendingDispatcher = [];
        $pendingAdmin      = [];
        $recentlyApproved  = [];

        foreach ($allRequests as $req) {
            $req['sla_info'] = SlaService::formatSlaCountdown($req['sla_deadline']);
            if ($req['status'] === 'pending_dispatcher') {
                $pendingDispatcher[] = $req;
            } elseif ($req['status'] === 'pending_admin') {
                $pendingAdmin[] = $req;
            } elseif ($req['status'] === 'approved') {
                // Only genuinely authorized requests belong in "Recently Authorized"
                $recentlyApproved[] = $req;
            }
        }

        // FR-1.3: emergency overrides awaiting post-trip documentation.
        // Overridden requests are already 'approved', so look there.
        $emergencyOverrides = array_values(array_filter(
            $recentlyApproved,
            fn($r) => !empty($r['is_emergency_override'])
                && in_array($r['post_trip_doc_status'] ?? null, ['pending', 'overdue'], true)
        ));

        $data = [
            'title'              => 'Government Approval Portal & 4h Escalation Monitor',
            'userRole'           => $userRole,
            'userId'             => (int) $userId,
            'pendingDispatcher'  => $pendingDispatcher,
            'pendingAdmin'       => $pendingAdmin,
            'recentlyApproved'   => $recentlyApproved,
            'emergencyOverrides' => $emergencyOverrides,
            // FR-1.3 + segregation of duties: only the Administrative Division
            // Head (or Super Admin) may bypass the two-tier chain.
            'canOverride'        => in_array($userRole, ['admin', 'approver_admin'], true),
            // Tier 1 action authority = Motorpool Dispatcher (or Super Admin); Tier 2 = Administrative Division Head
            'canActTier1'        => in_array($userRole, ['admin', 'dispatcher'], true),
            'canActTier2'        => in_array($userRole, ['admin', 'approver_admin'], true),
        ];

        return view('approvals/index', $data);
    }

    public function action($id)
    {
        $session = session();
        $userRole = $session->get('user_role');
        $userId   = $session->get('user_id');
        $userName = $session->get('user_name');

        $request = $this->requestModel->find($id);
        if (!$request) {
            return redirect()->to('/approvals')->with('error', 'Request not found.');
        }

        $action = $this->request->getPost('action'); // 'approve' or 'reject'
        $remarks = trim((string)$this->request->getPost('remarks'));
        $redirectTo = trim((string)$this->request->getPost('redirect_to'));
        $now = date('Y-m-d H:i:s');

        if ($action === 'reject' && empty($remarks)) {
            return redirect()->back()->with('error', 'Please provide justification remarks when rejecting an official travel request.');
        }

        if (!in_array($action, ['approve', 'reject'], true)) {
            $fallback = $redirectTo ?: '/approvals';
            return redirect()->to($fallback)->with('error', 'Invalid approval action.');
        }

        // Segregation of duties: the Tier 1 endorser cannot also grant Tier 2
        // final authority on the same VRS (unless super admin override).
        if ($request['status'] === 'pending_admin'
            && ($request['dispatcher_action'] ?? null) === 'approved'
            && (int) ($request['dispatcher_approver_id'] ?? 0) === (int) $userId
            && $userRole !== 'admin') {
            return redirect()->to('/approvals')->with('error', 'You endorsed this VRS at Tier 1; final Tier 2 authorization must come from a different official.');
        }

        // Tier 1: Motorpool Dispatcher Approval
        if ($userRole === 'dispatcher' || ($userRole === 'admin' && $request['status'] === 'pending_dispatcher')) {
            $tier1Title = $userRole === 'dispatcher' ? 'Motorpool Dispatcher' : 'Super Admin';
            if ($request['status'] !== 'pending_dispatcher') {
                return redirect()->to('/approvals')->with('error', 'This request is no longer awaiting Tier 1 action.');
            }

            if ($action === 'approve') {
                // Find the active Admin Division Head (Atty. Julius S. De Peralta)
                $userModel = new UserModel();
                $adminHead = $userModel->where('role', 'approver_admin')->where('status', 'active')->first();

                // Tier 2 final-action window (BR-02: 24-hour window)
                $newSlaDeadline = date('Y-m-d H:i:s', strtotime('+' . SlaService::FINAL_SLA_HOURS . ' hours'));

                $this->requestModel->update($id, [
                    'dispatcher_approver_id' => $userId,
                    'dispatcher_action'      => 'approved',
                    'dispatcher_action_at'   => $now,
                    'dispatcher_remarks'     => $remarks ?: 'Endorsed for administrative clearance.',
                    'admin_approver_id'      => $adminHead ? $adminHead['id'] : null,
                    'status'                 => 'pending_admin',
                    'sla_deadline'           => $newSlaDeadline,
                ]);

                // Notify Admin Head
                if ($adminHead) {
                    $this->notificationModel->insert([
                        'user_id'    => $adminHead['id'],
                        'title'      => "Endorsed VRS Ready for Final Approval: {$request['request_number']}",
                        'message'    => "{$tier1Title} {$userName} has endorsed VRS {$request['request_number']} ({$request['destination']}). Requires administrative clearance within " . SlaService::FINAL_SLA_HOURS . " hours or the request expires.",
                        'type'       => $request['is_rush_request'] ? 'danger' : 'info',
                        'link'       => '/approvals',
                        'is_read'    => 0,
                        'created_at' => $now,
                    ]);
                }

                // NFR-3: immutable audit trail
                AuditLogger::log(
                    AuditLogger::VRS_APPROVED,
                    "VRS {$request['request_number']} Tier-1 endorsed by {$userName} ({$tier1Title}).",
                    'trip_request',
                    (int) $id,
                    ['tier' => 1, 'remarks' => $remarks]
                );

                $target = $redirectTo ?: ($userRole === 'dispatcher' ? '/dispatch' : '/approvals');
                return redirect()->to($target)->with('success', "VRS {$request['request_number']} endorsed successfully. Transferred to Administrative Division Head.");
            } else {
                // Reject at Tier 1
                $this->requestModel->update($id, [
                    'dispatcher_approver_id' => $userId,
                    'dispatcher_action'      => 'rejected',
                    'dispatcher_action_at'   => $now,
                    'dispatcher_remarks'     => $remarks,
                    'status'                 => 'rejected',
                ]);

                // FR-1.5: in-app + email notification on rejection
                $this->mailer->notify(
                    (int) $request['requestor_id'],
                    "Request Rejected: {$request['request_number']}",
                    "Your Vehicle Request Slip for travel to {$request['destination']} was not endorsed at Tier 1 "
                    . "by the {$tier1Title}. Reason: {$remarks}",
                    'danger',
                    "/requests/{$id}"
                );

                // NFR-3: immutable audit trail
                AuditLogger::log(
                    AuditLogger::VRS_REJECTED,
                    "VRS {$request['request_number']} rejected at Tier 1 by {$userName}. Reason: {$remarks}",
                    'trip_request',
                    (int) $id,
                    ['tier' => 1, 'remarks' => $remarks]
                );

                $target = $redirectTo ?: ($userRole === 'dispatcher' ? '/dispatch' : '/approvals');
                return redirect()->to($target)->with('success', "VRS {$request['request_number']} has been rejected.");
            }
        }

        // Tier 2: Administrative Division Head Approval (Atty. Julius S. De Peralta)
        if ($userRole === 'approver_admin' || ($userRole === 'admin' && $request['status'] === 'pending_admin')) {
            if ($request['status'] !== 'pending_admin') {
                return redirect()->to('/approvals')->with('error', 'This request is not awaiting Administrative Division Head approval.');
            }

            if ($action === 'approve') {
                $this->requestModel->update($id, [
                    'admin_approver_id' => $userId,
                    'admin_action'      => 'approved',
                    'admin_action_at'   => $now,
                    'admin_remarks'     => $remarks ?: 'Approved for official travel and motorpool dispatch.',
                    'status'            => 'approved',
                ]);

                // Notify Requestor
                $this->notificationModel->insert([
                    'user_id'    => $request['requestor_id'],
                    'title'      => "VRS Approved: {$request['request_number']}",
                    'message'    => "Administrative Division Head has officially authorized your trip to {$request['destination']}. Awaiting motorpool dispatch.",
                    'type'       => 'success',
                    'link'       => "/requests/{$id}",
                    'is_read'    => 0,
                    'created_at' => $now,
                ]);

                // Notify Dispatchers
                $userModel = new UserModel();
                $dispatchers = $userModel->where('role', 'dispatcher')->where('status', 'active')->findAll();
                foreach ($dispatchers as $disp) {
                    $this->notificationModel->insert([
                        'user_id'    => $disp['id'],
                        'title'      => "New Approved Trip for Dispatch: {$request['request_number']}",
                        'message'    => "Trip to {$request['destination']} has been authorized by Admin Division Head. Assign vehicle and driver.",
                        'type'       => 'info',
                        'link'       => '/dispatch',
                        'is_read'    => 0,
                        'created_at' => $now,
                    ]);
                }

                // NFR-3: immutable audit trail
                AuditLogger::log(
                    AuditLogger::VRS_APPROVED,
                    "VRS {$request['request_number']} final approval by {$userName} (Administrative Division Chief).",
                    'trip_request',
                    (int) $id,
                    ['tier' => 2, 'remarks' => $remarks]
                );

                $target = $redirectTo ?: '/approvals';
                return redirect()->to($target)->with('success', "VRS {$request['request_number']} officially authorized! Request is now in the Dispatch Queue.");
            } else {
                // Reject at Tier 2
                $this->requestModel->update($id, [
                    'admin_approver_id' => $userId,
                    'admin_action'      => 'rejected',
                    'admin_action_at'   => $now,
                    'admin_remarks'     => $remarks,
                    'status'            => 'rejected',
                ]);

                // FR-1.5: in-app + email notification on rejection
                // (MailerService::notify() creates the in-app record itself).
                $this->mailer->notify(
                    (int) $request['requestor_id'],
                    "Request Disapproved: {$request['request_number']}",
                    "The Administrative Division Head disapproved your Vehicle Request Slip for travel to "
                    . "{$request['destination']}. Reason: {$remarks}",
                    'danger',
                    "/requests/{$id}"
                );

                // NFR-3: immutable audit trail
                AuditLogger::log(
                    AuditLogger::VRS_REJECTED,
                    "VRS {$request['request_number']} rejected at Tier 2 by {$userName} (Administrative Division Chief). Reason: {$remarks}",
                    'trip_request',
                    (int) $id,
                    ['tier' => 2, 'remarks' => $remarks]
                );

                $target = $redirectTo ?: '/approvals';
                return redirect()->to($target)->with('success', "VRS {$request['request_number']} disapproved.");
            }
        }

        return redirect()->to('/approvals')->with('error', 'Unauthorized approval action.');
    }

    /**
     * FR-1.3 (Emergency Fast-Track Override)
     *
     * The Administrative Division Chief bypasses the multi-stage approvals
     * for urgent deployments (media coverage, press conferences, crisis
     * response). Post-trip documentation is required within 24 hours.
     *
     * Segregation of duties: the Motorpool Dispatcher is the Tier 1 approver
     * AND the officer who allocates vehicles, so the Dispatcher may NOT use
     * this override — otherwise one person could skip the Admin Head's final
     * authority and dispatch the trip alone.
     */
    public function emergencyOverride($id)
    {
        $session  = session();
        $userRole = $session->get('user_role');
        $userId   = $session->get('user_id');
        $userName = $session->get('user_name');

        // BRD FR-1.3: Administrative Division Chief (the super-admin acts
        // with Division Chief authority).
        if (!in_array($userRole, ['approver_admin', 'admin'], true)) {
            return redirect()->to('/approvals')->with('error', 'Emergency Fast-Track Override is restricted to the Administrative Division Head (FR-1.3).');
        }

        $request = $this->requestModel->find($id);
        if (!$request) {
            return redirect()->to('/approvals')->with('error', 'Request not found.');
        }

        if (!in_array($request['status'], ['pending_dispatcher', 'pending_admin'], true)) {
            return redirect()->to('/approvals')->with('error', 'Only requests awaiting approval can be fast-tracked.');
        }

        if ((int) $request['requestor_id'] === (int) $userId) {
            return redirect()->to('/approvals')->with('error', 'You cannot fast-track a Vehicle Request Slip that you filed yourself.');
        }

        $remarks = trim((string) $this->request->getPost('remarks'));
        if ($remarks === '') {
            return redirect()->back()->with('error', 'State the emergency justification for the fast-track override (FR-1.3).');
        }

        $now = date('Y-m-d H:i:s');

        $this->requestModel->update($id, [
            'status'                    => 'approved',
            'is_emergency_override'     => 1,
            'emergency_override_by'     => $userId,
            'emergency_override_at'     => $now,
            'emergency_override_remarks'=> $remarks,
            // FR-1.3: post-trip documentation due within 24 hours
            'post_trip_doc_due'         => date('Y-m-d H:i:s', strtotime('+24 hours')),
            'post_trip_doc_status'      => 'pending',
            // Tier 2 authority is exercised by the acting Division Head
            'admin_approver_id'         => $userId,
            'admin_action'              => 'approved',
            'admin_action_at'           => $now,
            'admin_remarks'             => "EMERGENCY OVERRIDE: {$remarks}",
        ]);

        // Notify the requester — email + in-app (FR-1.3 requires post-trip doc
        // reminder within 24h). MailerService::notify() creates the in-app record.
        $this->mailer->notify(
            (int) $request['requestor_id'],
            "EMERGENCY OVERRIDE — {$request['request_number']} (post-trip docs due in 24h)",
            "Your vehicle request to {$request['destination']} was fast-tracked via the Emergency Dispatch "
            . "override by {$userName}. Justification recorded: {$remarks}. "
            . "MANDATORY: submit post-trip documentation within 24 hours (due "
            . date('M d, Y h:i A', strtotime('+24 hours')) . ").",
            'danger',
            "/requests/{$id}"
        );

        // Notify dispatchers to assign immediately
        $userModel = new UserModel();
        foreach ($userModel->where('role', 'dispatcher')->where('status', 'active')->findAll() as $disp) {
            $this->notificationModel->insert([
                'user_id'    => $disp['id'],
                'title'      => "EMERGENCY DISPATCH QUEUE: {$request['request_number']}",
                'message'    => "Emergency override by {$userName} — assign vehicle & driver immediately for {$request['destination']}.",
                'type'       => 'danger',
                'link'       => '/dispatch',
                'is_read'    => 0,
                'created_at' => $now,
            ]);
        }

        // NFR-3: audit the override (explicitly named in the BRD)
        AuditLogger::log(
            AuditLogger::EMERGENCY_OVERRIDE,
            "EMERGENCY FAST-TRACK OVERRIDE on VRS {$request['request_number']} by {$userName} ({$userRole}). "
            . "Justification: {$remarks}",
            'trip_request',
            (int) $id,
            ['previous_status' => $request['status'], 'role' => $userRole]
        );

        return redirect()->to('/approvals')->with('success', "EMERGENCY OVERRIDE applied to {$request['request_number']}. Multi-stage approvals bypassed — post-trip documentation due within 24 hours.");
    }
}
