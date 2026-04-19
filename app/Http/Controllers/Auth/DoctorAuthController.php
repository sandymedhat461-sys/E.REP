<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Throwable;

class DoctorAuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/auth/doctor/register",
     *     tags={"Auth - Doctor"},
     *     summary="Register doctor (multipart: syndicate_id_image required)",
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"full_name","phone","national_id","email","password","password_confirmation","specialization","hospital_name","syndicate_id","syndicate_id_image"},
     *                 @OA\Property(property="full_name", type="string"),
     *                 @OA\Property(property="phone", type="string"),
     *                 @OA\Property(property="national_id", type="string"),
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="password", type="string", format="password"),
     *                 @OA\Property(property="password_confirmation", type="string", format="password"),
     *                 @OA\Property(property="specialization", type="string"),
     *                 @OA\Property(property="hospital_name", type="string"),
     *                 @OA\Property(property="syndicate_id", type="string"),
     *                 @OA\Property(property="profile_image", type="string", format="binary"),
     *                 @OA\Property(property="syndicate_id_image", type="string", format="binary")
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
            'national_id' => ['required', 'string', 'max:20', 'unique:doctors,national_id'],
            'email' => ['required', 'email', 'unique:doctors,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'specialization' => ['required', 'string', 'max:100'],
            'hospital_name' => ['required', 'string', 'max:255'],
            'syndicate_id' => ['required', 'string', 'unique:doctors,syndicate_id', 'min:5', 'max:20'],
            'profile_image' => ['nullable', 'image', 'max:2048'],
            'syndicate_id_image' => ['required', 'image', 'max:2048'],
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
                $data['profile_image'] = $request->file('profile_image')->store('doctors', 'public');
            }

            if ($request->hasFile('syndicate_id_image')) {
                $data['syndicate_id_image'] = $request->file('syndicate_id_image')->store('doctors', 'public');
            }

            $data['status'] = 'pending';

            $doctor = Doctor::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Account created, awaiting admin approval',
                'data' => [
                    'doctor' => $doctor,
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
     *     path="/api/auth/doctor/login",
     *     tags={"Auth - Doctor"},
     *     summary="Doctor login",
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", example="doctor1@erep.com"),
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
            $doctor = Doctor::where('email', $data['email'])->first();

            if (!$doctor || !Hash::check($data['password'], (string) $doctor->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                ], 401);
            }

            if ($doctor->status === 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Account awaiting admin approval',
                ], 403);
            }

            if ($doctor->status === 'blocked') {
                return response()->json([
                    'success' => false,
                    'message' => 'Account has been blocked',
                ], 403);
            }

            $token = $doctor->createToken('doctor-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'data' => [
                    'doctor' => $doctor,
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
     *     path="/api/auth/doctor/logout",
     *     tags={"Auth - Doctor"},
     *     summary="Doctor logout",
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
     *     path="/api/auth/doctor/me",
     *     tags={"Auth - Doctor"},
     *     summary="Current doctor",
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
                'doctor' => $request->user(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/doctor/check-syndicate",
     *     tags={"Auth - Doctor"},
     *     summary="Check syndicate ID availability",
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="syndicate_id", type="string", example="SYN001")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function checkSyndicateId(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'syndicate_id' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $exists = Doctor::where('syndicate_id', $request->syndicate_id)->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'available' => !$exists,
            ],
        ]);
    }
}

