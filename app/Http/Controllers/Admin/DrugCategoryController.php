<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Drug;
use App\Models\DrugCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DrugCategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/categories",
     *     tags={"Admin - Categories"},
     *     summary="List drug categories",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(): JsonResponse
    {
        if (!auth('admin-api')->user()) {
            return $this->error('Unauthenticated', 401);
        }

        $categories = DrugCategory::query()->orderBy('name')->get();

        return $this->success(['categories' => $categories]);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/categories",
     *     tags={"Admin - Categories"},
     *     summary="Create drug category",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="line_manager_name", type="string", description="Optional")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        if (!auth('admin-api')->user()) {
            return $this->error('Unauthenticated', 401);
        }

        $validated = $this->validateRequest($request, [
            'name' => ['required', 'string', 'max:255'],
            'line_manager_name' => ['nullable', 'string', 'max:255'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $category = DrugCategory::create($validated);

        return $this->success(['category' => $category], null, 201);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/categories/{id}",
     *     tags={"Admin - Categories"},
     *     summary="Update drug category",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="line_manager_name", type="string", description="Optional")
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
        if (!auth('admin-api')->user()) {
            return $this->error('Unauthenticated', 401);
        }

        $category = DrugCategory::find($id);
        if (!$category) {
            return $this->error('Category not found', 404);
        }

        $validated = $this->validateRequest($request, [
            'name' => ['required', 'string', 'max:255'],
            'line_manager_name' => ['nullable', 'string', 'max:255'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $category->update($validated);

        return $this->success(['category' => $category->fresh()]);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/categories/{id}",
     *     tags={"Admin - Categories"},
     *     summary="Delete drug category",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Category has assigned drugs"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        if (!auth('admin-api')->user()) {
            return $this->error('Unauthenticated', 401);
        }

        $category = DrugCategory::find($id);
        if (!$category) {
            return $this->error('Category not found', 404);
        }

        if (Drug::where('category_id', $category->id)->exists()) {
            return $this->error('Category has assigned drugs', 422);
        }

        $category->delete();

        return $this->success([], 'Category deleted');
    }
}
