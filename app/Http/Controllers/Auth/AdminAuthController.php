<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Support\PersonalAccessTokenLabel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Throwable;

class AdminAuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/auth/admin/register",
     *     tags={"Auth - Admin"},
     *     summary="Register admin",
     *
     *     @OA\RequestBody(
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="full_name", type="string", example="Admin User"),
     *             @OA\Property(property="phone", type="string", example="+201000000099"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password")
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $admin = Admin::create($validator->validated());
            $token = $admin->createToken(PersonalAccessTokenLabel::make(
                (string) $admin->full_name,
                PersonalAccessTokenLabel::ROLE_ADMIN
            ))->plainTextToken;

            return response()->json([
                'success' => true,
                'data' => [
                    'admin' => $admin,
                    'token' => $token,
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
     *     path="/api/auth/admin/login",
     *     tags={"Auth - Admin"},
     *     summary="Admin login",
     *
     *     @OA\RequestBody(
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="email", type="string", example="admin@erep.com"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Invalid credentials"),
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
            $admin = Admin::where('email', $data['email'])->first();

            if (! $admin || ! Hash::check($data['password'], (string) $admin->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                ], 401);
            }

            $token = $admin->createToken(PersonalAccessTokenLabel::make(
                (string) $admin->full_name,
                PersonalAccessTokenLabel::ROLE_ADMIN
            ))->plainTextToken;

            return response()->json([
                'success' => true,
                'data' => [
                    'admin' => $admin,
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
     *     path="/api/auth/admin/logout",
     *     tags={"Auth - Admin"},
     *     summary="Admin logout",
     *     security={{"bearerAuth":{}}},
     *
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
     *     path="/api/auth/admin/me",
     *     tags={"Auth - Admin"},
     *     summary="Current admin",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'admin' => $request->user(),
            ],
        ]);
    }
}
