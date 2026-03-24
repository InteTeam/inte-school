<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('school_id');
            $table->ulid('task_id');
            $table->ulid('template_id')->nullable();
            $table->ulid('group_id')->nullable();
            $table->string('title');
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_custom')->default(true); // false = created from template
            $table->integer('sort_order')->default(0);
            $table->timestamp('deadline_at')->nullable();
            $table->integer('default_deadline_hours')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
            $table->foreign('template_id')->references('id')->on('task_templates')->nullOnDelete();
            $table->foreign('group_id')->references('id')->on('task_template_groups')->nullOnDelete();

            $table->index(['task_id'], 'idx_item_task');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_items');
    }
};
