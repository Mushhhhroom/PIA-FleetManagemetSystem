<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table            = 'system_notifications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'user_id', 'title', 'message', 'type', 'link', 'is_read', 'created_at'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    public function getUserNotifications(int|string $userId, int $limit = 10)
    {
        return $this->where('user_id', (int) $userId)
                    ->orderBy('id', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    public function getUnreadCount(int|string $userId)
    {
        return $this->where('user_id', (int) $userId)
                    ->where('is_read', 0)
                    ->countAllResults();
    }
}
