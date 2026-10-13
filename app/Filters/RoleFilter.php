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

        // 2. Drivers and Guards have dedicated specialized operational interfaces
        $uri = $request->getUri()->getPath();
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
                return redirect()->to('/driver/trips')->with('error', 'Access restricted: Drivers are directed to the mobile driver portal.');
            }
        }
        if ($userRole === 'guard') {
            // 'tickets' = e-DTT verification at the checkpoint (route group includes guard)
            if (!str_contains($uri, 'gate') && !str_contains($uri, 'tickets') && !str_contains($uri, 'logout') && !str_contains($uri, 'api') && !str_contains($uri, 'mfa') && !str_contains($uri, 'notif')) {
                return redirect()->to('/gate')->with('error', 'Access restricted: Security officers are directed to the Gate Security Checkpoint.');
            }
        }

        // 3. If specific role arguments were defined for the route, check membership
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
                // Route to appropriate default view based on role
                $redirectUrl = match ($userRole) {
                    'driver'                         => '/driver/trips',
                    'guard'                          => '/gate',
                    'maintenance'                    => '/maintenance',
                    'dispatcher'                     => '/dispatch',
                    'requestor'                      => '/requests',
                    'approver_oic', 'approver_admin' => '/approvals',
                    'auditor'                        => '/audit',
                    default                          => '/',
                };

                return redirect()->to($redirectUrl)->with('error', 'Access Restricted: Your role (' . ucfirst($userRole) . ') does not have permission for this section.');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post-processing needed
    }
}
