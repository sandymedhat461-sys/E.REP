<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Drug;
use App\Models\Favorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DrugController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/doctor/drugs",
     *     tags={"Doctor - Drugs"},
     *     summary="List drugs (paginated)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="category_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="company_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $doctorId = (int) $request->user()->id;

        $query = Drug::query()
            ->with(['company:id,company_name', 'category:id,name'])
            ->withAvg('drugReviews as average_rating', 'rating');

        if ($request->filled('search')) {
            $query->where('market_name', 'like', '%' . $request->string('search') . '%');
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

        $drugs = $query->paginate(15);
        $drugs->getCollection()->transform(function (Drug $drug) use ($doctorId) {
            $drug->is_favorite = Favorite::where('doctor_id', $doctorId)->where('drug_id', $drug->id)->exists();
            return $drug;
        });

        return $this->success(['drugs' => $drugs]);
    }

    /**
     * @OA\Get(
     *     path="/api/doctor/drugs/{id}",
     *     tags={"Doctor - Drugs"},
     *     summary="Get drug detail",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $doctorId = (int) $request->user()->id;
        $drug = Drug::query()
            ->with([
                'company:id,company_name',
                'category:id,name',
                'activeIngredients:id,name,description,side_effect',
            ])
            ->find($id);

        if (!$drug) {
            return $this->error('Drug not found', 404);
        }

        $averageRating = (float) Drug::query()
            ->where('id', $drug->id)
            ->withAvg('drugReviews as average_rating', 'rating')
            ->value('average_rating');

        $reviewsCount = $drug->drugReviews()->count();
        $isFavorite = Favorite::where('doctor_id', $doctorId)->where('drug_id', $drug->id)->exists();

        return $this->success([
            'drug' => $drug,
            'average_rating' => $averageRating,
            'reviews_count' => $reviewsCount,
            'is_favorite' => $isFavorite,
        ]);
    }
}
