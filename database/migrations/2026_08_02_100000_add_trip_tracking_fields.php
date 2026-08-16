<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->foreignId('route_id')->nullable()->after('vehicle_id')->constrained()->nullOnDelete();
            $table->string('start_city')->nullable()->after('start_location');
            $table->string('end_city')->nullable()->after('destination');
            $table->decimal('start_lat', 10, 7)->nullable()->after('end_city');
            $table->decimal('start_lng', 10, 7)->nullable();
            $table->decimal('end_lat', 10, 7)->nullable();
            $table->decimal('end_lng', 10, 7)->nullable();
            $table->timestamp('paused_at')->nullable()->after('ended_at');
            $table->integer('total_paused_seconds')->default(0)->after('paused_at');
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('route_id');
            $table->dropColumn([
                'start_city', 'end_city', 'start_lat', 'start_lng',
                'end_lat', 'end_lng', 'paused_at', 'total_paused_seconds',
            ]);
        });
    }
};
