<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePiaFleetTables extends Migration
{
    public function up()
    {
        // 1. Expand User Roles to support Government RBAC
        $this->db->query("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('admin', 'dispatcher', 'maintenance', 'driver', 'requestor', 'approver_admin', 'guard', 'auditor') DEFAULT 'requestor'");
        $this->db->query("ALTER TABLE `users` ADD COLUMN `office_id` INT UNSIGNED NULL AFTER `role`");
        $this->db->query("ALTER TABLE `users` ADD COLUMN `designation` VARCHAR(100) NULL AFTER `name`");

        // 2. Offices Table (Central, Regional, Satellite offices)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'office_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'office_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'office_type' => [
                'type'       => 'ENUM',
                'constraint' => ['central', 'regional', 'satellite'],
                'default'    => 'central',
            ],
            'address' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'division_head_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('offices', true);

        // 3. Trip Requests Table (ADMIN-F-018 rev2: Vehicle Request Slip)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'request_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'requestor_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'requestor_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'office_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'office_scope' => [
                'type'       => 'ENUM',
                'constraint' => ['central', 'regional', 'satellite'],
                'default'    => 'central',
            ],
            'destination' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'purpose' => [
                'type' => 'TEXT',
            ],
            'passenger_names' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'passenger_count' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 1,
            ],
            'departure_time' => [
                'type' => 'DATETIME',
            ],
            'return_time' => [
                'type' => 'DATETIME',
            ],
            'requested_driver_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'requested_vehicle_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            // BR-01 & BR-03: 24h Advance Notice & Same-Day Justification Gate
            'is_rush_request' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'justification_file' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'justification_notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            // Overall Status
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending_dispatcher', 'pending_admin', 'approved', 'dispatched', 'completed', 'rejected', 'expired'],
                'default'    => 'pending_dispatcher',
            ],
            // Approver 1: Motorpool Dispatcher (Tier 1 Review)
            'dispatcher_approver_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'dispatcher_action' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'approved', 'rejected'],
                'default'    => 'pending',
            ],
            'dispatcher_action_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'dispatcher_remarks' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            // Approver 2: Administrative Division Head (Atty. Julius S. De Peralta)
            'admin_approver_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'admin_action' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'approved', 'rejected'],
                'default'    => 'pending',
            ],
            'admin_action_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'admin_remarks' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            // BR-02: 24-Hour Approval SLA
            'sla_deadline' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'is_sla_breached' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('requestor_id');
        $this->forge->addKey('status');
        $this->forge->createTable('trip_requests', true);

        // 4. Trip Tickets Table (ADMIN-F-001 rev1: Driver's Trip Ticket)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ticket_serial_no' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'request_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'vehicle_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'driver_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            // Security: Cryptographically Signed HMAC-SHA256 QR Token
            'qr_crypt_token' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'unique'     => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['issued', 'departed', 'arrived_dest', 'departed_dest', 'returned', 'completed', 'cancelled'],
                'default'    => 'issued',
            ],
            // Section A: Authority to Travel (Auto-populated from VRS)
            'authorized_departure' => [
                'type' => 'DATETIME',
            ],
            'authorized_return' => [
                'type' => 'DATETIME',
            ],
            'authorized_passengers' => [
                'type' => 'TEXT',
            ],
            'authorized_destination' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'authorized_purpose' => [
                'type' => 'TEXT',
            ],
            'admin_approver_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => 'Atty. Julius S. De Peralta',
            ],
            // Section B: Driver Trip Log Timeline & Odometer
            'departure_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'arrival_dest_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'departure_dest_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'arrival_back_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'start_odometer' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
            ],
            'dest_odometer' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
            ],
            'return_odometer' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
            ],
            'total_distance_km' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ],
            // Section B: Fuel Balance Formula
            // Balance = Start Tank + Stock + Purchased - Used
            'fuel_balance_start_liters' => [
                'type'       => 'DECIMAL',
                'constraint' => '8,2',
                'default'    => 0.00,
            ],
            'fuel_issued_stock_liters' => [
                'type'       => 'DECIMAL',
                'constraint' => '8,2',
                'default'    => 0.00,
            ],
            'fuel_purchased_liters' => [
                'type'       => 'DECIMAL',
                'constraint' => '8,2',
                'default'    => 0.00,
            ],
            'fuel_purchased_cost' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ],
            'fuel_used_liters' => [
                'type'       => 'DECIMAL',
                'constraint' => '8,2',
                'default'    => 0.00,
            ],
            'fuel_balance_end_liters' => [
                'type'       => 'DECIMAL',
                'constraint' => '8,2',
                'default'    => 0.00,
            ],
            'fuel_efficiency_kml' => [
                'type'       => 'DECIMAL',
                'constraint' => '8,2',
                'default'    => 0.00,
            ],
            'fuel_anomaly_flag' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            // Consumables & Lubricants
            'gear_oil_liters' => [
                'type'       => 'DECIMAL',
                'constraint' => '6,2',
                'default'    => 0.00,
            ],
            'lube_oil_liters' => [
                'type'       => 'DECIMAL',
                'constraint' => '6,2',
                'default'    => 0.00,
            ],
            'grease_units' => [
                'type'       => 'DECIMAL',
                'constraint' => '6,2',
                'default'    => 0.00,
            ],
            // Dual Digital Certifications
            'driver_certified' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'driver_certified_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'passenger_certified' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'passenger_certifier_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'passenger_certified_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('request_id');
        $this->forge->addKey('vehicle_id');
        $this->forge->addKey('driver_id');
        $this->forge->createTable('trip_tickets', true);

        // 5. Gate Logs Table (Security checkpoint entry/exit timestamps & odometers)
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'trip_ticket_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'event_type' => [
                'type'       => 'ENUM',
                'constraint' => ['egress', 'ingress'],
            ],
            'guard_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'guard_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'scanned_at' => [
                'type' => 'DATETIME',
            ],
            'odometer_reading' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'security_status' => [
                'type'       => 'ENUM',
                'constraint' => ['cleared', 'flagged', 'rejected'],
                'default'    => 'cleared',
            ],
            'odometer_verified' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'remarks' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('trip_ticket_id');
        $this->forge->createTable('gate_logs', true);

        // 6. Preventive Maintenance (5,000 km threshold tracking & auto-lock)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'vehicle_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'unique'     => true,
            ],
            'last_pms_odometer' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ],
            'next_pms_odometer' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 5000.00,
            ],
            'pms_interval_km' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 5000.00,
            ],
            'is_locked' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['ok', 'due_soon', 'overdue', 'in_service'],
                'default'    => 'ok',
            ],
            'last_pms_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('pms_records', true);

        // 7. System Notifications Table (SLA countdowns, approvals, rush request alerts)
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'message' => [
                'type' => 'TEXT',
            ],
            'type' => [
                'type'       => 'ENUM',
                'constraint' => ['info', 'warning', 'danger', 'success'],
                'default'    => 'info',
            ],
            'link' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'is_read' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'is_read']);
        $this->forge->createTable('system_notifications', true);
    }

    public function down()
    {
        $this->forge->dropTable('system_notifications', true);
        $this->forge->dropTable('pms_records', true);
        $this->forge->dropTable('gate_logs', true);
        $this->forge->dropTable('trip_tickets', true);
        $this->forge->dropTable('trip_requests', true);
        $this->forge->dropTable('offices', true);
    }
}
