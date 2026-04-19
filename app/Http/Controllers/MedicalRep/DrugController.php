<?php

namespace App\Http\Controllers\MedicalRep;

use App\Models\Drug;
use Illuminate\Http\JsonResponse;

class DrugController extends BaseMedicalRepController
{
    /**
     * @OA\Get(
     *     path="/api/rep/drugs",
     *     tags={"Rep - Drugs"},
     *     summary="List drugs in rep category",
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

        $drugs = Drug::where('category_id', $rep->category_id)
            ->with(['company:id,company_name', 'category:id,name'])
            ->get();

        return $this->success(['drugs' => $drugs]);
    }

    /**
     * @OA\Get(
     *     path="/api/rep/drugs/{id}",
     *     tags={"Rep - Drugs"},
     *     summary="Get drug in rep category",
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

        $drug = Drug::where('category_id', $rep->category_id)
            ->with(['company:id,company_name', 'category:id,name', 'activeIngredients'])
            ->find($id);
        if (!$drug) {
            return $this->error('Drug not found', 404);
        }

        return $this->success(['drug' => $drug]);
    }
}
