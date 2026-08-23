<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserIdToStaffRecordsTable extends Migration
{
    public function up()
    {
        // Some deployed databases received this column before the migration
        // history was synchronized. Treat the existing column as completed.
        if (Schema::hasColumn('staff_records', 'user_id')) {
            return;
        }

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
        if (!Schema::hasColumn('staff_records', 'user_id')) {
            return;
        }

        Schema::table('staff_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
}
