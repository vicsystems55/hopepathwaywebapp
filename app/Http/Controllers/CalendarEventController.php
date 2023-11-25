<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CalendarEvent;
use App\Models\CalendarEventProp;
use App\Http\Resources\CalendarEventResource;

class CalendarEventController extends Controller
{
    //

    public function index(){
             // Retrieve all journal entries
             $journals = CalendarEvent::with('extendedProps')->get();

             // Transform the collection using the resource
             return CalendarEventResource::collection($journals);
    }

    public function store(Request $request){

        // return $request->all();

        $cal_event = CalendarEvent::create([
            'title' => $request->value['title'],
            'start' => $request->value['start'],
            'end' => $request->value['end'],
            'allDay' => $request->value['allDay'],
            'url' => $request->value['url'],

        ]);

        CalendarEventProp::updateOrCreate([
            'calendar_event_id' => $cal_event->id
        ],[
            'calendar' => $request->value['extendedProps']['calendar'],
            // 'guests' => $request->eventProps->guests
            'location' => $request->value['extendedProps']['location'],
            'description' => $request->value['extendedProps']['description']

        ]);

        return $cal_event;

    }
}
