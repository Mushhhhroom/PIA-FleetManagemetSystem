<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSystemModuleControls extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'module_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'module_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'category' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'route_prefix' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'icon' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'fa-cube',
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'is_enabled' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'disabled_roles' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'maintenance_reason' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'default'    => 'Temporarily offline for scheduled maintenance and bug resolution.',
            ],
            'updated_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('system_module_controls', true);
    }

    public function down()
    {
        $this->forge->dropTable('system_module_controls', true);
    }
}
