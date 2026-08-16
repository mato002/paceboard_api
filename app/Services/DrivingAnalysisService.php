<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\DrivingAnalysis;
use App\Models\Trip;

class DrivingAnalysisService
{
    public function analyze(Trip $trip): DrivingAnalysis
    {
        $points = $trip->points()->orderBy('recorded_at')->get();
        $speedLimit = (int) AppSetting::getValue('speed_limit_kmh', config('paceboard.speed_limit_kmh', 80));

        $harshBraking = 0;
        $harshAcceleration = 0;
        $speedingEvents = 0;
        $speedChanges = [];
        $previous = null;

        foreach ($points as $point) {
            if ($point->speed > $speedLimit) {
                $speedingEvents++;
            }

            if ($previous) {
                $timeDiff = max(1, abs($point->recorded_at->diffInSeconds($previous->recorded_at)));
                $accel = ($point->speed - $previous->speed) / $timeDiff;

                if ($accel < -8) {
                    $harshBraking++;
                } elseif ($accel > 6) {
                    $harshAcceleration++;
                }

                $speedChanges[] = abs($point->speed - $previous->speed);
            }

            $previous = $point;
        }

        $avgSpeedChange = count($speedChanges) > 0 ? array_sum($speedChanges) / count($speedChanges) : 0;
        $smoothness = max(0, 100 - ($harshBraking * 8) - ($harshAcceleration * 6));
        $safety = max(0, 100 - ($speedingEvents * 5) - ($harshBraking * 5));
        $eco = max(0, 100 - (max(0, ($trip->average_speed ?? 0) - 90) * 2));
        $consistency = max(0, 100 - ($avgSpeedChange * 3));

        $insights = [];
        if ($harshBraking > 0) {
            $insights[] = "Detected {$harshBraking} harsh braking event(s). Try smoother stops.";
        }
        if ($harshAcceleration > 0) {
            $insights[] = "Detected {$harshAcceleration} harsh acceleration event(s).";
        }
        if ($speedingEvents > 0) {
            $insights[] = "Speed limit exceeded {$speedingEvents} time(s).";
        }
        if (empty($insights)) {
            $insights[] = 'Great drive! Smooth and safe driving detected.';
        }

        return DrivingAnalysis::updateOrCreate(
            ['trip_id' => $trip->id],
            [
                'safety_score' => (int) round($safety),
                'eco_score' => (int) round($eco),
                'smoothness_score' => (int) round($smoothness),
                'consistency_score' => (int) round($consistency),
                'harsh_braking_count' => $harshBraking,
                'harsh_acceleration_count' => $harshAcceleration,
                'speeding_events' => $speedingEvents,
                'insights' => $insights,
            ]
        );
    }
}
