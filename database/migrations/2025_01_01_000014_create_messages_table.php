<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('school_id', 26);
            $table->string('sender_id', 26);
            $table->string('thread_id', 26)->nullable();   // null = root of thread
            $table->string('transaction_id', 26)->unique(); // deduplication key (ULID)
            $table->string('type');                         // announcement|attendance_alert|trip_permission|quick_reply
            $table->text('body');
            $table->boolean('requires_read_receipt')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('thread_id')->references('id')->on('messages')->onDelete('cascade');

            $table->index(['school_id', 'created_at'], 'idx_messages_school_created');
            $table->index(['school_id', 'thread_id'], 'idx_messages_school_thread');
            $table->index(['school_id', 'type'], 'idx_messages_school_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
