<?php

namespace App\Services;

use App\Models\VehicleModel;
use App\Models\DriverModel;
use App\Models\PmsRecordModel;
use App\Models\TripTicketModel;
use Config\Database;

class DispatchEngineService
{
    protected $db;
    protected VehicleModel $vehicleModel;
    protected DriverModel $driverModel;
    protected PmsRecordModel $pmsModel;
    protected TripTicketModel $ticketModel;

    public function __construct()
    {
        $this->db          = Database::connect();
        $this->vehicleModel= new VehicleModel();
        $this->driverModel = new DriverModel();
        $this->pmsModel    = new PmsRecordModel();
        $this->ticketModel = new TripTicketModel();
    }

    /**
     * Estimates legal passenger seating capacity based on vehicle type and model.
     */
    public static function getVehicleCapacity(array $vehicle): int
    {
        $type  = strtolower($vehicle['type'] ?? '');
        $model = strtolower($vehicle['model'] ?? '');
        $make  = strtolower($vehicle['make'] ?? '');

        if (str_contains($type, 'van') || str_contains($model, 'hiace') || str_contains($model, 'urvan') || str_contains($model, 'transit') || str_contains($model, 'sprinter')) {
            return 15;
        }
        if (str_contains($type, 'suv') || str_contains($model, 'fortuner') || str_contains($model, 'montero') || str_contains($model, 'innova') || str_contains($model, 'everest')) {
            return 7;
        }
        if (str_contains($type, 'pickup') || str_contains($model, 'd-max') || str_contains($model, 'hilux') || str_contains($model, 'ranger')) {
            return 5;
        }
        if (str_contains($type, 'sedan') || str_contains($model, 'vios') || str_contains($model, 'altis') || str_contains($model, 'city')) {
            return 5;
        }
        if (str_contains($type, 'truck')) {
            return 3;
        }
        return 5;
    }

    /**
     * Calculate recommendations and rank vehicle-driver pairings for a trip request
     */
    public function getRecommendations(array $request): array
    {
        $depTime = $request['departure_time'];
        $retTime = $request['return_time'] ?: date('Y-m-d H:i:s', strtotime($depTime) + 4 * 3600);
        $passCount = (int)($request['passenger_count'] ?? 1);
        $reqType = strtolower($request['requested_vehicle_type'] ?? '');
        $preferredDriverId = (int)($request['requested_driver_id'] ?? 0);

        // Fetch active vehicles with PMS status
        $vehicles = $this->db->table('vehicles')
            ->select('vehicles.*, pms_records.is_locked, pms_records.next_pms_odometer, pms_records.status AS pms_status')
            ->join('pms_records', 'pms_records.vehicle_id = vehicles.id', 'left')
            ->whereNotIn('vehicles.status', ['maintenance', 'under_maintenance', 'disabled_breakdown', 'out_of_service'])
            ->get()->getResultArray();

        // Fetch drivers
        $drivers = $this->db->table('drivers')
            ->where('status !=', 'suspended')
            ->get()->getResultArray();

        // Check booked vehicle and driver IDs during this time window
        $conflicts = $this->getBookingConflicts($depTime, $retTime);

        $vehicleCandidates = [];
        foreach ($vehicles as $v) {
            $vid = (int)$v['id'];
            $isBooked = in_array($vid, $conflicts['vehicle_ids'], true);
            $isPmsLocked = (int)($v['is_locked'] ?? 0) === 1;

            if ($isBooked || $isPmsLocked) {
                continue; // Cannot recommend unavailable or PMS-locked vehicles
            }

            // Calculate Vehicle Sub-scores
            // PMS Health score (0.0 to 1.0)
            $currOdo = (float)$v['odometer_km'];
            $nextPms = (float)($v['next_pms_odometer'] ?? ($currOdo + 5000));
            $kmToPms = max(0, $nextPms - $currOdo);
            $wPms = min(1.0, $kmToPms / 5000.0);

            // Capacity / Type Match (0.0 to 1.0)
            $cap = self::getVehicleCapacity($v);
            $wCap = 0.8;
            if ($reqType && str_contains(strtolower($v['type']), $reqType)) {
                $wCap = 1.0;
            } elseif ($passCount > 4 && $cap >= $passCount) {
                $wCap = 1.0;
            }

            $vehicleScore = ($wPms * 50) + ($wCap * 50);

            $v['calculated_score'] = round($vehicleScore, 1);
            $v['km_to_pms'] = round($kmToPms, 0);
            $v['seating_capacity'] = $cap;
            $vehicleCandidates[] = $v;
        }

        // Sort vehicles by score descending
        usort($vehicleCandidates, fn($a, $b) => $b['calculated_score'] <=> $a['calculated_score']);

        $driverCandidates = [];
        foreach ($drivers as $d) {
            $did = (int)$d['id'];
            
            // Check driver availability against duty status, overlapping tickets, and license
            $avail = $this->checkDriverAvailability($did, $depTime, $retTime, (int)($request['id'] ?? 0));
            if (!$avail['available']) {
                $d['is_available'] = false;
                $d['unavailability_reason'] = $avail['reason'];
                $d['calculated_score'] = 0;
                $driverCandidates[] = $d;
                continue;
            }

            $d['is_available'] = true;
            $d['unavailability_reason'] = null;

            // Driver Priority Match (0.0 to 1.0)
            $wPrio = 0.7;
            if ($preferredDriverId > 0 && $preferredDriverId === $did) {
                $wPrio = 1.0;
            }

            // Rest & Safety Score (0.0 to 1.0)
            $safetyScore = (float)($d['safety_score'] ?? 95.0);
            $wRest = min(1.0, $safetyScore / 100.0);

            $driverScore = ($wPrio * 50) + ($wRest * 50);

            $d['calculated_score'] = round($driverScore, 1);
            $driverCandidates[] = $d;
        }

        // Sort drivers: available first, then by score descending
        usort($driverCandidates, function ($a, $b) {
            if ($a['is_available'] !== $b['is_available']) {
                return $b['is_available'] ? 1 : -1;
            }
            return $b['calculated_score'] <=> $a['calculated_score'];
        });

        $availableDrivers = array_filter($driverCandidates, fn($d) => $d['is_available']);

        return [
            'recommended_vehicles' => $vehicleCandidates,
            'recommended_drivers'  => $driverCandidates,
            'has_available_match'  => !empty($vehicleCandidates) && !empty($availableDrivers),
        ];
    }

