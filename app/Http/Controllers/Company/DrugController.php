<?php

namespace App\Http\Controllers\Company;

use App\Models\ActiveIngredient;
use App\Models\Drug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DrugController extends BaseCompanyController
{
    /**
     * @OA\Get(
     *     path="/api/company/drugs",
     *     tags={"Company - Drugs"},
     *     summary="List company drugs",
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

        $drugs = Drug::where('company_id', $company->id)
            ->with(['category', 'ingredients'])
            ->get();

        return $this->success(['drugs' => $drugs]);
    }

    /**
     * @OA\Post(
     *     path="/api/company/drugs",
     *     tags={"Company - Drugs"},
     *     summary="Create drug (multipart if image)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name","category_id"},
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="category_id", type="integer"),
     *                 @OA\Property(property="image", type="string", format="binary"),
     *                 @OA\Property(property="status", type="string", enum={"active","inactive"}),
     *                 @OA\Property(property="ingredient_ids", type="array", @OA\Items(type="integer"))
     *             )
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
            'category_id' => ['required', 'exists:drug_categories,id'],
            'image' => ['nullable', 'image', 'max:2048'],
            'status' => ['nullable', 'in:active,inactive'],
            'ingredient_ids' => ['nullable', 'array'],
            'ingredient_ids.*' => ['integer', 'exists:active_ingredients,id'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('drugs', 'public');
        }

        $name = $validated['name'];
        $payload = [
            'company_id' => $company->id,
            'market_name' => $name,
            'description' => $validated['description'] ?? null,
            'category_id' => $validated['category_id'],
            'image' => $imagePath,
        ];
        if (Schema::hasColumn('drugs', 'name')) {
            $payload['name'] = $name;
        }
        if (Schema::hasColumn('drugs', 'status')) {
            $payload['status'] = $validated['status'] ?? 'active';
        }
        $drug = Drug::create($payload);

        $ingredientIds = $validated['ingredient_ids'] ?? [];
        $ownedIngredientIds = ActiveIngredient::where('created_by_company_id', $company->id)
            ->whereIn('id', $ingredientIds)
            ->pluck('id')
            ->all();
        $drug->ingredients()->sync($ownedIngredientIds);

        return $this->success(['drug' => $drug->load(['category', 'ingredients'])], null, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/company/drugs/{id}",
     *     tags={"Company - Drugs"},
     *     summary="Get drug",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $drug = Drug::where('company_id', $company->id)
            ->with(['category', 'ingredients'])
            ->find($id);
        if (!$drug) {
            return $this->error('Drug not found', 404);
        }

        return $this->success(['drug' => $drug]);
    }

    /**
     * @OA\Put(
     *     path="/api/company/drugs/{id}",
     *     tags={"Company - Drugs"},
     *     summary="Update drug",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name","category_id"},
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="category_id", type="integer"),
     *                 @OA\Property(property="image", type="string", format="binary"),
     *                 @OA\Property(property="status", type="string", enum={"active","inactive"}),
     *                 @OA\Property(property="ingredient_ids", type="array", @OA\Items(type="integer"))
     *             )
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

        $drug = Drug::where('company_id', $company->id)->find($id);
        if (!$drug) {
            return $this->error('Drug not found', 404);
        }

        $validated = $this->validateRequest($request, [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:drug_categories,id'],
            'image' => ['nullable', 'image', 'max:2048'],
            'status' => ['nullable', 'in:active,inactive'],
            'ingredient_ids' => ['nullable', 'array'],
            'ingredient_ids.*' => ['integer', 'exists:active_ingredients,id'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $name = $validated['name'];
        $data = [
            'market_name' => $name,
            'description' => $validated['description'] ?? null,
            'category_id' => $validated['category_id'],
        ];
        if (Schema::hasColumn('drugs', 'name')) {
            $data['name'] = $name;
        }
        if (Schema::hasColumn('drugs', 'status')) {
            $data['status'] = $validated['status'] ?? $drug->status ?? 'active';
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('drugs', 'public');
        }

        $drug->update($data);

        if (array_key_exists('ingredient_ids', $validated)) {
            $ownedIngredientIds = ActiveIngredient::where('created_by_company_id', $company->id)
                ->whereIn('id', $validated['ingredient_ids'] ?? [])
                ->pluck('id')
                ->all();
            $drug->ingredients()->sync($ownedIngredientIds);
        }

        return $this->success(['drug' => $drug->fresh()->load(['category', 'ingredients'])]);
    }

    /**
     * @OA\Delete(
     *     path="/api/company/drugs/{id}",
     *     tags={"Company - Drugs"},
     *     summary="Delete drug",
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

        $drug = Drug::where('company_id', $company->id)->find($id);
        if (!$drug) {
            return $this->error('Drug not found', 404);
        }

        $drug->delete();
        return $this->success([], 'Drug deleted');
    }
}
