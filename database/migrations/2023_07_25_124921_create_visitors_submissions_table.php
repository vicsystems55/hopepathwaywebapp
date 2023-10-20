<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVisitorsSubmissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('visitors_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('title');
            $table->string('from_address');
            $table->string('phone')->nullable();
            $table->string('comments')->nullable();
            $table->string('tracking_code');

            $table->string('ref_no')->nullable();

            $table->foreignId('office_id');
            $table->string('submission_format');
            $table->string('submission_date');
            $table->string('uploada_url')->nullable();
            $table->string('uploadb_url')->nullable();
            $table->string('uploadc_url')->nullable();
            $table->string('uploadd_url')->nullable();
            $table->string('uploade_url')->nullable();
            $table->string('uploadf_url')->nullable();

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
        Schema::dropIfExists('visitors_submissions');
    }
}
