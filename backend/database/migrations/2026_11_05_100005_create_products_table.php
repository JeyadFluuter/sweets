<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->decimal('discount_price', 12, 2)->nullable();
            $table->timestamp('discount_starts_at')->nullable();
            $table->timestamp('discount_ends_at')->nullable();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_available')->default(true);
            $table->unsignedInteger('lead_time_hours')->default(0);
            $table->unsignedInteger('daily_limit')->nullable();
            $table->unsignedInteger('serves_min')->nullable();
            $table->unsignedInteger('serves_max')->nullable();
            $table->text('ingredients')->nullable();
            $table->text('allergen_alerts')->nullable();
            $table->boolean('ai_suggestable')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('category_id');
            $table->index(['is_available', 'sort_order']);
            $table->index(['discount_starts_at', 'discount_ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
