<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_registers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('school_id');
            $table->ulid('class_id');
            $table->ulid('teacher_id');
            $table->date('register_date');
            $table->string('period')->nullable(); // null = daily, or "morning", "period_1", etc.
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
            $table->foreign('teacher_id')->references('id')->on('users')->restrictOnDelete();

            $table->unique(['school_id', 'class_id', 'register_date', 'period'], 'uq_register_class_date_period');
            $table->index(['school_id', 'created_at'], 'idx_register_school_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_registers');
    }
};
