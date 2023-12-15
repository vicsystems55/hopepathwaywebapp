<?php

namespace App\Http\Controllers;

use App\Models\TrainingProgramme;
use Illuminate\Http\Request;

class TrainingProgrammeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $trainingProgrammes = TrainingProgramme::latest()->get();
        return $trainingProgrammes;
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

        $trainingProgramme = TrainingProgramme::create([
            'name' => $request->name
        ]);

        return $trainingProgramme;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\TrainingProgramme  $trainingProgramme
     * @return \Illuminate\Http\Response
     */
    public function show(TrainingProgramme $trainingProgramme)
    {
        //

        return $trainingProgramme;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TrainingProgramme  $trainingProgramme
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TrainingProgramme $trainingProgramme)
    {
        //
        $trainingProgramme->update([
            'name' => $request->name,
        ]);

        return $trainingProgramme;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TrainingProgramme  $trainingProgramme
     * @return \Illuminate\Http\Response
     */
    public function destroy(TrainingProgramme $trainingProgramme)
    {
        //

        return TrainingProgramme::find($trainingProgramme->id)->destroy();
    }
}
