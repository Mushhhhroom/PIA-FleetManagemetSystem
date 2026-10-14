<?php

namespace App\Controllers;

use App\Models\RfidCardModel;
use App\Models\RfidTransactionModel;
use App\Models\VehicleModel;
use App\Models\NotificationModel;
use App\Models\UserModel;
use App\Services\AuditLogger;
use App\Services\MailerService;

/**
 * Module 6 — Tollway RFID & Fleet Expense Management
 *
 * FR-6.1 (RFID Balance Tracking): AutoSweep & EasyTrip card numbers and
 *          account balances per vehicle.
 * FR-6.2 (Toll Expense & Reload Entry): dispatchers log reloads via the
 *          web portal; drivers log tolls on trip tickets.
 * FR-6.3 (Low-Balance Alerts): automated alerts to the Motorpool Head
 *          when a balance drops below ₱500.
 */
class RfidController extends BaseController
{
    protected RfidCardModel $cardModel;
    protected RfidTransactionModel $txnModel;
    protected VehicleModel $vehicleModel;
    protected NotificationModel $notificationModel;

    public function __construct()
    {
        $this->cardModel         = new RfidCardModel();
        $this->txnModel          = new RfidTransactionModel();
        $this->vehicleModel      = new VehicleModel();
        $this->notificationModel = new NotificationModel();
    }

    /** FR-6.1: RFID card registry, balances, ledger & low-balance watch. */
    public function index()
    {
        $cards       = $this->cardModel->getCardsWithVehicle();
        $lowBalance  = array_values(array_filter($cards, fn($c) => $this->cardModel->isLowBalance($c)));
        $lowOnly     = $this->request->getGet('filter') === 'low';

        $data = [
            'title'      => 'Tollway RFID & Fleet Expense Management',
            'cards'      => $cards,
            'lowBalance' => $lowBalance,
            'lowOnly'    => $lowOnly,
            'ledger'     => $this->txnModel->getRecentWithDetails(100),
            'vehicles'   => $this->vehicleModel->orderBy('plate_number', 'ASC')->findAll(),
        ];

        return view('rfid/index', $data);
    }

    /** Register an RFID card for a vehicle (FR-6.1). */
    public function storeCard()
    {
        $vehicleId = (int) $this->request->getPost('vehicle_id');
        $provider  = $this->request->getPost('provider');
        $cardNo    = trim((string) $this->request->getPost('card_number'));
        $balance   = (float) $this->request->getPost('balance');

        if (!$vehicleId || !in_array($provider, ['autosweep', 'easytrip'], true) || $cardNo === '') {
            return redirect()->back()->with('error', 'Vehicle, provider (AutoSweep/EasyTrip) and card number are required (FR-6.1).');
        }

        $existing = $this->cardModel->where('vehicle_id', $vehicleId)->where('provider', $provider)->first();
        if ($existing) {
            return redirect()->back()->with('error', 'This vehicle already has a registered ' . ucfirst($provider) . ' card.');
        }

        $cardId = $this->cardModel->insert([
            'vehicle_id'           => $vehicleId,
            'provider'             => $provider,
            'card_number'          => $cardNo,
            'balance'              => max(0, $balance),
            'low_balance_threshold'=> 500.00, // FR-6.3 ₱500 default
            'status'               => 'active',
        ]);

        AuditLogger::log(
            AuditLogger::STATUS_CHANGE,
            "RFID card {$cardNo} ({$provider}) registered.",
            'rfid_card',
            (int) $cardId
        );

        // FR-6.3: a card registered below the ₱500 threshold must alert immediately
        $this->checkLowBalance((int) $cardId);

        return redirect()->to('/rfid')->with('success', ucfirst($provider) . " card {$cardNo} registered successfully.");
    }

