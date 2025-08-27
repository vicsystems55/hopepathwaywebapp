<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CoursePerformance;
use Illuminate\Support\Facades\Auth;

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
}
