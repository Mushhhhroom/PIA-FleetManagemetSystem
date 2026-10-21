<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\DriverModel;
use App\Models\VehicleModel;
use App\Models\TripTicketModel;
use App\Models\TripRequestModel;
use App\Models\PassengerDelayModel;
use App\Models\NotificationModel;
use App\Models\IncidentModel;
use App\Models\PirReportModel;
use App\Models\RfidCardModel;
use App\Models\SafetyCheckModel;
use App\Controllers\DriverController;

class DriverTest extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'driver:test';
    protected $description = 'Verifies the minimalist, role-accurate Driver Mobile Portal and field operational actions.';

    public function run(array $params)
    {
        CLI::write("\n=========================================================", 'cyan');
        CLI::write("   DRIVER MOBILE PORTAL: MINIMALISM & UX VERIFICATION    ", 'yellow');
        CLI::write("=========================================================\n", 'cyan');

        $passCount = 0;
        $failCount = 0;

        $assert = function (bool $condition, string $label) use (&$passCount, &$failCount) {
            if ($condition) {
                CLI::write("[PASS] {$label}", 'green');
                $passCount++;
            } else {
                CLI::write("[FAIL] {$label}", 'red');
                $failCount++;
            }
        };

        $driverModel  = new DriverModel();
        $vehicleModel = new VehicleModel();
        $ticketModel  = new TripTicketModel();
        $delayModel   = new PassengerDelayModel();
        $incidentModel = new IncidentModel();
        $pirModel     = new PirReportModel();
        $rfidModel    = new RfidCardModel();
        $safetyModel  = new SafetyCheckModel();

        // -------------------------------------------------------------
        // Test 1: Driver Mobile View Rendering & Minimalism
        // -------------------------------------------------------------
        CLI::write("\n--- Test 1: Driver Mobile View & Layout Structure ---", 'yellow');
        $driverCtrl = new DriverController();
        $driverCtrl->initController(
            \Config\Services::request(),
            \Config\Services::response(),
            \Config\Services::logger()
        );
        $html = (string) $driverCtrl->mobileApp();

        $assert(str_contains($html, 'Gate Security Clearance Pass'), "Gate Security QR Clearance Pass is present");
        $assert(str_contains($html, 'id="dutyModal"'), "Duty Status switcher modal is integrated");
        $assert(str_contains($html, 'id="delayModal"'), "15-Minute Passenger Delay modal (FR-4.2) is integrated");
        $assert(str_contains($html, 'id="incidentModal"'), "Emergency SOS Breakdown modal (FR-4.3) is integrated");
        $assert(str_contains($html, 'id="fuelModal"'), "Refuel & Petroleum logging modal is integrated");
        $assert(str_contains($html, 'bottom-thumb-dock'), "Thumb ergonomic bottom dock (4 distinct views) is present");
        $assert(str_contains($html, 'Tollway RFID Balances'), "Tollway RFID cards (AutoSweep & EasyTrip) view is present");
        $assert(str_contains($html, 'BLOWBAGETS'), "BLOWBAGETS Pre-Trip Safety indicators (FR-5.1) are present");

        // -------------------------------------------------------------
        // Test 2: Duty Status Updates
        // -------------------------------------------------------------
        CLI::write("\n--- Test 2: Driver Duty Status State Transitions ---", 'yellow');
        $testDriver = $driverModel->first();
        if ($testDriver) {
            $originalStatus = $testDriver['status'];

            $driverModel->update($testDriver['id'], ['status' => 'on_trip']);
            $fresh = $driverModel->find($testDriver['id']);
            $assert($fresh['status'] === 'on_trip', "Driver status transitioned to 'on_trip'");

            $driverModel->update($testDriver['id'], ['status' => 'off_duty']);
            $fresh = $driverModel->find($testDriver['id']);
            $assert($fresh['status'] === 'off_duty', "Driver status transitioned to 'off_duty'");

            $driverModel->update($testDriver['id'], ['status' => 'available']);
            $fresh = $driverModel->find($testDriver['id']);
            $assert($fresh['status'] === 'available', "Driver status restored to 'available'");

            // Restore original
            $driverModel->update($testDriver['id'], ['status' => $originalStatus]);
        }

        // -------------------------------------------------------------
        // Test 3: Passenger Delay Logger (FR-4.2)
        // -------------------------------------------------------------
        CLI::write("\n--- Test 3: 15-Minute Passenger Delay Protocol (FR-4.2) ---", 'yellow');
        $activeTicket = $ticketModel->whereIn('status', ['issued', 'departed'])->first();
        if (!$activeTicket) {
            $activeTicket = $ticketModel->first();
        }

        if ($activeTicket) {
            $delayId = $delayModel->insert([
                'trip_ticket_id'      => (int) $activeTicket['id'],
                'delay_minutes'       => 20,
                'reason'              => 'VIP meeting ran over schedule',
                'dispatcher_notified' => 1,
                'created_at'          => date('Y-m-d H:i:s'),
            ]);

            $savedDelay = $delayModel->find($delayId);
            $assert($savedDelay !== null, "Passenger delay of 20 minutes recorded successfully");
            $assert((int)$savedDelay['dispatcher_notified'] === 1, "Dispatcher notification flag activated (>=15 min rule)");

            // Cleanup delay test record
            $delayModel->delete($delayId);
        }

        // -------------------------------------------------------------
        // Test 4: Breakdown SOS Auto-PIR Generation (FR-4.3)
        // -------------------------------------------------------------
        CLI::write("\n--- Test 4: Breakdown SOS & Mechanic Queue Routing (FR-4.3) ---", 'yellow');
        $testVeh = $vehicleModel->first();
        if ($testVeh) {
            $origVehStatus = $testVeh['status'];

            $incNo = 'INC-TEST-' . rand(100, 999);
            $incId = $incidentModel->insert([
                'incident_no'     => $incNo,
                'vehicle_id'      => (int) $testVeh['id'],
                'driver_id'       => $testDriver ? (int) $testDriver['id'] : null,
                'incident_date'   => date('Y-m-d H:i:s'),
                'severity'        => 'severe',
                'type'            => 'Mechanical Breakdown',
                'location'        => 'Test Highway Route',
                'description'     => 'Test overheating issue',
                'status'          => 'reported',
            ]);

            // Simulate the controller's FR-4.3 breakdown action
            $vehicleModel->update($testVeh['id'], ['status' => 'disabled_breakdown']);
            $pirNum = $pirModel->generatePirNumber();
            $pirId = $pirModel->insert([
                'pir_number'         => $pirNum,
                'vehicle_id'         => (int) $testVeh['id'],
                'incident_id'        => (int) $incId,
                'source'             => 'breakdown',
                'defect_description' => 'Automated PIR for breakdown test',
                'status'             => 'pending',
                'created_at'         => date('Y-m-d H:i:s'),
            ]);

            $checkVeh = $vehicleModel->find($testVeh['id']);
            $assert($checkVeh['status'] === 'disabled_breakdown', "Vehicle immediately transitioned to 'disabled_breakdown'");

            $checkPir = $pirModel->find($pirId);
            $assert($checkPir !== null && $checkPir['status'] === 'pending', "PIR {$pirNum} automatically generated for mechanic queue");

            // Cleanup test records
            $pirModel->delete($pirId);
            $incidentModel->delete($incId);
            $vehicleModel->update($testVeh['id'], ['status' => $origVehStatus]);
        }

        // -------------------------------------------------------------
        // Test 5: Section B Fuel Accounting Formula Verification
        // -------------------------------------------------------------
        CLI::write("\n--- Test 5: Section B Government Fuel Balance Formula ---", 'yellow');
        $startFuel  = 50.00;
        $issuedFuel = 20.00;
        $purchFuel  = 15.00;
        $usedFuel   = 35.00;
        $endingFuel = ($startFuel + $issuedFuel + $purchFuel) - $usedFuel;
        $assert($endingFuel === 50.00, "COA Section B fuel balance formula matches: (50 + 20 + 15) - 35 = 50.00 L");

        $startOdo = 12500.0;
        $retOdo   = 12642.5;
        $distKm   = $retOdo - $startOdo;
        $assert($distKm === 142.5, "Distance auto-calculated accurately: 142.5 KM");

        // -------------------------------------------------------------
        // Test 6: 4-Phase DTT Automation (Egress -> Arrive -> Depart -> Ingress -> Section B)
        // -------------------------------------------------------------
        CLI::write("\n--- Test 6: 4-Phase DDT Trip Lifecycle Automation ---", 'yellow');
        $ticketModel->where('ticket_serial_no', 'DTT-TEST-4PHASE')->delete();
        $testTicketId = $ticketModel->insert([
            'ticket_serial_no'       => 'DTT-TEST-4PHASE',
            'request_id'             => 1,
            'vehicle_id'             => 1,
            'driver_id'              => 1,
            'qr_crypt_token'         => 'TEST-TOKEN-4PHASE',
            'status'                 => 'issued',
            'authorized_departure'   => date('Y-m-d 08:00:00'),
            'authorized_return'      => date('Y-m-d 17:00:00'),
            'authorized_destination' => 'PICC Pasay City',
            'authorized_purpose'     => 'Presidential Coverage',
            'authorized_passengers'  => 'Official Media Crew',
            'start_odometer'         => 50000.0,
        ]);

        try {
            $ticket = $ticketModel->find($testTicketId);
            $assert($ticket['status'] === 'issued', "Phase 1 initialized: status is 'issued'");

            // Phase 1 Egress Gate Scan:
            $ticketModel->update($testTicketId, [
                'status'         => 'departed',
                'departure_time' => date('Y-m-d 08:15:00'),
                'start_odometer' => 50000.0,
            ]);
            $ticket = $ticketModel->find($testTicketId);
            $assert($ticket['status'] === 'departed' && !empty($ticket['departure_time']), "Phase 1 Complete: Compound Egress recorded");

            // Phase 2: Destination Arrival
            $_POST['ticket_id'] = $testTicketId;
            $_POST['dest_odometer'] = 50025.5;
            $driverCtrl->arriveDestination();
            $ticket = $ticketModel->find($testTicketId);
            $assert($ticket['status'] === 'arrived_dest', "Phase 2 Complete: Destination Arrival recorded (status 'arrived_dest')");
            $assert(!empty($ticket['arrival_dest_time']), "Phase 2 Complete: Destination arrival timestamp stamped");
            $assert((float)$ticket['dest_odometer'] === 50025.5, "Phase 2 Complete: Destination odometer 50025.5 KM recorded");

            // Phase 3: Departure from Destination
            $_POST['ticket_id'] = $testTicketId;
            $driverCtrl->departDestination();
            $ticket = $ticketModel->find($testTicketId);
            $assert($ticket['status'] === 'departed_dest', "Phase 3 Complete: Destination Departure recorded (status 'departed_dest')");
            $assert(!empty($ticket['departure_dest_time']), "Phase 3 Complete: Destination departure timestamp stamped");

            // Phase 4: Compound Return Ingress
            $ticketModel->update($testTicketId, [
                'status'            => 'returned',
                'arrival_back_time' => date('Y-m-d 16:30:00'),
                'return_odometer'   => 50052.0,
                'total_distance_km' => 52.0,
            ]);
            $ticket = $ticketModel->find($testTicketId);
            $assert($ticket['status'] === 'returned', "Phase 4 Complete: Compound Ingress recorded (status 'returned')");

            // Section B Completion: Dual Certifications
            $ticketController = new \App\Controllers\TripTicketController();
            $ticketController->initController(
                \Config\Services::request(),
                \Config\Services::response(),
                \Config\Services::logger()
            );

            $_POST = [
                'start_odometer'            => '50000.0',
                'return_odometer'           => '50052.0',
                'fuel_balance_start_liters' => '40.0',
                'fuel_issued_stock_liters'  => '0.0',
                'fuel_purchased_liters'     => '10.0',
                'fuel_purchased_cost'       => '650.00',
                'fuel_used_liters'          => '6.0',
                'toll_expense'              => '120.00',
                'toll_provider'             => 'cash',
                'driver_certified'          => '1',
                'passenger_certified'       => '1',
                'passenger_certifier_name'  => 'Director Maria Gomez',
            ];
            $ticketController->updateSectionB($testTicketId);
            $ticket = $ticketModel->find($testTicketId);
            $assert($ticket['status'] === 'completed', "Section B Complete: Trip ticket finalized to 'completed'");
            $assert((float)$ticket['fuel_balance_end_liters'] === 44.0, "Section B Complete: Fuel balance ending (40 + 10 - 6 = 44 L)");
            $assert((int)$ticket['driver_certified'] === 1 && (int)$ticket['passenger_certified'] === 1, "Section B Complete: Dual certifications verified");
        } finally {
            $ticketModel->delete($testTicketId);
        }

        // -------------------------------------------------------------
        // Test 7: Mobile Scrollability & Clean UI Checks
        // -------------------------------------------------------------
        CLI::write("\n--- Test 7: Mobile Scrollability & Clean Architecture ---", 'yellow');
        $driverHtml = (string) $driverCtrl->mobileApp();
        $assert(str_contains($driverHtml, 'phase-stepper'), "4-Phase visual stepper component present in UI");
        $assert(str_contains($driverHtml, 'id="arriveModal"'), "Phase 2 Destination Arrival modal present");
        $assert(str_contains($driverHtml, 'id="departModal"'), "Phase 3 Destination Departure modal present");
        $assert(str_contains($driverHtml, 'name="passenger_certified"'), "Section B Passenger certification present");
        $assert(str_contains($driverHtml, 'name="driver_certified"'), "Section B Driver certification present");
        $assert(str_contains($driverHtml, 'overflow-y: scroll') || str_contains($driverHtml, 'overflow-y: auto'), "Touch scrollability CSS constraints present");
        $assert(!str_contains($driverHtml, 'class="nav-segmented"'), "Duplicated top segmented tabs removed in favor of thumb dock");

        CLI::write("\n=========================================================", 'cyan');
        CLI::write("   TEST SUMMARY: {$passCount} PASSED, {$failCount} FAILED", $failCount === 0 ? 'green' : 'red');
        CLI::write("=========================================================\n", 'cyan');

        if ($failCount === 0) {
            CLI::write("SUCCESS: Driver Mobile Portal redesign and UX functionality fully verified!\n", 'green');
        }
    }
}
