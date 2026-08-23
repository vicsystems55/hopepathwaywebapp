<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveCalendarEventRequest;
use App\Http\Resources\CalendarEventResource;
use App\Models\CalendarEvent;
use App\Models\CalendarEventProp;
use Illuminate\Support\Facades\DB;

class CalendarEventController extends Controller
{
    public function index()
    {
        return CalendarEventResource::collection(
            CalendarEvent::with('extendedProps')->orderBy('start')->get()
        );
    }

    public function show(CalendarEvent $calendarEvent)
    {
        return new CalendarEventResource($calendarEvent->load('extendedProps'));
    }

    public function store(SaveCalendarEventRequest $request)
    {
        $event = $this->persist(new CalendarEvent(), $request->validated());

        return (new CalendarEventResource($event))->response()->setStatusCode(201);
    }

    public function update(SaveCalendarEventRequest $request, CalendarEvent $calendarEvent)
    {
        return new CalendarEventResource(
            $this->persist($calendarEvent, $request->validated())
        );
    }

    public function destroy(CalendarEvent $calendarEvent)
    {
        DB::transaction(function () use ($calendarEvent) {
            $calendarEvent->extendedProps()->delete();
            $calendarEvent->delete();
        });

        return response()->json(['message' => 'Calendar event deleted successfully.']);
    }

    private function persist(CalendarEvent $event, array $data): CalendarEvent
    {
        return DB::transaction(function () use ($event, $data) {
            $event->fill([
                'title' => $data['title'],
                'start' => $data['start'],
                'end' => $data['end'] ?? null,
                'allDay' => $data['allDay'] ?? false,
                'url' => $data['url'] ?? '',
            ])->save();

            CalendarEventProp::updateOrCreate([
                'calendar_event_id' => $event->id,
            ], [
                'calendar' => $data['calendar'] ?? 'General',
                'guests' => $data['guests'] ?? null,
                'location' => $data['location'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            return $event->fresh('extendedProps');
        });
    }
}
