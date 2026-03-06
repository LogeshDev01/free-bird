<?php

namespace App\Http\Controllers\Api\v1\Mobile\Trainer;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 🔔 NOTIFICATION APIs
    |--------------------------------------------------------------------------
    */

    /**
     * GET /api/v1/mobile/trainer/notifications
     * List all notifications with pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();

            $notifications = $trainer->notifications()
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            $formatted = collect($notifications->items())->map(function ($notification) {
                return [
                    'id'         => $notification->id,
                    'title'      => $notification->title,
                    'message'    => $notification->message,
                    'is_read'    => !is_null($notification->read_at),
                    'created_at' => \Illuminate\Support\Carbon::parse($notification->created_at)->format('d M Y'),
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'Notifications fetched successfully',
                'data'    => $formatted,
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'per_page'     => $notifications->perPage(),
                    'total'        => $notifications->total(),
                    'last_page'    => $notifications->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Notification list failed', [
                'trainer_id' => auth('trainer')->id(),
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/mobile/trainer/notifications/unread-count
     * Get count of unread notifications (for badge)
     */
    public function unreadCount(): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();

            $count = $trainer->notifications()->unread()->count();

            return response()->json([
                'status'  => true,
                'message' => 'Unread count fetched successfully',
                'data'    => ['unread_count' => $count],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Notification unread count failed', [
                'trainer_id' => auth('trainer')->id(),
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     * POST /api/v1/mobile/trainer/notifications/{id}/read
     * Mark a notification as read
     */
    public function markAsRead(Request $request): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();

            $notification = $trainer->notifications()->find($request->id);

            if (!$notification) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Notification not found',
                ], 404);
            }

            $notification->markAsRead();

            return response()->json([
                'status'  => true,
                'message' => 'Notification marked as read',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Notification mark read failed', [
                'trainer_id'      => auth('trainer')->id(),
                'notification_id' => $request->id,
                'error'           => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     * POST /api/v1/mobile/trainer/notifications/read-all
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();

            $trainer->notifications()
                ->unread()
                ->update(['read_at' => now()]);

            return response()->json([
                'status'  => true,
                'message' => 'All notifications marked as read',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Notification mark all read failed', [
                'trainer_id' => auth('trainer')->id(),
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }
}
