<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Closes the last duplicate-merge gaps in reference data: department and
 * office NAMES (their codes, and all serial/number columns across the
 * system, already carry unique constraints from the base schema).
 *
 * MySQL unique indexes permit multiple NULLs, so nullable rows are safe.
 */
class AddReferenceNameUniqueIndexes extends Migration
{
    private array $indexes = [
        ['departments', 'department_name', 'uq_departments_department_name'],
        ['offices', 'office_name', 'uq_offices_office_name'],
    ];

    public function up()
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;
        foreach ($this->indexes as [$table, $column, $index]) {
            if (!$db->tableExists($table) || !$db->fieldExists($column, $table)) {
                continue;
            }
            $exists = $db->query(
                "SELECT COUNT(*) AS c FROM information_schema.STATISTICS
                 WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? AND non_unique = 0",
                [$table, $column]
            )->getRow()->c ?? 0;
            if ((int) $exists > 0) {
                continue; // already unique — never stack duplicate constraints
            }
            $db->query("ALTER TABLE `{$table}` ADD UNIQUE `{$index}` (`{$column}`)");
        }
    }

    public function down()
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = $this->db;
        foreach ($this->indexes as [$table, , $index]) {
            if (!$db->tableExists($table)) {
                continue;
            }
            $exists = $db->query(
                "SELECT COUNT(*) AS c FROM information_schema.STATISTICS
                 WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?",
                [$table, $index]
            )->getRow()->c ?? 0;
            if ((int) $exists > 0) {
                $db->query("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
            }
        }
    }
}
