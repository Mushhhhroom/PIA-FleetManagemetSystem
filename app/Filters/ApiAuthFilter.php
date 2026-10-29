<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * ApiAuthFilter — Enterprise API Authentication & Role RBAC Guard.
 *
 * Protects state-modifying REST endpoints against unauthenticated access,
 * data tampering, telemetry spoofing, and privilege escalation (CWE-306, OWASP A01:2021).
 *
 * Supports two authentication schemes:
 *  1. Stateful Session: Validates active user session (logged_in === true)
 *     and enforces allowed roles specified in filter arguments.
 *  2. Telematics / API Key: Validates X-API-Key header, Authorization Bearer,
 *     or query parameter against configured environment secrets using constant-time hash_equals.
 */
class ApiAuthFilter implements FilterInterface
{
    /**
     * Executes authentication & authorization check before controller action.
     *
     * @param RequestInterface $request
     * @param array|string|null $arguments Allowed roles when authenticated via session (e.g. ['admin', 'dispatcher', 'driver'])
     *
     * @return ResponseInterface|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // 1. Allow CORS preflight OPTIONS requests to pass through
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return;
        }

        $session = session();
        $hasSession = (bool) ($session->get('logged_in') && $session->get('user_id'));

        // 2. Check for provided API key
        $providedKey = $this->extractApiKey($request);
        $configuredKeys = $this->getConfiguredApiKeys();

        $hasValidApiKey = false;
        if ($providedKey !== null && $providedKey !== '') {
            foreach ($configuredKeys as $validKey) {
                if ($validKey !== '' && hash_equals($validKey, $providedKey)) {
                    $hasValidApiKey = true;
                    break;
                }
            }
        }

        // 3. Reject if neither a valid user session nor a valid API key is present
        if (!$hasSession && !$hasValidApiKey) {
            return service('response')
                ->setStatusCode(401)
                ->setHeader('Content-Type', 'application/json')
                ->setJSON([
                    'status'  => 'error',
                    'code'    => 'UNAUTHORIZED',
                    'message' => 'Authentication required: State-modifying REST APIs require an active user session or valid API key (X-API-Key or Bearer token).',
                ]);
        }

        // 4. If authenticated via web session, enforce role restrictions if specified
        if ($hasSession && !empty($arguments)) {
            $userRole = (string) ($session->get('user_role') ?? '');
            $allowedRoles = is_array($arguments) ? $arguments : explode(',', (string) $arguments);
            $allowedRoles = array_map('trim', $allowedRoles);

            if (!in_array($userRole, $allowedRoles, true)) {
                return service('response')
                    ->setStatusCode(403)
                    ->setHeader('Content-Type', 'application/json')
                    ->setJSON([
                        'status'  => 'error',
                        'code'    => 'FORBIDDEN',
                        'message' => 'Access denied: Your account role does not have authorization to invoke this endpoint.',
                    ]);
            }
        }
    }

    /**
     * No post-processing required.
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed
    }

    /**
     * Extracts an API key from request headers or query parameters.
     */
    protected function extractApiKey(RequestInterface $request): ?string
    {
        $headerKey = $request->getHeaderLine('X-API-Key');
        if ($headerKey !== '') {
            return trim($headerKey);
        }

        $authHeader = $request->getHeaderLine('Authorization');
        if (str_starts_with(strtolower($authHeader), 'bearer ')) {
            return trim(substr($authHeader, 7));
        }

        $queryKey = $request->getVar('api_key') ?? $request->getVar('key');
        return is_string($queryKey) && $queryKey !== '' ? trim($queryKey) : null;
    }

    /**
     * Retrieves valid configured API keys from environment configuration.
     *
     * @return list<string>
     */
    protected function getConfiguredApiKeys(): array
    {
        $keys = [];

        $telematicsKey = trim((string) env('TELEMATICS_API_KEY', ''));
        if ($telematicsKey !== '') {
            $keys[] = $telematicsKey;
        }

        $apiKey = trim((string) env('API_KEY', ''));
        if ($apiKey !== '') {
            $keys[] = $apiKey;
        }

        return $keys;
    }
}
