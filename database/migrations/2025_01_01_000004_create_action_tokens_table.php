<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('action_tokens', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('school_id', 26);
            $table->string('message_id', 26)->nullable(); // FK added in P2.1 when messages table exists
            $table->string('recipient_id', 26);
            $table->string('token')->unique();
            $table->string('action_type'); // acknowledge, confirm_absence, trip_consent
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('recipient_id')->references('id')->on('users')->onDelete('cascade');

            $table->index('token', 'idx_action_tokens_token');
            $table->index(['school_id', 'created_at'], 'idx_action_tokens_school_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_tokens');
    }
};
