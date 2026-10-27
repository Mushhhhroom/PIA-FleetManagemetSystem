<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ModuleControlModel;
use App\Models\UserModel;
use App\Services\AuditLogger;
use App\Services\ModuleControlService;

/**
 * Super Admin Module & Menu Controls
 *
 * Powerful feature management console allowing Super Admin to:
 *  - Globally toggle modules/functions on or off for instant maintenance & bug containment.
 *  - Restrict specific functions per user account role (e.g. disable requests for requestors during audit).
 *  - Broadcast informative maintenance reasons so users understand why a module is temporarily unavailable.
 *  - Instantly update menu visibility across all accounts without requiring code deployments.
 */
class ModuleControlController extends BaseController
{
    protected ModuleControlModel $moduleModel;

    public function __construct()
    {
        $this->moduleModel = new ModuleControlModel();
        $this->moduleModel->ensureInitialized();
    }

    /**
     * Defense-in-depth: Ensure only Super Admin can access module controls.
     */
    protected function guardSuperAdmin()
    {
        if (session()->get('user_role') !== 'admin') {
            return redirect()->to('/')
                ->with('error', 'Access denied: System Module Controls are strictly restricted to the Super Admin.');
        }
        return null;
    }

    /**
     * Render the Module & Menu Control Dashboard.
     */
    public function index()
    {
        if ($guard = $this->guardSuperAdmin()) {
            return $guard;
        }

        $allModules = ModuleControlService::getAllModules();

        // Categorize modules
        $categories = [];
        $totalModules = count($allModules);
        $activeCount = 0;
        $offlineCount = 0;
        $roleRestrictedCount = 0;

        foreach ($allModules as $mod) {
            $cat = $mod['category'] ?? 'General';
            $categories[$cat][] = $mod;

            if ((int)$mod['is_enabled'] === 1) {
                $activeCount++;
            } else {
                $offlineCount++;
            }

            if (!empty($mod['disabled_roles_array'])) {
                $roleRestrictedCount++;
            }
        }

        // Available RBAC roles in the system
        $systemRoles = [
            'requestor'      => 'Staff / Requestor',
            'dispatcher'     => 'Motorpool Dispatcher',
            'approver_admin' => 'Admin Head (Approver)',
            'driver'         => 'Official Driver',
            'guard'          => 'Gate Security Guard',
            'auditor'        => 'COA Auditor',
            'maintenance'    => 'Mechanic / PMS Tech',
        ];

        $data = [
            'title'               => 'System Module & Menu Controls',
            'categories'          => $categories,
            'allModules'          => $allModules,
            'systemRoles'         => $systemRoles,
            'totalModules'        => $totalModules,
            'activeCount'         => $activeCount,
            'offlineCount'        => $offlineCount,
            'roleRestrictedCount' => $roleRestrictedCount,
        ];

        return view('admin/modules/index', $data);
    }

    /**
     * AJAX: Toggle global status of a module (Activate / Deactivate).
     */
    public function toggle()
    {
        if ($this->guardSuperAdmin()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(403);
        }

        $moduleKey = trim((string)$this->request->getPost('module_key'));
        $isEnabled = (int)$this->request->getPost('is_enabled') === 1 ? 1 : 0;
        $reason    = trim((string)$this->request->getPost('maintenance_reason'));

        $record = $this->moduleModel->where('module_key', $moduleKey)->first();
        if (!$record) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Module not found.'])->setStatusCode(404);
        }

