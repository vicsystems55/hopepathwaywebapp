<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OfficeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        DB::table('offices')->insertOrIgnore([

            [
                "name" => "The Honourable Ministers Registry",
                "abbrev" => "HMR",
                "user_id" => null
            ],

            [
                "name" => "The Permanent Secretary Finance (Special Duties) Registry",
                "abbrev" => "PSFR-SD",
                "user_id" => null
            ],

            [
                "name" => "The Permanent Secretary Finance Registry",
                "abbrev" => "PSFR",
                "user_id" => null
            ],

            [
                "name" => "The Director ICT Registry",
                "abbrev" => "D-ICT",
                "user_id" => 1
            ],

            [
                "name" => "The Deputy Director ICT Registry",
                "abbrev" => "DD-ICT",
                "user_id" => 2
            ],

            [
                "name" => "The Assistant Director ICT Registry",
                "abbrev" => "AD-ICT",
                "user_id" => 3
            ],

            [
                "name" => "The CPA ICT Registry",
                "abbrev" => "CPA-ICT",
                "user_id" => 4
            ],

            [
                "name" => "The CCE ICT Registry ",
                "abbrev" => "CCE-ICT",
                "user_id" => 5
            ],







        ]);

    }
}
