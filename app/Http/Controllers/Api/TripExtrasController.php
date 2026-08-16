<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Services\WeatherService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TripExtrasController extends Controller
{
    public function share(Request $request, Trip $trip)
    {
        $this->authorize('share', $trip);

        if (! $trip->share_token) {
            $trip->update(['share_token' => Str::random(48)]);
        }

        $trip->load(['route', 'user:id,name,country', 'drivingAnalysis']);

        $baseUrl = config('paceboard.share_base_url');

        return response()->json([
            'share_url' => "{$baseUrl}/{$trip->share_token}",
            'share_text' => $this->buildShareText($trip),
            'trip' => [
                'id' => $trip->id,
                'name' => $trip->name,
                'route' => $trip->route?->name,
                'distance_km' => $trip->distance,
                'duration_seconds' => $trip->duration_seconds,
                'score' => $trip->score,
                'driver' => $trip->user?->name,
            ],
        ]);
    }

    public function showShared(string $token)
    {
        $trip = Trip::where('share_token', $token)
            ->whereNotNull('ended_at')
            ->with(['route', 'user:id,name,country', 'drivingAnalysis'])
            ->firstOrFail();

        return response()->json([
            'trip' => [
                'name' => $trip->name,
                'route' => $trip->route?->name,
                'start_city' => $trip->start_city,
                'end_city' => $trip->end_city,
                'distance_km' => $trip->distance,
                'duration_seconds' => $trip->duration_seconds,
                'average_speed' => $trip->average_speed,
                'score' => $trip->score,
                'driver' => $trip->user?->only(['name', 'country']),
                'analysis' => $trip->drivingAnalysis,
                'completed_at' => $trip->ended_at,
            ],
        ]);
    }

    public function weather(Request $request, Trip $trip, WeatherService $weather)
    {
        if ($trip->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($trip->weather) {
            return response()->json(['weather' => $trip->weather]);
        }

        $point = $trip->points()->orderBy('recorded_at')->first();
        if (! $point) {
            return response()->json(['message' => 'No GPS data for weather lookup'], 422);
        }

        $data = $weather->fetch((float) $point->latitude, (float) $point->longitude);

        if ($data) {
            $trip->update(['weather' => $data]);
        }

        return response()->json(['weather' => $data]);
    }

    public function analysis(Request $request, Trip $trip)
    {
        if ($trip->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json([
            'analysis' => $trip->drivingAnalysis,
        ]);
    }

    private function buildShareText(Trip $trip): string
    {
        $route = $trip->route?->name ?? ($trip->name ?? 'My trip');

        return sprintf(
            'I just completed %s — %.1f km in %d min with a safety score of %d on PaceBoard!',
            $route,
            $trip->distance,
            (int) round($trip->duration_seconds / 60),
            $trip->score
        );
    }
}
