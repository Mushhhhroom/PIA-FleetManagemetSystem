<?php

namespace App\Models;

use CodeIgniter\Model;

class AdvisoryModel extends Model
{
    protected $table            = 'motorpool_advisories';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'posted_by',
        'dispatcher_name',
        'title',
        'category',
        'severity',
        'message',
        'is_active',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get active advisories ordered by most recent
     *
     * @param int $limit
     * @return array
     */
    public function getActiveAdvisories(int $limit = 5): array
    {
        return $this->where('is_active', 1)
            ->orderBy('created_at', 'DESC')
            ->findAll($limit);
    }

    /**
     * Get the single latest active advisory
     *
     * @return array|null
     */
    public function getLatestActive(): ?array
    {
        return $this->where('is_active', 1)
            ->orderBy('created_at', 'DESC')
            ->first();
    }
}
