<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddResidentCodeToResidentsManagementTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('residents_management', 'resident_code')) {
            Schema::table('residents_management', function (Blueprint $table) {
                $table->string('resident_code')->nullable()->after('discharge_date');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('residents_management', 'resident_code')) {
            Schema::table('residents_management', function (Blueprint $table) {
                $table->dropColumn('resident_code');
            });
        }
    }
}
