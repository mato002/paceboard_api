<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_reports', function (Blueprint $table) {
            $table->unsignedInteger('confirmations_count')->default(0)->after('verification_score');
            $table->unsignedInteger('dismissals_count')->default(0)->after('confirmations_count');
            $table->timestamp('last_confirmed_at')->nullable()->after('dismissals_count');
            $table->string('status', 20)->default('pending')->after('last_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('community_reports', function (Blueprint $table) {
            $table->dropColumn([
                'confirmations_count',
                'dismissals_count',
                'last_confirmed_at',
                'status',
            ]);
        });
    }
};
