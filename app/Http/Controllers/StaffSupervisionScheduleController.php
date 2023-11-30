<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\SupervisionAnswer;
use App\Models\SupervisionQuestion;
use App\Models\StaffSupervisionSchedule;

class StaffSupervisionScheduleController extends Controller
{
    //

    public function show(Request $request, $id){

        $scheduleData = StaffSupervisionSchedule::find($id);

        $supervisionQuestions = SupervisionQuestion::get();

        $supervisionAnswers = SupervisionAnswer::where('staff_supervision_schedule_id', $scheduleData->id)->get();

        // $supervisionData = StaffSupervisionSchedule::where('staff_id')->get();

        return [
            'scheduleData' => $scheduleData,
            'supervisionQuestions' => $supervisionQuestions,
            'supervisionAnswers' => $supervisionAnswers
        ];

    }

    public function store(Request $request){

        // return $request->all();

     foreach ($request->all() as $key => $data) {
        # code...

        if (Str::contains($key, 'ans')){

            $id = str_replace('ans', '', $key);
            // return $result;
            SupervisionAnswer::updateOrCreate([
                'supervision_question_id' => $id,
                'answer' => $data,

            ],[

                'supervision_question_id' => $id,
                'answer' => $data,
                'staff_supervision_schedule_id' => $request->staff_supervision_schedule_id

            ]);

        }


     }



     $scheduleData = StaffSupervisionSchedule::find($request->staff_supervision_schedule_id);

     Notification::create([
        'user_id' => 1,
        'subject' => 'Supervision Completed',
        'msg' => 'Staff supervision completed by ' . $request->user()->email,
    ]);


     return $scheduleData->update([
        'status' => 'completed'
     ]);



        // return $request->all();

        // supervision_question_id
        // answer
        // staff_supervision_schedule_id

    }


}
