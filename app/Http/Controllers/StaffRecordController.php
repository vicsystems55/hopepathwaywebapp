<?php

namespace App\Http\Controllers;

use App\Models\StaffQualification;
use App\Models\StaffRecord;
use Illuminate\Http\Request;

class StaffRecordController extends Controller
{
    //

    public function index(){


        $staff_records = StaffRecord::with('qualifications')->get();

        return $staff_records;

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
    foreach ($data as $key => $value) {

        $cert_name = null;
        $cert_path = null;

        for ($i=0; $i < 4 ; $i++) {
            # code...

            if ($key === 'file_'.$i) {


                $file = $value;
                // Save or process the file here
                // Example: Save to a folder
                $cert_path = $file->store('staff_certs', 'public');
                // This will save the file to the 'uploads' folder in your Laravel storage
            }
            if ($key === 'text_'.$i) {
                $cert_name = $value;
                // Save or process the file here
                // Example: Save to a folder
                // This will save the file to the 'uploads' folder in your Laravel storage
            }

            if ($cert_path != null && $cert_name != null) {
                # code...
                StaffQualification::create([
                    'staff_record_id' => $staff_record->id,
                    'qualification_title' => $cert_name,
                    'file_path' => $cert_path??'',
                ]);
            }
        }





    }


    }
}
