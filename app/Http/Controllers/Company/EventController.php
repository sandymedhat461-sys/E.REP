<?php

namespace App\Http\Controllers\Company;

use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends BaseCompanyController
{
    /**
     * @OA\Get(
     *     path="/api/company/events",
     *     tags={"Company - Events"},
     *     summary="List company events",
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

        $events = Event::where('company_id', $company->id)
            ->withCount([
                'eventRequests as requests_count',
                'eventInvitations as invitations_count',
            ])
            ->latest('event_date')
            ->get();

        return $this->success(['events' => $events]);
    }

    /**
     * @OA\Post(
     *     path="/api/company/events",
     *     tags={"Company - Events"},
     *     summary="Create event",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="location", type="string"),
     *             @OA\Property(property="event_date", type="string", format="date-time"),
     *             @OA\Property(property="date", type="string", format="date-time", description="Alias for event_date"),
     *             @OA\Property(property="max_capacity", type="integer"),
     *             @OA\Property(property="status", type="string", enum={"upcoming","ongoing","completed","cancelled"}),
     *             @OA\Property(property="points_required", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
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

        if ($request->filled('date') && !$request->filled('event_date')) {
            $request->merge(['event_date' => $request->input('date')]);
        }

        $validated = $this->validateRequest($request, [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'max_capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'in:upcoming,ongoing,completed,cancelled'],
            'points_required' => ['nullable', 'integer', 'min:0'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $eventDate = $validated['event_date'];

        $event = Event::create([
            'company_id' => $company->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'location' => $validated['location'] ?? null,
            'event_date' => $eventDate,
            'max_capacity' => $validated['max_capacity'] ?? null,
            'status' => $validated['status'] ?? 'upcoming',
            'points_required' => $validated['points_required'] ?? null,
        ]);

        return $this->success(['event' => $event], null, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/company/events/{id}",
     *     tags={"Company - Events"},
     *     summary="Get event",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $event = Event::where('company_id', $company->id)
            ->withCount([
                'eventRequests as requests_count',
                'eventInvitations as invitations_count',
            ])
            ->find($id);
        if (!$event) {
            return $this->error('Event not found', 404);
        }

        return $this->success(['event' => $event]);
    }

    /**
     * @OA\Put(
     *     path="/api/company/events/{id}",
     *     tags={"Company - Events"},
     *     summary="Update event",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="location", type="string"),
     *             @OA\Property(property="event_date", type="string", format="date-time"),
     *             @OA\Property(property="max_capacity", type="integer"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="points_required", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $event = Event::where('company_id', $company->id)->find($id);
        if (!$event) {
            return $this->error('Event not found', 404);
        }

        if ($request->filled('date') && !$request->filled('event_date')) {
            $request->merge(['event_date' => $request->input('date')]);
        }

        $validated = $this->validateRequest($request, [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'max_capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'in:upcoming,ongoing,completed,cancelled'],
            'points_required' => ['nullable', 'integer', 'min:0'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $eventDate = $validated['event_date'];
        $event->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'location' => $validated['location'] ?? null,
            'event_date' => $eventDate,
            'max_capacity' => array_key_exists('max_capacity', $validated) ? $validated['max_capacity'] : $event->max_capacity,
            'status' => $validated['status'] ?? $event->status,
            'points_required' => $validated['points_required'] ?? $event->points_required,
        ]);

        $event = Event::where('company_id', $company->id)
            ->withCount([
                'eventRequests as requests_count',
                'eventInvitations as invitations_count',
            ])
            ->find($event->id);

        return $this->success(['event' => $event]);
    }

    /**
     * @OA\Delete(
     *     path="/api/company/events/{id}",
     *     tags={"Company - Events"},
     *     summary="Delete event",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $event = Event::where('company_id', $company->id)->find($id);
        if (!$event) {
            return $this->error('Event not found', 404);
        }

        $event->delete();
        return $this->success([], 'Event deleted');
    }
}
