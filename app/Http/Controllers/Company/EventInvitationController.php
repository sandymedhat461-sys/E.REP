<?php

namespace App\Http\Controllers\Company;

use App\Models\Doctor;
use App\Models\Event;
use App\Models\EventInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventInvitationController extends BaseCompanyController
{
    /**
     * @OA\Post(
     *     path="/api/company/events/{eventId}/invite",
     *     tags={"Company - Events"},
     *     summary="Invite doctors to event",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="eventId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="doctor_ids", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=404, description="Event not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function invite(Request $request, int $eventId): JsonResponse
    {
        $event = $this->ownedEvent($eventId);
        if ($event instanceof JsonResponse) {
            return $event;
        }

        $validated = $this->validateRequest($request, [
            'doctor_ids' => ['required', 'array', 'min:1'],
            'doctor_ids.*' => ['exists:doctors,id'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $doctorIds = Doctor::whereIn('id', $validated['doctor_ids'])->pluck('id')->all();
        $created = [];
        foreach ($doctorIds as $doctorId) {
            $created[] = EventInvitation::firstOrCreate(
                ['event_id' => $event->id, 'doctor_id' => $doctorId],
                ['status' => 'pending']
            );
        }

        return $this->success(['invitations' => $created], null, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/company/events/{eventId}/invitations",
     *     tags={"Company - Events"},
     *     summary="List event invitations",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="eventId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Event not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(int $eventId): JsonResponse
    {
        $event = $this->ownedEvent($eventId);
        if ($event instanceof JsonResponse) {
            return $event;
        }

        $invitations = EventInvitation::where('event_id', $event->id)
            ->with('doctor:id,full_name,email')
            ->latest()
            ->get();

        return $this->success(['invitations' => $invitations]);
    }

    private function ownedEvent(int $eventId): Event|JsonResponse
    {
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $event = Event::where('company_id', $company->id)->find($eventId);
        if (!$event) {
            return $this->error('Event not found', 404);
        }

        return $event;
    }
}
