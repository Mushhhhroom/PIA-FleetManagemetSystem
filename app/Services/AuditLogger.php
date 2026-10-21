<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

/**
 * NFR-3 System Auditability
 *
 * Records all critical events (approvals, emergency overrides, status
 * changes, login failures) into the immutable `system_audit_logs` table
 * with timestamps and IP addresses, exactly as required by the BRD.
 *
 * Logging must never break a business transaction — failures are swallowed
 * and reported via return value only.
 */
class AuditLogger
{
    public const LOGIN_SUCCESS      = 'login_success';
    public const LOGIN_FAILURE      = 'login_failure';
    public const LOGOUT             = 'logout';
    public const VRS_SUBMITTED      = 'vrs_submitted';
    public const VRS_APPROVED       = 'vrs_approved';
    public const VRS_REJECTED       = 'vrs_rejected';
    public const VRS_ESCALATED      = 'vrs_escalated';
    public const EMERGENCY_OVERRIDE = 'emergency_override';
    public const VRS_DISPATCHED     = 'vrs_dispatched';
    public const STATUS_CHANGE      = 'status_change';
    public const SAFETY_CHECK_FAIL  = 'safety_check_failed';
    public const PIR_CREATED        = 'pir_created';
    public const PIR_RELEASED       = 'pir_released';
    public const RFID_RELOAD        = 'rfid_reload';
    public const USER_MFA_CHANGE    = 'mfa_setting_changed';
    public const REPORT_EXPORT      = 'report_export';
    public const USER_ACCOUNT_CREATED  = 'user_account_created';
    public const USER_ACCOUNT_UPDATED  = 'user_account_updated';
    public const USER_ACCOUNT_TOGGLED  = 'user_account_status_changed';
    public const ADMIN_ACTION          = 'admin_action';

    /**
     * Write an immutable audit entry. Returns true when persisted.
     *
     * @param array $context Optional structured context (JSON-encoded).
     */
    public static function log(
        string $eventType,
        string $description,
        ?string $entityType = null,
        ?int $entityId = null,
        array $context = []
    ): bool {
        try {
            $session = session();
            $request = service('request');

            $userId   = $session ? $session->get('user_id') : null;
            $userName = $session ? $session->get('user_name') : null;
            $userRole = $session ? $session->get('user_role') : null;

            // CLI (spark commands / scheduled watchers) has no HTTP request
            // context and no signed-in user — attribute the entry to the
            // scheduled task itself instead of dropping it.
            $ip        = null;
            $userAgent = null;

            if (is_cli()) {
                if ($userId === null) {
                    $userName = 'SYSTEM (Scheduled Task)';
                    $userRole = 'system';
                }
            } elseif ($request instanceof \CodeIgniter\HTTP\IncomingRequest) {
                // Recorded verbatim for forensics (loopback addresses included).
                $ip        = $request->getIPAddress();
                $userAgent = substr((string) $request->getUserAgent()->getAgentString(), 0, 255);
            } elseif ($request !== null && method_exists($request, 'getIPAddress')) {
                $ip = $request->getIPAddress();
            }

            $db = \Config\Database::connect();
            $db->table('system_audit_logs')->insert([
                'user_id'      => $userId !== null ? (int) $userId : null,
                'user_name'    => $userName,
                'user_role'    => $userRole,
                'event_type'   => $eventType,
                'entity_type'  => $entityType,
                'entity_id'    => $entityId !== null ? (int) $entityId : null,
                'description'  => $description,
                'context'      => !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : null,
                'ip_address'   => $ip,
                'user_agent'   => $userAgent,
                'created_at'   => date('Y-m-d H:i:s'),
            ]);

            return true;
        } catch (\Throwable $e) {
            log_message('error', 'AuditLogger failed: {msg}', ['msg' => $e->getMessage()]);
            return false;
        }
    }
}
