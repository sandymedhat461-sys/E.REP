<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return $this->success(['company' => $request->user()]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $this->validateRequest($request, [
            'company_name' => ['sometimes', 'string', 'max:255'],
            'hotline' => ['sometimes', 'string'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $company = $request->user();
        $company->fill($validated);
        $company->save();

        return $this->success(['company' => $company->fresh()]);
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

        $company = $request->user();
        if (!Hash::check($validated['current_password'], $company->password)) {
            return $this->error('Current password is incorrect', 422);
        }

        $company->password = bcrypt($validated['new_password']);
        $company->save();

        return $this->success([], 'Password updated successfully');
    }
}
