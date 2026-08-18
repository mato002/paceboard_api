<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_photos', function (Blueprint $table) {
            $table->dropForeign(['trip_id']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            Schema::table('trip_photos', function (Blueprint $table) {
                $table->unsignedBigInteger('trip_id')->nullable()->change();
            });
        } else {
            DB::statement('ALTER TABLE trip_photos MODIFY trip_id BIGINT UNSIGNED NULL');
        }

        Schema::table('trip_photos', function (Blueprint $table) {
            $table->foreign('trip_id')->references('id')->on('trips')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trip_photos', function (Blueprint $table) {
            $table->dropForeign(['trip_id']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            Schema::table('trip_photos', function (Blueprint $table) {
                $table->unsignedBigInteger('trip_id')->nullable(false)->change();
            });
        } else {
            DB::statement('ALTER TABLE trip_photos MODIFY trip_id BIGINT UNSIGNED NOT NULL');
        }

        Schema::table('trip_photos', function (Blueprint $table) {
            $table->foreign('trip_id')->references('id')->on('trips')->cascadeOnDelete();
        });
    }
};
