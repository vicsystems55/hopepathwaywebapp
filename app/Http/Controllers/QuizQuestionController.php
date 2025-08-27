<?php

namespace App\Http\Controllers;


use App\Models\Quiz;
use App\Models\Course;
use App\Models\QuizQuestion;
use Illuminate\Http\Request;

class QuizQuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Optionally filter by quiz_id
        $quizId = request('quiz_id');
        $query = QuizQuestion::query();
        if ($quizId) {
            $query->where('quiz_id', $quizId);
        }
        return response()->json($query->get());
    }

    public function getCourseQuizzes($courseId)
    {
        $quizz = Quiz::where('course_id', $courseId)->first();
        $quizz->load('questions');
        return response()->json([
            'quizzes' => $quizz->questions
        ]);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        return response()->json($request->all());

        $validated = $request->validate([
            'quiz_id' => 'required|exists:quizzes,id',
            'question' => 'required|string',
            'options' => 'required|array|min:2',
            'correct' => 'required|string',
            'mark' => 'integer|min:1',
        ]);
        $validated['options'] = json_encode($validated['options']);
        $question = QuizQuestion::create($validated);
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
        $question = QuizQuestion::findOrFail($id);
        $question->options = json_decode($question->options);
        return response()->json($question);
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
        $question = QuizQuestion::findOrFail($id);
        $question->delete();
        return response()->json(['message' => 'Quiz question deleted.']);
    }
}
