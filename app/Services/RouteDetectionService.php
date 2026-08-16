<?php

namespace App\Services;

use App\Models\Route;
use App\Models\Trip;

class RouteDetectionService
{
    public function resolveCity(?float $lat, ?float $lng, ?string $provided = null): ?string
    {
        if ($provided) {
            return $this->normalizeCity($provided);
        }

        if ($lat === null || $lng === null) {
            return null;
        }

        $nearest = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach (config('kenya_cities', []) as $city => $coords) {
            $distance = $this->haversineKm($lat, $lng, $coords['lat'], $coords['lng']);
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearest = $city;
            }
        }

        return $minDistance <= 50 ? $nearest : null;
    }

    public function assignRoute(Trip $trip): ?Route
    {
        $points = $trip->points()->orderBy('recorded_at')->get(['latitude', 'longitude']);
        $first = $points->first();
        $last = $points->last();

        $startLat = $trip->start_lat ?? $first?->latitude;
        $startLng = $trip->start_lng ?? $first?->longitude;
        $endLat = $trip->end_lat ?? $last?->latitude;
        $endLng = $trip->end_lng ?? $last?->longitude;

        $startCity = $this->resolveCity(
            $startLat !== null ? (float) $startLat : null,
            $startLng !== null ? (float) $startLng : null,
            $trip->start_city ?? $trip->start_location
        );
        $endCity = $this->resolveCity(
            $endLat !== null ? (float) $endLat : null,
            $endLng !== null ? (float) $endLng : null,
            $trip->end_city ?? $trip->destination
        );

        if (! $startCity || ! $endCity || $startCity === $endCity) {
            $trip->update([
                'start_city' => $startCity,
                'end_city' => $endCity,
                'start_lat' => $startLat,
                'start_lng' => $startLng,
                'end_lat' => $endLat,
                'end_lng' => $endLng,
            ]);

            return null;
        }

        $name = "{$startCity} → {$endCity}";

        $route = Route::firstOrCreate(
            ['start_city' => $startCity, 'end_city' => $endCity],
            ['name' => $name, 'total_trips' => 0, 'is_popular' => false]
        );

        $route->increment('total_trips');
        if ($route->total_trips >= 10) {
            $route->update(['is_popular' => true]);
        }

        $trip->update([
            'route_id' => $route->id,
            'start_city' => $startCity,
            'end_city' => $endCity,
            'start_lat' => $startLat,
            'start_lng' => $startLng,
            'end_lat' => $endLat,
            'end_lng' => $endLng,
        ]);

        return $route;
    }

    private function normalizeCity(string $value): string
    {
        $value = trim($value);

        foreach (array_keys(config('kenya_cities', [])) as $city) {
            if (strcasecmp($value, $city) === 0) {
                return $city;
            }
        }

        return $value;
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
