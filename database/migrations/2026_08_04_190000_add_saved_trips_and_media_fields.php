<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'trip_id']);
        });

        Schema::table('trip_photos', function (Blueprint $table) {
            $table->string('media_type', 20)->default('image')->after('path');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('nickname')->nullable()->after('model');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_trips');
        Schema::table('trip_photos', fn (Blueprint $table) => $table->dropColumn('media_type'));
        Schema::table('vehicles', fn (Blueprint $table) => $table->dropColumn('nickname'));
    }
};
