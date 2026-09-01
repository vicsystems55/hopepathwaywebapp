<?php

namespace App\Http\Controllers;

use App\Models\Policy;
use App\Models\StaffRecord;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\ResidentsManagement;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Mail\SubmissionNotifyAdminMail;

class ResidentsManagementController extends Controller
{
    //

    public function index(Request $request)
    {
        if ($request->dashboard) {
            # code...

            $residentsRecords = ResidentsManagement::get();
            $total_policies = Policy::get()->count();
            $total_staff = StaffRecord::get()->count();

            return compact(['residentsRecords', 'total_policies', 'total_staff']);



        }else{

            $residentsRecords = ResidentsManagement::get();

            return $residentsRecords;
        }
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|string|max:50',
            'address' => 'required|string|max:500',
            'caregiver_id' => 'required|exists:staff_records,id',
            'emergency_contact_name' => 'required|string|max:255',
            'emergency_contact_relationship' => 'required|string|max:100',
            'emergency_contact_phone' => 'required|string|max:40',
            'passport_file' => 'nullable|image|mimes:jpg,png,jpeg|max:10240',
            'government_details_file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'past_records_file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'national_insurance_number' => 'nullable|string|max:50',
            'nhs_number' => 'nullable|string|max:50',
            'medical_history' => 'nullable|string',
            'care_level' => 'nullable|string|max:100',
            'payment_information' => 'nullable|string|max:255',
            'room_assignment' => 'nullable|string|max:100',
            'dietary_restrictions' => 'nullable|string|max:500',
            'special_requests_or_notes' => 'nullable|string',
            'admission_date' => 'nullable|date',
            'allergies' => 'nullable|string|max:500',
        ]);

        $storeFile = function (string $key, string $directory) use ($request) {
            return $request->hasFile($key) ? $request->file($key)->store($directory, 'public') : null;
        };

        $residentsRecord = ResidentsManagement::create([
            'fullname' => $validated['name'],
            'date_of_birth' => $validated['date_of_birth'],
            'gender' => $validated['gender'],
            'address' => $validated['address'],
            'caregiver_id' => $validated['caregiver_id'],
            'passport_file' => $storeFile('passport_file', 'images'),
            'government_details_file' => $storeFile('government_details_file', 'government_details_file'),
            'past_records_file' => $storeFile('past_records_file', 'past_records_file'),
            'national_insurance_number' => $validated['national_insurance_number'] ?? null,
            'nhs_number' => $validated['nhs_number'] ?? null,
            'emergency_contact_name' => $validated['emergency_contact_name'],
            'emergency_contact_relationship' => $validated['emergency_contact_relationship'],
            'emergency_contact_phone' => $validated['emergency_contact_phone'],
            'medical_history' => $validated['medical_history'] ?? null,
            'care_level' => $validated['care_level'] ?? null,
            'payment_information' => $validated['payment_information'] ?? null,
            'room_assignment' => $validated['room_assignment'] ?? null,
            'dietary_restrictions' => $validated['dietary_restrictions'] ?? null,
            'special_requests_or_notes' => $validated['special_requests_or_notes'] ?? null,
            'admission_date' => $validated['admission_date'] ?? null,
            'allergies' => $validated['allergies'] ?? null,
            'resident_code' => 'HPW-R-' . strtoupper(substr(uniqid(), -6)),
        ]);

        Notification::create([
            'user_id' => $request->user()->id,
            'subject' => 'New Record',
            'msg' => 'New resident record created by, ' . $request->user()->email,
        ]);

        $datax = [
            'resident_name' => $residentsRecord->fullname
        ];


        try {
            Mail::to('testing@hopepathway.co.uk')->send(new SubmissionNotifyAdminMail($datax));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return response()->json([
            'message' => 'Resident record created successfully.',
            'data' => $residentsRecord,
        ], 201);
    }

    public function update(Request $request, $id)
    {

        // return $request->all();

        if ($request->file('passport_file')) {
            # code...
            $request->validate([
                'passport_file' => 'image|mimes:jpg,png,jpeg,gif,svg|max:50000',
            ]);
        }
        if ($request->file('governemnt_details_file')) {
            # code...
            $request->validate([
                'government_details_file' => 'image|mimes:pdf,xlsx,jpg,png,jpeg,gif,svg|max:50000',
            ]);
        }

        if ($request->file('past_records_file')) {
            # code...
            $request->validate([
                'past_records_file' => 'image|mimes:pdf,xlsx,jpg,png,jpeg,gif,svg|max:50000',
            ]);
        }


        $request->validate([
            'name' => 'required',
            'emergency_contact_name' => 'required',
            'emergency_contact_relationship' => 'required',
            'emergency_contact_phone' => 'required',

        ]);




        try {
            //code...

            $passport_file = $request->file('passport_file');

            $path = $passport_file->store('images', 'public');

            $government_details_file = $request->file('government_details_file');

            $government_details_file_path = $government_details_file->store('government_details_file', 'public');

            $past_records_file = $request->file('past_records_file');

            $past_records_file_path = $past_records_file->store('past_records_file', 'public');

        } catch (\Throwable $th) {
            //throw $th;


        }


        $residentsRecord = ResidentsManagement::find($id);


        ResidentsManagement::find($id)->update([
            "fullname" => $request->name,
            "date_of_birth" => $request->date_of_birth,
            "gender" => $request->gender,
            "address" => $request->address,
            "caregiver_id" => $request->caregiver_id,

            "passport_file" => $path ?? $residentsRecord->passport_file,
            "government_details_file" => $government_details_file_path ?? $residentsRecord->passport_file,
            "past_records_file" => $past_records_file_path ?? $residentsRecord->passport_file,

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
            'subject' => 'Record Updated',
            'msg' => 'Resident record for ' . $residentsRecord->fullname . ', updated by, ' . $request->user()->email,
        ]);

        $datax = [
            'resident_name' => $residentsRecord->fullname
        ];


        // Mail::to('testing@hopepathway.co.uk')->send(new SubmissionNotifyAdminMail($datax));
        // Mail::to('victechsystems55@gmail.com')->send(new SubmissionNotifyAdminMail($datax));



        return $residentsRecord;
    }

    public function show($id)
    {
        return response()->json([
            'data' => ResidentsManagement::findOrFail($id),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $resident = ResidentsManagement::findOrFail($id);
        $files = array_filter([
            $resident->passport_file,
            $resident->government_details_file,
            $resident->past_records_file,
        ]);

        DB::transaction(function () use ($request, $resident) {
            Notification::create([
                'user_id' => $request->user()->id,
                'subject' => 'Resident record deleted',
                'msg' => 'Resident record: ' . $resident->fullname . ' deleted by ' . $request->user()->email,
            ]);

            $resident->delete();
        });

        Storage::disk('public')->delete($files);

        return response()->json([
            'message' => 'Resident record deleted successfully.',
        ]);
    }



    private function residentUpdateWithDoc($request)
    {

        return $request->all();
    }
}
