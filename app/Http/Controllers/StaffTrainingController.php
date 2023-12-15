<?php

namespace App\Http\Controllers;

use App\Models\StaffRecord;
use App\Models\StaffTraining;
use App\Models\TrainingProgramme;
use Illuminate\Http\Request;

class StaffTrainingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function generate(Request $request)
    {
        //

        // return $request->staff_record_id;

        $trainingProgrammes = TrainingProgramme::latest()->get();
        $stafRecordId = StaffRecord::find($request->staff_record_id)->id;

        foreach ($trainingProgrammes as $key => $trainingProgramme) {
            # code...

            StaffTraining::updateOrCreate([
                'staff_record_id' => $stafRecordId,
                'training_programme_id' => $trainingProgramme->id
            ],[
                'staff_record_id' => $stafRecordId,
                'training_programme_id' => $trainingProgramme->id
            ]);
        }

        return StaffTraining::where('staff_record_id', $stafRecordId)->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\StaffTraining  $staffTraining
     * @return \Illuminate\Http\Response
     */
    public function show(StaffTraining $staffTraining)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\StaffTraining  $staffTraining
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, StaffTraining $staffTraining)
    {
        //

        $staffTraining->update([
            'grade' => $request->grade
        ]);

        return $staffTraining;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\StaffTraining  $staffTraining
     * @return \Illuminate\Http\Response
     */
    public function destroy(StaffTraining $staffTraining)
    {
        //
    }
}
