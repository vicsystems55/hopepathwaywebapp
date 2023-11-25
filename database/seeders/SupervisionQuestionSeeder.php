<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupervisionQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //

        DB::table('supervision_questions')->insertOrIgnore([

            [
                'id' => 1,
                'question' => '
                What is the aim/purpose of this meeting/ what would you like to discuss?
                ',

            ],

            [
                'id' => 2,
                'question' => '
                If this is a follow-up session, are there issues outstanding from the last meeting? (Ensure the previous record/s of supervision are available.)
                ',

            ],


            [
                'id' => 3,
                'question' => '
                Identify new goals or objectives since the last meeting
                ',

            ],

            [
                'id' => 4,
                'question' => '
                Urgent/priority issues to be resolved
                ',

            ],

            [
                'id' => 5,
                'question' => '
                Other problem areas identified
                ',

            ],

            [
                'id' => 6,
                'question' => '
                Matters affecting Aims & Objectives
                ',

            ],


            [
                'id' => 7,
                'question' => '
                Policy amendment/update requirements
                ',

            ],


            [
                'id' => 8,
                'question' => '
                Staff feedback on the service provided
                ',

            ],

            [
                'id' => 9,
                'question' => '
                Handling/assessing service users
                ',

            ],

            [
                'id' => 10,
                'question' => '
                Service user information, issues or concerns
                ',

            ],

            [
                'id' => 11,
                'question' => '
                Monitoring
                ',

            ],


            [
                'id' => 12,
                'question' => '
                HSC Qualifications & objectives
                ',

            ],

            [
                'id' => 13,
                'question' => '
                Hours worked/workload issues
                ',

            ],

            [
                'id' => 14,
                'question' => '
                Conduct
                ',

            ],

            [
                'id' => 15,
                'question' => '
                Punctuality
                ',

            ],

            [
                'id' => 16,
                'question' => '
                Reliability
                ',

            ],

            [
                'id' => 17,
                'question' => '
                Spot audits of service delivery
                ',

            ],

            [
                'id' => 18,
                'question' => '
                Are there any areas of your role or current identified tasks that you are not clear about?
                ',

            ],

            [
                'id' => 19,
                'question' => '
                What, if any, challenges have arisen since the last supervision and what actions have you taken to overcome these?
                ',

            ],

            [
                'id' => 20,
                'question' => '
                If challenges remain, what support is needed to help overcome this and move forward?
                ',

            ],

            [
                'id' => 21,
                'question' => '
                Service User Concerns
                ',

            ],

            [
                'id' => 22,
                'question' => '
                Service User Concerns
                ',

            ],

            [
                'id' => 23,
                'question' => '
                Full-time/Part-time
                ',

            ],

            [
                'id' => 24,
                'question' => '
                Contracted Hours per week:
                ',

            ],

            [
                'id' => 25,
                'question' => '
                Job Position
                ',

            ],


            [
                'id' => 26,
                'question' => '
                Supervisor’s name
                ',

            ],

            [
                'id' => 27,
                'question' => '
                Supervisor’s name
                ',

            ],

            [
                'id' => 28,
                'question' => '
                Issues discussed
                ',

            ],

            [
                'id' => 29,
                'question' => '
                Action to be taken
                ',

            ],

            [
                'id' => 30,
                'question' => '
               By whom and when
                ',

            ],



        ]);
    }
}
