<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registered_devices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('school_id', 26)->nullable(); // FK added in P1.4 when schools table exists
            $table->string('user_id', 26);
            $table->string('device_name')->nullable();
            $table->string('device_fingerprint');
            $table->jsonb('push_subscription')->nullable();
            $table->timestamp('last_seen_at');
            $table->timestamp('trusted_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->index(['user_id', 'school_id'], 'idx_registered_devices_user_device');
            $table->index(['school_id', 'created_at'], 'idx_registered_devices_school_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registered_devices');
    }
};
