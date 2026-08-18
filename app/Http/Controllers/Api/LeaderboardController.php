<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leaderboard;
use App\Services\LeaderboardEnrichmentService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LeaderboardController extends Controller
{
    public function index(Request $request, SettingsService $settings, LeaderboardEnrichmentService $enrichment)
    {
        $request->validate([
            'category' => 'required|in:distance,trips,score,safety,community',
            'period' => 'required|in:daily,weekly,monthly,yearly,all_time',
            'scope' => 'nullable|in:global,friends,national',
        ]);

        $scope = $request->scope ?? 'global';
        $user = $request->user();
        $category = $request->category;
        $period = $request->period;
        $minScore = 60;
        try {
            $minScore = (int) $settings->get('ranking_min_score', config('paceboard.ranking_min_score', 60));
        } catch (\Throwable $e) {
            Log::warning('Leaderboard settings lookup failed', ['error' => $e->getMessage()]);
        }

        try {
            $query = Leaderboard::with('user')
                ->where('category', $category)
                ->where('period', $period);

            if (in_array($category, ['score', 'safety'])) {
                $query->where('score_value', '>=', $minScore);
            }

            if ($scope === 'friends') {
                $friendIds = $user->following()->pluck('users.id')->push($user->id);
                $query->whereIn('user_id', $friendIds);
            } elseif ($scope === 'national' && $user->country) {
                $query->whereHas('user', fn ($q) => $q->where('country', $user->country));
            }

            $rawEntries = $query->orderByDesc('score_value')->take(100)->get()->values();
        } catch (\Throwable $e) {
            Log::error('Leaderboard query failed', ['error' => $e->getMessage()]);

            return response()->json([
                'category' => $category,
                'period' => $period,
                'scope' => $scope,
                'category_meta' => $enrichment->categoryMeta($category),
                'entries' => [],
                'my_rank' => null,
                'my_score' => null,
                'my_rank_delta' => null,
                'points_to_next_rank' => null,
                'next_rank' => null,
                'my_breakdown' => null,
                'min_score_required' => in_array($category, ['score', 'safety']) ? $minScore : null,
                'total_entries' => 0,
                'message' => 'Rankings are temporarily unavailable.',
            ]);
        }
        foreach ($rawEntries as $index => $entry) {
            $entry->rank_position = $entry->rank_position ?? ($index + 1);
        }

        $entries = $rawEntries->map(function ($entry) use ($enrichment, $category, $period) {
            try {
                return $enrichment->enrichEntry($entry, $category, $period);
            } catch (\Throwable) {
                return [
                    'id' => $entry->id,
                    'user_id' => $entry->user_id,
                    'category' => $entry->category,
                    'period' => $entry->period,
                    'rank_position' => $entry->rank_position,
                    'score_value' => round((float) $entry->score_value, 2),
                    'rank_delta' => null,
                    'user' => $entry->user ? [
                        'id' => $entry->user->id,
                        'name' => $entry->user->name,
                        'country' => $entry->user->country ?? null,
                        'avatar_url' => $entry->user->avatar_url ?? null,
                    ] : null,
                ];
            }
        })->values();

        $myEntry = Leaderboard::where('user_id', $user->id)
            ->where('category', $category)
            ->where('period', $period)
            ->first();

        try {
            $myContext = $enrichment->myContext($user->id, $category, $period, $myEntry, $rawEntries);
        } catch (\Throwable) {
            $myContext = [
                'my_rank' => $myEntry?->rank_position,
                'my_score' => $myEntry ? round((float) $myEntry->score_value, 2) : null,
                'my_rank_delta' => null,
                'points_to_next_rank' => null,
                'next_rank' => null,
                'my_breakdown' => null,
            ];
        }

        return response()->json([
            'category' => $category,
            'period' => $period,
            'scope' => $scope,
            'category_meta' => $enrichment->categoryMeta($category),
            'entries' => $entries,
            'my_rank' => $myContext['my_rank'],
            'my_score' => $myContext['my_score'],
            'my_rank_delta' => $myContext['my_rank_delta'],
            'points_to_next_rank' => $myContext['points_to_next_rank'],
            'next_rank' => $myContext['next_rank'],
            'my_breakdown' => $myContext['my_breakdown'],
            'min_score_required' => in_array($category, ['score', 'safety']) ? $minScore : null,
            'total_entries' => $entries->count(),
        ]);
    }

    public function winners(Request $request, LeaderboardEnrichmentService $enrichment)
    {
        $request->validate([
            'period' => 'required|in:daily,weekly,monthly,yearly,all_time',
            'scope' => 'nullable|in:global,friends,national',
        ]);

        $period = $request->period;
        $scope = $request->scope ?? 'global';

        try {
            $winners = $enrichment->winners($period, $scope, $request->user());
        } catch (\Throwable $e) {
            Log::error('Leaderboard winners failed', ['error' => $e->getMessage()]);
            $winners = [];
        }

        return response()->json([
            'period' => $period,
            'scope' => $scope,
            'winners' => $winners,
        ]);
    }
}
