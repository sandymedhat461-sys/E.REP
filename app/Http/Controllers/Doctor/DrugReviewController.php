<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DoctorPoint;
use App\Models\Drug;
use App\Models\DrugReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DrugReviewController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/doctor/drugs/{drugId}/reviews",
     *     tags={"Doctor - Drugs"},
     *     summary="List drug reviews",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="drugId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Drug not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(int $drugId): JsonResponse
    {
        if (!Drug::whereKey($drugId)->exists()) {
            return $this->error('Drug not found', 404);
        }

        $reviews = DrugReview::query()
            ->where('drug_id', $drugId)
            ->with('doctor:id,full_name')
            ->latest()
            ->get();

        return $this->success(['reviews' => $reviews]);
    }

    /**
     * @OA\Post(
     *     path="/api/doctor/drugs/{drugId}/reviews",
     *     tags={"Doctor - Drugs"},
     *     summary="Create drug review",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="drugId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="rating", type="integer", minimum=1, maximum=5),
     *             @OA\Property(property="comment", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Already reviewed or validation error"),
     *     @OA\Response(response=404, description="Drug not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(Request $request, int $drugId): JsonResponse
    {
        if (!Drug::whereKey($drugId)->exists()) {
            return $this->error('Drug not found', 404);
        }

        $validated = $this->validateRequest($request, [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $doctorId = (int) $request->user()->id;
        $alreadyReviewed = DrugReview::where('drug_id', $drugId)
            ->where('doctor_id', $doctorId)
            ->exists();

        if ($alreadyReviewed) {
            return $this->error('You already reviewed this drug', 422);
        }

        $review = DrugReview::create([
            'drug_id' => $drugId,
            'doctor_id' => $doctorId,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        DoctorPoint::create([
            'doctor_id' => $doctorId,
            'source' => 'review',
            'source_id' => $review->id,
            'value' => 5,
        ]);

        return $this->success(['review' => $review], null, 201);
    }
}
