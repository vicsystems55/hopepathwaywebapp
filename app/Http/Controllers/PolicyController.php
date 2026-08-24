<?php

namespace App\Http\Controllers;

use App\Models\Policy;
use Illuminate\Support\Str;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\PolicyDocument;

class PolicyController extends Controller
{
    //

    public function index(){

        $policies = Policy::latest()->get();

        return $policies;

    }

    public function store(Request $request){

        // return $request->all();

        $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'nullable|string|max:100',
            'exp_date' => 'required|date',
            'text_0' => 'nullable|string|max:255',
            'file_0' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $policy = Policy::create([
            'name' => $request->name,
            'content' => $request->content,
            'type' => $request->type,
            'exp_date' => $request->exp_date,
        ]);

        $data = $request->all();

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
            $cert_path = $value->store('policies', 'public');

            PolicyDocument::create([
                'policy_id' => $policy->id,
                'title' => $all_names[$key],
                'file_path' => $cert_path??'',
            ]);

        }
        return response()->json([
            'message' => 'Policy created successfully.',
            'data' => $policy->fresh('documents'),
        ], 201);

    }


    public function show($id)
    {
        # code...


        return response()->json([
            'data' => Policy::with('documents')->findOrFail($id),
        ]);
    }

    public function destroy(Request $request, $id){

        $policy = Policy::find($id);


        Notification::create([
            'user_id' => $request->user()->id,
            'subject' => 'Policy Deleted',
            'msg' => 'Policy record:  '.$policy->name.' deleted by, ' . $request->user()->email,
        ]);



       return $policy->delete();

    }

    public function update_policy(Request $request){


        // return $request->all();

        $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'nullable|string|max:100',
            'exp_date' => 'required|date',
            'policy_id' => 'required|exists:policies,id',
            'text_0' => 'nullable|string|max:255',
            'file_0' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $policy = Policy::find($request->policy_id)->update([
            'name' => $request->name,
            'content' => $request->content,
            'type' => $request->type,
            'exp_date' => $request->exp_date,
        ]);

        $data = $request->all();

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
            $cert_path = $value->store('policies', 'public');

            PolicyDocument::create([
                'policy_id' => $request->policy_id,
                'title' => $all_names[$key],
                'file_path' => $cert_path??'',
            ]);

        }
        return response()->json([
            'message' => 'Policy review saved successfully.',
            'data' => Policy::with('documents')->findOrFail($request->policy_id),
        ]);

    }




}
