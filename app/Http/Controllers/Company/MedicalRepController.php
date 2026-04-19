<?php

namespace App\Http\Controllers\Company;

use App\Models\MedicalRep;
use App\Models\RepTarget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicalRepController extends BaseCompanyController
{
    /**
     * @OA\Get(
     *     path="/api/company/reps",
     *     tags={"Company - Reps"},
     *     summary="List company medical reps",
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

        $reps = MedicalRep::where('company_id', $company->id)
            ->with('category:id,name')
            ->get();

        return $this->success(['reps' => $reps]);
    }

    /**
     * @OA\Get(
     *     path="/api/company/reps/{id}",
     *     tags={"Company - Reps"},
     *     summary="Get medical rep",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $rep = $this->ownedRep($id);
        if ($rep instanceof JsonResponse) {
            return $rep;
        }

        $rep->load(['category:id,name', 'repTargets']);
        return $this->success(['rep' => $rep]);
    }

    /**
     * @OA\Post(
     *     path="/api/company/reps/{id}/targets",
     *     tags={"Company - Reps"},
     *     summary="Create or update rep target",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="target_type", type="string", enum={"meetings","samples","reviews","events","doctors"}),
     *             @OA\Property(property="target_value", type="integer"),
     *             @OA\Property(property="period_start", type="string", format="date"),
     *             @OA\Property(property="period_end", type="string", format="date")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Rep not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function upsertTarget(Request $request, int $id): JsonResponse
    {
        $rep = $this->ownedRep($id);
        if ($rep instanceof JsonResponse) {
            return $rep;
        }

        $validated = $this->validateRequest($request, [
            'target_type' => ['required', 'in:meetings,samples,reviews,events,doctors'],
            'target_value' => ['required', 'integer', 'min:1'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $target = RepTarget::updateOrCreate(
            ['rep_id' => $rep->id, 'target_type' => $validated['target_type']],
            [
                'target_value' => $validated['target_value'],
                'period_start' => $validated['period_start'],
                'period_end' => $validated['period_end'],
                'period' => $validated['period_start'] . ' - ' . $validated['period_end'],
            ]
        );

        return $this->success(['target' => $target]);
    }

    /**
     * @OA\Get(
     *     path="/api/company/reps/{id}/targets",
     *     tags={"Company - Reps"},
     *     summary="List rep targets with progress",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Rep not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function targets(int $id): JsonResponse
    {
        $rep = $this->ownedRep($id);
        if ($rep instanceof JsonResponse) {
            return $rep;
        }

        $targets = RepTarget::where('rep_id', $rep->id)->get()->map(function (RepTarget $target) {
            $percentage = $target->target_value > 0
                ? round(($target->current_value / $target->target_value) * 100, 2)
                : 0;

            $start = $target->period_start?->format('Y-m-d H:i:s');
            $end = $target->period_end?->format('Y-m-d H:i:s');
            if ($start === null && $target->period) {
                [$start, $end] = array_pad(explode(' - ', (string) $target->period), 2, null);
            }

            return [
                'id' => $target->id,
                'target_type' => $target->target_type,
                'target_value' => $target->target_value,
                'current_value' => $target->current_value,
                'percentage' => $percentage,
                'period_start' => $start,
                'period_end' => $end,
            ];
        });

        return $this->success(['targets' => $targets]);
    }

    private function ownedRep(int $id): MedicalRep|JsonResponse
    {
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $rep = MedicalRep::where('company_id', $company->id)->find($id);
        if (!$rep) {
            return $this->error('Rep not found', 404);
        }

        return $rep;
    }
}