    /** FR-6.2: dispatcher logs an RFID balance reload. */
    public function reload()
    {
        $cardId = (int) $this->request->getPost('rfid_card_id');
        $amount = (float) $this->request->getPost('amount');
        $remarks = trim((string) $this->request->getPost('remarks'));

        $card = $this->cardModel->find($cardId);
        if (!$card) {
            return redirect()->back()->with('error', 'RFID card not found.');
        }
        if ($amount <= 0) {
            return redirect()->back()->with('error', 'Reload amount must be greater than zero (FR-6.2).');
        }

        $newBalance = round((float) $card['balance'] + $amount, 2);
        $this->cardModel->update($cardId, ['balance' => $newBalance]);

        $userId = (int) session()->get('user_id');
        $this->txnModel->insert([
            'rfid_card_id'  => $cardId,
            'type'          => 'reload',
            'amount'        => $amount,
            'balance_after' => $newBalance,
            'remarks'       => $remarks ?: 'Portal reload entry',
            'recorded_by'   => $userId ?: null,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        AuditLogger::log(
            AuditLogger::RFID_RELOAD,
            "RFID reload of ₱" . number_format($amount, 2) . " on card {$card['card_number']} ({$card['provider']}); new balance ₱" . number_format($newBalance, 2) . ".",
            'rfid_card',
            $cardId
        );

        $this->checkLowBalance($cardId);

        return redirect()->to('/rfid')->with('success', "Reload recorded. New balance for {$card['card_number']}: ₱" . number_format($newBalance, 2) . ".");
    }

    /**
     * FR-6.3: automated alert to the Motorpool Head when balance < threshold.
     * Called after every balance mutation (reload, toll, manual adjustment).
     */
    public function checkLowBalance(int $cardId): bool
    {
        $card = $this->cardModel->find($cardId);
        if (!$card || !$this->cardModel->isLowBalance($card)) {
            return false;
        }

        $vehicle  = $this->vehicleModel->find($card['vehicle_id']);
        $userModel = new UserModel();
        $mailer    = new MailerService();
        $heads = array_merge(
            $userModel->where('role', 'dispatcher')->where('status', 'active')->findAll(),
            $userModel->where('role', 'admin')->where('status', 'active')->findAll()
        );

        $title = "RFID LOW BALANCE ALERT (FR-6.3): {$card['card_number']}";
        $message = ucfirst($card['provider']) . " card {$card['card_number']} for {$vehicle['plate_number']} is down to ₱"
            . number_format((float) $card['balance'], 2) . " — below the ₱"
            . number_format((float) $card['low_balance_threshold'], 2) . " threshold. Reload immediately to avoid toll lane violations.";

        foreach ($heads as $head) {
            // FR-6.3: in-app notification always + e-mail when SMTP is configured
            $mailer->notify((int) $head['id'], $title, $message, 'warning', '/rfid?filter=low', true);
        }

        return true;
    }

    /**
     * FR-6.2: log a toll deduction (used by trip tickets and dispatchers).
     * Returns the new balance; also enforces FR-6.3 low-balance alerts.
     */
    public function recordToll(int $vehicleId, float $amount, string $provider, ?int $tripTicketId = null, ?string $remarks = null): ?float
    {
        $card = $this->cardModel->where('vehicle_id', $vehicleId)
                                ->where('provider', $provider)
                                ->first();
        if (!$card) {
            return null;
        }

        $newBalance = round(max(0, (float) $card['balance'] - abs($amount)), 2);
        $this->cardModel->update($card['id'], ['balance' => $newBalance]);

        $this->txnModel->insert([
            'rfid_card_id'   => (int) $card['id'],
            'trip_ticket_id' => $tripTicketId,
            'type'           => 'toll',
            'amount'         => -abs($amount),
            'balance_after'  => $newBalance,
            'remarks'        => $remarks ?: 'Trip ticket toll deduction',
            'recorded_by'    => (int) session()->get('user_id') ?: null,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        $this->checkLowBalance((int) $card['id']);

        return $newBalance;
    }
}
