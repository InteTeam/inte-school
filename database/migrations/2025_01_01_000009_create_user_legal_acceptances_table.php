<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_legal_acceptances', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('school_id', 26);
            $table->string('user_id', 26);
            $table->string('document_id', 26);
            $table->string('document_type'); // privacy_policy, terms_conditions
            $table->string('document_version'); // snapshot of version at time of acceptance
            $table->timestamp('accepted_at');
            $table->string('ip_address');
            $table->text('user_agent');
            // No updated_at — append-only audit trail
            $table->timestamp('created_at')->nullable();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('document_id')->references('id')->on('school_legal_documents')->onDelete('cascade');

            $table->index(['school_id', 'user_id'], 'idx_legal_accept_user');
            $table->index('document_id', 'idx_legal_accept_document');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_legal_acceptances');
    }
};
