<?php

namespace App\Http\Controllers\MedicalRep;

use App\Models\DrugSample;
use Illuminate\Http\JsonResponse;

class DrugSampleController extends BaseMedicalRepController
{
    /**
     * @OA\Get(
     *     path="/api/rep/samples",
     *     tags={"Rep - Samples"},
     *     summary="List sample requests",
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

        $samples = DrugSample::where('rep_id', $rep->id)
            ->with(['doctor:id,full_name,email', 'drug:id,name,market_name'])
            ->latest()
            ->get();
        return $this->success(['samples' => $samples]);
    }

    /**
     * @OA\Get(
     *     path="/api/rep/samples/{id}",
     *     tags={"Rep - Samples"},
     *     summary="Get sample request",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $sample = $this->ownedSample($id);
        if ($sample instanceof JsonResponse) {
            return $sample;
        }

        return $this->success(['sample' => $sample->load(['doctor:id,full_name,email', 'drug:id,name,market_name'])]);
    }

    /**
     * @OA\Post(
     *     path="/api/rep/samples/{id}/approve",
     *     tags={"Rep - Samples"},
     *     summary="Approve sample request",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function approve(int $id): JsonResponse
    {
        $sample = $this->ownedSample($id);
        if ($sample instanceof JsonResponse) {
            return $sample;
        }
        $sample->update(['status' => 'approved']);
        return $this->success(['sample' => $sample->fresh()]);
    }

    /**
     * @OA\Post(
     *     path="/api/rep/samples/{id}/reject",
     *     tags={"Rep - Samples"},
     *     summary="Reject sample request",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function reject(int $id): JsonResponse
    {
        $sample = $this->ownedSample($id);
        if ($sample instanceof JsonResponse) {
            return $sample;
        }
        $sample->update(['status' => 'rejected']);
        return $this->success(['sample' => $sample->fresh()]);
    }

    /**
     * @OA\Post(
     *     path="/api/rep/samples/{id}/deliver",
     *     tags={"Rep - Samples"},
     *     summary="Mark sample as delivered",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=422, description="Must be approved first"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function deliver(int $id): JsonResponse
    {
        $sample = $this->ownedSample($id);
        if ($sample instanceof JsonResponse) {
            return $sample;
        }
        if ($sample->status !== 'approved') {
            return $this->error('Only approved samples can be delivered', 422);
        }
        $sample->update(['status' => 'delivered']);
        return $this->success(['sample' => $sample->fresh()]);
    }

    private function ownedSample(int $id): DrugSample|JsonResponse
    {
        $rep = $this->repOrForbidden();
        if ($rep instanceof JsonResponse) {
            return $rep;
        }

        $sample = DrugSample::where('rep_id', $rep->id)->find($id);
        if (!$sample) {
            return $this->error('Sample not found', 404);
        }
        return $sample;
    }
}
