<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $trips = $user->trips()->whereNotNull('ended_at')->get(['distance', 'duration_seconds', 'average_speed', 'started_at']);

        $hourly = array_fill(0, 24, 0);
        foreach ($trips as $trip) {
            if ($trip->started_at) {
                $hourly[(int) $trip->started_at->format('G')]++;
            }
        }
        $peakHour = array_keys($hourly, max($hourly))[0] ?? 0;

        return response()->json([
            'daily_distance_km' => round($trips->filter(fn ($t) => $t->started_at?->isToday())->sum('distance'), 2),
            'weekly_distance_km' => round($trips->filter(fn ($t) => $t->started_at?->gte(now()->subDays(7)))->sum('distance'), 2),
            'monthly_distance_km' => round($trips->filter(fn ($t) => $t->started_at?->gte(now()->startOfMonth()))->sum('distance'), 2),
            'yearly_distance_km' => round($trips->filter(fn ($t) => $t->started_at?->gte(now()->startOfYear()))->sum('distance'), 2),
            'driving_hours' => round($trips->sum('duration_seconds') / 3600, 2),
            'peak_driving_hour' => $peakHour,
            'hourly_distribution' => collect($hourly)->map(fn ($count, $hour) => [
                'hour' => $hour,
                'trips' => $count,
            ])->values(),
            'avg_speed_trend' => $this->monthlySpeedTrend($user),
            'top_routes' => $user->trips()
                ->whereNotNull('route_id')
                ->with('route:id,name')
                ->select('route_id', DB::raw('count(*) as trip_count'), DB::raw('sum(distance) as total_distance'))
                ->groupBy('route_id')
                ->orderByDesc('total_distance')
                ->take(5)
                ->get(),
        ]);
    }

    private function monthlySpeedTrend(User $user): array
    {
        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $avg = $user->trips()
                ->whereNotNull('ended_at')
                ->whereYear('started_at', $month->year)
                ->whereMonth('started_at', $month->month)
                ->avg('average_speed');

            $trend[] = [
                'month' => $month->format('Y-m'),
                'label' => $month->format('M Y'),
                'avg_speed' => round((float) $avg, 1),
            ];
        }

        return $trend;
    }
}
