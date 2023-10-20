<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //

        DB::table('users')->insertOrIgnore([

            [
                'name' => 'Permanent Secretary',
                'role' => 'perm-sec',
                'email' => 'perm-sec@amtm.gov.ng',
                'password' => Hash::make('12345670'),
                'id' => 1

            ],

            [
                'name' => 'Director Procurement',
                'role' => 'd-procurement',
                'email' => 'd-procurement@amtm.gov.ng',
                'password' => Hash::make('12345670'),
                'id' => 2

            ],
            [
                'name' => 'Director General Services',
                'role' => 'd-gen-services',
                'email' => 'd-gen-services@amtm.gov.ng',
                'password' => Hash::make('12345670'),
                'id' => 3

            ],
            [
                'name' => 'Department Head 001',
                'role' => 'dept-head',
                'email' => 'dept-head1@amtm.gov.ng',
                'password' => Hash::make('12345670'),
                'id' => 4

            ],
            [
                'name' => 'Administrator',
                'role' => 'admin',
                'email' => 'admin@amtm.gov.ng',
                'password' => Hash::make('12345670@2023'),
                'id' => 5

            ],


        ]);
    }
}
