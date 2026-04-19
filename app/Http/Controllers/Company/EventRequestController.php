<?php

namespace App\Http\Controllers\Company;

use App\Models\Event;
use App\Models\EventRequest;
use Illuminate\Http\JsonResponse;

class EventRequestController extends BaseCompanyController
{
    /**
     * @OA\Get(
     *     path="/api/company/events/{eventId}/requests",
     *     tags={"Company - Events"},
     *     summary="List event registration requests",
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

        $requests = EventRequest::where('event_id', $event->id)
            ->with('doctor:id,full_name,email,specialization')
            ->latest()
            ->get();

        return $this->success(['requests' => $requests]);
    }

    /**
     * @OA\Post(
     *     path="/api/company/events/{eventId}/requests/{id}/approve",
     *     tags={"Company - Events"},
     *     summary="Approve event request",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="eventId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function approve(int $eventId, int $id): JsonResponse
    {
        $request = $this->ownedEventRequest($eventId, $id);
        if ($request instanceof JsonResponse) {
            return $request;
        }

        $request->update(['status' => 'approved']);
        return $this->success(['request' => $request->fresh()]);
    }

    /**
     * @OA\Post(
     *     path="/api/company/events/{eventId}/requests/{id}/reject",
     *     tags={"Company - Events"},
     *     summary="Reject event request",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="eventId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function reject(int $eventId, int $id): JsonResponse
    {
        $request = $this->ownedEventRequest($eventId, $id);
        if ($request instanceof JsonResponse) {
            return $request;
        }

        $request->update(['status' => 'rejected']);
        return $this->success(['request' => $request->fresh()]);
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

    private function ownedEventRequest(int $eventId, int $id): EventRequest|JsonResponse
    {
        $event = $this->ownedEvent($eventId);
        if ($event instanceof JsonResponse) {
            return $event;
        }

        $request = EventRequest::where('event_id', $event->id)->find($id);
        if (!$request) {
            return $this->error('Event request not found', 404);
        }

        return $request;
    }
}
