<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffCourseExpertise extends Model
{
    protected $table = 'staff_course_expertise';

    protected $fillable = ['staff_record_id', 'course_id', 'expertise', 'rated_by'];

    public function ratedBy()
    {
        return $this->belongsTo(User::class, 'rated_by');
    }
}
