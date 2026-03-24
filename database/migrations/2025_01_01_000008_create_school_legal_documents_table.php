<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_legal_documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('school_id', 26);
            $table->string('type'); // privacy_policy, terms_conditions
            $table->text('content'); // rich text HTML, editable by school admin
            $table->string('version'); // e.g. "1.0", "1.1", "2.0"
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->string('published_by', 26)->nullable();
            $table->string('created_by', 26);
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('published_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            $table->index(['school_id', 'type'], 'idx_school_legal_school_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_legal_documents');
    }
};
