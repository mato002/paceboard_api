<?php

namespace Tests\Feature;

use App\Models\Leaderboard;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardPeriodTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_leaderboard_only_counts_todays_trips(): void
    {
        $user = User::factory()->create();

        Trip::create([
            'user_id' => $user->id,
            'distance' => 10,
            'score' => 80,
            'started_at' => now()->subDay(),
            'ended_at' => now()->subDay(),
            'analytics_processed_at' => now()->subDay(),
            'analytics_distance_applied' => 10,
        ]);

        Trip::create([
            'user_id' => $user->id,
            'distance' => 5,
            'score' => 90,
            'started_at' => now(),
            'ended_at' => now(),
            'analytics_processed_at' => now(),
            'analytics_distance_applied' => 5,
        ]);

        app(\App\Services\LeaderboardService::class)->updateForUser($user);

        $daily = Leaderboard::where('user_id', $user->id)
            ->where('category', 'distance')
            ->where('period', 'daily')
            ->first();

        $allTime = Leaderboard::where('user_id', $user->id)
            ->where('category', 'distance')
            ->where('period', 'all_time')
            ->first();

        $this->assertEquals(5, (float) $daily->score_value);
        $this->assertEquals(15, (float) $allTime->score_value);
    }
}
