<?php

// app/Http/Resources/CalendarEventResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CalendarEventResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'start' => $this->start,
            'end' => $this->end,

            'title' => $this->title,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'extendedProps' => [
                'id' => $this->extendedProps['id'],
                'calendar_event_id' => $this->extendedProps['calendar_event_id'],
                'calendar' => $this->extendedProps['calendar'],
                'guests' => $this->extendedProps['guests'],
                'location' => $this->extendedProps['location'],
                // ... other properties
            ],
        ];
    }
}

