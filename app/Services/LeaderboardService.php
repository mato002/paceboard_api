<?php

namespace App\Services;

use App\Models\Leaderboard;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaderboardService
{
    public function __construct(private SettingsService $settings) {}

    public function updateForUser(User $user): void
    {
        $periods = ['daily', 'weekly', 'monthly', 'yearly', 'all_time'];
        $minScore = (int) $this->settings->get('ranking_min_score', config('paceboard.ranking_min_score', 60));

        foreach ($periods as $period) {
            $trips = $this->tripsInPeriod($user, $period)->get(['distance', 'score']);
            $communityScore = $this->communityScore($user, $period);

            if ($trips->isEmpty() && $communityScore <= 0) {
                Leaderboard::where('user_id', $user->id)->where('period', $period)->delete();

                continue;
            }

            if ($trips->isNotEmpty()) {
                $totalDistance = (float) $trips->sum('distance');
                $tripCount = $trips->count();
                $bestScore = (float) $trips->max('score');
                $avgScore = (float) $trips->avg('score');

                $this->upsert($user->id, 'distance', $period, $totalDistance);
                $this->upsert($user->id, 'trips', $period, $tripCount);

                if ($bestScore >= $minScore) {
                    $this->upsert($user->id, 'score', $period, $bestScore);
                } else {
                    $this->remove($user->id, 'score', $period);
                }

                if ($avgScore >= $minScore) {
                    $this->upsert($user->id, 'safety', $period, $avgScore);
                } else {
                    $this->remove($user->id, 'safety', $period);
                }
            } else {
                foreach (['distance', 'trips', 'score', 'safety'] as $cat) {
                    $this->remove($user->id, $cat, $period);
                }
            }

            if ($communityScore > 0) {
                $this->upsert($user->id, 'community', $period, $communityScore);
            } else {
                $this->remove($user->id, 'community', $period);
            }
        }

        $this->recalculateRanks();
    }

    public function recalculateRanks(): void
    {
        foreach (['daily', 'weekly', 'monthly', 'yearly', 'all_time'] as $period) {
            foreach (['distance', 'trips', 'score', 'safety', 'community'] as $category) {
                $entries = Leaderboard::where('category', $category)
                    ->where('period', $period)
                    ->orderByDesc('score_value')
                    ->get(['id']);

                foreach ($entries as $index => $entry) {
                    Leaderboard::whereKey($entry->id)->update(['rank_position' => $index + 1]);
                }
            }
        }
    }

    private function tripsInPeriod(User $user, string $period): HasMany
    {
        $query = $user->trips()->whereNotNull('ended_at');

        return match ($period) {
            'daily' => $query->whereDate('ended_at', today()),
            'weekly' => $query->where('ended_at', '>=', now()->startOfWeek()),
            'monthly' => $query->where('ended_at', '>=', now()->startOfMonth()),
            'yearly' => $query->where('ended_at', '>=', now()->startOfYear()),
            default => $query,
        };
    }

    private function upsert(int $userId, string $category, string $period, float $value): void
    {
        Leaderboard::updateOrCreate(
            ['user_id' => $userId, 'category' => $category, 'period' => $period],
            ['score_value' => $value]
        );
    }

    private function remove(int $userId, string $category, string $period): void
    {
        Leaderboard::where('user_id', $userId)
            ->where('category', $category)
            ->where('period', $period)
            ->delete();
    }

    private function communityScore(User $user, string $period): float
    {
        $query = $user->communityReports();

        $query = match ($period) {
            'daily' => $query->whereDate('created_at', today()),
            'weekly' => $query->where('created_at', '>=', now()->startOfWeek()),
            'monthly' => $query->where('created_at', '>=', now()->startOfMonth()),
            'yearly' => $query->where('created_at', '>=', now()->startOfYear()),
            default => $query,
        };

        $reports = $query->get(['confirmations_count', 'verification_score']);

        if ($reports->isEmpty()) {
            return 0;
        }

        return (float) (
            ($reports->count() * 10)
            + ($reports->sum('confirmations_count') * 5)
            + ($reports->where('verification_score', '>', 0)->count() * 15)
        );
    }
}
