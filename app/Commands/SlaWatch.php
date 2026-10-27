<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\SlaService;

class SlaWatch extends BaseCommand
{
    protected $group       = 'PIA Fleet';
    protected $name        = 'sla:watch';
    protected $description = 'FR-1.4: escalates VRS approvals unactioned after 4 hours to the Administrative Division Chief, then applies the 24-hour final expiry backstop and FR-1.3 post-trip documentation checks.';

    public function run(array $params)
    {
        unset($params);
        CLI::write('Running PIA-AFMS SLA watcher (BRD FR-1.3 / FR-1.4)...', 'yellow');

        $slaService = new SlaService();

        // 1. FR-1.4: 4-hour escalation to the Administrative Division Chief
        $escalated = $slaService->checkAndEscalateRequests();
        if (empty($escalated)) {
            CLI::write('No requests breached the 4-hour escalation window.', 'green');
        } else {
            CLI::write(sprintf('Escalated %d request(s): %s', count($escalated), implode(', ', $escalated)), 'yellow');
        }

        // 2. FR-02 backstop: 24-hour final expiry on the escalated/final tier
        $expired = $slaService->checkAndExpireRequests();
        if (empty($expired)) {
            CLI::write('No requests reached the 24-hour expiry window.', 'green');
        } else {
            CLI::error(sprintf('Auto-expired %d stalled request(s): %s', count($expired), implode(', ', $expired)));
        }

        // 3. FR-1.3: post-trip documentation overdue after emergency override
        $overdue = $slaService->checkPostTripDocumentation();
        if (empty($overdue)) {
            CLI::write('No overdue post-trip documentation.', 'green');
        } else {
            CLI::write(sprintf('Flagged %d overdue post-trip document(s): %s', count($overdue), implode(', ', $overdue)), 'red');
        }
    }
}
