<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserIdToStaffRecordsTable extends Migration
{
    public function up()
    {
        Schema::table('staff_records', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('staff_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
}
