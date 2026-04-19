<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/doctor/favorites",
     *     tags={"Doctor - Drugs"},
     *     summary="List favorite drugs",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $favorites = Favorite::query()
            ->where('doctor_id', $request->user()->id)
            ->with(['drug.company:id,company_name', 'drug.category:id,name'])
            ->latest()
            ->get();

        return $this->success(['favorites' => $favorites]);
    }

    /**
     * @OA\Post(
     *     path="/api/doctor/favorites",
     *     tags={"Doctor - Drugs"},
     *     summary="Add drug to favorites",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="drug_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Already in favorites"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateRequest($request, [
            'drug_id' => ['required', 'exists:drugs,id'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $doctorId = (int) $request->user()->id;
        $exists = Favorite::where('doctor_id', $doctorId)
            ->where('drug_id', $validated['drug_id'])
            ->exists();

        if ($exists) {
            return $this->error('Already in favorites', 422);
        }

        Favorite::create([
            'doctor_id' => $doctorId,
            'drug_id' => $validated['drug_id'],
        ]);

        return $this->success([], 'Added to favorites', 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/doctor/favorites/{drugId}",
     *     tags={"Doctor - Drugs"},
     *     summary="Remove favorite",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="drugId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function destroy(Request $request, int $drugId): JsonResponse
    {
        $favorite = Favorite::where('doctor_id', $request->user()->id)
            ->where('drug_id', $drugId)
            ->first();

        if (!$favorite) {
            return $this->error('Favorite not found', 404);
        }

        $favorite->delete();

        return $this->success([], 'Removed from favorites');
    }
}
