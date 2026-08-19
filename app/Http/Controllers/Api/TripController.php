<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripPoint;
use App\Jobs\ProcessTripAnalytics;
use App\Support\TripVisibility;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TripController extends Controller
{
    public function start(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'name' => 'nullable|string|max:255',
            'start_location' => 'nullable|string|max:255',
            'start_city' => 'nullable|string|max:255',
            'start_lat' => 'nullable|numeric|between:-90,90',
            'start_lng' => 'nullable|numeric|between:-180,180',
        ]);

        $user = $request->user();

        if ($user->trips()->whereNull('ended_at')->exists()) {
            throw ValidationException::withMessages([
                'trip' => ['You already have an active trip. End it before starting a new one.'],
            ]);
        }

        if ($request->filled('vehicle_id')) {
            $ownsVehicle = $user->vehicles()->whereKey($request->vehicle_id)->exists();
            if (! $ownsVehicle) {
                throw ValidationException::withMessages([
                    'vehicle_id' => ['The selected vehicle does not belong to you.'],
                ]);
            }
        }

        $trip = $user->trips()->create([
            'vehicle_id' => $request->vehicle_id,
            'name' => $request->name,
            'start_location' => $request->start_location,
            'start_city' => $request->start_city,
            'start_lat' => $request->start_lat,
            'start_lng' => $request->start_lng,
            'started_at' => now(),
            'score' => 100,
        ]);

        return response()->json([
            'message' => 'Trip started successfully',
            'trip' => $trip,
        ]);
    }

    public function sync(Request $request, Trip $trip)
    {
        $this->authorizeTrip($request, $trip);

        if ($trip->ended_at !== null) {
            return response()->json(['message' => 'Trip has already ended'], 422);
        }

        $request->validate([
            'points' => 'required|array|min:1|max:100',
            'points.*.latitude' => 'required|numeric|between:-90,90',
            'points.*.longitude' => 'required|numeric|between:-180,180',
            'points.*.altitude' => 'nullable|numeric',
            'points.*.heading' => 'nullable|numeric',
            'points.*.accuracy' => 'nullable|numeric',
            'points.*.speed' => 'required|numeric|min:0|max:300',
            'points.*.recorded_at' => 'required|date',
        ]);

        $pointsData = array_map(function ($point) use ($trip) {
            // The Flutter app already sends speed in km/h (position.speed * 3.6).
            // No server-side conversion needed.
            $speed = (float) $point['speed'];

            return [
                'trip_id' => $trip->id,
                'latitude' => $point['latitude'],
                'longitude' => $point['longitude'],
                'altitude' => $point['altitude'] ?? null,
                'heading' => $point['heading'] ?? null,
                'accuracy' => $point['accuracy'] ?? null,
                'speed' => round($speed, 2),
                'recorded_at' => \Carbon\Carbon::parse($point['recorded_at']),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $request->points);

        TripPoint::insert($pointsData);

        return response()->json([
            'message' => count($pointsData).' points synced successfully',
        ]);
    }

    public function pause(Request $request, Trip $trip)
    {
        $this->authorizeTrip($request, $trip);

        if ($trip->ended_at !== null) {
            return response()->json(['message' => 'Trip has already ended'], 422);
        }

        if ($trip->isPaused()) {
            return response()->json(['message' => 'Trip is already paused', 'trip' => $trip]);
        }

        $trip->update(['paused_at' => now()]);

        return response()->json([
            'message' => 'Trip paused',
            'trip' => $trip->fresh(),
        ]);
    }

    public function resume(Request $request, Trip $trip)
    {
        $this->authorizeTrip($request, $trip);

        if ($trip->ended_at !== null) {
            return response()->json(['message' => 'Trip has already ended'], 422);
        }

        if (! $trip->isPaused()) {
            return response()->json(['message' => 'Trip is not paused', 'trip' => $trip]);
        }

        $pausedSeconds = abs(now()->diffInSeconds($trip->paused_at));
        $trip->update([
            'paused_at' => null,
            'total_paused_seconds' => ($trip->total_paused_seconds ?? 0) + $pausedSeconds,
        ]);

        return response()->json([
            'message' => 'Trip resumed',
            'trip' => $trip->fresh(),
        ]);
    }

    public function end(Request $request, Trip $trip)
    {
        $this->authorizeTrip($request, $trip);

        if ($trip->ended_at !== null) {
            return response()->json(['message' => 'Trip already ended', 'trip' => $trip]);
        }

        if ($trip->isPaused()) {
            $pausedSeconds = abs(now()->diffInSeconds($trip->paused_at));
            $trip->update([
                'paused_at' => null,
                'total_paused_seconds' => ($trip->total_paused_seconds ?? 0) + $pausedSeconds,
            ]);
        }

        $request->validate([
            'destination' => 'nullable|string|max:255',
            'end_city' => 'nullable|string|max:255',
            'end_lat' => 'nullable|numeric|between:-90,90',
            'end_lng' => 'nullable|numeric|between:-180,180',
            'visibility' => 'nullable|string|in:public,followers,private',
        ]);

        $trip->update([
            'destination' => $request->destination,
            'end_city' => $request->end_city,
            'end_lat' => $request->end_lat,
            'end_lng' => $request->end_lng,
            'ended_at' => now(),
            'visibility' => $request->input('visibility', $trip->visibility ?? 'public'),
        ]);

        ProcessTripAnalytics::dispatch($trip);
        $trip->refresh()->load('route');

        return response()->json([
            'message' => 'Trip ended and analytics processed',
            'trip' => $trip,
        ]);
    }

    public function index(Request $request)
    {
        $trips = $request->user()->trips()
            ->with('route:id,name,start_city,end_city')
            ->withCount(['likes', 'comments'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($trips);
    }

    public function active(Request $request)
    {
        $trip = $request->user()->trips()
            ->with('route:id,name,start_city,end_city')
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        return response()->json(['trip' => $trip]);
    }

    public function show(Request $request, Trip $trip)
    {
        if (! TripVisibility::canView($request->user(), $trip)) {
            abort(403, 'You cannot view this trip');
        }

        $trip->load(['route', 'user:id,name,country,avatar_path', 'vehicle:id,nickname,manufacturer,model'])
            ->loadCount(['likes', 'comments', 'photos']);

        $viewer = $request->user();
        $liked = $viewer
            ? $viewer->likedTrips()->where('trip_id', $trip->id)->exists()
            : false;
        $saved = $viewer
            ? \App\Models\SavedTrip::where('user_id', $viewer->id)->where('trip_id', $trip->id)->exists()
            : false;

        $points = $trip->points()
            ->orderBy('recorded_at')
            ->get(['id', 'latitude', 'longitude', 'speed', 'heading', 'recorded_at']);

        if ($points->count() > 800) {
            $step = (int) ceil($points->count() / 800);
            $points = $points->values()->filter(fn ($_, $i) => $i % $step === 0)->values();
        }

        return response()->json([
            'trip' => $trip,
            'points' => $points,
            'liked_by_me' => $liked,
            'saved_by_me' => $saved,
            'can_edit' => $request->user()?->id === $trip->user_id,
        ]);
    }

    public function update(Request $request, Trip $trip)
    {
        $this->authorizeTrip($request, $trip);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'visibility' => 'nullable|string|in:public,followers,private',
        ]);

        $trip->update($validated);

        return response()->json(['message' => 'Trip updated', 'trip' => $trip->fresh()]);
    }

    private function authorizeTrip(Request $request, Trip $trip): void
    {
        if ($trip->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }
    }
}