        $updateData = [
            'is_enabled' => $isEnabled,
            'updated_by' => (int)session()->get('user_id'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if (!empty($reason)) {
            $updateData['maintenance_reason'] = $reason;
        }

        $this->moduleModel->update($record['id'], $updateData);
        ModuleControlService::clearCache();

        $actionWord = $isEnabled ? 'ACTIVATED' : 'DEACTIVATED';
        AuditLogger::log(
            AuditLogger::ADMIN_ACTION,
            "Super Admin {$actionWord} system module '{$record['module_name']}' ({$moduleKey}). Status: " . ($isEnabled ? 'Online' : 'Offline / In-Maintenance'),
            'system_module_controls',
            (int)$record['id'],
            ['module_key' => $moduleKey, 'is_enabled' => $isEnabled, 'reason' => $reason]
        );

        return $this->response->setJSON([
            'status'     => 'success',
            'message'    => "Module '{$record['module_name']}' successfully {$actionWord}.",
            'module_key' => $moduleKey,
            'is_enabled' => $isEnabled,
            'csrf_hash'  => csrf_hash(),
        ]);
    }

    /**
     * AJAX: Toggle role restriction for a module.
     */
    public function toggleRole()
    {
        if ($this->guardSuperAdmin()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(403);
        }

        $moduleKey = trim((string)$this->request->getPost('module_key'));
        $targetRole = trim((string)$this->request->getPost('role'));
        $allowed = (int)$this->request->getPost('allowed') === 1;

        $record = $this->moduleModel->where('module_key', $moduleKey)->first();
        if (!$record) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Module not found.'])->setStatusCode(404);
        }

        $disabledRoles = [];
        if (!empty($record['disabled_roles'])) {
            $decoded = json_decode($record['disabled_roles'], true);
            $disabledRoles = is_array($decoded) ? $decoded : [];
        }

        if ($allowed) {
            // Remove from disabled list
            $disabledRoles = array_values(array_filter($disabledRoles, fn($r) => $r !== $targetRole));
        } else {
            // Add to disabled list
            if (!in_array($targetRole, $disabledRoles, true)) {
                $disabledRoles[] = $targetRole;
            }
        }

        $this->moduleModel->update($record['id'], [
            'disabled_roles' => json_encode(array_values($disabledRoles)),
            'updated_by'     => (int)session()->get('user_id'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        ModuleControlService::clearCache();

        $statusLabel = $allowed ? 'RESTORED' : 'RESTRICTED / BLOCKED';
        AuditLogger::log(
            AuditLogger::ADMIN_ACTION,
            "Super Admin {$statusLabel} access to module '{$record['module_name']}' for role '{$targetRole}'.",
            'system_module_controls',
            (int)$record['id'],
            ['module_key' => $moduleKey, 'role' => $targetRole, 'allowed' => $allowed]
        );

        return $this->response->setJSON([
            'status'         => 'success',
            'message'        => "Role '{$targetRole}' access {$statusLabel} for module '{$record['module_name']}'.",
            'module_key'     => $moduleKey,
            'role'           => $targetRole,
            'allowed'        => $allowed,
            'disabled_roles' => $disabledRoles,
            'csrf_hash'      => csrf_hash(),
        ]);
    }

    /**
     * Update the maintenance notice reason for a module.
     */
    public function updateReason()
    {
        if ($guard = $this->guardSuperAdmin()) {
            return $guard;
        }

        $moduleKey = trim((string)$this->request->getPost('module_key'));
        $reason    = trim((string)$this->request->getPost('maintenance_reason'));

        $record = $this->moduleModel->where('module_key', $moduleKey)->first();
        if ($record && !empty($reason)) {
            $this->moduleModel->update($record['id'], [
                'maintenance_reason' => $reason,
                'updated_by'         => (int)session()->get('user_id'),
                'updated_at'         => date('Y-m-d H:i:s'),
            ]);
            ModuleControlService::clearCache();
        }

        return redirect()->to('/admin/modules')
            ->with('success', "Maintenance message updated for '{$record['module_name']}'.");
    }

    /**
     * Restore all modules and roles to default fully active state.
     */
    public function resetDefaults()
    {
        if ($guard = $this->guardSuperAdmin()) {
            return $guard;
        }

        $modules = $this->moduleModel->findAll();
        foreach ($modules as $mod) {
            $this->moduleModel->update($mod['id'], [
                'is_enabled'         => 1,
                'disabled_roles'     => '[]',
                'maintenance_reason' => 'Temporarily offline for scheduled maintenance and bug resolution.',
                'updated_by'         => (int)session()->get('user_id'),
                'updated_at'         => date('Y-m-d H:i:s'),
            ]);
        }
        ModuleControlService::clearCache();

        AuditLogger::log(
            AuditLogger::ADMIN_ACTION,
            "Super Admin RESET all system module controls to default ACTIVE state.",
            'system_module_controls',
            0,
            ['action' => 'reset_all_modules_to_active']
        );

        return redirect()->to('/admin/modules')
            ->with('success', 'All system modules and roles have been restored to Active state.');
    }
}
