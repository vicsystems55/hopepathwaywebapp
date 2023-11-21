<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaffSupervisionSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('staff_supervision_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_record_id');
            $table->string('next_supervision_date');
            $table->string('status')->default('active');
            $table->boolean('staff_reminder')->default(false);
            $table->boolean('admin_reminder')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('staff_supervision_schedules');
    }
}
