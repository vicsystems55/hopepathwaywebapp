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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StaffRecordController extends Controller
{
    //

    public function index(Request $request)
    {

        if ($request->trainings) {
            # code...
            $staff_records = StaffRecord::with('staff_trainings')->latest()->get();

            return $staff_records;
        }


        $staff_records = StaffRecord::latest()->with(['qualifications', 'supervision_schedule'])->get();

        return $staff_records;
    }

    public function destroy(Request $request, $id)
    {
        $staffRecord = StaffRecord::with(['user', 'qualifications', 'documents'])->findOrFail($id);
        $publicFiles = array_filter(array_merge(
            [$staffRecord->passport_file, $staffRecord->dbs_path],
            $staffRecord->qualifications->pluck('file_path')->filter()->all()
        ));
        $privateFiles = $staffRecord->documents->pluck('file_path')->filter()->all();

        DB::transaction(function () use ($request, $staffRecord) {
            $scheduleIds = DB::table('staff_supervision_schedules')
                ->where('staff_record_id', $staffRecord->id)
                ->pluck('id');

            if ($scheduleIds->isNotEmpty()) {
                DB::table('supervision_answers')
                    ->whereIn('staff_supervision_schedule_id', $scheduleIds)
                    ->delete();
            }

            DB::table('staff_supervision_schedules')->where('staff_record_id', $staffRecord->id)->delete();
            DB::table('staff_dbs_renewals')->where('staff_record_id', $staffRecord->id)->delete();
            DB::table('staff_trainings')->where('staff_record_id', $staffRecord->id)->delete();
            DB::table('staff_qualifications')->where('staff_record_id', $staffRecord->id)->delete();
            DB::table('staff_documents')->where('staff_record_id', $staffRecord->id)->delete();
            DB::table('residents_management')->where('caregiver_id', $staffRecord->id)->update(['caregiver_id' => null]);

            if ($staffRecord->user) {
                $staffRecord->user->tokens()->delete();
                $staffRecord->user->update(['is_active' => false]);
            }

            Notification::create([
                'user_id' => $request->user()->id,
                'subject' => 'Staff record deleted',
                'msg' => 'Staff record: ' . $staffRecord->fullname . ' deleted by ' . $request->user()->email,
            ]);

            $staffRecord->delete();
        });

        Storage::disk('public')->delete($publicFiles);
        Storage::disk('local')->delete($privateFiles);

        return response()->json([
            'message' => 'Staff record deleted successfully. Any linked portal login has been disabled.',
        ]);
    }


    public function show($id)
    {
        $staffRecord = StaffRecord::with([
            'user:id,name,email,is_active,must_change_password',
            'qualifications',
            'documents.uploader:id,name',
            'supervision_schedule' => function ($query) {
                $query->orderByDesc('next_supervision_date');
            },
            'staff_trainings.trainingProgramme',
        ])->findOrFail($id);

        return response()->json(['data' => $staffRecord]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|string|max:50',
            'address' => 'required|string|max:500',
            'phone' => 'required|string|max:40',
            'email' => 'required|email|max:255|unique:staff_records,email',
            'dbs_date' => 'nullable|date',
            'last_supervision_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'passport_file' => 'nullable|image|mimes:jpg,jpeg,png|max:10240',
            'dbs_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'qualification_titles' => 'nullable|array|max:10',
            'qualification_titles.*' => 'required|string|max:255',
            'qualification_files' => 'nullable|array|max:10',
            'qualification_files.*' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ]);

        $staffRecord = DB::transaction(function () use ($request, $validated) {
            $staffRecord = StaffRecord::create([
                'fullname' => $validated['fullname'],
                'date_of_birth' => $validated['date_of_birth'],
                'gender' => $validated['gender'],
                'address' => $validated['address'],
                'passport_file' => $request->hasFile('passport_file')
                    ? $request->file('passport_file')->store('images', 'public')
                    : null,
                'dbs_path' => $request->hasFile('dbs_file')
                    ? $request->file('dbs_file')->store('staff_dbs', 'public')
                    : null,
                'dbs_date' => $validated['dbs_date'] ?? null,
                'last_supervision_date' => $validated['last_supervision_date'] ?? null,
                'phone_number' => $validated['phone'],
                'email' => $validated['email'],
                'notes' => $validated['notes'] ?? null,
                'staff_id' => 'HPW-' . strtoupper(substr(uniqid(), -6)),
            ]);

            $titles = $validated['qualification_titles'] ?? [];
            $files = $request->file('qualification_files', []);
            foreach ($files as $index => $file) {
                $staffRecord->qualifications()->create([
                    'qualification_title' => $titles[$index],
                    'file_path' => $file->store('staff_certs', 'public'),
                ]);
            }

            if (!empty($validated['last_supervision_date'])) {
                $nextDate = Carbon::parse($validated['last_supervision_date']);
                for ($month = 0; $month < 12; $month++) {
                    StaffSupervisionSchedule::create([
                        'staff_record_id' => $staffRecord->id,
                        'next_supervision_date' => $nextDate->copy()->addMonths($month),
                    ]);
                }
            }

            if (!empty($validated['dbs_date'])) {
                StaffDbsRenewal::create([
                    'staff_record_id' => $staffRecord->id,
                    'dbs_renewal_date' => Carbon::parse($validated['dbs_date'])->addYear(),
                ]);
            }

            return $staffRecord;
        });

        return response()->json([
            'message' => 'Staff record created successfully.',
            'data' => $staffRecord->load(['qualifications', 'supervision_schedule']),
        ], 201);
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

            $path = $staff_record->passport_file;
        }

        try {
            //code...
            $dbs_file = $request->file('dbs_file');

            $dbs_path = $dbs_file->store('staff_dbs', 'public');
        } catch (\Throwable $th) {
            //throw $th;

            $dbs_path = $staff_record->dbs_path;
        }

        $staff_record = StaffRecord::find($id)->update([
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
            // 'staff_id' => 'HPW-' . rand(1000, 9999),

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
