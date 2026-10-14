<?php

namespace App\Controllers;

use App\Models\VehicleModel;
use App\Models\TripTicketModel;
use App\Models\TripRequestModel;
use App\Models\NonUsageCertificateModel;
use App\Models\NotificationModel;
use App\Models\UserModel;
use App\Services\AuditLogger;
use App\Services\MailerService;

/**
 * Module 7 — COA & Government Compliance Portal
 *
 * FR-7.1 (Regulatory Expiry Monitoring): LTO registration & GSIS insurance
 *          30-day automated web + email alerts.
 * FR-7.2 (COA Form B Auto-Generation): Monthly Report of Official Travel —
 *          driver details, distance, fuel, destinations, passenger lists.
 * FR-7.3 (Fuel Efficiency Analytics): km/L rates & fuel usage anomalies.
 * FR-7.4 (Certificate of Non-Usage): monthly certs for idle vehicles for
 *          COA/HRDD submission.
 */
class ComplianceController extends BaseController
{
    protected VehicleModel $vehicleModel;
    protected TripTicketModel $ticketModel;
    protected TripRequestModel $requestModel;
    protected NonUsageCertificateModel $cnuModel;
    protected NotificationModel $notificationModel;

    /** FR-7.1: alert window in days before expiry. */
    public const EXPIRY_ALERT_DAYS = 30;

    /**
     * FR-7.1: minimum interval between two identical expiry alerts. Re-running
     * the daily watcher must remind, not spam the same recipients.
     */
    public const ALERT_REMIND_DAYS = 7;

    /** Result of the last sendExpiryAlerts() run — read by fleet:expiry-watch. */
    public array $alertStats = ['expiring' => 0, 'issued' => 0, 'skipped' => 0];

    public function __construct()
    {
        $this->vehicleModel      = new VehicleModel();
        $this->ticketModel       = new TripTicketModel();
        $this->requestModel      = new TripRequestModel();
        $this->cnuModel          = new NonUsageCertificateModel();
        $this->notificationModel = new NotificationModel();
    }

    /** Compliance dashboard: expiring documents, Form B, analytics, CNU. */
    public function index()
    {
        $month = $this->request->getGet('month') ?: date('Y-m');

        $data = [
            'title'          => 'COA & Government Compliance Portal',
            'month'          => $month,
            'expiring'       => $this->getExpiringDocuments(),
            'formB'          => $this->buildFormB($month),
            'fuelAnalytics'  => $this->buildFuelAnalytics($month),
            'idleVehicles'   => $this->getIdleVehicles($month),
            'certificates'   => $this->cnuModel->getWithVehicle($month),
        ];

        return view('compliance/index', $data);
    }

    // ------------------------------------------------------------------
    // FR-7.1 — Regulatory Expiry Monitoring (LTO / GSIS, 30-day alerts)
    // ------------------------------------------------------------------

    /**
     * Vehicles whose LTO registration or GSIS insurance expires within
     * the alert window (including already-expired documents).
     */
    public function getExpiringDocuments(): array
    {
        $limit = date('Y-m-d', strtotime('+' . self::EXPIRY_ALERT_DAYS . ' days'));
        $today = date('Y-m-d');

        $rows = $this->vehicleModel
            ->where('lto_registration_expiry IS NOT NULL', null, false)
            ->orWhere('gsis_insurance_expiry IS NOT NULL', null, false)
            ->orderBy('lto_registration_expiry', 'ASC')
            ->findAll();

        $expiring = [];
        foreach ($rows as $v) {
            $lto = $v['lto_registration_expiry'] ?? null;
            $gsis = $v['gsis_insurance_expiry'] ?? null;

            $ltoDue  = $lto  !== null && $lto  <= $limit;
            $gsisDue = $gsis !== null && $gsis <= $limit;
            if (!$ltoDue && !$gsisDue) {
                continue;
            }

            $ltoDays  = $lto  !== null ? (int) round((strtotime($lto)  - strtotime($today)) / 86400) : null;
            $gsisDays = $gsis !== null ? (int) round((strtotime($gsis) - strtotime($today)) / 86400) : null;

            $expiring[] = [
                'vehicle'      => $v,
                'lto'          => $lto,
                'lto_days'     => $ltoDays,
                'lto_expired'  => $ltoDays !== null && $ltoDays < 0,
                'gsis'         => $gsis,
                'gsis_days'    => $gsisDays,
                'gsis_expired' => $gsisDays !== null && $gsisDays < 0,
            ];
        }

        return $expiring;
    }

