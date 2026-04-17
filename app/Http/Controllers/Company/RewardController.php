<?php

namespace App\Http\Controllers\Company;

use App\Models\Reward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RewardController extends BaseCompanyController
{
    public function index(): JsonResponse
    {
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $rewards = Reward::where('company_id', $company->id)->latest()->get();
        return $this->success(['rewards' => $rewards]);
    }

    public function store(Request $request): JsonResponse
    {
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $validated = $this->validateRequest($request, [
            'title' => ['required', 'string', 'max:255'],
            'points_required' => ['required', 'integer', 'min:1'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $reward = Reward::create([
            'company_id' => $company->id,
            ...$validated,
        ]);

        return $this->success(['reward' => $reward], null, 201);
    }

    public function show(int $id): JsonResponse
    {
        $reward = $this->ownedReward($id);
        if ($reward instanceof JsonResponse) {
            return $reward;
        }

        $reward->loadCount('rewardRedemptions');
        return $this->success(['reward' => $reward]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $reward = $this->ownedReward($id);
        if ($reward instanceof JsonResponse) {
            return $reward;
        }

        $validated = $this->validateRequest($request, [
            'title' => ['required', 'string', 'max:255'],
            'points_required' => ['required', 'integer', 'min:1'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $reward->update($validated);
        return $this->success(['reward' => $reward->fresh()]);
    }

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
