<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leaderboard;
use App\Services\LeaderboardEnrichmentService;
use App\Services\SettingsService;
use Illuminate\Http\Request;

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
        $minScore = (int) $settings->get('ranking_min_score', config('paceboard.ranking_min_score', 60));

        $query = Leaderboard::with('user:id,name,country,avatar_path')
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
        foreach ($rawEntries as $index => $entry) {
            $entry->rank_position = $entry->rank_position ?? ($index + 1);
        }

        $entries = $rawEntries->map(fn ($entry) => $enrichment->enrichEntry($entry, $category, $period))->values();

        $myEntry = Leaderboard::where('user_id', $user->id)
            ->where('category', $category)
            ->where('period', $period)
            ->first();

        $myContext = $enrichment->myContext($user->id, $category, $period, $myEntry, $rawEntries);

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

        return response()->json([
            'period' => $period,
            'scope' => $scope,
            'winners' => $enrichment->winners($period, $scope, $request->user()),
        ]);
    }
}
