<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('school_id');
            $table->ulid('recipient_id');
            $table->ulid('message_id')->nullable(); // nullable — manual SMS may not be cascade-triggered
            $table->string('phone_number', 20);
            $table->string('notify_message_id', 100)->nullable(); // GOV.UK Notify delivery ID
            $table->string('status', 30)->default('queued'); // queued, delivered, failed, provider_error
            $table->unsignedSmallInteger('segments')->default(1);
            $table->unsignedInteger('cost_pence')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('recipient_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('message_id')->references('id')->on('messages')->nullOnDelete();

            $table->index(['school_id', 'sent_at'], 'idx_sms_school_sent');
            $table->index('notify_message_id', 'idx_sms_notify_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
