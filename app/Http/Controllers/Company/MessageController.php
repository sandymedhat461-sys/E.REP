<?php

namespace App\Http\Controllers\Company;

use App\Events\MessageSent;
use App\Models\Company;
use App\Models\Doctor;
use App\Models\MedicalRep;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends BaseCompanyController
{
    /**
     * @OA\Get(
     *     path="/api/company/messages",
     *     tags={"Company - Messages"},
     *     summary="List received messages",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(): JsonResponse
    {
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $messages = Message::query()
            ->where('receiver_id', $company->id)
            ->where('receiver_type', Company::class)
            ->with('sender')
            ->orderByDesc('created_at')
            ->get();

        return $this->success(['messages' => $messages]);
    }

    /**
     * @OA\Post(
     *     path="/api/company/messages",
     *     tags={"Company - Messages"},
     *     summary="Send message to doctor or rep",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="receiver_id", type="integer"),
     *             @OA\Property(property="receiver_type", type="string", enum={"doctor","medical_rep"}),
     *             @OA\Property(property="body", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=404, description="Receiver not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $validated = $this->validateRequest($request, [
            'receiver_id' => ['required', 'integer'],
            'receiver_type' => ['required', 'in:doctor,medical_rep'],
            'body' => ['required', 'string'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $receiverClass = match ($validated['receiver_type']) {
            'doctor' => Doctor::class,
            'medical_rep' => MedicalRep::class,
            default => null,
        };

        if ($receiverClass === Doctor::class) {
            if (!Doctor::whereKey($validated['receiver_id'])->exists()) {
                return $this->error('Receiver not found', 404);
            }
        } elseif (!MedicalRep::whereKey($validated['receiver_id'])->where('company_id', $company->id)->exists()) {
            return $this->error('Receiver not found', 404);
        }

        $message = Message::create([
            'sender_type' => Company::class,
            'sender_id' => $company->id,
            'receiver_id' => $validated['receiver_id'],
            'receiver_type' => $receiverClass,
            'body' => $validated['body'],
            'is_read' => false,
        ]);

        $message->load('sender');
        broadcast(new MessageSent($message))->toOthers();

        return $this->success(['message' => $message], null, 201);
    }

    /**
     * @OA\Post(
     *     path="/api/company/messages/{id}/read",
     *     tags={"Company - Messages"},
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
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $message = Message::query()
            ->where('id', $id)
            ->where('receiver_id', $company->id)
            ->where('receiver_type', Company::class)
            ->first();

        if (!$message) {
            return $this->error('Message not found', 404);
        }

        $message->update(['is_read' => true]);

        return $this->success([], 'Marked as read');
    }
}
