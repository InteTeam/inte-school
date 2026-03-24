<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_recipients', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('school_id', 26);
            $table->string('message_id', 26);
            $table->string('recipient_id', 26);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('quick_reply')->nullable(); // stores the reply option chosen
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('message_id')->references('id')->on('messages')->onDelete('cascade');
            $table->foreign('recipient_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['message_id', 'recipient_id'], 'uq_message_recipient');
            $table->index(['school_id', 'recipient_id', 'read_at'], 'idx_recipients_school_user_read');
            $table->index(['school_id', 'created_at'], 'idx_recipients_school_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_recipients');
    }
};
