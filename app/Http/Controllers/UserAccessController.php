<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\CreateStaffLoginRequest;
use App\Http\Requests\UpdateUserStatusRequest;
use App\Models\StaffRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserAccessController extends Controller
{
    public function createStaffLogin(CreateStaffLoginRequest $request, StaffRecord $staffRecord)
    {
        if (!$staffRecord->email) {
            throw ValidationException::withMessages([
                'email' => ['Add an email address to this staff record before creating login access.'],
            ]);
        }

        $temporaryPassword = null;
        $created = false;

        $user = DB::transaction(function () use ($staffRecord, &$temporaryPassword, &$created) {
            $user = User::where('email', $staffRecord->email)->first();

            if ($user && $user->role !== User::ROLE_STAFF) {
                throw ValidationException::withMessages([
                    'email' => ['This email address already belongs to a non-staff account.'],
                ]);
            }

            if ($user) {
                $otherRecord = StaffRecord::where('user_id', $user->id)
                    ->where('id', '!=', $staffRecord->id)
                    ->first();

                if ($otherRecord) {
                    throw ValidationException::withMessages([
                        'email' => ['This login is already linked to another staff record.'],
                    ]);
                }
            } else {
                $temporaryPassword = $this->temporaryPassword();
                $created = true;
                $user = User::create([
                    'name' => $staffRecord->fullname,
                    'email' => $staffRecord->email,
                    'role' => User::ROLE_STAFF,
                    'password' => Hash::make($temporaryPassword),
                    'is_active' => true,
                    'must_change_password' => true,
                ]);
            }

            $staffRecord->update(['user_id' => $user->id]);

            return $user;
        });

        return response()->json([
            'message' => $created
                ? 'Staff login created and linked successfully.'
                : 'Existing staff login linked successfully. Its password was not changed.',
            'created' => $created,
            'temporary_password' => $temporaryPassword,
            'data' => $staffRecord->fresh('user'),
        ], $created ? 201 : 200);
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user)
    {
        $this->ensureStaffAccount($user);

        $user->update(['is_active' => $request->boolean('is_active')]);

        if (!$user->is_active) {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => $user->is_active ? 'Staff login activated.' : 'Staff login suspended.',
            'data' => $user->fresh(),
        ]);
    }

    public function resetPassword(Request $request, User $user)
    {
        $this->ensureStaffAccount($user);

        $temporaryPassword = $this->temporaryPassword();
        $user->update([
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
        ]);
        $user->tokens()->delete();

        return response()->json([
            'message' => 'A new temporary password has been generated.',
            'temporary_password' => $temporaryPassword,
        ]);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();

        if (!Hash::check($request->validated()['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->validated()['password']),
            'must_change_password' => false,
        ]);

        $currentTokenId = optional($user->currentAccessToken())->id;
        if ($currentTokenId) {
            $user->tokens()->where('id', '!=', $currentTokenId)->delete();
        }

        return response()->json([
            'message' => 'Password changed successfully.',
            'user_data' => $user->fresh(),
        ]);
    }

    private function ensureStaffAccount(User $user): void
    {
        if ($user->role !== User::ROLE_STAFF) {
            throw ValidationException::withMessages([
                'user' => ['Only staff accounts can be managed from this area.'],
            ]);
        }
    }

    private function temporaryPassword(): string
    {
        return Str::upper(Str::random(3))
            . Str::lower(Str::random(5))
            . random_int(10, 99)
            . '!Hp';
    }
}
