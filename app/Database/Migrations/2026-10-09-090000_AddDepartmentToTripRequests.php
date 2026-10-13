<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds Department Information to the Vehicle Request Slip (VRS).
 *
 *  - Creates the `departments` lookup table (the dropdown source).
 *  - Adds a `department_id` column on `trip_requests`.
 *  - Seeds the table with the standard PIA department roster.
 */
class AddDepartmentToTripRequests extends Migration
{
    public function up()
    {
        // ------------------------------------------------------------------
        // 1. departments lookup table (dropdown source)
        // ------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'department_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'department_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'head_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'inactive'],
                'default'    => 'active',
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
        $this->forge->addKey('status');
        $this->forge->createTable('departments', true);

        // ------------------------------------------------------------------
        // 2. department_id on trip_requests (nullable: legacy rows have none)
        // ------------------------------------------------------------------
        $this->db->query("ALTER TABLE `trip_requests`
            ADD COLUMN `department_id` INT UNSIGNED NULL AFTER `office_id`");

        // ------------------------------------------------------------------
        // 3. Seed the standard PIA department roster (idempotent)
        // ------------------------------------------------------------------
        $now = date('Y-m-d H:i:s');
        $departments = [
            ['DG-OFFICE',    "Director-General's Office",              'Office of the Director-General',                                    'Dir. Jose A. Torres Jr.'],
            ['ADM-DIV',      'Administrative Division',                'General administration, logistics and motorpool coordination',       'Atty. Julius S. De Peralta'],
            ['FIN-DIV',      'Finance and Accounting Division',        'Budget utilization, disbursement and COA liquidation',              'Ms. Cristina V. Bautista'],
            ['HRM-DIV',      'Human Resource Management Division',     'Personnel, travel authorities and official business clearances',    'Mr. Emmanuel R. Dizon'],
            ['PLN-DIV',      'Planning and Operations Division',       'Program planning, monitoring and field operations',                 'Engr. Ricardo M. Salazar'],
            ['NID',          'News and Information Division',          'News release, press briefing and public information campaigns',    'Ms. Patricia L. Gomez'],
            ['PMD',          'Production and Media Division',          'Audio-visual production, multimedia and content development',       'Mr. Adrian C. Mendoza'],
            ['PAO-DIV',      'Public Assistance and Complaints Desk',  'Citizen feedback, complaints and public assistance coordination',   'Ms. Rosalinda P. Aguilar'],
            ['ITSD',         'Information Technology Services Division','Network, systems administration and digital services',              'Mr. Kevin B. Rocero'],
            ['ROPS-DIV',     'Regional Operations Division',           'Field operations and regional office coordination',                 'Ms. Angelica T. Ramos'],
        ];

        foreach ($departments as [$code, $name, $desc, $head]) {
            $exists = $this->db->table('departments')
                ->where('department_code', $code)
                ->get()
                ->getRow();
            if (!$exists) {
                $this->db->table('departments')->insert([
                    'department_code' => $code,
                    'department_name' => $name,
                    'description'     => $desc,
                    'head_name'       => $head,
                    'status'          => 'active',
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
            }
        }
    }

    public function down()
    {
        $this->db->query("ALTER TABLE `trip_requests` DROP COLUMN `department_id`");
        $this->forge->dropTable('departments', true);
    }
}
