<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\VehicleModel;
use App\Models\MaintenanceModel;

class MaintenanceDue extends BaseCommand
{
    protected $group       = 'Fleet';
    protected $name        = 'maintenance:due';
    protected $description = 'Checks fleet vehicle odometer readings and schedules preventive maintenance due.';

    public function run(array $params)
    {
        CLI::write('Checking fleet maintenance schedules...', 'yellow');

        $vehicleModel = new VehicleModel();
        $maintenanceModel = new MaintenanceModel();

        $vehicles = $vehicleModel->findAll();
        $createdCount = 0;

        foreach ($vehicles as $v) {
            $odo = (float)$v['odometer_km'];
            $nextDue = (float)($v['next_service_km'] ?? ($odo + 10000));

            // Check if vehicle has exceeded service threshold
            if ($odo >= $nextDue && $nextDue > 0) {
                // Check if already an open work order exists
                $existing = $maintenanceModel->where('vehicle_id', $v['id'])
                                            ->whereIn('status', ['scheduled', 'in_progress'])
                                            ->first();

                if (!$existing) {
                    $refNo = 'WO-AUTO-' . date('Ymd') . '-' . $v['id'];
                    $maintenanceModel->insert([
                        'reference_no'        => $refNo,
                        'vehicle_id'          => $v['id'],
                        'service_type'        => 'Auto-Scheduled Mileage Preventive Maintenance (PMS)',
                        'priority'            => 'high',
                        'scheduled_date'      => date('Y-m-d', strtotime('+3 days')),
                        'odometer_at_service' => $odo,
                        'service_center'      => 'In-House Fleet Workshop',
                        'cost'                => 15000.00,
                        'status'              => 'scheduled',
                        'description'         => "Vehicle exceeded scheduled service threshold of {$nextDue} km (Current: {$odo} km).",
                    ]);

                    CLI::write("Created {$refNo} for vehicle {$v['vehicle_code']}.", 'green');
                    $createdCount++;
                }
            }
        }

        CLI::write("Maintenance schedule check complete. {$createdCount} work orders generated.", 'cyan');
    }
}
