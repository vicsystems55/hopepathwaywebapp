<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitOwnSupervisionRequest;
use App\Http\Requests\UpdateOwnStaffProfileRequest;
use App\Models\Notification;
use App\Models\StaffRecord;
use App\Models\SupervisionAnswer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Exceptions\HttpResponseException;

class StaffSelfServiceController extends Controller
{
    public function profile(Request $request)
    {
        $staffRecord = $this->staffRecord($request);

        return response()->json([
            'data' => $staffRecord->load(['qualifications', 'documents']),
        ]);
    }

    public function updateProfile(UpdateOwnStaffProfileRequest $request)
    {
        $staffRecord = $this->staffRecord($request);
        $staffRecord->update($request->validated());

        return response()->json([
            'message' => 'Profile updated successfully.',
            'data' => $staffRecord->fresh(['qualifications', 'documents']),
        ]);
    }

    public function qualifications(Request $request)
    {
        return response()->json([
            'data' => $this->staffRecord($request)->qualifications()->latest()->get(),
        ]);
    }

    public function supervisions(Request $request)
    {
        $schedules = $this->staffRecord($request)
            ->supervision_schedule()
            ->withCount('answers')
            ->orderBy('next_supervision_date')
            ->paginate(12);

        return response()->json($schedules);
    }

    public function supervision(Request $request, $schedule)
    {
        $schedule = $this->staffRecord($request)
            ->supervision_schedule()
            ->with(['answers.questions'])
            ->findOrFail($schedule);

        return response()->json([
            'data' => $schedule,
        ]);
    }

    public function submitSupervision(SubmitOwnSupervisionRequest $request, $schedule)
    {
        $staffRecord = $this->staffRecord($request);
        $schedule = $staffRecord->supervision_schedule()->findOrFail($schedule);

        DB::transaction(function () use ($request, $schedule) {
            foreach ($request->validated()['answers'] as $answer) {
                SupervisionAnswer::updateOrCreate([
                    'supervision_question_id' => $answer['supervision_question_id'],
                    'staff_supervision_schedule_id' => $schedule->id,
                ], [
                    'answer' => $answer['answer'],
                    'status' => 'active',
                ]);
            }

            $schedule->update(['status' => 'completed']);
        });

        $adminId = User::where('role', User::ROLE_ADMIN)->value('id');
        if ($adminId) {
            Notification::create([
                'user_id' => $adminId,
                'subject' => 'Supervision Completed',
                'msg' => $staffRecord->fullname . ' completed a supervision session.',
            ]);
        }

        return response()->json([
            'message' => 'Supervision submitted successfully.',
            'data' => $schedule->fresh(['answers.questions']),
        ]);
    }

    public function training(Request $request)
    {
        $training = $this->staffRecord($request)
            ->staff_trainings()
            ->with('trainingProgramme')
            ->latest()
            ->paginate(12);

        return response()->json($training);
    }

    private function staffRecord(Request $request): StaffRecord
    {
        if (!Schema::hasColumn('staff_records', 'user_id')) {
            throw new HttpResponseException(response()->json([
                'message' => 'Staff account linking has not been enabled on this database yet.',
                'code' => 'STAFF_LINKING_NOT_MIGRATED',
            ], 503));
        }

        $staffRecord = $request->user()->staffRecord;

        if (!$staffRecord) {
            throw new HttpResponseException(response()->json([
                'message' => 'This staff account has not been linked to a staff record.',
                'code' => 'STAFF_RECORD_NOT_LINKED',
            ], 409));
        }

        return $staffRecord;
    }
}
