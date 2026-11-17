<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->string('provider')->default('openai_compatible');
            $table->string('fallback_provider')->nullable();
            $table->string('base_url')->nullable();
            $table->string('model')->nullable();
            $table->text('api_key_encrypted')->nullable();
            $table->decimal('temperature', 3, 2)->default(0.40);
            $table->unsignedInteger('max_tokens')->default(1024);
            $table->unsignedInteger('daily_message_limit')->default(30);
            $table->longText('system_prompt')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_test_status')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_prompt_versions', function (Blueprint $table) {
            $table->id();
            $table->longText('prompt');
            $table->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('role'); // user|assistant|system
            $table->longText('content');
            $table->json('suggested_items')->nullable();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
        Schema::dropIfExists('ai_prompt_versions');
        Schema::dropIfExists('ai_settings');
    }
};
