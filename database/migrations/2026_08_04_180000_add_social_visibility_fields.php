<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->string('visibility', 20)->default('public')->after('share_token');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_visibility', 20)->default('public')->after('bio');
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn('visibility');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('profile_visibility');
        });
    }
};
