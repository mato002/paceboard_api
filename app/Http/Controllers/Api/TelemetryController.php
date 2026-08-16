<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\VehicleTelemetry;
use Illuminate\Http\Request;

class TelemetryController extends Controller
{
    public function sync(Request $request, Trip $trip)
    {
        if ($trip->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($trip->ended_at !== null) {
            return response()->json(['message' => 'Trip has ended'], 422);
        }

        $request->validate([
            'readings' => 'required|array|min:1|max:50',
            'readings.*.rpm' => 'nullable|integer|min:0|max:10000',
            'readings.*.engine_temp' => 'nullable|numeric',
            'readings.*.fuel_level_percent' => 'nullable|numeric|min:0|max:100',
            'readings.*.throttle_position' => 'nullable|numeric|min:0|max:100',
            'readings.*.dtc_codes' => 'nullable|string|max:255',
            'readings.*.recorded_at' => 'required|date',
        ]);

        $rows = array_map(fn ($r) => [
            'trip_id' => $trip->id,
            'vehicle_id' => $trip->vehicle_id,
            'rpm' => $r['rpm'] ?? null,
            'engine_temp' => $r['engine_temp'] ?? null,
            'fuel_level_percent' => $r['fuel_level_percent'] ?? null,
            'throttle_position' => $r['throttle_position'] ?? null,
            'dtc_codes' => $r['dtc_codes'] ?? null,
            'recorded_at' => $r['recorded_at'],
            'created_at' => now(),
            'updated_at' => now(),
        ], $request->readings);

        VehicleTelemetry::insert($rows);

        return response()->json(['message' => count($rows).' telemetry readings synced']);
    }

    public function index(Request $request, Trip $trip)
    {
        if ($trip->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json([
            'telemetry' => $trip->telemetry()->orderBy('recorded_at')->paginate(100),
        ]);
    }
}
