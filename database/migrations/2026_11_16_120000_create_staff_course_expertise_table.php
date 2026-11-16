<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaffCourseExpertiseTable extends Migration
{
    public function up()
    {
        Schema::create('staff_course_expertise', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_record_id')->constrained('staff_records')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->string('expertise', 20);
            $table->foreignId('rated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->unique(['staff_record_id', 'course_id'], 'staff_course_expertise_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('staff_course_expertise');
    }
}
