<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_leaderboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->decimal('score_value', 12, 2);
            $table->integer('rank_position')->nullable();
            $table->timestamps();
            $table->unique(['route_id', 'user_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_leaderboards');
    }
};
