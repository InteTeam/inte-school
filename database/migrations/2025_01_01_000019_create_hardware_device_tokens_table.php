<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hardware_device_tokens', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('school_id');
            $table->string('name');
            $table->string('token_hash')->unique(); // SHA-256 of raw token, shown once
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();

            $table->index(['school_id', 'created_at'], 'idx_hw_token_school_created');
        });

        // NFC card ID → user mapping (for hardware attendance reader)
        Schema::table('users', function (Blueprint $table): void {
            $table->string('nfc_card_id')->nullable()->unique()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('nfc_card_id');
        });

        Schema::dropIfExists('hardware_device_tokens');
    }
};
