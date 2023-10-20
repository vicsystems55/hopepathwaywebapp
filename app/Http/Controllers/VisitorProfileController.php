<?php

namespace App\Http\Controllers;

use App\Models\VisitorProfile;
use App\Http\Requests\StoreVisitorProfileRequest;
use App\Http\Requests\UpdateVisitorProfileRequest;

class VisitorProfileController extends Controller
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreVisitorProfileRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreVisitorProfileRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\VisitorProfile  $visitorProfile
     * @return \Illuminate\Http\Response
     */
    public function show(VisitorProfile $visitorProfile)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateVisitorProfileRequest  $request
     * @param  \App\Models\VisitorProfile  $visitorProfile
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateVisitorProfileRequest $request, VisitorProfile $visitorProfile)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\VisitorProfile  $visitorProfile
     * @return \Illuminate\Http\Response
     */
    public function destroy(VisitorProfile $visitorProfile)
    {
        //
    }
}
