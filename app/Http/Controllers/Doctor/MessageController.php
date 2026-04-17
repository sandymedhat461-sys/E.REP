<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $doctorId = (int) $request->user()->id;
        $messages = Message::query()
            ->where(function ($query) use ($doctorId) {
                $query->where('sender_type', 'doctor')->where('sender_id', $doctorId);
            })
            ->orWhere('receiver_doctor_id', $doctorId)
            ->orderByDesc('created_at')
            ->get();

        return $this->success(['messages' => $messages]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateRequest($request, [
            'receiver_rep_id' => ['required', 'exists:medical_reps,id'],
            'content' => ['required', 'string'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $message = Message::create([
            'sender_type' => 'doctor',
            'sender_id' => $request->user()->id,
            'receiver_rep_id' => $validated['receiver_rep_id'],
            'receiver_doctor_id' => null,
            'content' => $validated['content'],
        ]);

        return $this->success(['message' => $message], null, 201);
    }

    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $message = Message::where('id', $id)
            ->where('receiver_doctor_id', $request->user()->id)
            ->first();

        if (!$message) {
            return $this->error('Message not found', 404);
        }

        $message->update(['read_status' => true]);

        return $this->success([], 'Marked as read');
    }
}
