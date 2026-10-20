<?php

namespace App\Controllers;

use App\Models\VehicleModel;
use App\Models\DriverModel;
use App\Models\TripTicketModel;
use App\Models\MaintenanceModel;
use App\Models\FuelLogModel;
use App\Models\TripRequestModel;
use App\Services\SlaService;

class DashboardController extends BaseController
{
    public function index()
    {
        $vehicleModel     = new VehicleModel();
        $driverModel      = new DriverModel();
        $ticketModel      = new TripTicketModel();
        $maintenanceModel = new MaintenanceModel();
        $fuelModel        = new FuelLogModel();
        $requestModel     = new TripRequestModel();

        // 0. Official Travel VRS Queues
        $pendingTier1Count    = $requestModel->where('status', 'pending_dispatcher')->countAllResults();
        $pendingAdminCount    = $requestModel->where('status', 'pending_admin')->countAllResults();
        $pendingDispatchCount = $requestModel->where('status', 'approved')->countAllResults();

        $pendingTier1List = $requestModel->getRequestsWithDetails(['status' => 'pending_dispatcher'], 5);
        foreach ($pendingTier1List as &$t1) {
            $t1['sla_info'] = SlaService::formatSlaCountdown($t1['sla_deadline']);
        }

        // 1. KPI Counts
        $totalVehicles       = $vehicleModel->countAllResults();
        $inTransitVehicles   = $vehicleModel->where('status', 'in_transit')->countAllResults();
        $activeVehicles      = $vehicleModel->where('status', 'active')->countAllResults();
        $maintenanceVehicles = $vehicleModel->whereIn('status', ['maintenance', 'under_maintenance', 'disabled_breakdown'])->countAllResults();

        $totalDrivers     = $driverModel->countAllResults();
        $availableDrivers = $driverModel->where('status', 'available')->countAllResults();
        $onTripDrivers    = $driverModel->where('status', 'on_trip')->countAllResults();

        $activeTrips    = $ticketModel->whereIn('status', ['departed', 'arrived_dest', 'departed_dest'])->countAllResults();
        $scheduledTrips = $ticketModel->where('status', 'issued')->countAllResults();

        $openMaintenance = $maintenanceModel->whereIn('status', ['scheduled', 'in_progress'])->countAllResults();

        // Total fuel cost
        $fuelSummary   = $fuelModel->selectSum('total_cost')->first();
        $totalFuelCost = $fuelSummary['total_cost'] ?? 0;

        // 2. Live vehicles for interactive map widget
        $liveVehicles = $vehicleModel->select('vehicles.*, CONCAT(drivers.first_name, \' \', drivers.last_name) AS driver_name, drivers.phone AS driver_phone')
                                    ->join('drivers', 'drivers.id = vehicles.current_driver_id', 'left')
                                    ->findAll();

        // 3. Recent Official Driver's Trip Tickets (e-DTT)
        $recentTrips = $ticketModel->getTicketsWithDetails([], 6);

        // 4. Maintenance Alerts
        $maintenanceAlerts = $maintenanceModel->getRecordsWithVehicle(5);

        $data = [
            'title'                => 'Fleet Command Center',
            'totalVehicles'        => $totalVehicles,
            'inTransitVehicles'    => $inTransitVehicles,
            'activeVehicles'       => $activeVehicles,
            'maintenanceVehicles'  => $maintenanceVehicles,
            'totalDrivers'         => $totalDrivers,
            'availableDrivers'     => $availableDrivers,
            'onTripDrivers'        => $onTripDrivers,
            'activeTrips'          => $activeTrips,
            'scheduledTrips'       => $scheduledTrips,
            'openMaintenance'      => $openMaintenance,
            'totalFuelCost'        => $totalFuelCost,
            'liveVehicles'         => $liveVehicles,
            'recentTrips'          => $recentTrips,
            'maintenanceAlerts'    => $maintenanceAlerts,
            'pendingTier1Count'    => $pendingTier1Count,
            'pendingAdminCount'    => $pendingAdminCount,
            'pendingDispatchCount' => $pendingDispatchCount,
            'pendingTier1List'     => $pendingTier1List,
        ];

        return view('dashboard/index', $data);
    }
}
