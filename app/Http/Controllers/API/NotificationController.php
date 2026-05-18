<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get all notifications for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi berhasil diambil',
            'data' => NotificationResource::collection($notifications->items())->resolve(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'total' => $notifications->total(),
                'per_page' => $notifications->perPage(),
                'last_page' => $notifications->lastPage(),
            ]
        ], 200);
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $request->user()
            ->notifications()
            ->unread()
            ->count();

        return response()->json([
            'success' => true,
            'message' => 'Jumlah notifikasi belum dibaca',
            'data' => [
                'unread_count' => $count
            ]
        ], 200);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, $notificationId): JsonResponse
    {
        try {
            $notification = Notification::where('user_id', $request->user()->id)
                ->findOrFail($notificationId);

            $notification->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Notifikasi telah ditandai sebagai telah dibaca',
                'data' => $notification
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notifikasi tidak ditemukan',
                'errors' => []
            ], 404);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()
            ->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Semua notifikasi telah ditandai sebagai telah dibaca',
            'data' => null
        ], 200);
    }

    /**
     * Delete a notification
     */
    public function destroy(Request $request, $notificationId): JsonResponse
    {
        try {
            $notification = Notification::where('user_id', $request->user()->id)
                ->findOrFail($notificationId);

            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notifikasi berhasil dihapus',
                'data' => null
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notifikasi tidak ditemukan',
                'errors' => []
            ], 404);
        }
    }
}
