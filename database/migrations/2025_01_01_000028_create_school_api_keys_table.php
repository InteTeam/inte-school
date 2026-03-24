<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_api_keys', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('school_id');
            $table->string('name');
            $table->string('key_hash')->unique(); // sha256 of raw key — shown once
            $table->jsonb('permissions')->default('[]'); // e.g. ["attendance","messages","homework"]
            $table->ulid('created_by');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();

            $table->index(['school_id', 'created_at'], 'idx_api_key_school_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_api_keys');
    }
};
