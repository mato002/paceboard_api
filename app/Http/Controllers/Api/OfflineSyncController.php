<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessTripAnalytics;
use App\Models\Trip;
use App\Models\TripPoint;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OfflineSyncController extends Controller
{
    public function sync(Request $request)
    {
        $request->validate([
            'trips' => 'required|array|min:1|max:10',
            'trips.*.local_id' => 'required|string',
            'trips.*.server_trip_id' => 'nullable|integer',
            'trips.*.name' => 'nullable|string|max:255',
            'trips.*.start_city' => 'nullable|string|max:255',
            'trips.*.end_city' => 'nullable|string|max:255',
            'trips.*.started_at' => 'required|date',
            'trips.*.ended_at' => 'required|date',
            'trips.*.points' => 'required|array|max:10000',
            'trips.*.points.*.latitude' => 'required|numeric',
            'trips.*.points.*.longitude' => 'required|numeric',
            'trips.*.points.*.speed' => 'required|numeric|min:0',
            'trips.*.points.*.recorded_at' => 'required|date',
        ]);

        $results = [];

        DB::transaction(function () use ($request, &$results) {
            foreach ($request->trips as $tripData) {
                $startedAt = Carbon::parse($tripData['started_at'])->startOfSecond();
                $endedAt = Carbon::parse($tripData['ended_at'])->startOfSecond();
                if ($endedAt->lte($startedAt)) {
                    continue;
                }

                if (! empty($tripData['server_trip_id'])) {
                    $trip = $request->user()->trips()
                        ->whereKey($tripData['server_trip_id'])
                        ->first();
                    if (! $trip) {
                        continue;
                    }
                    if (! $trip->ended_at) {
                        $existingTimes = $trip->points()
                            ->pluck('recorded_at')
                            ->mapWithKeys(fn ($time) => [
                                Carbon::parse($time)->startOfSecond()->toDateTimeString() => true,
                            ]);
                        $unsyncedPoints = array_values(array_filter(
                            $tripData['points'],
                            fn ($point) => ! $existingTimes->has(
                                Carbon::parse($point['recorded_at'])->startOfSecond()->toDateTimeString()
                            )
                        ));
                        $points = array_map(fn ($p) => [
                            'trip_id' => $trip->id,
                            'latitude' => $p['latitude'],
                            'longitude' => $p['longitude'],
                            'speed' => $p['speed'],
                            'heading' => $p['heading'] ?? null,
                            'altitude' => $p['altitude'] ?? null,
                            'recorded_at' => Carbon::parse($p['recorded_at'])->startOfSecond(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ], $unsyncedPoints);
                        foreach (array_chunk($points, 100) as $chunk) {
                            TripPoint::insert($chunk);
                        }
                        $trip->update(['ended_at' => $endedAt, 'paused_at' => null]);
                        ProcessTripAnalytics::dispatch($trip);
                    }
                    $results[] = [
                        'local_id' => $tripData['local_id'],
                        'trip_id' => $trip->id,
                        'status' => 'synced',
                    ];
                    continue;
                }

                if (count($tripData['points']) < 2) {
                    continue;
                }

                // A client may retry after the server committed but its response
                // was lost. Matching the immutable timestamps makes retries
                // idempotent without exposing a local identifier publicly.
                $existing = $request->user()->trips()
                    ->where('started_at', $startedAt)
                    ->where('ended_at', $endedAt)
                    ->first();
                if ($existing) {
                    $results[] = [
                        'local_id' => $tripData['local_id'],
                        'trip_id' => $existing->id,
                        'status' => 'synced',
                    ];
                    continue;
                }

                $trip = $request->user()->trips()->create([
                    'name' => $tripData['name'] ?? 'Offline trip',
                    'start_city' => $tripData['start_city'] ?? null,
                    'end_city' => $tripData['end_city'] ?? null,
                    'started_at' => $startedAt,
                    'ended_at' => $endedAt,
                    'score' => 100,
                ]);

                $points = array_map(fn ($p) => [
                    'trip_id' => $trip->id,
                    'latitude' => $p['latitude'],
                    'longitude' => $p['longitude'],
                    'speed' => $p['speed'],
                    'heading' => $p['heading'] ?? null,
                    'altitude' => $p['altitude'] ?? null,
                    'recorded_at' => $p['recorded_at'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $tripData['points']);

                foreach (array_chunk($points, 100) as $chunk) {
                    TripPoint::insert($chunk);
                }

                ProcessTripAnalytics::dispatch($trip);

                $results[] = [
                    'local_id' => $tripData['local_id'],
                    'trip_id' => $trip->id,
                    'status' => 'synced',
                ];
            }
        });

        return response()->json([
            'message' => count($results).' trip(s) synced from offline storage',
            'results' => $results,
        ]);
    }
}
