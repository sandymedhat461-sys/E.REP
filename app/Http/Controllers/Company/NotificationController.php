<?php

namespace App\Http\Controllers\Company;

use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class NotificationController extends BaseCompanyController
{
    /**
     * @OA\Get(
     *     path="/api/company/notifications",
     *     tags={"Company - Messages"},
     *     summary="List notifications",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(): JsonResponse
    {
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $types = ['company', 'Company', Company::class, 'App\\Models\\Company'];
        $notifications = DB::table('notifications')
            ->where('notifiable_id', $company->id)
            ->whereIn('notifiable_type', $types)
            ->orderByDesc('created_at')
            ->get();

        $unreadCount = DB::table('notifications')
            ->where('notifiable_id', $company->id)
            ->whereIn('notifiable_type', $types)
            ->whereNull('read_at')
            ->count();

        return $this->success([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/company/notifications/{id}/read",
     *     tags={"Company - Messages"},
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
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $types = ['company', 'Company', Company::class, 'App\\Models\\Company'];
        $updated = DB::table('notifications')
            ->where('id', $id)
            ->where('notifiable_id', $company->id)
            ->whereIn('notifiable_type', $types)
            ->update(['read_at' => Carbon::now()]);

        if (!$updated) {
            return $this->error('Notification not found', 404);
        }

        return $this->success([], 'Marked as read');
    }

    /**
     * @OA\Post(
     *     path="/api/company/notifications/read-all",
     *     tags={"Company - Messages"},
     *     summary="Mark all notifications as read",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function markAllAsRead(): JsonResponse
    {
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $types = ['company', 'Company', Company::class, 'App\\Models\\Company'];
        DB::table('notifications')
            ->where('notifiable_id', $company->id)
            ->whereIn('notifiable_type', $types)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);

        return $this->success([], 'All marked as read');
    }
}
