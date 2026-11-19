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

    public function add_questions(Request $request){

        $question = SupervisionQuestion::create([
            'question' => $request->question,
            'option_a' => $request->option_a,
            'option_b' => $request->option_b,
            'option_d' => $request->option_d,
            'option_e' => $request->option_e,
            'question_type' => $request->question_type,
        ]);

        return $question;

    }

    public function rearrange_questions(Request $request){

        // return $request->all()

        $allQuestions = SupervisionQuestion::where('id','!=','3000')->delete();



        // SupervisionQuestion::create([$request->all()]);

        foreach ($request->all() as $key => $value) {
            # code...

            SupervisionQuestion::create([
                'id' => $key + 1,
                'question' => $value['question']
            ]);
        }

        return 1;

    }

    public function index(){


        $supervisionQuestions = SupervisionQuestion::get();


        return [

            'supervisionQuestions' => $supervisionQuestions,

        ];


    }

    public function show(Request $request, $id){

        $scheduleData = StaffSupervisionSchedule::with('staff')->findOrFail($id);

        $supervisionQuestions = SupervisionQuestion::orderBy('id')->get();

        $supervisionAnswers = $scheduleData->answers()->with('questions')
            ->orderBy('supervision_question_id')->orderBy('id')->get();

        return response()->json([
            'scheduleData' => $scheduleData,
            'supervisionQuestions' => $supervisionQuestions,
            'supervisionAnswers' => $supervisionAnswers
        ], 200, [
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);

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
                    'staff_supervision_schedule_id' => $request->staff_supervision_schedule_id


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

    public function update(Request $request, $id){
        $question = SupervisionQuestion::find($id)->update([
            'question' => $request->question,
            'option_a' => $request->option_a,
            'option_b' => $request->option_b,
            'option_d' => $request->option_d,
            'option_e' => $request->option_e,
            'question_type' => $request->question_type,
        ]);

        return $question;
    }

    public function destroy($id){
        $question = SupervisionQuestion::find($id);

        return $question->delete();
    }


}
