<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\QuizAttempt;

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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'quiz_id' => 'required|exists:quizzes,id',
            'score' => 'required|integer|min:0',
            'total_questions' => 'required|integer|min:1',
            'correct_answers' => 'required|integer|min:0',
            'attempted_at' => 'nullable|date',
            'report' => 'nullable|array',
        ]);
        $validated['attempted_at'] = $validated['attempted_at'] ?? now();
        if (isset($validated['report'])) {
            $validated['report'] = json_encode($validated['report']);
        }
        $id = DB::table('quiz_attempts')->insertGetId($validated);
        $record = DB::table('quiz_attempts')->where('id', $id)->first();
        if ($record && isset($record->report)) {
            $record->report = json_decode($record->report);
        }
        return response()->json($record, 201);
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