    /**
     * Authoritative driver availability checker for a proposed travel schedule.
     * Evaluates duty status, active overlapping trip tickets, and license validity.
     */
    /**
     * Authoritative driver availability checker for a proposed travel schedule.
     * Evaluates duty status, active overlapping trip tickets, and license validity.
     */
    public function checkDriverAvailability(int $driverId, string $depTime, ?string $retTime = null, ?int $excludeRequestId = null): array
    {
        if (empty($retTime)) {
            $retTime = date('Y-m-d H:i:s', strtotime($depTime) + 4 * 3600);
        }

        $driver = $this->driverModel->find($driverId);
        if (!$driver) {
            return [
                'available'     => false,
                'reason'        => 'Driver record not found in system.',
                'reasons'       => ['Driver record not found in system.'],
                'conflict_type' => 'not_found',
            ];
        }

        // 1. Check administrative duty status
        if (in_array($driver['status'], ['off_duty', 'on_leave', 'suspended'], true)) {
            $statusLabel = match ($driver['status']) {
                'off_duty'  => 'Off Duty',
                'on_leave'  => 'On Official Leave',
                'suspended' => 'Suspended',
                default     => ucfirst($driver['status']),
            };
            $msg = "Driver {$driver['first_name']} {$driver['last_name']} is currently {$statusLabel}.";
            return [
                'available'     => false,
                'reason'        => $msg,
                'reasons'       => [$msg],
                'conflict_type' => 'duty_status',
                'driver'        => $driver,
            ];
        }

        // 2. Check professional driver's license expiration
        if (!empty($driver['license_expiry']) && $driver['license_expiry'] < substr($depTime, 0, 10)) {
            $msg = "Driver {$driver['first_name']} {$driver['last_name']}'s driver license expired on {$driver['license_expiry']}.";
            return [
                'available'     => false,
                'reason'        => $msg,
                'reasons'       => [$msg],
                'conflict_type' => 'license_expired',
                'driver'        => $driver,
            ];
        }

        // 3. Check active overlapping assigned trip tickets
        $builder = $this->db->table('trip_tickets tt')
            ->select('tt.*, v.plate_number')
            ->join('vehicles v', 'v.id = tt.vehicle_id', 'left')
            ->where('tt.driver_id', $driverId)
            ->whereIn('tt.status', ['issued', 'departed', 'arrived_dest', 'departed_dest'])
            ->groupStart()
                ->where('tt.authorized_departure <=', $retTime)
                ->where('tt.authorized_return >=', $depTime)
            ->groupEnd();

        if ($excludeRequestId) {
            $builder->where('tt.request_id !=', $excludeRequestId);
        }

        $conflictTicket = $builder->get()->getRowArray();
        if ($conflictTicket) {
            $msg = "Driver {$driver['first_name']} {$driver['last_name']} already holds ticket {$conflictTicket['ticket_serial_no']} ({$conflictTicket['authorized_departure']} -> {$conflictTicket['authorized_return']}).";
            return [
                'available'       => false,
                'reason'          => $msg,
                'reasons'         => [$msg],
                'conflict_type'   => 'booking_overlap',
                'conflict_ticket' => $conflictTicket,
                'driver'          => $driver,
            ];
        }

        return [
            'available'     => true,
            'reason'        => 'Driver is certified and available for this schedule.',
            'reasons'       => [],
            'conflict_type' => null,
            'driver'        => $driver,
        ];
    }

