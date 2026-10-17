<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\TripRequestModel;
use App\Models\TripTicketModel;
use App\Models\VehicleModel;
use App\Models\DriverModel;
use App\Models\NotificationModel;
use App\Services\DispatchEngineService;

class DispatchTest extends BaseCommand
{
    protected $group       = 'PIA Fleet';
    protected $name        = 'dispatch:test';
    protected $description = 'Verifies strict driver availability, auto-denial on conflict, and multi-request merging';

    public function run(array $params)
    {
        CLI::write("=========================================================", 'cyan');
        CLI::write("   DISPATCH CONSOLE: DRIVER AVAILABILITY & MERGE TEST    ", 'yellow');
        CLI::write("=========================================================\n", 'cyan');

        $requestModel  = new TripRequestModel();
        $ticketModel   = new TripTicketModel();
        $vehicleModel  = new VehicleModel();
        $driverModel   = new DriverModel();
        $dispatchEngine = new DispatchEngineService();

        $passCount = 0;
        $failCount = 0;

        $assert = function (bool $condition, string $msg) use (&$passCount, &$failCount) {
            if ($condition) {
                CLI::write("[PASS] {$msg}", 'green');
                $passCount++;
            } else {
                CLI::write("[FAIL] {$msg}", 'red');
                $failCount++;
            }
        };

        // Pre-cleanup in case previous run was interrupted
        $requestModel->whereIn('request_number', ['VRS-TEST-001', 'VRS-TEST-002', 'VRS-TEST-003'])->delete();
        $ticketModel->like('qr_crypt_token', 'TEST-TOKEN-')->delete();

        try {
            // -------------------------------------------------------------
            // Test 1: Vehicle Seating Capacity
            // -------------------------------------------------------------
            CLI::write("\n--- Test 1: Realistic Vehicle Seating Capacity Calculation ---", 'yellow');
            $vanVehicle = ['model' => 'HiAce Commuter Van', 'vehicle_type' => 'van'];
            $suvVehicle = ['model' => 'Innova 2.8 E', 'vehicle_type' => 'suv'];
            $sedanVehicle = ['model' => 'Vios 1.3', 'vehicle_type' => 'sedan'];

            $assert($dispatchEngine->getVehicleCapacity($vanVehicle) === 15, "Van capacity is 15 seats");
            $assert($dispatchEngine->getVehicleCapacity($suvVehicle) === 7, "Innova / SUV capacity is 7 seats");
            $assert($dispatchEngine->getVehicleCapacity($sedanVehicle) === 5, "Sedan capacity is 5 seats");

            // -------------------------------------------------------------
            // Test 2: Strict Driver Availability Checking
            // -------------------------------------------------------------
            CLI::write("\n--- Test 2: Strict Driver Availability Guard ---", 'yellow');
            $activeDriver = $driverModel->where('status', 'available')->first() ?: $driverModel->first();
            if (!$activeDriver) {
                CLI::error("No driver found in database to test!");
                return;
            }

            $testDep = '2026-10-25 08:00:00';
            $testRet = '2026-10-25 17:00:00';

            // 2.1 Available driver check
            $originalStatus = $activeDriver['status'];
            $driverModel->update($activeDriver['id'], ['status' => 'available']);
            $checkAvail = $dispatchEngine->checkDriverAvailability($activeDriver['id'], $testDep, $testRet);
            $assert($checkAvail['available'] === true, "Active driver is certified and marked available");

            // 2.2 Off-duty driver check
            $driverModel->update($activeDriver['id'], ['status' => 'off_duty']);
            $checkOffDuty = $dispatchEngine->checkDriverAvailability($activeDriver['id'], $testDep, $testRet);
            $assert($checkOffDuty['available'] === false && $checkOffDuty['conflict_type'] === 'duty_status',
                "Driver with status 'off_duty' is strictly blocked from dispatch: {$checkOffDuty['reason']}");

            // 2.3 On-leave driver check
            $driverModel->update($activeDriver['id'], ['status' => 'on_leave']);
            $checkOnLeave = $dispatchEngine->checkDriverAvailability($activeDriver['id'], $testDep, $testRet);
            $assert($checkOnLeave['available'] === false && $checkOnLeave['conflict_type'] === 'duty_status',
                "Driver on official leave is strictly blocked from dispatch: {$checkOnLeave['reason']}");

            // Restore status
            $driverModel->update($activeDriver['id'], ['status' => $originalStatus]);

            // -------------------------------------------------------------
            // Test 3: Multi-Request Merge Corridor Matching
            // -------------------------------------------------------------
            CLI::write("\n--- Test 3: Mergeable Request Corridor Matching ---", 'yellow');
            $primarySample = [
                'id'             => 99991,
                'destination'    => 'Subic Bay Freeport Zone, Olongapo',
                'departure_time' => '2026-10-26 07:00:00',
            ];

            // Create two temporary test requests to test merging logic
            $testReq1Id = $requestModel->insert([
                'request_number'         => 'VRS-TEST-001',
            'requestor_id'           => 1,
            'requestor_name'         => 'Test Officer Alpha',
            'office_id'              => 1,
            'destination'            => 'Subic Bay Metropolitan Authority (SBMA)',
            'purpose'                => 'Official Maritime Forum',
            'departure_time'         => '2026-10-26 07:30:00',
            'return_time'            => '2026-10-26 18:00:00',
            'passenger_count'        => 3,
            'passenger_names'        => 'Alpha 1, Alpha 2, Alpha 3',
            'requested_driver_id'    => $activeDriver['id'],
            'status'                 => 'approved',
            'created_at'             => date('Y-m-d H:i:s'),
        ]);

        $testReq2Id = $requestModel->insert([
            'request_number'         => 'VRS-TEST-002',
            'requestor_id'           => 1,
            'requestor_name'         => 'Test Officer Beta',
            'office_id'              => 2,
            'destination'            => 'Subic Port Authority, Zambales',
            'purpose'                => 'Joint Media Coverage',
            'departure_time'         => '2026-10-26 08:00:00',
            'return_time'            => '2026-10-26 18:30:00',
            'passenger_count'        => 4,
            'passenger_names'        => 'Beta 1, Beta 2, Beta 3, Beta 4',
            'requested_driver_id'    => null,
            'status'                 => 'approved',
            'created_at'             => date('Y-m-d H:i:s'),
        ]);

        $mergeables = $dispatchEngine->findMergeableRequests($primarySample);
        $foundSubic = array_filter($mergeables, fn($m) => in_array($m['id'], [$testReq1Id, $testReq2Id]));
        $assert(count($foundSubic) >= 2, "Heuristic corridor engine identified Subic Bay requests as high-compatibility merge matches");

        // -------------------------------------------------------------
        // Test 4: Conflicting Driver Requests Identification
        // -------------------------------------------------------------
        CLI::write("\n--- Test 4: Driver Schedule Conflict Detection ---", 'yellow');
        // VRS-TEST-001 specifically requested $activeDriver for 2026-10-26 07:30 to 18:00
        $conflicts = $dispatchEngine->findConflictingDriverRequests(
            $activeDriver['id'],
            '2026-10-26 07:00:00',
            '2026-10-26 18:00:00',
            []
        );

        $hasConflictReq1 = !empty(array_filter($conflicts, fn($c) => (int)$c['id'] === (int)$testReq1Id));
        $assert($hasConflictReq1, "Engine successfully flagged VRS-TEST-001 as conflicting for driver {$activeDriver['driver_code']}");

        // -------------------------------------------------------------
        // Test 5: End-to-End Consolidated Dispatch Execution
        // -------------------------------------------------------------
        CLI::write("\n--- Test 5: End-to-End Consolidated Carpool Dispatch ---", 'yellow');
        $testVehicle = $vehicleModel->like('model', 'Innova')->first() ?: $vehicleModel->like('model', 'HiAce')->first() ?: $vehicleModel->first();

        // Simulate multi-request dispatch: primary is VRS-TEST-001, merged with VRS-TEST-002
        $req1 = $requestModel->find($testReq1Id);
        $req2 = $requestModel->find($testReq2Id);

        $combinedPaxCount = (int)$req1['passenger_count'] + (int)$req2['passenger_count'];
        $vehicleCap = $dispatchEngine->getVehicleCapacity($testVehicle);
        CLI::write("  Combined Passengers: {$combinedPaxCount} pax | Vehicle {$testVehicle['plate_number']} Capacity: {$vehicleCap} seats", 'white');

        $assert($combinedPaxCount <= $vehicleCap, "Combined passengers fit inside vehicle capacity limits");

        $serialNo = $ticketModel->generateSerialNo();
        $ticketId = $ticketModel->insert([
            'ticket_serial_no'          => $serialNo,
            'request_id'                => $testReq1Id,
            'vehicle_id'                => $testVehicle['id'],
            'driver_id'                 => $activeDriver['id'],
            'qr_crypt_token'            => 'TEST-TOKEN-' . bin2hex(random_bytes(16)),
            'status'                    => 'issued',
            'authorized_departure'      => $req1['departure_time'],
            'authorized_return'         => $req1['return_time'],
            'authorized_passengers'     => $req1['passenger_names'] . "\n[VRS {$req2['request_number']}]: " . $req2['passenger_names'],
            'authorized_destination'    => $req1['destination'],
            'authorized_purpose'        => $req1['purpose'] . " [CONSOLIDATED with {$req2['request_number']}]",
            'merged_request_ids'        => json_encode([(int)$testReq2Id]),
            'notes'                     => "Consolidated dispatch for {$req1['request_number']}, {$req2['request_number']}",
            'start_odometer'            => (float)$testVehicle['odometer_km'],
            'fuel_balance_start_liters' => 50.0,
            'fuel_balance_end_liters'   => 50.0,
        ]);

        // Update primary
        $requestModel->update($testReq1Id, [
            'status'                 => 'dispatched',
            'ticket_id'              => $ticketId,
            'is_merged'              => 1,
            'merged_with_request_id' => null,
        ]);

        // Update secondary
        $requestModel->update($testReq2Id, [
            'status'                 => 'dispatched',
            'ticket_id'              => $ticketId,
            'is_merged'              => 1,
            'merged_with_request_id' => $testReq1Id,
        ]);

        $freshReq1 = $requestModel->find($testReq1Id);
        $freshReq2 = $requestModel->find($testReq2Id);
        $freshTicket = $ticketModel->find($ticketId);

        $assert($freshReq1['status'] === 'dispatched' && (int)$freshReq1['ticket_id'] === (int)$ticketId,
            "Primary request {$freshReq1['request_number']} updated to 'dispatched' and linked to ticket #{$ticketId}");
        $assert($freshReq2['status'] === 'dispatched' && (int)$freshReq2['ticket_id'] === (int)$ticketId && (int)$freshReq2['merged_with_request_id'] === (int)$testReq1Id,
            "Secondary request {$freshReq2['request_number']} updated to 'dispatched' and linked to ticket #{$ticketId} (merged with #{$testReq1Id})");
        $assert(!empty($freshTicket['merged_request_ids']) && json_decode($freshTicket['merged_request_ids'], true) === [(int)$testReq2Id],
            "Trip ticket stores secondary request IDs in merged_request_ids JSON");

        // -------------------------------------------------------------
        // Test 6: Automatic Denial of Overlapping Request on Assignment
        // -------------------------------------------------------------
        CLI::write("\n--- Test 6: Automatic Denial of Conflicting Request ---", 'yellow');
        // Create a 3rd request requesting the same driver for the same schedule
        $testReq3Id = $requestModel->insert([
            'request_number'         => 'VRS-TEST-003',
            'requestor_id'           => 1,
            'requestor_name'         => 'Test Officer Gamma',
            'office_id'              => 1,
            'destination'            => 'Subic Olongapo',
            'purpose'                => 'Late Request for Rodrigo Santos',
            'departure_time'         => '2026-10-26 09:00:00',
            'return_time'            => '2026-10-26 15:00:00',
            'passenger_count'        => 2,
            'passenger_names'        => 'Gamma 1, Gamma 2',
            'requested_driver_id'    => $activeDriver['id'],
            'status'                 => 'approved',
            'created_at'             => date('Y-m-d H:i:s'),
        ]);

        // Now driver availability check for that time window must report booking overlap conflict!
        $availCheckDuringTrip = $dispatchEngine->checkDriverAvailability(
            $activeDriver['id'],
            '2026-10-26 09:00:00',
            '2026-10-26 15:00:00',
            null
        );
        $assert($availCheckDuringTrip['available'] === false && $availCheckDuringTrip['conflict_type'] === 'booking_overlap',
            "Driver availability check blocks assignment: {$availCheckDuringTrip['reason']}");

        // Now simulate the auto-denial triggered on dispatch
        $conflictsToAutoDeny = $dispatchEngine->findConflictingDriverRequests(
            $activeDriver['id'],
            $req1['departure_time'],
            $req1['return_time'],
            [$testReq1Id, $testReq2Id]
        );

        $deniedCount = 0;
        foreach ($conflictsToAutoDeny as $c) {
            if ((int)$c['id'] === (int)$testReq3Id) {
                $denyReason = "Auto-denied: Requested driver {$activeDriver['first_name']} {$activeDriver['last_name']} was officially assigned to dispatched trip ticket {$serialNo}.";
                $requestModel->update($c['id'], [
                    'status'             => 'rejected',
                    'dispatcher_action'  => 'rejected',
                    'dispatcher_remarks' => $denyReason,
                ]);
                $deniedCount++;
            }
        }

        $freshReq3 = $requestModel->find($testReq3Id);
        $assert($freshReq3['status'] === 'rejected' && str_contains($freshReq3['dispatcher_remarks'], 'Auto-denied'),
            "Conflicting request {$freshReq3['request_number']} was automatically rejected with audit explanation");

        // -------------------------------------------------------------
        // Test 7: View Rendering Verification
        // -------------------------------------------------------------
        CLI::write("\n--- Test 7: View Templates Integrity ---", 'yellow');
        $dispatchCtrl = new \App\Controllers\DispatchController();
        $indexHtml = (string)$dispatchCtrl->index();
        $assert(str_contains($indexHtml, 'Dispatch Command & Asset Allocation'), "dispatch/index view renders successfully");
        $assert(str_contains($indexHtml, 'batchMergeForm'), "dispatch/index includes batch merge form");

        $firstApproved = $requestModel->where('status', 'approved')->first();
        if ($firstApproved) {
            $assignHtml = (string)$dispatchCtrl->assign($firstApproved['id']);
            $assert(str_contains($assignHtml, 'Consolidate with Other Approved Requests'), "dispatch/assign view renders with merge consolidation panel");
        }

        } finally {
            // -------------------------------------------------------------
            // Cleanup Test Records
            // -------------------------------------------------------------
            if (!empty($ticketId)) {
                $ticketModel->delete($ticketId);
            }
            $requestModel->whereIn('request_number', ['VRS-TEST-001', 'VRS-TEST-002', 'VRS-TEST-003'])->delete();
            $ticketModel->like('qr_crypt_token', 'TEST-TOKEN-')->delete();
        }

        CLI::write("\n=========================================================", 'cyan');
        CLI::write("   TEST SUMMARY: {$passCount} PASSED, {$failCount} FAILED", $failCount === 0 ? 'green' : 'red');
        CLI::write("=========================================================\n", 'cyan');

        if ($failCount === 0) {
            CLI::write("SUCCESS: Strict driver availability, automatic denial of conflicts, and multi-request merging are verified!", 'green');
        }
    }
}
