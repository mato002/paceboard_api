<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        return response()->json($request->user()->vehicles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'manufacturer' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'year' => 'nullable|integer',
            'color' => 'nullable|string|max:50',
            'registration_number' => 'nullable|string|max:50',
            'fuel_type' => 'nullable|string|max:50',
        ]);

        $vehicle = $request->user()->vehicles()->create($validated);

        return response()->json($vehicle, 201);
    }

    public function serviceStatus(Request $request, Vehicle $vehicle)
    {
        if ($vehicle->user_id !== $request->user()->id) {
            abort(403);
        }

        $currentOdometer = (int) ($vehicle->mileage ?? 0);
        $lastService = (int) ($vehicle->last_service_odometer_km ?? 0);
        $interval = (int) ($vehicle->service_interval_km ?? 10000);
        $kmSinceService = $currentOdometer - $lastService;
        $kmUntilService = max(0, $interval - $kmSinceService);

        return response()->json([
            'vehicle_id' => $vehicle->id,
            'km_since_service' => $kmSinceService,
            'km_until_service' => $kmUntilService,
            'service_due' => $kmSinceService >= $interval,
            'last_service_at' => $vehicle->last_service_at,
        ]);
    }

    public function recordService(Request $request, Vehicle $vehicle)
    {
        if ($vehicle->user_id !== $request->user()->id) {
            abort(403);
        }

        $request->validate([
            'odometer_km' => 'required|integer|min:0',
        ]);

        $vehicle->update([
            'last_service_odometer_km' => $request->odometer_km,
            'last_service_at' => now(),
            'mileage' => $request->odometer_km,
        ]);

        return response()->json(['message' => 'Service recorded', 'vehicle' => $vehicle]);
    }

    public function show(Request $request, Vehicle $vehicle)
    {
        if ($vehicle->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return response()->json($vehicle);
    }

    public function destroy(Request $request, Vehicle $vehicle)
    {
        if ($vehicle->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $vehicle->delete();
        return response()->json(['message' => 'Vehicle deleted']);
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        if ($vehicle->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'manufacturer' => 'sometimes|string|max:255',
            'model' => 'sometimes|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'year' => 'nullable|integer',
            'color' => 'nullable|string|max:50',
            'registration_number' => 'nullable|string|max:50',
            'fuel_type' => 'nullable|string|max:50',
        ]);

        $vehicle->update($validated);

        return response()->json($vehicle);
    }
}
