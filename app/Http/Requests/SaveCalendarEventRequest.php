<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveCalendarEventRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->hasPermission('calendar.manage');
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'start' => 'required|date',
            'end' => 'nullable|date|after_or_equal:start',
            'allDay' => 'sometimes|boolean',
            'url' => 'nullable|string|max:500',
            'calendar' => 'nullable|string|max:80',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'guests' => 'nullable|string|max:1000',
        ];
    }
}
