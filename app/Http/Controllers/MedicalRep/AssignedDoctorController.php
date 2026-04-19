<?php

namespace App\Http\Controllers\MedicalRep;

use App\Models\Doctor;
use App\Models\RepDoctor;
use Illuminate\Http\JsonResponse;

class AssignedDoctorController extends BaseMedicalRepController
{
    /**
     * @OA\Get(
     *     path="/api/rep/doctors",
     *     tags={"Rep - Doctors"},
     *     summary="List assigned doctors",
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

        $doctors = Doctor::whereIn('id', RepDoctor::where('rep_id', $rep->id)->pluck('doctor_id'))->get();
        return $this->success(['doctors' => $doctors]);
    }

    /**
     * @OA\Get(
     *     path="/api/rep/doctors/{id}",
     *     tags={"Rep - Doctors"},
     *     summary="Get assigned doctor",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $rep = $this->repOrForbidden();
        if ($rep instanceof JsonResponse) {
            return $rep;
        }

        $assigned = RepDoctor::where('rep_id', $rep->id)->where('doctor_id', $id)->exists();
        if (!$assigned) {
            return $this->error('Doctor not found', 404);
        }

        return $this->success(['doctor' => Doctor::find($id)]);
    }
}
