<?php

namespace App\Controllers;

use App\Models\OfficeModel;
use App\Models\UserModel;
use App\Services\AuditLogger;

/**
 * User Account Administration — SUPER ADMIN ONLY.
 *
 * Lets the Super Admin (the `admin` role) provision and govern login
 * accounts for every RBAC role in the system (requestors, dispatchers,
 * approvers, drivers, guards, auditors, maintenance, etc.).
 *
 * Security design:
 *  - Route group is locked behind the `role:admin` RBAC filter, and every
 *    action re-asserts a Super Admin session guard (defense in depth).
 *  - Passwords are hashed with the system-wide algorithm (Argon2id when
 *    compiled in, otherwise the strongest available default) — plaintext
 *    credentials are never stored, echoed, or written to the audit trail.
 *  - Role values are constrained to a whitelist that mirrors the
 *    `users.role` ENUM — no arbitrary role injection.
 *  - Self-lockout is blocked (the Super Admin cannot deactivate or demote
 *    their own account) and the last active Super Admin can never be
 *    disabled, so the system can always be administered.
 *  - Every provisioning and status action is written to the immutable
 *    NFR-3 system audit trail with the acting administrator's identity.
 */
class UserController extends BaseController
{
    protected UserModel $userModel;
    protected OfficeModel $officeModel;

    /**
     * Whitelist of assignable roles => human labels.
     * Must mirror the users.role ENUM in the database.
     */
    public const ASSIGNABLE_ROLES = [
        'admin'          => 'Super Admin / Director',
        'approver_admin' => 'Tier 2 Approver (Admin Head)',
        'dispatcher'     => 'Motorpool Dispatcher (Tier 1 Approver & Asset Allocation)',
        'maintenance'    => 'Safety & PMS Technician',
        'driver'         => 'Official Driver',
        'guard'          => 'Gate Security Officer',
        'auditor'        => 'COA Resident Auditor',
        'requestor'      => 'Staff / Requestor',
    ];

    /** Minimum password strength enforced on provisioning and resets. */
    public const MIN_PASSWORD_LENGTH = 8;

    public function __construct()
    {
        $this->userModel  = new UserModel();
        $this->officeModel = new OfficeModel();
    }

    /**
     * Defense in depth — the route filter already restricts these routes to
     * the admin role; this re-asserts it so a future route change can never
     * silently expose account administration.
     */
    protected function guardSuperAdmin()
    {
        if (session()->get('user_role') !== 'admin') {
            return redirect()->to('/')
                ->with('error', 'Access denied: user account administration is restricted to the Super Admin.');
        }

        return null;
    }

    // ------------------------------------------------------------------
    // Account directory
    // ------------------------------------------------------------------

    /** Account directory with role/status filters and provisioning summary. */
    public function index()
    {
        if ($denied = $this->guardSuperAdmin()) {
            return $denied;
        }

        $q            = trim((string) $this->request->getGet('q'));
        $roleFilter   = (string) $this->request->getGet('role');
        $statusFilter = (string) $this->request->getGet('status');

        $builder = $this->userModel->builder();
        $builder->select('users.*, offices.office_name, offices.office_code')
            ->join('offices', 'offices.id = users.office_id', 'left')
            ->orderBy('users.name', 'ASC');

        if ($q !== '') {
            $builder->groupStart()->like('users.name', $q)->orLike('users.email', $q)->groupEnd();
        }
        if (array_key_exists($roleFilter, self::ASSIGNABLE_ROLES)) {
            $builder->where('users.role', $roleFilter);
        }
        if (in_array($statusFilter, ['active', 'inactive'], true)) {
            $builder->where('users.status', $statusFilter);
        }

        $users = $builder->get()->getResultArray();

        // Summary tiles
        $all     = $this->userModel->findAll();
        $summary = [
            'total'    => count($all),
            'active'   => count(array_filter($all, static fn ($u) => $u['status'] === 'active')),
            'inactive' => count(array_filter($all, static fn ($u) => $u['status'] !== 'active')),
            'roles'    => count(self::ASSIGNABLE_ROLES),
        ];

        return view('users/index', [
            'title'       => 'User Account Administration',
            'users'       => $users,
            'summary'     => $summary,
            'roles'       => self::ASSIGNABLE_ROLES,
            'q'           => $q,
            'roleFilter'  => $roleFilter,
            'statusFilter'=> $statusFilter,
        ]);
    }

    // ------------------------------------------------------------------
    // Provision a new account
    // ------------------------------------------------------------------

