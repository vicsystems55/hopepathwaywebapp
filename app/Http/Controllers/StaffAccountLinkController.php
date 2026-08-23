<?php

namespace App\Http\Controllers;

use App\Http\Requests\LinkStaffAccountRequest;
use App\Models\StaffRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class StaffAccountLinkController extends Controller
{
    public function index()
    {
        $this->ensureLinkingIsAvailable();

        return response()->json([
            'linked_staff_records' => StaffRecord::whereNotNull('user_id')
                ->with('user:id,name,email,is_active,must_change_password')
                ->orderBy('fullname')
                ->get(['id', 'user_id', 'fullname', 'staff_id', 'email']),
            'unlinked_staff_records' => StaffRecord::whereNull('user_id')
                ->orderBy('fullname')
                ->get(['id', 'fullname', 'staff_id', 'email']),
            'available_staff_accounts' => User::where('role', User::ROLE_STAFF)
                ->whereDoesntHave('staffRecord')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'is_active', 'must_change_password']),
        ]);
    }

    public function store(LinkStaffAccountRequest $request, StaffRecord $staffRecord)
    {
        $this->ensureLinkingIsAvailable();

        $userId = (int) $request->validated()['user_id'];
        $linkedRecord = StaffRecord::where('user_id', $userId)
            ->where('id', '!=', $staffRecord->id)
            ->first();

        if ($linkedRecord) {
            throw ValidationException::withMessages([
                'user_id' => ['This staff account is already linked to another staff record.'],
            ]);
        }

        if ($staffRecord->user_id && (int) $staffRecord->user_id !== $userId) {
            throw ValidationException::withMessages([
                'user_id' => ['This staff record is already linked to another account. Unlink it first.'],
            ]);
        }

        $staffRecord->update(['user_id' => $userId]);

        return response()->json([
            'message' => 'Staff account linked successfully.',
            'data' => $staffRecord->fresh('user'),
        ]);
    }

    public function destroy(Request $request, StaffRecord $staffRecord)
    {
        $this->ensureLinkingIsAvailable();

        $staffRecord->update(['user_id' => null]);

        return response()->json([
            'message' => 'Staff account unlinked successfully.',
        ]);
    }

    private function ensureLinkingIsAvailable(): void
    {
        if (!Schema::hasColumn('staff_records', 'user_id')) {
            throw new HttpResponseException(response()->json([
                'message' => 'Run the staff account linking migration before using this endpoint.',
                'code' => 'STAFF_LINKING_NOT_MIGRATED',
            ], 503));
        }
    }
}
