<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffRecord extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function qualifications(){

        return $this->hasMany(StaffQualification::class, 'staff_record_id', 'id');
    }

    public function supervision_schedule(){

        return $this->hasMany(StaffSupervisionSchedule::class, 'staff_record_id', 'id');
    }

}
