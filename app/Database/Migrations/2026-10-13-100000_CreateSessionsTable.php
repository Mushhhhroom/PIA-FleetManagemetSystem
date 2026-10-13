<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Session storage table for the database session handler.
 *
 * Used on deployment targets without a persistent filesystem (Vercel):
 * set `session.driver = CodeIgniter\Session\Handlers\DatabaseHandler`
 * and `session.savePath = sessions` in the environment.
 *
 * Schema matches CodeIgniter 4's DatabaseHandler expectations:
 * id (PK), ip_address, timestamp (indexed), data.
 */
class CreateSessionsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => false],
            'timestamp'  => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'null' => false],
            'data'       => ['type' => 'BLOB', 'null' => false],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('timestamp');
        $this->forge->createTable('sessions', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('sessions', true);
    }
}
