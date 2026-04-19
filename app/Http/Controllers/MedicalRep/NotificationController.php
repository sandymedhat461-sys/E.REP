<?php

namespace App\Http\Controllers\MedicalRep;

use App\Models\MedicalRep;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class NotificationController extends BaseMedicalRepController
{
    /**
     * @OA\Get(
     *     path="/api/rep/notifications",
     *     tags={"Rep - Messages"},
     *     summary="List notifications",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(): JsonResponse
    {
        $rep = $this->repOrForbidden();
        if ($rep instanceof JsonResponse) {
            return $rep;
        }

        $notifications = DB::table('notifications')
            ->where('notifiable_id', $rep->id)
            ->where(function ($q) {
                $q->where('notifiable_type', MedicalRep::class)
                    ->orWhere('notifiable_type', 'medical_rep')
                    ->orWhere('notifiable_type', 'MedicalRep');
            })
            ->orderByDesc('created_at')
            ->get();

        return $this->success(['notifications' => $notifications]);
    }

    /**
     * @OA\Post(
     *     path="/api/rep/notifications/{id}/read",
     *     tags={"Rep - Messages"},
     *     summary="Mark notification as read",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function markAsRead(string $id): JsonResponse
    {
        $rep = $this->repOrForbidden();
        if ($rep instanceof JsonResponse) {
            return $rep;
        }

        $updated = DB::table('notifications')
            ->where('id', $id)
            ->where('notifiable_id', $rep->id)
            ->where(function ($q) {
                $q->where('notifiable_type', MedicalRep::class)
                    ->orWhere('notifiable_type', 'medical_rep')
                    ->orWhere('notifiable_type', 'MedicalRep');
            })
            ->update(['read_at' => Carbon::now()]);

        if (!$updated) {
            return $this->error('Notification not found', 404);
        }

        return $this->success([], 'Marked as read');
    }

    /**
     * @OA\Post(
     *     path="/api/rep/notifications/read-all",
     *     tags={"Rep - Messages"},
     *     summary="Mark all notifications as read",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function markAllAsRead(): JsonResponse
    {
        $rep = $this->repOrForbidden();
        if ($rep instanceof JsonResponse) {
            return $rep;
        }

        DB::table('notifications')
            ->where('notifiable_id', $rep->id)
            ->where(function ($q) {
                $q->where('notifiable_type', MedicalRep::class)
                    ->orWhere('notifiable_type', 'medical_rep')
                    ->orWhere('notifiable_type', 'MedicalRep');
            })
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);

        return $this->success([], 'All marked as read');
    }
}
