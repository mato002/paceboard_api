<?php

namespace App\Services;

use App\Models\Route;
use App\Models\RouteLeaderboard;
use App\Models\Trip;

class RouteLeaderboardService
{
    public function updateForTrip(Trip $trip): void
    {
        if (! $trip->route_id) {
            return;
        }

        $userId = $trip->user_id;
        $routeId = $trip->route_id;

        $this->upsertBest($routeId, $userId, 'fastest', (float) $trip->duration_seconds, ascending: true);
        $this->upsertBest($routeId, $userId, 'distance', (float) $trip->distance);
        $this->upsertBest($routeId, $userId, 'score', (float) $trip->score);

        $tripCount = Trip::where('route_id', $routeId)
            ->where('user_id', $userId)
            ->whereNotNull('ended_at')
            ->count();
        $this->upsert($routeId, $userId, 'trips', (float) $tripCount);

        $this->recalculateRanks($routeId);
    }

    private function upsertBest(int $routeId, int $userId, string $category, float $value, bool $ascending = false): void
    {
        $existing = RouteLeaderboard::where('route_id', $routeId)
            ->where('user_id', $userId)
            ->where('category', $category)
            ->first();

        if (! $existing) {
            RouteLeaderboard::create([
                'route_id' => $routeId,
                'user_id' => $userId,
                'category' => $category,
                'score_value' => $value,
            ]);

            return;
        }

        $isBetter = $ascending ? $value < $existing->score_value : $value > $existing->score_value;
        if ($isBetter) {
            $existing->update(['score_value' => $value]);
        }
    }

    private function upsert(int $routeId, int $userId, string $category, float $value): void
    {
        RouteLeaderboard::updateOrCreate(
            ['route_id' => $routeId, 'user_id' => $userId, 'category' => $category],
            ['score_value' => $value]
        );
    }

    private function recalculateRanks(int $routeId): void
    {
        foreach (['fastest', 'distance', 'score', 'trips'] as $category) {
            $ascending = $category === 'fastest';
            $query = RouteLeaderboard::where('route_id', $routeId)->where('category', $category);

            $entries = $ascending
                ? $query->orderBy('score_value')->get()
                : $query->orderByDesc('score_value')->get();

            foreach ($entries as $index => $entry) {
                $entry->update(['rank_position' => $index + 1]);
            }
        }
    }
}
