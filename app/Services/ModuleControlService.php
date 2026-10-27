<?php

namespace App\Services;

use App\Models\ModuleControlModel;

class ModuleControlService
{
    /** @var array<string, array<string, mixed>>|null */
    protected static ?array $cachedModules = null;

    /**
     * Load all module control settings into memory.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getAllModules(): array
    {
        if (self::$cachedModules !== null) {
            return self::$cachedModules;
        }

        try {
            $model = new ModuleControlModel();
            $model->ensureInitialized();
            $rows = $model->findAll();

            self::$cachedModules = [];
            foreach ($rows as $row) {
                $disabledRoles = [];
                if (!empty($row['disabled_roles'])) {
                    $decoded = json_decode($row['disabled_roles'], true);
                    $disabledRoles = is_array($decoded) ? $decoded : [];
                }
                $row['disabled_roles_array'] = $disabledRoles;
                self::$cachedModules[$row['module_key']] = $row;
            }
        } catch (\Throwable) {
            // Fail open defensively if DB table is unavailable
            self::$cachedModules = [];
            foreach (ModuleControlModel::DEFAULT_MODULES as $mod) {
                $mod['disabled_roles_array'] = [];
                self::$cachedModules[$mod['module_key']] = $mod;
            }
        }

        return self::$cachedModules;
    }

    /**
     * Clear cached memory state.
     */
    public static function clearCache(): void
    {
        self::$cachedModules = null;
    }

    /**
     * Check if a module is currently enabled for a given role.
     * Super Admin (`admin` role) ALWAYS has access so they can test and resolve bugs.
     */
    public static function isModuleEnabled(string $moduleKey, ?string $userRole = null): bool
    {
        // Super admin bypasses all locks to perform maintenance and debugging
        if ($userRole === 'admin') {
            return true;
        }

        $modules = self::getAllModules();
        if (!isset($modules[$moduleKey])) {
            return true; // Not under feature flag control
        }

        $mod = $modules[$moduleKey];

        // Global kill-switch check
        if ((int)($mod['is_enabled'] ?? 1) === 0) {
            return false;
        }

        // Role-level deactivation check
        if ($userRole !== null && !empty($mod['disabled_roles_array'])) {
            if (in_array($userRole, $mod['disabled_roles_array'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the maintenance reason for a deactivated module.
     */
    public static function getMaintenanceReason(string $moduleKey): string
    {
        $modules = self::getAllModules();
        if (isset($modules[$moduleKey]['maintenance_reason']) && !empty($modules[$moduleKey]['maintenance_reason'])) {
            return $modules[$moduleKey]['maintenance_reason'];
        }

        return 'This module has been temporarily deactivated by the Super Admin for maintenance and issue resolution.';
    }

    /**
     * Find module by route prefix or URI string.
     */
    public static function findModuleByUri(string $uri): ?array
    {
        $modules = self::getAllModules();
        $cleanUri = trim(preg_replace('#^index\.php/?#i', '', trim($uri, '/')), '/');

        foreach ($modules as $mod) {
            $prefix = trim($mod['route_prefix'], '/');
            if ($cleanUri === $prefix || str_starts_with($cleanUri, $prefix . '/')) {
                return $mod;
            }
        }

        return null;
    }
}
