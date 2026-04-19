<?php

namespace App\Http\Controllers\MedicalRep;

use App\Events\MessageSent;
use App\Models\Doctor;
use App\Models\MedicalRep;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends BaseMedicalRepController
{
    /**
     * @OA\Get(
     *     path="/api/rep/messages",
     *     tags={"Rep - Messages"},
     *     summary="List received messages",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(): JsonResponse
    {
        $rep = $this->repOrForbidden();
        if ($rep instanceof JsonResponse) {
            return $rep;
        }

        $messages = Message::query()
            ->where('receiver_id', $rep->id)
            ->where('receiver_type', MedicalRep::class)
            ->with('sender')
            ->orderByDesc('created_at')
            ->get();

        return $this->success(['messages' => $messages]);
    }

    /**
     * @OA\Post(
     *     path="/api/rep/messages",
     *     tags={"Rep - Messages"},
     *     summary="Send message to doctor",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="receiver_id", type="integer"),
     *             @OA\Property(property="receiver_type", type="string", enum={"doctor","company"}),
     *             @OA\Property(property="body", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=404, description="Doctor not found"),
     *     @OA\Response(response=422, description="Validation or unsupported receiver"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $rep = $this->repOrForbidden();
        if ($rep instanceof JsonResponse) {
            return $rep;
        }

        $validated = $this->validateRequest($request, [
            'receiver_id' => ['required', 'integer'],
            'receiver_type' => ['required', 'in:doctor,company'],
            'body' => ['required', 'string'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        if ($validated['receiver_type'] === 'company') {
            return $this->error('Messaging company is not supported for reps', 422);
        }

        if (!Doctor::whereKey($validated['receiver_id'])->exists()) {
            return $this->error('Doctor not found', 404);
        }

        $message = Message::create([
            'sender_type' => 'rep',
            'sender_id' => $rep->id,
            'receiver_id' => $validated['receiver_id'],
            'receiver_type' => Doctor::class,
            'body' => $validated['body'],
            'is_read' => false,
        ]);

        $message->load('sender');
        broadcast(new MessageSent($message))->toOthers();

        return $this->success(['message' => $message], null, 201);
    }

    /**
     * @OA\Post(
     *     path="/api/rep/messages/{id}/read",
     *     tags={"Rep - Messages"},
     *     summary="Mark message as read",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function markAsRead(int $id): JsonResponse
    {
        $rep = $this->repOrForbidden();
        if ($rep instanceof JsonResponse) {
            return $rep;
        }

        $message = Message::query()
            ->where('id', $id)
            ->where('receiver_id', $rep->id)
            ->where('receiver_type', MedicalRep::class)
            ->first();
        if (!$message) {
            return $this->error('Message not found', 404);
        }

        $message->update(['is_read' => true]);

        return $this->success([], 'Marked as read');
    }
}
