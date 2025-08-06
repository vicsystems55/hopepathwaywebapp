<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Quiz;
use App\Models\QuizQuestion;

class QuizController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Optionally filter by course_id
        $courseId = request('course_id');
        $query = Quiz::with('questions');
        if ($courseId) {
            $query->where('course_id', $courseId);
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
            'course_id' => 'required|exists:courses,id',
        ]);
        $quiz = Quiz::create($validated);
        return response()->json($quiz, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $quiz = Quiz::with('questions')->findOrFail($id);
        return response()->json($quiz);
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
        $quiz = Quiz::findOrFail($id);
        $validated = $request->validate([
            'course_id' => 'sometimes|required|exists:courses,id',
        ]);
        $quiz->update($validated);
        return response()->json($quiz);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $quiz = Quiz::findOrFail($id);
        $quiz->delete();
        return response()->json(['message' => 'Quiz deleted.']);
    }
}
