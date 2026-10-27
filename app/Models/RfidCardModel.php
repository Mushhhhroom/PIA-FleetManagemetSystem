<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * FR-6.1 / FR-6.2 / FR-6.3 — AutoSweep & EasyTrip RFID cards,
 * reload/toll ledger and low-balance threshold (₱500 default).
 */
class RfidCardModel extends Model
{
    protected $table            = 'rfid_cards';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $allowedFields    = [
        'vehicle_id', 'provider', 'card_number', 'balance',
        'low_balance_threshold', 'status', 'created_at', 'updated_at',
    ];

    public function getCardsWithVehicle()
    {
        return $this->select('rfid_cards.*, vehicles.plate_number, vehicles.vehicle_code,
                              vehicles.make, vehicles.model')
                    ->join('vehicles', 'vehicles.id = rfid_cards.vehicle_id', 'left')
                    ->orderBy('rfid_cards.balance', 'ASC')
                    ->findAll();
    }

    /**
     * FR-6.3 — cards whose balance dropped below their threshold.
     */
    public function getLowBalanceCards(): array
    {
        return $this->where('status', 'active')
                    ->where('balance < low_balance_threshold', null, false)
                    ->findAll();
    }

    /**
     * FR-6.3 — returns the low-balance card (if any) after a balance change.
     */
    public function isLowBalance(array $card): bool
    {
        return (float) $card['balance'] < (float) $card['low_balance_threshold'];
    }
}
