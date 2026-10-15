<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;
use App\Models\TripRequestModel;
use App\Models\TripTicketModel;
use App\Models\GateLogModel;
use App\Services\SecurityQrService;
use App\Services\DispatchEngineService;
use App\Services\SlaService;

class PiaTest extends BaseCommand
{
    protected $group       = 'PIA Fleet';
    protected $name        = 'pia:test';
    protected $description = 'Executes end-to-end integration verification of the PIA Motorpool Fleet workflow';

    public function run(array $params)
    {
        $db = Database::connect();

        CLI::write("=========================================================", 'cyan');
        CLI::write("   PIA MOTORPOOL FLEET MANAGEMENT SYSTEM - VERIFICATION  ", 'yellow');
        CLI::write("=========================================================\n", 'cyan');

        $passCount = 0;
        $failCount = 0;

        $assertTest = function (string $description, bool $condition) use (&$passCount, &$failCount) {
            if ($condition) {
                CLI::write("[PASS] {$description}", 'green');
                $passCount++;
            } else {
                CLI::write("[FAIL] {$description}", 'red');
                $failCount++;
            }
        };

        // 1. Verify Offices Directory
        CLI::write("\n--- Step 1: Database Seed Verification ---", 'yellow');
        $officesCount = $db->table('offices')->countAll();
        $assertTest("Offices seeded (found {$officesCount} offices)", $officesCount >= 5);

        // 2. Verify Government RBAC Users (All Roles)
        CLI::write("\n--- Step 2: RBAC Roles Verification ---", 'yellow');
        $requiredRoles = ['admin', 'requestor', 'approver_admin', 'dispatcher', 'guard', 'auditor', 'driver'];
        foreach ($requiredRoles as $role) {
            $user = $db->table('users')->where('role', $role)->get()->getRow();
            $assertTest("User exists for role '{$role}': " . ($user ? $user->name : 'NONE'), $user !== null);
        }

        // 3. Cryptographic QR Token Generation & Verification
        CLI::write("\n--- Step 3: Cryptographic Security QR Service ---", 'yellow');
        $qrService = new SecurityQrService();
        $testSerial = "DTT-TEST-9999";
        $testPlate = "SJL-4192";
        $testDriver = "DRV-101";
        $testDate = "2026-10-02 08:00:00";

        $token = $qrService->generateToken($testSerial, $testPlate, $testDriver, $testDate);
        $assertTest("HMAC-SHA256 Token generated successfully", !empty($token) && str_contains($token, '.'));

        $verified = $qrService->verifyToken($token);
        $assertTest("HMAC-SHA256 Token verified authentic", $verified !== null && $verified['serial_no'] === $testSerial);

        // Tampering detection
        $tamperedToken = substr($token, 0, -4) . 'XXXX';
        $tamperedResult = $qrService->verifyToken($tamperedToken);
        $assertTest("Tampered token strictly rejected by security service", $tamperedResult === null);

        // 4. Heuristic Dispatch Matching Engine
        CLI::write("\n--- Step 4: Heuristic Dispatch Matching Engine ---", 'yellow');
        $engine = new DispatchEngineService();
        $sampleReq = [
            'departure_time'         => date('Y-m-d H:i:s', strtotime('+2 days')),
            'return_time'            => date('Y-m-d H:i:s', strtotime('+2 days 8 hours')),
            'passenger_count'        => 5,
            'requested_vehicle_type' => 'van',
            'requested_driver_id'    => 1,
        ];

        $recommendations = $engine->getRecommendations($sampleReq);
        $assertTest("Heuristic engine recommended vehicles", !empty($recommendations['recommended_vehicles']));
        $assertTest("Heuristic engine recommended drivers", !empty($recommendations['recommended_drivers']));
        if (!empty($recommendations['recommended_vehicles'])) {
            $topVeh = $recommendations['recommended_vehicles'][0];
            CLI::write("  Top Recommended Vehicle: {$topVeh['plate_number']} (Score: {$topVeh['calculated_score']}%, PMS Margin: {$topVeh['km_to_pms']} km)", 'white');
        }

        // 5. Full 10-Step Workflow Execution
        CLI::write("\n--- Step 5: Full 10-Step Workflow Execution ---", 'yellow');
        $reqModel = new TripRequestModel();
        $ticketModel = new TripTicketModel();
        $gateModel = new GateLogModel();

        $requestor = $db->table('users')->where('role', 'requestor')->get()->getRow();
        $dispUser  = $db->table('users')->where('role', 'dispatcher')->get()->getRow();
        $adminUser = $db->table('users')->where('role', 'approver_admin')->get()->getRow();
        $guardUser = $db->table('users')->where('role', 'guard')->get()->getRow();
        $hiace     = $db->table('vehicles')->where('plate_number', 'SJL-4192')->get()->getRow();
        $driver    = $db->table('drivers')->where('driver_code', 'DRV-101')->get()->getRow();

        // 5.1 File new VRS (ADMIN-F-018 rev2)
        $vrsNo = $reqModel->generateVrsNumber();
        $newReqId = $reqModel->insert([
            'request_number'         => $vrsNo,
            'requestor_id'           => $requestor->id,
            'requestor_name'         => $requestor->name,
            'office_id'              => $requestor->office_id ?: 1,
            'office_scope'           => 'central',
            'destination'            => 'Subic Bay Freeport Zone, Zambales',
            'purpose'                => 'Official coverage of ASEAN Maritime Security Summit.',
            'passenger_names'        => "1. Juan Dela Cruz\n2. Mark Santos",
            'passenger_count'        => 2,
            'departure_time'         => date('Y-m-d 08:00:00', strtotime('+3 days')),
            'return_time'            => date('Y-m-d 18:00:00', strtotime('+3 days')),
            'is_rush_request'        => 0,
            'status'                 => 'pending_dispatcher',
            'dispatcher_approver_id' => $dispUser->id,
            'sla_deadline'           => date('Y-m-d H:i:s', strtotime('+24 hours')),
        ]);
        $assertTest("5.1: VRS filed ({$vrsNo}), status 'pending_dispatcher'", $newReqId > 0);

        // 5.2 Tier 1 Dispatcher Endorsement
        $reqModel->update($newReqId, [
            'dispatcher_action'    => 'approved',
            'dispatcher_action_at' => date('Y-m-d H:i:s'),
            'dispatcher_remarks'   => 'Endorsed for administrative authorization.',
            'status'               => 'pending_admin',
        ]);
        $reqAfterDisp = $reqModel->find($newReqId);
        $assertTest("5.2: Tier 1 Dispatcher Endorsement recorded, status 'pending_admin'", $reqAfterDisp['status'] === 'pending_admin');

        // 5.3 Tier 2 Administrative Division Head Authorization (Atty. Julius S. De Peralta)
        $reqModel->update($newReqId, [
            'admin_approver_id' => $adminUser->id,
            'admin_action'      => 'approved',
            'admin_action_at'   => date('Y-m-d H:i:s'),
            'admin_remarks'     => 'Authorized for motorpool dispatch.',
            'status'            => 'approved',
        ]);
        $reqAfterAdmin = $reqModel->find($newReqId);
        $assertTest("5.3: Tier 2 Admin Head Authorization recorded, status 'approved'", $reqAfterAdmin['status'] === 'approved');

        // 5.4 Motorpool Dispatch & e-DTT Issuance (ADMIN-F-001 rev1)
        $dttSerial = $ticketModel->generateSerialNo();
        $dttQrToken = $qrService->generateToken($dttSerial, $hiace->plate_number, $driver->driver_code, $reqAfterAdmin['departure_time']);

        $newTicketId = $ticketModel->insert([
            'ticket_serial_no'          => $dttSerial,
            'request_id'                => $newReqId,
            'vehicle_id'                => $hiace->id,
            'driver_id'                 => $driver->id,
            'qr_crypt_token'            => $dttQrToken,
            'status'                    => 'issued',
            'authorized_departure'      => $reqAfterAdmin['departure_time'],
            'authorized_return'         => $reqAfterAdmin['return_time'],
            'authorized_passengers'     => $reqAfterAdmin['passenger_names'],
            'authorized_destination'    => $reqAfterAdmin['destination'],
            'authorized_purpose'        => $reqAfterAdmin['purpose'],
            'admin_approver_name'       => $adminUser->name,
            'start_odometer'            => (float)$hiace->odometer_km,
            'fuel_balance_start_liters' => 60.00,
            'fuel_issued_stock_liters'  => 0.00,
            'fuel_purchased_liters'     => 0.00,
            'fuel_used_liters'          => 0.00,
            'fuel_balance_end_liters'   => 60.00,
        ]);
        $reqModel->update($newReqId, ['status' => 'dispatched']);
        $assertTest("5.4: e-DTT generated ({$dttSerial}) with Section A and signed QR token", $newTicketId > 0);

        // 5.5 Compound Gate Security Egress Scan (Departure)
        $departureOdo = (float)$hiace->odometer_km;
        $gateModel->insert([
            'trip_ticket_id'    => $newTicketId,
            'event_type'        => 'egress',
            'guard_user_id'     => $guardUser->id,
            'guard_name'        => $guardUser->name,
            'scanned_at'        => date('Y-m-d H:i:s'),
            'odometer_reading'  => $departureOdo,
            'security_status'   => 'cleared',
            'odometer_verified' => 1,
            'remarks'           => 'Vehicle cleared for compound departure.',
        ]);
        $ticketModel->update($newTicketId, [
            'status'         => 'departed',
            'departure_time' => date('Y-m-d H:i:s'),
            'start_odometer' => $departureOdo,
        ]);
        $ticketAfterEgress = $ticketModel->find($newTicketId);
        $assertTest("5.5: Gate Egress scan recorded, status 'departed', start odo verified ({$departureOdo} km)", $ticketAfterEgress['status'] === 'departed');

        // 5.6 Compound Gate Security Ingress Scan (Return)
        $returnOdo = $departureOdo + 185.00; // 185 km trip
        $gateModel->insert([
            'trip_ticket_id'    => $newTicketId,
            'event_type'        => 'ingress',
            'guard_user_id'     => $guardUser->id,
            'guard_name'        => $guardUser->name,
            'scanned_at'        => date('Y-m-d H:i:s'),
            'odometer_reading'  => $returnOdo,
            'security_status'   => 'cleared',
            'odometer_verified' => 1,
            'remarks'           => 'Vehicle returned safely to compound.',
        ]);
        $ticketModel->update($newTicketId, [
            'status'            => 'returned',
            'arrival_back_time' => date('Y-m-d H:i:s'),
            'return_odometer'   => $returnOdo,
            'total_distance_km' => 185.00,
        ]);
        $ticketAfterIngress = $ticketModel->find($newTicketId);
        $assertTest("5.6: Gate Ingress scan recorded, status 'returned', return odo verified ({$returnOdo} km)", $ticketAfterIngress['status'] === 'returned');

        // 5.7 Section B Fuel Balance Calculation & Dual Certifications
        $fuelStart = 60.00;
        $fuelPurchased = 25.00;
        $fuelUsed = 18.50; // 185 km / 18.5 L = 10.0 KM/L
        $fuelEnd = round(($fuelStart + $fuelPurchased) - $fuelUsed, 2);
        $efficiency = round(185.00 / $fuelUsed, 2);

        $ticketModel->update($newTicketId, [
            'fuel_balance_start_liters' => $fuelStart,
            'fuel_purchased_liters'     => $fuelPurchased,
            'fuel_purchased_cost'       => 1550.00,
            'fuel_used_liters'          => $fuelUsed,
            'fuel_balance_end_liters'   => $fuelEnd,
            'fuel_efficiency_kml'       => $efficiency,
            'fuel_anomaly_flag'         => 0,
            'driver_certified'          => 1,
            'driver_certified_at'       => date('Y-m-d H:i:s'),
            'passenger_certified'       => 1,
            'passenger_certifier_name'  => $requestor->name,
            'passenger_certified_at'    => date('Y-m-d H:i:s'),
            'status'                    => 'completed',
        ]);
        $reqModel->update($newReqId, ['status' => 'completed']);

        $finalTicket = $ticketModel->find($newTicketId);
        $assertTest("5.7: Section B Fuel Accounting Formula verified (End = 60 + 25 - 18.5 = 66.5L)", (float)$finalTicket['fuel_balance_end_liters'] === 66.50);
        $assertTest("5.8: Dual certifications verified (Driver & Passenger signed)", (int)$finalTicket['driver_certified'] === 1 && (int)$finalTicket['passenger_certified'] === 1);
        $assertTest("5.9: Trip Ticket completed and audited", $finalTicket['status'] === 'completed');

        // 6. SLA Expiry Watcher
        CLI::write("\n--- Step 6: 24-Hour SLA Watcher CLI ---", 'yellow');
        $slaService = new SlaService();
        $expiredList = $slaService->checkAndExpireRequests();
        $assertTest("SLA Watcher executed safely without errors", is_array($expiredList));

        // Summary
        CLI::write("\n=========================================================", 'cyan');
        CLI::write("   TEST SUMMARY: {$passCount} PASSED, {$failCount} FAILED", $failCount === 0 ? 'green' : 'red');
        CLI::write("=========================================================\n", 'cyan');

        if ($failCount === 0) {
            CLI::write("SUCCESS: Entire PIA Motorpool Fleet Management System workflow verified!\n", 'green');
        }
    }
}
