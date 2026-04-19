<?php

namespace App\Http\Controllers\Doctor;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\MedicalRep;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/doctor/messages",
     *     tags={"Doctor - Messages"},
     *     summary="List messages",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $doctorId = (int) $request->user()->id;
        $messages = Message::query()
            ->where(function ($query) use ($doctorId) {
                $query->where(function ($q) use ($doctorId) {
                    $q->where('sender_type', 'doctor')->where('sender_id', $doctorId);
                })->orWhere(function ($q) use ($doctorId) {
                    $q->where('receiver_id', $doctorId)->where('receiver_type', Doctor::class);
                });
            })
            ->with(['sender', 'receiver'])
            ->orderByDesc('created_at')
            ->get();

        return $this->success(['messages' => $messages]);
    }

    /**
     * @OA\Post(
     *     path="/api/doctor/messages",
     *     tags={"Doctor - Messages"},
     *     summary="Send message to medical rep",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="receiver_rep_id", type="integer"),
     *             @OA\Property(property="content", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
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
            'receiver_id' => $validated['receiver_rep_id'],
            'receiver_type' => MedicalRep::class,
            'body' => $validated['content'],
            'is_read' => false,
        ]);

        $message->load('sender');
        broadcast(new MessageSent($message))->toOthers();

        return $this->success(['message' => $message], null, 201);
    }

    /**
     * @OA\Post(
     *     path="/api/doctor/messages/{id}/read",
     *     tags={"Doctor - Messages"},
     *     summary="Mark message as read",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $message = Message::where('id', $id)
            ->where('receiver_id', $request->user()->id)
            ->where('receiver_type', Doctor::class)
            ->first();

        if (!$message) {
            return $this->error('Message not found', 404);
        }

        $message->update(['is_read' => true]);

        return $this->success([], 'Marked as read');
    }
}
