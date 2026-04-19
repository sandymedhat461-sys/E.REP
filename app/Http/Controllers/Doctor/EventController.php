<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EventController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/doctor/events",
     *     tags={"Doctor - Events"},
     *     summary="List upcoming events",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="company_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Event::query()
            ->where('event_date', '>', Carbon::now())
            ->with('company:id,company_name')
            ->withCount('eventRequests');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

        return $this->success(['events' => $query->orderBy('event_date')->get()]);
    }

    /**
     * @OA\Get(
     *     path="/api/doctor/events/{id}",
     *     tags={"Doctor - Events"},
     *     summary="Get event",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $event = Event::with('company:id,company_name')->find($id);
        if (!$event) {
            return $this->error('Event not found', 404);
        }

        $isRegistered = EventRequest::where('event_id', $event->id)
            ->where('doctor_id', $request->user()->id)
            ->exists();

        return $this->success([
            'event' => $event,
            'is_registered' => $isRegistered,
        ]);
    }
}
