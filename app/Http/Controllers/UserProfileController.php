<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\Welcome;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserProfileController extends Controller
{
    //

    public function index(){
        $users = User::where('role', '!=','visitor')->latest()->get();
        return $users;
    }

    public function store(Request $request){

        $request->validate([
            'email' => 'required|unique:users'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make($request->password)
        ]);

        if ($user->role == 'visitor') {
            # code...

            $datax =[
                'name' => $user->name
            ];

            Mail::to($user->email)->send(new Welcome($datax));
        }

        return $user;

    }
}
