<?php

namespace App\Libraries;

use SQLite3;

/**
 * Automatic SQLite3 database initializer for Vercel serverless deployments
 * when no external DB_HOST (MySQL/PostgreSQL) is configured.
 */
class VercelSqliteBootstrap
{
    public static function ensureDatabase(string $sqlitePath, string $snapshotPath): void
    {
        if (is_file($sqlitePath) && filesize($sqlitePath) > 4096) {
            return;
        }

        if (! is_file($snapshotPath) || ! class_exists(SQLite3::class)) {
            return;
        }

        $json = file_get_contents($snapshotPath);
        if ($json === false) {
            return;
        }

        $snapshot = json_decode($json, true);
        if (! is_array($snapshot) || empty($snapshot['tables'])) {
            return;
        }

        $dir = dirname($sqlitePath);
        if (! is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $db = new SQLite3($sqlitePath);
        $db->busyTimeout(5000);
        $db->exec('PRAGMA journal_mode = WAL;');
        $db->exec('PRAGMA synchronous = NORMAL;');
        $db->exec('PRAGMA foreign_keys = OFF;');
        $db->exec('BEGIN IMMEDIATE TRANSACTION;');

        foreach ($snapshot['tables'] as $tableName => $tableDef) {
            $columns = $tableDef['columns'] ?? [];
            if (empty($columns)) {
                continue;
            }

            $colDefs = [];
            foreach ($columns as $col) {
                $field = $col['Field'];
                $type  = strtolower((string) ($col['Type'] ?? 'text'));
                $key   = (string) ($col['Key'] ?? '');
                $extra = strtolower((string) ($col['Extra'] ?? ''));
                $def   = $col['Default'] ?? null;

                if ($key === 'PRI' && str_contains($extra, 'auto_increment')) {
                    $colDefs[] = "`{$field}` INTEGER PRIMARY KEY AUTOINCREMENT";
                    continue;
                }

                if (str_contains($type, 'int')) {
                    $sqType = 'INTEGER';
                } elseif (str_contains($type, 'decimal') || str_contains($type, 'float') || str_contains($type, 'double')) {
                    $sqType = 'REAL';
                } else {
                    $sqType = 'TEXT';
                }

                $clause = "`{$field}` {$sqType}";
                if ($key === 'PRI') {
                    $clause .= ' PRIMARY KEY';
                }
                if ($def !== null && strtoupper((string) $def) !== 'CURRENT_TIMESTAMP') {
                    $escapedDef = str_replace("'", "''", (string) $def);
                    $clause .= " DEFAULT '{$escapedDef}'";
                }
                $colDefs[] = $clause;
            }

            $createSql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (" . implode(', ', $colDefs) . ');';
            $db->exec($createSql);

            $rows = $tableDef['rows'] ?? [];
            foreach ($rows as $row) {
                if (! is_array($row) || empty($row)) {
                    continue;
                }
                $fields = array_keys($row);
                $colList = '`' . implode('`, `', $fields) . '`';
                $valList = [];
                foreach ($row as $val) {
                    if ($val === null) {
                        $valList[] = 'NULL';
                    } else {
                        $valList[] = "'" . $db->escapeString((string) $val) . "'";
                    }
                }
                $insertSql = "INSERT OR IGNORE INTO `{$tableName}` ({$colList}) VALUES (" . implode(', ', $valList) . ');';
                $db->exec($insertSql);
            }
        }

        $db->exec('COMMIT;');
        $db->close();
    }
}
