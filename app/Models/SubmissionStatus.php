<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmissionStatus extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function visitors_submission(){

        return $this->belongsTo(VisitorsSubmission::class, 'visitors_submission_id', 'id');

    }

    public function office(){

        return $this->belongsTo(VisitorsSubmission::class, 'office_id', 'id');

    }


}
