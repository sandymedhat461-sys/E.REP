<?php

namespace App\Http\Controllers\MedicalRep;

use App\Models\Event;
use App\Models\EventInvitation;
use App\Models\RepDoctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventInvitationController extends BaseMedicalRepController
{
    /**
     * @OA\Get(
     *     path="/api/rep/invitations",
     *     tags={"Rep - Meetings"},
     *     summary="List event invitations for assigned doctors",
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

        $doctorIds = RepDoctor::where('rep_id', $rep->id)->pluck('doctor_id');
        $invitations = EventInvitation::query()
            ->whereHas('event', fn ($q) => $q->where('company_id', $rep->company_id))
            ->whereIn('doctor_id', $doctorIds)
            ->with(['event:id,title,event_date', 'doctor:id,full_name,email'])
            ->latest()
            ->get();

        return $this->success(['invitations' => $invitations]);
    }

    /**
     * @OA\Post(
     *     path="/api/rep/events/{eventId}/invite",
     *     tags={"Rep - Meetings"},
     *     summary="Invite assigned doctor to company event",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="eventId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="doctor_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=403, description="Doctor not assigned"),
     *     @OA\Response(response=404, description="Event not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function invite(Request $request, int $eventId): JsonResponse
    {
        $rep = $this->repOrForbidden();
        if ($rep instanceof JsonResponse) {
            return $rep;
        }

        $validated = $this->validateRequest($request, [
            'doctor_id' => ['required', 'exists:doctors,id'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $event = Event::where('company_id', $rep->company_id)->find($eventId);
        if (!$event) {
            return $this->error('Event not found', 404);
        }

        $assigned = RepDoctor::where('rep_id', $rep->id)->where('doctor_id', $validated['doctor_id'])->exists();
        if (!$assigned) {
            return $this->error('Doctor is not assigned to this rep', 403);
        }

        $invitation = EventInvitation::firstOrCreate(
            ['event_id' => $event->id, 'doctor_id' => $validated['doctor_id']],
            ['invited_by_rep_id' => $rep->id, 'status' => 'pending']
        );

        return $this->success(['invitation' => $invitation], null, 201);
    }
}
