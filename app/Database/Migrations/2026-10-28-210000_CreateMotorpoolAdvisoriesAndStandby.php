<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMotorpoolAdvisoriesAndStandby extends Migration
{
    public function up()
    {
        // 1. Create motorpool_advisories table
        if (!$this->db->tableExists('motorpool_advisories')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'posted_by' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'dispatcher_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'default'    => 'Motorpool Dispatcher',
                ],
                'title' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                ],
                'category' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'default'    => 'Road Traffic',
                ],
                'severity' => [
                    'type'       => 'ENUM',
                    'constraint' => ['info', 'warning', 'danger'],
                    'default'    => 'info',
                ],
                'message' => [
                    'type' => 'TEXT',
                ],
                'is_active' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
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
            $this->forge->addKey('is_active');
            $this->forge->createTable('motorpool_advisories', true);
        }

        // 2. Add standby_checkin_at column to drivers table if not already present
        if ($this->db->tableExists('drivers') && !$this->db->fieldExists('standby_checkin_at', 'drivers')) {
            $this->forge->addColumn('drivers', [
                'standby_checkin_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'status',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('motorpool_advisories')) {
            $this->forge->dropTable('motorpool_advisories', true);
        }

        if ($this->db->tableExists('drivers') && $this->db->fieldExists('standby_checkin_at', 'drivers')) {
            $this->forge->dropColumn('drivers', 'standby_checkin_at');
        }
    }
}
