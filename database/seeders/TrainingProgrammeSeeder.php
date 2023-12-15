<?php

namespace Database\Seeders;
use Illuminate\Support\Facades\DB;

use Illuminate\Database\Seeder;

class TrainingProgrammeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //

        DB::table('training_programmes')->insertOrIgnore([
            [
                'name' => 'Duty of care',
            ],
            [
                'name' => 'Equality and Diversity',
            ],
            [
                'name' => 'Communication',
            ],
            [
                'name' => 'Work in Person Centere Way',
            ],
            [
                'name' => 'Privacy and dignity',
            ],
            [
                'name' => 'Fluids and Nutrition',
            ],
            [
                'name' => 'Mental Health Awareness',
            ],
            [
                'name' => 'Safeguarding Adults',
            ],
            [
                'name' => 'Safeguarding Children',
            ],
            [
                'name' => 'Basic Life Support',
            ],
            [
                'name' => 'Health and Safety',
            ],
            [
                'name' => 'Handling Information',
            ],
            [
                'name' => 'Infection Prevention and Control',
            ],
            [
                'name' => 'Understanding your role',
            ],
            [
                'name' => 'PMVA',
            ],



        ]);
    }
}