    /** New account form. */
    public function create()
    {
        if ($denied = $this->guardSuperAdmin()) {
            return $denied;
        }

        return view('users/form', [
            'title'   => 'Provision User Account',
            'user'    => null,
            'roles'   => self::ASSIGNABLE_ROLES,
            'offices' => $this->officeModel->orderBy('office_name', 'ASC')->findAll(),
        ]);
    }

    /** Validate and persist a newly provisioned account. */
    public function store()
    {
        if ($denied = $this->guardSuperAdmin()) {
            return $denied;
        }

        $name   = trim((string) $this->request->getPost('name'));
        $email  = strtolower(trim((string) $this->request->getPost('email')));
        $role   = (string) $this->request->getPost('role');
        $status = (string) $this->request->getPost('status') ?: 'active';

        if ($name === '') {
            return redirect()->back()->withInput()->with('error', 'Full name is required.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('error', 'A valid e-mail address (login identifier) is required.');
        }
        if ($this->userModel->where('email', $email)->first()) {
            return redirect()->back()->withInput()->with('error', 'An account with this e-mail address already exists. Sign-in identifiers must be unique.');
        }
        if (!array_key_exists($role, self::ASSIGNABLE_ROLES)) {
            return redirect()->back()->withInput()->with('error', 'Please select a valid account role.');
        }
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $password = (string) $this->request->getPost('password');
        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            return redirect()->back()->withInput()->with('error', 'Password must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters long.');
        }

        $userId = $this->userModel->insert([
            'name'         => $name,
            'email'        => $email,
            'password'     => password_hash($password, AuthController::hashAlgorithm()),
            'role'         => $role,
            'office_id'    => (int) $this->request->getPost('office_id') ?: null,
            'designation'  => trim((string) $this->request->getPost('designation')) ?: null,
            'phone'        => trim((string) $this->request->getPost('phone')) ?: null,
            'status'       => $status,
            'totp_enabled' => 0,
        ]);

        AuditLogger::log(
            AuditLogger::USER_ACCOUNT_CREATED,
            "Super Admin provisioned a new {$role} account for {$name} ({$email}) — status: {$status}.",
            'user',
            (int) $userId,
            ['role' => $role, 'email' => $email, 'status' => $status]
        );

        return redirect()->to('/users')->with('success', "Account for {$name} ({$email}) has been provisioned as {$this->roleLabel($role)}.");
    }

    // ------------------------------------------------------------------
    // Edit an existing account
    // ------------------------------------------------------------------

    /** Edit account form (password reset optional). */
    public function edit($id)
    {
        if ($denied = $this->guardSuperAdmin()) {
            return $denied;
        }

        $user = $this->userModel->find((int) $id);
        if (!$user) {
            return redirect()->to('/users')->with('error', 'User account not found.');
        }

        return view('users/form', [
            'title'   => 'Edit User Account',
            'user'    => $user,
            'roles'   => self::ASSIGNABLE_ROLES,
            'offices' => $this->officeModel->orderBy('office_name', 'ASC')->findAll(),
        ]);
    }

    /** Update account details, role, status and (optionally) the password. */
    public function update($id)
    {
        if ($denied = $this->guardSuperAdmin()) {
            return $denied;
        }

        $id   = (int) $id;
        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->to('/users')->with('error', 'User account not found.');
        }

        $name   = trim((string) $this->request->getPost('name'));
        $email  = strtolower(trim((string) $this->request->getPost('email')));
        $role   = (string) $this->request->getPost('role');
        $status = (string) $this->request->getPost('status');

        if ($name === '') {
            return redirect()->back()->withInput()->with('error', 'Full name is required.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('error', 'A valid e-mail address (login identifier) is required.');
        }
        $emailOwner = $this->userModel->where('email', $email)->first();
        if ($emailOwner && (int) $emailOwner['id'] !== $id) {
            return redirect()->back()->withInput()->with('error', 'Another account already uses this e-mail address. Sign-in identifiers must be unique.');
        }
        if (!array_key_exists($role, self::ASSIGNABLE_ROLES)) {
            return redirect()->back()->withInput()->with('error', 'Please select a valid account role.');
        }
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = $user['status'];
        }

        // --- Security guards -------------------------------------------
        $selfId = (int) session()->get('user_id');
        if ($id === $selfId) {
            if ($role !== 'admin') {
                return redirect()->back()->withInput()->with('error', 'You cannot remove your own Super Admin role — ask another Super Admin to do it.');
            }
            if ($status !== 'active') {
                return redirect()->back()->withInput()->with('error', 'You cannot deactivate your own account while signed in.');
            }
        }
        if ($user['role'] === 'admin' && ($role !== 'admin' || $status !== 'active')
            && !$this->anotherActiveAdminExists($id)) {
            return redirect()->back()->withInput()->with('error', 'Blocked: this is the last active Super Admin account. Promote another account to Super Admin first — the system must always remain administrable.');
        }

