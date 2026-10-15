<?php

namespace App\Services;

use App\Models\TripRequestModel;
use App\Models\NotificationModel;
use App\Models\UserModel;
use Config\Database;

class SlaService
{
    /** BRD FR-1.4: Initial Approver must act within 4 hours or the request
     *  is auto-escalated to the Administrative Division Chief. */
    public const ESCALATION_HOURS = 4;

    /** Backstop: a request still unactioned after this long expires (BR-02). */
    public const FINAL_SLA_HOURS = 24;

    protected TripRequestModel $requestModel;
    protected NotificationModel $notificationModel;
    protected $db;

    public function __construct()
    {
        $this->requestModel      = new TripRequestModel();
        $this->notificationModel = new NotificationModel();
        $this->db                = Database::connect();
    }

    /**
     * FR-1.4 (Approval SLA & Escalation): when an Initial Approver has not
     * acted on a pending VRS within 4 hours, issue an email notification
     * and auto-escalate the request to the Administrative Division Chief.
     *
     * @return string[] list of escalated request numbers
     */
    public function checkAndEscalateRequests(): array
    {
        $now = date('Y-m-d H:i:s');

        $dueList = $this->db->table('trip_requests')
            ->where('status', 'pending_dispatcher')
            ->where('escalated_at IS NULL')
            ->where('sla_deadline IS NOT NULL')
            ->where('sla_deadline <', $now)
            ->get()->getResultArray();

        $escalated = [];
        foreach ($dueList as $req) {
            $userModel = new UserModel();
            $adminHead = $userModel->where('role', 'approver_admin')->where('status', 'active')->first();

            $this->requestModel->update($req['id'], [
                'escalated_at'      => $now,
                'admin_approver_id' => $adminHead ? $adminHead['id'] : null,
                'status'            => 'pending_admin',
                // New 24-hour window for the escalated final action
                'sla_deadline'      => date('Y-m-d H:i:s', strtotime('+' . self::FINAL_SLA_HOURS . ' hours')),
                'is_sla_breached'   => 1,
            ]);

            $mailer = new MailerService();

            // Email + in-app notification to the Administrative Division Chief (FR-1.4)
            if ($adminHead) {
                $mailer->notify(
                    (int) $adminHead['id'],
                    "ESCALATED: VRS {$req['request_number']} awaits your action",
                    "The Motorpool Dispatcher did not act on vehicle request {$req['request_number']} to "
                    . "{$req['destination']} within the mandatory " . self::ESCALATION_HOURS . "-hour window. "
                    . "Per BRD FR-1.4 the request has been auto-escalated to the Administrative Division Chief.",
                    'warning',
                    '/approvals'
                );
            }

            // Email + in-app notification to the requestor (FR-1.4 transparency)
            $mailer->notify(
                (int) $req['requestor_id'],
                "VRS {$req['request_number']} escalated for faster action",
                "Your vehicle request to {$req['destination']} was not actioned within "
                . self::ESCALATION_HOURS . " hours and has been escalated to the Administrative Division Chief.",
                'warning',
                "/requests/{$req['id']}",
                false // requestor already gets in-app; avoid duplicate email
            );

            AuditLogger::log(
                AuditLogger::VRS_ESCALATED,
                "VRS {$req['request_number']} auto-escalated to Administrative Division Chief after "
                . self::ESCALATION_HOURS . "h SLA breach (FR-1.4).",
                'trip_request',
                (int) $req['id'],
                ['previous_status' => 'pending_dispatcher', 'new_status' => 'pending_admin']
            );

            $escalated[] = $req['request_number'];
        }

        return $escalated;
    }

