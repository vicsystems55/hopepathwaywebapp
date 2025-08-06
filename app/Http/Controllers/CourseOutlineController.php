<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\CourseOutline;
use App\Models\Course;

class CourseOutlineController extends Controller
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
        $query = CourseOutline::query();
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
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]);
        $outline = CourseOutline::create($validated);
        return response()->json($outline, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $outline = CourseOutline::findOrFail($id);
        return response()->json($outline);
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
        $outline = CourseOutline::findOrFail($id);
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'body' => 'sometimes|required|string',
        ]);
        $outline->update($validated);
        return response()->json($outline);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $outline = CourseOutline::findOrFail($id);
        $outline->delete();
        return response()->json(['message' => 'Course outline deleted.']);
    }
}
