<?php

namespace App\Http\Controllers\Company;

use App\Models\RewardRedemption;
use Illuminate\Http\JsonResponse;

class RewardRedemptionController extends BaseCompanyController
{
    /**
     * @OA\Get(
     *     path="/api/company/redemptions",
     *     tags={"Company - Rewards"},
     *     summary="List reward redemptions",
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

        $redemptions = RewardRedemption::query()
            ->whereHas('reward', fn ($q) => $q->where('company_id', $company->id))
            ->with(['reward', 'doctor:id,full_name,email'])
            ->latest()
            ->get();

        return $this->success(['redemptions' => $redemptions]);
    }

    /**
     * @OA\Post(
     *     path="/api/company/redemptions/{id}/fulfill",
     *     tags={"Company - Rewards"},
     *     summary="Fulfill redemption",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function fulfill(int $id): JsonResponse
    {
        $redemption = $this->ownedRedemption($id);
        if ($redemption instanceof JsonResponse) {
            return $redemption;
        }

        $redemption->update(['status' => 'fulfilled']);
        return $this->success(['redemption' => $redemption->fresh()]);
    }

    /**
     * @OA\Post(
     *     path="/api/company/redemptions/{id}/cancel",
     *     tags={"Company - Rewards"},
     *     summary="Cancel redemption",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function cancel(int $id): JsonResponse
    {
        $redemption = $this->ownedRedemption($id);
        if ($redemption instanceof JsonResponse) {
            return $redemption;
        }

        $redemption->update(['status' => 'cancelled']);
        return $this->success(['redemption' => $redemption->fresh()]);
    }

    private function ownedRedemption(int $id): RewardRedemption|JsonResponse
    {
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $redemption = RewardRedemption::query()
            ->where('id', $id)
            ->whereHas('reward', fn ($q) => $q->where('company_id', $company->id))
            ->first();

        if (!$redemption) {
            return $this->error('Redemption not found', 404);
        }

        return $redemption;
    }
}
