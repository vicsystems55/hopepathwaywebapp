<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffTraining extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function staff()
    {
        return $this->belongsTo(StaffRecord::class, 'staff_record_id', 'id');
    }

    public function trainingProgramme()
    {
        return $this->belongsTo(TrainingProgramme::class, 'training_programme_id', 'id');
    }
}
