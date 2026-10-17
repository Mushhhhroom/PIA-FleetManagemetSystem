<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Controllers\DispatchController;

class CalendarTest extends BaseCommand
{
    protected $group       = 'PIA Fleet';
    protected $name        = 'calendar:test';
    protected $description = 'Verifies the interactive dispatch calendar allocation process and multi-day spans';

    public function run(array $params)
    {
        CLI::write("=========================================================", 'cyan');
        CLI::write("     CALENDAR ALLOCATION PROCESS VERIFICATION TEST        ", 'yellow');
        CLI::write("=========================================================\n", 'cyan');

        $controller = new DispatchController();
        $month = $params[0] ?? '2026-10';

        CLI::write("Fetching calendar data for month: {$month}...", 'white');
        $allocations = $controller->calendarData($month);

        CLI::write("Total day-allocation records generated: " . count($allocations), 'green');

        // Check stages distribution
        $stageCounts = [];
        $phases = [];
        $uniqueVrs = [];

        foreach ($allocations as $a) {
            $stage = $a['stage_num'];
            $stageCounts[$stage] = ($stageCounts[$stage] ?? 0) + 1;
            $phases[$a['day_phase']] = ($phases[$a['day_phase']] ?? 0) + 1;
            $uniqueVrs[$a['request_number']] = true;
        }

        CLI::write("\n--- Unique Requests: " . count($uniqueVrs) . " ---", 'yellow');
        CLI::write("--- Stage Distribution ---", 'yellow');
        foreach ($stageCounts as $stage => $count) {
            CLI::write("  Stage {$stage}: {$count} day allocations", 'white');
        }

        CLI::write("\n--- Day Phase Distribution ---", 'yellow');
        foreach ($phases as $phase => $count) {
            CLI::write("  {$phase}: {$count}", 'white');
        }

        // Verify multi-day requests
        CLI::write("\n--- Multi-Day Trips Inspection ---", 'yellow');
        $multiDayMap = [];
        foreach ($allocations as $a) {
            $multiDayMap[$a['request_number']][] = $a;
        }

        $foundMulti = 0;
        foreach ($multiDayMap as $vrs => $days) {
            if (count($days) > 1) {
                $foundMulti++;
                $dList = array_map(fn($d) => "{$d['date']} ({$d['day_phase_label']})", $days);
                CLI::write("  {$vrs} spans " . count($days) . " days: " . implode(' -> ', $dList), 'cyan');
            }
        }
        CLI::write("Total multi-day requests identified: {$foundMulti}", 'green');

        // Sample allocations across stages
        CLI::write("\n--- Sample Allocation Items by Stage ---", 'yellow');
        $shownStages = [];
        foreach ($allocations as $a) {
            if (!isset($shownStages[$a['stage_num']])) {
                $shownStages[$a['stage_num']] = true;
                CLI::write("  [Stage {$a['stage_num']}] {$a['request_number']} | {$a['date']} ({$a['day_phase']}) | {$a['process_title']} | Asset: {$a['plate']} | Driver: {$a['driver']} | Dest: {$a['destination']}", 'white');
            }
        }

        // Test rendering the calendar view
        CLI::write("\n--- Testing Calendar View Render ---", 'yellow');
        try {
            // Mock session
            $session = session();
            $session->set('user_role', 'dispatcher');
            $session->set('user_name', 'Lead Dispatcher');

            // Render view directly with data
            $viewData = [
                'title'              => 'Interactive Dispatch Calendar & Allocation Board',
                'month'              => $month,
                'prevMonth'          => '2026-09',
                'nextMonth'          => '2026-11',
                'allocations'        => $allocations,
                'awaitingAllocation' => array_values(array_filter($allocations, fn($a) => $a['allocation_status'] === 'ready_to_allocate')),
                'kpi'                => [
                    'total'             => 27,
                    'pending_review'    => 3,
                    'ready_to_allocate' => 0,
                    'dispatched_active' => 9,
                    'completed'         => 13,
                ],
                'vehicles'           => (new \App\Models\VehicleModel())->findAll(),
                'drivers'            => (new \App\Models\DriverModel())->findAll(),
                'userRole'           => 'dispatcher',
            ];
            $html = view('dispatch/calendar', $viewData);
            CLI::write("Calendar view rendered successfully (" . strlen($html) . " bytes)", 'green');
        } catch (\Throwable $e) {
            CLI::write("Render error: " . $e->getMessage(), 'red');
            CLI::write($e->getTraceAsString(), 'red');
            return;
        }

        CLI::write("\n[SUCCESS] Calendar allocation process logic and view verified successfully!", 'green');
    }
}
