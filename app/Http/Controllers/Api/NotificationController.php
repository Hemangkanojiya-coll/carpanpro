<?php

namespace App\Http\Controllers\Api;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    /**
     * GET /api/notifications — List user notifications
     */
    public function index(): JsonResponse
    {
        $notifications = Notification::where('user_id', auth('api')->id())
                                     ->orderByDesc('created_at')
                                     ->paginate(20);

        $unreadCount = Notification::where('user_id', auth('api')->id())
                                   ->where('is_read', false)
                                   ->count();

        return $this->sendResponse('Notifications.', [
            'notifications' => $notifications,
            'unread_count'  => $unreadCount,
        ]);
    }

    /**
     * PUT /api/notifications/{id}/read — Mark single as read
     */
    public function markRead(int $id): JsonResponse
    {
        $notif = Notification::where('user_id', auth('api')->id())->find($id);

        if (! $notif) {
            return $this->sendError('Notification not found.', null, 404);
        }

        $notif->update(['is_read' => true]);

        return $this->sendResponse('Marked as read.');
    }

    /**
     * PUT /api/notifications/read-all — Mark all as read
     */
    public function markAllRead(): JsonResponse
    {
        Notification::where('user_id', auth('api')->id())
                    ->where('is_read', false)
                    ->update(['is_read' => true]);

        return $this->sendResponse('All notifications marked as read.');
    }
}
