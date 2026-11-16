<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\CoursePerformance;
use App\Models\StaffRecord;
use App\Models\StaffCourseExpertise;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CoursePerformanceController extends Controller
{
    //

    public function myPerformances()
    {
        $user = Auth::user();

        $performances = CoursePerformance::with(['course', 'quiz'])
            ->where('user_id', $user->id)
            ->get();

        return response()->json([
            'message' => 'User performances fetched successfully',
            'data' => $performances
        ]);
    }


    public function allPerformances()
    {
        $user = Auth::user();

        $performances = CoursePerformance::latest()->with(['course', 'quiz', 'user'])
            ->get();

        return response()->json([
            'message' => 'User performances fetched successfully',
            'data' => $performances
        ]);
    }

    public function matrix()
    {
        $courses = Course::query()
            ->select(['id', 'title', 'category', 'level', 'status'])
            ->orderBy('id')
            ->get()
            ->each(function ($course, $index) {
                $course->setAttribute('code', str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT));
            });

        $staff = StaffRecord::query()
            ->with('user:id,name,email,is_active')
            ->select(['id', 'user_id', 'fullname', 'email', 'staff_id', 'status'])
            ->orderBy('fullname')
            ->get();

        $userIds = $staff->pluck('user_id')->filter()->values();
        $performances = CoursePerformance::query()
            ->whereIn('user_id', $userIds)
            ->where('expected_score', '>', 0)
            ->orderByDesc('id')
            ->get()
            ->groupBy(function ($performance) {
                return $performance->user_id . ':' . $performance->course_id;
            });

        $ratings = StaffCourseExpertise::query()
            ->with('ratedBy:id,name')
            ->whereIn('staff_record_id', $staff->pluck('id'))
            ->get()
            ->keyBy(function ($rating) {
                return $rating->staff_record_id . ':' . $rating->course_id;
            });

        $rows = $staff->map(function ($staffRecord) use ($courses, $performances, $ratings) {
            $results = [];

            foreach ($courses as $course) {
                $attempts = $performances->get($staffRecord->user_id . ':' . $course->id, collect());
                $rating = $ratings->get($staffRecord->id . ':' . $course->id);
                $result = $this->matrixResult($attempts, $rating);

                if ($result !== null) {
                    $results[(string) $course->id] = $result;
                }
            }

            return [
                'id' => $staffRecord->id,
                'user_id' => $staffRecord->user_id,
                'fullname' => $staffRecord->fullname,
                'email' => optional($staffRecord->user)->email ?: $staffRecord->email,
                'staff_id' => $staffRecord->staff_id,
                'status' => $staffRecord->status,
                'has_login' => (bool) $staffRecord->user_id,
                'results' => $results,
            ];
        });

        return response()->json([
            'data' => [
                'courses' => $courses,
                'staff' => $rows,
                'thresholds' => [
                    'beginner' => 'Below 50%',
                    'average' => '50% to 79%',
                    'advanced' => '80% and above',
                ],
            ],
        ]);
    }

    public function updateExpertise(Request $request, StaffRecord $staffRecord, Course $course)
    {
        $validated = $request->validate([
            'expertise' => ['required', Rule::in(['beginner', 'average', 'advanced', 'unassessed'])],
        ]);

        $rating = StaffCourseExpertise::updateOrCreate(
            ['staff_record_id' => $staffRecord->id, 'course_id' => $course->id],
            ['expertise' => $validated['expertise'], 'rated_by' => $request->user()->id]
        );

        return response()->json([
            'message' => 'Expertise level saved.',
            'data' => ['result' => $this->matrixResult($this->courseAttempts($staffRecord, $course), $rating->load('ratedBy:id,name'))],
        ]);
    }

    public function resetExpertise(StaffRecord $staffRecord, Course $course)
    {
        StaffCourseExpertise::where('staff_record_id', $staffRecord->id)
            ->where('course_id', $course->id)
            ->delete();

        return response()->json([
            'message' => 'Expertise now follows assessment results.',
            'data' => ['result' => $this->matrixResult($this->courseAttempts($staffRecord, $course))],
        ]);
    }

    private function courseAttempts(StaffRecord $staffRecord, Course $course)
    {
        if (!$staffRecord->user_id) {
            return collect();
        }

        return CoursePerformance::where('user_id', $staffRecord->user_id)
            ->where('course_id', $course->id)
            ->where('expected_score', '>', 0)
            ->orderByDesc('id')
            ->get();
    }

    private function matrixResult($attempts, ?StaffCourseExpertise $rating = null): ?array
    {
        $best = $attempts->sortByDesc(function ($performance) {
            return $performance->total_score / $performance->expected_score;
        })->first();

        $percentage = $best ? max(0, min(100, ($best->total_score / $best->expected_score) * 100)) : null;
        $assessment = $best ? [
            'performance_id' => $best->id,
            'percentage' => round($percentage, 2),
            'expertise' => $this->expertiseLevel($percentage),
            'grade' => $best->grade,
            'status' => $best->status,
            'attempts' => $attempts->count(),
            'completed_at' => optional($best->updated_at)->toIso8601String(),
            'source' => 'assessment',
        ] : null;

        if (!$rating) {
            return $assessment;
        }

        return [
            'expertise' => $rating->expertise,
            'source' => 'manual',
            'percentage' => null,
            'assessment_percentage' => $assessment['percentage'] ?? null,
            'rated_at' => optional($rating->updated_at)->toIso8601String(),
            'rated_by_name' => optional($rating->ratedBy)->name,
        ];
    }

    private function expertiseLevel(float $percentage): string
    {
        if ($percentage >= 80) {
            return 'advanced';
        }

        if ($percentage >= 50) {
            return 'average';
        }

        return 'beginner';
    }
}
