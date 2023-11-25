<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\StaffRecord;
use Illuminate\Support\Str;
use App\Models\Notification;
use App\Models\StaffDbsRenewal;
use Illuminate\Http\Request;
use App\Models\StaffQualification;
use App\Models\StaffSupervisionSchedule;

class StaffRecordController extends Controller
{
    //

    public function index()
    {


        $staff_records = StaffRecord::latest()->with(['qualifications', 'supervision_schedule'])->get();

        return $staff_records;
    }

    public function destroy(Request $request, $id)
    {

        $resident = StaffRecord::find($id);


        Notification::create([
            'user_id' => $request->user()->id,
            'subject' => 'Record Deleted',
            'msg' => 'Resident record:  ' . $resident->fullname . ' deleted by, ' . $request->user()->email,
        ]);



        return $resident->delete();
    }


    public function show($id)
    {

        $staff_record = StaffRecord::with('supervision_schedule')->with('qualifications')->find($id);

        return $staff_record;
    }

    public function store(Request $request)
    {

        // return $request->all();


        // Handle form data received from the frontend
        $data = $request->all();

        $passport_file = $request->file('passport_file');

        $path = $passport_file->store('images', 'public');

        $dbs_file = $request->file('dbs_file');

        $dbs_path = $dbs_file->store('staff_dbs', 'public');

        $staff_record = StaffRecord::create([
            'fullname' => $request->fullname,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'address' => $request->address,
            'passport_file' => $path,
            'dbs_path' => $dbs_path,
            'dbs_date' => $request->dbs_date,
            'last_supervision_date' => $request->last_supervision_date,
            'phone_number' => $request->phone,
            'email' => $request->email,
            'notes' => $request->notes,
            'staff_id' => 'HPW-' . rand(1000, 9999),

        ]);




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
        }

        foreach ($all_files as $key => $value) {
            # code...
            $cert_path = $value->store('staff_certs', 'public');

            StaffQualification::create([
                'staff_record_id' => $staff_record->id,
                'qualification_title' => $all_names[$key],
                'file_path' => $cert_path ?? '',
            ]);
        }

        // create supervision schedule

        $last_supervision_date = Carbon::parse($staff_record->last_supervision_date);
        for ($i = 0; $i < 12; $i++) {
            # code...


            $sch = StaffSupervisionSchedule::create([
                'staff_record_id' => $staff_record->id,
                'next_supervision_date' => $last_supervision_date
            ]);

            $last_supervision_date = $last_supervision_date->addDays(30)->addHours(12)->addMinutes(30);
        }



        // create staff dbs renewal table

        StaffDbsRenewal::create([
            'staff_record_id' => $staff_record->id,
            'dbs_renewal_date' => Carbon::parse($staff_record->dbs_date)->addDays(365)
        ]);




        return $all_names;
    }

    public function updateStaff(Request $request, $id)
    {

        // return $request->all();
        $staff_record = StaffRecord::find($id);



        // Handle form data received from the frontend
        $data = $request->all();

        try {
            //code...

            $passport_file = $request->file('passport_file');

            $path = $passport_file->store('images', 'public');
        } catch (\Throwable $th) {
            //throw $th;
        }

        $dbs_file = $request->file('dbs_file');

        $dbs_path = $dbs_file->store('staff_dbs', 'public');

        $staff_record = StaffRecord::find($id)->update([
            'fullname' => $request->fullname,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'address' => $request->address,
            'passport_file' => $staff_record->passport_file,
            'dbs_path' => $dbs_path,
            'dbs_date' => $request->dbs_date,
            'last_supervision_date' => $request->last_supervision_date,
            'phone_number' => $request->phone,
            'email' => $request->email,
            'notes' => $request->notes,
            'staff_id' => 'HPW-' . rand(1000, 9999),

        ]);

        $staff_record = StaffRecord::find($id);




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
        }
        // remove all former records
        // StaffQualification::where('staff_record_id', $staff_record->id)->delete();

        foreach ($all_files as $key => $value) {
            # code...

            try {
                //code...
                $cert_path = $value->store('staff_certs', 'public');

                StaffQualification::create([
                    'staff_record_id' => $staff_record->id,
                    'qualification_title' => $all_names[$key],
                    'file_path' => $cert_path ?? '',
                ]);
            } catch (\Throwable $th) {
                //throw $th;
            }

        }

        // create supervision schedule

        // $last_supervision_date = Carbon::parse($staff_record->last_supervision_date);
        // for ($i = 0; $i < 12; $i++) {
        //     # code...


        //     $sch = StaffSupervisionSchedule::create([
        //         'staff_record_id' => $staff_record->id,
        //         'next_supervision_date' => $last_supervision_date
        //     ]);

        //     $last_supervision_date = $last_supervision_date->addDays(30)->addHours(12)->addMinutes(30);
        // }



        // create staff dbs renewal table

        // StaffDbsRenewal::create([
        //     'staff_record_id' => $staff_record->id,
        //     'dbs_renewal_date' => Carbon::parse($staff_record->dbs_date)->addDays(365)
        // ]);




        return $all_names;
    }
}
