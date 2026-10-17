<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class InspectData extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:inspect';
    protected $description = 'Inspect all tables and counts in fleet_db';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        $tables = $db->listTables();

        $filter = $params[0] ?? null;

        if ($filter) {
            CLI::write("Inspecting table: {$filter}", 'yellow');
            $rows = $db->table($filter)->get()->getResultArray();
            CLI::write("Total rows: " . count($rows), 'cyan');
            foreach ($rows as $idx => $r) {
                CLI::write("--- Row {$idx} ---", 'yellow');
                foreach ($r as $k => $v) {
                    if (is_string($v) && strlen($v) > 80) {
                        $v = substr($v, 0, 77) . '...';
                    }
                    CLI::write("  {$k}: " . ($v ?? 'NULL'));
                }
            }
            return;
        }

        CLI::write("=========================================================", 'cyan');
        CLI::write("      DATABASE TABLES & RECORD COUNTS IN fleet_db         ", 'yellow');
        CLI::write("=========================================================", 'cyan');

        foreach ($tables as $t) {
            $count = $db->table($t)->countAllResults();
            CLI::write(sprintf("  %-32s : %d rows", $t, $count), $count > 0 ? 'green' : 'white');
        }
    }
}
