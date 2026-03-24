<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardian_student', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('school_id', 26);
            $table->string('guardian_id', 26);
            $table->string('student_id', 26);
            $table->boolean('is_primary')->default(true);
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('guardian_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['school_id', 'guardian_id', 'student_id'], 'uq_guardian_student');
            $table->index(['school_id', 'student_id'], 'idx_guardian_student_school_student');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_student');
    }
};
