<?php

namespace App\Jobs;

use App\Models\Trip;
use App\Models\UserNotification;
use App\Services\AchievementService;
use App\Services\ChallengeProgressService;
use App\Services\DrivingAnalysisService;
use App\Services\LeaderboardService;
use App\Services\RouteDetectionService;
use App\Services\RouteLeaderboardService;
use App\Services\SettingsService;
use App\Support\Geo;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessTripAnalytics implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public Trip $trip) {}

    public function handle(): void
    {
        $trip = $this->trip->fresh();
        $points = $trip->points()->orderBy('recorded_at')->get();

        if ($points->count() < 2) {
            return;
        }

        $user = $trip->user;
        $wasProcessed = $trip->analytics_processed_at !== null;

        if ($wasProcessed) {
            $user->decrement('total_distance', (float) ($trip->analytics_distance_applied ?? 0));
            $user->decrement('driving_hours', round(($trip->analytics_moving_seconds_applied ?? 0) / 3600, 4));
        }

        $totalDistance = 0.0;
        $maxSpeed = 0.0;
        $movingTime = 0;
        $stoppedTime = 0;
        $totalTime = 0;
        $previousPoint = null;

        foreach ($points as $point) {
            if ($point->speed > $maxSpeed) {
                $maxSpeed = $point->speed;
            }

            if ($previousPoint) {
                $timeDiff = abs($point->recorded_at->diffInSeconds($previousPoint->recorded_at));
                $totalTime += $timeDiff;

                $segmentDistance = Geo::haversineKm(
                    (float) $previousPoint->latitude,
                    (float) $previousPoint->longitude,
                    (float) $point->latitude,
                    (float) $point->longitude
                );

                if ($point->speed > 5 || $segmentDistance > 0.01) {
                    $movingTime += $timeDiff;
                    $totalDistance += $segmentDistance > 0 ? $segmentDistance : $point->speed * ($timeDiff / 3600);
                } else {
                    $stoppedTime += $timeDiff;
                }
            }

            $previousPoint = $point;
        }

        $totalTime = max(0, $totalTime - ($trip->total_paused_seconds ?? 0));
        $avgSpeed = $movingTime > 0 ? ($totalDistance / ($movingTime / 3600)) : 0;

        $score = 100;
        if ($maxSpeed > 120) {
            $score -= 10;
        }
        if ($maxSpeed > 140) {
            $score -= 20;
        }
        if ($avgSpeed > 0 && $avgSpeed < 80) {
            $score += 5;
        }

        $fuelConsumption = (float) app(SettingsService::class)->get(
            'fuel_consumption_per_100km',
            config('paceboard.fuel_consumption_per_100km', 8.5)
        );
        $fuelEstimate = round($totalDistance * $fuelConsumption / 100, 2);
        $distanceRounded = round($totalDistance, 2);

        $trip->update([
            'distance' => $distanceRounded,
            'duration_seconds' => $totalTime,
            'moving_time_seconds' => $movingTime,
            'stopped_time_seconds' => $stoppedTime,
            'average_speed' => round($avgSpeed, 2),
            'top_speed' => round($maxSpeed, 2),
            'score' => max(0, min(100, $score)),
            'fuel_estimate_liters' => $fuelEstimate,
            'analytics_processed_at' => now(),
            'analytics_distance_applied' => $distanceRounded,
            'analytics_moving_seconds_applied' => $movingTime,
        ]);

        $user->increment('total_distance', $distanceRounded);
        $user->increment('driving_hours', round($movingTime / 3600, 4));

        $route = app(RouteDetectionService::class)->assignRoute($trip->fresh());
        $trip->refresh();

        if ($route) {
            app(RouteLeaderboardService::class)->updateForTrip($trip);
        }

        app(LeaderboardService::class)->updateForUser($user->fresh());
        $analysis = app(DrivingAnalysisService::class)->analyze($trip);
        $trip->update(['score' => (int) round(($trip->score + $analysis->safety_score) / 2)]);

        app(AchievementService::class)->checkAfterTrip($trip->fresh());
        app(ChallengeProgressService::class)->updateAfterTrip($trip->fresh());

        if (! $wasProcessed) {
            UserNotification::create([
                'user_id' => $user->id,
                'type' => 'trip_completed',
                'title' => 'Trip Completed',
                'body' => sprintf(
                    'You drove %.1f km with a safety score of %d.',
                    $trip->distance,
                    $trip->score
                ),
                'data' => ['trip_id' => $trip->id],
            ]);
        }
    }
}
