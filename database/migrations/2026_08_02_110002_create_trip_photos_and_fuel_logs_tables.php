<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('caption')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });

        Schema::create('fuel_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('liters', 8, 2);
            $table->decimal('cost', 10, 2)->nullable();
            $table->decimal('odometer_km', 10, 2)->nullable();
            $table->string('fuel_type')->default('petrol');
            $table->timestamp('filled_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('trips', function (Blueprint $table) {
            $table->decimal('fuel_estimate_liters', 8, 2)->nullable()->after('score');
            $table->json('weather')->nullable()->after('fuel_estimate_liters');
            $table->string('share_token', 64)->nullable()->unique()->after('weather');
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn(['fuel_estimate_liters', 'weather', 'share_token']);
        });

        Schema::dropIfExists('fuel_logs');
        Schema::dropIfExists('trip_photos');
    }
};
