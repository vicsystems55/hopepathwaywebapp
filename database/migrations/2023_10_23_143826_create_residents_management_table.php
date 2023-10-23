<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResidentsManagementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('residents_management', function (Blueprint $table) {
            $table->id();
            $table->string('fullname');
            $table->date('date_of_birth');
            $table->string('gender');
            $table->string('address');
            $table->string('caregiver_id');

            $table->string('passport_file')->nullable();
            $table->string('government_details_file')->nullable();
            $table->string('past_records_file')->nullable();

            $table->string('national_insurance_number')->nullable();
            $table->string('nhs_number')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->text('medical_history')->nullable();
            $table->string('care_level')->nullable();
            $table->string('payment_information')->nullable();
            $table->string('room_assignment')->nullable();
            $table->string('dietary_restrictions')->nullable();
            $table->text('special_requests_or_notes')->nullable();
            $table->date('admission_date')->nullable();
            $table->date('discharge_date')->nullable();
            $table->string('status')->default('active');

            // Allergy fields
            $table->string('allergies')->nullable();

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
        Schema::dropIfExists('residents_management');
    }
}
