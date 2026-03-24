<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('school_id');
            $table->ulid('submitted_by');
            $table->string('title', 150);
            $table->text('body'); // max 2000 chars enforced at app layer
            $table->string('status')->default('open'); // open, under_review, planned, done, declined
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('submitted_by')->references('id')->on('users')->restrictOnDelete();

            $table->index(['school_id', 'created_at'], 'idx_feature_request_school_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_requests');
    }
};
