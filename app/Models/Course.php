<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'feature_image',
        'title',
        'short_description',
        'duration',
        'category',
        'level',
        'created_by',
        'status',
    ];

    public function outlines()
    {
        return $this->hasMany(CourseOutline::class);
    }

    public function quiz()
    {
        return $this->hasOne(Quiz::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'course_user')->withPivot([
            'current_outline_id', 'completion_percentage', 'assigned_by', 'assigned_at', 'started_at', 'completed_at'
        ])->withTimestamps();
    }
}
