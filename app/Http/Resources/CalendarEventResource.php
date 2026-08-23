<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CalendarEventResource extends JsonResource
{
    public function toArray($request)
    {
        $properties = $this->extendedProps;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'start' => optional($this->start)->toIso8601String(),
            'end' => optional($this->end)->toIso8601String(),
            'allDay' => (bool) $this->allDay,
            'url' => $this->url,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'extendedProps' => [
                'calendar' => optional($properties)->calendar ?: 'General',
                'guests' => optional($properties)->guests,
                'location' => optional($properties)->location,
                'description' => optional($properties)->description,
            ],
        ];
    }
}
