<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NOTE: This migration requires the pgvector extension.
     * Run `CREATE EXTENSION IF NOT EXISTS vector;` before migrating (handled in migration 001).
     */
    public function up(): void
    {
        $isPostgres = \Illuminate\Support\Facades\DB::getDriverName() === 'pgsql';

        Schema::create('document_chunks', function (\Illuminate\Database\Schema\Blueprint $table) use ($isPostgres): void {
            $table->ulid('id')->primary();
            $table->ulid('school_id');
            $table->ulid('document_id');
            $table->unsignedInteger('chunk_index');
            $table->text('content');

            if ($isPostgres) {
                $table->vector('embedding', 768); // nomic-embed-text output dimension
            } else {
                $table->text('embedding')->nullable(); // SQLite test fallback
            }

            $table->timestamp('created_at')->useCurrent();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('document_id')->references('id')->on('documents')->cascadeOnDelete();

            $table->index(['document_id'], 'idx_chunk_document');
        });

        // IVFFlat approximate nearest neighbour index for cosine similarity (PostgreSQL only)
        if ($isPostgres) {
            \Illuminate\Support\Facades\DB::statement(
                'CREATE INDEX idx_embedding ON document_chunks USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};
