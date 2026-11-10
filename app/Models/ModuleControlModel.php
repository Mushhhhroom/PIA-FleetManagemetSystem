<?php

namespace App\Models;

use CodeIgniter\Model;

class ModuleControlModel extends Model
{
    protected $table            = 'system_module_controls';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'module_key',
        'module_name',
        'category',
        'route_prefix',
        'icon',
        'description',
        'is_enabled',
        'disabled_roles',
        'maintenance_reason',
        'updated_by',
        'updated_at',
        'created_at',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Pre-defined core modules list across all official processes.
     */
    public const DEFAULT_MODULES = [
        [
            'module_key'         => 'dashboard',
            'module_name'        => 'Command Center (Dashboard)',
            'category'           => 'Overview',
            'route_prefix'       => 'dashboard',
            'icon'               => 'fa-chart-line',
            'description'        => 'High-level real-time operations dashboard, KPIs, and telematics map.',
            'is_enabled'         => 1,
            'disabled_roles'     => '[]',
            'maintenance_reason' => 'Temporarily offline for scheduled maintenance and bug resolution.',
        ],
        [
            'module_key'         => 'requests',
            'module_name'        => 'Vehicle Requests (VRS)',
            'category'           => 'Official Travel',
            'route_prefix'       => 'requests',
            'icon'               => 'fa-file-signature',
            'description'        => 'Digital filing of Vehicle Request Slips (ADMIN-F-018 rev2).',
            'is_enabled'         => 1,
            'disabled_roles'     => '[]',
            'maintenance_reason' => 'Temporarily offline for scheduled maintenance and bug resolution.',
        ],
        [
            'module_key'         => 'approvals',
            'module_name'        => 'Approvals & 4h Escalation',
            'category'           => 'Official Travel',
            'route_prefix'       => 'approvals',
            'icon'               => 'fa-stamp',
            'description'        => 'Tier 1 Dispatcher endorsement and Tier 2 Admin Division Head authorization.',
            'is_enabled'         => 1,
            'disabled_roles'     => '[]',
            'maintenance_reason' => 'Temporarily offline for scheduled maintenance and bug resolution.',
        ],
        [
            'module_key'         => 'dispatch',
            'module_name'        => 'Dispatch Console & Heuristic Engine',
            'category'           => 'Official Travel',
            'route_prefix'       => 'dispatch',
            'icon'               => 'fa-truck-fast',
            'description'        => 'Motorpool asset allocation, PMS lock enforcement, and request consolidation.',
            'is_enabled'         => 1,
            'disabled_roles'     => '[]',
            'maintenance_reason' => 'Temporarily offline for scheduled maintenance and bug resolution.',
        ],
        [
            'module_key'         => 'dispatch_calendar',
            'module_name'        => 'Allocation Calendar Board',
            'category'           => 'Official Travel',
            'route_prefix'       => 'dispatch/calendar',
            'icon'               => 'fa-calendar-days',
            'description'        => 'Interactive monthly schedule, multi-day tracking, and single updatable allocation.',
            'is_enabled'         => 1,
            'disabled_roles'     => '[]',
            'maintenance_reason' => 'Temporarily offline for scheduled maintenance and bug resolution.',
        ],
        [
            'module_key'         => 'tickets',
            'module_name'        => "Driver's Trip Tickets (e-DTT)",
            'category'           => 'Official Travel',
            'route_prefix'       => 'tickets',
            'icon'               => 'fa-ticket',
            'description'        => 'Official e-DTT generation (ADMIN-F-001 rev1) and Section B fuel accounting.',
            'is_enabled'         => 1,
            'disabled_roles'     => '[]',
            'maintenance_reason' => 'Temporarily offline for scheduled maintenance and bug resolution.',
        ],
        [
            'module_key'         => 'gate',
            'module_name'        => 'Gate Security QR Checkpoint',
            'category'           => 'Official Travel',
            'route_prefix'       => 'gate',
            'icon'               => 'fa-shield-halved',
            'description'        => 'Cryptographic QR token scanning for compound egress and ingress clearances.',
            'is_enabled'         => 1,
            'disabled_roles'     => '[]',
            'maintenance_reason' => 'Temporarily offline for scheduled maintenance and bug resolution.',
        ],
        [
            'module_key'         => 'safety',
            'module_name'        => 'BLOWBAGETS Safety Checks',
            'category'           => 'Safety & Maintenance',
            'route_prefix'       => 'safety',
            'icon'               => 'fa-clipboard-check',
            'description'        => '10-point roadworthiness safety checklist required before gate departure.',
            'is_enabled'         => 1,
            'disabled_roles'     => '[]',
            'maintenance_reason' => 'Temporarily offline for scheduled maintenance and bug resolution.',
        ],
        [
            'module_key'         => 'pir',
            'module_name'        => 'Mechanic PIR Work Queue',
            'category'           => 'Safety & Maintenance',
            'route_prefix'       => 'pir',
            'icon'               => 'fa-screwdriver-wrench',
            'description'        => 'Pre-Inspection Reports routing failed items to workshop mechanics.',
            'is_enabled'         => 1,
            'disabled_roles'     => '[]',
            'maintenance_reason' => 'Temporarily offline for scheduled maintenance and bug resolution.',
        ],
        [
            'module_key'         => 'rfid',
            'module_name'        => 'Tollway RFID Cards',
            'category'           => 'Safety & Maintenance',
            'route_prefix'       => 'rfid',
            'icon'               => 'fa-credit-card',
            'description'        => 'AutoSweep and EasyTrip prepaid balances, reload logs, and low-balance alerts.',
            'is_enabled'         => 1,
            'disabled_roles'     => '[]',
            'maintenance_reason' => 'Temporarily offline for scheduled maintenance and bug resolution.',
        ],
        [
            'module_key'         => 'vehicles',
            'module_name'        => 'Vehicle Fleet & 5K PMS',
            'category'           => 'Fleet Assets',
            'route_prefix'       => 'vehicles',
            'icon'               => 'fa-truck',
            'description'        => 'Vehicle registry, dedicated executive segregation, and 5,000 km PMS locks.',
            'is_enabled'         => 1,
            'disabled_roles'     => '[]',
            'maintenance_reason' => 'Temporarily offline for scheduled maintenance and bug resolution.',
        ],
        [
            'module_key'         => 'drivers',
            'module_name'        => 'Driver Personnel Registry',
            'category'           => 'Fleet Assets',
            'route_prefix'       => 'drivers',
            'icon'               => 'fa-id-card',
            'description'        => 'Professional driver profiles, license expiry tracking, and duty status.',
            'is_enabled'         => 1,
            'disabled_roles'     => '[]',
            'maintenance_reason' => 'Temporarily offline for scheduled maintenance and bug resolution.',
        ],
        [
            'module_key'         => 'tracking',
            'module_name'        => 'Live GPS Telematics Radar',
            'category'           => 'Fleet Assets',
            'route_prefix'       => 'tracking',
            'icon'               => 'fa-satellite-dish',
            'description'        => 'Interactive map visualization of real-time coordinates, speed, and heading.',
            'is_enabled'         => 1,
            'disabled_roles'     => '[]',
            'maintenance_reason' => 'Temporarily offline for scheduled maintenance and bug resolution.',
        ],
        [
            'module_key'         => 'fuel',
            'module_name'        => 'Fuel Ledger & Receipts',
            'category'           => 'Fleet Assets',
            'route_prefix'       => 'fuel',
            'icon'               => 'fa-gas-pump',
            'description'        => 'Fleet petroleum tracking, consumption efficiency, and anomaly detection.',
            'is_enabled'         => 1,
            'disabled_roles'     => '[]',
            'maintenance_reason' => 'Temporarily offline for scheduled maintenance and bug resolution.',
        ],
        [
            'module_key'         => 'maintenance',
            'module_name'        => 'Service Orders & PMS',
            'category'           => 'Fleet Assets',
            'route_prefix'       => 'maintenance',
            'icon'               => 'fa-wrench',
            'description'        => 'Preventive maintenance work orders, technician assignments, and repairs.',
            'is_enabled'         => 1,
            'disabled_roles'     => '[]',
            'maintenance_reason' => 'Temporarily offline for scheduled maintenance and bug resolution.',
        ],
        [
            'module_key'         => 'audit',
            'module_name'        => 'COA Compliance & Audits',
            'category'           => 'Compliance',
            'route_prefix'       => 'audit',
            'icon'               => 'fa-scale-balanced',
            'description'        => 'Official COA Circular 75-6 audit tables, CSV exports, and immutable trail.',
            'is_enabled'         => 1,
            'disabled_roles'     => '[]',
            'maintenance_reason' => 'Temporarily offline for scheduled maintenance and bug resolution.',
        ],
        [
            'module_key'         => 'compliance',
            'module_name'        => 'Compliance Portal (COA Form B)',
            'category'           => 'Compliance',
            'route_prefix'       => 'compliance',
            'icon'               => 'fa-file-shield',
            'description'        => 'Monthly Report of Official Travels and government fuel consumption sheets.',
            'is_enabled'         => 1,
            'disabled_roles'     => '[]',
            'maintenance_reason' => 'Temporarily offline for scheduled maintenance and bug resolution.',
        ],
        [
            'module_key'         => 'driver_portal',
            'module_name'        => 'Driver Mobile Web Portal',
            'category'           => 'Mobile Operations',
            'route_prefix'       => 'driver',
            'icon'               => 'fa-compass',
            'description'        => 'Driver 4-phase trip stepper, delay logging, and Section B certifications.',
            'is_enabled'         => 1,
            'disabled_roles'     => '[]',
            'maintenance_reason' => 'Temporarily offline for scheduled maintenance and bug resolution.',
        ],
    ];

    /**
     * Ensure database is initialized with default modules.
     */
    public function ensureInitialized(): void
    {
        $existing = array_column($this->findAll(), 'module_key');
        foreach (self::DEFAULT_MODULES as $mod) {
            if (!in_array($mod['module_key'], $existing, true)) {
                $this->insert($mod);
            }
        }
    }
}
