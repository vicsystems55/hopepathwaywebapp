<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    use HasFactory;

    protected $guarded = [];


    public function extendedProps(){

        return $this->hasOne(CalendarEventProp::class, 'calendar_event_id', 'id');
    }




}
