<?php

namespace App\Http\Controllers\Company;

use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends BaseCompanyController
{
    public function index(): JsonResponse
    {
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $events = Event::where('company_id', $company->id)->latest()->get();
        return $this->success(['events' => $events]);
    }

    public function store(Request $request): JsonResponse
    {
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $validated = $this->validateRequest($request, [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'points_required' => ['nullable', 'integer', 'min:0'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $event = Event::create([
            'company_id' => $company->id,
            ...$validated,
        ]);

        return $this->success(['event' => $event], null, 201);
    }

    public function show(int $id): JsonResponse
    {
        $company = $this->companyOrForbidden();
        if ($company instanceof JsonResponse) {
            return $company;
        }

        $event = Event::where('company_id', $company->id)
            ->withCount(['eventRequests', 'eventInvitations'])
            ->find($id);
        if (!$event) {
            return $this->error('Event not found', 404);
        }

        return $this->success(['event' => $event]);
    }

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

        $validated = $this->validateRequest($request, [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'points_required' => ['nullable', 'integer', 'min:0'],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $event->update($validated);
        return $this->success(['event' => $event->fresh()]);
    }

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
