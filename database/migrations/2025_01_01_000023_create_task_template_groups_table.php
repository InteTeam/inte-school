<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_template_groups', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('school_id');
            $table->string('name');
            $table->string('department_label')->nullable();
            $table->string('task_type')->default('staff'); // staff (homework doesn't use groups)
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();

            $table->index(['school_id', 'created_at'], 'idx_template_group_school_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_template_groups');
    }
};
