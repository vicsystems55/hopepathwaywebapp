<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Course;

class CourseUserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Optionally filter by user_id or course_id
        $userId = request('user_id');
        $courseId = request('course_id');
        $query = DB::table('course_user');
        if ($userId) {
            $query->where('user_id', $userId);
        }
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
            'user_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'assigned_by' => 'nullable|exists:users,id',
            'assigned_at' => 'nullable|date',
        ]);
        $validated['assigned_at'] = $validated['assigned_at'] ?? now();
        $validated['completion_percentage'] = 0;
        $validated['started_at'] = null;
        $validated['completed_at'] = null;
        $validated['current_outline_id'] = null;
        $id = DB::table('course_user')->insertGetId($validated);
        $record = DB::table('course_user')->where('id', $id)->first();
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
        $record = DB::table('course_user')->where('id', $id)->first();
        if (!$record) {
            return response()->json(['message' => 'Not found'], 404);
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
            'current_outline_id' => 'nullable|exists:course_outlines,id',
            'completion_percentage' => 'nullable|numeric|min:0|max:100',
            'started_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
        ]);
        $affected = DB::table('course_user')->where('id', $id)->update($validated);
        if (!$affected) {
            return response()->json(['message' => 'Not found or not updated'], 404);
        }
        $record = DB::table('course_user')->where('id', $id)->first();
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
        $deleted = DB::table('course_user')->where('id', $id)->delete();
        if (!$deleted) {
            return response()->json(['message' => 'Not found or not deleted'], 404);
        }
        return response()->json(['message' => 'User-course assignment deleted.']);
    }
}
