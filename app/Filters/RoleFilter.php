<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    /**
     * Enforce role-based access control (RBAC) across application modules.
     * Arguments define allowed roles, e.g. ['admin', 'dispatcher']
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        // 1. Verify user is authenticated
        if (!$session->get('logged_in')) {
            return redirect()->to('/login')->with('error', 'Please sign in to access your enterprise portal.');
        }

        $userRole = $session->get('user_role') ?? 'dispatcher';

        // 2. Dynamic Super Admin Module & Function Deactivation Guard (Feature Flagging & Bug Containment)
        $uri = $request->getUri()->getPath();
        if ($userRole !== 'admin') {
            $matchedModule = \App\Services\ModuleControlService::findModuleByUri($uri);
            if ($matchedModule && !\App\Services\ModuleControlService::isModuleEnabled($matchedModule['module_key'], $userRole)) {
                $reason = \App\Services\ModuleControlService::getMaintenanceReason($matchedModule['module_key']);
                $landing = $this->getSafeLanding($userRole, $uri);
                return redirect()->to($landing)->with('error', "Module Notice: {$matchedModule['module_name']} is temporarily deactivated by the Super Admin for maintenance/bug fixing. Reason: {$reason}");
            }
        }

        // 3. Drivers and Guards have dedicated specialized operational interfaces
        if ($userRole === 'driver') {
            // 'safety' = BLOWBAGETS checklist (FR-5.1); 'mfa'/'notif' = account services
            $allowed = ['driver', 'logout', 'api', 'tickets', 'safety', 'mfa', 'notif'];
            $permitted = false;
            foreach ($allowed as $needle) {
                if (str_contains($uri, $needle)) {
                    $permitted = true;
                    break;
                }
            }
            if (!$permitted) {
                $landing = $this->getSafeLanding($userRole, $uri);
                return redirect()->to($landing)->with('error', 'Access restricted: Drivers are directed to their active operational portal.');
            }
        }
        if ($userRole === 'guard') {
            // 'tickets' = e-DTT verification at the checkpoint (route group includes guard)
            if (!str_contains($uri, 'gate') && !str_contains($uri, 'tickets') && !str_contains($uri, 'logout') && !str_contains($uri, 'api') && !str_contains($uri, 'mfa') && !str_contains($uri, 'notif')) {
                $landing = $this->getSafeLanding($userRole, $uri);
                return redirect()->to($landing)->with('error', 'Access restricted: Security officers are directed to the Gate Security Checkpoint.');
            }
        }

        // 4. If specific role arguments were defined for the route, check membership
        if (!empty($arguments)) {
            // Admin always has universal access
            if ($userRole === 'admin') {
                return;
            }

            // Normalize arguments (CI4 may pass ['admin,dispatcher'] or ['admin', 'dispatcher'])
            if (is_string($arguments)) {
                $arguments = explode(',', $arguments);
            } elseif (is_array($arguments) && count($arguments) === 1 && str_contains($arguments[0], ',')) {
                $arguments = explode(',', $arguments[0]);
            }
            $allowedRoles = array_map('trim', (array)$arguments);

            if (!in_array($userRole, $allowedRoles, true)) {
                $redirectUrl = $this->getSafeLanding($userRole, $uri);
                return redirect()->to($redirectUrl)->with('error', 'Access Restricted: Your role (' . ucfirst($userRole) . ') does not have permission for this section.');
            }
        }
    }

    /**
     * Determine a safe landing route for a user when a module is disabled or restricted,
     * avoiding infinite redirect loops.
     */
    protected function getSafeLanding(string $userRole, string $currentUri): string
    {
        $roleCandidates = [
            'driver'         => ['/driver/trips', '/safety', '/tickets', '/notifications'],
            'guard'          => ['/gate', '/tickets', '/notifications'],
            'maintenance'    => ['/maintenance', '/pir', '/dashboard', '/notifications'],
            'dispatcher'     => ['/dispatch', '/dispatch/calendar', '/approvals', '/requests', '/dashboard', '/notifications'],
            'requestor'      => ['/requests', '/notifications'],
            'approver_admin' => ['/approvals', '/requests', '/dashboard', '/notifications'],
            'auditor'        => ['/audit', '/compliance', '/tickets', '/requests', '/notifications'],
        ];

        $candidates = $roleCandidates[$userRole] ?? ['/notifications'];
        $cleanCurrent = trim(preg_replace('#^index\.php/?#i', '', trim($currentUri, '/')), '/');

        foreach ($candidates as $cand) {
            $candClean = trim($cand, '/');
            // Skip if this candidate is the current route being requested
            if ($cleanCurrent === $candClean || str_starts_with($cleanCurrent, $candClean . '/')) {
                continue;
            }
            // Check if candidate route's module is enabled for this user role
            $candMod = \App\Services\ModuleControlService::findModuleByUri($cand);
            if ($candMod === null || \App\Services\ModuleControlService::isModuleEnabled($candMod['module_key'], $userRole)) {
                return $cand;
            }
        }

        return '/notifications';
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        unset($request, $response, $arguments);
        // No post-processing needed
    }
}
