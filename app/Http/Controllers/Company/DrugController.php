<?php

namespace App\Http\Controllers\Company;

use App\Models\ActiveIngredient;
use App\Models\Drug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DrugController extends BaseCompanyController
{
    public function index(): JsonResponse
    {
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $drugs = Drug::where('company_id', $company->id)
            ->with(['category', 'activeIngredients'])
            ->get();

        return $this->success(['drugs' => $drugs]);
    }

    public function store(Request $request): JsonResponse
    {
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $validated = $this->validateRequest($request, [
            'market_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:drug_categories,id'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'dosage' => ['nullable', 'string', 'max:255'],
            'side_effects' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:255'],
            'ingredient_ids' => ['nullable', 'array'],
            'ingredient_ids.*' => ['exists:active_ingredients,id'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $drug = Drug::create([
            'company_id' => $company->id,
            'market_name' => $validated['market_name'],
            'description' => $validated['description'] ?? null,
            'category_id' => $validated['category_id'],
            'price' => $validated['price'] ?? null,
            'dosage' => $validated['dosage'] ?? null,
            'side_effects' => $validated['side_effects'] ?? null,
            'image' => $validated['image'] ?? null,
        ]);

        $ingredientIds = $validated['ingredient_ids'] ?? [];
        if (!empty($ingredientIds)) {
            $ownedIngredientIds = ActiveIngredient::where('created_by_company_id', $company->id)
                ->whereIn('id', $ingredientIds)
                ->pluck('id')
                ->all();
            $drug->ingredients()->sync($ownedIngredientIds);
        }

        return $this->success(['drug' => $drug->load(['category', 'activeIngredients'])], null, 201);
    }

    public function show(int $id): JsonResponse
    {
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $drug = Drug::where('company_id', $company->id)
            ->with(['category', 'activeIngredients'])
            ->find($id);
        if (!$drug) {
            return $this->error('Drug not found', 404);
        }

        return $this->success(['drug' => $drug]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $drug = Drug::where('company_id', $company->id)->find($id);
        if (!$drug) {
            return $this->error('Drug not found', 404);
        }

        $validated = $this->validateRequest($request, [
            'market_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:drug_categories,id'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'dosage' => ['nullable', 'string', 'max:255'],
            'side_effects' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:255'],
            'ingredient_ids' => ['nullable', 'array'],
            'ingredient_ids.*' => ['exists:active_ingredients,id'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $drug->update([
            'market_name' => $validated['market_name'],
            'description' => $validated['description'] ?? null,
            'category_id' => $validated['category_id'],
            'price' => $validated['price'] ?? null,
            'dosage' => $validated['dosage'] ?? null,
            'side_effects' => $validated['side_effects'] ?? null,
            'image' => $validated['image'] ?? $drug->image,
        ]);

        if (array_key_exists('ingredient_ids', $validated)) {
            $ownedIngredientIds = ActiveIngredient::where('created_by_company_id', $company->id)
                ->whereIn('id', $validated['ingredient_ids'] ?? [])
                ->pluck('id')
                ->all();
            $drug->ingredients()->sync($ownedIngredientIds);
        }

        return $this->success(['drug' => $drug->fresh()->load(['category', 'activeIngredients'])]);
    }

    public function destroy(int $id): JsonResponse
    {
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $drug = Drug::where('company_id', $company->id)->find($id);
        if (!$drug) {
            return $this->error('Drug not found', 404);
        }

        $drug->delete();
        return $this->success([], 'Drug deleted');
    }
}
