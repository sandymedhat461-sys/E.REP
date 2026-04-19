<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Throwable;

class CompanyAuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/auth/company/register",
     *     tags={"Auth - Company"},
     *     summary="Register company (multipart: company_id_image required)",
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"company_name","email","password","password_confirmation","hotline","commercial_register","company_id_image"},
     *                 @OA\Property(property="company_name", type="string"),
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="password", type="string", format="password"),
     *                 @OA\Property(property="password_confirmation", type="string", format="password"),
     *                 @OA\Property(property="hotline", type="string"),
     *                 @OA\Property(property="commercial_register", type="string"),
     *                 @OA\Property(property="company_profile_image", type="string", format="binary"),
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
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:companies,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'hotline' => ['required', 'string', 'max:20'],
            'commercial_register' => ['required', 'string', 'max:100'],
            'company_profile_image' => ['nullable', 'image', 'max:2048'],
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

            if ($request->hasFile('company_profile_image')) {
                $data['company_profile_image'] = $request->file('company_profile_image')->store('companies', 'public');
            }

            if ($request->hasFile('company_id_image')) {
                $data['company_id_image'] = $request->file('company_id_image')->store('companies', 'public');
            }

            $data['status'] = 'pending';

            $company = Company::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Account created, awaiting admin approval',
                'data' => [
                    'company' => $company,
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
     *     path="/api/auth/company/login",
     *     tags={"Auth - Company"},
     *     summary="Company login",
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", example="company@pharmaegypt.com"),
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
            $company = Company::where('email', $data['email'])->first();

            if (!$company || !Hash::check($data['password'], (string) $company->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                ], 401);
            }

            if ($company->status === 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Account awaiting admin approval',
                ], 403);
            }

            if ($company->status === 'blocked') {
                return response()->json([
                    'success' => false,
                    'message' => 'Account has been blocked',
                ], 403);
            }

            if ($company->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Company account is not approved',
                ], 403);
            }

            $token = $company->createToken('company-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'data' => [
                    'company' => $company,
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
     *     path="/api/auth/company/logout",
     *     tags={"Auth - Company"},
     *     summary="Company logout",
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
     *     path="/api/auth/company/me",
     *     tags={"Auth - Company"},
     *     summary="Current company",
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
                'company' => $request->user(),
            ],
        ]);
    }
}

