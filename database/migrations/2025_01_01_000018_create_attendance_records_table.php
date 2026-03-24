<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('school_id');
            $table->ulid('register_id');
            $table->ulid('student_id');
            $table->string('status');        // present, absent, late
            $table->ulid('marked_by');
            $table->string('marked_via')->default('manual'); // manual, nfc_card, nfc_phone, api
            $table->boolean('pre_notified')->default(false);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('register_id')->references('id')->on('attendance_registers')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('marked_by')->references('id')->on('users')->restrictOnDelete();

            $table->index(['school_id', 'student_id'], 'idx_record_school_student');
            $table->index(['register_id'], 'idx_record_register');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
