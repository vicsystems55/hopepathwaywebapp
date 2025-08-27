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

            'question' => 'required|string',
            'options' => 'required|array|min:2',
            // 'correct_answer' => 'required|string',
            // 'mark' => 'required|integer|min:1',
        ]);


        $quiz = Quiz::updateOrCreate([
            'course_id' => $validated['course_id']
        ],[
            'course_id' => $validated['course_id']
        ]);


        $validated['options'] = json_encode($validated['options']);
        $question = QuizQuestion::create([
            "quiz_id" => $quiz->id,
            "question" => $validated['question'],
            "options" => $validated['options'],
            "correct_answer" => $request->correct,
            "mark" => 12,
        ]);
        $question->options = json_decode($question->options); // return as array
        return response()->json($question, 201);

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


        $question = QuizQuestion::findOrFail($id);
        $validated = $request->validate([
            'question' => 'sometimes|required|string',
            'options' => 'sometimes|required|array|min:2',
            'correct' => 'sometimes|required|string',
            'mark' => 'sometimes|required|integer|min:1',
        ]);
        if (isset($validated['options'])) {
            $validated['options'] = json_encode($validated['options']);
        }
        $question->update($validated);
        $question->options = json_decode($question->options);
        return response()->json($question);
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
