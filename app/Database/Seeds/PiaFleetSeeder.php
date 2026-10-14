<?php

namespace App\Database\Seeds;

use App\Services\SecurityQrService;
use CodeIgniter\Database\Seeder;

class PiaFleetSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // 1. PIA Offices Directory
        $offices = [
            [
                'office_code'        => 'CO-ODG',
                'office_name'        => 'Central Office - Office of the Director-General',
                'office_type'        => 'central',
                'address'            => 'PIA Bldg., Visayas Avenue, Diliman, Quezon City',
                'division_head_name' => 'Director-General Jose A. Torres Jr.',
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'office_code'        => 'CO-ADMIN',
                'office_name'        => 'Central Office - Administrative Division',
                'office_type'        => 'central',
                'address'            => 'PIA Bldg., Visayas Avenue, Diliman, Quezon City',
                'division_head_name' => 'Atty. Julius S. De Peralta',
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'office_code'        => 'CO-NID',
                'office_name'        => 'Central Office - News & Information Division',
                'office_type'        => 'central',
                'address'            => 'PIA Bldg., Visayas Avenue, Diliman, Quezon City',
                'division_head_name' => 'Dir. Maria Elena Rodriguez',
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'office_code'        => 'CO-PMD',
                'office_name'        => 'Central Office - Production & Media Division',
                'office_type'        => 'central',
                'address'            => 'PIA Bldg., Visayas Avenue, Diliman, Quezon City',
                'division_head_name' => 'Dir. Ferdinand C. Rivera',
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'office_code'        => 'RO-NCR',
                'office_name'        => 'Regional Office - National Capital Region',
                'office_type'        => 'regional',
                'address'            => 'PIA NCR, Diliman, Quezon City',
                'division_head_name' => 'Reg. Dir. Emigdio A. Gomez',
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'office_code'        => 'RO-CAR',
                'office_name'        => 'Regional Office - Cordillera Administrative Region',
                'office_type'        => 'regional',
                'address'            => 'PIA CAR, Baguio City',
                'division_head_name' => 'Reg. Dir. Helen R. Tibaldo',
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'office_code'        => 'RO-01',
                'office_name'        => 'Regional Office I - Ilocos Region',
                'office_type'        => 'regional',
                'address'            => 'PIA Region 1, San Fernando City, La Union',
                'division_head_name' => 'Reg. Dir. Jennilyne C. Role',
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'office_code'        => 'RO-03',
                'office_name'        => 'Regional Office III - Central Luzon',
                'office_type'        => 'regional',
                'address'            => 'PIA Region 3, City of San Fernando, Pampanga',
                'division_head_name' => 'Reg. Dir. William L. Beltran',
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'office_code'        => 'RO-04A',
                'office_name'        => 'Regional Office IV-A - CALABARZON',
                'office_type'        => 'regional',
                'address'            => 'PIA CALABARZON, Calamba City, Laguna',
                'division_head_name' => 'Reg. Dir. Cristina C. Arzadon',
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'office_code'        => 'RO-07',
                'office_name'        => 'Regional Office VII - Central Visayas',
                'office_type'        => 'regional',
                'address'            => 'PIA Region 7, Cebu City',
                'division_head_name' => 'Reg. Dir. Fayette C. Riñen',
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
        ];

        foreach ($offices as $office) {
            $existing = $this->db->table('offices')->where('office_code', $office['office_code'])->get()->getRow();
            if (!$existing) {
                $this->db->table('offices')->insert($office);
            }
        }

        // Get Office IDs for mapping
        $adminOffice = $this->db->table('offices')->where('office_code', 'CO-ADMIN')->get()->getRow();
        $newsOffice  = $this->db->table('offices')->where('office_code', 'CO-NID')->get()->getRow();
        $carOffice   = $this->db->table('offices')->where('office_code', 'RO-CAR')->get()->getRow();

        // 2. Official Government RBAC Users
        $piaUsers = [
            // Admin
            [
                'name'        => 'Director-General Jose A. Torres Jr.',
                'designation' => 'Director-General / Super Administrator',
                'email'       => 'admin@pia.gov.ph',
                'password'    => password_hash('Admin_PIA2026!', PASSWORD_DEFAULT),
                'role'        => 'admin',
                'office_id'   => $adminOffice ? $adminOffice->id : 1,
                'phone'       => '+63 917 555 0100',
                'status'      => 'active',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            // Approver 1 (Staff Director / Regional Director / OIC)
            [
                'name'        => 'Dir. Maria Elena Rodriguez',
                'designation' => 'Staff Director, News & Information Division',
                'email'       => 'oic.news@pia.gov.ph',
                'password'    => password_hash('Oic_News2026!', PASSWORD_DEFAULT),
                'role'        => 'approver_oic',
                'office_id'   => $newsOffice ? $newsOffice->id : 3,
                'phone'       => '+63 917 555 0101',
                'status'      => 'active',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            // Approver 2 (Administrative Division Head - Atty. Julius S. De Peralta)
            [
                'name'        => 'Atty. Julius S. De Peralta',
                'designation' => 'Head, Administrative Division',
                'email'       => 'admin.head@pia.gov.ph',
                'password'    => password_hash('AdminHead_2026!', PASSWORD_DEFAULT),
                'role'        => 'approver_admin',
                'office_id'   => $adminOffice ? $adminOffice->id : 2,
                'phone'       => '+63 917 555 0102',
                'status'      => 'active',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            // Dispatcher
            [
                'name'        => 'Engr. Roberto D. Tan',
                'designation' => 'Motorpool Dispatch Officer',
                'email'       => 'dispatcher@pia.gov.ph',
                'password'    => password_hash('Dispatch_2026!', PASSWORD_DEFAULT),
                'role'        => 'dispatcher',
                'office_id'   => $adminOffice ? $adminOffice->id : 2,
                'phone'       => '+63 918 555 0200',
                'status'      => 'active',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            // Gate Security Guard
            [
                'name'        => 'Sgt. Danilo Ramos',
                'designation' => 'Senior Compound Security Officer',
                'email'       => 'guard@pia.gov.ph',
                'password'    => password_hash('Guard_2026!', PASSWORD_DEFAULT),
                'role'        => 'guard',
                'office_id'   => $adminOffice ? $adminOffice->id : 2,
                'phone'       => '+63 918 555 0400',
                'status'      => 'active',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            // Resident COA Auditor
            [
                'name'        => 'State Auditor IV Teresa Santos',
                'designation' => 'COA Resident Fleet Auditor',
                'email'       => 'auditor@pia.gov.ph',
                'password'    => password_hash('Auditor_2026!', PASSWORD_DEFAULT),
                'role'        => 'auditor',
                'office_id'   => $adminOffice ? $adminOffice->id : 2,
                'phone'       => '+63 919 555 0500',
                'status'      => 'active',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            // Requestor
            [
                'name'        => 'Juan Dela Cruz',
                'designation' => 'Senior Information Officer, Coverage Unit',
                'email'       => 'requestor@pia.gov.ph',
                'password'    => password_hash('Requestor_2026!', PASSWORD_DEFAULT),
                'role'        => 'requestor',
                'office_id'   => $newsOffice ? $newsOffice->id : 3,
                'phone'       => '+63 919 555 0600',
                'status'      => 'active',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            // Driver user account for Rodrigo Santos
            [
                'name'        => 'Rodrigo Santos',
                'designation' => 'Senior Official Driver (Central Pool)',
                'email'       => 'driver.santos@pia.gov.ph',
                'password'    => password_hash('Driver_2026!', PASSWORD_DEFAULT),
                'role'        => 'driver',
                'office_id'   => $adminOffice ? $adminOffice->id : 2,
                'phone'       => '+63 917 111 2233',
                'status'      => 'active',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            // Driver user account for Eduardo Reyes
            [
                'name'        => 'Eduardo Reyes',
                'designation' => 'Official Driver (Regional Pool)',
                'email'       => 'driver.reyes@pia.gov.ph',
                'password'    => password_hash('Driver_2026!', PASSWORD_DEFAULT),
                'role'        => 'driver',
                'office_id'   => $adminOffice ? $adminOffice->id : 2,
                'phone'       => '+63 918 222 3344',
                'status'      => 'active',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ];

        foreach ($piaUsers as $u) {
            $existing = $this->db->table('users')->where('email', $u['email'])->get()->getRow();
            if ($existing) {
                $this->db->table('users')->where('email', $u['email'])->update([
                    'role'        => $u['role'],
                    'name'        => $u['name'],
                    'designation' => $u['designation'],
                    'office_id'   => $u['office_id'],
                ]);
            } else {
                $this->db->table('users')->insert($u);
            }
        }

        // Link driver records to their user accounts
        $drvSantos = $this->db->table('users')->where('email', 'driver.santos@pia.gov.ph')->get()->getRow();
        if ($drvSantos) {
            $this->db->table('drivers')->where('driver_code', 'DRV-101')->update(['user_id' => $drvSantos->id]);
        }
        $drvReyes = $this->db->table('users')->where('email', 'driver.reyes@pia.gov.ph')->get()->getRow();
        if ($drvReyes) {
            $this->db->table('drivers')->where('driver_code', 'DRV-102')->update(['user_id' => $drvReyes->id]);
        }

        // 3. Realistic Government Fleet Vehicles for Philippine Information Agency
        $govVehicles = [
            [
                'vehicle_code'        => 'PIA-V01',
                'plate_number'        => 'SJL-4192',
                'vin'                 => 'JTFSS22P10023401',
                'make'                => 'Toyota',
                'model'               => 'HiAce Grandia 3.0D Tourer',
                'year'                => 2023,
                'type'                => 'delivery_van', // used as multi-passenger van
                'fuel_type'           => 'diesel',
                'max_payload_kg'      => 1200.00,
                'fuel_capacity_liters'=> 70.00,
                'odometer_km'         => 34250.00,
                'current_latitude'    => 14.650800, // PIA Central Office, Diliman QC
                'current_longitude'   => 121.050400,
                'current_speed'       => 0.00,
                'current_fuel_level'  => 85.00,
                'engine_status'       => 'off',
                'status'              => 'active',
                'current_driver_id'   => null,
                'fleet_category'          => 'pool',
                'assigned_official'       => null,
                'lto_registration_expiry' => date('Y-m-d', strtotime('+180 days')),
                'gsis_insurance_expiry'   => date('Y-m-d', strtotime('+45 days')),
                'last_service_date'   => date('Y-m-d', strtotime('-30 days')),
                'next_service_km'     => 35000.00,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'vehicle_code'        => 'PIA-V02',
                'plate_number'        => 'SAB-8821',
                'vin'                 => 'MHFGB42G50067802',
                'make'                => 'Toyota',
                'model'               => 'Fortuner 2.8L 4x4 LTD',
                'year'                => 2024,
                'type'                => 'suv',
                'fuel_type'           => 'diesel',
                'max_payload_kg'      => 750.00,
                'fuel_capacity_liters'=> 80.00,
                'odometer_km'         => 18920.00,
                'current_latitude'    => 14.650800,
                'current_longitude'   => 121.050400,
                'current_speed'       => 0.00,
                'current_fuel_level'  => 90.00,
                'engine_status'       => 'off',
                'status'              => 'active',
                'current_driver_id'   => null,
                // FR-2.1 dedicated executive vehicle (senior official pool)
                'fleet_category'          => 'dedicated',
                'assigned_official'       => 'Regional Director Maria L. Santos',
                'lto_registration_expiry' => date('Y-m-d', strtotime('+12 days')),  // inside FR-7.1 30-day window
                'gsis_insurance_expiry'   => date('Y-m-d', strtotime('+240 days')),
                'last_service_date'   => date('Y-m-d', strtotime('-45 days')),
                'next_service_km'     => 20000.00,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'vehicle_code'        => 'PIA-V03',
                'plate_number'        => 'SKE-5510',
                'vin'                 => 'JN8AS52B40091203',
                'make'                => 'Nissan',
                'model'               => 'NV350 Urvan Premium',
                'year'                => 2022,
                'type'                => 'delivery_van',
                'fuel_type'           => 'diesel',
                'max_payload_kg'      => 1400.00,
                'fuel_capacity_liters'=> 65.00,
                'odometer_km'         => 48800.00,
                'current_latitude'    => 14.650800,
                'current_longitude'   => 121.050400,
                'current_speed'       => 0.00,
                'current_fuel_level'  => 70.00,
                'engine_status'       => 'off',
                'status'              => 'active',
                'current_driver_id'   => null,
                'fleet_category'          => 'pool',
                'assigned_official'       => null,
                'lto_registration_expiry' => date('Y-m-d', strtotime('+95 days')),
                'gsis_insurance_expiry'   => date('Y-m-d', strtotime('+19 days')),  // inside FR-7.1 30-day window
                'last_service_date'   => date('Y-m-d', strtotime('-15 days')),
                'next_service_km'     => 50000.00,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'vehicle_code'        => 'PIA-V04',
                'plate_number'        => 'SKZ-7734',
                'vin'                 => 'MPB4FR5460012304',
                'make'                => 'Isuzu',
                'model'               => 'D-Max 3.0 4x4 Boondock',
                'year'                => 2023,
                'type'                => 'pickup',
                'fuel_type'           => 'diesel',
                'max_payload_kg'      => 1050.00,
                'fuel_capacity_liters'=> 76.00,
                'odometer_km'         => 29400.00,
                'current_latitude'    => 14.650800,
                'current_longitude'   => 121.050400,
                'current_speed'       => 0.00,
                'current_fuel_level'  => 60.00,
                'engine_status'       => 'off',
                'status'              => 'active',
                'current_driver_id'   => null,
                'fleet_category'          => 'pool',
                'assigned_official'       => null,
                'lto_registration_expiry' => date('Y-m-d', strtotime('+300 days')),
                'gsis_insurance_expiry'   => date('Y-m-d', strtotime('+300 days')),
                'last_service_date'   => date('Y-m-d', strtotime('-25 days')),
                'next_service_km'     => 30000.00,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
        ];

        foreach ($govVehicles as $v) {
            $existing = $this->db->table('vehicles')->where('plate_number', $v['plate_number'])->get()->getRow();
            if (!$existing) {
                $this->db->table('vehicles')->insert($v);
            } else {
                // BRD FR-2.1 / FR-7.1 columns are added by a later migration —
                // keep already-seeded rows in sync on re-seed.
                $this->db->table('vehicles')->where('plate_number', $v['plate_number'])->update([
                    'fleet_category'          => $v['fleet_category'],
                    'assigned_official'       => $v['assigned_official'],
                    'lto_registration_expiry' => $v['lto_registration_expiry'],
                    'gsis_insurance_expiry'   => $v['gsis_insurance_expiry'],
                ]);
            }
        }

        // 4. Initialize PMS Records for vehicles (5,000 km intervals)
        $allVehicles = $this->db->table('vehicles')->get()->getResult();
        foreach ($allVehicles as $veh) {
            $pmsExists = $this->db->table('pms_records')->where('vehicle_id', $veh->id)->get()->getRow();
            $currentOdo = (float)$veh->odometer_km;
            $interval = 5000.00;
            $nextPms = ceil(($currentOdo + 1) / $interval) * $interval;
            $diff = $nextPms - $currentOdo;

            $status = 'ok';
            $isLocked = 0;
            if ($diff <= 0) {
                $status = 'overdue';
                $isLocked = 1;
            } elseif ($diff <= 500) {
                $status = 'due_soon';
            }

            if (!$pmsExists) {
                $this->db->table('pms_records')->insert([
                    'vehicle_id'        => $veh->id,
                    'last_pms_odometer' => max(0, $nextPms - $interval),
                    'next_pms_odometer' => $nextPms,
                    'pms_interval_km'   => $interval,
                    'is_locked'         => $isLocked,
                    'status'            => $status,
                    'last_pms_date'     => $veh->last_service_date,
                    'notes'             => "Routine 5,000 KM PMS Cycle. Next inspection at {$nextPms} km.",
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);
            }
        }

        // 5. Seed Demonstration Vehicle Request Slips (ADMIN-F-018 rev2)
        $reqUser = $this->db->table('users')->where('email', 'requestor@pia.gov.ph')->get()->getRow();
        $oicUser = $this->db->table('users')->where('email', 'oic.news@pia.gov.ph')->get()->getRow();
        $admUser = $this->db->table('users')->where('email', 'admin.head@pia.gov.ph')->get()->getRow();
        $guardUser = $this->db->table('users')->where('email', 'guard@pia.gov.ph')->get()->getRow();
        // BRD revision: Tier 1 approval authority rests with the Motorpool Dispatcher
        $dispUser = $this->db->table('users')->where('role', 'dispatcher')->where('status', 'active')->get()->getRow();
        $tier1User = $dispUser ?: $oicUser;

        $hiace = $this->db->table('vehicles')->where('plate_number', 'SJL-4192')->get()->getRow() ?? $allVehicles[0];
        $fortuner = $this->db->table('vehicles')->where('plate_number', 'SAB-8821')->get()->getRow() ?? ($allVehicles[1] ?? $allVehicles[0]);
        $driverSantos = $this->db->table('drivers')->where('driver_code', 'DRV-101')->get()->getRow();
        $driverReyes  = $this->db->table('drivers')->where('driver_code', 'DRV-102')->get()->getRow();

        // Sample Request 1: Approved and Dispatched with live e-DTT ready for Gate clearance
        $existingReq1 = $this->db->table('trip_requests')->where('request_number', 'VRS-2026-0001')->get()->getRow();
        if (!$existingReq1 && $reqUser && $oicUser && $admUser) {
            $depTime = date('Y-m-d 08:30:00', strtotime('+1 day'));
            $retTime = date('Y-m-d 17:00:00', strtotime('+1 day'));

            $this->db->table('trip_requests')->insert([
                'request_number'         => 'VRS-2026-0001',
                'requestor_id'           => $reqUser->id,
                'requestor_name'         => $reqUser->name,
                'office_id'              => $newsOffice ? $newsOffice->id : 1,
                'office_scope'           => 'central',
                'destination'            => 'Malacañang Palace Press Briefing Room, Manila',
                'purpose'                => 'Official coverage and live broadcast of the Presidential Press Conference on economic relief programs.',
                'passenger_names'        => "1. Juan Dela Cruz (Senior Information Officer)\n2. Mark Anthony Cruz (Videographer/Cameraman)\n3. Sarah Jane Perez (Broadcast Tech Specialist)",
                'passenger_count'        => 3,
                'departure_time'         => $depTime,
                'return_time'            => $retTime,
                'requested_driver_id'    => $driverSantos ? $driverSantos->id : null,
                'requested_vehicle_type' => 'van',
                'is_rush_request'        => 0,
                'status'                 => 'dispatched',
                'oic_approver_id'        => $oicUser->id,
                'oic_action'             => 'approved',
                'oic_action_at'          => date('Y-m-d H:i:s', strtotime('-12 hours')),
                'oic_remarks'            => 'Endorsed. Essential state media coverage.',
                'admin_approver_id'      => $admUser->id,
                'admin_action'           => 'approved',
                'admin_action_at'        => date('Y-m-d H:i:s', strtotime('-6 hours')),
                'admin_remarks'          => 'Approved for dispatch. Driver Rodrigo Santos assigned with Toyota HiAce.',
                'sla_deadline'           => date('Y-m-d H:i:s', strtotime('+12 hours')),
                'created_at'             => date('Y-m-d H:i:s', strtotime('-18 hours')),
                'updated_at'             => $now,
            ]);
            $req1Id = $this->db->insertID();

            // Create corresponding Driver's Trip Ticket (ADMIN-F-001 rev1)
            $cryptToken = (new SecurityQrService())->generateToken(
                'DTT-2026-0001',
                $hiace->plate_number,
                $driverSantos ? $driverSantos->driver_code : 'DRV-101',
                $depTime
            );

            $this->db->table('trip_tickets')->insert([
                'ticket_serial_no'          => 'DTT-2026-0001',
                'request_id'                => $req1Id,
                'vehicle_id'                => $hiace->id,
                'driver_id'                 => $driverSantos ? $driverSantos->id : 1,
                'qr_crypt_token'            => $cryptToken,
                'status'                    => 'issued',
                'authorized_departure'      => $depTime,
                'authorized_return'         => $retTime,
                'authorized_passengers'     => "Juan Dela Cruz, Mark Anthony Cruz, Sarah Jane Perez",
                'authorized_destination'    => 'Malacañang Palace Press Briefing Room, Manila',
                'authorized_purpose'        => 'Official coverage and live broadcast of the Presidential Press Conference.',
                'admin_approver_name'       => 'Atty. Julius S. De Peralta',
                'start_odometer'            => $hiace->odometer_km,
                'fuel_balance_start_liters' => 55.00,
                'fuel_issued_stock_liters'  => 20.00,
                'fuel_purchased_liters'     => 0.00,
                'fuel_used_liters'          => 0.00,
                'fuel_balance_end_liters'   => 75.00,
                'created_at'                => date('Y-m-d H:i:s', strtotime('-6 hours')),
                'updated_at'                => $now,
            ]);
        }

        // Sample Request 2: SAME-DAY RUSH REQUEST with attached justification memo (Pending Motorpool Dispatcher Review — Tier 1 SLA timer)
        $existingReq2 = $this->db->table('trip_requests')->where('request_number', 'VRS-2026-0002')->get()->getRow();
        if (!$existingReq2 && $reqUser && $tier1User) {
            $rushDep = date('Y-m-d H:i:s', strtotime('+4 hours'));
            $rushRet = date('Y-m-d H:i:s', strtotime('+10 hours'));

            $this->db->table('trip_requests')->insert([
                'request_number'         => 'VRS-2026-0002',
                'requestor_id'           => $reqUser->id,
                'requestor_name'         => $reqUser->name,
                'office_id'              => $newsOffice ? $newsOffice->id : 1,
                'office_scope'           => 'central',
                'destination'            => 'NDRRMC Operations Center, Camp Aguinaldo, QC',
                'purpose'                => 'Urgent Typhoon Situational Briefing coverage and emergency media pooling.',
                'passenger_names'        => "1. Juan Dela Cruz (Senior Information Officer)\n2. Roberto Gomez (Field Reporter)\n3. Joel Bautista (Audio-Visual Tech)",
                'passenger_count'        => 3,
                'departure_time'         => $rushDep,
                'return_time'            => $rushRet,
                'requested_driver_id'    => $driverReyes ? $driverReyes->id : null,
                'requested_vehicle_type' => 'suv',
                'is_rush_request'        => 1, // Same-day emergency flag (BR-03)
                'justification_file'     => 'uploads/justifications/sample_emergency_memo_ndrrmc.pdf',
                'justification_notes'    => 'URGENT: Rapid deployment required per NDRRMC Red Alert advisory. Media briefing called on 2 hours notice.',
                'status'                 => 'pending_oic',
                'oic_approver_id'        => $tier1User->id,
                'oic_action'             => 'pending',
                'sla_deadline'           => date('Y-m-d H:i:s', strtotime('+22 hours')),
                'created_at'             => date('Y-m-d H:i:s', strtotime('-2 hours')),
                'updated_at'             => $now,
            ]);
        }

        // Sample Request 3: Endorsed by OIC, currently waiting on Atty. Julius S. De Peralta (pending_admin)
        $existingReq3 = $this->db->table('trip_requests')->where('request_number', 'VRS-2026-0003')->get()->getRow();
        if (!$existingReq3 && $reqUser && $oicUser && $admUser) {
            $dep3 = date('Y-m-d 09:00:00', strtotime('+2 days'));
            $ret3 = date('Y-m-d 18:00:00', strtotime('+2 days'));

            $this->db->table('trip_requests')->insert([
                'request_number'         => 'VRS-2026-0003',
                'requestor_id'           => $reqUser->id,
                'requestor_name'         => $reqUser->name,
                'office_id'              => $newsOffice ? $newsOffice->id : 1,
                'office_scope'           => 'central',
                'destination'            => 'DOH Central Compound, Tayuman, Sta. Cruz, Manila',
                'purpose'                => 'Coverage of National Immunization Program press conference & health forum.',
                'passenger_names'        => "1. Grace Lim (Information Officer)\n2. Paul Ramos (Photographer)",
                'passenger_count'        => 2,
                'departure_time'         => $dep3,
                'return_time'            => $ret3,
                'requested_driver_id'    => null,
                'requested_vehicle_type' => 'van',
                'is_rush_request'        => 0,
                'status'                 => 'pending_admin',
                'oic_approver_id'        => $oicUser->id,
                'oic_action'             => 'approved',
                'oic_action_at'          => date('Y-m-d H:i:s', strtotime('-3 hours')),
                'oic_remarks'            => 'Endorsed for administrative clearance.',
                'admin_approver_id'      => $admUser->id,
                'admin_action'           => 'pending',
                'sla_deadline'           => date('Y-m-d H:i:s', strtotime('+21 hours')),
                'created_at'             => date('Y-m-d H:i:s', strtotime('-5 hours')),
                'updated_at'             => $now,
            ]);
        }

        // Sample Request 4: Fully Completed historical trip with full Fuel Balance & Dual Certifications
        $existingReq4 = $this->db->table('trip_requests')->where('request_number', 'VRS-2026-0004')->get()->getRow();
        if (!$existingReq4 && $reqUser && $oicUser && $admUser && $guardUser) {
            $pastDep = date('Y-m-d 07:00:00', strtotime('-3 days'));
            $pastRet = date('Y-m-d 19:30:00', strtotime('-3 days'));

            $this->db->table('trip_requests')->insert([
                'request_number'         => 'VRS-2026-0004',
                'requestor_id'           => $reqUser->id,
                'requestor_name'         => $reqUser->name,
                'office_id'              => $newsOffice ? $newsOffice->id : 1,
                'office_scope'           => 'central',
                'destination'            => 'Clark Freeport Zone, Pampanga',
                'purpose'                => 'Media coverage of Central Luzon Investment Summit and infrastructure tour.',
                'passenger_names'        => "1. Juan Dela Cruz\n2. Andrea Soriano\n3. Kenneth Lee",
                'passenger_count'        => 3,
                'departure_time'         => $pastDep,
                'return_time'            => $pastRet,
                'requested_driver_id'    => $driverSantos ? $driverSantos->id : 1,
                'requested_vehicle_type' => 'van',
                'is_rush_request'        => 0,
                'status'                 => 'completed',
                'oic_approver_id'        => $oicUser->id,
                'oic_action'             => 'approved',
                'oic_action_at'          => date('Y-m-d H:i:s', strtotime('-4 days')),
                'admin_approver_id'      => $admUser->id,
                'admin_action'           => 'approved',
                'admin_action_at'        => date('Y-m-d H:i:s', strtotime('-3 days 20 hours')),
                'created_at'             => date('Y-m-d H:i:s', strtotime('-5 days')),
                'updated_at'             => date('Y-m-d H:i:s', strtotime('-3 days')),
            ]);
            $req4Id = $this->db->insertID();

            $token4 = (new SecurityQrService())->generateToken(
                'DTT-2026-0002',
                $hiace->plate_number,
                $driverSantos ? $driverSantos->driver_code : 'DRV-101',
                $pastDep
            );
            $startOdo = 34040.00;
            $endOdo   = 34250.00;
            $distKm   = 210.00;
            $usedFuel = 21.00; // 10 km/L

            $this->db->table('trip_tickets')->insert([
                'ticket_serial_no'          => 'DTT-2026-0002',
                'request_id'                => $req4Id,
                'vehicle_id'                => $hiace->id,
                'driver_id'                 => $driverSantos ? $driverSantos->id : 1,
                'qr_crypt_token'            => $token4,
                'status'                    => 'completed',
                'authorized_departure'      => $pastDep,
                'authorized_return'         => $pastRet,
                'authorized_passengers'     => "Juan Dela Cruz, Andrea Soriano, Kenneth Lee",
                'authorized_destination'    => 'Clark Freeport Zone, Pampanga',
                'authorized_purpose'        => 'Media coverage of Central Luzon Investment Summit.',
                'admin_approver_name'       => 'Atty. Julius S. De Peralta',
                'departure_time'            => $pastDep,
                'arrival_dest_time'         => date('Y-m-d 09:30:00', strtotime('-3 days')),
                'departure_dest_time'       => date('Y-m-d 16:45:00', strtotime('-3 days')),
                'arrival_back_time'         => $pastRet,
                'start_odometer'            => $startOdo,
                'dest_odometer'             => 34145.00,
                'return_odometer'           => $endOdo,
                'total_distance_km'         => $distKm,
                // Fuel formula: Balance End = Balance Start (50) + Issued (0) + Purchased (30) - Used (21) = 59
                'fuel_balance_start_liters' => 50.00,
                'fuel_issued_stock_liters'  => 0.00,
                'fuel_purchased_liters'     => 30.00,
                'fuel_purchased_cost'       => 1860.00,
                'fuel_used_liters'          => $usedFuel,
                'fuel_balance_end_liters'   => 59.00,
                'fuel_efficiency_kml'       => 10.00,
                'fuel_anomaly_flag'         => 0,
                'driver_certified'          => 1,
                'driver_certified_at'       => $pastRet,
                'passenger_certified'       => 1,
                'passenger_certifier_name'  => 'Juan Dela Cruz',
                'passenger_certified_at'    => $pastRet,
                'created_at'                => date('Y-m-d H:i:s', strtotime('-3 days 20 hours')),
                'updated_at'                => $pastRet,
            ]);
            $dtt4Id = $this->db->insertID();

            // Gate logs for this completed trip
            $this->db->table('gate_logs')->insert([
                'trip_ticket_id'    => $dtt4Id,
                'event_type'        => 'egress',
                'guard_user_id'     => $guardUser->id,
                'guard_name'        => $guardUser->name,
                'scanned_at'        => $pastDep,
                'odometer_reading'  => $startOdo,
                'security_status'   => 'cleared',
                'odometer_verified' => 1,
                'remarks'           => 'Vehicle cleared for egress. All passenger credentials verified.',
                'created_at'        => $pastDep,
            ]);
            $this->db->table('gate_logs')->insert([
                'trip_ticket_id'    => $dtt4Id,
                'event_type'        => 'ingress',
                'guard_user_id'     => $guardUser->id,
                'guard_name'        => $guardUser->name,
                'scanned_at'        => $pastRet,
                'odometer_reading'  => $endOdo,
                'security_status'   => 'cleared',
                'odometer_verified' => 1,
                'remarks'           => 'Vehicle returned safely to PIA motorpool compound. Odometer verified.',
                'created_at'        => $pastRet,
            ]);
        }
    }
}
