<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('custom_domain')->unique()->nullable();
            $table->string('logo_path')->nullable();
            $table->jsonb('theme_config')->default('{}');
            $table->jsonb('settings')->default('{}');
            $table->jsonb('notification_settings')->default('{}');
            $table->jsonb('security_policy')->default('{}');
            $table->string('plan')->default('standard'); // starter/standard/pro/enterprise
            $table->boolean('rag_enabled')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['slug'], 'idx_schools_slug');
            $table->index(['is_active', 'created_at'], 'idx_schools_active_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
