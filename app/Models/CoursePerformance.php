<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoursePerformance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'quiz_id',
        'total_score',
        'expected_score',
        'grade',
        'attempts',
        'status',
        'certificate_status',
        'certificate_path',
        'completion_status',
        'reviewed_by',
    ];

    // 🔗 Relationship to User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 🔗 Relationship to Course
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // 🔗 Relationship to Quiz
    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    // 🔗 Reviewer (admin user)
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
