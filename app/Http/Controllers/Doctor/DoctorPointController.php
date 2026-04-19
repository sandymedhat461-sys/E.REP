<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DoctorPoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorPointController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/doctor/points",
     *     tags={"Doctor - Points"},
     *     summary="Points history",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $points = DoctorPoint::where('doctor_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get(['source', 'source_id', 'value', 'created_at']);

        return $this->success(['points' => $points]);
    }

    /**
     * @OA\Get(
     *     path="/api/doctor/points/total",
     *     tags={"Doctor - Points"},
     *     summary="Total points",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function total(Request $request): JsonResponse
    {
        $total = (int) DoctorPoint::where('doctor_id', $request->user()->id)->sum('value');

        return $this->success(['total_points' => $total]);
    }
}
