<?php

namespace App\Http\Controllers\Company;

use App\Models\ActiveIngredient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActiveIngredientController extends BaseCompanyController
{
    /**
     * @OA\Get(
     *     path="/api/company/ingredients",
     *     tags={"Company - Drugs"},
     *     summary="List company active ingredients",
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

        $ingredients = ActiveIngredient::where('created_by_company_id', $company->id)->get();
        return $this->success(['ingredients' => $ingredients]);
    }

    /**
     * @OA\Post(
     *     path="/api/company/ingredients",
     *     tags={"Company - Drugs"},
     *     summary="Create active ingredient",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="side_effect", type="string")
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
            'name' => ['required', 'string', 'max:255', 'unique:active_ingredients,name'],
            'description' => ['nullable', 'string'],
            'side_effect' => ['nullable', 'string'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $validated['created_by_company_id'] = $company->id;
        $ingredient = ActiveIngredient::create($validated);

        return $this->success(['ingredient' => $ingredient], null, 201);
    }

    /**
     * @OA\Put(
     *     path="/api/company/ingredients/{id}",
     *     tags={"Company - Drugs"},
     *     summary="Update active ingredient",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="side_effect", type="string")
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
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $ingredient = ActiveIngredient::where('id', $id)
            ->where('created_by_company_id', $company->id)
            ->first();
        if (!$ingredient) {
            return $this->error('Ingredient not found', 404);
        }

        $validated = $this->validateRequest($request, [
            'name' => ['required', 'string', 'max:255', 'unique:active_ingredients,name,' . $id],
            'description' => ['nullable', 'string'],
            'side_effect' => ['nullable', 'string'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $ingredient->update($validated);
        return $this->success(['ingredient' => $ingredient->fresh()]);
    }

    /**
     * @OA\Delete(
     *     path="/api/company/ingredients/{id}",
     *     tags={"Company - Drugs"},
     *     summary="Delete active ingredient",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $ingredient = ActiveIngredient::where('id', $id)
            ->where('created_by_company_id', $company->id)
            ->first();
        if (!$ingredient) {
            return $this->error('Ingredient not found', 404);
        }

        $ingredient->delete();
        return $this->success([], 'Ingredient deleted');
    }
}
