<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DrugSample;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DrugSampleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DrugSample::query()
            ->where('doctor_id', $request->user()->id)
            ->with(['drug:id,market_name,company_id,category_id', 'rep:id,full_name,email']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return $this->success(['samples' => $query->latest()->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateRequest($request, [
            'drug_id' => ['required', 'exists:drugs,id'],
            'rep_id' => ['required', 'exists:medical_reps,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $sample = DrugSample::create([
            'doctor_id' => $request->user()->id,
            'drug_id' => $validated['drug_id'],
            'rep_id' => $validated['rep_id'],
            'quantity' => $validated['quantity'],
            'status' => 'pending',
        ]);

        return $this->success(['sample' => $sample], null, 201);
    }
}
