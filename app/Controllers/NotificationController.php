<?php

namespace App\Controllers;

use App\Models\NotificationModel;

/**
 * In-app notification center.
 *
 * Every BRD flow (FR-1.4 escalation, FR-1.5 rejection emails, FR-4.2 delay
 * alerts, FR-5.2 safety locks, FR-6.3 RFID low balance, FR-7.1 regulatory
 * expiry) always writes an in-app notification — this controller is the
 * surface where users actually read them (the email leg is best-effort).
 */
class NotificationController extends BaseController
{
    protected NotificationModel $notificationModel;

    public function __construct()
    {
        $this->notificationModel = new NotificationModel();
    }

    /** Inbox for the signed-in user. */
    public function index()
    {
        $userId = (int) session()->get('user_id');
        if (!$userId) {
            return redirect()->to('/login');
        }

        $filter = $this->request->getGet('filter');

        $builder = $this->notificationModel
            ->where('user_id', $userId)
            ->orderBy('id', 'DESC');

        if ($filter === 'unread') {
            $builder->where('is_read', 0);
        }

        $data = [
            'title'  => 'My Notifications',
            'items'  => $builder->findAll(200),
            'unread' => $this->notificationModel->getUnreadCount($userId),
            'filter' => $filter === 'unread' ? 'unread' : '',
        ];

        return view('notifications/index', $data);
    }

    /** Mark a single notification as read (ownership enforced). */
    public function markRead(int|string $id)
    {
        $userId = (int) session()->get('user_id');
        $row = $this->notificationModel
            ->where('id', (int) $id)
            ->where('user_id', $userId)
            ->first();

        if ($row) {
            if ((int) $row['is_read'] === 0) {
                $this->notificationModel->update($row['id'], ['is_read' => 1]);
            }
            if (!empty($row['link'])) {
                return redirect()->to($row['link']);
            }
        }

        return $this->back();
    }

    /** Clear the unread badge. */
    public function markAllRead()
    {
        $userId = (int) session()->get('user_id');
        $this->notificationModel
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->update(null, ['is_read' => 1]);

        return $this->back()->with('success', 'All notifications marked as read.');
    }

    /** JSON unread counter for the header bell. */
    public function unreadCount()
    {
        return $this->response->setJSON([
            'status' => 'success',
            'unread' => $this->notificationModel->getUnreadCount((int) session()->get('user_id')),
        ]);
    }

    protected function back()
    {
        $referer = $this->request->getServer('HTTP_REFERER');
        if ($referer) {
            return redirect()->to($referer);
        }
        return redirect()->to('/notifications');
    }
}
