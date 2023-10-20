<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

use App\Models\User;

use App\Mail\Welcome;

use App\Models\UserProfile;

use App\Models\Notification;

use Illuminate\Http\Request;

use App\Mail\EmailVerification;

use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ApiAuthController extends Controller
{
    //

    public function register(Request $request)
    {

            $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            ]);


            $regCode = "DTS" .rand(11100,999999);

            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'role' => 'visitor',
                'password' => Hash::make($validatedData['password']),
            ]);

            $user->update([
                'otp' => rand(111111,999999)
            ]);


        $datax = [
            'name' => $user->name,
            'email' => $user->email,
            'otp' => $user->otp??''
        ];


                    Mail::to($user->email)
                    ->send(new Welcome($datax));

        try {


            // Mail::to($user->email)
            // ->send(new EmailVerification($datax));

        } catch (\Throwable $th) {
            // throw $th;
        }


        $token = $user->createToken('auth_token')->plainTextToken;

        // $user = User::where($user->id);

        return response()->json([
                    'access_token' => $token,
                    'user_data' => $user,
                    'token_type' => 'Bearer',
        ]);




    }

    public function login(Request $request)
    {
        # code...

           if (!Auth::attempt($request->only('email', 'password'))) {

            return response()->json([
            'message' => 'Invalid login details'
                       ], 401);
        }else{

            $user = User::with('office')->where('email', $request['email'])->firstOrFail();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                       'access_token' => $token,
                       'user_data' => $user,
                       'token_type' => 'Bearer',
            ]);

        }

    }

    public function verify_otp(Request $request)
    {
        # code...

        try {
            //code...

            $user = User::where('id', $request->user()->id)->where('otp', $request->otp)->first();

            if ($user) {


                return response()->json([
                    // 'access_token' => $token,
                    'user_data' => $user,
                    'token_type' => 'Bearer',
                ]);


            }
        } catch (\Throwable $th) {
            //throw $th;

            return $th;
        }


    }

    public function resend_otp(Request $request)
    {
        # code...

        try {
            //code...

            $user = User::where('id', $request->user()->id)->first();

            if ($user) {

                $user->update([
                    'otp' => rand(111111,999999)
                ]);

                $datax = [
                    'name' => $user->name,
                    'email' => $user->email,
                    'otp' => $user->otp
                ];
                //     Mail::to($user->email)
                //     ->send(new EmailVerification($datax));


                return response()->json([
                    // 'access_token' => $token,
                    'user_data' => $user,
                    'token_type' => 'Bearer',
                ]);


            }
        } catch (\Throwable $th) {
            //throw $th;

            return $th;
        }


    }



}