    /**
     * Send the 30-day LTO/GSIS expiry alerts (web + email, FR-7.1).
     * Called from the compliance dashboard and the fleet:expiry-watch CLI.
     *
     * @return int number of vehicles alerted on this run
     */
    public function sendExpiryAlerts(): int
    {
        $expiring = $this->getExpiringDocuments();
        if (empty($expiring)) {
            $this->alertStats = ['expiring' => 0, 'issued' => 0, 'skipped' => 0];

            return 0;
        }

        $mailer   = new MailerService();
        $userModel = new UserModel();
        $heads = array_merge(
            $userModel->where('role', 'admin')->where('status', 'active')->findAll(),
            $userModel->where('role', 'dispatcher')->where('status', 'active')->findAll()
        );

        $issued  = 0;
        $skipped = 0;

        foreach ($expiring as $row) {
            $v = $row['vehicle'];
            $parts = [];
            if ($row['lto'] !== null) {
                $parts[] = $row['lto_expired']
                    ? "LTO registration EXPIRED on {$row['lto']}"
                    : "LTO registration expires {$row['lto']} ({$row['lto_days']} days)";
            }
            if ($row['gsis'] !== null) {
                $parts[] = $row['gsis_expired']
                    ? "GSIS insurance EXPIRED on {$row['gsis']}"
                    : "GSIS insurance expires {$row['gsis']} ({$row['gsis_days']} days)";
            }
            $detail = implode('; ', $parts);

            $sent = 0;
            foreach ($heads as $head) {
                // De-duplicate: the watcher runs on a schedule, so a recipient
                // already alerted for this plate within the reminder window
                // is skipped (manual re-sends stay available after that).
                $recent = $this->notificationModel
                    ->where('user_id', (int) $head['id'])
                    ->where('title', "REGULATORY EXPIRY ALERT (FR-7.1): {$v['plate_number']}")
                    ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-' . self::ALERT_REMIND_DAYS . ' days')))
                    ->countAllResults();
                if ($recent > 0) {
                    continue;
                }

                $this->notificationModel->insert([
                    'user_id'    => $head['id'],
                    'title'      => "REGULATORY EXPIRY ALERT (FR-7.1): {$v['plate_number']}",
                    'message'    => "{$v['vehicle_code']} {$v['make']} {$v['model']} — {$detail}. Comply before the 30-day alert window closes.",
                    'type'       => 'warning',
                    'link'       => '/compliance',
                    'is_read'    => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                $mailer->notify(
                    (int) $head['id'],
                    "LTO/GSIS 30-Day Expiry Alert — {$v['plate_number']}",
                    "Fleet unit {$v['vehicle_code']} ({$v['plate_number']}) — {$detail}.",
                    'warning',
                    '/compliance',
                    true
                );

                $sent++;
            }

            if ($sent === 0) {
                $skipped++;
                continue;
            }

            $issued++;

            AuditLogger::log(
                AuditLogger::STATUS_CHANGE,
                "FR-7.1 expiry alert issued for {$v['plate_number']}: {$detail}",
                'vehicle',
                (int) $v['id']
            );
        }

        $this->alertStats = [
            'expiring' => count($expiring),
            'issued'   => $issued,
            'skipped'  => $skipped,
        ];

        return $issued;
    }

    /**
     * Web entry point for the compliance portal's
     * "Send 30-Day Expiry Alerts now" action (FR-7.1).
     */
    public function sendExpiryAlertsAction()
    {
        $issued = $this->sendExpiryAlerts();
        $stats  = $this->alertStats;

        if ($issued > 0) {
            $message = "FR-7.1 expiry alerts issued for {$issued} of {$stats['expiring']} vehicle(s) — web notifications and emails dispatched.";
        } elseif ($stats['expiring'] > 0) {
            $message = "Alerts for {$stats['expiring']} expiring document(s) were already issued within the last " . self::ALERT_REMIND_DAYS . ' days — no duplicates sent.';
        } else {
            $message = 'No regulatory documents are due to expire within the 30-day alert window.';
        }

        session()->setFlashdata('success', $message);

        return redirect()->to('/compliance');
    }

    // ------------------------------------------------------------------
    // FR-7.2 — COA Form B (Monthly Report of Official Travel)
    // ------------------------------------------------------------------

    /**
     * Build Form B rows: driver details, distance travelled, fuel consumed,
     * destinations and passenger lists for the given YYYY-MM period.
     */
    public function buildFormB(string $month): array
    {
        $start = $month . '-01 00:00:00';
        $end   = date('Y-m-t 23:59:59', strtotime($start));

        $rows = $this->ticketModel
            ->select('trip_tickets.*, trip_requests.destination, trip_requests.purpose,
                      trip_requests.passenger_names, trip_requests.departure_time,
                      trip_requests.return_time, trip_requests.requestor_name,
                      vehicles.plate_number, vehicles.vehicle_code, vehicles.make, vehicles.model,
                      drivers.first_name, drivers.last_name, drivers.license_number')
            ->join('trip_requests', 'trip_requests.id = trip_tickets.request_id', 'left')
            ->join('vehicles', 'vehicles.id = trip_tickets.vehicle_id', 'left')
            ->join('drivers', 'drivers.id = trip_tickets.driver_id', 'left')
            ->where('trip_tickets.created_at >=', $start)
            ->where('trip_tickets.created_at <=', $end)
            ->where('trip_tickets.status !=', 'cancelled')
            ->orderBy('trip_tickets.id', 'ASC')
            ->findAll();

        return $rows;
    }

    /** Printable COA Form B view. */
    public function formB()
    {
        $month = $this->request->getGet('month') ?: date('Y-m');

        $data = [
            'title' => 'COA Form B — Monthly Report of Official Travel',
            'month' => $month,
            'rows'  => $this->buildFormB($month),
        ];

        AuditLogger::log(
            AuditLogger::REPORT_EXPORT,
            'COA Form B (Monthly Report of Official Travel) generated for ' . $month . '.',
            'report',
            null,
            ['month' => $month]
        );

        return view('compliance/form_b', $data);
    }

    // ------------------------------------------------------------------
    // FR-7.3 — Fuel Efficiency Analytics (km/L + anomaly detection)
    // ------------------------------------------------------------------

    /**
     * Per-ticket fuel efficiency for the period with anomaly flags.
     */
    public function buildFuelAnalytics(string $month): array
    {
        $start = $month . '-01 00:00:00';
        $end   = date('Y-m-t 23:59:59', strtotime($start));

        $rows = $this->ticketModel
            ->select('trip_tickets.*, vehicles.plate_number, vehicles.fuel_capacity_liters,
                      drivers.first_name, drivers.last_name')
            ->join('vehicles', 'vehicles.id = trip_tickets.vehicle_id', 'left')
            ->join('drivers', 'drivers.id = trip_tickets.driver_id', 'left')
            ->where('trip_tickets.created_at >=', $start)
            ->where('trip_tickets.created_at <=', $end)
            ->where('trip_tickets.total_distance_km >', 0)
            ->orderBy('trip_tickets.fuel_efficiency_kml', 'ASC')
            ->findAll();

        return $rows;
    }

    // ------------------------------------------------------------------
    // FR-7.4 — Certificate of Non-Usage (idle vehicles)
    // ------------------------------------------------------------------

    /**
     * Vehicles with no trip activity in the given month (idle fleet units).
     */
    public function getIdleVehicles(string $month): array
    {
        $start = $month . '-01 00:00:00';
        $end   = date('Y-m-t 23:59:59', strtotime($start));

        $activeVehicleIds = $this->ticketModel
            ->select('vehicle_id')
            ->where('created_at >=', $start)
            ->where('created_at <=', $end)
            ->where('status !=', 'cancelled')
            ->groupBy('vehicle_id')
            ->findAll();

        $activeIds = array_map(fn($r) => (int) $r['vehicle_id'], $activeVehicleIds);

        $vehicles = $this->vehicleModel
            ->where('status !=', 'out_of_service')
            ->orderBy('plate_number', 'ASC')
            ->findAll();

        return array_values(array_filter(
            $vehicles,
            fn($v) => !in_array((int) $v['id'], $activeIds, true)
        ));
    }

    /** FR-7.4: generate monthly Certificates of Non-Usage for idle vehicles. */
    public function generateCertificates()
    {
        $month = $this->request->getPost('month') ?: date('Y-m');
        $idle  = $this->getIdleVehicles($month);

        if (empty($idle)) {
            return redirect()->to('/compliance?month=' . urlencode($month))
                ->with('error', 'No idle vehicles found for this month — all fleet units recorded trip activity.');
        }

        $userId = (int) session()->get('user_id');
        $created = 0;

        foreach ($idle as $v) {
            $exists = $this->cnuModel
                ->where('vehicle_id', $v['id'])
                ->where('period_month', $month)
                ->first();
            if ($exists) {
                continue;
            }

            // Odometer movement during the period (must be zero to qualify)
            $tickets = $this->ticketModel
                ->where('vehicle_id', $v['id'])
                ->where('created_at >=', $month . '-01 00:00:00')
                ->where('created_at <=', date('Y-m-t 23:59:59', strtotime($month . '-01')))
                ->where('status !=', 'cancelled')
                ->findAll();

            $startOdo = null;
            $endOdo   = null;
            if (!empty($tickets)) {
                $odometers = [];
                foreach ($tickets as $t) {
                    foreach (['start_odometer', 'return_odometer'] as $k) {
                        if (!empty($t[$k])) {
                            $odometers[] = (float) $t[$k];
                        }
                    }
                }
                if ($odometers) {
                    $startOdo = min($odometers);
                    $endOdo   = max($odometers);
                }
            }
            if ($startOdo === null) {
                $startOdo = (float) $v['odometer_km'];
                $endOdo   = (float) $v['odometer_km'];
            }

            $this->cnuModel->insert([
                'certificate_no' => $this->cnuModel->generateCertificateNo(),
                'vehicle_id'     => (int) $v['id'],
                'period_month'   => $month,
                'trip_count'     => count($tickets),
                'odometer_start' => $startOdo,
                'odometer_end'   => $endOdo,
                'generated_by'   => $userId ?: null,
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
            $created++;
        }

        AuditLogger::log(
            AuditLogger::REPORT_EXPORT,
            "Generated {$created} Certificate(s) of Non-Usage for {$month} (FR-7.4).",
            'report',
            null,
            ['month' => $month, 'created' => $created]
        );

        return redirect()->to('/compliance?month=' . urlencode($month))
            ->with('success', "{$created} Certificate(s) of Non-Usage generated for {$month} and ready for COA/HRDD submission.");
    }

    /** Printable CNU certificate. */
    public function certificate($id)
    {
        $cert = $this->cnuModel
            ->select('non_usage_certificates.*, vehicles.plate_number, vehicles.vehicle_code,
                      vehicles.make, vehicles.model, vehicles.fleet_category,
                      gen.name AS generated_by_name')
            ->join('vehicles', 'vehicles.id = non_usage_certificates.vehicle_id', 'left')
            ->join('users AS gen', 'gen.id = non_usage_certificates.generated_by', 'left')
            ->where('non_usage_certificates.id', (int) $id)
            ->first();

        if (!$cert) {
            return redirect()->to('/compliance')->with('error', 'Certificate not found.');
        }

        $data = [
            'title' => 'Certificate of Non-Usage — ' . $cert['certificate_no'],
            'cert'  => $cert,
        ];

        return view('compliance/certificate', $data);
    }
}
