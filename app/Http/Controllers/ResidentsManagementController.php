<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\ResidentsManagement;
use Illuminate\Support\Facades\Mail;
use App\Mail\SubmissionNotifyAdminMail;

class ResidentsManagementController extends Controller
{
    //

    public function index()
    {
        $residentsRecords = ResidentsManagement::get();

        return $residentsRecords;
    }


    public function store(Request $request)
    {


        $request->validate([
            'name' => 'required',
            'emergency_contact_name' => 'required',
            'emergency_contact_relationship' => 'required',
            'emergency_contact_phone' => 'required',
            // 'amount' => 'required|numeric|min:99700|between:0,99.99',
            // 'number_of_accounts' => 'required|numeric|min:1|max:15',
            'passport_file' => 'image|mimes:jpg,png,jpeg,gif,svg|max:50000',
            // 'government_details_file' => 'image|mimes:pdf,xlsx,jpg,png,jpeg,gif,svg|max:50000',
            // 'past_records_file' => 'image|mimes:pdf,xlsx,jpg,png,jpeg,gif,svg|max:50000',


        ]);


            $passport_file = $request->file('passport_file');

            $path = $passport_file->store('images', 'public');

            try {
                //code...

                $government_details_file = $request->file('government_details_file');

                $government_details_file_path = $government_details_file->store('government_details_file', 'public');

                $past_records_file = $request->file('past_records_file');

                $past_records_file_path = $past_records_file->store('past_records_file', 'public');
            } catch (\Throwable $th) {
                //throw $th;


            }




        // return $path;


        // $new_name = rand().".".$doc->getClientOriginalExtension();

        // $file1 = $doc->move(public_path('featured_images'), $new_name);





        $residentsRecord = ResidentsManagement::create([
            "fullname" => $request->name,
            "date_of_birth" => $request->date_of_birth,
            "gender" => $request->gender,
            "address" => $request->address,
            "caregiver_id" => $request->caregiver_id,

            "passport_file" => $path,
            "government_details_file" => $government_details_file_path??'',
            "past_records_file" => $past_records_file_path??'',

            "national_insurance_number" => $request->national_insurance_number,
            "nhs_number" => $request->nhs_number,
            "emergency_contact_name" => $request->emergency_contact_name,
            "emergency_contact_relationship" => $request->emergency_contact_relationship,
            "emergency_contact_phone" => $request->emergency_contact_phone,
            "medical_history" => $request->medical_history,
            "care_level" => $request->care_level,
            "payment_information" => $request->payment_information,
            "room_assignment" => $request->room_assignment,
            "dietary_restrictions" => $request->dietary_restrictions,
            "special_requests_or_notes" => $request->special_requests_or_notes,
            "admission_date" => $request->admission_date,
            // "discharge_date" => $request->discharge_date,
            "allergies" => $request->allergies,
        ]);

        Notification::create([
            'user_id' => $request->user()->id,
            'subject' => 'New Record',
            'msg' => 'New resident record created by, '.$request->user()->email,
        ]);

        $datax = [
            'resident_name' => $residentsRecord->fullname
        ];


        // Mail::to('testing@hopepathway.co.uk')->send(new SubmissionNotifyAdminMail($datax));
        Mail::to('victechsystems55@gmail.com')->send(new SubmissionNotifyAdminMail($datax));



        return $residentsRecord;
    }

    public function show($id){

        $record = ResidentsManagement::find($id);
        return $record;
    }
}
