<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $badges = [
            ['slug' => 'route_explorer', 'name' => 'Route Explorer', 'description' => 'Complete a featured route challenge.', 'icon' => '🗺️', 'category' => 'routes'],
            ['slug' => 'road_guardian', 'name' => 'Road Guardian', 'description' => 'Help other drivers with hazard reports.', 'icon' => '🚨', 'category' => 'community'],
            ['slug' => 'safe_driver', 'name' => 'Safe Driver', 'description' => 'Complete safe, high-score trips.', 'icon' => '🛡️', 'category' => 'safety'],
            ['slug' => 'weekend_warrior', 'name' => 'Weekend Warrior', 'description' => 'Complete weekend driving challenges.', 'icon' => '🌙', 'category' => 'time'],
            ['slug' => 'long_hauler', 'name' => 'Long Hauler', 'description' => 'Cover serious distance in PaceBoard.', 'icon' => '🛣️', 'category' => 'distance'],
            ['slug' => 'pace_setter', 'name' => 'Pace Setter', 'description' => 'Stay consistent and competitive.', 'icon' => '🏆', 'category' => 'ranking'],
        ];

        foreach ($badges as $badge) {
            $existing = DB::table('achievements')->where('slug', $badge['slug'])->first();
            if ($existing) {
                continue;
            }
            DB::table('achievements')->insert([
                ...$badge,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('achievements')->whereIn('slug', [
            'route_explorer',
            'road_guardian',
            'safe_driver',
            'weekend_warrior',
            'long_hauler',
            'pace_setter',
        ])->delete();
    }
};