        $password = (string) $this->request->getPost('password');
        if ($password !== '' && strlen($password) < self::MIN_PASSWORD_LENGTH) {
            return redirect()->back()->withInput()->with('error', 'New password must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters long.');
        }

        $payload = [
            'name'        => $name,
            'email'       => $email,
            'role'        => $role,
            'status'      => $status,
            'office_id'   => (int) $this->request->getPost('office_id') ?: null,
            'designation' => trim((string) $this->request->getPost('designation')) ?: null,
            'phone'       => trim((string) $this->request->getPost('phone')) ?: null,
        ];

        // Optional credential reset — hashed, never logged in plaintext
        $passwordReset = false;
        if ($password !== '') {
            $payload['password']  = password_hash($password, AuthController::hashAlgorithm());
            $payload['totp_enabled'] = 0; // new credentials require re-enrollment of MFA
            $passwordReset = true;
        }

        $this->userModel->update($id, $payload);

        $changes = [];
        foreach (['name', 'email', 'role', 'status', 'office_id', 'designation', 'phone'] as $field) {
            if ((string) ($user[$field] ?? '') !== (string) ($payload[$field] ?? '')) {
                $changes[] = $field;
            }
        }

        AuditLogger::log(
            AuditLogger::USER_ACCOUNT_UPDATED,
            "Super Admin updated account #{$id} ({$email})"
            . ($changes ? ' — changed: ' . implode(', ', $changes) : '')
            . ($passwordReset ? ' — password reset issued' : '') . '.',
            'user',
            $id,
            ['changed' => $changes, 'password_reset' => $passwordReset]
        );

        $msg = "Account for {$name} has been updated.";
        if ($passwordReset) {
            $msg .= ' Password was reset and two-factor enrollment was cleared — the user must re-enroll MFA.';
        }

        return redirect()->to('/users')->with('success', $msg);
    }

    // ------------------------------------------------------------------
    // Quick activate / deactivate
    // ------------------------------------------------------------------

    /** Toggle account availability (deactivation blocks further sign-ins). */
    public function toggle($id)
    {
        if ($denied = $this->guardSuperAdmin()) {
            return $denied;
        }

        $id   = (int) $id;
        $user = $this->userModel->find($id);
        if (!$user) {
            return redirect()->to('/users')->with('error', 'User account not found.');
        }

        $newStatus = $user['status'] === 'active' ? 'inactive' : 'active';
        $selfId    = (int) session()->get('user_id');

        if ($id === $selfId && $newStatus === 'inactive') {
            return redirect()->back()->with('error', 'You cannot deactivate your own account while signed in.');
        }
        if ($user['role'] === 'admin' && $newStatus === 'inactive' && !$this->anotherActiveAdminExists($id)) {
            return redirect()->back()->with('error', 'Blocked: this is the last active Super Admin account. Promote another account to Super Admin first.');
        }

        $this->userModel->update($id, ['status' => $newStatus]);

        $verb = $newStatus === 'active' ? 'reactivated' : 'deactivated';

        AuditLogger::log(
            AuditLogger::USER_ACCOUNT_TOGGLED,
            "Super Admin {$verb} account #{$id} ({$user['email']}, {$user['role']}).",
            'user',
            $id,
            ['status' => $newStatus]
        );

        return redirect()->back()->with(
            'success',
            "Account for {$user['name']} is now " . ($newStatus === 'active' ? 'ACTIVE — the user can sign in again.' : 'DEACTIVATED — sign-in is blocked.') 
        );
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /** True when another ACTIVE Super Admin account exists besides $excludeId. */
    protected function anotherActiveAdminExists(int $excludeId): bool
    {
        return $this->userModel
                   ->where('role', 'admin')
                   ->where('status', 'active')
                   ->where('id !=', $excludeId)
                   ->countAllResults() > 0;
    }

    protected function roleLabel(string $role): string
    {
        return self::ASSIGNABLE_ROLES[$role] ?? ucfirst($role);
    }

    /**
     * Flowchart and System Navigations view for Super Admin & Motorpool Dispatcher.
     */
    public function flowchart()
    {
        $role = session()->get('user_role');
        if (!in_array($role, ['admin', 'dispatcher', 'approver_admin'], true)) {
            return redirect()->to('/')->with('error', 'Access Restricted: Flowchart viewing is limited to Administrators, Division Heads, and Dispatchers.');
        }

        return view('admin/flowchart', [
            'title' => 'System Flowchart & Navigation',
        ]);
    }
}
