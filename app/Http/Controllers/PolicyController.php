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



        return $policy;

    }


    public function show($id)
    {
        # code...


        $policy = Policy::with('documents')->find($id);

        return $policy;
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



        return $policy;

    }




}
