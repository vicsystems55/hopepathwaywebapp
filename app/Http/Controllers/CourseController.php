<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Course;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Fetch all courses with outlines and quiz
        return response()->json(Course::with(['outlines', 'quiz'])->get());
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
            'feature_image' => 'nullable|string',
            'title' => 'required|string|max:255',
            'short_description' => 'nullable|string',
            'duration' => 'nullable|string',
            'category' => 'nullable|string',
            'level' => 'required|in:beginner,intermediate,advanced',
            'status' => 'required|in:active,inactive',
        ]);
        $validated['created_by'] = auth()->id() ?? 1;
        $course = Course::create($validated);
        return response()->json($course, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $course = Course::with(['outlines', 'quiz'])->findOrFail($id);
        return response()->json($course);
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
        $course = Course::findOrFail($id);
        $validated = $request->validate([
            'feature_image' => 'nullable|string',
            'title' => 'sometimes|required|string|max:255',
            'short_description' => 'nullable|string',
            'duration' => 'nullable|string',
            'category' => 'nullable|string',
            'level' => 'sometimes|required|in:beginner,intermediate,advanced',
            'status' => 'sometimes|required|in:active,inactive',
        ]);
        $course->update($validated);
        return response()->json($course);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $course = Course::findOrFail($id);
        $course->status = 'inactive';
        $course->save();
        return response()->json(['message' => 'Course deactivated.']);
    }
}
