<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('reward_points')->default(0)->after('driving_hours');
        });

        Schema::table('trips', function (Blueprint $table) {
            $table->timestamp('analytics_processed_at')->nullable()->after('ended_at');
            $table->decimal('analytics_distance_applied', 10, 2)->nullable()->after('analytics_processed_at');
            $table->unsignedInteger('analytics_moving_seconds_applied')->nullable()->after('analytics_distance_applied');
            $table->softDeletes();

            $table->index(['user_id', 'ended_at']);
            $table->index('route_id');
        });

        Schema::table('report_verifications', function (Blueprint $table) {
            $table->string('vote', 8)->default('up')->after('community_report_id');
        });

        Schema::table('leaderboards', function (Blueprint $table) {
            $table->unique(['user_id', 'category', 'period'], 'leaderboards_user_category_period_unique');
            $table->index(['category', 'period', 'score_value']);
        });

        Schema::table('community_reports', function (Blueprint $table) {
            $table->index(['is_active', 'expires_at']);
            $table->index(['latitude', 'longitude']);
        });

        Schema::table('user_notifications', function (Blueprint $table) {
            $table->index(['user_id', 'read_at']);
        });

        Schema::table('routes', function (Blueprint $table) {
            $table->unique(['start_city', 'end_city'], 'routes_cities_unique');
        });
    }

    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropUnique('routes_cities_unique');
        });

        Schema::table('user_notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'read_at']);
        });

        Schema::table('community_reports', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'expires_at']);
            $table->dropIndex(['latitude', 'longitude']);
        });

        Schema::table('leaderboards', function (Blueprint $table) {
            $table->dropUnique('leaderboards_user_category_period_unique');
            $table->dropIndex(['category', 'period', 'score_value']);
        });

        Schema::table('report_verifications', function (Blueprint $table) {
            $table->dropColumn('vote');
        });

        Schema::table('trips', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex(['user_id', 'ended_at']);
            $table->dropIndex(['route_id']);
            $table->dropColumn([
                'analytics_processed_at',
                'analytics_distance_applied',
                'analytics_moving_seconds_applied',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('reward_points');
        });
    }
};
