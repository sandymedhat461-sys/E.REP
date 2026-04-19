<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DoctorPoint;
use App\Models\Reward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RewardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/doctor/rewards",
     *     tags={"Doctor - Points"},
     *     summary="List rewards with can_redeem flag",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $totalPoints = (int) DoctorPoint::where('doctor_id', $request->user()->id)->sum('value');
        $rewards = Reward::with('company:id,company_name')->get();

        $rewards->transform(function (Reward $reward) use ($totalPoints) {
            $reward->can_redeem = $totalPoints >= (int) $reward->points_required;
            return $reward;
        });

        return $this->success(['rewards' => $rewards]);
    }
}
