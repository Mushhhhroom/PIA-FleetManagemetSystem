<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Services\AuditLogger;
use App\Services\TotpService;

/**
 * Authentication hardened per BRD (NFR-2 / technology stack):
 *
 *  - Argon2id password hashing (transparent rehash of legacy hashes)
 *  - Optional TOTP MFA (RFC 6238) challenge before the session is issued
 *  - Immutable audit logging of login successes and failures (NFR-3)
 *  - Session-fixation regeneration and brute-force throttling retained
 */
class AuthController extends BaseController
{
    /**
     * Password algorithm: Argon2id when compiled into PHP, otherwise the
     * strongest available default (per BRD technology stack).
     *
     * NOTE: PASSWORD_ARGON2ID is an int, PASSWORD_DEFAULT is a string —
     * both must be accepted by the return type.
     *
     * @return int|string
     */
    public static function hashAlgorithm(): int|string
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
    }

    public function login()
    {
        if (session()->get('logged_in')) {
            return redirect()->to('/');
        }
        // A pending MFA challenge must be completed first
        if (session()->get('mfa_pending_user_id')) {
            return redirect()->to('/login/mfa');
        }
        return view('auth/login');
    }

    public function authenticate()
    {
        $session = session();
        $email = trim((string) $this->request->getPost('email'));
        $password = (string) $this->request->getPost('password');

        // Security Layer: Brute-Force Rate Limiting (25 attempts per minute per IP)
        $throttler = \Config\Services::throttler();
        $ip = $this->request->getIPAddress();
        $throttleKey = 'login_attempt_' . md5($ip);

        if ($throttler->check($throttleKey, 25, MINUTE) === false) {
            $remaining = $throttler->getTokenTime();
            return redirect()->back()->with('error', "Security Alert: Too many login attempts from this network. Please wait {$remaining} seconds before attempting to sign in again.");
        }

        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();

        // NFR-3: record failed logins (unknown account or bad password)
        if (!$user || !password_verify($password, $user['password'])) {
            AuditLogger::log(
                AuditLogger::LOGIN_FAILURE,
                'Failed login attempt' . ($user ? " for user #{$user['id']} ({$email})" : " for unknown account ({$email})"),
                'user',
                $user ? (int) $user['id'] : null,
                ['email' => $email]
            );
            return redirect()->back()->with('error', 'Invalid email or password. Please verify your credentials.');
        }

        if ($user['status'] !== 'active') {
            AuditLogger::log(
                AuditLogger::LOGIN_FAILURE,
                "Login blocked — deactivated account {$email}",
                'user',
                (int) $user['id'],
                ['email' => $email]
            );
            return redirect()->back()->with('error', 'Your account has been deactivated. Please contact the system administrator.');
        }

        // NFR-2: transparently upgrade legacy hashes to Argon2id
        if (password_needs_rehash($user['password'], self::hashAlgorithm())) {
            $userModel->update($user['id'], ['password' => password_hash($password, self::hashAlgorithm())]);
        }

        // NFR-2: optional TOTP MFA — challenge before issuing the session
        if (!empty($user['totp_enabled']) && !empty($user['totp_secret'])) {
            $session->regenerate(true);
            $session->set([
                'mfa_pending_user_id' => $user['id'],
                'mfa_pending_email'   => $user['email'],
            ]);
            return redirect()->to('/login/mfa')->with('info', 'Enter the 6-digit code from your authenticator app.');
        }

        return $this->issueSession($user, $ip);
    }

    /**
     * MFA challenge screen (GET).
     */
    public function mfaChallenge()
    {
        $session = session();
        if ($session->get('logged_in')) {
            return redirect()->to('/');
        }
        if (!$session->get('mfa_pending_user_id')) {
            return redirect()->to('/login');
        }
        return view('auth/mfa_challenge');
    }

    /**
     * MFA challenge verification (POST) — completes the login.
     */
    public function mfaVerify()
    {
        $session = session();
        $pendingId = (int) $session->get('mfa_pending_user_id');
        if (!$pendingId) {
            return redirect()->to('/login');
        }

        $code = trim((string) $this->request->getPost('code'));
        $userModel = new UserModel();
        $user = $userModel->find($pendingId);

        if (!$user) {
            $session->remove(['mfa_pending_user_id', 'mfa_pending_email']);
            return redirect()->to('/login')->with('error', 'MFA session expired. Please sign in again.');
        }

        if (!TotpService::verify($code, (string) $user['totp_secret'])) {
            AuditLogger::log(
                AuditLogger::LOGIN_FAILURE,
                "Failed TOTP verification for user #{$user['id']} ({$user['email']})",
                'user',
                (int) $user['id']
            );
            return redirect()->back()->with('error', 'Invalid or expired authentication code. Please try again.');
        }

        $session->remove(['mfa_pending_user_id', 'mfa_pending_email']);
        return $this->issueSession($user, $this->request->getIPAddress());
    }

    /**
     * Establish the authenticated session after password (+ MFA) checks.
     */
    protected function issueSession(array $user, string $ip)
    {
        $session = session();

        // Security Layer: Prevent Session Fixation attacks
        $session->regenerate(true);

        $session->set([
            'user_id'   => $user['id'],
            'user_name' => $user['name'],
            'user_email'=> $user['email'],
            'user_role' => $user['role'],
            'logged_in' => true,
        ]);

        AuditLogger::log(
            AuditLogger::LOGIN_SUCCESS,
            "Successful login for {$user['email']} ({$user['role']})",
            'user',
            (int) $user['id']
        );

        $targetRoute = match ($user['role']) {
            'driver'                         => '/driver/trips',
            'guard'                          => '/gate',
            'requestor'                      => '/requests',
            'approver_admin'                 => '/approvals',
            'dispatcher'                     => '/dispatch',
            'auditor'                        => '/audit',
            'maintenance'                    => '/maintenance',
            default                          => '/',
        };

        return redirect()->to($targetRoute)->with('success', 'Welcome, ' . esc($user['name']) . '! Security credentials verified.');
    }

    public function logout()
    {
        if (session()->get('logged_in')) {
            AuditLogger::log(AuditLogger::LOGOUT, 'User signed out.', 'user', (int) session()->get('user_id'));
        }
        session()->destroy();
        if (isset($_COOKIE['ci_session'])) {
            unset($_COOKIE['ci_session']);
            setcookie('ci_session', '', time() - 3600, '/');
        }
        return redirect()->to('/login')->with('success', 'You have been successfully logged out.');
    }

    // ------------------------------------------------------------------
    // Optional TOTP MFA enrollment (account hardening, NFR-2)
    // ------------------------------------------------------------------

    /**
     * Show the MFA setup screen (generate / confirm / disable).
     */
    public function mfaSetup()
    {
        $session = session();
        $userId = (int) $session->get('user_id');
        if (!$userId) {
            return redirect()->to('/login');
        }

        $userModel = new UserModel();
        $user = $userModel->find($userId);

        $data = [
            'title'   => 'Multi-Factor Authentication (TOTP)',
            'enabled' => !empty($user['totp_enabled']),
            'secret'  => $user['totp_secret'] ?? TotpService::generateSecret(),
            'uri'     => TotpService::provisioningUri(
                $user['totp_secret'] ?? '',
                $user['email'],
                'PIA-AFMS'
            ),
        ];

        // Never persist a freshly generated secret until confirmed
        if (empty($user['totp_secret'])) {
            $data['uri'] = TotpService::provisioningUri($data['secret'], $user['email'], 'PIA-AFMS');
        }

        return view('auth/mfa_setup', $data);
    }

    /**
     * Confirm a TOTP code and enable MFA for the account.
     */
    public function mfaEnable()
    {
        $session = session();
        $userId = (int) $session->get('user_id');
        if (!$userId) {
            return redirect()->to('/login');
        }

        $userModel = new UserModel();
        $user = $userModel->find($userId);

        $secret = trim((string) $this->request->getPost('secret'));
        $code   = trim((string) $this->request->getPost('code'));

        if ($secret === '' || !TotpService::verify($code, $secret)) {
            return redirect()->back()->with('error', 'The authentication code does not match the shared secret. MFA was not enabled.');
        }

        $userModel->update($userId, [
            'totp_secret'  => $secret,
            'totp_enabled' => 1,
        ]);

        AuditLogger::log(
            AuditLogger::USER_MFA_CHANGE,
            'TOTP multi-factor authentication enabled.',
            'user',
            $userId
        );

        return redirect()->to('/mfa/setup')->with('success', 'Multi-factor authentication is now ENABLED for your account.');
    }

    /**
     * Disable TOTP MFA for the account.
     */
    public function mfaDisable()
    {
        $session = session();
        $userId = (int) $session->get('user_id');
        if (!$userId) {
            return redirect()->to('/login');
        }

        $userModel = new UserModel();
        $userModel->update($userId, ['totp_enabled' => 0]);

        AuditLogger::log(
            AuditLogger::USER_MFA_CHANGE,
            'TOTP multi-factor authentication disabled.',
            'user',
            $userId
        );

        return redirect()->to('/mfa/setup')->with('success', 'Multi-factor authentication disabled.');
    }
}
