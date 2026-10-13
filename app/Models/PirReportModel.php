<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * FR-4.3 / FR-5.2 / FR-5.3 — Pre-Repair Inspection Reports (PIR).
 * Auto-generated on BLOWBAGETS failure or breakdown; worked by mechanics
 * who record pre-inspection findings and post-repair evaluations before
 * restoring the vehicle to Available status.
 */
class PirReportModel extends Model
{
    protected $table            = 'pir_reports';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $allowedFields    = [
        'pir_number', 'vehicle_id', 'trip_ticket_id', 'incident_id', 'source',
        'defect_description', 'status', 'pre_inspection_findings',
        'post_repair_evaluation', 'parts_replaced', 'repair_cost',
        'mechanic_id', 'reported_by', 'released_at', 'created_at', 'updated_at',
    ];

    /**
     * MAX-based so deleted reports can never re-issue an existing number.
     */
    public function generatePirNumber(): string
    {
        $year   = date('Y');
        $prefix = "PIR-{$year}-";
        $row    = $this->db->table('pir_reports')
            ->select("MAX(CAST(SUBSTRING_INDEX(pir_number, '-', -1) AS UNSIGNED)) AS max_no")
            ->like('pir_number', $prefix, 'after')
            ->get()
            ->getRow();

        $next = (int) ($row->max_no ?? 0) + 1;
        do {
            $number = sprintf('%s%04d', $prefix, $next++);
        } while ($this->db->table('pir_reports')->where('pir_number', $number)->countAllResults() > 0);

        return $number;
    }

    public function getQueue(?string $status = null)
    {
        $builder = $this->select('pir_reports.*, vehicles.plate_number, vehicles.vehicle_code,
                                  vehicles.make, vehicles.model, vehicles.status AS vehicle_status,
                                  mech.name AS mechanic_name, rep.name AS reported_by_name')
                        ->join('vehicles', 'vehicles.id = pir_reports.vehicle_id', 'left')
                        ->join('users AS mech', 'mech.id = pir_reports.mechanic_id', 'left')
                        ->join('users AS rep', 'rep.id = pir_reports.reported_by', 'left')
                        ->orderBy('pir_reports.id', 'DESC');

        if ($status) {
            $builder->where('pir_reports.status', $status);
        } else {
            $builder->where('pir_reports.status !=', 'released');
        }

        return $builder->findAll();
    }

    public function getWithVehicle(int $id): ?array
    {
        return $this->select('pir_reports.*, vehicles.plate_number, vehicles.vehicle_code,
                              vehicles.make, vehicles.model, vehicles.year, vehicles.odometer_km,
                              vehicles.status AS vehicle_status,
                              mech.name AS mechanic_name, rep.name AS reported_by_name')
                    ->join('vehicles', 'vehicles.id = pir_reports.vehicle_id', 'left')
                    ->join('users AS mech', 'mech.id = pir_reports.mechanic_id', 'left')
                    ->join('users AS rep', 'rep.id = pir_reports.reported_by', 'left')
                    ->where('pir_reports.id', $id)
                    ->first();
    }
}