    /**
     * Find any other requests in the pipeline that requested this driver on an overlapping schedule.
     */
    public function findConflictingDriverRequests(int $driverId, string $depTime, ?string $retTime = null, array $excludeRequestIds = []): array
    {
        if (empty($retTime)) {
            $retTime = date('Y-m-d H:i:s', strtotime($depTime) + 4 * 3600);
        }

        $builder = $this->db->table('trip_requests tr')
            ->select('tr.*, offices.office_name, users.name AS requestor_user_name')
            ->join('offices', 'offices.id = tr.office_id', 'left')
            ->join('users', 'users.id = tr.requestor_id', 'left')
            ->where('tr.requested_driver_id', $driverId)
            ->whereIn('tr.status', ['pending_dispatcher', 'pending_admin', 'approved'])
            ->where('tr.ticket_id IS NULL')
            ->where('tr.departure_time <=', $retTime)
            ->groupStart()
                ->where('tr.return_time >=', $depTime)
                ->orGroupStart()
                    ->where('tr.return_time IS NULL')
                    ->where('tr.departure_time >=', $depTime)
                ->groupEnd()
            ->groupEnd();

        if (!empty($excludeRequestIds)) {
            $builder->whereNotIn('tr.id', $excludeRequestIds);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Find eligible approved requests that can be merged / carpooled with the primary request.
     */
    public function findMergeableRequests(array $primaryRequest): array
    {
        $primaryId = (int)($primaryRequest['id'] ?? 0);
        $depTime   = $primaryRequest['departure_time'] ?? '';
        if (!$depTime) {
            return [];
        }

        $depDate = substr($depTime, 0, 10);
        $depTs   = strtotime($depTime);
        $startWindow = date('Y-m-d H:i:s', $depTs - 12 * 3600);
        $endWindow   = date('Y-m-d H:i:s', $depTs + 24 * 3600);

        $candidates = $this->db->table('trip_requests tr')
            ->select('tr.*, offices.office_name, departments.department_name, users.name AS requestor_user_name')
            ->join('offices', 'offices.id = tr.office_id', 'left')
            ->join('departments', 'departments.id = tr.department_id', 'left')
            ->join('users', 'users.id = tr.requestor_id', 'left')
            ->where('tr.status', 'approved')
            ->where('tr.ticket_id IS NULL')
            ->where('tr.id !=', $primaryId)
            ->where('tr.departure_time >=', $startWindow)
            ->where('tr.departure_time <=', $endWindow)
            ->orderBy('tr.departure_time', 'ASC')
            ->get()->getResultArray();

        $primaryDest = strtolower(trim($primaryRequest['destination'] ?? ''));

        foreach ($candidates as &$c) {
            $dest = strtolower(trim($c['destination'] ?? ''));
            $isSameDate = substr($c['departure_time'], 0, 10) === $depDate;
            $score = $isSameDate ? 50 : 20;

            // Extract keywords from destinations (e.g. malacañang, senate, subic, clark, baguio, qc, manila)
            $pWords = preg_split('/[\s,\.\-]+/', $primaryDest);
            $cWords = preg_split('/[\s,\.\-]+/', $dest);
            $common = array_intersect(
                array_filter($pWords, fn($w) => strlen($w) > 3),
                array_filter($cWords, fn($w) => strlen($w) > 3)
            );

            if (!empty($common)) {
                $score += 50;
                $c['destination_match'] = 'High (' . implode(', ', array_slice($common, 0, 2)) . ')';
            } elseif (str_contains($dest, $primaryDest) || str_contains($primaryDest, $dest)) {
                $score += 50;
                $c['destination_match'] = 'High (Direct Match)';
            } else {
                $c['destination_match'] = 'Same Travel Window / Area';
            }

            $c['merge_score'] = min(100, $score);
        }
        unset($c);

        usort($candidates, fn($a, $b) => $b['merge_score'] <=> $a['merge_score']);
        return $candidates;
    }

    /**
     * Find active trip tickets that overlap with the proposed schedule
     */
    private function getBookingConflicts(string $depTime, string $retTime): array
    {
        $overlapping = $this->db->table('trip_tickets')
            ->select('vehicle_id, driver_id')
            ->whereIn('status', ['issued', 'departed', 'arrived_dest', 'departed_dest'])
            ->groupStart()
                ->where('authorized_departure <=', $retTime)
                ->where('authorized_return >=', $depTime)
            ->groupEnd()
            ->get()->getResultArray();

        $vehicleIds = array_column($overlapping, 'vehicle_id');
        $driverIds  = array_column($overlapping, 'driver_id');

        return [
            'vehicle_ids' => array_unique(array_map('intval', $vehicleIds)),
            'driver_ids'  => array_unique(array_map('intval', $driverIds)),
        ];
    }
}
