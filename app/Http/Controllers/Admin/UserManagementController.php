<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Doctor;
use App\Models\MedicalRep;
use Illuminate\Http\JsonResponse;
use Throwable;

class UserManagementController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/users/pending",
     *     tags={"Admin - Users"},
     *     summary="List pending users",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'companies' => Company::where('status', 'pending')->get(),
                'doctors' => Doctor::where('status', 'pending')->get(),
                'reps' => MedicalRep::where('status', 'pending')->get(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/users/{type}/{id}/approve",
     *     tags={"Admin - Users"},
     *     summary="Approve user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="type", in="path", required=true, @OA\Schema(type="string", enum={"company","doctor","rep"})),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function approve(string $type, int $id): JsonResponse
    {
        try {
            $user = $this->resolveUser($type, $id);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            if ($user instanceof Company) {
                $user->status = 'approved';
            } else {
                $user->status = 'active';
            }
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Account approved successfully',
            ]);
        } catch (Throwable) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/users/{type}/{id}/block",
     *     tags={"Admin - Users"},
     *     summary="Block user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="type", in="path", required=true, @OA\Schema(type="string", enum={"company","doctor","rep"})),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function block(string $type, int $id): JsonResponse
    {
        try {
            $user = $this->resolveUser($type, $id);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $user->status = 'blocked';
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Account blocked successfully',
            ]);
        } catch (Throwable) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    private function resolveUser(string $type, int $id): Company|Doctor|MedicalRep|null
    {
        return match ($type) {
            'company' => Company::find($id),
            'doctor' => Doctor::find($id),
            'rep' => MedicalRep::find($id),
            default => null,
        };
    }
}

