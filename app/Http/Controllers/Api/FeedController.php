<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\ChallengeParticipant;
use App\Models\CommunityReport;
use App\Models\Leaderboard;
use App\Models\Route;
use App\Models\SavedTrip;
use App\Models\Trip;
use App\Models\TripPhoto;
use App\Support\TripVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FeedController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 15;

        $followingIds = $user->following()->pluck('users.id')->push($user->id);

        $trips = TripVisibility::visibleToQuery($user)
            ->whereIn('user_id', $followingIds)
            ->with([
                'user:id,name,avatar_path,country,driver_status',
                'route:id,name,start_city,end_city',
                'vehicle:id,nickname,manufacturer,model',
                'photos' => fn ($q) => $q->latest()->limit(4),
            ])
            ->withCount(['likes', 'comments', 'photos'])
            ->latest('ended_at')
            ->take(20)
            ->get()
            ->map(fn (Trip $trip) => $this->formatTripFeedItem($trip, $user));

        $photoActivity = TripPhoto::query()
            ->whereIn('trip_id', TripVisibility::visibleToQuery($user)->select('id'))
            ->whereIn('user_id', $followingIds)
            ->with(['user:id,name,avatar_path', 'trip:id,name,start_city,end_city,user_id'])
            ->latest()
            ->take(10)
            ->get()
            ->groupBy('user_id')
            ->map(function ($photos) {
                $first = $photos->first();

                return [
                    'type' => 'photo_activity',
                    'user' => [
                        'id' => $first->user_id,
                        'name' => $first->user?->name,
                        'avatar_url' => $first->user?->avatar_url,
                    ],
                    'photo_count' => $photos->count(),
                    'photos' => $photos->take(4)->map(fn ($p) => [
                        'id' => $p->id,
                        'url' => Storage::disk('public')->url($p->path),
                        'trip_id' => $p->trip_id,
                    ])->values(),
                    'created_at' => $first->created_at?->toIso8601String(),
                ];
            })
            ->values();

        $alerts = CommunityReport::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->with('user:id,name,avatar_path')
            ->latest()
            ->take(8)
            ->get()
            ->map(fn ($r) => [
                'type' => 'road_alert',
                'id' => $r->id,
                'alert_type' => $r->type,
                'title' => $this->alertTitle($r->type),
                'road_name' => $r->road_name,
                'description' => $r->description,
                'latitude' => $r->latitude !== null ? (float) $r->latitude : null,
                'longitude' => $r->longitude !== null ? (float) $r->longitude : null,
                'created_at' => $r->created_at?->toIso8601String(),
                'expires_at' => $r->expires_at?->toIso8601String(),
                'minutes_ago' => (int) $r->created_at?->diffInMinutes(now()),
                'time_ago' => $this->timeAgo($r->created_at),
                'verification_score' => (int) ($r->verification_score ?? 0),
                'confirmations' => max(1, (int) round(((int) ($r->verification_score ?? 0)) / 5)),
                'reporter' => $r->user ? [
                    'id' => $r->user->id,
                    'name' => $r->user->name,
                    'avatar_url' => $r->user->avatar_url,
                ] : null,
            ]);

        $leaderboard = Leaderboard::query()
            ->where('category', 'score')
            ->where('period', 'monthly')
            ->orderBy('rank_position')
            ->with('user:id,name,avatar_path')
            ->take(5)
            ->get()
            ->map(fn ($row) => [
                'rank' => $row->rank_position,
                'user_id' => $row->user_id,
                'name' => $row->user?->name,
                'avatar_url' => $row->user?->avatar_url,
                'score' => (float) $row->score_value,
            ]);

        $weeklyChallenge = Challenge::query()
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->withCount('participants')
            ->orderByDesc('starts_at')
            ->first();

        $weeklyParticipant = null;
        if ($weeklyChallenge) {
            $weeklyParticipant = ChallengeParticipant::where('user_id', $user->id)
                ->where('challenge_id', $weeklyChallenge->id)
                ->first();
        }

        $trendingRoutes = Route::query()
            ->withAvg('trips', 'distance')
            ->withAvg('trips', 'duration_seconds')
            ->orderByDesc('total_trips')
            ->take(8)
            ->get(['id', 'name', 'start_city', 'end_city', 'total_trips', 'is_popular'])
            ->map(function (Route $route) {
                $distance = round((float) ($route->trips_avg_distance ?? 0), 1);
                $durationSec = (int) round((float) ($route->trips_avg_duration_seconds ?? 0));
                $drivers = max(1, (int) $route->total_trips);
                $difficulty = match (true) {
                    $distance >= 120 => 'Hard',
                    $distance >= 40 => 'Moderate',
                    default => 'Easy',
                };
                $rating = min(5.0, round(3.6 + min(1.4, $drivers / 40), 1));

                return [
                    'id' => $route->id,
                    'name' => $route->name ?? trim(($route->start_city ?? '').' → '.($route->end_city ?? '')),
                    'start_city' => $route->start_city,
                    'end_city' => $route->end_city,
                    'total_trips' => $drivers,
                    'is_popular' => (bool) $route->is_popular,
                    'distance_km' => $distance > 0 ? $distance : null,
                    'avg_duration_seconds' => $durationSec > 0 ? $durationSec : null,
                    'drivers_count' => $drivers,
                    'difficulty' => $difficulty,
                    'rating' => $rating,
                    'traffic' => $drivers > 30 ? 'Moderate' : 'Light',
                    'estimated_fuel_liters' => $distance > 0 ? round($distance / 12, 1) : null,
                ];
            });

        $items = collect()
            ->merge($trips->map(fn ($t) => ['type' => 'trip', ...$t]))
            ->merge($photoActivity)
            ->merge($alerts)
            ->sortByDesc(fn ($item) => $item['created_at'] ?? $item['ended_at'] ?? now()->toIso8601String())
            ->values()
            ->forPage($page, $perPage)
            ->values();

        return response()->json([
            'greeting' => $this->greeting(),
            'user_name' => $user->name,
            'items' => $items,
            'leaderboard' => $leaderboard,
            'weekly_challenge' => $weeklyChallenge
                ? \App\Support\ChallengePresenter::format($weeklyChallenge, $user, $weeklyParticipant)
                : null,
            'trending_routes' => $trendingRoutes,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'has_more' => $trips->count() >= $perPage,
            ],
        ]);
    }

    private function formatTripFeedItem(Trip $trip, $viewer): array
    {
        $liked = $viewer->likedTrips()->where('trip_id', $trip->id)->exists();
        $saved = SavedTrip::where('user_id', $viewer->id)->where('trip_id', $trip->id)->exists();

        return [
            'id' => $trip->id,
            'user' => [
                'id' => $trip->user_id,
                'name' => $trip->user?->name,
                'avatar_url' => $trip->user?->avatar_url,
                'country' => $trip->user?->country,
                'is_verified' => $trip->user?->driver_status === 'verified',
            ],
            'title' => $trip->name
                ?? $trip->route?->name
                ?? trim(($trip->start_city ?? 'Start').' → '.($trip->end_city ?? 'End')),
            'start_city' => $trip->start_city ?? $trip->route?->start_city,
            'end_city' => $trip->end_city ?? $trip->route?->end_city,
            'start_lat' => $trip->start_lat !== null ? (float) $trip->start_lat : null,
            'start_lng' => $trip->start_lng !== null ? (float) $trip->start_lng : null,
            'end_lat' => $trip->end_lat !== null ? (float) $trip->end_lat : null,
            'end_lng' => $trip->end_lng !== null ? (float) $trip->end_lng : null,
            'cover_photo_url' => $trip->photos->first()
                ? Storage::disk('public')->url($trip->photos->first()->path)
                : null,
            'photo_urls' => $trip->photos->map(fn ($p) => Storage::disk('public')->url($p->path))->values()->all(),
            'distance_km' => round((float) $trip->distance, 1),
            'duration_seconds' => $trip->duration_seconds,
            'average_speed' => round((float) $trip->average_speed, 1),
            'top_speed' => round((float) $trip->top_speed, 1),
            'score' => $trip->score,
            'fuel_estimate_liters' => $trip->fuel_estimate_liters !== null
                ? round((float) $trip->fuel_estimate_liters, 1)
                : null,
            'weather' => is_array($trip->weather) ? $trip->weather : null,
            'hazards_count' => 0,
            'likes_count' => $trip->likes_count ?? 0,
            'comments_count' => $trip->comments_count ?? 0,
            'photos_count' => $trip->photos_count ?? 0,
            'liked_by_me' => $liked,
            'saved_by_me' => $saved,
            'vehicle' => $trip->vehicle ? [
                'id' => $trip->vehicle->id,
                'label' => $trip->vehicle->nickname ?? trim(($trip->vehicle->manufacturer ?? '').' '.($trip->vehicle->model ?? '')),
                'type' => 'car',
            ] : null,
            'ended_at' => $trip->ended_at?->toIso8601String(),
            'created_at' => $trip->ended_at?->toIso8601String(),
        ];
    }

    private function greeting(): string
    {
        $hour = (int) now()->format('H');
        if ($hour < 12) {
            return 'Good morning';
        }
        if ($hour < 17) {
            return 'Good afternoon';
        }

        return 'Good evening';
    }

    private function alertTitle(string $type): string
    {
        return match ($type) {
            'speed_camera' => 'Speed camera',
            'pothole' => 'Pothole',
            'accident' => 'Accident',
            'traffic' => 'Traffic jam',
            'police' => 'Police check',
            'hazard' => 'Road hazard',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    private function timeAgo(?\Carbon\Carbon $at): string
    {
        if ($at === null) {
            return 'Recently';
        }

        $minutes = (int) $at->diffInMinutes(now());
        if ($minutes < 1) {
            return 'Just now';
        }
        if ($minutes < 60) {
            return "{$minutes} min ago";
        }

        $hours = (int) $at->diffInHours(now());
        if ($hours < 24) {
            return "{$hours}h ago";
        }

        $days = (int) $at->diffInDays(now());

        return "{$days}d ago";
    }
}