    /**
     * Backstop: expire requests never actioned within the final 24-hour
     * window (retained BR-02 behaviour on the escalated/final tier).
     *
     * @return string[] list of expired request numbers
     */
    public function checkAndExpireRequests(): array
    {
        $now = date('Y-m-d H:i:s');
        $expiredList = $this->db->table('trip_requests')
            ->whereIn('status', ['pending_dispatcher', 'pending_admin'])
            ->where('sla_deadline <', $now)
            ->where('sla_deadline IS NOT NULL')
            ->get()->getResultArray();

        $processed = [];
        foreach ($expiredList as $req) {
            // Never expire something the escalation pass can still handle
            if ($req['status'] === 'pending_dispatcher' && empty($req['escalated_at'])) {
                continue;
            }

            $this->requestModel->update($req['id'], [
                'status'          => 'expired',
                'is_sla_breached' => 1,
            ]);

            $mailer = new MailerService();
            $mailer->notify(
                (int) $req['requestor_id'],
                "Request Expired: {$req['request_number']}",
                "Your vehicle request to {$req['destination']} has expired because approval was not completed "
                . "within the mandatory " . self::FINAL_SLA_HOURS . "-hour SLA window.",
                'danger',
                "/requests/{$req['id']}",
                false
            );

            AuditLogger::log(
                AuditLogger::STATUS_CHANGE,
                "VRS {$req['request_number']} auto-expired after final 24h SLA window.",
                'trip_request',
                (int) $req['id'],
                ['new_status' => 'expired']
            );

            $processed[] = $req['request_number'];
        }

        return $processed;
    }

    /**
     * FR-1.3: track overdue post-trip documentation for emergency overrides.
     *
     * @return string[] request numbers now flagged overdue
     */
    public function checkPostTripDocumentation(): array
    {
        $now = date('Y-m-d H:i:s');

        $overdue = $this->db->table('trip_requests')
            ->where('is_emergency_override', 1)
            ->where('post_trip_doc_status', 'pending')
            ->where('post_trip_doc_due IS NOT NULL')
            ->where('post_trip_doc_due <', $now)
            ->get()->getResultArray();

        $flagged = [];
        foreach ($overdue as $req) {
            $this->requestModel->update($req['id'], ['post_trip_doc_status' => 'overdue']);

            $mailer = new MailerService();
            $mailer->notify(
                (int) $req['requestor_id'],
                "OVERDUE: Post-trip documentation for {$req['request_number']}",
                "This request used the Emergency Fast-Track Override. Post-trip documentation was due by "
                . date('M d, Y h:i A', strtotime($req['post_trip_doc_due'])) . " and is now overdue (FR-1.3).",
                'danger',
                "/requests/{$req['id']}"
            );

            $flagged[] = $req['request_number'];
        }

        return $flagged;
    }

    /**
     * Format remaining SLA countdown string
     */
    public static function formatSlaCountdown(?string $slaDeadline): array
    {
        if (!$slaDeadline) {
            return ['text' => 'No SLA', 'class' => 'badge-secondary', 'is_urgent' => false, 'seconds' => 0];
        }

        $remainingSecs = strtotime($slaDeadline) - time();

        if ($remainingSecs <= 0) {
            $hoursAgo = abs(round($remainingSecs / 3600, 1));
            return [
                'text'      => "Breached {$hoursAgo}h ago",
                'class'     => 'badge-danger text-white bg-danger',
                'is_urgent' => true,
                'is_breached' => true,
                'seconds'   => $remainingSecs,
            ];
        }

        $hours = floor($remainingSecs / 3600);
        $minutes = floor(($remainingSecs % 3600) / 60);

        // FR-1.4: red zone inside the 4-hour escalation window
        if ($hours < 4) {
            $badgeClass = 'badge-danger bg-danger text-white animate-pulse';
            $isUrgent = true;
        } elseif ($hours < 12) {
            $badgeClass = 'badge-warning bg-warning text-dark';
            $isUrgent = true;
        } else {
            $badgeClass = 'badge-info bg-primary text-white';
            $isUrgent = false;
        }

        return [
            'text'        => "{$hours}h {$minutes}m left",
            'class'       => $badgeClass,
            'is_urgent'   => $isUrgent,
            'is_breached' => false,
            'seconds'     => $remainingSecs,
        ];
    }
}
