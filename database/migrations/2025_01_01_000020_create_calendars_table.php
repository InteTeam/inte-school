<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendars', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('school_id');
            $table->string('name');
            $table->string('type');               // internal, external, department, holiday
            $table->string('department_label')->nullable();
            $table->string('color')->nullable();  // hex colour e.g. #3b82f6
            $table->boolean('is_public')->default(false); // external calendars visible to parents
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();

            $table->index(['school_id', 'created_at'], 'idx_calendar_school_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendars');
    }
};
