<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Support\Facades\Auth;
use App\Services\CoursePerformanceService;


class QuizAttemptController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Optionally filter by user_id or quiz_id
        $userId = request('user_id');
        $quizId = request('quiz_id');
        $query = DB::table('quiz_attempts');
        if ($userId) {
            $query->where('user_id', $userId);
        }
        if ($quizId) {
            $query->where('quiz_id', $quizId);
        }
        return response()->json($query->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request, CoursePerformanceService $performanceService)
    {

        // return $request->all();
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'quiz_id' => 'required|exists:quizzes,id',
            'answers' => 'required|array|min:1',
            'answers.*.quiz_question_id' => 'required|exists:quiz_questions,id',
            'answers.*.answer' => 'nullable|string',
        ]);

        $user = Auth::user();

        $totalScore = 0;
        $attempts = [];

        // return $request->all();


        DB::beginTransaction();
        try {
            foreach ($request->answers as $answerData) {
                $question = QuizQuestion::findOrFail($answerData['quiz_question_id']);

                $isCorrect = trim(strtolower($answerData['answer'])) === trim(strtolower($question->correct_answer));

                $score = $isCorrect ? $question->mark : 0;

                $attempt = QuizAttempt::create([
                    'user_id' => $user->id,
                    'quiz_id' => $request->quiz_id,
                    'quiz_question_id' => $question->id,
                    'course_id' => $request->course_id,
                    'answer' => $answerData['answer'],
                    'correct_answer' => $question->correct_answer,
                    'score' => $score,
                ]);

                $totalScore += $score;
                $attempts[] = $attempt;
            }

            // ✅ Record course performance in a separate service
            $performance = $performanceService->recordPerformance(
                $request->course_id,
                $request->quiz_id,
                $totalScore
            );

            DB::commit();




            return response()->json([
                'message' => 'Quiz submitted successfully',
                'total_score' => $totalScore,
                'attempts' => $attempts
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to submit quiz',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $record = DB::table('quiz_attempts')->where('id', $id)->first();
        if (!$record) {
            return response()->json(['message' => 'Not found'], 404);
        }
        if (isset($record->report)) {
            $record->report = json_decode($record->report);
        }
        return response()->json($record);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'score' => 'sometimes|required|integer|min:0',
            'total_questions' => 'sometimes|required|integer|min:1',
            'correct_answers' => 'sometimes|required|integer|min:0',
            'attempted_at' => 'nullable|date',
            'report' => 'nullable|array',
        ]);
        if (isset($validated['report'])) {
            $validated['report'] = json_encode($validated['report']);
        }
        $affected = DB::table('quiz_attempts')->where('id', $id)->update($validated);
        if (!$affected) {
            return response()->json(['message' => 'Not found or not updated'], 404);
        }
        $record = DB::table('quiz_attempts')->where('id', $id)->first();
        if ($record && isset($record->report)) {
            $record->report = json_decode($record->report);
        }
        return response()->json($record);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $deleted = DB::table('quiz_attempts')->where('id', $id)->delete();
        if (!$deleted) {
            return response()->json(['message' => 'Not found or not deleted'], 404);
        }
        return response()->json(['message' => 'Quiz attempt deleted.']);
    }
}
