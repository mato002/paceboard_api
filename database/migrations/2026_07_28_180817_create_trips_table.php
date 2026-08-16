<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('start_location')->nullable();
            $table->string('destination')->nullable();
            $table->decimal('distance', 10, 2)->default(0);
            $table->integer('duration_seconds')->default(0);
            $table->decimal('average_speed', 8, 2)->default(0);
            $table->decimal('top_speed', 8, 2)->default(0);
            $table->integer('moving_time_seconds')->default(0);
            $table->integer('stopped_time_seconds')->default(0);
            $table->integer('score')->default(100);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
