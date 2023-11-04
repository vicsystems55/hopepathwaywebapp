<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaffRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('staff_records', function (Blueprint $table) {
            $table->id();
            $table->string('fullname');
            $table->date('date_of_birth');
            $table->string('gender');
            $table->string('address');

            // Fields to upload documents
            $table->string('passport_file')->nullable(); // For passport uploads

            // Other caregiver-specific fields
            $table->string('phone_number');
            $table->string('email')->unique();
            $table->string('qualification')->nullable();
            $table->string('experience')->nullable();
            $table->string('staff_id');
            $table->text('notes')->nullable();
            $table->string('status')->default('active');

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
        Schema::dropIfExists('staff_records');
    }
}

