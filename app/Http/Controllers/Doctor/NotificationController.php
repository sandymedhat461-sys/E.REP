<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $doctorId = (int) $request->user()->id;
        $notifications = DB::table('notifications')
            ->where('notifiable_id', $doctorId)
            ->where('notifiable_type', 'doctor')
            ->orderByDesc('created_at')
            ->get();

        $unreadCount = DB::table('notifications')
            ->where('notifiable_id', $doctorId)
            ->where('notifiable_type', 'doctor')
            ->whereNull('read_at')
            ->count();

        return $this->success([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $updated = DB::table('notifications')
            ->where('id', $id)
            ->where('notifiable_id', $request->user()->id)
            ->where('notifiable_type', 'doctor')
            ->update(['read_at' => Carbon::now()]);

        if (!$updated) {
            return $this->error('Notification not found', 404);
        }

        return $this->success([], 'Marked as read');
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        DB::table('notifications')
            ->where('notifiable_id', $request->user()->id)
            ->where('notifiable_type', 'doctor')
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);

        return $this->success([], 'All marked as read');
    }
}
