<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffSupervisionSchedule extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function staff(){


        return $this->belongsTo(StaffRecord::class, 'staff_record_id', 'id');
    }

}
