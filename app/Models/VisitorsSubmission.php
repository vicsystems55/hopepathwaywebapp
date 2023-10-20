<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorsSubmission extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function office(){
        return $this->belongsTo(Office::class, 'office_id', 'id');
    }

    public function visitor(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
