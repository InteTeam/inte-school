<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('school_id');
            $table->string('type');                // staff_task, homework, action_item
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('todo'); // todo, in_progress, done, cancelled
            $table->string('priority')->nullable();    // low, medium, high, urgent
            $table->ulid('assignee_id')->nullable();
            $table->ulid('assigned_by_id')->nullable();
            $table->string('department_label')->nullable();
            $table->ulid('class_id')->nullable();             // for homework
            $table->timestamp('due_at')->nullable();
            $table->ulid('source_message_id')->nullable();    // action items from messaging
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('assignee_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('assigned_by_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('class_id')->references('id')->on('classes')->nullOnDelete();
            $table->foreign('source_message_id')->references('id')->on('messages')->nullOnDelete();

            $table->index(['school_id', 'created_at'], 'idx_task_school_created');
            $table->index(['school_id', 'assignee_id'], 'idx_task_school_assignee');
            $table->index(['school_id', 'status'], 'idx_task_school_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
