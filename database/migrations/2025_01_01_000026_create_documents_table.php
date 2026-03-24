<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('school_id');
            $table->string('name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->ulid('uploaded_by');
            $table->boolean('is_parent_facing')->default(true);
            $table->boolean('is_staff_facing')->default(true);
            $table->string('processing_status')->default('pending'); // pending, processing, indexed, failed
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('users')->restrictOnDelete();

            $table->index(['school_id', 'created_at'], 'idx_document_school_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
