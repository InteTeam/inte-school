<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_document_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('type'); // privacy_policy, terms_conditions
            $table->string('name'); // e.g. "UK School Privacy Policy Template v1"
            $table->text('content'); // rich text HTML — default starting point for schools
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('type', 'idx_legal_templates_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_document_templates');
    }
};
