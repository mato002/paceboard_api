<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leaderboard;
use App\Models\Trip;
use App\Models\User;
use App\Support\TripVisibility;
use Illuminate\Http\Request;

class ExploreController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $trendingTripCollection = TripVisibility::visibleToQuery($user)
            ->with(['user:id,name,avatar_path,country', 'route:id,name,start_city,end_city'])
            ->withCount(['likes', 'comments', 'photos'])
            ->orderByDesc('likes_count')
            ->orderByDesc('ended_at')
            ->take(10)
            ->get();

        $recentTripCollection = TripVisibility::visibleToQuery($user)
            ->with(['user:id,name,avatar_path'])
            ->withCount(['likes', 'photos'])
            ->latest('ended_at')
            ->take(10)
            ->get();

        // Batch-load liked trip IDs to avoid N+1 query inside formatTrip.
        $allTripIds = $trendingTripCollection->pluck('id')
            ->merge($recentTripCollection->pluck('id'))
            ->unique();
        $likedTripIds = $user->likedTrips()
            ->whereIn('trip_id', $allTripIds)
            ->pluck('trip_id')
            ->flip();

        $trendingTrips = $trendingTripCollection
            ->map(fn (Trip $trip) => $this->formatTrip($trip, $likedTripIds));

        $recentTrips = $recentTripCollection
            ->map(fn (Trip $trip) => $this->formatTrip($trip, $likedTripIds));

        $topDrivers = Leaderboard::query()
            ->where('category', 'score')
            ->where('period', 'monthly')
            ->orderBy('rank_position')
            ->with('user:id,name,avatar_path,country')
            ->take(10)
            ->get()
            ->map(fn ($row) => [
                'user_id' => $row->user_id,
                'name' => $row->user?->name,
                'avatar_url' => $row->user?->avatar_url,
                'country' => $row->user?->country,
                'rank' => $row->rank_position,
                'score' => (float) $row->score_value,
            ]);

        $newDrivers = User::query()
            ->where('id', '!=', $user->id)
            ->where(function ($q) {
                $q->whereNull('profile_visibility')->orWhere('profile_visibility', 'public');
            })
            ->orderByDesc('created_at')
            ->take(10)
            ->get(['id', 'name', 'avatar_path', 'country', 'created_at'])
            ->map(fn (User $u) => [
                'user_id' => $u->id,
                'name' => $u->name,
                'avatar_url' => $u->avatar_url,
                'country' => $u->country,
            ]);

        return response()->json([
            'trending_trips' => $trendingTrips,
            'recent_trips' => $recentTrips,
            'top_drivers' => $topDrivers,
            'new_drivers' => $newDrivers,
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int,mixed>  $likedTripIds  Pre-loaded set of liked trip IDs (flip'd for O(1) lookup)
     */
    private function formatTrip(Trip $trip, \Illuminate\Support\Collection $likedTripIds): array
    {
        return [
            'id' => $trip->id,
            'title' => $trip->name ?? trim(($trip->start_city ?? 'Start').' → '.($trip->end_city ?? 'End')),
            'start_city' => $trip->start_city,
            'end_city' => $trip->end_city,
            'distance_km' => round((float) $trip->distance, 1),
            'top_speed' => round((float) $trip->top_speed, 1),
            'likes_count' => $trip->likes_count ?? 0,
            'photos_count' => $trip->photos_count ?? 0,
            'user' => [
                'id' => $trip->user_id,
                'name' => $trip->user?->name,
                'avatar_url' => $trip->user?->avatar_url,
            ],
            'liked_by_me' => $likedTripIds->has($trip->id),
            'ended_at' => $trip->ended_at?->toIso8601String(),
        ];
    }
}
