<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return $this->success(['admin' => $request->user()]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $this->validateRequest($request, [
            'full_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string'],
            'email' => ['sometimes', 'email', 'unique:admins,email,' . $request->user()->id],
            'image' => ['sometimes', 'string'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $admin = $request->user();
        $admin->fill($validated);
        $admin->save();

        return $this->success(['admin' => $admin->fresh()]);
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

        $admin = $request->user();
        if (!Hash::check($validated['current_password'], $admin->password)) {
            return $this->error('Current password is incorrect', 422);
        }

        $admin->password = bcrypt($validated['new_password']);
        $admin->save();

        return $this->success([], 'Password updated successfully');
    }
}
