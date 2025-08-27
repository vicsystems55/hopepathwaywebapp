<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoursePerformancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('course_performances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('quiz_id');

            $table->integer('total_score')->default(0);
            $table->integer('expected_score')->default(0);
            $table->string('grade')->nullable();
            $table->integer('attempts')->default(1);

            $table->enum('status', ['passed', 'failed', 'in-progress'])->default('in-progress');
            $table->enum('completion_status', ['not-started', 'in-progress', 'completed'])->default('not-started');

            // Certificate fields
            $table->enum('certificate_status', ['not-issued', 'issued'])->default('not-issued');
            $table->string('certificate_path')->nullable(); // NEW FIELD

            $table->unsignedBigInteger('reviewed_by')->nullable(); // admin user_id

            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
            $table->foreign('quiz_id')->references('id')->on('quizzes')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('course_performances');
    }
}
