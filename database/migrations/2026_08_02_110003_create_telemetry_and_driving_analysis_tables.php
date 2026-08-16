<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_telemetry', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('rpm')->nullable();
            $table->decimal('engine_temp', 5, 1)->nullable();
            $table->decimal('fuel_level_percent', 5, 2)->nullable();
            $table->decimal('throttle_position', 5, 2)->nullable();
            $table->string('dtc_codes')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();
        });

        Schema::create('driving_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->integer('safety_score')->default(100);
            $table->integer('eco_score')->default(100);
            $table->integer('smoothness_score')->default(100);
            $table->integer('consistency_score')->default(100);
            $table->integer('harsh_braking_count')->default(0);
            $table->integer('harsh_acceleration_count')->default(0);
            $table->integer('speeding_events')->default(0);
            $table->json('insights')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('driving_analyses');
        Schema::dropIfExists('vehicle_telemetry');
    }
};
