<?php

namespace App\Http\Controllers;

use App\Models\StaffQualification;
use App\Models\StaffRecord;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StaffRecordController extends Controller
{
    //

    public function index(){


        $staff_records = StaffRecord::with('qualifications')->get();

        return $staff_records;

    }

    public function destroy(Request $request, $id){

        $resident = StaffRecord::find($id);


        Notification::create([
            'user_id' => $request->user()->id,
            'subject' => 'Record Deleted',
            'msg' => 'Resident record:  '.$resident->fullname.' deleted by, ' . $request->user()->email,
        ]);



       return $resident->delete();

    }


    public function show($id){

        $staff_record = StaffRecord::with('qualifications')->find($id);

        return $staff_record;

    }

    public function store(Request $request){

        // return $request->all();


    // Handle form data received from the frontend
    $data = $request->all();

    $passport_file = $request->file('passport_file');

    $path = $passport_file->store('images', 'public');

    $staff_record = StaffRecord::create([
        'fullname' => $request->fullname,
        'date_of_birth' => $request->date_of_birth,
        'gender' => $request->gender,
        'address' => $request->address,
        'passport_file' => $path,
        'phone_number' => $request->phone,
        'email' => $request->email,
        'notes' => $request->notes,
        'staff_id' => 'HPW-'.rand(1000,9999),

    ]);

    // Process the data, save it to the database, or perform any other actions

    // Example: Save file uploads
    // $cert_name = null;
    // $cert_path = null;

    $all_files = [];
    $all_names = [];


    foreach ($data as $key => $value) {

        if (Str::contains($key, 'file_')) {
            # code...

            array_push($all_files, $value);
        }

        if (Str::contains($key, 'text_')) {
            # code...

            array_push($all_names, $value);
        }




            # code...

            // if ($key === 'file_'.$i) {

            //     // return $value;


            //     $file = $value;
            //     // Save or process the file here
            //     // Example: Save to a folder
            //     $cert_path = $file->store('staff_certs', 'public');
            //     // This will save the file to the 'uploads' folder in your Laravel storage


            //         $quali = StaffQualification::create([
            //             'staff_record_id' => $staff_record->id,
            //             'qualification_title' => '1',
            //             'file_path' => $cert_path??'',
            //         ]);

            //         return $quali->id;



            // }
            // if ($key === 'text_'.$i) {



            //     // Save or process the file here
            //     // Example: Save to a folder
            //     // This will save the file to the 'uploads' folder in your Laravel storage

                    // StaffQualification::find($quali->id)->update([
                    //     'qualification_title' => $value
                    // ]);



            // }


    }

    foreach ($all_files as $key => $value) {
        # code...
        $cert_path = $value->store('staff_certs', 'public');

        StaffQualification::create([
            'staff_record_id' => $staff_record->id,
            'qualification_title' => $all_names[$key],
            'file_path' => $cert_path??'',
        ]);

    }

    return $all_names;


    }
}
