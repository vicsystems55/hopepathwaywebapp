<?php

namespace Database\Seeders;

use App\Models\StaffRecord;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class StaffLoginSeeder extends Seeder
{
    /**
     * Create a predictable sample staff login and link it to a staff record.
     */
    public function run()
    {
        if (!Schema::hasColumn('staff_records', 'user_id')) {
            throw new RuntimeException(
                'The staff account-linking migration must be run before StaffLoginSeeder.'
            );
        }

        $name = env('SAMPLE_STAFF_NAME', 'Sample Care Worker');
        $email = env('SAMPLE_STAFF_EMAIL', 'staff@hopepathway.co.uk');
        $password = env('SAMPLE_STAFF_PASSWORD', 'HopePathway123!');

        DB::transaction(function () use ($name, $email, $password) {
            $user = User::where('email', $email)->first();

            if ($user && $user->role !== User::ROLE_STAFF) {
                throw new RuntimeException(
                    "The sample email {$email} already belongs to a non-staff account."
                );
            }

            if (!$user) {
                $user = new User();
                $user->email = $email;
            }

            $user->name = $name;
            $user->role = User::ROLE_STAFF;
            $user->password = Hash::make($password);
            $user->save();

            $staffRecord = StaffRecord::where('user_id', $user->id)
                ->orWhere('email', $email)
                ->first();

            if ($staffRecord && $staffRecord->user_id && (int) $staffRecord->user_id !== (int) $user->id) {
                throw new RuntimeException(
                    "The staff record for {$email} is already linked to another user."
                );
            }

            if (!$staffRecord) {
                $staffRecord = new StaffRecord([
                    'fullname' => $name,
                    'date_of_birth' => '1990-01-01',
                    'gender' => 'Prefer not to say',
                    'address' => 'Hope Pathway sample address',
                    'phone_number' => '07000000000',
                    'email' => $email,
                    'qualification' => 'Care Certificate',
                    'experience' => 'Sample staff profile for portal testing',
                    'staff_id' => 'HPW-SAMPLE-001',
                    'notes' => 'Created by StaffLoginSeeder for development and testing.',
                    'status' => 'active',
                ]);
            }

            $staffRecord->user_id = $user->id;
            $staffRecord->save();
        });

        if ($this->command) {
            $this->command->info("Sample staff login ready: {$email}");
        }
    }
}
