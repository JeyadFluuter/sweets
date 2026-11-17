<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('day_of_week'); // 0=Sunday .. 6=Saturday
            $table->time('open_at')->nullable();
            $table->time('close_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->timestamps();
            $table->unique('day_of_week');
        });

        Schema::create('closed_dates', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_slots', function (Blueprint $table) {
            $table->id();
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('max_orders')->default(10);
            $table->string('type')->default('both'); // pickup|delivery|both
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_slots');
        Schema::dropIfExists('closed_dates');
        Schema::dropIfExists('business_hours');
    }
};
