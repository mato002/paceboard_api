<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommunityReport;
use App\Models\Leaderboard;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $todayStart = now()->startOfDay();

        $todayTrips = $user->trips()
            ->where('started_at', '>=', $todayStart)
            ->whereNotNull('ended_at')
            ->get();

        $distanceToday = round($todayTrips->sum('distance'), 2);
        $avgSpeedToday = $todayTrips->isNotEmpty()
            ? round($todayTrips->avg('average_speed'), 1)
            : 0;

        $activeTrip = $user->trips()
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        $myRank = Leaderboard::where('user_id', $user->id)
            ->where('category', 'score')
            ->where('period', 'monthly')
            ->first();

        $nearbyAlerts = [];
        if ($request->filled(['lat', 'lng'])) {
            $lat = (float) $request->lat;
            $lng = (float) $request->lng;

            $reports = CommunityReport::where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->get();

            $nearbyAlerts = $reports->map(function ($report) use ($lat, $lng) {
                $distanceKm = $this->haversineKm(
                    $lat,
                    $lng,
                    (float) $report->latitude,
                    (float) $report->longitude
                );

                return [
                    'id' => $report->id,
                    'type' => $report->type,
                    'title' => $this->reportTitle($report->type),
                    'road_name' => $report->road_name,
                    'description' => $report->description,
                    'distance_km' => round($distanceKm, 1),
                    'distance_label' => $this->formatDistance($distanceKm),
                ];
            })
            ->filter(fn ($r) => $r['distance_km'] <= 25)
            ->sortBy('distance_km')
            ->take(5)
            ->values()
            ->all();
        }

        $weekStart = now()->subDays(6)->startOfDay();
        $weekTrips = $user->trips()
            ->where('started_at', '>=', $weekStart)
            ->whereNotNull('ended_at')
            ->get(['distance', 'score', 'started_at']);

        $weeklyDistance = [];
        $weeklyScore = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->startOfDay();
            $dayKey = $day->toDateString();
            $dayTrips = $weekTrips->filter(fn ($t) => $t->started_at?->toDateString() === $dayKey);
            $weeklyDistance[] = [
                'date' => $dayKey,
                'label' => $day->format('D'),
                'km' => round($dayTrips->sum('distance'), 2),
            ];
            $weeklyScore[] = [
                'date' => $dayKey,
                'label' => $day->format('D'),
                'score' => $dayTrips->isNotEmpty() ? round($dayTrips->avg('score'), 1) : 0,
            ];
        }

        $monthStart = now()->startOfMonth();
        $monthTrips = $user->trips()
            ->where('started_at', '>=', $monthStart)
            ->whereNotNull('ended_at')
            ->get();

        $recentTrips = $user->trips()
            ->with('route:id,name,start_city,end_city')
            ->whereNotNull('ended_at')
            ->latest('ended_at')
            ->take(5)
            ->get(['id', 'name', 'route_id', 'distance', 'duration_seconds', 'score', 'started_at', 'ended_at']);

        $drivingTimeToday = $todayTrips->sum('duration_seconds');

        return response()->json([
            'greeting' => $this->greeting(),
            'user_name' => $user->name,
            'distance_today_km' => $distanceToday,
            'avg_speed_today_kmh' => $avgSpeedToday,
            'trips_today' => $todayTrips->count(),
            'driving_time_today_seconds' => (int) $drivingTimeToday,
            'total_distance_km' => (float) $user->total_distance,
            'driving_hours' => (float) $user->driving_hours,
            'score' => $activeTrip?->score ?? ($todayTrips->last()?->score ?? 100),
            'active_trip' => $activeTrip ? [
                'id' => $activeTrip->id,
                'name' => $activeTrip->name,
                'started_at' => $activeTrip->started_at,
                'is_paused' => $activeTrip->paused_at !== null,
            ] : null,
            'recent_trips' => $recentTrips,
            'monthly_stats' => [
                'distance_km' => round($monthTrips->sum('distance'), 2),
                'trips' => $monthTrips->count(),
                'avg_score' => $monthTrips->isNotEmpty() ? round($monthTrips->avg('score'), 1) : 0,
            ],
            'leaderboard_rank' => $myRank?->rank_position,
            'leaderboard_score' => $myRank ? (float) $myRank->score_value : null,
            'nearby_alerts' => $nearbyAlerts,
            'weekly_distance' => $weeklyDistance,
            'weekly_score' => $weeklyScore,
        ]);
    }

    private function greeting(): string
    {
        $hour = (int) now()->format('H');
        if ($hour < 12) {
            return 'Good Morning';
        }
        if ($hour < 17) {
            return 'Good Afternoon';
        }
        return 'Good Evening';
    }

    private function reportTitle(string $type): string
    {
        return match ($type) {
            'speed_camera' => 'Speed Camera',
            'pothole' => 'Pothole',
            'accident' => 'Accident',
            'traffic' => 'Heavy Traffic',
            'police' => 'Police Checkpoint',
            'hazard' => 'Road Hazard',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    private function formatDistance(float $km): string
    {
        if ($km < 1) {
            return round($km * 1000).'m away';
        }

        return round($km, 1).' km away';
    }

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
