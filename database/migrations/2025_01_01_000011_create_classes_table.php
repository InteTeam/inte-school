<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('school_id', 26);
            $table->string('name'); // e.g. "Year 1A", "P3"
            $table->string('year_group');
            $table->string('teacher_id', 26)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('teacher_id')->references('id')->on('users')->onDelete('set null');

            $table->index(['school_id', 'created_at'], 'idx_classes_school_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
