<?php

namespace App\Services;

use App\Models\CoursePerformance;
use App\Models\Quiz;
use Illuminate\Support\Facades\Auth;

class CoursePerformanceService
{
    public function recordPerformance($courseId, $quizId, $totalScore)
    {
        $user = Auth::user();

        // Expected score = sum of marks from all quiz questions
        $quiz = Quiz::with('questions')->findOrFail($quizId);
        $expectedScore = $quiz->questions->sum('mark');

        // Percentage and grading
        $percentage = ($expectedScore > 0) ? ($totalScore / $expectedScore) * 100 : 0;
        $grade = $this->getGrade($percentage);

        // Certificate logic
        // $certificateStatus = $percentage >= 70 ? 'issued' : 'not-issued';
        $certificateStatus = 'not-issued';

        $certificatePath = null;

        if ($certificateStatus === 'issued') {
            $certificatePath = "certificates/user_{$user->id}_course_{$courseId}.pdf";
            // TODO: generate and save PDF file here
        }

        // Save performance
        return CoursePerformance::create([
            'user_id' => $user->id,
            'course_id' => $courseId,
            'quiz_id' => $quizId,
            'total_score' => $totalScore,
            'expected_score' => $expectedScore,
            'grade' => $grade,
            'attempts' => 1,
            'status' => $percentage >= 50 ? 'passed' : 'failed',
            'completion_status' => 'completed',
            'certificate_status' => $certificateStatus,
            'certificate_path' => $certificatePath,
            'reviewed_by' => 1, // admin
        ]);
    }

    private function getGrade($percentage)
    {
        if ($percentage >= 90) return 'A';
        if ($percentage >= 80) return 'B';
        if ($percentage >= 70) return 'C';
        if ($percentage >= 60) return 'D';
        return 'F';
    }
}
