<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return $this->success(['doctor' => $request->user()]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $this->validateRequest($request, [
            'full_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string'],
            'hospital_name' => ['sometimes', 'string'],
            'specialization' => ['sometimes', 'string'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $doctor = $request->user();
        $doctor->fill($validated);
        $doctor->save();

        return $this->success(['doctor' => $doctor->fresh()]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $this->validateRequest($request, [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $doctor = $request->user();
        if (!Hash::check($validated['current_password'], $doctor->password)) {
            return $this->error('Current password is incorrect', 422);
        }

        $doctor->password = bcrypt($validated['new_password']);
        $doctor->save();

        return $this->success([], 'Password updated successfully');
    }
}
