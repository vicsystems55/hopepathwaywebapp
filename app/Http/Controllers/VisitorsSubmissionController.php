<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Office;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Mail\SubmissionNotifyMail;
use App\Models\VisitorsSubmission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\SubmissionNotifyAdminMail;
use App\Http\Requests\StoreVisitorsSubmissionRequest;
use App\Http\Requests\UpdateVisitorsSubmissionRequest;
use App\Models\SubmissionStatus;

class VisitorsSubmissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //

        $submissions = VisitorsSubmission::with('office')->latest()->get();

        return $submissions;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreVisitorsSubmissionRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //



        if ($request->dispatch) {
            # code...




            $request->all();
            $submission = SubmissionStatus::updateOrCreate([
                'visitors_submission_id' => $request->visitors_submission_id,
                'office_id' => $request->office_id,
            ],[
                'visitors_submission_id' => $request->visitors_submission_id,
                'office_id' => $request->office_id,
                'remark' => $request->remark,
                'order' => 1
            ]);

            $visitor_submission = VisitorsSubmission::with('visitor')->find($request->visitors_submission_id);

            Notification::create([
                'user_id' => $visitor_submission->visitor->id,
                'office_id' => $request->office_id,
                'subject' => 'New Submission',
                'msg' => 'Your submission has been escalated to the next office',
            ]);
            $office = Office::with('officer')->find($request->office_id);

            Notification::create([
                'user_id' => $office->officer->id,
                'office_id' => $request->office_id,
                'subject' => 'New Submission Alert',
                'msg' => 'A new submission has been received',
            ]);


        }else{

        $user = User::where('email', $request->email)->first();

        if ($user) {
            # code...

            // $doc = $request->file('file');

            // $new_name = rand().".".$doc->getClientOriginalExtension();

            // $doc->move(public_path('avatars'), $new_name);

            // $avatar = User::find($request->user()->id)->update([
            //     'avatar' => config('app.url').'avatars/'.$new_name
            // ]);
            $submission = VisitorsSubmission::create([
                'user_id' => $user->id,
                'title' => $request->title,
                'from_address' => $request->from_address,
                'phone' => $request->phone,
                'comments' => $request->comments,
                'tracking_code' => ('FMF-DTS-2023-'.rand(1000,9999)),

                'office_id' => $request->office_id,
                'submission_format' => $request->submission_format,
                'submission_date' => $request->submission_date,
                'uploada_url' =>'/doc.png',
            ]);


        }else{
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->phone),
                'role' => 'visistor',
            ]);

            $submission = VisitorsSubmission::create([
                'user_id' => $user->id,
                'title' => $request->title,
                'from_address' => $request->from_address,
                'phone' => $request->phone,
                'comments' => $request->comments,
                'tracking_id' => rand('FMF-DTS-2023-'.rand(1000,9999)),

                'office_id' => $request->office_id,
                'submission_format' => $request->submission_format,
                'submission_date' => $request->submission_date,
                'uploada_url' =>'/doc.png',
            ]);
        }



        $office = Office::find($submission->office_id);

        SubmissionStatus::create([
            'visitors_submission_id' => $submission->id,
            'office_id' => $submission->office_id,
            'order' => 1
        ]);



        Notification::create([
            'user_id' => $user->id,
            'office_id' => $submission->office_id,
            'subject' => 'New Submission',
            'msg' => 'Your submission has been successfully sent',
        ]);

        Notification::create([
            'user_id' => $office->user_id,
            'office_id' => $submission->office_id,
            'subject' => 'New Submission Alert',
            'msg' => 'A new submission has been received',
        ]);

        $datax =[
            'tracking_id' => $submission->tracking_code,
            'name' => $user->name,
            'officer_name' => $office->officer->name
        ];

        Mail::to($user->email)->send(new SubmissionNotifyMail($datax));

        Mail::to($office->officer->email)->send(new SubmissionNotifyAdminMail($datax));
        }





        return $submission;




    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\VisitorsSubmission  $visitorsSubmission
     * @return \Illuminate\Http\Response
     */
    public function show(VisitorsSubmission $visitorsSubmission)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateVisitorsSubmissionRequest  $request
     * @param  \App\Models\VisitorsSubmission  $visitorsSubmission
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateVisitorsSubmissionRequest $request, VisitorsSubmission $visitorsSubmission)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\VisitorsSubmission  $visitorsSubmission
     * @return \Illuminate\Http\Response
     */
    public function destroy(VisitorsSubmission $visitorsSubmission)
    {
        //
    }
}
