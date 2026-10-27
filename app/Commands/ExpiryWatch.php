<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Controllers\ComplianceController;

class ExpiryWatch extends BaseCommand
{
    protected $group       = 'PIA Fleet';
    protected $name        = 'fleet:expiry-watch';
    protected $description = 'FR-7.1: scans the fleet for LTO registration and GSIS insurance expiring within 30 days and issues automated web + email alerts to the Administrative Division and Motorpool Head.';

    public function run(array $params)
    {
        unset($params);
        CLI::write('Scanning fleet for LTO/GSIS expiries within 30 days (FR-7.1)...', 'yellow');

        $compliance = new ComplianceController();
        $issued     = $compliance->sendExpiryAlerts();
        $stats      = $compliance->alertStats;

        if ($stats['expiring'] === 0) {
            CLI::write('No regulatory documents are due to expire within the alert window.', 'green');

            return;
        }

        CLI::write(sprintf(
            '%d vehicle(s) have documents expiring within %d days.',
            $stats['expiring'],
            ComplianceController::EXPIRY_ALERT_DAYS
        ), 'yellow');

        if ($issued > 0) {
            CLI::write(sprintf(
                'Issued fresh alerts for %d vehicle(s) — notifications and emails dispatched.',
                $issued
            ), 'green');
        } else {
            CLI::write(sprintf(
                'All %d vehicle(s) were already alerted within the last %d days — no duplicates sent.',
                $stats['skipped'],
                ComplianceController::ALERT_REMIND_DAYS
            ), 'cyan');
        }

        if ($stats['skipped'] > 0 && $issued > 0) {
            CLI::write(sprintf('%d vehicle(s) skipped (already alerted recently).', $stats['skipped']), 'cyan');
        }
    }
}
