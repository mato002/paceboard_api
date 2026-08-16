<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\Leaderboard;
use App\Models\Trip;
use App\Models\User;
use App\Models\UserNotification;

class AchievementService
{
    public function checkAfterTrip(Trip $trip): array
    {
        $user = $trip->user;
        $earned = [];

        $checks = [
            'first_trip' => fn () => $user->trips()->whereNotNull('ended_at')->count() === 1,
            'club_100km' => fn () => (float) $user->total_distance >= 100,
            'club_1000km' => fn () => (float) $user->total_distance >= 1000,
            'night_driver' => fn () => $trip->started_at && (
                (int) $trip->started_at->format('H') >= 22 || (int) $trip->started_at->format('H') < 5
            ),
            'weekend_driver' => fn () => $trip->started_at && $trip->started_at->isWeekend(),
            'highway_master' => fn () => (float) $trip->distance >= 50 && (float) $trip->average_speed >= 60,
            'explorer' => fn () => $user->trips()->whereNotNull('route_id')->distinct('route_id')->count('route_id') >= 5,
        ];

        foreach ($checks as $slug => $condition) {
            if ($condition() && $this->award($user, $slug)) {
                $earned[] = $slug;
            }
        }

        $this->checkMonthlyChampion($user);

        return $earned;
    }

    public function checkMonthlyChampion(User $user): void
    {
        $top = Leaderboard::where('category', 'safety')
            ->where('period', 'monthly')
            ->orderByDesc('score_value')
            ->first();

        if ($top && $top->user_id === $user->id) {
            $this->award($user, 'monthly_champion');
        }
    }

    public function award(User $user, string $slug): bool
    {
        $achievement = Achievement::where('slug', $slug)->first();
        if (! $achievement || $user->achievements()->where('achievement_id', $achievement->id)->exists()) {
            return false;
        }

        $user->achievements()->attach($achievement->id, ['earned_at' => now()]);

        UserNotification::create([
            'user_id' => $user->id,
            'type' => 'badge_earned',
            'title' => 'New Badge Earned!',
            'body' => "You earned the {$achievement->name} badge.",
            'data' => ['achievement_slug' => $slug],
        ]);

        return true;
    }
}
