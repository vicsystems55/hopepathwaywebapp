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


            $regCode = "HPW" .rand(11100,999999);

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
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'portal' => 'nullable|in:admin,staff',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {

            return response()->json([
                'message' => 'Invalid login details',
            ], 401);
        }

        $user = User::with('office')->where('email', $validated['email'])->firstOrFail();

        if ($user->is_active === false) {
            Auth::logout();

            return response()->json([
                'message' => 'This account has been suspended. Contact an administrator.',
            ], 403);
        }

        if (!in_array($user->role, User::PORTAL_ROLES, true)) {
            Auth::logout();

            return response()->json([
                'message' => 'This account cannot access the staff portal.',
            ], 403);
        }

        if (!empty($validated['portal']) && $validated['portal'] !== $user->role) {
            Auth::logout();

            return response()->json([
                'message' => "These credentials do not belong to the {$validated['portal']} portal.",
            ], 403);
        }

        $permissions = $user->permissions();
        $token = $user->createToken('auth_token', $permissions)->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'user_data' => $user,
            'permissions' => $permissions,
            'token_type' => 'Bearer',
        ]);
    }

    public function adminLogin(Request $request)
    {
        $request->merge(['portal' => User::ROLE_ADMIN]);

        return $this->login($request);
    }

    public function staffLogin(Request $request)
    {
        $request->merge(['portal' => User::ROLE_STAFF]);

        return $this->login($request);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('office');

        return response()->json([
            'user_data' => $user,
            'permissions' => $user->permissions(),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);

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
