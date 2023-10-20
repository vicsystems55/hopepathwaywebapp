<?php

namespace App\Http\Controllers;

use App\Models\ApprovalStage;
use App\Http\Requests\StoreApprovalStageRequest;
use App\Http\Requests\UpdateApprovalStageRequest;

class ApprovalStageController extends Controller
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
     * @param  \App\Http\Requests\StoreApprovalStageRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreApprovalStageRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ApprovalStage  $approvalStage
     * @return \Illuminate\Http\Response
     */
    public function show(ApprovalStage $approvalStage)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateApprovalStageRequest  $request
     * @param  \App\Models\ApprovalStage  $approvalStage
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateApprovalStageRequest $request, ApprovalStage $approvalStage)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ApprovalStage  $approvalStage
     * @return \Illuminate\Http\Response
     */
    public function destroy(ApprovalStage $approvalStage)
    {
        //
    }
}
