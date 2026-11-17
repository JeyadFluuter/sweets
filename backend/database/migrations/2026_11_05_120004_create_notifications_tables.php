<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('token')->unique();
            $table->string('platform')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('notification_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('image_path')->nullable();
            $table->string('audience')->default('all');
            $table->timestamp('scheduled_at')->nullable();
            $table->string('status')->default('draft'); // draft|scheduled|sending|sent|failed
            $table->timestamps();
        });

        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->nullable();
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('channel')->default('fcm');
            $table->string('type')->nullable();
            $table->nullableMorphs('notifiable');
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('idempotency_key')->nullable()->unique();
            $table->string('status')->default('pending');
            $table->text('error')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('integration_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_settings');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('app_notifications');
        Schema::dropIfExists('notification_campaigns');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('device_tokens');
    }
};
