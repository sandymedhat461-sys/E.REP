<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\MedicalRep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Throwable;

class MedicalRepAuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/auth/rep/register",
     *     tags={"Auth - Rep"},
     *     summary="Register medical rep (multipart: company_id_image required)",
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"full_name","phone","national_id","email","password","password_confirmation","company_id","company_name","company_id_image"},
     *                 @OA\Property(property="full_name", type="string"),
     *                 @OA\Property(property="phone", type="string"),
     *                 @OA\Property(property="national_id", type="string"),
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="password", type="string", format="password"),
     *                 @OA\Property(property="password_confirmation", type="string", format="password"),
     *                 @OA\Property(property="company_id", type="integer", example=1),
     *                 @OA\Property(property="company_name", type="string"),
     *                 @OA\Property(property="profile_image", type="string", format="binary"),
     *                 @OA\Property(property="company_id_image", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'national_id' => ['required', 'string', 'max:20', 'unique:medical_reps,national_id'],
            'email' => ['required', 'email', 'unique:medical_reps,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'company_id' => ['required', 'exists:companies,id'],
            'company_name' => ['required', 'string', 'max:255'],
            'profile_image' => ['nullable', 'image', 'max:2048'],
            'company_id_image' => ['required', 'image', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();

            if ($request->hasFile('profile_image')) {
                $data['profile_image'] = $request->file('profile_image')->store('reps', 'public');
            }

            if ($request->hasFile('company_id_image')) {
                $data['company_id_image'] = $request->file('company_id_image')->store('reps', 'public');
            }

            $data['status'] = 'pending';

            $rep = MedicalRep::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Account created, awaiting admin approval',
                'data' => [
                    'rep' => $rep,
                ],
            ], 201);
        } catch (Throwable) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/rep/login",
     *     tags={"Auth - Rep"},
     *     summary="Medical rep login",
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", example="rep1@erep.com"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Invalid credentials"),
     *     @OA\Response(response=403, description="Pending or blocked"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();
            $rep = MedicalRep::where('email', $data['email'])->first();

            if (!$rep || !Hash::check($data['password'], (string) $rep->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                ], 401);
            }

            if ($rep->status === 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Account awaiting admin approval',
                ], 403);
            }

            if ($rep->status === 'blocked') {
                return response()->json([
                    'success' => false,
                    'message' => 'Account has been blocked',
                ], 403);
            }

            $token = $rep->createToken('rep-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'data' => [
                    'rep' => $rep,
                    'token' => $token,
                ],
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
     *     path="/api/auth/rep/logout",
     *     tags={"Auth - Rep"},
     *     summary="Medical rep logout",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/auth/rep/me",
     *     tags={"Auth - Rep"},
     *     summary="Current medical rep",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'rep' => $request->user(),
            ],
        ]);
    }
}

