<?php

namespace App\Http\Controllers\Company;

use App\Models\Reward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RewardController extends BaseCompanyController
{
    /**
     * @OA\Get(
     *     path="/api/company/rewards",
     *     tags={"Company - Rewards"},
     *     summary="List rewards",
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

        $rewards = Reward::where('company_id', $company->id)->latest()->get();
        return $this->success(['rewards' => $rewards]);
    }

    /**
     * @OA\Post(
     *     path="/api/company/rewards",
     *     tags={"Company - Rewards"},
     *     summary="Create reward",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="points_required", type="integer"),
     *             @OA\Property(property="quantity_available", type="integer"),
     *             @OA\Property(property="status", type="string", enum={"active","inactive"})
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $validated = $this->validateRequest($request, [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'points_required' => ['required', 'integer', 'min:1'],
            'quantity_available' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $reward = Reward::create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'title' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'points_required' => $validated['points_required'],
            'quantity_available' => $validated['quantity_available'] ?? null,
            'status' => $validated['status'] ?? 'active',
        ]);

        return $this->success(['reward' => $reward], null, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/company/rewards/{id}",
     *     tags={"Company - Rewards"},
     *     summary="Get reward",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $reward = $this->ownedReward($id);
        if ($reward instanceof JsonResponse) {
            return $reward;
        }

        $reward->loadCount(['rewardRedemptions as redemptions_count']);
        return $this->success(['reward' => $reward]);
    }

    /**
     * @OA\Put(
     *     path="/api/company/rewards/{id}",
     *     tags={"Company - Rewards"},
     *     summary="Update reward",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="points_required", type="integer"),
     *             @OA\Property(property="quantity_available", type="integer"),
     *             @OA\Property(property="status", type="string", enum={"active","inactive"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $reward = $this->ownedReward($id);
        if ($reward instanceof JsonResponse) {
            return $reward;
        }

        $validated = $this->validateRequest($request, [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'points_required' => ['required', 'integer', 'min:1'],
            'quantity_available' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $reward->update([
            'name' => $validated['name'],
            'title' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'points_required' => $validated['points_required'],
            'quantity_available' => $validated['quantity_available'] ?? null,
            'status' => $validated['status'] ?? $reward->status,
        ]);

        return $this->success(['reward' => $reward->fresh()]);
    }

    /**
     * @OA\Delete(
     *     path="/api/company/rewards/{id}",
     *     tags={"Company - Rewards"},
     *     summary="Delete reward",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $reward = $this->ownedReward($id);
        if ($reward instanceof JsonResponse) {
            return $reward;
        }

        $reward->delete();
        return $this->success([], 'Reward deleted');
    }

    private function ownedReward(int $id): Reward|JsonResponse
    {
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $reward = Reward::where('company_id', $company->id)->find($id);
        if (!$reward) {
            return $this->error('Reward not found', 404);
        }

        return $reward;
    }
}
