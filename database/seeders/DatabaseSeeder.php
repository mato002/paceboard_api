<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\AppSetting;
use App\Models\Challenge;
use App\Models\CommunityReport;
use App\Models\Leaderboard;
use App\Models\Role;
use App\Models\Route;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Administrator', 'slug' => 'admin', 'permissions' => ['*']],
            ['name' => 'Moderator', 'slug' => 'moderator', 'permissions' => ['manage_reports', 'view_users']],
            ['name' => 'Driver', 'slug' => 'driver', 'permissions' => ['drive', 'view_leaderboards']],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['slug' => $role['slug']], $role);
        }

        AppSetting::setValue('speed_limit_kmh', 80, 'integer', 'app');
        AppSetting::setValue('ranking_min_score', 60, 'integer', 'app');
        AppSetting::setValue('maintenance_mode', false, 'boolean', 'app');
        AppSetting::setValue('map_provider', 'openstreetmap', 'string', 'app');
        AppSetting::setValue('fuel_consumption_per_100km', 8.5, 'float', 'app');
        AppSetting::setValue('ranking_rules', ['primary' => 'safety', 'min_trips' => 3], 'json', 'app');

        $achievements = [
            ['slug' => 'first_trip', 'name' => 'First Trip', 'description' => 'Complete your first trip', 'icon' => '🚗', 'category' => 'milestone'],
            ['slug' => 'club_100km', 'name' => '100 KM Club', 'description' => 'Drive 100 km total', 'icon' => '🏅', 'category' => 'distance'],
            ['slug' => 'club_1000km', 'name' => '1000 KM Club', 'description' => 'Drive 1000 km total', 'icon' => '🏆', 'category' => 'distance'],
            ['slug' => 'night_driver', 'name' => 'Night Driver', 'description' => 'Complete a trip between 10 PM and 5 AM', 'icon' => '🌙', 'category' => 'time'],
            ['slug' => 'explorer', 'name' => 'Explorer', 'description' => 'Complete trips on 5 different routes', 'icon' => '🗺️', 'category' => 'routes'],
            ['slug' => 'highway_master', 'name' => 'Highway Master', 'description' => 'Complete a 50+ km trip at 60+ km/h average', 'icon' => '🛣️', 'category' => 'driving'],
            ['slug' => 'weekend_driver', 'name' => 'Weekend Driver', 'description' => 'Complete a trip on the weekend', 'icon' => '📅', 'category' => 'time'],
            ['slug' => 'monthly_champion', 'name' => 'Monthly Champion', 'description' => 'Top the monthly safety leaderboard', 'icon' => '👑', 'category' => 'ranking'],
        ];

        foreach ($achievements as $achievement) {
            Achievement::firstOrCreate(['slug' => $achievement['slug']], $achievement);
        }

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'total_distance' => 124.5,
            'driving_hours' => 12.3,
            'is_admin' => true,
            'email_verified_at' => now(),
            'privacy_accepted_at' => now(),
            'country' => 'Kenya',
            'phone' => '+254712345678',
            'phone_verified_at' => now(),
            'driver_status' => 'verified',
        ]);

        $user->roles()->attach(Role::where('slug', 'admin')->first());

        $route = Route::create([
            'name' => 'Nairobi → Westlands',
            'start_city' => 'Nairobi',
            'end_city' => 'Westlands',
            'total_trips' => 2,
            'is_popular' => false,
        ]);

        Trip::create([
            'user_id' => $user->id,
            'route_id' => $route->id,
            'name' => 'Morning commute',
            'start_location' => 'Nairobi CBD',
            'start_city' => 'Nairobi',
            'destination' => 'Westlands',
            'end_city' => 'Westlands',
            'distance' => 12.4,
            'duration_seconds' => 1800,
            'average_speed' => 45.2,
            'top_speed' => 78.0,
            'moving_time_seconds' => 1500,
            'stopped_time_seconds' => 300,
            'score' => 95,
            'started_at' => now()->startOfDay()->addHours(7),
            'ended_at' => now()->startOfDay()->addHours(7)->addMinutes(30),
        ]);

        Trip::create([
            'user_id' => $user->id,
            'route_id' => $route->id,
            'name' => 'Evening drive',
            'start_location' => 'Westlands',
            'start_city' => 'Westlands',
            'destination' => 'Karen',
            'end_city' => 'Karen',
            'distance' => 8.6,
            'duration_seconds' => 1200,
            'average_speed' => 38.5,
            'top_speed' => 65.0,
            'moving_time_seconds' => 1000,
            'stopped_time_seconds' => 200,
            'score' => 92,
            'started_at' => now()->startOfDay()->addHours(17),
            'ended_at' => now()->startOfDay()->addHours(17)->addMinutes(25),
        ]);

        $reports = [
            ['type' => 'speed_camera', 'latitude' => -1.2145, 'longitude' => 36.8942, 'road_name' => 'Thika Road', 'description' => 'Fixed speed camera near exit'],
            ['type' => 'pothole', 'latitude' => -1.2985, 'longitude' => 36.8150, 'road_name' => 'Uhuru Highway', 'description' => 'Large pothole in middle lane'],
            ['type' => 'traffic', 'latitude' => -1.2674, 'longitude' => 36.8019, 'road_name' => 'Waiyaki Way', 'description' => 'Heavy congestion'],
            ['type' => 'hazard', 'latitude' => -1.3030, 'longitude' => 36.8250, 'road_name' => 'Mombasa Road', 'description' => 'Debris on road'],
        ];

        foreach ($reports as $report) {
            CommunityReport::create([
                ...$report,
                'user_id' => $user->id,
                'is_active' => true,
                'expires_at' => now()->addDays(7),
            ]);
        }

        $periods = ['daily', 'weekly', 'monthly', 'yearly', 'all_time'];
        foreach ($periods as $period) {
            foreach (['score', 'distance', 'safety'] as $category) {
                Leaderboard::create([
                    'user_id' => $user->id,
                    'category' => $category,
                    'period' => $period,
                    'rank_position' => 1,
                    'score_value' => $category === 'distance' ? 124.5 : 95,
                ]);
            }
        }

        Challenge::create([
            'title' => 'Drive 100 KM',
            'description' => 'Complete 100 km of driving this month',
            'type' => 'distance',
            'target_value' => 100,
            'reward_points' => 500,
            'starts_at' => now()->startOfMonth(),
            'ends_at' => now()->endOfMonth(),
        ]);

        Challenge::create([
            'title' => 'Weekend Challenge',
            'description' => 'Complete 3 weekend trips',
            'type' => 'weekend',
            'target_value' => 3,
            'reward_points' => 200,
            'starts_at' => now()->startOfMonth(),
            'ends_at' => now()->endOfMonth(),
        ]);

        Challenge::create([
            'title' => 'Drive Nairobi → Nakuru',
            'description' => 'Complete the Nairobi to Nakuru route',
            'type' => 'route',
            'target_value' => 1,
            'reward_points' => 300,
            'starts_at' => now(),
            'ends_at' => now()->addMonths(3),
        ]);

        $this->call(DriversAndRoutesSeeder::class);
    }
}
