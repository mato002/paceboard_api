<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leaderboard;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function index(Request $request, SettingsService $settings)
    {
        $request->validate([
            'category' => 'required|in:distance,trips,score,safety',
            'period' => 'required|in:daily,weekly,monthly,yearly,all_time',
            'scope' => 'nullable|in:global,friends,national',
        ]);

        $scope = $request->scope ?? 'global';
        $user = $request->user();
        $minScore = (int) $settings->get('ranking_min_score', config('paceboard.ranking_min_score', 60));

        $query = Leaderboard::with('user:id,name,country')
            ->where('category', $request->category)
            ->where('period', $request->period);

        if (in_array($request->category, ['score', 'safety'])) {
            $query->where('score_value', '>=', $minScore);
        }

        if ($scope === 'friends') {
            $friendIds = $user->following()->pluck('users.id')->push($user->id);
            $query->whereIn('user_id', $friendIds);
        } elseif ($scope === 'national' && $user->country) {
            $query->whereHas('user', fn ($q) => $q->where('country', $user->country));
        }

        $leaderboard = $query->orderByDesc('score_value')
            ->take(100)
            ->get()
            ->values()
            ->map(function ($entry, $index) {
                $entry->rank_position = $entry->rank_position ?? ($index + 1);

                return $entry;
            });

        $myEntry = Leaderboard::where('user_id', $user->id)
            ->where('category', $request->category)
            ->where('period', $request->period)
            ->first();

        return response()->json([
            'entries' => $leaderboard,
            'my_rank' => $myEntry?->rank_position,
            'my_score' => $myEntry ? (float) $myEntry->score_value : null,
            'min_score_required' => in_array($request->category, ['score', 'safety']) ? $minScore : null,
        ]);
    }
}
