<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NOTE: calendar_events are ordered by `starts_at ASC` for future event queries.
     * This is a documented exception to the global `orderBy created_at DESC` rule.
     */
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('school_id');
            $table->ulid('calendar_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('all_day')->default(false);
            $table->string('location')->nullable();
            $table->jsonb('meta')->nullable();
            $table->ulid('created_by');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('calendar_id')->references('id')->on('calendars')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();

            // Optimised for time-range queries (school + ascending starts_at)
            $table->index(['school_id', 'starts_at'], 'idx_event_school_starts_at');
            $table->index(['calendar_id', 'starts_at'], 'idx_event_calendar_starts_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
