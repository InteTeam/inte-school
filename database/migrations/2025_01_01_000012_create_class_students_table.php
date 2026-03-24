<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_students', function (Blueprint $table) {
            $table->string('class_id', 26);
            $table->string('student_id', 26);
            $table->string('school_id', 26);
            $table->timestamp('enrolled_at');
            $table->timestamp('left_at')->nullable();

            $table->primary(['class_id', 'student_id']);

            $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');

            $table->index(['school_id', 'student_id'], 'idx_class_students_school_student');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_students');
    }
};
