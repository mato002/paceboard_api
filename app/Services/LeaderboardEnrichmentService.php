<?php

namespace App\Services;

use App\Models\CommunityReport;
use App\Models\DrivingAnalysis;
use App\Models\Leaderboard;
use App\Models\Trip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LeaderboardEnrichmentService
{
    public function categoryMeta(string $category): array
    {
        return match ($category) {
            'safety' => [
                'label' => 'Safety',
                'description' => 'Average safety score from completed trips. Higher smooth driving and speed compliance raise your rank.',
                'unit' => 'pts',
            ],
            'distance' => [
                'label' => 'Distance',
                'description' => 'Total kilometers driven in the selected period.',
                'unit' => 'km',
            ],
            'trips' => [
                'label' => 'Trips',
                'description' => 'Number of completed trips in the selected period.',
                'unit' => 'trips',
            ],
            'community' => [
                'label' => 'Community',
                'description' => 'Points from hazard reports, confirmations, and helpful votes from other drivers.',
                'unit' => 'pts',
            ],
            'score' => [
                'label' => 'Best Score',
                'description' => 'Your highest single-trip score in the period.',
                'unit' => 'pts',
            ],
            default => ['label' => ucfirst($category), 'description' => '', 'unit' => 'pts'],
        };
    }

    public function enrichEntry(Leaderboard $entry, string $category, string $period): array
    {
        $user = $entry->user;
        $stats = $user ? $this->periodStats($user, $period) : [];

        return [
            'id' => $entry->id,
            'user_id' => $entry->user_id,
            'category' => $entry->category,
            'period' => $entry->period,
            'rank_position' => $entry->rank_position,
            'score_value' => round((float) $entry->score_value, 2),
            'rank_delta' => $user ? $this->rankDelta($user->id, $category, $period, (int) $entry->rank_position) : null,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'country' => $user->country,
                'avatar_url' => $user->avatar_url,
                'period_distance_km' => $stats['distance_km'],
                'period_trips' => $stats['trips'],
                'period_safety_score' => $stats['safety_score'],
                'period_drive_hours' => $stats['drive_hours'],
                'community_reports' => $stats['community_reports'],
                'community_confirmations' => $stats['community_confirmations'],
                'community_helpful' => $stats['community_helpful'],
            ] : null,
        ];
    }

    public function myContext(int $userId, string $category, string $period, ?Leaderboard $myEntry, Collection $entries): array
    {
        $context = [
            'my_rank' => $myEntry?->rank_position,
            'my_score' => $myEntry ? round((float) $myEntry->score_value, 2) : null,
            'my_rank_delta' => $myEntry ? $this->rankDelta($userId, $category, $period, (int) $myEntry->rank_position) : null,
            'points_to_next_rank' => null,
            'next_rank' => null,
            'my_breakdown' => null,
        ];

        if (! $myEntry) {
            return $context;
        }

        $myRank = (int) $myEntry->rank_position;
        if ($myRank > 1) {
            $above = $entries->first(fn ($e) => (int) $e->rank_position === $myRank - 1);
            if ($above) {
                $context['points_to_next_rank'] = round(max(0, (float) $above->score_value - (float) $myEntry->score_value), 2);
                $context['next_rank'] = $myRank - 1;
            }
        }

        if (in_array($category, ['safety', 'score'], true)) {
            $user = User::find($userId);
            if ($user) {
                $context['my_breakdown'] = $this->safetyBreakdown($user, $period);
            }
        }

        if ($category === 'community') {
            $user = User::find($userId);
            if ($user) {
                $context['my_breakdown'] = $this->communityBreakdown($user, $period);
            }
        }

        if ($category === 'distance') {
            $user = User::find($userId);
            if ($user) {
                $stats = $this->periodStats($user, $period);
                $context['my_breakdown'] = [
                    'distance_km' => $stats['distance_km'],
                    'trips' => $stats['trips'],
                    'drive_hours' => $stats['drive_hours'],
                ];
            }
        }

        if ($category === 'trips') {
            $user = User::find($userId);
            if ($user) {
                $stats = $this->periodStats($user, $period);
                $context['my_breakdown'] = [
                    'trips' => $stats['trips'],
                    'distance_km' => $stats['distance_km'],
                    'drive_hours' => $stats['drive_hours'],
                ];
            }
        }

        return $context;
    }

    public function winners(string $period, string $scope, User $viewer): array
    {
        $categories = [
            'distance' => ['label' => 'Most Distance', 'icon' => 'route', 'category' => 'distance'],
            'trips' => ['label' => 'Most Trips', 'icon' => 'car', 'category' => 'trips'],
            'reporter' => ['label' => 'Best Reporter', 'icon' => 'report', 'category' => 'community'],
            'helpful' => ['label' => 'Most Helpful', 'icon' => 'thumb_up', 'category' => 'community_helpful'],
        ];

        $result = [];
        foreach ($categories as $key => $meta) {
            $winner = match ($key) {
                'helpful' => $this->topHelpfulReporter($period, $scope, $viewer),
                'reporter' => $this->topCommunityReporter($period, $scope, $viewer),
                default => $this->topLeaderboardEntry($meta['category'], $period, $scope, $viewer),
            };

            $result[$key] = array_merge($meta, $winner ?? ['entry' => null]);
        }

        return $result;
    }

    private function topLeaderboardEntry(string $category, string $period, string $scope, User $viewer): ?array
    {
        $query = Leaderboard::with('user:id,name,country,avatar_path')
            ->where('category', $category)
            ->where('period', $period)
            ->orderByDesc('score_value');

        $this->applyScope($query, $scope, $viewer);

        $entry = $query->first();
        if (! $entry) {
            return null;
        }

        return ['entry' => $this->enrichEntry($entry, $category, $period)];
    }

    private function topCommunityReporter(string $period, string $scope, User $viewer): ?array
    {
        [$start, $end] = $this->periodBounds($period);
        $scores = $this->communityScoresByUser($start, $end, $scope, $viewer, 'reports');
        if ($scores->isEmpty()) {
            return null;
        }

        return $this->winnerFromScores($scores, 'community', $period);
    }

    private function topHelpfulReporter(string $period, string $scope, User $viewer): ?array
    {
        [$start, $end] = $this->periodBounds($period);
        $scores = $this->communityScoresByUser($start, $end, $scope, $viewer, 'helpful');
        if ($scores->isEmpty()) {
            return null;
        }

        return $this->winnerFromScores($scores, 'community', $period);
    }

    private function winnerFromScores(Collection $scores, string $category, string $period): array
    {
        $top = $scores->sortDesc()->keys()->first();
        $user = User::find($top);
        $value = $scores[$top];

        return [
            'entry' => [
                'rank_position' => 1,
                'score_value' => round((float) $value, 2),
                'user' => $user ? array_merge([
                    'id' => $user->id,
                    'name' => $user->name,
                    'country' => $user->country,
                    'avatar_url' => $user->avatar_url,
                ], $this->periodStats($user, $period)) : null,
            ],
        ];
    }

    private function communityScoresByUser(Carbon $start, Carbon $end, string $scope, User $viewer, string $mode): Collection
    {
        $query = CommunityReport::query()
            ->whereBetween('created_at', [$start, $end]);

        if ($scope === 'friends') {
            $friendIds = $viewer->following()->pluck('users.id')->push($viewer->id);
            $query->whereIn('user_id', $friendIds);
        } elseif ($scope === 'national' && $viewer->country) {
            $query->whereHas('user', fn ($q) => $q->where('country', $viewer->country));
        }

        $reports = $query->get(['user_id', 'confirmations_count']);

        return $reports->groupBy('user_id')->map(function ($group) use ($mode) {
            return match ($mode) {
                'helpful' => (float) $group->sum('confirmations_count'),
                default => (float) $group->count(),
            };
        });
    }

    private function applyScope($query, string $scope, User $viewer): void
    {
        if ($scope === 'friends') {
            $friendIds = $viewer->following()->pluck('users.id')->push($viewer->id);
            $query->whereIn('user_id', $friendIds);
        } elseif ($scope === 'national' && $viewer->country) {
            $query->whereHas('user', fn ($q) => $q->where('country', $viewer->country));
        }
    }

    public function periodStats(User $user, string $period): array
    {
        [$start, $end] = $this->periodBounds($period);

        $trips = Trip::query()
            ->where('user_id', $user->id)
            ->whereNotNull('ended_at')
            ->whereBetween('ended_at', [$start, $end])
            ->get(['distance', 'score', 'duration_seconds']);

        $reports = CommunityReport::query()
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->get(['confirmations_count', 'verification_score']);

        $driveSeconds = (int) $trips->sum('duration_seconds');

        return [
            'distance_km' => round((float) $trips->sum('distance'), 1),
            'trips' => $trips->count(),
            'safety_score' => $trips->isNotEmpty() ? round((float) $trips->avg('score'), 1) : null,
            'drive_hours' => round($driveSeconds / 3600, 1),
            'community_reports' => $reports->count(),
            'community_confirmations' => (int) $reports->sum('confirmations_count'),
            'community_helpful' => (int) $reports->sum('confirmations_count'),
        ];
    }

    public function safetyBreakdown(User $user, string $period): ?array
    {
        [$start, $end] = $this->periodBounds($period);

        $tripIds = Trip::query()
            ->where('user_id', $user->id)
            ->whereNotNull('ended_at')
            ->whereBetween('ended_at', [$start, $end])
            ->pluck('id');

        if ($tripIds->isEmpty()) {
            return null;
        }

        $analyses = DrivingAnalysis::query()->whereIn('trip_id', $tripIds)->get();
        if ($analyses->isEmpty()) {
            return null;
        }

        $speeding = (int) $analyses->sum('speeding_events');
        $tripCount = max(1, $analyses->count());

        return [
            'safety_score' => round((float) $analyses->avg('safety_score'), 1),
            'smooth_driving' => round((float) $analyses->avg('smoothness_score'), 1),
            'speed_compliance' => max(0, round(100 - (($speeding / $tripCount) * 5), 1)),
            'hard_braking' => max(0, round(100 - (((int) $analyses->sum('harsh_braking_count') / $tripCount) * 8), 1)),
            'rapid_acceleration' => max(0, round(100 - (((int) $analyses->sum('harsh_acceleration_count') / $tripCount) * 6), 1)),
            'driving_consistency' => round((float) $analyses->avg('consistency_score'), 1),
            'trip_count' => $tripCount,
        ];
    }

    public function communityBreakdown(User $user, string $period): ?array
    {
        [$start, $end] = $this->periodBounds($period);

        $reports = CommunityReport::query()
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        if ($reports->isEmpty()) {
            return null;
        }

        $confirmed = $reports->where('confirmations_count', '>', 0)->count();
        $accuracy = $reports->count() > 0
            ? round(($confirmed / $reports->count()) * 100, 0)
            : 0;

        return [
            'reports_submitted' => $reports->count(),
            'reports_confirmed' => $confirmed,
            'drivers_helped' => (int) $reports->sum('confirmations_count'),
            'useful_reports' => $reports->where('verification_score', '>', 0)->count(),
            'report_accuracy_pct' => $accuracy,
        ];
    }

    public function rankDelta(int $userId, string $category, string $period, int $currentRank): ?int
    {
        $previousRank = $this->calculateRankForPreviousPeriod($userId, $category, $period);
        if ($previousRank === null) {
            return null;
        }

        return $previousRank - $currentRank;
    }

    private function calculateRankForPreviousPeriod(int $userId, string $category, string $period): ?int
    {
        [$prevStart, $prevEnd] = $this->previousPeriodBounds($period);
        $scores = $this->scoresForWindow($category, $prevStart, $prevEnd);

        if ($scores->isEmpty() || ! $scores->has($userId)) {
            return null;
        }

        $sorted = $scores->sortDesc()->keys()->values();
        $position = $sorted->search($userId);

        return $position === false ? null : $position + 1;
    }

    private function scoresForWindow(string $category, Carbon $start, Carbon $end): Collection
    {
        if ($category === 'community') {
            return CommunityReport::query()
                ->whereBetween('created_at', [$start, $end])
                ->get(['user_id', 'confirmations_count'])
                ->groupBy('user_id')
                ->map(fn ($group) => ($group->count() * 10) + ($group->sum('confirmations_count') * 5));
        }

        return Trip::query()
            ->whereNotNull('ended_at')
            ->whereBetween('ended_at', [$start, $end])
            ->get(['user_id', 'distance', 'score'])
            ->groupBy('user_id')
            ->map(function ($trips) use ($category) {
                return match ($category) {
                    'distance' => (float) $trips->sum('distance'),
                    'trips' => (float) $trips->count(),
                    'safety' => (float) $trips->avg('score'),
                    'score' => (float) $trips->max('score'),
                    default => 0,
                };
            });
    }

    public function periodBounds(string $period): array
    {
        return match ($period) {
            'daily' => [now()->startOfDay(), now()->endOfDay()],
            'weekly' => [now()->startOfWeek(), now()->endOfWeek()],
            'monthly' => [now()->startOfMonth(), now()->endOfMonth()],
            'yearly' => [now()->startOfYear(), now()->endOfYear()],
            default => [Carbon::createFromTimestamp(0), now()],
        };
    }

    private function previousPeriodBounds(string $period): array
    {
        return match ($period) {
            'daily' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'weekly' => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'monthly' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'yearly' => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            default => [Carbon::createFromTimestamp(0), now()->subYear()],
        };
    }
}
