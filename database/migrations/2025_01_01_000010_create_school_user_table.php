<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_user', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('school_id', 26);
            $table->string('user_id', 26);
            $table->string('role'); // admin, teacher, support, student, parent
            $table->string('department_label')->nullable();
            $table->string('invitation_token')->nullable()->unique();
            $table->timestamp('invitation_expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->string('invited_by', 26)->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('invited_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['school_id', 'user_id'], 'idx_school_user');
            $table->index(['school_id', 'role'], 'idx_school_role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_user');
    }
};
