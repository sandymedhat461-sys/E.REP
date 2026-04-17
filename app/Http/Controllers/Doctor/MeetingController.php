<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Meeting::query()
            ->where('doctor_id', $request->user()->id)
            ->with('rep:id,full_name,email,phone');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return $this->success(['meetings' => $query->latest()->get()]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $meeting = Meeting::query()
            ->where('doctor_id', $request->user()->id)
            ->with('rep:id,full_name,email,phone')
            ->find($id);

        if (!$meeting) {
            return $this->error('Meeting not found', 404);
        }

        return $this->success(['meeting' => $meeting]);
    }
}
